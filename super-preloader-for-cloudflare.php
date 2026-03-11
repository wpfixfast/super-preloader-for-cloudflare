<?php
/*
Plugin Name: Super Preloader for Cloudflare
Plugin URI: https://wpfixfast.com
Version: 1.0.6
Description: Preload pages into multiple Cloudflare Edge locations using proxies and a Cloudflare Worker.
Author: WP Fix Fast
Author URI: https://wpfixfast.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: super-preloader-for-cloudflare
Domain Path: /languages
 */

if (!defined('ABSPATH')) {
  exit();
}

// ============================================================
// Constants
// ============================================================

define('WPFF_SP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPFF_SP_PLUGIN_URL', plugin_dir_url(__FILE__));

$wpff_sp_upload_dir = wp_upload_dir();
define(
  'WPFF_SP_LOG_DIR',
  trailingslashit($wpff_sp_upload_dir['basedir']) . 'super-preloader-for-cloudflare'
);

// Log file path - changed to .php (after 1.0.3 update)
define('WPFF_SP_LOG_FILE', WPFF_SP_LOG_DIR . '/super-preloader-for-cloudflare-log.php');

// Log file PHP header - defines how many lines to skip
define('WPFF_SP_LOG_HEADER', "<?php\nif (!defined('ABSPATH')) exit;\n?>\n// ==========================================\n// Super Preloader for Cloudflare Log File - Do not edit\n// ==========================================\n");

// Number of header lines to skip when reading logs
define('WPFF_SP_LOG_HEADER_LINES', 6);

// User-Agent string used by all preloader HTTP requests
define('WPFF_SP_USER_AGENT', 'WP Fix Fast Super Preloader/1.0');

// ============================================================
// Load classes (in dependency order)
// ============================================================

require_once WPFF_SP_PLUGIN_PATH . 'includes/class-helpers.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-http-request.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-cron.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-post-handlers.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-preloader.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-ajax.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/class-admin-ui.php';

// ============================================================
// Bootstrap
// ============================================================

// Migrate log file on plugin load (after 1.0.3 update)
WPFF_SP_Helpers::migrate_log_file();

// ============================================================
// Filters
// ============================================================

// Settings link in the plugins page
add_filter(
  'plugin_action_links_' . plugin_basename(__FILE__),
  ['WPFF_SP_Helpers', 'add_settings_link']
);

// Custom cron intervals
add_filter('cron_schedules', ['WPFF_SP_Cron', 'add_custom_intervals']);

// ============================================================
// Actions — Admin
// ============================================================

add_action('admin_menu', ['WPFF_SP_Admin_UI', 'register_menu']);
add_action('admin_enqueue_scripts', ['WPFF_SP_Admin_UI', 'enqueue_assets']);

// ============================================================
// Actions — AJAX
// ============================================================

// AJAX hook for manual preload via button
add_action('wp_ajax_wpff_sp_run_preloader', ['WPFF_SP_Ajax', 'run_preloader']);

// AJAX hook for logs retrieval
add_action('wp_ajax_wpff_sp_get_logs', ['WPFF_SP_Ajax', 'get_logs']);
add_action('wp_ajax_wpff_sp_get_status', ['WPFF_SP_Ajax', 'get_status']);

// ============================================================
// Actions — Cron
// ============================================================

// Cron hook for scheduled preloading
add_action('wpff_sp_run_preloader', ['WPFF_SP_Preloader', 'run'], 10, 1);

// ============================================================
// Activation / Deactivation
// ============================================================

register_activation_hook(__FILE__, ['WPFF_SP_Cron', 'on_activation']);
register_deactivation_hook(__FILE__, ['WPFF_SP_Cron', 'on_deactivation']);
