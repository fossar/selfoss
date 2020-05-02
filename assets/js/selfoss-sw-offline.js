/* eslint-env worker, serviceworker, es6:false */
/* Due to a parcel-plugin-sw-cache limitations, this file is not preprocessed so we need to limit ourselves to older syntax.
 * https://github.com/mischnic/parcel-plugin-sw-cache/issues/22
 */

function precacheAndRoute(cachedEntries) {
    return cachedEntries;
}

// [] will be substituted by list of {url: String, revision: String}
const cachedEntries = precacheAndRoute([]);


self.addEventListener('install', function(event) {
    event.waitUntil(Promise.all(cachedEntries.map(entry =>
        // We will cache each file in a separate cache denoted by its revision.
        caches.open(entry.revision).then(cache => cache.add(entry.url))
    )));
});


self.addEventListener('activate', function(event) {
    const validCacheNames = cachedEntries.map(entry => entry.revision) + ['userCss', 'userJs'];

    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(cacheNames.filter(function(cacheName) {
                return !validCacheNames.includes(cacheName);
            }).map(function(cacheName) {
                return caches.delete(cacheName);
            }));
        })
    );
});


self.addEventListener('fetch', function(event) {
    event.respondWith(caches.match(event.request).then(function(resp) {
        return resp || fetch(event.request);
    }));
});


self.addEventListener('message', function(messageEvent) {
    if (messageEvent.data === 'skipWaiting') {
        return self.skipWaiting();
    }
});
