# Settings/ShScript Admin Menu Change Plan

Plan date: 2026-08-12

Scope: add a dedicated Admin route/page/menu entry for the already-refactored `Settings/ShScript` component.

Status: **Awaiting explicit approval before implementation.**

## 1. Change Goal

Expose the restricted server-owned script operation panel through the Admin sidebar under the existing **Công cụ Hệ thống** group.

The new entry must:

- open a dedicated System page that mounts `system.settings.sh-script`;
- require `system.commands.run` at menu, route, and Livewire action boundaries;
- reuse the refactored `ShScript` component and `SystemScriptOperationService`;
- not restore script create/edit/delete behavior;
- not restore browser-controlled filename/path/content execution;
- preserve existing System-tab production containment;
- show the safe empty state while the approved script registry remains empty.

## 2. Current Safety State

`ShScript` has already been refactored so the browser can no longer create, edit, delete, or directly execute arbitrary shell content.

`SystemScriptOperationService` owns the server-side registry. The initial registry is intentionally empty because no repository-owned script under the approved `app/sh` root was available for review.

Therefore the dedicated Admin page is safe to expose only as a restricted operation surface; it will initially display that no scripts have been approved.

## 3. Proposed Route

Add to `Modules/System/routes/web.php`:

```text
GET /admin/system/scripts
name: admin.system.scripts
middleware: auth:admin + permission:system.commands.run,admin
```

The route should point to a narrow `SettingController::scripts()` method.

## 4. Controller / Page

Update:

`Modules/System/Http/Controllers/SettingController.php`

Add:

```text
scripts() -> System::pages.settings.scripts
```

Create:

`Modules/System/resources/views/pages/settings/scripts.blade.php`

The page should use the existing Admin layout and mount only:

```blade
<livewire:system.settings.sh-script />
```

No shell/process logic belongs in the controller or page.

## 5. Admin Menu

Update canonical menu source:

`Modules/Admin/data/menus.json`

Under parent:

`Công cụ Hệ thống`

add a child adjacent to `Thao tác Artisan`:

```text
Name: Thao tác Script
URL: /admin/system/scripts
Can: system.commands.run
Active: true
```

No new permission is required.

## 6. Existing Admin Menu Database Data

`AdminMenuSeeder` skips when `admin_menus` already contains data, so changing `menus.json` alone does not update an existing installation.

Implementation must not reset or reseed the whole menu table.

After implementation, provide an idempotent `updateOrCreate` post-deploy command for existing installations, scoped only to `/admin/system/scripts` under the current `Công cụ Hệ thống` parent.

## 7. Authorization

The following layers must agree:

1. Menu visibility: `can = system.commands.run`.
2. Route middleware: `permission:system.commands.run,admin`.
3. Livewire execution: existing `authorizePermission('system.commands.run')` remains enforced.

No new permission or migration is needed.

## 8. Production Containment

Preserve unchanged:

- the System tab for `system.settings.sh-script` remains disabled;
- `SystemConfigService` continues forcing that tab disabled in production normalization.

The dedicated route is a separately authorized surface for the refactored restricted runner. It does not re-enable the original tab or arbitrary shell execution.

## 9. Tests

Update `tests/Feature/System/SystemScriptOperationsTest.php` to verify:

- route `admin.system.scripts` exists;
- route has `auth:admin`;
- route has `permission:system.commands.run,admin`;
- dedicated page mounts `system.settings.sh-script`;
- canonical menu JSON contains `Thao tác Script` with `/admin/system/scripts` and `system.commands.run`;
- script registry remains empty until explicitly approved scripts are added;
- no editor/create/delete/arbitrary shell behavior is reintroduced;
- existing production containment remains intact.

## 10. Files to Change

Application/config:

- `Modules/System/routes/web.php`
- `Modules/System/Http/Controllers/SettingController.php`
- `Modules/System/resources/views/pages/settings/scripts.blade.php` (new)
- `Modules/Admin/data/menus.json`

Tests:

- `tests/Feature/System/SystemScriptOperationsTest.php`

Documentation:

- `docs/modules/System/livewire/settings-sh-script/ANALYSIS.md`
- `docs/modules/System/livewire/settings-sh-script/CHANGE_PLAN.md`

Explicitly unchanged:

- `Modules/System/Livewire/Settings/ShScript.php`
- `Modules/System/Services/SystemScriptOperationService.php`
- System permission manifest
- database schema
- `SystemConfigService` production containment

## 11. Acceptance Criteria

- [ ] `Công cụ Hệ thống` contains `Thao tác Script`.
- [ ] menu URL is `/admin/system/scripts`.
- [ ] menu capability is exactly `system.commands.run`.
- [ ] route is named `admin.system.scripts`.
- [ ] route requires `auth:admin` and `permission:system.commands.run,admin`.
- [ ] page mounts `system.settings.sh-script`.
- [ ] empty approved-script registry is displayed safely.
- [ ] browser still cannot create/edit/delete shell scripts.
- [ ] no arbitrary filename/path/content execution returns.
- [ ] production tab containment remains unchanged.
- [ ] no menu-table reset/reseed occurs.
- [ ] focused tests pass.

## 12. Approval Gate

This is a feature/change extension to the completed ShScript refactor. Implementation must not begin until the user explicitly approves this `CHANGE_PLAN.md`.
