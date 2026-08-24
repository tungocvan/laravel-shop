const FORBIDDEN_CLASSIFICATIONS = new Set([
    'confidential',
    'secret',
    'attachment',
    'binary',
    'computed_server_only',
]);

const SNAPSHOT_ALLOWLIST = {
    catalog: ['public_id', 'code', 'name', 'summary', 'group', 'updated_at'],
    mine: ['public_id', 'request_number', 'title', 'status', 'updated_at'],
    inbox: ['task_public_id', 'request_public_id', 'request_number', 'title', 'status', 'updated_at'],
    detail: ['public_id', 'request_number', 'title', 'status', 'updated_at', 'submitted_at'],
};

export function ownerScope(userId, installationScope = 'default') {
    const id = Number(userId);
    if (!Number.isInteger(id) || id <= 0) {
        throw new TypeError('request_offline_owner_invalid');
    }

    const scope = String(installationScope || '').trim();
    if (!scope || scope.length > 120) {
        throw new TypeError('request_offline_scope_invalid');
    }

    return `${id}:${scope}`;
}

export function sanitizeSnapshot(kind, value) {
    const keys = SNAPSHOT_ALLOWLIST[kind];
    if (!keys || value === null || typeof value !== 'object' || Array.isArray(value)) {
        throw new TypeError('request_offline_snapshot_invalid');
    }

    return Object.fromEntries(keys.filter((key) => value[key] !== undefined).map((key) => [key, value[key]]));
}

export function sanitizeDraft(fields, schemaFields) {
    if (fields === null || typeof fields !== 'object' || Array.isArray(fields) || !Array.isArray(schemaFields)) {
        throw new TypeError('request_offline_draft_invalid');
    }

    const allowed = new Set(schemaFields.filter((field) => {
        if (!field || typeof field !== 'object' || typeof field.key !== 'string') {
            return false;
        }
        const classification = String(field.classification || '').toLowerCase();
        const type = String(field.type || '').toLowerCase();

        return !FORBIDDEN_CLASSIFICATIONS.has(classification)
            && !['attachment', 'file', 'computed'].includes(type)
            && field.server_only !== true;
    }).map((field) => field.key));

    return Object.fromEntries(Object.entries(fields).filter(([key, value]) => allowed.has(key) && isJsonSafe(value)));
}

export function isExpired(record, now = Date.now()) {
    return !record || !Number.isFinite(Number(record.expires_at)) || Number(record.expires_at) <= Number(now);
}

export function ownerMatches(record, owner) {
    return Boolean(record && record.owner === owner);
}

export function buildRecord({ owner, kind, id, data, version = 1, ttlMs, now = Date.now() }) {
    if (!owner || !kind || !id || !Number.isFinite(ttlMs) || ttlMs <= 0) {
        throw new TypeError('request_offline_record_invalid');
    }

    return {
        key: `${owner}:${kind}:${id}`,
        owner,
        kind,
        id: String(id),
        version,
        stored_at: now,
        expires_at: now + ttlMs,
        data,
    };
}

export function mutationQueueSupported() {
    return false;
}

function isJsonSafe(value) {
    if (value === null || ['string', 'number', 'boolean'].includes(typeof value)) {
        return true;
    }
    if (Array.isArray(value)) {
        return value.every(isJsonSafe);
    }
    if (typeof value === 'object') {
        return Object.values(value).every(isJsonSafe);
    }

    return false;
}
