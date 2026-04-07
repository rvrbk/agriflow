import axios from 'axios';

const STORAGE_KEY = 'agriflow.offlineRequestQueue';
const RETRY_INTERVAL_MS = 15000;
const MUTATING_METHODS = new Set(['post', 'put', 'patch', 'delete']);

let syncInProgress = false;
let retryIntervalId = null;

const rawHttpClient = axios.create({
    baseURL: window.location.origin,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

function readQueue() {
    const raw = window.localStorage.getItem(STORAGE_KEY);

    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function writeQueue(queue) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
}

function createQueueId() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `queued-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function normalizeMethod(method) {
    return String(method || '').toLowerCase();
}

function isMutatingMethod(method) {
    return MUTATING_METHODS.has(normalizeMethod(method));
}

export function queueRequestFromConfig(config) {
    if (!config?.url || !isMutatingMethod(config.method)) {
        return null;
    }

    const queue = readQueue();
    const entry = {
        id: createQueueId(),
        method: normalizeMethod(config.method),
        url: config.url,
        data: config.data,
        params: config.params,
        queuedAt: new Date().toISOString(),
    };

    queue.push(entry);
    writeQueue(queue);

    window.dispatchEvent(new CustomEvent('offline-queue:queued', { detail: entry }));

    return entry;
}

function shouldRetryLater(error) {
    if (!error?.response) {
        return true;
    }

    const status = error.response.status;
    return status >= 500 || status === 429;
}

export async function processOfflineQueue() {
    if (syncInProgress || !window.navigator.onLine) {
        return;
    }

    syncInProgress = true;

    try {
        const queue = readQueue();

        while (queue.length > 0) {
            const next = queue[0];

            try {
                await rawHttpClient({
                    method: next.method,
                    url: next.url,
                    data: next.data,
                    params: next.params,
                });

                queue.shift();
                writeQueue(queue);
                window.dispatchEvent(new CustomEvent('offline-queue:synced', { detail: next }));
            } catch (error) {
                if (shouldRetryLater(error)) {
                    break;
                }

                queue.shift();
                writeQueue(queue);
                window.dispatchEvent(new CustomEvent('offline-queue:dropped', { detail: next }));
            }
        }
    } finally {
        syncInProgress = false;
    }
}

export function initializeOfflineQueue() {
    if (retryIntervalId) {
        return;
    }

    window.addEventListener('online', () => {
        void processOfflineQueue();
    });

    retryIntervalId = window.setInterval(() => {
        void processOfflineQueue();
    }, RETRY_INTERVAL_MS);

    void processOfflineQueue();
}
