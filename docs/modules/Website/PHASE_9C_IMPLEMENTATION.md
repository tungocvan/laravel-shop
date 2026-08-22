# Phase 9C Implementation — Header Schema & Component Registry

## Status

`IMPLEMENTED — WAITING FOR TARGETED WEBSITE TESTS AND UI REGRESSION APPROVAL`

## Scope

Phase 9C introduces the schema and registry layer approved in `PHASE_9_ANALYSIS.md`.

This slice does **not** introduce:

- Header Builder admin UI;
- drag/drop;
- persistence of custom layouts;
- arbitrary administrator-supplied view names/classes;
- visual redesign.

## Architecture

Runtime flow:

```text
Modules/Website/Config/header.php
        ↓
HeaderComponentRegistry
        ↓
HeaderLayoutService
        ↓
$headerLayout
        ↓
components/header/slot.blade.php
        ↓
registered component views
```

The persisted/schema-facing layout contains only stable component `type` values and optional validated configuration. Renderer/view names remain server-owned in the registry.

## Default Slots

```text
desktop.topbar
desktop.main.left
desktop.main.center
desktop.main.right
mobile.search
mobile.drawer
```

The default schema reproduces the current Phase 9A header composition:

```text
desktop.topbar       -> topbar
desktop.main.left    -> brand
desktop.main.center  -> search(mode=desktop)
desktop.main.right   -> actions
mobile.search        -> search(mode=mobile)
mobile.drawer        -> mobile-menu
```

## Security / Validation Rules

- unknown component types are rejected by the registry;
- components can render only in declared `allowed_slots`;
- layout normalization silently omits invalid/unknown items rather than executing them;
- schema items do not store Blade paths;
- registry renderer paths are application-owned config;
- no arbitrary executable class or view injection is accepted from layout data.

## Compatibility

The existing header data contract remains unchanged:

```text
$headerSettings
$mainMenu
$mobileMenu
$accountMenu
```

Phase 9C adds:

```text
$headerLayout
```

The default schema intentionally reproduces the same visual order and component configuration as Phase 9A/9B.

## Test Policy

Only Website-targeted tests are required for this phase.

Run:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan view:cache
php artisan test tests/Feature/Website
```

For a faster Phase 9C-focused gate:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHeaderSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteSettingsConfigurationTest.php \
  tests/Feature/Website/WebsiteProductionOptimizationTest.php
```

Do not run the full repository suite unless a separate release/regression request requires it.

## Manual UI Regression

```text
[ ] topbar unchanged
[ ] logo/brand unchanged
[ ] desktop search unchanged
[ ] desktop actions/navigation unchanged
[ ] account/wishlist/cart unchanged
[ ] mobile search unchanged
[ ] mobile drawer unchanged
[ ] no responsive overflow/regression
```

## Approval Gate

Do not start Phase 9D until targeted Website tests and UI regression are explicitly approved.
