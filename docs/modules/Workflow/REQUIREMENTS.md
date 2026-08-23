# Workflow Module Requirements

Status: Approved business and architecture input  
Specification: Workflow Enterprise v4.0 Ultimate  
Module: `Workflow`  
Type: `domain`

## 1. Purpose

Provide one configurable workflow engine for internal company requests. Authorized administrators can design and publish request forms and approval processes; employees and administrators can submit, review, approve, reject, return, recall, delegate, comment on, and track requests according to an immutable published workflow version.

The module owns only its internal request domain. It does not orchestrate or mutate `Order`, `Admission`, `Product`, `Pharma`, or any other domain module in version 1.

## 2. Approved decisions

- Organization scope: one company with a simple organization model.
- Execution model: deterministic versioned state machine; no mandatory BPMN runtime.
- Actors: User/Role are built-in resolvers; department/supervisor resolution is an extension contract.
- Integration: internal Workflow requests only; no polymorphic binding to external domain records.
- Signature: no digital signature or PKI in version 1.
- Dependencies: Workflow may depend only on system shell modules.
- Experience: requester and approver journeys are mobile-first; the definition designer is optimized for tablet/desktop.
- PWA: online-first app shell, selected sanitized cached reads, and non-sensitive offline drafts; all authoritative business mutations require online authentication.
- Output language: user-facing UI may be Vietnamese; identifiers, code, events, tables, and APIs use stable English names.

## 3. Module boundary

### Owned responsibilities

- Workflow definitions and immutable published versions.
- Dynamic form schemas and validated request payloads.
- Request instances, execution tokens, tasks, assignments, decisions, comments, attachments, timers, notifications, and audit events.
- User/Role actor resolution and resolver extension contracts.
- SLA, delegation, escalation, quorum, parallel branches, joins, conditions, and subflows between Workflow definitions.
- Admin designer, work inbox, request workspace, operations dashboard, reports, and internal API.

### Explicit non-responsibilities

- Master data for orders, admissions, products, finance, HR, or pharma.
- Direct reads/writes to models or tables owned by domain modules.
- Multi-company or tenant isolation.
- Full BPMN 2.0 execution/import/export.
- Legal PKI/e-signature.
- Fully offline submission, approval, rejection, return, claim, publication, upload, or operational mutation queues.
- Payroll, accounting posting, procurement, inventory, or CRM automation.
- Arbitrary PHP, SQL, Blade, shell, JavaScript, or expression execution.

## 4. Dependency invariant

Workflow may import or call only framework/application contracts and modules whose manifest resolves to `type = shell`. No class under `Modules/Workflow` may import a namespace owned by a `domain` module.

Proposed direct shell dependencies:

- `Admin`: admin shell, layout, menu, and workspace composition.
- `Auth`: authenticated guard conventions.
- `User`: actor identity.
- `Role`: roles, permissions, and capability checks.
- `Shared`: stable shared UI/import-export infrastructure when an approved feature uses it.

`System` may be used only through stable operational contracts when required. Queue names and worker requirements should be declared in the Workflow manifest so System can discover them without Workflow reaching into System internals.

Architecture tests must fail when Workflow imports a domain module or its manifest declares a non-shell dependency.

## 5. Actors and roles

- Requester: creates, edits, submits, recalls, and views own requests.
- Approver: acts on assigned approval tasks.
- Reviewer: performs non-approval manual review tasks.
- Delegate: acts within a valid delegation scope and period.
- Workflow Designer: manages draft definitions and tests validation.
- Workflow Publisher: publishes/retire definitions; separated from ordinary design permission.
- Workflow Operator: monitors instances, timers, queues, and failures without editing definitions.
- Auditor: read-only access to permitted histories, decisions, and exports.
- Workflow Administrator: manages catalogs, policies, and operational recovery with explicit permissions.
- Super Admin: follows the repository `Gate::before` behavior; all sensitive actions still create audit events.

## 6. Functional requirements

### 6.1 Definition lifecycle

- Create a definition with stable code and descriptive metadata.
- Edit only a draft version.
- Validate graph, schema, actors, conditions, SLAs, and permissions before publishing.
- Publish an immutable version atomically.
- Retire a definition for new submissions without affecting running instances.
- Duplicate a version into a new draft.
- Compare two versions and export a sanitized definition package.
- Never edit or delete a version referenced by an instance.

### 6.2 Supported node types

- start
- approval
- manual_task
- condition
- parallel_split
- parallel_join
- notification
- timer_wait
- subflow
- end

The engine must reject unknown types. New node types require explicit code registration and tests, never arbitrary class names from browser input.

### 6.3 Request lifecycle

Canonical statuses:

```text
draft -> submitted -> running -> approved -> completed
                         |          |
                         +-> rejected
                         +-> returned -> draft
draft/submitted -> cancelled when policy permits
submitted/running -> recalled only when definition policy permits
```

Every transition defines actor, permission, preconditions, validations, state mutation, token/task effects, notifications, audit data, and failure behavior. Status strings must be backed by enums and database constraints/validation.

### 6.4 Approval and task behavior

- Sequential and parallel approvals.
- Approve-all, approve-any, fixed quorum, or percentage quorum.
- Reject-fast or collect-all rejection policy.
- Return to requester or a named prior node.
- Role-based assignment with deterministic candidate snapshotting.
- Claim/unclaim when policy allows.
- Reassignment and delegation with reason, scope, start/end, and audit.
- Due date, reminder, escalation, expiration, and overdue tracking.
- No actor may decide the same task twice.
- Duplicate HTTP/job delivery must return the original result or a safe conflict, never repeat side effects.

### 6.5 Conditions

- Conditions use a typed allowlisted DSL over request fields and trusted workflow context.
- Supported operators are explicit and type-aware: equality, inequality, comparison, membership, presence, boolean composition, date comparison, and approved aggregate functions.
- Conditions cannot call PHP, SQL, functions, services, models, HTTP, filesystem, environment, or secrets.
- Validation must detect missing default paths, ambiguous outgoing edges, incompatible types, and unreachable nodes.

### 6.6 Dynamic forms

- Versioned JSON schema with sections, fields, validation, visibility, defaults, options, and safe calculations.
- Required initial field types: text, textarea, integer, decimal, currency, date, datetime, boolean, select, multiselect, user, role, attachment, and read-only computed.
- Stable field keys after publication.
- Server-side validation generated from the published schema.
- Conditional visibility does not bypass server-side authorization or validation.
- Uploaded files are private, scanned/validated according to repository capability, and downloaded through authorized controllers.

### 6.7 Workspaces

- My requests, drafts, work inbox, claimed tasks, delegated tasks, overdue tasks, completed tasks, and searchable archive.
- Definition designer with metadata, form builder, workflow graph editor, validation results, version comparison, and publish confirmation.
- Request detail with information, tasks/approval, timeline, comments, attachments, and audit summary.
- Operations workspace for stuck instances, failed jobs, due timers, outbox failures, and safe retry actions.
- Reports for throughput, cycle time, SLA compliance, backlog, rejection/return rate, and workload by role/user.

### 6.8 Responsive UX and PWA

- Mobile browser and installed PWA treat create request, resume draft, work inbox, task context, approve/reject/return, and request tracking as first-class journeys.
- Tablet supports the same requester/approver journeys and a responsive definition designer; landscape tablet/desktop is the primary topology-editing workspace.
- Phone layouts use cards or compact lists, progressive disclosure, sticky context-aware actions, bottom sheets/drawers, and no hover-only interaction or page-level horizontal scrolling.
- Controls are touch-friendly, keyboard accessible, visually explicit, and expose loading, empty, stale, offline, reconnecting, conflict, success, and failure states.
- Workflow uses the existing shell-owned manifest/service worker/PWA lifecycle. It must not create a second manifest, service worker, app shell, or global cache strategy.
- The app shell and public static assets may be cached. Selected authorized read models may be stored as sanitized, per-user, expiring snapshots for read-only offline display.
- Non-sensitive form values may be kept as a versioned local draft. Sensitive fields and attachment binaries are excluded from offline persistence unless a later security review explicitly approves them.
- Reconnection may synchronize a draft after revision/conflict checks, but must never automatically submit it or replay an approval/task/business command.
- Submit, approve, reject, return, claim/unclaim, recall, cancel, reassign, publish, upload, comment, retry, and other authoritative mutations are disabled offline with a clear explanation and retry path.
- Local Workflow data is scoped to the authenticated user and cleared on logout, account change, access revocation response, expiry, or explicit local-data removal.

## 7. Data requirements

Required aggregates and tables are defined in `02-DATABASE_ERD_AND_SCHEMA.md`. Core ownership includes definitions, versions, nodes, transitions, forms, requests, payload snapshots, tokens, tasks, candidates/assignees, decisions, delegations, timers, comments, attachments, audit events, outbox messages, and idempotency records.

Money uses decimal minor-safe semantics; timestamps are stored consistently in UTC and presented in configured local timezone. JSON columns are validated and size-bounded. Tables use foreign keys, unique constraints, and indexes matching actual access paths.

## 8. Permissions

Minimum capabilities:

```text
workflow.dashboard.view
workflow.definition.view
workflow.definition.create
workflow.definition.update
workflow.definition.publish
workflow.definition.retire
workflow.request.view-own
workflow.request.view-all
workflow.request.create
workflow.request.update-own
workflow.request.submit
workflow.request.recall
workflow.request.cancel
workflow.task.view
workflow.task.claim
workflow.task.decide
workflow.task.reassign
workflow.delegation.manage-own
workflow.delegation.manage-all
workflow.comment.create
workflow.attachment.upload
workflow.attachment.download
workflow.audit.view
workflow.report.view
workflow.operation.view
workflow.operation.retry
workflow.settings.manage
```

Permissions are necessary but not sufficient: policies must also check ownership, assignment/candidacy, definition scope, request status, delegation validity, and record visibility.

## 9. API requirements

- Laravel Sanctum authentication.
- Versioned `/api/workflow/v1` routes.
- Capability and record-level authorization.
- Idempotency key required for submit, decision, recall, cancel, retry, and other side-effecting commands.
- Stable problem response shape with correlation ID; no raw exception text.
- Cursor or bounded pagination for collections.
- Optimistic concurrency token for updates/decisions.
- API never accepts model class names, service classes, table names, executable expressions, storage paths, event class names, or queue names from clients.

## 10. Events, jobs, and notifications

- Domain events are recorded transactionally and delivered through an outbox.
- Jobs are idempotent, retry-safe, correlation-aware, and dispatched after commit.
- Dedicated queues are declared in the module manifest.
- Scheduler commands process due timers/escalations in bounded batches with leases/locks.
- Notifications support database and mail channels initially; channel failures do not roll back a committed decision.

## 11. Runtime state and storage

- Default enabled state is declared in `config/module.php`.
- Runtime enable/disable uses `ModuleStateRepository`/`ModuleStateResolver` only.
- Workflow never writes the tracked manifest or reads module-state JSON directly.
- Disabling Workflow blocks new HTTP/API/console entry points according to platform behavior but must not corrupt data.
- Private attachments and generated exports use Laravel Storage with server-controlled paths and retention policy.
- CLI and PHP-FPM ownership must work without `chmod 777`.

## 12. Security and audit

- Backend authorization for every sensitive mutation/download.
- Append-only audit event for definition publication, request transitions, task actions, delegation, reassignment, file access, operational retry, and administrative changes.
- Redaction policy for secrets and sensitive values.
- CSRF for web, Sanctum for API, rate limits, upload validation, output escaping, and bounded queries.
- Definition import and condition DSL are treated as untrusted input.
- Digital signature, certificate signing, and legal non-repudiation are explicitly absent from version 1.

## 13. Scope priority

### MUST HAVE

- Definition/form versioning and validation.
- Deterministic execution engine with sequential, parallel, conditional, quorum, timer, and subflow nodes.
- Internal request CRUD/lifecycle.
- User/Role actor resolution and resolver contract.
- Task inbox, assignment, decision, delegation, SLA/escalation.
- Private files, comments, notifications, append-only audit.
- Admin UI, API, operations, basic reports.
- Responsive mobile/tablet requester and approver UX plus the approved online-first PWA/offline-draft contract.
- Transactions, locking, idempotency, outbox, security and tests.

### SHOULD HAVE

- Definition package import/export with dry-run and diff.
- Workflow simulation with non-production sample payloads.
- Calendar-aware business-hour SLAs.
- Advanced report exports and saved filters.
- Additional notification channels through allowlisted adapters.
- Department/supervisor resolver implementation when a canonical shell source exists.

### FUTURE

- Full BPMN 2.0 compatibility.
- Multi-company/multi-tenant isolation.
- PKI/e-signature.
- Domain-module adapters and external orchestration.
- Process mining, predictive routing, and AI recommendations.
- Public marketplace of workflow templates.

## 14. Acceptance criteria

- A designer can create, validate, publish, duplicate, compare, and retire a definition.
- Published versions are immutable and running instances remain pinned.
- Requests execute deterministically across sequential, conditional, parallel, quorum, timer, and subflow scenarios.
- Duplicate commands/jobs cannot duplicate tasks, decisions, notifications, or audit events.
- Concurrent decisions produce one valid state outcome.
- Unauthorized users cannot view or mutate requests, tasks, attachments, definitions, operations, audit, or reports.
- Workflow source imports no domain-module namespace and its manifest declares only shell dependencies.
- Lists are searchable, filterable, bounded, paginated, and selection semantics are explicit.
- Queue/scheduler retries are safe and observable.
- Fresh migration, module discovery, runtime ON/OFF, targeted tests, module regression, full project regression, frontend build, and manual phone/tablet/desktop/installed-PWA UI smoke pass.
- Offline shell/read/draft behavior follows the approved matrix; local storage contains no sensitive field, token, file binary/key, raw unrestricted API response, or queued authoritative mutation.
- Runtime operations leave Git clean.

## 15. Approved out-of-scope items

- Multi-tenancy.
- Digital/PKI signature.
- Direct integration with domain records.
- Full BPMN engine.
- Fully offline business mutations or background replay of submit/approval/task commands.
- Application code during `/analyze-new-module`.

## 16. Remaining non-blocking notes

- Exact Vietnamese labels and default seeded templates may be finalized in `CREATE_PLAN.md`.
- Business-hour calendar details may start with configurable 24x7 elapsed time and be extended later.
- Department/supervisor resolver remains inactive until a canonical shell-owned organization source is confirmed.

## CREATE-MODULE READINESS

```text
Business requirements : READY
Module boundary       : READY
Bootstrap Contract    : READY
Dependencies          : READY
Database              : READY
Permissions           : READY
Workflow              : READY
Responsive UX/PWA     : READY
Runtime state         : READY
Docker/runtime storage: READY

Overall: READY FOR /create-module Workflow
```
