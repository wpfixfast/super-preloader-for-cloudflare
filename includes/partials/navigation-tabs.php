<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$wpff_sp_settings_class = 'settings' === $tab ? 'nav-tab nav-tab-active' : 'nav-tab';
$wpff_sp_stats_class    = 'stats' === $tab ? 'nav-tab nav-tab-active' : 'nav-tab';
$wpff_sp_logs_class     = 'logs' === $tab ? 'nav-tab nav-tab-active' : 'nav-tab';
$wpff_sp_howto_class    = 'howto' === $tab ? 'nav-tab nav-tab-active' : 'nav-tab';
?>

<h2 class="nav-tab-wrapper">
	<a href="?page=super-preloader-for-cloudflare&tab=settings" class="<?php echo esc_attr( $wpff_sp_settings_class ); ?>">
	<?php echo esc_html( __( 'Settings', 'super-preloader-for-cloudflare' ) ); ?>
	</a>
	<a href="?page=super-preloader-for-cloudflare&tab=stats" class="<?php echo esc_attr( $wpff_sp_stats_class ); ?>">
	<?php echo esc_html( __( 'Stats', 'super-preloader-for-cloudflare' ) ); ?>
	</a>
	<a href="?page=super-preloader-for-cloudflare&tab=logs" class="<?php echo esc_attr( $wpff_sp_logs_class ); ?>">
	<?php echo esc_html( __( 'Logs', 'super-preloader-for-cloudflare' ) ); ?>
	</a>
	<a href="?page=super-preloader-for-cloudflare&tab=howto" class="<?php echo esc_attr( $wpff_sp_howto_class ); ?>">
	<?php echo esc_html( __( 'How to Use', 'super-preloader-for-cloudflare' ) ); ?>
	</a>
</h2>
