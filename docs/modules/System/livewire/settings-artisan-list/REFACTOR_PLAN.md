# Settings/ArtisanList Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/ArtisanList.php` and direct UI/service/test dependencies only.

Status: **Implemented 2026-08-12 after explicit user approval.**

## Approved Goal

Replace the free-form browser Artisan terminal with a small server-defined allowlist, enforce `system.commands.run`, preserve production containment, delegate execution to a service, sanitize failures, and add focused tests.

## Implemented Contract

Preserved:

- Livewire alias `system.settings.artisan-list`;
- existing System tab registration;
- disabled-by-default core tab;
- `SystemConfigService` forced-disable containment;
- escaped operator output;
- loading/disabled UI behavior.

Removed intentionally:

- free-form command input;
- arbitrary command/argument execution;
- `key:generate`, `db:seed`, `migrate:fresh` shortcuts;
- raw exception messages in browser state.

## Implemented Allowlist

| Operation ID | Fixed Artisan command | Confirmation |
|---|---|---|
| `artisan.list` | `list` | No |
| `cache.optimize-clear` | `optimize:clear` | Yes |

No browser-controlled command arguments are accepted.

## Implemented Files

Application:

- `Modules/System/Livewire/Settings/ArtisanList.php`
- `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`
- `Modules/System/Services/SystemOperationService.php` (new)

Tests:

- `tests/Feature/System/SystemArtisanOperationsTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-artisan-list/ANALYSIS.md`
- `docs/modules/System/livewire/settings-artisan-list/REFACTOR_PLAN.md`

## Authorization

Every execution goes through:

```text
authorizePermission('system.commands.run')
```

before the operation service is called.

## Service Boundary

`SystemOperationService` owns the fixed registry, maps operation IDs to fixed commands/arguments, rejects unknown IDs before Artisan execution, and logs safe start/result/failure metadata.

Livewire no longer imports or calls the Artisan facade directly.

## UI

The terminal text field and quick-command buttons were replaced with restricted operation cards. The mutation operation `cache.optimize-clear` requires explicit confirmation. Output remains escaped.

## Testing Added

Focused tests cover:

- exact registry contents;
- fixed mapping for `list`;
- fixed mapping for `optimize:clear`;
- rejection of unknown IDs before Artisan execution;
- component authorization/security source contract;
- absence of destructive/free-form commands in Blade;
- preservation of production containment.

## Acceptance Criteria Status

- [x] no free-form Artisan command field remains;
- [x] no browser-controlled command string is passed to `Artisan::call()`;
- [x] only `artisan.list` and `cache.optimize-clear` are exposed;
- [x] execution requires `system.commands.run`;
- [x] unknown operation IDs are rejected;
- [x] destructive shortcuts are absent;
- [x] raw exception details are not exposed to UI;
- [x] privileged operations have safe structured logging;
- [x] output remains escaped;
- [x] loading/disabled behavior remains;
- [x] production containment remains unchanged;
- [x] focused tests were added;
- [x] no routes, schema, unrelated modules, or unrelated Livewire components were changed;
- [x] component analysis was updated.

## Verification Note

The implementation was performed through the connected GitHub repository. Focused tests were added for execution in the project runtime/CI; no database migration or recovery step is required.

## Rollback

Rollback remains file-level because this refactor introduces no migration or persistent schema changes: revert the Livewire/Blade files and remove `SystemOperationService.php` plus the focused test if the feature is reverted completely. Production containment remains in place independently.
