<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WPFF_SP_Admin_UI {

	/**
	 * Register the plugin settings page under the Settings menu.
	 */
	public static function register_menu() {
		$hook = add_options_page(
			__( 'Super Preloader for Cloudflare', 'super-preloader-for-cloudflare' ),
			__( 'Super Preloader', 'super-preloader-for-cloudflare' ),
			'manage_options',
			'super-preloader-for-cloudflare',
			array( __CLASS__, 'render_page' )
		);

		add_action( "load-$hook", array( __CLASS__, 'maybe_add_screen_options' ) );
		add_action( "load-$hook", array( __CLASS__, 'maybe_process_url_bulk_action' ) );
	}

	/**
	 * Require the URLs tab's WP_List_Table class and its WP core dependency.
	 * Public so WPFF_SP_Ajax::get_urls_table() can reuse it too.
	 */
	public static function load_urls_list_table_class() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-wpff-sp-urls-list-table.php';
	}

	/**
	 * Register the "URLs per page" Screen Option, but only on the URLs tab —
	 * the Settings/Stats/Logs/How to Use tabs share this same admin page.
	 */
	public static function maybe_add_screen_options() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

		if ( 'exclusions' !== $tab ) {
			return;
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'URLs per page', 'super-preloader-for-cloudflare' ),
				'default' => 20,
				'option'  => 'wpff_sp_urls_per_page',
			)
		);
	}

	/**
	 * Process the URLs tab's Exclude/Include bulk action on the load-{hook}
	 * action — this is the latest point in the request where a redirect is
	 * still possible, since admin-header.php sends output shortly after.
	 */
	public static function maybe_process_url_bulk_action() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

		if ( 'exclusions' !== $tab ) {
			return;
		}

		self::load_urls_list_table_class();

		$list_table = new WPFF_SP_Urls_List_Table();
		$list_table->process_bulk_action();
	}

	/**
	 * Persist the "URLs per page" Screen Option value.
	 *
	 * @param mixed  $status Default save status passed through by WordPress.
	 * @param string $option The screen option name.
	 * @param mixed  $value  The submitted value.
	 * @return mixed
	 */
	public static function save_screen_options( $status, $option, $value ) {
		if ( 'wpff_sp_urls_per_page' === $option ) {
			return (int) $value;
		}

		return $status;
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
		WPFF_SP_Post_Handlers::handle_url_exclusions();

	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		$tab = isset( $_GET['tab'] )
		? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
		: 'settings';

		$worker_url    = get_option( 'wpff_sp_worker_url', '' );
		$cf_api_token  = defined( 'WPFF_SP_CF_API_TOKEN' ) && '' !== (string) WPFF_SP_CF_API_TOKEN ? WPFF_SP_CF_API_TOKEN : get_option( 'wpff_sp_cf_api_token', '' );
		$cf_account_id = get_option( 'wpff_sp_cf_account_id', '' );
		$cf_zone_id    = get_option( 'wpff_sp_cf_zone_id', '' );

		// Infer a default the first time this is ever read (before the user has
		// explicitly toggled or saved a mode).
		$wpff_sp_worker_mode = get_option( 'wpff_sp_worker_mode', '' );
		if ( '' === $wpff_sp_worker_mode ) {
			if ( get_option( 'wpff_sp_cf_worker_script_name' ) ) {
				// Already completed an auto-deploy — land in "auto", not manual.
				$wpff_sp_worker_mode = 'auto';
			} elseif ( empty( $worker_url ) ) {
				// Nothing manually configured yet (a genuinely fresh install, or
				// a pre-upgrade install that was never set up) — default new
				// setups to the simpler API Token flow instead of the old
				// manual gist setup.
				$wpff_sp_worker_mode = 'auto';
			} else {
				// An existing manual user already has a Worker URL configured —
				// keep them on Manual so nothing changes for them on upgrade.
				$wpff_sp_worker_mode = 'manual';
			}
		}

		$cf_account_name    = get_option( 'wpff_sp_cf_account_name', '' );
		$cf_zone_name       = get_option( 'wpff_sp_cf_zone_name', '' );
		$cf_auto_worker_url = get_option( 'wpff_sp_auto_worker_url', '' );
		$proxy_url          = get_option( 'wpff_sp_proxy_list_url', '' );
		$sitemap_url        = get_option( 'wpff_sp_sitemap_url', get_site_url() . '/sitemap.xml' );
		$cron_interval      = get_option( 'wpff_sp_cron_interval', 'manual' );
		$shared_secret      = get_option( 'wpff_sp_shared_secret', '' );
		$wpff_sp_stats      = get_option( 'wpff_sp_preload_stats', array() );

		// Bulk actions are already handled earlier via load-{hook} (see
		// maybe_process_url_bulk_action) since a redirect can't happen here —
		// by this point admin-header.php has already sent output.
		//
		// On a plain fresh visit (no pagination/search/sort params),
		// prepare_items() re-fetches the sitemap live — deliberately, so
		// this tab always reflects the current sitemap rather than a stale
		// cache, unlike pagination/search/sort which reuse the cache for
		// consistency (see WPFF_SP_Urls_List_Table::get_all_urls()). That
		// live fetch can take a couple of seconds, so it's deferred to an
		// AJAX call (see js/admin-ui.js + WPFF_SP_Ajax::get_urls_table())
		// instead of blocking this page load — urls-list.php renders a
		// skeleton in the meantime when $wpff_sp_defer_urls_table is true.
		$wpff_sp_urls_list_table  = null;
		$wpff_sp_defer_urls_table = false;
		if ( 'exclusions' === $tab ) {
			self::load_urls_list_table_class();

			if ( WPFF_SP_Urls_List_Table::is_table_interaction() ) {
				$wpff_sp_urls_list_table = new WPFF_SP_Urls_List_Table();
				$wpff_sp_urls_list_table->prepare_items();
			} else {
				$wpff_sp_defer_urls_table = true;
			}
		}

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
		} elseif ( 'exclusions' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/urls-list.php';
		} elseif ( 'stats' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/stats-table.php';
		} elseif ( 'coverage' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/coverage-tab.php';
		} elseif ( 'logs' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/logs-viewer.php';
		} elseif ( 'howto' === $tab ) {
			include plugin_dir_path( __FILE__ ) . 'partials/setup-guide.php';
		}

		echo '</div>';
	}

	/**
	 * Add the "Super Preloader" admin bar menu, a plain link to the plugin's
	 * Settings page with a "Start Preload" shortcut nested under it — leaves
	 * room for more shortcuts to be added under the same parent later.
	 * Only shown when the admin bar shortcut setting is enabled.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 */
	public static function add_admin_bar_shortcut( $wp_admin_bar ) {
		if ( ! get_option( 'wpff_sp_admin_bar_shortcut' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'wpff-sp-preload',
				'title' => esc_html__( 'Super Preloader', 'super-preloader-for-cloudflare' ),
				'href'  => admin_url( 'options-general.php?page=super-preloader-for-cloudflare' ),
			)
		);

		// Render the real running state up front so the item is correct on
		// first paint and the JS never has to guess (or poll) while idle.
		$remaining  = WPFF_SP_Preloader::get_remaining_count();
		$is_running = null !== $remaining;
		$label_text = $is_running
			? __( 'Running…', 'super-preloader-for-cloudflare' )
			: __( 'Start Preload', 'super-preloader-for-cloudflare' );

		$meta = array(
			'title' => esc_attr__( 'Start Manual Preload', 'super-preloader-for-cloudflare' ),
		);
		if ( $is_running ) {
			$meta['class'] = 'wpff-sp-admin-bar-loading';
		}

		$wp_admin_bar->add_node(
			array(
				'id'     => 'wpff-sp-preload-start',
				'parent' => 'wpff-sp-preload',
				'title'  => '<span class="wpff-sp-preload-label">' . esc_html( $label_text ) . '</span>',
				'href'   => '#',
				'meta'   => $meta,
			)
		);
	}

	/**
	 * Enqueue the admin bar shortcut script and styles.
	 * Runs on every admin and front-end page when the shortcut is enabled and visible.
	 */
	public static function enqueue_admin_bar_assets() {
		if ( ! get_option( 'wpff_sp_admin_bar_shortcut' ) ) {
			return;
		}

		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpff-sp-admin-bar',
			plugin_dir_url( __DIR__ ) . 'css/admin-bar.css',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'css/admin-bar.css' )
		);

		wp_enqueue_script(
			'wpff-sp-admin-bar',
			plugin_dir_url( __DIR__ ) . 'js/admin-bar.js',
			array(),
			filemtime( plugin_dir_path( __DIR__ ) . 'js/admin-bar.js' ),
			true
		);

		wp_localize_script(
			'wpff-sp-admin-bar',
			'wpffSpAdminBar',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wpff_sp_preload_nonce' ),
				'statusNonce' => wp_create_nonce( 'wpff_sp_status_nonce' ),
				'i18n'        => array(
					'startLabel'     => __( 'Start Preload', 'super-preloader-for-cloudflare' ),
					'starting'       => __( 'Preloader started...', 'super-preloader-for-cloudflare' ),
					'alreadyRunning' => __( 'Preloader is already running...', 'super-preloader-for-cloudflare' ),
					'complete'       => __( 'Preload Complete', 'super-preloader-for-cloudflare' ),
					// translators: %d is the number of items remaining in the preload queue.
					'remaining'      => __( '%d items remaining. Background process will continue.', 'super-preloader-for-cloudflare' ),
					'running'        => __( 'Running...', 'super-preloader-for-cloudflare' ),
					'error'          => __( 'Error: ', 'super-preloader-for-cloudflare' ),
					'ajaxFailed'     => __( 'AJAX request failed.', 'super-preloader-for-cloudflare' ),
					'unknown'        => __( 'Unknown error.', 'super-preloader-for-cloudflare' ),
				),
			)
		);
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
				'nonce'              => wp_create_nonce( 'wpff_sp_preload_nonce' ),
				'statusNonce'        => wp_create_nonce( 'wpff_sp_status_nonce' ),
				'deployNonce'        => wp_create_nonce( 'wpff_sp_deploy_worker_nonce' ),
				'disconnectNonce'    => wp_create_nonce( 'wpff_sp_disconnect_worker_nonce' ),
				'workerStatusNonce'  => wp_create_nonce( 'wpff_sp_worker_status_nonce' ),
				'cacheCoverageNonce' => wp_create_nonce( 'wpff_sp_cache_coverage_nonce' ),
				'urlsTableNonce'     => wp_create_nonce( 'wpff_sp_urls_table_nonce' ),
				'i18n'               => array(
					'running'                       => __( 'Preloader running... Please wait for the first batch to complete.', 'super-preloader-for-cloudflare' ),
					'complete'                      => __( 'Preload Complete', 'super-preloader-for-cloudflare' ),
					// translators: %d is the number of items remaining in the preload queue.
					'remaining'                     => __( '%d items remaining. Background process will continue.', 'super-preloader-for-cloudflare' ),
					'error'                         => __( 'Error: ', 'super-preloader-for-cloudflare' ),
					'ajaxFailed'                    => __( 'AJAX request failed.', 'super-preloader-for-cloudflare' ),
					'unknown'                       => __( 'Unknown error.', 'super-preloader-for-cloudflare' ),
					'statusRunning'                 => __( 'Running', 'super-preloader-for-cloudflare' ),
					'statusIdle'                    => __( 'Idle', 'super-preloader-for-cloudflare' ),
					// translators: %d is the number of items remaining in the preload queue.
					'remainingTag'                  => __( '(%d remaining)', 'super-preloader-for-cloudflare' ),
					'deploying'                     => __( 'Deploying Worker to Cloudflare…', 'super-preloader-for-cloudflare' ),
					'deploySuccess'                 => __( 'Worker deployed successfully and your settings have been saved.', 'super-preloader-for-cloudflare' ),
					'deployMissing'                 => __( 'Enter your Cloudflare API Token first.', 'super-preloader-for-cloudflare' ),
					'disconnectConfirm'             => __( 'This will delete the deployed Worker from your Cloudflare account. Continue?', 'super-preloader-for-cloudflare' ),
					'disconnecting'                 => __( 'Disconnecting…', 'super-preloader-for-cloudflare' ),
					'disconnectSuccess'             => __( 'Worker disconnected and removed from Cloudflare.', 'super-preloader-for-cloudflare' ),
					'disconnectWorkerDeleteWarning' => __( 'Disconnected locally, but the Worker script could not be removed from Cloudflare automatically. You may need to delete it manually from your Cloudflare dashboard (Workers & Pages).', 'super-preloader-for-cloudflare' ),
					'workerStatusChecking'          => __( 'Checking…', 'super-preloader-for-cloudflare' ),
					'workerStatusDeploying'         => __( 'Waiting for the Worker', 'super-preloader-for-cloudflare' ),
					'workerStatusWorking'           => __( 'Deployed & Working', 'super-preloader-for-cloudflare' ),
					'workerStatusNotDeployed'       => __( 'Not Deployed', 'super-preloader-for-cloudflare' ),
					'workerStatusNotResponding'     => __( 'Not Responding', 'super-preloader-for-cloudflare' ),
					// translators: %1$d is the request count, %2$d is the miss count, both for the last 24 hours.
					'coverageMissesToday'           => __( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
					// translators: %1$d is the request count, %2$d is the miss count, both for the last 7 days.
					'coverageMisses7d'              => __( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
					// translators: %1$d is the request count, %2$d is the miss count, for a single country with no time period implied.
					'coverageMissesPlain'           => __( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
					'coverageAllGood'               => __( 'No misses in the last 7 days. Great coverage!', 'super-preloader-for-cloudflare' ),
					'coverageNoSuggestions'         => __( 'No countries need attention right now.', 'super-preloader-for-cloudflare' ),
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
