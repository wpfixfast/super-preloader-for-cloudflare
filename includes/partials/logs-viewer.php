<?php
if (!defined('ABSPATH')) {
  exit();
}

if (file_exists(WPFF_SP_LOG_FILE)) {
  $log_content = file_get_contents(WPFF_SP_LOG_FILE);
  ?>
  <pre id="wpff-sp-log-output"><?php echo esc_html($log_content); ?></pre>
  <script>
    window.addEventListener("load", function () {
      const logBox = document.getElementById("wpff-sp-log-output");
      if (logBox) logBox.scrollTop = logBox.scrollHeight;
    });
  </script>
  <?php
} else {
  ?>
  <p><?php echo esc_html(__('No log file found.', 'wpfixfast-super-preloader')); ?></p>
  <?php
}