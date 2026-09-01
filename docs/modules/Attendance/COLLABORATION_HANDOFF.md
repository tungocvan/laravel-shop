# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 domain core: merged through PR #131.
- MR-4 adjustment/audit/Admin config: explicitly approved by the user.
- MR-4 branch: `feat/attendance-adjustment-admin-config`.
- MR-4 implementation and focused verification are complete; ready for PR review.

## MR-4 — Adjustment / audit / Admin config foundation

Implemented scope:

- `AttendanceAdjustmentService` implements submit, approve, and reject lifecycle around `pending -> approved|rejected`;
- adjustment review runs inside transactions and locks request/record rows before canonical mutation;
- self-approval is explicitly rejected;
- approval applies requested check-in/check-out values to the canonical `AttendanceRecord` and recalculates worked/late/early minutes from immutable shift snapshots;
- rejection requires an explicit review note;
- `AttendanceRecordMaintenanceService` implements auditable void and manual time correction workflows;
- void requires a reason and changes lifecycle to `voided` without hard deleting history;
- manual correction refuses voided records and recalculates metrics from shift snapshots;
- `AttendanceAdminConfigService` validates shift grace values, enforces one default shift by demoting prior defaults, validates coordinate ranges, and bounds geofence radius/accuracy;
- audit actions cover adjustment submit/approve/reject, record void, and manual correction;
- focused MR-4 contract tests cover transaction/locking, no-self-approval, recalculation, no-hard-delete, audit action names, config bounds, and service autoloading.

## Domain rules preserved

- Account `EmployeeProfile` remains canonical employee identity.
- Adjustment approval changes canonical Attendance data only through Attendance domain services.
- No self-approval.
- No hard delete of attendance history.
- Shift snapshots remain historical calculation authority.
- Precise GPS data remains excluded from generic audit JSON by the existing `AttendanceAuditService` sanitizer.
- MR-4 adds Admin configuration business services only; it does not add full Admin dashboard/records/config UI.

## Explicitly not in MR-4

- full Admin dashboard/records/config routes and UI;
- ClientPortal/PWA Attendance adapter;
- export;
- GPS retention cleanup/scheduler integration;
- background tracking or offline official check-in/out.

## Verification results

Formatting / repository checks:

- Pint on `Modules/Attendance` and `tests/Feature/Attendance`: PASS after one formatting-only normalization in `AttendanceAdjustmentAdminConfigTest.php`.
- Formatting-only commit: `8427cd3b` (`style(attendance): normalize adjustment tests`).
- `git diff --check`: PASS in the final local verification flow.
- Working tree: clean and up to date with `origin/feat/attendance-adjustment-admin-config`.

Attendance focused tests:

```text
php artisan test \
  tests/Feature/Attendance/AttendanceModuleBootstrapTest.php \
  tests/Feature/Attendance/AttendancePersistenceContractTest.php \
  tests/Feature/Attendance/AttendanceDomainCoreTest.php \
  tests/Feature/Attendance/AttendanceAdjustmentAdminConfigTest.php
Tests: 23 passed (106 assertions)
Duration: 2.08s
```

Directly impacted System/module-runtime tests:

```text
php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
Tests: 13 passed (52 assertions)
Duration: 0.99s
```

No full-project regression was run because MR-4 is scoped to Attendance adjustment/maintenance/config services plus existing shared module-runtime contracts.

## Manual acceptance

MR-4 contains no full Admin business UI and no PWA UI, so no manual UI acceptance is required for this phase.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `Modules/Attendance/Services/AttendanceAdjustmentService.php`
- `Modules/Attendance/Services/AttendanceRecordMaintenanceService.php`
- `Modules/Attendance/Services/AttendanceAdminConfigService.php`
- `Modules/Attendance/Services/AttendanceAuditService.php`
- `Modules/Attendance/Services/AttendanceCalculationService.php`

## Next gate

MR-4 is ready for pull-request review and merge.

Do not start MR-5 Admin dashboard/records UI until MR-4 is reviewed/merged and the next phase is explicitly authorized.
