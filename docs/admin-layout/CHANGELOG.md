# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

## [2026-08-23] - Phase 13K Professional Content Workspace & Page Header System

### Design direction
Admin pages use a shared workspace hierarchy instead of each page inventing its own outer container, heading, spacing, and toolbar presentation. Page-level primitives remain presentation-only and do not absorb module business logic.

### Added
- Shared `x-admin::page-header` primitive with title, description, eyebrow, actions, and toolbar regions.
- Shared `x-admin::content-section` primitive for semantic section rhythm without forcing every section into a card.
- Content Workspace regression coverage.

### Changed
- `/admin/layout`, `/admin/layout/{section}`, and the Header/Menu manager now use the shared Content Workspace hierarchy.
- Page headings are owned by page shells instead of duplicated inside Livewire widgets.
- Header/Menu tabs use the page-header toolbar region while preserving existing Livewire feature ownership.
- `/admin/layout/design` is the canonical Admin Theme configuration UI.
- Legacy `/admin/themes` remains backward-compatible by redirecting to `/admin/layout/design`.
- The Header/Menu manager no longer embeds a second Theme editor and links to the canonical Design settings instead.
- Sidebar runtime theme selection now consumes the centralized Admin Layout configuration and refreshes after layout settings are saved or reset.
- Sidebar navigation presentation consumes the selected theme palette rather than hardcoded indigo/gray states.
- Theme validation uses `ThemeManager::all()` as the same canonical theme registry exposed to the UI.

### Fixed
- Fixed Sidebar Theme changes that persisted but did not refresh the mounted Sidebar immediately.
- Fixed `modern-dark` and `sunset-orange` being rejected by a mismatched normalization whitelist.
- Fixed duplicate page-heading ownership between page shells and Livewire settings widgets.

### Verification
- Content Workspace, Settings Hub, Sidebar, and Admin Layout contract gates passed during implementation.
- Four Sidebar themes passed persistence and UI verification: `soft-light`, `modern-dark`, `sunset-orange`, and `corporate-blue`.
- Canonical Theme routing passed: `/admin/themes` redirects to `/admin/layout/design`.
- Manual Content Workspace UI checks passed for the migrated Admin pages during the phase.

## [2026-08-23] - Phase 13J Professional Header UX & Presentation

### Design direction
The Header is a compact Admin command surface. It should support frequent search, notification, and account actions without competing visually with page content or the professional Sidebar.

### Implementation
- Refined Header shell spacing and presentation while preserving runtime shell dimensions and composition ownership.
- Refined desktop search into a wider compact command-style control with focus feedback and keyboard hint.
- Refined notification control and removed continuous pulse animation to reduce long-session visual noise.
- Refined account trigger and dropdown hierarchy with identity context, clearer action zones, restrained transitions, and preserved POST/CSRF logout behavior.
- Preserved Livewire widget boundaries and server-owned Header component registry.
- Aligned shell regression coverage with the Phase 13I borderless Sidebar decision.
- Added professional Header presentation regression contracts for search, notifications, accessibility, account menu, transitions, and logout behavior.

### Verification
- Targeted Header, responsive presentation, shell presentation, and layout contract tests passed.
- Manual Header UI verification passed.
- Search, notification control, account menu, and responsive presentation passed the UI gate.

## [2026-08-23] - Phase 13I Professional Sidebar UX & Presentation

### Design direction
The Sidebar is a persistent Admin workspace navigator, not a decorative menu. It should remain calm during long sessions and usable at both low and high navigation volume.

Sparse menus avoid unnecessary search chrome. High-volume menus gain local discoverability aids without changing authorization or route ownership.

### Implementation
- Refined brand/workspace hierarchy.
- Refined leaf/group active states and collapsed alignment.
- Added child navigation rail/indent hierarchy.
- Stabilized the scrolling navigation region between fixed brand and profile regions.
- Added destination-volume context to the Livewire Sidebar.
- Added configurable conditional navigation search.
- Search includes authorized child labels and exposes matching group children while filtering.
- Preserved collapsed-state accessible labels/tooltips.

### Verification
- Targeted Admin Sidebar, Settings Hub, Footer, Header, responsive, shell, design, and layout contract tests passed during the phase.
- Expanded/collapsed and responsive Sidebar UI verification passed.
- Vertical Sidebar divider was removed and regression-locked.

## [2026-08-23] - Phase 13H Responsive Presentation & Ownership Cleanup

### Verification
- Targeted Admin layout suite passed.
- Responsive UI manual verification passed.

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
