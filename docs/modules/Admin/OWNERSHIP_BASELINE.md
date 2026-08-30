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
| `BOUNDARY MOVED` | Canonical runtime now uses the target module, but legacy Admin copies still require final reachability proof | Keep compatibility until dedicated cleanup is proven safe |
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
| Categories legacy runtime | `CLEANED` | Category | Canonical routes/controller/Livewire/views are in `Modules/Category`; proven Admin runtime duplicates removed |
| Chat legacy runtime | `CLEANED` | Chat | Canonical Chat runtime is active; proven legacy Admin controller/Livewire/models/service/views removed |
| Product / ProductCommission legacy | `CLEANED` | Product | Product admin runtime/import/export ownership is canonical in `Modules/Product`; proven Admin duplicates removed. Dedicated ProductCommission UX remains follow-up debt. |
| Orders legacy | `UNKNOWN -> MOVE candidate` | Order | Verify callers and compatibility |
| Posts/content legacy | `UNKNOWN -> MOVE candidate` | canonical content/Post owner | Verify current module contract before choosing owner |
| Customer/address legacy | `UNKNOWN -> MOVE candidate` | Account/User/Identity according to current contract | Do not guess schema owner |
| Roles/staff legacy | `UNKNOWN -> MOVE candidate` | Role/User/Account according to responsibility | Admin may present screens without owning identity/authorization domain |
| Banner/public website header/footer/home settings | `UNKNOWN -> MOVE candidate` | Website/content owner | Distinguish public-site presentation from Admin shell header/footer |
| Affiliate/flash sale/coupon/marketing legacy | `UNKNOWN -> MOVE candidate` | canonical promotion/domain owner | Verify actual module inventory and callers first |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE` | future hardened System database-operation boundary | Must remain unreachable in Admin |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Category Cleanup Evidence

`Modules/Category` is the canonical owner of the Category admin workspace. The active `admin.category.*` routes, controller, Livewire components and views are Category-owned; proven Admin runtime duplicates were removed without schema/migration changes.

`tests/Feature/Admin/AdminCategoryOwnershipCleanupContractTest.php` prevents those runtime copies from returning.

## Chat Cleanup Evidence

`Modules/Chat` is the canonical owner of Chat runtime behavior while Admin remains its authenticated presentation shell.

Preserved contracts include the existing admin Chat URLs/names, canonical Chat controllers, `auth:admin`, `permission:view_chat,admin`, capability-specific Livewire permissions and realtime behavior.

`tests/Feature/Admin/AdminChatOwnershipBoundaryContractTest.php` prevents the removed legacy Admin Chat runtime from returning. No Chat schema/table/migration ownership was changed by the runtime cleanup.

## Product Cleanup Evidence

`Modules/Product` is the canonical owner of the Product admin runtime. Active `admin.products.*` routes resolve to Product-owned controllers and Product pages mount Product-owned Livewire components.

The following proven legacy Admin Product copies were removed:

- `Modules/Admin/Livewire/Products/ProductForm.php`;
- `Modules/Admin/Livewire/Products/ProductTable.php`;
- `Modules/Admin/resources/views/livewire/products/product-form.blade.php`;
- `Modules/Admin/resources/views/livewire/products/product-table.blade.php`;
- `Modules/Admin/Exports/ProductsExport.php`;
- `Modules/Admin/Imports/ProductsImport.php`.

Canonical Product authorization remains capability-specific. Product Create/Edit category selection now presents the canonical Category hierarchy as a collapsed recursive tree, and Edit reveals ancestors needed for already-selected child categories. Product list pagination uses a Product-scoped white/indigo pagination view.

`tests/Feature/Admin/AdminProductOwnershipCleanupContractTest.php` protects canonical Product route/runtime ownership, legacy-file absence, authorization and the approved UI contracts.

`admin.products.commissions` remains canonically Product-owned. Its current page still reuses the Product form rather than a dedicated commission workspace; that UX is explicitly recorded as follow-up debt and was not redesigned in this cleanup.

No Product schema/table/migration ownership changed in this slice.

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
- Proven legacy Admin Category, Chat and Product runtime copies must remain absent.
- ProductCommission dedicated UX remains a separately scoped follow-up rather than being silently redesigned.

## Schema / Migration Rule

Runtime ownership cleanup does not authorize moving or renaming applied migrations or production tables. Category, Chat and Product runtime cleanup did not change schema/migration ownership.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. keep Category, Chat and Product cleanup guardrails green;
3. choose one next legacy family with a verified canonical replacement;
4. prove callers/reachability and compatibility;
5. migrate/remove only the proven obsolete Admin runtime copy;
6. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- complete runtime reachability of remaining legacy Admin families;
- production usage of legacy Admin tables;
- production migration ledger for Admin migrations;
- external dependencies on historical Admin URLs/aliases outside cleaned Category/Chat/Product families.

These unknowns remain blockers against bulk deletion of unrelated families.
