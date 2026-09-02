# Request — Module Contract

## 1. Identity

- Module: `Request`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Request/config/module.php`
- Routes: `Modules/Request/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

`Request` owns the internal-request business domain: configurable request definitions and versions, requester submission lifecycle, approval tasks and decisions, comments/attachments, audit/outbox/notification state, operational recovery, reporting and secure exports.

The Module serves both the Admin workspace and approved client-facing consumers without transferring business ownership away from `Request`.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Request dashboard and workspace navigation | `Request` | `request.dashboard` → `RequestDashboardController` |
| Request groups, types, versions and designer | `Request` | `request.admin.groups`, `request.admin.types`, `request.admin.types.designer`, `request.admin.types.versions` |
| Definition package import/export | `Request` | `RequestDefinitionPackageController` |
| Requester catalog/create/mine/detail | `Request` | `RequestRequesterController` + requester Livewire components |
| Approver inbox/detail/decision | `Request` | requester/inbox route surface + approver Livewire components |
| Reports and bounded pagination | `Request` | `request.admin.reports` → `RequestReportController` |
| CSV/XLSX/PDF export planning, generation and download | `Request` | `RequestExportController`, `PlanRequestExport`, `RequestExportQuery` |
| Request operations/recovery | `Request` | `RequestOperationsController` |
| Request audit, attachment, comment, task and notification persistence | `Request` | Request models/services and Request-owned tables |
| Local/testing demo data for Request workflows | `Request` | `Modules/Request/Database/Seeders/RequestDemoSeeder.php` |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Admin shell/layout and Admin authentication surface | `Admin` / `Auth` | `Request` consumes the admin shell and guard |
| User identity directory | `User` | `Request` consumes user identities through approved contracts |
| Roles/permission infrastructure | `Role` | `Request` declares and consumes Request permissions |
| Shared cross-module utilities/contracts | `Shared` | `Request` consumes shared infrastructure where applicable |
| Client/PWA application shell | `ClientPortal` | Client surfaces consume Request capabilities; Request retains domain ownership |
| Runtime module enable/disable persistence | `System` module-state mechanism | Request declares manifest metadata only; it does not own runtime toggle storage |

## 5. Dependencies

### Direct dependencies

These dependencies MUST remain synchronized with `Modules/Request/config/module.php`.

| Module | Reason | Required |
|---|---|---|
| `Admin` | Admin layout, guard-facing workspace and route surface | Yes |
| `Auth` | Authentication integration | Yes |
| `User` | User identity/directory contract | Yes |
| `Role` | Permission/authorization infrastructure | Yes |
| `Shared` | Shared integration utilities/contracts used by the Module | Yes |

### Integration dependencies

- `ClientPortal` may expose Request capabilities in a PWA/client shell. It is a consumer, not the Request domain owner.
- `System` controls effective runtime module state; Request must not mutate the System module-state file directly.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Admin users | Definition management, reporting, operations and Request workspace |
| Authenticated requester users | Catalog, create, own requests, comments and attachments subject to permissions |
| Authenticated approvers | Inbox, request detail and task decisions subject to permissions |
| ClientPortal/PWA surfaces | Approved Request requester/approver capabilities through Request-owned contracts |
| System module catalog/runtime | Request manifest metadata, dependencies, permissions and expected tables |

## 7. Canonical Routes

The canonical route groups are registered by `Modules/Request/routes/web.php`:

- `admin/requests/admin/*` named `request.admin.*` for definitions, reports, exports and operations.
- `admin/requests/*` named `request.*` for the Admin Request workspace, requester functions, inbox, attachments and downloads.

Route ownership is determined by the Request runtime behind these routes, not by the `/admin` URL prefix.

Ownership audits MUST trace:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

Canonical controller families include:

- `RequestDashboardController`
- `RequestDefinitionController`
- `RequestDefinitionPackageController`
- `RequestRequesterController`
- `RequestReportController`
- `RequestExportController`
- `RequestOperationsController`
- `RequestAttachmentController`

### Livewire / UI Components

Canonical Livewire families are grouped by responsibility:

- `Livewire/Admin/*` for definition administration and designer workflows.
- `Livewire/Requester/*` for requester catalog/draft/detail/comment/attachment workflows.
- `Livewire/Approver/*` for inbox/detail/decision workflows.
- `Livewire/Shared/*` for Request-owned shared UI such as audit timeline.

Thin route Blade shells that mount these Livewire components are compatibility/runtime entry surfaces and are not duplicates merely because they share similar names.

### Services

`Application/Services/*` owns use-case orchestration, including definition lifecycle, request lifecycle, reporting/export planning and operational actions. Livewire/controllers must not absorb core business rules that belong in these services.

### Models

`Models/*` owns Request persistence representations. Persistence-sensitive changes require explicit schema/data approval when applicable.

## 9. Persistence Ownership

The Request manifest declares 18 Request-owned tables:

| Table / storage | Owner | Source | Notes |
|---|---|---|---|
| `request_groups` | `Request` | Request migrations | Definition grouping |
| `request_types` | `Request` | Request migrations | Request type identity/state |
| `request_type_versions` | `Request` | Request migrations | Versioned definition content |
| `request_type_audiences` | `Request` | Request migrations | Allowed requester audience |
| `request_stage_definitions` | `Request` | Request migrations | Approval-stage definitions |
| `request_audit_events` | `Request` | Request migrations | Durable audit trail |
| `request_outbox_messages` | `Request` | Request migrations | Outbox delivery state |
| `request_idempotency_keys` | `Request` | Request migrations | Mutation idempotency |
| `request_instances` | `Request` | Request migrations | Request instances |
| `request_payload_revisions` | `Request` | Request migrations | Payload revision history |
| `request_runs` | `Request` | Request migrations | Runtime workflow run state |
| `request_tasks` | `Request` | Request migrations | Approval tasks |
| `request_task_candidates` | `Request` | Request migrations | Task candidate state |
| `request_decisions` | `Request` | Request migrations | Approval decisions |
| `request_comments` | `Request` | Request migrations | Request comments |
| `request_attachments` | `Request` | Request migrations | Attachment metadata |
| `request_export_jobs` | `Request` | Request migrations | Export job/snapshot state |
| `request_notification_deliveries` | `Request` | Request migrations | Notification delivery state |

Private export/attachment files remain subject to Request authorization and storage contracts. Runtime module-state persistence is explicitly not owned by Request.

## 10. Integration Boundaries

### Admin/Auth/Role

- Business owner: `Request` for Request-specific permissions and behavior.
- Infrastructure owner: Admin/Auth/Role for shell, guards and permission mechanisms.
- Allowed direction: Request consumes these infrastructure contracts; Request permissions remain declared in its manifest.
- Request route middleware and server-side authorization must remain authoritative; UI visibility alone is never authorization proof.

### User directory

- Business owner: `User` for user identity.
- Consumer: `Request`.
- Request resolves active identities through approved User contracts such as `UserDirectory`; Request must not create a competing user directory.

### ClientPortal/PWA

- Business owner: `Request` for Request lifecycle and authorization semantics.
- Consumer: `ClientPortal` for client/PWA presentation.
- ClientPortal must not duplicate Request workflow rules or persistence.

### Export

- Business owner: `Request`.
- Export scope is always the intersection of the submitted filter/selection and the requester's current authorized scope.
- Download authorization must re-check owner/permission/current authorization scope and file expiry.
- Selected export semantics are canonical:
  - no selected IDs → export all records in the approved current filter scope;
  - selected IDs present → export only those selected records that are still inside the approved authorization scope.
- Selected export must never silently mean current page when no selection exists.

## 11. Compatibility / Deprecated Boundaries

No compatibility artifact is approved for deletion in the 2026-09-02 refactor.

Thin Blade route shells and existing Request/client integration entry points remain `KEEP` until explicit caller proof and a separately approved removal boundary exist.

## 12. Quarantine

The following boundaries are persistence/security sensitive and are effectively quarantined from opportunistic cleanup:

- Request table/migration ownership and existing production data.
- Authorization/permission names and guard semantics.
- Runtime module-state persistence owned outside Request.
- Export authorization snapshots, private storage and expiry semantics.
- Audit/outbox/idempotency history.

Do not rehome, delete or weaken these boundaries without separate explicit approval.

## 13. Refactor Invariants

Every refactor must preserve:

1. canonical Request route ownership;
2. admin/web guard and permission semantics;
3. Request persistence contracts and production data safety;
4. published definition/version compatibility;
5. requester/approver lifecycle behavior;
6. audit/outbox/idempotency integrity;
7. private attachment/export authorization;
8. dependency direction declared by the manifest;
9. ClientPortal as a consumer rather than a duplicate domain owner;
10. bounded Admin pagination for potentially large datasets;
11. Admin form controls with visible borders/focus/error states according to `ADMIN_UI_STANDARD.md`;
12. export semantics: selected rows when selection is non-empty, otherwise the complete approved filter scope;
13. local/testing demo seeders must be environment-safe and must not require production seeding.

## 14. Required Refactor Audit

Before implementation:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

Affected artifacts must be classified `KEEP / REHOME / DELETE / QUARANTINE / DEFER`.

For the 2026-09-02 refactor target:

- `KEEP`: Request domain/application architecture, routes, authorization, persistence, current workflow capabilities and thin route shells.
- `REHOME`: none approved.
- `DELETE`: none approved.
- `QUARANTINE`: persistence/authz/runtime-state/export-security boundaries listed above.
- `DEFER`: unrelated cross-module cleanup and any future shared-pagination convergence not proven safe in this batch.

## 15. Required Regression Scope

Minimum applicable gates:

1. focused tests for changed Request slice;
2. Request Feature regression;
3. impacted Admin/System/ClientPortal contract regression only when the changed boundary requires it;
4. Request route verification;
5. Pint for changed PHP files;
6. frontend build when Blade/assets are changed;
7. manual UI smoke for canonical Admin Request surfaces, with special attention to inputs, pagination and selected/all export behavior;
8. Git diff/clean verification before PR readiness.

Full-project regression is not automatic.

## 16. Architectural Change Rules

Update this file in the same PR whenever changing responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine or refactor invariants.

Source and this contract must not merge while they disagree.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Potential convergence of module-scoped Admin pagination into a single shared implementation | `Admin` / `Shared` | Must first prove a stable shared pagination contract across modules; not required to fix Request safely | Shared paginator is audited against Livewire and ordinary paginator consumers and adopted by an approved cross-module refactor |
| Broader ClientPortal/PWA presentation cleanup | `ClientPortal` | Request refactor must not expand into a separate presentation module | Separate ClientPortal audit/approval with Request integration regression |

## 18. Architecture Decisions

### 2026-09-02 — Preserve Request ownership and align UI/export contracts

**Decision:** Keep Request as the canonical owner of definitions, workflow, reports and exports; do not rehome or delete runtime boundaries in this refactor. Align Admin pagination/form controls, selected/all export semantics and local demo data inside Request.

**Reason:** Runtime evidence shows the existing Request architecture is mature and coherent; the material gaps are contract documentation, Admin UI consistency, selected export behavior and sufficiently rich local test data.

**Impact:** No schema migration, authorization-contract change, data deletion or major cross-module ownership change is authorized by this decision.
