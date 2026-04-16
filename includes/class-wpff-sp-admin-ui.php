<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WPFF_SP_Admin_UI {

	/**
	 * Register the plugin settings page under the Settings menu.
	 */
	public static function register_menu() {
		add_options_page(
			__( 'Super Preloader for Cloudflare', 'super-preloader-for-cloudflare' ),
			__( 'Super Preloader', 'super-preloader-for-cloudflare' ),
			'manage_options',
			'super-preloader-for-cloudflare',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Render the main plugin settings page.
	 * Handles form submissions and includes the appropriate tab partial.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		WPFF_SP_Post_Handlers::handle_settings();
		WPFF_SP_Post_Handlers::handle_reset();
		WPFF_SP_Post_Handlers::handle_stop();

	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		$tab = isset( $_GET['tab'] )
		? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		: 'settings';

		$worker_url    = get_option( 'wpff_sp_worker_url', '' );
		$proxy_url     = get_option( 'wpff_sp_proxy_list_url', '' );
		$sitemap_url   = get_option( 'wpff_sp_sitemap_url', get_site_url() . '/sitemap.xml' );
		$cron_interval = get_option( 'wpff_sp_cron_interval', 'manual' );
		$shared_secret = get_option( 'wpff_sp_shared_secret', '' );
		$wpff_sp_stats = get_option( 'wpff_sp_preload_stats', array() );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( __( 'Super Preloader for Cloudflare', 'super-preloader-for-cloudflare' ) ) . '</h1>';

		include plugin_dir_path( __FILE__ ) . 'partials/navigation-tabs.php';

		if ( 'settings' === $tab ) {
			echo '<div class="wpff-sp-settings-layout">';
			echo '<div class="wpff-sp-settings-main">';
			include plugin_dir_path( __FILE__ ) . 'partials/settings-form.php';
			include plugin_dir_path( __FILE__ ) . 'partials/start-preloader.php';
			include plugin_dir_path( __FILE__ ) . 'partials/reset-preloader.php';
			echo '</div>';
			echo '<div class="wpff-sp-settings-sidebar">';
			include plugin_dir_path( __FILE__ ) . 'partials/sidebar.php';
			echo '</div>';
			echo '</div>';
		} elseif ( 'stats' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/stats-table.php';
		} elseif ( 'logs' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/logs-viewer.php';
		} elseif ( 'howto' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/setup-guide.php';
		}

		echo '</div>';
	}

	/**
	 * Enqueue admin scripts and styles for the plugin settings page.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'settings_page_super-preloader-for-cloudflare' !== $hook ) {
			return;
		}

	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		$current_tab = isset( $_GET['tab'] )
		? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		: 'settings';

		wp_enqueue_script(
			'wpff-sp-admin-ui',
			plugin_dir_url( __DIR__ ) . 'js/admin-ui.js',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'js/admin-ui.js' ),
			true
		);

		wp_localize_script(
			'wpff-sp-admin-ui',
			'wpff',
			array(
				'nonce'       => wp_create_nonce( 'wpff_sp_preload_nonce' ),
				'statusNonce' => wp_create_nonce( 'wpff_sp_status_nonce' ),
				'i18n'        => array(
					'running'       => __( 'Preloader running... Please wait for the first batch to complete.', 'super-preloader-for-cloudflare' ),
					'complete'      => __( 'All URLs have been processed.', 'super-preloader-for-cloudflare' ),
					// translators: %d is the number of items remaining in the preload queue.
					'remaining'     => __( '%d items remaining. Background process will continue.', 'super-preloader-for-cloudflare' ),
					'error'         => __( 'Error: ', 'super-preloader-for-cloudflare' ),
					'ajaxFailed'    => __( 'AJAX request failed.', 'super-preloader-for-cloudflare' ),
					'unknown'       => __( 'Unknown error.', 'super-preloader-for-cloudflare' ),
					'statusRunning' => __( 'Running', 'super-preloader-for-cloudflare' ),
					'statusIdle'    => __( 'Idle', 'super-preloader-for-cloudflare' ),
				),
			)
		);

		// Only enqueue log auto-refresh on logs tab
		if ( 'logs' === $current_tab ) {
			wp_enqueue_script(
				'wpff-sp-log-auto-refresh',
				plugin_dir_url( __DIR__ ) . 'js/log-auto-refresh.js',
				array(),
				filemtime( plugin_dir_path( __DIR__ ) . 'js/log-auto-refresh.js' ),
				true
			);

			wp_localize_script(
				'wpff-sp-log-auto-refresh',
				'wpffSpLogs',
				array(
					'nonce' => wp_create_nonce( 'wpff_sp_logs_nonce' ),
				)
			);
		}

		// Enqueue CSS
		wp_enqueue_style(
			'wpff-sp-admin-ui-style',
			plugin_dir_url( __DIR__ ) . 'css/admin-ui.css',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'css/admin-ui.css' )
		);
	}
}
