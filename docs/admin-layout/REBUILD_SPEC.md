# Rebuild Specification

## Objective

Rebuild the Admin Layout as a stable, accessible, configurable shell while preserving existing admin page compatibility.

## Non-Goals

- Do not rewrite business modules.
- Do not change route names unless separately planned.
- Do not replace authorization with UI hiding.
- Do not migrate frontend framework versions during the layout rebuild.

## Required Layouts

| Layout | Requirement |
|---|---|
| Master | Authenticated admin shell with sidebar, header, content, stacks. |
| Auth | Login screens without admin chrome. |
| Blank | Print/invoice/export previews. |
| Error | Admin-styled error pages. |

## Master Layout Contract

The master layout must support:

- `@section('title')`
- `@section('css')`
- `@section('js')`
- `@stack('styles')`
- `@stack('scripts')`
- `{{ $slot }}`
- `@yield('content')`
- Livewire styles/scripts.
- Vite assets.

## Shell Requirements

| Area | Requirement |
|---|---|
| Sidebar | Desktop expanded/collapsed, mobile drawer, authorized items only. |
| Header | Sticky, responsive, search, notifications, user menu. |
| Main | Scrollable, configurable container, skip-link target. |
| Toasts | Global stack, accessible live regions. |
| Modals | Global stack with focus management. |
| Drawers | Global stack with focus management. |

## Navigation Requirements

Navigation builder must:

- Load active menu records.
- Normalize URLs.
- Support route or URL targets.
- Prune unauthorized items before rendering.
- Calculate active item and open groups.
- Support two levels initially.
- Expose icon names, labels, URLs, badges, active state, and disabled state.
- Cache safely and invalidate on menu changes.

## Theme Requirements

Theme layer must:

- Provide defaults.
- Support configured themes.
- Support session and user preference fallback.
- Use semantic tokens.
- Validate available theme names.
- Avoid arbitrary user-provided classes.

## Header Requirements

Header must include:

- Mobile sidebar toggle.
- Search or command palette trigger.
- Notification trigger.
- User menu.
- Optional theme switcher.

User menu must include:

- Profile link.
- Admin-location header menu items.
- Logout.

## Responsive Requirements

- `lg` and above: sidebar visible.
- Below `lg`: sidebar is drawer.
- Content must not overflow horizontally.
- Header controls must remain reachable on mobile.
- Tables and forms must have documented responsive patterns.

## Accessibility Requirements

- Skip link.
- Landmarks.
- Visible focus.
- Keyboard support.
- ARIA labels and expanded state.
- Focus trap for modal/drawer.
- `aria-current` for active navigation.
- Toast live regions.

## Performance Requirements

- No unbounded layout queries.
- Cached menu and settings.
- Targeted cache invalidation.
- Livewire only where needed.
- No duplicate global listeners.
- Build assets through Vite.

## Acceptance Criteria

| Area | Criteria |
|---|---|
| Compatibility | Existing admin pages render with no template changes. |
| Navigation | Users see only authorized menu entries. |
| Active state | Current page is highlighted correctly. |
| Mobile | Sidebar drawer opens, closes, and traps focus. |
| Accessibility | Keyboard-only user can navigate shell. |
| Performance | Layout does not introduce query or payload regressions. |
| Documentation | Docs updated with any changed contracts. |

