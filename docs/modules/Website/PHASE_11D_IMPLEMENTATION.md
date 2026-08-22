# Phase 11D — Homepage Presentation & Responsive Preview

## Scope

Phase 11D introduces a presentation contract for Homepage and a responsive Admin preview without changing the structured content model from 11A–11C.

## Persistence

Presentation is stored separately at:

```text
homepage.presentation
```

The contract contains only visual/layout values:

- `mode`: `basic|advanced`
- `container`: `standard|wide|full`
- `spacing`: `compact|normal|comfortable`
- `custom.container_width`
- `custom.page_padding`
- `custom.section_gap`
- `custom.mobile_section_gap`

Business content, category/product IDs and section content are not part of presentation.

## Resolver

`Modules/Website/Services/HomepagePresentationService.php`

The resolver owns defaults, whitelists enum values and clamps numeric values before persistence/runtime use.

## Admin preview

`home-settings-v3.blade.php` wraps the Phase 11C Admin UI and adds:

- Desktop/Mobile device switch
- presentation controls following `ADMIN_UI_INPUT_STANDARD.md`
- advanced numeric tokens
- section-order preview driven by current Builder state
- visibility-aware Desktop/Mobile preview

The preview is state-only. It does not publish settings until the existing `save()` action is executed.

## Frontend

`HomeList` reads `homepage.presentation` through `SettingsService`, resolves it through `HomepagePresentationService`, and applies container width, horizontal padding and responsive section gaps to the storefront orchestration shell.

## Compatibility

Legacy `home_*` compatibility writes remain intact until Phase 11F. `homepage.presentation` is deliberately separate and does not participate in legacy section content.

## Tests

Targeted configuration test:

```bash
php artisan test tests/Feature/Website/WebsiteHomepagePresentationConfigurationTest.php
```

Recommended Homepage regression gate:

```bash
php artisan test tests/Feature/Website/WebsiteHomepage*
```
