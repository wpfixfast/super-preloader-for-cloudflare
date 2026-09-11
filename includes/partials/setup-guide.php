<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>

<h3><?php echo esc_html( __( 'How to Use', 'super-preloader-for-cloudflare' ) ); ?></h3>

<div class="wpff-sp-setup-guide card">
	<h4><?php echo esc_html( __( 'Setup Guide', 'super-preloader-for-cloudflare' ) ); ?></h4>
	<p>
		<?php
		echo esc_html__(
			'This plugin helps warm up Cloudflare edge caches by preloading your public URLs using a Worker script and optional rotating proxies.',
			'super-preloader-for-cloudflare'
		);
		?>
	</p>
	<p>
		<?php
		echo esc_html__(
			'It helps reduce time-to-first-byte (TTFB) and improve real user performance metrics by ensuring content is already cached near your visitors.',
			'super-preloader-for-cloudflare'
		);
		?>
	</p>
	<p>
		<?php
		echo esc_html__(
			'Normally, Cloudflare only populates its edge cache in a specific location when a visitor from that region requests the page. This means users in some regions may experience slower load times until the cache is warmed up. By sending preload requests from various IP locations, the plugin helps increase Cloudflare cache hit rates across multiple edges.',
			'super-preloader-for-cloudflare'
		);
		?>
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
	<h4><?php echo esc_html( __( 'Steps to Get Started:', 'super-preloader-for-cloudflare' ) ); ?></h4>
	<ol>
		<li><a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-automatically-deploy-a-cloudflare-worker-using-an-api-token" target="_blank" rel="noopener"><?php echo esc_html( __( 'Deploy Cloudflare Worker using API Token', 'super-preloader-for-cloudflare' ) ); ?></a></li>
		<li><a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-register-and-use-webshare-proxies" target="_blank" rel="noopener"><?php echo esc_html( __( 'Register and Use Webshare Proxies', 'super-preloader-for-cloudflare' ) ); ?></a></li>
		<li><a href="https://wordpress.org/plugins/search/seo/" target="_blank" rel="noopener"><?php echo esc_html( __( 'Create your sitemap using Yoast, RankMath, or similar SEO plugin', 'super-preloader-for-cloudflare' ) ); ?></a></li>
		<li><?php echo esc_html( __( 'Start Preloading', 'super-preloader-for-cloudflare' ) ); ?></li>
	</ol>
	<p class="description">
		<?php echo esc_html( __( 'Note: The WebShare link on the Settings tab includes our referral code, which supports our plugin development at no extra cost to you.', 'super-preloader-for-cloudflare' ) ); ?>
	</p>
	<h4><?php echo esc_html( __( 'FAQs', 'super-preloader-for-cloudflare' ) ); ?></h4>
	<ul class="ul-disc">
		<li>
		<a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#what-is-cloudflare-edge" target="_blank" rel="noopener">
			<?php echo esc_html( __( 'What is Cloudflare Edge?', 'super-preloader-for-cloudflare' ) ); ?>
		</a>
		</li>
		<li>
		<a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-you-should-set-up-super-page-cache-first" target="_blank" rel="noopener">
			<?php echo esc_html( __( 'Why you should set up Super Page Cache first', 'super-preloader-for-cloudflare' ) ); ?>
		</a>
		</li>
		<li>
		<a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#why-a-cloudflare-worker-is-needed-to-preload-cache" target="_blank" rel="noopener">
			<?php echo esc_html( __( 'Why a Cloudflare Worker is needed to preload cache', 'super-preloader-for-cloudflare' ) ); ?>
		</a>
		</li>
		<li>
		<a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#how-to-check-cache-from-your-location" target="_blank" rel="noopener">
			<?php echo esc_html( __( 'How to Check if it\'s working', 'super-preloader-for-cloudflare' ) ); ?>
		</a>
		</li>
		<li>
		<a href="https://wpfixfast.com/blog/preload-cloudflare-cache/#usage-notes-and-key-considerations" target="_blank" rel="noopener">
			<?php echo esc_html( __( 'Usage notes and key considerations', 'super-preloader-for-cloudflare' ) ); ?>
		</a>
		</li>
	</ul>
	<h4><?php echo esc_html( __( "What's New", 'super-preloader-for-cloudflare' ) ); ?></h4>
	<h5><?php echo esc_html( __( 'Version 1.3.0', 'super-preloader-for-cloudflare' ) ); ?></h5>
	<ul class="ul-disc">
		<li><?php echo esc_html__( 'Added: Cache Coverage report. See real visitor cache hit/miss rates by country from Cloudflare Analytics, with suggested Webshare proxy countries to improve coverage.', 'super-preloader-for-cloudflare' ); ?></li>
		<li><?php echo esc_html__( 'Improved: Exclusions tab now loads instantly instead of waiting on a live sitemap re-fetch every time.', 'super-preloader-for-cloudflare' ); ?></li>
		<li><?php echo esc_html__( 'Improved: Visual improvements on all tabs.', 'super-preloader-for-cloudflare' ); ?></li>
	</ul>
	<h5><?php echo esc_html( __( 'Version 1.2.0', 'super-preloader-for-cloudflare' ) ); ?></h5>
	<ul class="ul-disc">
		<li><?php echo esc_html__( 'Added: Optional automated Cloudflare Worker deployment.', 'super-preloader-for-cloudflare' ); ?></li>
		<li><?php echo esc_html__( 'Improved: Admin bar shortcut', 'super-preloader-for-cloudflare' ); ?></li>
	</ul>
</div>
