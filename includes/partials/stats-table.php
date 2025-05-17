<?php
if (!defined('ABSPATH')) {
  exit();
}

if (!isset($stats) || !is_array($stats)) {
  $stats = [];
}
$site_url = get_site_url();
?>

<div style="overflow-x: auto;">
  <table class="widefat wpff-sp-stats-table">
    <thead>
      <tr>
        <th><?php echo esc_html(__('URL', 'wpfixfast-super-preloader')); ?></th>
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <th>
            <?php
// translators: %d is the run number in the preload stats table header.
echo esc_html(sprintf(__('Run #%d', 'wpfixfast-super-preloader'), $i));
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
    echo '<td>' . esc_html($entry) . '</td>';
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
