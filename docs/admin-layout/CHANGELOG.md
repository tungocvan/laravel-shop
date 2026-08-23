# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added

- Initial documentation set for Admin Layout architecture.
- Current architecture analysis for `master.blade.php`, Livewire header/sidebar partials, sidebar menu services, header menu service, theme manager, toast component, and icon component.
- Target architecture, component tree, responsive strategy, configuration spec, design system, accessibility, performance, Livewire, and Blade component guidance.
- Refactor plan, rebuild spec, implementation roadmap, merge checklist, and ADR log.
- Production admin layout rebuild with module configuration, layout partials, skip link, accessible shell regions, flash message region, mobile search overlay, and global stack roots.
- Admin layout configuration UI backed by database settings.
- Phase 13 baseline report at `docs/modules/Admin/PHASE_13_ANALYSIS.md`.
- `AdminLayoutContractTest` covering orchestration, page rendering extension points, and the single Livewire asset-source contract.
- Semantic Admin design-token contract, resolver service, presentation CSS variables, and targeted design-contract tests.
- Header composition service, prepared component registry, extracted header component partials, and targeted header contract tests.
- Sidebar render-ready navigation view-model contract, extracted item/group partials, and targeted Sidebar contract tests.

### Changed

- `Modules/Admin/resources/views/layouts/master.blade.php` now delegates head, shell, content, stacks, and scripts to dedicated partials.
- Header, sidebar, toast, and icon views now include stronger responsive and accessibility attributes.
- Sidebar authorization pruning and active-state calculation now run in `SidebarService` before rendering.
- Admin layout config can now be managed from `/admin/layout` and falls back to `Modules/Admin/config/admin.php`.
- Phase 13A is treated as master-shell contract/runtime hardening rather than a new large decomposition because the current master layout is already an orchestration shell.
- Livewire frontend assets now use explicit Blade directives as the canonical source; automatic asset injection is disabled.
- ADR-004 is accepted: future Admin presentation work should consume sanitized semantic tokens instead of introducing new presentation literals.
- Header orchestration now uses `AdminHeaderService` and a prepared ordered component registry instead of direct config reads and composition decisions inside root header Blade.
- Sidebar navigation rendering now consumes prepared entries from `SidebarService` instead of deriving URL/group/render structure inside the root Sidebar Blade.
- Sidebar browser open/collapse state is owned by Alpine/localStorage rather than duplicated in Livewire/session state.

### Deprecated

- Treating older descriptions of a monolithic `master.blade.php`, Blade-side navigation authorization, or missing mobile search as authoritative current-state documentation.
- Adding new arbitrary Tailwind color/dimension fragments as configurable Admin theme values when an existing semantic token can represent the role.
- Adding new header composition conditionals directly to the root header Blade when the behavior belongs in the prepared header registry.
- Reintroducing permission/active-state/URL/group-identity decisions into root Sidebar Blade when those decisions belong in the navigation view-model builder.

### Removed

- Duplicate Livewire automatic asset injection from the runtime contract.
- Root-header responsibility for reading Admin layout configuration directly.
- Duplicate Livewire/session ownership of Sidebar open/collapse state.

### Fixed

- Potential duplicate Livewire styles/scripts caused by combining `inject_assets => true` with `@livewireStyles` / `@livewireScripts`.
- Phase 13 baseline documentation now distinguishes current implementation from stale historical architecture descriptions.
- Header composition policy is now testable independently from presentation markup.
- Sidebar render structure and state ownership are now explicit and testable independently from root Blade markup.

### Security

- Admin design-token output is constrained to whitelisted values; Phase 13B does not expose arbitrary CSS or class injection.
- No authorization model changes in Phase 13C.
- Sidebar permission pruning remains service-backed in Phase 13D; no route authorization policy is weakened or moved into the client.

## [2026-08-23] - Phase 13D Sidebar Navigation Architecture

### Added

- Render-ready navigation fields from `SidebarService`, including entry kind, normalized href, group identity, active state, and prepared children.
- Dedicated Sidebar navigation partials for leaf items and groups.
- `AdminSidebarContractTest` covering navigation view-model structure, root Blade delegation, service-owned permission/active-state behavior, and Alpine-owned open/collapse state.

### Changed

- Root Sidebar Blade delegates navigation item/group rendering to prepared partials and no longer computes child/group/URL policy itself.
- Existing `SidebarService` remains the canonical foundation rather than introducing a parallel navigation service.
- Existing permission pruning and parent/child active-state semantics are preserved.
- Desktop collapse persistence remains in Alpine/localStorage, matching the UI runtime that had already been verified in earlier phases.
- Existing Sidebar classes, responsive behavior, menu hierarchy, branding, footer-profile presentation, and mobile drawer behavior remain the visual baseline.

### Removed

- `sidebarOpen` Livewire property and session persistence that duplicated Alpine/localStorage browser state.
- Livewire `toggleSidebar()` server action that was not the active owner of the rendered Sidebar interaction.

### Verification

- Phase 13D targeted tests passed together with Header, Design Token, and Master Layout regression contracts.
- Manual desktop/mobile Sidebar verification passed, including collapse persistence, parent/child menu interaction, active state, permission visibility, drawer/backdrop behavior, and JavaScript console check.

### Security

- Permission filtering remains in `SidebarService`; this phase changes rendering boundaries, not access-control semantics.

## [2026-08-23] - Phase 13C Header Architecture

### Added

- `AdminHeaderService` as the canonical header composition/context builder.
- Ordered header component registry for sidebar toggle, search, notifications, divider, and user menu.
- Dedicated component partials under `livewire/partials/header/components`.
- `AdminHeaderContractTest` covering registry order, enable/disable behavior, sticky context, Livewire context ownership, and declarative root Blade composition.

### Changed

- `Livewire\Partials\Header` now receives prepared header context from `AdminHeaderService`.
- Root header Blade renders the prepared registry and no longer queries `AdminLayoutManager` directly.
- Existing search, notification, and user-menu widgets keep their current behavior and ownership.
- Existing Header markup/classes and responsive behavior remain the visual baseline.

### Verification

- Phase 13C targeted tests passed together with Phase 13B and Phase 13A regression contracts.
- Manual desktop/mobile Header verification passed with no intended UI change.

### Security

- No permission, authentication, logout, or user-menu authorization behavior was changed.

## [2026-08-23] - Phase 13B Design Token Contract

### Added

- `AdminDesignService` as the semantic design-token resolver and sanitizer.
- `admin.design` defaults covering semantic colors, typography, spacing, radius, and shell dimensions.
- Dedicated `presentation-styles` partial exposing resolved values through `--admin-*` CSS custom properties.
- `AdminDesignContractTest` to lock token defaults, sanitization, CSS variable output, and head composition.

### Changed

- Admin presentation now has a canonical semantic token layer available to later Header, Sidebar, Footer, and shell phases.
- Existing Tailwind presentation classes remain in place in Phase 13B to preserve the already approved UI baseline.

### Fixed

- Design-system semantics are no longer documentation-only; they now have a runtime contract that future presentation consumers can adopt incrementally.

### Security

- Invalid token values fall back to approved defaults instead of being emitted into runtime CSS.

### Verification

- Targeted Phase 13B tests passed.
- Existing Phase 13A Admin layout contract tests passed alongside Phase 13B tests.
- Manual desktop/mobile Admin UI verification passed with no intended visual change.

## [2026-08-23] - Phase 13A Baseline Closure

### Added

- Targeted Admin layout regression test: 4 tests, 23 assertions.
- Manual Admin UI verification gate.

### Changed

- Preserved current Admin UI/presentation after targeted tests and manual UI verification passed.
- Future presentation changes are deferred to the design-token and layout-presentation phases.

### Fixed

- Livewire asset ownership is now explicit and single-source.

## Release Entry Template

```md
## [YYYY-MM-DD] - Short Title

### Added

- New behavior, component, document, or contract.

### Changed

- Existing behavior or architecture changed.

### Deprecated

- Behavior that remains available but should not be used for new work.

### Removed

- Removed behavior, component, file, or contract.

### Fixed

- Bug fixes or documentation corrections.

### Security

- Security, authorization, data exposure, or sensitive workflow changes.
```
