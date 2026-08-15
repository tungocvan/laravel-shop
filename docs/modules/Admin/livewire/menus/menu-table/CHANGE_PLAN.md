# MenuTable Change Plan

## Status

**PLANNING COMPLETE — AWAITING APPROVAL**

## Requested Changes

1. When a parent menu checkbox is selected, select all descendant menu checkboxes as well; unselecting the parent unselects its descendants.
2. When exporting the full menu scope, also refresh the restore snapshot so `Khôi phục` can restore the latest exported menu structure.
3. Add a `Quét Module chưa có trong Menu` action that discovers GET routes not yet represented in Admin Menu and allows adding them.

These are feature changes, not refactor-only changes.

## P1 — Hierarchical checkbox selection

### Behavior

- Clicking a menu checkbox with descendants selects that menu and all descendants currently represented in the tree.
- Unchecking the parent removes the parent and all descendants from `selectedMenus`.
- Selecting a child alone does not automatically select the parent unless every descendant-selection rule is explicitly satisfied; v1 should avoid indeterminate parent-state complexity.
- Existing top-level `Chọn tất cả theo bộ lọc hiện tại` semantics remain unchanged.
- Filter/search changes still clear selection.

### Implementation

- `MenuService` should expose a bounded helper such as `descendantIds(int $menuId): array` or `idsForBranch(int $menuId): array`.
- `MenuTable` should expose an action such as `toggleMenuSelection(int $menuId, bool $selected)` and update `selectedMenus` centrally.
- `menu-item.blade.php` should use explicit `wire:click`/checked state rather than relying only on `wire:model.live` for hierarchical behavior.
- Keep server-side normalization so tampered IDs cannot select non-menu records.

## P1 — Export all refreshes the restore snapshot

### Current Contract

`Khôi phục` currently reads:

```text
Modules/Admin/data/menus.json
```

via `MenuImportExportService::defaultPath()`.

### New Contract

When canonical Export runs with **no selected menus**:

1. Export the full approved filter scope to spreadsheet as today.
2. Also write a JSON tree snapshot of the current menu structure to the configured/default restore path.
3. Snapshot write must be atomic/safe enough that a failed write does not leave a partially written restore file.
4. `Khôi phục` continues to restore from that latest snapshot.

When selected menus are exported, **do not overwrite** the restore snapshot because a partial selection is not a valid full restore source.

### Important Scope Decision

The restore snapshot should represent the **entire Admin menu tree**, not only the current search/status-filtered subset. Therefore canonical export may still download the current approved export scope, but snapshot refresh must always serialize all menu records needed for a complete restore.

Recommended service method:

```text
MenuImportExportService::refreshRestoreSnapshot(): string
```

or an export-all orchestration method that returns spreadsheet path + snapshot result.

## P1 — Scan GET routes missing from Menu

### Goal

Add a button:

```text
Quét Module chưa có trong Menu
```

that scans registered Laravel routes and finds route destinations not currently represented in `admin_menus`.

### Route eligibility rules

Only include routes that:

- support `GET` or `HEAD` with GET capability;
- have a named route;
- belong to the Admin-facing route space, preferably URI beginning `admin/` and/or named route beginning `admin.`;
- are not framework/debug/internal endpoints such as Livewire assets/update, Telescope/Horizon/debugbar, storage, health, auth callbacks, generated fallback routes, etc.;
- do not already exist in menu by normalized route/url key;
- have a concrete usable URI for navigation.

Do **not** blindly add every GET route in the application.

### Module grouping

The scanner should infer module/group from the route name/URI where practical. Proposed v1 grouping:

- derive first meaningful segment after `admin.` or `admin/`;
- present candidates grouped by module/area;
- create a parent section only when needed and when the operator confirms adding children.

### Safety / UX

- Scanning is read-only.
- Show candidates in a centered modal/drawer before persistence.
- Each candidate is checkbox-selectable.
- Operator may select all candidates or selected candidates.
- Default menu name can be generated from route name/URI but remains editable only in a later enhancement; v1 may use a readable title-cased fallback.
- URL should be generated from the route URI/route name without requiring route parameters. Routes with required parameters such as `{id}` should be excluded from v1 unless defaults exist.
- `can` permission may be inferred from route middleware containing `permission:<name>,admin` when available.
- New records must use `MenuService` persistence helpers, unique slugs, safe sort order, and cache invalidation.

### Suggested service boundary

Introduce a focused service rather than putting Router inspection into Livewire:

```text
Modules/Admin/Services/MenuRouteScannerService.php
```

Responsibilities:

- inspect Laravel route collection;
- normalize eligible GET admin routes;
- infer name/url/permission/group;
- exclude existing menu entries;
- return candidate DTO/arrays;
- persist approved selected candidates through `MenuService`.

`MenuTable` owns only modal state, selected route candidates, authorization and feedback.

## Permissions

Existing permissions should be reused where possible:

- scan/view candidates: `admin.menu.view`
- add scanned routes: `admin.menu.create`
- export snapshot: `admin.menu.export`

Do not introduce a new permission unless implementation proves the existing boundary insufficient.

## Files Expected To Change

```text
Modules/Admin/Livewire/Menus/MenuTable.php
Modules/Admin/Services/MenuService.php
Modules/Admin/Services/MenuImportExportService.php
Modules/Admin/Services/MenuRouteScannerService.php            # new
Modules/Admin/resources/views/livewire/menus/menu-table.blade.php
Modules/Admin/resources/views/components/menu-item.blade.php
tests/Feature/Admin/MenuLivewireRefactorContractTest.php
tests/Feature/Admin/MenuRouteScannerServiceTest.php            # likely new
tests/Feature/Admin/MenuImportExportServiceTest.php
docs/modules/Admin/livewire/menus/menu-table/ANALYSIS.md
docs/modules/Admin/livewire/menus/menu-table/CHANGE_PLAN.md
```

## Test Plan

### Hierarchical selection

- selecting a parent returns/selects parent + children + grandchildren;
- unselecting removes the same branch;
- child-only selection remains possible;
- invalid IDs are ignored/rejected safely.

### Export/restore snapshot

- no-selection export refreshes restore JSON;
- selected export does not change restore JSON;
- snapshot contains full hierarchy and required fields;
- restore-from-snapshot recreates latest exported structure.

### GET route scanner

- includes eligible named GET admin routes;
- excludes POST/PUT/PATCH/DELETE-only routes;
- excludes parameterized routes with required parameters in v1;
- excludes framework/internal routes;
- excludes routes already represented in menu;
- infers permission middleware where present;
- selected candidates can be persisted without duplicates.

## Manual UI Smoke

1. Select parent menu → all descendants become checked.
2. Unselect parent → descendants are unchecked.
3. Export with no selection → spreadsheet downloads and latest restore snapshot updates.
4. Modify menu, then `Khôi phục` → restores snapshot from last full export.
5. Open `Quét Module chưa có trong Menu` → modal lists only eligible missing GET admin routes.
6. Add selected candidates → menu tree refreshes and no duplicate routes appear on a second scan.

## Risks / Guardrails

- Do not overwrite restore snapshot from partial selected export.
- Do not add parameterized/detail routes such as `/edit/{id}` in v1.
- Do not add public/frontend GET routes to Admin Menu.
- Do not infer permission from controller code; only use route middleware metadata when reliable.
- Route scanning must not execute controllers or perform HTTP requests.
- Preserve drag/drop hierarchy and existing Import/Export compatibility.

## Acceptance Criteria

- [ ] Parent selection cascades to descendants.
- [ ] Parent unselection clears descendants.
- [ ] Full export refreshes a complete latest restore snapshot.
- [ ] Selected export leaves restore snapshot untouched.
- [ ] Scanner only proposes eligible missing GET Admin routes.
- [ ] Scanner excludes required-parameter routes and framework/internal endpoints.
- [ ] Scanner modal allows selected persistence.
- [ ] Added routes are not duplicated on future scans.
- [ ] Permissions remain server-side enforced.
- [ ] Targeted Admin tests, Pint, build and full regression pass.

## Approval Gate

**AWAITING USER APPROVAL**
