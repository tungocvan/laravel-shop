# Phase 12B — Global Design Tokens Admin

## Goal

Promote Website design tokens from config-only defaults to validated, admin-managed global settings while retaining `Config/design.php` as the safe fallback.

## Source of truth

Runtime resolution is now:

```text
Config/design.php defaults
        +
website.design saved setting
        ↓
WebsiteDesignService
        ↓
sanitized resolved design
        ↓
WebsiteServiceProvider
        ↓
design-tokens.blade.php
```

Raw database values are never passed directly to CSS tokens.

## Admin

`/admin/website/settings` now has a dedicated `Thiết kế toàn site` workspace for:

- Body font family
- Heading font family
- Base font size
- Semantic colors
- Default container preset
- Compact / Standard / Wide container widths
- Semantic border radius

The UI follows `ADMIN_UI_INPUT_STANDARD.md` conventions with visible borders, white input surfaces, focus rings and a sticky save action.

## Safety

`WebsiteDesignService` validates/sanitizes:

- colors as six-digit hex values
- CSS lengths as bounded `px`/`rem`
- font-family strings against CSS-breaking characters
- default container against a fixed enum
- `full` container remains system-controlled at `100%`
- shadows remain config-controlled in 12B

## Compatibility

When `website.design` is absent or invalid, the storefront resolves exactly to `Config/design.php` defaults.

## Deferred

Phase 12B does not yet manage:

- Website shell spacing/layout
- PWA/browser metadata
- toast position/duration
- responsive preview
- Website design themes

Those remain 12C–12F.
