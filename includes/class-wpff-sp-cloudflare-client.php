<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WPFF_SP_Cloudflare_Client {

	const API_BASE = 'https://api.cloudflare.com/client/v4';

	/**
	 * Log a message to the plugin's log file, prefixed with the API context.
	 *
	 * @param string $context Short label identifying which call this came from.
	 * @param string $message The message to log.
	 */
	private static function log( $context, $message ) {
		WPFF_SP_Helpers::log( sprintf( '[Cloudflare API] %s: %s', $context, $message ) );
	}

	/**
	 * Build the wp_remote_* args array for an authenticated Cloudflare API request.
	 *
	 * @param string $token         Cloudflare API token.
	 * @param array  $extra_headers Additional headers to merge in (e.g. a multipart Content-Type).
	 * @return array
	 */
	private static function get_api_auth_args( $token, $extra_headers = array() ) {
		return array(
			'timeout' => 30,
			'headers' => array_merge(
				array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				$extra_headers
			),
		);
	}

	/**
	 * Check whether a Cloudflare API response succeeded, logging and populating
	 * $error on failure.
	 *
	 * @param array|WP_Error $response The response from wp_remote_*().
	 * @param string          $context The calling method name, for logging.
	 * @param string          $error   Reference; populated with a human-readable error message on failure.
	 * @return array|false Decoded response body on success, false on failure.
	 */
	private static function is_success_api_response( $response, $context, &$error ) {
		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			self::log( $context, 'Connection error: ' . $error );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			$messages = array();

			if ( is_array( $body ) && ! empty( $body['errors'] ) ) {
				foreach ( $body['errors'] as $api_error ) {
					if ( ! empty( $api_error['message'] ) ) {
						$messages[] = $api_error['message'];
					}
				}
			}

			$error = ! empty( $messages )
				? implode( ' ', $messages )
				: __( 'Unknown Cloudflare API error.', 'super-preloader-for-cloudflare' );

			self::log( $context, 'API error: ' . $error );
			return false;
		}

		return $body;
	}

	/**
	 * Build a raw multipart/form-data request body from a list of parts.
	 *
	 * @param array  $parts    List of parts, each with name/content_type/content and optional filename.
	 * @param string $boundary The multipart boundary string.
	 * @return string
	 */
	private static function build_multipart_body( $parts, $boundary ) {
		$body = '';

		foreach ( $parts as $part ) {
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="' . $part['name'] . '"';

			if ( ! empty( $part['filename'] ) ) {
				$body .= '; filename="' . $part['filename'] . '"';
			}

			$body .= "\r\n";
			$body .= 'Content-Type: ' . $part['content_type'] . "\r\n\r\n";
			$body .= $part['content'] . "\r\n";
		}

		$body .= '--' . $boundary . "--\r\n";

		return $body;
	}

	/**
	 * List the Cloudflare accounts visible to the given API token.
	 *
	 * @param string $token Cloudflare API token.
	 * @param string $error Reference; populated with the error message on failure.
	 * @return array|false List of ['id' => ..., 'name' => ...] entries, or false on failure.
	 */
	public static function get_account_ids( $token, &$error = '' ) {
		$accounts    = array();
		$page        = 1;
		$total_pages = 1;

		do {
			$url      = self::API_BASE . '/accounts?page=' . $page . '&per_page=50';
			$response = wp_remote_get( $url, self::get_api_auth_args( $token ) );
			$body     = self::is_success_api_response( $response, 'get_account_ids', $error );

			if ( false === $body ) {
				return false;
			}

			if ( ! empty( $body['result'] ) && is_array( $body['result'] ) ) {
				foreach ( $body['result'] as $account ) {
					if ( isset( $account['id'], $account['name'] ) ) {
						$accounts[] = array(
							'id'   => $account['id'],
							'name' => $account['name'],
						);
					}
				}
			}

			if ( ! empty( $body['result_info']['total_pages'] ) ) {
				$total_pages = (int) $body['result_info']['total_pages'];
			}

			++$page;
		} while ( $page <= $total_pages );

		return $accounts;
	}

	/**
	 * Deploy the bundled Worker script to the given Cloudflare account, bind the
	 * shared secret, enable its workers.dev route, and return the resulting URL.
	 *
	 * @param string $token       Cloudflare API token.
	 * @param string $account_id  Cloudflare account ID to deploy under.
	 * @param string $script_name Name for the Worker script (must be unique within the account).
	 * @param string $secret      Value to bind as the Worker's SECRET environment variable.
	 * @param string $error       Reference; populated with the error message on failure.
	 * @return string|false The deployed https://{script}.{subdomain}.workers.dev URL, or false on failure.
	 */
	public static function deploy_worker( $token, $account_id, $script_name, $secret, &$error = '' ) {
		$script_path = WPFF_SP_PLUGIN_PATH . 'data/worker-script.js';

		if ( ! file_exists( $script_path ) ) {
			$error = __( 'Bundled Worker script template is missing.', 'super-preloader-for-cloudflare' );
			return false;
		}

		$script_content = file_get_contents( $script_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a bundled local plugin asset, not a remote URL.

		$metadata = array(
			'main_module'        => 'worker.js',
			'compatibility_date' => gmdate( 'Y-m-d' ),
			'bindings'           => array(
				array(
					'name' => 'SECRET',
					'type' => 'secret_text',
					'text' => $secret,
				),
			),
		);

		$boundary = wp_generate_password( 24, false );

		$body = self::build_multipart_body(
			array(
				array(
					'name'         => 'metadata',
					'content_type' => 'application/json',
					'content'      => wp_json_encode( $metadata ),
				),
				array(
					'name'         => 'worker.js',
					'filename'     => 'worker.js',
					'content_type' => 'application/javascript+module',
					'content'      => $script_content,
				),
			),
			$boundary
		);

		$deploy_url  = self::API_BASE . '/accounts/' . rawurlencode( $account_id ) . '/workers/scripts/' . rawurlencode( $script_name );
		$deploy_args = self::get_api_auth_args( $token, array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ) );

		$deploy_args['method'] = 'PUT';
		$deploy_args['body']   = $body;

		self::log( 'deploy_worker', sprintf( 'Deploying Worker script "%s" to account %s', $script_name, $account_id ) );

		$deploy_response = wp_remote_request( $deploy_url, $deploy_args );

		if ( false === self::is_success_api_response( $deploy_response, 'deploy_worker', $error ) ) {
			return false;
		}

		// Enable the workers.dev subdomain route for this script.
		$subdomain_enable_url            = self::API_BASE . '/accounts/' . rawurlencode( $account_id ) . '/workers/scripts/' . rawurlencode( $script_name ) . '/subdomain';
		$subdomain_enable_args           = self::get_api_auth_args( $token );
		$subdomain_enable_args['method'] = 'POST';
		$subdomain_enable_args['body']   = wp_json_encode( array( 'enabled' => true ) );

		$subdomain_enable_response = wp_remote_request( $subdomain_enable_url, $subdomain_enable_args );

		if ( false === self::is_success_api_response( $subdomain_enable_response, 'enable_subdomain', $error ) ) {
			return false;
		}

		// Look up the account's workers.dev subdomain name to build the full URL.
		$subdomain_lookup_url      = self::API_BASE . '/accounts/' . rawurlencode( $account_id ) . '/workers/subdomain';
		$subdomain_lookup_response = wp_remote_get( $subdomain_lookup_url, self::get_api_auth_args( $token ) );
		$subdomain_lookup_body     = self::is_success_api_response( $subdomain_lookup_response, 'get_subdomain', $error );

		if ( false === $subdomain_lookup_body || empty( $subdomain_lookup_body['result']['subdomain'] ) ) {
			$error = __( 'Worker deployed, but could not determine your workers.dev subdomain.', 'super-preloader-for-cloudflare' );
			return false;
		}

		return sprintf( 'https://%s.%s.workers.dev', $script_name, $subdomain_lookup_body['result']['subdomain'] );
	}

	/**
	 * Delete a previously deployed Worker script from Cloudflare.
	 *
	 * @param string $token       Cloudflare API token.
	 * @param string $account_id  Cloudflare account ID the script was deployed under.
	 * @param string $script_name Name of the Worker script to delete.
	 * @param string $error       Reference; populated with the error message on failure.
	 * @return bool
	 */
	public static function delete_worker( $token, $account_id, $script_name, &$error = '' ) {
		$url  = self::API_BASE . '/accounts/' . rawurlencode( $account_id ) . '/workers/scripts/' . rawurlencode( $script_name );
		$args = self::get_api_auth_args( $token );

		$args['method'] = 'DELETE';

		$response = wp_remote_request( $url, $args );

		// A 404 means the script is already gone — treat that as success rather
		// than an error, since the end state the caller wants is already true.
		if ( ! is_wp_error( $response ) && 404 === wp_remote_retrieve_response_code( $response ) ) {
			return true;
		}

		return false !== self::is_success_api_response( $response, 'delete_worker', $error );
	}

	/**
	 * Check whether a Worker script still exists in the given Cloudflare
	 * account, via the account's script listing (not the workers.dev route,
	 * which only tells you it isn't responding — this asks Cloudflare's own
	 * account records directly).
	 *
	 * @param string $token       Cloudflare API token.
	 * @param string $account_id  Cloudflare account ID.
	 * @param string $script_name Name of the Worker script to look for.
	 * @param string $error       Reference; populated with the error message if the check itself fails.
	 * @return bool|null True if found, false if confirmed not found, null if the check couldn't be completed (e.g. an invalid token).
	 */
	public static function script_exists( $token, $account_id, $script_name, &$error = '' ) {
		$url      = self::API_BASE . '/accounts/' . rawurlencode( $account_id ) . '/workers/scripts?per_page=50';
		$response = wp_remote_get( $url, self::get_api_auth_args( $token ) );
		$body     = self::is_success_api_response( $response, 'script_exists', $error );

		if ( false === $body ) {
			return null;
		}

		if ( ! empty( $body['result'] ) && is_array( $body['result'] ) ) {
			foreach ( $body['result'] as $script ) {
				if ( isset( $script['id'] ) && $script['id'] === $script_name ) {
					return true;
				}
			}
		}

		return false;
	}
}
