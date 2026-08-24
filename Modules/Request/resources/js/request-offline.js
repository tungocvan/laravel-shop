import {
    buildRecord,
    isExpired,
    ownerMatches,
    ownerScope,
    sanitizeDraft,
    sanitizeSnapshot,
} from './request-offline-policy.js';

const DB_NAME = 'request-v1';
const DB_VERSION = 1;
const STORE = 'records';
const DEFAULT_SNAPSHOT_TTL_MS = 24 * 60 * 60 * 1000;
const DEFAULT_DRAFT_TTL_MS = 168 * 60 * 60 * 1000;

export class RequestOfflineStore {
    constructor({ userId, installationScope = 'default', indexedDB = globalThis.indexedDB } = {}) {
        this.owner = ownerScope(userId, installationScope);
        this.indexedDB = indexedDB;
        this.db = null;
    }

    async open() {
        if (!this.indexedDB) {
            throw new Error('request_offline_indexeddb_unavailable');
        }
        if (this.db) {
            return this.db;
        }

        this.db = await new Promise((resolve, reject) => {
            const request = this.indexedDB.open(DB_NAME, DB_VERSION);
            request.onerror = () => reject(request.error);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'key' });
                }
            };
            request.onsuccess = () => resolve(request.result);
        });

        await this.clearForeignOwners();
        await this.pruneExpired();

        return this.db;
    }

    async putSnapshot(kind, id, value, ttlMs = DEFAULT_SNAPSHOT_TTL_MS) {
        const data = sanitizeSnapshot(kind, value);
        return this.put(buildRecord({ owner: this.owner, kind: `snapshot:${kind}`, id, data, ttlMs }));
    }

    async putDraft(id, fields, schemaFields, metadata = {}, ttlMs = DEFAULT_DRAFT_TTL_MS) {
        const data = {
            fields: sanitizeDraft(fields, schemaFields),
            schema_version: metadata.schema_version ?? null,
            server_lock_version: metadata.server_lock_version ?? null,
            checksum: metadata.checksum ?? null,
        };

        return this.put(buildRecord({ owner: this.owner, kind: 'draft', id, data, ttlMs }));
    }

    async get(kind, id) {
        await this.open();
        const key = `${this.owner}:${kind}:${id}`;
        const record = await this.read(key);
        if (!record || !ownerMatches(record, this.owner) || isExpired(record)) {
            if (record) {
                await this.delete(key);
            }
            return null;
        }

        return record;
    }

    async clear() {
        await this.open();
        const records = await this.all();
        await Promise.all(records.filter((record) => record.owner === this.owner).map((record) => this.delete(record.key)));
    }

    async handleAuthorizationFailure(status) {
        if ([401, 403].includes(Number(status))) {
            await this.clear();
        }
    }

    async put(record) {
        await this.open();
        return this.transaction('readwrite', (store) => store.put(record));
    }

    async read(key) {
        return this.transaction('readonly', (store) => store.get(key));
    }

    async delete(key) {
        return this.transaction('readwrite', (store) => store.delete(key));
    }

    async all() {
        return this.transaction('readonly', (store) => store.getAll());
    }

    async clearForeignOwners() {
        const records = await this.all();
        await Promise.all(records.filter((record) => record.owner !== this.owner).map((record) => this.delete(record.key)));
    }

    async pruneExpired() {
        const records = await this.all();
        await Promise.all(records.filter((record) => isExpired(record)).map((record) => this.delete(record.key)));
    }

    transaction(mode, operation) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(STORE, mode);
            const request = operation(transaction.objectStore(STORE));
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
        });
    }
}

export function bootRequestOffline() {
    const marker = document.querySelector('[data-request-offline-root]');
    if (!marker) {
        return null;
    }

    const userId = marker.dataset.requestUserId;
    if (!userId) {
        return null;
    }

    const store = new RequestOfflineStore({
        userId,
        installationScope: marker.dataset.requestInstallation || location.host,
    });

    const emitConnectivity = () => {
        marker.dataset.requestConnectivity = navigator.onLine ? 'online' : 'offline';
        window.dispatchEvent(new CustomEvent('request:connectivity', {
            detail: { online: navigator.onLine },
        }));
    };

    window.addEventListener('online', emitConnectivity);
    window.addEventListener('offline', emitConnectivity);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.action && /\/logout(?:$|\?)/.test(form.action)) {
            void store.clear();
        }
    }, true);

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-request-clear-local]') : null;
        if (trigger) {
            event.preventDefault();
            void store.clear().then(() => window.dispatchEvent(new CustomEvent('request:local-cleared')));
        }
    });

    window.addEventListener('request:authorization-failure', (event) => {
        void store.handleAuthorizationFailure(event.detail?.status);
    });

    window.requestOfflineStore = store;
    void store.open().then(() => {
        marker.dataset.requestOffline = 'ready';
    }).catch(() => {
        marker.dataset.requestOffline = 'disabled';
    });
    emitConnectivity();

    return store;
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootRequestOffline, { once: true });
    } else {
        bootRequestOffline();
    }
}
