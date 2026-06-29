<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPFF_SP_Urls_List_Table extends WP_List_Table {

	/**
	 * Error message from the last sitemap fetch attempt, if it failed.
	 *
	 * @var string|null
	 */
	public static $fetch_error = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'url',
				'plural'   => 'urls',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'     => '<input type="checkbox" />',
			'url'    => __( 'URL', 'super-preloader-for-cloudflare' ),
			'status' => __( 'Status', 'super-preloader-for-cloudflare' ),
		);
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'url'    => array( 'url', false ),
			'status' => array( 'status', false ),
		);
	}

	/**
	 * No bulk actions dropdown — replaced by explicit Exclude/Include
	 * Selected buttons in extra_tablenav() for clarity.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Render the "Exclude Selected" / "Include Selected" buttons in the
	 * toolbar slot the bulk actions dropdown would otherwise occupy.
	 *
	 * @param string $which Either 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'bottom' !== $which ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<?php wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce', false ); ?>
			<button type="submit" name="wpff_sp_bulk_action" value="exclude" class="button wpff-sp-danger-button">
				<?php esc_html_e( 'Exclude Selected', 'super-preloader-for-cloudflare' ); ?>
			</button>
			<button type="submit" name="wpff_sp_bulk_action" value="include" class="button button-primary">
				<?php esc_html_e( 'Include Selected', 'super-preloader-for-cloudflare' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param string $item The row's URL.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="url[]" value="%s" />', esc_attr( $item ) );
	}

	/**
	 * Render the URL column.
	 *
	 * @param string $item The row's URL.
	 * @return string
	 */
	public function column_url( $item ) {
		return '<a href="' . esc_url( $item ) . '" target="_blank" rel="noopener">' . esc_html( $item ) . '</a>';
	}

	/**
	 * Render the status column — Included, Excluded, or Excluded by keyword.
	 *
	 * @param string $item The row's URL.
	 * @return string
	 */
	public function column_status( $item ) {
		$excluded_urls = WPFF_SP_Helpers::get_excluded_urls();
		$keywords      = WPFF_SP_Helpers::get_exclusion_keywords();
		$matched       = WPFF_SP_Helpers::get_matched_exclusion_keyword( $item, $excluded_urls, $keywords );

		if ( null === $matched ) {
			return '<span class="wpff-sp-status-badge wpff-sp-status-running">' . esc_html__( 'Included', 'super-preloader-for-cloudflare' ) . '</span>';
		}

		if ( 0 === strcasecmp( $matched, $item ) ) {
			return '<span class="wpff-sp-status-badge wpff-sp-status-excluded">' . esc_html__( 'Excluded', 'super-preloader-for-cloudflare' ) . '</span>';
		}

		return '<span class="wpff-sp-status-badge wpff-sp-status-excluded">' .
			sprintf(
				/* translators: %s is the matched exclusion keyword. */
				esc_html__( 'Excluded by keyword: %s', 'super-preloader-for-cloudflare' ),
				esc_html( $matched )
			) .
		'</span>';
	}

	/**
	 * Fallback column renderer.
	 *
	 * @param string $item       The row's URL.
	 * @param string $column_name The column being rendered.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return esc_html( $item );
	}

	/**
	 * Message shown when there are no rows to display.
	 */
	public function no_items() {
		esc_html_e( 'No URLs found.', 'super-preloader-for-cloudflare' );
	}

	/**
	 * Process the Exclude/Include Selected buttons.
	 * Selected URLs are added to (or removed from) the wpff_sp_excluded_urls
	 * option — kept separate from the typed keyword list.
	 */
	public function process_bulk_action() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified explicitly below via check_admin_referer().
		$action = isset( $_REQUEST['wpff_sp_bulk_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpff_sp_bulk_action'] ) ) : '';

		if ( ! in_array( $action, array( 'exclude', 'include' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$selected = isset( $_REQUEST['url'] ) ? array_map( 'esc_url_raw', wp_unslash( (array) $_REQUEST['url'] ) ) : array();

		if ( empty( $selected ) ) {
			return;
		}

		$excluded_urls = WPFF_SP_Helpers::get_excluded_urls();

		if ( 'exclude' === $action ) {
			$excluded_urls = array_values( array_unique( array_merge( $excluded_urls, $selected ) ) );
		} else {
			$excluded_urls = array_values( array_diff( $excluded_urls, $selected ) );
		}

		update_option( 'wpff_sp_excluded_urls', $excluded_urls );

		// Strip the action/checkbox/nonce params so refreshing the page doesn't
		// resubmit the same bulk action again.
		$redirect_url = remove_query_arg( array( 'wpff_sp_bulk_action', 'url', '_wpnonce', '_wp_http_referer' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Check whether the current request is a table interaction (pagination,
	 * search, or sort) rather than a fresh arrival at the URLs tab.
	 *
	 * @return bool
	 */
	private function is_table_interaction() {
		foreach ( array( 'paged', 's', 'orderby', 'order' ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination/search/sort state, not a state-changing action.
			if ( isset( $_GET[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the full URL list, refreshing from the sitemap only on a fresh tab
	 * visit — pagination, search, and sorting reuse the short-lived cache.
	 *
	 * @return array
	 */
	private function get_all_urls() {
		$cached = get_transient( 'wpff_sp_urls_tab_cache' );

		if ( $this->is_table_interaction() && is_array( $cached ) ) {
			return $cached;
		}

		$sitemap_url = get_option( 'wpff_sp_sitemap_url', get_site_url() . '/sitemap.xml' );
		$urls        = WPFF_SP_Helpers::get_sitemap_urls( $sitemap_url );

		if ( is_wp_error( $urls ) ) {
			self::$fetch_error = $urls->get_error_message();
			return is_array( $cached ) ? $cached : array();
		}

		self::$fetch_error = null;
		set_transient( 'wpff_sp_urls_tab_cache', $urls, 5 * MINUTE_IN_SECONDS );

		return $urls;
	}

	/**
	 * Filter the URL list by the search box term, if any.
	 *
	 * @param array $urls Flat array of URLs.
	 * @return array
	 */
	private function filter_by_search( $urls ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only search term, not a state-changing action.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		if ( '' === $search ) {
			return $urls;
		}

		return array_values(
			array_filter(
				$urls,
				function ( $url ) use ( $search ) {
					return false !== stripos( $url, $search );
				}
			)
		);
	}

	/**
	 * Sort the URL list by the current orderby/order request values.
	 *
	 * @param array $urls Flat array of URLs.
	 * @return array
	 */
	private function sort_urls( $urls ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sort column, not a state-changing action.
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'url';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sort direction, not a state-changing action.
		$order = isset( $_REQUEST['order'] ) && 'desc' === strtolower( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) ? 'desc' : 'asc';

		$excluded_urls = WPFF_SP_Helpers::get_excluded_urls();
		$keywords      = WPFF_SP_Helpers::get_exclusion_keywords();

		usort(
			$urls,
			function ( $a, $b ) use ( $orderby, $excluded_urls, $keywords ) {
				if ( 'status' === $orderby ) {
					$a_val = null === WPFF_SP_Helpers::get_matched_exclusion_keyword( $a, $excluded_urls, $keywords ) ? 0 : 1;
					$b_val = null === WPFF_SP_Helpers::get_matched_exclusion_keyword( $b, $excluded_urls, $keywords ) ? 0 : 1;
					return $a_val <=> $b_val;
				}
				return strcasecmp( $a, $b );
			}
		);

		if ( 'desc' === $order ) {
			$urls = array_reverse( $urls );
		}

		return $urls;
	}

	/**
	 * Prepare the table's items — runs bulk actions, fetches/filters/sorts the
	 * URL list, and slices it for the current page.
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$urls = $this->get_all_urls();
		$urls = $this->filter_by_search( $urls );
		$urls = $this->sort_urls( $urls );

		$total_items  = count( $urls );
		$per_page     = $this->get_items_per_page( 'wpff_sp_urls_per_page', 20 );
		$current_page = $this->get_pagenum();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$this->items = array_slice( $urls, ( $current_page - 1 ) * $per_page, $per_page );
	}
}
