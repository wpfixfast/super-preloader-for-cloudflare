<?php
if (!defined('ABSPATH')) {
  exit();
}

// Add custom cron intervals
add_filter('cron_schedules', 'wpff_sp_add_custom_cron_intervals');
function wpff_sp_add_custom_cron_intervals($schedules) {
  // Weekly interval (for WP 5.4 and older)
  if (!isset($schedules['weekly'])) {
    $schedules['weekly'] = [
      'interval' => 7 * DAY_IN_SECONDS,
      'display'  => __('Once Weekly', 'super-preloader-for-cloudflare'),
    ];
  }
  
  // New static intervals (added in 1.0.5)
  $schedules['every_3_hours'] = array(
    'interval' => 10800, // 3 hours in seconds
    'display'  => __('Every 3 Hours', 'super-preloader-for-cloudflare')
  );
  $schedules['every_6_hours'] = array(
    'interval' => 21600, // 6 hours in seconds
    'display'  => __('Every 6 Hours', 'super-preloader-for-cloudflare')
  );
  $schedules['every_12_hours'] = array(
    'interval' => 43200, // 12 hours in seconds
    'display'  => __('Every 12 Hours', 'super-preloader-for-cloudflare')
  );
  
  // Dynamic custom interval
  $custom_hours = floatval(get_option('wpff_sp_custom_hours', 1));
  $schedules['wpff_sp_custom_interval'] = array(
    'interval' => (int)($custom_hours * 3600), // Convert to integer seconds
    /* translators: %s: Number of hours for the custom interval */
    'display'  => sprintf(__('Every %.2f Hour(s)', 'super-preloader-for-cloudflare'), $custom_hours)
  );
  
  return $schedules;
}

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
    
    // For custom interval, ensure the cron schedule is updated with latest hours value
    if ($interval === 'wpff_sp_custom_interval') {
      // Force WordPress to re-register the custom schedule with current hours
      wp_cache_delete('cron_schedules', 'cron');
    }

    wp_schedule_event($start_timestamp, $interval, 'wpff_sp_run_preloader');
  }
}

function wpff_sp_get_next_cron_run() {
  $timestamp = wp_next_scheduled('wpff_sp_run_preloader');
  
  if (!$timestamp) {
    return __('Not scheduled', 'super-preloader-for-cloudflare');
  }
  
  $timezone = wp_timezone();
  $next_run = new DateTime('@' . $timestamp);
  $next_run->setTimezone($timezone);
  $now = new DateTime('now', $timezone);
  
  // Calculate precise time difference
  $diff = $timestamp - time();
  $hours = floor($diff / 3600);
  $minutes = floor(($diff % 3600) / 60);
  
  // Build human readable string
  if ($hours > 0 && $minutes > 0) {
    /* translators: 1: Number of hours, 2: Number of minutes */
    $time_diff = sprintf(__('%1$d hours %2$d minutes', 'super-preloader-for-cloudflare'), $hours, $minutes);
  } elseif ($hours > 0) {
    /* translators: %d: Number of hours */
    $time_diff = sprintf(_n('%d hour', '%d hours', $hours, 'super-preloader-for-cloudflare'), $hours);
  } else {
    /* translators: %d: Number of minutes */
    $time_diff = sprintf(_n('%d minute', '%d minutes', $minutes, 'super-preloader-for-cloudflare'), $minutes);
  }
  
  // Check if it's today or tomorrow
  $today = $now->format('Y-m-d');
  $tomorrow = $now->modify('+1 day')->format('Y-m-d');
  $next_date = $next_run->format('Y-m-d');
  
  if ($next_date === $today) {
    /* translators: %s: Time in 12-hour format (e.g., "9:13 pm") */
    $formatted_time = sprintf(__('Today at %s', 'super-preloader-for-cloudflare'), $next_run->format('g:i a'));
  } elseif ($next_date === $tomorrow) {
    /* translators: %s: Time in 12-hour format (e.g., "9:13 pm") */
    $formatted_time = sprintf(__('Tomorrow at %s', 'super-preloader-for-cloudflare'), $next_run->format('g:i a'));
  } else {
    $formatted_time = $next_run->format('F j, Y \a\t g:i a');
  }
  
  return sprintf(
    /* translators: 1: Human-readable time difference (e.g., "5 hours 30 minutes"), 2: Formatted date and time or "Today/Tomorrow at time" */
    __('In %1$s (%2$s)', 'super-preloader-for-cloudflare'),
    $time_diff,
    $formatted_time
  );
}