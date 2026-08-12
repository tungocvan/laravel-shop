# Settings/ArtisanList Livewire Analysis

Analysis date: 2026-08-12

Implementation status: **Refactored and exposed through restricted Admin route 2026-08-12**

Scope: `Modules/System/Livewire/Settings/ArtisanList.php` and direct dependencies.

## Executive Summary

`Settings/ArtisanList` is no longer a free-form browser Artisan terminal. The P0 arbitrary command execution surface has been removed.

The component now exposes only two server-defined operation IDs:

- `artisan.list` -> fixed Artisan command `list`;
- `cache.optimize-clear` -> fixed Artisan command `optimize:clear`.

Browser-controlled command text and command arguments are no longer accepted. Execution requires `system.commands.run` at the Livewire action boundary and is delegated to `Modules/System/Services/SystemOperationService.php`.

The component is now also reachable through a dedicated restricted Admin route:

```text
GET /admin/system/artisan
name: admin.system.artisan
middleware: auth:admin + permission:system.commands.run,admin
```

Canonical Admin menu data contains `Thao tác Artisan` under `Công cụ Hệ thống` with `can=system.commands.run`.

Production tab containment remains unchanged: the System tab is disabled by default and `SystemConfigService` continues to force `system.settings.artisan-list` disabled during normalization. The dedicated route is a separately authorized surface.

## Current Dependency Flow

```text
Admin menu: Thao tác Artisan
  -> /admin/system/artisan
  -> auth:admin
  -> permission:system.commands.run,admin
  -> SettingController::artisan()
  -> System::pages.settings.artisan
  -> Livewire ArtisanList
      -> authorize system.commands.run
      -> SystemOperationService
          -> fixed operation registry
          -> Artisan::call(fixed command, fixed arguments)
          -> safe application logging
      -> escaped output / generic browser-facing failure
```

## Livewire PHP

Current public state:

- `$selectedOperation` defaults to `artisan.list`;
- `$commandOutput`;
- `$errorMessage`.

`executeOperation()`:

1. authorizes `system.commands.run`;
2. clears previous output/error state;
3. passes only the selected operation ID and actor ID to `SystemOperationService`;
4. shows safe output on success;
5. catches failures without copying exception details into browser state.

The component no longer imports the Artisan facade and no longer contains `Artisan::call()`.

## Operation Service

`Modules/System/Services/SystemOperationService.php` owns the allowlist.

Registry:

| ID | Command | Confirmation |
|---|---|---|
| `artisan.list` | `list` | No |
| `cache.optimize-clear` | `optimize:clear` | Yes |

Unknown operation IDs are rejected before Artisan execution.

The service logs safe metadata for start/completion/failure:

- actor ID;
- operation ID;
- fixed command name;
- result/exit code;
- exception class on failure.

No arbitrary request payload or command arguments are logged.

## Blade UI

The former terminal command input and dangerous shortcuts were removed.

Removed examples include:

- `key:generate`;
- `db:seed`;
- `migrate:fresh`.

The UI now renders only operation cards provided by the service, shows an impact description, marks read-only vs mutation operations, uses confirmation for `cache.optimize-clear`, preserves loading/disabled state, and renders output through escaped Blade output.

Component-local scrollbar CSS was removed.

## Dedicated Admin Route / Page

Implemented route:

- URI: `/admin/system/artisan`;
- name: `admin.system.artisan`;
- route middleware: `auth:admin` and `permission:system.commands.run,admin`.

Controller:

`SettingController::artisan()` returns `System::pages.settings.artisan`.

Page:

`Modules/System/resources/views/pages/settings/artisan.blade.php`

uses the canonical Admin layout and mounts:

```blade
@livewire('system.settings.artisan-list')
```

No operational logic was added to the route/controller/page layer.

## Admin Menu

Canonical source:

`Modules/Admin/data/menus.json`

Entry:

```text
Parent: Công cụ Hệ thống
Name: Thao tác Artisan
URL: /admin/system/artisan
Can: system.commands.run
Active: true
```

`AdminMenuSeeder` still skips if `admin_menus` already contains data. Therefore existing installations require a narrowly scoped idempotent insert/update of this one child entry (or equivalent Admin menu UI update); no destructive reseed/reset is required.

## Authorization

Authorization now exists at three layers:

1. Admin menu capability: `system.commands.run`;
2. dedicated route middleware: `permission:system.commands.run,admin`;
3. Livewire mutation action: `authorizePermission('system.commands.run')`.

This closes the previous action-boundary authorization gap while keeping page access aligned with operation capability.

## Security / Data Integrity Status

### Resolved P0 — Arbitrary Artisan execution

No browser-controlled command string reaches `Artisan::call()`.

### Resolved P0 — Missing command authorization

`system.commands.run` is enforced in the Livewire mutation action and dedicated route.

### Resolved P0 — Destructive quick commands

`key:generate`, `db:seed`, and `migrate:fresh` are no longer exposed.

### Resolved P1 — Raw exception disclosure

Detailed exception messages are no longer stored in Livewire error state. The UI receives a generic operator-safe message.

### Improved P1 — Operational auditability

Operation attempts/results are recorded through Laravel application logging with safe structured metadata.

## Production Containment

Containment remains defense-in-depth and was not weakened for the tab-based System surface:

- `Modules/System/config/system_tabs.php` keeps the Artisan tab disabled by default;
- `Modules/System/Services/SystemConfigService.php` continues to force `system.settings.artisan-list` disabled.

The dedicated `/admin/system/artisan` route is intentionally available only to admins with `system.commands.run`.

## Tests

`tests/Feature/System/SystemArtisanOperationsTest.php`

Coverage includes:

- exact allowlist IDs;
- fixed command mapping for `list` and `optimize:clear` using Artisan mocks;
- unknown operation rejection before Artisan execution;
- component authorization/security contract;
- removal of destructive/free-form command UI;
- dedicated route existence, URI and permission middleware;
- dedicated page mounting the restricted Livewire component;
- canonical Admin menu entry and exact capability;
- preservation of production tab containment.

## Remaining Risks / Follow-up

- Operations still execute synchronously inside the Livewire request. This is acceptable for the two approved short operations; future long-running operations require a separate plan.
- Application logging is the current audit mechanism. Migration to a future canonical persistent Audit Log framework is cross-cutting work outside this component refactor.
- Existing databases do not automatically receive the new menu row because `AdminMenuSeeder` intentionally skips populated menu tables; deployers should perform the approved one-entry idempotent update.

## Refactor Decision

**Refactor and Admin menu exposure complete for the approved scope.**

Do not add new Artisan operations by accepting command text or arbitrary arguments. Any future operation must be added explicitly to the server-side registry and reviewed for permission, confirmation, runtime impact and audit requirements.
