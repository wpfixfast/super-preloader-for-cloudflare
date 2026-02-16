<?php
if (!defined('ABSPATH')) {
  exit();
}
?>

<form method="post">
  <?php wp_nonce_field('wpff_sp_save_settings'); ?>
  <input type="hidden" name="wpff_sp_settings" value="1">

  <table class="form-table">
    <tbody>
      <tr>
        <th>
          <label><?php echo esc_html(__('Cloudflare Worker URL', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <input
            type="text"
            name="worker_url"
            placeholder="<?php echo esc_attr(__('https://your-worker-name.username.workers.dev', 'super-preloader-for-cloudflare')); ?>"
            value="<?php echo esc_attr($worker_url); ?>"
            class="long-url-field"
          />
         <p class="long-description">
          <?php
echo sprintf(
  // translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
  esc_html__(
    '%1$sDownload%2$s and deploy this Cloudflare Worker code to create your Worker URL. More details and detailed guide at our How to Use section below.',
    'super-preloader-for-cloudflare'
  ),
  '<a href="https://gist.github.com/wpfixfast/1d8dc70931f9db5cbda4735227dbe065" target="_blank" rel="noopener">',
  '</a>'
);
?>
        </p>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Proxy List URL', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <input
            type="text"
            name="proxy_list_url"
            placeholder="<?php echo esc_attr(__('https://proxy.webshare.io/api/v2/proxy/list/download/...', 'super-preloader-for-cloudflare')); ?>"
            value="<?php echo esc_url($proxy_url); ?>"
            class="long-url-field"
          />
          <p class="long-description">
            <?php echo esc_html(__('Optional. If not set, requests will go directly from your server and only warm cache at its nearest Cloudflare edge location.', 'super-preloader-for-cloudflare')); ?>
          </p>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Sitemap URL', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <input
            type="text"
            name="sitemap_url"
            placeholder="<?php echo esc_attr(__('https://your-domain.com/sitemap.xml', 'super-preloader-for-cloudflare')); ?>"
            value="<?php echo esc_url($sitemap_url); ?>"
            class="long-url-field"
          />
          <?php
$wpff_sp_url_count = get_option('wpff_sp_sitemap_url_count');
if ($wpff_sp_url_count) {
  printf(
    '<p class="long-description">%s</p>',
    // translators: %d is the number of URLs found during the last preload run.
    esc_html(sprintf(__(' %d URLs were found during the last preload run.', 'super-preloader-for-cloudflare'), $wpff_sp_url_count))
  );
} else {
  echo '<p class="long-description">' .
  esc_html(__('No sitemap data available yet. Run the preloader to count URLs.', 'super-preloader-for-cloudflare')) .
    '</p>';
}
?>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Shared Secret', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <input
            type="text"
            name="shared_secret"
            placeholder="<?php echo esc_attr(__('Secret key defined in CF Worker', 'super-preloader-for-cloudflare')); ?>"
            value="<?php echo esc_attr($shared_secret); ?>"
            class="regular-text"
          />
          <p class="long-description">
            <?php echo esc_html(__('A secret key used to authenticate requests between your site and the Cloudflare Worker. This must match the value defined in your Worker code.', 'super-preloader-for-cloudflare')); ?>
          </p>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Auto Run Interval', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <select name="cron_interval" id="cron_interval">
  <?php
$wpff_sp_intervals = [
  'manual'         => __('Manual Only', 'super-preloader-for-cloudflare'),
  'hourly'         => __('Hourly', 'super-preloader-for-cloudflare'),
  'every_3_hours'  => __('Every 3 Hours', 'super-preloader-for-cloudflare'),
  'every_6_hours'  => __('Every 6 Hours', 'super-preloader-for-cloudflare'),
  'every_12_hours' => __('Every 12 Hours', 'super-preloader-for-cloudflare'),
  'daily'          => __('Daily', 'super-preloader-for-cloudflare'),
  'weekly'         => __('Weekly', 'super-preloader-for-cloudflare'),
  'wpff_sp_custom_interval' => __('Custom Interval...', 'super-preloader-for-cloudflare'),
];
foreach ($wpff_sp_intervals as $wpff_sp_key => $wpff_sp_label) {
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($wpff_sp_key),
    selected($cron_interval, $wpff_sp_key, false),
    esc_html($wpff_sp_label)
  );
}
?>
</select>

<div id="custom_interval_field" style="display: none; margin-top: 10px;">
  <label for="custom_hours">
    <?php esc_html_e('Run every', 'super-preloader-for-cloudflare'); ?>
  </label>
  <input type="number" name="custom_hours" id="custom_hours" 
         value="<?php echo esc_attr(get_option('wpff_sp_custom_hours', 1)); ?>" 
         min="0.25" max="720" step="0.25" style="width: 80px;">
  <span><?php esc_html_e('hour(s)', 'super-preloader-for-cloudflare'); ?></span>
  <small style="display: block; margin-top: 5px; color: #666;">
    <?php esc_html_e('Examples: 0.5 = 30 min, 1 = hourly, 24 = daily, 72 = every 3 days', 'super-preloader-for-cloudflare'); ?>
  </small>
</div>

<script>
document.getElementById('cron_interval').addEventListener('change', function() {
  document.getElementById('custom_interval_field').style.display = 
    this.value === 'wpff_sp_custom_interval' ? 'block' : 'none';
});
// Show on page load if custom is selected
if (document.getElementById('cron_interval').value === 'wpff_sp_custom_interval') {
  document.getElementById('custom_interval_field').style.display = 'block';
}
</script>

<?php
// Display next scheduled run time
if ($cron_interval !== 'manual') {
  $wpff_sp_next_run = wpff_sp_get_next_cron_run();
  ?>
  <p class="description" style="margin-top: 10px;">
    <strong><?php esc_html_e('Next scheduled run:', 'super-preloader-for-cloudflare'); ?></strong>
    <?php echo esc_html($wpff_sp_next_run); ?>
  </p>
  <?php
}
?>
        </td>
      </tr>

      <tr id="wpff_time_row" style="display: none;">
        <th>
          <label><?php echo esc_html(__('Auto Run Start Time', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <select name="cron_start_hour" style="width: 60px;">
          <?php
for ($wpff_sp_hour = 0; $wpff_sp_hour < 24; $wpff_sp_hour++):
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($wpff_sp_hour),
    selected(get_option('wpff_sp_cron_start_hour'), $wpff_sp_hour, false),
    esc_html(sprintf('%02d', $wpff_sp_hour))
  );              
endfor;
        ?>
          </select>
          :
          <select name="cron_start_minute" style="width: 60px;">
          <?php
for ($m = 0; $m < 60; $m++):
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($m),
    selected(get_option('wpff_sp_cron_start_minute'), $m, false),
    esc_html(sprintf('%02d', $m))
  );              
endfor;
        ?>
          </select>
<?php
$wpff_sp_timezone = wp_timezone();
$wpff_sp_current = new DateTime('now', $wpff_sp_timezone);
?>
          <span style="margin-left: 15px; color: #666;">
              <?php echo esc_html(__('Current time', 'super-preloader-for-cloudflare')); ?>: <strong><?php echo esc_html( $wpff_sp_current->format('H:i') ); ?></strong>
          </span>          
          <script>
            // Condifional display based on cron interval
            const el_wpff_time_row = document.getElementById('wpff_time_row');
            const el_cron_interval = document.getElementById('cron_interval')
            el_cron_interval.addEventListener('change', function() {
              let displayTimeFields = (this.value !== 'manual');
              el_wpff_time_row.style.display = displayTimeFields ? 'table-row' : 'none';
            });
            // Trigger change event on page load to set initial visibility
            el_cron_interval.dispatchEvent(new Event('change'));
          </script>             
        </td>
      </tr>      

      <tr>
        <th>
          <label><?php echo esc_html(__('Batch Size (URLs per batch)', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <select name="batch_size" class="mr-5">
            <?php
foreach ([5, 10, 20, 50, 100, 500] as $wpff_sp_batch_size) {
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($wpff_sp_batch_size),
    selected(get_option('wpff_sp_batch_size', 10), $wpff_sp_batch_size, false),
    esc_html($wpff_sp_batch_size)
  );
}
?>
          </select>
          <span class="default-value-text">(<?php echo esc_html(__('Default', 'super-preloader-for-cloudflare')); ?>: 10)</span>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Delay Between URLs (seconds)', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <select name="delay_between_urls" class="mr-5">
            <?php
foreach ([1, 2, 3, 5, 10] as $wpff_sp_delay_between_urls) {
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($wpff_sp_delay_between_urls),
    selected(get_option('wpff_sp_delay_between_urls', 1), $wpff_sp_delay_between_urls, false),
    esc_html($wpff_sp_delay_between_urls)
  );
}
?>
          </select>
          <span class="default-value-text">(<?php echo esc_html(__('Default', 'super-preloader-for-cloudflare')); ?>: 1)</span>
        </td>
      </tr>      

      <tr>
        <th>
          <label for="wpff_sp_delete_data_on_uninstall"><?php echo esc_html(__('Delete Data on Uninstall', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <input
            type="checkbox"
            name="wpff_sp_delete_data_on_uninstall"
            id="wpff_sp_delete_data_on_uninstall"
            value="1"
            <?php checked(get_option('wpff_sp_delete_data_on_uninstall'), '1'); ?>
          />
          <label for="wpff_sp_delete_data_on_uninstall"><?php echo esc_html(__('Remove all plugin data and logs when the plugin is deleted.', 'super-preloader-for-cloudflare')); ?></label>
        </td>
      </tr>
    </tbody>
  </table>

  <p>
    <input
      type="submit"
      class="button button-primary"
      value="<?php echo esc_attr(__('Save Settings', 'super-preloader-for-cloudflare')); ?>"
    />
  </p>
</form>
