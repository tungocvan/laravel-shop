# Phase 10C Implementation — Footer Schema & Component Registry

## Status

`IMPLEMENTED — WAITING FOR TARGETED WEBSITE TESTS / UI REGRESSION / USER APPROVAL`

## Scope

Phase 10C introduces a code-owned Footer component registry and a validated slot-based schema without adding Admin Builder controls.

## Runtime Flow

```text
Config/footer.php
    -> FooterComponentRegistry
    -> FooterLayoutService
    -> $footerLayout
    -> components/footer/slot.blade.php
    -> registered Footer component views
```

## Security / Integrity Contract

Persisted Footer layout items store only:

```text
type
enabled
config
```

They never store Blade view paths or PHP classes. Renderer paths remain code-owned in `website.footer.components`.

Unknown, disabled or misplaced components are skipped by `FooterLayoutService`.

## Registered Components

```text
brand
contact
menu-columns
app-install
social-links
copyright
legal-links
trust-badges
back-to-top
```

Chat remains outside the Footer registry because it is an application overlay.

## Slots

```text
desktop.top
desktop.main.brand
desktop.main.columns
desktop.main.extra
desktop.bottom.left
desktop.bottom.right
mobile.main
mobile.bottom
overlay
```

## UI Compatibility

The default schema reconstructs the current Footer composition. Desktop/tablet keep the multi-column layout while mobile uses the explicit stacked mobile schema.

Legal links and trust badges remain hardcoded content in this phase; their content cleanup stays Phase 10F.

## Persistence

The storefront may read an optional setting:

```text
footer.layout
```

When absent or invalid, source-owned defaults in `Config/footer.php` are used.

No new migration or database table is introduced.

## Test Gate

Run only Website-scoped tests:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan view:cache
php artisan test tests/Feature/Website
```

Focused Phase 10C gate:

```bash
php artisan test \
  tests/Feature/Website/WebsiteFooterSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterPresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterDecompositionConfigurationTest.php
```

Then manually verify Footer Desktop and Mobile UI before approval.
