<?php
if (!defined('ABSPATH')) {
  exit();
}

function wpff_sp_handle_settings_post() {
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

    wpff_sp_log(__('Settings updated.', 'super-preloader-for-cloudflare'));

    wpff_sp_update_cron_schedule();

    echo '<div class="updated"><p>' . esc_html(__('Settings saved.', 'super-preloader-for-cloudflare')) . '</p></div>';
  }
}

function wpff_sp_handle_reset_post() {
  if (
    isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['wpff_sp_reset_state']) &&
    check_admin_referer('wpff_sp_reset_state')
  ) {
    delete_option('wpff_sp_preload_stats');

    if (file_exists(WPFF_SP_LOG_FILE)) {
      wp_delete_file(WPFF_SP_LOG_FILE);
    }

    wpff_sp_log(__('Reset Stats: Stats and logs cleared.', 'super-preloader-for-cloudflare'));
    echo '<div class="notice notice-warning"><p>' . esc_html(__('Stats and logs cleared.', 'super-preloader-for-cloudflare')) . '</p></div>';
  }
}

function wpff_sp_handle_stop_preloader() {
  if (
    isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['wpff_sp_stop_preloader']) &&
    check_admin_referer('wpff_sp_stop_preloader')
  ) {
    // Set a stop flag to stop the preloader if it's running
    set_transient('wpff_sp_stop_flag', true, 5 * MINUTE_IN_SECONDS);
    echo '<div class="notice notice-warning"><p>' . esc_html(__('Stop signal sent. Preloader will stop after the current batch.', 'super-preloader-for-cloudflare')) . '</p></div>';
  }
}
