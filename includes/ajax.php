<?php
if (!defined('ABSPATH')) {
  exit();
}

function wpff_sp_run_preloader_ajax() {
  if (!current_user_can('manage_options')) {
    wp_send_json_error(esc_html__('Unauthorized', 'super-preloader-for-cloudflare'));
  }

  check_ajax_referer('wpff_sp_preload_nonce', 'nonce');

  if (!function_exists('wpff_sp_run_preloader')) {
    wp_send_json_error(esc_html__('Preloader function not found.', 'super-preloader-for-cloudflare'));
  }

  wpff_sp_run_preloader();

  $cursor = get_transient('wpff_sp_preload_cursor');
  $urls   = get_transient('wpff_sp_preload_urls');

  $total_urls    = is_array($urls) ? count($urls) : 0;
  $current_index = is_array($cursor) && isset($cursor['index']) ? (int)$cursor['index'] : 0;
  $remaining     = max(0, $total_urls - $current_index);

  wp_send_json_success([
    'message'   => esc_html__('First batch completed.', 'super-preloader-for-cloudflare'),
    'remaining' => $remaining,
    'done'      => $remaining === 0,
  ]);
}

function wpff_sp_get_logs_ajax() {
    check_ajax_referer('wpff_sp_logs_nonce', 'nonce');
    
    if (file_exists(WPFF_SP_LOG_FILE)) {
        $all_lines = file(WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $log_lines = array_slice($all_lines, WPFF_SP_LOG_HEADER_LINES);
        echo esc_html(implode("\n", $log_lines));
    } else {
        echo esc_html(__('No log file found.', 'super-preloader-for-cloudflare'));
    }
    
    wp_die();
}