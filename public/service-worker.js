const CACHE_NAME = 'wps-payroll-cache-v2';
const OFFLINE_URL = '/offline';
const PRECACHE_URLS = ['/', OFFLINE_URL, '/manifest.webmanifest'];
const BACKGROUND_SYNC_TAG = 'wps-offline-queue-sync';
const QUEUE_DB_NAME = 'wps-offline-queue';
const QUEUE_STORE_NAME = 'requests';
const HORIZON_METRICS_ENDPOINT = '/horizon/api/metrics/jobs';
const METRICS_CACHE_TTL = 60 * 1000;

let cachedMetrics = null;
let cachedMetricsFetchedAt = 0;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) =>
            Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== CACHE_NAME)
                    .map((cacheName) => caches.delete(cacheName)),
            ),
        ).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method === 'GET') {
        event.respondWith(handleNetworkFirst(request));

        return;
    }

    if (shouldQueueRequest(request)) {
        event.respondWith(handleQueueableRequest(request));
    }
});

self.addEventListener('sync', (event) => {
    if (event.tag === BACKGROUND_SYNC_TAG) {
        event.waitUntil(processQueue());
    }
});

self.addEventListener('message', (event) => {
    const payload = event.data || {};

    if (payload.type === 'FLUSH_OFFLINE_QUEUE') {
        event.waitUntil(processQueue());

        return;
    }

    if (payload.type === 'REQUEST_QUEUE_METRICS') {
        event.waitUntil(sendQueueMetrics(event.source));
    }
});

async function handleNetworkFirst(request) {
    try {
        const response = await fetch(request);

        if (request.url.startsWith(self.location.origin)) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        if (request.mode === 'navigate') {
            const offlineFallback = await caches.match(OFFLINE_URL);

            if (offlineFallback) {
                return offlineFallback;
            }
        }

        throw error;
    }
}

function shouldQueueRequest(request) {
    const method = request.method.toUpperCase();

    if (!['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        return false;
    }

    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;

    if (!isSameOrigin) {
        return false;
    }

    const contentType = request.headers.get('content-type') || '';
    const acceptsJson = (request.headers.get('accept') || '').includes('application/json');

    return contentType.includes('application/json') || acceptsJson;
}

async function handleQueueableRequest(request) {
    try {
        return await fetch(request.clone());
    } catch (error) {
        const cloned = request.clone();
        const body = await cloned.text();

        await enqueueRequest({
            url: request.url,
            method: request.method,
            headers: serializeHeaders(request.headers),
            body,
            credentials: request.credentials,
            timestamp: Date.now(),
        });

        await registerBackgroundSync();
        await broadcastMessage({ type: 'REQUEST_QUEUED', url: request.url });
        await maybeNotify('Payroll action saved offline', 'We will retry when connectivity is restored.');

        return new Response(JSON.stringify({ queued: true }), {
            status: 202,
            headers: {
                'Content-Type': 'application/json',
            },
        });
    }
}

async function registerBackgroundSync() {
    if ('sync' in self.registration) {
        try {
            await self.registration.sync.register(BACKGROUND_SYNC_TAG);

            return;
        } catch (error) {
            // Fall through to immediate processing.
        }
    }

    await processQueue();
}

async function processQueue() {
    const queued = await getQueuedRequests();

    if (queued.length === 0) {
        return;
    }

    let success = 0;
    let failures = 0;

    for (const entry of queued) {
        try {
            const response = await fetch(entry.url, {
                method: entry.method,
                headers: entry.headers,
                body: entry.body,
                credentials: entry.credentials || 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            await removeQueuedRequest(entry.id);
            success += 1;
        } catch (error) {
            failures += 1;
        }
    }

    if (success > 0) {
        await maybeNotify('Payroll queue synced', `${success} pending action${success === 1 ? '' : 's'} processed successfully.`);
    }

    await broadcastMessage({
        type: 'QUEUE_SYNC_RESULT',
        processed: success,
        failed: failures,
    });

    if (success > 0) {
        await refreshQueueMetrics();
    }
}

async function sendQueueMetrics(client) {
    const message = await retrieveQueueMetrics();
    await broadcastMessage(message, client);
}

async function refreshQueueMetrics() {
    const message = await retrieveQueueMetrics();
    await broadcastMessage(message);
}

async function retrieveQueueMetrics() {
    const now = Date.now();

    if (cachedMetrics && now - cachedMetricsFetchedAt < METRICS_CACHE_TTL) {
        return {
            type: 'QUEUE_METRICS_RESULT',
            ok: true,
            data: cachedMetrics,
            cached: true,
            receivedAt: now,
        };
    }

    try {
        const response = await fetch(HORIZON_METRICS_ENDPOINT, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Metrics endpoint returned ${response.status}`);
        }

        const data = await response.json();
        cachedMetrics = data;
        cachedMetricsFetchedAt = now;

        return {
            type: 'QUEUE_METRICS_RESULT',
            ok: true,
            data,
            cached: false,
            receivedAt: now,
        };
    } catch (error) {
        return {
            type: 'QUEUE_METRICS_RESULT',
            ok: false,
            error: error.message,
            receivedAt: now,
        };
    }
}

function serializeHeaders(headers) {
    const serialized = {};

    headers.forEach((value, key) => {
        serialized[key] = value;
    });

    return serialized;
}

async function enqueueRequest(record) {
    const db = await openQueueDb();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(QUEUE_STORE_NAME, 'readwrite');
        const store = transaction.objectStore(QUEUE_STORE_NAME);

        store.add(record);

        transaction.oncomplete = () => {
            db.close();
            resolve();
        };
        transaction.onerror = () => {
            db.close();
            reject(transaction.error);
        };
    });
}

async function getQueuedRequests() {
    const db = await openQueueDb();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(QUEUE_STORE_NAME, 'readonly');
        const store = transaction.objectStore(QUEUE_STORE_NAME);
        const request = store.getAll();

        request.onsuccess = () => {
            db.close();
            resolve(request.result || []);
        };
        request.onerror = () => {
            db.close();
            reject(request.error);
        };
    });
}

async function removeQueuedRequest(id) {
    const db = await openQueueDb();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(QUEUE_STORE_NAME, 'readwrite');
        const store = transaction.objectStore(QUEUE_STORE_NAME);

        store.delete(id);

        transaction.oncomplete = () => {
            db.close();
            resolve();
        };
        transaction.onerror = () => {
            db.close();
            reject(transaction.error);
        };
    });
}

function openQueueDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(QUEUE_DB_NAME, 1);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(QUEUE_STORE_NAME)) {
                db.createObjectStore(QUEUE_STORE_NAME, { keyPath: 'id', autoIncrement: true });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function broadcastMessage(message, target) {
    if (target && typeof target.postMessage === 'function') {
        target.postMessage(message);

        return;
    }

    const clientList = await self.clients.matchAll({
        includeUncontrolled: true,
        type: 'window',
    });

    clientList.forEach((client) => {
        client.postMessage(message);
    });
}

async function maybeNotify(title, body) {
    if (typeof self.registration.showNotification !== 'function') {
        return;
    }

    if (typeof Notification !== 'undefined' && Notification.permission !== 'granted') {
        return;
    }

    try {
        await self.registration.showNotification(title, {
            body,
            icon: '/favicon.ico',
            tag: 'wps-payroll-queue',
        });
    } catch (error) {
        // Notifications can fail silently; ignore.
    }
}
