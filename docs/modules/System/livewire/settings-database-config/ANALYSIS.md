# System Livewire Analysis — Settings/DatabaseConfig

Analysis date: 2026-08-12

## Executive Summary

`Modules/System/Livewire/Settings/DatabaseConfig.php` manages database connection values in `.env`, tests a temporary connection, creates a backup, writes the new environment values, and clears Laravel config cache. The underlying `EnvManagerService` now provides dotenv validation, safety backup, file locking, in-place writes, and rollback-on-write-failure, which is a strong service-level safeguard.

The component remains **P1 / Major Refactor** because sensitive mutations do not enforce `system.env.update` at the Livewire action boundary, database credentials including `DB_PASSWORD` are hydrated into public Livewire state, validation is incomplete, and `render()` points to an Admin-owned view instead of the System view that exists in this module.

## Component Purpose

Path: `Modules/System/Livewire/Settings/DatabaseConfig.php`

Expected alias: `system.settings.database-config`

View declared by PHP: `Admin::livewire.settings.database-config`

System-local view also exists: `Modules/System/resources/views/livewire/settings/database-config.blade.php`.

Responsibilities:

- Read current database environment variables.
- Test proposed connection settings.
- Backup `.env` before save.
- Persist database settings.
- Clear Laravel config cache.

## Dependency Flow

`/admin/system/settings/env`
→ `EnvConfigController`
→ dynamic System env tab
→ `DatabaseConfig`
→ `DbConnectionService` / `EnvBackupService` / `EnvManagerService`
→ `.env` / database connection

## Livewire PHP Analysis

Public state includes `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

`mount()` loads existing `.env` values into public component state.

`testConnection()` delegates to `DbConnectionService::testConnection()`.

`save()`:

1. Retests the connection.
2. Creates an env backup.
3. Calls `EnvManagerService::update()`.
4. Calls `Artisan::call('config:clear')`.

The component contains no explicit capability authorization.

## Livewire Blade Analysis

The System-local Blade has clear DB fields, password input, connection status, loading states, and disables Save until the connection status is `connected`.

However, the PHP component currently renders an Admin module view instead of this System-local view, creating cross-module UI ownership drift.

A password input only masks display. Because the secret is stored in a public Livewire property, the current DB password is part of Livewire component state once mounted.

## State / Validation / Actions

Actions:

- `testConnection()`
- `save()`

There is no explicit field validation in the component before testing/saving. `DbConnectionService` indexes several expected array keys directly and accepts the driver supplied through `DB_CONNECTION`.

Recommended validation should cover allowed driver, host, port range, database/username length, and intentional password handling.

## Authorization

**P1 finding:** module manifest defines `system.env.update`, but this component does not enforce it.

The env page route uses a view capability. Sensitive save/test actions must enforce capability-specific authorization independently of page visibility.

## Service / Model Dependencies

### EnvManagerService

Strong current safeguards:

- validates candidate dotenv content before write;
- creates persistent safety backups;
- uses file locking;
- writes in-place for Docker bind-mount compatibility;
- attempts restoration of original content on failure.

### DbConnectionService

Creates a temporary Laravel DB connection and attempts PDO connection. Current error response includes the caught exception message, which may expose infrastructure details to the admin UI.

## Performance

No significant query-volume issue. Connection tests are synchronous network operations and can block the Livewire request until the database timeout behavior resolves.

## Security / Data Integrity

### P1 — Missing mutation authorization

`testConnection()` and `save()` do not enforce `system.env.update`.

### P1 — Secret hydration

Existing `DB_PASSWORD` is loaded into public Livewire state. Prefer a write-only replacement-secret pattern: do not prefill the current password; preserve the existing secret unless an authorized operator explicitly supplies a replacement.

### P1 — Infrastructure error disclosure

`DbConnectionService` returns raw connection exception messages. These may contain host/driver/network details.

### P1 — Cross-module rendering

The component renders an Admin view despite a System-owned Blade existing. This weakens module ownership and makes future changes harder to reason about.

## UI/UX Compliance

Positive:

- responsive form layout;
- password masking;
- loading/disabled states;
- connection status feedback.

Needs improvement:

- validation errors for all connection fields;
- explicit confirmation/warning before replacing production DB connection;
- avoid showing an existing secret as public component state;
- resolve System/Admin view ownership.

## Test Coverage

No System-specific Feature or Unit test was found in the current `tests/Feature` or `tests/Unit` trees for this component.

Critical missing tests:

- unauthorized save/test rejection;
- preserve existing password when replacement is blank;
- invalid DB configuration rejection;
- `.env` rollback on failed update;
- config cache behavior;
- System-local view ownership.

## Issue List

### P1 — Missing `system.env.update` action authorization

**File:** `Modules/System/Livewire/Settings/DatabaseConfig.php`

**Impact:** an authenticated admin able to reach or invoke the component may attempt a production database configuration mutation without the intended capability check at the mutation boundary.

**Recommendation:** enforce `system.env.update` inside sensitive actions through the System authorization concern/pattern.

### P1 — Existing database password exposed through public Livewire state

**Recommendation:** do not hydrate the current password; use an optional replacement password and preserve current value when empty.

### P1 — Incomplete validation and raw connection errors

**Recommendation:** validate all connection fields and map connection failures to safe operator messages while logging technical details server-side.

### P2 — Cross-module Blade ownership drift

**Recommendation:** render the canonical System view unless there is a documented reason for Admin ownership.

## Recommended Direction

**Major Refactor, not rebuild.** Keep `EnvManagerService` and its transactional file-write safeguards. Refactor authorization, secret handling, validation, error redaction, and view ownership around the existing service.

## Open Questions / Unknowns

- Whether the Admin-owned database-config Blade is intentionally canonical or leftover duplication.
- Whether production policy should allow database connection changes from the web UI at all.
- Whether config reload/restart is required for all deployed PHP runtime models after changing DB settings.
