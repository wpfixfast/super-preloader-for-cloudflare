<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Shared by both render paths: included directly for a table-interaction
// request (pagination/search/sort — already fast, cache reused), and
// included from within WPFF_SP_Ajax::get_urls_table() for the deferred,
// initially-skeleton'd fresh-visit path. Expects $wpff_sp_urls_list_table
// to already have prepare_items() called.
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

$wpff_sp_all_urls      = get_transient( 'wpff_sp_urls_tab_cache' );
$wpff_sp_all_urls      = is_array( $wpff_sp_all_urls ) ? $wpff_sp_all_urls : array();
$wpff_sp_included_urls = WPFF_SP_Helpers::filter_excluded_urls( $wpff_sp_all_urls );
?>

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
