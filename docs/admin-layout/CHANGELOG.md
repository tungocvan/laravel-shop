# Admin Layout Changelog

All notable changes to the Admin Layout documentation and architecture should be recorded here.

Use dates in `YYYY-MM-DD` format.

## [Unreleased]

### Added
- Phase 13I Professional Sidebar UX & Presentation implementation.
- Adaptive Sidebar metadata for sparse versus high-volume authorized navigation.
- Configurable `sidebar.navigation_search_threshold` with a default of 12 and normalized range 4–50.
- Large-navigation local filtering across parent and child destination labels.
- Professional Sidebar regression contract coverage.

### Changed
- Sidebar brand area presents site identity as an Admin workspace rather than a centered banner.
- Navigation hierarchy uses clearer icon containers/fallbacks, active indicators, child rail/indentation, and restrained transitions.
- Navigation scrolling uses stable scrollbar gutter while brand/profile regions remain fixed.
- Profile footer is balanced in expanded and collapsed modes.
- `/admin/layout/sidebar` exposes the search threshold so sparse and high-volume installations can tune the adaptive behavior.
- Responsive tests assert behavior/accessibility contracts rather than fixed collapse-button dimensions.

### Security
- Authorization pruning and route preparation remain owned by `SidebarService`; filtering only operates on the already-authorized render model.

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

### Verification required
- Targeted Admin Sidebar, Settings Hub, Footer, Header, responsive, shell, design, and layout contract tests.
- Manual expanded/collapsed Sidebar UI gate.
- Manual sparse-menu and high-volume-menu review.
- Search behavior check using both parent and child destination names.
- Mobile/tablet drawer regression check.

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
