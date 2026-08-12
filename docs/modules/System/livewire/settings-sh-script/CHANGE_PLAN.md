# Settings/ShScript Admin Menu Change Plan

Plan date: 2026-08-12

Scope: add a dedicated Admin route/page/menu entry for the already-refactored `Settings/ShScript` component.

Status: **Implemented 2026-08-12.**

## 1. Change Goal

Expose the restricted server-owned script operation panel through the Admin sidebar under the existing **Công cụ Hệ thống** group.

Implemented behavior:

- dedicated page mounts `system.settings.sh-script`;
- menu, route and Livewire execution all use `system.commands.run`;
- refactored `ShScript` and `SystemScriptOperationService` are reused unchanged;
- script create/edit/delete behavior remains removed;
- browser-controlled filename/path/content execution remains removed;
- existing System-tab production containment remains unchanged;
- safe empty state remains visible while the approved script registry is empty.

## 2. Current Safety State

`ShScript` is a restricted server-owned operation runner. The browser cannot create, edit, delete, or directly execute arbitrary shell content.

`SystemScriptOperationService` owns the server-side registry. The initial registry remains intentionally empty because no repository-owned script under `app/sh` has been reviewed and approved.

## 3. Implemented Route

```text
GET /admin/system/scripts
name: admin.system.scripts
middleware: auth:admin + permission:system.commands.run,admin
```

Controller method:

`SettingController::scripts()`

## 4. Controller / Page

Implemented:

`Modules/System/Http/Controllers/SettingController.php`

with `scripts()` returning:

`System::pages.settings.scripts`

Created:

`Modules/System/resources/views/pages/settings/scripts.blade.php`

which mounts:

```blade
<livewire:system.settings.sh-script />
```

## 5. Admin Menu

Updated canonical menu source:

`Modules/Admin/data/menus.json`

Under:

`Công cụ Hệ thống`

added:

```text
Name: Thao tác Script
URL: /admin/system/scripts
Can: system.commands.run
Active: true
```

No new permission was added.

## 6. Existing Admin Menu Database Data

`AdminMenuSeeder` still intentionally skips when `admin_menus` already contains data. No menu table reset or global reseed was added.

For existing installations, use a narrowly-scoped idempotent `updateOrCreate` for `/admin/system/scripts` under the current `Công cụ Hệ thống` parent.

## 7. Authorization

Aligned layers:

1. Menu visibility: `system.commands.run`.
2. Route middleware: `permission:system.commands.run,admin`.
3. Livewire execution: `authorizePermission('system.commands.run')`.

## 8. Production Containment

Preserved unchanged:

- System tab for `system.settings.sh-script` remains disabled;
- `SystemConfigService` continues forcing the tab disabled.

The dedicated route is a separately authorized surface for the restricted runner only.

## 9. Tests

Updated:

`tests/Feature/System/SystemScriptOperationsTest.php`

Coverage now includes:

- route `admin.system.scripts` exists;
- route uses `auth:admin`;
- route uses `permission:system.commands.run,admin`;
- dedicated page mounts `system.settings.sh-script`;
- canonical menu contains `Thao tác Script` with `/admin/system/scripts` and `system.commands.run`;
- registry remains empty until scripts are explicitly approved;
- shell editor/create/delete behavior remains absent;
- production containment remains intact.

## 10. Files Changed

- `Modules/System/routes/web.php`
- `Modules/System/Http/Controllers/SettingController.php`
- `Modules/System/resources/views/pages/settings/scripts.blade.php`
- `Modules/Admin/data/menus.json`
- `tests/Feature/System/SystemScriptOperationsTest.php`
- `docs/modules/System/livewire/settings-sh-script/ANALYSIS.md`
- `docs/modules/System/livewire/settings-sh-script/CHANGE_PLAN.md`

Unchanged security core:

- `Modules/System/Livewire/Settings/ShScript.php`
- `Modules/System/Services/SystemScriptOperationService.php`
- System permission manifest
- database schema
- `SystemConfigService`

## 11. Acceptance Criteria

- [x] `Công cụ Hệ thống` contains `Thao tác Script`.
- [x] menu URL is `/admin/system/scripts`.
- [x] menu capability is exactly `system.commands.run`.
- [x] route is named `admin.system.scripts`.
- [x] route requires `auth:admin` and `permission:system.commands.run,admin`.
- [x] page mounts `system.settings.sh-script`.
- [x] empty approved-script registry is displayed safely.
- [x] browser still cannot create/edit/delete shell scripts.
- [x] no arbitrary filename/path/content execution returns.
- [x] production tab containment remains unchanged.
- [x] no menu-table reset/reseed occurs.
- [ ] focused tests must be run in the target Laravel runtime after pull/merge.
