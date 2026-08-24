# Domain Model and Invariants

## 1. Aggregate boundaries

### 1.1 Request type aggregate

Root: `RequestType`

Owns stable code, group, availability, current published version pointer, and draft-version lifecycle. A `RequestTypeVersion` owns its audience, form schema, policies, and ordered approval-stage definitions. Publication is the aggregate's critical atomic transition.

### 1.2 Internal request aggregate

Root: `InternalRequest`

Owns public ID/number, requester, pinned type version, status, optimistic version, current payload revision, current run, lifecycle timestamps, and archive metadata. Payload revisions and approval runs are historical children. A run owns its runtime stages/tasks/candidates/decisions.

### 1.3 Collaboration records

Comments and attachments belong to one internal request and are policy-protected by its visibility. They do not control request state. Audit and outbox records are append-only evidence/delivery artifacts written in the same transaction as the business action.

## 2. Value objects and enums

Required enums/value objects include:

- `RequestTypeStatus`: `draft`, `published`, `retired` at the stable type level as applicable.
- `RequestTypeVersionStatus`: `draft`, `published`, `superseded`, `retired`.
- `RequestStatus`: `draft`, `pending`, `approved`, `rejected`, `returned`, `cancelled`.
- `RunStatus`: `active`, `approved`, `rejected`, `returned`, `cancelled`, `failed_activation`.
- `TaskStatus`: `active`, `approved`, `rejected`, `returned`, `skipped`, `cancelled`, `reassigned`.
- `StageMode`: `single`, `parallel_all`, `parallel_any`.
- `DecisionType`: `approve`, `reject`, `return`.
- `ActorResolverKey`: registry-backed stable string, never an arbitrary class.
- `RequestNumber`, `PublicUlid`, `SchemaChecksum`, `PayloadChecksum`, `OptimisticVersion`, `MoneyValue`.

Enums are validated at service boundaries and persisted with database constraints where the supported database permits. Do not scatter unvalidated status strings across controllers, components, or jobs.

## 3. Universal invariants

1. An aggregate has exactly one database owner: Request.
2. Every request belongs to one authenticated requester and one published type version.
3. A request number and public ULID are globally unique within the one-company installation.
4. A published type version is immutable.
5. A payload revision, run, candidate snapshot, decision, and audit event is immutable after creation except for explicit delivery/operational metadata that does not change business evidence.
6. Only one active run exists per request.
7. Only one active stage position exists per active run.
8. A task is actionable only while its run and request are active/pending and its stage is current.
9. A decision actor must be an active candidate for the exact task at decision time and must pass policy/permission checks.
10. The requester cannot approve, reject, or return their own request in v1.
11. Every state-changing command carries an idempotency key and expected optimistic version.
12. Business mutation and corresponding audit/outbox append succeed or roll back together.
13. Historical display uses pinned snapshots/identifiers and never silently re-evaluates old definitions.
14. No Request entity holds a foreign key or polymorphic pointer to a domain module.
15. No hard delete removes approval evidence.

## 4. Request-type lifecycle

### 4.1 Create

- Stable `code` is normalized and unique; changing it after first publication is forbidden.
- A type starts with one mutable draft version.
- At most one active draft version exists per type.

### 4.2 Validate

Validation checks metadata, audience, schema grammar, stable keys, option uniqueness, size bounds, resolver key/config, stage order, candidate viability for static resolvers, self-approval risk warning, and package checksum. Validation never mutates publication status.

### 4.3 Publish

- Publisher permission is separate from update permission.
- The service acquires a lock on the type/draft.
- It repeats authoritative validation, builds a canonical snapshot/checksum, marks the draft published, supersedes the previous published version for new creation where applicable, advances the stable type pointer, appends audit/outbox, and commits atomically.
- A failed validation or side effect before commit leaves no partial publication.

### 4.4 Revise and retire

- Editing a published version requires cloning it to a new draft.
- Retiring prevents new drafts/submissions according to policy but keeps all historical and already-pinned access.
- Re-publication creates a new monotonically increasing version number; it never overwrites the old version.

## 5. Internal request lifecycle invariants

### 5.1 Draft

- Only an eligible requester can create a draft for a currently available type.
- The draft records its intended type/version but first submission performs the authoritative pin and validation.
- Only the requester or explicitly authorized administrator may edit it.
- Autosave uses expected revision/version and never overwrites a newer server revision silently.
- Cancellation is permitted and immutable; cancelled drafts cannot be reopened in v1.

### 5.2 Submit

Within one transaction:

1. Lock the internal request and validate expected version/idempotency.
2. Confirm requester, audience, active published type/version, and module state.
3. Normalize and validate payload server-side.
4. Resolve the first stage enough to prove it can activate; enforce no self-approval/empty candidate set.
5. Create an immutable payload revision and checksum.
6. Create a new active run with monotonically increasing sequence.
7. Activate the first stage/tasks/candidates.
8. Set request to `pending`, advance optimistic version, append audit and outbox.

If any step fails, none of them persist.

### 5.3 Approve

- A decision locks the task, run, and request in a documented order.
- It verifies active state, candidacy, permission, expected version, and idempotency.
- It appends one decision and updates that task.
- It evaluates the current stage exactly once.
- If the stage completes, remaining ANY tasks are skipped and either the next stage activates or the run/request becomes approved.
- A final approval timestamp is immutable.

### 5.4 Reject

- Reject requires a reason.
- In `single` and `parallel_all`, any valid rejection terminates the run/request immediately.
- In `parallel_any`, a rejection completes only that candidate task; the stage remains active while another actionable candidate remains. When every candidate task is rejected, the run/request becomes rejected using the final decision transaction.
- Remaining active/future tasks become cancelled when the request is rejected.
- Reject is terminal in v1.

### 5.5 Return and resubmit

- Return requires a reason and terminates the active run as `returned` immediately in every stage mode.
- Remaining tasks become cancelled; the request becomes `returned`.
- The requester edits a new draft payload revision under the same pinned type version.
- Resubmit repeats full schema and actor validation, creates a new immutable payload revision and run, and begins at stage one.
- Old decisions are never reset, overwritten, or displayed as part of the new run without clear run labels.

### 5.6 Cancellation

- Requester may cancel only `draft` or `returned` requests that they own.
- An actor with `request.instance.cancel-any` may cancel `pending` with a mandatory reason.
- Pending cancellation locks the active aggregate, cancels active/future tasks, closes the run, appends audit/outbox, and sends notifications after commit.
- Approved/rejected/cancelled requests cannot be cancelled again.

## 6. Candidate and task invariants

- Stage activation calls exactly one registered resolver using the published configuration and current trusted context.
- Resolver output is a deduplicated ordered set of active user IDs with safe source metadata.
- Requester is removed before cardinality validation.
- `single` requires exactly one remaining user; `parallel_all`/`parallel_any` require at least one.
- Candidate snapshots capture user ID and safe display metadata/reference needed for historical explanation, without copying secrets.
- Role membership changes after activation do not add/remove candidates from that active stage.
- Later stages resolve when activated, not at first submission, so they use then-current trusted Shell data; the exact output is snapshotted.
- Each candidate receives one task unless the mode is `single`, which still produces one task.
- A task has at most one terminal business decision.
- Skipped, cancelled, or reassigned tasks are not actionable.

## 7. Reassignment invariants

- Only `request.task.reassign` plus record policy allows reassignment.
- Target must be an active Shell User, cannot be requester, and must meet any type/stage constraints.
- A reason is mandatory.
- Reassignment marks the old task `reassigned`, preserves candidates and history, creates a replacement task/candidate linked to the old task, and increments aggregate version.
- It cannot change stage mode or resurrect a terminal task/run.
- Concurrent reassignment and decision: exactly one succeeds; the loser receives a safe conflict.

## 8. Comment and attachment invariants

- Visibility is inherited from the request policy; knowing a public ULID is insufficient.
- Comments are append-only and contain bounded text. Mentions, if implemented, may reference only authorized participants and do not grant access.
- Attachment metadata is committed only after a successful private upload flow; incomplete temporary objects are cleaned by bounded maintenance.
- Download reauthorizes on every request and never returns a permanent public URL.
- File replacement creates a new attachment record; it does not overwrite historical evidence.

## 9. Idempotency and concurrency

- Idempotency scope is authenticated actor + command + aggregate/public ID + key.
- Store a request fingerprint and serialized safe result reference. Reuse with a different fingerprint returns conflict.
- Completed duplicate commands return the original safe response; in-progress duplicates return a retryable conflict/accepted response defined by API policy.
- Optimistic version protects stale form/task UI. Database row locks protect critical transitions.
- Lock order must be standardized in `CREATE_PLAN.md` to avoid deadlocks, with retry only for known transient database errors.
- Notifications/events/exports are never the source of truth for a decision.

## 10. Historical truth

Historical views resolve labels in this order:

1. Stored safe snapshot on type version/task/decision.
2. Current Shell record only as supplemental current-state information.
3. A stable deleted/unavailable marker.

Renaming or disabling a user/role/type must not rewrite a historical decision. Replaying a resolver against historical data to reconstruct candidates is forbidden.
