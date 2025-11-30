<?php
/*
Plugin Name: Super Preloader for Cloudflare
Plugin URI: https://wpfixfast.com
Version: 1.0.3
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

// Define constants
define('WPFF_SP_PLUGIN_PATH', plugin_dir_path(__FILE__));

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

// Load includes
require_once WPFF_SP_PLUGIN_PATH . 'includes/admin-ui.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/http_request_functions.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/helpers.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/post-handlers.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/cron.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/preloader.php';
require_once WPFF_SP_PLUGIN_PATH . 'includes/ajax.php';

// Migrate log file on plugin load (after 1.0.3 update)
wpff_sp_migrate_log_file();

// Settings link in the plugins page
add_filter(
  'plugin_action_links_' . plugin_basename(__FILE__),
  'wpff_sp_add_settings_link'
);

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'wpff_sp_activation');
register_deactivation_hook(__FILE__, 'wpff_sp_deactivation');

// AJAX hook for manual preload via button
add_action('wp_ajax_wpff_sp_run_preloader', 'wpff_sp_run_preloader_ajax');

// Cron hook for scheduled preloading
add_action('wpff_sp_run_preloader', 'wpff_sp_run_preloader');
