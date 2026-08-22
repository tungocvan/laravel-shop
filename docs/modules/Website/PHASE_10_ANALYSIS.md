# Phase 10 Analysis — Footer Architecture & Builder

## Status

`ANALYZED — WAITING FOR USER APPROVAL BEFORE IMPLEMENTATION`

## Goal

Refactor the Website Footer using the same controlled architecture that was approved and completed for Header in Phase 9.

The target is a Footer that is:

- easy to change without hardcoding layout structure in Blade;
- easy to administer without editing source code;
- composed from stable, reusable components;
- driven by a validated schema and registry;
- configurable with safe presentation presets and bounded advanced values;
- compatible with the existing Website global design tokens from Phase 9B;
- manageable through a Footer Builder with drag/drop and responsive preview;
- backward compatible with existing Footer info, columns/links and social-link data.

Implementation must be incremental and preserve current frontend behavior before introducing layout changes.

---

## Current State

`Modules/Website/resources/views/partials/footer.blade.php` currently combines many responsibilities in one Blade file:

- decorative top accent;
- brand/logo;
- brand description;
- address/email/phone;
- dynamic Footer menu columns;
- PWA installer;
- social links;
- legal/privacy links;
- payment/trust logos;
- copyright;
- back-to-top button;
- chat widget.

The current Footer Admin already has stable management surfaces for:

- Footer information / Brand;
- Footer menu columns and links;
- social links.

These existing content managers must remain the canonical editors for their data. The new Footer Builder should manage layout/composition, not duplicate content management.

---

## Main Problems

### 1. Monolithic Blade

The Footer partial performs rendering, layout composition, fallback content and integration wiring in one file. This makes changes risky and encourages more hardcoded markup.

### 2. Layout is hardcoded

The current desktop layout is effectively fixed as:

```text
Brand information
+ dynamic menu columns
+ app/social block
+ bottom legal/payment row
```

An administrator cannot safely reorder or disable these regions without source-code changes.

### 3. Presentation values are embedded in Tailwind classes

Examples include fixed dark colors, paddings, gaps, column spans and typography classes. This prevents the Footer from consistently inheriting the Website design system.

### 4. Some content remains hardcoded

Examples include:

- "Tải Ứng Dụng";
- app-install description;
- Privacy Policy / Terms of Service / Cookie Settings;
- payment/trust logo URLs;
- several visual defaults.

These should become settings or managed component configuration where appropriate.

### 5. External UI dependencies exist in Footer Admin

The current Footer admin page includes CDN dependencies for Font Awesome, SweetAlert and SortableJS. Phase 10 should avoid adding new external dependencies. Existing dependencies should only be removed when replacement behavior is verified.

---

## Design Principles

Footer follows the same hierarchy established by Header:

```text
GLOBAL WEBSITE DESIGN SYSTEM
        ↓
Footer Presentation Tokens
        ↓
Footer Schema
        ↓
Footer Component Registry
        ↓
Footer Layout Service
        ↓
Footer Slot Renderer
        ↓
Footer Components
```

The schema must never persist arbitrary Blade paths or PHP classes.

Only source-controlled registry definitions may map a component `type` to a renderer.

---

## Proposed Component Decomposition

Target view structure:

```text
Modules/Website/resources/views/components/footer/
├── brand.blade.php
├── contact.blade.php
├── menu-columns.blade.php
├── app-install.blade.php
├── social-links.blade.php
├── legal-links.blade.php
├── trust-badges.blade.php
├── copyright.blade.php
├── back-to-top.blade.php
└── slot.blade.php
```

`partials/footer.blade.php` should become an orchestration shell only.

The Chat widget should remain outside the visual Footer layout registry unless a later requirement explicitly makes it layout-configurable. It is an application overlay, not Footer content.

---

## Proposed Footer Slots

Unlike Header, Footer benefits from row-based responsive layout rather than fixed left/center/right slots.

Recommended schema:

```text
desktop.top

desktop.main.brand
desktop.main.columns
desktop.main.extra

desktop.bottom.left
desktop.bottom.right

mobile.main
mobile.bottom
```

The Builder may visually present these as:

```text
TOP ACCENT / OPTIONAL REGION

MAIN FOOTER
┌──────────────┬──────────────────────────┬──────────────┐
│ Brand        │ Menu Columns             │ Extra        │
│ Contact      │                          │ App / Social │
└──────────────┴──────────────────────────┴──────────────┘

BOTTOM FOOTER
┌──────────────────────────┬─────────────────────────────┐
│ Copyright / Legal        │ Trust / Payment badges      │
└──────────────────────────┴─────────────────────────────┘
```

On mobile, components should stack automatically according to the mobile schema instead of exposing pixel coordinates.

Free-position `x/y` layout is explicitly out of scope.

---

## Proposed Registry

Example registry concepts:

```text
brand
contact
menu-columns
app-install
social-links
legal-links
trust-badges
copyright
```

Each registry entry should define:

```text
label
view
allowed_slots
default_config
availability (optional)
```

The registry remains code-owned.

Admin persistence stores only:

```text
type
enabled
config
```

---

## Content vs Layout Separation

The Footer Builder must not replace existing content managers.

### Footer Information / Brand

Continue managing:

```text
brand name
logo
description
address
email
phone
copyright
```

### Footer Columns

Continue managing:

```text
column title
links
link order
enabled state
new-tab behavior
```

### Social Links

Continue managing:

```text
network/name
URL
icon
enabled state
order
```

### Footer Builder

Manage only:

```text
which components appear
which slot they occupy
component order
enabled/disabled state
presentation settings
responsive composition
```

---

## Global Typography

Footer must inherit the Phase 9B global typography system.

Do not introduce Footer-specific `font-family` configuration in v1.

Semantic text-size choices may be allowed later, but must map to global typography scale tokens instead of storing arbitrary pixel values per component.

---

## Footer Presentation Settings

Footer should support safe presentation configuration similar to Header.

### Basic Mode

Recommended controls:

```text
Container width
- compact ~1024px
- standard ~1280px (default)
- wide ~1440px
- full

Vertical spacing
- compact
- normal (default)
- comfortable

Column gap
- compact
- normal (default)
- wide

Top accent
- enabled by default

Border
- enabled by default

Color inheritance
- inherit global Website theme by default
```

### Advanced Mode

Bounded values may include:

```text
container max width : 960–1920px
main padding top    : 32–120px
main padding bottom : 24–96px
column gap          : 12–64px
row gap             : 12–64px
logo max height     : 24–72px
social icon size    : 28–56px
bottom spacing      : 16–64px
```

All custom numeric values must be normalized/clamped before persistence and rendering.

---

## Footer Colors

Default behavior:

```text
Use Global Website Theme: ON
```

Footer may expose controlled overrides for:

```text
background
foreground
muted text
heading text
accent
border
bottom background
```

No arbitrary Tailwind classes should be persisted.

Default values should preserve the current dark Footer appearance unless the global design system explicitly overrides it.

---

## Trust / Payment Badges

The currently hardcoded external badge/logo URLs should not remain embedded directly in Footer Blade long term.

Phase 10 should move these into managed configuration or a safe component data source.

Recommended model for v1:

```text
label
image_url or managed media path
link_url (optional)
enabled
sort_order
```

Do not build a new table unless existing Website settings/media infrastructure is insufficient. First prefer the current settings architecture for a small fixed list.

---

## Legal Links

Privacy Policy, Terms of Service and Cookie Settings should become configurable data.

Recommended setting shape:

```text
footer.legal_links
```

Each item:

```text
label
url
enabled
sort_order
```

The renderer must not hardcode route/view paths.

---

## Persistence Strategy

Reuse the existing settings architecture wherever practical.

Recommended keys:

```text
footer.layout
footer.presentation
footer.legal_links
footer.trust_badges
```

Existing Footer columns/social tables remain unchanged.

Do not create a dedicated `website_footer_layouts` table unless future requirements need:

- multiple named Footer layouts;
- draft/publish;
- version history;
- multi-site assignment;
- reusable presets stored as records.

---

## Responsive Preview

The Footer Builder should eventually provide:

```text
Desktop
Tablet
Mobile
```

Preview should use current unsaved Livewire state, following the Phase 9E Header approach.

It should reflect:

- enabled/disabled components;
- slot order;
- vertical spacing;
- container width;
- color inheritance/overrides;
- menu-column density;
- social/app/trust sections;
- bottom-row arrangement.

Preview should not depend on an iframe of the storefront.

---

## Drag & Drop

Follow the Phase 9E Header pattern:

- native HTML5 Drag & Drop + Alpine/Livewire is preferred;
- server-side validation must still enforce registry allowed slots;
- no arbitrary payload may control renderer paths;
- up/down and select-based movement should remain as accessibility/fallback controls where useful;
- no new external SortableJS dependency should be introduced.

The current existing Footer Admin SortableJS dependency may remain temporarily for legacy column/link ordering until a dedicated cleanup slice verifies a replacement.

---

## Proposed Implementation Roadmap

### Phase 10A — Footer Decomposition

- split monolithic Footer Blade into stable components;
- preserve current visual behavior;
- keep current service/settings contracts;
- targeted Website tests only.

### Phase 10B — Footer Design Tokens & Presentation

- reuse Phase 9B global design tokens;
- introduce Footer presentation config/defaults;
- semantic CSS variables/utilities;
- preserve current dark default appearance.

### Phase 10C — Footer Schema & Component Registry

- introduce validated Footer component registry;
- define slots and default schema;
- add FooterLayoutService;
- add slot renderer;
- no admin builder yet.

### Phase 10D — Footer Builder Admin

- integrate into existing `/admin/footer-settings` page;
- enable/disable components;
- reorder and controlled slot movement;
- Basic/Advanced presentation controls;
- reset to defaults;
- persist through SettingsService;
- preserve existing Info / Columns / Social managers.

### Phase 10E — Drag/drop & Responsive Preview

- add drag/drop between compatible slots;
- add Desktop / Tablet / Mobile preview;
- live preview from unsaved builder state;
- retain server-side registry validation.

### Optional Phase 10F — Footer Content Hardcode Cleanup

If not safely included earlier:

- managed legal links;
- managed trust/payment badges;
- remove remaining hardcoded Footer labels/content;
- evaluate removal of legacy external UI CDN dependencies.

---

## Test Policy

Follow the agreed module-scoped workflow.

Do not run the entire project suite during normal Footer implementation.

Primary gate:

```bash
php artisan test tests/Feature/Website
```

Each slice should add focused Website tests for files/contracts it changes.

Only include directly affected module tests if a cross-module contract is intentionally changed.

---

## Admin Route

Current Footer Admin route remains:

```text
/admin/footer-settings
```

Phase 10D must integrate the Footer Builder into this existing page rather than introducing a competing administration route.

---

## Approval Gate

No Footer source refactor should begin until this Phase 10 analysis is explicitly approved.

After approval, implementation begins with **Phase 10A — Footer Decomposition**, preserving existing UI and runtime behavior before schema/builder work.
