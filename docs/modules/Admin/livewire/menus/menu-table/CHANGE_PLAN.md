# MenuTable Change Plan

## Status

**IMPLEMENTED — AWAITING LOCAL VERIFICATION**

## Approved Changes

1. Parent checkbox selection cascades to all descendants; unselecting the parent clears the same branch.
2. Full export refreshes the latest restore snapshot; selected export never overwrites the restore snapshot.
3. Add `Quét Module chưa có trong Menu` for eligible authenticated Admin GET routes not yet represented in Menu.

## Hierarchical checkbox selection

Implemented contract:

```text
select parent   -> parent + all descendants selected
unselect parent -> parent + all descendants removed
select child    -> child can remain independently selected
```

`MenuService::idsForBranch()` owns descendant resolution. `MenuTable::toggleMenuSelection()` owns Livewire selection state. The recursive `menu-item` Blade component only renders checked state and dispatches the explicit toggle action.

Top-level `Chọn tất cả menu theo bộ lọc hiện tại` behavior remains filter-scoped.

## Latest restore snapshot

Runtime restore state is now stored outside source control:

```text
storage/app/menu/menus.json
```

This replaces runtime dependency on:

```text
Modules/Admin/data/menus.json
```

Contract:

```text
Export with no selected menu
    -> download spreadsheet for current approved export scope
    -> refresh complete Admin menu-tree snapshot at storage/app/menu/menus.json

Export with selected menus
    -> download only selected menus
    -> DO NOT change storage/app/menu/menus.json

Khôi phục snapshot
    -> read storage/app/menu/menus.json
    -> replace current menu tree from the latest full-export snapshot
```

The snapshot is private under `storage/app`, not `storage/app/public`, and is not exposed by `storage:link`.

If no snapshot exists, restore must fail clearly and instruct the operator to perform a full export first.

## GET Admin route scanner

Implemented through:

```text
Modules/Admin/Services/MenuRouteScannerService.php
```

Eligibility rules:

- route supports `GET`;
- route is named;
- route belongs to `admin.*` and/or `admin/...`;
- route includes `auth:admin` middleware;
- route has no required URI parameter (`{...}` excluded in v1);
- framework/internal/auth endpoints are excluded;
- route URL/name is not already represented in `admin_menus`.

Permission is inferred only when route middleware exposes:

```text
permission:<permission-name>,admin
```

The scanner is read-only until the operator explicitly selects candidates and presses `Thêm vào Menu`.

Candidates are grouped by the first meaningful Admin route segment. Missing group sections are created only for selected candidates. Persistence remains inside `MenuService`.

## Permissions

Existing permissions are reused:

- scan candidates: `admin.menu.view`
- add candidates: `admin.menu.create`
- full/selected export: `admin.menu.export`
- restore snapshot: `admin.menu.restore`

## Implemented Files

```text
Modules/Admin/Livewire/Menus/MenuTable.php
Modules/Admin/Services/MenuService.php
Modules/Admin/Services/MenuImportExportService.php
Modules/Admin/Services/MenuRouteScannerService.php
Modules/Admin/resources/views/livewire/menus/menu-table.blade.php
Modules/Admin/resources/views/components/menu-item.blade.php
tests/Feature/Admin/MenuLivewireRefactorContractTest.php
docs/modules/Admin/livewire/menus/menu-table/CHANGE_PLAN.md
```

## Verification Required

```bash
vendor/bin/pint --test Modules/Admin tests/Feature/Admin
php artisan test tests/Feature/Admin
npm run build
php artisan test
```

Manual smoke:

1. Select a parent menu -> all children/grandchildren become selected.
2. Unselect the parent -> the whole branch becomes unselected.
3. Select a child alone -> child-only selection remains possible.
4. Full export with no checkbox selection -> Excel downloads and `storage/app/menu/menus.json` is created/updated.
5. Selected export -> verify snapshot modified time/content does not change.
6. Modify menu after full export -> `Khôi phục snapshot` restores the saved tree.
7. Open route scanner -> only authenticated named GET Admin routes without required parameters appear.
8. Add selected routes -> menu refreshes; scanning again does not propose duplicates.

## Acceptance Criteria

- [x] Parent selection cascades to descendants.
- [x] Parent unselection clears descendants.
- [x] Full export refreshes a complete private runtime snapshot.
- [x] Selected export leaves restore snapshot untouched.
- [x] Scanner only proposes eligible missing GET Admin routes.
- [x] Scanner requires `auth:admin` and excludes parameterized/internal/auth endpoints.
- [x] Scanner modal supports selected persistence.
- [x] Existing permissions remain server-side enforced.
- [ ] Pint PASS on Local.
- [ ] Targeted Admin tests PASS on Local.
- [ ] Frontend build PASS on Local.
- [ ] Full regression PASS on Local.
- [ ] Manual UI smoke PASS on Local.
