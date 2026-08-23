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

### Changed

- `Modules/Admin/resources/views/layouts/master.blade.php` now delegates head, shell, content, stacks, and scripts to dedicated partials.
- Header, sidebar, toast, and icon views now include stronger responsive and accessibility attributes.
- Sidebar authorization pruning and active-state calculation now run in `SidebarService` before rendering.
- Admin layout config can now be managed from `/admin/layout` and falls back to `Modules/Admin/config/admin.php`.
- Phase 13A is treated as master-shell contract/runtime hardening rather than a new large decomposition because the current master layout is already an orchestration shell.
- Livewire frontend assets now use explicit Blade directives as the canonical source; automatic asset injection is disabled.

### Deprecated

- Treating older descriptions of a monolithic `master.blade.php`, Blade-side navigation authorization, or missing mobile search as authoritative current-state documentation.

### Removed

- Duplicate Livewire automatic asset injection from the runtime contract.

### Fixed

- Potential duplicate Livewire styles/scripts caused by combining `inject_assets => true` with `@livewireStyles` / `@livewireScripts`.
- Phase 13 baseline documentation now distinguishes current implementation from stale historical architecture descriptions.

### Security

- No authorization model changes in Phase 13A.

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
