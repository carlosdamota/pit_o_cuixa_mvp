/**
 * Pit o Cuixa — Service Worker
 *
 * Cache strategies:
 *   HTML pages     → network-first, fallback to cache (pages-v1)
 *   CSS / JS / Fonts → cache-first (static-v1)
 *   Images         → cache-first with LRU cap (images-v1)
 *   API responses  → network-first, fallback to cache (api-v1)
 *   Offline fallback → offline.html
 *
 * @version 1.0.0
 */

const CACHE_NAMES = {
  pages: 'pages-v1',
  static: 'static-v1',
  images: 'images-v1',
  api: 'api-v1',
};

const IMAGE_CACHE_MAX = 30; // LRU cap for image cache

// ── Install: Pre-cache offline fallback ────────────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAMES.pages).then((cache) => {
      return cache.add('/offline.html').catch(() => {
        // offline.html might not exist yet — non-fatal
      });
    })
  );
  // Activate immediately — don't wait for page reload
  self.skipWaiting();
});

// ── Activate: Claim all clients and clean old caches ──────────────────────
self.addEventListener('activate', (event) => {
  event.waitUntil(
    Promise.all([
      clients.claim(),
      // Remove outdated cache versions
      caches.keys().then((keys) => {
        const valid = new Set(Object.values(CACHE_NAMES));
        return Promise.all(
          keys
            .filter((key) => !valid.has(key))
            .map((key) => caches.delete(key))
        );
      }),
    ])
  );
});

// ── Fetch: Route to appropriate strategy ──────────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET and navigation preload
  if (request.method !== 'GET') return;

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) return;

  // ── Strategy selection ─────────────────────────────────────────────
  if (request.mode === 'navigate') {
    // HTML pages: network-first, fallback to cache
    event.respondWith(networkFirst(request, CACHE_NAMES.pages));
    return;
  }

  if (url.pathname.startsWith('/api/')) {
    // API responses: network-first, fallback to cache
    event.respondWith(networkFirst(request, CACHE_NAMES.api));
    return;
  }

  if (url.pathname.startsWith('/css/') || url.pathname.startsWith('/js/') || url.pathname.startsWith('/fonts/')) {
    // Static assets: cache-first
    event.respondWith(cacheFirst(request, CACHE_NAMES.static));
    return;
  }

  if (url.pathname.startsWith('/img/')) {
    // Images: cache-first with LRU cap
    event.respondWith(cacheFirstImage(request));
    return;
  }

  // Everything else: network-first (e.g. manifest.json, .htaccess isn't fetched)
  event.respondWith(networkFirst(request, CACHE_NAMES.pages));
});

// ── Cache Strategies ──────────────────────────────────────────────────────

/**
 * Network-first: try the network, fall back to cache on failure.
 * For navigations, cache the response for offline use.
 */
async function networkFirst(request, cacheName) {
  try {
    const response = await fetch(request);

    // Only cache valid responses
    if (response.ok || response.type === 'opaqueredirect') {
      const cache = await caches.open(cacheName);
      // Clone because response body can only be consumed once
      cache.put(request, response.clone());
    }

    return response;
  } catch (err) {
    // Network failed — try cache
    const cached = await caches.match(request);

    if (cached) {
      return cached;
    }

    // For navigation requests, serve offline fallback
    if (request.mode === 'navigate') {
      return caches.match('/offline.html');
    }

    // No cache, no network — cannot serve
    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

/**
 * Cache-first: serve from cache if available, otherwise fetch and cache.
 */
async function cacheFirst(request, cacheName) {
  const cached = await caches.match(request);

  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);

    if (response.ok) {
      const cache = await caches.open(cacheName);
      cache.put(request, response.clone());
    }

    return response;
  } catch (err) {
    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}

/**
 * Cache-first for images with LRU eviction.
 * When the cache exceeds IMAGE_CACHE_MAX entries, the oldest entries are removed.
 */
async function cacheFirstImage(request) {
  const cache = await caches.open(CACHE_NAMES.images);
  const cached = await cache.match(request);

  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);

    if (response.ok) {
      // Evict oldest entries if over limit
      const keys = await cache.keys();
      if (keys.length >= IMAGE_CACHE_MAX) {
        const toDelete = keys.slice(0, keys.length - IMAGE_CACHE_MAX + 1);
        await Promise.all(toDelete.map((key) => cache.delete(key)));
      }

      cache.put(request, response.clone());
    }

    return response;
  } catch (err) {
    return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
  }
}
