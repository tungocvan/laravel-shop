# System Livewire Analysis — Settings/DatabaseConfig

Analysis date: 2026-08-12
Refactor status: **Implemented — pending local focused test verification.**

## Executive Summary

`Settings/DatabaseConfig` has been refactored from a privileged Livewire component that directly hydrated `.env` secrets and orchestrated connection testing/env writes into a thin UI over `DatabaseConfigService`.

The previous P1 gaps are addressed in source:

- `testConnection()` and `save()` enforce `system.env.update` at the Livewire action boundary;
- the current `DB_PASSWORD` is never returned by the service or hydrated into public Livewire state;
- a blank password field means preserve the current server-side password;
- mysql/pgsql are explicitly allowlisted and all connection fields are validated;
- `DbConnectionService` returns generic failure messages and purges the temporary connection in `finally`;
- `.env` write orchestration is owned by `DatabaseConfigService`, which reuses `EnvManagerService` safety backup/validation/file-lock/in-place-write/rollback behavior;
- redundant direct `EnvBackupService` orchestration was removed from DatabaseConfig;
- save is serialized with `system:database-config:update` application lock;
- the component now renders the canonical System-owned Blade;
- the canonical `Quản lý ENV` Admin Menu entry uses `system.env.view`.

## Component Boundary After Refactor

Route/page:

`/admin/system/settings/env`
→ `system.env.view`
→ Env settings page
→ `system.settings.database-config`

Mutation flow:

`DatabaseConfig Livewire`
→ `system.env.update`
→ validation
→ `DatabaseConfigService`
→ `DbConnectionService` temporary connection test
→ `EnvManagerService::update()`
→ `config:clear`

## Secret Handling

The public form contains an empty `DB_PASSWORD` replacement field only.

`DatabaseConfigService::publicConfig()` returns only:

- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME

When the replacement password is blank, the service reads the current password from `.env` server-side and uses it only for the effective connection candidate/persisted values. The existing password is not returned to Livewire.

## Authorization

Page/menu visibility:

`system.env.view`

Sensitive actions:

- `testConnection()` → `system.env.update`
- `save()` → `system.env.update`

This keeps view-only operators read-only while ensuring crafted Livewire action requests still meet the mutation capability.

## Validation and Driver Policy

Livewire validates:

- DB_CONNECTION: mysql or pgsql only;
- DB_HOST: required bounded string;
- DB_PORT: integer 1–65535;
- DB_DATABASE: required bounded string;
- DB_USERNAME: required bounded string;
- DB_PASSWORD: nullable replacement string, max 4096.

The orchestration service defensively rechecks the fixed driver/key boundary before infrastructure operations.

## Connection Test Hardening

`DbConnectionService` now:

- supports mysql/pgsql candidates only;
- builds driver-appropriate temporary connection config;
- returns generic success/failure text;
- logs only safe metadata and exception class;
- never returns raw PDO/driver exception messages to Livewire;
- always calls `DB::purge()` in `finally`;
- clears the temporary runtime config after use.

## Env Write Integrity

`DatabaseConfigService::save()`:

1. acquires `system:database-config:update` lock;
2. resolves the effective password server-side;
3. re-tests the candidate connection;
4. aborts without touching `.env` if the test fails;
5. updates only the fixed DB env keys through `EnvManagerService`;
6. relies on EnvManagerService's dotenv validation, safety backup, file lock, in-place write and rollback-on-write-failure;
7. clears Laravel configuration cache after successful env write.

The older explicit `EnvBackupService::createBackup()` call was intentionally removed from this workflow because EnvManagerService already performs the stronger canonical safety backup.

## View / UX

The component now renders:

`System::livewire.settings.database-config`

The System Blade includes:

- explicit mysql/pgsql driver selector;
- inline validation errors;
- write-only password guidance;
- read-only notice for users without `system.env.update`;
- connection-test/loading states;
- production-impact warning;
- save confirmation;
- deployment note explaining that long-lived PHP/queue processes may require manual reload and that no migrations/restarts are run automatically.

## Admin Menu

No new DatabaseConfig menu was added because this component remains a tab under `/admin/system/settings/env`.

Canonical entry:

```text
Quản lý ENV
/admin/system/settings/env
system.env.view
```

Existing installations may require the targeted idempotent menu-row update documented in deployment guidance.

## Focused Tests

Added:

`tests/Feature/System/SystemDatabaseConfigTest.php`

Coverage includes route/menu permission contract, action authorization source contract, secret non-hydration, blank-password preservation, explicit replacement handling, driver/port validation contract, generic temporary-connection errors, purge behavior, workflow lock, canonical EnvManager ownership, System view ownership, confirmation/read-only UI and connection-status reset behavior.

## Remaining Operational Note

Changing `.env` and clearing config cache does not force all long-running workers/processes to reload their environment. Production deployment procedures may still require PHP-FPM/queue/process reload depending on runtime architecture. This refactor intentionally does not expose process restart or migration operations through DatabaseConfig.
