# Attendance — Collaboration Handoff

## Current status

- Requirements/specification: merged through PR #127.
- Create plan: merged through PR #128.
- MR-1 bootstrap/runtime contract: merged through PR #129.
- MR-2 persistence/schema/models: merged through PR #130.
- MR-3 domain core: merged through PR #131.
- MR-4 adjustment/audit/Admin config: merged through PR #132.
- MR-5 Admin dashboard/records workspace: merged through PR #133.
- MR-6 Attendance Export: merged through PR #134.
- MR-7 ClientPortal/PWA Attendance adapter: merged through PR #135.
- Current follow-up MR: `feat/attendance-local-demo-admin-ops` — implementation complete and manual UI acceptance reported as `UI PASS`; final automated closeout gate remains to be run locally before PR.
- MR-8 privacy/release readiness is not started and is not authorized by this handoff.

## Attendance local demo & Admin operations — implemented scope

### Admin location management

- `/admin/attendance/locations` exposes create/update location management behind `attendance.location.view` / `attendance.location.manage`.
- Admin can configure name, code, address, latitude, longitude, geofence radius, maximum accepted GPS accuracy, active state and check-in/check-out availability.
- Location forms follow the Admin UI field hierarchy with explicit labels, full-width bounded inputs, helper text and responsive grouping.
- Existing locations are rendered as clearly identified edit cards rather than unlabeled coordinate/value grids.
- Address is persisted as nullable metadata through a forward-only migration; existing Attendance location data is preserved.
- Browser geolocation is available only on explicit Admin action through `navigator.geolocation.getCurrentPosition()` with high-accuracy preference; no `watchPosition()` or background tracking is introduced.
- Address geocoding is available through a dedicated Admin endpoint and `AttendanceGeocodingService` boundary.
- Current geocoding provider is OpenStreetMap Nominatim over Laravel HTTP client; provider URL is isolated in the service so Attendance UI/domain code does not depend directly on provider details.
- Geocoding fills the form coordinates for Admin review and does not automatically persist a location.

### Admin shift management

- `/admin/attendance/shifts` exposes create/update shift management behind `attendance.shift.view` / `attendance.shift.manage`.
- Admin can configure name/code, start/end times, late grace, early-leave grace, active state and default state.
- UI uses the same labeled/responsive field contract as Location management.
- Existing historical attendance shift snapshots remain unchanged by editing current shift configuration.

### Local demo operations

- `/admin/attendance/demo-operations` is available only in `local` / `testing` and remains behind authenticated Admin/Attendance permission boundaries.
- Demo seeding covers representative completed, late, early-leave, missing-checkout and voided Attendance records plus pending/approved/rejected adjustment examples.
- `DEMO-HQ` uses `firstOrCreate`; re-seeding does not overwrite an existing locally adjusted demo geofence configuration.
- Demo reset is Attendance-scoped and targets demo Attendance records/adjustments/audit evidence only.
- Demo reset does not delete Account users or `EmployeeProfile` rows and does not invoke destructive schema reset/migration flows.
- Admin reset requires an explicit confirmation action in the UI.

## Architecture and privacy boundaries preserved

- Attendance remains the canonical owner of attendance locations, shifts, records, adjustments, audit and geofence rules.
- Direct domain dependency remains `Attendance -> Account`; no reverse `Attendance -> ClientPortal` dependency is introduced.
- MR-7 ClientPortal check-in/check-out behavior is unchanged by this follow-up MR.
- Server-side Attendance geofence validation remains authoritative; Admin helper geolocation/geocoding only assists location configuration.
- No continuous/background GPS tracking is introduced.
- Precise GPS evidence is not added to ordinary Admin list/report surfaces by this MR.
- No new Composer/NPM package is introduced.
- The only schema expansion in this follow-up is nullable `attendance_locations.address`, added through a new forward migration rather than editing the already-run base migration.

## Manual acceptance

Manual UI acceptance has been reported as `UI PASS` for the Admin Attendance configuration work, including the revised Location/Shift layouts and the location coordinate helpers.

The acceptance flow includes:

- Location form renders with correct field hierarchy and responsive inputs;
- Shift form renders with correct field hierarchy and responsive inputs;
- address can be used to request coordinates through the geocoding action;
- current browser position can populate latitude/longitude on explicit Admin request;
- geofence radius/GPS accuracy/status controls remain editable;
- no automatic save occurs merely from resolving coordinates.

## Final local closeout gate

Run this gate on `feat/attendance-local-demo-admin-ops` after pulling the latest handoff commit:

```bash
vendor/bin/pint \
  Modules/Attendance/Http/Controllers/AttendanceLocationsController.php \
  Modules/Attendance/Http/Controllers/AttendanceShiftsController.php \
  Modules/Attendance/Http/Controllers/AttendanceDemoOperationsController.php \
  Modules/Attendance/Models/AttendanceLocation.php \
  Modules/Attendance/Services/AttendanceGeocodingService.php \
  Modules/Attendance/Services/AttendanceDemoDataService.php \
  Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php \
  Modules/Attendance/database/migrations/2026_09_01_190001_add_address_to_attendance_locations_table.php \
  tests/Feature/Attendance/AttendanceAdminOperationsContractTest.php

php artisan test tests/Feature/Attendance/AttendanceAdminOperationsContractTest.php
php artisan test tests/Feature/Attendance
php artisan test tests/Feature/System

php artisan route:list --path=admin/attendance
npm run build
git diff --check
git status
```

Expected closeout:

- Pint PASS;
- Attendance Admin operations contract PASS;
- Attendance regression PASS;
- impacted System regression PASS;
- Admin Attendance routes include dashboard, records, locations, location geocode, shifts and local demo operations;
- Vite production build PASS;
- `git diff --check` PASS;
- working tree clean and synchronized with `origin/feat/attendance-local-demo-admin-ops`.

Do not run destructive migration/reset commands for this gate. The nullable address migration should already have been applied during the accepted local UI test.

## Canonical sources for this follow-up MR

- `Modules/Attendance/Http/Controllers/AttendanceLocationsController.php`
- `Modules/Attendance/Http/Controllers/AttendanceShiftsController.php`
- `Modules/Attendance/Http/Controllers/AttendanceDemoOperationsController.php`
- `Modules/Attendance/Services/AttendanceGeocodingService.php`
- `Modules/Attendance/Services/AttendanceDemoDataService.php`
- `Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php`
- `Modules/Attendance/Models/AttendanceLocation.php`
- `Modules/Attendance/resources/views/admin/locations.blade.php`
- `Modules/Attendance/resources/views/admin/shifts.blade.php`
- `Modules/Attendance/resources/views/admin/demo-operations.blade.php`
- `Modules/Attendance/resources/views/admin/dashboard.blade.php`
- `Modules/Attendance/database/migrations/2026_09_01_190001_add_address_to_attendance_locations_table.php`
- `tests/Feature/Attendance/AttendanceAdminOperationsContractTest.php`

## Next gate

This follow-up MR may proceed to PR after the final local closeout gate above is green and the working tree is clean.

After merge, stop and determine the next scope with the user. Do not start MR-8 privacy/release readiness automatically.
