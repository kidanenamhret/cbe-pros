const CACHE_NAME = 'mesfin-bank-v1';
const ASSETS = [
    '/mesfin-bank/',
    '/mesfin-bank/index.php',
    '/mesfin-bank/css/style.css',
    '/mesfin-bank/css/dashboard.css',
    '/mesfin-bank/js/dashboard.js'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
    );
});

self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request).then(response => {
            return response || fetch(event.request);
        })
    );
});
