# Phase 11A — Homepage Section Registry & Renderer

## Status

Implemented on `refactor/website-homepage-phase-11`.

## Goal

Create one canonical registry for Homepage section metadata and frontend renderers before changing Builder lifecycle in Phase 11B.

## Canonical registry

Configuration:

`Modules/Website/Config/homepage.php`

Service:

`Modules/Website/Services/HomepageSectionRegistry.php`

Each canonical section defines:

- `type`
- `label`
- `description`
- `renderer`
- optional static `params`
- optional context `props`
- `duplicatable`

The initial registry contains 10 canonical Homepage sections:

1. hero
2. categories
3. flash_sale
4. featured
5. new_arrivals
6. best_sellers
7. blog_highlight
8. promo_banner
9. trust_badges
10. newsletter

## Copy resolution

Duplicated sections retain keys such as:

- `featured_copy_1`
- `new_arrivals_copy_2`
- `best_sellers_copy_1`

The registry strips the `_copy_N` suffix and resolves the copy through its canonical definition. This keeps the renderer deterministic even when multiple sections share the structured type `product_grid`.

## Frontend renderer

Before 11A, `home-list.blade.php` contained a long `@switch` mapping section types and special cases to Livewire components.

After 11A:

1. `HomeList` resolves every section through `HomepageSectionRegistry`.
2. The component prepares a safe `renderer + params` contract.
3. Blade renders the resolved Livewire component dynamically.
4. Unknown/mismatched sections are skipped instead of executing arbitrary renderer names.

No renderer path is persisted in `WebsiteSection` or settings.

## Responsive visibility correction

The previous special wrapper `hidden md:block` around Trust Badges was removed from the Homepage orchestration view. All sections now obey the shared visibility contract (`all`, `desktop`, `mobile`, `none/hidden`) consistently.

## Admin integration

`HomeSettings` no longer declares the canonical `show_*` layout array itself. It initializes canonical layout keys from the registry and exposes `homepageSections` to the Admin view.

The existing detailed Admin form remains functionally unchanged in 11A. UI restructuring and per-section configuration panels belong to Phase 11C.

## Content service integration

`HomepageContentService` now derives fallback canonical section keys and fallback structured types from the registry. This removes another duplicated canonical section list from runtime composition.

## Compatibility

Phase 11A intentionally does **not** remove:

- `home_*` compatibility settings
- `HomepageBackfillService`
- current duplicate/remove/restore persistence behavior
- current Homepage Admin tabs
- current content models

Those are handled in later phases according to `PHASE_11_ANALYSIS.md`.

## Security / safety rules

- Renderer aliases exist only in application config.
- Unknown section keys are rejected by the registry.
- Stored section types must match the canonical key/type contract.
- Section copies resolve only to their canonical source definition.
- Database content cannot inject arbitrary Blade/Livewire renderer names.

## Focused validation

Run only Website/Homepage-scoped tests:

```bash
php artisan test \
  tests/Feature/Website/WebsiteHomepageSectionRegistryConfigurationTest.php
```

Then run existing Homepage-related Website tests if present. Do not use full-project `php artisan test` for this phase.

Also validate Blade compilation:

```bash
php artisan view:clear
php artisan view:cache
```

## UI regression checklist

At `/admin/homepage-settings`:

- existing 10 canonical sections still appear;
- section order remains unchanged;
- visibility selectors remain functional;
- duplicate/remove/restore behavior remains unchanged for 11A;
- product/category configuration remains unchanged.

At storefront `/`:

- all enabled sections render in their previous order;
- Featured, New Arrivals and Best Sellers use their correct renderers;
- duplicated product sections resolve to their canonical renderer;
- Trust Badges obey configured Desktop/Mobile visibility instead of being forcibly desktop-only.

## Next phase

Phase 11B — Builder State & Safe Drag/Drop:

- move duplicate/remove/restore to Builder state first;
- no DB mutation before Save;
- replace current Sortable CDN dependency with the approved Builder drag/drop contract;
- publish only after explicit Save.
