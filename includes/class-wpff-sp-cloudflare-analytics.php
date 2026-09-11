<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Talks to Cloudflare's GraphQL Analytics API to find out which countries
 * have the worst page-cache-miss coverage, so the Stats tab and sidebar can
 * point the user at which Webshare proxy countries would actually help.
 *
 * Only ever queries HTML page requests (edgeResponseContentTypeName: "html")
 * — most cache misses on a typical site are static assets (images/JS/CSS),
 * which are irrelevant to this plugin's page/post preloading purpose.
 *
 * Cloudflare caps a single GraphQL Analytics query to a 24h window
 * (confirmed against the live API, not just documented) but retains data
 * for longer, so a multi-day report is built from several sequential 24h
 * queries rather than one wider request.
 */
class WPFF_SP_Cloudflare_Analytics {

	const GRAPHQL_URL = 'https://api.cloudflare.com/client/v4/graphql';

	/**
	 * Log a message to the plugin's log file, prefixed with the API context.
	 *
	 * @param string $context Short label identifying which call this came from.
	 * @param string $message The message to log.
	 */
	private static function log( $context, $message ) {
		WPFF_SP_Helpers::log( sprintf( '[Cloudflare Analytics] %s: %s', $context, $message ) );
	}

	/**
	 * Query one <=24h window of HTML page hit/miss counts by country.
	 *
	 * @param string $token   Cloudflare API token.
	 * @param string $zone_id Cloudflare zone ID.
	 * @param string $host    Hostname to scope the query to (the site's own host).
	 * @param string $since   ISO8601 UTC start of the window.
	 * @param string $until   ISO8601 UTC end of the window.
	 * @param string $error   Reference; populated with the error message on failure.
	 * @return array|false Array keyed by country code => ['hits' => int, 'misses' => int], or false on failure.
	 */
	private static function query_window( $token, $zone_id, $host, $since, $until, &$error ) {
		$query = 'query Q($zoneTag: String!, $since: Time!, $until: Time!, $host: String!) {
			viewer {
				zones(filter: {zoneTag: $zoneTag}) {
					httpRequestsAdaptiveGroups(
						limit: 1000
						filter: {
							datetime_geq: $since
							datetime_leq: $until
							cacheStatus_in: ["hit", "miss"]
							edgeResponseContentTypeName: "html"
							clientRequestHTTPHost: $host
						}
					) {
						count
						dimensions {
							clientCountryName
							cacheStatus
						}
					}
				}
			}
		}';

		$response = wp_remote_post(
			self::GRAPHQL_URL,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'query'     => $query,
						'variables' => array(
							'zoneTag' => $zone_id,
							'since'   => $since,
							'until'   => $until,
							'host'    => $host,
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error = $response->get_error_message();
			self::log( 'query_window', 'Connection error: ' . $error );
			set_transient( 'wpff_sp_cf_coverage_error', $error, 5 * MINUTE_IN_SECONDS );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['errors'] ) ) {
			$messages = array();

			foreach ( $body['errors'] as $api_error ) {
				if ( ! empty( $api_error['message'] ) ) {
					$messages[] = $api_error['message'];
				}
			}

			$raw_error = ! empty( $messages )
				? implode( ' ', $messages )
				: __( 'Unknown Cloudflare Analytics API error.', 'super-preloader-for-cloudflare' );

			self::log( 'query_window', 'API error: ' . $raw_error );

			// Cloudflare's own permission-denied message is long, technical,
			// and not meant for an end user ("Actor '...' does not have
			// permission '...' for zone ..."); it also isn't line-wrap-friendly
			// in the sidebar/Stats tab cards. Swap it for a short, actionable
			// message instead — the raw text is still logged above for anyone
			// debugging via the Logs tab.
			$error = false !== stripos( $raw_error, 'does not have permission' )
				? __( 'Your Cloudflare API token is missing a required permission. Disconnect and reconnect with the updated permissions to enable this.', 'super-preloader-for-cloudflare' )
				: $raw_error;

			// Single source of truth for "is the report currently broken" —
			// coverage-tab.php/sidebar.php check this to render the warning
			// directly on the next page load instead of flashing the tiles'
			// skeleton state for a whole AJAX round trip only to hide it
			// again once the same failure repeats.
			set_transient( 'wpff_sp_cf_coverage_error', $error, 5 * MINUTE_IN_SECONDS );

			return false;
		}

		$rows = array();
		if (
			isset( $body['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] ) &&
			is_array( $body['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'] )
		) {
			$rows = $body['data']['viewer']['zones'][0]['httpRequestsAdaptiveGroups'];
		}

		$by_country = array();

		foreach ( $rows as $row ) {
			$country = isset( $row['dimensions']['clientCountryName'] ) ? $row['dimensions']['clientCountryName'] : '';
			$status  = isset( $row['dimensions']['cacheStatus'] ) ? $row['dimensions']['cacheStatus'] : '';
			$count   = isset( $row['count'] ) ? (int) $row['count'] : 0;

			if ( '' === $country ) {
				continue;
			}

			if ( ! isset( $by_country[ $country ] ) ) {
				$by_country[ $country ] = array(
					'hits'   => 0,
					'misses' => 0,
				);
			}

			if ( 'hit' === $status ) {
				$by_country[ $country ]['hits'] += $count;
			} elseif ( 'miss' === $status ) {
				$by_country[ $country ]['misses'] += $count;
			}
		}

		return $by_country;
	}

	/**
	 * Merge a window's per-country hit/miss counts into a running total.
	 *
	 * @param array $totals Reference to the running totals array.
	 * @param array $window Per-country counts from one query_window() call.
	 */
	private static function merge_window( &$totals, $window ) {
		foreach ( $window as $country => $counts ) {
			if ( ! isset( $totals[ $country ] ) ) {
				$totals[ $country ] = array(
					'hits'   => 0,
					'misses' => 0,
				);
			}

			$totals[ $country ]['hits']   += $counts['hits'];
			$totals[ $country ]['misses'] += $counts['misses'];
		}
	}

	/**
	 * Get HTML page hit/miss counts by country for the trailing 24 hours,
	 * cached for 5 minutes (cheap — a single API call).
	 *
	 * @param string $token   Cloudflare API token.
	 * @param string $zone_id Cloudflare zone ID.
	 * @param string $host    Hostname to scope the query to.
	 * @param string $error   Reference; populated with the error message on failure.
	 * @param bool   $force   Bypass the cache and re-query live (the sidebar card's manual refresh button).
	 * @return array|false
	 */
	public static function get_24h_stats( $token, $zone_id, $host, &$error = '', $force = false ) {
		$cached = $force ? false : get_transient( 'wpff_sp_cf_coverage_24h' );
		if ( false !== $cached ) {
			return $cached;
		}

		$until  = gmdate( 'Y-m-d\TH:i:s\Z' );
		$since  = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-24 hours' ) );
		$window = self::query_window( $token, $zone_id, $host, $since, $until, $error );

		if ( false === $window ) {
			return false;
		}

		set_transient( 'wpff_sp_cf_coverage_24h', $window, 5 * MINUTE_IN_SECONDS );
		delete_transient( 'wpff_sp_cf_coverage_error' );

		return $window;
	}

	/**
	 * Get HTML page hit/miss counts by country for the trailing 7 days,
	 * stitched from 7 sequential 24h queries. Cached for 6 hours since this
	 * costs 7 API calls and a week-level report doesn't need to be fresher
	 * than that.
	 *
	 * @param string $token   Cloudflare API token.
	 * @param string $zone_id Cloudflare zone ID.
	 * @param string $host    Hostname to scope the query to.
	 * @param string $error   Reference; populated with the error message on failure.
	 * @param bool   $force   Bypass the cache and re-query live (the sidebar card's manual refresh button).
	 * @return array|false
	 */
	public static function get_7d_stats( $token, $zone_id, $host, &$error = '', $force = false ) {
		$cached = $force ? false : get_transient( 'wpff_sp_cf_coverage_7d' );
		if ( false !== $cached ) {
			return $cached;
		}

		$totals = array();

		for ( $days_ago = 0; $days_ago < 7; $days_ago++ ) {
			$until  = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-' . ( 24 * $days_ago ) . ' hours' ) );
			$since  = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-' . ( 24 * ( $days_ago + 1 ) ) . ' hours' ) );
			$window = self::query_window( $token, $zone_id, $host, $since, $until, $error );

			if ( false === $window ) {
				return false;
			}

			self::merge_window( $totals, $window );
		}

		set_transient( 'wpff_sp_cf_coverage_7d', $totals, 6 * HOUR_IN_SECONDS );
		delete_transient( 'wpff_sp_cf_coverage_error' );

		return $totals;
	}

	/**
	 * Whether a cached result already exists for the given range, without
	 * triggering a live query if it doesn't. Lets a synchronous PHP render
	 * path (sidebar.php / coverage-tab.php) decide up front whether to
	 * render the real numbers directly or defer to the skeleton+AJAX path
	 * — the same "only defer when it'd actually be slow" approach used for
	 * the Exclusions tab's URL table.
	 *
	 * @param string $range '24h' or '7d'.
	 * @return bool
	 */
	public static function has_cached_stats( $range ) {
		$transient = '7d' === $range ? 'wpff_sp_cf_coverage_7d' : 'wpff_sp_cf_coverage_24h';

		return false !== get_transient( $transient );
	}

	/**
	 * The single source of truth for "is the report currently broken" — set
	 * by query_window() on failure, cleared on the next successful query.
	 * A synchronous PHP render path checks this to show the same warning
	 * directly, instead of rendering the tiles' skeleton state only to
	 * have it flash away once an AJAX call repeats the same failure.
	 *
	 * @return string|false The cached error message, or false if none.
	 */
	public static function get_cached_error() {
		return get_transient( 'wpff_sp_cf_coverage_error' );
	}

	/**
	 * Resolve the saved token/zone/host and return the full summary for the
	 * given range — the one entry point both the AJAX handler and any
	 * synchronous PHP render path use, so host resolution and summary
	 * computation aren't duplicated across callers.
	 *
	 * @param string $range '24h' or '7d'.
	 * @param bool   $force Bypass the cache and re-query live.
	 * @param string $error Reference; populated with the error message on failure.
	 * @return array|false compute_summary() output (plus 'range'), or false if not configured or on failure.
	 */
	public static function get_summary( $range, $force = false, &$error = '' ) {
		$token   = get_option( 'wpff_sp_cf_api_token' );
		$zone_id = get_option( 'wpff_sp_cf_zone_id' );

		if ( ! $token || ! $zone_id ) {
			return false;
		}

		// The zone's own name (its actual domain) is the right host to
		// filter real Cloudflare traffic by, not this WordPress install's
		// own home_url() — see get_cache_coverage()'s history. Resolved
		// here now that more than one caller needs it.
		$zone_name = get_option( 'wpff_sp_cf_zone_name' );
		$host      = $zone_name ? $zone_name : wp_parse_url( home_url(), PHP_URL_HOST );

		$by_country = '7d' === $range
			? self::get_7d_stats( $token, $zone_id, $host, $error, $force )
			: self::get_24h_stats( $token, $zone_id, $host, $error, $force );

		if ( false === $by_country ) {
			return false;
		}

		$summary          = self::compute_summary( $by_country );
		$summary['range'] = $range;

		return $summary;
	}

	/**
	 * Reduce a per-country hit/miss array into the summary shape the sidebar
	 * and Stats tab cards render: overall hit rate, a top-10-by-miss-count
	 * country table (each with its own hit rate), and a suggested-proxy
	 * shortlist.
	 *
	 * @param array $by_country Per-country counts from get_24h_stats()/get_7d_stats().
	 * @return array
	 */
	public static function compute_summary( $by_country ) {
		$total_hits   = 0;
		$total_misses = 0;
		$rows         = array();

		foreach ( $by_country as $country => $counts ) {
			$total_hits   += $counts['hits'];
			$total_misses += $counts['misses'];

			if ( $counts['misses'] > 0 ) {
				$country_total = $counts['hits'] + $counts['misses'];
				$rows[]        = array(
					'code'       => $country,
					// Cacheable page requests only (hit + miss) — the same
					// pair Hit Rate % is calculated from, so the two columns
					// stay internally consistent. Excludes bypass/dynamic/
					// other cache states, which this report never queries.
					'requests'   => $country_total,
					'misses'     => $counts['misses'],
					'hitRatePct' => $country_total > 0 ? round( ( $counts['hits'] / $country_total ) * 100, 1 ) : 0.0,
				);
			}
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return $b['misses'] <=> $a['misses'];
			}
		);

		$top10         = array_slice( $rows, 0, 10 );
		$overall_total = $total_hits + $total_misses;

		return array(
			'hitRatePct'        => $overall_total > 0 ? round( ( $total_hits / $overall_total ) * 100, 1 ) : null,
			'hits'              => $total_hits,
			'misses'            => $total_misses,
			'requests'          => $overall_total,
			'countriesAffected' => count( $rows ),
			'worstCountry'      => ! empty( $top10 ) ? $top10[0] : null,
			'top10'             => $top10,
			'suggested'         => array_column( array_slice( $top10, 0, 5 ), 'code' ),
		);
	}

	/**
	 * Clear both cached windows — called when the saved token/zone ID
	 * changes so a stale result from the previous connection isn't shown.
	 */
	public static function clear_cache() {
		delete_transient( 'wpff_sp_cf_coverage_24h' );
		delete_transient( 'wpff_sp_cf_coverage_7d' );
		delete_transient( 'wpff_sp_cf_coverage_error' );
	}
}
