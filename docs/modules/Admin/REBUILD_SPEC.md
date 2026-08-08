# Admin Rebuild Specification

Generated: 2026-06-22

## 1. Purpose

This specification governs the safe rebuild of `Modules/Admin`.

Admin is a `shell` module. Its rebuilt responsibility is the admin presentation shell only:

- Admin dashboard entry point.
- Admin layout, sidebar, header, and shell navigation.
- Shell menu management.
- Admin profile page composition.
- Theme and header shell UI when ownership is confirmed.

Admin must not be the canonical owner of product, order, post, category, coupon, customer, role, staff, affiliate, banner, flash sale, chat, footer, homepage, Website settings, database administration, or environment/system operations unless a later ownership decision explicitly says so.

This spec is documentation only. It does not authorize implementation yet.

## 2. Source Evidence

Required project files reviewed:

- `docs/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `docs/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `ROADMAP.md`
- `Modules/ModuleServiceProvider.php`
- `composer.json`
- `.codex/agents/architect.md`

Admin documents reviewed:

- `docs/modules/Admin/ANALYSIS.md`
- `docs/modules/Admin/REFACTOR_PLAN.md`
- `docs/modules/Admin/REBUILD_SPEC.md`
- `docs/modules/Admin/INFORMATION.md`
- `docs/modules/Admin/OVERVIEW.md`
- `docs/modules/Admin/REBUILD_DECISION.md`

Admin source inspected:

- `Modules/Admin/config/module.php`
- `Modules/Admin/routes/web.php`
- `Modules/Admin/routes/api.php`
- `Modules/Admin/Http/Controllers`
- `Modules/Admin/Livewire`
- `Modules/Admin/resources/views`
- `Modules/Admin/resources/views/components`
- `Modules/Admin/Services`
- `Modules/Admin/Imports`
- `Modules/Admin/Exports`
- `Modules/Admin/Models`
- `Modules/Admin/database/migrations`
- `Modules/Admin/database/seeders`
- `Modules/Admin/data`

When older documentation conflicts with source code, source code wins.

## 3. Current Source Corrections

The rebuild must account for these current facts:

- `Modules/Admin/routes/web copy.php` is absent in the current source. Older cleanup tasks for this file are obsolete.
- `Modules/Admin/routes/api.php` still registers an unauthenticated `/api/admin` endpoint through the module provider's `/api` wrapper.
- `Modules/Admin/routes/web.php` uses `web` and `auth:admin`, but no named permission middleware.
- `Modules/Admin/Services/ProfileService.php` is stored under Admin but declares namespace `Modules\Website\Services\Account`; `UserProfile` imports `Modules\Admin\Services\ProfileService`.
- `Modules/Admin/Models/AffiliateLevel.php` is stored under Admin but declares namespace `Modules\Website\Models`.
- `Modules/Admin/Livewire/Database/BackupManager.php` calls `DatabaseService::getBackupFiles()` and `DatabaseService::restore()`, but `DatabaseService` currently exposes `getAllBackupFiles()`, `restoreTable()`, and `restoreFromFile()`.
- `Modules/Admin/Services/DatabaseService.php` still builds shell command strings containing DB credentials, accepts table/file inputs, exposes raw errors, and toggles foreign-key checks without guaranteed `finally` restoration.

These corrections are part of the rebuild scope.

## 4. Final Rebuild Decision

Decision: **Safe rebuild**

Risk level: **High**

Rationale:

- Keep the stable shell concept and shell UI structure.
- Rebuild unsafe security, database, menu, authorization, and ownership boundaries.
- Move or retire domain behavior from Admin after canonical ownership is confirmed.
- Do not rewrite the whole module from scratch because layout, route shape, thin shell controllers, partials, and some shell models can be preserved.

## 5. Target Architecture

Target flow:

```text
Route
Controller
Page Blade
Livewire PHP
Livewire Blade
Admin or Shared Blade Components
Service
Import or Export, if needed
Model
Migration
Database
```

Layer rules:

- Routes define URI, name, middleware, and controller action only.
- Controllers return views or pass scalar IDs only.
- Page Blade files extend the Admin layout and mount Livewire components only.
- Livewire owns UI state, validation entry points, authorization calls, service calls, and browser events.
- Services own queries, transactions, invariants, cache invalidation, import/export orchestration, and storage decisions.
- Models own ORM configuration, casts, scopes, and relationships only.
- Migrations own schema, indexes, constraints, and deterministic ordering.

Forbidden target behavior:

- Admin Livewire classes must not own domain persistence.
- Admin must not build shell commands from browser-provided values.
- Admin must not expose raw exception text to users.
- Admin must not hide UI controls as a substitute for server-side authorization.
- Admin must not load unbounded domain datasets for export/listing.

## 6. Routes

### Active Web Routes To Keep

Current active web routes:

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | `DashboardController@index` |
| GET | `/admin/menus` | `admin.menus.index` | `MenuController@index` |
| GET | `/admin/menus/create` | `admin.menus.create` | `MenuController@create` |
| GET | `/admin/menus/{id}/edit` | `admin.menus.edit` | `MenuController@edit` |
| GET | `/admin/profile` | `admin.profile` | `ProfileController@profile` |
| GET | `/admin/themes` | `admin.themes` | `AdminController@themes` |
| GET | `/admin/admin-header` | `admin.header` | `AdminController@adminHeader` |

Target middleware:

- `web`
- `auth:admin`
- named permission middleware per route or route group

Target permission candidates:

- `admin.dashboard.view`
- `admin.menu.view`
- `admin.menu.create`
- `admin.menu.update`
- `admin.profile.view`
- `admin.theme.view`
- `admin.header.view`

Needs verification:

- Exact permission names and seeder location must align with the Role module and existing Spatie guard conventions.

### API Route

Current route:

- `GET /api/admin` via `Modules/Admin/routes/api.php`

Target decision:

- Prefer remove unless a real API consumer is confirmed.
- If retained, require explicit authentication and a named permission such as `admin.api.view`.

Acceptance criteria:

- Anonymous requests are denied or route is absent.
- Authenticated users without permission are denied.
- The API route does not become a generic future admin API surface without a contract.

## 7. Controllers

Keep and harden as thin shell controllers:

- `DashboardController`
- `MenuController`
- `ProfileController`
- `AdminController::themes`
- `AdminController::adminHeader`

Controller rules:

- Return views only.
- Pass scalar IDs only.
- Do not query domain models.
- Do not perform business writes.
- Do not authorize only in the controller when Livewire methods also mutate data; mutating Livewire methods must authorize too.

Migrate, disable, or remove after ownership confirmation:

- `AffiliateController`
- `BannerController`
- `CategoryController`
- `ChatController`
- `CouponController`
- `CustomerController`
- `DatabaseController`
- `EnvConfigController`
- `FlashSaleController`
- `FooterController`
- `HeaderController`
- `HomeSettingsController`
- `OrderController`
- `PostController`
- `ProductCommissionController`
- `ProductController`
- `RoleController`
- `SettingController`
- `StaffController`

High-risk controller concern:

- `DatabaseController::download($filename)` must not remain available without named permission, opaque server-owned backup identifiers, path validation, safe 404/403 behavior, and audit logging.

## 8. Page Blade Design

Keep/refactor active shell pages:

- `Modules/Admin/resources/views/pages/dashboard.blade.php`
- `Modules/Admin/resources/views/pages/menus/index.blade.php`
- `Modules/Admin/resources/views/pages/menus/create.blade.php`
- `Modules/Admin/resources/views/pages/menus/edit.blade.php`
- `Modules/Admin/resources/views/pages/profiles/profile.blade.php`
- `Modules/Admin/resources/views/pages/admin/themes.blade.php`
- `Modules/Admin/resources/views/pages/admin/header/index.blade.php`

Page Blade rules:

- Extend `Admin::layouts.master`.
- Mount Livewire components.
- Do not query models.
- Do not perform authorization-sensitive branching as the only protection.

Do not restore or expand these Admin page groups until canonical ownership is confirmed:

- affiliate
- banner
- categories
- chat
- coupons
- customers
- database
- flash-sale
- footer
- header legacy page
- home
- orders
- posts
- products
- roles
- settings
- staff

## 9. Livewire Design

### Keep As Admin Shell, Refactor

- `Modules/Admin/Livewire/Menus/MenuTable.php`
- `Modules/Admin/Livewire/Menus/MenuForm.php`
- `Modules/Admin/Livewire/Profile/UserProfile.php`
- `Modules/Admin/Livewire/Profile/UserAddress.php`, only if address ownership remains Admin.
- `Modules/Admin/Livewire/ThemeSwitcher.php`
- `Modules/Admin/Livewire/Header/GeneralSettings.php`, only if header settings remain Admin-owned.
- `Modules/Admin/Livewire/Header/MenuManager.php`, only if header menus remain Admin-owned.
- `Modules/Admin/Livewire/Partials/Header.php`
- `Modules/Admin/Livewire/Partials/HeaderNotifications.php`
- `Modules/Admin/Livewire/Partials/HeaderSearch.php`
- `Modules/Admin/Livewire/Partials/HeaderUser.php`
- `Modules/Admin/Livewire/Partials/Sidebar.php`

### Disable, Migrate, Or Remove

These are not target Admin shell components unless ownership is explicitly confirmed:

- Affiliate components.
- Banner components.
- Category components.
- Chat components.
- Customer components.
- Dashboard domain metric components.
- Database components.
- FlashSale components.
- Footer components.
- Home settings components.
- Marketing/coupon components.
- Order components.
- Post components.
- Product components.
- Settings/env/config components.
- System role/staff components.

### Livewire Rules

Every mutating action must:

- Authorize before service execution.
- Validate browser input.
- Call a service.
- Avoid direct model writes.
- Avoid direct filesystem writes unless the service owns storage policy.
- Return safe user-facing errors.

Events may include:

- `notify`
- `menu-saved`
- `menu-deleted`
- `menu-reordered`
- `import-completed`
- `restore-completed`
- `permission-denied`

Events must not be trusted for authorization-sensitive IDs.

## 10. Menu Rebuild

Current menu implementation:

- Uses `Modules\Admin\Models\Category` with `type = menu`.
- `MenuTable` directly performs queries, transactions, filesystem reads/writes, JSON import/export, recursive persistence, bulk updates, deletes, and cache invalidation.
- `MenuForm` directly queries categories and permissions, generates slugs, silently nulls invalid parents, and persists records.

Target design:

- Create a shell-owned menu service, for example `Modules\Admin\Services\MenuService`.
- Keep Livewire focused on state, UI validation, authorization, and service calls.
- Keep query, tree building, transactions, slug generation, cache invalidation, and import/export in the service.

Target `MenuService` responsibilities:

- `paginate(array $filters, int|string $perPage)`
- `tree(array $filters = [])`
- `findForEdit(int $id)`
- `create(array $data)`
- `update(int $id, array $data)`
- `delete(int $id)`
- `bulkDelete(array $ids)`
- `bulkSetActive(array $ids, bool $active)`
- `bulkAssignPermission(array $ids, ?string $permissionName)`
- `reorder(array $tree)`
- `duplicate(int $id)`
- `validateImport(array $payload): array`
- `import(array $payload, array $options): array`
- `export(array $filters = []): array`
- `restoreDefault(array $options): array`

Menu data rules:

- `name` is required.
- `slug` is unique.
- Parent must exist and must not create a cycle.
- Permission name must follow the confirmed permission policy.
- Restore default menu must validate all rows before deleting or replacing existing data.
- Bulk operations require explicit permissions and UI confirmation.
- Import must support dry-run or validation-only mode before destructive changes.

Needs verification:

- Whether Admin shell menus remain in `categories` with `type = menu`.
- Whether a dedicated `admin_menu_items` table should replace the current `categories` usage.
- Whether permission names should be strict `exists:permissions,name` or soft strings for stale permission compatibility.

## 11. Header, Theme, Profile

### Header

Candidate Admin-owned models:

- `Modules\Admin\Models\HeaderMenu`
- `Modules\Admin\Models\HeaderMenuItem`

Candidate Admin-owned tables:

- `header_menus`
- `header_menu_items`

Target rules:

- `HeaderMenuService` owns header menu queries, item creation, updates, deletes, reorder, and cache invalidation.
- `Header/MenuManager` should not directly create/query models except through the service after rebuild.
- `Header/GeneralSettings` must use a confirmed settings owner.

Needs verification:

- Whether header settings are Admin shell settings, Website settings, or Shared/System settings.

### Theme

Keep concept:

- `Modules\Admin\Support\ThemeManager`
- `Modules\Admin\Livewire\ThemeSwitcher`

Target rules:

- Theme changes require a named permission.
- Theme persistence must be server-controlled.
- Theme values must be allowlisted.

### Profile

Current issue:

- `Modules/Admin/Services/ProfileService.php` declares namespace `Modules\Website\Services\Account`.
- `UserProfile` imports `Modules\Admin\Services\ProfileService`.

Target rules:

- Fix by ownership decision, not a casual namespace patch.
- If Admin owns admin profile behavior, service namespace must be `Modules\Admin\Services`.
- If Account/User owns profile behavior, Livewire should depend on that canonical service.
- Avatar upload must validate image type/size and use Laravel Storage.
- Password update must return safe validation errors only.

### User Address

Current issue:

- `AddressService` performs multi-write default-address changes without explicit transactions.

Target rules:

- Keep only if Admin is confirmed as address owner.
- Otherwise move to Account/User canonical owner.
- Default-address create/update/delete/set-default must be transactional.

## 12. Database And System Operations

Current source risk:

- `DatabaseService::backupTable()`, `backupFullDatabase()`, and `restoreTable()` use shell command strings and command-line DB passwords.
- `truncateTable()`, `dropTable()`, and `restoreFromFile()` toggle foreign-key checks without guaranteed `finally` restoration.
- `restoreFromFile()` can drop all tables from a selected file path.
- `TableList` exposes export, backup, restore, truncate, drop, and full restore actions.
- `BackupManager` calls service methods that do not exist.

Target decision:

- Database administration should be disabled in Admin until P0 controls exist.
- Prefer moving production-control database administration to `Modules/System`.
- If retained temporarily in Admin, it must be treated as a hardened infrastructure surface, not normal shell UI.

Required controls if retained:

- Named permissions:
  - `database.view`
  - `database.backup`
  - `database.download`
  - `database.restore`
  - `database.destroy`
- Server-owned table identifiers.
- Server-owned opaque backup identifiers.
- No browser-provided paths.
- No browser-provided table names without schema allowlist resolution.
- No shell command strings.
- No command-line DB passwords.
- Process argument arrays or controlled process input/output.
- `try/finally` restoration for foreign-key toggles.
- Explicit destructive confirmation.
- Audit logs for allowed and denied destructive actions.
- Redacted logs and safe user-facing messages.
- Security regression tests before exposure.

Rewrite from scratch:

- Current backup/restore/truncate/drop implementation.
- Full database restore implementation.

## 13. Services

Keep/refactor as Admin shell services:

- `SidebarService`
- `HeaderMenuService`
- `SettingsService`, only if settings ownership is confirmed.
- `AddressService`, only if address ownership is confirmed.
- `ProfileService`, only after namespace/ownership decision.
- `MenuService`, to be created during implementation.

Move or migrate out of Admin unless ownership is confirmed:

- `AdminAffiliateService`
- `AffiliateRankService`
- `AuthService`
- `BannerService`
- `ChatService`
- `DatabaseService`
- `FlashSaleService`
- `HomeSettingService`
- `Services/Env/*`
- `Services/Database/DbConnectionService`

Service rules:

- Use constructor injection where practical.
- Document return shapes.
- Keep multi-record writes transactional.
- Do not return raw exception text to users.
- Do not hide validation errors inside generic exceptions.

## 14. Models And Tables

### Keep Or Rebuild As Admin-Owned

Confirmed/candidate shell models:

- `HeaderMenu`
- `HeaderMenuItem`
- `Setting`, only if Admin owns shell settings.
- `Category`, only if Admin shell menu remains in `categories`.

Candidate shell tables:

- `header_menus`
- `header_menu_items`
- `settings`, needs ownership confirmation.
- `categories` rows with `type = menu`, needs ownership confirmation.
- `admin_menu_items`, only if approved as replacement.

### Move Or Retire From Admin

Move/retire unless ownership is confirmed:

- `Admin`
- `AffiliateLevel`
- `AffiliateScheme`
- `Banner`
- `ChatMessage`
- `ChatSession`
- `FlashSale`
- `FlashSaleItem`
- `UserAddress`

Current namespace mismatch:

- `AffiliateLevel.php` is under `Modules/Admin/Models` but declares `Modules\Website\Models`.

### Migration Notes

Current Admin migrations:

- `-0001_11_30_000024_create_settings_table.php`
- `-0001_11_30_000034_create_header_menus_table.php`
- `-0001_11_30_000035_create_header_menu_items_table.php`

Rules:

- Do not rename migration files blindly.
- Check production migration history before any filename change.
- If old filenames have run in production, use a compatibility strategy.
- Fresh-install ordering must be deterministic after migration repair.

## 15. Import Design

Admin-owned import scope:

- Only shell menu/header/settings imports if ownership is confirmed.

Not Admin-owned:

- Product import.
- Post import.
- Coupon import.
- Role import.
- Staff import.
- Domain data imports.

Target menu import behavior:

- Prefer service-owned JSON restore/import if keeping the current JSON format.
- Use shared import/export foundation if spreadsheet import is approved.
- Validate entire payload before changing data.
- Support dry-run.
- Detect duplicate keys.
- Reject cycles and invalid parents.
- Report row/item-level errors.
- Never truncate/replace before successful validation.

Needs verification:

- Menu import format: JSON, spreadsheet, or both.
- Sample files.
- Unique key: slug, path, name+parent, or another confirmed key.
- Null overwrite policy.
- Duplicate handling policy.

## 16. Export Design

Admin-owned export scope:

- Only shell menu/header/settings exports if ownership is confirmed.

Not Admin-owned:

- Product export must move to the canonical product owner.
- Domain exports must move to canonical modules.

Target behavior:

- Export through services.
- Use private storage for generated files unless public output is explicitly required.
- Keep export row counts bounded or chunked.
- Avoid unbounded `get()` on domain data.
- Do not leak internal permission/menu data without permission.

Current rewrite target:

- `Modules/Admin/Exports/ProductsExport.php` should not remain as Admin-owned export logic.

## 17. Authorization

Required pattern:

- Route middleware for page access.
- Livewire action authorization for all mutating actions.
- Service invariant checks for sensitive operations.
- `Gate::before` Super Admin behavior remains project-level.

Mutating actions requiring checks:

- menu save
- menu delete
- menu bulk delete
- menu bulk status update
- menu bulk permission assignment
- menu import
- menu export
- menu restore default
- menu reorder
- menu duplicate
- theme update
- header update
- profile update
- password update
- address create/update/delete/set-default if retained
- database actions if retained
- env/system/module configuration actions if retained

UI menu filtering may remain as convenience only. It must never be treated as authorization.

## 18. Transactions And Integrity

Transactions required for:

- Menu create/update when related writes are introduced.
- Menu delete with descendants.
- Bulk menu delete.
- Bulk status update.
- Bulk permission assignment.
- Menu reorder.
- Menu duplicate.
- Menu import.
- Menu restore default.
- Header menu reorder.
- Address default changes if retained.
- Any retained destructive database action.

Rollback conditions:

- Failed validation.
- Duplicate key conflict.
- Parent cycle detection.
- Invalid permission reference when strict mode is enabled.
- File parse failure.
- Failed row in all-or-nothing import.
- Unauthorized action.
- Failed process execution.
- Storage failure.

## 19. Performance

Target rules:

- Use server-side pagination for lists.
- Avoid `paginate(999999)`.
- Avoid unbounded `get()` for exports.
- Load menu trees with bounded eager loading or a service-built tree from bounded queries.
- Cache only stable menu/header trees and invalidate after successful writes.
- Queue or chunk large domain imports/exports in canonical modules.

Known current risks:

- `MenuTable` loads root menu trees with `get()`.
- Recursive menu children can N+1 at deeper levels.
- `ProductsExport` loads all products.
- `ProductTable` supports `paginate(999999)`.
- Several domain import/export components build whole arrays in memory.

## 20. Shared UI Components

Admin-only candidates:

- `menu-item.blade.php`
- `icon.blade.php`, if only used by Admin shell.
- `toast.blade.php`, if layout-specific.

Move candidates after usage audit:

- `category-select.blade.php`
- `currency-input.blade.php`
- `editor.blade.php`
- `gallery.blade.php`
- `image-upload.blade.php`

Rules:

- Move to `Modules/Shared` only when at least two modules need the component and the contract is stable.
- Do not make other modules depend on Admin only for generic UI primitives.

## 21. Test Strategy

Minimum P0 tests before exposure:

- Anonymous `/api/admin` denied or route absent.
- Authenticated user without permission denied for Admin shell routes.
- Authorized admin can access shell routes.
- Mutating menu actions deny unauthorized users.
- Database export/truncate/drop/restore/download deny unauthorized users.
- Invalid table identifiers and backup identifiers are rejected.
- Raw process output and credentials are not returned to users.

P1 tests:

- Menu service tree building.
- Menu create/update validation.
- Slug uniqueness.
- Parent cycle rejection.
- Bulk operation transactions.
- Import dry-run and all-or-nothing rollback.
- Restore default validates before replacing.
- Cache invalidation after successful writes.
- Address default transactions if retained.
- Migration smoke tests after migration strategy is approved.

Performance tests:

- Menu tree query-count budget.
- No unbounded product export in Admin.
- Guarded `All` pagination behavior.

## 22. Implementation Phases

### Phase 0: Containment

1. Remove or protect `Modules/Admin/routes/api.php`.
2. Disable database administration actions in Admin until P0 controls exist.
3. Add named permissions to active Admin routes.
4. Add Livewire action authorization to active mutating shell actions.
5. Stop raw exception output from active shell/system flows.
6. Add first security regression tests.

### Phase 1: Admin Shell Rebuild

1. Confirm canonical ownership map.
2. Create service-backed Admin menu workflow.
3. Move menu query/persistence/import/export/restore logic out of Livewire.
4. Harden header/theme/profile shell components.
5. Resolve `ProfileService` namespace/ownership.
6. Resolve `Category` and `Setting` ownership.

### Phase 2: Domain Migration

1. Move product/post/order/category/coupon/customer/role/staff workflows to canonical modules.
2. Move product import/export to canonical product owner.
3. Move affiliate/banner/flash-sale/chat/footer/home/settings workflows to confirmed owners.
4. Remove Admin domain components only after route, menu, and caller verification.

### Phase 3: Migration And Performance

1. Prepare migration compatibility plan for negative-year migration filenames.
2. Add migration smoke tests.
3. Bound menu and header tree queries.
4. Remove unbounded Admin export/list patterns.

### Phase 4: Cleanup

1. Remove `Zone.Identifier` artifact after reference verification.
2. Remove placeholder/scaffold files after reference verification.
3. Prune duplicate Livewire views after render-path verification.
4. Move confirmed shared UI components to `Modules/Shared`.

## 23. Implementation Checklist

### P0

- [ ] Decide remove vs protect for `Modules/Admin/routes/api.php`.
- [ ] Disable or harden database actions before production exposure.
- [ ] Replace shell command strings and command-line secrets if database operations remain.
- [ ] Replace browser table/file inputs with server-owned identifiers if database operations remain.
- [ ] Add `try/finally` around FK toggles if destructive DB operations remain.
- [ ] Redact user-facing errors.
- [ ] Add named permissions to active Admin routes.
- [ ] Add authorization to active mutating Livewire methods.
- [ ] Add P0 security tests.

### P1

- [ ] Confirm canonical owner for Admin shell menus.
- [ ] Confirm canonical owner for settings.
- [ ] Confirm canonical owner for user addresses.
- [ ] Confirm canonical owner for database administration.
- [ ] Confirm canonical owner for product import/export.
- [ ] Resolve `ProfileService` namespace/ownership mismatch.
- [ ] Resolve `AffiliateLevel` namespace/ownership mismatch.
- [ ] Resolve `BackupManager` and `DatabaseService` method-contract mismatch.
- [ ] Create service-backed menu workflow.
- [ ] Make menu import/restore validated and all-or-nothing.
- [ ] Prepare migration compatibility plan.
- [ ] Audit reusable UI components.

### P2

- [ ] Remove `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier` after verification.
- [ ] Remove placeholder/scaffold files after verification.
- [ ] Prune duplicate `resources/views/livewire/admin/*` views after verification.
- [ ] Document menu visibility as UI-only.
- [ ] Add architecture checks to prevent new Admin domain ownership.

## 24. Open Decisions

Needs verification before coding:

- Should `/api/admin` be removed or protected?
- Should database administration move to `Modules/System`?
- Should Admin shell menus remain in `categories` or move to a dedicated table?
- Who owns `settings`?
- Who owns `user_addresses`?
- Who owns banners, flash sales, affiliate schemes, chat, footer, and home settings?
- Exact permission names and seed location.
- Production migration history for negative-year migrations.
- Menu import/export format and sample files.
- Test directory conventions for module/security tests.

