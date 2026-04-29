const CACHE_NAME = 'ekibanja-wms-shell-v2';
const CORE_URLS = ['/', '/login', '/build/manifest.webmanifest', '/build/manifest.json'];

async function getPrecacheUrls() {
    const urls = new Set(CORE_URLS);

    try {
        const response = await fetch('/build/manifest.json', { cache: 'no-store' });

        if (!response.ok) {
            return [...urls];
        }

        const manifest = await response.json();

        Object.values(manifest).forEach((entry) => {
            if (!entry || typeof entry !== 'object') {
                return;
            }

            if (typeof entry.file === 'string') {
                urls.add(`/build/${entry.file}`);
            }

            if (Array.isArray(entry.css)) {
                entry.css.forEach((cssFile) => {
                    if (typeof cssFile === 'string') {
                        urls.add(`/build/${cssFile}`);
                    }
                });
            }

            if (Array.isArray(entry.assets)) {
                entry.assets.forEach((assetFile) => {
                    if (typeof assetFile === 'string') {
                        urls.add(`/build/${assetFile}`);
                    }
                });
            }
        });
    } catch {
        // Ignore precache manifest fetch errors; core URLs are still cached.
    }

    return [...urls];
}

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(CACHE_NAME);
        const urls = await getPrecacheUrls();
        await cache.addAll(urls);
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();

        await Promise.all(
            keys
                .filter((key) => key !== CACHE_NAME)
                .map((key) => caches.delete(key)),
        );

        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const requestUrl = new URL(request.url);

    const isApiOrAuthEndpoint = requestUrl.origin === self.location.origin
        && (requestUrl.pathname.startsWith('/api/') || requestUrl.pathname.startsWith('/sanctum/'));

    if (isApiOrAuthEndpoint) {
        event.respondWith((async () => {
            try {
                return await fetch(request);
            } catch {
                return Response.error();
            }
        })());

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith((async () => {
            const cache = await caches.open(CACHE_NAME);

            try {
                const networkResponse = await fetch(request);
                cache.put(request, networkResponse.clone());
                return networkResponse;
            } catch {
                return (await cache.match(request))
                    || (await cache.match('/'))
                    || Response.error();
            }
        })());

        return;
    }

    if (requestUrl.origin !== self.location.origin) {
        return;
    }

    event.respondWith((async () => {
        const cache = await caches.open(CACHE_NAME);
        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        try {
            const networkResponse = await fetch(request);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        } catch {
            return Response.error();
        }
    })());
});
