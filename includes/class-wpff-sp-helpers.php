<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WPFF_SP_Helpers {

	/**
	 * Cached edge locations data.
	 *
	 * @var array|null
	 */
	private static $edge_locations = null;

	/**
	 * Write a message to the plugin log file.
	 *
	 * @param string $message The message to log.
	 */
	public static function log( $message ) {
		if ( ! file_exists( WPFF_SP_LOG_DIR ) ) {
			wp_mkdir_p( WPFF_SP_LOG_DIR );
		}

		/**
		 * Filters the log message before it is written to the log file.
		 * Return an empty string to suppress the log entry entirely.
		 *
		 * @param string $message The message about to be logged.
		 */
		$message = apply_filters( 'wpff_sp_log_message', $message );

		if ( '' === $message ) {
			return;
		}

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$log_line  = "[$timestamp] $message";

		if ( file_exists( WPFF_SP_LOG_FILE ) ) {
			$all_lines = file( WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

			// Skip header lines
			$lines   = array_slice( $all_lines, WPFF_SP_LOG_HEADER_LINES );
			$lines[] = $log_line;

			if ( count( $lines ) > 1000 ) {
				$lines = array_slice( $lines, -1000 );
			}

			$log_content = WPFF_SP_LOG_HEADER . implode( "\n", $lines ) . "\n";
			file_put_contents( WPFF_SP_LOG_FILE, $log_content );
		} else {
			// Create new file with header and first log entry
			file_put_contents( WPFF_SP_LOG_FILE, WPFF_SP_LOG_HEADER . $log_line . "\n" );
		}

		/**
		 * Fires after a message has been written to the log file.
		 * Useful for forwarding log entries to external services.
		 *
		 * @param string $message   The message that was logged.
		 * @param string $log_line  The full log line including timestamp.
		 */
		do_action( 'wpff_sp_logged', $message, $log_line );
	}

	/**
	 * Generate a SHA-256 token for a URL and shared secret pair.
	 *
	 * @param string $url    The URL to generate the token for.
	 * @param string $secret The shared secret.
	 * @return string
	 */
	public static function generate_token( $url, $secret ) {
		return hash( 'sha256', $url . $secret );
	}

	/**
	 * Prepend a Settings link to the plugin's action links on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=super-preloader-for-cloudflare' ) ) . '">' . esc_html( __( 'Settings', 'super-preloader-for-cloudflare' ) ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Get the Cloudflare edge locations mapping from the bundled JSON file.
	 * Result is cached in a static property after the first load.
	 *
	 * @return array Edge location codes mapped to location data.
	 */
	public static function get_edge_locations() {
		if ( null === self::$edge_locations ) {
			$file = WPFF_SP_PLUGIN_PATH . 'data/cloudflare-edge-locations.json';
			if ( file_exists( $file ) ) {
				self::$edge_locations = json_decode( file_get_contents( $file ), true );
			} else {
				self::$edge_locations = array();
			}
		}

		return self::$edge_locations;
	}

	/**
	 * Format an edge entry string with an HTML tooltip and region colour class.
	 *
	 * @param string $entry Entry string in the format "2025-05-28 12:04:09@SJC".
	 * @return string Formatted HTML string, or the original entry if no match.
	 */
	public static function format_edge_entry( $entry ) {
		$edge_locations = self::get_edge_locations();

		// Pattern: 2025-05-28 12:04:09@SJC
		if ( preg_match( '/(.+)@([A-Z]{3})$/', $entry, $matches ) ) {
			$datetime  = $matches[1];
			$edge_code = $matches[2];

			if ( isset( $edge_locations[ $edge_code ] ) ) {
				$location_name = $edge_locations[ $edge_code ]['name'];
				$region        = $edge_locations[ $edge_code ]['region'];

				return '<span class="edge-location ' . esc_attr( $region ) . '" title="' . esc_attr( $location_name ) . '">' . $edge_code . '</span><br>' . $datetime;
			} else {
				return '<span class="edge-location unknown" title="Unknown location">' . $edge_code . '</span><br>' . $datetime;
			}
		}

		return $entry;
	}

	/**
	 * Build a comprehensive stats summary across all preloader runs.
	 *
	 * @param array $stats The raw stats array from the wpff_sp_preload_stats option.
	 * @return array Summary data with cumulative and latest-run breakdowns.
	 */
	public static function get_stats_summary( $stats ) {
		if ( empty( $stats ) ) {
			return array(
				'total_urls' => 0,
				'run_number' => 0,
				'cumulative' => array(
					'unique_edges'        => 0,
					'unique_countries'    => 0,
					'unique_regions'      => 0,
					'edge_frequency'      => array(),
					'region_frequency'    => array(),
					'country_frequency'   => array(),
					'most_common_edge'    => null,
					'most_common_region'  => null,
					'most_common_country' => null,
				),
				'latest_run' => array(
					'urls_monitored'      => 0,
					'unique_edges'        => 0,
					'unique_countries'    => 0,
					'unique_regions'      => 0,
					'edge_frequency'      => array(),
					'region_frequency'    => array(),
					'country_frequency'   => array(),
					'most_common_edge'    => null,
					'most_common_region'  => null,
					'most_common_country' => null,
				),
			);
		}

		$edge_locations = self::get_edge_locations();

		// All runs data
		$all_edges     = array();
		$all_countries = array();
		$all_regions   = array();

		// Latest run data
		$latest_edges     = array();
		$latest_countries = array();
		$latest_regions   = array();

		// Find the run number from homepage URL (starts with http)
		$run_number = 0;
		foreach ( $stats as $url => $entries ) {
			if ( strpos( $url, 'http' ) === 0 && ! empty( $entries ) ) {
				$run_number = count( $entries );
				break; // Found homepage, use it as single source of truth
			}
		}

		// Process all URLs and all their entries
		foreach ( $stats as $url => $entries ) {
			if ( ! empty( $entries ) ) {

				// Process each cache entry (each run result)
				foreach ( $entries as $index => $entry ) {
					// Extract edge code from entry
					if ( preg_match( '/@([A-Z]{3})$/', $entry, $matches ) ) {
						$edge_code   = $matches[1];
						$all_edges[] = $edge_code;

						// If this is the latest run (last entry), also add to latest stats
						if ( $index === $run_number - 1 && count( $entries ) === $run_number ) {
								$latest_edges[] = $edge_code;
						}

						// Get region and country from edge locations data
						if ( isset( $edge_locations[ $edge_code ] ) ) {
							// Add the continental region (africa, asia, europe, etc.)
							$all_regions[] = $edge_locations[ $edge_code ]['region'];
							if ( $index === $run_number - 1 && count( $entries ) === $run_number ) {
								$latest_regions[] = $edge_locations[ $edge_code ]['region'];
							}

							// Extract country code from location name (2-letter codes only)
							$location_name = $edge_locations[ $edge_code ]['name'];
							if ( preg_match( '/, ([A-Z]{2})$/', $location_name, $country_matches ) ) {
								$all_countries[] = $country_matches[1];
								if ( $index === $run_number - 1 && count( $entries ) === $run_number ) {
									$latest_countries[] = $country_matches[1];
								}
							}
						}
					}
				}
			}
		}

		$latest_run_urls = 0;
		foreach ( $stats as $url => $entries ) {
			if ( ! empty( $entries ) && count( $entries ) === $run_number ) {
				++$latest_run_urls;
			}
		}

		// Calculate frequencies for all runs
		$edge_frequency    = array_count_values( $all_edges );
		$region_frequency  = array_count_values( $all_regions );
		$country_frequency = array_count_values( $all_countries );

		// Sort by frequency, highest first
		arsort( $edge_frequency );
		arsort( $region_frequency );
		arsort( $country_frequency );

		// Calculate frequencies for latest run
		$latest_edge_frequency    = array_count_values( $latest_edges );
		$latest_region_frequency  = array_count_values( $latest_regions );
		$latest_country_frequency = array_count_values( $latest_countries );

		// Sort by frequency, highest first
		arsort( $latest_edge_frequency );
		arsort( $latest_region_frequency );
		arsort( $latest_country_frequency );

		// Get most common items for cumulative
		$most_common_edge    = ! empty( $edge_frequency ) ? array_key_first( $edge_frequency ) : null;
		$most_common_region  = ! empty( $region_frequency ) ? array_key_first( $region_frequency ) : null;
		$most_common_country = ! empty( $country_frequency ) ? array_key_first( $country_frequency ) : null;

		// Get most common items for latest run
		$latest_most_common_edge    = ! empty( $latest_edge_frequency ) ? array_key_first( $latest_edge_frequency ) : null;
		$latest_most_common_region  = ! empty( $latest_region_frequency ) ? array_key_first( $latest_region_frequency ) : null;
		$latest_most_common_country = ! empty( $latest_country_frequency ) ? array_key_first( $latest_country_frequency ) : null;

		return array(
			'total_urls' => count( $stats ),
			'run_number' => $run_number,
			'cumulative' => array(
				'unique_edges'        => count( array_unique( $all_edges ) ),
				'unique_countries'    => count( array_unique( $all_countries ) ),
				'unique_regions'      => count( array_unique( $all_regions ) ),
				'edge_frequency'      => $edge_frequency,
				'region_frequency'    => $region_frequency,
				'country_frequency'   => $country_frequency,
				'most_common_edge'    => $most_common_edge,
				'most_common_region'  => ucwords( str_replace( array( '_', '-' ), ' ', $most_common_region ) ),
				'most_common_country' => $most_common_country,
			),
			'latest_run' => array(
				'urls_monitored'      => $latest_run_urls,
				'unique_edges'        => count( array_unique( $latest_edges ) ),
				'unique_countries'    => count( array_unique( $latest_countries ) ),
				'unique_regions'      => count( array_unique( $latest_regions ) ),
				'edge_frequency'      => $latest_edge_frequency,
				'region_frequency'    => $latest_region_frequency,
				'country_frequency'   => $latest_country_frequency,
				'most_common_edge'    => $latest_most_common_edge,
				'most_common_region'  => ucwords( str_replace( array( '_', '-' ), ' ', $latest_most_common_region ) ),
				'most_common_country' => $latest_most_common_country,
			),
		);
	}

	/**
	 * Get the country code for a given Cloudflare edge code.
	 *
	 * @param string $edge_code The 3-letter edge code (e.g. "SJC").
	 * @return string Two or three letter country code, or "Unknown".
	 */
	public static function get_edge_country( $edge_code ) {
		$edge_locations = self::get_edge_locations();

		if ( isset( $edge_locations[ $edge_code ] ) ) {
			$location_name = $edge_locations[ $edge_code ]['name'];
			// Extract country code (text after last comma)
			if ( preg_match( '/, ([A-Z]{2,3})$/', $location_name, $matches ) ) {
				return $matches[1];
			}
		}

		return 'Unknown';
	}

	/**
	 * Format an edge frequency array as a human-readable HTML string.
	 *
	 * @param array $edge_frequency Array of edge codes and their hit counts.
	 * @param int   $limit          Maximum number of edges to display.
	 * @return string
	 */
	public static function format_edge_frequency( $edge_frequency, $limit = 5 ) {
		if ( empty( $edge_frequency ) ) {
			return 'No data available';
		}

		$edge_locations = self::get_edge_locations();
		$formatted      = array();
		$count          = 0;

		foreach ( $edge_frequency as $edge_code => $frequency ) {
			if ( $count >= $limit ) {
				break;
			}

			$location_name = isset( $edge_locations[ $edge_code ] )
			? $edge_locations[ $edge_code ]['name']
			: 'Unknown location';

			$formatted[] = sprintf(
				'<span title="%s">%s</span> (%d)',
				esc_attr( $location_name ),
				esc_html( $edge_code ),
				$frequency
			);
			++$count;
		}

		$remaining = count( $edge_frequency ) - $limit;
		if ( $remaining > 0 ) {
			$formatted[] = sprintf( 'and %d more...', $remaining );
		}

		return implode( ', ', $formatted );
	}

	/**
	 * Format a region frequency array as a human-readable string.
	 *
	 * @param array $region_frequency Array of region slugs and their hit counts.
	 * @param int   $limit            Maximum number of regions to display.
	 * @return string
	 */
	public static function format_region_frequency( $region_frequency, $limit = 5 ) {
		if ( empty( $region_frequency ) ) {
			return 'No data available';
		}

		// Region display names
		$region_names = array(
			'africa'        => 'Africa',
			'asia'          => 'Asia',
			'europe'        => 'Europe',
			'north-america' => 'North America',
			'south-america' => 'South America',
			'oceania'       => 'Oceania',
			'middle-east'   => 'Middle East',
			'ramallah'      => 'Ramallah',
		);

		$formatted = array();
		$count     = 0;

		foreach ( $region_frequency as $region_code => $frequency ) {
			if ( $count >= $limit ) {
				break;
			}

			$region_name = isset( $region_names[ $region_code ] )
			? $region_names[ $region_code ]
			: ucfirst( str_replace( '-', ' ', $region_code ) );

			$formatted[] = sprintf(
				'%s (%d)',
				esc_html( $region_name ),
				$frequency
			);
			++$count;
		}

		$remaining = count( $region_frequency ) - $limit;
		if ( $remaining > 0 ) {
			$formatted[] = sprintf( 'and %d more...', $remaining );
		}

		return implode( ', ', $formatted );
	}

	/**
	 * Format a country frequency array as a human-readable string.
	 *
	 * @param array $country_frequency Array of country codes and their hit counts.
	 * @param int   $limit             Maximum number of countries to display.
	 * @return string
	 */
	public static function format_country_frequency( $country_frequency, $limit = 5 ) {
		if ( empty( $country_frequency ) ) {
			return 'No data available';
		}

		$formatted = array();
		$count     = 0;

		foreach ( $country_frequency as $country_code => $frequency ) {
			if ( $count >= $limit ) {
				break;
			}

			$formatted[] = sprintf(
				'%s (%d)',
				esc_html( $country_code ),
				$frequency
			);
			++$count;
		}

		$remaining = count( $country_frequency ) - $limit;
		if ( $remaining > 0 ) {
			$formatted[] = sprintf( 'and %d more...', $remaining );
		}

		return implode( ', ', $formatted );
	}

	/**
	 * Migrate the old .log file to the new .php log format (1.0.2 → 1.0.3).
	 * Runs once and marks itself complete via a DB option.
	 */
	public static function migrate_log_file() {
		// Check if migration has already been done
		if ( get_option( 'wpff_sp_log_migrated' ) ) {
			return;
		}

		$old_log_file = WPFF_SP_LOG_DIR . '/super-preloader-for-cloudflare.log';

		if ( file_exists( $old_log_file ) ) {
			$old_content = file_get_contents( $old_log_file );

			if ( $old_content ) {
				$new_content = WPFF_SP_LOG_HEADER . trim( $old_content ) . "\n";
				file_put_contents( WPFF_SP_LOG_FILE, $new_content );
			}

			wp_delete_file( $old_log_file );
		}

		// Mark migration as complete
		update_option( 'wpff_sp_log_migrated', 1 );
	}
}
