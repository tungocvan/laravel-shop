# Settings/ArtisanList Admin Menu Change Plan

Plan date: 2026-08-12

Scope: add a dedicated Admin Menu entry and route/page for the already-refactored `Settings/ArtisanList` component.

Status: **Awaiting explicit approval before implementation.**

## 1. Change Goal

Expose the restricted Artisan operations panel through the Admin sidebar under the existing **Công cụ Hệ thống** menu group.

The new menu entry must:

- open a dedicated System page that mounts `system.settings.artisan-list`;
- require `system.commands.run` at route/menu/action boundaries;
- reuse the existing refactored `ArtisanList` component and `SystemOperationService`;
- not restore arbitrary Artisan command execution;
- not add a new permission;
- preserve the existing System tab production containment behavior for the tab-based `/admin/system` surface.

## 2. Important Architecture Note

`SystemConfigService` currently forces the `system.settings.artisan-list` **tab** disabled. That containment protects the tab-based System page.

A dedicated admin route would be an intentional, separately-authorized exposure of the already-restricted operations panel. Therefore:

- the route must use `permission:system.commands.run,admin`;
- the Livewire action must continue to enforce `system.commands.run` internally;
- no generic/free-form terminal behavior may be reintroduced;
- the existing tab containment must remain unchanged.

## 3. Proposed Route

Add to `Modules/System/routes/web.php`:

```text
GET /admin/system/artisan
name: admin.system.artisan
middleware: auth:admin + permission:system.commands.run,admin
```

The route should point to `SettingController::artisan()` (or an equivalent narrowly scoped controller method) and render a dedicated page.

## 4. Controller/Page Changes

Update:

`Modules/System/Http/Controllers/SettingController.php`

Add a simple `artisan()` method returning a dedicated Blade page.

Create:

`Modules/System/resources/views/pages/settings/artisan.blade.php`

The page should only mount:

```blade
<livewire:system.settings.artisan-list />
```

inside the canonical admin layout/page structure already used by other System settings pages.

No command logic belongs in the controller/page.

## 5. Admin Menu Changes

Update canonical seed source:

`Modules/Admin/data/menus.json`

Under parent:

`Công cụ Hệ thống`

add child:

```text
Name: Artisan Operations
URL: /admin/system/artisan
Can: system.commands.run
Active: true
```

Suggested Vietnamese label:

`Thao tác Artisan`

Do not use old broad permissions such as `create_admin`, `view_role`, or `view_setting` for this entry.

## 6. Existing Database/Menu Data

`AdminMenuSeeder` intentionally skips seeding when `admin_menus` already contains data. Therefore changing `menus.json` alone will not update existing installations.

Implementation must **not** silently wipe or reseed existing menu data.

For existing deployments, provide an explicit safe post-deploy instruction using the existing Admin menu UI/import/restore mechanism, or add an idempotent narrowly-scoped menu sync only if the repository already has a canonical pattern for such updates.

No destructive database reset is allowed.

## 7. Authorization

Three layers must agree:

1. Menu visibility: `can = system.commands.run`.
2. Route middleware: `permission:system.commands.run,admin`.
3. Livewire mutation boundary: existing `authorizePermission('system.commands.run')` remains unchanged.

No new permission/migration is needed because `system.commands.run` already exists in the System module manifest.

## 8. Production Containment

Preserve unchanged:

- `Modules/System/config/system_tabs.php` keeps Artisan tab disabled;
- `SystemConfigService::DISABLED_PRODUCTION_COMPONENTS` continues to force the tab disabled.

The dedicated `/admin/system/artisan` page is allowed only because the component itself has already been converted from a generic terminal into two allowlisted operations and is protected by `system.commands.run`.

If policy later requires the dedicated route to be disabled in production too, that is a separate deployment-policy decision and should be planned explicitly.

## 9. Tests

Update/add focused tests to verify:

- `admin.system.artisan` route exists;
- route includes `auth:admin`;
- route includes `permission:system.commands.run,admin`;
- menu seed JSON contains `/admin/system/artisan` with `system.commands.run`;
- dedicated page mounts `system.settings.artisan-list`;
- existing Artisan operation tests continue to pass;
- existing tab production containment remains intact.

Preferred location:

`tests/Feature/System/SystemArtisanOperationsTest.php`

unless a separate route/menu configuration test is cleaner under existing System test conventions.

## 10. Files to Change

Planned application/config files:

- `Modules/System/routes/web.php`
- `Modules/System/Http/Controllers/SettingController.php`
- `Modules/System/resources/views/pages/settings/artisan.blade.php` (new)
- `Modules/Admin/data/menus.json`

Tests:

- `tests/Feature/System/SystemArtisanOperationsTest.php`

Documentation:

- `docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`
- `docs/modules/System/livewire/settings-artisan-list/CHANGE_PLAN.md`

Explicitly unchanged:

- `Modules/System/Livewire/Settings/ArtisanList.php` unless a newly discovered direct integration issue requires a plan update;
- `Modules/System/Services/SystemOperationService.php`;
- System permissions manifest;
- database schema/migrations;
- `SystemConfigService` production tab containment.

## 11. Acceptance Criteria

- [ ] Admin sidebar canonical menu data contains `Thao tác Artisan` under `Công cụ Hệ thống`.
- [ ] Menu URL is `/admin/system/artisan`.
- [ ] Menu capability is exactly `system.commands.run`.
- [ ] Dedicated route is named `admin.system.artisan`.
- [ ] Route requires `auth:admin` and `permission:system.commands.run,admin`.
- [ ] Dedicated page mounts the existing restricted `system.settings.artisan-list` component.
- [ ] No free-form Artisan execution is reintroduced.
- [ ] Existing System tab containment stays unchanged.
- [ ] Existing databases are not wiped/reseeded automatically.
- [ ] Focused tests pass.

## 12. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, this is a feature/change extension and implementation must not begin until the user explicitly approves this `CHANGE_PLAN.md`.
