# Website Module

`Modules/Website` is the public storefront and Website presentation/CMS module. Canonical ownership of users, products, categories, posts and orders remains in their domain modules; Website composes those contracts for storefront delivery.

## Admin entry points

- `/admin/website` — Website dashboard and quick access.
- `/admin/website/settings` — Global Website settings, shell, design, PWA, themes and responsive preview.
- `/admin/homepage-settings` — Homepage registry/layout/content/themes.
- `/admin/header-settings` — Header builder, navigation, actions and themes.
- `/admin/footer-settings` — Footer builder, content, links and themes.

## Architecture status

The controlled Website refactor is complete through Phase 12:

- Phase 9 — Header Architecture: decomposition, design tokens, component registry, builder, responsive preview, navigation/actions and themes.
- Phase 10 — Footer Architecture: decomposition, presentation, registry, builder, responsive preview, content administration and themes.
- Phase 11 — Homepage Architecture: section registry/renderer, admin actions, presentation, themes, seeder/demo data and consolidated admin UX.
- Phase 12 — Website Layout System: frontend shell decomposition, global design tokens, shell controls, layout presentation, PWA/browser appearance, runtime PWA version sync, Website Theme Schema v2 and responsive preview.

## Phase 12 contracts

Global settings are intentionally separated by responsibility:

```text
website.design      Global visual tokens
website.shell       Header/Homepage/Footer visibility + storefront maintenance
website.layout      Main shell container/background/responsive spacing
website.appearance  Browser/PWA appearance and runtime controls
website.features    Floating widget visibility/positions
```

`Modules/Website/resources/views/layouts/frontend.blade.php` remains an orchestration shell. Runtime behavior is delegated to dedicated partials/services rather than hardcoded into the layout.

Website Theme Schema v2 exports/imports only safe visual state:

```text
design + layout + appearance + safe floating-widget positions
```

It intentionally excludes Logo/Favicon, SEO, maintenance state, Header/Homepage/Footer enable state, Analytics and Header Script. Theme v1 remains supported for backward-compatible import/apply.

## PWA separation

Storefront Website PWA uses:

```text
/website-manifest.webmanifest
/website-pwa-version.json
/service-worker.js
```

The legacy/static `/manifest.webmanifest` may belong to a separate Client Portal experience and must not be overwritten by Website Settings.

PWA runtime checks for appearance/version changes when the app loads or returns to foreground and can prompt the user to update. Launcher name/icon refresh timing is still controlled by the browser/OS.

## Admin UI standards

All Website admin forms must follow:

- `ADMIN_UI_INPUT_STANDARD.md` — visible/editable input treatment.
- `ADMIN_OPERATION_VALIDATION_STANDARD.md` — validate/confirm/execute/feedback rules for Save/Update/Export/Import and other operations.

Core requirements:

```text
Editable controls must be visually distinguishable before focus.
Mutating operations validate before execution and return explicit success/failure feedback.
Import treats JSON as untrusted input; Export validates required selection/state first.
```

## Phase documentation

Key current documents:

- `PHASE_9_ANALYSIS.md` and Phase 9 implementation notes — Header.
- `PHASE_10_ANALYSIS.md` and `PHASE_10A_IMPLEMENTATION.md` … `PHASE_10F_IMPLEMENTATION.md` — Footer.
- Phase 11 implementation notes — Homepage registry/admin/presentation/themes.
- `PHASE_12C_IMPLEMENTATION.md` — Website Layout Presentation.
- `PHASE_12D_IMPLEMENTATION.md` — PWA & Browser Appearance.
- `PHASE_12E_IMPLEMENTATION.md` — PWA Runtime Update & Version Sync.
- `WEBSITE_THEME_SCHEMA_V2.md` — safe Website theme export/import contract.
- `PHASE_12F_IMPLEMENTATION.md` — Responsive Preview & final consolidation.
- `PHASE_12_FINAL_AUDIT.md` — merge checklist and final regression command.

## Development rules

Use the repository-standard flow where applicable:

```text
Route -> Controller -> Blade/Livewire -> Service -> Model/Settings -> Database
```

Do not reintroduce hardcoded Header/Footer/Homepage data into the frontend shell. Do not add duplicate Livewire asset injection. Do not add a fake `__lazyLoad()` method to work around Livewire runtime mismatches. Homepage lazy loading remains disabled until its runtime lifecycle is deliberately revalidated.

During implementation, targeted Website tests are preferred. Before merging a completed Website phase, run the full Website feature suite and perform the UI checklist documented in the phase final audit.
