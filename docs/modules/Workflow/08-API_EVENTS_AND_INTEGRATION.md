# API, Events, and Integration Contract

## 1. Integration boundary

Version 1 exposes Workflow's own internal-request API only. It does not accept references to external domain models and does not mutate domain-module state. Domain-module adapters, webhooks to arbitrary destinations, and generic polymorphic subjects are FUTURE work requiring a separate approved architecture.

## 2. API conventions

- Base path: `/api/workflow/v1`.
- Authentication: Laravel Sanctum.
- Authorization: capability plus policy/assignment/ownership.
- JSON only, UTF-8, stable snake_case fields.
- Public IDs in URLs; internal numeric IDs are not required client contracts.
- Bounded page size and cursor/page metadata.
- UTC ISO-8601 timestamps plus configured display timezone metadata when relevant.
- Money uses strings/object representation, never binary floats.
- Mutations accept `Idempotency-Key` and concurrency token.
- Rate limits by actor/action risk.

## 3. Resource endpoints

Exact route/controller grouping is finalized in CREATE_PLAN, but v1 must cover:

### Definitions

```text
GET    /definitions
POST   /definitions
GET    /definitions/{definition}
POST   /definitions/{definition}/drafts
PATCH  /definitions/{definition}/drafts/{version}
POST   /definitions/{definition}/drafts/{version}/validate
POST   /definitions/{definition}/drafts/{version}/publish
POST   /definitions/{definition}/retire
GET    /definitions/{definition}/versions/{version}
GET    /definitions/{definition}/versions/compare
```

### Requests

```text
GET    /requests
POST   /requests
GET    /requests/{request}
PATCH  /requests/{request}
POST   /requests/{request}/submit
POST   /requests/{request}/recall
POST   /requests/{request}/cancel
GET    /requests/{request}/timeline
```

### Tasks

```text
GET    /tasks
GET    /tasks/{task}
POST   /tasks/{task}/claim
POST   /tasks/{task}/unclaim
POST   /tasks/{task}/approve
POST   /tasks/{task}/reject
POST   /tasks/{task}/return
POST   /tasks/{task}/complete
POST   /tasks/{task}/reassign
```

### Collaboration and delegations

```text
GET/POST /requests/{request}/comments
GET/POST /requests/{request}/attachments
GET/POST /delegations
POST     /delegations/{delegation}/revoke
```

### Operations/reports

Read/retry endpoints are capability gated and call allowlisted operations only. Bulk exports are asynchronous and return job/resource status rather than holding large HTTP responses.

## 4. Command request shape

Example decision:

```json
{
  "request_revision": 7,
  "task_version": 3,
  "reason": "Approved within budget",
  "acting_for_user_id": null
}
```

Server derives actor, permissions, task, candidate, definition version, node, timestamps, status, queue, and event types. It does not trust them from the payload.

## 5. Response and error contract

Success envelopes should remain simple and resource-oriented. Error example:

```json
{
  "error": {
    "code": "workflow.task_not_actionable",
    "message": "The task is no longer actionable.",
    "correlation_id": "...",
    "details": {"current_status": "completed"}
  }
}
```

Use appropriate status codes: 400 malformed, 401 unauthenticated, 403 forbidden, 404 hidden/not found, 409 state/idempotency/concurrency conflict, 422 validation, 429 rate limit, 503 transient resolver/operation unavailable. Details are allowlisted and redacted.

## 6. Idempotency

- Required for submission, decision, recall, cancellation, reassignment, publish, timer fire, and operation retry.
- Scope includes actor/command/resource.
- Same key + same fingerprint returns stored authoritative outcome.
- Same key + different fingerprint returns `409 workflow.idempotency_conflict`.
- In-progress duplicate waits briefly or returns explicit processing conflict.
- Retention exceeds reasonable client/job retry windows.

## 7. Domain event catalog

Events are immutable facts emitted to the outbox, for example:

```text
workflow.definition.published.v1
workflow.definition.retired.v1
workflow.request.created.v1
workflow.request.submitted.v1
workflow.request.returned.v1
workflow.request.recalled.v1
workflow.request.cancelled.v1
workflow.instance.started.v1
workflow.instance.completed.v1
workflow.instance.failed.v1
workflow.task.created.v1
workflow.task.claimed.v1
workflow.task.decided.v1
workflow.task.reassigned.v1
workflow.task.overdue.v1
workflow.delegation.created.v1
workflow.delegation.revoked.v1
workflow.timer.fired.v1
```

Payload contains event ID/version/time, aggregate public ID/type/version, correlation/causation IDs, safe actor reference, and minimal event data. It excludes secrets, file contents, raw sensitive payload, and executable identifiers.

## 8. Event delivery

- Outbox row is committed with the state mutation.
- Dispatcher leases bounded pending rows.
- Consumer records/deduces event ID for idempotency.
- Delivery updates status separately; failure does not undo domain state.
- Retries use backoff/jitter and maximum attempts.
- Dead messages are operator-visible and replayable through audited allowlisted actions.
- Event schema changes use new versions; existing versions remain supported for approved retention window.

## 9. Notifications

Notifications consume domain events or task/timer records. Initial channels: database and email if configured. Templates are allowlisted/versioned, variables escaped, URLs generated server-side, and sensitive form values excluded unless explicitly safe. Duplicate event delivery must not duplicate a notification.

## 10. Definition package import/export

SHOULD scope only:

- JSON package, not executable archive.
- Server validates MIME/size/spec version/schema/checksum/registry capabilities.
- Dry-run produces validation and diff.
- Import creates a draft only; it never publishes automatically.
- IDs, actors, roles, template references, and resolver keys are remapped/validated explicitly.
- No client-provided class names, paths, URLs, commands, SQL, events, queues, or secrets.

