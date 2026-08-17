<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WPFF_SP_Ajax {

	/**
	 * Handle the AJAX request triggered by the "Start Preloader" button.
	 * Runs one batch of the preloader and returns progress info as JSON.
	 */
	public static function run_preloader() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Unauthorized', 'super-preloader-for-cloudflare' ) );
		}

		check_ajax_referer( 'wpff_sp_preload_nonce', 'nonce' );

		// Detect whether a run was already in progress before this call —
		// WPFF_SP_Preloader::run() silently no-ops in that case, so the caller
		// needs this to tell a "started" response apart from a "skipped" one.
		$already_running = (bool) get_transient( 'wpff_sp_preload_cursor' );

		WPFF_SP_Preloader::run();

		$cursor = get_transient( 'wpff_sp_preload_cursor' );
		$queue  = get_transient( 'wpff_sp_preload_urls' );

		$total_items   = is_array( $queue ) ? count( $queue ) : 0;
		$current_index = is_array( $cursor ) && isset( $cursor['index'] ) ? (int) $cursor['index'] : 0;
		$remaining     = max( 0, $total_items - $current_index );

		wp_send_json_success(
			array(
				'message'        => $already_running
					? esc_html__( 'Preloader is already running.', 'super-preloader-for-cloudflare' )
					: esc_html__( 'First batch completed.', 'super-preloader-for-cloudflare' ),
				'remaining'      => $remaining,
				'done'           => 0 === $remaining,
				'alreadyRunning' => $already_running,
			)
		);
	}

	/**
	 * Handle the AJAX request for fetching log file contents.
	 * Called by the auto-refresh script on the logs tab.
	 */
	public static function get_logs() {
		check_ajax_referer( 'wpff_sp_logs_nonce', 'nonce' );

		if ( file_exists( WPFF_SP_LOG_FILE ) ) {
			$all_lines = file( WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$log_lines = array_slice( $all_lines, WPFF_SP_LOG_HEADER_LINES );
			// Not HTML-escaped: js/log-auto-refresh.js inserts this via
			// .textContent, which never interprets its input as markup, so
			// it's already XSS-safe without escaping here. esc_html() would
			// double-escape (e.g. a literal quote in a log line would render
			// as the literal text "&quot;" instead of a quote character,
			// since .textContent doesn't decode entities).
			echo implode( "\n", $log_lines ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see comment above.
		} else {
			echo esc_html( __( 'No log file found.', 'super-preloader-for-cloudflare' ) );
		}

		wp_die();
	}

	/**
	 * Handle the AJAX request for checking the preloader status.
	 * Returns whether the preloader is currently running.
	 */
	public static function get_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'Unauthorized', 'super-preloader-for-cloudflare' ) );
		}
		check_ajax_referer( 'wpff_sp_status_nonce', 'nonce' );
		wp_send_json_success(
			array(
				'running'   => (bool) get_transient( 'wpff_sp_preload_cursor' ),
				'remaining' => WPFF_SP_Preloader::get_remaining_count(),
			)
		);
	}

	/**
	 * Handle the AJAX request for the sidebar's Worker Status card. Returns
	 * the cached status if one exists, otherwise runs a live check (see
	 * WPFF_SP_Preloader::get_worker_status()).
	 *
	 * When $_POST['check_deletion'] is set and the status comes back
	 * "not_responding" for an auto-deployed Worker, also asks the Cloudflare
	 * API directly whether the script still exists — a definitive answer,
	 * not a guess from the workers.dev response shape. Deliberately opt-in
	 * (only the page-load check requests it, not the post-deploy polling
	 * loop) so a brand new deploy's expected "not responding while
	 * propagating" window doesn't burn extra API calls checking for
	 * deletion of a Worker that was just created seconds ago.
	 */
	public static function get_worker_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'super-preloader-for-cloudflare' ) ) );
		}

		check_ajax_referer( 'wpff_sp_worker_status_nonce', 'nonce' );

		$force          = isset( $_POST['force'] ) && '1' === $_POST['force'];
		$check_deletion = isset( $_POST['check_deletion'] ) && '1' === $_POST['check_deletion'];
		$status         = WPFF_SP_Preloader::get_worker_status( $force );
		$deleted        = null;

		if ( $check_deletion && 'not_responding' === $status ) {
			$token       = get_option( 'wpff_sp_cf_api_token' );
			$account_id  = get_option( 'wpff_sp_cf_resolved_account_id' );
			$script_name = get_option( 'wpff_sp_cf_worker_script_name' );

			if ( $token && $account_id && $script_name ) {
				$exists = WPFF_SP_Cloudflare_Client::script_exists( $token, $account_id, $script_name );

				if ( null !== $exists ) {
					$deleted = ! $exists;
				}
			}
		}

		wp_send_json_success(
			array(
				'status'  => $status,
				'deleted' => $deleted,
			)
		);
	}

	/**
	 * Handle the AJAX request triggered by the "Deploy Worker Automatically" button.
	 * Deploys the bundled Worker script to the user's Cloudflare account using the
	 * supplied API token, then saves the resulting Worker URL and shared secret.
	 */
	public static function deploy_worker() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'super-preloader-for-cloudflare' ) ) );
		}

		check_ajax_referer( 'wpff_sp_deploy_worker_nonce', 'nonce' );

		$token = isset( $_POST['cf_api_token'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_api_token'] ) ) : '';

		// Only persist an account ID the user actually typed in — an
		// auto-detected single-account result is resolved fresh on every
		// deploy so the Account ID field stays hidden for solo-account users.
		$submitted_account_id = isset( $_POST['cf_account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['cf_account_id'] ) ) : '';

		if ( '' === $token ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Enter your Cloudflare API Token first.', 'super-preloader-for-cloudflare' ) ) );
		}

		$error = '';

		// Always resolve the account list (rather than only when no account ID
		// was submitted) so the account's display name is available for the
		// connected-card UI regardless of which path resolved the ID.
		$accounts = WPFF_SP_Cloudflare_Client::get_account_ids( $token, $error );

		if ( false === $accounts ) {
			wp_send_json_error( array( 'message' => $error ) );
		}

		if ( empty( $accounts ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'No Cloudflare accounts found for this API Token.', 'super-preloader-for-cloudflare' ) ) );
		}

		$account_id   = $submitted_account_id;
		$account_name = '';

		if ( '' !== $submitted_account_id ) {
			foreach ( $accounts as $account ) {
				if ( $account['id'] === $submitted_account_id ) {
					$account_name = $account['name'];
					break;
				}
			}
		} elseif ( count( $accounts ) > 1 ) {
			wp_send_json_error(
				array(
					'code'     => 'multiple_accounts',
					'message'  => esc_html__( 'This token has access to more than one Cloudflare account. Select the account to proceed.', 'super-preloader-for-cloudflare' ),
					'accounts' => $accounts,
				)
			);
		} else {
			$account_id   = $accounts[0]['id'];
			$account_name = $accounts[0]['name'];
		}

		$script_name = 'wpff-sp-' . sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) );

		// Deliberately separate from wpff_sp_shared_secret (the legacy/manual
		// field) — an auto-deployed Worker gets its own secret so Connect &
		// Deploy / Disconnect never read or clear a manually configured value.
		$secret = get_option( 'wpff_sp_auto_worker_secret' );
		if ( empty( $secret ) ) {
			$secret = bin2hex( random_bytes( 32 ) );
		}

		$worker_url = WPFF_SP_Cloudflare_Client::deploy_worker( $token, $account_id, $script_name, $secret, $error );

		if ( false === $worker_url ) {
			wp_send_json_error( array( 'message' => $error ) );
		}

		update_option( 'wpff_sp_auto_worker_url', esc_url_raw( $worker_url ) );
		update_option( 'wpff_sp_auto_worker_secret', $secret );
		update_option( 'wpff_sp_cf_api_token', $token );
		update_option( 'wpff_sp_cf_account_id', $submitted_account_id );

		// Persist the mode explicitly rather than relying only on the
		// settings-form fallback inference (get_option('wpff_sp_cf_worker_script_name')
		// ? 'auto' : 'manual') — that inference breaks once Disconnect clears
		// wpff_sp_cf_worker_script_name, which would otherwise bounce the user
		// back to the manual panel instead of the "not connected" auto panel.
		update_option( 'wpff_sp_worker_mode', 'auto' );

		// The Worker URL just changed — the cached status card would
		// otherwise keep showing whatever it last knew (likely "Not
		// Deployed") until the cache expires on its own.
		WPFF_SP_Preloader::clear_worker_status_cache();

		// Internal-only bookkeeping (never shown as an editable field) so a
		// later "Delete Worker on Uninstall" or "Disconnect" can target the
		// exact script/account/name that was actually used, even when
		// $submitted_account_id above was left blank because it was
		// auto-detected rather than typed in.
		update_option( 'wpff_sp_cf_resolved_account_id', $account_id );
		update_option( 'wpff_sp_cf_worker_script_name', $script_name );
		update_option( 'wpff_sp_cf_account_name', $account_name );

		WPFF_SP_Helpers::log(
			sprintf(
				/* translators: %s is the deployed Worker URL. */
				esc_html__( 'Worker deployed automatically: %s', 'super-preloader-for-cloudflare' ),
				$worker_url
			)
		);

		wp_send_json_success(
			array(
				'workerUrl'   => $worker_url,
				'accountName' => $account_name,
			)
		);
	}

	/**
	 * Handle the AJAX request triggered by the "Disconnect" button. Deletes the
	 * Worker this plugin deployed automatically from Cloudflare and clears the
	 * local Worker state — but keeps the saved API Token/Account ID so
	 * reconnecting doesn't require re-entering them.
	 */
	public static function disconnect_worker() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'super-preloader-for-cloudflare' ) ) );
		}

		check_ajax_referer( 'wpff_sp_disconnect_worker_nonce', 'nonce' );

		$token       = get_option( 'wpff_sp_cf_api_token' );
		$account_id  = get_option( 'wpff_sp_cf_resolved_account_id' );
		$script_name = get_option( 'wpff_sp_cf_worker_script_name' );

		if ( $token && $account_id && $script_name ) {
			$error = '';

			if ( ! WPFF_SP_Cloudflare_Client::delete_worker( $token, $account_id, $script_name, $error ) ) {
				wp_send_json_error( array( 'message' => $error ) );
			}
		}

		delete_option( 'wpff_sp_auto_worker_url' );
		delete_option( 'wpff_sp_auto_worker_secret' );
		delete_option( 'wpff_sp_cf_resolved_account_id' );
		delete_option( 'wpff_sp_cf_worker_script_name' );
		delete_option( 'wpff_sp_cf_account_name' );
		// Unlike the API Token (kept for a fast reconnect), the previously
		// selected Account ID isn't worth preserving — leaving it behind just
		// keeps the reactive Account ID row visible/populated after
		// disconnecting, even though there's nothing to reconnect to yet.
		delete_option( 'wpff_sp_cf_account_id' );

		WPFF_SP_Preloader::clear_worker_status_cache();

		WPFF_SP_Helpers::log( __( 'Worker disconnected and removed from Cloudflare.', 'super-preloader-for-cloudflare' ) );

		wp_send_json_success();
	}
}
