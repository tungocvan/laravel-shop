# API, Events, and Notifications

## 1. Interface strategy

Livewire/web is the primary repository-native experience. A versioned authenticated API supports first-party responsive/PWA behavior and future approved clients without exposing a generic workflow engine. Both interfaces call the same application services, policies, validators, transactions, and audit path.

## 2. Route boundaries

Suggested route groups (exact names in `CREATE_PLAN.md`):

- Web: `/request/...` and admin workspace under the repository's established admin prefix/convention.
- API: `/api/request/v1/...`.
- Private file/export downloads through signed, short-lived or session/Sanctum-authorized controller routes with record reauthorization.

All routes fail closed when module state is disabled. Route model binding uses public ULIDs and scoped ownership/policy; display numbers alone are not authorization.

## 3. API resources

Minimum resource families:

- catalog groups/types and published form presentation;
- drafts/internal requests and payload draft revisions;
- My Requests and request detail/timeline;
- active inbox tasks and decision context;
- comments and attachment upload/finalization/download metadata;
- admin type drafts, validation, diff, publish/retire;
- reports/export jobs and safe operations, permission-gated.

Responses use explicit resource transformers. Never serialize Eloquent models or entire JSON payloads by default.

## 4. Command endpoints

Side-effecting commands include save draft, submit/resubmit, approve/reject/return, cancel, reassign, comment, attachment finalize, publish/retire, definition import draft creation, export request, and operation retry.

Every business command requires:

- authenticated actor;
- named permission plus record policy;
- validated DTO/schema;
- `Idempotency-Key` for non-trivial mutation;
- expected revision via `If-Match`, explicit version field, or documented equivalent;
- correlation ID generated/accepted under safe format;
- transaction, audit, and outbox behavior.

GET/read endpoints never trigger business mutation, notification, claim, or local-draft merge.

## 5. Error contract

Stable problem response fields:

- `type` or stable error code;
- localized safe `message`;
- HTTP status;
- correlation ID;
- validation errors keyed by stable field path;
- safe current revision/status for conflicts where authorized;
- retryability and retry-after only when true.

No raw exception, SQL, filesystem path, stack trace, class name, secret, private payload, unauthorized record existence, or email is returned.

Recommended status behavior:

- 400 malformed command/schema;
- 401 unauthenticated;
- 403 authenticated but not authorized;
- 404 hidden/non-visible resource;
- 409 stale state/idempotency fingerprint conflict/terminal race;
- 422 form/business validation;
- 423 or 409 for temporary operational lock only if consistently adopted;
- 429 rate limit;
- 503 module disabled/dependency unavailable with safe retry semantics.

## 6. Pagination/filter/sort

- Collections use cursor pagination where stable and repository-compatible, otherwise bounded page pagination.
- Allowed filters/sorts are enumerated server-side.
- Search strings and date ranges are bounded; no arbitrary column/order expression.
- Page size maximum is 100 for interactive lists unless a stricter endpoint limit is defined.
- Response includes applied filters and stable next cursor/page metadata, not internal SQL details.

## 7. Event catalog

Request-owned domain/integration event keys, versioned from first release:

```text
request.type.published.v1
request.type.retired.v1
request.instance.submitted.v1
request.run.stage_activated.v1
request.task.assigned.v1
request.task.reassigned.v1
request.task.decided.v1
request.instance.approved.v1
request.instance.rejected.v1
request.instance.returned.v1
request.instance.resubmitted.v1
request.instance.cancelled.v1
request.comment.created.v1
request.attachment.created.v1
request.export.ready.v1
```

Event payloads contain public IDs, event time, schema version, correlation ID, safe status/type/stage identifiers, and only minimal actor references. They do not contain raw form payload, reason/comment text, file paths/URLs, authorization claims, or secrets by default.

Events are facts after commit. Consumers cannot use event delivery success/failure to alter the committed Request decision.

## 8. Transactional outbox

- Business transaction appends outbox record with immutable event identity/payload.
- Dispatcher reads due undispatched rows in bounded batches with safe locking.
- Delivery is at least once; consumers and notification handlers deduplicate by event public ID.
- Retry uses bounded exponential backoff/jitter and terminal failure threshold.
- Operators see safe error codes and can retry only an eligible failed row.
- Retrying delivery does not execute the original business command.
- Retention/archival preserves audit/reference needs without unbounded hot-table growth.

## 9. Notifications

Initial supported channels:

- Database/in-app notification through repository-approved infrastructure.
- Email through configured Laravel mail infrastructure.

Realtime/web push is optional and disabled until a canonical Shell-owned broadcasting/push contract is available and separately reviewed. Do not introduce a module-specific Socket.IO server, second websocket stack, or service worker.

Recipient derivation is from committed request/task state. Notifications are queued after commit and idempotent by logical event + recipient + channel + template version.

## 10. Notification content policy

Templates include:

- concise event and request type/number;
- safe actor/requester name only when recipient is authorized;
- current status/stage and required action;
- authenticated deep link;
- expiry/stale warning for actionable emails.

They exclude confidential payload fields, raw return/reject reasons by default, attachment names/links unless explicitly safe, internal DB IDs, and credentials. Opening a deep link always reauthorizes and refreshes state.

## 11. Webhooks and external integration

No user-configurable outbound webhook or domain adapter is included in v1. The outbox is an internal reliability boundary, not permission to transmit data externally. Any future webhook requires destination allowlisting, signing, secret storage, SSRF protection, payload classification, retry/dead-letter operations, and a separate approval.

## 12. Observability

Structured logs/metrics include safe identifiers and:

- command/event key, outcome, duration;
- stage resolver key and candidate count (not candidate identities in general logs);
- conflict/idempotent replay counts;
- outbox age/attempt/failure counts;
- notification/export queue latency/failure;
- list/report duration and row bounds.

Correlation ID links request logs, audit, outbox, job, and response. Do not use form values, comments, reasons, filenames, email addresses, or auth tokens as metric labels.
