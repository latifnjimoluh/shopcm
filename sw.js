// ============================================================
// ShopCM — Service Worker (PWA)
// Stratégie : Cache-first pour les assets statiques,
//             Network-first pour les pages PHP
// ============================================================

const CACHE_NAME    = 'shopcm-v1';
const OFFLINE_URL   = '/shopcm/offline.html';

// Assets statiques à mettre en cache immédiatement (install)
const STATIC_ASSETS = [
  '/shopcm/',
  '/shopcm/index.php',
  '/shopcm/assets/css/style.css',
  '/shopcm/assets/js/script.js',
  '/shopcm/assets/images/icons/icon-192.png',
  '/shopcm/assets/images/icons/icon-512.png',
  '/shopcm/assets/images/placeholder.jpg',
  '/shopcm/offline.html',
];

// ── Installation : précache des assets statiques ────────────
self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// ── Activation : suppression des anciens caches ─────────────
self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (key) { return key !== CACHE_NAME; })
          .map(function (key) { return caches.delete(key); })
      );
    })
  );
  self.clients.claim();
});

// ── Fetch : stratégie selon le type de ressource ────────────
self.addEventListener('fetch', function (event) {
  var url = new URL(event.request.url);

  // Ne pas intercepter les requêtes non-GET
  if (event.request.method !== 'GET') return;

  // Ne pas intercepter les requêtes hors scope
  if (!url.pathname.startsWith('/shopcm/')) return;

  // ── Assets statiques (CSS, JS, images) : Cache-first ──────
  if (
    url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)$/)
  ) {
    event.respondWith(
      caches.match(event.request).then(function (cached) {
        if (cached) return cached;
        return fetch(event.request).then(function (response) {
          if (response && response.status === 200) {
            var clone = response.clone();
            caches.open(CACHE_NAME).then(function (cache) {
              cache.put(event.request, clone);
            });
          }
          return response;
        });
      })
    );
    return;
  }

  // ── Pages PHP : Network-first avec fallback offline ────────
  event.respondWith(
    fetch(event.request)
      .then(function (response) {
        // Mettre en cache une copie fraîche des pages visitées
        if (response && response.status === 200) {
          var clone = response.clone();
          caches.open(CACHE_NAME).then(function (cache) {
            cache.put(event.request, clone);
          });
        }
        return response;
      })
      .catch(function () {
        // Réseau indisponible → chercher dans le cache
        return caches.match(event.request).then(function (cached) {
          if (cached) return cached;
          // Aucune version cachée → page offline générique
          return caches.match(OFFLINE_URL);
        });
      })
  );
});
