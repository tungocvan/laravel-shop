# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 Attendance domain core: explicitly approved by the user.
- MR-3 branch: `feat/attendance-domain-core`.
- MR-3 implementation and focused verification are complete; ready for PR review.

## MR-3 — Attendance domain core

Implemented scope:

- `ShiftResolver` resolves exactly one active default shift and produces immutable shift snapshots;
- overnight shifts use the shift-start business date for `work_date`;
- `GeofenceService` validates coordinate ranges/accuracy, resolves eligible locations server-side, calculates Haversine distance, and returns canonical verification results;
- `AttendanceCalculationService` calculates `worked_minutes`, `late_minutes`, and `early_leave_minutes` from server-authoritative timestamps and shift snapshots;
- `AttendanceService` implements check-in/check-out orchestration around the canonical Account `EmployeeProfile`;
- check-in/check-out use server time as the official event time while preserving device `captured_at` only as evidence;
- deterministic SHA-256 `session_key` provides retry/double-submit idempotency without introducing a unique `(user_id, work_date)` constraint;
- mutations run inside database transactions and use `lockForUpdate()` for existing attendance sessions;
- a unique-key race during check-in reloads the canonical session instead of creating a duplicate;
- check-out calculations are reconstructed from persisted shift snapshots rather than mutable current shift configuration;
- `AttendanceAuditService` writes append-only attendance audit events and strips precise latitude/longitude from generic audit JSON payloads;
- focused MR-3 tests cover day/overnight shift resolution, calculations, Haversine behavior, deterministic session identity, transaction/locking/audit contracts, and coordinate privacy guardrails.

## Domain rules preserved

- Attendance depends on canonical Account employee identity; no duplicate employee table/profile exists.
- Only persisted active employee profiles linked to a user are eligible for official attendance mutations.
- Geofence verification must be `verified` before check-in/check-out can mutate canonical attendance state.
- Client-provided location IDs, distance, verification result, and device clock are never authoritative inputs.
- Persistent lifecycle remains `checked_in -> completed`; `voided` sessions cannot be reused.
- Duplicate check-in and duplicate check-out are idempotent for the same canonical session.
- Multiple future shifts on the same work date remain possible because session identity includes employee + work date + shift code rather than user/date uniqueness.

## Explicitly not in MR-3

- adjustment submit/review/approval transactions;
- record void/manual-correction workflows;
- Admin routes/dashboard/records/config UI;
- ClientPortal/PWA Attendance adapter;
- export;
- GPS retention cleanup/scheduler integration;
- background tracking or offline official check-in/out.

## Verification results

Attendance focused tests:

```text
php artisan test \
  tests/Feature/Attendance/AttendanceModuleBootstrapTest.php \
  tests/Feature/Attendance/AttendancePersistenceContractTest.php \
  tests/Feature/Attendance/AttendanceDomainCoreTest.php
Tests: 18 passed (80 assertions)
Duration: 1.05s
```

Directly impacted System/module-runtime tests:

```text
php artisan test \
  tests/Feature/System/ModuleCatalogRegistryTest.php \
  tests/Feature/System/ModuleStateResolverTest.php \
  tests/Feature/System/ModuleGraphValidatorTest.php \
  tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
Tests: 13 passed (52 assertions)
Duration: 0.93s
```

- Working tree after focused verification: clean and up to date with `origin/feat/attendance-domain-core`.
- A test-only string interpolation bug in `AttendanceDomainCoreTest` was corrected before the final PASS run; no domain behavior changed in that correction.
- No full-project regression was run because MR-3 is scoped to Attendance domain services plus the existing module-runtime contracts.

## Manual acceptance

MR-3 has no Admin or PWA business UI, so no manual UI acceptance is required for this phase.

## Canonical sources

- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `Modules/Attendance/Models/*`
- `Modules/Attendance/Services/ShiftResolver.php`
- `Modules/Attendance/Services/GeofenceService.php`
- `Modules/Attendance/Services/AttendanceCalculationService.php`
- `Modules/Attendance/Services/AttendanceService.php`
- `Modules/Attendance/Services/AttendanceAuditService.php`

## Next gate

MR-3 is ready for pull-request review and merge.

Do not start MR-4 adjustment/audit/Admin configuration work until MR-3 is reviewed/merged and the next phase is explicitly authorized.
