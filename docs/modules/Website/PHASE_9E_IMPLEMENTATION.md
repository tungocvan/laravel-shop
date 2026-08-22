# Phase 9E Implementation — Drag/drop & Responsive Preview

## Status

`IMPLEMENTED — WAITING FOR TARGETED WEBSITE TESTS / ADMIN UI REGRESSION / USER APPROVAL`

## Scope

Phase 9E upgrades the Phase 9D Header Builder interaction layer without changing its persistence schema.

Included:

- native HTML5 drag/drop for Header components;
- reorder within the same slot;
- movement across Registry-approved slots;
- server-side Registry validation for every drop;
- fallback ↑/↓ and select movement retained;
- responsive preview modes: Desktop / Tablet / Mobile;
- preview from current unsaved Livewire state;
- live Basic/Advanced presentation updates in preview;
- preview of enabled/disabled component state;
- no external sorting library or CDN dependency.

Not included:

- arbitrary component creation;
- arbitrary Blade/class persistence;
- pixel-position free-form page-builder behavior;
- multiple named Header layouts;
- draft/publish/version history.

## Drag/drop Contract

The browser only supplies interaction intent:

```text
from slot
from index
to slot
to index
```

The server method:

```php
moveComponentByDrag(...)
```

must still validate:

```text
known builder slot
registered component type
allowed_slots from HeaderComponentRegistry
website.settings.manage permission
```

A component cannot bypass the Registry by manipulating browser-side drag data.

## No External Sorting Dependency

Phase 9E intentionally uses:

```text
HTML5 draggable
Alpine drag state
Livewire server mutation
```

No SortableJS or new CDN/package is required.

The existing ↑/↓ and slot select controls remain available as keyboard/fallback administration controls.

## Responsive Preview

Preview is rendered from current Builder state rather than an iframe to the saved storefront.

Inputs:

```text
builderSlots
previewPresentation
HeaderComponentRegistry metadata
Website design defaults
```

This means administrators can inspect changes before pressing `Lưu bố cục`.

Preview modes:

```text
Desktop  — full three-slot Header composition
Tablet   — compressed three-slot composition
Mobile   — compact Header plus mobile search/drawer representation
```

The preview is schematic and administration-oriented. The real storefront remains the final regression source of truth.

## Live Presentation Updates

Presentation controls use Livewire live updates so the preview refreshes before persistence.

Covered values include:

```text
container preset
header size
sticky
shadow
color inheritance
header colors
advanced responsive heights
logo max height
search max width
topbar height
```

`HeaderPresentationService` remains responsible for normalization and safety bounds.

## Persistence

No new setting keys or migrations are introduced.

Existing Phase 9D persistence remains:

```text
header.layout
header.presentation
```

Drag/drop only mutates the in-memory Builder state until `Lưu bố cục` is pressed.

## Test Policy

Do not run the entire repository suite.

Required scope:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan view:cache
php artisan test tests/Feature/Website
```

Focused Phase 9E command:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHeaderBuilderInteractionTest.php \
  tests/Feature/Website/WebsiteHeaderBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteHeaderSchemaConfigurationTest.php
```

## Admin UI Regression Gate

Route:

```text
/admin/header-settings
```

Open tab:

```text
Bố cục Header
```

Verify:

```text
[ ] drag within a desktop slot reorders correctly
[ ] drag brand/search/actions among allowed desktop slots works
[ ] invalid cross-slot drop is rejected with Builder error
[ ] dragging to the end-drop zone works
[ ] enable/disable still works
[ ] ↑/↓ fallback still works
[ ] slot select fallback still works
[ ] Desktop preview opens
[ ] Tablet preview opens
[ ] Mobile preview opens
[ ] preview changes immediately after drag/drop
[ ] preview changes after enable/disable
[ ] Basic size/container changes update preview before save
[ ] color changes update preview before save
[ ] Advanced height/width changes update preview before save
[ ] saving persists state after reload
[ ] reset restores recommended defaults
```

## Storefront Regression Gate

After saving a representative Builder layout, verify frontend Desktop / Tablet / Mobile:

```text
[ ] component order matches saved layout
[ ] disabled components remain hidden
[ ] topbar remains valid
[ ] logo/brand remains balanced
[ ] search remains functional
[ ] actions/account/cart/wishlist remain functional
[ ] mobile search remains functional
[ ] mobile drawer remains functional
[ ] no horizontal overflow
[ ] responsive dimensions remain within approved bounds
```

## Approval Gate

Do not merge Phase 9E until:

1. targeted Website tests pass;
2. admin drag/drop regression passes;
3. responsive preview regression passes;
4. storefront regression passes;
5. the user explicitly approves Phase 9E.
