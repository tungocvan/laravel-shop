# System Module Analysis

Analysis date: 2026-06-22

Scope: `Modules/System` only. This document is analysis only; no refactor or code change is included.

## 1. Module Purpose

`Modules/System` is a shell/admin operations module for privileged system management. It exposes screens for:

- System tab dashboard and module toggling.
- General site settings stored in the shared `settings` table.
- `.env` based database, mail, payment, social, queue, storage, and NodeJS bridge configuration.
- Database table listing, SQL backup, restore, truncate, drop, and download.
- Free-form Artisan command execution.
- Shell script creation, editing, execution, and deletion.

This module overlaps heavily with `Modules/Admin` for settings, database, env, and operational tooling. The roadmap already flags System as a P0 security containment area.

## Analysis Flow

### Route

Routes are defined in `Modules/System/routes/web.php` and `Modules/System/routes/api.php`.

| Method | URI | Name | Controller | View / Component |
|---|---|---|---|---|
| GET | `/admin/system` | `admin.system.index` | `Modules\System\Http\Controllers\SystemController@index` | `System::system` |
| GET | `/admin/system/modules` | `admin.system.modules` | `Modules\System\Http\Controllers\SettingController@modules` | `System::pages.settings.modules` |
| GET | `/admin/system/settings` | `admin.system.settings.index` | `Modules\System\Http\Controllers\SettingController@index` | `System::pages.settings.index` |
| GET | `/admin/system/settings/env` | `admin.system.settings.env` | `Modules\System\Http\Controllers\EnvConfigController@index` | `System::pages.settings.env` |
| GET | `/admin/system/database` | `admin.system.database.index` | `Modules\System\Http\Controllers\DatabaseController@index` | `System::pages.database` |
| GET | `/admin/system/database/download/{filename}` | `admin.system.database.download` | `Modules\System\Http\Controllers\DatabaseController@download` | SQL file download |
| GET | `/api/system` | unnamed | `Modules\System\Http\Controllers\Api\SystemController@index` | Missing method |

Web routes use only `web` and `auth:admin`. There is no capability middleware such as `system.manage`, `database.restore`, or `database.destroy`.

### Controller

- `Modules/System/Http/Controllers/SystemController.php`
  - Loads tabs from `Modules\System\Services\SystemConfigService`.
  - Checks Livewire component registration before rendering `System::system`.
  - Permission middleware is commented out.

- `Modules/System/Http/Controllers/SettingController.php`
  - Returns settings/module/profile views.
  - `profile()` has no route in this module.

- `Modules/System/Http/Controllers/EnvConfigController.php`
  - Defines env tabs inline and renders `System::pages.settings.env`.
  - Duplicates tab configuration already present in `Modules/System/config/system_tabs.php`.

- `Modules/System/Http/Controllers/DatabaseController.php`
  - Renders database manager page.
  - Downloads SQL backups by filename through `DatabaseService::getDownloadPath()`.
  - Authorization is commented out.

- `Modules/System/Http/Controllers/Api/SystemController.php`
  - Empty controller.
  - `Modules/System/routes/api.php` routes to `index`, but no `index()` method exists.

### Page Blade

- `Modules/System/resources/views/system.blade.php`
  - Main System dashboard, dynamically mounts components from configured tabs.

- `Modules/System/resources/views/pages/database.blade.php`
  - Database manager page; mounts `system.database.table-list`.

- `Modules/System/resources/views/pages/settings/index.blade.php`
  - Settings page; mounts `system.settings.setting-form`.

- `Modules/System/resources/views/pages/settings/env.blade.php`
  - Env settings page; dynamically mounts configured Livewire components.

- `Modules/System/resources/views/pages/settings/modules.blade.php`
  - Module toggle page; mounts `system.settings.modules-form`.

- `Modules/System/resources/views/pages/settings/profile.blade.php`
  - Profile page; mounts Website account profile components, but no System route points to it.

- `Modules/System/resources/views/pages/index.blade.php`
  - Placeholder page; no System route points to it.

- `Modules/System/resources/views/pages/settings/placeholder.blade.php`
  - Placeholder page; no System route points to it.

### Livewire PHP

Database components:

- `Modules/System/Livewire/Database/TableList.php`
  - Lists tables, full backup, per-table backup/restore, truncate, drop, full restore from selected backup path.

- `Modules/System/Livewire/Database/BackupManager.php`
  - Backup history and restore component, but calls missing `DatabaseService::getBackupFiles()` and `DatabaseService::restore()` methods.

- `Modules/System/Livewire/Database/ImportDrawer.php`
  - SQL import upload, but calls missing `DatabaseService::import()` method.

Settings components:

- `Modules/System/Livewire/Settings/SettingForm.php`
  - Tab shell for theme, general, menu, image, SEO, and custom settings.

- `Modules/System/Livewire/Settings/ModulesForm.php`
  - Enables/disables modules by rewriting module manifest PHP files.

- `Modules/System/Livewire/Settings/ArtisanList.php`
  - Runs arbitrary Artisan command text from the browser.

- `Modules/System/Livewire/Settings/ShScript.php`
  - Lists, creates, edits, deletes, chmods, and executes scripts under `app/sh`.

- `Modules/System/Livewire/Settings/DatabaseConfig.php`
  - Reads/writes database `.env` settings, tests connection, backs up `.env`, clears config cache.
  - Renders `Admin::livewire.settings.database-config`, not the System view.

- `Modules/System/Livewire/Settings/MomoConfig.php`
  - Reads/writes MoMo env settings and tests endpoint.
  - Renders `Admin::livewire.settings.momo-config`, not the System view.

- `Modules/System/Livewire/Settings/MailConfig.php`
  - Reads/writes mail env settings and sends test mail.

- `Modules/System/Livewire/Settings/AdvancedConfig.php`
  - Reads/writes queue, NodeJS URL, and bridge secret settings; dispatches queue test job.

- `Modules/System/Livewire/Settings/SocialConfig.php`
  - Reads/writes social login, TinyMCE, and Google Analytics env values.

- `Modules/System/Livewire/Settings/EnvManager.php`
  - Exports current `.env` to suffixed files; `getTabsDefinition()` is empty.

- `Modules/System/Livewire/Settings/StorageConfig.php`
  - Placeholder render-only component.

- `Modules/System/Livewire/Settings/Placeholder.php`
  - Placeholder render-only component.

Settings partial components:

- `Modules/System/Livewire/Settings/Partials/General.php`
  - Reads/writes `site_name` and `site_email`.

- `Modules/System/Livewire/Settings/Partials/Images.php`
  - Uploads/removes logo and favicon under public storage.

- `Modules/System/Livewire/Settings/Partials/Seo.php`
  - Reads/writes SEO title, description, social links, and header scripts.

- `Modules/System/Livewire/Settings/Partials/Custom.php`
  - Dynamic settings fields, images, galleries, and HTML values.

### Livewire Blade

- `Modules/System/resources/views/livewire/database/table-list.blade.php`
  - Database table list, full backup, full restore modal, per-table backup/download/restore/truncate/drop controls.

- `Modules/System/resources/views/livewire/database/backup-manager.blade.php`
  - Backup history UI; likely not mounted by current page.

- `Modules/System/resources/views/livewire/database/import-drawer.blade.php`
  - SQL import drawer; likely not mounted by current page.

- `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`
  - Free-form Artisan terminal UI.

- `Modules/System/resources/views/livewire/settings/sh-script.blade.php`
  - Shell script editor and executor UI.

- `Modules/System/resources/views/livewire/settings/setting-form.blade.php`
  - Settings tab shell; also pushes jQuery/Summernote CDN assets.

- `Modules/System/resources/views/livewire/settings/modules-form.blade.php`
  - Module registry toggle UI.

- `Modules/System/resources/views/livewire/settings/mail-config.blade.php`
  - Mail env form and test email UI.

- `Modules/System/resources/views/livewire/settings/advanced-config.blade.php`
  - Queue and NodeJS bridge form.

- `Modules/System/resources/views/livewire/settings/social-config.blade.php`
  - Social/SEO env form.

- `Modules/System/resources/views/livewire/settings/database-config.blade.php`
  - System DB env view exists but is not used by `DatabaseConfig::render()`.

- `Modules/System/resources/views/livewire/settings/momo-config.blade.php`
  - System MoMo view exists but is not used by `MomoConfig::render()`.

- `Modules/System/resources/views/livewire/settings/storage-config.blade.php`
  - Placeholder/storage UI.

- `Modules/System/resources/views/livewire/settings/env-manager.blade.php`
  - Env snapshot buttons; commented out in page wrappers.

- `Modules/System/resources/views/livewire/settings/placeholder.blade.php`
  - Placeholder.

- `Modules/System/resources/views/livewire/settings/partials/general.blade.php`
  - General settings inputs include keys not present in PHP state.

- `Modules/System/resources/views/livewire/settings/partials/images.blade.php`
  - Logo/favicon upload UI.

- `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`
  - SEO/social/header script UI.

- `Modules/System/resources/views/livewire/settings/partials/custom.blade.php`
  - Dynamic settings UI.

### Shared Components

- `Modules/System/resources/views/components/placeholder.blade.php`
  - Anonymous System placeholder component.

- `Modules/System/resources/views/livewire/placeholder.blade.php`
  - Livewire placeholder view.

Cross-module components mounted from System:

- `admin.theme-switcher` in `Modules/System/config/system_tabs.php` and `Modules/System/Livewire/Settings/SettingForm.php`.
- `admin.header.menu-manager` in `Modules/System/Livewire/Settings/SettingForm.php`.
- `website.account.profile.user-profile` and `website.account.profile.user-address` in `Modules/System/resources/views/pages/settings/profile.blade.php`.

### Service

- `Modules/System/Services/DatabaseService.php`
  - Public methods: `getAllTables`, `backupTable`, `backupFullDatabase`, `restoreTable`, `truncateTable`, `dropTable`, `getDownloadPath`, `getAllBackupFiles`, `restoreFromFile`.

- `Modules/System/Services/SystemConfigService.php`
  - Public methods: `getTabs`, `updateTab`, `save`, `reset`, `clearCache`.

- `Modules/System/Services/Database/DbConnectionService.php`
  - Public methods: `testConnection`.

- `Modules/System/Services/Env/EnvManagerService.php`
  - Public methods: `exportToEnvironment`, `getValues`, `update`.

- `Modules/System/Services/Env/EnvBackupService.php`
  - Public methods: `createBackup`.

- `Modules/System/Services/Env/MailConfigService.php`
  - Public methods: `testSendMail`.

- `Modules/System/Services/Env/SocialConfigService.php`
  - Public methods: `validateCredentials`.

- `Modules/System/Services/Env/SystemConfigService.php`
  - Public methods: `pingNodeJS`, `dispatchTestJob`, `checkQueueStatus`.

### Import/Export

There are no classes under `Modules/System/Imports` or `Modules/System/Exports`.

Database SQL import/export is implemented manually:

- Export/backup: `Modules/System/Services/DatabaseService.php`.
- Import/restore: `Modules/System/Services/DatabaseService.php` and `Modules/System/Livewire/Database/ImportDrawer.php`.
- No use of `Modules/Shared/Services/ImportExport`.

### Model

- `Modules/System/Models/Setting.php`
  - Eloquent model for `settings` table by convention.
  - Fillable: `key`, `value`, `group_name`, `type`, `label`.
  - Static helpers: `getValue`, `setValue`.
  - Cache keys: `setting_{key}`.

### Migration

No `Modules/System/database/migrations` directory was found.

The `settings` table migration appears to live outside this module:

- `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`

This means `Modules/System/Models/Setting.php` depends on an Admin-owned migration. The migration filename also has a negative year, which is already called out by the roadmap as migration hygiene risk.

## 2. Route List

See the route table in the Route section above.

Recommendations:

- P0: Add explicit permission middleware/policy checks to all `Modules/System/routes/web.php` privileged routes.
- P0: Remove or protect the unauthenticated `Modules/System/routes/api.php` `/api/system` route, and add the missing controller method only if the endpoint has a real purpose.
- P1: Rename permission concepts from generic `view_system/create_system/edit_system/delete_system` in `Modules/System/config/module.php` to explicit capabilities such as `system.manage`, `database.backup`, `database.restore`, `database.destroy`, and `system.commands`.

## 3. Controllers

Main findings:

- `Modules/System/Http/Controllers/SystemController.php` has commented-out permission middleware.
- `Modules/System/Http/Controllers/DatabaseController.php` has commented-out authorization for backup downloads.
- `Modules/System/Http/Controllers/EnvConfigController.php` duplicates tab definitions instead of using `Modules/System/config/system_tabs.php`.
- `Modules/System/Http/Controllers/Api/SystemController.php` is empty while `Modules/System/routes/api.php` calls `index`.
- `Modules/System/Http/Controllers/SettingController.php` exposes `profile()` but no System route maps to it.

Recommendations:

- P0: Enforce capability checks in `Modules/System/Http/Controllers/DatabaseController.php` before database downloads.
- P0: Enforce capability checks in `Modules/System/Http/Controllers/SystemController.php` and `Modules/System/Http/Controllers/EnvConfigController.php` before exposing operational tabs.
- P1: Consolidate tab source in `Modules/System/Http/Controllers/EnvConfigController.php` with `Modules/System/Services/SystemConfigService.php`.
- P2: Remove or route `Modules/System/Http/Controllers/SettingController.php::profile()` after confirming intended ownership.

## 4. Page Blade Files

Main findings:

- `Modules/System/resources/views/system.blade.php` and `Modules/System/resources/views/pages/settings/env.blade.php` both dynamically render configured Livewire components.
- `Modules/System/resources/views/pages/settings/profile.blade.php` references Website account components from a System settings page.
- `Modules/System/resources/views/pages/index.blade.php` and `Modules/System/resources/views/pages/settings/placeholder.blade.php` look like unused placeholder pages.

Recommendations:

- P0: Restrict configurable dynamic component rendering in `Modules/System/resources/views/system.blade.php` and `Modules/System/resources/views/pages/settings/env.blade.php` to a server-side allowlist of safe System components.
- P1: Remove cross-module profile UI from `Modules/System/resources/views/pages/settings/profile.blade.php` or document why System owns that page.
- P2: Delete confirmed unused placeholders after route and view reference tests.

## 5. Livewire PHP Classes

Main findings:

- `Modules/System/Livewire/Settings/ArtisanList.php` runs arbitrary command input via `Artisan::call($this->artisanCommand)`.
- `Modules/System/Livewire/Settings/ShScript.php` writes executable files under `app/sh` and executes them through `shell_exec`.
- `Modules/System/Livewire/Database/TableList.php` exposes backup, restore, truncate, and drop actions with no authorization inside the methods.
- `Modules/System/Livewire/Settings/ModulesForm.php` rewrites module manifest PHP files with no authorization inside the method.
- `Modules/System/Livewire/Settings/DatabaseConfig.php`, `MailConfig.php`, `MomoConfig.php`, `AdvancedConfig.php`, and `SocialConfig.php` write `.env` values with no authorization inside the methods.
- `Modules/System/Livewire/Database/BackupManager.php` calls `DatabaseService` methods that do not exist.
- `Modules/System/Livewire/Database/ImportDrawer.php` calls `DatabaseService::import()`, which does not exist.
- `Modules/System/Livewire/Settings/EnvManager.php::getTabsDefinition()` returns nothing, so `render()` passes null tabs.

Recommendations:

- P0: Disable or remove `Modules/System/Livewire/Settings/ArtisanList.php` and `Modules/System/Livewire/Settings/ShScript.php` in production; replace with allowlisted operations.
- P0: Add authorization checks inside every mutating method in `Modules/System/Livewire/Database/TableList.php`, `Modules/System/Livewire/Settings/ModulesForm.php`, and all env-writing config components.
- P1: Fix stale API calls in `Modules/System/Livewire/Database/BackupManager.php` and `Modules/System/Livewire/Database/ImportDrawer.php`, or remove those components if unused.
- P1: Add explicit Livewire validation rules for all env/config fields before writes.
- P2: Complete or remove `Modules/System/Livewire/Settings/EnvManager.php`.

## 6. Livewire Blade Views

Main findings:

- `Modules/System/resources/views/livewire/database/table-list.blade.php` passes table names and full backup file paths from the browser back into destructive Livewire methods.
- `Modules/System/resources/views/livewire/database/table-list.blade.php` uses `wire:confirm`, but confirmation is client-side only and not a security boundary.
- `Modules/System/resources/views/livewire/settings/artisan-list.blade.php` presents a production-mode free-form terminal and suggests destructive commands such as `migrate:fresh`.
- `Modules/System/resources/views/livewire/settings/sh-script.blade.php` provides a browser shell script editor/executor.
- `Modules/System/resources/views/livewire/settings/partials/seo.blade.php` renders `seo_description` with raw `{!! !!}` output.
- `Modules/System/resources/views/livewire/settings/partials/images.blade.php` uses `temporaryUrl()` for uploaded previews; Livewire image validation exists in PHP for Images, but Custom uploads lack equivalent validation.

Recommendations:

- P0: Replace browser-supplied backup file paths in `Modules/System/resources/views/livewire/database/table-list.blade.php` with server-issued opaque identifiers.
- P0: Remove destructive command suggestions from `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`.
- P1: Escape or sanitize raw SEO preview output in `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`.
- P1: Add server-side confirmation tokens or audit records for destructive database actions; do not rely on Blade `wire:confirm`.

## 7. Services and Public Methods

See the Service section above for the complete public method list.

Main findings:

- `Modules/System/Services/DatabaseService.php` builds shell command strings for `mysqldump` and `mysql` with credentials in command arguments.
- `Modules/System/Services/DatabaseService.php` accepts table names from callers and interpolates them into SQL and shell commands without validating against schema metadata.
- `Modules/System/Services/DatabaseService.php::restoreFromFile()` accepts an absolute file path from Livewire state and drops all database tables.
- `Modules/System/Services/DatabaseService.php::truncateTable()` and `dropTable()` disable foreign key checks without a `finally` safety restore.
- `Modules/System/Services/Env/EnvManagerService.php` edits `.env` using regex without key allowlisting.
- `Modules/System/Services/Env/EnvManagerService.php::exportToEnvironment()` accepts a suffix and writes `.env.{suffix}` without validating the suffix.
- `Modules/System/Services/SystemConfigService.php::clearCache()` forgets `system_tabs`, but `getTabs()` stores under timestamped keys.

Recommendations:

- P0: Replace shell command strings in `Modules/System/Services/DatabaseService.php` with `Process` argument arrays and avoid passing DB passwords in process arguments.
- P0: Validate table names in `Modules/System/Services/DatabaseService.php` against live schema metadata before backup, restore, truncate, drop, or analyze.
- P0: Replace path-based restore in `Modules/System/Services/DatabaseService.php::restoreFromFile()` with server-issued backup IDs constrained to approved backup directories.
- P0: Restore `FOREIGN_KEY_CHECKS` in `finally` blocks in `Modules/System/Services/DatabaseService.php`.
- P1: Add env key allowlists and suffix validation to `Modules/System/Services/Env/EnvManagerService.php`.
- P2: Fix cache invalidation in `Modules/System/Services/SystemConfigService.php`.

## 8. Models and Database Tables

| Model | Table | Migration ownership |
|---|---|---|
| `Modules\System\Models\Setting` | `settings` | `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php` |

Main findings:

- `Modules/System/Models/Setting.php` and `Modules/Admin/Models/Setting.php` both appear to model the same `settings` table.
- `Modules/System/Models/Setting.php` uses static helpers and direct cache keys, duplicating settings access patterns in Admin/Website services.
- System has no local migration for the `settings` table.

Recommendations:

- P1: Decide canonical ownership for the `settings` table and model; avoid parallel `Setting` models in System and Admin.
- P1: Move shared settings access to a single service/model owner and update System callers after tests exist.
- P1: Repair the malformed Admin migration filename that owns `settings` as part of migration hygiene.

## 9. Import/Export Classes

No System import/export classes exist.

Main findings:

- `Modules/System/Livewire/Database/ImportDrawer.php` is an import UI, but `Modules/System/Services/DatabaseService.php` does not implement the called `import()` method.
- SQL backup/restore is implemented as direct database operations, not through `Modules/Shared/Services/ImportExport`.
- Full backups are stored in `storage/app/backups`, while table backups use `storage/app/private/backups`; download logic only checks `backups`.

Recommendations:

- P0: Treat SQL import/restore as destructive database restore and gate it with `database.restore` plus explicit audited confirmation.
- P1: Either remove `Modules/System/Livewire/Database/ImportDrawer.php` or implement a safe, private, allowlisted SQL restore service.
- P1: Standardize backup storage paths and retention policy in `Modules/System/Services/DatabaseService.php`.

## 10. Authorization/Security Risks

- P0: `Modules/System/routes/web.php` protects privileged operations only with `auth:admin`; add explicit capability authorization.
- P0: `Modules/System/routes/api.php` exposes `/api/system` without auth and points at a missing method.
- P0: `Modules/System/Livewire/Settings/ArtisanList.php` allows arbitrary Artisan execution.
- P0: `Modules/System/Livewire/Settings/ShScript.php` allows arbitrary executable script creation and shell execution.
- P0: `Modules/System/Services/DatabaseService.php` exposes database credentials through shell command arguments.
- P0: `Modules/System/Livewire/Database/TableList.php` and `Modules/System/Services/DatabaseService.php` allow destructive table/database actions based on browser-provided identifiers.
- P0: `Modules/System/Livewire/Settings/ModulesForm.php` rewrites PHP manifest files from a Livewire method without per-action authorization.
- P0: `Modules/System/Services/Env/EnvManagerService.php` allows `.env` writes for caller-supplied keys.
- P1: `Modules/System/resources/views/livewire/settings/partials/seo.blade.php` renders database-controlled SEO description as raw HTML.

## 11. Validation Problems

- P0: `Modules/System/Livewire/Settings/ArtisanList.php` validates only non-empty command text.
- P0: `Modules/System/Livewire/Settings/ShScript.php` does not validate script filename for path separators, extension, traversal, or shell safety.
- P0: `Modules/System/Livewire/Database/TableList.php` does not validate table names or backup selections before destructive operations.
- P1: `Modules/System/Livewire/Settings/DatabaseConfig.php` has no rules for host, port, database, username, driver, or password shape.
- P1: `Modules/System/Livewire/Settings/MailConfig.php` validates only test recipient, not saved mail config.
- P1: `Modules/System/Livewire/Settings/MomoConfig.php`, `AdvancedConfig.php`, and `SocialConfig.php` lack comprehensive validation for URLs, secrets, IDs, and enum values.
- P1: `Modules/System/Livewire/Settings/Partials/Custom.php` does not validate uploaded image/gallery MIME and size.
- P1: `Modules/System/Livewire/Settings/Partials/General.php` PHP state omits `site_hotline` and `site_address`, but the Blade view binds those keys.

## 12. Transaction Risks

- P0: `Modules/System/Services/DatabaseService.php::restoreFromFile()` drops all tables before importing SQL and has no rollback path if import fails.
- P0: `Modules/System/Services/DatabaseService.php::dropTable()` disables foreign keys outside a transaction and without `finally`.
- P1: `Modules/System/Services/DatabaseService.php::truncateTable()` wraps statements in a transaction, but MySQL `TRUNCATE` causes implicit commits, so rollback assumptions are unsafe.
- P1: `Modules/System/Livewire/Settings/Partials/Custom.php::save()` updates multiple settings/files without a transaction or compensating file cleanup.
- P1: `Modules/System/Livewire/Settings/Partials/Images.php::save()` deletes old files before all writes are guaranteed complete.
- P1: `Modules/System/Livewire/Settings/ModulesForm.php` rewrites manifests without atomic write/backup.
- P1: `Modules/System/Services/Env/EnvManagerService.php::update()` rewrites `.env` without locking or atomic replace.

## 13. N+1/Query Performance Risks

- P1: `Modules/System/Livewire/Settings/Partials/Custom.php::loadSettings()` loads all custom settings unpaginated and can grow without bounds.
- P1: `Modules/System/Livewire/Database/TableList.php::render()` runs `SHOW TABLE STATUS` on every render; search and selection can trigger frequent full metadata scans.
- P1: `Modules/System/Services/DatabaseService.php::getAllTables()` checks `Storage::disk('local')->exists()` for every table on each render.
- P2: `Modules/System/Livewire/Settings/Partials/General.php` and `Seo.php` call `Setting::getValue()` per key; cache mitigates this, but cache misses create multiple queries.

## 14. Duplicate Logic

- P1: `Modules/System/Http/Controllers/EnvConfigController.php` duplicates env tab definitions instead of using `Modules/System/config/system_tabs.php`.
- P1: `Modules/System/Livewire/Settings/DatabaseConfig.php` and `MomoConfig.php` render Admin views while System views also exist.
- P1: `Modules/System/Services/Env/*` mirrors Admin/Website env service patterns.
- P1: `Modules/System/Models/Setting.php` duplicates `Modules/Admin/Models/Setting.php` for the same table.
- P1: `Modules/System/Services/DatabaseService.php` appears to mirror Admin database management behavior while stale components still call older Admin-style method names.

## 15. Files That Look Unused

These require confirmation with route/component tests before deletion:

- P2: `Modules/System/resources/views/pages/index.blade.php` - no System route found.
- P2: `Modules/System/resources/views/pages/settings/placeholder.blade.php` - no System route found.
- P2: `Modules/System/resources/views/pages/settings/profile.blade.php` - controller method exists but no route found in System.
- P2: `Modules/System/resources/views/livewire/placeholder.blade.php` - no class render target found except generic placeholder convention.
- P2: `Modules/System/resources/views/components/placeholder.blade.php` - included only by the likely unused page index.
- P2: `Modules/System/Livewire/Database/BackupManager.php` - no current System page mounts it, and it calls missing service methods.
- P2: `Modules/System/Livewire/Database/ImportDrawer.php` - no current System page mounts it, and it calls missing service method.
- P2: `Modules/System/Livewire/Settings/EnvManager.php` - commented out in page wrappers and has incomplete tab definition method.
- P2: `Modules/System/Livewire/Settings/StorageConfig.php` - render-only placeholder.
- P2: `Modules/System/Livewire/Settings/Placeholder.php` - render-only placeholder.
- P2: `Modules/System/resources/views/livewire/settings/database-config.blade.php` - System class renders Admin view instead.
- P2: `Modules/System/resources/views/livewire/settings/momo-config.blade.php` - System class renders Admin view instead.

## 16. Refactor Plan

### P0 Critical

- P0: Add explicit permissions for System routes and Livewire mutating methods in `Modules/System/routes/web.php`, `Modules/System/Http/Controllers/*`, and `Modules/System/Livewire/**/*.php`.
- P0: Disable arbitrary Artisan execution in `Modules/System/Livewire/Settings/ArtisanList.php`; replace with a server-side allowlist if any commands remain.
- P0: Disable shell script editing/execution in `Modules/System/Livewire/Settings/ShScript.php`; replace with audited, predefined operations if needed.
- P0: Harden database backup/restore/destructive operations in `Modules/System/Services/DatabaseService.php` and `Modules/System/Livewire/Database/TableList.php`.
- P0: Remove browser-provided backup paths from `Modules/System/resources/views/livewire/database/table-list.blade.php` and `TableList.php`.
- P0: Protect or remove `/api/system` in `Modules/System/routes/api.php`.
- P0: Add safe error redaction so raw process/DB exception text is not dispatched to users from `Modules/System/Livewire/Database/TableList.php` and `Modules/System/Services/DatabaseService.php`.

### P1 Important

- P1: Consolidate System/Admin duplicate settings and env implementations, starting with canonical ownership for the `settings` table and `Setting` model.
- P1: Fix broken `DatabaseService` method references in `Modules/System/Livewire/Database/BackupManager.php` and `ImportDrawer.php`, or remove unused components.
- P1: Add comprehensive validation rules for all env/config Livewire components.
- P1: Make `.env`, module manifest, and settings/file writes atomic where practical.
- P1: Standardize backup storage paths, retention, private visibility, and download authorization.
- P1: Replace direct dynamic component rendering with an allowlisted component registry in System tab views.
- P1: Reduce database metadata scans in `TableList` with debounce, pagination, or cached metadata where safe.
- P1: Repair migration ownership and malformed settings migration filename.

### P2 Nice To Have

- P2: Remove confirmed unused placeholder pages, placeholder Livewire classes, and stale database components after route/component tests.
- P2: Fix `SystemConfigService::clearCache()` to invalidate timestamped tab cache keys reliably.
- P2: Normalize view namespaces so System components render System views instead of Admin views where System remains the owner.
- P2: Replace CDN-pushed jQuery/Summernote assets in `Modules/System/resources/views/livewire/settings/setting-form.blade.php` with the project asset pipeline if the editor is still needed.
- P2: Add module documentation for the final System ownership boundary after P0/P1 cleanup.
