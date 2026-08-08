# System Module Overview

Assessment date: 2026-06-22

Scope: `Modules/System` compared against `docs/modules/System/ANALYSIS.md`, `docs/modules/System/REFACTOR_PLAN.md`, and `docs/modules/System/REBUILD_SPEC.md`. `docs/modules/System/INFORMATION.md` does not exist. Actual source code is treated as the source of truth.

## 1. Responsibility

The `System` module is a shell/admin operations module. In the current source it is responsible for:

- Rendering System admin pages under `/admin/system`.
- Managing System tabs through `Modules/System/config/system_tabs.php`, `Modules/System/data/system_tabs.json`, and `Modules/System/Services/SystemConfigService.php`.
- Displaying and mutating database state through backup, restore, truncate, drop, and download flows in `Modules/System/Services/DatabaseService.php` and `Modules/System/Livewire/Database/TableList.php`.
- Editing `.env`-backed database, mail, payment, social, queue, NodeJS bridge, and storage settings through Livewire components under `Modules/System/Livewire/Settings`.
- Toggling module manifests through `Modules/System/Livewire/Settings/ModulesForm.php`.
- Managing general/site settings through `Modules/System/Models/Setting.php` and partial Livewire components.
- Running operational checks such as queue test and NodeJS bridge health.
- Exposing high-risk command surfaces: arbitrary Artisan execution and shell script editing/execution.

This responsibility is too broad for the current authorization and validation boundaries. The module should remain an operations shell, but its privileged capabilities need a safe rebuild before production exposure.

## 2. Documentation Consistency

The current documentation is mostly consistent with the actual source code.

- `ANALYSIS.md` is still valid. The source still has `auth:admin` without capability-level permissions in `Modules/System/routes/web.php`, an unauthenticated `/api/system` route in `Modules/System/routes/api.php`, missing API `index()` in `Modules/System/Http/Controllers/Api/SystemController.php`, arbitrary `Artisan::call()` in `Modules/System/Livewire/Settings/ArtisanList.php`, `shell_exec()` in `Modules/System/Livewire/Settings/ShScript.php`, unsafe database operations in `Modules/System/Services/DatabaseService.php`, missing `DatabaseService::getBackupFiles()`, `restore()`, and `import()` methods referenced by Livewire components, and no `Modules/System/database` directory.
- `REFACTOR_PLAN.md` is still valid. Its P0 containment plan maps directly to the current source.
- `REBUILD_SPEC.md` is still valid as a target architecture. One note remains: it correctly says the request text previously mentioned Category but the actual module target is System.
- `INFORMATION.md` is missing. Needs verification whether this doc is expected before implementation begins.

No meaningful conflict was found where existing module docs contradict current source. Some command output showed mojibake in comments/UI strings, but that does not change the architectural findings.

## 3. Stable Parts To Preserve

Preserve these parts, after adding authorization and validation where needed:

- Module discovery compatibility: `Modules/System/config/module.php` is a valid manifest and marks System as a `shell` module.
- Route/page structure under `/admin/system`: the broad page categories are useful even though permission middleware must be added.
- View namespace usage for most System views: `System::...` files are registered by `Modules/ModuleServiceProvider.php`.
- Livewire auto-registration layout under `Modules/System/Livewire`.
- `Modules/System/Services/SystemConfigService.php` concept: a tab registry service is useful, but it needs an allowlist and cache invalidation fix.
- Settings partial concepts: general, image, SEO, and custom settings are useful if canonical settings ownership is settled.
- Queue test concept in `Modules/System/Jobs/TestQueueJob.php` and `Modules/System/Livewire/Settings/AdvancedConfig.php`, if kept as an authorized diagnostics feature.

## 4. Parts To Refactor

Refactor these in place after P0 containment:

- `Modules/System/routes/web.php`: add explicit permission middleware.
- `Modules/System/config/module.php`: replace generic permissions with capability names such as `system.manage`, `system.env.update`, `database.backup`, `database.restore`, and `database.destroy`.
- `Modules/System/Http/Controllers/SystemController.php`, `EnvConfigController.php`, and `DatabaseController.php`: keep thin but add authorization and remove duplicated tab source logic.
- `Modules/System/Services/SystemConfigService.php`: centralize tabs, validate JSON overrides, forbid arbitrary Livewire aliases, and fix `clearCache()`.
- Env/config Livewire components: add Livewire 3 validation, method-level authorization, secret handling, and safe user messages.
- `Modules/System/Livewire/Settings/ModulesForm.php`: move manifest writes to a service and make writes atomic.
- `Modules/System/Livewire/Settings/Partials/General.php`: align PHP state with Blade fields `site_hotline` and `site_address`.
- `Modules/System/Livewire/Settings/Partials/Images.php` and `Custom.php`: add safe file lifecycle, validation, and compensating cleanup.
- `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`: remove raw SEO preview output or sanitize it.

## 5. Parts To Rebuild

Rebuild these as safe workflows rather than patching lightly:

- Database backup/restore/destructive workflow:
  - `Modules/System/Services/DatabaseService.php`
  - `Modules/System/Livewire/Database/TableList.php`
  - `Modules/System/resources/views/livewire/database/table-list.blade.php`
  - `Modules/System/Http/Controllers/DatabaseController.php`

  This area needs server-side table allowlists, opaque backup identifiers, private storage, safe Symfony Process argument arrays, redacted errors, audit records, and explicit confirmations.

- SQL import/restore:
  - `Modules/System/Livewire/Database/ImportDrawer.php`
  - `Modules/System/resources/views/livewire/database/import-drawer.blade.php`

  The current component calls a missing service method and should remain disabled until a safe restore service exists.

- Backup history:
  - `Modules/System/Livewire/Database/BackupManager.php`
  - `Modules/System/resources/views/livewire/database/backup-manager.blade.php`

  Current calls reference missing service methods. Rebuild only if the UI is still needed.

- Settings/model ownership:
  - `Modules/System/Models/Setting.php`
  - settings partial components
  - `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`

  Needs verification before coding because it crosses module boundaries.

## 6. Parts To Rewrite From Scratch

Rewrite or remove these rather than preserving current implementation:

- `Modules/System/Livewire/Settings/ArtisanList.php` and `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`: current free-form command execution is not a safe base. If retained, rebuild as an allowlisted operations panel.
- `Modules/System/Livewire/Settings/ShScript.php` and `Modules/System/resources/views/livewire/settings/sh-script.blade.php`: current browser-driven script editor/executor is remote command execution risk. If retained at all, rebuild as predefined audited operations.
- `Modules/System/routes/api.php` and `Modules/System/Http/Controllers/Api/SystemController.php`: current API route is unauthenticated and points at a missing action. Remove or rewrite only for a confirmed API consumer.
- Placeholder/incomplete files after verification:
  - `Modules/System/Livewire/Settings/EnvManager.php`
  - `Modules/System/Livewire/Settings/StorageConfig.php`
  - `Modules/System/Livewire/Settings/Placeholder.php`
  - `Modules/System/resources/views/pages/index.blade.php`
  - `Modules/System/resources/views/pages/settings/placeholder.blade.php`
  - `Modules/System/resources/views/livewire/placeholder.blade.php`
  - `Modules/System/resources/views/components/placeholder.blade.php`

## 7. Security Risks

Risk level is high.

- Privileged web routes use only `auth:admin`; no explicit capability middleware in `Modules/System/routes/web.php`.
- Public API route in `Modules/System/routes/api.php` is unauthenticated and points to a missing method.
- Arbitrary Artisan execution in `Modules/System/Livewire/Settings/ArtisanList.php`.
- Shell script creation/editing/deletion/execution in `Modules/System/Livewire/Settings/ShScript.php`.
- Database credentials are passed through shell command strings in `Modules/System/Services/DatabaseService.php`.
- Browser-supplied table names and backup paths reach destructive methods in `Modules/System/Livewire/Database/TableList.php` and `Modules/System/Services/DatabaseService.php`.
- `FOREIGN_KEY_CHECKS` is disabled without guaranteed `finally` restoration in database operations.
- `.env` writes are broad and regex-based in `Modules/System/Services/Env/EnvManagerService.php`.
- Raw exception output is dispatched to UI in database and env/config components.
- Raw HTML output appears in `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`.

## 8. Performance Risks

- `Modules/System/Livewire/Database/TableList.php::render()` calls `DatabaseService::getAllTables()` on every render.
- `Modules/System/Services/DatabaseService.php::getAllTables()` performs `SHOW TABLE STATUS` and per-table storage checks, which can become expensive on large schemas.
- Custom settings are loaded unpaginated in `Modules/System/Livewire/Settings/Partials/Custom.php`.
- Settings reads use per-key static calls in `Modules/System/Models/Setting.php`; cache helps, but cache misses create multiple queries.
- Long-running backups/restores run synchronously from Livewire requests rather than queue jobs.

## 9. Maintainability Risks

- System duplicates Admin/Website settings and env patterns.
- `Modules/System/Models/Setting.php` models the `settings` table while the migration lives in Admin.
- Some System Livewire classes render Admin views: `DatabaseConfig.php` and `MomoConfig.php`.
- `EnvConfigController.php` duplicates tab definitions instead of using the tab service.
- Dynamic `Livewire::mount()` from config/JSON lacks a server-side component allowlist.
- Components call missing service methods: `getBackupFiles`, `restore`, and `import`.
- Several placeholder/incomplete files remain registered by module discovery.
- Comments and UI strings show encoding corruption, making maintenance harder. Needs verification before changing text files.

## 10. Validity Of Existing Planning Docs

- `ANALYSIS.md`: valid and aligned with current source.
- `REFACTOR_PLAN.md`: valid and should remain the tactical plan.
- `REBUILD_SPEC.md`: valid as the implementation specification for a safe rebuild. It is intentionally conservative and correctly marks cross-module ownership as needing confirmation.
- `INFORMATION.md`: missing; should be created after the final module boundary is confirmed.

## Final Recommendation

Decision:
- Safe rebuild

Reason:

The module should not be kept as-is because it exposes production-control capabilities with insufficient authorization, validation, path/identifier control, and error redaction. A small partial refactor would leave too much risky behavior in place. A full rewrite from scratch is not necessary because the route/page shell, tab concept, many settings forms, and module discovery conventions are usable. The safest path is a staged rebuild: first contain P0 security risks, then rebuild the database/command/env workflows around Laravel 12 services, Livewire 3 validation, explicit permissions, private storage, and tests.

Risk level:
- High

Suggested next step:

Implement the first P0 safety slice only: protect/remove `/api/system`, add explicit System/database permissions, disable free-form Artisan and shell script tabs in production, and harden database backup/restore inputs before any broader settings ownership or cleanup work.
