# Attendance — Create Plan

## 1. Status and approval gate

This document is the implementation plan for creating `Modules/Attendance` from the approved canonical business specification:

```text
docs/modules/Attendance/REQUIREMENTS.md
```

Planning source of truth:

- `docs/modules/Attendance/REQUIREMENTS.md`
- `.codex/tasks/create-module.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- repository bootstrap/runtime source

Current status:

```text
Business requirements : APPROVED
Create plan           : PROPOSED — WAITING FOR APPROVAL
Application code      : NOT STARTED
Migrations            : NOT STARTED
Routes                : NOT STARTED
Implementation branch : NOT CREATED
```

This plan is an approval gate. No Attendance application source, migration, route, ClientPortal adapter, runtime state mutation or implementation branch may be created until this plan is explicitly approved.

---

## 2. Purpose and Release-1 business scope

`Attendance` is a new `domain` module and canonical owner of employee attendance behavior and persistence.

Release 1 covers:

- server-authoritative check-in/check-out;
- canonical Account employee identity integration;
- one configurable default shift;
- multiple-location-capable attendance location schema;
- strict check-in and check-out geofence verification;
- GPS accuracy validation;
- attendance sessions/history;
- late, early-leave and worked-time facts;
- adjustment submission and Admin/HR review;
- auditable corrections/voiding;
- Admin dashboard;
- Admin records workspace;
- search/filter/reset/bounded pagination;
- Attendance export;
- ClientPortal PWA adapter;
- runtime enable/disable;
- focused automated verification and documentation.

Release 1 explicitly excludes payroll, leave, overtime approval, remote/WFH attendance, continuous/background GPS tracking, offline official mutations, biometric/face verification, canonical Attendance history import, manager approval requirements and employee-specific shift assignment workflows.

---

## 3. Selected module type

```text
Type: domain
```

Rationale:

- Attendance owns business rules, calculations, persistence, authorization and workflows.
- Attendance is not shared infrastructure.
- Attendance is not a presentation shell.
- Admin and ClientPortal consume Attendance; neither becomes the domain owner.

Canonical dependency direction:

```text
ClientPortal -> Attendance -> Account
```

Forbidden dependency:

```text
Attendance -> ClientPortal
```

---

## 4. Reference modules and conventions to reuse

### 4.1 Account

Reuse:

- canonical employee identity through `Modules\Account\Models\EmployeeProfile`;
- existing user/profile relationship instead of creating duplicate employee persistence;
- module dependency declaration pattern.

Attendance must not create an `employees` table or duplicate employee profile fields.

### 4.2 Request

Reuse current conventions where applicable:

- dot-delimited capability permissions;
- `permissions_by_guard` for web/ClientPortal capabilities;
- domain-owned audit-event pattern;
- runtime-disabled domain manifest pattern;
- service-oriented workflows;
- ClientPortal consumer integration pattern;
- bounded Admin list/workspace behavior.

Attendance will not copy Request's larger workflow engine; only current repository conventions that match Attendance are reused.

### 4.3 Shared

Reuse:

- canonical import/export foundation for Attendance export;
- shared export UI when suitable;
- common reusable components only when the contract already exists and is stable.

Release 1 does not introduce Attendance history import.

### 4.4 Admin

Reuse:

- `Admin::layouts.master` shell;
- canonical Admin navigation/layout conventions;
- existing shared input/modal/status/pagination patterns when available.

Admin remains presentation shell; Attendance owns Attendance pages, Livewire classes and business services.

### 4.5 ClientPortal

Reuse:

- Application adapter/registry contract;
- adaptive/PWA shell;
- authenticated client navigation;
- source-module effective-state gating.

ClientPortal adapter stays thin and contains presentation/orchestration only.

---

## 5. Bootstrap Contract

| Contract | Attendance plan |
|---|---|
| Manifest | `Modules/Attendance/config/module.php` |
| Type | `domain` |
| Dependencies | `Account` only unless implementation proof requires another explicit runtime dependency |
| Module Provider | Not required initially |
| Config | Yes |
| Web routes | Yes |
| API routes | No for Release 1 |
| Migrations | Yes |
| Livewire | Yes |
| Blade components | Only if reusable/required |
| Console commands | No for Release 1 |
| Runtime state | Supported |
| Special runtime filesystem | No for core Release 1 |
| External services | None |
| Queue | Not required for synchronous check-in/check-out |

Compatibility rules:

- `Modules/ModuleServiceProvider.php` is the only module bootstrap path.
- Do not create `module.json`.
- Do not add nwidart infrastructure.
- Do not manually register routes, migrations, Livewire or resources already discovered by the root provider.
- Do not create a second module registry.
- A module-specific service provider is unnecessary unless a concrete future requirement cannot be handled by root discovery.

---

## 6. Manifest design

Proposed manifest baseline:

```text
name               = Attendance
type               = domain
default_enabled    = false
depends            = [Account]
permissions        = Admin/domain capability list
permissions_by_guard.web = ClientPortal/employee capability list
```

Use repository-supported manifest fields only.

Do not mutate manifest state at runtime.

Recommended capability names:

### Admin/domain guard

```text
attendance.dashboard.view
attendance.record.view
attendance.record.adjust
attendance.record.void
attendance.adjustment.view
attendance.adjustment.approve
attendance.shift.view
attendance.shift.manage
attendance.location.view
attendance.location.manage
attendance.export
attendance.audit.view
```

### Web/ClientPortal guard

```text
client.attendance.access
attendance.record.view-own
attendance.check-in
attendance.check-out
attendance.adjustment.create
```

These names follow the currently preferred dot-delimited capability convention and the approved REQUIREMENTS capability model.

---

## 7. Runtime-state behavior

Source/default state:

```text
default_enabled = false
```

Effective state must be resolved through repository `ModuleStateRepository` / `ModuleStateResolver` behavior.

Required runtime behavior:

- runtime ON exposes Attendance routes/resources according to root provider conventions;
- runtime OFF removes Attendance runtime surfaces from module bootstrap;
- ClientPortal adapter is unavailable while Attendance is effectively disabled;
- Attendance tables and historical data are never removed by runtime disable;
- runtime toggle must not alter tracked source files;
- Git must remain clean after runtime toggle operations;
- dependency validation must use effective state.

No direct reads/writes to `storage/app/system/module-state.json` are allowed.

---

## 8. Proposed module structure

```text
Modules/Attendance/
├── Actions/
│   ├── CheckIn.php
│   ├── CheckOut.php
│   ├── ApproveAdjustment.php
│   ├── RejectAdjustment.php
│   └── VoidAttendanceRecord.php
├── DTOs/
│   ├── LocationEvidenceData.php
│   └── AttendanceMutationResult.php
├── Enums/
│   ├── AttendanceRecordStatus.php
│   ├── AdjustmentStatus.php
│   ├── VerificationResult.php
│   └── AuditAction.php
├── Http/
│   └── Controllers/
│       ├── Admin/
│       │   ├── AttendanceDashboardController.php
│       │   ├── AttendanceRecordsController.php
│       │   ├── AttendanceAdjustmentsController.php
│       │   ├── AttendanceLocationsController.php
│       │   ├── AttendanceShiftsController.php
│       │   └── AttendanceAuditController.php
│       └── Portal/
│           └── AttendancePortalController.php
├── Livewire/
│   └── Admin/
│       ├── Dashboard.php
│       ├── RecordsWorkspace.php
│       ├── AdjustmentsWorkspace.php
│       ├── LocationsWorkspace.php
│       ├── ShiftsWorkspace.php
│       └── AuditWorkspace.php
├── Models/
│   ├── AttendanceLocation.php
│   ├── AttendanceShift.php
│   ├── AttendanceRecord.php
│   ├── AttendanceAdjustmentRequest.php
│   └── AttendanceAuditEvent.php
├── Policies/
│   ├── AttendanceRecordPolicy.php
│   ├── AttendanceAdjustmentRequestPolicy.php
│   ├── AttendanceLocationPolicy.php
│   └── AttendanceShiftPolicy.php
├── Services/
│   ├── AttendanceService.php
│   ├── AttendanceQueryService.php
│   ├── GeofenceService.php
│   ├── ShiftResolver.php
│   ├── AttendanceCalculationService.php
│   ├── AdjustmentService.php
│   ├── AttendanceAuditService.php
│   └── AttendanceExportService.php
├── config/
│   ├── module.php
│   └── attendance.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── AttendanceDefaultsSeeder.php
├── resources/
│   └── views/
│       ├── admin/
│       ├── portal/
│       └── livewire/admin/
└── routes/
    └── web.php
```

The exact number of classes may be reduced during implementation if responsibilities remain clear. The plan intentionally avoids a module provider, API route file and console directory in Release 1.

---

## 9. Database and model design

### 9.1 `attendance_locations`

Purpose: server-owned eligible attendance locations.

Proposed fields:

```text
id
name
code unique
latitude decimal(10,7)
longitude decimal(10,7)
radius_meters unsigned integer
maximum_accuracy_meters unsigned integer
is_active boolean
check_in_enabled boolean
check_out_enabled boolean
created_by nullable FK user
updated_by nullable FK user
created_at
updated_at
```

Rules:

- latitude `-90..90`;
- longitude `-180..180`;
- radius > 0;
- maximum accuracy >= 0;
- initial default radius 150m;
- initial maximum accuracy 100m;
- client cannot choose a trusted location ID as proof; server resolves eligible location from evidence.

### 9.2 `attendance_shifts`

Purpose: current shift configuration.

Proposed fields:

```text
id
name
code unique
start_time time
end_time time
late_grace_minutes unsigned integer
 early_leave_grace_minutes unsigned integer
is_default boolean
is_active boolean
created_by nullable FK user
updated_by nullable FK user
created_at
updated_at
```

Release-1 seeded default:

```text
08:00–17:00
late grace = 5
 early grace = 5
```

Only one active default shift is required in Release 1. Service validation must prevent ambiguous multiple defaults.

### 9.3 `attendance_records`

Purpose: canonical attendance session/shift instance.

Proposed identity/reference fields:

```text
id
employee_profile_id FK employee_profiles
user_id FK users
work_date date
shift_id nullable FK attendance_shifts
session_key string unique
status string
```

Shift snapshot fields:

```text
shift_code_snapshot
shift_name_snapshot
shift_start_time_snapshot
shift_end_time_snapshot
late_grace_minutes_snapshot
 early_leave_grace_minutes_snapshot
```

Check-in fields:

```text
checked_in_at nullable timestamp
check_in_location_id nullable FK attendance_locations
check_in_latitude nullable decimal(10,7)
check_in_longitude nullable decimal(10,7)
check_in_accuracy_meters nullable decimal(8,2)
check_in_distance_meters nullable decimal(10,2)
check_in_captured_at nullable timestamp
check_in_verification_result nullable string
```

Check-out fields mirror check-in:

```text
checked_out_at nullable timestamp
check_out_location_id nullable FK attendance_locations
check_out_latitude nullable decimal(10,7)
check_out_longitude nullable decimal(10,7)
check_out_accuracy_meters nullable decimal(8,2)
check_out_distance_meters nullable decimal(10,2)
check_out_captured_at nullable timestamp
check_out_verification_result nullable string
```

Calculated facts:

```text
worked_minutes nullable unsigned integer
late_minutes unsigned integer default 0
 early_leave_minutes unsigned integer default 0
```

Invalidation/correction metadata:

```text
voided_at nullable timestamp
voided_by nullable FK user
void_reason nullable text
adjusted_at nullable timestamp
```

Timestamps:

```text
created_at
updated_at
```

Important constraints/indexes:

- do **not** create `UNIQUE(user_id, work_date)`;
- `session_key` provides idempotent session identity without blocking future multiple/overnight sessions;
- index `(employee_profile_id, work_date)`;
- index `(work_date, status)`;
- index `checked_in_at` and `checked_out_at` as reporting needs justify;
- index location references used by filters;
- FKs should restrict/cascade only where ownership semantics are safe; canonical Attendance history must not disappear because an Account profile is deleted/soft-deleted.

### 9.4 Persistent vs derived state

Persistent canonical states for Release 1:

```text
checked_in
completed
voided
```

`not_started` is no-record/UI state.

The following are derived/reporting/exception facts rather than primary lifecycle state:

```text
absent
missing_checkout
late
 early_leave
adjustment_pending
adjusted
```

Reason:

- absence depends on expected-employee and time context;
- missing checkout depends on shift/time context;
- adjustment state belongs to adjustment requests;
- late/early are numeric facts;
- storing every derived label would create state drift.

`adjusted_at` and audit history identify records modified through approved adjustment/correction workflows.

### 9.5 `attendance_adjustment_requests`

Proposed fields:

```text
id
employee_profile_id FK employee_profiles
user_id FK users
attendance_record_id nullable FK attendance_records
requested_work_date date
requested_check_in_at nullable timestamp
requested_check_out_at nullable timestamp
reason text
note nullable text
status string
submitted_at timestamp
reviewed_at nullable timestamp
reviewed_by nullable FK user
review_note nullable text
created_at
updated_at
```

Statuses:

```text
pending
approved
rejected
```

No employee may approve their own request.

Approval never performs direct model-field mutation from Livewire; it calls Attendance adjustment/domain service inside a transaction and records before/after audit history.

### 9.6 `attendance_audit_events`

Repository inspection did not identify a stable canonical shared audit service suitable for Attendance's required immutable domain history. Therefore Release 1 plans an Attendance-owned audit table.

Proposed fields:

```text
id
actor_user_id nullable FK user
action string
target_type string
target_id nullable bigint
attendance_record_id nullable FK attendance_records
reason nullable text
before_json nullable json
after_json nullable json
metadata_json nullable json
created_at
```

Audit events are append-only through `AttendanceAuditService`; normal UI has no update/delete path.

Precise GPS should not be duplicated into generic audit JSON when the canonical record evidence already contains it.

---

## 10. GPS evidence, privacy and retention

### Collection

Location is requested only:

- during check-in;
- during check-out;
- explicit user location refresh when necessary.

No background/continuous tracking is allowed.

### Server validation

Client submits only evidence:

```text
latitude
longitude
accuracy_meters
captured_at
```

Server:

1. validates coordinate ranges;
2. validates accuracy threshold;
3. loads active eligible Attendance locations;
4. calculates distance using a server-side geodesic/Haversine implementation;
5. chooses/resolves eligible location;
6. verifies radius;
7. persists result/evidence only after domain validation.

Client-supplied `location_id`, distance or verification result is never trusted as canonical proof.

### Raw-GPS retention

Approved baseline: 12 months.

Implementation approach:

- retain coordinates/accuracy/captured-at for 12 months;
- after retention, null precise coordinate/accuracy fields through a server-controlled cleanup process;
- preserve non-precise business facts needed for history: resolved location, distance when justified, verification result, official timestamps and attendance facts.

Release 1 core does not require a custom scheduler/console command in the initial skeleton. The cleanup mechanism must reuse the repository's canonical scheduler/automatic-job infrastructure when implemented; it must not introduce a parallel scheduler. If retention cleanup cannot be wired safely in the initial Attendance implementation slice, it is a release gate before production enablement, not a reason to weaken retention requirements.

---

## 11. Time and shift semantics

Canonical attendance timestamps use Laravel/server time only.

Device/browser time:

- cannot define official check-in/check-out time;
- may be accepted only as non-authoritative diagnostic capture metadata.

Timezone display must use the application's configured timezone (`config('app.timezone')`) unless an existing company-timezone abstraction is proven during implementation. No Attendance-private timezone setting will be invented.

### Future-safe overnight behavior

Release 1 default shift does not cross midnight, but schema/service contracts should model a session with explicit `work_date` and shift snapshot rather than equating session identity with calendar date.

Future overnight resolution can define `work_date` as the shift-start business date without schema replacement.

---

## 12. Service boundaries

### `ShiftResolver`

Owns:

- default shift lookup;
- validation that one eligible default is active;
- building immutable snapshot data for a new attendance session.

### `GeofenceService`

Owns:

- coordinate validation support;
- distance calculation;
- active-location resolution;
- accuracy/radius verification result.

Pure calculation logic should be independently unit-testable.

### `AttendanceCalculationService`

Owns:

- late minutes;
- early-leave minutes;
- worked minutes;
- shift snapshot time interpretation.

No calculation logic in Blade or ClientPortal adapter.

### `AttendanceService` / actions

Owns canonical check-in/check-out orchestration.

### `AdjustmentService`

Owns submission, approval/rejection and canonical correction behavior.

### `AttendanceQueryService`

Owns Admin/PWA read queries, filters, pagination and reporting projections.

### `AttendanceExportService`

Owns export query/filter/scope and mapping while reusing Shared export infrastructure.

### `AttendanceAuditService`

Owns append-only audit recording and redaction policy.

---

## 13. Check-in transaction and concurrency contract

Check-in flow:

```text
authenticate/authorize
-> resolve canonical employee profile
-> resolve default shift + snapshot
-> validate GPS evidence
-> resolve eligible location/geofence
-> begin DB transaction
-> acquire concurrency guard
-> find/create canonical active session
-> reject duplicate invalid state or return idempotent success
-> persist server timestamp + evidence + snapshot
-> append audit event
-> commit
```

Concurrency strategy:

- deterministic `session_key` derived server-side from employee + resolved work-date + shift/session discriminator;
- unique DB constraint on `session_key`;
- transaction plus `lockForUpdate()` on existing candidate session where applicable;
- duplicate-key race is translated into a safe idempotent/reload path rather than a duplicate session;
- no client-generated canonical idempotency key is required for Release 1.

This protects double taps, retries and concurrent requests while keeping schema future-safe for multiple shift sessions.

---

## 14. Check-out transaction contract

Check-out flow:

```text
authenticate/authorize
-> resolve canonical employee
-> validate GPS evidence/geofence
-> begin transaction
-> lock active attendance session
-> reject missing/voided/already-completed state or return idempotent success
-> persist server-authoritative checkout
-> calculate worked/late/early facts
-> append audit event
-> commit
```

Check-out requires an active checked-in session and valid check-out geofence.

---

## 15. Adjustment/correction contract

Employee:

- may submit a request for themselves;
- cannot directly edit canonical records;
- cannot approve own request.

Admin/HR approval transaction:

```text
lock adjustment
-> verify still pending
-> authorize reviewer
-> reject self-review
-> lock target/create correction target as allowed
-> snapshot before state
-> apply approved times through Attendance domain service
-> recalculate facts
-> mark request approved
-> append audit with before/after/reason
-> commit
```

Reject path records reviewer/reason/status without modifying canonical Attendance time facts.

Manual correction and void operations use the same service/audit boundary and require explicit reason.

No hard-delete route/action is planned.

---

## 16. Routes

### Admin routes

Proposed canonical names:

```text
GET  /admin/attendance/dashboard      admin.attendance.dashboard
GET  /admin/attendance/records        admin.attendance.records
GET  /admin/attendance/adjustments    admin.attendance.adjustments
GET  /admin/attendance/locations      admin.attendance.locations
GET  /admin/attendance/shifts         admin.attendance.shifts
GET  /admin/attendance/audit          admin.attendance.audit
```

Routes use the repository's current Admin authentication middleware plus capability-specific permission middleware/policy boundaries.

Sensitive mutations remain Livewire/service actions with explicit backend authorization.

### Employee/portal domain routes

Attendance may expose authenticated web endpoints/pages consumed by ClientPortal for:

```text
today/state
check-in
check-out
history
adjustments
```

Exact URLs should follow current ClientPortal integration conventions discovered immediately before implementation. They remain Attendance-owned capabilities; no Release-1 `routes/api.php` is planned.

---

## 17. ClientPortal PWA adapter

Proposed adapter:

```text
Modules/ClientPortal/Applications/Attendance/
application key: attendance
source module: Attendance
```

Responsibilities allowed in adapter:

- app registration/metadata;
- navigation;
- presentation state;
- requesting browser geolocation;
- invoking Attendance-owned routes/services/capabilities;
- rendering errors/status returned by Attendance.

Forbidden in adapter:

- geofence calculation as authority;
- attendance calculations;
- canonical time decisions;
- persistence;
- employee eligibility rules;
- adjustment approval logic.

PWA UX states include:

```text
requesting_location
permission_denied
location_unavailable
accuracy_low
outside_area
verified
```

Official check-in/check-out are online-only. Service worker/offline behavior must never queue an official Attendance mutation for later replay as if it occurred at the original device time.

---

## 18. Admin dashboard design

Route:

```text
/admin/attendance/dashboard
```

Primary operational metrics:

- expected employees today;
- checked in;
- not checked in;
- late;
- checked out;
- missing checkout;
- pending adjustments.

Expected employees are active canonical Account employee profiles eligible for the default-shift model. Exact `EmployeeProfile.status` values must be verified against current Account data/contract before implementing the query; no new status vocabulary is invented in Attendance.

Dashboard is a focused operational summary with links into filtered records/adjustment workspaces, not a second copy of all CRUD screens.

---

## 19. Admin records workspace

Must follow `.codex/standards/ADMIN_UI_STANDARD.md`.

### Search

Search by canonical employee name/code using Account relationship data.

### Filters

Planned:

```text
date/date range
location
shift
canonical/derived attendance status
late yes/no
 early-leave yes/no
missing-checkout yes/no
adjustment state where useful
```

### Reset behavior

`Xóa bộ lọc` resets all domain filters, page and row selection.

Changing search/filter/page-size resets pagination.

### Pagination

Bounded choices only:

```text
10 / 25 / 50 / 100
```

Default: 25.

Tampered values normalize to 25.

Use an approved shared Admin paginator if it satisfies the current visual contract; otherwise use Attendance-scoped paginator with white inactive controls and indigo active page.

### Selection

Checkbox selection is useful primarily for selected-row export.

Header checkbox selects visible page only unless the UI explicitly states otherwise.

Selection is cleared when filter scope changes.

### Bulk actions

Release 1 does not plan destructive bulk actions.

No bulk void/correction is planned because Attendance corrections require case-specific reason/evidence and audit context.

### Empty/loading/error/success states

Required from initial implementation.

Sensitive actions use loading/disabled state to prevent double-submit.

---

## 20. Import / Export evaluation

### Export: APPLICABLE and required

Attendance owns portable tabular operational data and REQUIREMENTS explicitly approve export.

Implementation must reuse Shared import/export/export-storage conventions where compatible.

Default export scope follows active Admin filters.

Recommended columns:

```text
Employee code
Employee name
Work date
Shift code/name
Check-in
Check-out
Worked minutes
Late minutes
Early-leave minutes
Resolved check-in location
Resolved check-out location
Record status
Adjustment marker/status
```

Precise latitude/longitude are excluded from normal spreadsheet export by default because they are privacy-sensitive evidence, not ordinary operational reporting fields.

Selected export contract:

```text
selected IDs present -> export selected approved records
selected IDs empty   -> export all matching approved filter scope
```

Never silently export only the current pagination page.

Large exports must use lazy/chunked query handling. A concrete queue threshold will be selected after profiling the shared export implementation; proposed starting policy is synchronous for modest filtered scopes and queued generation when row count exceeds 10,000. This threshold is implementation-tunable and is not a business invariant.

Generated export files containing employee attendance data must use private storage and controlled authenticated download.

### Import: NOT APPLICABLE for canonical Attendance history in Release 1

No direct bulk import/upsert/overwrite of canonical Attendance records.

Therefore no Attendance import UI/template is created in Release 1.

Future legacy migration requires a separate approved import plan with unique-key, validation, audit and reconciliation rules.

---

## 21. Seeder design

A deterministic idempotent `AttendanceDefaultsSeeder` is appropriate for initial configuration only.

It may create/update:

- default shift `08:00–17:00`, 5/5 grace;
- an initial office configuration only when deployment-specific coordinates are explicitly supplied/configured.

Do not invent production latitude/longitude.

Therefore the seeder should not create a fake active office with arbitrary coordinates. Production enablement remains blocked until a real active attendance location is configured.

No Faker dependency.

---

## 22. Authorization and data isolation

Backend authorization is mandatory at route/Livewire/service caller boundaries.

Employee rules:

- own attendance state/history only by default;
- own adjustment creation only;
- check-in/out only for canonical authenticated employee identity.

Admin/HR rules:

- explicit capabilities for dashboard, record view/adjust/void, adjustments, locations, shifts, export and audit.

Super Admin behavior remains governed by repository `Gate::before` convention.

No browser-provided user/employee ID is trusted for employee self-service mutations; actor identity is resolved from authenticated user.

---

## 23. Security and data-integrity controls

Mandatory:

- server-authoritative timestamps;
- CSRF-protected web mutations;
- coordinate and accuracy validation;
- server-side geofence resolution;
- transaction boundaries;
- row locking/unique constraint duplicate defense;
- explicit state-transition validation;
- no hard-delete normal workflow;
- reason required for void/manual correction;
- audit before/after sensitive changes;
- no raw internal exception exposure;
- no precise GPS in ordinary application logs;
- employee data isolation;
- private export storage;
- no client control over model class/table/path/location verification result.

---

## 24. Events, jobs, queue and console evaluation

### Synchronous core

Check-in/check-out and adjustment decision remain synchronous.

### Events

Domain events may be introduced only if there is a concrete cross-component need. They are not required for the first skeleton.

### Jobs/queue

Not required for core mutation path.

May be used for:

- large export generation;
- future notifications;
- future reminders;
- GPS retention cleanup if repository scheduler infrastructure delegates to queued jobs.

### Console commands

Not required in Release 1 baseline.

Do not create a private scheduling framework or command merely because retention exists; first reuse canonical System scheduling infrastructure.

---

## 25. Runtime storage / Docker

Core Attendance does not require a special module-owned runtime directory.

Exports use repository-controlled private Laravel Storage.

No `chmod 777`.

No direct filesystem state for module enablement.

If queued export/retention implementation requires persistent files, verify Docker volume ownership and `www-data` access in that implementation MR.

---

## 26. Tests and verification plan

### Bootstrap/runtime

- module discovery through root provider;
- manifest type/dependency/default disabled;
- effective ON override;
- effective OFF override;
- dependency behavior with Account;
- toggle leaves manifest unchanged/Git clean contract where testable.

### Database/schema

- all tables/columns/indexes/FKs;
- no forbidden unique `(user_id, work_date)`;
- unique session key;
- default shift invariants;
- audit append-only application contract.

### Geofence

- coordinate validation;
- Haversine/distance calculation;
- exact boundary/radius cases;
- low-accuracy rejection;
- outside-geofence rejection;
- inactive location rejection;
- check-in and check-out both require policy.

### Check-in/check-out

- successful check-in;
- server timestamp authority;
- duplicate retry idempotency;
- simulated/concurrent race protection;
- successful checkout;
- checkout without active check-in rejected;
- repeated checkout safe/idempotent;
- voided record cannot mutate as normal session.

### Calculations

- on-time;
- grace boundary;
- late minutes;
- early-leave minutes;
- worked minutes;
- shift snapshot remains stable after shift config edit.

### Identity/isolation

- canonical Account EmployeeProfile resolution;
- non-employee denied;
- employee cannot read another employee's history;
- browser cannot mutate another employee by submitting foreign IDs.

### Adjustment

- submit;
- invalid requested time validation;
- approve;
- reject;
- self-approval denied;
- concurrent double decision safe;
- recalculation after approval;
- before/after audit preserved.

### Admin

- route authorization;
- dashboard metrics;
- search;
- each meaningful filter;
- reset;
- pagination values and tamper normalization;
- page reset after scope changes;
- selected-row behavior;
- no hard-delete action.

### Export

- authorization;
- active-filter export scope;
- no selection = all matching scope;
- selected IDs = selected records;
- no accidental current-page-only export;
- precise GPS excluded by default;
- bounded/chunk-safe query path;
- private download authorization.

### ClientPortal/PWA

- app visible only while Attendance effective ON and permission granted;
- hidden/denied while OFF;
- geolocation error states;
- official mutation requires online/server response;
- no Attendance business rule duplicated in adapter;
- own-history authorization.

### Regression scope

Expected layered verification:

1. Attendance focused tests;
2. Attendance module regression;
3. Account impacted contract tests;
4. ClientPortal impacted tests;
5. System/runtime-state tests only when shared runtime infrastructure is touched;
6. Admin regression for shell/menu contract when Admin integration changes;
7. frontend build for UI/PWA changes.

Full-project regression is not a default per-MR gate under the current collaboration workflow unless shared/core infrastructure is changed broadly or a release checkpoint explicitly requires it.

---

## 27. Manual UI acceptance

### Admin desktop/mobile

Verify:

- dashboard visual hierarchy;
- records workspace usable at representative widths;
- visible form/filter boundaries;
- search/filter/reset;
- pagination uses white inactive / indigo active contract;
- horizontal table overflow is controlled;
- loading/empty/error states;
- action visibility and authorization;
- adjustment approval/rejection confirmation and feedback;
- location/shift configuration UX;
- export feedback/download behavior.

### PWA/mobile

Verify:

- app launch from ClientPortal;
- today's shift/state clearly visible;
- location permission request occurs only when needed;
- denied/unavailable/low accuracy/outside-area states are understandable;
- double tap cannot submit duplicate mutation;
- successful check-in/check-out refreshes canonical state;
- offline mode cannot claim official success;
- own history and adjustment flow are usable on mobile.

---

## 28. Files expected to be created/changed after approval

### New Attendance module

```text
Modules/Attendance/config/module.php
Modules/Attendance/config/attendance.php
Modules/Attendance/routes/web.php
Modules/Attendance/Http/Controllers/...
Modules/Attendance/Livewire/...
Modules/Attendance/resources/views/...
Modules/Attendance/Models/...
Modules/Attendance/Services/...
Modules/Attendance/Actions/...
Modules/Attendance/DTOs/...
Modules/Attendance/Enums/...
Modules/Attendance/Policies/...
Modules/Attendance/database/migrations/...
Modules/Attendance/database/seeders/AttendanceDefaultsSeeder.php
```

### ClientPortal integration

```text
Modules/ClientPortal/Applications/Attendance/...
```

plus only the existing registry/navigation files required by current ClientPortal contract.

### Admin integration

Only canonical menu/shell integration files if Attendance routes are not automatically discoverable in navigation.

### Tests

```text
tests/Feature/Attendance/...
tests/Unit/Attendance/...
```

plus narrowly impacted ClientPortal/Account/System/Admin contract tests where necessary.

### Documentation

At implementation completion create/update Attendance maintenance/handoff docs required by repository workflow, including `COLLABORATION_HANDOFF.md` before PR/merge gates.

---

## 29. Suggested MR / phase breakdown

### MR-0 — Create Plan

Scope:

- this `CREATE_PLAN.md` only;
- no application code.

Gate: explicit user approval.

### MR-1 — Module skeleton + manifest + bootstrap/runtime contract

Scope:

- Attendance folder skeleton;
- manifest/config;
- bootstrap discovery tests;
- runtime default/override/dependency tests;
- no business UI yet.

### MR-2 — Persistence + models + defaults

Scope:

- locations;
- shifts;
- records;
- adjustment requests;
- audit events;
- models/enums;
- deterministic default-shift seeder;
- schema/model tests.

### MR-3 — Attendance domain core

Scope:

- employee resolution boundary;
- ShiftResolver;
- GeofenceService;
- AttendanceCalculationService;
- check-in/check-out actions/services;
- transaction/concurrency/idempotency tests.

### MR-4 — Adjustment + audit + Admin configuration

Scope:

- adjustment submit/review;
- corrections/voiding;
- audit service;
- location/shift Admin workspaces;
- authorization tests.

### MR-5 — Admin dashboard + records workspace

Scope:

- dashboard;
- records workspace;
- search/filters/reset;
- bounded pagination;
- selection semantics;
- Admin UI standard acceptance.

### MR-6 — Attendance export

Scope:

- Shared export integration;
- selected/all-matching scope;
- private storage/download;
- bounded/chunk behavior;
- export tests.

### MR-7 — ClientPortal PWA adapter

Scope:

- ClientPortal Attendance app adapter;
- PWA UI;
- geolocation orchestration;
- online-only mutation UX;
- effective-state/permission gating;
- ClientPortal regression.

### MR-8 — Privacy retention + release readiness

Scope:

- 12-month raw-GPS cleanup using canonical scheduling infrastructure;
- final authorization/security audit;
- module/impacted regressions;
- manual desktop/mobile/PWA smoke;
- docs/handoff closeout;
- production enablement checklist.

Phases may be merged when a smaller coherent implementation is safer, but persistence/domain/PWA boundaries should not be collapsed into one oversized unreviewable MR.

---

## 30. Risks and mitigations

### Risk: GPS spoofing

Mitigation: explicitly treat geofence as evidence, not cryptographic proof; preserve server time, accuracy, distance and audit facts. Do not promise anti-spoof guarantees a PWA cannot provide.

### Risk: duplicate sessions from retries/concurrency

Mitigation: deterministic server session key + unique constraint + transaction + row lock + idempotent result handling.

### Risk: historical shift meaning changes

Mitigation: immutable shift snapshot columns on AttendanceRecord.

### Risk: employee identity duplication

Mitigation: FK/reference canonical Account `EmployeeProfile`; no duplicate employee table.

### Risk: derived status drift

Mitigation: persist only canonical lifecycle state; compute absence/missing-checkout/late/early/adjustment projection from source facts.

### Risk: privacy leakage

Mitigation: precise GPS stored only as action evidence, excluded from ordinary logs/export, private access, 12-month cleanup.

### Risk: Account module disabled

Mitigation: Attendance declares `depends = [Account]`; runtime dependency validation must prevent unsafe effective enablement.

### Risk: default office not configured

Mitigation: Attendance remains default disabled and check-in/out fail closed until an active eligible location exists.

### Risk: export volume

Mitigation: filtered query, bounded/lazy iteration, queue threshold after profiling, private storage.

### Risk: PWA/offline replay

Mitigation: no offline official mutation queue; UI distinguishes cached display from server-confirmed attendance action.

---

## 31. Resolved planning decisions from REQUIREMENTS remaining notes

| REQUIREMENTS note | CREATE_PLAN resolution |
|---|---|
| Exact table/FK design | Five Attendance-owned tables including audit; FK to canonical Account employee profile/user; no duplicate employee persistence |
| Persistent vs derived status | Persist `checked_in/completed/voided`; derive absent, missing checkout, late, early leave and adjustment projections |
| Shift snapshot | Store shift code/name/start/end/grace snapshot on record |
| Concurrency/idempotency | Server deterministic `session_key`, DB unique constraint, transaction, row lock, safe duplicate-key reload/idempotent result |
| Shared audit reuse | No suitable stable shared contract found; Attendance-owned append-only audit events planned |
| Permission strings | Dot-delimited capability names from approved model; web capabilities in `permissions_by_guard.web` |
| Admin route names/components | `admin.attendance.*` routes and focused Livewire workspaces defined above |
| Export implementation/threshold | Shared foundation; all-matching/selected contract; private storage; provisional queue threshold >10k rows subject to implementation profiling |
| GPS cleanup | Null precise raw evidence after 12 months via canonical scheduler infrastructure; production enablement gate |
| Timezone display | `config('app.timezone')` unless an existing canonical company-timezone abstraction is proven before implementation |
| Overnight future safety | session/work-date + shift snapshot model; no unique user/date rule |

---

## 32. Explicit implementation gates

Before creating application code, user must explicitly approve this `CREATE_PLAN.md`.

After approval and before MR-1 code:

- re-read current `main`;
- verify `Modules/Attendance` still does not exist;
- re-read root provider/runtime registry source;
- re-check current Account and ClientPortal contracts;
- create a new implementation branch from current `main`;
- do not reuse the docs-only planning branch for implementation.

Additional production enablement gates:

- real active Attendance location configured;
- geofence/accuracy policy accepted;
- raw-GPS 12-month cleanup operational;
- required permissions assigned;
- runtime/Account dependency verified;
- focused + impacted tests pass;
- Admin/PWA manual smoke passes;
- Git clean after runtime toggle.

---

## 33. Approval decision

Proposed decision:

```text
CREATE_PLAN STATUS: WAITING FOR USER APPROVAL
```

If approved, the next action is **MR-1 — module skeleton + manifest + bootstrap/runtime contract** on a new implementation branch created from current `main`.
