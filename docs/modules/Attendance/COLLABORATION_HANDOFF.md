# Attendance — Collaboration Handoff

## Current status

- `docs/modules/Attendance/REQUIREMENTS.md`: approved and merged through PR #127.
- `docs/modules/Attendance/CREATE_PLAN.md`: approved and merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 domain core: merged through PR #131.
- MR-4 adjustment/audit/Admin config: merged through PR #132.
- MR-5 Admin dashboard/records workspace: merged through PR #133.
- MR-6 Attendance Export: merged through PR #134.
- MR-7 ClientPortal/PWA Attendance adapter: explicitly approved, implemented and manually accepted on branch `feat/clientportal-attendance-pwa`.
- MR-7 introduces no schema/migration changes and no new third-party package.
- Manual UI/PWA acceptance for MR-7 has been reported as `UI PASS`.
- Do not start MR-8 or any follow-up Admin/demo operations MR until MR-7 is reviewed/merged and the next scope is explicitly authorized.

## MR-7 — ClientPortal/PWA Attendance adapter

Implemented scope:

- ClientPortal application added under `Modules/ClientPortal/Applications/Attendance/`;
- app key is `attendance`, source module is canonical `Attendance`, and application visibility follows the existing ClientPortal application registry/module-enabled boundary;
- route surface is `/apps/attendance` with authenticated ClientPortal middleware and feature/permission boundaries;
- PWA/mobile-first attendance dashboard shows current work date, resolved shift snapshot, current attendance state, allowed attendance locations and check-in/check-out action;
- browser geolocation is requested only when the user taps a check-in/check-out action;
- no `watchPosition`, background tracking or continuous location collection is introduced;
- official attendance actions are online-only; offline submission is blocked in the client instead of being queued as an official attendance event;
- location evidence sent by the client is delegated to canonical `AttendanceService` geofence verification; ClientPortal does not duplicate attendance/geofence calculations;
- server time remains authoritative for check-in/check-out timestamps;
- users without a canonical Account `EmployeeProfile` are shown an explicit unavailable state; ClientPortal does not create employee identity automatically;
- attendance history is scoped to the authenticated user, paginated, and does not expose precise latitude/longitude fields;
- adjustment submission is scoped to the authenticated user's own Attendance records and delegates to canonical `AttendanceAdjustmentService`;
- Attendance domain errors exposed to ClientPortal are mapped to user-friendly Vietnamese messages;
- attendance record statuses are localized in the ClientPortal UI (`Đã vào ca`, `Hoàn thành`, `Đã hủy`);
- attendance adjustment statuses are localized (`Chờ duyệt`, `Đã duyệt`, `Từ chối`).

## MR-7 permissions and boundaries

The adapter uses the already-approved web permissions owned by Attendance:

- `client.attendance.access`
- `attendance.record.view-own`
- `attendance.check-in`
- `attendance.check-out`
- `attendance.adjustment.create`

Architecture boundaries preserved:

- `Attendance -> Account` remains the domain dependency for employee identity;
- ClientPortal consumes Attendance services/models through the adapter;
- no reverse dependency `Attendance -> ClientPortal` was introduced;
- no public Attendance API was introduced;
- no new persistence/schema ownership was introduced in ClientPortal;
- precise GPS evidence remains an Attendance-domain concern and is not rendered in ClientPortal history.

## MR-7 verification recorded

Automated verification reported during implementation:

- Pint: PASS for Attendance ClientPortal adapter/test files after the contract-test corrective;
- `AttendanceApplicationContractTest`: `6 passed (36 assertions)`;
- Attendance regression: `34 passed (169 assertions)`;
- `/apps/attendance` route surface: 6 routes present when Attendance is enabled;
- Vite production build: PASS;
- `git diff --check`: PASS;
- branch was clean and synchronized after the contract-test corrective.

After the later localization corrective commits, run the final focused gate below before opening the PR.

## Manual UI/PWA acceptance

Manual acceptance on the local environment has been reported as `UI PASS`.

Verified end-to-end behavior includes:

- Attendance app renders correctly in the ClientPortal/PWA shell on a mobile viewport;
- user without `EmployeeProfile` is blocked with a clear message;
- after linking the local test user to an active canonical Account `EmployeeProfile`, the default shift resolves correctly;
- geofence rejection was observed before local demo location alignment, proving backend location verification remains authoritative;
- local demo location was aligned to the tester's device location for acceptance without changing schema/business logic;
- check-in succeeded and the UI transitioned to the checked-in state;
- check-out succeeded and the attendance session transitioned to completed;
- history displayed the authenticated user's completed record with check-in/check-out times and location names, without precise GPS coordinates;
- adjustment request submission succeeded and appeared in the recent-request list in pending state;
- localized status labels and localized ClientPortal-facing attendance errors were manually accepted.

Local acceptance data changes were environment-only and are not repository schema/config changes.

## Explicitly not in MR-7

- offline official check-in/out queueing or later synchronization;
- background/continuous location tracking;
- public Attendance API endpoints;
- new Attendance schema/migrations;
- new third-party packages;
- Admin location/shift management expansion;
- local demo reset/delete tooling;
- Admin destructive demo cleanup actions;
- GPS evidence retention automation and MR-8 release/privacy readiness work.

## Follow-up proposal after MR-7 merge

A separate Attendance local/demo/Admin operations MR has been discussed and accepted in principle for local development/testing. It should remain separate from MR-7 and requires its own explicit implementation authorization after MR-7 is merged.

Candidate scope for that later MR:

- richer local demo data covering on-time, late, early leave, missing checkout, completed, voided and adjustment lifecycle cases;
- repeatable local-only demo seed/reset commands;
- Admin-facing location and shift configuration surfaces if current Admin exposure is incomplete;
- local/testing-only demo cleanup guarded by environment and Admin authorization;
- no deletion of Account users/employee profiles and no destructive schema reset.

## Final verification gate before MR-7 PR

After pulling this handoff closeout commit, run:

```bash
vendor/bin/pint \
  Modules/ClientPortal/Applications/Attendance \
  tests/Feature/ClientPortal/AttendanceApplicationContractTest.php

php artisan test tests/Feature/ClientPortal/AttendanceApplicationContractTest.php
php artisan test tests/Feature/ClientPortal
php artisan test tests/Feature/Attendance

php artisan route:list --path=apps/attendance
npm run build
git diff --check
git status
```

Expected gate:

- Pint PASS;
- Attendance ClientPortal contract PASS;
- ClientPortal focused regression PASS;
- Attendance regression PASS;
- 6 Attendance ClientPortal routes present when Attendance is enabled;
- Vite production build PASS;
- `git diff --check` PASS;
- working tree clean on `feat/clientportal-attendance-pwa`.

No destructive migration/reset command is required or authorized for MR-7.

## Canonical MR-7 sources

- `Modules/ClientPortal/Applications/Attendance/manifest.php`
- `Modules/ClientPortal/Applications/Attendance/routes.php`
- `Modules/ClientPortal/Applications/Attendance/Http/Controllers/AttendanceApplicationController.php`
- `Modules/ClientPortal/resources/views/applications/attendance/dashboard.blade.php`
- `Modules/ClientPortal/resources/views/applications/attendance/history.blade.php`
- `Modules/ClientPortal/resources/views/applications/attendance/adjustments.blade.php`
- `tests/Feature/ClientPortal/AttendanceApplicationContractTest.php`
- `Modules/Attendance/Services/AttendanceService.php`
- `Modules/Attendance/Services/AttendanceAdjustmentService.php`
- `docs/modules/Attendance/REQUIREMENTS.md`
- `docs/modules/Attendance/CREATE_PLAN.md`

## Next gate

MR-7 may proceed to PR only after the final post-closeout focused verification above is green and the working tree is clean.

Do not start MR-8 or the separate Attendance local/demo/Admin operations MR until MR-7 is reviewed/merged and the user explicitly authorizes the next phase.
