const CACHE_NAME = 'inafo-client-shell-v2';
const OFFLINE_URL = '/pwa/offline.html';
const VERSION_URL = '/website-pwa-version.json';
const WEBSITE_MANIFEST_URL = '/website-manifest.webmanifest';
const SHELL_ASSETS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  WEBSITE_MANIFEST_URL,
  '/pwa/icon.svg',
  '/pwa/icon-maskable.svg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
  if (event.data?.type === 'REFRESH_PWA_ASSETS') {
    event.waitUntil(
      caches.open(CACHE_NAME).then(async (cache) => {
        await Promise.all([
          cache.add(new Request(WEBSITE_MANIFEST_URL, { cache: 'reload' })),
          cache.add(new Request(VERSION_URL, { cache: 'reload' })),
        ]);
      })
    );
  }
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  if (url.pathname === VERSION_URL || url.pathname === WEBSITE_MANIFEST_URL) {
    event.respondWith(fetch(request, { cache: 'no-store' }).catch(() => caches.match(request)));
    return;
  }

  if (SHELL_ASSETS.includes(url.pathname)) {
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
  }
});
