# Admission Livewire Refactor Plan — Admin/Applications/Index

## 1. Scope and Approval Gate

Target component:

`Modules/Admission/Livewire/Admin/Applications/Index.php`

Component key:

`admin-applications-index`

Livewire alias:

`admission.admin.applications.index`

This plan is based on:

- `.codex/tasks/refactor-livewire.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `docs/modules/Admission/livewire/admin-applications-index/ANALYSIS.md`
- current component/service/model/export/view behavior.

This is a focused refactor. It does not add new Admission business features.

Implementation is forbidden until the user explicitly approves this plan.

## 2. Refactor Goal

Refactor `Admin/Applications/Index` so that it:

1. enforces capability-specific authorization at every sensitive Livewire action boundary;
2. moves review/delete/query workflow responsibilities out of Livewire and into the Admission domain service boundary;
3. records reviewer metadata using the authenticated `admin` guard consistently;
4. makes approve/reject transitions concurrency-safe without breaking model events or PDF job dispatch;
5. prevents destructive bulk operations from trusting unvalidated client-controlled IDs;
6. removes unbounded list rendering and memory-bound export behavior;
7. aligns Blade capability gates with declared Admission permissions;
8. improves destructive/loading/empty-state UX while preserving the existing page structure and Livewire alias;
9. adds focused runtime tests for authorization, transition behavior, filtering, deletion, and export.

## 3. Public Contracts to Preserve

Unless explicitly noted below, preserve:

- route `admin.admission.index`;
- URI `/admin/admission`;
- Livewire alias `admission.admin.applications.index`;
- component class path;
- parent page mount contract;
- search fields: student name, identity number, phone;
- status filter semantics;
- registration-class filter semantics;
- default page size `10`;
- sort order: latest updated/created records first;
- action method names where practical: `approve`, `reject`, `delete`, `deleteSelected`, `export`;
- status values `pending`, `approved`, `rejected`, and existing import/other behavior;
- Excel download filename `applications.xlsx` unless the existing exporter requires a technical adjustment;
- model-event contract that approval may dispatch `GenerateAdmissionPdfJob` after commit when no PDF exists;
- model deletion behavior that removes generated PDF/Word files;
- existing route-level permission middleware.

Intentional compatibility change:

- remove the unbounded `perPage = all` option from the admin list. This is required by `ADMIN_UI_STANDARD.md` and avoids loading the entire production dataset into Livewire memory.

No route, database table, permission name, status name, or storage path will be renamed in this refactor.

## 4. Priority Summary

### P0

- Sensitive Livewire actions lack capability authorization.
- Bulk delete trusts client-controlled `selected` IDs and performs destructive mutation without server-side authorization.

### P1

- Approve/reject business workflow lives directly in Livewire.
- Reviewer metadata uses the wrong/default authentication context and model fillable contract is incomplete.
- Approve/reject uses a read-then-write race-prone transition.
- `perPage = all` is unbounded.
- Export is memory-bound through `FromCollection` + `get()`.
- Blade uses `create_admission` for unrelated capabilities.
- Destructive/loading/empty states are incomplete.

### P2

- Hard-coded class filter options may drift from school settings.
- Table rows lack stable `wire:key`.
- Selection controls are visible to users who cannot delete.
- Accessibility labels can be improved.

## 5. Livewire PHP Changes

File:

`Modules/Admission/Livewire/Admin/Applications/Index.php`

### 5.1 Keep Livewire responsible for UI state only

Keep in Livewire:

- `search`;
- `filterStatus`;
- `filterClass`;
- `perPage`;
- `selected`;
- `selectAll`;
- pagination reset/selection reset behavior;
- invoking domain service methods;
- returning the Excel download response;
- rendering the Blade view.

Remove from Livewire:

- direct approval/rejection model updates;
- direct bulk/single database deletion workflow;
- duplicated application query construction where it can safely be delegated to the Admission service;
- default-guard reviewer lookup.

### 5.2 Normalize and bound user-controlled state

Define allowed page sizes, proposed:

`[5, 10, 20, 50]`

When `perPage` is not an allowed value, normalize it to `10` rather than executing an unbounded query.

Validate/normalize selected IDs before passing them to the service:

- integer IDs only;
- positive values only;
- unique values;
- discard malformed values;
- no direct `whereIn()` mutation inside Livewire.

Status/class filters remain query inputs and must continue using parameterized Eloquent queries.

### 5.3 Capability authorization at action boundaries

Before any mutation/download-producing action, resolve the current authenticated admin explicitly from the `admin` guard.

Required capability mapping:

- `approve()` → `approve_admission`
- `reject()` → `reject_admission`
- `delete()` → `delete_admission`
- `deleteSelected()` → `delete_admission`
- `export()` → `export_admission`

Authorization must happen server-side inside the Livewire action even though Blade and routes also hide/protect capabilities.

A user with only `view_admission` must receive an authorization failure when attempting to invoke any of the above actions directly through Livewire.

Do not use `create_admission` as a substitute capability.

### 5.4 Explicit admin reviewer identity

`approve()` and `reject()` must obtain reviewer identity from the authenticated `admin` guard and pass the reviewer ID to the service.

Do not rely on plain `Auth::id()` because the application's default guard can be `web`.

### 5.5 Action result behavior

Preserve current no-op semantics for an application that is no longer `pending`, but make the result deterministic and testable.

Recommended behavior:

- service returns whether a transition was applied;
- Livewire may set a session/flash message when transition succeeds or is stale;
- do not introduce a new modal or business flow in this refactor.

If existing repository UI conventions favor silent stale-transition handling, retain silence and cover it with tests. Do not change business rules without approval.

## 6. Service Boundary Changes

Primary file:

`Modules/Admission/Services/AdmissionService.php`

Use the existing Admission domain service rather than introducing another service solely for style.

Add focused methods with clear names, for example:

- filtered/paginated application listing;
- approve pending application;
- reject pending application;
- delete one application;
- delete multiple applications;
- reusable filtered export query.

Exact method names may follow repository naming conventions during implementation, but responsibilities must remain as described below.

### 6.1 Listing/query responsibility

Move the reusable search/filter/sort query construction into the service.

Inputs:

- search;
- status;
- registration class.

The query contract must preserve current filters and sort order.

Livewire requests a paginator using a bounded page size.

The exporter reuses the same filter semantics so list and export cannot drift.

### 6.2 Approval workflow

Service owns the status transition.

Required behavior:

1. start a database transaction;
2. load the target row with `lockForUpdate()`;
3. fail with normal not-found behavior for missing ID;
4. only transition when current status is `pending`;
5. set:
   - `status = approved`;
   - `approved_at = now()`;
   - `approved_by = authenticated admin ID`;
6. clear stale rejection metadata only if current domain behavior/records require it; otherwise do not add new behavior silently;
7. save through the Eloquent model so existing `updated` model events still fire;
8. commit transaction.

Do not replace the model save with a raw query-builder update because that would bypass Eloquent events and could break `GenerateAdmissionPdfJob` dispatch.

Existing job dispatch uses `afterCommit`; this contract must remain intact.

### 6.3 Rejection workflow

Mirror approval with:

- `status = rejected`;
- `rejected_at = now()`;
- `rejected_by = authenticated admin ID`;
- row lock + transaction;
- Eloquent save to preserve model semantics.

Do not add a rejection-reason feature in this refactor. That is a business feature and would require a separate `CHANGE_PLAN.md`.

### 6.4 Delete workflow

Service owns single and multi-record deletion.

Required safeguards:

- receive normalized IDs only;
- query only actual matching `AdmissionApplication` rows;
- call model `delete()` for each record so existing deletion hooks remain active;
- return a useful deleted-count/result to Livewire;
- never use a direct query-builder bulk `delete()` because it would bypass model deleting hooks and generated-file cleanup.

Transaction note:

The current model deletes filesystem artifacts inside `deleting` and catches filesystem failures. Filesystem operations are not database-transactional. Therefore this focused refactor must not claim full atomic DB/filesystem rollback.

Preferred scoped behavior:

- preserve current model-hook deletion semantics;
- perform deterministic record-by-record deletes through the service;
- document/test partial-failure semantics where feasible;
- do not redesign file lifecycle in this component refactor.

If implementation discovers that safe deletion requires moving file cleanup out of model events, STOP and update this plan because that materially expands scope.

## 7. Model Changes

File:

`Modules/Admission/Models/AdmissionApplication.php`

Add review metadata to the model persistence contract:

- `approved_at`;
- `approved_by`;
- `rejected_at`;
- `rejected_by`.

Add appropriate casts for review timestamps:

- `approved_at` → datetime;
- `rejected_at` → datetime.

Reviewer IDs may remain integer/native attributes unless repository convention requires explicit integer casts.

Do not rewrite the existing applied migration.

No new schema migration is required for these four columns because the existing migration already creates them.

Do not add foreign keys in this focused refactor; that is a schema-hardening change with broader migration/rollback implications.

## 8. Concurrency and Data Integrity

### 8.1 Approve/reject race

Use transaction + row lock to serialize concurrent review transitions on the same application.

Expected contract:

- exactly one transition from `pending` can win;
- a later concurrent action sees the new non-pending state and becomes a no-op/stale result;
- final status cannot oscillate merely because two concurrent requests read `pending` before either write commits.

### 8.2 Model events

Approval must continue to trigger the model `updated` event and therefore preserve PDF job behavior.

Tests must fake/inspect the queue where appropriate and verify no duplicate dispatch is introduced by the refactor.

### 8.3 Mass assignment

With review metadata added to `$fillable`, service-level `fill()`/`update()` will persist the intended fields consistently.

Do not use unguarded mass assignment.

## 9. Export Changes

File:

`Modules/Admission/Exports/ApplicationsExport.php`

Goal: preserve filtered Excel download while avoiding full collection loading.

Preferred refactor:

- replace `FromCollection` with `FromQuery` where compatible with current column mapping/headings;
- reuse the Admission service's filter/query semantics;
- retain the existing excluded-field policy unless a security review during implementation proves a field must be excluded to preserve an already-declared permission boundary;
- keep date/array formatting behavior through mapping concerns such as `WithMapping` if required;
- avoid `->get()` over the full filtered dataset;
- keep export synchronous for this scoped refactor unless actual library constraints force a queued export.

Do not introduce a new export engine if Maatwebsite Excel already supports the required query/chunk behavior.

If `FromQuery` cannot preserve dynamic columns safely, use the closest chunked/lazy supported concern and document the choice.

## 10. Livewire Blade Changes

File:

`Modules/Admission/resources/views/livewire/admin/applications/index.blade.php`

### 10.1 Correct permission gates

Use the declared capabilities independently:

- Export controls → `export_admission`
- Import form → `import_admission`
- Approve button → `approve_admission`
- Reject button → `reject_admission`
- Delete button/bulk-delete selection → `delete_admission`

Do not wrap unrelated actions in `create_admission`.

The page-level create button remains governed by `create_admission` and is outside this component's source unless a direct inconsistency must be corrected.

### 10.2 Selection controls

Selection checkboxes and select-all should only be shown when the admin has `delete_admission`, because selection currently exists solely for bulk delete.

Keep `wire:model.live` behavior unless testing shows an unnecessary request issue.

Add a stable row identity, e.g. `wire:key` using application ID.

### 10.3 Remove unbounded All option

Remove `All` from page-size options.

Keep bounded options:

- 5
- 10
- 20
- 50

### 10.4 Loading/disabled states

Add `wire:loading.attr="disabled"` / target-aware disabled/loading feedback for:

- approve;
- reject;
- delete;
- bulk delete;
- export.

Import is a normal HTML POST form; retain its current client-side file-selected disabled behavior and rely on route middleware for server-side `import_admission` enforcement.

Avoid a global loading overlay that blocks unrelated controls unnecessarily.

### 10.5 Destructive confirmation

Add confirmation before:

- single delete;
- bulk delete.

Prefer an existing canonical shared confirmation pattern if one exists in the repository.

If no shared component exists, use the smallest repository-consistent Alpine/Livewire confirmation mechanism; do not introduce a new modal framework.

### 10.6 Empty state

When no records match filters, render a clear table empty state such as “Không có hồ sơ phù hợp”.

### 10.7 Accessibility

Add accessible labels for:

- select-all checkbox;
- row selection checkbox;
- page-size selector where practical.

Maintain visible text status/action controls.

## 11. Registration Class Filter

The Blade currently hard-codes registration-class values.

During implementation, verify current `SchoolSettingService::registrationClasses()` availability and contract.

If it is already the canonical source used by Admission registration screens, reuse it through the service/component and preserve the same option values.

If the service is not reliably available or would introduce a new cross-boundary dependency, leave the current hard-coded values in this focused refactor and record the issue as P2 follow-up.

Do not invent or rename class values.

## 12. Route and Controller Changes

Expected: no route-name or URI changes.

No controller business logic should be added.

The parent route already enforces `auth:admin` + `view_admission` for viewing and should remain the list-page boundary.

Capability-specific mutations remain Livewire action responsibilities and must enforce their own permissions.

The import POST route already has its own permission middleware according to current Admission route tests; no new import workflow is part of this component refactor.

## 13. Database / Migration Changes

Expected: no new migration.

Do not rewrite applied migrations.

Existing review metadata columns remain unchanged.

No new indexes are required for this focused refactor.

Search performance for `%term%` name/phone matching remains a measurement-driven P2 concern rather than adding speculative indexes/full-text infrastructure.

## 14. Test Plan

Add a focused test file, proposed:

`tests/Feature/Admission/AdmissionApplicationsIndexLivewireTest.php`

Exact organization may follow existing test conventions.

### P0 authorization tests

Verify an authenticated admin with only `view_admission` cannot invoke:

- `approve()`;
- `reject()`;
- `delete()`;
- `deleteSelected()`;
- `export()`.

Verify users with the specific capability can invoke only the intended action.

### Review workflow tests

Verify:

- pending application can be approved;
- approval records `approved_at` and the authenticated admin ID from the `admin` guard;
- pending application can be rejected;
- rejection records `rejected_at` and the authenticated admin ID;
- non-pending application is not transitioned again;
- approval preserves/dispatches expected PDF-generation job behavior;
- no duplicate job is introduced for stale/non-pending transition.

Concurrency testing:

- where database test infrastructure supports transactional locking semantics, test competing transitions;
- otherwise test the service's status re-check under lock and document DB-driver limitations of the automated test environment.

### Delete tests

Verify:

- unauthorized single delete is forbidden;
- unauthorized bulk delete is forbidden even when `selected` is manually populated;
- malformed/tampered selected IDs are normalized and do not expand deletion scope;
- authorized single delete removes the target record;
- authorized bulk delete removes only selected existing records;
- model deletion hooks are still invoked rather than bypassed by query-builder bulk delete.

Filesystem behavior should be faked where feasible without changing the model's production contract.

### Listing/filter tests

Verify:

- default page size is 10;
- invalid/unbounded page-size input is normalized;
- search by name works;
- search by identity number works;
- search by phone works;
- status filter works;
- class filter works;
- sort order remains latest first;
- `All` is not an accepted runtime page-size mode.

### Export tests

Verify:

- export requires `export_admission`;
- exported query obeys current search/status/class filters;
- export does not rely on an unbounded `FromCollection::get()` implementation;
- headings/column exclusions remain compatible;
- filename remains compatible.

### Blade/UI tests where practical

Verify capability-specific controls are not exposed to users lacking the corresponding permission.

Do not rely on Blade visibility tests as the sole authorization verification.

## 15. Files Expected to Change

Required/likely:

1. `Modules/Admission/Livewire/Admin/Applications/Index.php`
2. `Modules/Admission/resources/views/livewire/admin/applications/index.blade.php`
3. `Modules/Admission/Services/AdmissionService.php`
4. `Modules/Admission/Models/AdmissionApplication.php`
5. `Modules/Admission/Exports/ApplicationsExport.php`
6. `tests/Feature/Admission/AdmissionApplicationsIndexLivewireTest.php` (new)

Documentation after implementation if behavior materially changes:

7. `docs/modules/Admission/livewire/admin-applications-index/ANALYSIS.md`
8. `docs/modules/Admission/INFORMATION.md` only if module runtime information changes materially.
9. `docs/modules/Admission/README.md` only if user-facing/module usage documentation changes materially.

Expected not to change:

- Admission route names/URIs;
- Admission migrations;
- other Livewire components;
- unrelated modules;
- generated document storage paths;
- permission names.

## 16. Explicit Non-Goals

This refactor will not:

- add rejection reason capture;
- add new admission statuses;
- redesign the full Admission admin UI;
- rebuild the registration form;
- change public admission search;
- add an Admission admin menu entry;
- add reviewer foreign keys;
- redesign model file lifecycle globally;
- add audit-log infrastructure if none is already canonical;
- introduce a new import engine;
- queue exports unless necessary to preserve production safety with the existing Excel library;
- modify unrelated Admission components.

Any of the above requires a separate plan/change approval if later requested.

## 17. Rollback / Recovery Notes

Implementation should be kept as small reviewable commits where possible.

No schema migration means rollback is code-only for this refactor.

If review transition behavior fails during verification:

- revert service/Livewire transition changes together;
- retain no partial database schema state because no migration is introduced.

If export compatibility fails:

- revert only exporter/query implementation while retaining authorization fixes;
- do not restore unbounded Livewire `All` mode merely to fix export.

If deletion semantics reveal unsafe filesystem/DB behavior beyond current known hooks:

- stop implementation;
- update this plan with a dedicated file-lifecycle scope;
- obtain user approval before changing model event architecture.

## 18. Acceptance Criteria

Implementation is accepted only when all applicable criteria pass:

### Security

- [ ] `approve()` enforces `approve_admission` server-side.
- [ ] `reject()` enforces `reject_admission` server-side.
- [ ] `delete()` and `deleteSelected()` enforce `delete_admission` server-side.
- [ ] `export()` enforces `export_admission` server-side.
- [ ] reviewer identity is taken explicitly from the `admin` guard.
- [ ] client-controlled selected IDs are normalized before destructive service calls.

### Architecture

- [ ] Livewire no longer owns approval/rejection/delete database workflows.
- [ ] reusable list/filter workflow is delegated through the Admission service boundary.
- [ ] no raw update bypasses model events for status transition.
- [ ] no bulk query-builder delete bypasses model deletion hooks.

### Data integrity / concurrency

- [ ] approve/reject transitions are transactionally serialized per record.
- [ ] only `pending` can transition through these actions.
- [ ] review metadata persists correctly.
- [ ] approval still preserves after-commit PDF job behavior.

### Performance

- [ ] list page has no unbounded `All` mode.
- [ ] runtime page size is bounded.
- [ ] Excel export no longer materializes the entire filtered dataset with `get()` through `FromCollection`.

### UI/UX

- [ ] Blade gates use capability-specific permissions.
- [ ] delete actions require confirmation.
- [ ] mutation/export controls expose target-aware loading/disabled states.
- [ ] empty result state is present.
- [ ] selection UI is permission-aware.
- [ ] table rows have stable identity where appropriate.

### Compatibility

- [ ] route/URI unchanged.
- [ ] Livewire alias unchanged.
- [ ] current filter semantics preserved.
- [ ] current status names preserved.
- [ ] Excel filename/column contract remains compatible unless a documented security correction is approved.
- [ ] generated document storage behavior remains intact.

### Tests / verification

- [ ] focused Livewire/service authorization tests pass.
- [ ] review transition tests pass.
- [ ] delete tests pass.
- [ ] list/filter/pagination tests pass.
- [ ] export tests pass.
- [ ] existing `tests/Feature/Admission` tests pass.
- [ ] targeted formatter/static checks used by the repository pass.

## 19. Implementation Order After Approval

Recommended order:

1. add failing focused authorization/runtime tests;
2. fix model review metadata contract;
3. add service query/review/delete methods;
4. refactor Livewire to authorize and delegate;
5. refactor exporter to query/chunk-safe behavior;
6. update Blade capability gates and bounded pagination;
7. add destructive confirmation/loading/empty/accessibility improvements;
8. run focused Admission tests;
9. run broader relevant regression tests;
10. update component analysis/docs only where implemented behavior changed.

## 20. Approval Status

Status: **WAITING FOR USER APPROVAL**

No application source code may be changed under `/refactor-livewire` until the user explicitly approves this plan.
