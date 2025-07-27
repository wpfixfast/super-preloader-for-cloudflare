<?php
if (!defined('ABSPATH')) {
  exit();
}

if (!isset($stats) || !is_array($stats)) {
  $stats = [];
}
$site_url = get_site_url();

// Get summary data
$summary = wpff_sp_get_stats_summary($stats);
?>

<?php if (!empty($stats)): ?>
<!-- Summary Section -->
<div class="wpff-sp-summary">
  <section>
    <!-- Overall Stats Section -->
    <div class="wpff-sp-summary-section wpff-sp-overall-section">
      <div class="wpff-sp-section-header">
        <h5><?php
        // translators: %d is the number of completed runs
        echo esc_html(sprintf(__('Cumulative Stats Across All Runs (%d Completed)', 'super-preloader-for-cloudflare'), $summary['run_number']));
        ?></h5>
        <p class="wpff-sp-section-description"><?php echo esc_html(__('Aggregated data from all completed preloader runs', 'super-preloader-for-cloudflare')); ?></p>
      </div>
      <div class="wpff-sp-summary-grid wpff-sp-overall-grid">
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['total_urls']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('URLs preloaded', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['unique_edges']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique edge locations', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['unique_regions']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique regions', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['unique_countries']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique countries', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <?php if ($summary['cumulative']['most_common_edge']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['most_common_edge']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common edge', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_edge_frequency($summary['cumulative']['edge_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($summary['cumulative']['most_common_country']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['most_common_country']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common country', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_edge_frequency($summary['cumulative']['country_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>        
        <?php if ($summary['cumulative']['most_common_region']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['cumulative']['most_common_region']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common region', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_region_frequency($summary['cumulative']['region_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <section>
    <!-- Latest Run Stats -->
    <?php if (!empty($summary['latest_run']) && $summary['run_number'] > 0): ?>
    <div class="wpff-sp-summary-section wpff-sp-latest-section">
      <div class="wpff-sp-section-header">
        <h5><?php
        // translators: %d is the latest run number
        echo esc_html(sprintf(__('Latest Run (#%d)', 'super-preloader-for-cloudflare'), $summary['run_number']));
        ?></h5>
        <p class="wpff-sp-section-description"><?php echo esc_html(__('Performance data from the most recent preloader run', 'super-preloader-for-cloudflare')); ?></p>
      </div>
      <div class="wpff-sp-summary-grid wpff-sp-overall-grid">
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['urls_monitored']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('URLs preloaded', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['unique_edges']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique edge locations', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['unique_regions']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique regions', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <div class="wpff-sp-summary-item">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['unique_countries']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Unique countries', 'super-preloader-for-cloudflare')); ?></div>
        </div>
        <?php if ($summary['latest_run']['most_common_edge']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['most_common_edge']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common edge', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_edge_frequency($summary['latest_run']['edge_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($summary['latest_run']['most_common_country']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['most_common_country']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common country', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_edge_frequency($summary['latest_run']['country_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>          
        <?php if ($summary['latest_run']['most_common_region']): ?>
        <div class="wpff-sp-summary-item wide">
          <div class="wpff-sp-metric-value"><?php echo esc_html($summary['latest_run']['most_common_region']); ?></div>
          <div class="wpff-sp-metric-label"><?php echo esc_html(__('Most common region', 'super-preloader-for-cloudflare')); ?></div>
          <div class="wpff-sp-frequency">
            <?php echo wp_kses_post(wpff_sp_format_region_frequency($summary['latest_run']['region_frequency'], 3)); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </section>

</div>
<?php endif; ?>

<?php if (!empty($stats)): ?>
  <div class="wpff-sp-stats-table-wrapper">
    <table class="widefat wpff-sp-stats-table">
      <thead>
        <tr>
          <th><?php echo esc_html(__('URL', 'super-preloader-for-cloudflare')); ?></th>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <th>
              <?php
  // translators: %d is the run number in the preload stats table header.
  echo esc_html(sprintf(__('Run #%d', 'super-preloader-for-cloudflare'), $i));
  ?>
            </th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php
  $row_index = 0;

  foreach ($stats as $url => $entries) {
    $row_class = $row_index % 2 === 1 ? 'bg-light-gray' : '';

    // Strip base URL to show only relative path
    $display_url = str_replace($site_url, '', $url);

    if ($display_url === $url) {
      // Fallback if domain doesn't match
      $display_url = wp_parse_url($url, PHP_URL_PATH);
    }

    if ($display_url === '' || $display_url === '/') {
      // Show homepage as full site URL
      $display_url = trailingslashit($url);
    }
    ?>
          <tr class="<?php echo esc_attr($row_class); ?>">
            <td>
              <a href="<?php echo esc_url($url); ?>" target="_blank">
                <?php echo esc_html($display_url); ?>
              </a>
            </td>
            <?php
  $entries = array_slice($entries, -5);
    foreach ($entries as $entry) {
      echo '<td>' . wp_kses_post(wpff_sp_format_edge_entry($entry)) . '</td>';
    }

    for ($i = count($entries); $i < 5; $i++) {
      echo '<td></td>';
    }
    ?>
          </tr>
          <?php
  $row_index++;
  }
  ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="notice notice-info">
  <p><?php echo esc_html(__('No stats available yet. Run preloader to generate stats.', 'super-preloader-for-cloudflare')); ?></p>
  </div>  
<?php endif; ?>