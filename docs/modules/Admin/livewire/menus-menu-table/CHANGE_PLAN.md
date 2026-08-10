# CHANGE PLAN — Export Selected Menus

## Goal

Add a new bulk action to export only the menus currently selected by checkbox in `Admin / Menus / MenuTable`.

This is an additive feature. Existing filtered export, template export, import, restore, bulk actions, permissions, routes, and menu hierarchy behavior must remain compatible.

## Intended User Flow

```text
Select one or more menus
        ↓
Bulk action bar appears
        ↓
Click "Export đã chọn"
        ↓
Validate admin.menu.export permission
        ↓
Validate selected menu IDs server-side
        ↓
Export selected menu records to XLSX
        ↓
Download file
```

## Scope

Expected implementation scope after approval:

```text
Modules/Admin/Livewire/Menus/MenuTable.php
Modules/Admin/resources/views/livewire/menus/menu-table.blade.php
Modules/Admin/Services/MenuService.php
Modules/Admin/Services/MenuImportExportService.php
```

Targeted tests may be added/updated.

No database migration is required.

---

## Selection Semantics

Use the existing Livewire state:

```text
selectedMenus
```

Do not create a competing selection mechanism.

Before export:

- reject empty selection;
- normalize IDs server-side;
- ignore/reject invalid IDs safely;
- export only existing Admin menu records in the intended menu scope.

The current search/status filter should not silently add extra rows beyond the explicit selection.

## Parent / Child Behavior

Recommended default:

- Export exactly the selected menu IDs.
- Do not automatically include unselected parents or children.
- Preserve each selected row's current `parent_key` reference when possible.

Important consequence:

If a child is selected but its parent is not selected, the exported file may contain a `parent_key` that is not included in the same export. This is acceptable for a "selected rows" export, but import behavior should remain unchanged and may require the parent to already exist in the database.

If future UX requires "include descendants" or "include full subtree", that should be a separate explicitly approved feature.

---

## Authorization

Selected export must require the existing permission:

```text
admin.menu.export
```

The permission check must run inside the Livewire action.

Do not add a new permission.

---

## Service Design

### MenuService

Add/reuse a canonical method to safely resolve selected menu IDs, conceptually:

```text
menusByIds(array $ids)
```

or equivalent query helper.

Responsibilities:

- normalize selected IDs;
- constrain to Admin menu scope;
- load only existing selected records;
- preserve stable ordering suitable for export.

### MenuImportExportService

Add a selected export workflow, conceptually:

```text
exportSelected(array $menuIds): string
```

Responsibilities:

1. obtain selected records through canonical MenuService query logic;
2. map them to the existing export column contract;
3. preserve columns:

```text
key
parent_key
name
url
icon
can
is_active
sort_order
```

4. write XLSX using the existing spreadsheet writer;
5. return the generated export path.

Do not duplicate menu filter/query rules.

---

## Parent Key Mapping

For each selected menu:

```text
key        = menu slug/key
parent_key = parent menu slug/key, if parent exists
```

The parent does not need to be selected for `parent_key` to be populated.

This keeps the exported row semantically compatible with the existing import format.

---

## Livewire Action

Add an action such as:

```text
exportSelected()
```

Behavior:

1. authorize `admin.menu.export`;
2. reject empty selection with warning notification;
3. call `MenuImportExportService`;
4. return controlled Storage download;
5. report technical exceptions server-side;
6. show only user-safe errors.

Do not reset selection automatically unless there is a clear UX reason; keeping selection after download is preferred.

---

## UI

In the existing bulk action bar, add:

```text
Export đã chọn
```

Recommended placement: before destructive actions such as bulk delete.

Requirements:

- visible only when at least one menu is selected;
- loading/disabled state while export runs;
- show selected count using existing bulk bar;
- preserve responsive layout.

Existing top-level `Export Excel` continues to export using current search/status filters.

The distinction should be clear:

```text
Export Excel      = export current filtered dataset
Export đã chọn    = export exact checkbox selection
```

---

## Import Compatibility

The selected export file must remain compatible with the existing XLSX import headers and normalization rules.

No import behavior change is part of this feature.

---

## Tests / Verification

Recommended focused coverage:

```text
[ ] unauthorized user cannot export selected menus
[ ] empty selectedMenus is rejected safely
[ ] invalid/manipulated IDs are not exported
[ ] one selected menu exports one row
[ ] multiple selected menus export only those rows
[ ] unselected menus are absent
[ ] parent_key is preserved even when parent is not selected
[ ] selected export uses existing XLSX headers
[ ] normal filtered Export Excel remains unchanged
[ ] search/filter changes continue to reset selection as currently designed
```

Manual verification:

```text
1. select one root menu and export
2. select multiple unrelated menus and export
3. select child only and verify parent_key in XLSX
4. select parent + child and verify both rows
5. verify top-level Export Excel still follows current filters
```

---

## Compatibility

Preserve:

- `admin.menu.export` permission;
- existing routes;
- Livewire component alias/path;
- current XLSX headers;
- current import behavior;
- current filtered export behavior;
- menu database schema.

No migration is required.

## Acceptance Criteria

```text
[ ] bulk bar has "Export đã chọn"
[ ] action requires admin.menu.export
[ ] only selected menu IDs are exported
[ ] invalid IDs are safely excluded/rejected
[ ] parent_key remains meaningful
[ ] XLSX format remains import-compatible
[ ] existing Export Excel remains unchanged
[ ] loading/error UX is safe and consistent
```

## Implementation Gate

**Do not implement this feature until the user explicitly approves `CHANGE_PLAN.md`.**
