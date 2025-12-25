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
  <form method="post" id="wpff-sp-stop-preloader-form">
    <?php wp_nonce_field('wpff_sp_stop_preloader'); ?>
    <input
      type="submit"
      name="wpff_sp_stop_preloader"
      class="button wpff-sp-danger-button-outline"
      value="<?php echo esc_attr(__('Stop Preloader', 'super-preloader-for-cloudflare')); ?>"
    />
  </form>
  <span class="spinner" id="wpff-sp-spinner"></span>
  <div id="wpff-sp-preload-result"></div>
</div>

<?php
if (get_transient('wpff_sp_preload_cursor')) {
?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.getElementById('wpff-sp-run-now-button').style.display = 'none';
      document.getElementById('wpff-sp-stop-preloader-form').style.display = 'block';
    });
  </script>
<?php
}
