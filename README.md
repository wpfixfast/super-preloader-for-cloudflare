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

You need a Cloudflare Worker URL (required) and optionally Webshare.io proxies for better global coverage. The plugin provides the Worker script code to deploy to your Cloudflare account. A sitemap is also required — any SEO plugin like Yoast or RankMath will generate one automatically.

**Do I need a caching plugin?**

Yes, strongly recommended. Cloudflare only caches static files by default — not HTML pages. Without a full-page caching plugin like Super Page Cache, preloading your URLs will have no effect on page load times. Always set up full-page caching before running the preloader.

**How does this improve my site performance?**

It pre-warms Cloudflare's edge caches globally, reducing Time to First Byte (TTFB) and improving Core Web Vitals scores — especially Largest Contentful Paint (LCP). Instead of the first visitor in a region triggering a slow cache miss, content is already cached near them before they arrive.

**What is Full Proxy Pass mode?**

Full Proxy Pass sends every URL through every proxy in your list, maximizing the number of Cloudflare edge locations warmed in a single run. It significantly increases run time and request volume, so it's best used occasionally rather than as your regular scheduled interval. Per-URL stats are not recorded in this mode.

**How many proxies do I need?**

There is no fixed requirement. More proxies from diverse geographic locations means more Cloudflare edge locations get warmed. Webshare.io's rotating residential proxies work well. Even a small list of 10 proxies from different regions can make a meaningful difference.

**Will this slow down my server?**

No. The plugin runs via WordPress cron in the background and processes URLs in small batches with a configurable delay between requests. It does not affect your site's frontend performance or normal traffic.

**What happens if the preloader is already running when the cron fires again?**

The new cron event is automatically skipped. The plugin uses overlapping run protection to ensure only one preload run is active at a time, preventing duplicate requests and server overload.

**How often should I run the preloader?**

It depends on how frequently your content changes and how aggressively Cloudflare expires cached content. For most sites, once or twice daily is sufficient. If you update content frequently, hourly may be more appropriate.

**Why do some URLs show different edge locations each run?**

Cloudflare routes requests to the nearest available edge based on the proxy's geographic location. As proxies rotate and network conditions change, different edges may serve the same URL across runs. This is normal and expected.

**Does this plugin work without proxies?**

Yes. Without proxies, requests go directly from your server and only warm the nearest Cloudflare edge location to your server. Adding proxies from multiple regions is what enables global cache warming.

## Screenshots

1. On settings tab you can configure Cloudflare Worker URL, Webshare.io Proxies URL, Sitemap.xml URL, and auto run (Cron) scheduler.
2. Auto Run Interval displays the Next scheduled run and Auto Run Start Time lets you adjust the exact moment to start the preloader.
3. Sidebar shows current mode, running or idle status, last run times, URL count and proxy count. Stop Preloader button allows stopping the current operation.
4. Full proxy pass mode allows sending every URL through every proxy for maximum Cloudflare edge coverage.
5. The Stats tab displays detailed information on edge locations where your URLs are cached.
6. If full proxy pass mode is selected, per-URL stats are not recorded.
7. Logs tab with Auto refresh feature helps you follow the live HTTP responses and keep track of other important events.
8. How to use tab guides you with the setup process and includes FAQs.
9. Detailed cron scheduler.
10. Cloudflare Worker script code to deploy. Set secret key here.
11. How to check if your Cache is HIT, MISS, or BYPASS.

## Changelog

### 1.0.7 - 16.04.2026

- **Fixed:** Sitemap download timeout increased to 30 seconds to prevent cURL error 28 on slow servers
- **Refactor:** Code internals for better compatibility with WordPress coding standards

### 1.0.6 - 11.03.2026

- **Refactor:** Converted plugin internals to class-based architecture for improved maintainability
- **Improved:** Overlapping run protection — if a cron event fires while a run is already in progress, it is automatically skipped
- **Improved:** Auto-refresh logs are now enabled by default on the Logs tab
- **Added:** Full Proxy Pass mode that sends every URL through every proxy for maximum Cloudflare edge coverage
- **Added:** New sidebar on the Settings page shows current mode, running or idle status, last run times, URL count and proxy count

### 1.0.5 - 16.02.2026

- **Added:** Extra intervals 3hrs, 6hrs, 12hrs, and custom
- **Added:** Next scheduled run date/time display

### 1.0.4 - 25.12.2025

- **Improved:** HTTP request helper function to always send a clear User-Agent
- **Improved:** Preloader by increasing cursor timeout to 24 hours to avoid incomplete batches
- **Added:** Delay between URLs setting to adjust wait time in seconds between each URL preload
- **Added:** Auto-refresh to log viewer
- **Added:** Stop preloader button to cancel the running preloading process

### 1.0.3 - 30.11.2025

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
