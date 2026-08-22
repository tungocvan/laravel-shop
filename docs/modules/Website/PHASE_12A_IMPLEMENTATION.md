# Phase 12A — Website Layout Decomposition

## Status

Implemented on `refactor/website-layout-phase-12`.

## Goal

Reduce `Modules/Website/resources/views/layouts/frontend.blade.php` to an orchestration shell without changing storefront behavior or making runtime/security contracts admin-configurable prematurely.

## Decomposition

The frontend shell now composes:

- `Website::partials.layout.head-meta`
- `Website::partials.layout.runtime-head`
- `Website::partials.header`
- main content slot/yield
- `Website::partials.footer`
- `Website::partials.layout.global-toast`
- `Website::partials.layout.runtime-scripts`

## Preserved runtime contracts

Phase 12A intentionally preserves the existing values and behavior for:

- charset and viewport
- SEO/OpenGraph metadata
- favicon resolution
- trusted header/analytics scripts
- realtime bootstrap
- design token include
- Vite assets
- Livewire styles/scripts
- skip-link and `main-content` accessibility target
- global toast behavior
- service worker registration

The hardcoded visual/PWA values are only relocated in 12A. They become configurable in later Phase 12 steps after validation/sanitization services exist.

## Non-goals

12A does not yet:

- change `/admin/website/settings`
- change design token values
- change `py-8` on the main shell
- make PWA metadata configurable
- change toast position/duration
- add responsive preview
- add Website design themes

## Verification

Run only the Website layout-related test:

```bash
php artisan test tests/Feature/Website/WebsiteFrontendLayoutDecompositionConfigurationTest.php
```

Then compile Blade views:

```bash
php artisan view:clear
php artisan view:cache
```

UI regression: open the storefront and verify header, page content, footer, notifications and browser/PWA metadata behave as before.
