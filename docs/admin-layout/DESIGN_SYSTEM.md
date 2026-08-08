# Design System

## Principles

The admin UI is a work-focused SaaS interface. It should be dense enough for repeated operations, calm enough for long sessions, and consistent enough that every module feels like part of the same product.

## Typography

| Use | Size | Weight | Notes |
|---|---:|---:|---|
| Page title | `text-2xl` | `font-semibold` | Use once per page. |
| Section title | `text-lg` | `font-semibold` | For major content groups. |
| Card title | `text-sm` or `text-base` | `font-semibold` | Keep compact. |
| Body | `text-sm` | normal | Default admin text. |
| Metadata | `text-xs` | normal/medium | Secondary labels, timestamps. |
| Table text | `text-sm` | normal | Use `text-xs` only for dense secondary data. |

Do not scale font size with viewport width. Letter spacing should remain normal unless a specific component requires uppercase labels.

## Spacing

| Token | Value | Use |
|---|---:|---|
| `1` | 4px | Tight icon gaps. |
| `2` | 8px | Compact item gaps. |
| `3` | 12px | Form and button internal gaps. |
| `4` | 16px | Default content spacing. |
| `6` | 24px | Section spacing. |
| `8` | 32px | Major page spacing. |

Admin pages should avoid large marketing-style vertical gaps.

## Colors

Use semantic roles:

| Role | Purpose |
|---|---|
| Surface base | Page background. |
| Surface raised | Header, dropdowns, modals, cards. |
| Text primary | Important content. |
| Text secondary | Supporting content. |
| Text muted | Disabled or low-emphasis content. |
| Border subtle | Default separators. |
| Accent | Primary actions and active navigation. |
| Danger | Destructive actions and errors. |
| Warning | Risk or pending states. |
| Success | Completed states. |
| Info | Neutral informational states. |

Avoid a one-note palette. Sidebar themes may use blue, slate, orange, or dark variants, but the full admin UI should keep status colors distinct.

## Radius

| Token | Value | Use |
|---|---:|---|
| `sm` | 4px | Inputs inside dense tables. |
| `md` | 6px | Dropdown items and compact controls. |
| `lg` | 8px | Cards, buttons, inputs, panels. |
| `xl` | 12px | Toasts and larger overlays only when needed. |

Default cards should use 8px radius or less.

## Shadows

Use shadows sparingly:

| Level | Use |
|---|---|
| None | Default page sections and tables. |
| Subtle | Header, dropdowns, floating controls. |
| Medium | Modals and drawers. |
| Strong | Toasts and critical overlays only. |

## Buttons

Required variants:

- Primary.
- Secondary.
- Ghost.
- Danger.
- Icon-only.
- Link-style.

Rules:

- Icon-only buttons require accessible names and tooltips for unfamiliar controls.
- Destructive buttons must be visually distinct and require confirmation for irreversible actions.
- Button height should remain stable.

## Forms

Rules:

- Labels are visible.
- Required fields are indicated consistently.
- Help text is below label or below input.
- Validation error appears below the field.
- Disabled state is visually clear.
- Use field groups for related settings.

## Tables

Required states:

- Loading.
- Empty.
- Error.
- Filtered empty.
- Paginated.
- Bulk selection.

Rules:

- Use consistent row height.
- Align numeric values right when comparison matters.
- Keep action columns compact.
- Use status badges rather than colored text alone.

## Cards

Cards are for repeated items, summaries, modal bodies, and framed tools. Do not put cards inside cards. Do not make page sections floating card stacks unless the screen is truly a dashboard.

## Badges

Badge variants:

- Neutral.
- Success.
- Warning.
- Danger.
- Info.
- Accent.

Badges must not rely on color only. Text must clearly name the state.

## Alerts

Use alerts for persistent page-level messages. Use toasts for transient operation feedback.

Alert variants:

- Info.
- Success.
- Warning.
- Danger.

## Dropdowns

Dropdown requirements:

- Keyboard reachable.
- Escape closes.
- Click outside closes.
- Focus is restored.
- Menu items have roles where appropriate.

## Navigation

Sidebar item requirements:

- Icon, label, active state.
- `aria-current` for current page.
- Tooltip or accessible label in collapsed state.
- Permission-aware rendering.
- Open group state for active child routes.

## Icons

Use a consistent icon source. The current `x-icon` component uses hand-defined Heroicons-like paths. A future refactor should normalize icon definitions and avoid nested SVG output.

## Charts

Charts should:

- Include text summaries.
- Use accessible colors.
- Avoid relying on hover-only data.
- Use loading skeletons.
- Defer heavy chart scripts.

## Empty States

Empty states should include:

- Short title.
- Clear explanation.
- Primary action when the user can fix the state.
- Permission-aware actions.

## Loading States

Use skeletons for content lists and tables. Use spinners only for short inline actions.

## Responsive Rules

Every fixed-format element must have stable dimensions:

- Boards.
- Toolbars.
- Icon buttons.
- Counters.
- Table action cells.
- Sidebar items.

