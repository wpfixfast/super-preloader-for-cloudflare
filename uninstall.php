<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

if (get_option('wpff_sp_delete_data_on_uninstall') !== '1') {
  return;
}

// Recalculate log paths since main plugin is not loaded
$wpff_sp_upload_dir = wp_upload_dir();
$wpff_sp_log_dir    = trailingslashit($wpff_sp_upload_dir['basedir']) . 'super-preloader-for-cloudflare';
$wpff_sp_log_file = $wpff_sp_log_dir . '/super-preloader-for-cloudflare-log.php';

// Delete options
delete_option('wpff_sp_worker_url');
delete_option('wpff_sp_proxy_list_url');
delete_option('wpff_sp_sitemap_url');
delete_option('wpff_sp_cron_interval');
delete_option('wpff_sp_cron_start_hour');
delete_option('wpff_sp_cron_start_minute');
delete_option('wpff_sp_batch_size');
delete_option('wpff_sp_delay_between_urls');
delete_option('wpff_sp_shared_secret');
delete_option('wpff_sp_preload_stats');
delete_option('wpff_sp_sitemap_url_count');
delete_option('wpff_sp_delete_data_on_uninstall');
delete_option('wpff_sp_log_migrated');

// Delete transients
delete_transient('wpff_sp_preload_cursor');
delete_transient('wpff_sp_preload_urls');
delete_transient('wpff_sp_batch_stats');

// Delete log file
if (file_exists($wpff_sp_log_file)) {
  wp_delete_file($wpff_sp_log_file);
}

// Delete log directory if empty using WP_Filesystem
if (is_dir($wpff_sp_log_dir) && count(glob("$wpff_sp_log_dir/*")) === 0) {
  if (!function_exists('WP_Filesystem')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }

  global $wp_filesystem;
  WP_Filesystem();

  if ($wp_filesystem && $wp_filesystem->is_dir($wpff_sp_log_dir)) {
    $wp_filesystem->rmdir($wpff_sp_log_dir);
  }
}
