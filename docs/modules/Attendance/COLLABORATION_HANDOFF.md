# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: explicitly approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: explicitly approved by the user.
- MR-2 branch: `feat/attendance-persistence-foundation`.
- MR-2 implementation and focused verification: complete; ready for PR review.

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

## Verification results

Formatting / repository checks:

- Pint on `Modules/Attendance` and `tests/Feature/Attendance`: PASS; one test file received formatting-only normalization and was committed to the branch.
- `git diff --check`: PASS in the local verification flow before final branch sync.

Attendance focused tests:

```text
php artisan test \
  tests/Feature/Attendance/AttendanceModuleBootstrapTest.php \
  tests/Feature/Attendance/AttendancePersistenceContractTest.php
Tests: 10 passed (55 assertions)
Duration: 0.73s
```

Directly impacted System/module-runtime tests:

```text
php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
Tests: 13 passed (52 assertions)
Duration: 0.86s
```

The MR-1 bootstrap assertion for `tables=[]` was intentionally advanced in MR-2 to assert the five canonical Attendance-owned tables.

No full-project regression was run because MR-2 is scoped to Attendance persistence plus existing module-runtime contracts.

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

MR-2 is ready for pull-request review and merge.

Do not start MR-3 Attendance domain-core implementation until MR-2 is merged and the next phase is explicitly authorized.
