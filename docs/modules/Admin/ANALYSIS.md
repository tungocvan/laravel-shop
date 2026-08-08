# Module Admin Analysis

## 1. Module Purpose

`Modules/Admin` is declared as a `shell` module in `Modules/Admin/config/module.php`. Its legitimate purpose is the admin presentation shell: admin layout, navigation, dashboard entry point, profile screens, theme/header/menu shell UI, and small shell-owned settings.

Current implementation goes beyond a shell. It contains duplicated or cross-domain business code for products, categories, orders, posts, coupons, customers, roles, staff, database administration, chat, banners, affiliate commissions, flash sales, home/header/footer settings, import/export, and user addresses.

Needs verification: which of these screens are intentionally still owned by Admin versus legacy code waiting to be migrated. Based on `docs/CODEX_BOOTSTRAP.md`, Admin should not own domain behavior.

## 2. Current Architecture Flow

Active web flow:

```text
Modules/Admin/routes/web.php
→ DashboardController/MenuController/ProfileController/AdminController
→ Modules/Admin/resources/views/pages/*
→ Modules/Admin/Livewire/*
→ Modules/Admin/resources/views/livewire/*
→ Services or direct Model access
→ Models under Modules/Admin, Modules/Website, App\Models, Spatie Permission
→ Database
```

Active API flow:

```text
Modules/Admin/routes/api.php
→ Modules\Admin\Http\Controllers\Api\AdminController@index
→ JSON response
```

Main architecture deviations:

- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` performs queries, transactions, file reads/writes, JSON import/export, recursive persistence, and deletes directly instead of using a service.
- P1: `Modules/Admin/Livewire/Menus/MenuForm.php` performs direct model queries/writes and slug generation instead of using a service.
- P1: Many inactive-or-legacy Livewire classes under `Modules/Admin/Livewire/*` directly use `Modules\Website` models/services and `App\Models\User`, creating shell-to-domain coupling.
- P0: `Modules/Admin/Services/DatabaseService.php` contains backup, restore, truncate, drop, and full database restore capabilities with insufficient authorization and unsafe process construction.
- P1: `Modules/Admin/routes/web copy.php` contains many old routes but is not loaded by the module bootstrap because only `routes/web.php` and `routes/api.php` are registered.

## 3. Route List

Active routes in `Modules/Admin/routes/web.php`:

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | `DashboardController@index` |
| GET | `/admin/menus` | `admin.menus.index` | `MenuController@index` |
| GET | `/admin/menus/create` | `admin.menus.create` | `MenuController@create` |
| GET | `/admin/menus/{id}/edit` | `admin.menus.edit` | `MenuController@edit` |
| GET | `/admin/profile` | `admin.profile` | `ProfileController@profile` |
| GET | `/admin/themes` | `admin.themes` | `AdminController@themes` |
| GET | `/admin/admin-header` | `admin.header` | `AdminController@adminHeader` |

Active API route in `Modules/Admin/routes/api.php`:

| Method | URI after module provider API wrapper | Controller |
|---|---|---|
| GET | `/api/admin` | `Api\AdminController@index` |

Not loaded:

- `Modules/Admin/routes/web copy.php` contains routes for products, orders, settings, posts, customers, database, roles, staff, and other screens, but `docs/PROJECT_BOOTSTRAP.md` says files with names like `web copy.php` are not loaded.

Issues:

- P0: `Modules/Admin/routes/api.php` exposes `/api/admin` without auth middleware. Add explicit authentication/authorization or remove the route if unused.
- P1: `Modules/Admin/routes/web.php` relies only on `auth:admin`; menu/profile/theme/header actions lack named permissions.
- P2: `Modules/Admin/routes/web copy.php` is stale route clutter and should be removed after verification.

## 4. Controllers

Active controllers:

- `Modules/Admin/Http/Controllers/DashboardController.php`: `index()` returns `Admin::pages.dashboard`.
- `Modules/Admin/Http/Controllers/MenuController.php`: `index()`, `create()`, `edit($id)` return menu page blades.
- `Modules/Admin/Http/Controllers/ProfileController.php`: `profile()` returns profile page.
- `Modules/Admin/Http/Controllers/AdminController.php`: `index()`, `adminHeader()`, `themes()` return shell views; resource methods are empty.
- `Modules/Admin/Http/Controllers/Api/AdminController.php`: `index()` returns a JSON status.

Other controllers present but not routed by active `web.php`:

- `AffiliateController`, `AuthController`, `Auth/GoogleController`, `BannerController`, `CategoryController`, `ChatController`, `CouponController`, `CustomerController`, `DatabaseController`, `EnvConfigController`, `FlashSaleController`, `FooterController`, `HeaderController`, `HomeSettingsController`, `OrderController`, `PostController`, `ProductCommissionController`, `ProductController`, `RoleController`, `SettingController`, `StaffController`.

Issues:

- P0: `Modules/Admin/Http/Controllers/DatabaseController.php` has `download($filename)` with authorization commented out and returns downloads from a filename supplied by the route.
- P1: `Modules/Admin/Http/Controllers/ProductCommissionController.php` queries `Modules\Website\Models\WpProduct` directly inside the controller.
- P2: `Modules/Admin/Http/Controllers/AdminController.php` imports `Illuminate\Http\Request` and contains empty resource methods, which look scaffolded and unused.

## 5. Page Blade Files

Active through `routes/web.php`:

- `Modules/Admin/resources/views/pages/dashboard.blade.php`
- `Modules/Admin/resources/views/pages/menus/index.blade.php`
- `Modules/Admin/resources/views/pages/menus/create.blade.php`
- `Modules/Admin/resources/views/pages/menus/edit.blade.php`
- `Modules/Admin/resources/views/pages/profiles/profile.blade.php`
- `Modules/Admin/resources/views/pages/admin/themes.blade.php`
- `Modules/Admin/resources/views/pages/admin/header/index.blade.php`

Other page blades present:

- `Modules/Admin/resources/views/pages/affiliate/*`
- `Modules/Admin/resources/views/pages/banner/index.blade.php`
- `Modules/Admin/resources/views/pages/categories/*`
- `Modules/Admin/resources/views/pages/chat/index.blade.php`
- `Modules/Admin/resources/views/pages/coupons/*`
- `Modules/Admin/resources/views/pages/customers/*`
- `Modules/Admin/resources/views/pages/database.blade.php`
- `Modules/Admin/resources/views/pages/flash-sale/index.blade.php`
- `Modules/Admin/resources/views/pages/footer/index.blade.php`
- `Modules/Admin/resources/views/pages/header/index.blade.php`
- `Modules/Admin/resources/views/pages/home/index.blade.php`
- `Modules/Admin/resources/views/pages/orders/*`
- `Modules/Admin/resources/views/pages/posts/*`
- `Modules/Admin/resources/views/pages/products/*`
- `Modules/Admin/resources/views/pages/roles/*`
- `Modules/Admin/resources/views/pages/settings/*`
- `Modules/Admin/resources/views/pages/staff/*`

Issues:

- P1: Many page blades mount Livewire components for domain modules from inside Admin, for example `Modules/Admin/resources/views/pages/products/index.blade.php`, `Modules/Admin/resources/views/pages/orders/index.blade.php`, and `Modules/Admin/resources/views/pages/posts/index.blade.php`.
- P2: Several page blades appear unreachable from active routes and need route/link verification before removal.

## 6. Livewire PHP Classes

Active through current routes:

- `Modules/Admin/Livewire/Menus/MenuTable.php`
- `Modules/Admin/Livewire/Menus/MenuForm.php`
- `Modules/Admin/Livewire/Profile/UserProfile.php`
- `Modules/Admin/Livewire/Profile/UserAddress.php`
- `Modules/Admin/Livewire/ThemeSwitcher.php`
- `Modules/Admin/Livewire/Header/GeneralSettings.php`
- `Modules/Admin/Livewire/Header/MenuManager.php`
- `Modules/Admin/Livewire/Partials/Header.php`
- `Modules/Admin/Livewire/Partials/HeaderNotifications.php`
- `Modules/Admin/Livewire/Partials/HeaderSearch.php`
- `Modules/Admin/Livewire/Partials/HeaderUser.php`
- `Modules/Admin/Livewire/Partials/Sidebar.php`

Other Livewire classes present:

- Affiliate: `CommissionList`, `CommissionMatrix`
- Auth: `LoginForm`
- Banner: `BannerManager`
- Categories: `CategoryForm`, `CategoryTable`
- Chat: `ChatManager`
- Customers: `CustomerCreate`, `CustomerDetail`, `CustomerTable`
- Dashboard: `RecentOrders`, `RevenueChart`, `StatsOverview`
- Database: `BackupManager`, `ImportDrawer`, `TableList`
- FlashSale: `FlashSaleManager`
- Footer: `FooterColumns`, `FooterInfo`, `SocialLinks`
- Home: `HomeSettings`
- Marketing: `CouponForm`, `CouponTable`
- Orders: `OrderDetail`, `OrderDetailModal`, `OrderTable`
- Posts: `PostForm`, `PostTable`
- Products: `ProductForm`, `ProductTable`
- Settings: `AdvancedConfig`, `DatabaseConfig`, `EnvManager`, `MailConfig`, `ModulesForm`, `MomoConfig`, `Placeholder`, `SettingForm`, `SocialConfig`, `StorageConfig`
- System: `RoleForm`, `RoleTable`, `StaffForm`, `StaffTable`

Issues:

- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` mixes UI, query, import/export, transaction, filesystem, and persistence responsibilities.
- P1: `Modules/Admin/Livewire/Menus/MenuForm.php` queries `Category`, `Permission`, validates parent existence, generates slugs, and persists menus directly.
- P1: `Modules/Admin/Livewire/Products/ProductTable.php`, `Posts/PostTable.php`, `Marketing/CouponTable.php`, and `System/RoleTable.php` implement import/export or destructive mutations inside Livewire.
- P0: `Modules/Admin/Livewire/Database/TableList.php` exposes `exportTable`, `truncateTable`, and `dropTable` actions that delegate to dangerous database service methods.

## 7. Livewire Blade Views

Active through current routes:

- `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php`
- `Modules/Admin/resources/views/livewire/menus/menu-form.blade.php`
- `Modules/Admin/resources/views/livewire/profile/user-profile.blade.php`
- `Modules/Admin/resources/views/livewire/profile/user-address.blade.php`
- `Modules/Admin/resources/views/livewire/theme-switcher.blade.php`
- `Modules/Admin/resources/views/livewire/header/general-settings.blade.php`
- `Modules/Admin/resources/views/livewire/header/menu-manager.blade.php`
- `Modules/Admin/resources/views/livewire/partials/header.blade.php`
- `Modules/Admin/resources/views/livewire/partials/header-notifications.blade.php`
- `Modules/Admin/resources/views/livewire/partials/header-search.blade.php`
- `Modules/Admin/resources/views/livewire/partials/header-user.blade.php`
- `Modules/Admin/resources/views/livewire/partials/sidebar.blade.php`

Duplicate namespace-style views also exist under:

- `Modules/Admin/resources/views/livewire/admin/affiliate/*`
- `Modules/Admin/resources/views/livewire/admin/banner/*`
- `Modules/Admin/resources/views/livewire/admin/flash-sale/*`
- `Modules/Admin/resources/views/livewire/admin/footer/*`
- `Modules/Admin/resources/views/livewire/admin/header/*`
- `Modules/Admin/resources/views/livewire/admin/home/*`

Issues:

- P2: Duplicate view trees under `resources/views/livewire/admin/*` and `resources/views/livewire/*` need verification; they look like parallel versions for the same components.
- P2: `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier` is a Windows metadata artifact and should be removed after verification.

## 8. Shared Components Used

Admin shell components:

- `Modules/Admin/resources/views/layouts/master.blade.php`
- `Modules/Admin/resources/views/components/toast.blade.php`
- `Modules/Admin/resources/views/components/menu-item.blade.php`
- `Modules/Admin/resources/views/components/icon.blade.php`
- `Modules/Admin/resources/views/components/category-select.blade.php`
- `Modules/Admin/resources/views/components/currency-input.blade.php`
- `Modules/Admin/resources/views/components/editor.blade.php`
- `Modules/Admin/resources/views/components/gallery.blade.php`
- `Modules/Admin/resources/views/components/image-upload.blade.php`

Layout mounts:

- `livewire:admin.partials.sidebar`
- `livewire:admin.partials.header`
- `x-toast`

Issues:

- P1: The Admin shell defines reusable components such as category select, currency input, editor, gallery, and image upload that may be domain/shared UI concerns. Needs verification before moving to `Modules/Shared`.
- P2: `Modules/Admin/resources/views/components/menu-item.blade.php` recursively renders menu items and uses permissions from menu data; confirm it does not hide links as a substitute for server-side authorization.

## 9. Services and Public Methods

Admin services:

- `Modules/Admin/Services/AddressService.php`: `getUserAddresses`, `create`, `update`, `delete`, `setDefault`
- `Modules/Admin/Services/AdminAffiliateService.php`: `getCommissions`, `reject`, `getOrderDetail`, `approve`
- `Modules/Admin/Services/AffiliateRankService.php`: `checkAndUpdateRank`
- `Modules/Admin/Services/AuthService.php`: `handleGoogleUser`
- `Modules/Admin/Services/BannerService.php`: `getAll`, `save`, `delete`
- `Modules/Admin/Services/ChatService.php`: `sendMessage`, `getOrCreateSession`, `deleteMessage`, `deleteAllMessages`
- `Modules/Admin/Services/Database/DbConnectionService.php`: `testConnection`
- `Modules/Admin/Services/DatabaseService.php`: `getAllTables`, `backupTable`, `backupFullDatabase`, `restoreTable`, `truncateTable`, `dropTable`, `getDownloadPath`, `getAllBackupFiles`, `restoreFromFile`
- `Modules/Admin/Services/Env/EnvBackupService.php`: `createBackup`
- `Modules/Admin/Services/Env/EnvManagerService.php`: `exportToEnvironment`, `getValues`, `update`
- `Modules/Admin/Services/Env/MailConfigService.php`: `testSendMail`
- `Modules/Admin/Services/Env/SocialConfigService.php`: `validateCredentials`
- `Modules/Admin/Services/Env/SystemConfigService.php`: `pingNodeJS`, `dispatchTestJob`, `checkQueueStatus`
- `Modules/Admin/Services/FlashSaleService.php`: `getAll`, `createFlashSale`, `updateFlashSale`, `delete`
- `Modules/Admin/Services/HeaderMenuService.php`: `getMenuTreeByLocation`, `createItem`, `updateItem`, `deleteItem`, `reorderItems`
- `Modules/Admin/Services/HomeSettingService.php`: `getHomeSettings`, `updateHomeSettings`
- `Modules/Admin/Services/ProfileService.php`: `updateInfo`, `updatePassword`
- `Modules/Admin/Services/SettingsService.php`: `get`, `set`, `updateMany`
- `Modules/Admin/Services/SidebarService.php`: `getMenus`, `clearCache`

Issues:

- P0: `Modules/Admin/Services/DatabaseService.php` builds shell command strings with DB credentials and table names in `backupTable`, `backupFullDatabase`, and `restoreTable`.
- P0: `Modules/Admin/Services/DatabaseService.php` disables foreign key checks and drops/truncates tables without `finally` restoration in `truncateTable`, `dropTable`, and `restoreFromFile`.
- P1: `Modules/Admin/Services/AdminAffiliateService.php`, `AffiliateRankService.php`, and several UI services depend on Website/App domain models, violating Admin shell ownership.
- P1: `Modules/Admin/Livewire/Menus/*` bypasses services entirely for menu business behavior.

## 10. Models and Database Tables

Models under `Modules/Admin/Models`:

- `Admin.php`: scaffold model, no active table/fillable.
- `AffiliateLevel.php`: no explicit table; fillable `name`, `slug`, `min_revenue_required`, `is_default`; relates to `App\Models\User` and `AffiliateScheme`.
- `AffiliateScheme.php`: table `wp_affiliate_schemes`; relates to Website `WpProduct`, Admin `AffiliateLevel`, and `App\Models\User`.
- `Banner.php`: table `wp_banners`.
- `Category.php`: no explicit table, uses default `categories`; menu scopes and self-referential parent/children.
- `ChatMessage.php`: default table inferred, relates to `ChatSession`.
- `ChatSession.php`: default table inferred, relates to `ChatMessage` and `App\Models\User`.
- `FlashSale.php`: table `wp_flash_sales`, has many `FlashSaleItem`.
- `FlashSaleItem.php`: table `wp_flash_sale_items`, relates to Website `WpProduct`.
- `HeaderMenu.php`: default table `header_menus`, has many `HeaderMenuItem`.
- `HeaderMenuItem.php`: default table `header_menu_items`, self-referential tree.
- `Setting.php`: table `settings`.
- `UserAddress.php`: table `user_addresses`, relates to `App\Models\User`.

Admin migrations:

- `Modules/Admin/database/migrations/-0001_11_30_000024_create_settings_table.php`: creates `settings`.
- `Modules/Admin/database/migrations/-0001_11_30_000034_create_header_menus_table.php`: creates `header_menus`.
- `Modules/Admin/database/migrations/-0001_11_30_000035_create_header_menu_items_table.php`: creates `header_menu_items`.

Issues:

- P1: Migration filenames under `Modules/Admin/database/migrations` use malformed negative-year timestamps, risking migration ordering and fresh-install behavior.
- P1: `Modules/Admin/Models/Category.php` owns default `categories` table behavior inside Admin, while Category/Website domain modules also appear to own categories. Needs verification of canonical owner.
- P1: `Modules/Admin/Models/Setting.php` duplicates settings concepts also referenced by `Modules\Website\Models\Setting` in `Modules/Admin/Livewire/Settings/SettingForm.php`.
- P1: Admin models for `wp_banners`, `wp_flash_sales`, `wp_affiliate_schemes`, and `user_addresses` look like domain data, not shell data.

## 11. Import/Export Classes

Import/export classes:

- `Modules/Admin/Imports/ProductsImport.php`
- `Modules/Admin/Exports/ProductsExport.php`

Livewire import/export implementations also exist in:

- `Modules/Admin/Livewire/Menus/MenuTable.php`
- `Modules/Admin/Livewire/Products/ProductTable.php`
- `Modules/Admin/Livewire/Posts/PostTable.php`
- `Modules/Admin/Livewire/Marketing/CouponTable.php`
- `Modules/Admin/Livewire/System/RoleTable.php`

Issues:

- P1: `Modules/Admin/Imports/ProductsImport.php` and `Modules/Admin/Exports/ProductsExport.php` use `Maatwebsite\Excel` patterns instead of the project standard `rap2hpoutre/fast-excel` and `Modules/Shared/Services/ImportExport`.
- P1: `Modules/Admin/Imports/ProductsImport.php` persists `Modules\Website\Models\WpProduct` directly from an import class and syncs categories without a module service, dry-run, confirmed unique key, null-overwrite policy, or transaction strategy.
- P1: `Modules/Admin/Exports/ProductsExport.php` calls `get()` for all products when no IDs are supplied, which is unsafe for large datasets.
- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` implements custom JSON import/export directly in Livewire instead of a module `ImportExport` service.

## 12. Authorization and Security Risks

- P0: `Modules/Admin/routes/api.php` exposes an unauthenticated admin API status endpoint.
- P0: `Modules/Admin/Http/Controllers/DatabaseController.php` has the authorization check for database downloads commented out.
- P0: `Modules/Admin/Livewire/Database/TableList.php` exposes table export, truncate, and drop actions through Livewire. Needs verification whether it is reachable only through stale routes; the class itself remains registrable by Livewire.
- P0: `Modules/Admin/Services/DatabaseService.php` accepts table names and file paths into destructive database operations. It has a protected-table list, but no schema allowlist/opaque server-issued identifiers.
- P0: `Modules/Admin/Services/DatabaseService.php` exposes DB credentials in shell command strings and process command arguments.
- P0: `Modules/Admin/Services/DatabaseService.php` returns raw exception messages to callers, which can leak command output, paths, and credentials.
- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` and `MenuForm.php` perform mutating menu actions with no named permission checks.
- P1: `Modules/Admin/resources/views/components/menu-item.blade.php` may hide UI items based on permission data; UI hiding must not replace server-side checks. Needs verification.

## 13. Validation Problems

- P1: `Modules/Admin/Livewire/Menus/MenuForm.php` intentionally avoids `exists` validation for `can` and `parent_id`, then silently nulls invalid parent IDs.
- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` validates import as a generic JSON file but does not validate required row structure before recursive persistence.
- P1: `Modules/Admin/Imports/ProductsImport.php` does not validate required headers, money values, booleans, category IDs, JSON columns, or duplicate products.
- P1: `Modules/Admin/Livewire/Marketing/CouponTable.php`, `Posts/PostTable.php`, and `System/RoleTable.php` expose custom imports with limited validation and no shared report format.
- P2: `Modules/Admin/Livewire/Profile/UserProfile.php` does not validate email changes because email appears read-only in state; Needs verification in blade.

## 14. Transaction Risks

- P0: `Modules/Admin/Services/DatabaseService.php` disables foreign key checks in destructive paths without guaranteed `finally` restoration.
- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` deletes all menu records in `restoreDefaultMenu()` before import processing; failure can leave no menu data.
- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` performs bulk deletes and bulk status updates outside service-owned transactions.
- P1: `Modules/Admin/Imports/ProductsImport.php` creates products row-by-row and syncs categories without an all-or-nothing strategy.
- P1: `Modules/Admin/Services/AddressService.php` performs multi-step default-address updates without explicit transaction.

## 15. N+1 / Query Performance Risks

- P1: `Modules/Admin/Livewire/Menus/MenuTable.php` recursively walks `children`; only first-level children are eager loaded, so deeper menu trees can trigger N+1 queries.
- P1: `Modules/Admin/Livewire/Menus/MenuForm.php` builds a tree from all menu records in memory; acceptable for small menus but should remain bounded.
- P1: `Modules/Admin/Exports/ProductsExport.php` exports all products with `get()` when no IDs are selected.
- P1: `Modules/Admin/Livewire/Products/ProductTable.php` uses `paginate(999999)` for the `All` option.
- P1: `Modules/Admin/Services/DatabaseService.php` scans backup directories and runs `SHOW TABLE STATUS`; acceptable for admin tools but must be authorization-gated and potentially paginated for large schemas.

## 16. Duplicate Logic

- P1: Product management is duplicated in Admin (`Modules/Admin/Livewire/Products/*`, `Imports/ProductsImport.php`, `Exports/ProductsExport.php`) while using Website product models.
- P1: Post/category/coupon/order/customer management exists in Admin Livewire while using Website/App models.
- P1: Role/staff management exists in `Modules/Admin/Livewire/System/*` while `Modules/Role` is the support module likely intended to own role behavior.
- P1: Settings exist as both `Modules/Admin/Models/Setting.php` and `Modules\Website\Models\Setting` usage from Admin components.
- P2: Header/footer/home livewire views have both `resources/views/livewire/admin/*` and `resources/views/livewire/*` variants.

## 17. Files That Look Unused

Needs verification before deletion:

- `Modules/Admin/routes/web copy.php`: not loaded by module bootstrap.
- `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier`: Windows metadata artifact.
- `Modules/Admin/Models/Admin.php`: scaffold model with commented config.
- `Modules/Admin/Livewire/Settings/Placeholder.php` and `Modules/Admin/resources/views/pages/settings/placeholder.blade.php`: placeholder naming suggests unfinished screen.
- Page blades and controllers for products/orders/posts/customers/roles/staff/database/settings if they are only referenced from `web copy.php`.
- Duplicate Livewire blade tree under `Modules/Admin/resources/views/livewire/admin/*`.

## 18. Module Boundary Violations

- P1: `Modules/Admin` acts as a domain owner for product, order, post, category, coupon, customer, role, staff, affiliate, flash sale, banner, chat, and settings behavior.
- P1: `Modules/Admin/Livewire/Products/ProductForm.php` and `ProductTable.php` use `Modules\Website\Models\WpProduct` and `Modules\Website\Models\Category`.
- P1: `Modules/Admin/Livewire/Posts/PostForm.php` and `PostTable.php` use `Modules\Website\Models\Post`, `Category`, and `Tag`.
- P1: `Modules/Admin/Livewire/Orders/OrderTable.php` and `OrderDetail.php` use `Modules\Website\Models\Order`.
- P1: `Modules/Admin/Livewire/Footer/*` uses `Modules\Website\Services\FooterService` and Website footer models.
- P1: `Modules/Admin/Services/AdminAffiliateService.php` and `AffiliateRankService.php` use Website orders and App users.
- P1: `Modules/Admin/Models/AffiliateScheme.php` and `FlashSaleItem.php` relate directly to Website `WpProduct`.
- P1: `Modules/Admin/Services/AuthService.php`, staff/customer Livewire classes, and address services use `App\Models\User`, which may be acceptable for framework user ownership but conflicts with module-first domain boundaries. Needs verification.

## 19. Refactor Summary by Priority

### P0 Critical

- P0: Lock down `Modules/Admin/routes/api.php` with auth/permissions or remove it.
- P0: Disable or permission-gate database backup/restore/truncate/drop/download flows in `Modules/Admin/Http/Controllers/DatabaseController.php`, `Modules/Admin/Livewire/Database/TableList.php`, and `Modules/Admin/Services/DatabaseService.php`.
- P0: Replace shell command strings in `Modules/Admin/Services/DatabaseService.php` with Symfony Process argument arrays, remove DB passwords from command strings, and validate table/file identifiers against server-owned metadata.
- P0: Wrap foreign-key disabling in `Modules/Admin/Services/DatabaseService.php` with `try/finally` and add destructive-action confirmation plus audit logging.

### P1 Important

- P1: Define Admin as a shell-only owner and migrate product/order/post/category/coupon/customer/role/staff/domain code out of `Modules/Admin`.
- P1: Create a shell/menu service for `Modules/Admin/Livewire/Menus/MenuTable.php` and `MenuForm.php`; move queries, transactions, JSON import/export, slug logic, and recursive persistence out of Livewire.
- P1: Replace custom Admin product/menu/post/coupon/role import/export logic with the shared `Modules/Shared/Services/ImportExport` foundation.
- P1: Remove direct model queries from Admin Livewire classes and route all business operations through services owned by the canonical modules.
- P1: Fix malformed migration timestamps in `Modules/Admin/database/migrations/*` after planning migration compatibility.
- P1: Add named permission middleware/checks for active Admin web routes and all mutating Livewire methods.
- P1: Add bounded pagination/chunking for large list/export paths including `ProductsExport` and `ProductTable`.

### P2 Nice to have

- P2: Remove `Modules/Admin/routes/web copy.php` and other stale files after route/link verification.
- P2: Remove `Modules/Admin/Livewire/Affiliate/commission-list.blade.php:Zone.Identifier`.
- P2: Prune duplicate `resources/views/livewire/admin/*` views after confirming active component view names.
- P2: Remove scaffold/empty controller methods in `Modules/Admin/Http/Controllers/AdminController.php`.
- P2: Move genuinely reusable UI components from Admin to `Modules/Shared` after confirming cross-module use.
