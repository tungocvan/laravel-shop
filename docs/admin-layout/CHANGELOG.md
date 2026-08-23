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
- Footer composition service and component registry with app-name/environment components.
- Footer controls for visibility, app name, and environment in Admin Layout settings.
- Footer contract tests covering hidden baseline, composition, settings UI bindings, and persistence contract.

### Changed

- `Modules/Admin/resources/views/layouts/master.blade.php` delegates head, shell, content, stacks, and scripts to dedicated partials.
- Header, sidebar, toast, and icon views include stronger responsive and accessibility attributes.
- Sidebar authorization pruning and active-state calculation run in `SidebarService` before rendering.
- Admin layout config is database-backed and falls back to `Modules/Admin/config/admin.php`.
- Footer visibility is owned by the prepared Footer context rather than by the outer shell.
- Footer-specific settings are normalized by `AdminLayoutManager`.

### Fixed

- Livewire frontend assets use one explicit source.
- Admin Layout JSON settings now round-trip correctly when `SettingsService` returns decoded arrays, fixing settings that appeared to save but reverted after reload.

### Security

- Footer configuration remains constrained to boolean composition flags; no arbitrary footer markup is persisted.

## [2026-08-23] - Phase 13E Footer Architecture

### Added

- `AdminFooterService` prepared Footer context/registry.
- Footer `app_name` and `environment` presentation components.
- Dedicated Footer settings section in `/admin/layout`.
- `AdminFooterContractTest` regression coverage.

### Changed

- The shell always composes the Footer partial; the Footer context decides whether output is rendered.
- Footer defaults preserve the prior runtime baseline with `layout.show_footer = false`.

### Fixed

- JSON Admin Layout settings persistence now accepts the decoded array contract returned by the settings layer while remaining compatible with legacy JSON strings.

### Verification

- Targeted Admin layout test suite passed.
- Footer OFF/ON and component toggle UI verification passed.
- Footer settings persistence across save/reload passed.

### Follow-up

- Phase 13F will replace the long `/admin/layout` settings form with an Admin UI settings hub modeled after `/admin/website`, with dedicated General, Header, Sidebar, Footer, Design, and Navigation pages while retaining one `AdminLayoutManager` source of truth.

## [2026-08-23] - Phase 13A Baseline Closure

### Added

- Targeted Admin layout regression test: 4 tests, 23 assertions.
- Manual Admin UI verification gate.

### Changed

- Preserved current Admin UI/presentation after targeted tests and manual UI verification passed.

### Fixed

- Livewire asset ownership is explicit and single-source.

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
