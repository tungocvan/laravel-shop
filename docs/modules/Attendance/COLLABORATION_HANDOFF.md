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
- Attendance local demo & Admin operations follow-up: merged through PR #136.
- MR-8 privacy/release readiness branch: `feat/attendance-privacy-release-readiness`.
- MR-8 implementation and automated release-readiness gate: COMPLETE / PASS locally on 2026-09-01.
- MR-8 is ready for final diff review and PR preparation; do not start another Attendance scope automatically.

## MR-8 — Privacy and release-readiness scope

### Raw employee GPS retention

Attendance uses precise employee GPS only as short-lived geofence verification evidence for explicit check-in/check-out actions.

Approved policy implemented by MR-8:

- default raw employee GPS retention: **30 days**;
- deployment override: `ATTENDANCE_RAW_GPS_RETENTION_DAYS`;
- intended operational choices are 7 / 30 / 90 days, with 30 days as the privacy-minimized default;
- cleanup nulls check-in/check-out latitude, longitude, accuracy and captured-at after retention expiry;
- cleanup preserves canonical Attendance records, employee/session identity, resolved Attendance location, official check-in/out timestamps, geofence verification result, distance and calculated work/late/early facts;
- office/location coordinates remain configuration because they describe company geofences rather than employee location history;
- no continuous/background GPS tracking is introduced.

`AttendancePrivacyRetentionService` owns cleanup behavior and `attendance:privacy-purge` exposes the operation through the module console convention.

The repository's canonical scheduler in `routes/console.php` runs the purge daily at 02:30 with `withoutOverlapping()` while Attendance is enabled. No parallel scheduler infrastructure is introduced.

Behavioral retention tests prove expired raw GPS is removed, recent GPS is retained and repeated cleanup is idempotent.

### Audit privacy hardening

`AttendanceAuditService` recursively sanitizes generic audit payloads so precise/raw GPS fields are not copied into nested audit JSON.

The sanitizer excludes latitude, longitude, GPS accuracy and capture timestamps while preserving non-precise business/audit facts.

### Production demo isolation

Attendance demo routes are registered only in `local` / `testing` environments.

The existing local/testing service/controller guards remain defense in depth. Production therefore does not expose the Attendance demo seed/reset route surface.

### Optional geocoding hardening

Admin location geocoding remains an optional configuration helper and is not part of employee check-in/check-out verification.

MR-8 adds configuration for:

- enable/disable;
- provider endpoint;
- request timeout.

Provider connection failures are converted to a controlled domain error. Provider response data is reduced to latitude, longitude and display name; arbitrary provider payload is not returned by the Attendance service.

Admins retain manual latitude/longitude entry and explicit one-shot browser geolocation when geocoding is disabled or unavailable.

## Privacy boundaries after MR-8

- Attendance is not an employee tracking system.
- GPS is requested only for explicit attendance actions or explicit Admin location-configuration helpers.
- No `watchPosition()` or background location collection is introduced.
- Precise employee GPS is not exposed in ordinary Attendance UI, export or generic audit payloads.
- Raw employee GPS is short-lived evidence with a 30-day default retention policy.
- Attendance business history remains available after raw GPS cleanup.
- Attendance remains canonical owner of attendance rules/persistence; direct dependency remains `Attendance -> Account`.
- ClientPortal remains a thin consumer; no `Attendance -> ClientPortal` dependency exists.

## MR-8 automated acceptance

Local gate reported on 2026-09-01:

```text
AttendanceReleaseReadinessTest : 5 passed / 15 assertions
Attendance regression          : 49 passed / 251 assertions
ClientPortal impacted           : 6 passed / 36 assertions
System impacted                 : 184 passed / 1055 assertions
Vite production build           : PASS
Working tree                    : clean / synchronized with origin branch
```

Additional privacy behavioral gate:

```text
AttendancePrivacyRetentionTest : 2 passed / 24 assertions
Attendance regression          : 44 passed / 236 assertions (before release-readiness tests were added)
```

Scheduler verification also confirmed:

```text
30 2 * * *  php artisan attendance:privacy-purge
```

Manual `attendance:privacy-purge` execution completed successfully with zero expired records in the current local dataset.

## Canonical MR-8 files

- `Modules/Attendance/config/attendance.php`
- `Modules/Attendance/Services/AttendancePrivacyRetentionService.php`
- `Modules/Attendance/Console/PurgeExpiredRawGps.php`
- `Modules/Attendance/Services/AttendanceAuditService.php`
- `Modules/Attendance/Services/AttendanceGeocodingService.php`
- `Modules/Attendance/routes/web.php`
- `routes/console.php`
- `tests/Feature/Attendance/AttendancePrivacyRetentionTest.php`
- `tests/Feature/Attendance/AttendanceReleaseReadinessTest.php`
- `tests/Feature/Attendance/AttendanceAdminOperationsContractTest.php`
- `tests/Feature/Attendance/AttendanceDomainCoreTest.php`
- `tests/Feature/Attendance/AttendanceModuleBootstrapTest.php`

## Final MR-8 PR gate

Before creating the PR, review the branch diff against `main` and confirm that canonical Attendance documentation reflects the 30-day privacy policy rather than the superseded 12-month baseline.

No destructive migration/reset command is required. MR-8 adds no Attendance schema migration for GPS retention because the raw GPS evidence columns are already nullable.

After MR-8 merges, stop. Determine any next Attendance phase with the user and require a new explicit approval before implementation.
