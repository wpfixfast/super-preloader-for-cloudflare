<?php
if (!defined('ABSPATH')) {
  exit();
}

$wpff_sp_manual_nonce = wp_create_nonce('wpff_sp_preload_nonce');
?>

<div class="d-flex items-center mt-20 gap-10">
  <button
    type="button"
    class="button"
    id="wpff-sp-run-now-button"
    data-nonce="<?php echo esc_attr($wpff_sp_manual_nonce); ?>"
  >
    <?php echo esc_html(__('Start Manual Preload', 'super-preloader-for-cloudflare')); ?>
  </button>
  <span class="spinner" id="wpff-sp-spinner"></span>
  <div id="wpff-sp-preload-result"></div>
</div>
