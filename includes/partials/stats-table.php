<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! isset( $wpff_sp_stats ) || ! is_array( $wpff_sp_stats ) ) {
	$wpff_sp_stats = array();
}
$wpff_sp_site_url   = get_site_url();
$wpff_sp_last_run   = get_option( 'wpff_sp_last_run_meta', array() );
$wpff_sp_full_proxy = get_option( 'wpff_sp_full_proxy_pass' );
?>

<h3><?php echo esc_html( __( 'Stats', 'super-preloader-for-cloudflare' ) ); ?></h3>

<?php
// ============================================================
// Full Proxy Pass mode — simple stats block, no URL table
// ============================================================
if ( $wpff_sp_full_proxy ) :
	?>

	<?php if ( ! empty( $wpff_sp_stats ) ) : ?>
<div class="wpff-sp-summary">
	<section>
	<div class="wpff-sp-summary-section">
		<div class="wpff-sp-section-header">
		<h4><?php echo esc_html( __( 'Full Proxy Pass Stats', 'super-preloader-for-cloudflare' ) ); ?></h4>
		<p class="wpff-sp-section-description"><?php echo esc_html( __( 'Summary from the last Full Proxy Pass run. Per-URL stats are not recorded in this mode.', 'super-preloader-for-cloudflare' ) ); ?></p>
		</div>
		<div class="wpff-sp-summary-grid">
		<?php if ( isset( $wpff_sp_stats['total_urls'] ) ) : ?>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_stats['total_urls'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'URLs', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<?php endif; ?>
		<?php if ( isset( $wpff_sp_stats['total_proxies'] ) ) : ?>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_stats['total_proxies'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Proxies', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<?php endif; ?>
		<?php if ( isset( $wpff_sp_stats['total_requests'] ) ) : ?>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_stats['total_requests'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Total requests', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $wpff_sp_stats['edges'] ) ) : ?>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( count( array_unique( $wpff_sp_stats['edges'] ) ) ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Unique edge locations', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<?php endif; ?>
		</div>
	</div>
	</section>
</div>
<?php else : ?>
<div class="notice notice-info">
	<p><?php echo esc_html( __( 'No stats available yet. Run preloader to generate stats.', 'super-preloader-for-cloudflare' ) ); ?></p>
</div>
<?php endif; ?>

	<?php
	// ============================================================
	// Normal mode — existing summary and URL table, fully unchanged
	// ============================================================
else :

	// Get summary data
	$wpff_sp_summary = WPFF_SP_Helpers::get_stats_summary( $wpff_sp_stats );

	?>

	<?php if ( ! empty( $wpff_sp_stats ) ) : ?>
<!-- Summary Section -->
<div class="wpff-sp-summary">
	<section>
	<!-- Overall Stats Section -->
	<div class="wpff-sp-summary-section wpff-sp-overall-section">
		<div class="wpff-sp-section-header">
		<h4>
		<?php
		// translators: %d is the number of completed runs
		echo esc_html( sprintf( __( 'Cumulative stats across all runs (%d completed)', 'super-preloader-for-cloudflare' ), $wpff_sp_summary['run_number'] ) );
		?>
		</h4>
		</div>
		<div class="wpff-sp-summary-grid wpff-sp-overall-grid">
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['total_urls'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'URLs preloaded', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['unique_edges'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Unique edge locations', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['unique_regions'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Unique regions', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<div class="wpff-sp-summary-item">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['unique_countries'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Unique countries', 'super-preloader-for-cloudflare' ) ); ?></div>
		</div>
		<?php if ( $wpff_sp_summary['cumulative']['most_common_edge'] ) : ?>
		<div class="wpff-sp-summary-item wide">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['most_common_edge'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Most common edge', 'super-preloader-for-cloudflare' ) ); ?></div>
			<div class="wpff-sp-frequency">
			<?php echo wp_kses_post( WPFF_SP_Helpers::format_edge_frequency( $wpff_sp_summary['cumulative']['edge_frequency'], 3 ) ); ?>
			</div>
		</div>
		<?php endif; ?>
		<?php if ( $wpff_sp_summary['cumulative']['most_common_country'] ) : ?>
		<div class="wpff-sp-summary-item wide">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['most_common_country'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Most common country', 'super-preloader-for-cloudflare' ) ); ?></div>
			<div class="wpff-sp-frequency">
			<?php echo wp_kses_post( WPFF_SP_Helpers::format_edge_frequency( $wpff_sp_summary['cumulative']['country_frequency'], 3 ) ); ?>
			</div>
		</div>
		<?php endif; ?>        
		<?php if ( $wpff_sp_summary['cumulative']['most_common_region'] ) : ?>
		<div class="wpff-sp-summary-item wide">
			<div class="wpff-sp-metric-value"><?php echo esc_html( $wpff_sp_summary['cumulative']['most_common_region'] ); ?></div>
			<div class="wpff-sp-metric-label"><?php echo esc_html( __( 'Most common region', 'super-preloader-for-cloudflare' ) ); ?></div>
			<div class="wpff-sp-frequency">
			<?php echo wp_kses_post( WPFF_SP_Helpers::format_region_frequency( $wpff_sp_summary['cumulative']['region_frequency'], 3 ) ); ?>
			</div>
		</div>
		<?php endif; ?>
		</div>
	</div>
	</section>

</div>
	<?php endif; ?>

	<?php if ( ! empty( $wpff_sp_stats ) ) : ?>
		<?php
		// Only render as many Run columns as the URL with the most history
		// actually has, instead of always showing 5 — most sites won't have
		// 5 completed runs yet, and empty columns just add clutter.
		$wpff_sp_run_columns = 0;
		foreach ( $wpff_sp_stats as $wpff_sp_run_entries ) {
			$wpff_sp_run_columns = max( $wpff_sp_run_columns, count( array_slice( $wpff_sp_run_entries, -5 ) ) );
		}
		?>
	<div class="wpff-sp-stats-table-wrapper">
	<table class="wp-list-table widefat fixed striped wpff-sp-stats-table">
		<thead>
		<tr>
			<th><?php echo esc_html( __( 'URL', 'super-preloader-for-cloudflare' ) ); ?></th>
			<?php for ( $wpff_sp_i = 1; $wpff_sp_i <= $wpff_sp_run_columns; $wpff_sp_i++ ) : ?>
			<th>
				<?php
				// translators: %d is the run number in the preload stats table header.
				echo esc_html( sprintf( __( 'Run #%d', 'super-preloader-for-cloudflare' ), $wpff_sp_i ) );
				?>
			</th>
			<?php endfor; ?>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $wpff_sp_stats as $wpff_sp_url => $wpff_sp_entries ) {
			// Strip base URL to show only relative path
			$wpff_sp_display_url = str_replace( $wpff_sp_site_url, '', $wpff_sp_url );

			if ( $wpff_sp_display_url === $wpff_sp_url ) {
				// Fallback if domain doesn't match
				$wpff_sp_display_url = wp_parse_url( $wpff_sp_url, PHP_URL_PATH );
			}

			if ( '' === $wpff_sp_display_url || '/' === $wpff_sp_display_url ) {
				// Show homepage as full site URL
				$wpff_sp_display_url = trailingslashit( $wpff_sp_url );
			}
			?>
			<tr>
			<td>
				<a href="<?php echo esc_url( $wpff_sp_url ); ?>" target="_blank">
				<?php echo esc_html( $wpff_sp_display_url ); ?>
				</a>
			</td>
			<?php
			$wpff_sp_entries = array_slice( $wpff_sp_entries, -5 );
			foreach ( $wpff_sp_entries as $wpff_sp_entry ) {
				echo '<td>' . wp_kses_post( WPFF_SP_Helpers::format_edge_entry( $wpff_sp_entry ) ) . '</td>';
			}

			for ( $wpff_sp_i = count( $wpff_sp_entries ); $wpff_sp_i < $wpff_sp_run_columns; $wpff_sp_i++ ) {
				echo '<td></td>';
			}
			?>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	</div>
<?php else : ?>
	<div class="notice notice-info">
	<p><?php echo esc_html( __( 'No stats available yet. Run preloader to generate stats.', 'super-preloader-for-cloudflare' ) ); ?></p>
	</div>  
<?php endif; ?>

<?php endif; // end normal mode ?>
