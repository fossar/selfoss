/* eslint-env worker, serviceworker */

import { manifest, version } from '@parcel/service-worker';

async function install() {
    const cache = await caches.open(version);

    const entriesToCache = manifest
        // We need to pass index.html through PHP to perform templating.
        .map((entry) => (entry === 'index.html' ? './' : entry));

    await cache.addAll(entriesToCache);
}
self.addEventListener('install', (event) => event.waitUntil(install()));

async function activate() {
    const keys = await caches.keys();
    await Promise.all(
        keys
            .filter(
                (key) =>
                    !(key === version || key === 'userCss' || key === 'userJs'),
            )
            .map((key) => caches.delete(key)),
    );
}
self.addEventListener('activate', (event) => event.waitUntil(activate()));

self.addEventListener('fetch', (event) => {
    if (
        event.request.method !== 'GET' ||
        event.request.headers.get('X-Requested-With') === 'XMLHttpRequest'
    ) {
        return;
    }

    event.respondWith(
        caches
            .match(event.request)
            .then((cachedResponse) => cachedResponse || fetch(event.request))
            .catch(() => caches.match('./')),
    );
});

self.addEventListener('message', (messageEvent) => {
    if (messageEvent.data === 'skipWaiting') {
        return self.skipWaiting();
    }
});
