# Phase 9B Implementation — Global Design Tokens

## Status

`IMPLEMENTED — WAITING FOR TARGETED WEBSITE TESTS, UI REGRESSION AND USER APPROVAL`

## Scope

Phase 9B formalizes global Website design tokens approved in `PHASE_9_ANALYSIS.md`.

This slice does **not** introduce:

- Header Builder;
- drag/drop;
- header component registry/schema;
- admin persistence/editor for design tokens;
- new database tables;
- intentional visual redesign.

The current storefront appearance remains the regression baseline.

## Source of Truth

Global default tokens now live in:

`Modules/Website/Config/design.php`

Groups:

```text
Typography
- body font family
- heading font family
- mono font family
- base font size
- semantic font-size scale
- line-height scale

Colors
- primary
- secondary
- background
- surface
- text
- muted
- border
- success
- warning
- danger

Layout
- compact / standard / wide / full container widths
- default container preset
- radius scale
- shadow scale
```

## Default Compatibility

Defaults intentionally match the existing Website presentation as closely as practical:

```text
Base font size        16px
Body/heading font     system sans stack
Primary               #2563eb
Background            #f9fafb (gray-50)
Surface               #ffffff
Text                   #111827 (gray-900)
Muted                  #6b7280
Border                 #e5e7eb
Standard container     1280px
```

No custom webfont dependency is introduced in Phase 9B.

## Rendering Flow

```text
Modules/Website/Config/design.php
        ↓
WebsiteServiceProvider
        ↓
$websiteDesign
        ↓
partials/design-tokens.blade.php
        ↓
CSS custom properties (:root)
        ↓
Tailwind semantic theme aliases
        ↓
Website frontend layout/components
```

`WebsiteServiceProvider` loads module config through `mergeConfigFrom()` and exposes the design array to frontend views.

## Semantic Tailwind Tokens

`resources/css/tailwind.css` maps Website variables into semantic utilities such as:

```text
bg-website-background
bg-website-surface
text-website-text
text-website-muted
text-website-primary
border-website-border
font-website-body
font-website-heading
font-website-mono
```

The frontend body now uses semantic Website background/text/body-font utilities rather than direct gray/font-sans classes.

Existing component-specific Tailwind classes are intentionally not mass-rewritten in this slice. Later approved slices may progressively consume semantic tokens where useful.

## Typography Ownership

Typography is global Website design configuration.

Header does not receive its own font-family override in Phase 9B. Header, Footer and content inherit the global body/heading tokens.

Header-specific dimensions/colors remain Phase 9D builder responsibilities, while component layout/schema remains Phase 9C.

## Persistence Decision

Phase 9B uses validated code defaults only.

Admin-managed persistence is intentionally deferred. This avoids creating duplicate settings contracts before the Header schema/builder design is implemented.

A later Theme/Design Settings UI may map approved settings onto these tokens through `SettingsService`, but the token names and fallback defaults established here should remain stable.

## Targeted Test Gate

Do not run the full repository suite for normal Phase 9B validation.

Run Website tests only:

```bash
php artisan optimize:clear
php artisan view:clear
php artisan view:cache
php artisan test tests/Feature/Website
```

For a faster Phase 9B-focused check:

```bash
php artisan test \
  tests/Feature/Website/WebsiteDesignTokensConfigurationTest.php \
  tests/Feature/Website/WebsiteSettingsConfigurationTest.php \
  tests/Feature/Website/WebsiteProductionOptimizationTest.php
```

Only add another module's tests when Phase 9B changes a direct contract owned by that module. No such cross-module contract is intentionally changed in this slice.

## Manual UI Regression

```text
[ ] homepage typography visually unchanged
[ ] header typography/layout visually unchanged
[ ] footer typography visually unchanged
[ ] background/text colors visually unchanged
[ ] desktop layout unchanged
[ ] tablet layout unchanged
[ ] mobile layout unchanged
[ ] no flash of unstyled design-token values
[ ] no horizontal overflow introduced
```

## Approval Gate

Do not start Phase 9C until targeted Website tests pass, UI regression is confirmed, and the user explicitly approves Phase 9B.
