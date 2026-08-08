# System Rebuild Specification

This specification targets `Modules/System` only and follows the `@architect rebuild-spec System` workflow.

This is an implementation specification only. Do not write code from this document until P0 safety decisions are confirmed.

## 1. Goal

The rebuilt/refactored System module must become a secure Laravel 12 and Livewire 3 operations module for trusted administrators. It must expose system configuration, database backup/restore, module toggles, and operational health checks only through explicit capability permissions, validated inputs, auditable workflows, and safe service contracts.

Design decisions:

- Disable arbitrary Artisan and shell execution from HTTP/Livewire in production. Reference: `ANALYSIS.md` sections 5, 6, 10; `REFACTOR_PLAN.md` P0-02 and P0-03.
- Require capability-level authorization for routes, controllers, and Livewire mutating methods. Reference: `ANALYSIS.md` sections 2, 3, 5, 10; `REFACTOR_PLAN.md` P0-01.
- Harden database backup, download, restore, truncate, and drop operations with server-side allowlists, opaque backup IDs, private storage, safe process execution, and audit logs. Reference: `ANALYSIS.md` sections 6, 7, 9, 10, 12; `REFACTOR_PLAN.md` P0-04, P0-07, P1-11.
- Keep System as a shell/support operations module and avoid expanding it into Admin, Website, or general settings ownership until module ownership is decided. Reference: `ANALYSIS.md` sections 8, 14; `REFACTOR_PLAN.md` P1-05, P1-14, Risk Control.
- Rebuild Livewire components around validated state, explicit events, bounded queries, and service-driven business logic. Reference: `ANALYSIS.md` sections 11, 12, 13; `REFACTOR_PLAN.md` P1-01, P1-10, P1-12.

## 2. Target Architecture

Flow:

Route
-> Controller
-> Page Blade
-> Livewire PHP
-> Livewire Blade
-> Shared Components
-> Service
-> Import
-> Export
-> Model
-> Migration

### Route

Target route groups:

- `Modules/System/routes/web.php`
  - `GET /admin/system` with `auth:admin` and `permission:system.manage`.
  - `GET /admin/system/settings` with `auth:admin` and `permission:system.settings.view`.
  - `GET /admin/system/settings/env` with `auth:admin` and `permission:system.env.view`.
  - `GET /admin/system/modules` with `auth:admin` and `permission:system.modules.view`.
  - `GET /admin/system/database` with `auth:admin` and `permission:database.view`.
  - `GET /admin/system/database/download/{backup}` with `auth:admin` and `permission:database.download`, using server-issued backup IDs rather than raw filenames.

Design decision: Keep only routes that have a real page and permission. Reference: `ANALYSIS.md` sections 2, 3, 4; `REFACTOR_PLAN.md` P0-01, P0-04, P1-14.

- `Modules/System/routes/api.php`
  - Remove `/api/system`, or protect it with `auth:sanctum` and explicit ability if a real API is required.

Design decision: Default to removal unless a concrete API consumer exists. Needs confirmation before coding. Reference: `ANALYSIS.md` sections 2, 3, 10; `REFACTOR_PLAN.md` P0-05.

### Controller

Controllers remain thin:

- `Modules/System/Http/Controllers/SystemController.php`
  - Authorize `system.manage`.
  - Load allowlisted tabs from System tab service.
  - Render `System::system`.

- `Modules/System/Http/Controllers/SettingController.php`
  - Render settings and modules pages only.
  - Remove or relocate `profile()` unless System intentionally owns it.

- `Modules/System/Http/Controllers/EnvConfigController.php`
  - Authorize `system.env.view`.
  - Load env tabs from the same tab registry service as `SystemController`.

- `Modules/System/Http/Controllers/DatabaseController.php`
  - Authorize database view/download permissions.
  - Delegate backup lookup and download response data to a database backup service.

- `Modules/System/Http/Controllers/Api/SystemController.php`
  - Remove or implement only after API ownership is confirmed.

Design decision: Controllers should orchestrate authorization, service calls, and views only. Reference: `ANALYSIS.md` section 3; `REFACTOR_PLAN.md` P0-01, P0-05, P1-04.

### Page Blade

Page Blade files:

- `Modules/System/resources/views/system.blade.php`
- `Modules/System/resources/views/pages/database.blade.php`
- `Modules/System/resources/views/pages/settings/index.blade.php`
- `Modules/System/resources/views/pages/settings/env.blade.php`
- `Modules/System/resources/views/pages/settings/modules.blade.php`

Remove after verification:

- `Modules/System/resources/views/pages/index.blade.php`
- `Modules/System/resources/views/pages/settings/placeholder.blade.php`
- `Modules/System/resources/views/pages/settings/profile.blade.php`, unless ownership is confirmed.

Design decision: Page Blade files mount only allowlisted Livewire components and never accept arbitrary component aliases from JSON/config. Reference: `ANALYSIS.md` sections 4, 6, 15; `REFACTOR_PLAN.md` P1-04, P2-01, P1-14.

### Livewire PHP

Target component groups:

- Database:
  - `Modules/System/Livewire/Database/TableList.php`
  - `Modules/System/Livewire/Database/BackupManager.php` only if retained and reconciled with the service contract.
  - `Modules/System/Livewire/Database/ImportDrawer.php` disabled until a safe restore contract exists.

- Settings:
  - `Modules/System/Livewire/Settings/SettingForm.php`
  - `Modules/System/Livewire/Settings/ModulesForm.php`
  - `Modules/System/Livewire/Settings/DatabaseConfig.php`
  - `Modules/System/Livewire/Settings/MailConfig.php`
  - `Modules/System/Livewire/Settings/MomoConfig.php`
  - `Modules/System/Livewire/Settings/AdvancedConfig.php`
  - `Modules/System/Livewire/Settings/SocialConfig.php`
  - `Modules/System/Livewire/Settings/Partials/General.php`
  - `Modules/System/Livewire/Settings/Partials/Images.php`
  - `Modules/System/Livewire/Settings/Partials/Seo.php`
  - `Modules/System/Livewire/Settings/Partials/Custom.php`

Remove/disable:

- `Modules/System/Livewire/Settings/ArtisanList.php` in production.
- `Modules/System/Livewire/Settings/ShScript.php` in production.
- `Modules/System/Livewire/Settings/EnvManager.php` unless completed.
- `Modules/System/Livewire/Settings/StorageConfig.php` unless completed.
- `Modules/System/Livewire/Settings/Placeholder.php` after verification.

Design decision: Every mutating Livewire method must authorize, validate, call a service, and emit safe events. Reference: `ANALYSIS.md` sections 5, 10, 11; `REFACTOR_PLAN.md` P0-01, P1-01.

### Livewire Blade

Livewire Blade files must:

- Show validation errors and permission-denied states.
- Use Bootstrap 5/AdminLTE 4-compatible layout conventions.
- Avoid raw `{!! !!}` for user/database-controlled content.
- Never submit raw table names, backup file paths, command names, script paths, or component aliases as trusted server decisions.

Design decision: Blade is a presentation layer only; all dangerous decisions are revalidated server-side. Reference: `ANALYSIS.md` section 6; `REFACTOR_PLAN.md` P0-04, P1-09.

### Shared Components

Use System-local components only for stable System UI. Do not add new shared components until the same UI contract is needed by another module.

Potential shared components:

- Confirmation modal for destructive operations.
- Permission-denied state.
- Safe operation status panel.

Needs confirmation before coding: whether these belong in `Modules/System` or `Modules/Shared`.

Reference: `REFACTOR_PLAN.md` Risk Control; `CODEX_BOOTSTRAP.md` Shared module guidance.

### Service

Target service layer:

- `Modules/System/Services/SystemTabService.php` or refactored `SystemConfigService`.
- `Modules/System/Services/Database/DatabaseMetadataService.php`.
- `Modules/System/Services/Database/DatabaseBackupService.php`.
- `Modules/System/Services/Database/DatabaseRestoreService.php`.
- `Modules/System/Services/Env/EnvManagerService.php`.
- `Modules/System/Services/Env/EnvBackupService.php`.
- `Modules/System/Services/Env/MailConfigService.php`.
- `Modules/System/Services/Env/SocialConfigService.php`.
- `Modules/System/Services/Env/SystemConfigService.php`.
- `Modules/System/Services/ModuleManifestService.php`.
- `Modules/System/Services/SettingsService.php` only if System remains settings owner. Needs confirmation before coding.

Design decision: Split `DatabaseService.php` into smaller services if refactoring scope allows; otherwise harden the existing service first. Reference: `ANALYSIS.md` section 7; `REFACTOR_PLAN.md` P0-04, P1-11.

### Import

No general import classes are required for System. SQL import/restore is a destructive restore workflow, not a normal row import.

Design decision: Keep `Modules/System/Livewire/Database/ImportDrawer.php` disabled or remove it until a safe restore service exists. Reference: `ANALYSIS.md` sections 5, 9; `REFACTOR_PLAN.md` P0-07, P1-03.

### Export

System export means database backup generation, not business data export.

Design decision: Implement backups through a backup service with private storage, metadata, retention, permission checks, and chunked/streamed download where appropriate. Reference: `ANALYSIS.md` sections 7, 9; `REFACTOR_PLAN.md` P0-04, P1-11.

### Model

Existing:

- `Modules/System/Models/Setting.php`

Potential new model:

- `Modules/System/Models/SystemBackup.php` or similar backup metadata model. Needs confirmation before coding.
- `Modules/System/Models/SystemAuditLog.php` only if no existing audit infrastructure exists. Needs confirmation before coding.

Design decision: Do not add backup/audit models until storage/audit ownership is confirmed. Reference: `REFACTOR_PLAN.md` P0-04, P0-07, Risk Control.

### Migration

Current System has no migrations.

Potential migrations:

- Backup metadata table.
- Operational audit table.
- Settings table ownership migration only after cross-module ownership is confirmed.

Design decision: Do not move or rename `settings` migration during P0. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-05, P1-13, Risk Control.

## 3. Database Design

### Tables

Existing table dependency:

- `settings`
  - Currently modeled by `Modules/System/Models/Setting.php`.
  - Migration appears in `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`.
  - Needs confirmation before coding: canonical ownership should be System, Admin, Shared, or another support module.

Potential new table:

- `system_backups`
  - Stores server-issued backup metadata.
  - Needed if opaque backup IDs, retention, status, and auditability are not available elsewhere.
  - Reference: `REFACTOR_PLAN.md` P0-04, P1-11.

Potential new table:

- `system_operation_audits`
  - Stores immutable audit records for destructive or sensitive operations.
  - Needs confirmation before coding if the project already has an audit package/table.
  - Reference: `REFACTOR_PLAN.md` P0-04, P0-07, P0-06.

### Columns

`settings` target columns, based on current usage:

- `id`
- `key`
- `value`
- `group_name`
- `type`
- `label`
- `created_at`
- `updated_at`

Design decision: Do not change the `settings` schema until ownership is confirmed. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-05.

`system_backups` proposed columns, if implemented:

- `id`
- `uuid`
- `disk`
- `path`
- `original_name`
- `backup_type` such as `full` or `table`.
- `table_name` nullable.
- `size_bytes`
- `checksum` nullable.
- `status` such as `pending`, `completed`, `failed`, `expired`.
- `created_by_admin_id` nullable depending on admin model ownership.
- `expires_at` nullable.
- `created_at`
- `updated_at`

`system_operation_audits` proposed columns, if implemented:

- `id`
- `operation`
- `target_type`
- `target_identifier`
- `status`
- `requested_by_admin_id` nullable depending on admin model ownership.
- `ip_address`
- `user_agent`
- `correlation_id`
- `metadata` JSON with redacted values only.
- `created_at`
- `updated_at`

### Indexes

`settings` target indexes:

- Unique index on `key`.
- Optional index on `group_name`.

`system_backups` proposed indexes:

- Unique index on `uuid`.
- Index on `backup_type`.
- Index on `status`.
- Index on `expires_at`.
- Index on `created_by_admin_id` if the admin model/table is confirmed.

`system_operation_audits` proposed indexes:

- Index on `operation`.
- Index on `status`.
- Index on `requested_by_admin_id` if available.
- Index on `correlation_id`.
- Index on `created_at`.

### Foreign Keys

- `system_backups.created_by_admin_id` should reference the admin users table only after the admin guard model/table is confirmed. Needs confirmation before coding.
- `system_operation_audits.requested_by_admin_id` should reference the admin users table only after the admin guard model/table is confirmed. Needs confirmation before coding.

Design decision: Avoid guessing admin table names in migrations. Reference: `REFACTOR_PLAN.md` Risk Control.

### Constraints

- `settings.key` must be unique.
- `settings.type` should be constrained by app validation to allowed values such as `text`, `textarea`, `image`, `html`, `gallery`, `boolean`, `json`.
- `system_backups.backup_type` and `status` should be enforced by validation or DB enum/check according to project migration compatibility.
- `system_operation_audits.metadata` must not store secrets.

### Migration Notes

- Do not rename `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php` during P0. Reference: `REFACTOR_PLAN.md` P1-13.
- Add migration smoke tests before changing settings ownership. Reference: `ROADMAP.md` P1-08 and `REFACTOR_PLAN.md` P1-13.
- New System migrations, if approved, should live under `Modules/System/database/migrations`.
- For SQLite test compatibility, avoid MySQL-specific check constraints unless covered by conditional migration logic.

## 4. Model Design

### Model Classes

Current:

- `Modules\System\Models\Setting`

Potential:

- `Modules\System\Models\SystemBackup` if backup metadata table is approved.
- `Modules\System\Models\SystemOperationAudit` if audit table is approved.

Needs confirmation before coding: whether `Setting` should remain in System or move to a canonical settings owner. Reference: `REFACTOR_PLAN.md` P1-05.

### Fillable Fields

`Setting`:

- `key`
- `value`
- `group_name`
- `type`
- `label`

`SystemBackup`, if implemented:

- `uuid`
- `disk`
- `path`
- `original_name`
- `backup_type`
- `table_name`
- `size_bytes`
- `checksum`
- `status`
- `created_by_admin_id`
- `expires_at`

`SystemOperationAudit`, if implemented:

- `operation`
- `target_type`
- `target_identifier`
- `status`
- `requested_by_admin_id`
- `ip_address`
- `user_agent`
- `correlation_id`
- `metadata`

### Casts

`Setting`:

- Keep `value` as string unless a canonical settings service handles typed values.
- Needs confirmation before coding: add typed casts by setting type only if existing callers are migrated.

`SystemBackup`, if implemented:

- `size_bytes` integer.
- `expires_at` datetime.

`SystemOperationAudit`, if implemented:

- `metadata` array.

### Relationships

`Setting`:

- None required.

`SystemBackup`, if implemented:

- `createdByAdmin()` belongs-to admin user model. Needs confirmation before coding.

`SystemOperationAudit`, if implemented:

- `requestedByAdmin()` belongs-to admin user model. Needs confirmation before coding.

### Scopes

`Setting`:

- `scopeGroup($query, string $groupName)` if System remains owner.

`SystemBackup`, if implemented:

- `scopeActive()`
- `scopeExpired()`
- `scopeType(string $type)`
- `scopeCompleted()`

`SystemOperationAudit`, if implemented:

- `scopeOperation(string $operation)`
- `scopeStatus(string $status)`

### Accessors / Mutators

`Setting`:

- Avoid magic JSON casting until canonical ownership is settled.
- Use a settings service for typed reads/writes instead.

`SystemBackup`, if implemented:

- `getHumanSizeAttribute()` optional for UI only; avoid using it in core logic.

Design decision: Keep models thin; put operational logic in services. Reference: `CODEX_BOOTSTRAP.md` Service Layer Architecture; `REFACTOR_PLAN.md` P1-05.

## 5. Service Design

### Service Classes

Target services:

- `Modules/System/Services/SystemConfigService.php` or renamed `SystemTabService`
  - Own allowlisted tab definitions and JSON override validation.
  - Reference: `REFACTOR_PLAN.md` P1-04, P2-03.

- `Modules/System/Services/DatabaseService.php`
  - Either harden as a facade or split into smaller services.
  - Reference: `REFACTOR_PLAN.md` P0-04, P1-11.

- `Modules/System/Services/Database/DatabaseMetadataService.php`
  - Table metadata, schema allowlist, search, and bounded listing.
  - Reference: `REFACTOR_PLAN.md` P1-12.

- `Modules/System/Services/Database/DatabaseBackupService.php`
  - Full/table backup creation, private storage, metadata, download resolution, retention cleanup.
  - Reference: `REFACTOR_PLAN.md` P0-04, P1-11.

- `Modules/System/Services/Database/DatabaseRestoreService.php`
  - Safe restore from server-approved backups only; no raw path restore.
  - Reference: `REFACTOR_PLAN.md` P0-07.

- `Modules/System/Services/Env/EnvManagerService.php`
  - Allowlisted `.env` reads/writes with locking and atomic replacement.
  - Reference: `REFACTOR_PLAN.md` P1-02.

- `Modules/System/Services/ModuleManifestService.php`
  - Atomic module manifest updates.
  - Reference: `REFACTOR_PLAN.md` P1-10.

- `Modules/System/Services/SettingsService.php`
  - Batch settings get/set only if System remains settings owner.
  - Needs confirmation before coding.
  - Reference: `REFACTOR_PLAN.md` P1-05, P2-05.

### Public Methods

`SystemConfigService` target methods:

- `getTabs(string $context): array`
- `getAllowedComponents(string $context): array`
- `updateTab(string $id, array $data): void`
- `resetOverrides(): void`
- `clearCache(): void`

`DatabaseMetadataService` target methods:

- `listTables(string $search = '', int $page = 1, int $perPage = 25): array`
- `assertAllowedTable(string $tableName): void`
- `protectedTables(): array`

`DatabaseBackupService` target methods:

- `createFullBackup(AdminUser $actor): BackupResult`
- `createTableBackup(string $tableName, AdminUser $actor): BackupResult`
- `listBackups(array $filters = []): array`
- `resolveDownload(string $backupUuid, AdminUser $actor): DownloadResult`
- `pruneExpired(): int`

`DatabaseRestoreService` target methods:

- `restoreTableFromBackup(string $backupUuid, string $tableName, AdminUser $actor): OperationResult`
- `restoreDatabaseFromBackup(string $backupUuid, AdminUser $actor, string $confirmation): OperationResult`
- `validateBackupForRestore(string $backupUuid): RestorePreflightResult`

`EnvManagerService` target methods:

- `getValues(array $allowedKeys): array`
- `update(array $data, array $allowedKeys, AdminUser $actor): void`
- `exportToEnvironment(string $suffix, AdminUser $actor): string`

`ModuleManifestService` target methods:

- `listModules(): array`
- `toggleModule(string $moduleName, bool $enabled, AdminUser $actor): void`

### Responsibilities

- Livewire handles UI state and validation trigger.
- Services enforce domain invariants, server-side allowlists, storage paths, process calls, and transactions/atomic writes.
- Controllers authorize and render pages.

Reference: `CODEX_BOOTSTRAP.md` Livewire and Service Layer Architecture; `REFACTOR_PLAN.md` P1-01, P1-10.

### Transaction Boundaries

- Settings batch writes should use database transactions.
- Custom settings with file uploads should use DB transaction plus compensating file cleanup.
- Database destructive operations cannot rely on normal DB transactions for `DROP`/`TRUNCATE`; they require preflight backup, confirmation, audit records, and safe failure handling.
- `.env` and manifest writes require file locks and atomic rename rather than DB transactions.

Reference: `ANALYSIS.md` section 12; `REFACTOR_PLAN.md` P1-10.

### Business Rules

- No destructive database action without permission, server-side target validation, explicit confirmation, and audit record.
- No arbitrary command execution in production.
- No raw exception details returned to UI.
- No `.env` key updates outside component-specific allowlists.
- No dynamic component rendering outside System-approved component aliases.

Reference: `REFACTOR_PLAN.md` P0-01 through P0-07.

## 6. Livewire Design

### Component List

Keep and harden:

- `system.database.table-list`
- `system.settings.setting-form`
- `system.settings.modules-form`
- `system.settings.database-config`
- `system.settings.mail-config`
- `system.settings.momo-config`
- `system.settings.advanced-config`
- `system.settings.social-config`
- `system.settings.partials.general`
- `system.settings.partials.images`
- `system.settings.partials.seo`
- `system.settings.partials.custom`

Disable/remove or rebuild later:

- `system.settings.artisan-list`
- `system.settings.sh-script`
- `system.database.import-drawer`
- `system.database.backup-manager`
- `system.settings.env-manager`
- `system.settings.storage-config`
- `system.settings.placeholder`

Reference: `ANALYSIS.md` sections 5 and 15; `REFACTOR_PLAN.md` P0-02, P0-03, P1-03, P2-01, P2-02.

### State Properties

`TableList`:

- `search`
- `selectedTableIds` or server-issued table tokens, not raw trusted names.
- `perPage`
- `sortField`
- `sortDirection`
- `selectedBackupUuid`
- `confirmText`
- `isProcessing`

`ModulesForm`:

- `modules`
- `pendingModuleName`
- `confirmText`

Env/config components:

- Use typed form objects or public arrays with explicit rules.
- Keep secrets masked in UI and avoid re-saving masked placeholders as real values.

Settings partials:

- Explicit public properties for every visible field.
- Dynamic settings should map by setting ID and validate per type.

Reference: `ANALYSIS.md` section 11; `REFACTOR_PLAN.md` P1-01, P1-07.

### Validation Rules

Database:

- `search`: nullable string max length.
- `tableName` or table token: required, server-resolved, schema-allowlisted.
- `backupUuid`: required UUID, exists in approved backup metadata.
- `confirmText`: required exact phrase for destructive full restore/drop/truncate.

Env/config:

- DB host string, port integer range, driver enum, database/user strings, password nullable string.
- Mail host string, port integer, encryption enum, from address email, username/password nullable.
- MoMo endpoint URL, partner/access/secret keys strings with max lengths.
- NodeJS URL URL, bridge secret string, queue connection enum.
- Social IDs/secrets format-specific where possible.

Uploads:

- Images and galleries must validate MIME, extension, max size, and storage disk.

Reference: `ANALYSIS.md` section 11; `REFACTOR_PLAN.md` P1-01, P1-08.

### Events

Livewire events:

- `notify` for safe user-facing notifications only.
- `system-backup-created`
- `system-restore-started`
- `system-restore-finished`
- `settings-saved`
- `module-toggled`

Design decision: Events must not carry secrets, raw paths, or raw exception output. Reference: `REFACTOR_PLAN.md` P0-06.

### Pagination

- `TableList` must paginate table metadata or otherwise bound the list.
- Backup history must paginate if retained.
- Custom settings list should paginate or be bounded by group.

Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-12.

### Search/Filter/Sort Behavior

- Database table search must debounce and sanitize input.
- Sorting must be server-defined by allowed fields only.
- Backup filters should be by status/type/date, not raw path.
- Settings filters should be by group/type through service methods.

Reference: `REFACTOR_PLAN.md` P1-12, P1-11.

## 7. Blade/UI Design

### Page Blade Files

Target:

- `Modules/System/resources/views/system.blade.php`
- `Modules/System/resources/views/pages/database.blade.php`
- `Modules/System/resources/views/pages/settings/index.blade.php`
- `Modules/System/resources/views/pages/settings/env.blade.php`
- `Modules/System/resources/views/pages/settings/modules.blade.php`

Remove after tests:

- `Modules/System/resources/views/pages/index.blade.php`
- `Modules/System/resources/views/pages/settings/placeholder.blade.php`
- `Modules/System/resources/views/pages/settings/profile.blade.php`

Reference: `ANALYSIS.md` sections 4, 15; `REFACTOR_PLAN.md` P2-01, P1-14.

### Livewire Blade Files

Target hardened files:

- `Modules/System/resources/views/livewire/database/table-list.blade.php`
- `Modules/System/resources/views/livewire/settings/setting-form.blade.php`
- `Modules/System/resources/views/livewire/settings/modules-form.blade.php`
- `Modules/System/resources/views/livewire/settings/database-config.blade.php`
- `Modules/System/resources/views/livewire/settings/mail-config.blade.php`
- `Modules/System/resources/views/livewire/settings/momo-config.blade.php`
- `Modules/System/resources/views/livewire/settings/advanced-config.blade.php`
- `Modules/System/resources/views/livewire/settings/social-config.blade.php`
- `Modules/System/resources/views/livewire/settings/partials/general.blade.php`
- `Modules/System/resources/views/livewire/settings/partials/images.blade.php`
- `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`
- `Modules/System/resources/views/livewire/settings/partials/custom.blade.php`

Disable/remove:

- `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`
- `Modules/System/resources/views/livewire/settings/sh-script.blade.php`
- `Modules/System/resources/views/livewire/database/import-drawer.blade.php`
- `Modules/System/resources/views/livewire/database/backup-manager.blade.php` if unused.

Reference: `REFACTOR_PLAN.md` P0-02, P0-03, P1-03, P1-06.

### Shared Components

Potential:

- Safe confirmation modal.
- Operation status/progress panel.
- Permission denied panel.

Needs confirmation before coding: local System components or shared components.

### AdminLTE/Bootstrap Layout Rules

- Use Bootstrap 5.3/AdminLTE 4-compatible classes and components.
- Avoid adding jQuery/Summernote CDN assets from Livewire views.
- Keep operational screens dense, readable, and explicit about destructive operations.

Reference: `REFACTOR_PLAN.md` P2-04; `ROADMAP.md` P1-03.

### Table Design

Database table manager:

- Paginated table list.
- Columns: table display name, rows, size, collation, backup status, protected status, actions.
- Actions: backup, download, restore, truncate, drop.
- Destructive actions require permission-controlled buttons, typed confirmation, and disabled state while processing.

Reference: `ANALYSIS.md` sections 6, 13; `REFACTOR_PLAN.md` P0-04, P1-12.

### Form Design

- Config forms show field-level validation.
- Secrets are masked and changed only when a new value is entered.
- Forms disable save while invalid or processing.
- Permission-denied states are explicit.

Reference: `REFACTOR_PLAN.md` P1-01, P0-01.

## 8. Import Design

### Import Classes

No standard import classes are required for System.

SQL import should not be implemented as a normal import class until restore safety is designed.

Needs confirmation before coding: whether SQL restore should become `Modules/System/Imports/DatabaseSqlImport.php` or remain a `DatabaseRestoreService` operation. Preferred: service operation, not row import.

Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P0-07.

### Header Mapping

Not applicable for SQL restore.

If any future CSV/XLSX settings import is requested, use `Modules/Shared/Services/ImportExport` with explicit header mapping. Needs confirmation before coding.

### Column Mapping

Not applicable for SQL restore.

### Row Normalization

Not applicable for SQL restore.

### Row Validation

Not applicable for SQL restore.

### Duplicate Handling

Not applicable for SQL restore.

### Error Reporting

SQL restore errors must:

- Use safe user-facing messages.
- Log redacted technical details with correlation ID.
- Preserve the uploaded file privately only if needed for operator review and retention policy allows it.

Reference: `REFACTOR_PLAN.md` P0-06, P0-07.

## 9. Export Design

### Export Classes

No standard business export classes are required.

Database backup should be implemented through service classes:

- `DatabaseBackupService`
- `DatabaseMetadataService`
- Existing `DatabaseService` facade only if kept for compatibility.

Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P0-04, P1-11.

### Query Design

- Table metadata comes from schema metadata with bounded/paginated results.
- Backup list comes from backup metadata records or safe storage listing.
- Do not query permissions directly from Blade.

Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-12.

### Export Mapping

SQL backup mapping:

- Full backup: database-level SQL dump stored under private backup path.
- Table backup: one table, validated against schema allowlist.
- Metadata: backup UUID, type, table name, size, checksum, status, actor, expiry.

### Template Generation If Needed

Not needed for SQL backup.

### Large Export Strategy

- Use Symfony Process with argument arrays and timeout policy.
- For very large backups, queue the backup job and show progress/status instead of blocking a Livewire request.
- Store results privately and download through authorized controller.

Reference: `ROADMAP.md` P1-06; `REFACTOR_PLAN.md` P1-11.

## 10. Permissions and Authorization

### Required Permissions

Target permissions:

- `system.manage`
- `system.settings.view`
- `system.settings.update`
- `system.env.view`
- `system.env.update`
- `system.modules.view`
- `system.modules.update`
- `system.commands.run` only for allowlisted local/dev commands; production should not grant free-form execution.
- `database.view`
- `database.backup`
- `database.download`
- `database.restore`
- `database.destroy`

Reference: `REFACTOR_PLAN.md` P0-01.

### Policy/Gate Checks

- Use route middleware for page access.
- Use Livewire method authorization for every mutating action.
- Use service-level assertions for dangerous target identifiers.
- Super Admin `Gate::before` exists at provider level, but do not rely on it as the only authorization rule.

Reference: `ANALYSIS.md` section 10; `REFACTOR_PLAN.md` P0-01.

### Livewire Action Protection

Every action must call authorization before validation side effects:

- Database backup/restore/truncate/drop/download resolution.
- Module toggle.
- Env save/export.
- Settings save.
- Image/custom upload save.
- Any retained command operation.

Reference: `ANALYSIS.md` section 5; `REFACTOR_PLAN.md` P0-01.

### Route Middleware

Use:

- `web`
- `auth:admin`
- `permission:<capability>`

Reference: `ANALYSIS.md` section 2.

## 11. Transactions and Data Integrity

### Actions Requiring DB Transactions

- Batch settings writes.
- Custom settings creates/deletes/saves.
- Backup metadata creation/update if metadata table is implemented.
- Audit record creation with operation status changes.

Reference: `ANALYSIS.md` section 12; `REFACTOR_PLAN.md` P1-10.

### Rollback Conditions

- Validation failure: no writes.
- Authorization failure: no writes.
- File upload failure: rollback DB updates and delete newly stored files.
- Backup process failure: mark backup metadata failed and remove partial file if safe.
- Restore preflight failure: no restore begins.

### Idempotency Concerns

- Backup requests may be retried; generate unique UUIDs and avoid overwriting completed backups.
- Restore requests must not double-run accidentally; use operation status/lock if implemented.
- Env updates should be atomic and backed up.
- Module toggles should be idempotent by target enabled state, not blind toggle.

Reference: `ROADMAP.md` P1-09; `REFACTOR_PLAN.md` P1-10.

## 12. Performance Strategy

### Eager Loading

Minimal Eloquent relationships currently exist. If backup/audit models are added, eager load actor relationships for listing.

Needs confirmation before coding: admin user model/table.

### Query Optimization

- Bound `SHOW TABLE STATUS` calls.
- Batch backup existence checks.
- Batch settings reads.
- Avoid per-row filesystem calls in table list rendering.

Reference: `ANALYSIS.md` section 13; `REFACTOR_PLAN.md` P1-12, P2-05.

### Pagination

- Database table list: paginate or limit.
- Backup history: paginate.
- Custom settings: paginate or group-bound.

### Caching If Needed

- Cache System tabs with correct invalidation.
- Cache table metadata briefly only if safe and explicitly invalidated/refreshed.
- Cache settings through canonical settings service after ownership is confirmed.

Reference: `REFACTOR_PLAN.md` P2-03, P2-05.

## 13. Test Strategy

### Route Tests

- Guests cannot access System web routes.
- Admin without permissions receives 403.
- Admin with exact permission can access each page.
- `/api/system` is removed or protected.

Reference: `REFACTOR_PLAN.md` P0-01, P0-05.

### Livewire Tests

- Every mutating method denies unauthorized users.
- Database actions reject invalid table tokens, table names, backup IDs, and paths.
- Env/config components validate bad inputs.
- Module toggle requires permission and target state.
- Artisan and shell components are unavailable or disabled in production.

Reference: `REFACTOR_PLAN.md` P0-01, P0-02, P0-03, P1-01.

### Service Tests

- `DatabaseBackupService` validates tables and creates private backup metadata.
- `DatabaseRestoreService` rejects raw paths and invalid backup IDs.
- `EnvManagerService` rejects unknown keys and invalid suffixes.
- `SystemConfigService` rejects arbitrary component aliases and invalidates cache.
- `ModuleManifestService` writes atomically.

Reference: `REFACTOR_PLAN.md` P0-04, P1-02, P1-04, P1-10.

### Import Tests

- SQL import UI is unavailable until safe restore is implemented.
- If implemented, invalid MIME/extension/oversized SQL files are rejected.
- Restore preflight rejects unapproved files.

Reference: `REFACTOR_PLAN.md` P0-07.

### Export Tests

- Backup creation requires permission.
- Table backup rejects non-schema table names.
- Downloads require permission and valid backup UUID.
- Large backup job/status behavior is tested if queued.

Reference: `REFACTOR_PLAN.md` P0-04, P1-11.

### Authorization Tests

- Permission matrix covers `system.manage`, `system.settings.update`, `system.env.update`, `system.modules.update`, `database.backup`, `database.download`, `database.restore`, and `database.destroy`.
- Hidden UI controls are not the only protection; direct Livewire calls are denied.

Reference: `ANALYSIS.md` section 10; `REFACTOR_PLAN.md` P0-01.

## 14. Implementation Checklist

### P0

- [ ] Remove or protect `Modules/System/routes/api.php` and `Modules/System/Http/Controllers/Api/SystemController.php`. Reference: `REFACTOR_PLAN.md` P0-05.
- [ ] Add explicit permissions in `Modules/System/config/module.php`. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Add route permission middleware in `Modules/System/routes/web.php`. Reference: `ANALYSIS.md` section 2.
- [ ] Add authorization checks in System controllers and mutating Livewire methods. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Disable `Modules/System/Livewire/Settings/ArtisanList.php` and `Modules/System/resources/views/livewire/settings/artisan-list.blade.php` in production. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Disable `Modules/System/Livewire/Settings/ShScript.php` and `Modules/System/resources/views/livewire/settings/sh-script.blade.php` in production. Reference: `REFACTOR_PLAN.md` P0-03.
- [ ] Harden `Modules/System/Services/DatabaseService.php` or split it into safe database services. Reference: `REFACTOR_PLAN.md` P0-04.
- [ ] Replace browser-provided backup paths in `Modules/System/Livewire/Database/TableList.php` and `Modules/System/resources/views/livewire/database/table-list.blade.php`. Reference: `REFACTOR_PLAN.md` P0-04.
- [ ] Keep SQL import disabled until `DatabaseRestoreService` is safe. Reference: `REFACTOR_PLAN.md` P0-07.
- [ ] Redact raw operational errors in services and Livewire notifications. Reference: `REFACTOR_PLAN.md` P0-06.
- [ ] Add P0 route, Livewire, service, and authorization tests. Reference: `ROADMAP.md` P0-06.

### P1

- [ ] Add Livewire validation rules/Form objects for env/config components. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Add `.env` key allowlists, suffix validation, locks, and atomic writes. Reference: `REFACTOR_PLAN.md` P1-02.
- [ ] Fix or remove stale database components `BackupManager` and `ImportDrawer`. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Consolidate tab sources and allowlisted component rendering. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Decide settings table/model ownership. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Normalize System components to render System views or move ownership explicitly. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Fix General settings state mismatch. Reference: `REFACTOR_PLAN.md` P1-07.
- [ ] Validate custom setting uploads and define HTML policy. Reference: `REFACTOR_PLAN.md` P1-08.
- [ ] Escape or sanitize SEO preview output. Reference: `REFACTOR_PLAN.md` P1-09.
- [ ] Make settings/file/module/env writes atomic or compensating. Reference: `REFACTOR_PLAN.md` P1-10.
- [ ] Standardize backup storage, retention, and download policy. Reference: `REFACTOR_PLAN.md` P1-11.
- [ ] Optimize database metadata listing. Reference: `REFACTOR_PLAN.md` P1-12.
- [ ] Repair settings migration hygiene after ownership decision. Needs confirmation before coding. Reference: `REFACTOR_PLAN.md` P1-13.
- [ ] Remove or document cross-module profile UI. Reference: `REFACTOR_PLAN.md` P1-14.

### P2

- [ ] Remove confirmed placeholder/dead files after route/component tests. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Complete or remove `EnvManager`. Reference: `REFACTOR_PLAN.md` P2-02.
- [ ] Fix `SystemConfigService` cache invalidation. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Replace CDN-pushed jQuery/Summernote assets. Reference: `REFACTOR_PLAN.md` P2-04.
- [ ] Add batch settings reads after settings ownership is stable. Reference: `REFACTOR_PLAN.md` P2-05.
- [ ] Update `docs/modules/System/README.md` and `docs/modules/System/INFORMATION.md` with final boundaries. Reference: `REFACTOR_PLAN.md` P2-06.
