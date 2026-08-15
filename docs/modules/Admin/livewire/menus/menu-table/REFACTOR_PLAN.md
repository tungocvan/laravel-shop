# MenuTable Refactor Plan

## Status

**PLANNING COMPLETE — AWAITING APPROVAL**

## Goal

Align `MenuTable` with the repository's current Admin UI / Import-Export standards without changing menu routes, storage/schema, permissions or service architecture.

## Approved Scope After User Approval

### P1 — Canonical export behavior

Replace the split export UX with one checkbox-aware export action:

```text
selectedMenus empty     -> export all records in current approved filter scope
selectedMenus not empty -> export only selected menu IDs
```

`MenuImportExportService` existing `export()` / `exportSelected()` methods may remain; the Livewire action can choose the correct service method.

### P1 — Destructive modal UX

- Replace bulk `wire:confirm` with centered modal confirmation.
- Modal must show selected count/scope, Cancel and explicit destructive action.
- Keep backend `admin.menu.delete` authorization.
- Keep loading/disabled state.

If single-row delete is currently immediate, align it to an explicit confirmation flow without changing the delete service contract.

### P2 — Form/filter visual consistency

Adopt shared Admin form primitives for search/status where low-risk:

```text
x-admin::form.input
x-admin::form.select
```

Preserve query-string behavior and selection reset on filter changes.

### P2 — Workspace width

Remove unnecessarily narrow `max-w-5xl` constraint and use the admin shell width with modest responsive padding.

### P2 — Success feedback

Keep existing notify mechanism unless a verified usability issue requires a modal. Import/export behavior should remain explicit and loading-safe.

## Contracts To Preserve

- Drag/drop nested menu sorting.
- Search/status filters and query string.
- "Select all" means all menus matching the current filter scope; UI wording must remain explicit.
- Import modes `skip_duplicate` and `update_or_create`.
- Restore-default flow.
- Named permissions for import/export/create/update/delete/restore.
- `MenuService` and `MenuImportExportService` remain domain/service boundaries.

## Files Expected To Change

```text
Modules/Admin/Livewire/Menus/MenuTable.php
Modules/Admin/resources/views/livewire/menus/menu-table.blade.php
possibly a small Admin menu confirmation component
tests/Feature/Admin/** menu-focused tests
docs/modules/Admin/livewire/menus/menu-table/ANALYSIS.md
docs/modules/Admin/livewire/menus/menu-table/REFACTOR_PLAN.md
```

## Verification

```bash
vendor/bin/pint --test Modules/Admin tests/Feature/Admin
php artisan test tests/Feature/Admin
npm run build
php artisan test
```

Manual smoke:

- search/status
- select-all filter semantics
- export with no selection
- export with selected rows
- bulk delete modal
- drag/drop reorder
- import modal

## Acceptance Criteria

- [ ] One canonical export button/action follows all-vs-selected contract.
- [ ] Bulk delete uses centered modal confirmation.
- [ ] Search/select controls visibly follow Admin form standard.
- [ ] Selection/filter behavior preserved.
- [ ] No business logic moved into Blade.
- [ ] Permissions remain enforced server-side.
- [ ] Targeted and full regression pass.

## Approval Gate

**AWAITING USER APPROVAL**
