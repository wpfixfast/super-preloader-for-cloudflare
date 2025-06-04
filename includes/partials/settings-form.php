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
$url_count = get_option('wpff_sp_sitemap_url_count');
if ($url_count) {
  printf(
    '<p class="long-description">%s</p>',
    // translators: %d is the number of URLs found during the last preload run.
    esc_html(sprintf(__(' %d URLs were found during the last preload run.', 'super-preloader-for-cloudflare'), $url_count))
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
          <select name="cron_interval">
            <?php
$intervals = [
  'manual'     => __('Manual Only', 'super-preloader-for-cloudflare'),
  'hourly'     => __('Hourly', 'super-preloader-for-cloudflare'),
  'twicedaily' => __('Twice Daily', 'super-preloader-for-cloudflare'),
  'daily'      => __('Daily', 'super-preloader-for-cloudflare'),
  'weekly'     => __('Weekly', 'super-preloader-for-cloudflare'),
];
foreach ($intervals as $key => $label) {
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($key),
    selected($cron_interval, $key, false),
    esc_html($label)
  );
}
?>
          </select>
        </td>
      </tr>

      <tr>
        <th>
          <label><?php echo esc_html(__('Batch Size (URLs per batch)', 'super-preloader-for-cloudflare')); ?></label>
        </th>
        <td>
          <select name="batch_size">
            <?php
foreach ([5, 10, 20, 50, 100, 500] as $size) {
  printf(
    '<option value="%s"%s>%s</option>',
    esc_attr($size),
    selected(get_option('wpff_sp_batch_size', 10), $size, false),
    esc_html($size)
  );
}
?>
          </select>
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
