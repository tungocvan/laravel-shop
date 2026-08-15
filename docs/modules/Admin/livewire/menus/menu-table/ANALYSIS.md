# MenuTable Livewire Analysis

## Component

`Modules/Admin/Livewire/Menus/MenuTable.php`

Blade:

`Modules/Admin/resources/views/livewire/menus/menu-table.blade.php`

## Purpose

Admin workspace for managing the menu tree: search/status filtering, drag-drop ordering, selection, bulk status/permission/delete actions, import/export, restore defaults and create/edit navigation.

## Strengths

- Business operations are delegated to `MenuService` / `MenuImportExportService` rather than implemented directly in Blade.
- Sensitive mutations authorize server-side with named permissions.
- Selection resets when search/status changes.
- Import has file validation, import modes and public-safe report shaping.
- Drag/drop ordering is delegated to service and reports invalid payloads cleanly.
- Loading states exist for most mutation/import/export actions.
- Empty state and selected count are present.

## Findings

### P1 — Export contract drift

The component exposes separate `export()` and `exportSelected()` actions. The new repository Import/Export standard requires one canonical checkbox-aware export behavior:

```text
no selected IDs -> export all records in approved filter scope
selected IDs    -> export selected records only
```

Keeping two buttons makes the behavior less consistent with other refactored modules and easier to misuse.

### P1 — Destructive confirmation UX

Bulk delete currently uses inline `wire:confirm`. Repository Admin UI standard now prefers a centered modal with explicit scope, Cancel, destructive confirmation and loading/disabled state.

Single-row delete should also use a consistent confirmation path if the row action is currently immediate.

### P1 — Selection semantics

`updatedSelectAll()` uses `idsForSelection($filters)` and therefore means all matching filtered records rather than only visible page rows. This is acceptable for a tree workspace only if the UI wording remains explicit. Current label already says "Chọn tất cả theo bộ lọc hiện tại", which is good and should be preserved.

### P2 — Search/filter visual standard

Search and status controls use direct Tailwind classes. They are visible, but should adopt shared Admin form controls where low-risk to validate the new form-component standard.

### P2 — Import/Export UI consistency

MenuTable has a custom import modal/report rather than the newer shared Import/Export panel. Because menu import/export includes restore-default behavior and a tree-specific service, replacing the entire flow is not required for this refactor. The important goal is to align export selection semantics, success feedback, loading states and visible form controls without changing the business contract.

### P2 — Workspace width

The page uses `max-w-5xl` even though it manages a tree workspace with many controls. `ADMIN_UI_STANDARD.md` discourages unnecessarily narrow global wrappers inside the admin shell. A wider workspace would improve scanability without changing behavior.

## Security / Data Integrity

Current authorization is good: create/update/delete/import/export/restore operations authorize inside component actions. Service boundaries should remain unchanged.

No business logic should be moved into Blade during refactor.

## Refactor Classification

**Moderate component refactor.** No rebuild and no schema/route changes required.
