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

## Changelog

### 1.0.0
- Initial release
