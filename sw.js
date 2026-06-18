const CACHE_NAME = 'maroc-pc-v4';
const APP_SHELL = [
  './',
  './index.php',
  './products.php',
  './cart.php',
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

  const url = new URL(request.url);
  const pageName = url.pathname.split('/').pop() || 'index.php';
  const storefrontPages = new Set(['index.php', 'products.php', 'cart.php']);
  const isStorefrontPage = storefrontPages.has(pageName);

  // Bypass cache completely for dynamic PHP APIs and session statuses.
  if (url.pathname.includes('/api/') || (url.pathname.endsWith('.php') && !isStorefrontPage)) {
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
              return caches.match('./index.php');
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
