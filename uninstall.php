<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

if (get_option('wpff_sp_delete_data_on_uninstall') !== '1') {
  return;
}

// Recalculate log paths since main plugin is not loaded
$upload_dir = wp_upload_dir();
$log_dir    =
  trailingslashit($upload_dir['basedir']) . 'wpfixfast-super-preloader';
$log_file = $log_dir . '/wpfixfast-super-preloader.log';

// Delete options
delete_option('wpff_sp_worker_url');
delete_option('wpff_sp_proxy_list_url');
delete_option('wpff_sp_sitemap_url');
delete_option('wpff_sp_cron_interval');
delete_option('wpff_sp_batch_size');
delete_option('wpff_sp_shared_secret');
delete_option('wpff_sp_preload_stats');
delete_option('wpff_sp_sitemap_url_count');
delete_option('wpff_sp_delete_data_on_uninstall');

// Delete transients
delete_transient('wpff_sp_preload_cursor');
delete_transient('wpff_sp_preload_urls');
delete_transient('wpff_sp_batch_stats');

// Delete log file
if (file_exists($log_file)) {
  wp_delete_file($log_file);
}

// Delete log directory if empty using WP_Filesystem
if (is_dir($log_dir) && count(glob("$log_dir/*")) === 0) {
  if (!function_exists('WP_Filesystem')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }

  global $wp_filesystem;
  WP_Filesystem();

  if ($wp_filesystem && $wp_filesystem->is_dir($log_dir)) {
    $wp_filesystem->rmdir($log_dir);
  }
}
