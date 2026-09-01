# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: explicitly approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: explicitly approved by the user.
- MR-2 branch: `feat/attendance-persistence-foundation`.
- MR-2 implementation is complete on the branch and is awaiting local verification.

## MR-2 — Persistence / schema / models

Implemented scope:

- added Attendance-owned migrations for:
  - `attendance_locations`;
  - `attendance_shifts`;
  - `attendance_records`;
  - `attendance_adjustment_requests`;
  - `attendance_audit_events`;
- added persistence enums:
  - `AttendanceRecordStatus`;
  - `AdjustmentStatus`;
  - `VerificationResult`;
- added Eloquent models and Account/User relationships;
- preserved historical employee/user relationships through restrictive/null-on-delete FK choices and soft-deleted parent relations;
- added an idempotent `AttendanceDefaultsSeeder` for the default 08:00–17:00 shift;
- intentionally did not seed a fake attendance location or fake coordinates;
- updated `config/module.php` with all five Attendance-owned tables;
- added focused persistence contract tests.

## Persistence decisions carried from CREATE_PLAN

- `attendance_records.session_key` is unique and is the canonical future-safe session identity.
- No unique constraint exists on `(user_id, work_date)`.
- `attendance_records` stores immutable shift snapshot fields needed for historical calculations.
- Raw check-in/check-out coordinates and accuracy fields are nullable so the later retention cleanup can redact precise GPS without deleting business history.
- Persistent lifecycle states remain `checked_in`, `completed`, and `voided`; derived states are not stored as the primary lifecycle status.
- Adjustment states are `pending`, `approved`, and `rejected`.
- Audit history has a dedicated Attendance-owned table and does not require update/delete UI paths.
- Account `EmployeeProfile` remains canonical; Attendance creates no duplicate employee profile table.

## Explicitly not in MR-2

- check-in/check-out orchestration;
- geofence/Haversine calculation;
- shift resolution service;
- worked/late/early calculation service;
- adjustment approval service/business transactions;
- audit service writes;
- Admin routes/UI/Livewire;
- ClientPortal Attendance adapter;
- export;
- GPS retention job/scheduler integration.

## Verification gate

Run Pint on Attendance PHP changes, then focused tests:

```bash
vendor/bin/pint Modules/Attendance tests/Feature/Attendance

php artisan test \
  tests/Feature/Attendance/AttendanceModuleBootstrapTest.php \
  tests/Feature/Attendance/AttendancePersistenceContractTest.php

php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php

git diff --check
git status
```

If Pint changes tracked files, review and commit those changes before the MR-2 PR. No full-project suite is required by default unless verification reveals a shared-runtime impact.

## Manual acceptance

MR-2 has no business UI; no Admin/PWA manual UI acceptance is required for this phase.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `Modules/Attendance/config/module.php`
- `Modules/Attendance/config/attendance.php`
- `Modules/Account/Models/EmployeeProfile.php`
- `Modules/Account/Models/User.php`
- `Modules/ModuleServiceProvider.php`

## Next gate

Do not start MR-3 Attendance domain-core implementation until MR-2 verification passes, MR-2 is reviewed/merged, and the next phase is explicitly authorized.
