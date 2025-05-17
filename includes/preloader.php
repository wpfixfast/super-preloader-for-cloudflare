<?php
if (!defined('ABSPATH')) {
  exit();
}

function wpff_sp_run_preloader() {
  $worker    = get_option('wpff_sp_worker_url');
  $proxy_url = get_option('wpff_sp_proxy_list_url');
  $sitemap   = get_option('wpff_sp_sitemap_url');

  if (empty($worker) || empty($sitemap)) {
    wpff_sp_log(esc_html__('Missing required settings: worker or sitemap.', 'wpfixfast-super-preloader'));
    return;
  }

  $proxies = [];

  if (!empty($proxy_url)) {
    $proxy_list = wp_remote_get($proxy_url);

    if (is_wp_error($proxy_list)) {
      // translators: %s is the error message from the proxy list request.
      wpff_sp_log(sprintf(esc_html__('Failed to download proxy list: %s', 'wpfixfast-super-preloader'), $proxy_list->get_error_message()));
    } else {
      $proxies = array_filter(
        array_map(
          'trim',
          preg_split('/\r?\n/', wp_remote_retrieve_body($proxy_list))
        )
      );
    }
  }

  $sitemap_xml = wp_remote_get($sitemap);
  if (is_wp_error($sitemap_xml)) {
    wpff_sp_log(
      esc_html__('Failed to download sitemap: ', 'wpfixfast-super-preloader') .
      esc_html($sitemap_xml->get_error_message())
    );
    return;
  }

  $urls = get_transient('wpff_sp_preload_urls');
  if (!is_array($urls)) {
    $urls   = [];
    $parsed = simplexml_load_string(wp_remote_retrieve_body($sitemap_xml));
    if (isset($parsed->url)) {
      foreach ($parsed->url as $entry) {
        $urls[] = (string)$entry->loc;
      }
    } elseif (isset($parsed->sitemap)) {
      foreach ($parsed->sitemap as $entry) {
        $loc   = (string)$entry->loc;
        $child = wp_remote_get($loc);
        if (!is_wp_error($child)) {
          $sub = simplexml_load_string(wp_remote_retrieve_body($child));
          foreach ($sub->url as $e) {
            $urls[] = (string)$e->loc;
          }
        }
      }
    }
    shuffle($urls);
    set_transient('wpff_sp_preload_urls', $urls, 24 * HOUR_IN_SECONDS);
    update_option('wpff_sp_sitemap_url_count', count($urls));
  }

  $stats = get_transient('wpff_sp_batch_stats');
  if (!is_array($stats)) {
    $stats = [];
  }

  $cursor = get_transient('wpff_sp_preload_cursor');
  if (!is_array($cursor)) {
    $cursor = ['index' => 0];
  }

  // Batch size
  $batch_size = (int)get_option('wpff_sp_batch_size', 10);
  $batch      = array_slice($urls, $cursor['index'], $batch_size);

  // Secret for token generation
  $shared_secret = sanitize_text_field(get_option('wpff_sp_shared_secret', ''));

  foreach ($batch as $url) {
    $token      = wpff_sp_generate_token($url, $shared_secret);
    $target_url = $worker . '?url=' . urlencode($url) . '&token=' . $token;

    $use_proxy = false;
    $proxy     = '';
    $auth      = '';

    if (!empty($proxies)) {
      $proxy_candidate = $proxies[array_rand($proxies)];
      if (substr_count($proxy_candidate, ':') === 3) {
        [$ip, $port, $user, $pass] = explode(':', $proxy_candidate);
        $use_proxy                 = true;
        $proxy                     = "$ip:$port";
        $auth                      = "$user:$pass";
      }
    }

    if ($use_proxy) {
      // translators: %1$s is the URL being warmed, %2$s is the proxy IP and port.
      wpff_sp_log(sprintf(esc_html__('Warming: %1$s via %2$s', 'wpfixfast-super-preloader'), $url, $proxy));
      $response = wpff_sp_proxy_request($target_url, $proxy, $auth);
    } else {
      // translators: %s is the URL being warmed directly from the server.
      wpff_sp_log(sprintf(esc_html__('Warming: %s directly from server', 'wpfixfast-super-preloader'), $url));
      $response = wpff_sp_direct_request($target_url);
    }

    // translators: %s is the HTTP response body returned from the preload request.
    wpff_sp_log(sprintf(esc_html__('Response: %s', 'wpfixfast-super-preloader'), trim($response['body'])));

    if (!empty($response['cf_ray'])) {
      $parts = explode('-', trim($response['cf_ray']));
      if (count($parts) === 2) {
        $edge = strtoupper(trim($parts[1]));
        if (!empty($edge)) {
          $time = current_time('mysql');
          if (!isset($stats[$url])) {
            $stats[$url] = [];
          }
          $stats[$url][] = "$time@$edge";
          if (count($stats[$url]) > 5) {
            array_shift($stats[$url]);
          }
          wpff_sp_log("[OK] $url | CF Edge: $edge");
        }
      }
    }

    sleep(1);
  }

  set_transient('wpff_sp_batch_stats', $stats, 15 * MINUTE_IN_SECONDS);

  $cursor['index'] += count($batch);
  if ($cursor['index'] >= count($urls)) {
    $previous = get_option('wpff_sp_preload_stats', []);

    foreach ($stats as $url => $new_entries) {
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

    update_option('wpff_sp_preload_stats', $previous);
    delete_transient('wpff_sp_batch_stats');
    delete_transient('wpff_sp_preload_cursor');
    delete_transient('wpff_sp_preload_urls'); // Clear cached sitemap
    wpff_sp_log(esc_html__('Preload completed.', 'wpfixfast-super-preloader'));
  } else {
    set_transient('wpff_sp_preload_cursor', $cursor, 10 * MINUTE_IN_SECONDS);
    wp_schedule_single_event(time() + 2, 'wpff_sp_run_preloader');
    // translators: %d is the index of the next scheduled preload batch.
    wpff_sp_log(sprintf(esc_html__('Scheduled next batch at index %d', 'wpfixfast-super-preloader'), $cursor['index']));
  }
}
