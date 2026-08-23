# Phase 13 — Admin Layout Baseline Analysis

## Status

- Phase 0 documentation audit: completed.
- Phase 0 source/runtime audit: completed.
- Phase 13A targeted tests: PASS (4 tests, 23 assertions).
- Phase 13A UI verification: PASS.
- Current Admin UI/layout presentation is considered stable and should not be visually redesigned during Phase 13A.

## Current Architecture

`Modules/Admin/resources/views/layouts/master.blade.php` is already a thin orchestration shell. It loads Admin layout configuration through `AdminLayoutManager`, composes head/shell/stacks/scripts partials, initializes the Alpine shell state, and preserves existing page rendering contracts.

Current composition:

```text
master.blade.php
├── layouts.partials.head
├── x-admin::layout.skip-link
├── layouts.partials.shell
│   ├── Livewire admin.partials.sidebar
│   ├── Livewire admin.partials.header
│   ├── layouts.partials.content
│   └── layouts.partials.footer (conditional)
├── layouts.partials.stacks
└── layouts.partials.scripts
```

The initial assumption that Phase 13A needed a large master-layout decomposition is outdated. Basic decomposition has already been implemented and verified.

## Runtime Contracts To Preserve

- `$slot` rendering.
- `@yield('content')` fallback.
- `@yield('title')`, `@yield('css')`, `@yield('js')`.
- `@stack('styles')`, `@stack('scripts')`.
- Vite asset loading.
- Livewire support.
- Existing route/auth contracts.
- Header/sidebar rendering boundaries.
- Mobile drawer behavior and responsive breakpoint behavior.
- Skip link and `#admin-main` landmark.

## Header

Current Header provides:

- Mobile sidebar trigger.
- Desktop search.
- Mobile search trigger.
- Notifications.
- User menu.
- Sticky behavior.
- Responsive visibility and accessible controls.

Current weakness: presentation remains partly hardcoded with Tailwind classes while configuration already exposes some layout/theme options. This is intentionally deferred to later design-token/presentation phases because current UI has passed manual verification.

## Sidebar

`SidebarService::getMenusForUser()` already performs permission pruning and active-state calculation before rendering. This means older documentation describing authorization and active-state calculation inside Blade is stale.

Sidebar Blade still owns markup/group rendering and presentation logic. This is acceptable for the Phase 13A baseline and should be addressed incrementally in the Sidebar architecture phase rather than rewritten now.

## Sidebar State

There are currently two sidebar state concepts:

1. Alpine/browser state with `localStorage` used by the actual shell UI.
2. Livewire `Sidebar::$sidebarOpen` with session persistence.

The visual UI currently uses Alpine state. The Livewire state therefore appears legacy/redundant and should be verified before removal in a later focused change. Phase 13A does not remove it because the current UI is stable.

## Footer

Footer visibility is already controlled by `layout.show_footer`. The Footer is not yet a full configurable subsystem; copyright/version/links/presentation/theme behavior belongs to the later Footer phase.

## Configuration

`AdminLayoutManager` already provides:

- Defaults.
- Persisted JSON settings.
- Recursive merge.
- Normalization and whitelisting.
- Save/reset behavior.

The manager should be evolved rather than replaced by a parallel service.

Known config/runtime gaps include values that exist in config but are not yet fully consumed by presentation, such as sidebar widths, header height, container/density options, and accent/theme values. These belong to Phase 13B/13F, not Phase 13A.

## Livewire Assets

Baseline audit found a critical duplicate-source risk:

- `config/livewire.php` had `inject_assets => true`.
- Admin layout explicitly renders `@livewireStyles` and `@livewireScripts`.

Phase 13A resolved this by choosing manual Blade directives as the single source and setting `inject_assets => false`.

A regression test now requires exactly one Livewire asset source.

## Alpine / JavaScript

Alpine currently owns browser-only shell state:

- Sidebar open/collapsed state.
- Desktop breakpoint detection.
- Search overlay state.
- Keyboard shortcut handling.
- Focus trap/restore behavior.
- Sidebar localStorage persistence.

Global shell JavaScript still lives partly in the head partial. Moving it to a Vite-managed idempotent module remains a later hardening task and should not be mixed into the already-passing Phase 13A UI closure.

## Responsive Behavior

Verified design:

- `lg` and above: sidebar is fixed and can be expanded/collapsed.
- Below `lg`: sidebar behaves as an off-canvas drawer with backdrop.
- Mobile search is available through a header trigger.
- Escape closes overlays.
- Body scroll is locked while mobile sidebar/search overlays are open.
- Focus is moved into overlays and restored after close.

Older documentation stating that mobile search is missing is outdated.

## Hardcodes

Hardcodes are grouped as follows:

### Keep for now

- Structural breakpoints.
- z-index hierarchy.
- DOM landmarks.
- Current transition timing where UI is stable.

### Move to presentation/design contracts later

- Sidebar width classes (`w-64`, `w-20`).
- Header height (`h-16`).
- Slate/indigo presentation classes.
- Main content padding/container presentation.

### Content hardcodes to review later

- `View Profile` text.
- Site-name fallback values.

## Documentation Discrepancies

The following older statements are stale relative to current source:

- Master layout is monolithic.
- Sidebar permission filtering is performed in Blade.
- Sidebar active-state calculation is performed in Blade.
- Mobile search is missing.
- Admin layout configuration is only proposed and not implemented.

`CHANGELOG.md` reflects the current implementation more accurately than the older current-architecture analysis in these areas.

## Tests Baseline

`tests/Feature/Admin/AdminLayoutContractTest.php` locks these behaviors:

- Master remains an orchestration shell.
- Header/sidebar/content/footer composition remains present.
- `$slot` and `@yield('content')` remain supported.
- Vite, style/script yields, and stacks remain supported.
- Livewire assets have exactly one source.

Targeted result at Phase 13A gate:

```text
Tests: 4 passed
Assertions: 23
```

Manual Admin UI verification also passed.

## Risk Matrix

| Risk | Severity | Phase Direction |
|---|---|---|
| Duplicate Livewire asset injection | P0 | Resolved in Phase 13A |
| Config exists but presentation ignores parts of it | P1 | Phase 13B / 13F |
| Alpine and Livewire sidebar state duplication | P1 | Focused Sidebar/Shell hardening |
| Global shell JS inside Blade head | P1 | JS/runtime hardening phase |
| Hardcoded presentation classes | P1 | Design token/layout presentation phases |
| Stale architecture docs | P1 | Documentation closure |
| Route/permission naming for future builders | P1 | Audit before builder phases |

## Target Architecture

The existing shell should evolve toward:

```text
AdminLayoutManager / layout context
        ↓
master orchestration shell
├── head-meta / runtime-head / presentation tokens
├── shell
│   ├── header subsystem
│   ├── sidebar/navigation subsystem
│   ├── main/content shell
│   └── footer subsystem
├── global stacks
└── runtime scripts
```

Do not create parallel managers/services where existing components already own the correct responsibility.

## Proposed Phase Roadmap

- Phase 13A — Admin Master Shell Contract & Runtime Hardening.
- Phase 13B — Admin Design Tokens & Presentation Contract.
- Phase 13C — Admin Header Architecture & Builder.
- Phase 13D — Admin Sidebar Navigation Registry & Builder.
- Phase 13E — Admin Footer Architecture.
- Phase 13F — Admin Global Shell/Layout Presentation.
- Phase 13G — Responsive Preview.
- Phase 13H — Header/Sidebar/Footer Themes.
- Phase 13I — Unified Admin Theme Schema + Import/Export.
- Phase 13J — Demo Seeders.
- Phase 13K — UI & Operation Validation Standards.
- Phase 13L — Final Repository Audit & Regression Closure.

## Phase 13A Closure

Phase 13A intentionally does not redesign the current Admin UI because targeted tests and manual UI verification passed. The phase establishes runtime contracts and removes the duplicate Livewire asset-source risk while preserving existing behavior.

Next phase must begin with analysis of design/config tokens and their actual runtime consumers before changing presentation.
