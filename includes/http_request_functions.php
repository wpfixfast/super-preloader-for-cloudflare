<?php
if (!defined('ABSPATH')) {
  exit();
}

/**
 * Performs a GET request through an authenticated proxy using cURL.
 *
 * This plugin intentionally uses cURL instead of wp_remote_get() because:
 * - The WordPress HTTP API does not support authenticated proxy connections using username:password credentials.
 * - Webshare and other rotating proxy providers require full transport-level proxy authentication.
 * - Global Cloudflare edge cache warming requires requests to originate from diverse IPs/locations,
 *   which is only reliably possible through direct proxy control with cURL.
 *
 * The use of cURL here is strictly for this technical requirement.
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
  // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_errno, WordPress.WP.AlternativeFunctions.curl_curl_error, WordPress.WP.AlternativeFunctions.curl_curl_close, WordPress.WP.AlternativeFunctions.curl_curl_getinfo
  // Reason: Direct use of cURL is required for authenticated proxy support (see block comment above).
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_PROXY, $proxy);
  curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_HEADER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: WP Fix Fast Super Preloader/1.0',
  ]);

  $raw = curl_exec($ch);

  if (curl_errno($ch)) {
    wpff_sp_log('cURL error: ' . curl_error($ch));
    curl_close($ch);
    return ['body' => '', 'cf_ray' => ''];
  }

  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $headers_raw = substr($raw, 0, $header_size);
  $body        = substr($raw, $header_size);
  curl_close($ch);
  // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_init, WordPress.WP.AlternativeFunctions.curl_curl_setopt, WordPress.WP.AlternativeFunctions.curl_curl_exec, WordPress.WP.AlternativeFunctions.curl_curl_errno, WordPress.WP.AlternativeFunctions.curl_curl_error, WordPress.WP.AlternativeFunctions.curl_curl_close, WordPress.WP.AlternativeFunctions.curl_curl_getinfo

  $cf_ray = '';
  foreach (explode("\r\n", $headers_raw) as $line) {
    if (stripos($line, 'cf-ray:') !== false) {
      $cf_ray = trim(str_ireplace('cf-ray:', '', $line));
      break;
    }
  }

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
