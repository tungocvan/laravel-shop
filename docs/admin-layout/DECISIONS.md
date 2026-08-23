# Decisions

## ADR-001: Admin Module Is A Presentation Shell

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Decision

Admin owns layout, navigation, theme, and Admin UI composition. Business-domain behavior remains in its owning modules/services.

## ADR-002: Preserve Existing Master Layout Compatibility

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Decision

Preserve `@yield('content')`, `$slot`, stacks, sections, Vite assets, and Livewire support unless an explicit migration is approved.

## ADR-003: Move Navigation Decisions Out Of Blade

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Permission pruning, active/open state, and render-ready navigation metadata belong in `SidebarService`; Sidebar Blade renders prepared navigation view models.

## ADR-004: Use Semantic Theme Tokens

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Admin presentation uses sanitized semantic design tokens and CSS custom properties. Presentation consumers migrate incrementally to preserve visual stability.

## ADR-005: Centralize Global JavaScript

| Field | Value |
|---|---|
| Status | Proposed |
| Date | 2026-06-27 |

### Decision

Future global layout JavaScript should move to Vite-managed modules with idempotent initialization.

## ADR-006: Use Manual Livewire Asset Injection

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Explicit Blade `@livewireStyles` / `@livewireScripts` directives are the canonical Livewire asset source and automatic injection remains disabled.

## ADR-007: Header Uses Prepared Composition Context

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Header composition decisions belong in a prepared service/context registry; the Livewire Header and Blade root remain declarative consumers.

## ADR-008: Sidebar State Has One Runtime Owner

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Alpine/localStorage owns interactive Sidebar open/collapsed state. `SidebarService` owns authorization and navigation view-model preparation.

## ADR-009: Footer Uses Prepared Composition Context

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

`AdminFooterService` owns Footer composition and visibility. Footer settings remain normalized through the central Admin layout configuration contract.

## ADR-010: Admin Layout Settings Use A Hub With Dedicated Subpages

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

`/admin/layout` is the Admin UI settings overview. General, Header, Sidebar, Footer, Design, and Navigation use dedicated subpages while sharing one `AdminLayoutManager` and one `admin_layout_config` persisted source. Section save/reset operations must not replace unrelated configuration.

## ADR-011: Shell Presentation Uses Centralized Normalized Layout Configuration

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

The Admin shell historically hard-coded key presentation values while semantic design tokens and layout settings existed separately. `AdminDesignService` also had a separate persistence path, creating a risk of two competing sources for runtime presentation.

### Decision

`AdminLayoutManager` / `admin_layout_config` is the canonical persisted source for layout and design presentation configuration.

`AdminShellPresentationService` resolves normalized configuration into render-ready shell values such as content container classes, density spacing, sidebar dimensions, and header height. `AdminDesignService` resolves semantic design tokens from the same centralized configuration source.

Container modes must be visually meaningful rather than aliases that differ only at unusually large viewport widths:

- `narrow` prioritizes focused, constrained content.
- `7xl` provides a balanced standard workspace.
- `screen-2xl` provides a wide workspace with controlled gutters/cap.
- `full` uses the full available content region.

Mobile remains width-safe and does not artificially constrain content based on desktop container modes.

### Consequences

- Runtime shell presentation has one configuration ownership path.
- Design settings cannot silently diverge from layout settings because of a second persisted design store.
- Container and density controls have observable runtime effects.
- Default configuration remains the compatibility baseline and requires manual UI verification when shell mappings change.
- Arbitrary CSS values remain outside the persisted contract; mappings stay allow-listed and controlled by application code.

## ADR Template

Use this format for future decisions:

```md
## ADR-XXX: Title

| Field | Value |
|---|---|
| Status | Proposed/Accepted/Superseded |
| Date | YYYY-MM-DD |

### Context

Describe the problem and constraints.

### Decision

State the chosen direction.

### Consequences

List benefits, tradeoffs, and follow-up requirements.
```
