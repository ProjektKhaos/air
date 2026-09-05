'use strict';

const CACHE = 'chiang-mai-air-watch-shell-v1.1.4';
const SCOPE = new URL('./', self.location.href).pathname;
const SHELL = [
  'offline.php',
  'manifest.webmanifest',
  'air_logo1.png',
  'assets/css/app.css',
  'assets/js/theme-init.js',
  'assets/js/sw-update.js',
  'assets/js/app.js',
  'assets/js/offline.js',
  'assets/js/home.js',
  'assets/js/stations.js',
  'assets/js/station.js',
  'assets/js/push.js',
  'assets/vendor/chart.umd.min.js',
  'assets/vendor/leaflet.css',
  'assets/vendor/leaflet.js',
  'assets/vendor/MarkerCluster.css',
  'assets/vendor/MarkerCluster.Default.css',
  'assets/vendor/leaflet.markercluster.js',
  'assets/fonts/roboto-latin-wght-normal.woff2',
  'assets/fonts/noto-sans-thai-thai-wght-normal.woff2',
  'assets/fonts/material-symbols-rounded.woff2',
].map(path => SCOPE + path);

self.addEventListener('install', event => event.waitUntil(
  caches.open(CACHE).then(cache => cache.addAll(SHELL)).then(() => self.skipWaiting()),
));

self.addEventListener('activate', event => event.waitUntil(
  caches.keys()
    .then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))
    .then(() => self.clients.claim()),
));

self.addEventListener('fetch', event => {
  const request = event.request;
  const requestUrl = new URL(request.url);
  if (request.method !== 'GET' || requestUrl.origin !== location.origin || requestUrl.pathname.startsWith(`${SCOPE}api/`) || requestUrl.hostname === 'tile.openstreetmap.org') return;
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(`${SCOPE}offline.php`)));
    return;
  }
  if (requestUrl.pathname.startsWith(`${SCOPE}assets/`) || requestUrl.pathname === `${SCOPE}manifest.webmanifest` || requestUrl.pathname === `${SCOPE}air_logo1.png`) {
    event.respondWith(caches.open(CACHE).then(cache => cache.match(request).then(hit => hit || fetch(request).then(response => {
      if (response.ok) cache.put(request, response.clone());
      return response;
    }))));
  }
});

self.addEventListener('push', event => {
  let payload = {};
  try { payload = event.data?.json() || {}; } catch {}
  event.waitUntil(self.registration.showNotification(payload.title || 'Chiang Mai Air Watch', {
    body: payload.body || '',
    icon: `${SCOPE}air_logo1.png`,
    badge: `${SCOPE}assets/icons/icon-192.png`,
    tag: payload.tag || 'cmaw-alert',
    renotify: true,
    data: {url: payload.url || `${SCOPE}alerts.php`},
  }));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  event.waitUntil(self.clients.openWindow(event.notification.data?.url || `${SCOPE}alerts.php`));
});
