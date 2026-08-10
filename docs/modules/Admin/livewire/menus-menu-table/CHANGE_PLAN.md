# CHANGE PLAN — Export Selected Menus + Import Duplicate Strategy + Collapse Children

## Goal

Extend `Admin / Menus / MenuTable` with three additive capabilities:

1. Export only the menus currently selected by checkbox.
2. Let the user choose how duplicate menu rows are handled during import:
   - **Bỏ qua dữ liệu đã tồn tại** (`skip_duplicate`) — default.
   - **Cập nhật dữ liệu đã tồn tại** (`update_or_create`).
3. Allow each parent menu to collapse/expand its child menu list in the MenuTable UI.

Existing filtered export, template export, restore, bulk actions, permissions, routes, menu hierarchy, drag-sort and database schema must remain compatible.

---

# Part A — Export Selected Menus

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

The current search/status filter must not silently add extra rows beyond the explicit selection.

## Parent / Child Behavior

Default behavior:

- Export exactly the selected menu IDs.
- Do not automatically include unselected parents or children.
- Preserve each selected row's current `parent_key` reference when possible.

If a child is selected but its parent is not selected, the exported file may still contain the parent's `parent_key`. This is acceptable; importing that row later requires the parent to already exist or be included elsewhere in the import file.

## Authorization

Selected export requires the existing permission:

```text
admin.menu.export
```

Permission enforcement must occur inside the Livewire action.

## Service Design

### MenuService

Add/reuse a canonical method to safely resolve selected menu IDs, conceptually:

```text
menusByIds(array $ids)
```

Responsibilities:

- normalize selected IDs;
- constrain to Admin menu scope;
- load only existing records;
- preserve stable ordering suitable for export.

### MenuImportExportService

Add a selected export workflow, conceptually:

```text
exportSelected(array $menuIds): string
```

Responsibilities:

1. obtain selected records through canonical MenuService logic;
2. map them to the existing export contract;
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
5. return the generated path.

Do not duplicate menu filter/query rules.

## Livewire Action

Add an action such as:

```text
exportSelected()
```

Behavior:

1. authorize `admin.menu.export`;
2. reject empty selection;
3. call `MenuImportExportService`;
4. return controlled Storage download;
5. log technical exceptions server-side;
6. show only user-safe errors.

Do not reset selection automatically after download.

## UI

In the existing bulk action bar, add:

```text
Export đã chọn
```

The distinction must remain clear:

```text
Export Excel      = export current filtered dataset
Export đã chọn    = export exact checkbox selection
```

---

# Part B — Import Duplicate Strategy

## Goal

Allow the user to decide what happens when the imported `key` already exists in the menu table.

The existing import engine already supports these modes internally:

```text
skip_duplicate
update_or_create
replace
```

This feature exposes only the two safe modes needed for normal user imports:

```text
skip_duplicate
update_or_create
```

`replace` remains reserved for the existing restore-default workflow and must not be exposed as a normal upload option.

## Duplicate Identity

Use the existing exported/imported menu `key` as the logical identifier.

Current implementation maps this value to the menu `slug`.

Conceptually:

```text
Excel key
    ↓ normalize
AdminMenu.slug
```

A row is considered duplicate when its normalized `key`/slug already exists, including soft-deleted rows when necessary to preserve unique-key integrity.

Do not use menu name or URL as duplicate identity.

## Import Options

Add Livewire state such as:

```text
importMode = 'skip_duplicate'
```

Allowed values:

```text
skip_duplicate
update_or_create
```

Default must remain:

```text
skip_duplicate
```

because it is the safest backward-compatible behavior.

## UI

Inside the Import modal, add a clearly labeled choice:

```text
Khi menu đã tồn tại:

● Bỏ qua dữ liệu đã tồn tại
○ Cập nhật dữ liệu đã tồn tại
```

Recommended helper text:

### Bỏ qua

```text
Menu đang tồn tại sẽ không bị thay đổi.
Menu đã xóa mềm có thể được khôi phục để tránh lỗi unique slug và bảo toàn cấu trúc menu.
```

### Cập nhật

```text
Menu có cùng key sẽ được cập nhật theo dữ liệu trong file import.
Menu đã xóa mềm sẽ được restore rồi cập nhật, giữ nguyên ID.
```

The dangerous/destructive `replace all` behavior must not appear in this selector.

## Skip Mode

When:

```text
importMode = skip_duplicate
```

and the normalized `key` already exists:

```text
active existing menu
    ↓
SKIP
    ↓
no overwrite
```

If the matching row is soft-deleted, restore it so the unique slug remains valid and the imported hierarchy becomes visible again.

The import report should increment `skipped_rows` for unchanged active duplicates and `success_rows` for restored rows.

## Update Mode

When:

```text
importMode = update_or_create
```

and the normalized `key` already exists, update the existing record instead of deleting/recreating it.

Preserve the existing database `id`.

If the matching row is soft-deleted:

```text
RESTORE
→ UPDATE
```

Update the importable fields according to the current contract:

```text
name
url
icon
can
is_active
sort_order
parent_id (resolved from parent_key)
```

Do not create a replacement row merely to update existing data.

If the key does not exist, create a new menu normally.

## Parent / Child Resolution

The existing two-pass spreadsheet import behavior should remain:

```text
Pass 1
→ create/update/restore/skip by key

Pass 2
→ resolve parent_key → parent_id
```

In `update_or_create` mode, parent relationships may therefore be updated to match the file.

If `parent_key` references neither:

- another valid row in the import file, nor
- an existing/soft-deleted database menu that can be resolved,

validation should fail before persistence.

## Authorization

Both import modes use the existing permission:

```text
admin.menu.import
```

No new permission is required.

## Livewire Integration

Update `import()` to pass the selected mode rather than hard-coding:

```text
['mode' => $this->importMode]
```

Server-side validation must whitelist the two allowed modes.

Never trust an arbitrary browser-provided mode value.

Conceptually:

```text
importMode
    ↓ validate whitelist
skip_duplicate | update_or_create
    ↓
MenuImportExportService::importFromFile(...)
```

## Modal Lifecycle

When opening a fresh import modal:

```text
importMode = skip_duplicate
```

When closing/resetting the modal, restore the default so a previous `update_or_create` choice does not silently carry into an unrelated later import.

## Import Report

Preserve current counts:

```text
total_rows
success_rows
skipped_rows
error_rows
```

For update mode, successfully updated rows count as:

```text
success_rows
```

No raw exception/debug details may be exposed in the normal browser report.

---

# Part C — Collapse / Expand Child Menus

## Goal

Add a lightweight UI control to each menu item that has children so users can temporarily hide/show its child list without changing menu data.

This is UI state only.

No database write, Livewire mutation, permission change, route change, or schema migration is required.

## Primary File

```text
Modules/Admin/resources/views/components/menu-item.blade.php
```

The current component already recursively renders children through:

```text
<x-menu-item :menu="$child" :selected="$selected" />
```

and is therefore the correct place for per-node collapse behavior.

## Interaction

For a menu with one or more children, render a collapse/expand button near the menu name or actions.

Recommended behavior:

```text
▼  expanded
▶  collapsed
```

or equivalent chevron icons.

Clicking the control:

```text
expanded → hide direct child <ul>
collapsed → show direct child <ul>
```

Each parent menu controls only its own descendant container.

Nested child menus may independently be collapsed/expanded.

## Technology

Use Alpine.js local UI state in the Blade component, for example conceptually:

```text
x-data="{ open: true }"
x-show="open"
@click="open = !open"
```

Do not create Livewire properties/actions for this visual-only state.

Reason:

- no server round-trip is needed;
- collapse state is purely presentational;
- avoids unnecessary component requests;
- keeps `MenuTable.php` focused on domain/UI actions that require server state.

## Default State

Default should be:

```text
expanded = true
```

so current behavior remains backward-compatible.

Do not persist collapse state to database in this feature.

Optional persistence via browser/local storage can be considered later as a separate feature.

## Child Count

Recommended UI enhancement:

For parent menus, show the number of direct children near the toggle, e.g.:

```text
Công cụ hệ thống  (4)
```

This count is display-only and should derive from the already-loaded children collection.

## Drag-Sort Compatibility

The collapse implementation must not break Sortable.js nesting.

Requirements:

- keep the child `<ul class="menu-list ...">` in the DOM;
- hide/show visually rather than conditionally removing it from Blade/Livewire state;
- preserve `data-id` and `.menu-list` structure;
- ensure expanding restores drag-sort usability;
- do not move the drag handle into the collapse button.

If `x-show` causes a Sortable visual/layout issue, use a CSS/Alpine hidden-class approach while keeping the same DOM structure.

## Selection Compatibility

Collapsing a parent must not clear or modify checkbox selections.

Example:

```text
select child menu
→ collapse parent
→ child remains selected
→ bulk/export actions still include that child
```

The collapse state must not alter `selectedMenus` or `selectAll` semantics.

## Search / Filter Behavior

Search/status filtering continues to be controlled by Livewire.

After Livewire re-renders a changed result set, newly rendered menu nodes may return to default expanded state. This is acceptable for this first version.

Do not add global persisted UI state unless separately approved.

## Accessibility

The toggle button should include:

- `type="button"`;
- accessible label/title;
- `aria-expanded` bound to state;
- visible focus style;
- adequate click target.

Do not rely only on icon orientation to communicate state.

## Optional Top-Level Controls

Not part of this initial implementation:

```text
Collapse all
Expand all
Persist collapsed nodes
```

These may be added later if needed.

---

# Tests / Verification

## Export Selected

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
```

## Import Duplicate Strategy

```text
[ ] default import mode is skip_duplicate
[ ] arbitrary mode values are rejected
[ ] active duplicate key + skip mode leaves row unchanged
[ ] active duplicate key + skip mode increments skipped_rows
[ ] soft-deleted duplicate + skip mode restores record safely
[ ] duplicate key + update mode preserves database ID
[ ] soft-deleted duplicate + update mode restores then updates
[ ] duplicate key + update mode updates importable fields
[ ] update mode can update parent relationship using parent_key
[ ] non-existing key is created in both modes
[ ] replace mode is not exposed by the normal import UI
[ ] import still requires admin.menu.import
[ ] closing/reopening modal resets mode to skip_duplicate
```

## Collapse / Expand

```text
[ ] only menu items with children show collapse toggle
[ ] default state is expanded
[ ] clicking toggle hides/shows direct child list
[ ] nested parents can be toggled independently
[ ] selection survives collapse/expand
[ ] drag-sort still works after expand
[ ] no Livewire request is triggered solely by collapse/expand
[ ] aria-expanded reflects current state
[ ] menu items without children do not show a meaningless toggle
```

Manual verification:

```text
1. Expand/collapse a root menu with children.
2. Expand/collapse a nested parent independently.
3. Select a child, collapse its parent, then export selected.
4. Re-expand and confirm checkbox remains selected.
5. Drag a child after re-expanding.
6. Change search/status and confirm menu renders normally.
```

---

# Compatibility

Preserve:

- existing routes;
- Livewire component alias/path;
- `admin.menu.export` permission;
- `admin.menu.import` permission;
- current XLSX/CSV headers;
- current duplicate handling behavior as refined above;
- existing default JSON restore workflow using `replace`;
- menu database schema;
- current hierarchy semantics;
- existing Sortable.js drag/drop behavior.

No migration is required.

## Acceptance Criteria

```text
[ ] bulk bar has "Export đã chọn"
[ ] selected export contains exactly selected menus
[ ] import modal lets user choose Skip or Update
[ ] Skip remains default
[ ] duplicate identity is based on canonical key/slug
[ ] soft-deleted duplicates are handled without unique-key errors
[ ] Update modifies existing menu without replacing its ID
[ ] Replace is not exposed for normal upload
[ ] parent menus have an expand/collapse control
[ ] collapse is local UI state and does not mutate database
[ ] nested collapse works independently
[ ] drag-sort and checkbox selection remain compatible
[ ] current import/export formats remain compatible
[ ] loading/error UX remains safe and consistent
```

## Implementation Gate

**Do not implement the new collapse/expand feature until the user explicitly approves this updated `CHANGE_PLAN.md`.**
