<?php
if (!defined('ABSPATH')) {
  exit();
}

if (file_exists(WPFF_SP_LOG_FILE)) {
  $wpff_sp_all_lines = file(WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  
  // Skip header lines using constant
  $wpff_sp_log_lines = array_slice($wpff_sp_all_lines, WPFF_SP_LOG_HEADER_LINES);
  $wpff_sp_log_content = implode("\n", $wpff_sp_log_lines);
  ?>
  <pre id="wpff-sp-log-output"><?php echo esc_html($wpff_sp_log_content); ?></pre>
  <?php
} else {
  ?>
  <p><?php echo esc_html(__('No log file found.', 'super-preloader-for-cloudflare')); ?></p>
  <?php
}
