# Admin Ownership & Reachability Baseline

## Purpose

This document is the implementation baseline for the Admin Major Refactor. Admin is the authenticated presentation/composition shell. Displaying a business feature inside the Admin UI does not make Admin the canonical owner of that feature's routes, models, services, permissions, or workflow.

## Canonical Admin Shell

The following surfaces are canonical Admin ownership and must be preserved unless a later approved architecture change explicitly replaces them:

- `/admin` dashboard entry and shell composition;
- `/admin/menus`, `/admin/menus/create`, `/admin/menus/{id}/edit` as the configuration workspace for Admin sidebar/navigation;
- `AdminMenu`, `MenuService`, and `MenuImportExportService` for Admin navigation metadata;
- Admin sidebar rendering and navigation composition;
- Admin header and header actions/user-menu presentation;
- Admin layout workspaces: general, header, sidebar, footer, design, and navigation;
- Admin theme/design presentation;
- Admin shell-owned profile/preferences;
- shell-specific presentation services and reusable Admin shell components.

A sidebar item may link to Product, Order, Post, System, Account, Role, Category, Chat, Website, or another module. That link is navigation metadata only. The target module remains the canonical owner of its business behavior.

## Current Active Route Boundary

`Modules/Admin/routes/web.php` imports only the canonical shell controllers:

- `AdminController`
- `DashboardController`
- `MenuController`
- `ProfileController`

`Modules/Admin/routes/api.php` is intentionally empty. Admin APIs require an explicit authentication/authorization contract and tests before introduction.

## Classification Vocabulary

| State | Meaning | Allowed action |
|---|---|---|
| `KEEP` | Canonical Admin shell ownership | Preserve and improve in Admin |
| `MOVE` | Reachable behavior whose canonical owner is another module | Migrate caller/contract first, then remove Admin copy |
| `BOUNDARY MOVED` | Canonical runtime now uses the target module, but legacy Admin copies still require final reachability proof | Keep compatibility until dedicated cleanup is proven safe |
| `CLEANED` | Canonical replacement is active and the proven Admin runtime duplicate/residue was removed | Keep ownership guardrails green |
| `DEPRECATE` | Replacement is proven but compatibility remains | Preserve compatibility until separately removed |
| `DEAD` | No reachable route/caller/alias/job/event/test and no production dependency | Remove in a focused cleanup slice |
| `UNKNOWN` | Reachability or production/schema state is not proven | Do not delete or move yet |
| `QUARANTINE` | Dangerous legacy capability that must stay unreachable | Redesign only in a separately approved owner |

## Ownership Families

| Admin family | Current state | Canonical owner / direction | Notes |
|---|---|---|---|
| Dashboard shell | `KEEP` | Admin | Shell entry/composition |
| Sidebar/navigation/menu management | `KEEP` | Admin | Includes `/admin/menus`, `AdminMenu`, menu services and shell rendering |
| Layout/header/sidebar/footer/design/theme | `KEEP` | Admin | Admin shell presentation only |
| Admin profile/preferences | `KEEP` | Admin UI + shared User/account contracts as applicable | `auth:admin` uses the shared users provider; no separate Admin identity model is required |
| Categories legacy runtime | `CLEANED` | Category | Canonical routes/controller/Livewire/views are in `Modules/Category`; proven Admin runtime duplicates removed |
| Chat legacy runtime | `CLEANED` | Chat | Canonical Chat runtime is active; proven legacy Admin controller/Livewire/models/service/views removed |
| Product / ProductCommission legacy | `CLEANED` | Product | Product admin runtime/import-export ownership is canonical in `Modules/Product`; proven Admin duplicates removed. Dedicated ProductCommission UX remains follow-up debt. |
| Order management legacy runtime | `CLEANED` | Order | Canonical `admin.orders.*` routes/controller/Livewire/views are Order-owned; proven Admin management duplicates removed. Affiliate commission compatibility is tracked separately. |
| Post/content legacy runtime | `CLEANED` | Post | Canonical routes/controller/Livewire/model/services/import-export/schema are in `Modules/Post`; proven Admin runtime duplicates removed; Post URLs/data/schema preserved |
| Customer runtime | `CLEANED` | Account | Dead Admin Customer runtime removed; Account owns active account/customer-profile workspace |
| Address legacy ownership | `BOUNDARY MOVED` | User | User owns canonical UserAddress model/service/schema; historical Admin UserAddress and AddressService are deprecated compatibility adapters with no independent persistence/business logic |
| Role / Staff / Admin identity legacy | `CLEANED` | Role + Account/shared User split | Role owns RBAC runtime; Account owns EmployeeProfile/account runtime; `auth:admin` uses shared users provider; obsolete Admin model removed |
| Banner/public website header/footer/home settings | `BOUNDARY MOVED` | Website + shared System settings | Banner and public HeaderMenu runtime are Website-owned; Admin may host management UI. Five deprecated Admin Banner/Header compatibility adapters remain pending complete caller proof. Footer columns/social links already use Website; footer/header text settings may use shared SettingsService. |
| Flash Sale legacy runtime | `BOUNDARY MOVED` | Website + Product query boundary | Website owns FlashSale/FlashSaleItem/service behavior; Product owns product querying. Admin keeps management composition. Three deprecated Admin Flash Sale adapters remain pending complete caller proof. |
| Coupon management | `KEEP management surface / domain aligned` | Website | Admin Coupon Livewire already consumes Website Coupon domain; no ownership migration required merely because it is marketing. |
| Affiliate commission/rank/scheme | `BOUNDARY MOVED` | Website orchestration + Order/Product/User boundaries | Active `/admin/affiliate` runtime and Affiliate models/services are Website-owned today; Order retains commission state, Product retains products, User retains affiliate identity/rank. Five deprecated Admin compatibility adapters remain pending complete caller proof. |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE / P0 CONTAINED` | System | Canonical `/admin/system/database*` runtime is System-owned. Legacy Admin DatabaseService/Livewire surfaces remain fail-closed compatibility debt pending complete caller proof. |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Cleaned / Moved Runtime Evidence

Category, Chat, Product, Order and Post have dedicated ownership cleanup contract tests and remain owned by their canonical modules. Their proven Admin runtime duplicates have been removed without schema/migration ownership changes.

Customer cleanup proves the Account side of the customer/address split:

- active `admin.accounts.index/create/edit` routes remain Account-owned under `auth:admin`;
- Account owns account/customer-profile runtime through `AccountService` and `CustomerProfile`;
- Order-history aggregation was not copied into Account;
- ten unreachable Admin Customer controller/Livewire/view artifacts were removed;
- no `/admin/customers*` route was revived without a proven compatibility caller;
- `tests/Feature/Admin/AdminCustomerOwnershipCleanupContractTest.php` protects this boundary.

Address ownership cleanup proves the User side of that split:

- `Modules\User\Models\UserAddress` remains the canonical persistence model for `user_addresses`;
- `Modules\User\Services\UserAddressService` owns address queries and create/update/delete/default-address mutations;
- historical `Modules\Admin\Models\UserAddress` is a deprecated compatibility alias only;
- historical `Modules\Admin\Services\AddressService` preserves its public API but delegates to the User service instead of owning duplicate mutation logic;
- no schema, migration, route or production-data change was made;
- compatibility classes remain because complete dynamic/external caller proof is not claimed;
- `tests/Feature/Admin/AdminAddressOwnershipContractTest.php` protects this boundary.

Role / Staff / Admin identity cleanup proves another split boundary:

- `/admin/roles*` is Role-owned and retains `admin.role.*` names, RoleController, existing permission middleware, Role services/Livewire, and historical `/admin/role*` redirects;
- `/admin/accounts*` remains Account-owned and `EmployeeProfile` remains in Account;
- both `web` and `admin` guards use the shared `users` provider;
- the nearly empty, unused `Modules/Admin/Models/Admin.php` legacy residue was removed;
- no auth config, role/account runtime, permission, schema, migration, or production data was changed;
- `tests/Feature/Admin/AdminRoleStaffIdentityOwnershipContractTest.php` protects this boundary.

Website Presentation ownership cleanup proves a management-surface/domain split:

- Website owns Banner and public HeaderMenu/HeaderMenuItem models/services;
- Admin Banner/Header Livewire management surfaces consume Website domain classes rather than independent Admin persistence/service implementations;
- five historical Admin Banner/Header classes are deprecated compatibility adapters only and delegate through Website ownership;
- FooterColumns/SocialLinks were already Website-owned and FooterInfo remains on the shared System SettingsService boundary;
- `/admin/admin-header` remains a management surface, while `/admin/layout/*` remains canonical Admin shell presentation;
- `tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` protects this boundary.

Flash Sale ownership cleanup proves another management-surface/domain split:

- Website owns FlashSale/FlashSaleItem models and FlashSaleService behavior;
- the active `admin.flash-sales` route remains Website-controller-owned;
- Admin FlashSaleManager consumes Website domain classes and the Product model instead of Admin Flash Sale persistence classes or raw `wp_products` queries;
- Product remains canonical owner of the `wp_products` model/query boundary;
- three historical Admin Flash Sale classes are deprecated compatibility adapters only;
- Coupon and Affiliate were not modified by that slice;
- schema/migrations/data and P0 DatabaseService were not changed;
- `tests/Feature/Admin/AdminFlashSaleOwnershipContractTest.php` protects this boundary.

Database P0 containment proves the operational owner and closes the legacy Admin entry points:

- active `/admin/system/database`, `/admin/system/database/backup-restore`, and `/admin/system/database/download/{filename}` routes are declared by `Modules/System` under `auth:admin` and dedicated database permissions;
- System `DatabaseController` resolves `Modules\System\Services\DatabaseService`, not the historical Admin service;
- Admin `TableList`, `BackupManager`, and `ImportDrawer` are fail-closed and do not expose an independent destructive database runtime;
- `BackupManager` and `ImportDrawer` no longer resolve `Modules\Admin\Services\DatabaseService`;
- repository/static searches found no canonical Admin route/static caller for the legacy Admin database family, but complete dynamic/external zero-caller proof is not claimed;
- the Admin DatabaseService and legacy database Livewire/views remain `QUARANTINE` compatibility debt rather than being deleted without proof;
- no System database redesign, schema, migration, or production-data change was made;
- `tests/Feature/Admin/AdminDatabaseP0ContainmentContractTest.php` protects this boundary.

Affiliate ownership cleanup proves a cross-module management/domain boundary:

- active `admin.affiliate.index` is Website-controller-owned and remains protected by `auth:admin` plus `affiliate.view`;
- canonical Website CommissionList and CommissionMatrix retain `affiliate.manage` authorization for mutations;
- Website currently owns AffiliateLevel/AffiliateScheme persistence models and commission/rank orchestration, while Order retains commission state, Product retains product ownership and User retains affiliate identity/rank state;
- CommissionList now applies level filtering and bounded `10/25/50/100` pagination aligned with the Admin UI standard;
- five historical Admin Affiliate classes are deprecated compatibility adapters with no independent business/persistence logic;
- the historical broken Admin AffiliateLevel namespace dependency is removed from those adapters;
- CommissionMatrix remains canonical Website capability, but direct static reachability is not claimed and its redesign is deferred;
- no dedicated Affiliate module, schema/migration/data movement or commission business-rule redesign was performed;
- `tests/Feature/Admin/AdminAffiliateOwnershipContractTest.php` protects this boundary.

## Reachability Proof Required Before Future MOVE / DEPRECATE / DEAD

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. Each future domain slice must check routes/providers, Livewire aliases, Blade callers, imports, jobs/events/commands, tests/seeders, navigation metadata, production table/migration state, and compatibility requirements as applicable.

Deprecated Address, Banner/Header, Flash Sale and Affiliate compatibility adapters must not be removed until repository/runtime caller proof is complete; their existence does not restore canonical Admin ownership.

The quarantined Admin database family must not be deleted or reactivated merely from static search results. Dynamic/external caller proof or an explicitly approved replacement/removal contract is required.

## P0 Database Administration Quarantine

Canonical database administration is owned by `Modules/System`. Its `/admin/system/database*` routes are distinct from the historical Admin database family.

`Modules/Admin/Services/DatabaseService.php` remains a latent destructive capability and is not part of the canonical Admin shell. It must stay unreachable. The legacy Admin `TableList`, `BackupManager`, and `ImportDrawer` surfaces are fail-closed. Their presence is compatibility debt, not an active operational owner.

## Guardrails

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports remain limited to canonical shell controllers.
- `/admin/menus` and `admin.menu.*` remain canonical sidebar/navigation configuration.
- Admin API remains closed by default.
- System owns canonical database administration under `/admin/system/database*` and dedicated database permissions.
- Legacy Admin database administration remains fail-closed and P0 quarantined; it must not resolve the Admin DatabaseService from Livewire runtime.
- Category business runtime remains owned by `Modules/Category`.
- Chat business runtime remains owned by `Modules/Chat`.
- Product business runtime remains owned by `Modules/Product`.
- Order management runtime remains owned by `Modules/Order`.
- Post/content runtime remains owned by `Modules/Post`.
- Account/customer-profile runtime remains owned by `Modules/Account`.
- UserAddress persistence and address mutation behavior remain owned by `Modules/User`; deprecated Admin Address adapters must not regain independent logic.
- Role/RBAC runtime remains owned by `Modules/Role`; EmployeeProfile/account runtime remains owned by `Modules/Account`.
- Website owns Banner and public HeaderMenu/HeaderMenuItem domain runtime; Admin may provide authenticated management composition only.
- Website owns Flash Sale domain runtime; Product owns product queries; Admin may provide authenticated Flash Sale management composition only.
- Website currently owns canonical Affiliate management/orchestration and Affiliate models; Order/Product/User boundaries remain separate and must not be collapsed into Website by cleanup work.
- Deprecated Admin Banner/Header/Flash Sale/Affiliate compatibility adapters must not regain independent persistence/business logic.
- Admin shell `/admin/layout/*` presentation must remain distinct from public Website presentation management.
- `auth:admin` continues to use the shared users provider unless a separately approved authentication architecture change replaces it.
- Proven legacy Admin Category, Chat, Product, Order, Post, Customer and Admin-identity residues must remain absent.
- ProductCommission dedicated UX remains a separately scoped follow-up rather than being silently redesigned.
- CommissionMatrix redesign or a dedicated Affiliate module requires a separately approved architecture scope.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Category, Chat, Product, Order, Post, Customer/address, Role/Staff/Admin-identity, Website-presentation, Flash Sale, Database P0 containment and Affiliate ownership cleanup do not change schema/migration ownership.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. keep all completed ownership cleanup guardrails green;
3. choose one next legacy family with a verified canonical replacement or explicit architecture decision;
4. prove callers/reachability and compatibility;
5. migrate/remove only the proven obsolete Admin runtime copy;
6. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of remaining legacy Admin families;
- external/dynamic callers of deprecated Admin Address/Banner/Header/Flash Sale/Affiliate compatibility adapters;
- external/dynamic callers of quarantined Admin database compatibility surfaces;
- direct runtime reachability and future scalability requirements of Website CommissionMatrix;
- whether Affiliate should eventually become a dedicated module;
- production usage of remaining legacy Admin tables;
- production migration ledger for Admin migrations;
- external dependencies on historical Admin URLs/aliases outside the cleaned/moved families;
- whether Role route names should eventually be normalized from singular `admin.role.*` to plural naming without breaking external callers.

These unknowns remain blockers against bulk deletion of unrelated families.
