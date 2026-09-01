# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 domain core: merged through PR #131.
- MR-4 adjustment/audit/Admin config: merged through PR #132.
- MR-5 Admin dashboard/records workspace: merged through PR #133.
- MR-6 Attendance Export: explicitly approved and implemented on branch `feat/attendance-export`.
- MR-6 manual Records/export acceptance has been reported as `UI PASS`.
- MR-6 has no schema/migration change and introduces no new package dependency.
- Do not start MR-7 ClientPortal/PWA adapter until MR-6 is reviewed/merged and the next phase is explicitly authorized.

## MR-6 — Attendance Export

Implemented scope:

- XLSX export uses the repository's existing `maatwebsite/excel` dependency; no new export infrastructure/package is introduced;
- `AttendanceRecordQueryService` is the shared canonical filter/query builder for both the Admin records workspace and export, preventing filter-semantic drift;
- shared filter normalization covers employee search, persistent status, shift, location, date range and bounded page size;
- `AdminRecordsTable` supports `exportFiltered()` for all records matching the active filter set;
- `AdminRecordsTable` supports `exportSelected()` for explicitly selected record rows;
- selected record IDs are normalized to positive unique stable IDs and are intersected with the current filtered query before export;
- backend authorization requires `attendance.export`; hiding export controls in Blade is not treated as authorization;
- the export UI is surfaced inside `/admin/attendance/records` and remains permission-aware;
- export fields cover employee identity/code, work date, historical shift snapshot, check-in/out, worked/late/early minutes, status and check-in/out location names;
- precise latitude, longitude, GPS accuracy and distance evidence are intentionally excluded from the XLSX contract;
- `AttendanceDemoSeeder` provides local/manual export acceptance data, reuses existing Account users, creates missing demo employee profiles without creating new user credentials/roles, seeds a demo office and idempotent attendance sessions;
- the demo seeder is test/local tooling and is not wired into production/default seeding.

## MR-6 verification recorded

Automated verification reported before final closeout:

- Pint: PASS for the MR-6 export implementation files;
- `AttendanceExportContractTest`: `6 passed (32 assertions)`;
- complete Attendance feature pack: `34 passed (169 assertions)`;
- `git diff --check`: PASS;
- branch working tree was clean and synchronized before the later demo-seeder closeout commits.

Manual runtime acceptance:

- demo Account employee profiles and Attendance records were prepared successfully after adapting the demo seeder to the local database, which had users but no `employee_profiles`;
- `/admin/attendance/records` was manually exercised with demo attendance data;
- Attendance export UI/runtime acceptance was reported as `UI PASS`.

Because `AttendanceDemoSeeder` was added/adjusted after the earlier automated gate, run the final focused gate below after pulling this handoff commit before opening the PR.

## Domain / privacy rules preserved

- Account remains the canonical owner of employee identity; Attendance references `EmployeeProfile` rather than introducing a duplicate employee model/table.
- Export uses Attendance historical shift snapshots so exported history is not rewritten by later shift configuration changes.
- Precise geolocation evidence remains persisted for Attendance verification/audit but is not exposed by the standard Admin XLSX export.
- No background location tracking is introduced.
- No hard-delete path is introduced.
- Existing MR-5 search/filter/pagination semantics remain canonical through the shared query service.
- Export authorization is enforced server-side with `attendance.export`.

## Explicitly not in MR-6

- ClientPortal/PWA Attendance application or check-in/check-out UI (MR-7);
- offline official check-in/out;
- Attendance API endpoints;
- GPS retention cleanup/scheduler integration (MR-8);
- import of historical attendance data;
- new schema/migrations;
- new third-party export package;
- production/default execution of `AttendanceDemoSeeder`.

## Final verification gate before PR

After pulling the handoff closeout commit, run:

```bash
vendor/bin/pint \
  Modules/Attendance/Exports \
  Modules/Attendance/Services/AttendanceRecordQueryService.php \
  Modules/Attendance/Livewire/AdminRecordsTable.php \
  Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php \
  tests/Feature/Attendance/AttendanceExportContractTest.php

php artisan test tests/Feature/Attendance/AttendanceExportContractTest.php
php artisan test tests/Feature/Attendance

npm run build
git diff --check
git status
```

Expected gate:

- Pint PASS;
- export focused tests PASS;
- Attendance regression PASS;
- Vite production build PASS because the Records Blade UI changed;
- `git diff --check` PASS;
- working tree clean on `feat/attendance-export`.

No destructive migration/reset command is required or authorized for MR-6.

## Manual UI acceptance

`UI PASS` has been reported for MR-6 after loading demo attendance data. Accepted surface includes:

- `/admin/attendance/records` remains functional;
- export controls are visible for an authorized Admin;
- filtered/selected export workflow is usable with demo records;
- existing Records UI remains operational after export integration.

## Canonical MR-6 sources

- `Modules/Attendance/Exports/AttendanceRecordsExport.php`
- `Modules/Attendance/Services/AttendanceRecordQueryService.php`
- `Modules/Attendance/Livewire/AdminRecordsTable.php`
- `Modules/Attendance/resources/views/livewire/admin-records-table.blade.php`
- `Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php`
- `tests/Feature/Attendance/AttendanceExportContractTest.php`
- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`

## Next gate

MR-6 may proceed to PR only after the final post-closeout focused verification above is green and the working tree is clean.

Do not start MR-7 ClientPortal/PWA Attendance adapter until MR-6 is reviewed/merged and the user explicitly authorizes the next phase.
