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
- MR-5 implementation is complete on the branch.
- Attendance migration recovery/orchestration corrective is verified at runtime: Attendance progressed from a consistent partial state to `RESUMABLE`, then migrated safely to `READY` with `5/5` owned tables and `5/5` canonical migration ledger entries; User and Account remained `READY`.
- Attendance default seeder PSR-4 casing was corrected to `Modules/Attendance/Database/Seeders/AttendanceDefaultsSeeder.php`; Linux autoload is verified and the default shift seeded successfully.
- Runtime module enable/disable behavior has been manually verified.
- Manual Admin UI acceptance has been reported as `UI PASS`.
- Vite production build has passed after the final corrective work.

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

## Corrective migration/runtime work completed inside MR-5

MR-5 runtime verification exposed two pre-existing infrastructure/integration defects and both were corrected conservatively:

- default `module:migrate` orchestration now uses canonical module lifecycle migration state rather than replaying dependency migrations from the legacy `module_migrations` ledger;
- a consistent interrupted migration prefix can be classified as `RESUMABLE`, while inconsistent schema/ledger states remain blocked for recovery;
- Attendance adjustment-request composite indexes now use explicit MySQL-safe names below the identifier length limit;
- Attendance default seeder path now matches its `Modules\Attendance\Database\Seeders` namespace on case-sensitive Linux filesystems.

Verified runtime results:

- focused migration/regression pack: `15 passed (57 assertions)`;
- Attendance migration status: `READY`, tables `5/5`, ledger `5/5`;
- User migration status: `READY`, tables `4/4`, ledger `5/5`;
- Account migration status: `READY`, tables `4/4`, ledger `5/5`;
- Attendance persistence/seeder contract: `5 passed (22 assertions)`;
- `class_exists('Modules\\Attendance\\Database\\Seeders\\AttendanceDefaultsSeeder') === true` on Linux;
- default shift seeded as `DEFAULT`, `08:00–17:00`, late grace `5`, early-leave grace `5`, default/active true;
- `git diff --check` passed and the working tree was clean after the PSR-4 corrective sync;
- Vite production build passed;
- runtime module toggle and Admin UI manually passed.

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
- full shift/location CRUD pages. MR-4 domain config services remain canonical and may be surfaced in a later dedicated Admin configuration workspace if required;
- destructive legacy `module:fresh` / refresh/delete migration semantics refactor. Those paths remain separate follow-up debt and were not broadened into this corrective.

## Final verification gate before PR

Run the final branch-level focused verification after pulling the handoff closeout commit:

```bash
vendor/bin/pint Modules/Attendance tests/Feature/Attendance \
  app/Modules/ModuleMigrationDiagnosis.php \
  app/Modules/ModuleLifecycleManager.php \
  app/Console/Commands/DiagnoseModuleMigrations.php \
  app/Console/Commands/Module/MigrateCommand.php \
  app/Modules/Migration/Services/ModuleMigrator.php \
  tests/Feature/System/ModuleLifecycleResumableMigrationTest.php \
  tests/Feature/System/ModuleMigrationOrchestrationContractTest.php

php artisan test tests/Feature/Attendance

php artisan test \
  tests/Feature/System/ModuleLifecycleResumableMigrationTest.php \
  tests/Feature/System/ModuleLifecycleMigrationRecoveryTest.php \
  tests/Feature/System/ModuleMigrationOrchestrationContractTest.php \
  tests/Feature/System/ModuleMigrationLedgerRepairerTest.php \
  tests/Feature/System/AccountMigrationRecoveryContractTest.php \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php

php artisan route:list --path=admin/attendance
npm run build
git diff --check
git status
```

The branch is ready for the MR-5 PR once this final post-closeout focused verification is green and the working tree is clean.

## Manual UI acceptance

`UI PASS` has been reported after runtime module enable/disable verification. The accepted surface includes:

- `/admin/attendance/dashboard`;
- `/admin/attendance/records`;
- responsive Admin presentation;
- bounded page-size behavior `10 / 25 / 50 / 100` with no `All`;
- search/filter/reset and paginator behavior;
- permission-aware correction, void and adjustment-review actions;
- confirmation/review modal behavior;
- return navigation;
- no precise GPS exposure in the records table.

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
- `Modules/Attendance/Database/Seeders/AttendanceDefaultsSeeder.php`
- `tests/Feature/Attendance/AttendanceAdminUiContractTest.php`
- `tests/Feature/Attendance/AttendancePersistenceContractTest.php`
- `tests/Feature/System/ModuleLifecycleResumableMigrationTest.php`
- `tests/Feature/System/ModuleMigrationOrchestrationContractTest.php`

## Next gate

Do not start MR-6 export until MR-5 is reviewed/merged and the next phase is explicitly authorized.
