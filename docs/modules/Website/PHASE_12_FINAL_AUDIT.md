# Phase 12 Final Audit — Website Layout System

## Status

Phase 12 is considered implementation-complete after targeted tests and UI validation pass for 12A–12F. This document is the final pre-merge checklist for `refactor/website-layout-phase-12`.

## Final architecture contracts

### Frontend shell

`Modules/Website/resources/views/layouts/frontend.blade.php` is orchestration-only. It may coordinate:

- head/runtime partials,
- Header/Homepage/Footer visibility,
- storefront maintenance presentation,
- main content slot/yield,
- global toast/runtime scripts.

Presentation details must remain in services/partials/settings rather than being re-hardcoded into the shell.

### Global settings boundaries

```text
website.design      semantic design tokens
website.shell       shell visibility + storefront maintenance
website.layout      main container/background/responsive spacing
website.appearance  browser/PWA presentation + runtime toggles
website.features    floating widget visibility/positions
```

### Theme boundary

Website Theme Schema v2 may contain only:

```text
design
layout
appearance
features.chat_position
features.back_to_top_position
```

It must not contain identity, SEO, maintenance state, shell enable state, analytics or arbitrary scripts. Legacy Theme v1 remains accepted and applies only its `design` payload.

### PWA boundary

Storefront Website PWA endpoints:

```text
/website-manifest.webmanifest
/website-pwa-version.json
/service-worker.js
```

Do not overwrite the static `/manifest.webmanifest` if it is used by another application/client portal.

### Livewire runtime boundary

- One Livewire asset source only.
- Do not re-add manual `@livewireStyles` / `@livewireScripts` while auto-injection is enabled.
- Homepage lazy loading remains disabled until Livewire lazy lifecycle compatibility is deliberately revalidated.
- Do not implement a fake public `__lazyLoad()` method.
- Every Livewire component view must keep a single HTML root element.

## Admin UX contracts

- Inputs follow `ADMIN_UI_INPUT_STANDARD.md`.
- Save/Update/Delete/Apply/Import operations validate before execution and use confirmation + result feedback where appropriate.
- Export validates required selection/state and always reports success/failure.
- Import JSON is treated as untrusted input and must fail safely without an exception page.
- Logo/Favicon uploads provide visible current/new-file feedback; ICO preview support is explicitly configured.
- Website responsive preview is schematic/state-driven and must not use an iframe or nested storefront runtime.

## Full Website regression suite

Run before merge:

```bash
php artisan optimize:clear
php artisan test tests/Feature/Website
```

If the full suite is too broad for an intermediate diagnosis, run the Phase 12 critical set:

```bash
php artisan test \
  tests/Feature/Website/WebsitePhase12FinalAuditConfigurationTest.php \
  tests/Feature/Website/WebsiteSettingsResponsivePreviewConfigurationTest.php \
  tests/Feature/Website/WebsiteDesignThemeSchemaV2ConfigurationTest.php \
  tests/Feature/Website/WebsitePwaRuntimeSyncConfigurationTest.php \
  tests/Feature/Website/WebsiteDynamicManifestConfigurationTest.php \
  tests/Feature/Website/WebsiteAppearanceConfigurationTest.php \
  tests/Feature/Website/WebsiteLayoutPresentationConfigurationTest.php \
  tests/Feature/Website/WebsiteShellControlsConfigurationTest.php \
  tests/Feature/Website/WebsiteGlobalDesignTokensConfigurationTest.php \
  tests/Feature/Website/WebsiteFrontendLayoutDecompositionConfigurationTest.php
```

## Final UI checklist

1. `/admin/website` quick access reaches Website Settings, Homepage, Header and Footer.
2. `/admin/website/settings` Save validates and returns modal success/failure feedback.
3. Identity: Logo/Favicon current image and newly selected image/file state are visible; PNG/SVG/ICO favicon flow does not throw.
4. Shell: Header/Homepage/Footer can each be disabled without deleting their configuration.
5. Maintenance: storefront shows configured safe text while Admin remains accessible.
6. Layout: Desktop/Mobile padding, container, alignment and backgrounds match responsive preview and storefront after Save.
7. Design: global colors/typography/container/radius render correctly.
8. Floating widgets: Chat and Back to Top visibility/position work for bottom-left, bottom-right and right-middle without overlap when configured separately.
9. PWA: dynamic manifest exposes current application/theme/background values; runtime version endpoint changes after relevant appearance changes.
10. Installed PWA: returning from background performs an update/version check and can show `Cập nhật ngay` when a new version is detected.
11. Themes: Save/Apply/Update/Rename/Delete/Restore/Export/Import all validate and report results; v2 export contains only safe visual groups.
12. Responsive Preview: Desktop/Mobile switch reflects unsaved state and does not create iframe/nested Livewire/PWA runtime.
13. Storefront `/`: scroll from top to bottom without `__lazyLoad` or multiple-root Livewire exceptions.
14. Product/blog/cart/checkout/account routes still resolve with their canonical route names and are unaffected by Website shell settings except maintenance presentation.

## Merge readiness

Phase 12 is merge-ready when:

```text
full Website tests PASS
+ final UI checklist PASS
+ working tree clean
+ no debug/temp artifacts
```

After merge, future Website work should treat Phase 9 Header, Phase 10 Footer, Phase 11 Homepage and Phase 12 Website Layout System as stable architectural contracts rather than reopening hardcoded legacy behavior.
