# Phase 9 Analysis — Header Architecture & Builder

## Purpose

This document defines the approved architecture for refactoring the Website header before application source code is changed.

The goal is to make the header easier to maintain, easier to administer, responsive by default, and configurable without turning the Website module into an unrestricted page builder.

Status: `ANALYZED / APPROVED — READY FOR PHASE 9A IMPLEMENTATION`.

Approval: user approved this specification on 2026-08-22. Source implementation must follow the staged sequence below and preserve current storefront behavior unless a later UI change is explicitly approved.

## 1. Current Header Assessment

Current file:

`Modules/Website/resources/views/partials/header.blade.php`

The current header works, but one Blade file currently owns too many responsibilities:

- top bar;
- brand/logo rendering;
- desktop search;
- desktop navigation and dropdowns;
- wishlist/cart actions;
- authentication/account dropdown;
- mobile search;
- mobile drawer/menu;
- contact fallbacks;
- route fallbacks;
- component/module availability checks;
- visual classes and responsive dimensions.

This creates a maintenance problem even when the runtime result is correct. Future header changes risk coupling unrelated concerns and increasing hardcoded presentation logic.

## 2. Design Principles

The refactor should follow these rules:

1. Blade renders prepared data; it should not become the source of business/configuration logic.
2. Header content, header layout and global theme/design settings are separate concerns.
3. Global typography belongs to Website Theme/Design Settings, not to the Header Builder.
4. Header dimensions and colors should have optimized defaults and semantic presets.
5. Responsive behavior should default to automatic values and remain safe when administrators customize settings.
6. Drag-and-drop should operate on controlled slots/components, not arbitrary absolute positioning.
7. Existing menus remain managed by the Menu Manager; the Header Builder only decides where menu components appear.
8. Header components must be registered through a controlled registry rather than arbitrary Blade view names stored by administrators.
9. The public header must continue working when optional components/modules are unavailable.
10. Existing storefront behavior is the regression baseline unless a UI change is explicitly approved.

## 3. Proposed Component Structure

Target view structure:

```text
Modules/Website/resources/views/
├── partials/
│   └── header.blade.php
└── components/
    └── header/
        ├── topbar.blade.php
        ├── brand.blade.php
        ├── search.blade.php
        ├── navigation.blade.php
        ├── actions.blade.php
        ├── account.blade.php
        └── mobile-menu.blade.php
```

The exact number of files may change during implementation, but components should be split by stable UI responsibility rather than by tiny markup fragments.

`header.blade.php` should become an orchestration shell instead of containing all header behavior directly.

## 4. Header Component Registry

The system should define a controlled registry of components that may be placed in the header.

Initial registry candidates:

```text
brand
search
navigation
wishlist
cart
account
hotline
email
custom-link
order-tracking
menu-toggle
search-toggle
```

Possible future registry components:

```text
language-switcher
currency-switcher
compare-products
social-links
branch-selector
custom-button
```

Each registered component should define at least:

- stable type/key;
- admin label;
- renderer/view;
- allowed regions/slots;
- desktop/mobile availability;
- optional availability callback when a module/class is required;
- optional configuration schema.

Administrators must not be able to persist arbitrary Blade paths or executable classes as component types.

## 5. Layout Model

The Header Builder should use controlled rows and slots.

Recommended model:

```text
Header
├── Topbar
│   ├── Left
│   └── Right
└── Main
    ├── Left
    ├── Center
    └── Right
```

Mobile may use a separate but compatible layout definition:

```text
Mobile Main
├── Left
├── Center
└── Right

Mobile Drawer
└── ordered components / menu/account/contact areas
```

Drag-and-drop should support:

- reorder within a slot;
- move compatible components between slots;
- enable/disable;
- desktop/tablet/mobile visibility where appropriate.

It should not support free-positioned `x/y` coordinates or arbitrary pixel placement. Absolute-position page-builder behavior would make responsive guarantees difficult to maintain.

## 6. Header Layout Configuration

A persisted layout may use validated JSON/configuration similar to:

```json
{
  "desktop": {
    "topbar": {
      "enabled": true,
      "left": [
        {"type": "hotline"},
        {"type": "email"}
      ],
      "right": [
        {"type": "custom-link", "label": "Trợ giúp", "url": "/help"},
        {"type": "order-tracking"}
      ]
    },
    "main": {
      "left": [
        {"type": "brand"}
      ],
      "center": [
        {"type": "search"}
      ],
      "right": [
        {"type": "navigation"},
        {"type": "wishlist"},
        {"type": "cart"},
        {"type": "account"}
      ]
    }
  }
}
```

The persisted structure must be validated against the component registry before save and before render.

## 7. Content vs Layout vs Global Design

The administration model should explicitly separate three responsibilities.

### 7.1 Header / Website Content Settings

Examples:

- logo;
- brand name;
- hotline;
- email;
- search placeholder;
- optional labels/links specific to header content.

### 7.2 Menu Manager

Owns:

- main menu;
- mobile menu;
- account menu;
- nested menu items;
- menu item ordering;
- internal/external links;
- targets and enabled state.

### 7.3 Header Builder

Owns:

- which registered components are visible;
- component order;
- slot placement;
- responsive visibility;
- header-specific dimensions/presentation overrides.

The Header Builder must not duplicate Menu Manager CRUD.

## 8. Global Website Design Settings

Typography should be global and inherited by Header, Footer and page components.

Recommended global design tokens:

```text
Typography
├── body font family
├── heading font family
├── mono font family (optional)
├── base font size
├── font-size scale
└── line-height scale

Colors
├── primary
├── secondary
├── background
├── surface
├── text
├── muted
├── border
├── success
├── warning
└── danger

Layout
├── default container width
├── spacing scale
├── radius scale
└── shadow scale
```

Header should inherit global typography by default.

Header v1 should not provide an independent font-family override. Header text sizes should use semantic sizes from the global scale rather than storing arbitrary pixel values per component.

Example semantic mapping:

```text
Topbar      -> xs
Navigation  -> sm/base
Account     -> sm
Brand       -> xl/2xl
```

## 9. Header Dimensions

Header dimensions should support `AUTO -> PRESET -> CUSTOM` in that priority order.

### 9.1 Recommended Defaults

Initial recommended defaults:

```text
Content container
- Standard: max-width approximately 1280px

Main header height
- Desktop: 80px
- Tablet: 72px
- Mobile: 64px

Topbar
- Desktop height: 32px
- Mobile: hidden by default

Logo max height
- Desktop: 48px
- Mobile: 40px

Search
- Desktop max width: approximately 560px
- Input height: approximately 42px

Common action icon
- approximately 24px

Account avatar
- approximately 32px
```

These are initial design-system defaults, not a requirement to hardcode all values directly into Blade.

### 9.2 Container Width Presets

Recommended presets:

```text
compact  ~1024px
standard ~1280px (default)
wide     ~1440px
full     100%
```

The header background should normally span the viewport while the header content uses the selected container width.

### 9.3 Header Height Presets

Recommended presets:

```text
compact
normal (default)
comfortable
custom
```

Changing a size preset should automatically adjust related spacing, logo maximum height, icon alignment and control heights where practical.

### 9.4 Custom Safety Bounds

Advanced custom values should be bounded, for example:

```text
Header height: 52–120px
Logo max height: 24–72px
Container max width: 960–1920px
```

Exact validation limits may be adjusted after visual implementation/testing.

## 10. Header Colors

Header should inherit the global Website theme unless an explicit header override is enabled.

Recommended header color tokens:

```text
background
foreground
muted
accent
border
topbar_background
topbar_foreground
```

Default direction:

```text
background            #ffffff
foreground            #111827
muted                  #6b7280
accent                 #2563eb
border                 #e5e7eb
topbar_background     #111827
topbar_foreground     #ffffff
```

Administrators should choose semantic colors through controlled inputs/color pickers. Individual components should not each invent independent color systems unless a future approved requirement needs it.

## 11. Basic and Advanced Administration Modes

Recommended `Basic` options:

```text
layout preset
header size
content width
background / use global
text color / use global
accent / use global
sticky
shadow
component visibility/order
```

Recommended `Advanced` options:

```text
exact container max width
desktop/tablet/mobile height
topbar height
logo max height
search max width
spacing density
border options
selected responsive visibility overrides
```

A `Reset to recommended defaults` action should always be available.

## 12. Responsive Rules

Default mode should be automatic.

Administrators should not need to enter desktop/tablet/mobile values merely to get a balanced header.

Responsive behavior should satisfy:

- no horizontal overflow at supported widths;
- logo preserves aspect ratio;
- search collapses or becomes a search toggle on smaller screens;
- desktop navigation does not force overflow;
- mobile drawer remains independent of desktop dropdown behavior;
- actions remain vertically centered as header height changes;
- custom values are validated against safe ranges.

The Header Builder admin screen should eventually provide Desktop / Tablet / Mobile previews.

## 13. Service / ViewModel Boundary

Target rendering flow:

```text
Website settings + Theme tokens + Menu data + Header layout
                         ↓
               Header configuration service
                         ↓
                  Header ViewModel/DTO
                         ↓
                  header.blade.php
                         ↓
             registered header components
```

Blade should not own:

- settings persistence;
- raw layout normalization;
- arbitrary route discovery logic where it can be prepared upstream;
- menu database queries;
- component registry validation;
- complex asset URL normalization.

The exact class names should be selected after inspecting current `WebsiteSettingsService`, navigation/header services and view composers to avoid duplicating existing abstractions.

## 14. Persistence Decision

Do not add a new database table automatically just because the builder uses JSON.

Before implementation, inspect the current settings/header persistence and choose between:

1. existing validated Website settings storage for a single active header layout; or
2. a dedicated header-layout model/table only if multiple layouts, draft/publish, history, reusable presets or future multi-site requirements justify it.

For v1, the simplest storage compatible with existing Website settings architecture is preferred.

## 15. Suggested Implementation Sequence

### Phase 9A — Header decomposition

- preserve current visual behavior;
- introduce focused Blade header components;
- move normalization/fallback logic out of the monolithic Blade where practical;
- add focused render/regression tests.

### Phase 9B — Global design tokens

- formalize global typography;
- formalize theme color/layout tokens;
- define recommended defaults;
- preserve existing site appearance through defaults.

### Phase 9C — Header schema and registry

- introduce component registry;
- introduce validated slot/layout schema;
- renderer reads the schema with safe fallback defaults;
- no drag/drop UI required yet.

### Phase 9D — Header Builder admin

- component enable/disable;
- reorder;
- slot movement;
- Basic/Advanced settings;
- width/height/color configuration;
- reset defaults;
- authorization and validation.

### Phase 9E — Drag/drop and responsive preview

- drag/drop between compatible slots;
- desktop/tablet/mobile preview;
- explicit save/publish behavior if required;
- accessibility and responsive validation.

Implementation must stop after each slice for testing/approval if the repository workflow requires phased confirmation.

## 16. Test Gate

Before Phase 9 is approved:

```text
Existing storefront header regression PASS
Desktop layout PASS
Tablet layout PASS
Mobile layout PASS
Menu hierarchy PASS
Account auth/guest states PASS
Cart/wishlist optional states PASS
Header settings validation PASS
Header layout schema validation PASS
Authorization PASS
Reset defaults PASS
No arbitrary view/class injection PASS
No horizontal overflow PASS
Accessibility keyboard/focus review PASS
Cache invalidation PASS
```

## 17. Decisions Locked by This Analysis

The following decisions are approved and locked for Phase 9 implementation:

- refactor the monolithic header into stable components;
- implement a controlled component registry;
- use slot-based layout rather than free-position coordinates;
- support drag/drop only inside the controlled builder model;
- provide optimized default dimensions;
- allow width, height and header color configuration with safe presets/ranges;
- global font family and typography scale live in Website Theme/Design Settings;
- Header inherits global typography by default;
- prefer `AUTO`, then preset, then custom dimension values;
- separate Header content settings, Menu Manager and Header Builder responsibilities;
- provide Basic and Advanced admin settings;
- add Desktop/Tablet/Mobile preview in a later builder slice;
- implementation begins with Phase 9A and must preserve current storefront behavior before builder features are introduced.
