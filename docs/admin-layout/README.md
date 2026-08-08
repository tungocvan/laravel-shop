# Admin Layout Documentation

## Purpose

This directory is the source of truth for the INAFO Pharma Admin Layout. It documents the current Laravel 12, Livewire 3, Alpine, Tailwind CSS 4, Bootstrap 5.3, and AdminLTE 4 RC admin shell before any rebuild or large refactor.

The current layout entry point is `Modules/Admin/resources/views/layouts/master.blade.php`. It composes the admin shell with Livewire partials for the sidebar and header, anonymous Blade components for icons and toasts, and Alpine state for sidebar behavior.

## Goals

| Goal | Description |
|---|---|
| Preserve behavior | Document the existing layout before changing it. |
| Reduce refactor risk | Give developers a shared model for shell, navigation, theme, and responsive behavior. |
| Standardize UI | Define a design system and component rules for future admin screens. |
| Improve accessibility | Establish keyboard, focus, ARIA, contrast, and semantic requirements. |
| Improve performance | Define rendering, asset, caching, and Livewire optimization targets. |
| Support rebuilds | Provide enough detail for another developer or AI agent to rebuild the layout without extra clarification. |

## Scope

Included:

- Master admin layout architecture.
- Header, sidebar, toast, icon, and related Livewire partials.
- Navigation, theme, configuration, responsive, accessibility, performance, and Livewire guidance.
- A refactor plan, rebuild specification, roadmap, merge checklist, decisions, and changelog template.

Excluded:

- Application code changes.
- Domain page redesigns outside the layout contract.
- Route, permission, database, or service rewrites.
- Frontend dependency upgrades or downgrades.

## Required Reading Order

1. `README.md`
2. `LAYOUT_ANALYSIS.md`
3. `CURRENT_ARCHITECTURE.md`
4. `TARGET_ARCHITECTURE.md`
5. `COMPONENT_TREE.md`
6. `CONFIGURATION_SPEC.md`
7. `DESIGN_SYSTEM.md`
8. `RESPONSIVE_STRATEGY.md`
9. `ACCESSIBILITY.md`
10. `PERFORMANCE.md`
11. `LIVEWIRE_GUIDE.md`
12. `BLADE_COMPONENT_GUIDE.md`
13. `REFACTOR_PLAN.md`
14. `REBUILD_SPEC.md`
15. `IMPLEMENTATION_ROADMAP.md`
16. `CHECKLIST.md`
17. `DECISIONS.md`
18. `CHANGELOG.md`

## Recommended Workflow

1. Read the bootstrap docs and roadmap:
   - `.codex/bootstrap/CODEX_BOOTSTRAP.md`
   - `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
   - `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
   - `ROADMAP.md`
2. Read this admin-layout documentation in the required order.
3. Confirm the target change is documentation-only, analysis-only, or implementation.
4. For implementation work, create a small plan that names affected Blade views, Livewire classes, services, config files, and tests.
5. Change one layout boundary at a time.
6. Verify desktop, laptop, tablet, and mobile behavior.
7. Update this documentation whenever the layout contract changes.

## Current Primary Files

| Area | File |
|---|---|
| Master layout | `Modules/Admin/resources/views/layouts/master.blade.php` |
| Header view | `Modules/Admin/resources/views/livewire/partials/header.blade.php` |
| Sidebar view | `Modules/Admin/resources/views/livewire/partials/sidebar.blade.php` |
| Header search | `Modules/Admin/resources/views/livewire/partials/header-search.blade.php` |
| Header notifications | `Modules/Admin/resources/views/livewire/partials/header-notifications.blade.php` |
| Header user menu | `Modules/Admin/resources/views/livewire/partials/header-user.blade.php` |
| Toast component | `Modules/Admin/resources/views/components/toast.blade.php` |
| Icon component | `Modules/Admin/resources/views/components/icon.blade.php` |
| Sidebar service | `Modules/Admin/Services/SidebarService.php` |
| Header menu service | `Modules/Admin/Services/HeaderMenuService.php` |
| Theme manager | `Modules/Admin/Support/ThemeManager.php` |
| Sidebar config | `Modules/Admin/config/sidebar.php` |

