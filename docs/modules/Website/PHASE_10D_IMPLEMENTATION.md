# Phase 10D — Footer Builder Admin

## Status

IMPLEMENTED — WAITING FOR TARGETED TESTS + ADMIN UI REGRESSION

## Scope

Phase 10D exposes the Phase 10C Footer schema/registry through the existing Footer admin route without replacing the existing content editors.

Existing route:

```text
/admin/footer-settings
```

Existing tabs remain:

```text
Thông tin & Brand
Cột Menu Links
Mạng xã hội
```

New tab:

```text
Bố cục Footer
```

## Builder responsibilities

The Builder manages layout/presentation only:

- enable/disable registered Footer component instances;
- reorder component instances inside the same slot using up/down controls;
- move component instances between registry-approved slots;
- save `footer.layout`;
- save `footer.presentation`;
- reset layout and presentation to source-owned defaults.

The Builder does not replace Footer content management.

## Components exposed

The default layout contains every registered component type:

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

`website.chat.chat-widget` remains outside the Footer registry because it is an application overlay.

## Security

All persistent Builder mutations require:

```text
website.footer.manage
```

The persisted layout is reconstructed server-side and stores only:

```text
type
enabled
config
```

`FooterComponentRegistry::resolve()` validates every item/slot before persistence. Blade view paths are never accepted from Livewire state.

## Presentation

Basic controls expose:

- mode;
- container preset;
- vertical spacing preset;
- column gap preset;
- inherit global colors;
- accent;
- border.

Advanced controls expose bounded values already normalized by `FooterPresentationService`:

- container width;
- padding top/bottom;
- column gap;
- section gap;
- logo max height;
- social icon size.

Typography continues to inherit Global Website Design Tokens.

## Drag/drop

Native drag/drop and responsive preview are intentionally deferred to Phase 10E. Phase 10D keeps explicit up/down and slot selection controls as the stable non-drag fallback.

## Test gate

Run only Website tests:

```bash
php artisan test tests/Feature/Website
```

Focused 10D gate:

```bash
php artisan test \
  tests/Feature/Website/WebsiteFooterBuilderConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterSchemaConfigurationTest.php \
  tests/Feature/Website/WebsiteFooterPresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteAdminAuthorizationConfigurationTest.php
```

Then manually test `/admin/footer-settings` → `Bố cục Footer` and verify persistence on the storefront.
