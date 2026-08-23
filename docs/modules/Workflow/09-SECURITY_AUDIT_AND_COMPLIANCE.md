# Security, Audit, and Compliance

## 1. Threat model

Protected assets include request payloads, attachments, approval authority, workflow definitions, audit evidence, credentials/tokens, operational controls, and availability of the engine.

Threats include unauthorized record access, privilege escalation through actor rules, self-approval, forged/replayed decisions, duplicate jobs, stale-state overwrite, malicious definition/package/condition data, file upload/path traversal, stored XSS, enumeration, audit tampering, sensitive logging, queue poisoning, denial of service through graph/payload fan-out, and unsafe operational retries.

## 2. Trust boundaries

- Browser/API data is untrusted.
- Workflow definition JSON is untrusted until validated/published.
- Form payload, filenames, MIME, IDs, concurrency tokens, and idempotency keys are untrusted.
- Queued payloads can be stale or duplicated.
- Shell identity/role data is trusted only through approved contracts and still checked for active state/guard.
- No domain-module data is a trusted dependency because Workflow cannot depend on domain modules.

## 3. Mandatory controls

- Capability permission and record policy for every endpoint/action/download.
- Server-owned registries for node, resolver, condition operator, notification channel/template, event topic, and operation.
- Transactions, deterministic locks, optimistic versions, unique constraints, and idempotency.
- Private storage with server-generated keys and controlled responses.
- MIME/extension/size/count/checksum validation and scan-status support.
- CSRF on web and Sanctum/rate limits on API.
- Output escaping and sanitized rendering; no untrusted raw HTML.
- Bounded search/filter/sort fields and parameterized queries.
- Redacted safe errors with correlation IDs.
- Secrets never stored in definitions, payload snapshots, audit metadata, logs, queue arguments, URLs, or source.

## 4. Definition safety

Publication fails if configuration contains:

- PHP/JavaScript/Blade/SQL/shell/code fragments intended for execution
- model/service/provider/event/job class names supplied by users
- arbitrary URL/webhook, storage path, table, column, queue, command or environment name
- unsupported resolver/node/operator/template key
- unbounded graph, recursion, fan-out, timer, payload, or notification behavior
- ambiguous/missing transitions or security policy

Safe display text remains length-bounded and escaped.

## 5. Authorization matrix principles

- Definition designer cannot publish without separate publish permission.
- Publisher sees executable diff and validation result.
- Requester sees own request unless policy explicitly broadens access.
- Candidate/assignee access is limited to relevant task/request context.
- Auditor is read-only and may receive redacted payload.
- Operator can view/retry operational failures but does not automatically receive full business payload.
- Reassignment/delegation cannot elevate beyond both permission and eligibility.
- Attachment permission is evaluated on every access.

## 6. Audit event requirements

Audit at minimum:

- definition create/edit validation/publish/retire/export/import
- request create/update/submit/return/resubmit/recall/cancel/complete
- task create/claim/unclaim/decision/reassign/skip/expire/escalate
- delegation create/revoke/use
- comment edit/delete and attachment upload/download/delete
- permission-sensitive list/export/report access when required by policy
- timer/outbox failure/retry/dead-letter
- operator resume/retry and settings change

Each event stores stable action, actor/effective actor, aggregate, request, before/after state where safe, timestamp, correlation/causation IDs, source channel, and redacted metadata. High-volume read events may use a separate access log policy but attachment/audit/export access remains captured.

## 7. Tamper evidence

Version 1 does not provide a digital signature. It may implement hash chaining/checksums for audit tamper detection:

```text
event_hash = SHA-256(canonical_event_without_hash + previous_hash)
```

This is integrity evidence, not legal non-repudiation. Hash verification command is read-only, bounded, and reports gaps without rewriting records. Keyed signing/PKI requires a future approved design.

## 8. Privacy and retention

- Classify form fields as ordinary or sensitive.
- Sensitive values are redacted from audit diffs, logs, notifications, reports, and exports by default.
- Define retention for requests, files, idempotency responses, outbox payloads, operational errors, and exports.
- Legal hold/record deletion is FUTURE unless required before implementation.
- Soft delete does not make audit disappear.
- User deactivation preserves historical actor IDs/labels according to privacy policy.

## 9. Secure files

- Private disk by default.
- Opaque path and public ID.
- Authorized download controller; no direct public symlink.
- Content-Disposition sanitized.
- SVG/HTML and executable formats denied by default.
- Upload staging and cleanup jobs are idempotent.
- Attachment checksum and immutable metadata retained for evidence.
- Preview generation, if added, is sandboxed/allowlisted and asynchronous.

## 10. Operational security

- No arbitrary Artisan/shell runner.
- Retry/resume operations are registry allowlisted with fixed typed arguments.
- Destructive cleanup requires permission, confirmation, dry-run where practical, audit, and retention checks.
- Queue/outbox errors store exception class/code and redacted message, not secrets/stack traces in UI.
- Correlation IDs allow investigation without exposing payloads.

## 11. Required security tests

- Guard separation and unauthenticated/forbidden routes.
- Own/participant/all record visibility.
- Unauthorized task decision, stale assignment, expired delegation, self-approval denial.
- Replay/idempotency and concurrency races.
- Malicious DSL/schema/package fields and registry bypass.
- Mass-assignment of status/actor/path/permission/internal fields.
- XSS in labels/comments/filenames/templates.
- Attachment enumeration/path traversal/MIME spoof/oversize.
- Audit update/delete paths absent and hash verification detects changes in fixtures.
- Operational retry allowlist and redaction.
- Domain dependency architecture rule.

