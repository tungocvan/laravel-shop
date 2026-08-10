# REFACTOR PLAN — Admin / Menus / MenuTable

## Goal

Refactor `Modules/Admin/Livewire/Menus/MenuTable.php` and its direct dependencies to improve authorization consistency, service boundaries, selection state, import/export maintainability, and UI reliability without changing existing public routes, permissions, menu data model, or core user-facing capabilities.

## Scope

Primary files expected to change after approval:

```text
Modules/Admin/Livewire/Menus/MenuTable.php
Modules/Admin/resources/views/livewire/menus/menu-table.blade.php
Modules/Admin/Services/MenuService.php
Modules/Admin/Services/MenuImportExportService.php
```

Targeted tests may be added/updated under the existing test structure.

No database migration is planned.

---

## P0 — Authorization consistency at UI action boundaries

### Problem

Most mutation methods enforce permissions, but `openBulkPermissionsModal()` currently opens a permission-changing workflow without calling `admin.menu.update` authorization first.

The actual save action is authorized, but mutation-related UI entry points should be consistently protected.

### Change

- Add `admin.menu.update` authorization to `openBulkPermissionsModal()`.
- Review all Livewire actions that expose privileged workflows and ensure the matching permission is checked inside the action, not only at route/page level.
- Keep existing permission names unchanged.

### Acceptance

```text
[ ] unauthorized users cannot open bulk permission workflow
[ ] delete/update/create/import/export/restore actions keep capability-specific checks
```

---

## P1 — Standardize service dependency resolution

### Problem

`MenuTable` resolves services repeatedly through:

```php
app(MenuService::class)
app(MenuImportExportService::class)
```

via private helper methods.

### Change

Use one canonical Livewire dependency pattern, for example:

```php
protected MenuService $menuService;
protected MenuImportExportService $importExportService;

public function boot(
    MenuService $menuService,
    MenuImportExportService $importExportService,
): void {
    ...
}
```

All actions/render logic should use those declared dependencies.

### Acceptance

```text
[ ] no repeated app(...) service resolution remains in MenuTable
[ ] component dependencies are explicit and testable
```

---

## P1 — Remove duplicated menu filter query logic

### Problem

`MenuService::query()` and `MenuImportExportService::query()` independently implement the same search/status filtering logic.

This can drift over time and cause the UI list and exported data to behave differently.

### Change

Make one canonical query/filter implementation.

Recommended direction:

- `MenuService` owns menu filtering/query rules.
- `MenuImportExportService` receives/reuses the canonical query/filter logic instead of maintaining a competing copy.
- Export must use exactly the same filter semantics as the visible table/tree.

### Acceptance

```text
[ ] search/status filter rules exist in one canonical place
[ ] export results match current UI filters
```

---

## P1 — Selection state correctness

### Problems

- `selectAll` is populated from current filters, but search/status changes do not explicitly reconcile existing selections.
- Hidden selected menu IDs can remain after filters change.
- `updatedSelectedMenus()` is not present to keep `selectAll` synchronized when individual items are unchecked.

### Change

- On search/status change, reset or safely reconcile selection.
- Add selected-menu synchronization so `selectAll` reflects the current visible/filter scope.
- Keep IDs server-normalized before bulk mutations.
- Preserve current bulk actions.

### Acceptance

```text
[ ] changing search/status cannot silently keep misleading hidden selections
[ ] select-all accurately reflects current filtered menu set
[ ] individual uncheck updates select-all state
```

---

## P1 — Bulk mutation consistency and transactions

### Review targets

```text
bulkDelete
bulkToggleStatus
bulkAssignPermission
updateOrder
```

### Change

- Keep business mutations in `MenuService`.
- Use transactions for multi-row mutations where partial completion would create inconsistent menu state.
- Clear menu cache once per completed mutation instead of relying on repeated model-level side effects where practical.
- Preserve current return counts and user-visible behavior.

### Acceptance

```text
[ ] bulk operations are atomic where required
[ ] cache is consistent after bulk changes
[ ] invalid/manipulated IDs remain ignored/rejected safely
```

---

## P1 — Drag-sort payload hardening

### Existing good behavior

`MenuService::updateOrder()` already:

- validates duplicate IDs;
- verifies IDs exist;
- uses a transaction;
- recursively updates parent/order;
- clears menu cache.

### Additional refactor

- Add reasonable payload depth/item limits if the repository/menu size warrants it.
- Ensure only menu records in the intended Admin menu scope can be reordered.
- Keep validation errors user-safe.
- Do not move drag-sort business logic into Livewire/Blade.

---

## P1 — Import workflow hardening

### Problems / risks

- Import uses Livewire upload correctly, but report/debug data may contain internal exception information from `MenuImportExportService`.
- The import service currently builds a full FastExcel collection before persistence; acceptable for small menu datasets, but a clear bounded-size contract should exist.
- UI allows opening import modal without an explicit import permission action boundary because `$set('showImportModal', true)` is called directly from Blade.

### Change

- Replace direct Blade `$set` with an authorized `openImportModal()` action requiring `admin.menu.import`.
- Ensure technical exception/debug details are logged server-side and are not exposed in normal admin report UI.
- Keep existing file type/size validation.
- Keep current `skip_duplicate` default behavior unless separately changed.
- Define/retain a bounded menu import size appropriate for this domain; no queue architecture is required for normal menu datasets.

### Acceptance

```text
[ ] import modal cannot be opened without admin.menu.import
[ ] raw technical exception details are not exposed to browser UI
[ ] current XLSX/CSV import behavior remains compatible
```

---

## P1 — Restore-default workflow safety

### Change

- Keep `admin.menu.restore` authorization.
- Wrap restore execution in safe exception handling at Livewire boundary.
- Keep technical details in logs.
- Preserve report display for business/data validation errors.
- Keep destructive confirmation in UI.

### Acceptance

```text
[ ] restore failure does not leak raw internal exceptions
[ ] success/error notifications remain clear
```

---

## P2 — Notification consistency

### Problem

The component mixes direct `$this->dispatch('notify', ...)` and `notify()` helper usage, with different optional payload keys (`action`, `duration`).

### Change

- Standardize a component helper for normal notifications.
- Preserve optional reload/duration semantics when needed.
- Do not change the global notification contract.

---

## P2 — UI standardization

Within this component only:

- normalize buttons, disabled/loading states and spacing;
- use `wire:confirm` consistently instead of inline JavaScript confirm where practical;
- disable repeated bulk/import/restore actions while requests are running;
- keep responsive layout and empty state;
- keep drag-sort usability;
- preserve current Tailwind-based appearance without unrelated global UI migration.

---

## Import / Export Architecture

Preserve current capabilities:

```text
Export current filtered menu data
Export template
Import XLSX/CSV
Restore default JSON
```

Refactor only to improve boundaries and consistency.

No new import/export feature is introduced by this plan.

---

## Tests / Verification

Add/update focused tests where practical:

```text
[ ] unauthorized user cannot open import modal
[ ] unauthorized user cannot open bulk permission modal
[ ] permission checks for delete/update/create/import/export/restore
[ ] search + status filters are canonical across list/export
[ ] select-all reflects current filtered IDs
[ ] filter change clears/reconciles hidden selections
[ ] bulk delete/update/permission operations handle invalid IDs safely
[ ] drag-sort rejects duplicate/invalid IDs
[ ] import report does not expose raw exception details
[ ] restore-default failure is user-safe
```

Manual verification:

```text
1. search/filter menus
2. select all and uncheck individual items
3. change filters and verify selection state
4. bulk enable/disable/delete/permission
5. drag-sort nested menus
6. export filtered menus
7. import template/data
8. restore defaults
```

---

## Compatibility

Preserve:

- existing route names;
- Livewire component alias/path;
- current menu table/schema;
- permission names;
- import/export file formats;
- default JSON restore source;
- current menu hierarchy semantics.

No schema migration is required.

---

## Acceptance Criteria

```text
[ ] privileged UI entry actions enforce authorization
[ ] service dependencies are explicit
[ ] filter/query rules are canonical and not duplicated
[ ] selection state remains accurate across filter changes
[ ] bulk mutations are transaction-safe where appropriate
[ ] import modal uses authorized action instead of direct Blade state mutation
[ ] technical exception details remain server-side
[ ] drag-sort behavior remains compatible
[ ] import/export/restore behavior remains compatible
[ ] targeted verification passes
```

## Implementation Gate

**Do not implement this plan until the user explicitly approves `REFACTOR_PLAN.md`.**
