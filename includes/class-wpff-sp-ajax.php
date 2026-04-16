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

		WPFF_SP_Preloader::run();

		$cursor = get_transient( 'wpff_sp_preload_cursor' );
		$queue  = get_transient( 'wpff_sp_preload_urls' );

		$total_items   = is_array( $queue ) ? count( $queue ) : 0;
		$current_index = is_array( $cursor ) && isset( $cursor['index'] ) ? (int) $cursor['index'] : 0;
		$remaining     = max( 0, $total_items - $current_index );

		wp_send_json_success(
			array(
				'message'   => esc_html__( 'First batch completed.', 'super-preloader-for-cloudflare' ),
				'remaining' => $remaining,
				'done'      => 0 === $remaining,
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
			echo esc_html( implode( "\n", $log_lines ) );
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
				'running' => (bool) get_transient( 'wpff_sp_preload_cursor' ),
			)
		);
	}
}
