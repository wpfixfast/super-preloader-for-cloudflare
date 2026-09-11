<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// ============================================================
// Cache Coverage — live from Cloudflare Analytics, distinct from the
// plugin's own preload run history (Stats tab): shows where actual visitor
// traffic is missing cache, so the user knows which Webshare proxy
// countries to add. Its own tab since the Stats tab had gotten crowded.
// ============================================================
$wpff_sp_coverage_ready = (bool) get_option( 'wpff_sp_cf_api_token' ) && (bool) get_option( 'wpff_sp_cf_zone_id' );

// A previously-cached failure (e.g. a token missing the Analytics
// permission — see WPFF_SP_Cloudflare_Analytics::get_cached_error(), the
// single source of truth for this) is checked before anything else. No
// point rendering the tiles' skeleton state only to have an AJAX call
// immediately repeat the same failure and hide them again — render the
// same warning directly instead.
$wpff_sp_coverage_cached_error = $wpff_sp_coverage_ready ? WPFF_SP_Cloudflare_Analytics::get_cached_error() : false;

// Render the real numbers directly whenever the relevant cache is already
// warm — only defer to the skeleton+AJAX path when it'd actually require a
// live query. The 24h and 7d windows have separate, independently-expiring
// caches, so each is checked on its own; either can be warm while the
// other isn't.
$wpff_sp_coverage_24h_summary = null;
$wpff_sp_coverage_7d_summary  = null;
if ( $wpff_sp_coverage_ready && ! $wpff_sp_coverage_cached_error ) {
	$wpff_sp_coverage_error = '';

	if ( WPFF_SP_Cloudflare_Analytics::has_cached_stats( '24h' ) ) {
		$wpff_sp_coverage_24h_summary = WPFF_SP_Cloudflare_Analytics::get_summary( '24h', false, $wpff_sp_coverage_error );
	}

	if ( WPFF_SP_Cloudflare_Analytics::has_cached_stats( '7d' ) ) {
		$wpff_sp_coverage_7d_summary = WPFF_SP_Cloudflare_Analytics::get_summary( '7d', false, $wpff_sp_coverage_error );
	}
}
?>

<h3><?php echo esc_html( __( 'Cache Coverage', 'super-preloader-for-cloudflare' ) ); ?></h3>

<?php if ( ! $wpff_sp_coverage_ready ) : ?>
<p class="wpff-sp-coverage-not-connected">
	<a href="<?php echo esc_url( admin_url( 'options-general.php?page=super-preloader-for-cloudflare#wpff-sp-cf-connect-section' ) ); ?>">
	<?php
	printf(
	// translators: %1$s is the opening strong tag, %2$s is the closing strong tag.
		esc_html__( 'Grant the %1$sAnalytics Read%2$s permission to your API token to access this report →', 'super-preloader-for-cloudflare' ),
		'<strong>',
		'</strong>'
	);
	?>
	</a>
</p>
<?php elseif ( $wpff_sp_coverage_cached_error ) : ?>
<p class="wpff-sp-coverage-not-connected"><?php echo esc_html( $wpff_sp_coverage_cached_error ); ?></p>
<?php else : ?>

<div class="wpff-sp-coverage-wrap">

	<p class="wpff-sp-coverage-error" id="wpff-sp-coverage-error" style="display: none;"></p>

	<div class="wpff-sp-coverage-tiles">

		<div class="wpff-sp-sidebar-card">
		<div class="wpff-sp-sidebar-card-header">
			<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Last 24 Hours', 'super-preloader-for-cloudflare' ) ); ?></span>
		</div>
		<?php if ( is_array( $wpff_sp_coverage_24h_summary ) ) : ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count" id="wpff-sp-coverage-today-hitrate"><?php echo esc_html( null !== $wpff_sp_coverage_24h_summary['hitRatePct'] ? $wpff_sp_coverage_24h_summary['hitRatePct'] . '%' : '—' ); ?></div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-today-misses">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %1$d is the request count, %2$d is the miss count, both for the last 24 hours. */
					__( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
					$wpff_sp_coverage_24h_summary['requests'],
					$wpff_sp_coverage_24h_summary['misses']
				)
			);
			?>
		</span>
		<?php else : ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count wpff-sp-skeleton" id="wpff-sp-coverage-today-hitrate" data-wpff-sp-loading="1">…</div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-today-misses"><span class="wpff-sp-skeleton wpff-sp-skeleton-bar" style="width: 180px;"></span></span>
		<?php endif; ?>
		</div>

		<div class="wpff-sp-sidebar-card">
		<div class="wpff-sp-sidebar-card-header">
			<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Last 7 Days', 'super-preloader-for-cloudflare' ) ); ?></span>
		</div>
		<?php if ( is_array( $wpff_sp_coverage_7d_summary ) ) : ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count" id="wpff-sp-coverage-7d-hitrate"><?php echo esc_html( null !== $wpff_sp_coverage_7d_summary['hitRatePct'] ? $wpff_sp_coverage_7d_summary['hitRatePct'] . '%' : '—' ); ?></div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-7d-misses">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %1$d is the request count, %2$d is the miss count, both for the last 7 days. */
					__( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
					$wpff_sp_coverage_7d_summary['requests'],
					$wpff_sp_coverage_7d_summary['misses']
				)
			);
			?>
		</span>
		<?php else : ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count wpff-sp-skeleton" id="wpff-sp-coverage-7d-hitrate" data-wpff-sp-loading="1">…</div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-7d-misses"><span class="wpff-sp-skeleton wpff-sp-skeleton-bar" style="width: 190px;"></span></span>
		<?php endif; ?>
		</div>

		<div class="wpff-sp-sidebar-card">
		<div class="wpff-sp-sidebar-card-header">
			<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Worst Coverage', 'super-preloader-for-cloudflare' ) ); ?></span>
		</div>
		<?php if ( is_array( $wpff_sp_coverage_7d_summary ) ) : ?>
			<?php $wpff_sp_coverage_worst = $wpff_sp_coverage_7d_summary['worstCountry']; ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count" id="wpff-sp-coverage-worst-country"><?php echo esc_html( $wpff_sp_coverage_worst ? $wpff_sp_coverage_worst['code'] : '—' ); ?></div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-worst-misses">
			<?php
			if ( $wpff_sp_coverage_worst ) {
				echo esc_html(
					sprintf(
						/* translators: %1$d is the request count, %2$d is the miss count, for a single country with no time period implied. */
						__( '%1$d requests - %2$d missed', 'super-preloader-for-cloudflare' ),
						$wpff_sp_coverage_worst['requests'],
						$wpff_sp_coverage_worst['misses']
					)
				);
			}
			?>
		</span>
		<?php else : ?>
		<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count wpff-sp-skeleton" id="wpff-sp-coverage-worst-country">…</div>
		<span class="wpff-sp-coverage-sidebar-note" id="wpff-sp-coverage-worst-misses"><span class="wpff-sp-skeleton wpff-sp-skeleton-bar" style="width: 170px;"></span></span>
		<?php endif; ?>
		</div>

	</div>

	<p class="wpff-sp-coverage-table-caption"><?php echo esc_html( __( 'Top 10 countries — last 7 days', 'super-preloader-for-cloudflare' ) ); ?></p>

	<div class="wpff-sp-coverage-table-wrapper">
		<table class="wp-list-table widefat fixed striped wpff-sp-coverage-table">
		<thead>
			<tr>
			<th><?php echo esc_html( __( 'Country', 'super-preloader-for-cloudflare' ) ); ?></th>
			<th><?php echo esc_html( __( 'Requests', 'super-preloader-for-cloudflare' ) ); ?></th>
			<th><?php echo esc_html( __( 'Misses', 'super-preloader-for-cloudflare' ) ); ?></th>
			<th><?php echo esc_html( __( 'Hit Rate', 'super-preloader-for-cloudflare' ) ); ?></th>
			</tr>
		</thead>
		<tbody id="wpff-sp-coverage-table-body">
		<?php if ( is_array( $wpff_sp_coverage_7d_summary ) ) : ?>
			<?php if ( empty( $wpff_sp_coverage_7d_summary['top10'] ) ) : ?>
			<tr>
				<td colspan="4"><?php echo esc_html( __( 'No misses in the last 7 days. Great coverage!', 'super-preloader-for-cloudflare' ) ); ?></td>
			</tr>
			<?php else : ?>
				<?php foreach ( $wpff_sp_coverage_7d_summary['top10'] as $wpff_sp_coverage_row ) : ?>
				<tr>
					<td><?php echo esc_html( $wpff_sp_coverage_row['code'] ); ?></td>
					<td><?php echo esc_html( $wpff_sp_coverage_row['requests'] ); ?></td>
					<td><?php echo esc_html( $wpff_sp_coverage_row['misses'] ); ?></td>
					<td><?php echo esc_html( number_format( $wpff_sp_coverage_row['hitRatePct'], 1 ) ); ?>%</td>
				</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
		</tbody>
		</table>
	</div>

	<p class="wpff-sp-coverage-suggestion">
		<?php echo esc_html( __( 'Consider adding Webshare proxies for:', 'super-preloader-for-cloudflare' ) ); ?>
		<strong id="wpff-sp-coverage-suggested">
		<?php
		if ( is_array( $wpff_sp_coverage_7d_summary ) ) {
			echo esc_html(
				! empty( $wpff_sp_coverage_7d_summary['suggested'] )
					? implode( ', ', $wpff_sp_coverage_7d_summary['suggested'] )
					: __( 'No countries need attention right now.', 'super-preloader-for-cloudflare' )
			);
		}
		?>
		</strong>
	</p>

</div>

<?php endif; ?>
