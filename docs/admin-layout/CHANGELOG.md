# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added

- `AdminShellPresentationService` for resolved runtime shell presentation context.
- Runtime support for distinct Admin content container modes and density modes.
- Contract coverage for shell presentation behavior.

### Changed

- Admin shell consumes semantic design variables for base surface, text, borders, and typography.
- Admin content width and spacing are resolved from `layout.container` and `layout.density`.
- Sidebar expanded/collapsed dimensions and header height are sourced from centralized Admin layout configuration.
- `AdminDesignService` reads design tokens from `AdminLayoutManager` so layout and design no longer have competing persisted configuration sources.
- Container modes are intentionally visually distinct on common desktop widths while remaining mobile-safe.

### Fixed

- Persistence regression assertion no longer depends on PHP source formatting/line breaks.

### Security

- Presentation values remain constrained to normalized/allow-listed configuration values; arbitrary runtime CSS input is not accepted.

## [2026-08-23] - Phase 13G Global Shell & Layout Presentation

### Added

- Runtime shell presentation context for container, density, sidebar dimensions, and header height.
- Distinct `full`, `narrow`, `7xl`, and `screen-2xl` content modes.
- `comfortable`, `compact`, and `dense` content spacing modes.

### Changed

- Semantic design tokens now use the central `admin_layout_config` ownership path.
- Shell/content presentation consumes centralized configuration instead of relying only on hard-coded Tailwind layout values.
- Container labels/settings were clarified so administrators can understand the visual intent of each mode.

### Verification

- Targeted Admin layout suite passed.
- Shell Presentation UI passed.
- Container modes passed manual UI verification.
- Density modes passed manual UI verification.

### Follow-up

- Continue with the next Admin Layout presentation phase while preserving the verified default shell baseline and centralized configuration ownership.

## [2026-08-23] - Phase 13F Admin UI Settings Hub

### Verification

- Targeted Admin layout suite passed.
- Settings Hub UI verification passed.
- Cross-section persistence verification passed.

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
