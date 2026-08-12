# Admission Livewire Analysis — Admin/Applications/Index

## Executive Summary

Target component:

`Modules/Admission/Livewire/Admin/Applications/Index.php`

Livewire alias:

`admission.admin.applications.index`

View:

`Modules/Admission/resources/views/livewire/admin/applications/index.blade.php`

Parent page:

`Modules/Admission/resources/views/pages/admin/index.blade.php`

Route:

`GET /admin/admission` → `admin.admission.index`

The component is the administrative list for admission applications. It owns search, filtering, pagination, selection, approve/reject/delete mutations, and Excel export. The component is functional but currently violates the repository's capability-specific authorization and service-boundary standards. The highest-risk issue is that sensitive Livewire actions do not authorize their own capabilities, while the page route requires only `view_admission`. UI permission gates also use the wrong permission for several actions.

A focused refactor is recommended before feature expansion. Application source code was not modified during this analysis.

## Component Purpose

The component provides the admin workflow for:

- listing admission applications;
- searching by student name, identity number, or phone;
- filtering by status and registration class;
- pagination or `all` display;
- selecting one or multiple records;
- approving pending applications;
- rejecting pending applications;
- deleting one or multiple applications;
- exporting filtered applications to Excel.

It is mounted by `pages/admin/index.blade.php` using:

```php
@livewire('admission.admin.applications.index')
```

## Dependency Flow

```text
GET /admin/admission
    ↓
AdmissionController::adminIndex()
    ↓
pages/admin/index.blade.php
    ↓
admission.admin.applications.index
    ↓
Admin/Applications/Index.php
    ↔
livewire/admin/applications/index.blade.php
    ↓
AdmissionApplication
    ↓
admission_applications

Export action
    ↓
ApplicationsExport
    ↓
AdmissionApplication
    ↓
admission_applications
```

No domain service is used by this component for application listing, review mutations, deletion, or export orchestration.

## Livewire PHP Analysis

### Responsibilities

Current responsibilities are broader than UI state management:

- owns query construction;
- owns filtering and pagination;
- owns approval/rejection business transitions;
- writes reviewer metadata;
- owns single and bulk deletion;
- invokes Excel export directly.

Repository `MODULE_STANDARD.md` states that Livewire should own UI state/validation/actions and call services, while sensitive mutations must authorize at the action boundary. Service is the preferred owner of queries, filtering, pagination, create/update/delete workflows, transactions, import/export orchestration, and concurrency controls.

Therefore this component contains material business/data workflow logic directly in Livewire.

### Public State

Public properties:

```text
search
filterStatus
filterClass
perPage
selected
selectAll
```

None are locked.

Risk assessment:

- `search`, `filterStatus`, `filterClass`, and `perPage` are expected user-controlled UI state.
- `selected` is also user-controlled state and is later trusted by `deleteSelected()` to select database IDs.
- Because destructive authorization is missing from `deleteSelected()`, tampering with `selected` materially increases the authorization risk.

### Lifecycle

No `mount()`, `boot()`, `hydrate()`, or similar lifecycle method is defined.

`updated($field)` resets pagination and selection for filter/page-size changes.

`updatedSelectAll($value)` selects IDs from the currently resolved applications collection/paginator.

### Computed Property / Query

`getApplicationsProperty()` builds an `AdmissionApplication` query with:

- name / identity number / phone search;
- status filter;
- registration-class filter;
- descending `updated_at` and `created_at` ordering.

Normal mode uses pagination.

When `perPage === 'all'`, the query uses unbounded `get()`.

### Validation

There are no explicit validation rules for:

- `perPage`;
- `filterStatus`;
- `filterClass`;
- IDs passed to `approve()`, `reject()`, `delete()`;
- `selected` IDs passed to bulk deletion.

Query builder parameter binding limits SQL-injection risk for search values, but user-controlled state still requires domain/capability enforcement for mutations.

## Actions / Mutations

### `approve($id)`

Current behavior:

1. `findOrFail($id)`.
2. Returns silently unless status is `pending`.
3. Updates status to `approved`.
4. Attempts to set `approved_at` and `approved_by`.

Issues:

- no `approve_admission` authorization;
- directly mutates the model from Livewire;
- read-then-write transition is not concurrency-safe;
- `approved_at` and `approved_by` exist in migration but are absent from the model `$fillable` list;
- reviewer ID uses `Auth::id()` while the admin route authenticates with `auth:admin`; project auth defaults to the `web` guard unless `AUTH_GUARD` overrides it;
- no explicit clearing/reconciliation of previous rejected metadata if a workflow later permits retry/re-review.

The model's `updated` event dispatches `GenerateAdmissionPdfJob` after an approval status change when no `pdf_path` exists, so this action also has an indirect queued side effect.

### `reject($id)`

Current behavior mirrors approval.

Issues:

- no `reject_admission` authorization;
- direct model mutation;
- concurrency race between approve/reject requests;
- `rejected_at` and `rejected_by` are absent from `$fillable`;
- reviewer ID is obtained using the default auth guard rather than explicitly using `admin`;
- no rejection reason is collected by this component.

### `deleteSelected()`

Current behavior:

```text
whereIn(id, selected)
→ get()
→ each->delete()
```

Issues:

- no `delete_admission` authorization;
- `selected` is public Livewire state and can be manipulated by the client;
- no validation/normalization of IDs;
- no transaction around multi-record deletion;
- deletion triggers model `deleting` hooks which remove PDF/Word files using filesystem calls, so partial failure can leave database/filesystem state inconsistent;
- no audit record is created;
- the UI has no destructive confirmation.

This is the highest-risk action in the component.

### `delete($id)`

Sets `selected = [$id]` and delegates to `deleteSelected()`.

It inherits the same missing authorization and data-integrity concerns.

### `export()`

Returns an Excel download through `ApplicationsExport`.

Issues:

- no `export_admission` authorization inside the Livewire action;
- `ApplicationsExport` implements `FromCollection` and loads all matching rows using `get()`;
- the export dynamically includes almost every database column except a short exclusion list, which can include sensitive student/family data;
- large exports are memory-bound and synchronous.

## Authorization

### Route boundary

The parent route requires:

```text
auth:admin
permission:view_admission,admin
```

This is appropriate for viewing the list, but it is not sufficient for capability-specific mutations.

The module declares separate permissions:

```text
view_admission
create_admission
edit_admission
delete_admission
import_admission
export_admission
approve_admission
reject_admission
download_admission_documents
manage_admission_locations
manage_admission_settings
```

### Livewire action boundary

The component contains no `authorize()`, `Gate`, `can()`, or explicit admin-guard capability checks in:

```text
approve
reject
delete
deleteSelected
export
```

This conflicts directly with `MODULE_STANDARD.md`, which requires sensitive mutations to enforce authorization at the action boundary.

### Blade permission gates

The Blade view uses `@can('create_admission')` around a block containing:

- Export Excel;
- Import form;
- bulk delete.

It also uses `@can('create_admission')` for approve/reject buttons.

Those actions have distinct declared permissions and should not be represented by `create_admission`.

The single delete button is correctly hidden behind `@can('delete_admission')`, but server-side action authorization is still absent.

Because UI hiding is not a security boundary, fixing Blade gates alone would not resolve the Livewire authorization issue.

## Service / Model Dependencies

### Direct model dependency

`AdmissionApplication` is queried and mutated directly.

This bypasses the module's `AdmissionService`, which already acts as the main application workflow service for create/update behavior.

A refactor should first determine whether review/list/delete operations belong in `AdmissionService` or in a focused application-review service according to repository conventions. Do not introduce a new service solely for stylistic reasons.

### Model side effects

`AdmissionApplication` contains model-event workflows:

- resets approved/rejected applications to `pending` when non-status data changes;
- dispatches `GenerateAdmissionPdfJob` after approval;
- removes PDF/Word files during deletion.

Therefore approve and delete operations are not simple CRUD operations; they trigger cross-boundary side effects and need regression tests.

### Review metadata schema mismatch

The migration defines:

```text
approved_at
approved_by
rejected_at
rejected_by
```

but these fields are not present in `AdmissionApplication::$fillable`.

The component uses mass-assignment `update([...])`, so reviewer metadata persistence is not reliably aligned with the model contract. Depending on Laravel's mass-assignment strictness configuration, attributes may be discarded or may raise an exception in strict environments.

No foreign keys are defined for `approved_by` / `rejected_by`; they are nullable unsigned big integers only.

## Transaction / Concurrency

### Approval vs rejection race

Both actions perform:

```text
find record
check status == pending
update record
```

Two concurrent requests can both observe `pending` and then write conflicting terminal statuses. The last write wins.

Recommended direction for refactor:

- enforce the status predicate in the write itself or use a transaction/row lock in the canonical service;
- define expected behavior for simultaneous approve/reject attempts;
- test the transition contract.

### Bulk deletion

Bulk delete is multi-record and also performs filesystem side effects through model events. There is no transaction/rollback strategy for the complete workflow.

Because filesystem deletion cannot be rolled back by a database transaction, a refactor plan should explicitly define failure semantics rather than wrapping everything blindly in `DB::transaction()`.

## Performance

### Pagination

Normal operation uses `WithPagination`, which is appropriate.

### Unbounded `All`

The UI explicitly offers `All`, and the component switches to `get()` for all matching applications.

This conflicts with `ADMIN_UI_STANDARD.md`, which says not to render unbounded production datasets to support an `All` option.

Recommendation: remove `All` or replace it with a bounded option after confirming business need.

### Search indexes

The review metadata migration adds indexes for:

- `ma_dinh_danh`;
- `status`;
- `loai_lop_dang_ky`.

Search also uses:

- `ho_va_ten_hoc_sinh LIKE %...%`;
- `sdt_enetviet LIKE %...%`.

Those wildcard searches will not benefit from ordinary prefix B-tree indexing in the general case. This is acceptable for small datasets but should be measured before introducing search infrastructure.

### Export

`ApplicationsExport` uses `FromCollection` and `get()`, so memory consumption scales with the complete filtered result set.

No N+1 issue was observed in the list because no relationships are eager/lazy loaded in the component query.

## Livewire Blade Analysis

### Strengths

- responsive `overflow-x-auto` table wrapper;
- clear search and filters;
- status badges include text, not only color;
- paginator is present in bounded mode;
- Tailwind structure is consistent and readable;
- search uses a debounce;
- session success/error areas exist.

### Issues

#### Wrong capability gates

`create_admission` is incorrectly reused for export, import, bulk delete, approve, and reject UI.

#### No confirmation for destructive actions

Single delete and bulk delete invoke Livewire actions immediately.

This conflicts with the Admin UI standard requiring dangerous actions to use confirmation where appropriate.

#### Missing loading/disabled states

Approve, reject, delete, bulk delete, and export buttons do not use `wire:loading`, `wire:target`, or a disabled state to prevent repeated submissions.

#### Missing empty state

When no applications match the current filter, the table body renders no dedicated empty-state message.

#### Selection exposure

The table's row checkbox/select-all controls remain visible independently of the bulk-delete permission block. This is mostly UX inconsistency, but permission-aware selection would reduce confusion.

#### Hard-coded registration classes

The filter class options are hard-coded, while the module now has `SchoolSettingService::registrationClasses()` for configurable registration classes elsewhere. This can drift from current settings.

#### Accessibility

Checkboxes and form controls rely largely on surrounding context and lack explicit accessible labels. Action buttons have visible text, which is good, but focus/disabled/loading feedback for mutations is incomplete.

### `wire:key`

Rows do not use an explicit `wire:key`.

For this table, records are mutable and filters/pagination change the collection. Adding a stable row key should be evaluated during refactor to improve DOM identity, but this is lower risk than authorization/data integrity issues.

## File Operations / External Services

The component itself does not directly upload/download files or call external HTTP services.

However:

- export returns an Excel file;
- deleting an `AdmissionApplication` indirectly deletes PDF/Word files through model hooks;
- approving an application indirectly dispatches a PDF-generation job.

These runtime contracts must be retained by regression tests during refactor.

## Events / Jobs

The component itself does not dispatch Livewire events.

Indirect model behavior dispatches:

`Modules\Admission\Jobs\GenerateAdmissionPdfJob`

when status changes to `approved` and no PDF exists.

This makes approval a queued side-effect workflow even though the Livewire method appears simple.

## Admin Menu / Route / Runtime Contract

The page route exists and is protected by `view_admission`.

The parent page mounts the component using alias:

`admission.admin.applications.index`

The current canonical `Modules/Admin/data/menus.json` does not contain an Admission menu entry. This is a documentation/runtime finding only; do not add a menu until the intended navigation contract is confirmed.

The component is definitely not dead code because it is directly mounted by `pages/admin/index.blade.php`.

## Test Coverage

Current Admission feature tests found:

- `tests/Feature/Admission/AdmissionRouteConfigurationTest.php`
- `tests/Feature/Admission/AdmissionLocationImportExportTest.php`

`AdmissionRouteConfigurationTest` verifies route names and route-level capability middleware, including `view_admission`, `import_admission`, `export_admission`, and document permissions.

No focused runtime Livewire test was found for `Admin/Applications/Index`.

Missing critical coverage:

- a user with only `view_admission` cannot approve;
- `approve_admission` can approve pending record;
- a user without `reject_admission` cannot reject;
- `reject_admission` can reject pending record;
- a user without `delete_admission` cannot single/bulk delete;
- public `selected` state cannot be abused to delete unauthorized IDs;
- a user without `export_admission` cannot call the Livewire export action;
- review metadata records the authenticated admin correctly;
- approve/reject race/transition behavior;
- approval dispatches PDF generation only as intended;
- delete preserves defined DB/filesystem failure semantics;
- pagination/filter/search behavior;
- `perPage=all` behavior should disappear or be bounded if refactored.

## Issue List

Priority below is component-local analysis. Module-wide PHASE 4 classification should still occur only after all Livewire components have been analyzed.

### P0 — Sensitive Livewire mutations lack capability authorization

**Evidence:** `approve()`, `reject()`, `delete()`, `deleteSelected()`, and `export()` do not enforce capability-specific authorization. The parent route requires only `view_admission`.

**Impact:** A user who can load the list component may be able to invoke capabilities beyond `view_admission`, including status mutation, deletion, and export of sensitive admission data.

**Direction:** Add server-side capability authorization at each sensitive Livewire action boundary, using the correct admin authentication context.

### P0 — Bulk delete trusts client-controlled selected IDs without mutation authorization

**Evidence:** `selected` is public Livewire state and is passed directly into `whereIn('id', $this->selected)` before deletion.

**Impact:** ID tampering combined with missing delete authorization can cause unauthorized destructive changes. Model deletion also removes associated generated files.

**Direction:** Authorize delete first, validate/normalize IDs, define record-level constraints if any, and place the destructive workflow behind the canonical service boundary.

### P1 — Review metadata contract is inconsistent

**Evidence:** migration defines `approved_at`, `approved_by`, `rejected_at`, `rejected_by`; component mass-assigns them; model `$fillable` omits them. Component uses `Auth::id()` while admin routes authenticate via `auth:admin`, and auth defaults to `web` unless overridden.

**Impact:** audit metadata can be missing, discarded, incorrect, or environment-dependent while application status still changes.

**Direction:** Make the reviewer metadata contract explicit and use the authenticated admin identity consistently.

### P1 — Approval/rejection transition has a concurrency race

**Evidence:** both actions read `pending` before an independent update, with no conditional write/lock.

**Impact:** concurrent approve/reject requests can race and the final state depends on last write.

**Direction:** move transition to canonical service with an atomic status predicate or explicit transaction/locking strategy.

### P1 — Business workflow bypasses service boundary

**Evidence:** query construction, approve/reject/delete, and export orchestration are implemented directly in Livewire.

**Impact:** authorization, transition rules, concurrency, side effects, and tests are harder to centralize and reuse.

**Direction:** during refactor, move material workflows to the canonical Admission service boundary without changing routes/aliases/business behavior unnecessarily.

### P1 — Export is synchronous and unbounded

**Evidence:** `ApplicationsExport` implements `FromCollection` and calls `get()` for all filtered rows.

**Impact:** memory/response-time risk as application volume grows; export contains broad admission data.

**Direction:** preserve filtering contract but use a bounded/query/chunked export strategy appropriate to repository canonical import/export guidance.

### P2 — `perPage=all` is unbounded

**Evidence:** UI exposes `All`; component executes `get()`.

**Impact:** admin page memory and rendering can degrade with dataset growth.

**Direction:** remove `All` or replace with a documented bounded maximum.

### P2 — UI capability gates are incorrect/inconsistent

**Evidence:** `create_admission` controls export/import/bulk-delete and approve/reject UI, despite dedicated permissions existing.

**Impact:** legitimate users can see the wrong controls and users may be denied/allowed UI inconsistent with capability design.

**Direction:** bind each UI control to its declared capability after server-side authorization is fixed.

### P2 — Destructive/loading/empty-state UX gaps

**Evidence:** delete actions have no confirmation; mutation/export buttons lack loading/disabled state; empty results have no explicit empty state.

**Impact:** accidental/repeated actions and poorer admin usability.

**Direction:** add confirmation, target-specific loading/disabled states, and an empty state while preserving responsive layout.

### P2 — Configurable registration classes can drift from filter options

**Evidence:** Blade hard-codes class options while Admission has configurable registration classes through `SchoolSettingService`.

**Impact:** filters can become inconsistent with current admission configuration.

**Direction:** source filter options from the canonical settings service if the product contract confirms classes are configurable globally.

### P2 — Missing focused Livewire regression tests

**Evidence:** Admission test folder contains route configuration and location import/export tests, but no component runtime test for Applications/Index.

**Impact:** critical authorization and status-transition regressions can pass the existing suite.

**Direction:** add focused Livewire feature tests before/with refactor.

## Recommended Direction

**Focused refactor is recommended.**

Do not rebuild the component from scratch. The existing list/search/filter/pagination UI contract is usable and should be preserved. Refactor should concentrate on:

1. capability-specific action authorization;
2. canonical service boundary for review/delete/query/export workflows;
3. correct admin reviewer metadata;
4. atomic approval/rejection transition;
5. safe bulk deletion semantics;
6. bounded list/export behavior;
7. permission-correct Blade controls;
8. loading/confirmation/empty states;
9. focused regression tests.

Before implementation, `/refactor-livewire Admission Admin/Applications/Index` should create a `REFACTOR_PLAN.md` and stop for approval according to the repository workflow.

## Open Questions / Unknowns

1. Should rejection require and persist a mandatory rejection reason? The current component has no reason field; business intent must be confirmed from the module contract before changing behavior.
2. Are `approved_by` and `rejected_by` intended to reference `users.id` formally with foreign keys, or are they intentionally loose audit IDs?
3. Should bulk delete be permitted for approved/rejected applications, or only particular statuses?
4. Should deletion remain physical deletion with immediate document removal, or does the business require history/audit retention?
5. What is the expected production upper bound for application volume and synchronous Excel export size?
6. Is an Admission entry intentionally absent from the canonical admin menu, or is this navigation drift?
7. `AUTH_GUARD` may override the default `web` guard in a specific environment; the component should nevertheless use an explicit admin identity for an admin-only workflow.
