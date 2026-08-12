# System Operation Registry Change Plan

Plan date: 2026-08-12

Status: **Implemented 2026-08-12.**

## Goal

Move approved Artisan and shell-script operation registries out of service constants into developer-owned Module config files, add grouping metadata for the Admin UI, and provide safe demo operations that show how future operations should be registered.

The browser continues to submit only an operation ID. Commands, script paths and arguments remain server-owned allowlist data deployed with source code.

## Implemented registry files

- `Modules/System/config/artisan_operations.php`
- `Modules/System/config/script_operations.php`

Services load these fixed Module-owned PHP config files directly. No request-controlled config path and no Admin CRUD editor exist.

## Artisan registry

Initial approved operations:

- `artisan.list` → group `Thông tin` → `list`
- `route.list` → group `Thông tin` → `route:list`
- `about` → group `Thông tin` → `about`
- `cache.optimize-clear` → group `Cache` → `optimize:clear`
- `queue.restart` → group `Queue` → `queue:restart`

Dangerous commands such as `migrate:fresh`, `db:wipe`, `db:seed` and `key:generate` are intentionally absent.

## Script registry

Approved root remains `app/sh`.

Initial read-only demo operations:

- `demo.system-info` → `app/sh/demo-system-info.sh` → group `Demo an toàn`
- `demo.disk-usage` → `app/sh/demo-disk-usage.sh` → group `Kiểm tra hệ thống`

Both demos use fixed paths, fixed arguments, 10-second timeouts and no browser input.

## Service behavior

`SystemOperationService` now validates the registry and exposes only display-safe metadata (`id`, `group`, `label`, `description`, `confirmation`) to Livewire. Command/arguments stay internal.

`SystemScriptOperationService` validates the registry, exposes display-safe metadata, preserves canonical `app/sh` path enforcement, `/bin/bash` argument-array execution, timeout handling and the 32 KB output bound.

## Admin UI

Both operation pages now group entries by `group`:

- `/admin/system/artisan`
- `/admin/system/scripts`

Both remain protected by `system.commands.run`. No command, path or argument editor was introduced.

## Registering a new Artisan operation

Edit only:

`Modules/System/config/artisan_operations.php`

Add a fixed operation ID with developer-owned group, label, description, command, fixed arguments and confirmation policy. Never accept command text/arguments from browser state.

## Registering a new script operation

1. Review and commit the script under `app/sh`.
2. Add a fixed entry to `Modules/System/config/script_operations.php`.
3. Define group, label, description, fixed relative script path, fixed arguments, timeout and confirmation policy.
4. Add/adjust focused tests for side effects, timeout, locking requirements and rollback semantics before enabling any mutating script.

## Tests

Focused test suites were updated:

- `tests/Feature/System/SystemArtisanOperationsTest.php`
- `tests/Feature/System/SystemScriptOperationsTest.php`

They cover registry IDs/group metadata, hidden execution internals, dangerous-command absence, demo-script safety, route/menu permission contracts, UI grouping and production containment.

## Acceptance criteria

- [x] Artisan registry lives in Module config, not a service constant.
- [x] Script registry lives in Module config, not a service constant.
- [x] Admin UI groups operations by `group`.
- [x] Safe Artisan examples are registered.
- [x] Two read-only demo scripts are committed and registered.
- [x] No Admin CRUD for commands/scripts exists.
- [x] No browser-controlled command/path/argument reaches execution.
- [x] `system.commands.run` remains enforced.
- [x] ShScript canonical-path, Process timeout and output bounds remain enforced.
- [x] Dangerous Artisan commands are not registered.
- [ ] Focused tests must be run in the project runtime after pull/merge.
