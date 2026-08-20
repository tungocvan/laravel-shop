# ClientPortal — PWA Admin Settings

Updated: 2026-08-20

## Scope

PWA configuration is owned by `Modules/ClientPortal`. Admin can manage ClientPortal presentation without editing Blade source, while authentication routes, guards, permission names and application business capabilities remain source-controlled.

## Admin route

```text
/admin/client-apps/pwa
```

Current authorization reuses the existing ClientPortal Admin management capability:

```text
auth:admin
permission:edit_role,admin
```

A dedicated `client-apps.pwa-settings.manage` Admin capability can be introduced in a later permission-hardening phase without changing the settings storage contract.

## Storage

Table:

```text
client_portal_settings
```

Important columns:

```text
group_name
key
value
type
updated_by
```

Uniqueness is scoped by:

```text
(group_name, key)
```

This allows future groups such as `pwa.launcher` and `applications.muasamcong` to reuse common keys like `title` or `description` safely.

## Defaults and overrides

Defaults live in:

```text
Modules/ClientPortal/config/pwa.php
```

Runtime overrides are read through:

```text
Modules\ClientPortal\Services\ClientPortalSettingsService
```

Flow:

```text
ClientPortal config defaults
        ↓
client_portal_settings Admin overrides
        ↓
ClientPortalSettingsService
        ↓
Controller
        ↓
Blade
```

Blade must not query settings directly from the database.

If the settings table does not exist yet, the service falls back to module config so the PWA login remains available before migration or during deployment transitions.

## Current groups

### `pwa.general`

Admin-editable in MR-1:

- application name;
- short name;
- browser title;
- Apple web app title;
- theme color;
- background color.

Security/routing values such as `start_url` and `display` remain source-controlled defaults for now. They should not become arbitrary Admin routing inputs without an explicit contract.

### `pwa.login`

Admin can configure:

- badge;
- main heading;
- description;
- show/hide desktop intro panel;
- website-back label;
- browser mode label;
- installed-PWA mode label;
- login feature/introduction cards;
- enable/disable each feature card.

The login Blade renders these values dynamically. Authentication itself remains the existing `web` guard Livewire login flow.

## Non-hardcode rule

User-facing configurable PWA copy must be read through `ClientPortalSettingsService`. Blade may contain layout/CSS/accessibility structure, but editable branding/copy must not be duplicated as literals in the login template.

Defaults are defined once in module config to provide a safe first-run state. Admin overrides then replace them without source changes.

## Boundaries

Admin settings may control presentation, but must not directly rewrite:

- authentication guard;
- controller class;
- arbitrary route names/URLs;
- permission names;
- source-module dependency;
- business capability implementation.

These remain controlled by manifests/routes/source code.

## Next phases

Planned extensions using the same settings service/storage contract:

1. `pwa.launcher` — `/my-apps` title, empty state, layout and presentation.
2. application/feature presentation overrides — labels, descriptions, icons, sort order, visibility and maintenance badges while preserving manifest permission contracts.
3. dynamic manifest presentation — selected safe manifest properties generated from ClientPortal settings while preserving `/my-apps` as the security-reviewed entry contract.
4. dedicated Admin capability for PWA/presentation management.

## Verification

Focused tests:

```bash
php artisan test tests/Feature/ClientApps/ClientPortalPwaSettingsTest.php
php artisan test tests/Feature/ClientApps/ClientPwaFoundationTest.php
php artisan test tests/Feature/ClientApps/ClientApplicationAdminTest.php
```

Then module regression:

```bash
php artisan test tests/Feature/ClientApps
```

Manual checks:

- `/admin/client-apps/pwa` loads for authorized Admin;
- save General settings and refresh;
- save Login content and refresh;
- `/my-apps/login` displays updated content;
- disabled feature card is not rendered;
- disabling desktop intro panel leaves the login form usable;
- invalid hex colors are rejected;
- Client login still authenticates guard `web`;
- successful login still redirects to `/my-apps`;
- Admin login/logout behavior is unchanged.
