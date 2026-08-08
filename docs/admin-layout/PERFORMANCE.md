# Performance

## Goals

- Keep the shell fast on every admin page.
- Avoid repeated authorization and settings queries during rendering.
- Limit Livewire hydration to components that need interaction.
- Keep global scripts centralized and idempotent.
- Cache stable layout data with targeted invalidation.

## Render Optimization

Current render cost comes from:

- Master layout reading settings in the head.
- Livewire sidebar mounting and loading menus.
- Livewire header widgets mounting.
- Blade permission checks inside sidebar loops.

Target:

- Build a `LayoutContext` once per request.
- Cache stable settings.
- Pre-filter authorized navigation in a service.
- Render static shell parts with Blade components when Livewire is not needed.

## DOM Optimization

Rules:

- Keep sidebar DOM to authorized items only.
- Do not render hidden duplicate mobile and desktop navigation trees unless necessary.
- Use one global toast stack.
- Use one modal and drawer host.
- Avoid nested SVG output in icon components.

## Asset Loading

Current:

- Vite loads `resources/css/tailwind.css` and `resources/js/tailwind.js`.
- Livewire assets are loaded in the layout.
- Some scripts are inline inside Blade components.

Target:

- Move reusable JavaScript into Vite-managed modules.
- Keep inline scripts only for tiny server-provided config values.
- Defer non-critical widgets such as charts and heavy search suggestions.
- Avoid loading page-specific scripts globally.

## Lazy Loading

Use Livewire lazy loading for:

- Notification dropdown contents.
- Search suggestions.
- Heavy dashboard widgets.
- Charts.
- Large filter metadata.

Do not lazy-load essential navigation if it causes visible shell shift.

## Deferred Scripts

Global behaviors that should be centralized:

- Sidebar persistence.
- Keyboard shortcut for search.
- Toast manager.
- Dropdown focus helpers.
- Drawer/modal focus trap.

## CSS Strategy

Rules:

- Prefer semantic Blade component variants over repeated long utility strings.
- Keep theme class generation explicit so Tailwind can see classes during build.
- Avoid user-generated arbitrary class names.
- Document all theme tokens.

## Caching

| Data | Current | Target |
|---|---|---|
| Sidebar menu | `admin.menus` for 3600 seconds | Same idea, targeted invalidation on menu writes. |
| Header menu | `menu_tree_{location}` | Same key pattern, no global cache flush. |
| Settings | Direct reads from `Setting::getValue()` | Cached settings repository with invalidation. |
| Theme config | Read from file in `ThemeManager` | Config repository or cached token map. |
| Permissions | Checked in Blade loop | Pre-computed allowed menu tree per user/role cache where safe. |

## Livewire Optimization

Rules:

- Use Livewire only for stateful widgets.
- Use typed public properties.
- Keep payloads small.
- Avoid large arrays when the view can use small DTOs.
- Prefer events with documented names.
- Avoid polling unless necessary.
- Use pagination for lists.

## Current Performance Risks

| Risk | Fix |
|---|---|
| `Cache::flush()` in menu reorder | Replace with targeted menu cache invalidation. |
| Permission checks in sidebar Blade | Move to service and cache authorized tree when possible. |
| Inline shortcut listener in search view | Move to one idempotent JS module. |
| Static notification button mounted as Livewire | Convert to Blade until dynamic notifications are implemented. |

## Verification

Before merging layout work:

- Run frontend build.
- Check Livewire payload size for header/sidebar.
- Inspect query count for first admin page load.
- Test with a large menu tree.
- Test with a user that has limited permissions.
- Test `wire:navigate` if enabled on admin links.

