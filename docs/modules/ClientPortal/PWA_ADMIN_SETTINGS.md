# ClientPortal — PWA Admin Settings

Updated: 2026-10-04

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

### `application.{key}.feature.{feature}.presentation`

Every user-facing PWA feature must expose its page copy through **Giao diện Application & Feature → Nội dung trang PWA** at `/admin/client-apps`.

Required manifest defaults for every feature are:

```php
'eyebrow' => '...',
'page_title' => '...',
'page_description' => '...',
```

These values are presentation defaults, not business configuration. Runtime pages must read the resolved values through `ClientPortalSettingsService::featurePresentation()` and pass them from the controller to Blade. Do not hard-code the same page eyebrow/title/description in Blade.

When adding a new PWA capability or feature, implementation is incomplete until:

- the manifest declares the three default page-content keys;
- `ApplicationRegistry` preserves them;
- Admin can edit them from `/admin/client-apps`;
- the runtime page consumes the resolved feature presentation;
- focused contract tests cover the defaults/override path;
- real UI acceptance confirms Admin changes appear in the PWA.

Admin overrides must never change route names, permissions, source-module contracts, or business logic.

## Application Hub and navigation presentation

The project-wide contract for every application is defined in `PWA_APPLICATION_STANDARD.md`.

Implemented settings groups are:

- `application.{key}.hub` — Hub hero plus Supporting/Foundation presentation;
- `application.{key}.navigation` — mobile Bottom Navigation visibility, order and icon overrides for manifest-declared navigation items;
- `pwa.bottom_navigation` — global mobile Bottom Navigation appearance;
- `application.{key}.feature.{feature}.presentation` — capability page presentation.

The Hub is application-owned. A Hub may use a stable key such as `overview` for route/middleware compatibility and navigation, but that does not require restoring Overview as a business capability in `features`. Hub authorization metadata remains source-controlled in the application manifest.

Bottom Navigation visibility is presentation-only. It must not grant/revoke a `web` permission and must not be used as route authorization. Runtime bottom items must satisfy both presentation visibility and the current User's capability availability/permission. Admin cannot edit route names or permission names.

### Global Bottom Navigation appearance and themes

`pwa.bottom_navigation` controls the active runtime appearance, including:

- presentation style (`default` or `neumorphism`);
- background and opacity;
- normal/active icon and text colors;
- active background;
- text/icon sizes and minimum height.

Built-in themes are source-controlled presets. User-created reusable theme snapshots are stored through System Settings under `clientportal.pwa.bottom_navigation.themes`; applying a theme copies its presentation snapshot into the active ClientPortal settings. No schema migration is required for these presentation values.

Neumorphism uses a single-surface mobile dock: the dock owns the outer relief, inactive items remain visually quiet, and the selected/pressed item may use inset relief. Bottom Navigation also follows the project native-touch contract so touch-down feedback is immediate. Tablet/desktop sidebar navigation is not restyled by this mobile presentation option.

New PWA applications and refactors must not hard-code their own incompatible Hub/navigation settings model. Reuse the ClientPortal settings contract and follow `PWA_APPLICATION_STANDARD.md`.

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

1. dynamic manifest presentation — selected safe manifest properties generated from ClientPortal settings while preserving `/my-apps` as the security-reviewed entry contract;
2. dedicated Admin capability for PWA/presentation management;
3. incremental adoption of the shared native-touch primitive as existing PWA screens are refactored.

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
