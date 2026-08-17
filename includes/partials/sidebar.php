<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Current mode
$wpff_sp_sidebar_full_proxy = get_option( 'wpff_sp_full_proxy_pass' );
$wpff_sp_sidebar_mode_label = $wpff_sp_sidebar_full_proxy
	? __( 'Full Proxy Pass', 'super-preloader-for-cloudflare' )
	: __( 'Normal', 'super-preloader-for-cloudflare' );

// Current status
$wpff_sp_sidebar_is_running = (bool) get_transient( 'wpff_sp_preload_cursor' );
$wpff_sp_sidebar_remaining  = WPFF_SP_Preloader::get_remaining_count();

// Last run meta
$wpff_sp_sidebar_last_run = get_option( 'wpff_sp_last_run_meta', array() );

// URL count
$wpff_sp_sidebar_url_count = get_option( 'wpff_sp_sitemap_url_count', null );

// Proxy count — refreshed when the proxy list URL is saved and after each run
$wpff_sp_sidebar_proxy_count = (int) get_option( 'wpff_sp_proxy_count', 0 );

// Worker status — read-only cache lookup, never triggers a live check on
// page render. JS refreshes this via AJAX after load (see js/admin-ui.js).
$wpff_sp_sidebar_worker_status = WPFF_SP_Preloader::get_cached_worker_status();

$wpff_sp_worker_status_labels = array(
	'working'        => __( 'Deployed & Working', 'super-preloader-for-cloudflare' ),
	'not_deployed'   => __( 'Not Deployed', 'super-preloader-for-cloudflare' ),
	'not_responding' => __( 'Not Responding', 'super-preloader-for-cloudflare' ),
);

$wpff_sp_worker_status_classes = array(
	'working'        => 'wpff-sp-status-connected',
	'not_deployed'   => 'wpff-sp-status-idle',
	'not_responding' => 'wpff-sp-status-error',
);
?>

<div class="wpff-sp-sidebar">

	<?php // Worker Status ?>
	<div class="wpff-sp-sidebar-card">
	<div class="wpff-sp-sidebar-card-header">
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Worker Status', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/cloud-cog.svg' ); ?>" width="20" height="20" alt="Worker status icon" />
	</div>
	<div class="wpff-sp-sidebar-value">
		<?php if ( null === $wpff_sp_sidebar_worker_status ) : ?>
		<span id="wpff-sp-worker-status-badge" class="wpff-sp-status-badge wpff-sp-status-idle"><?php echo esc_html( __( 'Checking…', 'super-preloader-for-cloudflare' ) ); ?></span>
		<?php else : ?>
		<span id="wpff-sp-worker-status-badge" class="wpff-sp-status-badge <?php echo esc_attr( $wpff_sp_worker_status_classes[ $wpff_sp_sidebar_worker_status ] ); ?>">
			<?php echo esc_html( $wpff_sp_worker_status_labels[ $wpff_sp_sidebar_worker_status ] ); ?>
		</span>
		<?php endif; ?>
	</div>
	</div>

	<?php // Mode ?>
	<div class="wpff-sp-sidebar-card">
	<div class="wpff-sp-sidebar-card-header">      
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Preloader Mode', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/mode.svg' ); ?>" width="20" height="20" alt="Settings icon" />
	</div>
	<div class="wpff-sp-sidebar-value <?php echo $wpff_sp_sidebar_full_proxy ? 'wpff-sp-mode-fpp' : 'wpff-sp-mode-normal'; ?>">
		<?php echo esc_html( $wpff_sp_sidebar_mode_label ); ?>
	</div>
	<?php if ( $wpff_sp_sidebar_full_proxy ) : ?>
	<p class="wpff-sp-sidebar-note"><?php echo esc_html( __( 'As every URL will hit each proxy, run times may be significantly longer in this mode.', 'super-preloader-for-cloudflare' ) ); ?></p>
	<?php endif; ?>    
	</div>

	<?php // Status ?>
	<div class="wpff-sp-sidebar-card">
	<div class="wpff-sp-sidebar-card-header">
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Preloader Status', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/status.svg' ); ?>" width="20" height="20" alt="Status icon" />
	</div>
	<div class="wpff-sp-sidebar-value">
		<?php if ( $wpff_sp_sidebar_is_running ) : ?>
		<span id="wpff-sp-status-badge" class="wpff-sp-status-badge wpff-sp-status-running"><?php echo esc_html( __( 'Running', 'super-preloader-for-cloudflare' ) ); ?></span>
		<?php else : ?>
		<span id="wpff-sp-status-badge" class="wpff-sp-status-badge wpff-sp-status-idle"><?php echo esc_html( __( 'Idle', 'super-preloader-for-cloudflare' ) ); ?></span>
		<?php endif; ?>
		<span class="wpff-sp-sidebar-remaining" id="wpff-sp-sidebar-remaining">
		<?php
		if ( null !== $wpff_sp_sidebar_remaining ) {
			echo esc_html(
				sprintf(
					/* translators: %d is the number of items remaining in the preload queue. */
					__( '(%d remaining)', 'super-preloader-for-cloudflare' ),
					$wpff_sp_sidebar_remaining
				)
			);
		}
		?>
		</span>
	</div>
	</div>

	<?php // Last Run ?>
	<?php if ( ! empty( $wpff_sp_sidebar_last_run ) ) : ?>
	<div class="wpff-sp-sidebar-card wpff-sp-sidebar-card-last-run">
	<div class="wpff-sp-sidebar-card-header">
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Last Run', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/last-run.svg' ); ?>" width="20" height="20" alt="Last run icon" />
	</div>
	<div class="wpff-sp-sidebar-meta">
		<?php if ( ! empty( $wpff_sp_sidebar_last_run['started_at'] ) ) : ?>
		<div class="wpff-sp-sidebar-meta-row">
		<span class="wpff-sp-sidebar-meta-label"><?php echo esc_html( __( 'Started', 'super-preloader-for-cloudflare' ) ); ?></span>
		<span class="wpff-sp-sidebar-meta-value"><?php echo esc_html( $wpff_sp_sidebar_last_run['started_at'] ); ?></span>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $wpff_sp_sidebar_last_run['completed_at'] ) ) : ?>
		<div class="wpff-sp-sidebar-meta-row">
		<span class="wpff-sp-sidebar-meta-label"><?php echo esc_html( __( 'Completed', 'super-preloader-for-cloudflare' ) ); ?></span>
		<span class="wpff-sp-sidebar-meta-value"><?php echo esc_html( $wpff_sp_sidebar_last_run['completed_at'] ); ?></span>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $wpff_sp_sidebar_last_run['duration'] ) ) : ?>
		<div class="wpff-sp-sidebar-meta-row">
		<span class="wpff-sp-sidebar-meta-label"><?php echo esc_html( __( 'Duration', 'super-preloader-for-cloudflare' ) ); ?></span>
		<span class="wpff-sp-sidebar-meta-value"><?php echo esc_html( $wpff_sp_sidebar_last_run['duration'] ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	</div>
	<?php endif; ?>

	<?php // URL Count ?>
	<?php if ( null !== $wpff_sp_sidebar_url_count ) : ?>
	<div class="wpff-sp-sidebar-card">
	<div class="wpff-sp-sidebar-card-header">
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'URLs', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/link.svg' ); ?>" width="20" height="20" alt="URL icon" />
	</div>
	<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count">
		<?php echo esc_html( $wpff_sp_sidebar_url_count ); ?>
	</div>
	</div>
	<?php endif; ?>

	<?php // Proxy Count ?>
	<div class="wpff-sp-sidebar-card">
	<div class="wpff-sp-sidebar-card-header">
		<span class="wpff-sp-sidebar-label"><?php echo esc_html( __( 'Proxies', 'super-preloader-for-cloudflare' ) ); ?></span>
		<img class="wpff-sp-sidebar-icon" src="<?php echo esc_url( WPFF_SP_PLUGIN_URL . 'images/globe.svg' ); ?>" width="20" height="20" alt="Globe icon" />
	</div>
	<div class="wpff-sp-sidebar-value wpff-sp-sidebar-count">
		<?php echo $wpff_sp_sidebar_proxy_count > 0 ? esc_html( $wpff_sp_sidebar_proxy_count ) : esc_html( __( 'None', 'super-preloader-for-cloudflare' ) ); ?>
	</div>
	</div>

</div>