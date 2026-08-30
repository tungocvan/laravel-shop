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

A sidebar item may link to Product, Order, System, Account, Category, Chat, or another module. That link is navigation metadata only. The target module remains the canonical owner of its business behavior.

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
| `CLEANED` | Canonical replacement is active and the proven Admin runtime duplicate was removed | Keep ownership guardrails green |
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
| Admin profile/preferences | `KEEP` | Admin UI + canonical identity/account contracts as applicable | Persistence ownership must be verified before moving models |
| Categories legacy runtime | `CLEANED` | Category | Canonical routes/controller/Livewire/views are in `Modules/Category`; proven Admin runtime duplicates removed in Category cleanup slice |
| Product / ProductCommission legacy | `UNKNOWN -> MOVE candidate` | Product | Verify callers and replacement completeness |
| Orders legacy | `UNKNOWN -> MOVE candidate` | Order | Verify callers and compatibility |
| Posts/content legacy | `UNKNOWN -> MOVE candidate` | canonical content/Post owner | Verify current module contract before choosing owner |
| Chat legacy | `UNKNOWN -> MOVE candidate` | Chat | Chat module exists; dependency and authorization contract still require review |
| Customer/address legacy | `UNKNOWN -> MOVE candidate` | Account/User/Identity according to current contract | Do not guess schema owner |
| Roles/staff legacy | `UNKNOWN -> MOVE candidate` | Role/User/Account according to responsibility | Admin may present screens without owning identity/authorization domain |
| Banner/public website header/footer/home settings | `UNKNOWN -> MOVE candidate` | Website/content owner | Distinguish public-site presentation from Admin shell header/footer |
| Affiliate/flash sale/coupon/marketing legacy | `UNKNOWN -> MOVE candidate` | canonical promotion/domain owner | Verify actual module inventory and callers first |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE` | future hardened System database-operation boundary | Must remain unreachable in Admin |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Category Cleanup Evidence

The first domain cleanup slice establishes `Modules/Category` as the canonical owner of the Category admin workspace.

Evidence and preserved contracts:

- `admin.category.index`, `admin.category.create`, and `admin.category.edit` are registered by `Modules/Category/routes/web.php`;
- those routes resolve to `Modules\Category\Http\Controllers\CategoryController`;
- URLs remain `/admin/category`, `/admin/category/create`, and `/admin/category/{id}/edit`;
- permissions remain `view_category`, `create_category`, `edit_category`, and `delete_category`;
- Category page views use `category.categories.category-table` and `category.categories.category-form`;
- the canonical Category Livewire implementation owns Category services, validation, authorization, persistence and redirect behavior;
- no Category schema or migration ownership is changed by this cleanup.

The following obsolete Admin runtime duplicates were removed after the canonical route/view/Livewire replacement was verified:

- `Modules/Admin/Http/Controllers/CategoryController.php`;
- `Modules/Admin/Livewire/Categories/CategoryForm.php`;
- `Modules/Admin/Livewire/Categories/CategoryTable.php`;
- `Modules/Admin/resources/views/pages/categories/{index,create,edit}.blade.php`;
- `Modules/Admin/resources/views/livewire/categories/{category-form,category-table}.blade.php`.

`tests/Feature/Admin/AdminCategoryOwnershipCleanupContractTest.php` prevents these runtime copies from returning and verifies the canonical Category workspace remains present.

## Reachability Proof Required Before Future MOVE / DEPRECATE / DEAD

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. Each future domain slice must check, where applicable:

1. web/API routes and route providers;
2. Livewire aliases and class discovery/registration;
3. Blade tags, includes and route links;
4. controller/service/model imports across modules;
5. jobs, events, listeners, commands and scheduled tasks;
6. tests and factories/seeders;
7. menu/navigation records or config using historical URLs;
8. production table usage and migration ledger when schema ownership is involved;
9. external clients or compatibility requirements for historical URLs/aliases.

Until those checks are complete, the family remains `UNKNOWN`.

## P0 Database Administration Quarantine

`Modules/Admin/Services/DatabaseService.php` is a latent destructive capability and is not part of the canonical Admin shell.

Current containment contract:

- no active Admin web route references database administration;
- Admin API routes remain empty;
- `Modules/Admin/Livewire/Database/TableList.php` returns an empty table set;
- all exposed database mutation actions delegate to a deny method that aborts with HTTP 403;
- the Livewire component does not instantiate or reference `DatabaseService`.

The containment tests do not make `DatabaseService` production-safe. A future System-owned design requires separate approval and must cover explicit permissions, audit, allowlists, safe process invocation, secret redaction, backup integrity, restore verification, bounded resources, and recovery/rollback.

## Guardrails

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports remain limited to canonical shell controllers.
- `/admin/menus` and `admin.menu.*` remain canonical sidebar/navigation configuration.
- Admin API remains closed by default.
- Admin database administration remains fail-closed and quarantined.
- Category business runtime remains owned by `Modules/Category`, not reintroduced under `Modules/Admin`.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Schema ownership must be reconciled only after production migration-ledger and table-usage evidence is available.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. complete Category cleanup and verify Admin + Category focused tests;
3. choose one next legacy family with a verified canonical replacement;
4. prove callers/reachability and compatibility;
5. migrate/remove only the proven obsolete Admin runtime copy;
6. repeat one family at a time;
7. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of remaining legacy Admin components;
- production usage of legacy Admin tables;
- production migration ledger for Admin migrations;
- completeness of remaining canonical replacements;
- external dependencies on historical Admin URLs or Livewire aliases.

These unknowns are blockers against bulk deletion, not authorization to infer or guess ownership.
