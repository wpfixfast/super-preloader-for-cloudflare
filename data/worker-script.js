// Cloudflare Worker for the Super Preloader for Cloudflare WordPress plugin by WP Fix Fast
// Securely preloads and caches target URLs at the Cloudflare edge
// Plugin is intended to work with Webshare.io proxies
// Requires ?url= and valid ?token= based on SHA-256 HMAC-like hash of the URL and secret.
// The secret is bound as the SECRET environment variable at deploy time.
// Visit https://wpfixfast.com/blog/preload-cloudflare-cache/ for more details

async function sha256(input) {
  const encoder = new TextEncoder()
  const data = encoder.encode(input)
  const hashBuffer = await crypto.subtle.digest('SHA-256', data)
  const hashArray = Array.from(new Uint8Array(hashBuffer))
  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('')
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url)
    const targetUrl = url.searchParams.get('url')
    const token = url.searchParams.get('token')

    if (!targetUrl) {
      return new Response('Missing ?url= param', { status: 400 })
    }

    const expectedToken = await sha256(targetUrl + env.SECRET)
    if (token !== expectedToken) {
      return new Response('Invalid token', { status: 401 })
    }

    const cache = caches.default
    const cachedResponse = await cache.match(targetUrl)
    if (cachedResponse) {
      return new Response('Already cached at this edge', { status: 200 })
    }

    const originResponse = await fetch(targetUrl, {
      headers: {
        'User-Agent': 'WP Fix Fast Super Preloader/1.0',
      },
      cf: { cacheEverything: true }
    })

    await cache.put(targetUrl, originResponse.clone())
    return new Response('Created cache at this edge', {
      status: 200,
      headers: {
        'cf-ray': request.headers.get('cf-ray') || ''
      }
    })
  }
}
