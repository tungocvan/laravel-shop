# Accessibility

## Target Standard

The admin layout should target WCAG 2.2 AA. Accessibility is part of the layout contract because the shell contains navigation, search, dropdowns, drawers, modals, notifications, and persistent controls.

## Semantic HTML

Required landmarks:

| Region | Element |
|---|---|
| Sidebar | `aside` |
| Primary navigation | `nav aria-label="Admin navigation"` |
| Header | `header` |
| Main content | `main id="admin-main"` |
| Footer | `footer` when enabled |

Add a skip link before the shell:

- Target: `#admin-main`.
- Visible on focus.

## ARIA

| Component | Requirement |
|---|---|
| Sidebar toggle | `aria-label`, `aria-expanded`, `aria-controls`. |
| Mobile drawer | `role="dialog"` or semantic drawer pattern when overlaying content. |
| Active nav item | `aria-current="page"`. |
| Dropdown trigger | `aria-haspopup="menu"`, accurate `aria-expanded`. |
| Dropdown menu | Menu semantics when using menu behavior, or plain list semantics for simple link lists. |
| Toast stack | `aria-live="polite"` for normal messages, `assertive` for critical errors. |
| Modal | `role="dialog"`, `aria-modal="true"`, labelled by visible title. |

## Keyboard Navigation

Required behavior:

- Tab reaches every visible interactive control.
- Shift+Tab order is logical.
- Escape closes dropdowns, drawers, modals, and search overlay.
- Enter and Space activate buttons.
- Arrow keys may be supported in menus, but must be consistent if introduced.
- Focus returns to the trigger after closing overlays.

## Focus Management

| Component | Focus Rule |
|---|---|
| Sidebar drawer | Move focus into drawer on open; restore to toggle on close. |
| User menu | Move focus to first menu item when opened by keyboard. |
| Modal | Trap focus inside modal. |
| Toast action | Focusable only when action exists; do not steal focus for passive toasts. |
| Search overlay | Autofocus input on open. |

## Contrast

Minimum targets:

- Normal text: 4.5:1.
- Large text: 3:1.
- Icon-only controls: 3:1 against adjacent colors.
- Focus indicator: visible against both control and page background.

Theme variants must be checked independently.

## Screen Reader Support

Rules:

- Icon-only buttons need `aria-label` or visible text.
- Loading states need readable status text when content is delayed.
- Badge counts need labels such as "3 unread notifications".
- Collapsed sidebar labels must remain available to assistive technology.

## Current Accessibility Gaps

| Gap | Recommendation |
|---|---|
| Sidebar toggle buttons lack explicit labels | Add `aria-label` and `aria-expanded`. |
| Sidebar active links lack `aria-current` | Add on active item. |
| Collapsed sidebar hides text with `x-show` | Ensure hidden labels remain accessible or provide `aria-label`. |
| Header search shortcut displays `⌘K` while placeholder says Ctrl+K | Normalize shortcut copy and accessible hint. |
| Toast stack lacks live region attributes | Add `aria-live` and role semantics. |
| Dropdown does not manage focus | Add focus management and Escape handling. |

## Reduced Motion

Respect `prefers-reduced-motion`. Sidebar, dropdown, toast, and drawer animations should reduce duration or disable transform-heavy transitions.

