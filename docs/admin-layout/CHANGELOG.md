# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added

- Responsive presentation contract coverage for Admin shell, Header, Sidebar drawer, and Search overlay.
- Phase 13I follow-up for Professional Sidebar UX & Presentation.

### Changed

- Admin responsive behavior now uses the `lg` / 1024px desktop boundary consistently across Sidebar drawer and Header Search behavior.
- Shell is the single owner of Sidebar expanded/collapsed dimensions; the Sidebar view fills the shell-owned width.
- Header height and surfaces consume centralized shell/design presentation values.
- Tablet/mobile Search uses the overlay presentation until the desktop breakpoint.

### Fixed

- Header regression coverage no longer requires obsolete hard-coded `h-16`, white surface, or slate border classes.
- Desktop Sidebar collapse control is hidden from drawer-mode layouts.

### Security

- Responsive presentation changes do not alter menu authorization or navigation permission pruning.

## [2026-08-23] - Phase 13H Responsive Presentation & Ownership Cleanup

### Added

- `AdminResponsivePresentationContractTest`.
- Shared responsive contract for drawer/search behavior at 1024px.

### Changed

- Sidebar width ownership is centralized in the shell.
- Header consumes configured height and semantic surface/border presentation.
- Mobile/tablet Search trigger and overlay remain active below 1024px.
- Search controls preserve 44px touch targets.

### Verification

- Targeted Admin layout suite passed after updating obsolete Header presentation assertions.
- Responsive UI manual verification passed.
- Drawer/Header/Search behavior was manually accepted across responsive modes.

### Follow-up: Phase 13I — Professional Sidebar UX & Presentation

Phase 13I will redesign Sidebar presentation as a dedicated UX phase rather than mixing a large visual redesign into responsive foundation work.

The redesign must work well for both sparse and high-volume navigation. It should evaluate hierarchy, grouping, spacing/density, active/open states, long-menu discoverability, optional search/quick access where justified, profile/footer balance, scrolling behavior, and restrained motion. Existing authorization, navigation preparation, and route contracts remain authoritative and must not be replaced by presentation logic.

## [2026-08-23] - Phase 13G Global Shell & Layout Presentation

### Verification

- Targeted Admin layout suite passed.
- Shell Presentation UI passed.
- Container modes passed manual UI verification.
- Density modes passed manual UI verification.

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
