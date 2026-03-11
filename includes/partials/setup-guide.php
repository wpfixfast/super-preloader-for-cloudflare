<?php
if (!defined('ABSPATH')) {
  exit();
}
?>

<div class="wpff-sp-setup-guide postbox mt-40">
  <div class="inside" style="max-width: 1024px;">
    <h2><?php echo esc_html(__('Setup Guide', 'super-preloader-for-cloudflare')); ?></h2>
    <p>
      <?php echo esc_html__(
  'This plugin helps warm up Cloudflare edge caches by preloading your public URLs using a Worker script and optional rotating proxies.',
  'super-preloader-for-cloudflare'
); ?>
    </p>
    <p>
      <?php echo esc_html__(
  'It helps reduce time-to-first-byte (TTFB) and improve real user performance metrics by ensuring content is already cached near your visitors.',
  'super-preloader-for-cloudflare'
); ?>
    </p>
    <p>
      <?php echo esc_html__(
  'Normally, Cloudflare only populates its edge cache in a specific location when a visitor from that region requests the page. This means users in some regions may experience slower load times until the cache is warmed up. By sending preload requests from various IP locations, the plugin helps increase Cloudflare cache hit rates across multiple edges.',
  'super-preloader-for-cloudflare'
); ?>
    </p>
    <p>
      <?php
echo wp_kses_post(
  sprintf(
    // translators: %s is a clickable link to the Super Page Cache plugin.
    __(
      'It works best when used together with a caching plugin like %s, since Cloudflare does not cache HTML responses by default—only static assets like images, CSS, and JS. This can lead to better scores in Core Web Vitals, especially the Largest Contentful Paint (LCP), which is a key factor in SEO and user experience.',
      'super-preloader-for-cloudflare'
    ),
    '<a href="https://wordpress.org/plugins/wp-cloudflare-page-cache/" target="_blank" rel="noopener">Super Page Cache</a>'
  )
);
?>
    </p>
    <h3><?php echo esc_html(__('Steps to Get Started:', 'super-preloader-for-cloudflare')); ?></h3>
    <ol>
      <li><a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-create-and-deploy-a-cloudflare-worker" target="_blank" rel="noopener"><?php echo esc_html(__('Create and Deploy Cloudflare Worker URL', 'super-preloader-for-cloudflare')); ?></a></li>
      <li><a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-register-and-use-webshare-proxies" target="_blank" rel="noopener"><?php echo esc_html(__('Register and Use Webshare Proxies', 'super-preloader-for-cloudflare')); ?></a></li>
      <li><a href="https://wordpress.org/plugins/search/seo/" target="_blank" rel="noopener"><?php echo esc_html(__('Create your sitemap using Yoast, RankMath, or similar SEO plugin', 'super-preloader-for-cloudflare')); ?></a></li>
      <li><?php echo esc_html(__('Start Preloading', 'super-preloader-for-cloudflare')); ?></li>
    </ol>
    <h2><?php echo esc_html(__('FAQs', 'super-preloader-for-cloudflare')); ?></h2>
    <ul class="ul-disc">
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#what-is-cloudflare-edge" target="_blank" rel="noopener">
          <?php echo esc_html(__('What is Cloudflare Edge?', 'super-preloader-for-cloudflare')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-you-should-set-up-super-page-cache-first" target="_blank" rel="noopener">
          <?php echo esc_html(__('Why you should set up Super Page Cache first', 'super-preloader-for-cloudflare')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-a-cloudflare-worker-is-needed-to-preload-cache" target="_blank" rel="noopener">
          <?php echo esc_html(__('Why a Cloudflare Worker is needed to preload cache', 'super-preloader-for-cloudflare')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-check-cache-from-your-location" target="_blank" rel="noopener">
          <?php echo esc_html(__('How to Check if it\'s working', 'super-preloader-for-cloudflare')); ?>
        </a>
      </li>      
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#usage-notes-and-key-considerations" target="_blank" rel="noopener">
          <?php echo esc_html(__('Usage notes and key considerations', 'super-preloader-for-cloudflare')); ?>
        </a>
      </li>   
    </ul>
    <h2><?php echo esc_html(__("What's New in 1.0.6", 'super-preloader-for-cloudflare')); ?></h2>
    <ul class="ul-disc">
      <li><?php echo esc_html(__('Refactored plugin internals to a class-based architecture for improved maintainability and reliability.', 'super-preloader-for-cloudflare')); ?></li>
      <li><?php echo esc_html__('Added Full Proxy Pass mode — sends every URL through every proxy for maximum Cloudflare edge coverage.', 'super-preloader-for-cloudflare'); ?></li>
      <li><?php echo esc_html__('Improved overlapping run protection — if a cron event fires while a run is already in progress, it is automatically skipped.', 'super-preloader-for-cloudflare'); ?></li>
      <li><?php echo esc_html__('Added a live status sidebar on the Settings page — shows current mode, running or idle status, last run times, URL count and proxy count at a glance.', 'super-preloader-for-cloudflare'); ?></li>
      <li><?php echo esc_html__('Auto-refresh logs are now enabled by default on the Logs tab.', 'super-preloader-for-cloudflare'); ?></li>
    </ul>
  </div>
</div>