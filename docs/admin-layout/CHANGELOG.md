# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added

- Admin UI settings hub at `/admin/layout`.
- Dedicated General, Header, Sidebar, Footer, Design, and Navigation settings pages.
- Section-aware settings editor with per-section save/reset behavior.
- Regression coverage for settings routes, hub quick access, and cross-section persistence.

### Changed

- `/admin/layout` is now an overview/dashboard instead of a monolithic settings form.
- Existing `admin.layout` route name is preserved for navigation compatibility.
- Settings pages continue to use one `AdminLayoutManager` and one persisted `admin_layout_config` source of truth.
- Saving or resetting one settings section no longer replaces unrelated sections.

### Fixed

- Footer contract regression tests now assert the stable section/editor contract instead of obsolete monolithic-form markup.

### Security

- No permission model or runtime Admin presentation changes were introduced by Phase 13F.

## [2026-08-23] - Phase 13F Admin UI Settings Hub

### Added

- `/admin/layout/general`
- `/admin/layout/header`
- `/admin/layout/sidebar`
- `/admin/layout/footer`
- `/admin/layout/design`
- `/admin/layout/navigation`
- Overview cards with subsystem status and quick-access actions.

### Changed

- Admin UI configuration follows the same overview-plus-dedicated-pages pattern used by the Website administration area.
- Save operations merge the active section into current configuration before persistence.
- Reset operations restore only the active section to defaults.

### Verification

- Targeted Admin layout suite passed.
- Settings Hub UI verification passed.
- Cross-section persistence verification passed.

### Follow-up

- The next phase may proceed to Global Shell/Layout Presentation while using the new settings hub as the administration surface.

## [2026-08-23] - Phase 13E Footer Architecture

### Verification

- Targeted Admin layout test suite passed.
- Footer OFF/ON and component toggle UI verification passed.
- Footer settings persistence across save/reload passed.

## [2026-08-23] - Phase 13A Baseline Closure

### Verification

- Targeted Admin layout regression test passed: 4 tests, 23 assertions.
- Manual Admin UI verification passed.

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
