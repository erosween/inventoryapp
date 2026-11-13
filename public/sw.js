const CACHE_PREFIX = 'inventory-msp-pwa-';
const CACHE_VERSION = 'v7';
const STATIC_CACHE = `${CACHE_PREFIX}${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
  OFFLINE_URL,
  '/manifest.json',
  '/manifest-admin.json',
  '/assets/img/surya-logo-mobile.webp',
  '/assets/pwa/surya/icon-180.png',
  '/assets/pwa/surya/icon-192.png',
  '/assets/pwa/surya/icon-512.png',
  '/assets/pwa/surya/icon-maskable-192.png',
  '/assets/pwa/surya/icon-maskable-512.png',
  '/assets/pwa/inventory/icon-180.png',
  '/assets/pwa/inventory/icon-192.png',
  '/assets/pwa/inventory/icon-512.png',
  '/assets/pwa/inventory/icon-maskable-192.png',
  '/assets/pwa/inventory/icon-maskable-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(PRECACHE_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(cacheNames => Promise.all(
        cacheNames
          .filter(cacheName => (
            (cacheName.startsWith(CACHE_PREFIX) || cacheName === 'msp-sales-mobile-pwa-v1') &&
            cacheName !== STATIC_CACHE
          ))
          .map(cacheName => caches.delete(cacheName))
      ))
      .then(() => self.clients.claim())
  );
});

async function networkFirst(request) {
  try {
    return await fetch(request, { cache: 'no-store' });
  } catch (error) {
    return (await caches.match(OFFLINE_URL, { cacheName: STATIC_CACHE })) || Response.error();
  }
}

self.addEventListener('fetch', event => {
  const { request } = event;

  if (request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(request.url);

  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
    return;
  }

  // Keep Cache Storage limited to the install-time list. Other assets use HTTP caching.
  if (requestUrl.origin === self.location.origin &&
      !requestUrl.search && PRECACHE_ASSETS.includes(requestUrl.pathname)) {
    event.respondWith(
      caches.match(request, { cacheName: STATIC_CACHE })
        .catch(() => undefined)
        .then(response => response || fetch(request))
    );
  }
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
