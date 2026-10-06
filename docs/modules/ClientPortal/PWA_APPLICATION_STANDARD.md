# ClientPortal — PWA Application Standard

Updated: 2026-10-03



### Shared PWA date control

For ClientPortal application date fields, prefer the shared `<x-pwa-date>` component instead of introducing raw native date markup or a page-local visible-text/native-date overlay. The component is the default iPhone/iOS-safe presentation boundary while the caller continues to own field name, value, required state, validation, submission and business semantics.

A raw `input[type=date]` is an exception, not the default. Use it only when the shared component cannot represent a deliberately different semantic/control, and cover that exception with an explicit contract test. Do not reintroduce JavaScript date-display synchronization merely to force `dd/mm/yyyy` on iOS.

## Purpose

This is the project-wide contract for **every ClientPortal PWA application**, not only Pharma.

It applies when:

- refactoring an existing domain Module into a ClientPortal/PWA application;
- creating a new PWA application for another Module;
- adding or refactoring a capability inside an existing PWA application;
- changing the Application Hub, dashboard, bottom navigation, presentation settings, searchable selectors or date filters.

A Module-specific implementation may add stricter rules, but it must not weaken this contract without an explicitly approved architecture change.

## Canonical architecture

Every PWA application follows:

```text
Source domain Module
    -> owns canonical business data, services and business rules

ClientPortal application adapter
    -> owns web-guard authorization orchestration
    -> owns PWA routes/controllers/presentation
    -> consumes source-domain services
    -> does not copy Admin controllers/business logic

/admin/client-apps
    -> owns presentation/navigation configuration
    -> does not own route names, permissions or business rules
```

Dependency direction remains:

```text
ClientPortal -> source domain module/service
source domain module -X-> ClientPortal
```

## Application Hub contract

Every PWA application must have an **Application Hub** as the canonical entry point for the Module.

The Hub is responsible for:

- application identity and managed hero copy;
- discovery of capabilities available to the authenticated User;
- configurable application-level supporting content;
- configurable bottom-navigation shortcuts;
- entry to an Overview area that can evolve into Module/capability configuration appropriate to the User.

The Hub is not a replacement for domain business logic or server-side authorization.

### Managed Hub hero

The Hub hero must not duplicate editable copy as hard-coded Blade text.

At minimum its presentation contract must support managed defaults/overrides for:

- eyebrow/badge;
- title;
- description.

The values flow through ClientPortal settings/presentation services and are editable from `/admin/client-apps`.

### Supporting/Foundation card

An application may expose supporting/foundation information on the Hub. When it does, the card must be presentation-managed rather than permanent hard-coded implementation copy.

The contract must support:

- show/hide;
- title;
- body/content.

The default may preserve the Module's current copy during migration. Hiding this card is presentation-only and must not disable the application or any capability.

## Bottom navigation contract

Bottom navigation is a **shortcut surface**, not a permission system.

For each eligible Hub/capability item, Admin presentation settings must be able to control:

- whether it appears in the bottom navigation;
- its stable ordering (`sort_order` or equivalent).

Defaults should preserve the application's current navigation when the feature is introduced. A new/refactored application should declare explicit defaults rather than relying on Blade order.

Rules:

1. Bottom visibility must never grant or revoke permission.
2. A User must still satisfy the capability's `web` permission.
3. Hiding a capability from Bottom Navigation must not make its authorized route inaccessible.
4. The Application Hub remains the canonical place to discover permitted capabilities.
5. Bottom Navigation must render only items that are both presentation-enabled for bottom navigation and authorized/available to the current User.
6. Do not hard-code a Module-specific list of bottom items in Blade when the application manifest/settings contract can describe it.
7. Keep navigation ordering separate from business ordering.

## Capability presentation contract

Every user-facing routable capability must declare manifest defaults:

```php
'eyebrow' => '...',
'page_title' => '...',
'page_description' => '...',
```

Runtime flow:

```text
application manifest
    -> ApplicationRegistry
    -> ClientPortalSettingsService
    -> controller/application adapter
    -> Blade
```

Admin presentation may manage copy, visibility/order and navigation presentation where the approved contract allows it. It must not make these source-controlled concerns editable:

- route names or arbitrary URLs;
- permission names;
- controller/service classes;
- source-module dependency;
- business rules;
- domain workflow state.

## Overview contract

Every application should reserve **Overview** as the Module-level PWA landing/configuration area rather than treating it as a permanently hard-coded placeholder.

Overview may progressively surface:

- available capability summary;
- User-visible Module configuration;
- capability configuration/status that the User is authorized to see or change;
- links into capability-specific configuration/workspaces.

Overview must not duplicate Admin-only configuration or bypass domain ownership. Any setting that changes business behavior belongs to the owning domain Module/service and requires its own authorization contract.

## Screen classification and shell

Every PWA route must be classified before implementation:

- `Hub`
- `Browse/Index`
- `Focused Task/Workspace`

The Application Hub owns application discovery/navigation. After entering a capability, Browse/Index and Focused Task/Workspace screens use local context/back/actions and should not mechanically duplicate global application navigation.

Use the established ClientPortal focused-shell sections where applicable:

```blade
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
```

## Searchable entity selectors

For non-Livewire ClientPortal/PWA entity selection, use the PWA-native searchable component:

```blade
<x-pwa-select-search>
```

It is mandatory for PWA selectors that can reasonably grow and require search, especially:

- User / manager / responsible user;
- Product / medicine;
- Customer / partner.

Use the same component for Supplier, Investor and similar searchable entities when applicable.

Do not introduce `<x-select-search>` into new/refactored non-Livewire PWA surfaces merely because it exists elsewhere; that component may depend on Livewire/TomSelect conventions. Reuse `<x-pwa-select-search>` unless the target surface has a documented reason to use another established PWA component.

## Date input — iPhone/iOS-safe contract

Treat native date input as a cross-platform compatibility surface, especially on iPhone/iOS Safari.

Rules:

1. User-facing display is `dd/mm/YYYY` where the product uses Vietnamese date presentation.
2. Submitted/backend value remains canonical `YYYY-MM-DD`.
3. Date controls must be width-contained with mobile-safe layout (`min-w-0`, `w-full` or equivalent) and must not overflow the viewport.
4. Prefer the native OS/browser date picker; do not build a custom calendar without a product requirement.
5. A compact visible trigger may control the native date input. Use `showPicker()` when supported with a safe fallback appropriate to the actual browser behavior.
6. The picker must work repeatedly, not only on the first click.
7. Do not rely on a transparent full-control date-input overlay when it produces inconsistent iOS/desktop click behavior.
8. Date-range controls must use stable responsive layout. For example, `Từ ngày` and `Đến ngày` should remain on one row at the intended tablet/desktop breakpoint when the design requires it.
9. Auto-submit after a date change is preferred for filter UX when no separate Apply step is needed.
10. Real UI acceptance must explicitly verify iPhone/iOS behavior when date controls are changed.

The known regressions to guard against are:

- native date control overflowing the iPhone viewport;
- picker opening once and then failing on later clicks;
- hidden/overlay input preventing the picker from opening;
- desktop/tablet date range unexpectedly stacking because a generated CSS class is unavailable.

## Pharma focused-shell navigation contract

For the current Pharma ClientPortal application:

- the Pharma Hub/dashboard owns the application shell and may render the application header and mobile bottom navigation;
- capability browse/index/detail/task/workflow screens use the focused shell and declare both `hide-application-header` and `hide-mobile-navigation`;
- focused screens provide local navigation to the relevant parent capability or Pharma Hub instead of relying on the global shell;
- do not reintroduce the application shell on an individual capability merely to expose a back action;
- shell consistency is presentation/navigation only and must not change capability authorization or Pharma domain behavior.

## Mobile-first list/filter rules

Unless a capability has a documented exception:

- mobile list UX should use native-like progressive loading such as `Xem thêm` rather than desktop pagination;
- searchable filters should have a clear/reset path;
- overlays/dropdowns must not be clipped by cards/containers;
- touch actions need appropriate hit targets and pressed-state feedback;
- tablet/desktop layout may become denser without breaking the mobile-first contract.

## Native touch interaction contract

ClientPortal PWA controls must feel like touch controls rather than plain web text links.

Apply native pressed-state feedback to user-triggered controls such as:

- primary/secondary action buttons;
- links visually presented as buttons;
- tappable action cards and floating action buttons;
- action-footer controls;
- mobile navigation controls.

Use the shared `<x-native-touch>` primitive for new/refactored standalone controls when its markup contract fits. Existing complex controls may implement the same contract directly.

Baseline interaction contract:

- the whole visual control is the touch target, not only its text/icon;
- provide immediate touch-down feedback, normally `active:scale-[0.985]`;
- use a short transform transition (about 100ms) rather than a long animation;
- use `touch-action: manipulation` and suppress WebKit tap highlight where appropriate;
- respect `prefers-reduced-motion` with `motion-reduce:transform-none` and no required motion;
- preserve keyboard `focus-visible` behavior and semantic button/link markup;
- disabled or `aria-disabled` controls must not animate as actionable controls;
- Neumorphism controls may add an immediate inset/pressed shadow in addition to the baseline scale feedback.

Bottom Navigation may use a slightly stronger scale response where needed for a native dock feel, but it must follow the same accessibility and reduced-motion rules.

Do not add a global CSS rule that blindly transforms every Website/Admin `button`. This contract is scoped to ClientPortal PWA and should be adopted incrementally when a screen is created or refactored.

## Admin configuration contract

`/admin/client-apps` is the canonical Admin surface for ClientPortal application presentation.

For every application, the target presentation model should support these groups as applicable:

```text
Application
├── Hub hero
│   ├── eyebrow
│   ├── title
│   └── description
├── Supporting/Foundation content
│   ├── visible
│   ├── title
│   └── body
├── Bottom navigation
│   ├── visible per eligible item
│   └── sort order
└── Capabilities
    ├── presentation copy
    ├── presentation visibility/order where approved
    └── source-controlled route + permission contract
```

Admin UI must follow `.codex/standards/ADMIN_UI_STANDARD.md`.

## New application checklist

A new Module PWA application is incomplete until all applicable items are satisfied:

```text
[ ] Source domain Module ownership is identified.
[ ] ClientPortal adapter/manifest exists.
[ ] Application access and capability permissions use the web guard.
[ ] Application Hub exists.
[ ] Hub hero copy is presentation-managed.
[ ] Supporting/Foundation content is presentation-managed if rendered.
[ ] Bottom navigation has explicit visibility/order defaults.
[ ] Bottom visibility is independent from authorization.
[ ] Every routable capability has managed eyebrow/title/description.
[ ] Overview is reserved for Module/capability summary/configuration evolution.
[ ] Routes are classified Hub / Browse/Index / Focused Task.
[ ] User/Product/Customer searchable selectors use x-pwa-select-search.
[ ] Date controls follow the iPhone/iOS-safe contract.
[ ] Mobile and desktop/tablet responsive behavior is tested.
[ ] Interactive PWA buttons/actions follow the native touch interaction contract.
[ ] Relevant automated contracts cover manifest/settings/permissions.
[ ] Real rendered UI acceptance is PASS before merge.
[ ] PWA file handoff rules are applied when downloads/exports exist.
```

## Refactor checklist

When refactoring an existing PWA Module, audit before changing code:

```text
[ ] hard-coded Hub/feature copy
[ ] hard-coded bottom-navigation items/order
[ ] presentation visibility incorrectly coupled to permission
[ ] legacy x-select-search/plain selects for searchable PWA entities
[ ] iPhone-unsafe date inputs
[ ] duplicated Admin business logic in ClientPortal
[ ] capability pages that incorrectly retain global shell navigation
[ ] desktop pagination reintroduced into mobile-first lists
[ ] missing native touch feedback on button-like PWA controls
[ ] missing presentation/settings contract tests
```

Refactor incrementally. Preserve working business rules and authorization while moving presentation/navigation to the canonical ClientPortal contract.

## Verification gate

For any implementation based on this standard:

1. focused automated tests;
2. ClientPortal/impacted regression appropriate to the change;
3. actual rendered Mobile/PWA acceptance;
4. actual rendered Desktop/Tablet acceptance;
5. iPhone/iOS acceptance for changed date/file-handoff behavior;
6. explicit User UI PASS before merge.

Tests passing alone are not sufficient for UI work.
