<?php
if (!defined('ABSPATH')) {
  exit();
}

$settings_class = $tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab';
$stats_class    = $tab === 'stats' ? 'nav-tab nav-tab-active' : 'nav-tab';
$logs_class     = $tab === 'logs' ? 'nav-tab nav-tab-active' : 'nav-tab';
?>

<h2 class="nav-tab-wrapper">
  <a href="?page=wpfixfast-super-preloader&tab=settings" class="<?php echo esc_attr($settings_class); ?>">
    <?php echo esc_html(__('Settings', 'wpfixfast-super-preloader')); ?>
  </a>
  <a href="?page=wpfixfast-super-preloader&tab=stats" class="<?php echo esc_attr($stats_class); ?>">
    <?php echo esc_html(__('Stats', 'wpfixfast-super-preloader')); ?>
  </a>
  <a href="?page=wpfixfast-super-preloader&tab=logs" class="<?php echo esc_attr($logs_class); ?>">
    <?php echo esc_html(__('Logs', 'wpfixfast-super-preloader')); ?>
  </a>
</h2>
