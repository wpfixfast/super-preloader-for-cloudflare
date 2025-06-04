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
      update_option(
        'wpff_sp_cron_interval',
        sanitize_text_field(wp_unslash($_POST['cron_interval']))
      );
    }

    if (isset($_POST['batch_size'])) {
      update_option('wpff_sp_batch_size', absint($_POST['batch_size']));
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
    delete_transient('wpff_sp_preload_cursor');
    delete_transient('wpff_sp_preload_urls');
    delete_option('wpff_sp_preload_stats');

    if (file_exists(WPFF_SP_LOG_FILE)) {
      wp_delete_file(WPFF_SP_LOG_FILE);
    }

    wpff_sp_log(__('Reset Preload: Cleared stats, log, and cursor.', 'super-preloader-for-cloudflare'));
    echo '<div class="notice notice-warning"><p>' . esc_html(__('Preload state has been fully reset.', 'super-preloader-for-cloudflare')) . '</p></div>';
  }
}
