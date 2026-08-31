# Admin Ownership & Reachability Baseline

## Purpose

This document is the implementation baseline for the Admin Major Refactor. Admin is the authenticated presentation/composition shell. Displaying a business feature inside the Admin UI does not make Admin the canonical owner of that feature's routes, models, services, permissions, or workflow.

## Canonical Admin Shell

The following surfaces are canonical Admin ownership and must be preserved unless a later approved architecture change explicitly replaces them:

- `/admin` dashboard entry and shell composition;
- `/admin/layout` as the Admin presentation orchestration hub;
- `/admin/layout/general`, `/header`, `/sidebar`, `/footer`, `/design`, and `/navigation` as shell presentation configuration;
- `/admin/menus`, `/admin/menus/create`, `/admin/menus/{id}/edit` as the separate configuration workspace for Admin sidebar/navigation structure;
- `AdminMenu`, `MenuService`, and `MenuImportExportService` for Admin navigation metadata;
- Admin sidebar rendering and navigation composition;
- Admin header and header actions/user-menu presentation;
- Admin theme/design presentation;
- Admin shell-owned profile/preferences;
- shell-specific presentation services and reusable Admin shell components.

`/admin/layout/sidebar` configures Sidebar appearance/behavior and links to `/admin/menus` for menu-structure management. The link is orchestration only: it does not merge the two route/controller subsystems.

A sidebar item may link to another module. That link is navigation metadata only; the target module remains the canonical owner of its business behavior.

## Current Active Route Boundary

`Modules/Admin/routes/web.php` imports only the canonical shell controllers:

- `AdminController`
- `DashboardController`
- `MenuController`
- `ProfileController`

The former `/admin/admin-header` entry is removed. `/admin/layout/header` is the canonical Admin shell Header configuration route, and `/admin/layout/footer` is the canonical Admin shell Footer configuration route.

Website-owned public presentation management remains separate under Website routes such as `/admin/header-settings` and `/admin/footer-settings`.

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
| Sidebar/navigation/menu management | `KEEP` | Admin | `/admin/layout/sidebar` owns appearance/behavior; `/admin/menus*` owns menu structure and is linked from the Sidebar workspace |
| Layout/header/sidebar/footer/design/theme | `KEEP` | Admin | `/admin/layout/*` is canonical Admin shell presentation; obsolete `/admin/admin-header` wrapper removed |
| Admin profile/preferences | `KEEP` | Admin UI + shared User/account contracts as applicable | `auth:admin` uses the shared users provider |
| Categories legacy runtime | `CLEANED` | Category | Canonical runtime is Category-owned |
| Chat legacy runtime | `CLEANED` | Chat | Canonical runtime is Chat-owned |
| Product / ProductCommission legacy | `CLEANED` | Product | Canonical Product runtime is Product-owned; ProductCommission UX remains separate debt |
| Order management legacy runtime | `CLEANED` | Order | Canonical order management is Order-owned; Affiliate commission residue is separate |
| Post/content legacy runtime | `CLEANED` | Post | Canonical content runtime is Post-owned |
| Customer runtime | `CLEANED` | Account | Account owns active account/customer-profile workspace |
| Address legacy ownership | `BOUNDARY MOVED` | User | Admin UserAddress/AddressService remain deprecated compatibility adapters |
| Role / Staff / Admin identity legacy | `CLEANED` | Role + Account/shared User split | Obsolete Admin identity model removed |
| Banner legacy runtime tree | `CLEANED` | Website | Active Website route/controller/view/Livewire is canonical; obsolete Admin Banner controller/view/Livewire tree removed. Deprecated Admin Banner model/service adapters remain compatibility debt. |
| Public Website Header/Footer legacy runtime | `CLEANED` | Website + shared System settings | Website routes/controllers/views/Livewire are canonical; historical Admin Header/Footer controllers, wrappers, Livewire classes and Blade runtime trees removed. Deprecated Admin HeaderMenu adapters remain compatibility debt. |
| Public Website Home settings residue | `BOUNDARY MOVED` | Website + shared System settings | Active Website management runtime is canonical; historical Admin Home residue still requires dedicated caller proof. |
| Flash Sale legacy runtime | `BOUNDARY MOVED` | Website + Product query boundary | Website owns Flash Sale behavior; Admin legacy runtime/adapters require separate cleanup proof |
| Coupon management residue | `BOUNDARY MOVED` | Website | Active Website route/controller/Livewire is canonical; historical Admin runtime requires dedicated cleanup proof |
| Affiliate commission/rank/scheme | `BOUNDARY MOVED` | Website orchestration + Order/Product/User boundaries | Active Affiliate runtime is Website-owned; compatibility/Order residue remains |
| Environment/system settings legacy | `UNKNOWN -> MOVE candidate` | System or dedicated configuration owner | Requires explicit operational boundary |
| Database administration | `QUARANTINE / P0 CONTAINED` | System | Canonical runtime is System-owned; Admin legacy family remains fail-closed |
| Historical scaffold/resource methods | `UNKNOWN -> DEAD candidate` | none | Remove only after caller proof |

## Proven Cleanup Evidence

Completed ownership slices for Category, Chat, Product, Order, Post, Customer, Role/Staff/Admin identity, Address, Website presentation, Flash Sale, Database P0 containment and Affiliate remain protected by their focused contract tests.

### Banner runtime-tree cleanup

Caller proof established the active Banner management graph as:

`Website route -> Modules\Website\Http\Controllers\Admin\BannerController -> Website::pages.admin.banner.index -> website.admin.banner.banner-manager`.

The historical Admin Banner graph had no place in the canonical Admin route-controller surface and was a migration residue. Its controller, wrapper view and Livewire runtime tree were removed. Deprecated Admin Banner model/service compatibility adapters remain intentionally until separate dynamic/external caller proof.

### Admin Layout Hub consolidation

The canonical Admin presentation graph is centered on `/admin/layout` and `/admin/layout/*`. The duplicate `/admin/admin-header -> AdminController::adminHeader -> Admin::pages.admin.header.index` entry was removed.

`/admin/layout/sidebar` provides a navigation action to `admin.menus.index`, while `/admin/menus*`, `MenuController`, menu Livewire components and `admin.menu.*` permissions remain independent canonical Admin ownership.

### Website Header/Footer legacy runtime cleanup

Caller proof established the active Header management graph as:

`Website /admin/header-settings route -> Modules\Website\Http\Controllers\Admin\HeaderController -> Website::pages.admin.header.index -> website.admin.header.header-settings-hub`.

Caller proof established the active Footer management graph as:

`Website /admin/footer-settings route -> Modules\Website\Http\Controllers\Admin\FooterController -> Website::pages.admin.footer.index -> Website footer Livewire components`.

The historical Admin `HeaderController`, `FooterController`, their wrapper views, three Header Livewire classes, three Footer Livewire classes and their Admin Blade trees were migration residue and have been removed. The removed Livewire implementations were already delegating Website presentation domain behavior to Website services/models or shared System settings.

This cleanup is distinct from canonical Admin shell `/admin/layout/header` and `/admin/layout/footer`, which remain `KEEP`.

Deprecated Admin `HeaderMenu`, `HeaderMenuItem`, and `HeaderMenuService` compatibility adapters remain intentionally until separate external/dynamic caller proof.

## Reachability Proof Required Before Future Cleanup

A file or family may not be removed merely because it is absent from `Modules/Admin/routes/web.php`. Each future domain slice must check routes/providers, Livewire aliases, Blade callers, imports, jobs/events/commands, tests/seeders, navigation metadata, production table/migration state, and compatibility requirements as applicable.

For historical features migrated from Admin into Website or another module, the required proof path is:

`active route -> controller -> wrapper view -> Livewire/component -> service/model boundary`.

The canonical replacement must be proven before deleting the old Admin runtime tree. GitHub code-search zero results are not sufficient proof when indexing is incomplete.

Deprecated Address, Banner/Header, Flash Sale and Affiliate compatibility adapters must not be removed until repository/runtime caller proof is complete; their existence does not restore canonical Admin ownership.

The quarantined Admin database family must not be deleted or reactivated merely from static search results.

## P0 Database Administration Quarantine

Canonical database administration is owned by `Modules/System`. Its `/admin/system/database*` routes are distinct from the historical Admin database family.

`Modules/Admin/Services/DatabaseService.php` remains a latent destructive capability and is not part of the canonical Admin shell. It must stay unreachable. The legacy Admin `TableList`, `BackupManager`, and `ImportDrawer` surfaces are fail-closed. Their presence is compatibility debt, not an active operational owner.

## Guardrails

- Admin manifest remains type `shell` with declared Auth/User/Role dependencies.
- Active Admin route controller imports remain limited to `AdminController`, `DashboardController`, `MenuController`, and `ProfileController`.
- `/admin/layout/*` is the canonical Admin shell presentation hub; `/admin/admin-header` must not return.
- `/admin/layout/sidebar` links to, but does not absorb, `/admin/menus`.
- `/admin/menus` and `admin.menu.*` remain canonical sidebar/navigation structure management.
- Admin API remains closed by default.
- Website owns canonical Banner, public Header and public Footer management/runtime; deleted Admin runtime trees must not return.
- Deprecated Admin Banner/HeaderMenu compatibility adapters must not regain independent persistence/business logic.
- Admin shell `/admin/layout/header|footer` remains distinct from public Website presentation management.
- System owns canonical database administration; legacy Admin database surfaces remain fail-closed and quarantined.
- Existing canonical ownership boundaries for Category, Chat, Product, Order, Post, Account, User, Role, Website and Affiliate remain unchanged.
- `auth:admin` continues to use the shared users provider unless separately redesigned.
- Schema/migration/data movement is not authorized by runtime ownership cleanup.

## Planned Refactor Sequence

1. keep ownership/P0 containment guardrails green;
2. choose exactly one next legacy family with a verified canonical replacement;
3. prove active route/controller/view/Livewire ownership and old-tree callers;
4. remove only the proven obsolete Admin runtime copy;
5. preserve compatibility adapters when dynamic/external callers remain unproven;
6. run focused tests and manual UI smoke before merge;
7. reconcile schema/migration ownership only after runtime ownership is stable.

## Outstanding Unknowns

- external/dynamic callers of deprecated Admin Address/Banner/Header/Flash Sale/Affiliate compatibility adapters;
- complete runtime reachability of remaining public Website Home settings Admin residue;
- external/dynamic callers of quarantined Admin database compatibility surfaces;
- direct runtime reachability and future scalability requirements of Website CommissionMatrix;
- production usage/migration ledger of remaining legacy Admin persistence;
- external dependencies on historical Admin URLs/aliases outside cleaned families.

These unknowns remain blockers against bulk deletion of unrelated families.
