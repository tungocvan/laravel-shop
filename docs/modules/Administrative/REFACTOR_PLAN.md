# Administrative Refactor Plan

## Status

**COMPLETED / VERIFIED — 2026-08-15**

Local verification supplied by the user:

```text
vendor/bin/pint --test Modules/Administrative tests/Feature/Administrative
PASS — 47 files

php artisan test
PASS — 353 tests / 12,815 assertions
Duration: 22.73s
```

## Goal

Refactor `Modules/Administrative` safely from the verified `/analyze Administrative` findings while preserving existing public/admin contracts and avoiding a rebuild.

## Implemented Scope

```text
Modules/Administrative/**
tests/Feature/Administrative/**
docs/modules/Administrative/**
```

No database migration was required. No unrelated module was refactored.

## Completed Changes

### P1.1 — Admin file download correctness

Completed:

- Added `Modules\Administrative\Models\AdministrativeFile` import to `AdministrativeFileService`.
- Preserved submission/file ownership scoping and private Storage download behavior.
- Added regression contract coverage for the model resolution defect.

### P1.2 — Permission contract normalization

Completed with backward compatibility:

- Dashboard canonical permission: `administrative.dashboard.view`.
- Legacy fallback: `administrative.submission.view`.
- Approve/reject/request-supplement canonical permission: `administrative.submission.process`.
- Legacy fallback: `administrative.submission.edit`.
- History UI honors `administrative.history.view` with legacy submission-view fallback.
- Sensitive Livewire mutations continue to authorize inside action methods.
- Procedure edit/update/archive UI actions are permission-aware.

No existing permission string was removed.

### P1.3 — Bounded admin pagination

Completed:

- Removed `All` from procedure/submission table page-size options.
- Allowed sizes are now `10, 25, 50, 100`.
- Services normalize invalid page-size values server-side.
- Admin list services always return `LengthAwarePaginator`.
- Unbounded user-triggered `get()` branch was removed.

### P1.4 — Archive audit behavior

Completed:

- Added `SubmissionAction::Archived`.
- `softDeleteMany()` now accepts the acting admin ID.
- Each archived submission receives an audit history entry before soft delete.
- Audit includes actor and `soft_delete` metadata.
- History creation and soft delete remain inside the same transaction.
- Existing submission status value is not changed by archive.

### P1.5 — Regression coverage

Completed:

- Updated `AdministrativeSubmissionRouteTest` for the dashboard permission contract.
- Added `AdministrativeRefactorContractTest` covering:
  - file-service model import regression;
  - bounded admin page sizes;
  - paginator return contracts;
  - removal of `All` branches;
  - archive action/audit contract;
  - canonical processing permission plus legacy fallback.

The pre-existing Administrative route/schema tests remain in place.

### P2.1 — Admin UX polish

Completed for affected screens:

- Permission-aware action visibility.
- Loading/disabled states on approve/reject/supplement/archive/bulk archive.
- Clearer archive wording instead of implying physical deletion.
- Pagination UI no longer branches on `All`.

## Compatibility Preserved

The refactor preserved:

- Public route URLs and names.
- Admin route URLs and names.
- Existing tables/columns.
- Existing status values: `pending`, `need_supplement`, `approved`, `rejected`.
- Public lookup hashing/session semantics.
- Existing storage roots.
- Existing Livewire aliases.
- Existing legacy permission strings through fallback compatibility.
- Soft delete rather than physical deletion.

## Database / Migration Impact

**None.**

The existing status-history schema was sufficient for the archive audit action, so no migration or historical migration rewrite was necessary.

## Security / Data Integrity Result

Post-refactor guarantees retained or improved:

- `auth:admin` + named permission boundaries remain.
- Private file storage remains controlled by server routes.
- File download remains submission-scoped.
- Public lookup remains non-enumerating and rate limited.
- Processing still uses transaction + `lockForUpdate()` + optimistic version checks.
- Archive audit and soft delete are atomic.
- Unbounded admin list loading is removed.

## Verification

Verified locally on 2026-08-15:

```bash
vendor/bin/pint --test Modules/Administrative tests/Feature/Administrative
```

Result:

```text
PASS — 47 files
```

Full application regression:

```bash
php artisan test
```

Result:

```text
353 passed
12,815 assertions
0 failed
Duration: 22.73s
```

## Acceptance Criteria

- [x] Admin file download class-resolution defect fixed.
- [x] No unbounded `All` page-size option remains.
- [x] Permission contract normalized with backward-compatible fallback.
- [x] Sensitive mutations authorize at action boundary.
- [x] Archive remains soft delete and is auditable.
- [x] Route/status/schema/storage contracts preserved.
- [x] Regression contract tests added.
- [x] Pint PASS.
- [x] Full regression PASS.
- [x] Documentation updated to implemented reality.

## Remaining Non-Blocking Improvements

Not required to close this refactor:

- Deeper behavioral tests for real upload MIME rejection/cleanup.
- Explicit service tests for each approve/reject/supplement concurrency path.
- Lookup-session expiry/result-file integration tests.
- Search optimization after production profiling.
- Formal `Account` dependency declaration if the repository standardizes manifest dependencies.

## Final Decision

`Modules/Administrative` remains a **Major Refactor success; Full Rebuild is not warranted**.

The module is ready to proceed through the repository's normal branch review/merge workflow.
