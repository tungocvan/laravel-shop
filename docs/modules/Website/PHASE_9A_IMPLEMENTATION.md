# Phase 9A Implementation — Header Decomposition

## Status

`IMPLEMENTED — WAITING FOR LOCAL/UI REGRESSION TEST AND USER APPROVAL`

## Scope

Phase 9A implements only the decomposition approved in `PHASE_9_ANALYSIS.md`.

This slice does **not** introduce:

- Header Builder;
- drag/drop;
- component registry/schema;
- global design tokens;
- width/height/color administration;
- new persistence/database tables;
- intentional visual redesign.

The existing header appearance and behavior remain the regression baseline.

## Changes

The previous monolithic view:

`Modules/Website/resources/views/partials/header.blade.php`

is now an orchestration shell that composes focused views under:

`Modules/Website/resources/views/components/header/`

Current decomposition:

```text
header/
├── topbar.blade.php
├── brand.blade.php
├── search.blade.php
├── navigation.blade.php
├── actions.blade.php
├── account.blade.php
└── mobile-menu.blade.php
```

Responsibilities:

- `topbar`: hotline, email, help and order-tracking links.
- `brand`: mobile menu toggle, logo/fallback initial and brand label.
- `search`: desktop and mobile search presentation through a local `mode` argument.
- `navigation`: primary desktop menu and child dropdowns.
- `actions`: mobile search toggle, navigation composition, wishlist/cart actions and account composition.
- `account`: authenticated account dropdown and guest login/register actions.
- `mobile-menu`: mobile drawer, account state, mobile/main-menu fallback and contact footer.

## Existing Contracts Preserved

The view data contract remains unchanged:

```text
$headerSettings
$mainMenu
$mobileMenu
$accountMenu
```

`WebsiteServiceProvider` continues to prepare these values and `HeaderMenuService` continues to own menu tree retrieval/cache.

The following behavior is intentionally preserved:

- existing Tailwind classes and responsive breakpoints;
- existing Alpine state names and interactions;
- existing Livewire wishlist/cart aliases;
- existing route fallbacks;
- existing logo URL handling;
- existing auth/guest branches;
- existing desktop menu dropdown behavior;
- existing mobile menu fallback to primary menu;
- existing header dimensions/colors.

## Test Gate

Run after pulling the Phase 9A branch:

```bash
git fetch origin
git switch refactor/website-header-phase-9a
git pull origin refactor/website-header-phase-9a
php artisan optimize:clear
```

Recommended Laravel checks:

```bash
php artisan view:clear
php artisan view:cache
php artisan test
```

If the full repository test suite contains known unrelated failures, record them separately and at minimum run the Website-focused tests available in the repository.

Manual UI regression checklist:

```text
[ ] guest desktop header unchanged
[ ] authenticated desktop header unchanged
[ ] logo/fallback brand renders
[ ] topbar hotline/email/help/tracking links render
[ ] desktop product search works
[ ] desktop navigation/dropdown works
[ ] wishlist icon renders
[ ] cart icon renders when component is available
[ ] account dropdown works
[ ] login/register state works
[ ] mobile search toggle works
[ ] mobile drawer opens/closes
[ ] mobile menu renders configured mobile menu
[ ] mobile menu falls back to primary menu when required
[ ] logout works from desktop/mobile
[ ] no horizontal overflow/regression on desktop/tablet/mobile
```

## Approval Gate

Do not start Phase 9B until Phase 9A passes the local/UI regression gate and receives explicit user approval.
