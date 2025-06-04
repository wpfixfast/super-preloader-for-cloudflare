<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 *
 * @param string $url   The target URL to request.
 * @param string $proxy Proxy in ip:port format.
 * @param string $auth  Proxy auth in username:password format.
 * @param int    $timeout Request timeout in seconds.
 * @return array {
 *     @type string $body   Response body.
 *     @type string $cf_ray CF-Ray edge location header if found.
 * }
 */
function wpff_sp_proxy_request($url, $proxy, $auth, $timeout = 15) {
  // Temporarily set proxy and auth via filter
  add_filter('http_api_curl', function ($handle) use ($proxy, $auth) {
    curl_setopt($handle, CURLOPT_PROXY, $proxy);
    curl_setopt($handle, CURLOPT_PROXYUSERPWD, $auth);
    // Optionally enable raw header output for debugging
    //curl_setopt($handle, CURLOPT_HEADER, true);
  });

  $response = wp_remote_get($url, [
    'timeout' => $timeout,
    'headers' => [
      'User-Agent' => 'WP Fix Fast Super Preloader/1.0',
    ],
  ]);

  // Remove the filter to prevent it affecting other requests
  remove_all_filters('http_api_curl');

  if (is_wp_error($response)) {
    wpff_sp_log('HTTP error (proxy): ' . $response->get_error_message());
    return ['body' => '', 'cf_ray' => ''];
  }

  $body    = wp_remote_retrieve_body($response);
  $headers = wp_remote_retrieve_headers($response);
  $cf_ray  = isset($headers['cf-ray']) ? trim($headers['cf-ray']) : '';

  return [
    'body'   => $body,
    'cf_ray' => $cf_ray,
  ];
}

function wpff_sp_direct_request($url, $timeout = 15) {
  $response = wp_remote_get($url, [
    'timeout' => $timeout,
    'headers' => [
      'User-Agent' => 'WP Fix Fast Super Preloader/1.0',
    ],
  ]);

  if (is_wp_error($response)) {
    wpff_sp_log('HTTP error (direct): ' . $response->get_error_message());
    return ['body' => '', 'cf_ray' => ''];
  }

  $body    = wp_remote_retrieve_body($response);
  $headers = wp_remote_retrieve_headers($response);
  $cf_ray  = isset($headers['cf-ray']) ? trim($headers['cf-ray']) : '';

  return [
    'body'   => $body,
    'cf_ray' => $cf_ray,
  ];
}
