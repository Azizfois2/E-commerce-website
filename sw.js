const CACHE_NAME = 'maroc-pc-v2';
const APP_SHELL = [
  './',
  './index.html',
  './products.html',
  './cart.html',
  './assets/css/styles.css',
  './assets/js/theme.js',
  './assets/js/cart.js',
  './assets/js/data.js',
  './logo.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        })
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') return;

  // Bypass cache completely for dynamic PHP APIs and session statuses
  if (request.url.includes('/api/') || request.url.includes('.php')) {
    event.respondWith(fetch(request));
    return;
  }

  // Network-First Strategy for documents, scripts, and stylesheets
  // This guarantees fresh code is served when online, falling back to cache only when offline.
  if (
    request.mode === 'navigate' || 
    request.destination === 'document' || 
    request.destination === 'script' || 
    request.destination === 'style'
  ) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.ok) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cached) => {
            if (cached) return cached;
            if (request.mode === 'navigate') {
              return caches.match('./index.html');
            }
            throw new Error('Offline and no cached response available.');
          });
        })
    );
  } else {
    // Stale-While-Revalidate for static assets (images, fonts, etc.)
    // Serves immediately from cache, fetches latest in background to update cache.
    event.respondWith(
      caches.match(request).then((cached) => {
        const fetchPromise = fetch(request)
          .then((response) => {
            if (response.ok) {
              const copy = response.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
            }
            return response;
          })
          .catch(() => {});
        return cached || fetchPromise;
      })
    );
  }
});
