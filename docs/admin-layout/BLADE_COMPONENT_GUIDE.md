# Blade Component Guide

## Purpose

Blade components should make the admin layout easier to read, safer to change, and visually consistent. They should not hide business logic or authorization decisions.

## Naming

Use clear names under the Admin module namespace:

| Component | Name |
|---|---|
| Navigation item | `x-admin::nav.item` |
| Navigation group | `x-admin::nav.group` |
| Page header | `x-admin::page-header` |
| Button | `x-admin::button` |
| Icon button | `x-admin::icon-button` |
| Dropdown | `x-admin::dropdown` |
| Toast stack | `x-admin::toast-stack` |
| Empty state | `x-admin::empty-state` |

Anonymous component files should use kebab-case.

## Folder Structure

Target structure:

```text
Modules/Admin/resources/views/components/
├── layout/
│   ├── shell.blade.php
│   ├── header.blade.php
│   └── sidebar.blade.php
├── nav/
│   ├── item.blade.php
│   └── group.blade.php
├── feedback/
│   ├── toast-stack.blade.php
│   └── alert.blade.php
├── overlays/
│   ├── modal.blade.php
│   └── drawer.blade.php
└── forms/
    ├── input.blade.php
    └── field.blade.php
```

## Props

Rules:

- Props should be explicit.
- Booleans should default safely.
- Do not pass whole Eloquent models unless the component is model-specific.
- Prefer arrays/DTOs for navigation and layout data.

Example prop contract:

```php
@props([
    'href' => '#',
    'icon' => null,
    'active' => false,
    'collapsed' => false,
    'label',
])
```

## Slots

Use slots for flexible content:

- `title`
- `subtitle`
- `actions`
- `footer`
- `trigger`
- `content`

Avoid too many named slots in a single component. If a component needs many slots, split it.

## Reusable Design

A component should own:

- Markup structure.
- Visual variants.
- Accessibility attributes.
- Stable dimensions.
- Loading/disabled states when relevant.

A component should not own:

- Database queries.
- Authorization checks.
- Route construction for business actions.
- Permission names.

## Variants

Supported variants should be finite and documented:

- `primary`
- `secondary`
- `ghost`
- `danger`
- `success`
- `warning`
- `info`

Do not allow arbitrary classes from user data.

## Current Components

| Component | Notes |
|---|---|
| `x-toast` | Useful global feedback stack; needs accessibility and script extraction. |
| `x-icon` | Needs normalization to avoid nested SVG and improve maintainability. |
| `x-menu-item` | Exists and should be reviewed before creating a new nav item. |
| `x-image-upload`, `x-gallery`, `x-editor` | Page-level form components, not layout shell components. |

## Testing Components

Before merging component changes:

- Render active/inactive states.
- Render disabled/loading states where supported.
- Test with long labels.
- Test keyboard focus.
- Test mobile width.
- Test dark/theme variants.

