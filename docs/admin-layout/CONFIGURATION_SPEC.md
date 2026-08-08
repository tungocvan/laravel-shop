# Configuration Spec

## Goal

The admin layout needs one explicit configuration contract. Today, sidebar themes live in `Modules/Admin/config/sidebar.php`, layout behavior lives in Blade/Alpine, menu data lives in database-backed services, and user choices live partly in session.

The target is `config/admin.php` or `Modules/Admin/config/admin.php` with documented ownership for static defaults, user preferences, cached data, and runtime state.

## Configuration Locations

| Location | Use |
|---|---|
| `Modules/Admin/config/admin.php` | Static layout defaults and presets. |
| `Modules/Admin/config/sidebar.php` | Existing theme source during migration; eventually merge into admin config. |
| Database | Menus, user-specific preferences, role layout overrides, notification settings. |
| Cache | Prepared menu trees, theme token maps, stable settings. |
| Session | Temporary user runtime choices such as selected theme before persistence. |
| Service classes | Validation, normalization, fallback, and view model construction. |

## Proposed `admin.php` Shape

```php
return [
    'layout' => [
        'preset' => 'default',
        'container' => '7xl',
        'density' => 'comfortable',
        'sticky_header' => true,
        'show_footer' => false,
    ],
    'sidebar' => [
        'enabled' => true,
        'expanded_width' => '16rem',
        'collapsed_width' => '5rem',
        'desktop_collapsible' => true,
        'mobile_drawer' => true,
        'persist_state' => true,
    ],
    'header' => [
        'height' => '4rem',
        'search' => true,
        'notifications' => true,
        'theme_switcher' => true,
        'user_menu' => true,
    ],
    'theme' => [
        'default' => 'corporate-blue',
        'dark_mode' => 'class',
        'accent' => 'blue',
    ],
    'navigation' => [
        'cache_ttl' => 3600,
        'active_strategy' => 'url-prefix',
        'max_depth' => 2,
    ],
];
```

This example is a specification, not an instruction to add code in this documentation task.

## Configurable Options

### Sidebar

| Option | Type | Default | Owner |
|---|---|---:|---|
| `enabled` | bool | true | Config |
| `expanded_width` | string | `16rem` | Config |
| `collapsed_width` | string | `5rem` | Config |
| `desktop_collapsible` | bool | true | Config |
| `mobile_drawer` | bool | true | Config |
| `persist_state` | bool | true | Config/user preference |
| `show_footer_profile` | bool | true | Config |
| `max_depth` | int | 2 | Config/navigation service |

### Header

| Option | Type | Default | Owner |
|---|---|---:|---|
| `height` | string | `4rem` | Config |
| `sticky` | bool | true | Config |
| `blurred_background` | bool | true | Config/theme |
| `search_enabled` | bool | true | Config/permission |
| `notifications_enabled` | bool | true | Config/permission |
| `user_menu_enabled` | bool | true | Config |
| `mobile_search_mode` | enum | `overlay` | Config |

### Theme

| Option | Type | Default | Owner |
|---|---|---:|---|
| `default` | string | `corporate-blue` | Config |
| `available` | array | existing themes | Config |
| `dark_mode` | enum | `class` | Config |
| `accent_color` | string | `blue` | Config/user preference |
| `allow_user_theme` | bool | true | Config |
| `persist_user_theme` | enum | `database` | Config |

### Container Width

| Value | Meaning |
|---|---|
| `full` | No max width; useful for data-heavy screens. |
| `7xl` | Current default and safe general purpose width. |
| `screen-2xl` | Wide admin dashboards. |
| `narrow` | Forms and settings pages. |

### Animations

| Option | Requirement |
|---|---|
| `enabled` | Respect `prefers-reduced-motion`. |
| `duration` | Keep shell transitions between 150ms and 300ms. |
| `collapse` | Sidebar collapse must not shift focus unexpectedly. |

### Compact Mode

Compact mode should affect:

- Sidebar item height.
- Header spacing.
- Table row height.
- Form vertical spacing.
- Button padding.

It should not reduce touch targets below 44px on touch screens.

### RTL

RTL is not currently implemented. If required:

- Sidebar placement must flip.
- Icon chevrons must flip.
- Breadcrumb direction must flip.
- Drawer placement must be configurable.

### Breadcrumb

| Option | Default |
|---|---|
| `enabled` | true |
| `show_home` | true |
| `max_items` | 4 |
| `collapse_mobile` | true |

### Footer

| Option | Default |
|---|---|
| `enabled` | false |
| `show_version` | true |
| `show_environment` | local only |

### Layout Presets

| Preset | Behavior |
|---|---|
| `default` | Sidebar, sticky header, constrained content. |
| `data-heavy` | Collapsed sidebar default, full-width content. |
| `focus` | No sidebar, minimal header, narrow content. |
| `settings` | Sidebar plus settings sub-navigation. |

### Role-Based Layout

Role-based layout should only change visibility of layout regions and menu entries. It must not replace authorization checks in routes, policies, controllers, or Livewire actions.

## Persistence Strategy

| State | Recommended Persistence |
|---|---|
| Sidebar collapsed | User preference in database; session fallback. |
| Active theme | User preference in database; session fallback. |
| Last search query | Browser session only. |
| Open menu groups | Browser local storage or Alpine persisted state. |
| Menu tree | Cache with targeted invalidation. |

