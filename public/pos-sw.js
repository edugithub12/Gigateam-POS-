const CACHE_NAME = 'gigateam-pos-v1';
const OFFLINE_URL = '/pos-offline.html';

// Files to cache for offline use
const STATIC_ASSETS = [
    '/pos-offline.html',
    '/images/gigateam-logo.png',
];

// ── Install: cache static assets ─────────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// ── Activate: clean old caches ────────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

// ── Fetch: serve from cache when offline ──────────────────────────────────────
self.addEventListener('fetch', event => {
    // Only handle GET requests for navigation
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // For the offline HTML page — always serve from cache
    if (url.pathname === '/pos-offline.html') {
        event.respondWith(
            caches.match('/pos-offline.html').then(r => r || fetch(event.request))
        );
        return;
    }

    // For the logo
    if (url.pathname.includes('gigateam-logo')) {
        event.respondWith(
            caches.match(event.request).then(r => r || fetch(event.request))
        );
        return;
    }
});

// ── Message: trigger sync ─────────────────────────────────────────────────────
self.addEventListener('message', event => {
    if (event.data === 'skipWaiting') self.skipWaiting();
});