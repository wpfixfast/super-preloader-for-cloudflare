<?php
if (!defined('ABSPATH')) {
  exit();
}
?>

<section class="mt-20 d-flex align-items-center gap-10">
  <form method="post">
    <?php wp_nonce_field('wpff_sp_reset_state'); ?>
    <input
      type="submit"
      name="wpff_sp_reset_state"
      class="button wpff-sp-danger-button"
      value="<?php echo esc_attr(__('Clear Stats and Logs', 'super-preloader-for-cloudflare')); ?>"
    />
  </form>
</section>