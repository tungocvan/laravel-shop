# Layout Analysis

## Summary

The current admin layout is a compact shell built around `master.blade.php`. It uses Alpine for local sidebar state, Livewire for header and sidebar partials, Tailwind CSS utility classes for visual structure, and anonymous Blade components for toast and icons.

The implementation is functional and easy to inspect, but several concerns are mixed together: layout state, navigation authorization, active-route detection, theming, component markup, global JavaScript listeners, and shell composition. This makes the layout fast to iterate on but risky to scale.

## Current Layout

| Layer | Current Behavior |
|---|---|
| Document shell | Defines HTML, head metadata, favicon, title, scripts, Vite assets, Livewire styles/scripts. |
| State | Alpine `sidebarOpen` and `isDesktop` are initialized from `window.innerWidth`. |
| Sidebar | Fixed positioned panel, open at `w-64`, collapsed at `lg:w-20`, hidden off-canvas on mobile. |
| Header | Sticky top bar with mobile sidebar button, search, notifications, and user menu. |
| Content | Scrollable main area with `max-w-7xl`, padding, and slot/yield support. |
| Toasts | Global Alpine toast manager listening to `window.notify`. |

## Strengths

| Strength | Evidence |
|---|---|
| Simple entry point | `master.blade.php` clearly shows sidebar, header, content, and toast composition. |
| Slot and section compatible | Supports both `{{ $slot }}` and `@yield('content')`. |
| Livewire partials are focused by area | Header, search, notifications, user menu, and sidebar have separate components. |
| Navigation is cached | `SidebarService` caches menu tree with `admin.menus`. |
| Theme fallback exists | `ThemeManager` merges configured theme classes with default values. |
| Basic mobile overlay exists | Mobile sidebar gets a backdrop and click-away close behavior. |
| Admin guard is used in user menu | `HeaderUser` reads `Auth::guard('admin')->user()`. |

## Weaknesses

| Weakness | Impact |
|---|---|
| Layout state is duplicated | Alpine controls visible sidebar state while `Sidebar` Livewire also has `$sidebarOpen` and `toggleSidebar()`. |
| Authorization is performed in Blade | Sidebar filters `can` values inside the view. This hurts testability and repeats policy logic in rendering. |
| Active-route logic is embedded in markup | Route matching in the sidebar view increases Blade complexity. |
| Icon component mixes SVG path and full SVG markup | Some icon cases return full `<svg>` while the component wraps the result in another `<svg>`. |
| Header search injects a global script | Keyboard shortcut registration is placed directly in a component view. |
| Toast manager is globally scoped | `window.__toast_initialized` and `window.notify` are practical but undocumented contracts. |
| Theme keys are partly unused | `hover`, `active_bg`, `active_text`, and child theme keys exist but sidebar markup hard-codes some colors. |
| No explicit layout config object | Sidebar width, container width, sticky behavior, compact mode, dark mode, and persistence are spread across files. |

## Technical Debt

| Debt | Severity | Recommended Direction |
|---|---:|---|
| Blade view contains navigation policy and active-state logic | High | Move to navigation DTO/service layer. |
| Mixed Tailwind and installed Bootstrap/AdminLTE stack lacks clear ownership | High | Define a layout design system and allowed utility patterns. |
| Global scripts live inside component views | Medium | Move to Vite entry or a layout script module. |
| Collapsed sidebar has no tooltip or accessible label strategy | Medium | Add icon-only navigation labels and focus behavior. |
| Mobile search is hidden with no alternative search trigger | Medium | Add search overlay or mobile search button. |
| No documented menu cache invalidation policy for category menu changes | Medium | Centralize invalidation in menu write services. |
| No formal test checklist | Medium | Add layout snapshot, Livewire, and browser checks. |

## Maintainability

The current layout is maintainable for small edits because files are short and visible. It becomes harder to maintain when adding:

- More sidebar states.
- Role-based layout variants.
- Dark mode.
- Multi-level navigation.
- Mobile search overlays.
- Modal, drawer, and toast stacks.
- Page headers and toolbars.

The main maintainability issue is that rendering code is also deciding authorization, active state, URL behavior, and responsive state.

## Scalability

The layout can scale if it is split into contracts:

| Contract | Target Owner |
|---|---|
| Navigation tree | `NavigationService` or `SidebarService` returning DTOs. |
| Authorization pruning | Service layer or policy-aware menu builder. |
| Theme tokens | Config plus theme manager. |
| Shell state | Alpine store persisted through session/local storage. |
| Header slots | Blade components and Livewire widgets. |
| Page chrome | Reusable page header, toolbar, breadcrumbs, and actions. |

## Blade Quality

The Blade files are readable but too procedural in the sidebar. The target should be:

- Thin layout files.
- Small anonymous Blade components for repeated UI pieces.
- Named slots for page chrome.
- Minimal `@php` blocks.
- Service-prepared arrays or DTOs instead of inline transformation.

## Tailwind Quality

The Tailwind usage is direct and effective. Problems to address:

- Hard-coded color classes bypass theme config.
- Border radius is inconsistent: `rounded-xl`, `rounded-2xl`, `rounded-md`, and `rounded-lg`.
- Some compact admin elements use generous spacing better suited to marketing UI.
- There is no token document for spacing, typography, shadow, or interactive states.

## Responsive Quality

Current responsive behavior covers the primary sidebar case:

- Desktop: fixed sidebar, content margin changes.
- Mobile: off-canvas sidebar with overlay.

Missing responsive contracts:

- Mobile search.
- Mobile user menu sizing.
- Touch target minimums.
- Table overflow rules.
- Page header collapse rules.
- Safe-area padding.
- Landscape mode behavior.

## Accessibility

Positive:

- Header notification and user menu buttons have some screen-reader text.
- User menu declares `aria-haspopup`.

Issues:

- Sidebar toggle buttons need labels and expanded state.
- Sidebar navigation needs semantic labels.
- Collapsed sidebar needs accessible names and tooltips.
- Dropdown focus is not trapped or restored.
- Toasts need live region semantics.
- SVG-only buttons need consistent `aria-label`.
- Active navigation state should use `aria-current="page"`.

## Performance

Positive:

- Sidebar menu is cached.
- Header components are small.
- Vite is used for assets.

Risks:

- Header and sidebar are Livewire components even when mostly static.
- Inline scripts can duplicate listeners with navigation.
- `Cache::flush()` in `HeaderMenuService::reorderItems()` invalidates more than menu cache.
- Blade authorization checks can call permission logic repeatedly during rendering.

## UI Consistency

The current UI has a modern shell, but consistency will drift without design rules. Future work should standardize:

- Sidebar item height.
- Icon size.
- Radius scale.
- Button states.
- Dropdown panels.
- Page headers.
- Empty states.
- Loading and skeleton states.
- Form and table density.

