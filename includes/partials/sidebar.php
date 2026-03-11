<?php
if (!defined('ABSPATH')) {
  exit();
}

// Current mode
$wpff_sp_sidebar_full_proxy = get_option('wpff_sp_full_proxy_pass');
$wpff_sp_sidebar_mode_label = $wpff_sp_sidebar_full_proxy
  ? __('Full Proxy Pass', 'super-preloader-for-cloudflare')
  : __('Normal', 'super-preloader-for-cloudflare');

// Current status
$wpff_sp_sidebar_is_running = (bool) get_transient('wpff_sp_preload_cursor');

// Last run meta
$wpff_sp_sidebar_last_run = get_option('wpff_sp_last_run_meta', []);

// URL count
$wpff_sp_sidebar_url_count = get_option('wpff_sp_sitemap_url_count', null);

// Proxy count — cached for 24 hours to avoid fetching on every page load
$wpff_sp_sidebar_proxy_count = get_transient('wpff_sp_proxy_count_cache');
if ($wpff_sp_sidebar_proxy_count === false) {
  $wpff_sp_sidebar_proxy_url = get_option('wpff_sp_proxy_list_url');
  if (!empty($wpff_sp_sidebar_proxy_url)) {
    $wpff_sp_sidebar_proxy_response = wp_remote_get($wpff_sp_sidebar_proxy_url);
    if (!is_wp_error($wpff_sp_sidebar_proxy_response)) {
      $wpff_sp_sidebar_proxy_lines = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n/', wp_remote_retrieve_body($wpff_sp_sidebar_proxy_response)))
      ));
      $wpff_sp_sidebar_proxy_count = count($wpff_sp_sidebar_proxy_lines);
    } else {
      $wpff_sp_sidebar_proxy_count = 0;
    }
  } else {
    $wpff_sp_sidebar_proxy_count = 0;
  }
  set_transient('wpff_sp_proxy_count_cache', $wpff_sp_sidebar_proxy_count, 24 * HOUR_IN_SECONDS);
}
?>

<div class="wpff-sp-sidebar">

  <?php // Mode ?>
  <div class="wpff-sp-sidebar-card">
    <div class="wpff-sp-sidebar-card-header">      
      <span class="wpff-sp-sidebar-label"><?php echo esc_html(__('Mode', 'super-preloader-for-cloudflare')); ?></span>
      <img class="wpff-sp-sidebar-icon" src="<?php echo esc_url(WPFF_SP_PLUGIN_URL . 'images/mode.svg'); ?>" width="20" height="20" alt="Settings icon" />
    </div>
    <div class="wpff-sp-sidebar-value <?php echo $wpff_sp_sidebar_full_proxy ? 'wpff-sp-mode-fpp' : 'wpff-sp-mode-normal'; ?>">
      <?php echo esc_html($wpff_sp_sidebar_mode_label); ?>
    </div>
    <?php if ($wpff_sp_sidebar_full_proxy): ?>
    <p class="wpff-sp-sidebar-note"><?php echo esc_html(__('As every URL will hit each proxy, run times may be significantly longer in this mode.', 'super-preloader-for-cloudflare')); ?></p>
    <?php endif; ?>    
  </div>

  <?php // Status ?>
  <div class="wpff-sp-sidebar-card">
    <div class="wpff-sp-sidebar-card-header">
      <span class="wpff-sp-sidebar-label"><?php echo esc_html(__('Status', 'super-preloader-for-cloudflare')); ?></span>
      <img class="wpff-sp-sidebar-icon" src="<?php echo esc_url(WPFF_SP_PLUGIN_URL . 'images/status.svg'); ?>" width="20" height="20" alt="Status icon" />
    </div>
    <div class="wpff-sp-sidebar-value">
      <?php if ($wpff_sp_sidebar_is_running): ?>
        <span class="wpff-sp-status-badge wpff-sp-status-running"><?php echo esc_html(__('Running', 'super-preloader-for-cloudflare')); ?></span>
      <?php else: ?>
        <span class="wpff-sp-status-badge wpff-sp-status-idle"><?php echo esc_html(__('Idle', 'super-preloader-for-cloudflare')); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <?php // Last Run ?>
  <?php if (!empty($wpff_sp_sidebar_last_run)): ?>
  <div class="wpff-sp-sidebar-card wpff-sp-sidebar-card-last-run">
    <div class="wpff-sp-sidebar-card-header">
      <span class="wpff-sp-sidebar-label"><?php echo esc_html(__('Last Run', 'super-preloader-for-cloudflare')); ?></span>
      <img class="wpff-sp-sidebar-icon" src="<?php echo esc_url(WPFF_SP_PLUGIN_URL . 'images/last-run.svg'); ?>" width="20" height="20" alt="Last run icon" />
    </div>
    <div class="wpff-sp-sidebar-meta">
      <?php if (!empty($wpff_sp_sidebar_last_run['started_at'])): ?>
      <div class="wpff-sp-sidebar-meta-row">
        <span class="wpff-sp-sidebar-meta-label"><?php echo esc_html(__('Started', 'super-preloader-for-cloudflare')); ?></span>
        <span class="wpff-sp-sidebar-meta-value"><?php echo esc_html($wpff_sp_sidebar_last_run['started_at']); ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($wpff_sp_sidebar_last_run['completed_at'])): ?>
      <div class="wpff-sp-sidebar-meta-row">
        <span class="wpff-sp-sidebar-meta-label"><?php echo esc_html(__('Completed', 'super-preloader-for-cloudflare')); ?></span>
        <span class="wpff-sp-sidebar-meta-value"><?php echo esc_html($wpff_sp_sidebar_last_run['completed_at']); ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($wpff_sp_sidebar_last_run['duration'])): ?>
      <div class="wpff-sp-sidebar-meta-row">
        <span class="wpff-sp-sidebar-meta-label"><?php echo esc_html(__('Duration', 'super-preloader-for-cloudflare')); ?></span>
        <span class="wpff-sp-sidebar-meta-value"><?php echo esc_html($wpff_sp_sidebar_last_run['duration']); ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php // URL Count ?>
  <?php if ($wpff_sp_sidebar_url_count !== null): ?>
  <div class="wpff-sp-sidebar-card">
    <div class="wpff-sp-sidebar-card-header">
      <span class="wpff-sp-sidebar-label"><?php echo esc_html(__('URLs', 'super-preloader-for-cloudflare')); ?></span>
      <img class="wpff-sp-sidebar-icon" src="<?php echo esc_url(WPFF_SP_PLUGIN_URL . 'images/link.svg'); ?>" width="20" height="20" alt="URL icon" />
    </div>
    <div class="wpff-sp-sidebar-value wpff-sp-sidebar-count">
      <?php echo esc_html($wpff_sp_sidebar_url_count); ?>
    </div>
  </div>
  <?php endif; ?>

  <?php // Proxy Count ?>
  <div class="wpff-sp-sidebar-card">
    <div class="wpff-sp-sidebar-card-header">
      <span class="wpff-sp-sidebar-label"><?php echo esc_html(__('Proxies', 'super-preloader-for-cloudflare')); ?></span>
      <img class="wpff-sp-sidebar-icon" src="<?php echo esc_url(WPFF_SP_PLUGIN_URL . 'images/globe.svg'); ?>" width="20" height="20" alt="Globe icon" />
    </div>
    <div class="wpff-sp-sidebar-value wpff-sp-sidebar-count">
      <?php echo $wpff_sp_sidebar_proxy_count > 0 ? esc_html($wpff_sp_sidebar_proxy_count) : esc_html(__('None', 'super-preloader-for-cloudflare')); ?>
    </div>
  </div>

</div>