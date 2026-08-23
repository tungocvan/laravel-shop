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

Admin presentation uses a sanitized semantic design-token contract and CSS custom properties. Presentation consumers migrate incrementally to avoid visual regression.

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

### Context

The former `/admin/layout` page accumulated Layout, Header, Sidebar, Footer, Theme, and Navigation controls in one long form. The Website administration area already demonstrates a clearer overview-plus-dedicated-pages pattern.

### Decision

`/admin/layout` is the Admin UI settings overview/dashboard. Dedicated editors are available at:

- `/admin/layout/general`
- `/admin/layout/header`
- `/admin/layout/sidebar`
- `/admin/layout/footer`
- `/admin/layout/design`
- `/admin/layout/navigation`

The existing `admin.layout` route name remains attached to the overview for backward compatibility.

All editors share one `AdminLayoutManager` and the same persisted `admin_layout_config`. A section editor must merge its validated section into the current configuration before saving, and reset must restore only that section rather than globally resetting unrelated UI settings.

### Consequences

- The overview remains concise and focused on status plus quick access.
- Each subsystem can evolve independently without recreating a monolithic settings form.
- Editing Footer cannot silently reset Header, Sidebar, Navigation, or other sections.
- Existing menu/database references to `admin.layout` remain valid.
- Reorganizing the settings administration does not itself alter runtime Admin presentation.

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
