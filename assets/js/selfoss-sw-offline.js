/* ServiceWorker environment, and prepended script */
/* global offlineManifest:false, Promise */


self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(offlineManifest.version).then(function(cache) {
            return cache.addAll(offlineManifest.files);
        })
    );
});


self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(cacheNames.filter(function(cacheName) {
                return cacheName != offlineManifest.version;
            }).map(function(cacheName) {
                return caches.delete(cacheName);
            }));
        })
    );
});


self.addEventListener('fetch', function(event) {
    event.respondWith(caches.match(event.request).then(function(resp) {
        return resp || fetch(event.request).catch(function(err) {
            return err;
        });
    }));
});


self.addEventListener('message', function(messageEvent) {
    if (messageEvent.data === 'skipWaiting') {
        return self.skipWaiting();
    }
});
