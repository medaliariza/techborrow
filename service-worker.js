const CACHE_NAME = 'techborrow-shell-v2';
const APP_SCOPE = self.registration.scope;
const OFFLINE_URL = new URL('offline.html', APP_SCOPE).toString();
const ICON_192_URL = new URL('assets/images/techborrow-192.png', APP_SCOPE).toString();
const ICON_512_URL = new URL('assets/images/techborrow-512.png', APP_SCOPE).toString();

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll([OFFLINE_URL, ICON_192_URL, ICON_512_URL]))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('techborrow-shell-') && key !== CACHE_NAME)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || request.mode !== 'navigate') {
        return;
    }

    event.respondWith(
        fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
});
