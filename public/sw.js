const CACHE_NAME = 'msp-sales-mobile-pwa-v1';
const CORE_ASSETS = [
  '/offline.html',
  '/manifest.json',
  '/assets/img/MSP5.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/sweetalert2@11',
  'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => Promise.allSettled(
      CORE_ASSETS.map(url => cache.add(url))
    ))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => Promise.all(
      cacheNames
        .filter(cacheName => cacheName !== CACHE_NAME)
        .map(cacheName => caches.delete(cacheName))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(event.request.url);
  const isMobilePage =
    requestUrl.origin === self.location.origin &&
    requestUrl.pathname.startsWith('/mobile');

  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request, isMobilePage ? { cache: 'no-store' } : undefined)
        .catch(() => caches.match('/offline.html'))
    );
    return;
  }

  const isLocalStatic =
    requestUrl.origin === self.location.origin &&
    (
      requestUrl.pathname.startsWith('/assets/') ||
      requestUrl.pathname.startsWith('/build/') ||
      requestUrl.pathname === '/manifest.json' ||
      requestUrl.pathname === '/offline.html'
    );

  const isCdnStatic = requestUrl.origin !== self.location.origin;

  if (!isLocalStatic && !isCdnStatic) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(cachedResponse => {
      const networkFetch = fetch(event.request)
        .then(response => {
          if (response && response.status < 500) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
          }

          return response;
        })
        .catch(() => cachedResponse);

      return cachedResponse || networkFetch;
    })
  );
});
