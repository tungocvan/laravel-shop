# System Refactor Plan

## 1. Executive Summary

`Modules/System` is a privileged shell module that currently exposes production-control features: database backup/restore/drop/truncate, `.env` edits, module manifest writes, arbitrary Artisan execution, and shell script execution. The immediate refactor goal is containment: deny dangerous operations by default, require explicit permissions, validate all browser-supplied identifiers server-side, and remove arbitrary command execution from HTTP/Livewire surfaces.

The second goal is correctness and maintainability. Several components reference stale service methods, System duplicates Admin settings/env/database behavior, some System components render Admin views, and the `settings` table is modeled in System while its migration is owned by Admin. These should be cleaned up only after the P0 security boundary is in place.

The third goal is cleanup and performance. Placeholder files, incomplete components, unbounded settings/table scans, and cache invalidation issues can be addressed after safety and correctness are stable.

## 2. P0 Critical Fixes

### P0-01: Capability Authorization For System Routes And Livewire Actions

- Issue: Privileged routes and mutating Livewire methods rely on `auth:admin` only, or have authorization comments instead of enforcement.
- Root Cause: `Modules/System/routes/web.php` groups every System route under the admin guard but does not require capability-level permissions; Livewire methods do not call `$this->authorize()` or equivalent gate/permission checks.
- Business Impact: Any authenticated admin can change system configuration, run commands, download backups, restore/drop/truncate database tables, or toggle modules.
- Technical Impact: Authorization is too coarse for a high-risk operations module and violates the roadmap P0 requirement for explicit capabilities.
- Proposed Solution: Add explicit permissions such as `system.manage`, `system.settings.update`, `system.modules.update`, `system.commands.run`, `database.backup`, `database.restore`, `database.download`, and `database.destroy`. Apply route middleware where practical and add method-level checks inside every mutating Livewire action.
- Files To Change: `Modules/System/routes/web.php`, `Modules/System/config/module.php`, `Modules/System/Http/Controllers/SystemController.php`, `Modules/System/Http/Controllers/EnvConfigController.php`, `Modules/System/Http/Controllers/DatabaseController.php`, `Modules/System/Livewire/Database/TableList.php`, `Modules/System/Livewire/Settings/ModulesForm.php`, `Modules/System/Livewire/Settings/DatabaseConfig.php`, `Modules/System/Livewire/Settings/MailConfig.php`, `Modules/System/Livewire/Settings/MomoConfig.php`, `Modules/System/Livewire/Settings/AdvancedConfig.php`, `Modules/System/Livewire/Settings/SocialConfig.php`, `Modules/System/Livewire/Settings/ArtisanList.php`, `Modules/System/Livewire/Settings/ShScript.php`, `Modules/System/Livewire/Settings/Partials/General.php`, `Modules/System/Livewire/Settings/Partials/Images.php`, `Modules/System/Livewire/Settings/Partials/Seo.php`, `Modules/System/Livewire/Settings/Partials/Custom.php`.
- Risk Level: Critical.
- Complexity: High.
- Estimated Effort: 2-4 days including tests.
- Acceptance Criteria: Unauthorized admin users receive 403 responses for every privileged route/action; authorized users can perform only their granted capabilities; Livewire mutating actions cannot be invoked successfully by bypassing hidden UI controls.

### P0-02: Remove Arbitrary Artisan Execution

- Issue: `Modules/System/Livewire/Settings/ArtisanList.php` runs browser-provided command text through `Artisan::call()`.
- Root Cause: The component treats an admin text input as trusted command selection.
- Business Impact: A compromised or over-privileged admin account can run destructive commands such as `migrate:fresh`, clear caches, seed data, generate keys, or invoke custom commands with side effects.
- Technical Impact: Web requests can trigger arbitrary application commands without allowlisting, audit logging, argument validation, timeout policy, or environment gating.
- Proposed Solution: Disable this feature outside local development. If command execution remains necessary, replace it with a fixed allowlist of named operations, fixed/validated arguments, per-command permissions, audit records, and safe output redaction.
- Files To Change: `Modules/System/Livewire/Settings/ArtisanList.php`, `Modules/System/resources/views/livewire/settings/artisan-list.blade.php`, `Modules/System/config/system_tabs.php`, `Modules/System/data/system_tabs.json`.
- Risk Level: Critical.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: No browser-supplied string can select an arbitrary Artisan command; production users cannot access a free-form Artisan terminal; destructive suggestions such as `migrate:fresh` are absent.

### P0-03: Remove Arbitrary Shell Script Editing And Execution

- Issue: `Modules/System/Livewire/Settings/ShScript.php` creates, edits, chmods, deletes, and executes shell scripts from the browser.
- Root Cause: The component stores executable files under `app/sh` and invokes `shell_exec("bash {$scriptPath}")` with browser-selected filenames.
- Business Impact: A privileged web user can execute server shell commands, alter application files, exfiltrate secrets, or damage production data.
- Technical Impact: The application exposes remote command execution through Livewire and lacks filename allowlisting, path validation, process isolation, audit logging, and timeout handling.
- Proposed Solution: Disable the shell script manager outside local development and remove it from production tabs. Replace any required operations with predefined service methods or queued jobs using Symfony Process argument arrays, fixed executable paths, explicit permissions, timeouts, and audit logs.
- Files To Change: `Modules/System/Livewire/Settings/ShScript.php`, `Modules/System/resources/views/livewire/settings/sh-script.blade.php`, `Modules/System/config/system_tabs.php`, `Modules/System/data/system_tabs.json`.
- Risk Level: Critical.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Production cannot create, edit, delete, chmod, or execute shell scripts through System UI; no Livewire method invokes `shell_exec`; any retained operation is predefined and authorized.

### P0-04: Harden Database Backup, Restore, Download, Truncate, And Drop

- Issue: Database operations accept browser-supplied table names, filenames, and backup paths; shell commands include credentials; destructive methods disable foreign key checks unsafely.
- Root Cause: `Modules/System/Services/DatabaseService.php` trusts caller input, uses shell command strings for `mysqldump`/`mysql`, and lacks server-issued backup identifiers.
- Business Impact: Unauthorized or malformed requests can drop/truncate tables, restore attacker-selected SQL files, expose database credentials, or leave the database inconsistent.
- Technical Impact: Command injection/path traversal risk, process-list secret leakage, irreversible data loss, and broken foreign-key state after exceptions.
- Proposed Solution: Validate table names against schema metadata before every table operation; replace shell command strings with Symfony Process argument arrays; avoid passwords in command arguments; store backups privately; replace path/filename selections with opaque server-issued backup IDs; always restore foreign key checks in `finally`; add immutable audit records for destructive operations.
- Files To Change: `Modules/System/Services/DatabaseService.php`, `Modules/System/Livewire/Database/TableList.php`, `Modules/System/resources/views/livewire/database/table-list.blade.php`, `Modules/System/Http/Controllers/DatabaseController.php`, `Modules/System/resources/views/livewire/database/backup-manager.blade.php`, `Modules/System/Livewire/Database/BackupManager.php`.
- Risk Level: Critical.
- Complexity: High.
- Estimated Effort: 4-7 days including regression tests.
- Acceptance Criteria: No browser-supplied string can directly choose a table, executable path, backup path, or SQL file path; destructive actions require permission and confirmation; credentials are not visible in process arguments; `FOREIGN_KEY_CHECKS` is restored after failures; invalid identifiers are rejected before execution.

### P0-05: Protect Or Remove The Unauthenticated API Endpoint

- Issue: `Modules/System/routes/api.php` exposes `GET /api/system` without auth and routes to a missing `index()` method.
- Root Cause: Previously commented Sanctum middleware was not restored, and the API controller remains empty.
- Business Impact: A System endpoint is publicly reachable and may become an accidental information disclosure surface if implemented later.
- Technical Impact: Current requests may fail with a controller action error; future changes could expose System data without authentication.
- Proposed Solution: Remove the route if unused. If needed, add `auth:sanctum`, explicit abilities, a real `index()` method, and response tests.
- Files To Change: `Modules/System/routes/api.php`, `Modules/System/Http/Controllers/Api/SystemController.php`.
- Risk Level: Critical.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: `/api/system` either returns 404 because it is intentionally removed or requires authenticated API access and has tests for allowed/denied behavior.

### P0-06: Safe Error Redaction For Operational Failures

- Issue: Raw exception messages from database/process/env operations are returned to users through Livewire notifications.
- Root Cause: Components catch exceptions and dispatch `$e->getMessage()` directly; services rethrow raw process/database details.
- Business Impact: Users may see database names, usernames, file paths, SQL errors, process output, or secrets.
- Technical Impact: Sensitive implementation details leak through UI and logs become hard to correlate safely.
- Proposed Solution: Convert operational failures to safe domain messages with correlation IDs. Log detailed errors server-side with secret redaction and dispatch generic user-facing messages.
- Files To Change: `Modules/System/Services/DatabaseService.php`, `Modules/System/Livewire/Database/TableList.php`, `Modules/System/Livewire/Database/BackupManager.php`, `Modules/System/Livewire/Database/ImportDrawer.php`, `Modules/System/Livewire/Settings/ArtisanList.php`, `Modules/System/Livewire/Settings/ShScript.php`, `Modules/System/Services/Env/MailConfigService.php`, `Modules/System/Services/Env/SystemConfigService.php`, `Modules/System/Services/Database/DbConnectionService.php`.
- Risk Level: Critical.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: UI messages do not include raw process output, SQL errors, secrets, or filesystem paths; detailed errors are logged with redaction and correlation IDs.

### P0-07: Gate SQL Import And Restore As Destructive Operations

- Issue: SQL import/restore paths are treated as UI operations but can replace or destroy the whole database.
- Root Cause: `Modules/System/Livewire/Database/ImportDrawer.php` accepts uploaded SQL and calls a missing import method; `Modules/System/Services/DatabaseService.php::restoreFromFile()` drops all tables before import.
- Business Impact: A bad or malicious SQL file can wipe production data.
- Technical Impact: Restore has no rollback path, no file provenance guarantee, no queue/progress model, and no audited confirmation.
- Proposed Solution: Do not expose SQL import until the restore service is redesigned. Require `database.restore`, private temp storage, server-issued file IDs, row/file validation where feasible, pre-restore backup, explicit typed confirmation, audit logs, and operator-only availability.
- Files To Change: `Modules/System/Livewire/Database/ImportDrawer.php`, `Modules/System/resources/views/livewire/database/import-drawer.blade.php`, `Modules/System/Services/DatabaseService.php`, `Modules/System/Livewire/Database/TableList.php`.
- Risk Level: Critical.
- Complexity: High.
- Estimated Effort: 3-5 days.
- Acceptance Criteria: SQL import is unavailable until implemented safely; restore cannot run without permission, server-approved backup ID, explicit confirmation, audit record, and preflight validation.

## 3. P1 Important Refactors

### P1-01: Standardize Livewire Validation For Env And Settings Forms

- Issue: Env/config components save values with little or no validation.
- Root Cause: Components store public arrays and call services directly instead of using `rules()`, Form objects, DTOs, enum validation, and per-field constraints.
- Business Impact: Invalid DB/mail/payment/social/queue configuration can break production services or lock operators out.
- Technical Impact: `.env` may receive malformed URLs, ports, drivers, credentials, queue settings, or secrets; service methods receive untrusted arrays.
- Proposed Solution: Add Livewire 3 validation rules or Form objects for every config component; validate before test actions and before save actions; keep service invariants server-side.
- Files To Change: `Modules/System/Livewire/Settings/DatabaseConfig.php`, `Modules/System/Livewire/Settings/MailConfig.php`, `Modules/System/Livewire/Settings/MomoConfig.php`, `Modules/System/Livewire/Settings/AdvancedConfig.php`, `Modules/System/Livewire/Settings/SocialConfig.php`, `Modules/System/Services/Env/EnvManagerService.php`, `Modules/System/Services/Database/DbConnectionService.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 2-3 days.
- Acceptance Criteria: Every saved field has explicit validation; invalid values produce validation errors; services reject unsupported keys and unsafe values.

### P1-02: Add Env Key Allowlist, Suffix Validation, Locking, And Atomic Writes

- Issue: `.env` updates and exports are string-based, broad, and not atomic.
- Root Cause: `Modules/System/Services/Env/EnvManagerService.php` writes caller-supplied keys with regex and exports `.env.{suffix}` using a caller-supplied suffix.
- Business Impact: Bad writes can corrupt `.env`, alter unrelated secrets, or create unexpected files.
- Technical Impact: Race conditions, malformed env lines, invalid suffix paths, and hard-to-debug runtime config drift.
- Proposed Solution: Define allowed env keys per component; validate export suffixes against fixed values; use file locks and atomic temp-file replacement; create backups before writes; clear only relevant config safely.
- Files To Change: `Modules/System/Services/Env/EnvManagerService.php`, `Modules/System/Services/Env/EnvBackupService.php`, `Modules/System/Livewire/Settings/DatabaseConfig.php`, `Modules/System/Livewire/Settings/MailConfig.php`, `Modules/System/Livewire/Settings/MomoConfig.php`, `Modules/System/Livewire/Settings/AdvancedConfig.php`, `Modules/System/Livewire/Settings/SocialConfig.php`, `Modules/System/Livewire/Settings/EnvManager.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 2-4 days.
- Acceptance Criteria: Only allowed keys can be changed; `.env` writes are atomic and backed up; invalid suffixes are rejected; concurrent writes cannot interleave.

### P1-03: Fix Stale Database Component API Calls

- Issue: `BackupManager` and `ImportDrawer` call service methods not implemented by `DatabaseService`.
- Root Cause: Components appear copied from an older Admin service contract and were not reconciled with System `DatabaseService`.
- Business Impact: Backup/import UI can fail at runtime if mounted, undermining operator trust during recovery.
- Technical Impact: Missing method calls cause fatal errors and block future database workflow consolidation.
- Proposed Solution: Decide whether these components are part of the System UX. If yes, update them to the hardened System service contract; if no, remove them after route/component tests confirm they are unused.
- Files To Change: `Modules/System/Livewire/Database/BackupManager.php`, `Modules/System/resources/views/livewire/database/backup-manager.blade.php`, `Modules/System/Livewire/Database/ImportDrawer.php`, `Modules/System/resources/views/livewire/database/import-drawer.blade.php`, `Modules/System/Services/DatabaseService.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1-2 days after P0 database hardening.
- Acceptance Criteria: No System Livewire component calls undefined service methods; unused components are either removed or documented as intentionally disabled.

### P1-04: Consolidate Dynamic Tab Sources And Allowlisted Component Registry

- Issue: System tabs are defined in multiple places and rendered dynamically.
- Root Cause: `EnvConfigController` defines tabs inline, `SystemConfigService` loads config/JSON tabs, and Blade views mount configured component names.
- Business Impact: Misconfigured JSON/config can expose unintended components or produce broken admin screens.
- Technical Impact: Duplicate tab state, stale cache entries, and unsafe dynamic component rendering.
- Proposed Solution: Move tab definitions behind one service that returns only allowlisted component aliases; validate JSON overrides by schema; remove inline controller tab arrays; fix cache invalidation.
- Files To Change: `Modules/System/Http/Controllers/EnvConfigController.php`, `Modules/System/Http/Controllers/SystemController.php`, `Modules/System/Services/SystemConfigService.php`, `Modules/System/config/system_tabs.php`, `Modules/System/data/system_tabs.json`, `Modules/System/resources/views/system.blade.php`, `Modules/System/resources/views/pages/settings/env.blade.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 2-3 days.
- Acceptance Criteria: One service owns tab definitions; only approved component aliases can render; JSON overrides cannot introduce arbitrary Livewire components; cache invalidation works after updates.

### P1-05: Resolve Settings Table And Model Ownership

- Issue: System defines `Modules\System\Models\Setting` while the `settings` migration is owned by Admin and Admin also has a `Setting` model.
- Root Cause: Settings behavior was duplicated across modules without canonical ownership.
- Business Impact: Settings changes may diverge between Admin, System, and Website behavior.
- Technical Impact: Duplicate models and services create inconsistent cache behavior, migration ordering risk, and unclear module boundaries.
- Proposed Solution: Document a canonical owner for `settings` before moving code. Prefer a single settings service/model owner, then migrate System callers in small steps after tests exist.
- Files To Change: `Modules/System/Models/Setting.php`, `Modules/System/Livewire/Settings/Partials/General.php`, `Modules/System/Livewire/Settings/Partials/Images.php`, `Modules/System/Livewire/Settings/Partials/Seo.php`, `Modules/System/Livewire/Settings/Partials/Custom.php`, `Modules/Admin/Models/Setting.php`, `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`.
- Risk Level: High.
- Complexity: High.
- Estimated Effort: 4-7 days with cross-module tests.
- Acceptance Criteria: There is one documented settings model/service owner; System callers use the canonical contract; fresh migrations create the `settings` table in deterministic order.

### P1-06: Normalize System Views And Remove Admin View Leakage

- Issue: System Livewire classes render Admin views while matching System views exist.
- Root Cause: `DatabaseConfig::render()` and `MomoConfig::render()` still point to `Admin::...` view namespaces.
- Business Impact: System UI changes can unexpectedly depend on Admin templates and assets.
- Technical Impact: Cross-module coupling makes refactors brittle and violates clean module boundaries.
- Proposed Solution: Render System-owned views from System components, or explicitly move the component ownership back to Admin if Admin remains the owner.
- Files To Change: `Modules/System/Livewire/Settings/DatabaseConfig.php`, `Modules/System/resources/views/livewire/settings/database-config.blade.php`, `Modules/System/Livewire/Settings/MomoConfig.php`, `Modules/System/resources/views/livewire/settings/momo-config.blade.php`.
- Risk Level: Medium.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: System components render `System::...` views or are removed from System ownership; no System component accidentally renders an Admin view.

### P1-07: Fix General Settings State Mismatch

- Issue: General settings Blade binds `site_hotline` and `site_address`, but PHP state and validation include only `site_name` and `site_email`.
- Root Cause: The Blade form and Livewire class evolved separately.
- Business Impact: Operators may think hotline/address settings are saved when they are ignored or inconsistently persisted.
- Technical Impact: Dynamic public array keys bypass explicit validation and create fragile state.
- Proposed Solution: Align Livewire public state, rules, mount loading, and save behavior with the Blade fields, or remove the unused fields from the view.
- Files To Change: `Modules/System/Livewire/Settings/Partials/General.php`, `Modules/System/resources/views/livewire/settings/partials/general.blade.php`.
- Risk Level: Medium.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: Every visible field is loaded, validated, saved, and tested; no undeclared setting keys are bound from the view.

### P1-08: Validate Custom Setting Uploads And HTML

- Issue: Custom setting image/gallery uploads and HTML values are not sufficiently validated.
- Root Cause: `Custom.php` validates new field metadata but not upload MIME/size/dimensions or HTML sanitization policy.
- Business Impact: Large or unsafe uploads can consume storage, and stored HTML can become a persistent XSS vector.
- Technical Impact: Public storage may contain unsupported files; HTML values are stored without a clear trust boundary.
- Proposed Solution: Add MIME/size validation for dynamic images and galleries; define whether HTML fields are trusted admin-only HTML or sanitized content; store files through Laravel Storage with cleanup on failure.
- Files To Change: `Modules/System/Livewire/Settings/Partials/Custom.php`, `Modules/System/resources/views/livewire/settings/partials/custom.blade.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Invalid uploads are rejected; max sizes are enforced; HTML storage/display policy is documented and tested.

### P1-09: Escape Or Sanitize SEO Preview Output

- Issue: SEO description is rendered with raw `{!! !!}` in the preview.
- Root Cause: The Blade preview treats database-controlled content as trusted HTML.
- Business Impact: A stored script can execute for admins viewing the settings page.
- Technical Impact: Persistent XSS risk in admin UI.
- Proposed Solution: Escape the preview by default or sanitize allowed HTML through a dedicated sanitizer before rendering.
- Files To Change: `Modules/System/resources/views/livewire/settings/partials/seo.blade.php`, `Modules/System/Livewire/Settings/Partials/Seo.php`.
- Risk Level: High.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: Script payloads stored in SEO description do not execute in the settings preview; tests cover escaped output.

### P1-10: Make Multi-Step Writes Atomic Or Compensating

- Issue: Settings file writes, uploaded images, module manifest rewrites, and env writes can leave partial state after failure.
- Root Cause: Multi-step workflows are implemented directly in Livewire/services without transactions, locks, temp files, or compensating cleanup.
- Business Impact: Failed saves can delete old files, corrupt manifests, or partially update settings.
- Technical Impact: Inconsistent DB/filesystem state and difficult recovery.
- Proposed Solution: For database settings, wrap related writes in transactions. For filesystem changes, write temp files then atomic rename, keep backups, and clean up new files on failure. Avoid deleting old files until new state is committed.
- Files To Change: `Modules/System/Livewire/Settings/Partials/Images.php`, `Modules/System/Livewire/Settings/Partials/Custom.php`, `Modules/System/Livewire/Settings/ModulesForm.php`, `Modules/System/Services/Env/EnvManagerService.php`, `Modules/System/Services/Env/EnvBackupService.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 2-3 days.
- Acceptance Criteria: Simulated failures do not remove old files or corrupt manifests/env files; database and file state remain consistent.

### P1-11: Standardize Backup Storage, Retention, And Download Policy

- Issue: Full and table backups use different paths and download logic checks only one path.
- Root Cause: `DatabaseService` writes to both `storage/app/backups` and `storage/app/private/backups` without a single storage abstraction or retention rule.
- Business Impact: Operators may not find expected backups, and sensitive SQL files may be stored inconsistently.
- Technical Impact: Broken downloads, unclear cleanup, and inconsistent privacy guarantees.
- Proposed Solution: Use one private backup disk/path policy, add metadata records or server-generated IDs, apply retention cleanup, and authorize every download.
- Files To Change: `Modules/System/Services/DatabaseService.php`, `Modules/System/Http/Controllers/DatabaseController.php`, `Modules/System/Livewire/Database/TableList.php`, `Modules/System/resources/views/livewire/database/table-list.blade.php`, `Modules/System/resources/views/livewire/database/backup-manager.blade.php`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 2-3 days.
- Acceptance Criteria: All generated SQL backups are private, discoverable by one service, downloadable only with permission, and subject to retention cleanup.

### P1-12: Improve Database Metadata Query Performance

- Issue: The table list runs `SHOW TABLE STATUS` and per-table storage checks on every render.
- Root Cause: `TableList::render()` reloads all metadata every Livewire render, and `DatabaseService::getAllTables()` checks backup existence inside the map.
- Business Impact: The database manager can become slow or expensive on schemas with many tables.
- Technical Impact: Frequent metadata scans and filesystem calls during search/selection updates.
- Proposed Solution: Add pagination or bounded metadata loading; debounce search; cache short-lived table metadata where safe; precompute backup filenames into a set instead of per-table storage checks.
- Files To Change: `Modules/System/Livewire/Database/TableList.php`, `Modules/System/Services/DatabaseService.php`, `Modules/System/resources/views/livewire/database/table-list.blade.php`.
- Risk Level: Medium.
- Complexity: Medium.
- Estimated Effort: 1-2 days.
- Acceptance Criteria: Table metadata reloads are bounded; large schemas remain responsive; backup existence checks are batched.

### P1-13: Repair Migration Hygiene For Settings

- Issue: The `settings` migration is under Admin with a malformed negative-year filename.
- Root Cause: Migration ownership and filename ordering were not standardized across modules.
- Business Impact: Fresh installs and CI migrations may fail or run in unexpected order.
- Technical Impact: System depends on a table it does not migrate, and migration ordering is fragile.
- Proposed Solution: After ownership is decided, rename or replace the malformed migration through a controlled migration hygiene task and add fresh-install smoke tests.
- Files To Change: `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`, `Modules/System/Models/Setting.php`, `docs/modules/System/REFACTOR_PLAN.md`.
- Risk Level: High.
- Complexity: Medium.
- Estimated Effort: 1-2 days plus CI validation.
- Acceptance Criteria: Fresh migration succeeds; settings table ownership is documented; no negative-year migration remains for settings.

### P1-14: Remove Cross-Module Profile UI From System Or Document Ownership

- Issue: System profile page mounts Website account profile components.
- Root Cause: `Modules/System/resources/views/pages/settings/profile.blade.php` mixes account profile UI into System settings without a System route.
- Business Impact: Profile behavior may break when Website changes, and System boundaries stay unclear.
- Technical Impact: Cross-module dependencies are hidden in a view.
- Proposed Solution: Remove the page if unused, move it to the owning module, or document and route it explicitly with proper authorization.
- Files To Change: `Modules/System/resources/views/pages/settings/profile.blade.php`, `Modules/System/Http/Controllers/SettingController.php`, `Modules/System/routes/web.php`.
- Risk Level: Medium.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: System does not silently mount Website profile components; any retained profile page has a documented owner and route.

## 4. P2 Nice To Have Improvements

### P2-01: Remove Confirmed Placeholder And Dead Files

- Issue: Several placeholder pages/components appear unused or incomplete.
- Root Cause: Scaffolding files were left in place after feature development moved elsewhere.
- Business Impact: Developers waste time tracing dead screens.
- Technical Impact: More component aliases/views are registered than necessary, increasing confusion.
- Proposed Solution: After route/component tests, delete confirmed unused placeholders and stale components.
- Files To Change: `Modules/System/resources/views/pages/index.blade.php`, `Modules/System/resources/views/pages/settings/placeholder.blade.php`, `Modules/System/resources/views/livewire/placeholder.blade.php`, `Modules/System/resources/views/components/placeholder.blade.php`, `Modules/System/Livewire/Settings/Placeholder.php`, `Modules/System/Livewire/Settings/StorageConfig.php`, `Modules/System/resources/views/livewire/settings/storage-config.blade.php`, `Modules/System/resources/views/livewire/settings/placeholder.blade.php`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: Route/component smoke tests pass after removal; no deleted view/component is referenced.

### P2-02: Complete Or Remove EnvManager

- Issue: `EnvManager` is commented out in views and has an empty `getTabsDefinition()` method.
- Root Cause: The env snapshot feature is unfinished.
- Business Impact: Future developers may mount it and get broken UI state.
- Technical Impact: Null tab data and unclear feature ownership.
- Proposed Solution: Either finish the feature with fixed export actions and authorization, or remove the component/view after confirming it is unused.
- Files To Change: `Modules/System/Livewire/Settings/EnvManager.php`, `Modules/System/resources/views/livewire/settings/env-manager.blade.php`, `Modules/System/resources/views/system.blade.php`, `Modules/System/resources/views/pages/settings/env.blade.php`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: No incomplete env manager component remains; if retained, it renders valid tabs and uses fixed export targets only.

### P2-03: Fix SystemConfigService Cache Invalidation

- Issue: `SystemConfigService::clearCache()` forgets `system_tabs`, while `getTabs()` stores timestamped keys.
- Root Cause: Cache key strategy changed without updating invalidation.
- Business Impact: Tab changes may not appear promptly.
- Technical Impact: Stale tab definitions remain cached until TTL expires.
- Proposed Solution: Use a stable cache key with dependency-aware invalidation, or track and forget the actual timestamped key.
- Files To Change: `Modules/System/Services/SystemConfigService.php`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5 day.
- Acceptance Criteria: Updating or resetting tabs immediately affects rendered System tabs.

### P2-04: Replace CDN-Pushed jQuery/Summernote Assets

- Issue: `setting-form.blade.php` pushes jQuery and Summernote CDN assets from inside the component view.
- Root Cause: Editor assets were added locally instead of through the project asset pipeline.
- Business Impact: Admin UI can load inconsistent assets and depend on external CDN availability.
- Technical Impact: Duplicate JavaScript, CSP friction, and frontend stack drift.
- Proposed Solution: If the editor is still needed, load it through Vite/project assets or a shared editor component policy; otherwise remove the unused asset push.
- Files To Change: `Modules/System/resources/views/livewire/settings/setting-form.blade.php`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: System settings page does not push ad hoc CDN assets; editor assets follow the project frontend policy.

### P2-05: Improve Settings Query Efficiency

- Issue: General and SEO settings load values one key at a time, relying on cache to hide query cost.
- Root Cause: `Setting::getValue()` is a static per-key helper and partial components call it repeatedly.
- Business Impact: Cache misses create unnecessary queries.
- Technical Impact: Settings access patterns are hard to batch and invalidate consistently.
- Proposed Solution: Once settings ownership is consolidated, add batch get/set operations in the canonical settings service.
- Files To Change: `Modules/System/Models/Setting.php`, `Modules/System/Livewire/Settings/Partials/General.php`, `Modules/System/Livewire/Settings/Partials/Seo.php`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5-1 day after P1 settings ownership.
- Acceptance Criteria: General and SEO settings are loaded in one service call each; cache invalidation remains correct.

### P2-06: Document Final System Module Boundary

- Issue: System currently overlaps Admin, Website, and database/settings ownership.
- Root Cause: Module boundaries evolved organically.
- Business Impact: Future changes may reintroduce duplicate implementations.
- Technical Impact: Dependency direction remains implicit.
- Proposed Solution: After P0/P1 cleanup, update System module docs with owned features, forbidden responsibilities, permissions, and cross-module dependencies.
- Files To Change: `docs/modules/System/ANALYSIS.md`, `docs/modules/System/REFACTOR_PLAN.md`, `docs/modules/System/README.md`, `docs/modules/System/INFORMATION.md`.
- Risk Level: Low.
- Complexity: Low.
- Estimated Effort: 0.5-1 day.
- Acceptance Criteria: System documentation clearly states what the module owns and what belongs to Admin, Website, Shared, or another canonical owner.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. P0-05: Remove or protect `Modules/System/routes/api.php` and `Modules/System/Http/Controllers/Api/SystemController.php`.
2. P0-01: Add capability authorization to `Modules/System/routes/web.php`, controllers, and Livewire mutating methods.
3. P0-02: Disable free-form Artisan execution in `Modules/System/Livewire/Settings/ArtisanList.php`.
4. P0-03: Disable shell script editing/execution in `Modules/System/Livewire/Settings/ShScript.php`.
5. P0-04: Harden `Modules/System/Services/DatabaseService.php` and database Livewire actions.
6. P0-07: Keep SQL import/restore disabled until a safe restore contract exists.
7. P0-06: Add safe operational error redaction and audit-friendly logging.

### Phase 2: Correctness and Maintainability

1. P1-01: Add explicit Livewire validation for env/config components.
2. P1-02: Add env key allowlists, suffix validation, locking, and atomic writes.
3. P1-03: Fix or remove stale database components.
4. P1-04: Consolidate System tab configuration and dynamic component allowlisting.
5. P1-05: Decide canonical settings ownership and migrate System callers.
6. P1-06: Normalize System view namespaces.
7. P1-07: Fix general settings state mismatch.
8. P1-08 and P1-09: Validate custom settings and sanitize/escape SEO preview.
9. P1-10: Make multi-step writes atomic.
10. P1-11 and P1-13: Standardize backup storage and repair settings migration hygiene.
11. P1-14: Remove or document cross-module profile UI.

### Phase 3: Performance and Cleanup

1. P1-12: Improve database metadata query performance.
2. P2-01: Remove confirmed placeholder/dead files.
3. P2-02: Complete or remove EnvManager.
4. P2-03: Fix SystemConfigService cache invalidation.
5. P2-04: Replace CDN-pushed editor assets.
6. P2-05: Batch settings queries after canonical ownership is stable.
7. P2-06: Update final module documentation.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/System/routes/web.php` | Modify | P0 | Add explicit capability middleware for System routes. |
| `Modules/System/routes/api.php` | Modify or remove route | P0 | Public `/api/system` endpoint is unauthenticated and points to a missing method. |
| `Modules/System/config/module.php` | Modify | P0 | Replace generic permissions with explicit capabilities. |
| `Modules/System/config/system_tabs.php` | Modify | P0 | Hide/remove unsafe Artisan and shell tabs in production; enforce safe component allowlist. |
| `Modules/System/data/system_tabs.json` | Modify | P0 | Prevent JSON overrides from enabling unsafe or arbitrary components. |
| `Modules/System/Http/Controllers/SystemController.php` | Modify | P0 | Enforce authorization and use safe tab service. |
| `Modules/System/Http/Controllers/EnvConfigController.php` | Modify | P0/P1 | Enforce authorization and remove duplicated inline tabs. |
| `Modules/System/Http/Controllers/DatabaseController.php` | Modify | P0 | Authorize and harden backup downloads. |
| `Modules/System/Http/Controllers/Api/SystemController.php` | Modify or remove | P0 | Match API route decision and avoid missing action. |
| `Modules/System/Livewire/Settings/ArtisanList.php` | Modify or disable | P0 | Remove arbitrary Artisan execution. |
| `Modules/System/resources/views/livewire/settings/artisan-list.blade.php` | Modify | P0 | Remove free-form terminal and destructive command suggestions. |
| `Modules/System/Livewire/Settings/ShScript.php` | Modify or disable | P0 | Remove arbitrary shell script editing/execution. |
| `Modules/System/resources/views/livewire/settings/sh-script.blade.php` | Modify | P0 | Remove shell script editor/executor UI. |
| `Modules/System/Services/DatabaseService.php` | Modify | P0 | Harden backups/restores/destructive DB operations and storage policy. |
| `Modules/System/Livewire/Database/TableList.php` | Modify | P0 | Add authorization, server-side identifier validation, and safer restore/backup flow. |
| `Modules/System/resources/views/livewire/database/table-list.blade.php` | Modify | P0 | Remove browser-provided paths and improve destructive confirmations. |
| `Modules/System/Livewire/Database/ImportDrawer.php` | Modify or remove | P0/P1 | SQL import calls missing service method and is destructive. |
| `Modules/System/resources/views/livewire/database/import-drawer.blade.php` | Modify or remove | P0/P1 | Import UI should not expose unsafe restore. |
| `Modules/System/Livewire/Database/BackupManager.php` | Modify or remove | P1 | Calls missing `DatabaseService` methods and appears unused. |
| `Modules/System/resources/views/livewire/database/backup-manager.blade.php` | Modify or remove | P1 | Backup UI must align with safe service contract. |
| `Modules/System/Livewire/Settings/ModulesForm.php` | Modify | P0/P1 | Add authorization and atomic manifest writes. |
| `Modules/System/resources/views/livewire/settings/modules-form.blade.php` | Modify | P0/P1 | Reflect safer permission/confirmation states. |
| `Modules/System/Livewire/Settings/DatabaseConfig.php` | Modify | P0/P1 | Add authorization, validation, env allowlisting, and System view namespace. |
| `Modules/System/resources/views/livewire/settings/database-config.blade.php` | Modify or remove | P1/P2 | Use System view if component remains System-owned. |
| `Modules/System/Livewire/Settings/MailConfig.php` | Modify | P0/P1 | Add authorization and full mail config validation. |
| `Modules/System/resources/views/livewire/settings/mail-config.blade.php` | Modify | P1 | Surface validation and permission-denied states. |
| `Modules/System/Livewire/Settings/MomoConfig.php` | Modify | P0/P1 | Add authorization, validation, and System view namespace. |
| `Modules/System/resources/views/livewire/settings/momo-config.blade.php` | Modify or remove | P1/P2 | Use System view if component remains System-owned. |
| `Modules/System/Livewire/Settings/AdvancedConfig.php` | Modify | P0/P1 | Add authorization and validate queue/NodeJS settings. |
| `Modules/System/resources/views/livewire/settings/advanced-config.blade.php` | Modify | P1 | Surface validation and permission-denied states. |
| `Modules/System/Livewire/Settings/SocialConfig.php` | Modify | P0/P1 | Add authorization and validate social/analytics keys. |
| `Modules/System/resources/views/livewire/settings/social-config.blade.php` | Modify | P1 | Surface validation and permission-denied states. |
| `Modules/System/Livewire/Settings/EnvManager.php` | Modify or remove | P1/P2 | Empty tab definition and unsafe suffix export must be resolved. |
| `Modules/System/resources/views/livewire/settings/env-manager.blade.php` | Modify or remove | P2 | Incomplete/commented feature. |
| `Modules/System/Services/Env/EnvManagerService.php` | Modify | P1 | Add key allowlists, suffix validation, locks, and atomic writes. |
| `Modules/System/Services/Env/EnvBackupService.php` | Modify | P1 | Make backups safe and integrated with atomic env writes. |
| `Modules/System/Services/Env/MailConfigService.php` | Modify | P0/P1 | Redact test-mail errors and validate config contract. |
| `Modules/System/Services/Env/SystemConfigService.php` | Modify | P0/P1 | Redact Node/queue errors and validate bridge inputs. |
| `Modules/System/Services/Env/SocialConfigService.php` | Modify | P1 | Expand credential validation if retained. |
| `Modules/System/Services/Database/DbConnectionService.php` | Modify | P0/P1 | Redact DB connection errors and validate config contract. |
| `Modules/System/Services/SystemConfigService.php` | Modify | P1/P2 | Centralize tab allowlist and fix cache invalidation. |
| `Modules/System/resources/views/system.blade.php` | Modify | P0/P1 | Restrict dynamic component rendering to allowlisted aliases. |
| `Modules/System/resources/views/pages/settings/env.blade.php` | Modify | P0/P1 | Restrict dynamic component rendering to allowlisted aliases. |
| `Modules/System/resources/views/pages/database.blade.php` | Modify if needed | P0/P1 | Mount only safe database components. |
| `Modules/System/resources/views/pages/settings/index.blade.php` | Modify if needed | P1 | Align settings shell with canonical ownership. |
| `Modules/System/resources/views/pages/settings/modules.blade.php` | Modify if needed | P0/P1 | Align module toggle page with authorization. |
| `Modules/System/resources/views/pages/settings/profile.blade.php` | Remove or move | P1/P2 | Cross-module Website profile UI has no System route. |
| `Modules/System/Http/Controllers/SettingController.php` | Modify | P1/P2 | Remove unused `profile()` or route it intentionally. |
| `Modules/System/Livewire/Settings/SettingForm.php` | Modify | P1/P2 | Keep dynamic tab mapping safe and remove cross-module leakage where needed. |
| `Modules/System/resources/views/livewire/settings/setting-form.blade.php` | Modify | P2 | Remove CDN-pushed assets and align UI with current frontend stack. |
| `Modules/System/Livewire/Settings/Partials/General.php` | Modify | P1/P2 | Fix state mismatch and move toward canonical settings service. |
| `Modules/System/resources/views/livewire/settings/partials/general.blade.php` | Modify | P1 | Align visible fields with PHP state/rules. |
| `Modules/System/Livewire/Settings/Partials/Images.php` | Modify | P1 | Make file replacement safer and atomic. |
| `Modules/System/resources/views/livewire/settings/partials/images.blade.php` | Modify if needed | P1 | Surface validation and safer file states. |
| `Modules/System/Livewire/Settings/Partials/Seo.php` | Modify | P1/P2 | Escape/sanitize preview and move toward batch settings access. |
| `Modules/System/resources/views/livewire/settings/partials/seo.blade.php` | Modify | P1 | Remove raw unsafe SEO preview output. |
| `Modules/System/Livewire/Settings/Partials/Custom.php` | Modify | P1 | Add upload validation, HTML policy, and safer multi-file writes. |
| `Modules/System/resources/views/livewire/settings/partials/custom.blade.php` | Modify | P1 | Surface validation for dynamic uploads/fields. |
| `Modules/System/Models/Setting.php` | Modify or deprecate | P1/P2 | Resolve duplicate settings model and improve settings access. |
| `Modules/Admin/Models/Setting.php` | Future cross-module change | P1 | Needed only when canonical settings ownership is decided. |
| `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php` | Future cross-module change | P1 | Repair settings migration ownership/order after planning. |
| `Modules/System/resources/views/pages/index.blade.php` | Remove after verification | P2 | Placeholder page appears unused. |
| `Modules/System/resources/views/pages/settings/placeholder.blade.php` | Remove after verification | P2 | Placeholder page appears unused. |
| `Modules/System/resources/views/livewire/placeholder.blade.php` | Remove after verification | P2 | Placeholder view appears unused. |
| `Modules/System/resources/views/components/placeholder.blade.php` | Remove after verification | P2 | Placeholder component appears unused. |
| `Modules/System/Livewire/Settings/Placeholder.php` | Remove after verification | P2 | Placeholder component appears unused. |
| `Modules/System/Livewire/Settings/StorageConfig.php` | Complete or remove | P2 | Render-only placeholder. |
| `Modules/System/resources/views/livewire/settings/storage-config.blade.php` | Complete or remove | P2 | Placeholder storage UI. |
| `docs/modules/System/ANALYSIS.md` | Update documentation | P2 | Keep analysis current after refactor. |
| `docs/modules/System/README.md` | Create/update documentation | P2 | Document final System boundary. |
| `docs/modules/System/INFORMATION.md` | Create/update documentation | P2 | Document final ownership and operational rules. |

## 7. Risk Control

Do not refactor System and Admin ownership in the same first step. The first implementation should only contain P0 containment: permissions, disabling arbitrary command execution, hardened database identifiers/paths, safe errors, and tests. The settings model/migration ownership problem touches `Modules/Admin` and should wait until denied-access and route/component smoke tests exist.

Do not rename routes, permissions, Livewire aliases, database tables, or migration files casually. Any rename must be planned with compatibility, seeders, existing menus, and permission assignments in mind.

Do not expose SQL import or full restore in production while the restore service still accepts browser-provided paths or drops tables before a verified restore plan. Keep SQL import disabled until a safe service contract, audit log, and pre-restore backup flow are in place.

Do not delete placeholder or unused-looking files until route discovery, Livewire alias registration, and view reference checks prove they are unused. Several files look stale, but removing them before tests could break hidden links or menus.

Do not move settings ownership out of System/Admin during P0. First stabilize the security boundary, then decide canonical ownership with architecture tests and migration smoke tests.

Do not add new shared abstractions until at least two modules have a stable need. For System, prefer local services for P0 hardening, and consider `Modules/Shared/Services/ImportExport` only after the destructive SQL restore path is made safe.
