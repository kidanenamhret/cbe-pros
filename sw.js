const CACHE_NAME = 'cbe-pros-v1';
const ASSETS = [
    '/cbe-pros/',
    '/cbe-pros/index.php',
    '/cbe-pros/css/style.css',
    '/cbe-pros/css/dashboard.css',
    '/cbe-pros/js/dashboard.js'
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
