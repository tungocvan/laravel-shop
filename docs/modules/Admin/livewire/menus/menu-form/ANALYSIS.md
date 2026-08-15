# MenuForm Livewire Analysis

## Component

`Modules/Admin/Livewire/Menus/MenuForm.php`

Blade:

`Modules/Admin/resources/views/livewire/menus/menu-form.blade.php`

## Purpose

Create/edit Admin menu records including name, parent, URL/section behavior, icon, permission and active state.

## Strengths

- Create/update permission is enforced server-side in `save()`.
- Parent options are built hierarchically for a clear admin experience.
- Section mode clears URL and keeps the workflow understandable.
- Unique slug generation avoids collisions.
- Cache invalidation occurs after save.

## Findings

### P1 — Business/query logic is too concentrated in Livewire

`MenuForm` queries `AdminMenu` directly for parent options, validation fallback, slug generation, max sort order and persistence. This is functional but inconsistent with the module's `MenuService` boundary already used by `MenuTable`.

Refactor should move create/update preparation and menu-specific persistence rules into `MenuService` while keeping UI state/validation in Livewire.

### P1 — Repeated queries during save

The component performs multiple direct `AdminMenu` queries in a single save path, including re-reading the current menu name and checking parent existence. These can be consolidated in a service method and avoid avoidable round trips.

### P1 — Form markup defect

The permission `<select wire:model="can" ...` opening tag in Blade is missing its closing `>` before the first `<option>`. This is malformed markup and should be fixed.

### P1 — Form controls violate the new visible-border standard

Several inputs/selects still use `border-0 + ring-1`. The new `ADMIN_UI_STANDARD.md` requires ordinary admin controls to have a clearly visible `border border-gray-300` resting state. This component is an ideal low-risk adopter of:

- `<x-admin::form.input>`
- `<x-admin::form.select>`

Special prefix/icon compositions may keep a small wrapper but must preserve a visible outer border.

### P2 — Types and structure

Public properties and lifecycle methods are largely untyped. Adding safe scalar/nullable types where Livewire-compatible would improve maintainability.

`buildTreeOption()` mutates model instances with a transient `view_name` property. A service/view-model array structure would be cleaner, but this is not necessary if it materially expands scope.

### P2 — Error handling

`save()` catches all throwables and exposes `$e->getMessage()` to the session. Admin users are trusted, but raw exception messages can reveal internal details. Prefer reporting the exception and showing a stable user-facing error message.

### P2 — Permission option query

`render()` directly queries all permissions on every render. This should be moved to the service or cached/derived in a way consistent with the component lifecycle.

## Security / Data Integrity

Authorization is present and must remain at the action boundary. Parent/self-reference and slug uniqueness must remain safe after extraction.

## Refactor Classification

**Moderate component refactor.** No route/schema changes required; service extraction + form UI standard adoption is appropriate.
