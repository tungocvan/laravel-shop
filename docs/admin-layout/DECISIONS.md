# Decisions

## ADR-001: Admin Module Is A Presentation Shell

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Context

Repository bootstrap documentation defines `Admin` as a shell module. Business domains such as Product, Order, Post, Category, Account, Admission, and Pharma should own domain behavior.

### Decision

The admin layout documentation treats Admin as a presentation shell. Layout, navigation, theme, and admin UI composition can live in Admin. Domain workflows should remain in their owning modules or services.

### Consequences

- Layout refactors must avoid moving domain behavior into Admin.
- Admin navigation can link to domain features but should not become their canonical owner.

## ADR-002: Preserve Existing Master Layout Compatibility

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-06-27 |

### Context

Current pages may use either Blade sections or component slots.

### Decision

Any rebuild must preserve `@yield('content')`, `$slot`, stacks, sections, Vite assets, and Livewire resource support unless a migration plan is explicitly approved.

### Consequences

- Rebuild can be incremental.
- Existing pages do not need simultaneous edits.

## ADR-003: Move Navigation Decisions Out Of Blade

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

Sidebar permission pruning and active-state logic had already moved into `SidebarService`, but root Sidebar Blade still derived render-time structure such as child/group detection, group IDs and hrefs. Sidebar visibility state was also duplicated between Livewire/session and Alpine/localStorage even though the rendered UI used Alpine as the active owner.

### Decision

Use `SidebarService` as the canonical Admin navigation view-model builder. It prepares permission-filtered, active-aware navigation entries with render-ready fields such as `kind`, `href`, `group_id`, `active`, `has_children`, and normalized children.

Keep authorization and active-state decisions in the service layer. Root Sidebar Blade renders prepared navigation item/group partials and must not reimplement permission, active-state, URL normalization, or group-identity policy.

Browser-only sidebar open/collapse state is owned by Alpine/localStorage. Livewire/session sidebar-open state is removed from the Sidebar component unless a future server-owned requirement is explicitly introduced.

### Consequences

- Navigation behavior is testable before Blade rendering.
- Sidebar Blade becomes declarative and easier to evolve into later builder/configuration work.
- Permission visibility and parent-active behavior retain their existing service-backed semantics.
- Desktop collapse persistence has one runtime owner instead of duplicated Livewire and Alpine state.
- Future navigation features should extend the prepared view-model contract rather than adding procedural decisions back into root Sidebar Blade.

## ADR-004: Use Semantic Theme Tokens

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

The Admin design system defines semantic roles for surfaces, text, borders, accent/status colors, spacing, radius, typography, and shell dimensions, while current views still contain Tailwind presentation literals. Replacing those literals all at once would create unnecessary visual-regression risk because the current Admin UI has already passed manual verification.

### Decision

Establish a sanitized semantic design-token contract under `admin.design`, resolve it through `AdminDesignService`, and expose the resolved values as `--admin-*` CSS custom properties from a dedicated presentation-styles partial.

Default token values must match the currently approved Admin presentation. Existing Tailwind classes are not migrated wholesale in Phase 13B; later Header, Sidebar, Footer, and shell phases may consume the semantic variables incrementally.

Only whitelisted token values may be emitted. Arbitrary user-provided CSS values or class fragments are not part of the design-token contract.

### Consequences

- Future presentation work has one semantic vocabulary instead of adding new hardcoded colors and dimensions.
- The approved current UI can remain visually unchanged while the architecture gains a stable token layer.
- Runtime values are constrained to known-safe palettes, spacing, radius, typography, and shell dimensions.
- Later phases must migrate presentation deliberately and verify UI after each consumer adopts tokens.
- Tailwind safelisting is not required for these tokens because the contract emits CSS custom-property values rather than dynamically generated Tailwind class names.

## ADR-005: Centralize Global JavaScript

| Field | Value |
|---|---|
| Status | Proposed |
| Date | 2026-06-27 |

### Context

Search shortcut and toast behavior currently live inside Blade component scripts.

### Decision

Future layout JavaScript should live in Vite-managed modules with idempotent initialization.

### Consequences

- Better compatibility with future `wire:navigate`.
- Easier testing and cleanup.
- Blade views become more declarative.

## ADR-006: Use Manual Livewire Asset Injection

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

The Admin layout explicitly renders `@livewireStyles` and `@livewireScripts`, while `config/livewire.php` previously had `inject_assets => true`. This created two possible sources for Livewire frontend assets and violated the single-source runtime contract established for the Admin layout.

### Decision

Use the existing explicit Blade directives as the canonical Livewire asset source and set `config/livewire.php` `inject_assets` to `false`.

Add a regression contract test that fails when both auto-injection and manual directives are enabled or when neither source is configured.

### Consequences

- Livewire assets have one predictable source.
- Existing Admin layout composition remains unchanged.
- Future layout refactors must preserve this decision unless the entire application deliberately migrates to automatic injection and removes manual directives consistently.
- UI verification is required after any future change to the Livewire asset contract.

## ADR-007: Header Composition Uses A Prepared Registry

| Field | Value |
|---|---|
| Status | Accepted |
| Date | 2026-08-23 |

### Context

The Admin header view previously read layout configuration directly and decided which header features to render. The Livewire `Header` class itself had no meaningful state, so composition policy lived in Blade alongside presentation markup.

### Decision

Move header composition policy into `AdminHeaderService`. The service returns a prepared header context containing sticky state and an ordered component registry. The Livewire `Header` component receives that context and passes it to a declarative Blade view.

Header widgets such as search, notifications, and the user menu keep their existing Livewire behavior and data ownership. Phase 13C only changes orchestration boundaries and component extraction; it does not redesign the approved Admin header UI.

### Consequences

- Header enable/disable and ordering decisions are testable outside Blade.
- Header Blade no longer needs to query `AdminLayoutManager` directly.
- Existing widget behavior and responsive markup can be preserved while future builder/config work targets one registry contract.
- New header components should be registered through the prepared header context instead of adding new conditional orchestration directly to the root header Blade.
- Any future presentation migration must continue to pass targeted tests and manual desktop/mobile UI verification.

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
