# Responsive Strategy

## Breakpoints

Use Tailwind defaults unless a future design-system decision changes them.

| Range | Breakpoint | Layout Name |
|---|---:|---|
| `< 640px` | default | Mobile |
| `>= 640px` | `sm` | Large mobile |
| `>= 768px` | `md` | Tablet |
| `>= 1024px` | `lg` | Laptop |
| `>= 1280px` | `xl` | Desktop |
| `>= 1536px` | `2xl` | Wide desktop |

The current shell uses `1024px` as the desktop threshold through `window.innerWidth >= 1024`.

## Desktop

| Area | Rule |
|---|---|
| Sidebar | Fixed, visible, expanded by default. |
| Collapsed sidebar | Width around 80px, icon-only with accessible tooltips. |
| Header | Sticky, full width after sidebar margin. |
| Content | Max width configurable, default `max-w-7xl`. |
| Tables | Full feature tables with sticky headers where useful. |

## Laptop

| Area | Rule |
|---|---|
| Sidebar | Collapsible to preserve horizontal space. |
| Header | Search remains visible if there is enough width. |
| Toolbars | Wrap secondary actions before shrinking labels. |
| Forms | Two-column forms where fields are related and readable. |

## Tablet

| Area | Rule |
|---|---|
| Sidebar | Off-canvas drawer by default. |
| Header | Mobile toggle visible; search can become icon trigger. |
| Tables | Horizontal scroll or card/table hybrid. |
| Forms | Single column except short paired controls. |
| Drawers | Use at most 90vw width. |

## Mobile

| Area | Rule |
|---|---|
| Sidebar | Hidden off-canvas, opens over content with backdrop. |
| Header | Compact, fixed height, no horizontal overflow. |
| Search | Use overlay/command palette trigger instead of hidden-only search. |
| Actions | Primary action visible, secondary actions in menu. |
| Tables | Prefer cards, stacked rows, or horizontal scroll with visible affordance. |
| Touch targets | Minimum 44px height/width for interactive controls. |

## Sidebar Behavior

| State | Desktop | Mobile |
|---|---|---|
| Expanded | `w-64`, content margin matches width. | Drawer visible, backdrop shown. |
| Collapsed | `w-20`, icon-only nav. | Not applicable; closed means off-canvas. |
| Closed | Avoid full close on desktop unless layout preset allows. | Translated off-screen. |
| Active group | Expanded if current route is inside group. | Expanded after drawer opens. |

## Drawer Behavior

Drawers should:

- Use a backdrop.
- Close on Escape.
- Close on backdrop click when there are no unsaved changes.
- Trap focus.
- Restore focus to the trigger.
- Respect mobile safe areas.

## Header

Header rules:

- Keep height stable across breakpoints.
- Do not let search, user name, or badges resize the header.
- Hide non-critical labels before hiding controls.
- Keep notification and user menu reachable on all breakpoints.

## Tables

| Screen | Strategy |
|---|---|
| Desktop | Full table, sortable headers, filters above table. |
| Laptop | Full table with column hiding only for low-priority columns. |
| Tablet | Horizontal scroll or compact columns. |
| Mobile | Stacked cards for business workflows; horizontal scroll only for admin power screens. |

## Cards

Cards should not be nested. Repeated list items may be cards; page sections should be full-width content bands or normal containers.

Mobile cards should:

- Keep action buttons visible.
- Avoid long unbroken labels.
- Use clear hierarchy: title, metadata, status, actions.

## Forms

Rules:

- Single column on mobile.
- Related short fields may share a row from `md` upward.
- Labels stay visible.
- Validation errors render directly below fields.
- Footer actions become sticky only when forms are long.

## Grid System

Use CSS grid with explicit responsive tracks:

| Pattern | Recommended Classes |
|---|---|
| Metrics | `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4` |
| Forms | `grid-cols-1 md:grid-cols-2` |
| Dense settings | `grid-cols-1 lg:grid-cols-3` |
| Content/sidebar | `grid-cols-1 xl:grid-cols-[1fr_320px]` |

## Landscape Mode

For mobile landscape:

- Avoid full-height modal content without internal scrolling.
- Keep drawer width capped.
- Ensure header does not consume excessive vertical space.

## Safe Areas

Use safe-area padding for fixed mobile elements when supported:

- Top header.
- Bottom action bars.
- Drawers.
- Toast stack.

