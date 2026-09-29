/*
 * FinanzView – Service Worker
 *
 * Bewusst zurückhaltend: Seiten mit Finanzdaten werden NIE
 * zwischengespeichert. Gecacht werden nur statische Dateien
 * (CSS/JS mit Hash im Namen, Icons, Logo) und eine Offline-Seite.
 */

const VERSION = 'fv-v2'; // erhöhen, wenn sich Icons o. Ä. ändern
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/finanzview.svg',
    '/icons/icon-192.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => !key.startsWith(VERSION))
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Seiten: immer aus dem Netz, ohne Netz die Offline-Seite.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Gebaute Assets tragen einen Hash im Namen -> dauerhaft cachen.
    const isStatic =
        url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.startsWith('/images/providers/')
        || /\.(?:svg|png|ico|woff2?)$/.test(url.pathname);

    if (!isStatic) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        })
    );
});
