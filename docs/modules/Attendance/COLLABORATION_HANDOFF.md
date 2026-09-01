# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 domain core: merged through PR #131.
- MR-4 adjustment/audit/Admin config: merged through PR #132.
- MR-5 Attendance Admin dashboard/records UI: explicitly approved by the user.
- MR-5 branch: `feat/attendance-admin-dashboard-records`.
- MR-5 implementation is complete on the branch and is awaiting local verification and manual Admin UI acceptance.

## MR-5 — Admin dashboard / records workspace

Implemented scope:

- Admin route `/admin/attendance/dashboard` named `admin.attendance.dashboard`, protected by `attendance.dashboard.view` on the `admin` guard;
- Admin route `/admin/attendance/records` named `admin.attendance.records`, protected by `attendance.record.view` on the `admin` guard;
- `AttendanceDashboardService` provides today's operational metrics: checked-in, completed, late, early leave, missing checkout, pending adjustments, plus five recent records;
- dashboard uses `Admin::layouts.master`, responsive KPI cards, recent activity, Records entry point and explicit return to Admin Dashboard;
- `AdminRecordsTable` is a class-based Livewire workspace with employee search, status/shift/location/date filters, reset behavior and bounded page-size normalization;
- canonical page-size options are `10 / 25 / 50 / 100`; no unbounded `All` option exists;
- module-scoped pagination explicitly renders white inactive controls, indigo current page and Livewire `previousPage` / `nextPage` / `gotoPage` actions;
- records table displays employee identity, work date/shift, check-in/out, worked/late/early minutes, locations, status and pending adjustment state;
- permission-aware action entry points call MR-4 domain services for manual time correction, auditable void, adjustment approve and adjustment reject;
- destructive void uses a centered confirmation modal with required reason; correction/review operations use modal workspaces with loading guards;
- backend authorization is repeated inside Livewire action methods; hiding buttons is not treated as authorization;
- filters/search/page size reset pagination where needed;
- focused source/autoload contract tests cover routes, permissions, service/component autoload, bounded pagination, Admin form visuals and permission-guarded domain actions.

## Domain / UI rules preserved

- Account employee identity remains canonical; Attendance does not duplicate employee records.
- Admin actions delegate canonical mutations to Attendance domain services rather than implementing business calculations in Blade/Livewire.
- Adjustment no-self-approval remains enforced by the MR-4 service.
- Void preserves the historical attendance row; no hard delete UI exists.
- Precise latitude/longitude are not displayed in the Admin records table.
- Records use historical shift snapshots for display/calculation continuity.
- Dashboard remains an operational overview rather than exposing every secondary function at once.
- Admin UI follows `.codex/standards/ADMIN_UI_STANDARD.md`, including visible form borders and bounded pagination.

## Explicitly not in MR-5

- Attendance export/import UI or export implementation (MR-6);
- ClientPortal/PWA Attendance adapter (MR-7);
- GPS retention cleanup/scheduler integration (MR-8);
- background tracking or offline official check-in/out;
- a second module registry/provider;
- full shift/location CRUD pages. MR-4 domain config services remain canonical and may be surfaced in a later dedicated Admin configuration workspace if required.

## Verification gate

```bash
vendor/bin/pint Modules/Attendance tests/Feature/Attendance

php artisan test \
  tests/Feature/Attendance/AttendanceModuleBootstrapTest.php \
  tests/Feature/Attendance/AttendancePersistenceContractTest.php \
  tests/Feature/Attendance/AttendanceDomainCoreTest.php \
  tests/Feature/Attendance/AttendanceAdjustmentAdminConfigTest.php \
  tests/Feature/Attendance/AttendanceAdminUiContractTest.php

php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php

php artisan route:list --path=admin/attendance

git diff --check
git status
```

If Pint changes tracked files, review and commit formatting before PR. Route visibility requires Attendance to be runtime-enabled with its Account dependency enabled; the source contract remains present while the module is disabled by design.

## Manual UI acceptance required

MR-5 introduces Admin business UI. Before PR merge, manually verify at minimum:

- `/admin/attendance/dashboard` on desktop and a narrow/mobile-width viewport;
- `/admin/attendance/records` table horizontal behavior and filter controls;
- page-size values `10 / 25 / 50 / 100` only, with no `All`;
- actual paginator surfaces are white/inactive and indigo/active after compiled Admin CSS;
- search/filter/reset behavior;
- permission visibility for correction, void and adjustment review;
- void confirmation and correction/review modals;
- return navigation to Attendance Dashboard and Admin Dashboard.

Report manual acceptance separately as `UI PASS` after verification.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `Modules/Attendance/routes/web.php`
- `Modules/Attendance/Http/Controllers/AttendanceDashboardController.php`
- `Modules/Attendance/Http/Controllers/AttendanceRecordsController.php`
- `Modules/Attendance/Services/AttendanceDashboardService.php`
- `Modules/Attendance/Livewire/AdminRecordsTable.php`
- `Modules/Attendance/resources/views/admin/dashboard.blade.php`
- `Modules/Attendance/resources/views/admin/records.blade.php`
- `Modules/Attendance/resources/views/livewire/admin-records-table.blade.php`
- `Modules/Attendance/resources/views/vendor/pagination/admin-attendance.blade.php`
- `tests/Feature/Attendance/AttendanceAdminUiContractTest.php`

## Next gate

Do not create the MR-5 PR until focused verification passes, the working tree is clean and manual Admin UI acceptance is reported.

Do not start MR-6 export until MR-5 is reviewed/merged and the next phase is explicitly authorized.
