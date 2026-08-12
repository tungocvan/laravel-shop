# Settings/ArtisanList Admin Menu Change Plan

Plan date: 2026-08-12

Scope: add a dedicated Admin Menu entry and route/page for the already-refactored `Settings/ArtisanList` component.

Status: **Implemented 2026-08-12.**

## 1. Change Goal

Expose the restricted Artisan operations panel through the Admin sidebar under the existing **Công cụ Hệ thống** menu group.

Implemented behavior:

- dedicated page mounts `system.settings.artisan-list`;
- route/menu/action boundaries use `system.commands.run`;
- existing `ArtisanList` and `SystemOperationService` are reused;
- arbitrary Artisan command execution remains removed;
- no new permission was added;
- existing System tab production containment remains unchanged.

## 2. Implemented Route

```text
GET /admin/system/artisan
name: admin.system.artisan
middleware: auth:admin + permission:system.commands.run,admin
```

The route points to `SettingController::artisan()`.

## 3. Controller/Page

Updated:

`Modules/System/Http/Controllers/SettingController.php`

Created:

`Modules/System/resources/views/pages/settings/artisan.blade.php`

The page uses the canonical Admin layout and mounts:

```blade
@livewire('system.settings.artisan-list')
```

No command logic was added to the controller/page.

## 4. Admin Menu

Updated canonical menu source:

`Modules/Admin/data/menus.json`

Under `Công cụ Hệ thống`:

```text
Name: Thao tác Artisan
URL: /admin/system/artisan
Can: system.commands.run
Active: true
```

No broad legacy permission is used for this entry.

## 5. Existing Database/Menu Data

`AdminMenuSeeder` still intentionally skips when `admin_menus` already contains data. The implementation therefore does not wipe, reset, or reseed existing menus automatically.

Existing installations need one explicit idempotent post-deploy menu insertion/update for the new child entry, or the equivalent Admin menu UI action.

Recommended safe post-deploy approach:

1. locate the existing `Công cụ Hệ thống` parent;
2. `updateOrCreate` the child identified by `/admin/system/artisan`;
3. set `name=Thao tác Artisan`, `can=system.commands.run`, `is_active=true`;
4. leave all unrelated menu rows untouched.

No `migrate:fresh`, destructive reseed, or menu reset is required.

## 6. Authorization

Three layers now agree:

1. Menu visibility: `system.commands.run`.
2. Route middleware: `permission:system.commands.run,admin`.
3. Livewire execution action: `authorizePermission('system.commands.run')`.

No new permission/migration was introduced.

## 7. Production Containment

Preserved unchanged:

- `Modules/System/config/system_tabs.php` keeps the Artisan tab disabled;
- `SystemConfigService::DISABLED_PRODUCTION_COMPONENTS` still forces the tab disabled.

The dedicated route is a separately authorized surface for the already restricted two-operation panel.

## 8. Tests

Updated:

`tests/Feature/System/SystemArtisanOperationsTest.php`

Added coverage for:

- route existence and URI;
- `auth:admin` middleware;
- `permission:system.commands.run,admin` middleware;
- dedicated page mounting `system.settings.artisan-list`;
- canonical Admin menu entry and exact capability;
- existing operation allowlist/security behavior;
- existing tab containment.

## 9. Acceptance Criteria Status

- [x] Canonical menu data contains `Thao tác Artisan` under `Công cụ Hệ thống`.
- [x] Menu URL is `/admin/system/artisan`.
- [x] Menu capability is exactly `system.commands.run`.
- [x] Dedicated route is named `admin.system.artisan`.
- [x] Route requires `auth:admin` and `permission:system.commands.run,admin`.
- [x] Dedicated page mounts the restricted `system.settings.artisan-list` component.
- [x] No free-form Artisan execution was reintroduced.
- [x] Existing System tab containment is unchanged.
- [x] Existing databases are not wiped/reseeded automatically.
- [x] Focused tests were updated; runtime verification should be executed after pull on the deployment environment.

## 10. Files Changed

- `Modules/System/routes/web.php`
- `Modules/System/Http/Controllers/SettingController.php`
- `Modules/System/resources/views/pages/settings/artisan.blade.php`
- `Modules/Admin/data/menus.json`
- `tests/Feature/System/SystemArtisanOperationsTest.php`
- `docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`
- `docs/modules/System/livewire/settings-artisan-list/CHANGE_PLAN.md`
