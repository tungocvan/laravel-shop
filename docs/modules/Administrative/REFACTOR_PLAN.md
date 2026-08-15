# Administrative Refactor Plan

## Refactor Goal

Refactor `Modules/Administrative` safely from the verified `/analyze Administrative` findings while preserving all existing public and admin contracts.

Primary goals:

- Fix confirmed correctness defects.
- Normalize permission boundaries without silently renaming public permission contracts.
- Remove unbounded admin list behavior.
- Improve auditability of destructive/archive operations.
- Add focused regression coverage for critical service and Livewire workflows.
- Preserve the current module architecture, routes, database schema, storage paths and public workflow.

Final target: **Major Refactor, not Full Rebuild**.

## Scope

Primary scope:

```text
Modules/Administrative/**
tests/Feature/Administrative/**
docs/modules/Administrative/**
```

Shared or cross-module files may only be changed if an approved fix proves they are required for Administrative permission registration or dependency compatibility.

## Evidence Being Addressed

### P1 — Confirmed runtime defect in admin file download

File:

`Modules/Administrative/Services/AdministrativeFileService.php`

Evidence:

`downloadForAdmin()` calls `AdministrativeFile::query()` but the class does not import `Modules\Administrative\Models\AdministrativeFile`.

Problem:

Inside namespace `Modules\Administrative\Services`, PHP may resolve the missing class as `Modules\Administrative\Services\AdministrativeFile`, producing a runtime class-not-found failure when an administrator downloads a submission file.

Planned change:

- Add the correct model import.
- Add a focused test proving an authorized admin download resolves the requested file only when it belongs to the selected submission.

### P1 — Permission contract inconsistency

Observed boundaries include:

- Route middleware:
  - `administrative.submission.view`
  - `administrative.file.download`
  - `administrative.procedure.view`
  - `administrative.procedure.create`
  - `administrative.procedure.update`
- Livewire mutations:
  - `administrative.submission.edit`
  - `administrative.submission.delete`
  - `administrative.procedure.archive`

Problem:

Administrative routes and Livewire actions correctly enforce named permissions, but the capability vocabulary is not yet documented as one canonical contract and should be verified against permission creation/seeding and UI visibility.

Planned change:

- Inventory every Administrative route/action permission.
- Preserve currently used permission strings unless a missing permission registration is proven.
- Ensure every sensitive Livewire mutation authorizes at action boundary.
- Ensure route middleware and UI action visibility align with the capability actually required.
- Add tests for denied and allowed access.

Compatibility rule:

Do not silently rename existing permission strings. If an alias/migration is required, keep backward compatibility and document it explicitly before implementation.

### P1 — Unbounded `All` list behavior

Files:

- `Modules/Administrative/Livewire/Procedures/ProcedureTable.php`
- `Modules/Administrative/Livewire/Submissions/SubmissionTable.php`
- `Modules/Administrative/Services/ProcedureService.php`
- `Modules/Administrative/Services/SubmissionService.php`

Evidence:

Both components expose `perPageOptions = [10, 25, 50, 100, 'All']`; services use `get()` when `perPage === 'All'`.

Problem:

A production dataset can be loaded into memory without a bound, contrary to `MODULE_STANDARD.md` and `ADMIN_UI_STANDARD.md`.

Planned change:

- Remove the unbounded `All` option from admin tables.
- Keep bounded page sizes: 10, 25, 50, 100.
- Validate `perPage` server-side before query execution.
- Keep existing filters/search behavior.

Compatibility impact:

The `All` UI option will be intentionally removed as a production-safety change; routes and query semantics remain otherwise compatible.

### P1 — Archive/delete audit gap

Files:

- `Modules/Administrative/Services/SubmissionService.php`
- `Modules/Administrative/Models/AdministrativeStatusHistory.php`
- related enums when required

Evidence:

`softDeleteMany()` soft-deletes selected submissions inside a transaction but does not write a status/audit history record for the archive operation.

Problem:

A privileged destructive action can remove submissions from normal admin listings without a domain audit event recording who performed the action.

Planned change:

- Preserve soft delete; do not hard-delete submissions.
- Record an immutable history entry for each archived submission when the current history schema and enum contract support it safely.
- Capture admin actor identity.
- Keep the operation transactional.
- If the current history enum/schema cannot represent archive without schema modification, document the exact limitation before adding any migration.

### P1 — Critical workflow test coverage

Current dedicated tests include route and database-structure coverage, but critical service and Livewire behavior is not fully locked by automated tests.

Planned coverage:

- Submission creation creates initial `pending` history.
- Approve succeeds only from `pending` with expected version.
- Reject requires reason and writes history.
- Request supplement moves `pending -> need_supplement` and records actor/history.
- Supplement resubmission moves `need_supplement -> pending`, increments version/revision and records history.
- Stale version cannot overwrite another admin's processing.
- Soft delete/archive is authorized and auditable.
- Admin file download is submission-scoped.
- Public lookup token failure does not reveal whether the submission code exists.
- Public result download remains scoped to the lookup grant and `result` file type.

### P2 — Admin UI/UX polish

Scope is intentionally limited to refactor-related screens.

Planned change:

- Remove `All` from page-size controls.
- Add/verify loading and disabled states on approve/reject/supplement/archive/bulk-delete actions where missing.
- Keep destructive confirmations clear.
- Preserve current layout and responsive table behavior.
- Do not redesign unrelated Administrative screens.

## Files Expected to Change

Confirmed or likely application files:

```text
Modules/Administrative/Services/AdministrativeFileService.php
Modules/Administrative/Services/SubmissionService.php
Modules/Administrative/Services/ProcedureService.php
Modules/Administrative/Livewire/Submissions/SubmissionTable.php
Modules/Administrative/Livewire/Submissions/SubmissionDetail.php
Modules/Administrative/Livewire/Procedures/ProcedureTable.php
Modules/Administrative/resources/views/livewire/submissions/submission-table.blade.php
Modules/Administrative/resources/views/livewire/submissions/submission-detail.blade.php
Modules/Administrative/resources/views/livewire/procedures/procedure-table.blade.php
```

Potentially changed only if evidence requires it:

```text
Modules/Administrative/Enums/SubmissionAction.php
Modules/Administrative/Models/AdministrativeStatusHistory.php
Administrative permission registration/seeder file owned by the repository
```

Tests expected to be added/updated under:

```text
tests/Feature/Administrative/**
```

Documentation after implementation:

```text
docs/modules/Administrative/ANALYSIS.md
docs/modules/Administrative/INFORMATION.md
docs/modules/Administrative/README.md
docs/modules/Administrative/REFACTOR_PLAN.md
```

## Contracts That Must Remain Compatible

The refactor must preserve:

- Public route URLs and route names.
- Admin route URLs and route names.
- Livewire component aliases generated by `Modules\ModuleServiceProvider`.
- Existing database tables and existing columns.
- Existing submission statuses:
  - `pending`
  - `need_supplement`
  - `approved`
  - `rejected`
- Existing public lookup-token hashing behavior.
- Existing receipt/session access behavior.
- Existing storage roots:
  - `administrative/submissions/...`
  - `administrative/templates/...`
- Existing file types and result-download scoping.
- Existing cross-module relationship from Administrative processing records to the canonical admin/user model currently used by the module.

No route, table, column, storage path, status value or Livewire alias may be renamed during this refactor.

## Database / Migration Impact

Default expectation: **no migration required**.

The existing history table should be reused where possible for archive audit.

A new migration is permitted only if implementation proves the existing status-history schema cannot represent an archive action safely. Historical migrations must not be rewritten.

If a migration becomes necessary, stop and document:

- exact new column/constraint required;
- backward compatibility;
- rollback behavior;
- migration test.

## Security and Authorization Impact

The refactor should improve security without widening access.

Requirements:

- Admin routes remain protected by `auth:admin` plus named capability permission.
- Sensitive Livewire mutations must authorize inside the action method.
- File downloads remain server-scoped by submission/file IDs and private storage.
- Public result files remain accessible only through a valid lookup session grant.
- Lookup failure messages remain non-enumerating.
- No browser-provided storage path is trusted.
- Existing rate limiting remains in place.

## Transactions and Data Integrity

Preserve current good behavior:

- `DB::transaction()` around multi-record writes.
- `lockForUpdate()` on admin processing workflows.
- optimistic `version` checks to prevent stale concurrent writes.
- cleanup of stored files when a transactional submission workflow fails.

Additional requirement:

- Archive audit/history and soft delete must succeed atomically.

## Performance Impact

Expected improvement:

- Remove unbounded `get()` triggered by `perPage = All`.
- Keep bounded pagination up to 100 rows.
- Preserve current eager loading on admin list/detail queries.

Out of scope for this refactor unless tests/profile prove necessary:

- full-text search infrastructure;
- Elasticsearch/Meilisearch;
- new caching layer;
- queued export infrastructure.

## Test Strategy

### Existing tests to preserve

- `AdministrativeDatabaseStructureTest.php`
- `AdministrativeLookupRouteTest.php`
- `AdministrativeProcedureRouteTest.php`
- `AdministrativePublicRouteTest.php`
- `AdministrativeSubmissionRouteTest.php`

### New focused tests

Add service/feature tests for:

1. Admin file download ownership and missing model regression.
2. Submission approve/reject/supplement state transitions.
3. Concurrent/stale-version rejection.
4. Archive/soft-delete audit behavior.
5. Bounded pagination and rejection/normalization of invalid `perPage` values.
6. Permission denial for sensitive mutations where practical through feature/Livewire tests.
7. Lookup/result-download scoping.

### Targeted verification commands

Expected local verification after implementation:

```bash
php artisan test tests/Feature/Administrative
vendor/bin/pint --test Modules/Administrative tests/Feature/Administrative
```

If UI Blade changes are material:

```bash
npm run build
```

Then run full regression when targeted tests pass:

```bash
php artisan test
```

## Acceptance Criteria

Implementation is complete only when:

- Admin file download no longer risks class-resolution failure.
- No Administrative admin table exposes an unbounded `All` option.
- Sensitive mutations retain named permission checks at the action boundary.
- Existing route names, statuses, database schema and storage paths remain compatible.
- Approve/reject/supplement transitions remain transactional and version-safe.
- Submission archive remains soft-delete and gains auditable actor/history behavior when representable without unsafe schema changes.
- Targeted Administrative tests pass.
- Pint passes for changed PHP files.
- UI build passes if relevant views/assets changed.
- Documentation is updated to match implemented reality.

## Rollback / Recovery Notes

- Correct model import: trivially reversible.
- Bounded pagination: reversible by restoring previous option, but intentionally retained for production safety.
- Permission normalization: do not remove old permissions during this refactor; rollback must leave current permission strings usable.
- Audit-history writes: additive only and must not change existing submission status.
- No destructive database migration is planned.
- No existing files or submissions will be physically deleted by the refactor.

## Explicit Non-Goals

This refactor will not:

- rebuild the Administrative module;
- replace Livewire architecture;
- introduce `nwidart/laravel-modules`;
- rename routes or Livewire aliases;
- redesign the entire admin UI;
- add import/export functionality;
- change public submission business fields;
- change the core status model;
- change storage provider/disk strategy;
- add search infrastructure or a new caching system;
- refactor unrelated modules;
- rewrite historical migrations.

## Implementation Order After Approval

```text
P1.1  Fix AdministrativeFileService model import + regression test
P1.2  Inventory/normalize Administrative permission boundaries + authorization tests
P1.3  Remove unbounded All pagination from Procedures/Submissions
P1.4  Add archive audit behavior while preserving soft delete
P1.5  Add workflow/concurrency/download/lookup regression tests
P2.1  Loading/disabled/destructive UX polish on affected screens
P2.2  Update ANALYSIS / INFORMATION / README / REFACTOR_PLAN
P2.3  Targeted tests + Pint + build when required + full regression
```

## Approval Gate

Status: **AWAITING USER APPROVAL**

No application source code may be modified until this plan is explicitly approved.
