<?php
if (!defined('ABSPATH')) {
  exit();
}
?>

<form method="post" class="mt-20">
  <?php wp_nonce_field('wpff_sp_reset_state'); ?>
  <input
    type="submit"
    name="wpff_sp_reset_state"
    class="button wpff-sp-danger-button"
    value="<?php echo esc_attr(__('Clear Stats and Logs', 'wpfixfast-super-preloader')); ?>"
  />
</form>
