# Attendance — Approved Requirements

## 1. Purpose

`Attendance` is a new `domain` module that owns employee attendance business logic and persistence.

It provides server-authoritative check-in/check-out, work-shift rules, attendance locations, GPS/geofence verification, attendance history, adjustment requests, Admin/HR operations, audit/history and reporting/export capabilities.

The Vietnamese UI name is **Chấm công**.

`ClientPortal` may expose Attendance through a PWA application adapter, but Attendance must not depend on ClientPortal and ClientPortal must not own Attendance business rules or persistence.

## 2. Scope

Release 1 includes:

- repository-compatible Attendance domain module;
- runtime enable/disable support;
- canonical employee identity integration;
- attendance locations with multiple-location-capable schema;
- one initial office configuration;
- configurable geofence radius;
- configurable GPS accuracy threshold;
- server-time authority;
- one default work shift;
- check-in;
- check-out;
- duplicate/retry/concurrency protection;
- attendance session/history;
- late, early-leave and worked-time facts;
- attendance adjustment requests;
- Admin/HR approval/rejection of adjustments;
- audit/history of sensitive changes;
- Admin dashboard;
- Admin records workspace;
- search, filters, reset filters and bounded pagination;
- backend authorization;
- ClientPortal Attendance PWA adapter;
- mobile/PWA UX;
- online-only official attendance mutations;
- Attendance export;
- focused automated tests and module documentation.

## 3. Actors / Roles

### Employee

An authenticated employee may:

- view today's attendance state and default shift;
- check in;
- check out;
- view only their own attendance history unless granted elevated permission;
- submit an attendance adjustment request;
- view the status of their own adjustment requests.

An employee may not:

- directly edit canonical attendance records;
- approve their own adjustment request;
- alter official server timestamps;
- bypass geofence policy from the client.

### Attendance Admin / HR

Authorized Attendance Admin/HR users may:

- view Attendance dashboard and records;
- search/filter records;
- review attendance evidence;
- review and approve/reject adjustment requests;
- manage Attendance locations;
- manage work shifts/policies;
- export Attendance data;
- inspect audit/history;
- void/invalidate or correct records only through authorized, reasoned and auditable workflows.

### Manager

Manager-scoped review is not required in Release 1. It is a SHOULD/FUTURE capability unless an existing organizational scope is deliberately integrated later.

## 4. Canonical Ownership and Module Boundary

Attendance owns:

- attendance business rules;
- attendance persistence;
- attendance models;
- attendance migrations;
- attendance services/actions;
- attendance policies/permissions;
- attendance Admin workflows;
- attendance calculations;
- geofence validation;
- adjustment workflows;
- Attendance-specific audit/history.

Attendance does not own:

- shared authentication/session/logout;
- canonical user identity;
- canonical employee profile data;
- ClientPortal shell/navigation/application registry;
- payroll/salary;
- leave;
- overtime approval;
- continuous location tracking;
- biometric identity.

Preferred direction:

```text
Employee
  -> ClientPortal PWA adapter
  -> Attendance public/domain capability
  -> Attendance domain/persistence
```

Forbidden direction:

```text
Attendance -> ClientPortal
```

## 5. Cross-Module Dependencies

### Direct dependency

Approved baseline:

```text
Attendance -> Account
```

`Account` is the canonical source of employee profile identity and already links employee profiles to canonical users.

Attendance must not create a duplicate `employees` or duplicate employee-profile table.

Attendance does not require a direct ClientPortal dependency.

Whether an additional explicit dependency is required by current runtime rules must be verified during `/create-module`, but no unnecessary dependency may be added.

### Consumer integration

ClientPortal may add:

```text
Modules/ClientPortal/Applications/Attendance/
```

with application key:

```text
attendance
```

and source module:

```text
Attendance
```

The adapter is exposed only while Attendance is effectively enabled and the user has the required client permission.

## 6. Bootstrap Contract

```text
Manifest          : config/module.php
Type              : domain
Dependencies      : Account
Module Provider   : not required initially
Config            : yes
Web routes        : yes
API routes        : no for Release 1
Migrations        : yes
Livewire          : yes for Admin UI where appropriate
Blade components  : only when reusable/required
Console commands  : no for Release 1
Runtime state     : supported
Runtime storage   : no special filesystem for MVP
External services : none required for Release 1
Queue             : not required for synchronous check-in/check-out
```

The module must use the repository's existing `Modules/ModuleServiceProvider.php` discovery/bootstrap behavior and must not introduce `module.json`, nwidart infrastructure, a second module registry, duplicate provider registration or parallel discovery logic.

## 7. Runtime-State Requirements

Attendance must support current repository runtime state.

Approved default:

```text
default_enabled = false
```

Manifest/default source state and runtime effective state are separate concerns.

Attendance must use the repository's `ModuleStateRepository` / `ModuleStateResolver` behavior and must not read or write `storage/app/system/module-state.json` directly.

When Attendance is disabled:

- Attendance runtime routes/UI are unavailable according to repository conventions;
- ClientPortal must not expose the Attendance app;
- historical Attendance data remains intact;
- no Attendance table is dropped;
- unrelated modules continue to operate;
- runtime toggle must leave Git clean.

## 8. Time Authority

Official attendance timestamps must use server time.

Device/browser clock must not determine canonical `checked_in_at` or `checked_out_at`.

Client time may only be retained as optional diagnostic metadata if later justified.

Displayed timezone follows the existing project/company timezone convention discovered during implementation planning.

## 9. Work Shift Rules

Release 1 uses one default work shift.

Approved default:

```text
Shift       : 08:00–17:00
Late grace  : 5 minutes
Early grace : 5 minutes
```

Release 1 does not require employee-specific shift assignment.

Schema/service design should avoid blocking future:

- employee-specific shifts;
- multiple shifts;
- split shifts;
- overnight shifts.

Conceptual shift data:

```text
name
code
start_time
end_time
late_grace_minutes
early_leave_grace_minutes
is_active
```

Historical records should preserve sufficient shift facts/snapshots so later shift configuration edits do not silently rewrite the historical meaning of attendance records.

## 10. Attendance Location / Geofence Rules

Release 1 schema supports multiple Attendance locations even though one office is initially configured.

Approved baseline:

```text
Initial office count : 1
Default radius       : 150 meters, configurable per location
Max GPS accuracy     : 100 meters, configurable
Check-in geofence    : required
Check-out geofence   : required
```

Conceptual location data:

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

The server should resolve an eligible location from submitted location evidence rather than trusting arbitrary client-selected location identity.

## 11. Geolocation Evidence

Attendance actions may capture:

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

Coordinates must be validated:

```text
latitude  : -90..90
longitude : -180..180
accuracy  : >= 0
```

Verification results should preserve meaningful states, including as applicable:

```text
verified
outside_geofence
accuracy_too_low
location_unavailable
location_inactive
not_required
```

Geofence is a verification signal, not cryptographic proof of physical presence. GPS spoofing cannot be completely prevented by a web/PWA implementation.

## 12. GPS Privacy and Retention

Attendance is not an employee tracking system.

Release 1 must not:

- continuously track employee location;
- perform background GPS tracking;
- collect location throughout the workday;
- request location outside an attendance action or explicit location refresh.

Approved raw GPS retention baseline:

```text
Precise/raw GPS evidence: 12 months
Attendance business record: retained according to company operational/record policy
```

After the raw-GPS retention period, implementation may retain non-precise verification facts such as verification result, resolved location and distance when required for business history, subject to the final retention implementation plan.

The implementation must avoid logging precise location data unnecessarily.

## 13. Offline Policy

Official check-in and check-out require an online connection.

The PWA may display cached shell/history according to existing ClientPortal conventions, but it must not queue an offline attendance mutation as an official attendance event.

Reason: official attendance requires server time, current authorization and server-side validation.

## 14. Attendance Session Model

Attendance must be designed around attendance sessions/shift instances rather than a hard one-row-per-user-per-calendar-date assumption.

Do not use a schema rule equivalent to:

```text
UNIQUE(user_id, date)
```

when it would block future multiple or overnight shift support.

Conceptual record identity should account for:

- employee identity;
- work date;
- shift/shift snapshot;
- attendance session.

## 15. Check-In Workflow

```text
Authenticated employee
  -> resolve employee eligibility
  -> resolve default/current shift
  -> receive and validate current geolocation evidence
  -> validate GPS accuracy
  -> resolve eligible Attendance location
  -> validate required geofence
  -> validate attendance session state
  -> atomically create/update canonical check-in
  -> preserve verification evidence
  -> record audit/history
  -> return success state
```

The server decides whether check-in is accepted.

The workflow must be transaction-safe and protect against double taps, request retries and concurrent requests.

## 16. Check-Out Workflow

```text
Authenticated employee
  -> resolve active attendance session
  -> validate actor/state
  -> collect and validate location evidence
  -> require approved check-out geofence policy
  -> persist server-authoritative check-out atomically
  -> calculate attendance facts
  -> record audit/history
  -> return completed state
```

Check-out is also subject to duplicate/retry/concurrency protection.

## 17. Attendance Facts

Attendance domain services own attendance calculations.

Required facts include where applicable:

```text
late_minutes
early_leave_minutes
worked_minutes
```

These calculations must not live in Blade, Livewire views or ClientPortal adapters.

Historical meaning must remain stable when shift configuration changes; create-plan must define the precise snapshot strategy.

## 18. Attendance States

Core lifecycle:

```text
NOT_STARTED
  -> CHECKED_IN
  -> COMPLETED
```

Exception/business states may include:

```text
ABSENT
MISSING_CHECKOUT
ADJUSTMENT_PENDING
ADJUSTED
VOIDED
```

`/create-module` must distinguish persistent states from derived states before schema implementation.

## 19. Adjustment Workflow

Employees do not directly modify canonical attendance records.

Approved workflow:

```text
SUBMIT
  -> PENDING
  -> APPROVED | REJECTED
```

Initial reviewer is Attendance Admin / HR.

Release 1 does not require Manager approval.

Adjustment data may include:

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

Approval must modify canonical attendance only through an authorized Attendance service and must preserve the original/change history.

Remote/WFH attendance is out of scope for Release 1. Legitimate exceptional cases may use the adjustment workflow.

## 20. Audit / History Requirements

Sensitive actions must be auditable, including:

- check-in;
- check-out;
- adjustment submission;
- adjustment approval/rejection;
- manual authorized correction;
- record void/invalidation;
- Attendance location configuration changes;
- shift/policy changes.

Audit/history should preserve as applicable:

```text
actor
action
target
before
after
timestamp
reason
```

If no suitable canonical shared audit service exists, Attendance may own a domain-specific audit/event table. `/create-module` must verify reuse before adding a new audit implementation.

## 21. Anti-Fraud Baseline

Release 1 MUST preserve:

- authenticated employee identity;
- server time;
- GPS coordinates within approved retention rules;
- GPS accuracy;
- geofence result;
- resolved Attendance location;
- audit/history.

Optional metadata such as IP address or user-agent/device context may only be added when compatible with repository security/privacy conventions.

Release 1 does not include:

- biometric/face verification;
- device attestation;
- QR attendance;
- company-network enforcement;
- advanced fraud scoring.

## 22. PWA Requirements

Proposed ClientPortal application key:

```text
attendance
```

Proposed adapter path:

```text
Modules/ClientPortal/Applications/Attendance/
```

The PWA should show:

- current date/time presentation;
- default shift;
- current attendance state;
- location verification state;
- primary `Vào ca` or `Ra ca` action;
- recent attendance history;
- adjustment entry points where appropriate.

Location states must be explicit, including as applicable:

```text
requesting_location
permission_denied
location_unavailable
accuracy_low
outside_area
verified
```

Permission denial must not silently bypass geofence.

ClientPortal must remain a thin adapter and must call Attendance domain capabilities rather than duplicate attendance logic.

## 23. Admin Dashboard

Conceptual route:

```text
/admin/attendance/dashboard
```

The dashboard should answer operational questions such as:

- employees expected today;
- checked in;
- not checked in;
- late;
- checked out;
- missing checkout;
- pending adjustments.

Exact expected-employee semantics must use canonical employee status/identity and the approved default shift model.

## 24. Admin Records Workspace

Conceptual route:

```text
/admin/attendance/records
```

The workspace must follow `.codex/standards/ADMIN_UI_STANDARD.md`.

Required design evaluation/behavior:

- employee/name/code search;
- date/date-range filter;
- location filter;
- shift filter;
- status filter;
- late filter;
- early-leave filter;
- missing-checkout filter;
- adjustment-state filter where useful;
- reset filters;
- bounded pagination;
- explicit loading/empty/error states;
- responsive table/workspace behavior;
- row selection only where useful;
- no hard-delete action.

Approved page-size choices:

```text
10 / 25 / 50 / 100
```

No unbounded `All` option.

Filter and page-size changes must reset pagination where required.

## 25. Record Invalidation / Destructive Rules

Hard-delete is not a normal Attendance operation.

If a record is invalid, prefer an authorized, auditable void/invalidation model such as:

```text
voided_at
voided_by
void_reason
```

or an equivalent approved design.

Sensitive mutations require backend authorization and explicit reason/audit.

## 26. Import / Export Requirements

### Export — Release 1

Attendance export is approved for Release 1.

Export should use the canonical shared import/export foundation where applicable.

Potential exported data:

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

Selected-row export should follow repository selection/export conventions when row selection is implemented.

Large export scope must remain bounded/streamed/queued as appropriate and must not load an unbounded production dataset into memory.

### Import — Release 1

Direct import that creates/overwrites canonical Attendance history is NOT approved for Release 1.

Future controlled import may cover:

- legacy attendance migration;
- shift assignment;
- work calendar.

Any future import requires separate validation, unique-key and audit decisions.

## 27. Reporting Requirements

Release 1 / near-term reporting should support:

- daily attendance;
- monthly employee attendance;
- late arrivals;
- missing check-outs;
- pending adjustments.

Future reporting may include:

- department/manager summary;
- overtime;
- absence/leave reconciliation;
- payroll export.

## 28. Permissions

Exact permission naming must be normalized against current repository conventions during `/create-module`.

Approved capability model is conceptually:

### Admin/domain permissions

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

### web/ClientPortal permissions

```text
client.attendance.access
attendance.record.view-own
attendance.check-in
attendance.check-out
attendance.adjustment.create
```

Backend authorization is mandatory. UI visibility alone is not authorization.

## 29. Security Requirements

Release 1 MUST include:

- authenticated employee/admin boundaries;
- capability-specific backend authorization;
- CSRF protection for web mutations;
- validated coordinate/accuracy inputs;
- server-time authority;
- transaction and race protection;
- employee data isolation;
- no raw internal exception exposure;
- audit/history for sensitive changes;
- no silent destructive deletion;
- no direct browser control over canonical timestamps, location identity, model class, table name or server storage path.

## 30. Database Requirements

Potential owned tables:

```text
attendance_locations
attendance_shifts
attendance_records
attendance_adjustment_requests
```

Potential domain audit/history table may be added only if no reusable canonical audit infrastructure exists.

`attendance_shift_assignments` is NOT required in Release 1.

Attendance records must reference canonical employee/user identity without duplicating Account employee-profile ownership.

Indexes/constraints must be designed for actual search/filter and concurrency behavior.

A simplistic unique key on `(user_id, work_date)` must not be used if it blocks the approved session/future shift model.

## 31. Service / Transaction Boundaries

Controllers and Livewire components must remain thin.

Attendance business logic belongs in Attendance services/actions.

Exact class names are not yet fixed, but responsibilities include:

- check-in orchestration;
- check-out orchestration;
- geofence calculation;
- shift resolution;
- attendance calculation;
- adjustment workflow;
- Attendance query/report/export orchestration.

Check-in/check-out and adjustment approval require appropriate transactions and locking/idempotency controls.

## 32. Notifications / Jobs

Notifications are not required for core Release 1 usability.

SHOULD capabilities include:

- adjustment approved/rejected notification;
- missing-checkout reminder.

Check-in/check-out must remain synchronous.

Queues may be used later for:

- large exports;
- reports;
- notifications;
- reminders;
- monthly aggregation.

## 33. Runtime Storage / Docker

No special Attendance runtime filesystem is required for Release 1 core.

If export files or future supporting documents require storage, use Laravel Storage and repository private-storage conventions.

No `chmod 777` or custom module-state file handling is permitted.

## 34. MUST HAVE

- Attendance domain ownership;
- Account employee identity integration;
- runtime ON/OFF;
- default disabled state;
- multiple-location-capable schema;
- one configured office initially;
- configurable 150m default geofence;
- configurable 100m maximum GPS accuracy baseline;
- required geofence for check-in and check-out;
- server-time authority;
- default 08:00–17:00 shift;
- 5-minute late grace;
- 5-minute early-leave grace;
- check-in/check-out;
- concurrency/idempotency safety;
- Attendance history;
- late/early/worked facts;
- adjustment submit + Admin/HR review;
- audit/history;
- Admin dashboard;
- Admin records workspace;
- search/filter/reset/bounded pagination;
- backend authorization;
- ClientPortal PWA adapter;
- online-only official mutations;
- Attendance export;
- privacy-safe GPS collection;
- 12-month raw GPS retention baseline;
- focused tests and documentation.

## 35. SHOULD HAVE

- employee-specific shift assignments;
- manager-scoped views;
- notifications;
- missing-checkout reminders;
- richer monthly summary;
- supporting documents for adjustment requests;
- selected-row export where useful.

## 36. FUTURE

- holiday calendar;
- leave integration;
- overtime workflow;
- payroll integration/export contract;
- remote/WFH policy;
- field-work attendance;
- dynamic/signed QR attendance;
- trusted-device validation;
- network verification where technically reliable;
- biometric/face verification;
- native/device attestation;
- advanced fraud scoring.

## 37. Explicit Out of Scope — Release 1

- payroll/salary calculation;
- leave management;
- overtime approval;
- remote/WFH attendance;
- field-work attendance policy;
- continuous location tracking;
- background GPS tracking;
- offline official check-in/check-out;
- biometric/face recognition;
- native mobile application;
- direct bulk import/overwrite of canonical Attendance history;
- normal hard-delete of Attendance records;
- manager approval requirement;
- employee-specific shift assignment UI/workflow.

## 38. Acceptance Criteria — Employee/PWA

Release 1 is acceptable when:

- an eligible authenticated employee can open Attendance from ClientPortal when Attendance is enabled;
- the employee sees the default shift and current Attendance state;
- the app requests location only when needed;
- invalid/denied/low-accuracy/outside-geofence states are clearly reported;
- check-in succeeds only after server-side validation;
- official timestamp comes from the server;
- duplicate/retried requests do not create duplicate sessions;
- check-out requires valid active attendance and required geofence;
- the employee can view only their own history by default;
- the employee can submit an adjustment request;
- offline mode cannot create official attendance mutations.

## 39. Acceptance Criteria — Admin

Release 1 is acceptable when:

- authorized Admin/HR can open Attendance dashboard;
- dashboard shows useful operational status;
- Attendance records can be searched and filtered;
- filter reset is available;
- pagination is bounded and follows Admin UI standard;
- authorized reviewers can approve/reject adjustments;
- unauthorized users are denied sensitive mutations server-side;
- Attendance records are not silently hard-deleted;
- sensitive changes are auditable;
- Attendance data can be exported through an approved bounded mechanism.

## 40. Acceptance Criteria — Architecture / Runtime

- `Modules/Attendance` is discoverable through `Modules/ModuleServiceProvider.php`;
- manifest uses repository-supported fields only;
- Attendance is `domain`;
- dependency design follows canonical ownership;
- runtime ON/OFF uses repository state infrastructure;
- runtime toggles do not modify tracked manifest files;
- Git remains clean after runtime toggle operations;
- disabling Attendance preserves historical data;
- Attendance does not depend on ClientPortal;
- ClientPortal adapter disappears when Attendance source module is disabled;
- ClientPortal adapter contains presentation/orchestration only, not Attendance business logic;
- no duplicate employee identity/profile persistence is introduced.

## 41. Required Verification During Implementation

At minimum evaluate/add tests for:

- module bootstrap/discovery;
- manifest/default disabled state;
- runtime override ON/OFF;
- dependency behavior;
- runtime toggle Git-clean contract;
- check-in success;
- duplicate/concurrent check-in protection;
- outside geofence rejection;
- poor GPS accuracy rejection;
- check-out success;
- check-out without active check-in rejection;
- required check-out geofence;
- late calculation;
- early-leave calculation;
- worked-time calculation;
- employee data isolation;
- Admin authorization;
- adjustment submit;
- adjustment approve/reject;
- audit/history;
- Admin search/filter/reset/pagination;
- export scope;
- ClientPortal adapter availability while Attendance ON/OFF;
- PWA route authorization.

## 42. Approved Decisions

The following previously open decisions are approved:

1. Default shift is `08:00–17:00`.
2. Late grace is `5 minutes`.
3. Early-leave grace is `5 minutes`.
4. Check-in requires geofence.
5. Check-out also requires geofence.
6. Raw precise GPS evidence retention baseline is `12 months`.
7. Release 1 uses one default shift; employee-specific assignment is deferred.
8. Remote/WFH attendance is out of scope for Release 1; exceptional cases use adjustment workflow.
9. Attendance export is included in Release 1.
10. Direct canonical Attendance import is not included in Release 1.
11. Attendance is proposed as a `domain` module.
12. Attendance does not depend on ClientPortal.
13. Attendance integrates with canonical Account employee identity instead of creating duplicate employee persistence.
14. Initial runtime default is disabled until configured/accepted.

## 43. Remaining Non-Blocking Notes

These do not block `/create-module` but must be resolved in `CREATE_PLAN.md` before implementation where applicable:

- exact Attendance table column types and FK strategy;
- exact persistent-vs-derived status representation;
- exact shift snapshot columns;
- exact concurrency/locking/idempotency implementation;
- whether a reusable project audit infrastructure can satisfy Attendance or Attendance needs a domain event/audit table;
- exact permission strings after final normalization against repository conventions;
- exact Admin route names and Livewire component structure;
- exact export implementation and queue threshold;
- exact raw-GPS cleanup mechanism after 12 months;
- exact timezone display convention;
- exact future-safe overnight-shift schema behavior.

## 44. CREATE-MODULE READINESS

```text
Business requirements : READY
Module boundary       : READY
Bootstrap Contract    : READY
Dependencies          : READY
Database              : READY
Permissions           : READY
Workflow              : READY
Runtime state         : READY
Docker/runtime storage: NOT APPLICABLE

Overall: READY FOR /create-module
```

This document is the approved business specification for:

```text
/create-module Attendance
```

The next task must create `docs/modules/Attendance/CREATE_PLAN.md` and stop at its separate approval gate before any Attendance application source code is created.
