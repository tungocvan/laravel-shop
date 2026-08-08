# Admin Refactor Plan

## 1. Executive Summary

`Modules/Admin` should remain a shell module: layout, navigation, dashboard entry, admin profile, and shell-owned UI. The current module contains high-risk system/database controls, unauthenticated API exposure, direct Livewire persistence, duplicated import/export logic, stale route files, and cross-domain business ownership for product, order, post, category, coupon, customer, role, staff, affiliate, banner, flash sale, chat, and settings features.

The refactor must proceed in this order:

1. Contain P0 security risks around API exposure and database administration.
2. Establish clear Admin shell boundaries and named permissions.
3. Move business logic out of Livewire and into services owned by canonical modules.
4. Standardize import/export on `Modules/Shared/Services/ImportExport`.
5. Clean stale files only after route/link/component verification.

No code should be changed until the rebuild specs for security controls, canonical ownership, and import/export behavior are confirmed.

## 2. P0 Critical Fixes

### P0-1: Unauthenticated Admin API Route

- Issue: `Modules/Admin/routes/api.php` exposes `GET /api/admin` without authentication or named permission checks.
- Root Cause: The route file registers `Route::prefix('admin')` directly and the module provider wraps API files only with the global `api` middleware.
- Business Impact: Public callers can detect an admin API surface and potentially use it as a foothold for later expansion.
- Technical Impact: Violates the project rule that admin/system routes must fail closed and require explicit authorization.
- Proposed Solution: Remove the route if it is only a health stub, or add an explicit authenticated admin/API guard and named permission such as `admin.api.view`. Keep the controller thin.
- Files To Change:
  - `Modules/Admin/routes/api.php`
  - `Modules/Admin/Http/Controllers/Api/AdminController.php`
  - Permission seeder or manifest file to be confirmed
  - Route/security tests to be created under `tests/Feature` or module test location to be confirmed
- Risk Level: Critical
- Complexity: Low
- Estimated Effort: 0.5 day
- Acceptance Criteria:
  - Anonymous request to `/api/admin` is denied.
  - Authenticated user without the named permission is denied.
  - Authorized admin receives the intended response, or the route no longer exists.

### P0-2: Database Download Lacks Authorization

- Issue: `Modules/Admin/Http/Controllers/DatabaseController.php` has the authorization call commented out in `download($filename)`.
- Root Cause: Database download behavior was wired before capability-level permissions and server-owned backup identifiers were enforced.
- Business Impact: A compromised or low-privilege admin account may download database backups.
- Technical Impact: Violates P0 roadmap requirements for backup downloads, path validation, named permissions, and auditability.
- Proposed Solution: Require a named permission such as `database.backup.download`, replace raw filename access with server-issued backup identifiers, and log/audit every successful and denied download.
- Files To Change:
  - `Modules/Admin/Http/Controllers/DatabaseController.php`
  - `Modules/Admin/Services/DatabaseService.php`
  - `Modules/Admin/routes/web.php` if database route is re-enabled
  - `Modules/Admin/routes/web copy.php` only if migrating active database routes out of the stale file
  - Permission seeder or manifest file to be confirmed
  - Security tests for download authorization and path traversal
- Risk Level: Critical
- Complexity: Medium
- Estimated Effort: 1 day
- Acceptance Criteria:
  - Backup downloads require a named permission in addition to `auth:admin`.
  - Browser-provided filenames cannot select arbitrary paths.
  - Missing/invalid backup identifiers return a safe 404/403 without leaking filesystem paths.

### P0-3: Dangerous Database Livewire Actions

- Issue: `Modules/Admin/Livewire/Database/TableList.php` exposes `exportTable`, `truncateTable`, and `dropTable` actions that delegate to destructive database service methods.
- Root Cause: System/database administration was built as direct admin UI behavior without P0 permission, confirmation, audit, and production gating.
- Business Impact: A user with access to the Livewire component can destroy or export production data.
- Technical Impact: Violates Laravel/Livewire security boundaries by trusting component actions and table names from client state.
- Proposed Solution: Disable destructive database actions until the P0 security design is implemented. Re-enable only behind named permissions, explicit confirmation tokens, server-side table allowlists, audit logs, and environment gates.
- Files To Change:
  - `Modules/Admin/Livewire/Database/TableList.php`
  - `Modules/Admin/resources/views/livewire/database/table-list.blade.php`
  - `Modules/Admin/Services/DatabaseService.php`
  - `Modules/Admin/resources/views/pages/database.blade.php`
  - `Modules/Admin/routes/web.php` if database routes are restored
  - Security tests for unauthorized export/truncate/drop actions
- Risk Level: Critical
- Complexity: High
- Estimated Effort: 2-3 days
- Acceptance Criteria:
  - No unauthorized Livewire call can export, truncate, drop, restore, or download database data.
  - Destructive actions require named permission, typed confirmation, server-side identifier validation, and audit records.
  - Production can fail closed while Phase 0 controls are incomplete.

### P0-4: Unsafe Shell Commands and Credential Exposure in Database Service

- Issue: `Modules/Admin/Services/DatabaseService.php` builds shell command strings containing DB credentials and table names in `backupTable`, `backupFullDatabase`, and `restoreTable`.
- Root Cause: `Process::fromShellCommandline()` was used for command redirection convenience instead of Symfony Process argument arrays and safe input handling.
- Business Impact: Database credentials may leak through process lists, logs, exceptions, or command injection surfaces.
- Technical Impact: Violates roadmap P0-02 and P0-03; browser-provided table identifiers can influence shell commands.
- Proposed Solution: Replace shell command strings with `Process` argument arrays, avoid passing passwords on the command line where possible, use safe environment variables or config files, stream dump/restore through controlled process input/output, and validate table names against schema metadata.
- Files To Change:
  - `Modules/Admin/Services/DatabaseService.php`
  - `Modules/Admin/Livewire/Database/TableList.php`
  - `Modules/Admin/Livewire/Database/BackupManager.php`
  - Tests for command argument safety and table identifier tampering
- Risk Level: Critical
- Complexity: High
- Estimated Effort: 2-4 days
- Acceptance Criteria:
  - No database password appears in constructed shell strings, logs, exception messages, or process command strings.
  - Table names are resolved from server-controlled metadata, not trusted client values.
  - Backup/restore failures return safe user-facing errors and internal redacted logs.

### P0-5: Foreign Key Checks Not Restored Reliably

- Issue: `Modules/Admin/Services/DatabaseService.php` disables foreign key checks in `truncateTable`, `dropTable`, and `restoreFromFile` without guaranteed `finally` restoration.
- Root Cause: Destructive operations are implemented as direct statements without failure-safe cleanup.
- Business Impact: A failed destructive operation can leave the database session in an unsafe state and cause data integrity damage.
- Technical Impact: Violates transaction and destructive-operation standards.
- Proposed Solution: Use `try/finally` around any foreign-key toggle, minimize FK-disabled scope, avoid `DROP TABLE`/`TRUNCATE` unless explicitly authorized, and add rollback/denial tests.
- Files To Change:
  - `Modules/Admin/Services/DatabaseService.php`
  - Tests for truncate/drop/restore failure paths
- Risk Level: Critical
- Complexity: Medium
- Estimated Effort: 1-2 days
- Acceptance Criteria:
  - Foreign key checks are restored after success and failure.
  - Destructive actions fail closed if validation, authorization, or process execution fails.
  - Negative tests prove invalid table/file input cannot alter schema.

### P0-6: Raw Exception Leakage From System Operations

- Issue: `Modules/Admin/Services/DatabaseService.php` throws raw process/database exception text back to callers.
- Root Cause: Operational errors were surfaced directly for debugging instead of using redacted domain exceptions.
- Business Impact: Users may see filesystem paths, DB names, command output, or sensitive configuration details.
- Technical Impact: Violates safe error handling and logging standards.
- Proposed Solution: Log internal errors with redaction and correlation IDs, return safe messages from controllers/Livewire, and avoid exposing process output.
- Files To Change:
  - `Modules/Admin/Services/DatabaseService.php`
  - `Modules/Admin/Http/Controllers/DatabaseController.php`
  - `Modules/Admin/Livewire/Database/TableList.php`
  - `Modules/Admin/Livewire/Database/BackupManager.php`
  - Error handling tests
- Risk Level: Critical
- Complexity: Medium
- Estimated Effort: 1 day
- Acceptance Criteria:
  - User-facing database errors are generic and localized.
  - Logs contain enough diagnostic context without credentials or raw command strings.
  - Tests verify sensitive process output is not returned.

## 3. P1 Important Refactors

### P1-1: Admin Shell Boundary and Domain Ownership

- Issue: `Modules/Admin` owns or duplicates product, order, post, category, coupon, customer, role, staff, affiliate, flash sale, banner, chat, settings, and user address behavior.
- Root Cause: Admin grew into a catch-all UI and business module instead of staying a shell.
- Business Impact: Features become harder to reason about, permission consistently breaks, and future refactors risk changing the wrong owner.
- Technical Impact: Violates `Admin is a presentation shell`; creates circular or unclear dependencies.
- Proposed Solution: Publish a canonical ownership map before moving code. Admin should keep shell-only UI. Product, Order, Post, Category, Role, User/Account, Website, System, and Shared should own their own models/services/import-export flows.
- Files To Change:
  - `docs/modules/Admin/REBUILD_SPEC.md` to be created before implementation
  - `Modules/Admin/Livewire/Products/ProductForm.php`
  - `Modules/Admin/Livewire/Products/ProductTable.php`
  - `Modules/Admin/Livewire/Posts/PostForm.php`
  - `Modules/Admin/Livewire/Posts/PostTable.php`
  - `Modules/Admin/Livewire/Orders/OrderTable.php`
  - `Modules/Admin/Livewire/Orders/OrderDetail.php`
  - `Modules/Admin/Livewire/Categories/CategoryForm.php`
  - `Modules/Admin/Livewire/Categories/CategoryTable.php`
  - `Modules/Admin/Livewire/Marketing/CouponForm.php`
  - `Modules/Admin/Livewire/Marketing/CouponTable.php`
  - `Modules/Admin/Livewire/System/RoleForm.php`
  - `Modules/Admin/Livewire/System/RoleTable.php`
  - `Modules/Admin/Livewire/System/StaffForm.php`
  - `Modules/Admin/Livewire/System/StaffTable.php`
  - `Modules/Admin/Services/AdminAffiliateService.php`
  - `Modules/Admin/Services/AffiliateRankService.php`
  - `Modules/Admin/Models/AffiliateScheme.php`
  - `Modules/Admin/Models/FlashSaleItem.php`
- Risk Level: High
- Complexity: Critical
- Estimated Effort: 1-2 weeks for mapping and staged migration
- Acceptance Criteria:
  - Each business concept has one documented canonical model and service owner.
  - Admin retains only shell/presentation responsibilities or documented facade calls.
  - Architecture tests or static checks can detect new forbidden Admin domain dependencies.

### P1-2: Active Admin Routes Lack Named Permissions

- Issue: `Modules/Admin/routes/web.php` uses only `web` and `auth:admin` for dashboard, menus, profile, themes, and admin-header.
- Root Cause: Authentication was treated as sufficient for admin screens.
- Business Impact: Any authenticated admin can mutate shell menus/settings if component methods are reachable.
- Technical Impact: Violates requirement for named permissions on privileged or mutating behavior.
- Proposed Solution: Add named route/controller/Livewire permission checks for shell capabilities such as `admin.dashboard.view`, `admin.menu.manage`, `admin.profile.manage`, `admin.theme.manage`, and `admin.header.manage`.
- Files To Change:
  - `Modules/Admin/routes/web.php`
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Admin/Livewire/Menus/MenuForm.php`
  - `Modules/Admin/Livewire/Header/GeneralSettings.php`
  - `Modules/Admin/Livewire/Header/MenuManager.php`
  - `Modules/Admin/Livewire/ThemeSwitcher.php`
  - Permission seeder or manifest file to be confirmed
  - Route and Livewire authorization tests
- Risk Level: High
- Complexity: Medium
- Estimated Effort: 1-2 days
- Acceptance Criteria:
  - Every active Admin route has an explicit capability policy.
  - Mutating Livewire methods deny users without the named permission.
  - Super Admin behavior remains covered by the project gate.

### P1-3: Menu Livewire Bypasses Service Layer

- Issue: `Modules/Admin/Livewire/Menus/MenuTable.php` and `Modules/Admin/Livewire/Menus/MenuForm.php` directly query, mutate, transact, import/export, generate slugs, and recurse menu trees.
- Root Cause: Business behavior was placed inside Livewire components for convenience.
- Business Impact: Menu mutations are difficult to authorize, test, rollback, and reuse.
- Technical Impact: Violates Livewire 3 best practice and project architecture flow.
- Proposed Solution: Create a shell-owned menu service, likely `Modules/Admin/Services/MenuService.php`, and move query, persistence, slug, tree, transaction, cache invalidation, and import/export orchestration there. Livewire keeps state, validation, and service calls.
- Files To Change:
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Admin/Livewire/Menus/MenuForm.php`
  - `Modules/Admin/Services/MenuService.php` to be created
  - `Modules/Admin/Models/Category.php` only for ORM definitions/scopes after canonical ownership confirmation
  - `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php`
  - `Modules/Admin/resources/views/livewire/menus/menu-form.blade.php`
  - Service and Livewire tests
- Risk Level: High
- Complexity: High
- Estimated Effort: 3-5 days
- Acceptance Criteria:
  - Livewire components do not call `Category::query()`, `DB::transaction()`, `File::get()`, or direct persistence methods.
  - Menu operations are covered by service tests and Livewire action tests.
  - Cache invalidation happens after successful writes only.

### P1-4: Menu Validation and Destructive Restore Behavior

- Issue: `Modules/Admin/Livewire/Menus/MenuForm.php` skips `exists` validation for `can` and `parent_id`; `Modules/Admin/Livewire/Menus/MenuTable.php` imports generic JSON and can delete all menus before import.
- Root Cause: Validation is optimistic and destructive restore lacks a staged transaction/import report.
- Business Impact: Admin navigation can be corrupted or removed by invalid input or failed restore.
- Technical Impact: Violates validation and transaction rules for mutating operations.
- Proposed Solution: Validate parent IDs, permissions, import schema, tree shape, duplicate keys, and circular parent relationships in a service. Restore should dry-run first, then all-or-nothing replace only after explicit confirmation.
- Files To Change:
  - `Modules/Admin/Livewire/Menus/MenuForm.php`
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Admin/Services/MenuService.php` to be created
  - `Modules/Admin/data/menus.json`
  - `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php`
  - Tests for invalid parent, invalid permission, bad JSON, partial failure, and restore confirmation
- Risk Level: High
- Complexity: High
- Estimated Effort: 2-4 days
- Acceptance Criteria:
  - Invalid menu imports produce structured errors without changing data.
  - Restore cannot delete existing menus unless the replacement has fully validated.
  - Parent and permission validation is explicit and user-visible.

### P1-5: Import/Export Does Not Use Shared v1.5 Foundation

- Issue: Product import/export classes and Livewire import/export methods use custom logic instead of `Modules/Shared/Services/ImportExport`.
- Root Cause: Legacy implementations predate the active FastExcel/shared import-export standard.
- Business Impact: Imports can silently create duplicates, overwrite data, or fail without actionable reports.
- Technical Impact: Violates v1.5 import/export architecture and makes large files unsafe.
- Proposed Solution: For each canonical owner, create or migrate to `Services/ImportExport.php` using the shared base service. Admin should not own product/post/coupon/role import/export unless those domains are explicitly reassigned.
- Files To Change:
  - `Modules/Admin/Imports/ProductsImport.php`
  - `Modules/Admin/Exports/ProductsExport.php`
  - `Modules/Admin/Livewire/Products/ProductTable.php`
  - `Modules/Admin/Livewire/Posts/PostTable.php`
  - `Modules/Admin/Livewire/Marketing/CouponTable.php`
  - `Modules/Admin/Livewire/System/RoleTable.php`
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Shared/Services/ImportExport/BaseImportExportService.php` only if extension points are missing
  - Canonical module import/export services to be confirmed
- Risk Level: High
- Complexity: Critical
- Estimated Effort: 1 week, staged by domain
- Acceptance Criteria:
  - Import/export entry point is a module service, not a Livewire component or model class.
  - Dry-run, unique key, null-overwrite, duplicate mode, validation report, and bounded export behavior are documented and tested.
  - No Admin product/post/coupon/role import/export remains active unless Admin is confirmed owner.

### P1-6: Product Import/Export Uses Unbounded Queries and Direct Persistence

- Issue: `Modules/Admin/Imports/ProductsImport.php` creates `Modules\Website\Models\WpProduct` directly; `Modules/Admin/Exports/ProductsExport.php` calls `get()` for all products when no IDs are supplied.
- Root Cause: Import/export classes own persistence and query behavior directly.
- Business Impact: Product data can be duplicated, partially imported, or exported in a memory-heavy request.
- Technical Impact: Violates service ownership, transaction, validation, and bounded iteration standards.
- Proposed Solution: Move product import/export to the Product module or confirmed canonical owner. Use shared import/export service with chunked/lazy export and explicit product unique key.
- Files To Change:
  - `Modules/Admin/Imports/ProductsImport.php`
  - `Modules/Admin/Exports/ProductsExport.php`
  - `Modules/Admin/Livewire/Products/ProductTable.php`
  - Canonical product import/export files to be confirmed
  - Product import/export tests
- Risk Level: High
- Complexity: High
- Estimated Effort: 3-5 days after mapping confirmation
- Acceptance Criteria:
  - Product import has a confirmed unique key and transaction policy.
  - Product export does not load all records unbounded.
  - Import validation rejects malformed money, JSON, booleans, categories, and missing required fields.

### P1-7: Direct Model Queries in Controllers and Livewire

- Issue: `Modules/Admin/Http/Controllers/ProductCommissionController.php` queries `WpProduct`; many Admin Livewire classes query Website/App/Admin models directly.
- Root Cause: UI classes were used as business/application services.
- Business Impact: Business rules are scattered and hard to secure consistently.
- Technical Impact: Violates Controller and Livewire boundaries.
- Proposed Solution: Controllers return views with scalar IDs only. Livewire calls canonical services. Domain queries move to services owned by the canonical module.
- Files To Change:
  - `Modules/Admin/Http/Controllers/ProductCommissionController.php`
  - `Modules/Admin/Livewire/Products/ProductForm.php`
  - `Modules/Admin/Livewire/Products/ProductTable.php`
  - `Modules/Admin/Livewire/Posts/PostForm.php`
  - `Modules/Admin/Livewire/Posts/PostTable.php`
  - `Modules/Admin/Livewire/Orders/OrderTable.php`
  - `Modules/Admin/Livewire/Orders/OrderDetail.php`
  - `Modules/Admin/Livewire/Categories/CategoryForm.php`
  - `Modules/Admin/Livewire/Categories/CategoryTable.php`
  - `Modules/Admin/Livewire/Home/HomeSettings.php`
  - `Modules/Admin/Livewire/Footer/FooterColumns.php`
  - `Modules/Admin/Livewire/Footer/FooterInfo.php`
  - `Modules/Admin/Livewire/Footer/SocialLinks.php`
- Risk Level: High
- Complexity: Critical
- Estimated Effort: 1-2 weeks, staged
- Acceptance Criteria:
  - No controller performs model queries.
  - Livewire components call services for search, pagination, persistence, deletes, imports, and exports.
  - Existing behavior is covered before moving callers.

### P1-8: Settings and Category Ownership Is Duplicated

- Issue: `Modules/Admin/Models/Category.php` owns `categories`; `Modules/Admin/Models/Setting.php` owns `settings`; Admin components also reference Website settings/category models.
- Root Cause: Shell menus/settings and domain categories/settings share table/model names without a clear canonical owner.
- Business Impact: Updates may affect the wrong feature or table owner.
- Technical Impact: Causes duplicate models, unclear migrations, and cross-module dependency risk.
- Proposed Solution: Split concepts explicitly. If Admin needs shell menu items, use an Admin-owned table/model name or a confirmed Category module owner. Settings should have one canonical owner and service API.
- Files To Change:
  - `Modules/Admin/Models/Category.php`
  - `Modules/Admin/Models/Setting.php`
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Admin/Livewire/Menus/MenuForm.php`
  - `Modules/Admin/Livewire/Settings/SettingForm.php`
  - `Modules/Admin/Livewire/Home/HomeSettings.php`
  - `Modules/Admin/Services/SettingsService.php`
  - `Modules/Admin/Services/HomeSettingService.php`
  - Migrations for `settings` and menu/category ownership to be confirmed
- Risk Level: High
- Complexity: High
- Estimated Effort: 3-7 days after ownership decision
- Acceptance Criteria:
  - Category/menu/settings ownership is documented.
  - Admin does not duplicate Website/Category settings behavior.
  - Existing data migration or compatibility plan is explicit before schema changes.

### P1-9: Malformed Admin Migration Timestamps

- Issue: `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`, `-0001_11_30_000034_create_header_menus_table.php`, and `-0001_11_30_000035_create_header_menu_items_table.php` use negative-year timestamps.
- Root Cause: Migration files were generated or renamed incorrectly.
- Business Impact: Fresh install ordering can be unstable or fail.
- Technical Impact: Violates migration hygiene and roadmap P1-08.
- Proposed Solution: Plan a migration renaming strategy that preserves already-run production migrations. Use new timestamped migrations only if production has already recorded old filenames.
- Files To Change:
  - `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`
  - `Modules/Admin/database/migrations/-0001_11_30_000034_create_header_menus_table.php`
  - `Modules/Admin/database/migrations/-0001_11_30_000035_create_header_menu_items_table.php`
  - Migration compatibility documentation or rebuild spec
  - Migration smoke tests
- Risk Level: High
- Complexity: Medium
- Estimated Effort: 1-2 days
- Acceptance Criteria:
  - Fresh migration ordering is deterministic.
  - Production migration history compatibility is documented.
  - Migration smoke test passes on the supported DB path.

### P1-10: Unbounded Pagination and N+1 Risks

- Issue: `Modules/Admin/Livewire/Menus/MenuTable.php` may N+1 deeper children; `Modules/Admin/Livewire/Products/ProductTable.php` uses `paginate(999999)`; `Modules/Admin/Exports/ProductsExport.php` uses unbounded `get()`.
- Root Cause: Query performance is handled in UI/import-export classes instead of services with bounded iteration.
- Business Impact: Admin pages and exports can become slow or fail with production data.
- Technical Impact: Violates performance standards for pagination, eager loading, and large exports.
- Proposed Solution: Move queries to services, cap or disable unsafe `All`, use recursive eager loading only to a known depth or a tree query strategy, and chunk/lazy export large data.
- Files To Change:
  - `Modules/Admin/Livewire/Menus/MenuTable.php`
  - `Modules/Admin/Livewire/Menus/MenuForm.php`
  - `Modules/Admin/Livewire/Products/ProductTable.php`
  - `Modules/Admin/Exports/ProductsExport.php`
  - `Modules/Admin/Services/MenuService.php` to be created
  - Query-count/performance tests
- Risk Level: Medium
- Complexity: Medium
- Estimated Effort: 2-4 days
- Acceptance Criteria:
  - List queries are paginated or demonstrably bounded.
  - Export does not load unbounded datasets into memory.
  - Menu tree rendering has no known deeper-level N+1 issue.

### P1-11: Address Default Updates Are Not Transactional

- Issue: `Modules/Admin/Services/AddressService.php` performs multi-step default-address updates without explicit transactions.
- Root Cause: Default address logic updates multiple records in separate statements.
- Business Impact: A user may end up with zero or multiple default addresses after partial failure.
- Technical Impact: Violates transaction rules for multi-write operations.
- Proposed Solution: Wrap create/update/delete/set-default paths that alter default flags in transactions and enforce owner constraints.
- Files To Change:
  - `Modules/Admin/Services/AddressService.php`
  - `Modules/Admin/Livewire/Profile/UserAddress.php`
  - Address service tests
- Risk Level: Medium
- Complexity: Low
- Estimated Effort: 0.5-1 day
- Acceptance Criteria:
  - Default address updates are atomic.
  - Tests cover create first default, change default, delete default, and unauthorized address ID.

### P1-12: Reusable UI Components May Belong in Shared

- Issue: Admin defines potentially reusable components such as category select, currency input, editor, gallery, and image upload.
- Root Cause: Shared UI primitives were placed in the Admin shell.
- Business Impact: Other modules may copy UI components or depend on Admin unnecessarily.
- Technical Impact: Creates shell-to-domain/shared coupling.
- Proposed Solution: Audit component usage. Move genuinely cross-module UI primitives to `Modules/Shared` after call sites are known. Keep Admin-only menu/layout components in Admin.
- Files To Change:
  - `Modules/Admin/resources/views/components/category-select.blade.php`
  - `Modules/Admin/resources/views/components/currency-input.blade.php`
  - `Modules/Admin/resources/views/components/editor.blade.php`
  - `Modules/Admin/resources/views/components/gallery.blade.php`
  - `Modules/Admin/resources/views/components/image-upload.blade.php`
  - Target shared component paths under `Modules/Shared/resources/views/components` to be confirmed
  - Blade call sites to be found before implementation
- Risk Level: Medium
- Complexity: Medium
- Estimated Effort: 1-3 days
- Acceptance Criteria:
  - Components used by multiple modules live in Shared.
  - Admin shell components remain in Admin.
  - No module depends on Admin only to render generic form controls.

## 4. P2 Nice To Have Improvements

### P2-1: Stale `web copy.php`

- Issue: `Modules/Admin/routes/web copy.php` contains many route definitions but is not loaded.
- Root Cause: A backup or legacy route file was left in the module routes directory.
- Business Impact: Developers may mistake stale routes for active behavior.
- Technical Impact: Increases analysis noise and may hide route drift.
- Proposed Solution: Verify no route or documentation depends on it, migrate any intentionally active route to `Modules/Admin/routes/web.php` with permissions, then delete the stale file.
- Files To Change:
  - `Modules/Admin/routes/web copy.php`
  - `Modules/Admin/routes/web.php` only if confirmed routes must be restored
  - Route tests
- Risk Level: Low
- Complexity: Low
- Estimated Effort: 0.5 day
- Acceptance Criteria:
  - Confirmed unused stale route file is removed.
  - Active route list is covered by route boot tests.

### P2-2: Windows Metadata Artifact

- Issue: `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier` is a Windows metadata artifact.
- Root Cause: A downloaded file sidecar was committed or copied into the repository.
- Business Impact: None directly, but it creates repository noise.
- Technical Impact: Can confuse file scans and deployment packaging.
- Proposed Solution: Remove after confirming it is not referenced.
- Files To Change:
  - `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier`
- Risk Level: Low
- Complexity: Low
- Estimated Effort: 0.1 day
- Acceptance Criteria:
  - Artifact is removed.
  - No app references the artifact path.

### P2-3: Duplicate Livewire Blade Trees

- Issue: Duplicate view trees exist under `Modules/Admin/resources/views/livewire/admin/*` and `Modules/Admin/resources/views/livewire/*`.
- Root Cause: Component view namespaces were likely changed or duplicated during earlier refactors.
- Business Impact: UI fixes may be applied to the wrong file.
- Technical Impact: Increases stale code and view resolution ambiguity.
- Proposed Solution: Use Livewire view resolution and references to identify active views. Delete duplicates only after tests or manual route checks confirm inactive files.
- Files To Change:
  - `Modules/Admin/resources/views/livewire/admin/affiliate/commission-list.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/affiliate/commission-matrix.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/banner/banner-manager.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/flash-sale/flash-sale-manager.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/footer/footer-columns.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/footer/footer-info.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/footer/social-links.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/header/general-settings.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/header/header-settings-hub.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/header/menu-manager.blade.php`
  - `Modules/Admin/resources/views/livewire/admin/home/home-settings.blade.php`
- Risk Level: Low
- Complexity: Medium
- Estimated Effort: 1 day
- Acceptance Criteria:
  - Only active Livewire views remain.
  - Removed views have no references from component `render()` methods.

### P2-4: Scaffold and Placeholder Files

- Issue: `Modules/Admin/Http/Controllers/AdminController.php` has empty resource methods; `Modules/Admin/Models/Admin.php`, `Modules/Admin/Livewire/Settings/Placeholder.php`, and `Modules/Admin/resources/views/pages/settings/placeholder.blade.php` look unused.
- Root Cause: Scaffolding and placeholder screens were left in place.
- Business Impact: Noise slows maintenance and creates false feature expectations.
- Technical Impact: Adds dead code and unused classes to module discovery/autoload.
- Proposed Solution: Verify references, then remove unused scaffold/placeholder methods/files. Keep `AdminController@index`, `adminHeader`, and `themes` if still active.
- Files To Change:
  - `Modules/Admin/Http/Controllers/AdminController.php`
  - `Modules/Admin/Models/Admin.php`
  - `Modules/Admin/Livewire/Settings/Placeholder.php`
  - `Modules/Admin/resources/views/pages/settings/placeholder.blade.php`
  - `Modules/Admin/resources/views/livewire/settings/placeholder.blade.php` if present during verification
- Risk Level: Low
- Complexity: Low
- Estimated Effort: 0.5 day
- Acceptance Criteria:
  - Empty unused resource methods/files are removed.
  - Active themes/header/admin page behavior remains unchanged.

### P2-5: Permission Display in Menu Component Needs Verification

- Issue: `Modules/Admin/resources/views/components/menu-item.blade.php` uses permission data for link rendering; this can be mistaken for real authorization.
- Root Cause: UI navigation and permission enforcement may be conflated.
- Business Impact: Hidden menu items may give a false sense of security.
- Technical Impact: UI checks do not protect routes or Livewire actions.
- Proposed Solution: Keep UI permission filtering only as convenience. Ensure matching server-side route and action authorization exists.
- Files To Change:
  - `Modules/Admin/resources/views/components/menu-item.blade.php`
  - `Modules/Admin/routes/web.php`
  - Livewire components for actions linked by menu entries, especially `Modules/Admin/Livewire/Menus/MenuTable.php` and `Modules/Admin/Livewire/Menus/MenuForm.php`
- Risk Level: Low
- Complexity: Low
- Estimated Effort: 0.5 day after P1 permission design
- Acceptance Criteria:
  - Menu hiding is documented as UI-only.
  - Every linked privileged route/action has server-side authorization.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. Remove or secure `Modules/Admin/routes/api.php`.
2. Disable or permission-gate database download/export/truncate/drop/restore flows in `Modules/Admin/Http/Controllers/DatabaseController.php`, `Modules/Admin/Livewire/Database/TableList.php`, and `Modules/Admin/Services/DatabaseService.php`.
3. Replace database shell command strings and credential exposure in `Modules/Admin/Services/DatabaseService.php`.
4. Add safe error handling and foreign-key restoration guards in `Modules/Admin/Services/DatabaseService.php`.
5. Add named permissions for active `Modules/Admin/routes/web.php` routes and active mutating Livewire actions.

### Phase 2: Correctness and Maintainability

1. Create a rebuild spec documenting canonical ownership for Admin, Product, Order, Post, Category, Role, User/Account, Website, System, and Shared.
2. Extract Admin menu behavior from `Modules/Admin/Livewire/Menus/MenuTable.php` and `Modules/Admin/Livewire/Menus/MenuForm.php` into a shell-owned service.
3. Fix menu validation, restore, and import/export behavior without changing user-facing behavior unexpectedly.
4. Migrate product/post/coupon/role import-export to canonical modules using `Modules/Shared/Services/ImportExport`.
5. Move direct model queries out of Admin controllers and Livewire classes into services.
6. Resolve settings/category ownership and malformed migration strategy.

### Phase 3: Performance and Cleanup

1. Bound large exports and unsafe `All` pagination.
2. Add query-count coverage for menu trees and relationship-heavy lists.
3. Verify and remove `Modules/Admin/routes/web copy.php`.
4. Remove Zone.Identifier, placeholder, scaffold, and duplicate Livewire view files after reference checks.
5. Move reusable UI components to `Modules/Shared` only after confirming cross-module use.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Admin/routes/api.php` | Modify or delete | P0 | Unauthenticated admin API route |
| `Modules/Admin/Http/Controllers/Api/AdminController.php` | Modify or delete | P0 | API response should be protected or removed |
| `Modules/Admin/Http/Controllers/DatabaseController.php` | Modify | P0 | Database downloads lack authorization and safe identifiers |
| `Modules/Admin/Livewire/Database/TableList.php` | Modify or disable actions | P0 | Export/truncate/drop table actions are dangerous |
| `Modules/Admin/Livewire/Database/BackupManager.php` | Modify | P0 | Backup/restore UI must enforce permissions and safe errors |
| `Modules/Admin/resources/views/livewire/database/table-list.blade.php` | Modify | P0 | Dangerous UI actions need confirmation/permission states |
| `Modules/Admin/resources/views/pages/database.blade.php` | Modify | P0 | Database admin page must not expose unsafe controls |
| `Modules/Admin/Services/DatabaseService.php` | Modify | P0 | Shell command strings, credentials, FK toggles, raw errors |
| `Modules/Admin/routes/web.php` | Modify | P1 | Add named permissions for active admin routes |
| `Modules/Admin/Livewire/Menus/MenuTable.php` | Modify | P1 | Move queries, transactions, import/export, persistence to service |
| `Modules/Admin/Livewire/Menus/MenuForm.php` | Modify | P1 | Move queries, slug logic, validation, persistence to service |
| `Modules/Admin/Services/MenuService.php` | Create | P1 | Service owner for shell menu behavior |
| `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php` | Modify | P1 | Add loading/disabled/confirmation/error states |
| `Modules/Admin/resources/views/livewire/menus/menu-form.blade.php` | Modify | P1 | Align with service-backed validation and errors |
| `Modules/Admin/data/menus.json` | Review | P1 | Default menu import needs schema validation |
| `Modules/Admin/Models/Category.php` | Modify or migrate | P1 | Category/menu ownership is unclear |
| `Modules/Admin/Models/Setting.php` | Modify or migrate | P1 | Settings ownership duplicated |
| `Modules/Admin/Livewire/Settings/SettingForm.php` | Modify/migrate | P1 | Uses Website settings model from Admin UI |
| `Modules/Admin/Livewire/Home/HomeSettings.php` | Modify/migrate | P1 | Direct settings/product/category queries in Admin |
| `Modules/Admin/Services/SettingsService.php` | Modify/migrate | P1 | Settings service ownership needs confirmation |
| `Modules/Admin/Services/HomeSettingService.php` | Modify/migrate | P1 | Homepage settings likely domain/Website behavior |
| `Modules/Admin/Imports/ProductsImport.php` | Delete/migrate | P1 | Legacy product import bypasses shared foundation |
| `Modules/Admin/Exports/ProductsExport.php` | Delete/migrate | P1 | Legacy product export is unbounded |
| `Modules/Admin/Livewire/Products/ProductTable.php` | Modify/migrate | P1 | Product management/import-export belongs to canonical owner |
| `Modules/Admin/Livewire/Products/ProductForm.php` | Modify/migrate | P1 | Product form uses Website models directly |
| `Modules/Admin/Livewire/Posts/PostTable.php` | Modify/migrate | P1 | Post management/import-export belongs to canonical owner |
| `Modules/Admin/Livewire/Posts/PostForm.php` | Modify/migrate | P1 | Post form uses Website models directly |
| `Modules/Admin/Livewire/Marketing/CouponTable.php` | Modify/migrate | P1 | Coupon import/export in Livewire |
| `Modules/Admin/Livewire/Marketing/CouponForm.php` | Modify/migrate | P1 | Coupon ownership likely Website/domain |
| `Modules/Admin/Livewire/System/RoleTable.php` | Modify/migrate | P1 | Role import/export and mutations should belong to Role/System owner |
| `Modules/Admin/Livewire/System/RoleForm.php` | Modify/migrate | P1 | Role form duplicated outside Role module |
| `Modules/Admin/Livewire/System/StaffTable.php` | Modify/migrate | P1 | Staff ownership and authorization need canonical owner |
| `Modules/Admin/Livewire/System/StaffForm.php` | Modify/migrate | P1 | Staff form uses `App\Models\User` directly |
| `Modules/Admin/Http/Controllers/ProductCommissionController.php` | Modify/migrate | P1 | Controller queries Website product directly |
| `Modules/Admin/Livewire/Orders/OrderTable.php` | Modify/migrate | P1 | Order logic belongs to canonical Order module |
| `Modules/Admin/Livewire/Orders/OrderDetail.php` | Modify/migrate | P1 | Order detail uses Website order model |
| `Modules/Admin/Livewire/Categories/CategoryTable.php` | Modify/migrate | P1 | Category logic belongs to canonical Category module |
| `Modules/Admin/Livewire/Categories/CategoryForm.php` | Modify/migrate | P1 | Category form uses Website category model |
| `Modules/Admin/Livewire/Footer/FooterColumns.php` | Modify/migrate | P1 | Uses Website footer service/model |
| `Modules/Admin/Livewire/Footer/FooterInfo.php` | Modify/migrate | P1 | Uses Website settings service |
| `Modules/Admin/Livewire/Footer/SocialLinks.php` | Modify/migrate | P1 | Uses Website footer service/model |
| `Modules/Admin/Services/AdminAffiliateService.php` | Modify/migrate | P1 | Affiliate/order logic crosses Website/App boundaries |
| `Modules/Admin/Services/AffiliateRankService.php` | Modify/migrate | P1 | Affiliate rank logic crosses Website/App boundaries |
| `Modules/Admin/Models/AffiliateScheme.php` | Modify/migrate | P1 | Domain model in Admin shell |
| `Modules/Admin/Models/FlashSaleItem.php` | Modify/migrate | P1 | Domain model depends on Website product |
| `Modules/Admin/Services/AddressService.php` | Modify | P1 | Default address multi-write operations need transactions |
| `Modules/Admin/Livewire/Profile/UserAddress.php` | Modify | P1 | Address actions should surface service validation/errors |
| `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php` | Rename/compatibility plan | P1 | Malformed migration timestamp |
| `Modules/Admin/database/migrations/-0001_11_30_000034_create_header_menus_table.php` | Rename/compatibility plan | P1 | Malformed migration timestamp |
| `Modules/Admin/database/migrations/-0001_11_30_000035_create_header_menu_items_table.php` | Rename/compatibility plan | P1 | Malformed migration timestamp |
| `Modules/Admin/resources/views/components/category-select.blade.php` | Move/review | P1 | Potential shared UI component |
| `Modules/Admin/resources/views/components/currency-input.blade.php` | Move/review | P1 | Potential shared UI component |
| `Modules/Admin/resources/views/components/editor.blade.php` | Move/review | P1 | Potential shared UI component |
| `Modules/Admin/resources/views/components/gallery.blade.php` | Move/review | P1 | Potential shared UI component |
| `Modules/Admin/resources/views/components/image-upload.blade.php` | Move/review | P1 | Potential shared UI component |
| `Modules/Admin/routes/web copy.php` | Delete after verification | P2 | Stale route file not loaded |
| `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier` | Delete after verification | P2 | Windows metadata artifact |
| `Modules/Admin/resources/views/livewire/admin/*` | Delete after verification | P2 | Duplicate view tree |
| `Modules/Admin/Http/Controllers/AdminController.php` | Clean | P2 | Empty scaffold resource methods |
| `Modules/Admin/Models/Admin.php` | Delete after verification | P2 | Scaffold model appears unused |
| `Modules/Admin/Livewire/Settings/Placeholder.php` | Delete after verification | P2 | Placeholder component |
| `Modules/Admin/resources/views/pages/settings/placeholder.blade.php` | Delete after verification | P2 | Placeholder page |
| `Modules/Admin/resources/views/components/menu-item.blade.php` | Review | P2 | Ensure UI permission checks are not treated as authorization |

## 7. Risk Control

Do not change these yet:

- Do not delete `Modules/Admin/routes/web copy.php` until route/link checks prove all routes are intentionally inactive.
- Do not move product, order, post, category, coupon, role, staff, affiliate, banner, flash sale, chat, settings, or user address code until canonical ownership is confirmed in a rebuild spec.
- Do not rename malformed migrations until production migration history is checked and a compatibility strategy is approved.
- Do not rewrite import/export behavior until sample files, unique keys, import modes, dry-run behavior, null-overwrite rules, and transaction strategy are confirmed.
- Do not expose or expand database administration screens while P0 controls are incomplete.
- Do not treat menu visibility or hidden buttons as authorization; server-side route and Livewire action checks must come first.
- Do not introduce DTOs; use validated arrays and service methods.
- Do not move shared-looking UI components out of Admin until actual cross-module usage is verified.
- Do not remove duplicate Livewire views until each component `render()` path and route/page mount has been verified.
