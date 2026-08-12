# Settings/DatabaseConfig Livewire Refactor Plan

Plan date: 2026-08-12

Scope: `Modules/System/Livewire/Settings/DatabaseConfig.php`, its canonical System Blade, database/env orchestration service(s), focused tests, and normalization of the existing `/admin/system/settings/env` Admin Menu entry.

Status: **Awaiting explicit approval before implementation.**

## 1. Goal

Refactor DatabaseConfig into a thin, authorized UI for testing and changing the application's primary database connection while preserving the strong `.env` write safeguards already implemented by `EnvManagerService`.

Primary goals:

- enforce `system.env.update` at every sensitive action boundary;
- never hydrate the existing `DB_PASSWORD` into public Livewire state;
- preserve the existing password when the replacement field is blank;
- validate and allowlist connection configuration;
- sanitize connection-test failures shown to the browser;
- move orchestration out of Livewire;
- render the System-owned Blade instead of the duplicate Admin-owned view;
- normalize the existing ENV Admin Menu permission;
- retain `.env` backup, validation, lock, in-place write and rollback safeguards.

This is a major refactor, not a rebuild.

## 2. Route, Parent Page and Admin Menu

DatabaseConfig is not a standalone route. `EnvConfigController` mounts it as the `database` tab inside:

```text
GET /admin/system/settings/env
name: admin.system.settings.env
middleware: auth:admin + permission:system.env.view,admin
```

No new route or separate DatabaseConfig menu item should be created.

The existing Admin Menu entry is currently legacy:

```text
Name: Quản lý ENV
URL: /admin/system/settings/env
Can: view_role
```

Normalize the existing entry to:

```text
Name: Quản lý ENV
URL: /admin/system/settings/env
Can: system.env.view
Active: true
```

Do not create a duplicate menu entry. For existing installations provide a targeted idempotent update only; do not reset/reseed the full menu table.

## 3. Authorization

Page/menu visibility remains:

`system.env.view`

Both sensitive Livewire actions must enforce:

`system.env.update`

using `AuthorizesSystemActions`:

- `testConnection()`
- `save()`

Although testConnection does not persist data, it accepts database credentials and initiates an outbound infrastructure connection attempt, so it belongs behind the update/privileged boundary rather than ordinary view access.

No new permission is required because both `system.env.view` and `system.env.update` already exist in the System manifest.

## 4. Secret Handling — Write-only Password

Current behavior hydrates `DB_PASSWORD` from `.env` into public Livewire state. This must stop.

Target behavior:

- `mount()` loads driver/host/port/database/username only;
- public password field starts as an empty string;
- UI label explains: blank means preserve current password;
- current password must never be returned to the browser/Livewire snapshot;
- when testing or saving, the server-side service reads the current `.env` value and substitutes it only if replacement password is blank;
- after successful save, reset the public replacement password back to empty;
- logs must never include username/password pairs or password hashes/body.

A blank replacement is not the same as setting DB_PASSWORD to empty. This task adopts explicit preserve-on-blank semantics for backward-safe secret handling.

## 5. Validation / Connection Allowlist

Livewire validates before service execution.

Proposed rules:

```text
DB_CONNECTION: required|in:mysql,pgsql
DB_HOST: required|string|max:255
DB_PORT: required|integer|min:1|max:65535
DB_DATABASE: required|string|max:255
DB_USERNAME: required|string|max:255
DB_PASSWORD: nullable|string|max:4096
```

The current UI claims MySQL/PostgreSQL support, so the initial allowlist should be limited to `mysql` and `pgsql` unless repository evidence requires another driver.

The service must defensively re-check the allowlisted driver and required fixed keys so crafted Livewire state cannot introduce arbitrary `.env` variable names or arbitrary Laravel connection config.

No browser-provided extra `.env` keys are accepted.

## 6. New Database Configuration Orchestration Service

Create a focused service, proposed:

`Modules/System/Services/Database/DatabaseConfigService.php`

Responsibilities:

### Read public configuration

Return only non-secret current values:

- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`

Never return current `DB_PASSWORD`.

### Resolve effective candidate

Accept only the fixed six DB keys.

If replacement `DB_PASSWORD` is blank:

- load current DB_PASSWORD server-side from `EnvManagerService::getValues()`;
- use it only in the temporary connection candidate and eventual persisted data;
- never send it back to Livewire.

### Test connection

- validate/normalize fixed config;
- call a hardened `DbConnectionService`;
- return a small safe result (`success`, generic message);
- log only safe metadata such as actor ID, driver, host/database if repository policy permits; never password;
- always purge the temporary connection/config after testing so stale credentials do not remain in runtime connection state.

### Save

1. resolve effective fixed candidate server-side;
2. re-test connection immediately before persistence;
3. if test fails, do not touch `.env`;
4. call `EnvManagerService::update()` with DB keys only;
5. rely on EnvManagerService's existing persistent safety backup + dotenv validation + lock + in-place write + rollback-on-write-failure;
6. clear Laravel configuration cache after successful write;
7. log safe operation metadata;
8. return a generic success result.

Do not update arbitrary env values.

## 7. Remove Duplicate Backup Orchestration

Current `DatabaseConfig::save()` explicitly calls `EnvBackupService::createBackup()` and then `EnvManagerService::update()`.

`EnvManagerService::update()` already creates its own safety backup before writing, with restrictive permissions and rollback behavior.

For DatabaseConfig, remove the redundant explicit `EnvBackupService` call from this workflow and use the canonical `EnvManagerService` safety backup only.

Do not delete `EnvBackupService` from the repository in this component-level task because other components may still depend on it.

## 8. Harden DbConnectionService

Current service returns raw exception text and leaves the temporary connection config/runtime connection available after the call.

Refactor requirements:

- support only caller-validated mysql/pgsql candidates;
- build driver-appropriate config (e.g. PostgreSQL charset/schema options must not blindly reuse MySQL collation fields where inappropriate);
- wrap connection attempt in try/catch/finally;
- log exception class/details server-side without credentials;
- return generic browser-safe failure text;
- `DB::purge($tempConn)` in `finally`;
- remove temporary connection config after test where practical;
- never expose PDO/SQL exception message to Livewire.

Tests must verify raw connection exception text is not returned.

## 9. Livewire Responsibility

Keep in Livewire:

- non-secret form state;
- write-only replacement password field;
- validation;
- authorization;
- service delegation;
- connection status UI state;
- `canUpdate` UI capability state;
- safe notifications.

Move out of Livewire:

- direct `.env` reads containing password;
- password preservation logic;
- backup/update orchestration;
- connection candidate construction;
- direct `Artisan::call('config:clear')`;
- infrastructure exception handling details.

## 10. View Ownership

Current PHP renders:

`Admin::livewire.settings.database-config`

while an equivalent canonical System view already exists:

`System::livewire.settings.database-config`

Change `render()` to the System-owned view and update only that System Blade going forward.

Do not delete the duplicate Admin Blade in this component-level refactor unless a repository-wide reference check proves it is unused; document it as legacy duplication if left in place.

## 11. Blade / UX

Use the System-owned Blade and preserve the current layout while improving:

- add an explicit DB driver select (`mysql`, `pgsql`) so the field is visible and intentional;
- inline validation errors for every field;
- password label/placeholder: `Để trống để giữ mật khẩu hiện tại`;
- never display current password or a fake prefilled secret;
- disable all test/save controls for users without `system.env.update`;
- add a read-only notice for view-only users;
- keep loading/disabled state;
- reset connection status to untested whenever any connection field changes;
- Save must re-test server-side regardless of the prior UI status;
- add `wire:confirm` before Save warning that changing the primary DB can make the application unavailable;
- clearly state that config applies to subsequent requests/processes and workers may need restart depending on deployment model.

## 12. Runtime / Deployment Semantics

Writing `.env` and clearing config cache does not guarantee already-running long-lived PHP/queue processes reload environment/config immediately.

This refactor should not attempt to restart PHP-FPM/queue/processes from the web UI.

UI/documentation should state that deployment/runtime processes may require restart/reload after a DB configuration change.

The component must not auto-run migrations against the newly configured database.

## 13. Error Handling / Logging

Browser errors must be generic.

Safe log metadata may include:

- actor/admin ID;
- operation (`database.config.test`, `database.config.save`);
- driver;
- whether password was replaced (`true/false`), never the password;
- connection-test success/failure;
- exception class;
- `.env` update stage/result.

Do not log:

- DB_PASSWORD;
- full request/form payload;
- DSN containing credentials;
- raw `.env` contents;
- PDO exception text in browser notifications.

## 14. Data Integrity / Concurrency

`EnvManagerService` already serializes actual `.env` writes with file locking and restores original content if writing fails.

DatabaseConfigService should add a short application-level lock around the save workflow so two admins cannot concurrently perform test→write sequences against different candidates.

Suggested lock:

`system:database-config:update`

If unavailable, return a controlled generic message that another database configuration update is in progress.

The lock is for workflow serialization; the EnvManager file lock remains the final write safeguard.

## 15. Tests

Create focused tests, proposed:

`tests/Feature/System/SystemDatabaseConfigTest.php`

Coverage:

1. `/admin/system/settings/env` route requires `system.env.view`;
2. canonical ENV Admin Menu uses `/admin/system/settings/env` + `system.env.view`;
3. DatabaseConfig uses `AuthorizesSystemActions`;
4. `testConnection()` enforces `system.env.update`;
5. `save()` enforces `system.env.update`;
6. mount/public form does not hydrate current DB_PASSWORD;
7. blank replacement password preserves existing server-side password;
8. explicit replacement password is used/persisted but never returned by read API;
9. allowed drivers are mysql/pgsql only;
10. invalid port/host/database/username fail validation before connection attempt;
11. DbConnectionService returns generic failure, not raw exception detail;
12. temporary connection is purged after success/failure;
13. failed connection test prevents `.env` update;
14. EnvManagerService remains the canonical backup/write/rollback mechanism;
15. DatabaseConfig no longer calls EnvBackupService directly;
16. save uses application-level lock;
17. successful save clears config cache only after env update succeeds;
18. raw exceptions/passwords are not flashed or logged by component contract;
19. render uses `System::livewire.settings.database-config`;
20. Blade includes password-preserve guidance, driver select, validation errors, confirmation and read-only state;
21. no new route/menu is created for DatabaseConfig.

High-impact filesystem tests should use a controlled temporary env fixture/subclass where possible and must not overwrite the real developer `.env` during tests.

## 16. Files Expected to Change

Application:

- `Modules/System/Livewire/Settings/DatabaseConfig.php`
- `Modules/System/resources/views/livewire/settings/database-config.blade.php`
- `Modules/System/Services/Database/DatabaseConfigService.php` (new)
- `Modules/System/Services/Database/DbConnectionService.php`
- `Modules/Admin/data/menus.json` (normalize existing ENV menu permission)

Reused safeguards:

- `Modules/System/Services/Env/EnvManagerService.php`

Potentially unchanged/legacy:

- `Modules/System/Services/Env/EnvBackupService.php` (not used by DatabaseConfig after refactor; do not delete globally)
- `Modules/Admin/resources/views/livewire/settings/database-config.blade.php` (legacy duplicate, leave unless proven globally unused)

Tests:

- `tests/Feature/System/SystemDatabaseConfigTest.php` (new)

Documentation:

- `docs/modules/System/livewire/settings-database-config/ANALYSIS.md`
- `docs/modules/System/livewire/settings-database-config/REFACTOR_PLAN.md`

No database migration is planned.
No new route is planned.
No new permission is planned.
No auto migration/restart operation is planned.

## 17. Existing Installation Menu Update

For an existing database, target the current `Quản lý ENV` entry under `Công cụ Hệ thống` and idempotently set:

```text
url: /admin/system/settings/env
can: system.env.view
is_active: true
```

Preserve parent/name/order unless normalization is required.

Do not reset/reseed unrelated menu rows.

## 18. Acceptance Criteria

- [ ] DatabaseConfig remains the database tab inside `/admin/system/settings/env`;
- [ ] no duplicate route/menu is created;
- [ ] ENV page/menu visibility uses `system.env.view`;
- [ ] test/save enforce `system.env.update`;
- [ ] current DB password is never hydrated into public Livewire state;
- [ ] blank replacement preserves the current password server-side;
- [ ] only fixed DB env keys are accepted;
- [ ] mysql/pgsql are allowlisted;
- [ ] all form fields have validation;
- [ ] raw connection errors are not returned to browser;
- [ ] temp DB connection is purged after tests;
- [ ] Livewire no longer orchestrates backup/env write/config clear directly;
- [ ] redundant explicit EnvBackupService call is removed from this workflow;
- [ ] EnvManagerService safety backup/lock/in-place-write/rollback remains intact;
- [ ] save workflow is serialized with an application lock;
- [ ] canonical System Blade is rendered;
- [ ] save has production-impact confirmation and read-only UX;
- [ ] no automatic process restart or DB migration is introduced;
- [ ] focused tests pass;
- [ ] no destructive menu/database reset occurs.

## 19. Approval Gate

Per `.codex/tasks/refactor-livewire.md`, implementation must not begin until the user explicitly approves this plan.
