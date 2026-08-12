# Settings/ArtisanList Livewire Analysis

Analysis date: 2026-08-12

Implementation status: **Refactored 2026-08-12**

Scope: `Modules/System/Livewire/Settings/ArtisanList.php` and direct dependencies.

## Executive Summary

`Settings/ArtisanList` is no longer a free-form browser Artisan terminal. The P0 arbitrary command execution surface has been removed.

The component now exposes only two server-defined operation IDs:

- `artisan.list` -> fixed Artisan command `list`;
- `cache.optimize-clear` -> fixed Artisan command `optimize:clear`.

Browser-controlled command text and command arguments are no longer accepted. Execution requires `system.commands.run` at the Livewire action boundary and is delegated to `Modules/System/Services/SystemOperationService.php`.

Production containment remains unchanged: the System tab is disabled by default and `SystemConfigService` continues to force `system.settings.artisan-list` disabled during normalization.

## Current Dependency Flow

```text
Livewire ArtisanList
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

## Authorization

`ArtisanList` now uses `AuthorizesSystemActions` and calls:

```text
authorizePermission('system.commands.run')
```

before operation lookup/execution.

This closes the previous action-boundary authorization gap.

## Security / Data Integrity Status

### Resolved P0 — Arbitrary Artisan execution

No browser-controlled command string reaches `Artisan::call()`.

### Resolved P0 — Missing command authorization

`system.commands.run` is enforced in the Livewire mutation action.

### Resolved P0 — Destructive quick commands

`key:generate`, `db:seed`, and `migrate:fresh` are no longer exposed.

### Resolved P1 — Raw exception disclosure

Detailed exception messages are no longer stored in Livewire error state. The UI receives a generic operator-safe message.

### Improved P1 — Operational auditability

Operation attempts/results are recorded through Laravel application logging with safe structured metadata.

## Production Containment

Containment remains defense-in-depth and was not weakened:

- `Modules/System/config/system_tabs.php` keeps the Artisan tab disabled by default;
- `Modules/System/Services/SystemConfigService.php` continues to force `system.settings.artisan-list` disabled.

Authorization and containment remain independent controls.

## Tests

Added:

`tests/Feature/System/SystemArtisanOperationsTest.php`

Coverage includes:

- exact allowlist IDs;
- fixed command mapping for `list` and `optimize:clear` using Artisan mocks;
- unknown operation rejection before Artisan execution;
- component authorization/security contract;
- removal of destructive/free-form command UI;
- preservation of production containment.

## Remaining Risks / Follow-up

- Operations still execute synchronously inside the Livewire request. This is acceptable for the two approved short operations; future long-running operations require a separate plan.
- Application logging is the current audit mechanism. Migration to a future canonical persistent Audit Log framework is cross-cutting work outside this component refactor.
- The component remains intentionally unavailable through normal production System tab configuration.

## Refactor Decision

**Refactor complete for the approved scope.**

Do not add new Artisan operations by accepting command text or arbitrary arguments. Any future operation must be added explicitly to the server-side registry and reviewed for permission, confirmation, runtime impact and audit requirements.
