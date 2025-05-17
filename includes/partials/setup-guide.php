<?php
if (!defined('ABSPATH')) {
  exit();
}
?>

<div class="wpff-sp-setup-guide postbox mt-40">
  <div class="inside">
    <h2><?php echo esc_html(__('Getting Started', 'wpfixfast-super-preloader')); ?></h2>
    <p>
      <?php echo esc_html__(
  'This plugin helps warm up Cloudflare edge caches by preloading your public URLs using a Worker script and optional rotating proxies. It helps reduce time-to-first-byte (TTFB) and improve real user performance metrics by ensuring content is already cached near your visitors.',
  'wpfixfast-super-preloader'
); ?>
    </p>

    <p>
      <?php echo esc_html__(
  'Normally, Cloudflare only populates its edge cache in a specific location when a visitor from that region requests the page. This means content may be slow for first-time visitors in other geographic regions. By sending preload requests from various IP locations, the plugin helps increase Cloudflare cache hit rates across multiple edges.',
  'wpfixfast-super-preloader'
); ?>
    </p>

    <p>
      <?php
echo wp_kses_post(
  sprintf(
    // translators: %s is a clickable link to the Super Page Cache plugin.
    __(
      'It works best when used together with a caching plugin like %s, since Cloudflare does not cache HTML responses by default—only static assets like images, CSS, and JS. This can lead to better scores in Core Web Vitals—especially the Largest Contentful Paint (LCP)—which is a key factor in SEO and user experience.',
      'wpfixfast-super-preloader'
    ),
    '<a href="https://wordpress.org/plugins/wp-cloudflare-page-cache/" target="_blank" rel="noopener">Super Page Cache</a>'
  )
);
?>
    </p>
    <p>
      <?php echo esc_html__(
  'To get started, follow the detailes instructions at our How to Use section below. Recommended steps: 1) Create and Deploy Cloudflare Worker URL. 2) Get Webshare Proxies Download Link. 3) Create your sitemap using Yoast SEO, RankMath SEO, or similar plugin. 4) Start Preloading.',
  'wpfixfast-super-preloader'
); ?>
    </p>
    <h2><?php echo esc_html(__('How to Use', 'wpfixfast-super-preloader')); ?></h2>
    <ul class="ul-disc">
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#what-is-cloudflare-edge" target="_blank" rel="noopener">
          <?php echo esc_html(__('What is Cloudflare Edge?', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-you-should-set-up-super-page-cache-first" target="_blank" rel="noopener">
          <?php echo esc_html(__('Why you should set up Super Page Cache first', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-a-cloudflare-worker-is-needed-to-preload-cache" target="_blank" rel="noopener">
          <?php echo esc_html(__('Why a Cloudflare Worker is needed to preload cache', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-create-and-deploy-a-cloudflare-worker" target="_blank" rel="noopener">
          <?php echo esc_html(__('How to create and deploy a Cloudflare Worker', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-register-and-use-webshare-proxies" target="_blank" rel="noopener">
          <?php echo esc_html(__('How to register and use Webshare Proxies', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
      <li>
        <a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#usage-notes-and-key-considerations" target="_blank" rel="noopener">
          <?php echo esc_html(__('Usage Notes and Key Considerations', 'wpfixfast-super-preloader')); ?>
        </a>
      </li>
    </ul>
  </div>
</div>