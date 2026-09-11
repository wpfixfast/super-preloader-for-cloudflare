<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$wpff_sp_url_keywords = WPFF_SP_Helpers::get_exclusion_keywords();
?>

<div class="wpff-sp-urls-tab">

	<form method="post" class="mt-20 wpff-sp-urls-keyword-form">
	<?php wp_nonce_field( 'wpff_sp_save_url_exclusions' ); ?>
	<input type="hidden" name="wpff_sp_url_exclusions" value="1">

	<h3><?php echo esc_html( __( 'Exclude URLs by Keyword', 'super-preloader-for-cloudflare' ) ); ?></h3>
	<p class="long-description">
		<?php
		echo wp_kses_post(
			sprintf(
			// translators: %1$s is a line break.
				__(
					'One keyword per line.%1$sAny URL matching a path (case-insensitive), such as /wishlist, is skipped during preloading. Useful for saving time by avoiding pages that don\'t need to be cached.',
					'super-preloader-for-cloudflare'
				),
				'<br>'
			)
		);
		?>
	</p>
	<textarea
		name="excluded_keywords"
		rows="6"
		class="large-text code"
		placeholder="<?php echo esc_attr( __( "/wishlist\n/my-page\n/my-main-page/my-sub-page", 'super-preloader-for-cloudflare' ) ); ?>"
	><?php echo esc_textarea( implode( "\n", $wpff_sp_url_keywords ) ); ?></textarea>

	<p>
		<input
		type="submit"
		class="button button-primary"
		value="<?php echo esc_attr( __( 'Save Keyword Exclusions', 'super-preloader-for-cloudflare' ) ); ?>"
		/>
	</p>
	</form>

	<?php if ( $wpff_sp_defer_urls_table ) : ?>
	<div id="wpff-sp-urls-table-section" data-wpff-sp-loading="1">
		<p class="wpff-sp-urls-summary"><span class="wpff-sp-skeleton wpff-sp-skeleton-bar" style="width: 220px;"></span></p>
		<div class="wpff-sp-urls-table-toolbar">
			<h3 class="wpff-sp-urls-table-heading"><?php echo esc_html( __( 'Manual URL Exclusions', 'super-preloader-for-cloudflare' ) ); ?></h3>
		</div>
		<div class="wpff-sp-urls-skeleton-table">
			<?php
			// Varied bar widths so the rows don't look like a single
			// repeated tile — closer to how real URLs vary in length.
			$wpff_sp_skeleton_widths = array( '70%', '45%', '85%', '55%', '65%', '40%' );
			foreach ( $wpff_sp_skeleton_widths as $wpff_sp_skeleton_width ) :
				?>
			<div class="wpff-sp-urls-skeleton-row">
				<span class="wpff-sp-skeleton wpff-sp-urls-skeleton-bar" style="width: <?php echo esc_attr( $wpff_sp_skeleton_width ); ?>;"></span>
			</div>
				<?php
			endforeach;
			?>
		</div>
	</div>
	<?php else : ?>
	<div id="wpff-sp-urls-table-section">
		<?php include plugin_dir_path( __FILE__ ) . 'urls-table-section.php'; ?>
	</div>
	<?php endif; ?>

</div>
