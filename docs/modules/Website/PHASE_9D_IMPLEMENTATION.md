# Phase 9D Implementation — Header Builder Admin

## Status

`IMPLEMENTED — WAITING FOR TARGETED WEBSITE TESTS / ADMIN UI REGRESSION / USER APPROVAL`

## Scope

Phase 9D builds the administrator-facing Header Builder on top of the approved Phase 9C schema and registry.

Included in this slice:

- Header Builder tab inside the existing Header Settings Hub;
- component enable/disable;
- component reorder within a slot;
- movement between registry-approved slots;
- Basic presentation presets;
- Advanced custom dimensions with safety bounds;
- sticky/shadow controls;
- global-theme color inheritance or controlled Header color overrides;
- reset to recommended defaults;
- persistence through existing `SettingsService`;
- storefront consumption of persisted layout/presentation.

Not included:

- drag/drop interactions;
- desktop/tablet/mobile live preview frames;
- arbitrary component creation;
- arbitrary Blade/class persistence;
- multiple named Header layouts;
- draft/publish/version history.

Those remain Phase 9E or later requirements.

## Persistence

No migration or new table is introduced.

Phase 9D uses the existing settings architecture:

```text
header.layout
header.presentation
```

`header.layout` stores only safe schema data:

```text
type
enabled
config
```

Blade view paths remain source-owned inside `HeaderComponentRegistry`.

## Builder Slots

Current managed slots:

```text
desktop.topbar
desktop.main.left
desktop.main.center
desktop.main.right
mobile.search
mobile.drawer
```

Desktop `brand`, `search` and `actions` may move among the three main desktop slots because the registry explicitly permits those locations.

Topbar and mobile-only components remain constrained to their compatible slots.

## Basic Presentation

Basic mode provides safe semantic choices:

```text
Container:
- compact ~1024px
- standard ~1280px (default)
- wide ~1440px
- full

Header size:
- compact
- normal (default)
- comfortable

Sticky:
- enabled by default

Shadow:
- none
- soft (default)
- medium
```

Colors inherit the Phase 9B global Website design tokens by default.

Administrators may explicitly disable inheritance and provide Header overrides for:

```text
background
foreground
accent
border
topbar background
topbar foreground
```

Only validated six-digit hexadecimal values survive normalization.

## Advanced Presentation

Advanced mode exposes bounded pixel values.

Current safety limits:

```text
Container max width : 960–1920px
Header height       : 52–120px
Topbar height       : 24–56px
Logo max height     : 24–72px
Search max width    : 320–900px
```

Values outside these ranges are clamped by `HeaderPresentationService` before persistence/rendering.

## Authorization

Header layout/presentation mutations require:

```text
website.settings.manage
```

Existing menu mutations keep their existing `website.menu.manage` permission boundary.

## Storefront Rendering

Rendering flow after Phase 9D:

```text
SettingsService
    ├── header.layout
    └── header.presentation
            ↓
HeaderLayoutService + HeaderPresentationService
            ↓
WebsiteServiceProvider view composer
            ↓
header.blade.php
            ↓
registry-controlled slot renderer
```

Presentation is translated to bounded CSS variables for:

```text
container width
responsive header heights
logo max height
search max width
topbar height
header colors
sticky state
shadow
```

## Test Policy

Do not run the entire repository suite for this phase.

Required scope:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan view:cache
php artisan test tests/Feature/Website
```

Focused Phase 9D command:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHeaderBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteHeaderSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteSettingsConfigurationTest.php \
  tests/Feature/Website/WebsiteProductionOptimizationTest.php
```

## Admin UI Regression Gate

Verify:

```text
[ ] Header Settings Hub loads normally
[ ] General tab still works
[ ] Menu tab still works
[ ] Header Builder tab opens
[ ] enable/disable works
[ ] up/down reorder works
[ ] allowed desktop slot movement works
[ ] invalid slot movement is rejected
[ ] Basic container presets save
[ ] Basic size presets save
[ ] sticky toggle saves
[ ] shadow setting saves
[ ] global color inheritance saves
[ ] Header color overrides save
[ ] Advanced values save and remain bounded
[ ] Reset restores recommended defaults before save
[ ] saved settings survive page reload
```

## Storefront UI Regression Gate

Verify desktop/tablet/mobile:

```text
[ ] default Header remains balanced
[ ] topbar remains aligned
[ ] brand/logo renders correctly
[ ] desktop search works
[ ] navigation/actions work
[ ] account/cart/wishlist behavior remains intact
[ ] mobile search works
[ ] mobile drawer works
[ ] sticky off/on behaves correctly
[ ] container presets do not cause horizontal overflow
[ ] custom safe dimensions do not break layout
[ ] color inheritance matches global Website theme
```

## Approval Gate

Do not start Phase 9E until:

1. targeted Website tests pass;
2. Header Builder admin UI regression passes;
3. storefront UI regression passes;
4. the user explicitly approves Phase 9D.
