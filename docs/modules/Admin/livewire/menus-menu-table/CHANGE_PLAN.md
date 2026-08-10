# CHANGE PLAN — Export Selected Menus + Import Duplicate Strategy

## Goal

Extend `Admin / Menus / MenuTable` with two additive capabilities:

1. Export only the menus currently selected by checkbox.
2. Let the user choose how duplicate menu rows are handled during import:
   - **Bỏ qua dữ liệu đã tồn tại** (`skip_duplicate`) — default.
   - **Cập nhật dữ liệu đã tồn tại** (`update_or_create`).

Existing filtered export, template export, restore, bulk actions, permissions, routes, menu hierarchy and database schema must remain compatible.

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

Current implementation normalizes this value and maps it to the menu `slug`.

Conceptually:

```text
Excel key
    ↓ normalize
AdminMenu.slug
```

A row is considered duplicate when its normalized `key`/slug already exists.

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
Menu có cùng key sẽ không bị thay đổi.
```

### Cập nhật

```text
Menu có cùng key sẽ được cập nhật theo dữ liệu trong file import.
```

The dangerous/destructive `replace all` behavior must not appear in this selector.

## Skip Mode

When:

```text
importMode = skip_duplicate
```

and the normalized `key` already exists:

```text
existing menu
    ↓
SKIP
    ↓
no overwrite
```

The import report should increment `skipped_rows`.

Existing menu values must remain unchanged.

## Update Mode

When:

```text
importMode = update_or_create
```

and the normalized `key` already exists, update the existing record instead of deleting/recreating it.

Preserve the existing database `id`.

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
→ create/update/skips by key

Pass 2
→ resolve parent_key → parent_id
```

In `update_or_create` mode, parent relationships may therefore be updated to match the file.

If `parent_key` references neither:

- another valid row in the import file, nor
- an existing database menu,

validation should fail before persistence as it does currently.

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
[ ] duplicate key + skip mode leaves existing row unchanged
[ ] duplicate key + skip mode increments skipped_rows
[ ] duplicate key + update mode preserves database ID
[ ] duplicate key + update mode updates importable fields
[ ] update mode can update parent relationship using parent_key
[ ] non-existing key is created in both modes
[ ] replace mode is not exposed by the normal import UI
[ ] import still requires admin.menu.import
[ ] closing/reopening modal resets mode to skip_duplicate
```

Manual verification:

```text
1. Export a few selected menus.
2. Edit name/url/icon in exported XLSX without changing key.
3. Import using "Bỏ qua" and confirm DB/menu remains unchanged.
4. Import same file using "Cập nhật" and confirm fields change while menu ID remains unchanged.
5. Verify parent-child relationships after update import.
6. Verify existing filtered Export Excel still behaves normally.
```

---

# Compatibility

Preserve:

- existing routes;
- Livewire component alias/path;
- `admin.menu.export` permission;
- `admin.menu.import` permission;
- current XLSX/CSV headers;
- current `skip_duplicate` default behavior;
- existing default JSON restore workflow using `replace`;
- menu database schema;
- current hierarchy semantics.

No migration is required.

## Acceptance Criteria

```text
[ ] bulk bar has "Export đã chọn"
[ ] selected export contains exactly selected menus
[ ] import modal lets user choose Skip or Update
[ ] Skip remains default
[ ] duplicate identity is based on normalized key/slug
[ ] Update modifies existing menu without replacing its ID
[ ] Replace is not exposed for normal upload
[ ] current import/export formats remain compatible
[ ] loading/error UX remains safe and consistent
```

## Implementation Gate

**Do not implement these features until the user explicitly approves this updated `CHANGE_PLAN.md`.**
