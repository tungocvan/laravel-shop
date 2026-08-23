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

Admin presentation uses a sanitized semantic design-token contract and CSS custom properties. Existing presentation consumers migrate incrementally to avoid visual regression.

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

Header composition decisions belong in a prepared service/context registry; the Livewire Header and Blade root remain declarative consumers of that context.

## ADR-008: Sidebar State Has One Runtime Owner

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

Alpine/localStorage owns interactive Sidebar open/collapsed state. Livewire/session do not duplicate that client-side state. `SidebarService` owns authorization and navigation view-model preparation.

## ADR-009: Footer Uses Prepared Composition Context

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

Footer originally rendered app name/environment directly and the outer shell owned the `show_footer` conditional. Phase 13E also exposed a persistence bug in the Admin Layout JSON setting contract.

### Decision

Use `AdminFooterService` as the Footer composition owner. The shell always includes the Footer partial; Footer context decides visibility and enabled components. Footer component flags are normalized by `AdminLayoutManager`.

The settings layer's decoded JSON array is the canonical read contract; `AdminLayoutManager` remains backward-compatible with legacy JSON strings.

### Consequences

- Footer can grow without moving composition conditionals back into the shell.
- Default `show_footer=false` preserves the approved UI baseline.
- Footer controls persist correctly across save/reload.
- Arbitrary Footer HTML is not part of the configuration contract.

## ADR-010: Admin Layout Settings Use A Hub With Dedicated Subpages

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

`/admin/layout` has accumulated Layout, Sidebar, Header, Footer, Theme, and Navigation controls in one long form. The existing `/admin/website` area demonstrates a more maintainable pattern: a dashboard/overview route with dedicated settings routes for individual subsystems.

### Decision

Starting in Phase 13F, `/admin/layout` becomes the Admin UI overview/dashboard rather than the primary long settings form.

Create dedicated settings surfaces for:

- `/admin/layout/general`
- `/admin/layout/header`
- `/admin/layout/sidebar`
- `/admin/layout/footer`
- `/admin/layout/design`
- `/admin/layout/navigation`

All pages continue to use one `AdminLayoutManager` configuration source and the same persisted `admin_layout_config` setting. Do not create independent competing settings stores per page.

The overview should prioritize status summaries and quick access. Editing belongs on the dedicated subsystem pages.

### Consequences

- Admin UI configuration is easier to discover, monitor, and maintain.
- Each page can evolve with its subsystem without turning `/admin/layout` back into a monolithic form.
- Persistence, permissions, and defaults remain centralized.
- Runtime Admin presentation must not change merely because settings administration is reorganized.

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
