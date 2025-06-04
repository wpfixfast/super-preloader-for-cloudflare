<?php
if (!defined('ABSPATH')) {
  exit();
}

$settings_class = $tab === 'settings' ? 'nav-tab nav-tab-active' : 'nav-tab';
$stats_class    = $tab === 'stats' ? 'nav-tab nav-tab-active' : 'nav-tab';
$logs_class     = $tab === 'logs' ? 'nav-tab nav-tab-active' : 'nav-tab';
?>

<h2 class="nav-tab-wrapper">
  <a href="?page=super-preloader-for-cloudflare&tab=settings" class="<?php echo esc_attr($settings_class); ?>">
    <?php echo esc_html(__('Settings', 'super-preloader-for-cloudflare')); ?>
  </a>
  <a href="?page=super-preloader-for-cloudflare&tab=stats" class="<?php echo esc_attr($stats_class); ?>">
    <?php echo esc_html(__('Stats', 'super-preloader-for-cloudflare')); ?>
  </a>
  <a href="?page=super-preloader-for-cloudflare&tab=logs" class="<?php echo esc_attr($logs_class); ?>">
    <?php echo esc_html(__('Logs', 'super-preloader-for-cloudflare')); ?>
  </a>
</h2>
