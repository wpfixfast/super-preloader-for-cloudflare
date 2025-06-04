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
