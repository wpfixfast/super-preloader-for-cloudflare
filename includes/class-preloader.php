<?php
if (!defined('ABSPATH')) {
  exit();
}

class WPFF_SP_Preloader {

  /**
   * Check if the preloader has been asked to stop.
   * Clears all related transients and logs if a stop flag is found.
   *
   * @return bool True if the preloader should stop, false otherwise.
   */
  public static function should_stop() {
    if (get_transient('wpff_sp_stop_flag')) {
      delete_transient('wpff_sp_stop_flag');
      delete_transient('wpff_sp_batch_stats');
      delete_transient('wpff_sp_preload_cursor');
      WPFF_SP_Helpers::log(__('Preloader stopped by user.', 'super-preloader-for-cloudflare'));
      return true;
    }
    return false;
  }

  /**
   * Parse a raw proxy list string into a clean indexed array.
   * Filters out any empty lines.
   *
   * @param string $raw Raw proxy list body (one proxy per line).
   * @return array Indexed array of proxy strings.
   */
  private static function parse_proxies($raw) {
    return array_values(
      array_filter(
        array_map('trim', preg_split('/\r?\n/', $raw))
      )
    );
  }

  /**
   * Build the preload queue depending on the active mode.
   *
   * Normal mode: returns a flat array of URLs. A random proxy is picked
   * inside the loop for each URL.
   *
   * Full Proxy Pass mode: returns an expanded array of url+proxy pair arrays
   * so that every URL is sent through every proxy. The main loop does not
   * need to know which mode is active — it just processes the queue.
   *
   * @param array $urls    Flat array of URLs from the sitemap.
   * @param array $proxies Indexed array of raw proxy strings (ip:port:user:pass).
   * @param bool  $full_proxy_pass Whether Full Proxy Pass mode is enabled.
   * @return array Queue of items to process.
   */
  private static function build_queue($urls, $proxies, $full_proxy_pass) {
    if (!$full_proxy_pass || empty($proxies)) {
      return $urls;
    }

    $queue = [];
    foreach ($urls as $url) {
      foreach ($proxies as $proxy_candidate) {
        if (substr_count($proxy_candidate, ':') === 3) {
          [$ip, $port, $user, $pass] = explode(':', $proxy_candidate);
          $queue[] = [
            'url'   => $url,
            'proxy' => "$ip:$port",
            'auth'  => "$user:$pass",
          ];
        }
      }
    }

    return $queue;
  }

  /**
   * Format a duration in seconds into a human-readable string.
   *
   * @param int $seconds Total seconds elapsed.
   * @return string e.g. "4 minutes 32 seconds" or "45 seconds".
   */
  private static function format_duration($seconds) {
    $minutes = round($seconds / 60);

    if ($minutes === 1) {
      /* translators: %d: Number of minutes */
      $format = __('%d minute', 'super-preloader-for-cloudflare');
    } else {
      /* translators: %d: Number of minutes */
      $format = __('%d minutes', 'super-preloader-for-cloudflare');
    }

    return sprintf($format, $minutes);
  }

  /**
   * Run the preloader. Processes one batch of URLs per call.
   * Subsequent batches are scheduled as new single cron events.
   * Called by both the manual AJAX trigger and the scheduled cron hook.
   */
  public static function run($is_continuation = false) {
    if (!$is_continuation && get_transient('wpff_sp_preload_cursor')) {
        WPFF_SP_Helpers::log(__('Preloader already running, skipping this cron event.', 'super-preloader-for-cloudflare'));
        return;
    }
  
    if (self::should_stop()) {
      return;
    }

    $worker    = get_option('wpff_sp_worker_url');
    $proxy_url = get_option('wpff_sp_proxy_list_url');
    $sitemap   = get_option('wpff_sp_sitemap_url');

    if (empty($worker) || empty($sitemap)) {
      WPFF_SP_Helpers::log(esc_html__('Missing required settings: worker or sitemap.', 'super-preloader-for-cloudflare'));
      return;
    }

    $full_proxy_pass = (bool) get_option('wpff_sp_full_proxy_pass', false);
    $proxies         = [];

    if (!empty($proxy_url)) {
      $proxy_list = wp_remote_get($proxy_url);

      if (is_wp_error($proxy_list)) {
        // translators: %s is the error message from the proxy list request.
        WPFF_SP_Helpers::log(sprintf(esc_html__('Failed to download proxy list: %s', 'super-preloader-for-cloudflare'), $proxy_list->get_error_message()));
      } else {
        $proxies = self::parse_proxies(wp_remote_retrieve_body($proxy_list));
      }
    }

    $sitemap_xml = wp_remote_get($sitemap);
    if (is_wp_error($sitemap_xml)) {
      WPFF_SP_Helpers::log(
        esc_html__('Failed to download sitemap: ', 'super-preloader-for-cloudflare') .
        esc_html($sitemap_xml->get_error_message())
      );
      return;
    }

    $queue = get_transient('wpff_sp_preload_urls');
    if (!is_array($queue)) {
      $urls   = [];
      $parsed = simplexml_load_string(wp_remote_retrieve_body($sitemap_xml));
      if (isset($parsed->url)) {
        foreach ($parsed->url as $entry) {
          $urls[] = (string) $entry->loc;
        }
      } elseif (isset($parsed->sitemap)) {
        foreach ($parsed->sitemap as $entry) {
          $loc   = (string) $entry->loc;
          $child = wp_remote_get($loc);
          if (!is_wp_error($child)) {
            $sub = simplexml_load_string(wp_remote_retrieve_body($child));
            foreach ($sub->url as $e) {
              $urls[] = (string) $e->loc;
            }
          }
        }
      }

      /**
       * Filters the list of URLs before they are queued for preloading.
       * Allows developers to add, remove, or reorder URLs before the run starts.
       *
       * @param array $urls Flat array of URLs parsed from the sitemap.
       */
      $urls = apply_filters('wpff_sp_preload_urls', $urls);

      shuffle($urls);
      update_option('wpff_sp_sitemap_url_count', count($urls), false);

      $queue = self::build_queue($urls, $proxies, $full_proxy_pass);
      set_transient('wpff_sp_preload_urls', $queue, 24 * HOUR_IN_SECONDS);
    }

    $stats = get_transient('wpff_sp_batch_stats');
    if (!is_array($stats)) {
      $stats = [];
    }

    $cursor = get_transient('wpff_sp_preload_cursor');
    if (!is_array($cursor)) {
      $cursor = ['index' => 0, 'started_at' => time()];
      set_transient('wpff_sp_preload_cursor', $cursor, 24 * HOUR_IN_SECONDS);
      /**
       * Fires when a new preload run begins (first batch only).
       *
       * @param int $total Total number of items in the queue.
       */
      do_action('wpff_sp_preloader_started', count($queue));

      WPFF_SP_Helpers::log(__('Preloader started.', 'super-preloader-for-cloudflare'));
    }

    // Batch size and URL delay
    $batch_size         = (int) get_option('wpff_sp_batch_size', 10);
    $batch              = array_slice($queue, $cursor['index'], $batch_size);
    $delay_between_urls = (int) get_option('wpff_sp_delay_between_urls', 1);

    // Secret for token generation
    $shared_secret = sanitize_text_field(get_option('wpff_sp_shared_secret', ''));

    foreach ($batch as $item) {
      // Normalise item — in normal mode item is a plain URL string,
      // in Full Proxy Pass mode it is an array with url, proxy, auth keys
      if (is_array($item)) {
        $url       = $item['url'];
        $proxy     = $item['proxy'];
        $auth      = $item['auth'];
        $use_proxy = true;
      } else {
        $url       = $item;
        $use_proxy = false;
        $proxy     = '';
        $auth      = '';

        // Normal mode — pick a random proxy if available
        if (!empty($proxies)) {
          $proxy_candidate = $proxies[array_rand($proxies)];
          if (substr_count($proxy_candidate, ':') === 3) {
            [$ip, $port, $user, $pass] = explode(':', $proxy_candidate);
            $use_proxy                 = true;
            $proxy                     = "$ip:$port";
            $auth                      = "$user:$pass";
          }
        }
      }

      $token      = WPFF_SP_Helpers::generate_token($url, $shared_secret);
      $target_url = $worker . '?url=' . urlencode($url) . '&token=' . $token;

      if ($use_proxy) {
        // translators: %1$s is the URL being warmed, %2$s is the proxy IP and port.
        WPFF_SP_Helpers::log(sprintf(esc_html__('Warming: %1$s via %2$s', 'super-preloader-for-cloudflare'), $url, $proxy));
        $response = WPFF_SP_Http_Request::request($target_url, $proxy, $auth);
      } else {
        // translators: %s is the URL being warmed directly from the server.
        WPFF_SP_Helpers::log(sprintf(esc_html__('Warming: %s directly from server', 'super-preloader-for-cloudflare'), $url));
        $response = WPFF_SP_Http_Request::request($target_url);
      }

      // translators: %s is the HTTP response body returned from the preload request.
      WPFF_SP_Helpers::log(sprintf(esc_html__('Response: %s', 'super-preloader-for-cloudflare'), trim($response['body'])));

      if (!empty($response['cf_ray'])) {
        $parts = explode('-', trim($response['cf_ray']));
        if (count($parts) === 2) {
          $edge = strtoupper(trim($parts[1]));
          if (!empty($edge)) {
            $time = current_time('mysql');

            if ($full_proxy_pass) {
              // Full Proxy Pass — accumulate unique edges only, no per-URL stats
              if (!isset($stats['_edges'])) {
                $stats['_edges'] = [];
              }
              if (!in_array($edge, $stats['_edges'], true)) {
                $stats['_edges'][] = $edge;
              }
            } else {
              // Normal mode — track per-URL stats
              if (!isset($stats[$url])) {
                $stats[$url] = [];
              }
              $stats[$url][] = "$time@$edge";
              if (count($stats[$url]) > 5) {
                array_shift($stats[$url]);
              }
            }

            WPFF_SP_Helpers::log("[OK] $url | CF Edge: $edge");
          }
        }
      }

      sleep($delay_between_urls);
    }

    set_transient('wpff_sp_batch_stats', $stats, 6 * HOUR_IN_SECONDS);

    $cursor['index'] += count($batch);
    if ($cursor['index'] >= count($queue)) {
      // Run is complete — calculate duration
      $duration_seconds = isset($cursor['started_at']) ? time() - $cursor['started_at'] : null;
      $duration_string  = $duration_seconds !== null ? self::format_duration($duration_seconds) : __('Unknown', 'super-preloader-for-cloudflare');
      $completed_at     = current_time('Y-m-d H:i:s');
      $started_at = isset($cursor['started_at']) 
        ? wp_date('Y-m-d H:i:s', $cursor['started_at']) 
        : $completed_at;

      // Save run metadata for both modes
      update_option('wpff_sp_last_run_meta', [
        'started_at'   => $started_at,
        'completed_at' => $completed_at,
        'duration'     => $duration_string,
        'mode'         => $full_proxy_pass ? 'full_proxy_pass' : 'normal',
      ], false);

      if ($full_proxy_pass) {
        // Full Proxy Pass — store simple summary stats
        $url_count   = count(array_unique(array_column(array_filter($queue, 'is_array'), 'url')));
        $proxy_count = count($proxies);
        $edges       = isset($stats['_edges']) ? $stats['_edges'] : [];

        update_option('wpff_sp_preload_stats', [
          'total_urls'     => $url_count,
          'total_proxies'  => $proxy_count,
          'total_requests' => $url_count * $proxy_count,
          'edges'          => $edges,
        ], false);

        WPFF_SP_Helpers::log(sprintf(
          /* translators: 1: URL count, 2: proxy count, 3: total requests */
          esc_html__('Full Proxy Pass completed. %1$d URLs x %2$d proxies = %3$d requests processed.', 'super-preloader-for-cloudflare'),
          $url_count,
          $proxy_count,
          $url_count * $proxy_count
        ));
        WPFF_SP_Helpers::log(sprintf(
          /* translators: %s: duration string */
          esc_html__('Time taken: %s.', 'super-preloader-for-cloudflare'),
          $duration_string
        ));
        if (!empty($edges)) {
          WPFF_SP_Helpers::log(esc_html__('CF Edge locations reached:', 'super-preloader-for-cloudflare'));
          foreach ($edges as $edge) {
            WPFF_SP_Helpers::log('  - ' . $edge);
          }
        }
      } else {
        // Normal mode — merge batch stats into persistent stats
        $previous = get_option('wpff_sp_preload_stats', []);

        foreach ($stats as $url => $new_entries) {
          if ($url === '_edges') continue;
          if (!isset($previous[$url])) {
            $previous[$url] = [];
          }

          // Append the new run as a joined string of time+edge (or just the latest if multiple)
          $last             = end($new_entries);
          $previous[$url][] = $last;

          if (count($previous[$url]) > 5) {
            array_shift($previous[$url]);
          }
        }

        update_option('wpff_sp_preload_stats', $previous, false); // Avoid autoloading large data
        WPFF_SP_Helpers::log(esc_html__('Preload completed.', 'super-preloader-for-cloudflare'));
      }

      /**
       * Fires when a preload run has fully completed.
       *
       * @param array $stats The final stats saved to wpff_sp_preload_stats.
       */
      do_action('wpff_sp_preloader_completed', get_option('wpff_sp_preload_stats', []));

      delete_transient('wpff_sp_batch_stats');
      delete_transient('wpff_sp_preload_cursor');
      delete_transient('wpff_sp_preload_urls');
    } else {
      set_transient('wpff_sp_preload_cursor', $cursor, 24 * HOUR_IN_SECONDS);
      wp_schedule_single_event(time() + 2, 'wpff_sp_run_preloader', [true]);
      // translators: %d is the index of the next scheduled preload batch.
      WPFF_SP_Helpers::log(sprintf(esc_html__('Scheduled next batch at index %d', 'super-preloader-for-cloudflare'), $cursor['index']));
    }
  }
}