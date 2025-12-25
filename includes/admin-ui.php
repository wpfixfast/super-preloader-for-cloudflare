<?php
if (!defined('ABSPATH')) {
  exit();
}

add_action('admin_menu', 'wpff_sp_register_admin_menu');
add_action('admin_enqueue_scripts', 'wpff_sp_enqueue_admin_assets');

function wpff_sp_register_admin_menu() {
  add_options_page(
    __('Super Preloader for Cloudflare', 'super-preloader-for-cloudflare'),
    __('Super Preloader', 'super-preloader-for-cloudflare'),
    'manage_options',
    'super-preloader-for-cloudflare',
    'wpff_sp_render_settings_page'
  );
}

function wpff_sp_render_settings_page() {
  if (!current_user_can('manage_options')) {
    return;
  }

  wpff_sp_handle_settings_post();
  wpff_sp_handle_reset_post();
  wpff_sp_handle_stop_preloader();

  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
  $tab = isset($_GET['tab'])
  ? sanitize_text_field(wp_unslash($_GET['tab'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
  : 'settings';

  $worker_url    = get_option('wpff_sp_worker_url', '');
  $proxy_url     = get_option('wpff_sp_proxy_list_url', '');
  $sitemap_url   = get_option('wpff_sp_sitemap_url', get_site_url() . '/sitemap.xml');
  $cron_interval = get_option('wpff_sp_cron_interval', 'manual');
  $shared_secret = get_option('wpff_sp_shared_secret', '');
  $wpff_sp_stats = get_option('wpff_sp_preload_stats', []);

  echo '<div class="wrap">';
  echo '<h1>' . esc_html(__('Super Preloader for Cloudflare', 'super-preloader-for-cloudflare')) . '</h1>';
  include plugin_dir_path(__FILE__) . 'partials/navigation-tabs.php';

  if ($tab === 'settings') {
    include plugin_dir_path(__FILE__) . 'partials/settings-form.php';
    include plugin_dir_path(__FILE__) . 'partials/start-preloader.php';
    include plugin_dir_path(__FILE__) . 'partials/reset-preloader.php';
    include plugin_dir_path(__FILE__) . 'partials/setup-guide.php';
  } elseif ($tab === 'stats') {
    include plugin_dir_path(__FILE__) . 'partials/stats-table.php';
  } elseif ($tab === 'logs') {
    include plugin_dir_path(__FILE__) . 'partials/logs-viewer.php';
  }
  echo '</div>';
}

function wpff_sp_enqueue_admin_assets($hook) {
  if ($hook !== 'settings_page_super-preloader-for-cloudflare') {
    return;
  }

  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
  $current_tab = isset($_GET['tab'])
  ? sanitize_text_field(wp_unslash($_GET['tab'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab value is only used for read-only display logic.
  : 'settings';    

  wp_enqueue_script(
    'wpff-sp-admin-ui',
    plugin_dir_url(__DIR__) . 'js/admin-ui.js',
    [],
    filemtime(plugin_dir_path(__DIR__) . 'js/admin-ui.js'),
    true
  );

  wp_localize_script('wpff-sp-admin-ui', 'wpff', [
    'nonce' => wp_create_nonce('wpff_sp_preload_nonce'),
    'i18n'  => [
      'running'    => __('Preloader running... Please wait for the first batch to complete.', 'super-preloader-for-cloudflare'),
      'complete'   => __('All URLs have been processed.', 'super-preloader-for-cloudflare'),
      // translators: %d is the number of URLs remaining in the preload queue.
      'remaining'  => __('%d URLs remaining. Background process will continue.', 'super-preloader-for-cloudflare'),
      'error'      => __('Error: ', 'super-preloader-for-cloudflare'),
      'ajaxFailed' => __('AJAX request failed.', 'super-preloader-for-cloudflare'),
      'unknown'    => __('Unknown error.', 'super-preloader-for-cloudflare'),
    ],
  ]);

  // Only enqueue log auto-refresh on logs tab
  if ($current_tab === 'logs') {
    wp_enqueue_script(
      'wpff-sp-log-auto-refresh',
      plugin_dir_url(__DIR__) . 'js/log-auto-refresh.js',
      [],
      filemtime(plugin_dir_path(__DIR__) . 'js/log-auto-refresh.js'),
      true
    );

    wp_localize_script('wpff-sp-log-auto-refresh', 'wpffSpLogs', [
      'nonce' => wp_create_nonce('wpff_sp_logs_nonce')
    ]);
  }  

  // Enqueue CSS
  wp_enqueue_style(
    'wpff-sp-admin-ui-style',
    plugin_dir_url(__DIR__) . 'css/admin-ui.css',
    [],
    filemtime(plugin_dir_path(__DIR__) . 'css/admin-ui.css')
  );
}
