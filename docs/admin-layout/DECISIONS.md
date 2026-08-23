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
Admin presentation uses sanitized semantic design tokens and controlled presentation mappings.

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
Header composition decisions belong in a prepared service/context registry; the Header view remains a declarative consumer.

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
`AdminFooterService` owns Footer composition and visibility.

## ADR-010: Admin Layout Settings Use A Hub With Dedicated Subpages

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision
`/admin/layout` is the Admin UI settings overview. Dedicated settings pages share one centralized Admin layout configuration source.

## ADR-011: Shell Presentation Uses Centralized Normalized Layout Configuration

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision
`AdminLayoutManager` is the canonical persisted source for layout/design presentation and `AdminShellPresentationService` resolves render-ready shell values.

## ADR-012: Responsive Admin Uses One Desktop Boundary And Shell-Owned Dimensions

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision
Use 1024px (`lg`) as the Admin desktop presentation boundary. The shell is the single owner of Sidebar expanded/collapsed dimensions.

## ADR-013: Professional Sidebar Uses Adaptive Navigation Density

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context
Admin installations can have very different authorized navigation volume. A Sidebar with only a few destinations should not be cluttered with unnecessary controls, while a Sidebar with many modules needs stronger hierarchy and discoverability.

### Decision
Treat Sidebar as a persistent professional workspace navigator with adaptive presentation based on authorized destination volume.

Sparse navigation keeps the interface simple: brand/workspace identity, navigation, and optional profile footer without unnecessary search chrome. High-volume navigation can expose destination context and a local navigation filter.

The search threshold belongs to centralized Sidebar presentation configuration as `sidebar.navigation_search_threshold`, normalized to 4–50 destinations and defaulting to 12. It is editable from `/admin/layout/sidebar`. The threshold controls presentation only; it does not alter authorization or menu contents.

Search matches parent labels and authorized child labels. During an active search, matching groups expose matching children so a child destination is discoverable without an extra manual expand step.

Navigation hierarchy uses restrained active indicators, consistent icon containers/fallbacks, clearer parent/child indentation, stable scrolling between fixed brand/profile regions, and reduced-motion-aware transitions. Collapsed Sidebar remains icon-oriented with accessible labels/tooltips; expanded Sidebar prioritizes readable hierarchy.

### Consequences
- Sparse Sidebars remain calm and visually balanced.
- Large Sidebars gain configurable discoverability without moving permission logic into the view.
- Existing `SidebarService` permission and route contracts remain authoritative.
- Manual UI verification must include sparse, high-volume, filtering, expanded, collapsed, and drawer scenarios.

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
