<?php
if (!defined('ABSPATH')) {
  exit();
}

// Add custom cron interval: weekly
add_filter('cron_schedules', function ($schedules) {
  if (!isset($schedules['weekly'])) {
    $schedules['weekly'] = [
      'interval' => 7 * DAY_IN_SECONDS,
      'display'  => __('Once Weekly', 'super-preloader-for-cloudflare'),
    ];
  }
  return $schedules;
});

function wpff_sp_activation() {
  $interval = get_option('wpff_sp_cron_interval', 'manual');
  if ($interval !== 'manual' && !wp_next_scheduled('wpff_sp_run_preloader')) {
    wp_schedule_event(time(), $interval, 'wpff_sp_run_preloader');
  }
}

function wpff_sp_deactivation() {
  wp_clear_scheduled_hook('wpff_sp_run_preloader');
}

function wpff_sp_update_cron_schedule() {
  wp_clear_scheduled_hook('wpff_sp_run_preloader');

  $interval = get_option('wpff_sp_cron_interval', 'manual');
  if ($interval !== 'manual') {
    wp_schedule_event(time(), $interval, 'wpff_sp_run_preloader');
  }
}
