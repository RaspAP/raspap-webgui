/**
 * RaspAP Service Worker
 *
 * Provides offline support and asset caching for the RaspAP PWA.
 * Uses a cache-first strategy for static assets and a network-first
 * strategy for all navigations and API/AJAX requests.
 */

const CACHE_NAME = 'raspap-static-v1';

const STATIC_ASSETS = [
    '/',
    '/dist/bootstrap/css/bootstrap.min.css',
    '/dist/sb-admin/css/styles.css',
    '/dist/font-awesome/css/all.min.css',
    '/dist/raspap/css/style.css',
    '/app/css/base.css',
    '/dist/jquery/jquery.min.js',
    '/dist/bootstrap/js/bootstrap.bundle.min.js',
    '/dist/jquery-easing/jquery.easing.min.js',
    '/dist/chart.js/Chart.min.js',
    '/dist/sb-admin/js/scripts.js',
    '/dist/jquery-mask/jquery.mask.min.js',
    '/app/js/app.js',
    '/app/js/helpers.js',
    '/app/icons/favicon.svg',
    '/app/icons/favicon-96x96.png',
    '/app/icons/apple-touch-icon.png',
    '/app/icons/web-app-manifest-192x192.png',
    '/app/icons/web-app-manifest-512x512.png',
    '/app/site.webmanifest',
];

// Install: pre-cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            )
        )
    );
    self.clients.claim();
});

// Fetch: cache-first for static assets, network-first for everything else
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only handle same-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Network-first for navigation, AJAX, and API requests
    if (
        request.mode === 'navigate' ||
        url.pathname.startsWith('/ajax/') ||
        url.pathname.startsWith('/api/')
    ) {
        event.respondWith(
            fetch(request).catch(() => {
                // Fall back to the cached root only when available (e.g. offline)
                return caches.match('/').then((cached) => cached || Response.error());
            })
        );
        return;
    }

    // Cache-first for static assets
    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }
            return fetch(request).then((response) => {
                // Cache valid responses for future use
                if (response && response.status === 200 && response.type === 'basic') {
                    // Only cache same-origin ('basic') responses; opaque/CORS responses
                    // are excluded because their status cannot be verified.
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            });
        })
    );
});
