# Attendance Module — Idea / Business Specification

## 1. Identity

- Technical module name: `Attendance`
- Vietnamese UI name: `Chấm công`
- Proposed module type: `domain`
- Initial runtime default: disabled until configured and accepted
- Intended consumer surface: Admin + ClientPortal PWA adapter

Attendance is the canonical owner of attendance/check-in/check-out business logic and persistence.

ClientPortal may expose an Attendance PWA application, but Attendance must not depend on ClientPortal. ClientPortal must only consume the public/domain capabilities exposed by Attendance.

## 2. Purpose

Provide an employee attendance system where an authenticated employee can arrive at the company, open the installed/web PWA, verify location, check in, later check out, review personal attendance history, and request corrections when attendance is incomplete or incorrect.

The module must also provide an Admin/HR workspace for attendance operations, configuration, review, audit, search/filtering, and reporting/export where appropriate.

Payroll, salary calculation, continuous employee tracking, and biometric attendance are explicitly outside the initial release.

## 3. Actors

### Employee

Can:

- view today's attendance state and assigned/default shift;
- check in;
- check out;
- view own attendance history;
- submit an attendance adjustment request;
- view adjustment status.

Cannot directly edit canonical attendance records or approve their own adjustment requests.

### Attendance Admin / HR

Can, subject to backend authorization:

- view attendance dashboard and records;
- search/filter attendance;
- review attendance evidence;
- approve/reject adjustment requests;
- manage attendance locations;
- manage work shifts/policies;
- export approved attendance data where applicable;
- inspect audit history.

### Manager

Manager-scoped attendance review is a SHOULD/FUTURE capability unless repository analysis proves an existing organization/manager scope should be reused in the first release.

## 4. Architecture Boundary

Preferred dependency direction:

```text
Employee
  -> ClientPortal PWA adapter
  -> Attendance public/domain service
  -> Attendance domain/persistence
```

Forbidden ownership direction:

```text
Attendance -> ClientPortal
```

Attendance must not own shared authentication/session/logout. It consumes the canonical authenticated user/employee identity from the repository.

The exact canonical employee identity (`users`, Account employee profile, or another existing source) must be determined by repository analysis before schema design.

## 5. Core Check-in Workflow

```text
Authenticated employee
  -> resolve employee eligibility
  -> resolve current/default shift
  -> request current device geolocation
  -> validate coordinates and GPS accuracy
  -> resolve eligible attendance location
  -> validate geofence
  -> validate no invalid duplicate/active session conflict
  -> persist server-side check-in atomically
  -> persist location verification evidence
  -> record audit
  -> return success state to PWA
```

The browser/PWA may collect evidence, but the server decides whether check-in is accepted.

## 6. Core Check-out Workflow

```text
Authenticated employee
  -> resolve active attendance session
  -> validate actor and current state
  -> collect/validate location evidence
  -> apply configured check-out geofence policy
  -> persist server-side check-out atomically
  -> calculate attendance facts
  -> record audit
  -> return completed state
```

Duplicate/retried requests must not create duplicate sessions or corrupt state.

## 7. Time Authority

Canonical check-in/check-out time must use server time.

Device/browser clock must not determine the official timestamp. Client time may only be stored as optional diagnostic metadata if justified later.

Displayed timezone follows existing project/company timezone conventions discovered during analysis.

## 8. Geolocation / Geofence

Release 1 should support multiple attendance locations in schema even if only one office is configured initially.

Each location should conceptually support:

```text
name
code
latitude
longitude
radius_meters
maximum_accuracy_meters
is_active
check_in_enabled
check_out_enabled
```

Recommended baseline pending analysis confirmation:

- initial office count: 1
- default radius: 150 meters, configurable per location
- maximum acceptable GPS accuracy: 100 meters, configurable
- check-in geofence: required
- check-out geofence: required

Employee location should preferably be matched automatically to an eligible location rather than trusting an arbitrary client-selected location.

## 9. Location Verification Results

Verification should preserve explicit result states rather than a single boolean, for example:

```text
verified
outside_geofence
accuracy_too_low
location_unavailable
location_inactive
not_required
```

Evidence may include:

```text
latitude
longitude
accuracy_meters
captured_at
distance_meters
location_id
verification_method
verification_result
```

## 10. Privacy

Attendance is not an employee tracking system.

Release 1 must not:

- continuously track employees;
- run background GPS tracking;
- collect location throughout the workday;
- access geolocation when not required for an attendance action.

PWA should request location only when required for check-in/check-out or an explicit location refresh.

Raw GPS retention duration remains a user/privacy decision and must be analyzed before final REQUIREMENTS approval.

## 11. Offline Policy

Official check-in/check-out requires an online connection.

The PWA may display cached shell/history where existing ClientPortal conventions allow it, but it must not queue an offline attendance mutation as an official attendance event because server time and trusted validation are unavailable.

## 12. Attendance Session Model

Design attendance around sessions/shift instances, not a hard assumption of one row per user per calendar date.

Avoid prematurely enforcing `UNIQUE(user_id, date)` because future requirements may include:

- multiple shifts;
- split shifts;
- overnight shifts;
- multiple attendance sessions.

Conceptual identity should account for employee, work date, shift and attendance session.

## 13. Work Shift Baseline

Release 1 should include a work-shift concept even if the company initially uses one default office shift.

Recommended baseline pending final user confirmation:

```text
Default shift: 08:00–17:00
Late grace: 5 minutes
Early-leave grace: 5 minutes
```

Conceptual shift fields:

```text
name
code
start_time
end_time
late_grace_minutes
early_leave_grace_minutes
is_active
```

Support for overnight shifts should be considered in schema/service design even if UI support is deferred.

Employee-specific shift assignment is an inference, not yet a confirmed Release 1 requirement. Analyzer must determine whether default shift only or assignment records are appropriate.

## 14. Attendance States

Conceptual lifecycle:

```text
NOT_STARTED
  -> CHECKED_IN
  -> COMPLETED
```

Potential derived/persistent exception states:

```text
ABSENT
MISSING_CHECKOUT
ADJUSTMENT_PENDING
ADJUSTED
VOIDED
```

Analyzer must decide which states should be persisted versus derived.

## 15. Late / Early Facts

Attendance calculation belongs in Attendance services/domain logic, not Blade/Livewire/ClientPortal.

Potential facts:

```text
late_minutes
early_leave_minutes
worked_minutes
```

Analysis must determine whether calculated facts should be snapshotted to preserve historical meaning when shift configuration changes later.

## 16. Adjustment Requests

Employees must not directly edit canonical attendance records.

When check-in/check-out is missing or exceptional, employee submits an adjustment request.

Conceptual workflow:

```text
DRAFT/SUBMIT
  -> PENDING
  -> APPROVED | REJECTED
```

Conceptual fields:

```text
attendance_record_id nullable
requested_check_in_at nullable
requested_check_out_at nullable
reason
note
status
submitted_at
reviewed_at
reviewed_by
review_note
```

On approval, official attendance is corrected through an authorized domain service and the original/change history remains auditable.

Initial reviewer: Attendance Admin / HR.

Manager approval is deferred unless existing repository organization conventions make it appropriate for Release 1.

## 17. Audit Requirements

Sensitive events must be auditable, including:

- check-in;
- check-out;
- adjustment submission;
- adjustment approval/rejection;
- manual correction;
- record void/invalidation;
- attendance location changes;
- shift/policy changes.

Audit should capture actor, action, target, before/after where appropriate, timestamp and reason.

Do not create a private Attendance audit framework if the repository already has a canonical audit/history infrastructure.

## 18. Anti-fraud Baseline

Release 1 MUST preserve these verification signals:

- authenticated employee identity;
- server time;
- GPS coordinates;
- GPS accuracy;
- geofence calculation/result;
- resolved attendance location;
- audit trail.

SHOULD consider repository-compatible metadata such as user agent/device context and IP address only when privacy/security conventions allow it.

FUTURE anti-fraud options:

- rotating/signed QR attendance;
- trusted devices;
- company-network validation where technically reliable;
- device attestation;
- face/biometric verification;
- fraud scoring.

Geofence must not be described as cryptographic proof of physical presence; GPS can be spoofed.

## 19. PWA Application

Proposed ClientPortal application key:

```text
attendance
```

Proposed adapter location:

```text
Modules/ClientPortal/Applications/Attendance/
```

Conceptual adapter manifest:

```text
key: attendance
source_module: Attendance
```

ClientPortal should only expose the application when the source module is effectively enabled and the user has the required client permissions.

Attendance business rules must remain inside Attendance.

## 20. PWA UX

Primary mobile screen should show:

- current date/time presentation;
- today's shift;
- current attendance state;
- resolved/verification location state;
- primary `Vào ca` or `Ra ca` action;
- recent history.

Location states should be explicit:

```text
requesting_location
permission_denied
location_unavailable
accuracy_low
outside_area
verified
```

If permission is denied, show a clear instruction and do not silently bypass geofence.

## 21. Admin Dashboard

Conceptual route:

```text
/admin/attendance/dashboard
```

Dashboard should answer operational questions such as:

- employees expected today;
- checked in;
- not checked in;
- late;
- checked out;
- missing checkout;
- pending adjustments.

Exact counts depend on canonical employee/shift/calendar ownership discovered during analysis.

## 22. Admin Records Workspace

Conceptual route:

```text
/admin/attendance/records
```

Workspace should follow `.codex/standards/ADMIN_UI_STANDARD.md` and evaluate:

- search;
- date/date-range filters;
- employee filter;
- location filter;
- shift filter;
- status filter;
- late/early/missing-checkout filters;
- reset filters;
- bounded pagination (`10`, `25`, `50`, `100` where repository conventions fit);
- row selection only where useful;
- safe bulk actions;
- export/import applicability;
- loading/success/error/confirmation UX.

No unbounded `All` page size.

Suggested record columns:

```text
Employee
Work date
Shift
Check-in
Check-out
Worked time
Location
Late
Early leave
Status
Adjustment
Actions
```

Hard-delete should not be a normal attendance operation. Prefer void/invalidate with permission, reason and audit if invalidation is required.

## 23. Import / Export

### Export

Admin/HR export is SHOULD HAVE for attendance records and monthly review.

Conceptual fields:

```text
Employee code
Employee name
Date
Shift
Check-in
Check-out
Worked minutes
Late minutes
Early-leave minutes
Location
Status
```

Selected-row export should reuse canonical shared import/export foundations if present.

### Import

Direct bulk import that overwrites canonical attendance history is NOT part of Release 1.

Potential future/controlled imports:

- legacy attendance migration;
- shift assignment import;
- calendar import.

Any import must preserve validation and audit.

## 24. Reporting

Initial/near-term reports:

- daily attendance;
- monthly employee attendance;
- late arrivals;
- missing check-outs;
- pending adjustments.

Future reports:

- department/manager summary;
- overtime;
- absence/leave reconciliation;
- payroll export.

## 25. Permissions

Conceptual Admin permissions; analyzer must normalize to current repository naming conventions:

```text
view_attendance
manage_attendance
view_attendance_record
edit_attendance_record
approve_attendance_adjustment
view_attendance_shift
manage_attendance_shift
view_attendance_location
manage_attendance_location
export_attendance
```

Conceptual ClientPortal permissions:

```text
client.attendance.view
client.attendance.check-in
client.attendance.check-out
client.attendance.history
client.attendance.adjustment.create
```

Backend authorization is mandatory. Hiding UI controls is not authorization.

## 26. Proposed Domain Services

Conceptual boundaries only; exact classes should be simplified/reused by analysis/create-plan:

```text
AttendanceService
AttendanceCheckInService
AttendanceCheckOutService
GeofenceService
ShiftResolver
AttendanceCalculationService
AttendanceAdjustmentService
AttendanceReportService
```

Controllers/Livewire/ClientPortal adapter must not own core attendance rules.

Check-in/check-out should use transactions and locking/idempotency appropriate to prevent double-tap/retry races.

## 27. Conceptual Persistence

Analyzer should validate against existing repository ownership before creating migrations.

Potential tables:

```text
attendance_locations
attendance_shifts
attendance_shift_assignments (only if required)
attendance_records
attendance_adjustment_requests
```

Potential dedicated attendance event table only if existing audit infrastructure cannot satisfy requirements.

### Attendance locations

Conceptual fields:

```text
id
name
code unique
latitude
longitude
radius_meters
maximum_accuracy_meters
check_in_enabled
check_out_enabled
is_active
timestamps
```

### Attendance shifts

Conceptual fields:

```text
id
name
code
start_time
end_time
late_grace_minutes
early_leave_grace_minutes
is_active
timestamps
```

### Attendance records

Conceptual fields include:

```text
id
employee/user identity reference
shift_id nullable/required depending on approved design
location_id
work_date
status
checked_in_at
checked_out_at
check-in location evidence
check-out location evidence
late_minutes
early_leave_minutes
worked_minutes
void metadata where required
timestamps
```

Exact foreign keys must not be chosen until canonical employee identity is proven from repository source.

### Adjustment requests

Conceptual indexes should support employee, attendance record, status, submitted/review dates and reviewer.

## 28. Runtime-State Requirements

Attendance must use the repository's canonical module runtime-state infrastructure.

Manifest/default source state and runtime effective state are separate concerns.

Attendance business code must not read/write `storage/app/system/module-state.json` directly.

When Attendance is OFF:

- Attendance routes/UI/services are unavailable according to repository conventions;
- ClientPortal Attendance app is not exposed;
- historical database data remains intact;
- unrelated modules continue to work;
- no schema/table deletion occurs.

Runtime toggling must leave Git clean.

## 29. Bootstrap Contract Proposal

This is input for analysis, not an approved implementation contract.

```text
Manifest          : config/module.php
Type              : domain
Dependencies      : TBD after repository identity/dependency analysis
Module Provider   : only if module-specific boot logic is actually needed
Config            : yes
Web routes        : yes
API routes        : no for MVP unless analysis proves needed
Migrations        : yes
Livewire          : likely yes for Admin workspace
Blade components  : only where reusable/required
Console commands  : no for MVP unless analysis proves needed
Runtime state     : supported
Runtime storage   : no special runtime filesystem required for MVP
External services : none required for MVP
Queue             : not required for check-in/check-out
```

The module must fit `Modules/ModuleServiceProvider.php` and must not introduce `module.json`, nwidart, a second registry, manual duplicate provider registration, or parallel bootstrap/discovery infrastructure.

## 30. Security Requirements

MUST include:

- authenticated employee/admin boundaries;
- backend capability authorization;
- CSRF protection for web mutations;
- coordinate validation (`latitude -90..90`, `longitude -180..180`, non-negative accuracy);
- server time authority;
- transaction/race protection;
- data isolation so employees only see their own records unless elevated permission exists;
- safe error handling with no raw internal exceptions exposed;
- audit of sensitive changes;
- no silent destructive deletion.

## 31. MUST HAVE — Release 1

- Attendance domain module;
- repository-compatible runtime ON/OFF;
- canonical employee identity integration;
- multiple-location-capable schema;
- one configured office initially;
- geofence verification;
- GPS accuracy validation;
- server-time authority;
- work-shift baseline;
- check-in;
- check-out;
- duplicate/race safety;
- employee attendance history;
- adjustment request + Admin/HR approval/rejection;
- Admin dashboard;
- Admin records workspace;
- search/filter/reset/bounded pagination;
- basic late/early/worked facts;
- authorization;
- audit;
- ClientPortal Attendance PWA adapter;
- mobile/PWA UX;
- focused tests and documentation.

## 32. SHOULD HAVE

- employee-specific/multiple shift assignments if analysis confirms;
- manager-scoped review;
- Excel/CSV export;
- selected-row export;
- notifications for adjustment decisions;
- missing-checkout reminders;
- supporting evidence files;
- monthly summary;
- explicit void/invalidation workflow.

## 33. FUTURE

- holiday calendar;
- leave integration;
- overtime workflow;
- payroll integration;
- rotating/signed QR attendance;
- trusted device/device attestation;
- reliable company-network validation;
- remote-work policy;
- field-work/business-trip policy;
- biometric/face verification;
- advanced anti-fraud scoring.

## 34. Explicit Out of Scope for Initial Release

- payroll/salary calculation;
- continuous/background GPS tracking;
- offline official check-in/check-out;
- face recognition;
- native mobile application;
- hard-delete attendance history as normal operation;
- automatic HR disciplinary decisions;
- automatic overtime approval;
- full leave-management domain.

## 35. Acceptance Criteria — Employee/PWA

- eligible employee can open Attendance in PWA;
- employee sees current attendance state and shift;
- location permission/accuracy/geofence states are clear;
- check-in uses server time and records verification evidence;
- duplicate check-in does not create duplicate attendance;
- check-out closes the correct active session;
- employee can view own history;
- employee can submit adjustment request;
- unauthorized/non-employee actor cannot mutate attendance;
- offline client cannot create official attendance.

## 36. Acceptance Criteria — Admin/HR

- authorized Admin/HR can access dashboard and records;
- search/filter/reset/pagination work with bounded page sizes;
- attendance evidence can be inspected according to permission/privacy rules;
- authorized reviewer can approve/reject adjustments;
- unauthorized admin cannot perform sensitive mutations;
- corrections/invalidation are auditable;
- no silent hard-delete of attendance history.

## 37. Acceptance Criteria — Architecture

- module discovered by `Modules/ModuleServiceProvider.php`;
- manifest follows current repository convention;
- runtime ON/OFF works through canonical runtime-state abstractions;
- runtime toggle leaves Git clean;
- no second module registry/bootstrap path;
- Attendance does not depend on ClientPortal;
- ClientPortal adapter contains no Attendance business logic;
- disabling Attendance hides/disables its capabilities without deleting historical data.

## 38. Expected Test Areas

Analyzer/create-plan should evaluate focused coverage for:

- module bootstrap/discovery;
- runtime state ON/OFF and Git-clean behavior;
- dependency contract;
- employee eligibility/identity;
- successful check-in;
- duplicate/racing check-in;
- outside geofence;
- poor GPS accuracy;
- invalid coordinates;
- successful check-out;
- check-out without active check-in;
- late calculation;
- early-leave calculation;
- employee data isolation;
- authorization;
- adjustment submit/approve/reject;
- audit;
- Admin search/filter/reset/pagination;
- ClientPortal application visibility when Attendance ON/OFF;
- PWA route authorization.

## 39. Decisions Required From User / Analyzer

The following remain intentionally unresolved and must not be silently converted to approved requirements:

1. Canonical employee identity source and exact Attendance dependency graph.
2. Whether Release 1 needs employee-specific shift assignment or only a default shift.
3. Exact company shift time if different from recommended `08:00–17:00`.
4. Exact late/early grace periods if different from recommended 5 minutes.
5. Raw GPS coordinate retention period and whether evidence should be reduced/anonymized after a retention window.
6. Whether manager approval/scope is needed in Release 1 or only Attendance Admin/HR review.
7. Whether remote/field attendance is required now; baseline assumes office-only and uses adjustment requests for exceptions.
8. Whether attendance export is MUST HAVE or SHOULD HAVE for the first usable release.
9. Whether an existing shared audit framework is sufficient or Attendance needs a domain event/history table.
10. Whether company calendar/working weekdays must be modeled in Release 1 or deferred.

## 40. Recommended Baseline Already Accepted for Analysis

Use the following as the preferred baseline unless repository inspection identifies a conflict:

```text
Module name               : Attendance
UI name                   : Chấm công
Module type               : domain
Default runtime state     : disabled until configured
Employee-only check       : yes
Multi-location schema     : yes
Initial configured office : 1
Check-in geofence         : required
Check-out geofence        : required
Default radius            : 150m configurable
Maximum GPS accuracy      : 100m configurable
Server time               : authoritative
Offline mutation          : prohibited
Default shift proposal    : 08:00–17:00
Late grace proposal       : 5 minutes
Early-leave grace proposal: 5 minutes
Adjustment request        : yes
Initial reviewer          : Attendance Admin / HR
Continuous GPS tracking   : prohibited
Payroll                   : out of scope
Leave / overtime          : future
QR / biometrics           : future
```

## 41. Required Next Workflow

This file is business/idea input only.

Next task:

```text
.codex/tasks/analyze-new-module.md
Input: docs/modules/Attendance/IDEA.md
```

The analyzer must inspect repository reality, classify Confirmed Requirement / Inference / Unknown, identify reference modules and duplicate ownership risks, propose the final Bootstrap Contract and dependencies, perform schema/security/runtime-state analysis, and present `CREATE-MODULE READINESS` before writing `docs/modules/Attendance/REQUIREMENTS.md`.

No Attendance application source, migration, route, service, model, provider, seeder, job, command or runtime-state mutation is authorized by this IDEA document.
