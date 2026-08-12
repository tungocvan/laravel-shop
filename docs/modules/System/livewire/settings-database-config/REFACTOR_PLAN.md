# Settings/DatabaseConfig Livewire Refactor Plan

Plan date: 2026-08-12

Status: **Implemented — pending local focused test verification.**

## Approved Scope

Refactor `Modules/System/Livewire/Settings/DatabaseConfig.php` and its direct database/env workflow without rebuilding the feature.

The approved implementation requirements were:

- preserve `/admin/system/settings/env` as the parent page;
- keep `system.env.view` for page/menu visibility;
- enforce `system.env.update` on `testConnection()` and `save()`;
- never hydrate the current `DB_PASSWORD` into Livewire state;
- treat blank password input as “preserve current password” server-side;
- allowlist mysql/pgsql and validate fixed DB fields;
- move test/write/cache-clear orchestration into a dedicated service;
- keep `EnvManagerService` as the canonical `.env` validation/backup/lock/in-place-write/rollback boundary;
- remove redundant `EnvBackupService` orchestration from this component workflow;
- redact infrastructure exceptions from browser responses;
- purge temporary DB connections after testing;
- serialize save workflow with an application lock;
- render the System-owned Blade;
- normalize the existing `Quản lý ENV` menu permission to `system.env.view`;
- add focused tests;
- do not add migrations, process restarts, database migrations, new route, new permission, or duplicate menu.

## Implemented Files

Application:

- `Modules/System/Services/Database/DatabaseConfigService.php` — new orchestration boundary.
- `Modules/System/Services/Database/DbConnectionService.php` — hardened temporary connection testing.
- `Modules/System/Livewire/Settings/DatabaseConfig.php` — thin authorized Livewire UI.
- `Modules/System/resources/views/livewire/settings/database-config.blade.php` — canonical System UI with validation, write-only password guidance, confirmation and read-only state.
- `Modules/Admin/data/menus.json` — existing ENV entry normalized to `system.env.view`.

Tests:

- `tests/Feature/System/SystemDatabaseConfigTest.php`.

Documentation:

- `docs/modules/System/livewire/settings-database-config/ANALYSIS.md`.
- this plan.

## Implemented Security / Integrity Flow

```text
/admin/system/settings/env
→ system.env.view
→ DatabaseConfig
→ system.env.update
→ validation
→ DatabaseConfigService
→ effective password resolved server-side
→ DbConnectionService temporary test
→ DB::purge temporary connection
→ application lock system:database-config:update
→ EnvManagerService::update fixed DB keys only
→ dotenv validation + safety backup + file lock + in-place write/rollback
→ config:clear
```

The browser never receives the existing DB password. Raw PDO/driver exceptions are not returned to the browser. DatabaseConfig no longer calls `EnvBackupService`, `EnvManagerService` or Artisan directly.

## Admin Menu Result

No new DatabaseConfig menu was created. The existing parent entry is canonicalized to:

```text
Name: Quản lý ENV
URL: /admin/system/settings/env
Can: system.env.view
Active: true
```

Existing database installations should apply only a targeted idempotent update to this row; no menu reset/reseed is required.

## Acceptance Status

- [x] DatabaseConfig remains a tab inside `/admin/system/settings/env`.
- [x] No duplicate route/menu created.
- [x] ENV page/menu uses `system.env.view`.
- [x] Test/save enforce `system.env.update`.
- [x] Existing DB password is not hydrated into public Livewire state.
- [x] Blank replacement preserves current password server-side.
- [x] Only fixed DB keys are accepted by orchestration.
- [x] mysql/pgsql allowlisted.
- [x] Livewire validation added.
- [x] Raw connection exception text removed from browser response.
- [x] Temporary connection purged after tests.
- [x] Livewire no longer orchestrates env backup/write/config-clear directly.
- [x] Redundant explicit EnvBackupService call removed from this workflow.
- [x] EnvManagerService safeguards retained.
- [x] Save workflow uses application-level lock.
- [x] Canonical System Blade rendered.
- [x] Production-impact confirmation/read-only UX added.
- [x] No auto restart or migration added.
- [x] Focused tests added.
- [ ] Focused tests verified on the user's runtime.
