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
function wpff_sp_http_request($url, $proxy = null, $auth = null, $timeout = 15) {

  $curl_filter = function ($handle) use ($proxy, $auth) {
    if (!empty($proxy)) {
      curl_setopt($handle, CURLOPT_PROXY, $proxy);
    }
    if (!empty($auth)) {
      curl_setopt($handle, CURLOPT_PROXYUSERPWD, $auth);
    }
    curl_setopt($handle, CURLOPT_USERAGENT, WPFF_SP_USER_AGENT );
    // Optionally enable raw header output for debugging
    // curl_setopt($handle, CURLOPT_HEADER, true);
  };

  // Temporary filter to set proxy, auth, and user-agent
  add_filter('http_api_curl', $curl_filter);

  // Make the request
  $response = wp_remote_get($url, [
    'timeout' => $timeout,
    'headers' => [
      'User-Agent' => WPFF_SP_USER_AGENT,
    ],
    'user-agent' => WPFF_SP_USER_AGENT,
  ]);

  // Remove the filter to prevent it affecting other requests
  remove_filter('http_api_curl', $curl_filter);

  if (is_wp_error($response)) {
    $connection_type = !empty($proxy) ? 'proxy' : 'direct';
    wpff_sp_log('HTTP error (' . $connection_type . '): ' . $response->get_error_message());
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
