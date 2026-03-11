<?php
if (!defined('ABSPATH')) {
  exit();
}

class WPFF_SP_Post_Handlers {

  /**
   * Handle the settings form POST request.
   * Saves all plugin options and reschedules the cron event.
   */
  public static function handle_settings() {
    if (
      isset($_SERVER['REQUEST_METHOD']) &&
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['wpff_sp_settings']) &&
      check_admin_referer('wpff_sp_save_settings')
    ) {
      if (isset($_POST['worker_url'])) {
        update_option(
          'wpff_sp_worker_url',
          esc_url_raw(wp_unslash($_POST['worker_url']))
        );
      }

      if (isset($_POST['proxy_list_url'])) {
        update_option(
          'wpff_sp_proxy_list_url',
          esc_url_raw(wp_unslash($_POST['proxy_list_url']))
        );
      }

      if (isset($_POST['sitemap_url'])) {
        update_option(
          'wpff_sp_sitemap_url',
          esc_url_raw(wp_unslash($_POST['sitemap_url']))
        );
      }

      if (isset($_POST['cron_interval'])) {
        $interval = sanitize_text_field(wp_unslash($_POST['cron_interval']));
        update_option('wpff_sp_cron_interval', $interval);

        if ($interval === 'wpff_sp_custom_interval' && isset($_POST['custom_hours'])) {
          $hours = floatval($_POST['custom_hours']);
          $hours = max(0.25, min(720, $hours)); // Min 15 minutes, max 30 days
          update_option('wpff_sp_custom_hours', $hours);
        }
      }

      if (isset($_POST['cron_start_hour'])) {
        update_option(
          'wpff_sp_cron_start_hour',
          sanitize_text_field(wp_unslash($_POST['cron_start_hour']))
        );
      }

      if (isset($_POST['cron_start_minute'])) {
        update_option(
          'wpff_sp_cron_start_minute',
          sanitize_text_field(wp_unslash($_POST['cron_start_minute']))
        );
      }

      if (isset($_POST['batch_size'])) {
        update_option('wpff_sp_batch_size', absint($_POST['batch_size']));
      }

      if (isset($_POST['delay_between_urls'])) {
        update_option('wpff_sp_delay_between_urls', absint($_POST['delay_between_urls']));
      }

      if (isset($_POST['shared_secret'])) {
        update_option(
          'wpff_sp_shared_secret',
          sanitize_text_field(wp_unslash($_POST['shared_secret']))
        );
      }

      if (isset($_POST['wpff_sp_delete_data_on_uninstall'])) {
        update_option('wpff_sp_delete_data_on_uninstall', '1');
      } else {
        delete_option('wpff_sp_delete_data_on_uninstall');
      }

      // Handle the Full Proxy Pass mode toggle (which clears stats when changed)
      $wpff_sp_previous_mode = get_option('wpff_sp_full_proxy_pass', '');
      $wpff_sp_new_mode      = isset($_POST['wpff_sp_full_proxy_pass']) ? '1' : '';

      if ($wpff_sp_previous_mode !== $wpff_sp_new_mode) {
        delete_option('wpff_sp_preload_stats');
        delete_option('wpff_sp_last_run_meta');
        delete_transient('wpff_sp_preload_urls');
      }

      if ($wpff_sp_new_mode === '1') {
        update_option('wpff_sp_full_proxy_pass', '1');
      } else {
        delete_option('wpff_sp_full_proxy_pass');
      }

      WPFF_SP_Helpers::log(__('Settings updated.', 'super-preloader-for-cloudflare'));

      WPFF_SP_Cron::update_schedule();

      /**
       * Fires after all plugin settings have been saved.
       * Useful for clearing external caches or syncing config to other services.
       */
      do_action('wpff_sp_settings_saved');

      echo '<div class="updated"><p>' . esc_html(__('Settings saved.', 'super-preloader-for-cloudflare')) . '</p></div>';
    }
  }

  /**
   * Handle the reset stats form POST request.
   * Deletes preload stats and clears the log file.
   */
  public static function handle_reset() {
    if (
      isset($_SERVER['REQUEST_METHOD']) &&
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['wpff_sp_reset_state']) &&
      check_admin_referer('wpff_sp_reset_state')
    ) {
      delete_option('wpff_sp_preload_stats');
      delete_option('wpff_sp_last_run_meta');

      if (file_exists(WPFF_SP_LOG_FILE)) {
        wp_delete_file(WPFF_SP_LOG_FILE);
      }

      WPFF_SP_Helpers::log(__('Reset Stats: Stats and logs cleared.', 'super-preloader-for-cloudflare'));

      /**
       * Fires after plugin stats and logs have been reset.
       * Useful for cleaning up related data in other plugins or services.
       */
      do_action('wpff_sp_stats_reset');

      echo '<div class="notice notice-warning"><p>' . esc_html(__('Stats and logs cleared.', 'super-preloader-for-cloudflare')) . '</p></div>';
    }
  }

  /**
   * Handle the stop preloader form POST request.
   * Sets a transient flag that tells the preloader to stop after the current batch.
   */
  public static function handle_stop() {
    if (
      isset($_SERVER['REQUEST_METHOD']) &&
      $_SERVER['REQUEST_METHOD'] === 'POST' &&
      isset($_POST['wpff_sp_stop_preloader']) &&
      check_admin_referer('wpff_sp_stop_preloader')
    ) {
      // Set a stop flag to stop the preloader if it's running
      set_transient('wpff_sp_stop_flag', true, 5 * MINUTE_IN_SECONDS);
      // Clear the cursor so the overlapping run protection is reset
      delete_transient('wpff_sp_preload_cursor');
      echo '<div class="notice notice-warning"><p>' . esc_html(__('Stop signal sent. Preloader will stop after the current batch.', 'super-preloader-for-cloudflare')) . '</p></div>';
    }
  }
}