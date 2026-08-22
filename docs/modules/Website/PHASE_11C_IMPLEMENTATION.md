# Phase 11C — Homepage Section Configuration & Admin UI Standard

## Status

Implemented on `refactor/website-homepage-phase-11`.

## Goals

Phase 11C turns Homepage Settings into a registry-driven admin experience and applies the shared Website Admin UI contract documented in `ADMIN_UI_INPUT_STANDARD.md`.

## Registry-driven section cards

`HomepageSectionRegistry::adminCards()` is the single source for Builder card metadata.

Each card resolves:

- layout key
- section key
- canonical section key
- label
- description
- duplicatable flag
- copy state
- admin action

The active Homepage Admin view does not maintain its own `$sections = [...]` metadata map.

## Component administration actions

Section definitions in `Config/homepage.php` may declare an `admin` contract.

Two action types are supported:

### Route action

Used when the component has a dedicated admin screen.

Examples:

- Hero / Banner Slider → `admin.banners`
- Flash Sale → `admin.flash-sales`

The Registry verifies `Route::has()` before returning a route action.

### Homepage tab action

Used when business content belongs to Homepage Settings itself.

Examples:

- Categories → `data`
- Featured Products → `data`
- New Arrivals / Best Sellers → `data`
- Promo Banner → `data`
- Newsletter → `data`
- Trust Badges → `trust_badges`

No route URL is hardcoded in the Blade view.

## UI Input Standard

The Phase 11C view uses the approved Website Admin field baseline:

- visible `border-gray-300`
- explicit white input background
- readable padding and text sizing
- hover border state
- visible focus border and focus ring
- consistent labels and helper text
- bordered cards for grouped configuration
- dashed empty states
- sticky primary Save action

The shared reference remains:

`docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md`

## Active and rollback views

Active view:

`Modules/Website/resources/views/livewire/admin/home/home-settings-v2.blade.php`

The previous `home-settings.blade.php` remains temporarily in the repository as a rollback reference during Phase 11. It is no longer rendered by `HomeSettings`.

## Builder lifecycle

Phase 11C does not alter the Phase 11B persistence contract.

Reorder, duplicate, hide and restore still mutate Builder state only. Database persistence still occurs only through the primary `save()` action.

## Homepage themes

Homepage themes remain scheduled for Phase 11E.

The accepted contract requires:

- Save theme
- Apply theme
- Update theme
- Rename theme
- Delete theme
- Export JSON
- Import JSON with schema/version validation

Themes may contain layout and presentation only. They must not contain business content such as category IDs, product IDs, banner images, newsletter copy or other section content.

## Targeted tests

```bash
php artisan test \
  tests/Feature/Website/WebsiteHomepageSectionAdminActionConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageSectionAdminUiConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageBuilderStateConfigurationTest.php \
  tests/Feature/Website/WebsiteHomepageSectionRegistryConfigurationTest.php
```

Do not use the full-project test suite for Phase 11C validation.
