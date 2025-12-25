# Super Preloader for Cloudflare

**Preload your sitemap URLs into multiple Cloudflare edge locations using proxies and a custom Cloudflare Worker.**

[![Donate](https://img.shields.io/badge/Donate-WPFixFast-blue)](https://wpfixfast.com/)

---

## Description

Super Preloader for Cloudflare helps you warm your site's edge cache across multiple Cloudflare nodes using proxies and a Worker URL. Perfect for globally distributed cache coverage.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate via the Plugins menu in WordPress
3. Go to **Settings → Super Preloader** to configure your Worker, Proxy list, and Sitemap.

## Getting Started

This plugin helps warm up Cloudflare edge caches by preloading your public URLs using a Worker script and optional rotating proxies. It helps reduce time-to-first-byte (TTFB) and improve real user performance metrics by ensuring content is already cached near your visitors.

Normally, Cloudflare only populates its edge cache in a specific location when a visitor from that region requests the page. This means content may be slow for first-time visitors in other geographic regions. By sending preload requests from various IP locations, the plugin helps increase Cloudflare cache hit rates across multiple edges.

It works best when used together with a caching plugin like [Super Page Cache](https://wordpress.org/plugins/wp-cloudflare-page-cache/), since Cloudflare does not cache HTML responses by default—only static assets like images, CSS, and JS. This can lead to better scores in Core Web Vitals—especially the Largest Contentful Paint (LCP)—which is a key factor in SEO and user experience.

**To get started, follow the detailed instructions in our [How to Use](#how-to-use) section below.**

Recommended steps:

1. Create and deploy your Cloudflare Worker URL
2. Get your Webshare proxies download link
3. Create your sitemap using Yoast SEO, RankMath SEO, or a similar plugin
4. Start preloading

## How to Use

- [What is Cloudflare Edge?](https://wpfixfast.com/blog/preload-cloudflare-cache/#what-is-cloudflare-edge)
- [Why you should set up Super Page Cache first](https://wpfixfast.com/blog/preload-cloudflare-cache/#why-you-should-set-up-super-page-cache-first)
- [Why a Cloudflare Worker is needed to preload cache](https://wpfixfast.com/blog/preload-cloudflare-cache/#why-a-cloudflare-worker-is-needed-to-preload-cache)
- [How to create and deploy a Cloudflare Worker](https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-create-and-deploy-a-cloudflare-worker)
- [How to register and use Webshare Proxies](https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-register-and-use-webshare-proxies)
- [Usage Notes and Key Considerations](https://wpfixfast.com/blog/preload-cloudflare-cache/#usage-notes-and-key-considerations)

## Frequently Asked Questions

**What do I need to use this plugin?**

You need a Cloudflare Worker URL (required) and optionally Webshare.io proxies for better global coverage. The plugin provides the Worker script code to deploy to your Cloudflare account.

**Do I need a caching plugin?**

It's highly recommended. Cloudflare only caches static files by default, not HTML pages. Use it with Super Page Cache or similar plugins for best results.

**How does this improve my site performance?**

It pre-warms Cloudflare's edge caches globally, reducing Time to First Byte (TTFB) and improving Core Web Vitals scores. Users worldwide get faster page loads instead of slow "cache miss" responses.

## Screenshots

1. On settings tab you can configure Cloudflare Worker URL, Webshare.io Proxies URL, Sitemap.xml URL, and auto run interval.
2. The Stats tab displays a table showing the last 5 runs and the edge locations where your URLs are cached.
3. Logs tab for checking HTTP responses and debugging.
4. Cloudflare Worker script code to deploy. Set secret key here.
5. How to check if your Cache is HIT, MISS, or BYPASS.
6. Comparison of proxy usage versus direct connections

## Changelog

### 1.0.4 - 25.12.2025 =

- **Improved:** HTTP request helper function to always send a clear User-Agent
- **Improved:** Preloader by increasing cursor time out to 24 hours to avoid incomplete batches
- **Added:** Delay between URLs setting to adjust wait time in seconds between each URL preload
- **Added:** Auto-refresh to log viewer
- **Added:** Stop preloader button to cancel the running preloading process

### 1.0.3 - 30.11.2025 =

- **Improved:** Log file security by migrating logs to super-preloader-for-cloudflare-log.php
- **Added:** Prefixes to global variables
- **Tested:** Compatibility with WordPress 6.9

### 1.0.2 - 20.10.2025

- **Added:** Manual setting of cron start time

### 1.0.1 - 27.07.2025

- **Fixed:** Removed deprecated load_plugin_textdomain() call
- **Improved:** Stats table styling and hover effects
- **Added:** Color coded tooltips for displaying edge locations on hover
- **Added:** Comprehensive statistics summary with cumulative and latest run metrics

### 1.0.0

- Initial release
