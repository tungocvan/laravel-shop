# Phase 12C — Website Layout Presentation

## Goal

Move storefront shell presentation out of hardcoded Blade classes and into validated Website settings without overlapping Header, Homepage, or Footer presentation systems.

## Source of truth

The new setting is:

```text
website.layout
```

It is resolved by `WebsiteLayoutPresentationService` before use.

## Managed presentation

### Body

- Background token: `background` or `surface`

### Main content shell

- Container: `full`, `wide`, `standard`, `compact`
- Background: `transparent`, `background`, `surface`
- Alignment: `left`, `center`
- Desktop padding top / bottom / horizontal
- Mobile padding top / bottom / horizontal

### Scroll

- Smooth anchor scrolling toggle

## Defaults and compatibility

The default presentation preserves the pre-12C storefront shell:

- full-width main content
- 32px top padding
- 32px bottom padding
- 0px horizontal padding
- background token for the page body
- transparent main background
- smooth scrolling disabled

`frontend.blade.php` no longer owns the old `py-8` hardcode. It exposes a stable `website-main-shell` runtime hook, while `presentation-styles.blade.php` renders only values that have already been resolved through the presentation service.

## Responsive contract

Desktop values apply above 767px. Mobile values override main padding at 767px and below.

Header, Homepage, and Footer keep their own responsive/presentation contracts. Website Layout Presentation only controls the outer `main` shell.

## Admin UI

The controls live at:

```text
/admin/website/settings
→ Bố cục Website
→ Website Layout Presentation
```

The section follows `ADMIN_UI_INPUT_STANDARD.md` and includes a dedicated `Khôi phục mặc định` action.

## Theme compatibility

Website Design Themes remain design-token-only during 12C. `website.layout` will be added to the versioned Website Theme payload when the Phase 12 theme schema is upgraded after the PWA/browser appearance contract is completed. This avoids changing theme schema repeatedly during 12C/12D.

## Verification

```bash
php artisan test \
  tests/Feature/Website/WebsiteLayoutPresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteShellControlsConfigurationTest.php \
  tests/Feature/Website/WebsiteFrontendLayoutDecompositionConfigurationTest.php
```

Then verify `/admin/website/settings` and storefront `/` on desktop/mobile widths.
