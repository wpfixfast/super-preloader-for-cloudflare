<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( null !== WPFF_SP_Urls_List_Table::$fetch_error ) {
	?>
	<div class="notice notice-error">
		<p>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s is the sitemap fetch error message. */
				__( 'Could not refresh the URL list: %s', 'super-preloader-for-cloudflare' ),
				WPFF_SP_Urls_List_Table::$fetch_error
			)
		);
		?>
		</p>
	</div>
	<?php
}

$wpff_sp_url_keywords  = WPFF_SP_Helpers::get_exclusion_keywords();
$wpff_sp_all_urls      = get_transient( 'wpff_sp_urls_tab_cache' );
$wpff_sp_all_urls      = is_array( $wpff_sp_all_urls ) ? $wpff_sp_all_urls : array();
$wpff_sp_included_urls = WPFF_SP_Helpers::filter_excluded_urls( $wpff_sp_all_urls );
?>

<div class="wpff-sp-urls-tab">

	<form method="post" class="mt-20">
	<?php wp_nonce_field( 'wpff_sp_save_url_exclusions' ); ?>
	<input type="hidden" name="wpff_sp_url_exclusions" value="1">

	<h3><?php echo esc_html( __( 'Exclude URLs by Keyword', 'super-preloader-for-cloudflare' ) ); ?></h3>
	<p class="long-description">
		<?php
		echo esc_html__(
			'One keyword per line. Any URL containing a keyword (case-insensitive) is skipped during preloading. Useful for excluding dynamically generated pages such as cart or checkout.',
			'super-preloader-for-cloudflare'
		);
		?>
	</p>
	<textarea
		name="excluded_keywords"
		rows="6"
		class="large-text code"
		placeholder="<?php echo esc_attr( __( 'e.g. cart', 'super-preloader-for-cloudflare' ) ); ?>"
	><?php echo esc_textarea( implode( "\n", $wpff_sp_url_keywords ) ); ?></textarea>

	<p>
		<input
		type="submit"
		class="button button-primary"
		value="<?php echo esc_attr( __( 'Save Keyword Exclusions', 'super-preloader-for-cloudflare' ) ); ?>"
		/>
	</p>
	</form>

	<p class="wpff-sp-urls-summary">
	<?php
	echo esc_html(
		sprintf(
			/* translators: 1: number of URLs that will be preloaded, 2: total number of URLs found in the sitemap. */
			__( '%1$d of %2$d URLs will be preloaded.', 'super-preloader-for-cloudflare' ),
			count( $wpff_sp_included_urls ),
			count( $wpff_sp_all_urls )
		)
	);
	?>
	</p>

	<form method="get">
	<input type="hidden" name="page" value="super-preloader-for-cloudflare" />
	<input type="hidden" name="tab" value="exclusions" />
	<div class="wpff-sp-urls-table-toolbar">
		<h3 class="wpff-sp-urls-table-heading"><?php echo esc_html( __( 'Manual URL Exclusions', 'super-preloader-for-cloudflare' ) ); ?></h3>
		<?php $wpff_sp_urls_list_table->search_box( __( 'Search URLs', 'super-preloader-for-cloudflare' ), 'wpff-sp-url-search' ); ?>
	</div>
	<?php $wpff_sp_urls_list_table->display(); ?>
	</form>

</div>
