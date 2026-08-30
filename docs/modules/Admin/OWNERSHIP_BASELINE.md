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

A sidebar item may link to Product, Order, Post, System, Account, Role, Category, Chat, or another module. That link is navigation metadata only. The target module remains the canonical owner of its business behavior.

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
| Customer/address legacy runtime | `CLEANED` | Account + User split | Dead Admin Customer runtime removed; Account owns active account/customer-profile workspace, User retains UserAddress/schema; no schema move or legacy route revival |
| Role / Staff / Admin identity legacy | `CLEANED` | Role + Account/shared User split | Role owns RBAC runtime; Account owns EmployeeProfile/account runtime; `auth:admin` uses shared users provider; obsolete Admin model removed |
| Banner/public website header/footer/home settings | `UNKNOWN -> MOVE candidate` | Website/content owner | Distinguish public-site presentation from Admin shell header/footer |
| Affiliate/flash sale/coupon/marketing legacy | `UNKNOWN -> MOVE candidate` | canonical promotion/domain owner | Order-associated affiliate modal/service compatibility remains deliberately outside Order management cleanup |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE` | future hardened System database-operation boundary | Must remain unreachable in Admin |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Cleaned Runtime Evidence

Category, Chat, Product, Order and Post have dedicated ownership cleanup contract tests and remain owned by their canonical modules. Their proven Admin runtime duplicates have been removed without schema/migration ownership changes.

Customer/address cleanup proves a split canonical boundary:

- active `admin.accounts.index/create/edit` routes remain Account-owned under `auth:admin`;
- Account owns account/customer-profile runtime through `AccountService` and `CustomerProfile`;
- User retains `UserAddress` and the existing `user_addresses` migration/schema contract;
- Order-history aggregation was not copied into Account;
- ten unreachable Admin Customer controller/Livewire/view artifacts were removed;
- no `/admin/customers*` route was revived without a proven compatibility caller;
- `tests/Feature/Admin/AdminCustomerOwnershipCleanupContractTest.php` protects this boundary.

Role / Staff / Admin identity cleanup proves another split boundary:

- `/admin/roles*` is Role-owned and retains `admin.role.*` names, RoleController, existing permission middleware, Role services/Livewire, and historical `/admin/role*` redirects;
- `/admin/accounts*` remains Account-owned and `EmployeeProfile` remains in Account;
- both `web` and `admin` guards use the shared `users` provider;
- the nearly empty, unused `Modules/Admin/Models/Admin.php` legacy residue was removed;
- no auth config, role/account runtime, permission, schema, migration, or production data was changed;
- `tests/Feature/Admin/AdminRoleStaffIdentityOwnershipContractTest.php` protects this boundary.

## Reachability Proof Required Before Future MOVE / DEPRECATE / DEAD

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. Each future domain slice must check routes/providers, Livewire aliases, Blade callers, imports, jobs/events/commands, tests/seeders, navigation metadata, production table/migration state, and compatibility requirements as applicable.

## P0 Database Administration Quarantine

`Modules/Admin/Services/DatabaseService.php` is a latent destructive capability and is not part of the canonical Admin shell. It must remain unreachable. Admin API routes remain empty and the Admin database Livewire surface remains fail closed.

## Guardrails

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports remain limited to canonical shell controllers.
- `/admin/menus` and `admin.menu.*` remain canonical sidebar/navigation configuration.
- Admin API remains closed by default.
- Admin database administration remains fail-closed and quarantined.
- Category business runtime remains owned by `Modules/Category`.
- Chat business runtime remains owned by `Modules/Chat`.
- Product business runtime remains owned by `Modules/Product`.
- Order management runtime remains owned by `Modules/Order`.
- Post/content runtime remains owned by `Modules/Post`.
- Account/customer-profile runtime remains owned by `Modules/Account`; UserAddress/schema remains owned by `Modules/User`.
- Role/RBAC runtime remains owned by `Modules/Role`; EmployeeProfile/account runtime remains owned by `Modules/Account`.
- `auth:admin` continues to use the shared users provider unless a separately approved authentication architecture change replaces it.
- Proven legacy Admin Category, Chat, Product, Order, Post, Customer and Admin-identity residues must remain absent.
- ProductCommission dedicated UX remains a separately scoped follow-up rather than being silently redesigned.
- Affiliate commission ownership remains a separately scoped follow-up rather than being silently absorbed into Order cleanup.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Category, Chat, Product, Order, Post, Customer/address and Role/Staff/Admin-identity cleanup do not change schema/migration ownership.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. keep all completed ownership cleanup guardrails green;
3. choose one next legacy family with a verified canonical replacement;
4. prove callers/reachability and compatibility;
5. migrate/remove only the proven obsolete Admin runtime copy;
6. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of remaining legacy Admin families;
- canonical ownership of Affiliate commission/rank/scheme behavior still duplicated across Admin/Order boundaries;
- production usage of remaining legacy Admin tables;
- production migration ledger for Admin migrations;
- external dependencies on historical Admin URLs/aliases outside the cleaned families;
- whether Role route names should eventually be normalized from singular `admin.role.*` to plural naming without breaking external callers.

These unknowns remain blockers against bulk deletion of unrelated families.
