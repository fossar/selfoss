/* eslint-env worker, serviceworker */
/// <reference lib="WebWorker" />

import { manifest, version } from '@parcel/service-worker';

// Default type of `self` is `WorkerGlobalScope & typeof globalThis`
// https://github.com/microsoft/TypeScript/issues/14877
declare const self: ServiceWorkerGlobalScope;

async function install(): Promise<void> {
    const cache = await caches.open(version);

    const entriesToCache: string[] = manifest
        // We need to pass index.html through PHP to perform templating.
        .map((entry: string) => (entry === 'index.html' ? './' : entry));

    await cache.addAll(entriesToCache);
}
self.addEventListener('install', (event: ExtendableEvent) =>
    event.waitUntil(install()),
);

async function activate(): Promise<void> {
    const keys = await caches.keys();
    await Promise.all(
        keys
            .filter(
                (key: string) =>
                    !(key === version || key === 'userCss' || key === 'userJs'),
            )
            .map((key: string) => caches.delete(key)),
    );
}
self.addEventListener('activate', (event: ExtendableEvent) =>
    event.waitUntil(activate()),
);

self.addEventListener('fetch', (event: FetchEvent) => {
    if (
        event.request.method !== 'GET' ||
        event.request.headers.get('X-Requested-With') === 'XMLHttpRequest'
    ) {
        return;
    }

    event.respondWith(
        caches
            .match(event.request)
            .then(
                (cachedResponse: Response | undefined) =>
                    cachedResponse || fetch(event.request),
            )
            .catch(() => caches.match('./')),
    );
});

self.addEventListener('message', (messageEvent: ExtendableMessageEvent) => {
    if (messageEvent.data === 'skipWaiting') {
        return self.skipWaiting();
    }
});
