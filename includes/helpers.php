<?php
if (!defined('ABSPATH')) {
  exit();
}

function wpff_sp_log($message) {
  if (!file_exists(WPFF_SP_LOG_DIR)) {
    wp_mkdir_p(WPFF_SP_LOG_DIR);
  }

  $timestamp = current_time('Y-m-d H:i:s');
  $log_line  = "[$timestamp] $message";

  if (file_exists(WPFF_SP_LOG_FILE)) {
    $lines   = file(WPFF_SP_LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines[] = $log_line;

    if (count($lines) > 1000) {
      $lines = array_slice($lines, -1000);
    }

    file_put_contents(WPFF_SP_LOG_FILE, implode("\n", $lines) . "\n");
  } else {
    file_put_contents(WPFF_SP_LOG_FILE, $log_line);
  }
}

function wpff_sp_generate_token($url, $secret) {
  return hash('sha256', $url . $secret);
}

function wpff_sp_add_settings_link($links) {
  $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=super-preloader-for-cloudflare')) . '">' . esc_html(__('Settings', 'super-preloader-for-cloudflare')) . '</a>';
  array_unshift($links, $settings_link);
  return $links;
}

/**
 * Get Cloudflare edge locations mapping
 *
 * @return array Edge location codes mapped to location data
 */
function wpff_sp_get_cloudflare_edge_locations() {
    static $edge_locations = null;
    
    if ($edge_locations === null) {
        $file = WPFF_SP_PLUGIN_PATH . 'data/cloudflare-edge-locations.json';
        if (file_exists($file)) {
            $edge_locations = json_decode(file_get_contents($file), true);
        } else {
            $edge_locations = [];
        }
    }
    
    return $edge_locations;
}

/**
 * Format edge entry with tooltip and color coding
 *
 * @param string $entry The entry string (e.g., "2025-05-28 12:04:09@SJC")
 * @return string Formatted entry with tooltip and region class
 */
function wpff_sp_format_edge_entry($entry) {
    static $edge_locations = null;
    
    if ($edge_locations === null) {
        $edge_locations = wpff_sp_get_cloudflare_edge_locations();
    }
    
    // Pattern: 2025-05-28 12:04:09@SJC
    if (preg_match('/(.+)@([A-Z]{3})$/', $entry, $matches)) {
        $datetime = $matches[1];
        $edge_code = $matches[2];

        $wrapped_datetime = str_replace(' ', '<br>', $datetime);
        
        if (isset($edge_locations[$edge_code])) {
            $location_data = $edge_locations[$edge_code];
            $location_name = $location_data['name'];
            $region = $location_data['region'];
            
            return '<span class="edge-location ' . esc_attr($region) . '" title="' . esc_attr($location_name) . '">' . $edge_code . '</span><br>' . $datetime;
        } else {
            return '<span class="edge-location unknown" title="Unknown location">' . $edge_code . '</span><br>' . $datetime;
        }
    }
    return $entry;
}

/**
 * Get comprehensive stats summary across all preloader runs
 *
 * @param array $stats The stats array
 * @return array Summary data with counts across all runs
 */
function wpff_sp_get_stats_summary($stats) {
    if (empty($stats)) {
        return [
            'total_urls' => 0,
            'run_number' => 0,
            'cumulative' => [
                'unique_edges' => 0,
                'unique_countries' => 0,
                'unique_regions' => 0,
                'edge_frequency' => [],
                'region_frequency' => [],
                'country_frequency' => [],
                'most_common_edge' => null,
                'most_common_region' => null,
                'most_common_country' => null
            ],
            'latest_run' => [
                'urls_monitored' => 0,
                'unique_edges' => 0,
                'unique_countries' => 0,
                'unique_regions' => 0,
                'edge_frequency' => [],
                'region_frequency' => [],
                'country_frequency' => [],
                'most_common_edge' => null,
                'most_common_region' => null,
                'most_common_country' => null
            ]
        ];
    }
    
    $edge_locations = wpff_sp_get_cloudflare_edge_locations();
    
    // All runs data
    $all_edges = [];
    $all_countries = [];
    $all_regions = [];
    
    // Latest run data
    $latest_edges = [];
    $latest_countries = [];
    $latest_regions = [];
    
    // Find the run number from homepage URL (starts with http)
    $run_number = 0;
    foreach ($stats as $url => $entries) {
        if (strpos($url, 'http') === 0 && !empty($entries)) {
            $run_number = count($entries);
            break; // Found homepage, use it as single source of truth
        }
    }
    
    // Process all URLs and all their entries
    foreach ($stats as $url => $entries) {
        if (!empty($entries)) {
            
            // Process each cache entry (each run result)
            foreach ($entries as $index => $entry) {
                // Extract edge code from entry
                if (preg_match('/@([A-Z]{3})$/', $entry, $matches)) {
                    $edge_code = $matches[1];
                    $all_edges[] = $edge_code;
                    
                    // If this is the latest run (last entry), also add to latest stats
                    if ($index === $run_number - 1 && count($entries) == $run_number) {
                        $latest_edges[] = $edge_code;
                    }
                    
                    // Get region and country from edge locations data
                    if (isset($edge_locations[$edge_code])) {
                        // Add the continental region (africa, asia, europe, etc.)
                        $all_regions[] = $edge_locations[$edge_code]['region'];
                        if ($index === $run_number - 1 && count($entries) == $run_number) {
                            $latest_regions[] = $edge_locations[$edge_code]['region'];
                        }
                        
                        // Extract country code from location name (2-letter codes only)
                        $location_name = $edge_locations[$edge_code]['name'];
                        if (preg_match('/, ([A-Z]{2})$/', $location_name, $country_matches)) {
                            $all_countries[] = $country_matches[1];
                            if ($index === $run_number - 1 && count($entries) == $run_number) {
                                $latest_countries[] = $country_matches[1];
                            }
                        }
                    }
                }
            }
        }
    }

    $latest_run_urls = 0;
    foreach ($stats as $url => $entries) {
        if (!empty($entries) && count($entries) == $run_number) {
            $latest_run_urls++;
        }
    }  
    
    // Calculate frequencies for all runs
    $edge_frequency = array_count_values($all_edges);
    $region_frequency = array_count_values($all_regions);
    $country_frequency = array_count_values($all_countries);
    
    // Sort by frequency, highest first
    arsort($edge_frequency);
    arsort($region_frequency);
    arsort($country_frequency);
    
    // Calculate frequencies for latest run
    $latest_edge_frequency = array_count_values($latest_edges);
    $latest_region_frequency = array_count_values($latest_regions);
    $latest_country_frequency = array_count_values($latest_countries);
    
    // Sort by frequency, highest first
    arsort($latest_edge_frequency);
    arsort($latest_region_frequency);
    arsort($latest_country_frequency);
    
    // Get most common items for cumulative
    $most_common_edge = !empty($edge_frequency) ? array_key_first($edge_frequency) : null;
    $most_common_region = !empty($region_frequency) ? array_key_first($region_frequency) : null;
    $most_common_country = !empty($country_frequency) ? array_key_first($country_frequency) : null;
    
    // Get most common items for latest run
    $latest_most_common_edge = !empty($latest_edge_frequency) ? array_key_first($latest_edge_frequency) : null;
    $latest_most_common_region = !empty($latest_region_frequency) ? array_key_first($latest_region_frequency) : null;
    $latest_most_common_country = !empty($latest_country_frequency) ? array_key_first($latest_country_frequency) : null;
    
    return [
        'total_urls' => count($stats),
        'run_number' => $run_number,
        'cumulative' => [
            'unique_edges' => count(array_unique($all_edges)),
            'unique_countries' => count(array_unique($all_countries)),
            'unique_regions' => count(array_unique($all_regions)),
            'edge_frequency' => $edge_frequency,
            'region_frequency' => $region_frequency,
            'country_frequency' => $country_frequency,
            'most_common_edge' => $most_common_edge,
            'most_common_region' => ucwords(str_replace(['_', '-'], ' ', $most_common_region)),
            'most_common_country' => $most_common_country
        ],
        'latest_run' => [
            'urls_monitored' => $latest_run_urls,
            'unique_edges' => count(array_unique($latest_edges)),
            'unique_countries' => count(array_unique($latest_countries)),
            'unique_regions' => count(array_unique($latest_regions)),
            'edge_frequency' => $latest_edge_frequency,
            'region_frequency' => $latest_region_frequency,
            'country_frequency' => $latest_country_frequency,
            'most_common_edge' => $latest_most_common_edge,
            'most_common_region' => ucwords(str_replace(['_', '-'], ' ', $latest_most_common_region)),
            'most_common_country' => $latest_most_common_country
        ]
    ];
}

/**
 * Get country name from edge location data
 *
 * @param string $edge_code The 3-letter edge code
 * @return string Country code or name
 */
function wpff_sp_get_edge_country($edge_code) {
    $edge_locations = wpff_sp_get_cloudflare_edge_locations();
    
    if (isset($edge_locations[$edge_code])) {
        $location_name = $edge_locations[$edge_code]['name'];
        // Extract country code (text after last comma)
        if (preg_match('/, ([A-Z]{2,3})$/', $location_name, $matches)) {
            return $matches[1];
        }
    }
    
    return 'Unknown';
}

/**
 * Format edge frequency for display
 *
 * @param array $edge_frequency Array of edge codes and their frequencies
 * @param int $limit Maximum number of edges to show
 * @return string Formatted frequency string
 */
function wpff_sp_format_edge_frequency($edge_frequency, $limit = 5) {
    if (empty($edge_frequency)) {
        return 'No data available';
    }
    
    $edge_locations = wpff_sp_get_cloudflare_edge_locations();
    $formatted = [];
    $count = 0;
    
    foreach ($edge_frequency as $edge_code => $frequency) {
        if ($count >= $limit) break;
        
        $location_name = isset($edge_locations[$edge_code]) 
            ? $edge_locations[$edge_code]['name'] 
            : 'Unknown location';
            
        $formatted[] = sprintf(
            '<span title="%s">%s</span> (%d)',
            esc_attr($location_name),
            esc_html($edge_code),
            $frequency
        );
        $count++;
    }
    
    $remaining = count($edge_frequency) - $limit;
    if ($remaining > 0) {
        $formatted[] = sprintf('and %d more...', $remaining);
    }
    
    return implode(', ', $formatted);
}

/**
 * Format region frequency for display
 *
 * @param array $region_frequency Array of regions and their frequencies
 * @param int $limit Maximum number of regions to show
 * @return string Formatted frequency string
 */
function wpff_sp_format_region_frequency($region_frequency, $limit = 5) {
    if (empty($region_frequency)) {
        return 'No data available';
    }
    
    // Region display names
    $region_names = [
        'africa' => 'Africa',
        'asia' => 'Asia',
        'europe' => 'Europe',
        'north-america' => 'North America',
        'south-america' => 'South America',
        'oceania' => 'Oceania',
        'middle-east' => 'Middle East',
        'ramallah' => 'Ramallah'
    ];
    
    $formatted = [];
    $count = 0;
    
    foreach ($region_frequency as $region_code => $frequency) {
        if ($count >= $limit) break;
        
        $region_name = isset($region_names[$region_code]) 
            ? $region_names[$region_code] 
            : ucfirst(str_replace('-', ' ', $region_code));
            
        $formatted[] = sprintf(
            '%s (%d)',
            esc_html($region_name),
            $frequency
        );
        $count++;
    }
    
    $remaining = count($region_frequency) - $limit;
    if ($remaining > 0) {
        $formatted[] = sprintf('and %d more...', $remaining);
    }
    
    return implode(', ', $formatted);
}

/**
 * Format country frequency for display
 *
 * @param array $country_frequency Array of country codes and their frequencies
 * @param int $limit Maximum number of countries to show
 * @return string Formatted frequency string
 */
function wpff_sp_format_country_frequency($country_frequency, $limit = 5) {
    if (empty($country_frequency)) {
        return 'No data available';
    }
    
    $formatted = [];
    $count = 0;
    
    foreach ($country_frequency as $country_code => $frequency) {
        if ($count >= $limit) break;
        
        $formatted[] = sprintf(
            '%s (%d)',
            esc_html($country_code),
            $frequency
        );
        $count++;
    }
    
    $remaining = count($country_frequency) - $limit;
    if ($remaining > 0) {
        $formatted[] = sprintf('and %d more...', $remaining);
    }
    
    return implode(', ', $formatted);
}