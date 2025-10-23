<?php
if (!defined('ABSPATH')) {
  exit();
}

// Add cron interval: weekly (only needed for WP 5.4 and older)
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
    $saved_hour = get_option('wpff_sp_cron_start_hour');
    $saved_minute = get_option('wpff_sp_cron_start_minute', 0);
    
    if ($saved_hour === false || $saved_hour === '') {
      // No time set, start immediately
      $start_timestamp = time();
    } else {
      $timezone = wp_timezone();
      $start_time = new DateTime('now', $timezone);
      $start_time->setTime((int) $saved_hour, (int) $saved_minute, 0);
      
      // If time already passed today, schedule for tomorrow
      if ($start_time->getTimestamp() <= time()) {
        $start_time->modify('+1 day');
      }
      
      $start_timestamp = $start_time->getTimestamp();
    }
    
    wp_schedule_event($start_timestamp, $interval, 'wpff_sp_run_preloader');
  }
}
