# ClientPortal — PWA Admin Settings

Updated: 2026-08-20

## Scope

PWA configuration is owned by `Modules/ClientPortal`. Admin can manage ClientPortal presentation without editing Blade source, while authentication routes, guards, permission names and application business capabilities remain source-controlled.

## Admin routes

```text
/admin/client-apps/pwa
/admin/client-apps/pwa/launcher
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

This allows groups such as `pwa.launcher` and `application.muasamcong.presentation` to reuse common keys like `name` or `description` safely.

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
ClientPortal config defaults / application manifest
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

If the settings table does not exist yet, the service falls back to module config or manifest values so ClientPortal remains available during deployment transitions.

## Current groups

### `pwa.general`

Admin can configure:

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

### `pwa.launcher`

MR-2 adds presentation settings for `/my-apps`:

- browser title;
- brand title/subtitle;
- workspace label;
- launcher heading and description;
- install button label;
- logout button label;
- application-card CTA label;
- empty-state title and description;
- show/hide source-module label on application cards.

### `application.{key}.presentation`

Each application adapter can receive a presentation-only override:

- `enabled` — show/hide the application card on `/my-apps`;
- `name` — display label;
- `description` — display copy;
- `sort_order` — launcher ordering.

Defaults come from the application manifest. The override service intentionally does not change:

- `route`;
- `permission`;
- `source_module`;
- feature/action permission contracts.

Hiding an application card is a presentation decision, not a replacement for permission enforcement. Application routes continue to enforce their existing access middleware.

## Non-hardcode rule

User-facing configurable PWA copy must be read through `ClientPortalSettingsService`. Blade may contain layout/CSS/accessibility structure, but editable branding/copy must not be duplicated as literals in login or launcher templates.

Defaults are defined once in module config. Application-specific defaults continue to come from application manifests so labels/descriptions are not duplicated in configuration.

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

1. application/feature presentation overrides — feature labels, descriptions, icons, order, navigation visibility and maintenance badges while preserving manifest permission contracts;
2. a canonical icon renderer before exposing icon editing in Admin;
3. dynamic manifest presentation — selected safe manifest properties generated from ClientPortal settings while preserving `/my-apps` as the security-reviewed entry contract;
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
- `/admin/client-apps/pwa/launcher` loads for authorized Admin;
- save launcher copy and refresh `/my-apps`;
- rename/reorder an application card and verify launcher output;
- disable one application card and verify it disappears from launcher;
- application route/permission remains unchanged after presentation override;
- `/my-apps/login` continues to display configured login content;
- Client login still authenticates guard `web`;
- successful login still redirects to `/my-apps`;
- Admin login/logout behavior is unchanged.
