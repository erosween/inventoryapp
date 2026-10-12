const CACHE_PREFIX = 'inventory-msp-pwa-';
const CACHE_VERSION = 'v4';
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
    return (await caches.match(OFFLINE_URL)) || Response.error();
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cachedResponse = await cache.match(request);
  const networkResponse = fetch(request)
    .then(response => {
      if (response && (response.ok || response.type === 'opaque')) {
        cache.put(request, response.clone());
      }
      return response;
    })
    .catch(() => null);

  if (cachedResponse) {
    return cachedResponse;
  }

  return (await networkResponse) || Response.error();
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

  const isLocalStatic = requestUrl.origin === self.location.origin && (
    requestUrl.pathname.startsWith('/assets/') ||
    requestUrl.pathname.startsWith('/build/') ||
    requestUrl.pathname === '/manifest.json' ||
    requestUrl.pathname === '/manifest-admin.json' ||
    requestUrl.pathname === OFFLINE_URL ||
    requestUrl.pathname === '/favicon.ico'
  );

  const isTrustedExternalAsset = requestUrl.origin !== self.location.origin &&
    ['font', 'image', 'script', 'style'].includes(request.destination);

  if (isLocalStatic || isTrustedExternalAsset) {
    event.respondWith(staleWhileRevalidate(request));
  }
});

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
