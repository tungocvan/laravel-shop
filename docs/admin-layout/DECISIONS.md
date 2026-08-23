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

Admin presentation uses sanitized semantic design tokens and CSS custom properties.

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

`AdminFooterService` owns Footer composition and visibility.

## ADR-010: Admin Layout Settings Use A Hub With Dedicated Subpages

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

`/admin/layout` is the Admin UI settings overview. Dedicated General, Header, Sidebar, Footer, Design, and Navigation pages share one `AdminLayoutManager` and one persisted configuration source.

## ADR-011: Shell Presentation Uses Centralized Normalized Layout Configuration

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Decision

`AdminLayoutManager` / `admin_layout_config` is the canonical persisted source for layout and design presentation. `AdminShellPresentationService` resolves normalized settings into render-ready shell presentation values.

## ADR-012: Responsive Admin Uses One Desktop Boundary And Shell-Owned Dimensions

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

Sidebar drawer behavior used the `lg` / 1024px boundary while Header Search presentation previously changed at the smaller `sm` boundary. Sidebar width was also represented both by the shell and by hard-coded width classes inside the Sidebar view.

### Decision

Use 1024px (`lg`) as the Admin desktop presentation boundary for Sidebar drawer and Header Search behavior.

The shell is the single owner of Sidebar expanded/collapsed dimensions. Sidebar presentation fills the shell-owned width rather than defining a competing width. Desktop collapse controls are desktop-only; drawer layouts use Header/drawer controls.

Header height and semantic surfaces should consume centralized presentation values rather than reintroducing hard-coded shell dimensions/colors.

### Consequences

- Tablet layouts behave consistently as drawer-mode layouts below 1024px.
- Search no longer switches to desktop presentation prematurely at 640px.
- Sidebar dimension changes have one runtime owner.
- Responsive presentation requires manual verification around mobile, tablet, and desktop boundaries.

## ADR-013: Professional Sidebar Redesign Is A Dedicated Presentation Phase

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

The Sidebar must remain usable when navigation is either sparse or very large. Solving this well requires more than responsive fixes and should not be mixed into the Phase 13H foundation branch.

### Decision

Phase 13I is dedicated to Professional Sidebar UX & Presentation.

The phase will audit and design for both sparse and high-volume menus. It should consider visual hierarchy, meaningful grouping, density/spacing, active and expanded states, long-menu navigation/search or quick access where justified, scroll behavior, profile/footer balance, and restrained motion.

Authorization pruning, route ownership, navigation preparation, and Sidebar state ownership established in earlier ADRs remain unchanged. Presentation must consume those contracts rather than duplicating business or permission logic in Blade/JavaScript.

### Consequences

- Sidebar redesign receives its own visual regression and manual UI gate.
- Responsive foundation remains independently testable and mergeable.
- Large-menu UX can be improved without compromising permission behavior.
- Sparse menus should remain visually balanced rather than appearing unfinished or excessively empty.

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
