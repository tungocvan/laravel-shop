# Phase 12D — PWA & Browser Appearance

## Goal

Move browser/PWA visual metadata out of hardcoded frontend Blade values and into validated Website settings without exposing system asset paths or runtime internals.

## Setting

`website.appearance`

```text
application_name
apple_title
theme_color
background_color
apple_status_bar_style
manifest_enabled
service_worker_enabled
```

## Runtime

`WebsiteAppearanceService` resolves and sanitizes saved values. `WebsiteServiceProvider` passes the resolved payload to the frontend shell.

`head-meta.blade.php` now reads resolved application name, Apple title, theme color and status-bar style. Manifest rendering is conditional but its path remains system-controlled at `/manifest.webmanifest`.

`runtime-scripts.blade.php` conditionally registers the service worker, but its path remains system-controlled at `/service-worker.js`.

## Admin UI

The controls live under `/admin/website/settings` → `Bố cục Website`, after Website Layout Presentation, so shell/layout/browser appearance remain grouped together.

The UI follows `ADMIN_UI_INPUT_STANDARD.md` and provides a browser appearance preview plus a dedicated reset-to-default action.

## Safety

- colors accept six-digit hex only
- application names are plain text and length-limited
- Apple status bar style is enum-controlled
- manifest and service-worker URLs cannot be edited by Admin
- disabling manifest/service worker only controls storefront rendering/registration; it does not delete system files

## Theme boundary

Phase 12D does not yet change Website Design Theme schema v1. The next theme-schema step can safely promote visual settings to a versioned payload containing `design`, `layout`, `appearance`, and safe floating-widget positions while continuing to exclude branding, SEO, maintenance state, analytics, and scripts.
