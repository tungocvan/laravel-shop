# Security, Audit, and Files

## 1. Threat model

Primary threats:

- IDOR across requests, tasks, attachments, exports, versions, and operations.
- Permission-only authorization that ignores ownership/candidacy/state.
- Mass assignment or client-controlled status/actor/resolver/schema fields.
- Duplicate/concurrent submit or decision side effects.
- Resolver injection, excessive role expansion, and self-approval.
- Stored XSS in form labels, values, comments, filenames, and imported packages.
- Malicious upload, MIME confusion, archive traversal, oversized/decompression-bomb input.
- Spreadsheet formula injection and sensitive export leakage.
- Offline/browser cache leakage on shared devices or account switch.
- Sensitive data in logs, notifications, events, URLs, telemetry, and audit diffs.
- Operational retry that replays business transitions.

## 2. Security controls

- Deny-by-default route middleware, policy, visibility query scope, resource transformer, and file/export authorization.
- CSRF protection/session hardening for web and Sanctum for API.
- DTO/form-request allowlists; guarded models; no direct `fill($request->all())`.
- Server-owned status, actor, requester, type version, stage, resolver implementation, timestamps, checksums, and audit fields.
- Idempotency, expected version, transactions, locks, and uniqueness constraints.
- Rate limits by actor/IP/action where appropriate, with lower limits for upload/export/import/operations.
- Output escaping and approved sanitized rendering only.
- Security headers and same-origin policies follow the application shell.

## 3. Authorization layers

Each access path checks:

1. Module/dependency enabled state.
2. Authenticated guard.
3. Named capability.
4. Record visibility scope.
5. Action predicate: ownership/candidacy/current state/version/classification.
6. Field/file/export subset authorization.

Queue jobs, notifications, exports, and download controllers repeat necessary checks or operate on a previously authorized immutable snapshot under a documented service identity. A queued job must not assume the initiating user still has access when delivery/download occurs.

## 4. Audit event requirements

Audit at minimum:

- group/type/version create/update/validate/publish/retire/import/export;
- request draft creation, submit/resubmit, approve/reject/return, cancellation;
- stage activation/failure/retry and task create/skip/cancel/reassign/decision;
- comment create/redact;
- attachment upload/finalize/download/quarantine/remove;
- report/export request/generation/download/expiry;
- operation view/retry and module configuration changes.

Each event stores stable event key/version, actor/effective actor, target public ID/type, UTC time, correlation ID, safe source metadata, reason when required, and structured safe delta/context.

Audit is append-only at application level. General administrators cannot edit it. Database/backup access is an operational concern documented outside the module.

## 5. Audit redaction policy

- Do not copy full request payload before/after into audit.
- Store changed field keys and redacted/type-aware summaries according to classification.
- Passwords, tokens, auth headers, secrets, file paths, signed URLs, raw idempotency keys, and confidential values are never recorded.
- Reason/comment text may be evidence in its own owned record; audit references its public ID and safe excerpt only if policy allows.
- IP/user agent retention follows application privacy policy and is not exported by default.

## 6. Private upload flow

1. Authenticate/authorize request and field/general attachment purpose.
2. Validate declared metadata and enforce per-file/per-request count/size limits.
3. Stream to a private temporary path with generated opaque filename.
4. Detect/validate MIME, extension allowlist, checksum, and archive/document policy.
5. Scan/quarantine through repository capability if available; until clean, file is not downloadable/attachable.
6. Finalize metadata and ownership in a transaction-safe flow.
7. Audit safe metadata; never log file content/path.
8. Clean abandoned temporary files with a bounded, race-safe process.

Exact allowed MIME/size values are configuration reviewed in `CREATE_PLAN.md`. Executables, scripts, macro-enabled content, HTML/SVG capable of active content, and nested archives default to denied.

## 7. Download flow

- Address attachment/export by public ULID through authenticated route.
- Reauthorize current viewer and request/field classification.
- Verify metadata/storage object integrity and safe disposition filename.
- Set content type, `nosniff`, private/no-store or approved short cache policy, and download disposition for risky display types.
- Stream without exposing storage path or permanent object URL.
- Audit/download metrics according to privacy policy.
- Signed URLs, if used, are short-lived, audience-bound where possible, and not embedded in notifications/logs.

## 8. Definition package security

- Accept only documented format/version and bounded size/file count.
- Parse in private temporary storage; reject absolute paths, `..`, symlinks, devices, nested archives, executable content, and excessive expansion.
- Never deserialize PHP/object data or execute formulas/templates/scripts.
- Validate checksums/schema before display/mapping.
- Escape all labels/help/options in preview.
- Unresolved User/Role references require explicit mapping and are not guessed by name/email.
- Import creates only a draft; publication is a separate audited permission/action.

## 9. Export security

- Query uses the same record/field policy as interactive access, with explicit report/export scope.
- Private queued artifact with expiry; reauthorize download.
- CSV/XLSX cells beginning with formula markers are neutralized according to shared exporter contract.
- PDF/print escapes content and prevents remote-resource fetches.
- Export omits confidential fields/reasons/comments/files unless explicitly selected and permitted.
- Export filters, field set, row count, classification, requester, completion, download, and expiry are audited safely.
- No public filesystem path, predictable filename, or emailed binary by default.

## 10. Browser/PWA privacy

- Server marks cacheable representations explicitly; default is no offline persistence.
- Cache entries are per-user, namespaced, expiring, sanitized, and cleared on logout/account switch/revocation/module disable/version mismatch.
- Confidential fields and attachment content are not stored offline.
- Local drafts contain only allowed fields and display a shared-device warning.
- No background sync of business commands.
- Service worker/app shell is Shell-owned and receives no broad Request fetch rule.

## 11. Retention and deletion

V1 must expose configuration/runbook hooks for retention even if automated legal deletion is future scope:

- Type definitions and request evidence referenced by decisions are retained/archived.
- Temporary uploads and expired exports have short bounded retention.
- Idempotency/outbox/delivery metadata have operational retention sufficient for retries/audit.
- User deactivation preserves historical ID/snapshot but removes future resolver eligibility.
- A later privacy deletion policy must redact/minimize while preserving required evidence and checksum semantics; hard deletion is not improvised in admin UI.

## 12. Security release evidence

- Route/policy matrix tests including hidden 404 vs 403 behavior.
- IDOR tests for every public-ID resource and nested relation.
- Concurrency/idempotency tests.
- Upload/package corpus tests and storage permission inspection.
- XSS tests for schema labels/options, payload display, comments, filenames, and imports.
- CSV formula/PDF remote resource tests.
- Log/event/notification/audit snapshot review for sensitive leakage.
- Cache inspection after offline use, logout, account switch, expiry, and revocation.
- Dependency/import scan proving no domain-module access.
