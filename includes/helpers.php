<?php
if (!defined('ABSPATH')) {
  exit();
}

function wpff_sp_log($message) {
  if (!file_exists(WPFF_SP_LOG_DIR)) {
    wp_mkdir_p(WPFF_SP_LOG_DIR);
  }

  $timestamp = current_time('Y-m-d H:i:s');
  $log_line  = "[$timestamp] $message";

  if (file_exists(WPFF_SP_LOG_FILE)) {
    $lines   = file(WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines[] = $log_line;

    if (count($lines) > 1000) {
      $lines = array_slice($lines, -1000);
    }

    file_put_contents(WPFF_SP_LOG_FILE, implode("\n", $lines) . "\n");
  } else {
    file_put_contents(WPFF_SP_LOG_FILE, $log_line);
  }
}

function wpff_sp_generate_token($url, $secret) {
  return hash('sha256', $url . $secret);
}

function wpff_sp_add_settings_link($links) {
  $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=super-preloader-for-cloudflare')) . '">' . esc_html(__('Settings', 'super-preloader-for-cloudflare')) . '</a>';
  array_unshift($links, $settings_link);
  return $links;
}
