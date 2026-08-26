# Request Module Requirements

Status: Approved business and architecture input  
Specification: Request v1  
Module: `Request`  
Type: `domain`

## 1. Purpose

Provide a focused internal request product for one company. Authorized administrators publish request types with dynamic forms and ordered approval stages. Authenticated employees create and track requests; resolved approvers approve, reject, or return them with a complete audit trail.

Request v1 is not a generic workflow/BPM engine. Its narrow boundary must be preserved so Codex can implement and validate a complete first release before the broader Workflow module is reconsidered.

## 2. Approved decisions

- Request is implemented first and independently; Workflow is deferred.
- Organization scope is one company with a simple structure.
- Request may depend only on system modules whose manifest declares `type = shell`.
- Request processes only its own internal requests and never reads or mutates another domain module.
- Approval is an ordered sequence of stages. A stage contains one or more approval tasks and completes by `all` or `any` policy.
- Built-in actor resolution supports fixed users, roles, and an approved user-valued form field. Department/supervisor resolution is an extension contract only because no canonical organization source currently exists.
- No digital signature or PKI in the first release.
- Request creation and inbox/approval are mobile-first; the type/form/approval designer is tablet/desktop-first.
- PWA is online-first: shell, selected sanitized cached reads, and non-sensitive offline drafts are allowed; submit and every decision/business mutation require online authentication.
- Dynamic form data uses immutable versioned JSON schemas and payload revisions, not an EAV field/value model.
- User-facing copy may be Vietnamese; code, tables, events, permission names, and APIs use stable English identifiers.

## 3. Module boundary

### 3.1 Owned responsibilities

- Request groups and request types.
- Draft and immutable published request-type versions.
- Dynamic form schemas, server-side validation, and payload revisions.
- Ordered approval-stage definitions with `single`, `parallel_all`, and `parallel_any` behavior.
- Internal requests, submission runs, approval tasks/candidates, decisions, comments, attachments, notifications, audit events, outbox records, and idempotency records.
- Request catalog, create/resume experience, My Requests, approval inbox, request detail/timeline, administration designer, operations view, reports, and authorized exports.
- Request-owned actor resolver contracts; built-in implementations may use only Shell Module contracts.

### 3.2 Explicit non-responsibilities

- A general graph, BPMN runtime, conditional gateway, split/join, subflow, timer node, SLA/escalation engine, or arbitrary workflow orchestration.
- Procurement, finance, HR, CRM, order, admission, product, pharma, inventory, accounting, payroll, or any other domain posting.
- Direct foreign keys, imports, model calls, table queries, or events coupled to a domain module.
- Multi-company/tenant isolation.
- Department hierarchy or manager master data.
- Delegation, quorum count/percentage, conditional approval stages, ad-hoc approver insertion, legal signature, or PKI.
- Anonymous/public submission or token-only public status lookup.
- Arbitrary PHP, SQL, Blade, shell, JavaScript, HTTP calls, or user-authored expressions.
- Offline submission, approval, rejection, return, cancellation, reassignment, comment, upload, publish, or retry queues.
- Bulk import of live requests.

## 4. Dependency invariant

The proposed direct dependencies are:

| Module | Required capability | Allowed type |
|---|---|---|
| `Admin` | Application/admin shell, layout, navigation, common workspace composition | `shell` |
| `Auth` | Authenticated guard and session conventions | `shell` |
| `User` | Stable user identity and active-user lookup | `shell` |
| `Role` | Role membership, permissions, and capability checks | `shell` |
| `Shared` | Approved shared UI and import/export primitives | `shell` |

No class under `Modules/Request` may import a namespace from a module whose manifest declares `type = domain`. Architecture tests must inspect both manifest dependencies and PHP namespaces. Framework/application contracts outside modules may be used only where repository standards already establish them.

The module manifest must use the repository-native `config/module.php` contract, declare `type = domain`, declare only the Shell dependencies above, participate in `ModuleStateResolver`, and be disabled by default until implementation and deployment verification are complete.

## 5. Actors

- Requester: creates, edits, submits, views, and cancels eligible own requests.
- Approver: views and decides an active task for which the user is a current candidate.
- Request Type Designer: edits groups and draft versions.
- Request Type Publisher: validates, publishes, and retires versions; separate permission from ordinary editing.
- Request Operator: views safe operational failures and invokes allowlisted recovery actions.
- Auditor/Reporter: read-only access to permitted audit and report views.
- Request Administrator: manages configuration within explicit permissions.
- Super Admin: follows the repository `Gate::before` convention; sensitive actions still create audit events.

## 6. Functional requirements

### 6.1 Request type lifecycle

- Create stable request groups and request types with unique codes.
- Create and edit only a draft version.
- Configure metadata, audience, form schema, approval stages, and request policies.
- Validate the entire draft before publication and display actionable errors.
- Publish atomically into an immutable version with a canonical checksum.
- Pin each submitted request to its published type version.
- Retire a type for new requests without changing historical or running requests.
- Duplicate an existing version into a new draft.
- Compare versions and export a sanitized definition package.
- Never edit or delete a published version referenced by a request.

### 6.2 Dynamic form

- Sections and stable field keys with labels, help text, types, required rules, defaults, options, display conditions, and size bounds.
- Required initial field types: text, textarea, integer, decimal, currency, date, datetime, boolean, select, multiselect, user, role, attachment, and read-only computed display.
- Server-side validation generated from the pinned schema is authoritative.
- Visibility rules may reference only declared fields through an allowlisted typed grammar; hidden fields never bypass authorization or validation.
- User and role values are stable identifiers, never browser-trusted names.
- Money is represented with currency plus decimal/minor-safe semantics; floats are forbidden.
- Attachments are private and are not embedded into payload JSON.

### 6.3 Request lifecycle

Canonical request statuses:

```text
draft -> pending -> approved
             |-> rejected
             |-> returned -> pending
draft/returned -> cancelled
pending -> cancelled only by an authorized administrator with a reason
```

- Draft is requester-editable.
- Submit validates access, schema, actors, type/version status, optimistic version, and idempotency key in one transaction.
- Submit creates an immutable payload revision and a new approval run.
- Return closes the active run, changes the request to `returned`, and makes a new draft payload revision possible.
- Resubmit retains the request identifier and originally pinned request-type version, creates a new immutable payload revision, and creates a new run. Previous runs and decisions remain immutable.
- Reject is terminal in v1. The requester may duplicate the data into a new request subject to current authorization.
- Approve completes the request only after every ordered stage has completed.
- Cancellation never deletes history. A pending request may be cancelled only by a privileged administrator, online, with a reason; its remaining tasks are cancelled.
- Every transition is enforced by an enum/state service and audited.

### 6.4 Approval pipeline

- Stages execute in ascending stable position, never as a free-form graph.
- `single`: exactly one resolved candidate; that candidate must approve.
- `parallel_all`: every generated task must approve; any rejection/return applies immediately to the run.
- `parallel_any`: the first valid approval completes the stage; remaining active tasks become `skipped`. A rejection by one candidate does not complete the stage while another candidate can act, unless all candidates reject; a return applies immediately.
- Resolved candidates are snapshotted when the stage activates.
- Empty or invalid resolution blocks submission/activation safely and creates no partial side effects.
- Self-approval is denied in v1. A requester removed from the resolved candidate set must not cause an empty stage; validation then fails safely.
- A user may decide each task at most once. Concurrent and duplicate decisions return the original result or a safe conflict.
- Reject, return, and reassignment require a non-empty reason.
- Reassignment is an explicit authorized operation; it never silently rewrites past candidate or decision history.

### 6.5 Actor resolution

Built-in resolver keys:

- `fixed_users`: one or more configured active users.
- `role_members`: active users currently assigned to one configured role.
- `form_user_field`: active user selected in one approved user-valued form field.

The resolver registry is server-owned and allowlisted. Browser input supplies only a registered key and validated configuration. `manager_of_requester` and `department_role` are reserved extension examples, not v1 implementations. They require a separately approved, canonical Shell-owned organization contract; Request must not guess from profile fields or query a domain module.

### 6.6 Collaboration and files

- Authorized participants may comment while the request is not archived; comment editing/deletion is not supported in v1.
- Comments are immutable audit-linked records; administrative redaction, if implemented, preserves metadata and reason.
- Attachments are uploaded through authorized endpoints, stored privately, validated by size/MIME/extension, and served through an authorization check.
- The UI clearly distinguishes form attachments, general request attachments, and generated exports.
- No attachment binary is persisted offline in v1.

### 6.7 Workspaces

- Dashboard: actionable counts and recent items, not an unbounded analytics surface.
- Catalog/create: discover request types by group, search, eligibility, and concise description.
- Draft/create: dynamic form, autosave, validation summary, and explicit submit review.
- My Requests: bounded search/filter/sort/status list with mobile cards and desktop table.
- Inbox: active tasks with context, filters, stale/offline handling, and safe decision confirmation.
- Request detail: summary, pinned form snapshot, current stage, all runs, decisions, timeline, comments, and private attachments.
- Type administration: group/type list, draft editor, form builder, approval-stage builder, validation, version diff, publish, and retire.
- Operations: failed outbox/notification/export records and only allowlisted idempotent retries.
- Reports: bounded operational metrics and authorized private exports.

### 6.8 Responsive and PWA experience

- Phone journeys prioritize create request, resume draft, My Requests, inbox, request context, and approve/reject/return.
- Tablet/desktop is primary for schema and approval-stage design; the designer remains usable on tablet landscape.
- No hover-only control or page-level horizontal scrolling. Touch targets, focus order, labels, contrast, reduced motion, loading/empty/error/stale/offline/conflict states, and keyboard navigation are required.
- Reuse the shell-owned web manifest, service worker, app shell, authentication lifecycle, and cache controls. Request must not register a second manifest/service worker or cache strategy.
- Selected authorized read models may be cached as sanitized, per-user, expiring snapshots for read-only offline viewing.
- Non-sensitive draft values may be stored locally with schema version, server revision, owner, timestamp, and expiry. Sensitive fields and attachment binaries are excluded.
- Reconnection may offer a reviewed draft synchronization after conflict detection; it must never auto-submit or replay a business command.
- All authoritative mutations are disabled offline with a clear explanation and retry affordance.
- Local Request data is cleared on logout, account change, access-revocation response, expiry, or explicit local-data removal.

## 7. Data requirements

The canonical model is specified in `02-DATABASE_ERD_AND_SCHEMA.md`. Required ownership includes request groups/types/versions/audiences/stages, request instances, payload revisions, runs, tasks/candidates, decisions, comments, attachments, audit events, outbox messages, and idempotency records.

Use foreign keys, unique constraints, enums/validation, bounded JSON columns, checksums, optimistic versions, and indexes matching documented access paths. Timestamps are UTC. Records carrying evidence are archived or retained, never silently hard-deleted. Public identifiers are non-sequential ULIDs; database primary keys may remain repository-standard integers.

## 8. Permissions

Minimum named permissions:

```text
request.dashboard.view
request.group.view
request.group.create
request.group.update
request.group.archive
request.type.view
request.type.create
request.type.update
request.type.audience.manage
request.type.publish
request.type.retire
request.type.import
request.type.export
request.instance.view-own
request.instance.view-participant
request.instance.view-all
request.instance.create
request.instance.update-own
request.instance.submit
request.instance.cancel-own
request.instance.cancel-any
request.task.view
request.task.decide
request.task.reassign
request.comment.create
request.attachment.upload
request.attachment.download
request.audit.view
request.report.view
request.export
request.operation.view
request.operation.retry
```

A permission is necessary but never sufficient. Policies also check request audience, ownership, participation/candidacy, active task, pinned version, record visibility, state, optimistic version, and resolver result.

## 9. API and integration requirements

- Web and Livewire are the primary first-party interface.
- Authenticated, versioned `/api/request/v1` routes support first-party responsive/PWA flows and later approved clients.
- Laravel Sanctum, record-level policies, bounded/cursor pagination, stable validation/problem responses, and correlation IDs are required.
- Side-effecting commands require an idempotency key and optimistic concurrency token.
- No generic endpoint accepts a PHP class, table, model type, resolver class, event class, or arbitrary expression from the browser.
- Request emits Request-owned integration events through a transactional outbox. No consumer acknowledgment may change Request state in v1.
- Initial notification channels are database/in-app and email through repository-approved infrastructure. Realtime delivery is optional only after a canonical shell-owned broadcasting contract exists.

## 10. Security, privacy, and audit

- Deny by default at route, policy, query, file, export, event, and cached-read boundaries.
- CSRF/session protection for web and Sanctum rules for API.
- Rate limits for submit, decide, upload/download, export, import validation, and recovery actions.
- Immutable audit events for publication, submission, stage activation, task generation/resolution, decisions, return/resubmit, cancellation, reassignment, file activity, export, and retry.
- Audit stores actor, effective actor, action, entity/public ID, before/after or structured delta, reason, correlation ID, idempotency key hash, IP/user agent where policy permits, and timestamp. Secrets and raw sensitive payloads are excluded.
- Exports and generated documents are private, authorized, expiring, and audited.
- Logs, notifications, events, and metrics contain only the minimum safe metadata.

## 11. Reliability and operations

- Transactions cover publish, submit, stage completion/next activation, decision, return, cancellation, reassignment, and outbox append.
- `lockForUpdate` or an equivalent repository-approved mechanism protects state-changing concurrency paths.
- Jobs are retry-safe and operate on identifiers, not serialized mutable models.
- Outbox dispatch and notification delivery have bounded retries and visible failure states.
- Operators may retry only allowlisted idempotent operations; no UI button may re-run an arbitrary class/job.
- Lists and exports are bounded; expensive exports are queued.
- Module-disable behavior is explicit: new Request routes/actions become unavailable, while data is preserved and already-running workers fail closed rather than mutating through a disabled module.

## 12. Quality requirements

- Unit tests for enums, schema validation, checksums, state transitions, stage modes, resolver contracts, and authorization predicates.
- Feature tests for complete request-type lifecycle, submit, sequential stages, parallel ALL/ANY, reject, return/resubmit, cancellation, reassignment, comments, files, exports, APIs, and operations.
- Concurrency tests prove exactly-once decisions and stage activation.
- Architecture tests enforce Shell-only dependencies and repository-native bootstrap.
- Accessibility tests cover keyboard, labels, focus, error association, reduced motion, and responsive breakpoints.
- PWA tests prove cache isolation, expiry/logout clearing, offline mutation blocking, draft conflict handling, and no attachment caching.
- Security tests cover IDOR, mass assignment, resolver/config injection, file attacks, spreadsheet formula injection, rate limiting, and sensitive-data leakage.

## 13. Release gates

- All MUST requirements and acceptance cases in `10-TEST_AND_ACCEPTANCE.md` pass.
- No domain-module dependency exists in manifest, imports, queries, routes, jobs, events, or tests.
- Published version immutability and historical request readability are proven.
- Every state-changing endpoint is authorized, transactional, idempotent, concurrency-safe, and audited.
- Mobile create/inbox/decision and tablet/desktop designer journeys pass manual QA.
- Private storage, queue workers, mail/database notifications, cache clearing, and module enable/disable runbook are documented and verified.
- Workflow remains disabled/deferred and does not duplicate Request-owned tables or routes.

## 14. CREATE-MODULE READINESS

| Area | Status | Evidence / decision |
|---|---|---|
| Purpose and v1 boundary | READY | Sections 1–3; master spec |
| Shell-only dependencies | READY | Section 4; architecture tests required |
| Actors and RBAC | READY | Sections 5 and 8; actor/RBAC spec |
| Request lifecycle | READY | Section 6.3; domain invariant spec |
| Sequential + parallel ALL/ANY | READY | Section 6.4; approval spec |
| Dynamic forms/versioning | READY | Sections 6.1–6.2; versioning spec |
| Data ownership/schema | READY | Section 7; ERD/schema spec |
| UX/responsive/PWA | READY | Section 6.7–6.8; UX spec |
| API/events/notifications | READY | Section 9; API/events spec |
| Security/audit/files | READY | Section 10; security spec |
| Reliability/operations | READY | Section 11; API/security specs |
| Reporting/import/export | READY | Reporting spec; live request import excluded |
| Tests and traceability | READY | Test spec and traceability matrix |
| Request/Workflow ownership | READY | Accepted ADR; Workflow deferred |

Overall: **READY FOR `/create-module Request` PLANNING ONLY**.

No application code is authorized by this document. `/create-module Request` must first create `CREATE_PLAN.md` and obtain explicit approval. `/create-module Workflow` is not authorized while `ADR-001` remains accepted.
