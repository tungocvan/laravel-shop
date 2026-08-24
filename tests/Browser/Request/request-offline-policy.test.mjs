import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildRecord,
    isExpired,
    mutationQueueSupported,
    ownerMatches,
    ownerScope,
    sanitizeDraft,
    sanitizeSnapshot,
} from '../../../Modules/Request/resources/js/request-offline-policy.js';

test('owner scope is user and installation bound', () => {
    assert.equal(ownerScope(12, 'admin.example.test'), '12:admin.example.test');
    assert.throws(() => ownerScope(0, 'x'), /request_offline_owner_invalid/);
});

test('snapshots are strict allowlists', () => {
    const result = sanitizeSnapshot('detail', {
        public_id: '01ABC',
        request_number: 'REQ-2026-1',
        title: 'Travel request',
        status: 'pending',
        updated_at: '2026-08-24T00:00:00Z',
        audit_context: { secret: true },
        attachment_path: '/private/file.pdf',
        decision_reason: 'sensitive',
        html: '<html>private</html>',
    });

    assert.deepEqual(result, {
        public_id: '01ABC',
        request_number: 'REQ-2026-1',
        title: 'Travel request',
        status: 'pending',
        updated_at: '2026-08-24T00:00:00Z',
    });
});

test('draft sanitizer excludes forbidden classifications and file or server-only fields', () => {
    const fields = {
        purpose: 'Conference',
        amount: 1500,
        secret_note: 'hidden',
        receipt: { name: 'receipt.pdf' },
        computed_total: 1500,
    };
    const schema = [
        { key: 'purpose', type: 'text', classification: 'internal' },
        { key: 'amount', type: 'number', classification: 'internal' },
        { key: 'secret_note', type: 'text', classification: 'secret' },
        { key: 'receipt', type: 'attachment', classification: 'internal' },
        { key: 'computed_total', type: 'number', server_only: true },
    ];

    assert.deepEqual(sanitizeDraft(fields, schema), { purpose: 'Conference', amount: 1500 });
});

test('records enforce ttl and owner mismatch checks', () => {
    const record = buildRecord({ owner: '9:host', kind: 'draft', id: 'abc', data: {}, ttlMs: 1000, now: 100 });
    assert.equal(isExpired(record, 1099), false);
    assert.equal(isExpired(record, 1100), true);
    assert.equal(ownerMatches(record, '9:host'), true);
    assert.equal(ownerMatches(record, '10:host'), false);
});

test('business mutation queue is deliberately unsupported', () => {
    assert.equal(mutationQueueSupported(), false);
});
