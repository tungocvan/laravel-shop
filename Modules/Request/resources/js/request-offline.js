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
const MAX_BYTES_PER_USER = 5 * 1024 * 1024;

export class RequestOfflineStore {
    constructor({ userId, installationScope = 'default', indexedDB = globalThis.indexedDB } = {}) {
        this.owner = ownerScope(userId, installationScope);
        this.indexedDB = indexedDB;
        this.db = null;
    }

    async open() {
        if (!this.indexedDB) throw new Error('request_offline_indexeddb_unavailable');
        if (this.db) return this.db;
        this.db = await new Promise((resolve, reject) => {
            const request = this.indexedDB.open(DB_NAME, DB_VERSION);
            request.onerror = () => reject(request.error);
            request.onupgradeneeded = () => {
                const db = request.result;
                if (!db.objectStoreNames.contains(STORE)) db.createObjectStore(STORE, { keyPath: 'key' });
            };
            request.onsuccess = () => resolve(request.result);
        });
        await this.clearForeignOwners();
        await this.pruneExpired();
        return this.db;
    }

    async putSnapshot(kind, id, value, ttlMs = DEFAULT_SNAPSHOT_TTL_MS) {
        return this.put(buildRecord({ owner: this.owner, kind: `snapshot:${kind}`, id, data: sanitizeSnapshot(kind, value), ttlMs }));
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
            if (record) await this.delete(key);
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
        if ([401, 403].includes(Number(status))) await this.clear();
    }

    async put(record) {
        await this.open();
        const records = (await this.all()).filter((item) => item.owner === this.owner && item.key !== record.key);
        const bytes = new TextEncoder().encode(JSON.stringify([...records, record])).byteLength;
        if (bytes > MAX_BYTES_PER_USER) throw new Error('request_offline_quota_exceeded');
        return this.transaction('readwrite', (store) => store.put(record));
    }

    async read(key) { return this.transaction('readonly', (store) => store.get(key)); }
    async delete(key) { return this.transaction('readwrite', (store) => store.delete(key)); }
    async all() { return this.transaction('readonly', (store) => store.getAll()); }

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

function draftSchemaFields(form) {
    return [...form.querySelectorAll('[data-request-draft-field]')].map((wrapper) => ({
        key: wrapper.dataset.requestDraftField,
        type: wrapper.dataset.requestFieldType,
        classification: wrapper.dataset.requestClassification,
        offline_draft: wrapper.dataset.requestOfflineDraft === '1',
    }));
}

function readDraftValues(form) {
    const values = {};
    for (const wrapper of form.querySelectorAll('[data-request-draft-field]')) {
        const key = wrapper.dataset.requestDraftField;
        const type = wrapper.dataset.requestFieldType;
        if (!key) continue;
        if (type === 'currency') {
            values[key] = {
                amount: wrapper.querySelector('[data-request-currency-part="amount"]')?.value ?? '',
                currency: wrapper.querySelector('[data-request-currency-part="currency"]')?.value ?? '',
            };
            continue;
        }
        const control = wrapper.querySelector('input, textarea, select');
        if (!control) continue;
        if (control instanceof HTMLInputElement && control.type === 'checkbox') values[key] = control.checked;
        else if (control instanceof HTMLSelectElement && control.multiple) values[key] = [...control.selectedOptions].map((option) => option.value);
        else values[key] = control.value;
    }
    return values;
}

function applyDraftValues(form, values) {
    for (const wrapper of form.querySelectorAll('[data-request-draft-field]')) {
        const key = wrapper.dataset.requestDraftField;
        if (!Object.prototype.hasOwnProperty.call(values, key)) continue;
        const value = values[key];
        const type = wrapper.dataset.requestFieldType;
        if (type === 'currency' && value && typeof value === 'object') {
            for (const part of ['amount', 'currency']) {
                const control = wrapper.querySelector(`[data-request-currency-part="${part}"]`);
                if (control) {
                    control.value = value[part] ?? '';
                    control.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            continue;
        }
        const control = wrapper.querySelector('input, textarea, select');
        if (!control) continue;
        if (control instanceof HTMLInputElement && control.type === 'checkbox') control.checked = Boolean(value);
        else if (control instanceof HTMLSelectElement && control.multiple && Array.isArray(value)) [...control.options].forEach((option) => { option.selected = value.includes(option.value); });
        else control.value = value ?? '';
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

export function formatGroupedInteger(value) {
    const digits = String(value ?? '').replace(/\D/g, '').replace(/^0+(?=\d)/, '');

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function setupGroupedIntegerFields(scope) {
    for (const control of scope?.querySelectorAll?.('[data-request-grouped-integer]') ?? []) {
        if (!(control instanceof HTMLInputElement) || control.dataset.requestGroupedIntegerBound === '1') continue;
        control.dataset.requestGroupedIntegerBound = '1';
        const format = () => {
            const formatted = formatGroupedInteger(control.value);
            if (control.value !== formatted) control.value = formatted;
        };
        format();
        control.addEventListener('input', format);
    }
}

function setupDraftPersistence(store) {
    const form = document.querySelector('[data-request-draft-form]');
    if (!form || form.dataset.requestLocalEditable !== '1') return;
    const id = form.dataset.requestDraftForm;
    const schemaVersion = Number(form.dataset.requestSchemaVersion || 1);
    const serverLockVersion = Number(form.dataset.requestLockVersion || 0);
    const status = form.querySelector('[data-request-local-status]');
    const restore = form.querySelector('[data-request-restore-draft]');
    let timer = null;
    let localRecord = null;
    const messages = {
        empty: 'No local draft saved for this server revision.',
        available: 'A local draft is available for review.',
        saved: 'Local draft saved on this device.',
        restored: 'Local values restored for review; nothing was submitted.',
        conflict: 'Server revision changed. Local restore is blocked until you review the newer server version.',
        error: 'Local persistence is unavailable or its quota was reached.',
    };
    const setState = (state) => {
        form.dataset.requestLocalState = state;
        if (status) status.textContent = messages[state] ?? state;
        if (restore) restore.hidden = state !== 'available';
        window.dispatchEvent(new CustomEvent('request:local-draft-state', { detail: { state } }));
    };
    const persist = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            void store.putDraft(id, readDraftValues(form), draftSchemaFields(form), {
                schema_version: schemaVersion,
                server_lock_version: serverLockVersion,
            }).then(() => setState('saved')).catch(() => setState('error'));
        }, 350);
    };
    form.addEventListener('input', persist);
    form.addEventListener('change', persist);
    restore?.addEventListener('click', () => {
        if (localRecord?.data?.fields && Number(localRecord.data.server_lock_version) === serverLockVersion) {
            applyDraftValues(form, localRecord.data.fields);
            setState('restored');
        }
    });
    void store.get('draft', id).then((record) => {
        localRecord = record;
        if (!record) return setState('empty');
        if (Number(record.data?.server_lock_version) !== serverLockVersion) return setState('conflict');
        setState('available');
    });
}

function syncMutationControls(scope, online) {
    if (!scope) return;
    const controls = scope.querySelectorAll('button:not([data-request-offline-allowed]), input[type="file"]');
    controls.forEach((control) => {
        if (!online) {
            if (!control.disabled) control.dataset.requestOfflineDisabled = '1';
            control.disabled = true;
        } else if (control.dataset.requestOfflineDisabled === '1') {
            control.disabled = false;
            delete control.dataset.requestOfflineDisabled;
        }
    });
}

export function bootRequestOffline() {
    const marker = document.querySelector('[data-request-offline-root]');
    if (!marker) return null;
    const userId = marker.dataset.requestUserId;
    if (!userId) return null;
    const scope = marker.parentElement;
    const store = new RequestOfflineStore({ userId, installationScope: marker.dataset.requestInstallation || location.host });
    setupGroupedIntegerFields(scope);

    const emitConnectivity = () => {
        const online = navigator.onLine;
        marker.dataset.requestConnectivity = online ? 'online' : 'offline';
        syncMutationControls(scope, online);
        window.dispatchEvent(new CustomEvent('request:connectivity', { detail: { online } }));
    };

    window.addEventListener('online', emitConnectivity);
    window.addEventListener('offline', emitConnectivity);
    if (scope) new MutationObserver(() => {
        emitConnectivity();
        setupGroupedIntegerFields(scope);
    }).observe(scope, { childList: true, subtree: true });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form instanceof HTMLFormElement && form.action && /\/logout(?:$|\?)/.test(form.action)) void store.clear();
    }, true);

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-request-clear-local]') : null;
        if (trigger) {
            event.preventDefault();
            void store.clear().then(() => window.dispatchEvent(new CustomEvent('request:local-cleared')));
        }
    });

    window.addEventListener('request:authorization-failure', (event) => { void store.handleAuthorizationFailure(event.detail?.status); });
    window.requestOfflineStore = store;
    void store.open().then(() => {
        marker.dataset.requestOffline = 'ready';
        setupDraftPersistence(store);
    }).catch(() => { marker.dataset.requestOffline = 'disabled'; });
    emitConnectivity();
    return store;
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootRequestOffline, { once: true });
    else bootRequestOffline();
}
