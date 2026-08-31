# Admin — Module Contract

## 1. Identity

- Module: `Admin`
- Type: `shell`
- Status: `active`
- Manifest: `Modules/Admin/config/module.php`
- Routes: `Modules/Admin/routes/web.php`
- Last architecture review: `2026-08-31`

This document is the architectural source of truth for `Modules/Admin`. Major refactors and changes to ownership, dependencies, canonical routes, persistence, compatibility boundaries, or quarantine rules must read this contract first and update it in the same PR when the contract changes.

## 2. Purpose

`Modules/Admin` owns the authenticated administration shell and the shared presentation/integration surfaces required to operate that shell.

It is responsible for:

- the Admin dashboard shell;
- Admin menu management;
- Admin profile surface;
- the canonical `/admin/layout` configuration hub;
- shared Admin layout presentation for general, header, sidebar, footer, design, and navigation configuration;
- integration of specialized module capabilities into the Admin shell without taking ownership of their business domains.

Admin is not a catch-all business module.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Admin shell | Admin | `/admin` |
| Admin dashboard | Admin | Admin dashboard route/controller |
| Admin menu management | Admin | `/admin/menus*` |
| Admin profile | Admin | `/admin/profile` |
| Admin layout hub | Admin | `/admin/layout` |
| General layout configuration | Admin | `/admin/layout/general` |
| Header shell configuration | Admin | `/admin/layout/header` |
| Sidebar shell configuration | Admin | `/admin/layout/sidebar` |
| Footer shell configuration | Admin | `/admin/layout/footer` |
| Design/theme shell configuration | Admin | `/admin/layout/design` |
| Navigation shell configuration | Admin | `/admin/layout/navigation` |

The canonical Admin controller boundary is intentionally narrow:

- `Modules\Admin\Http\Controllers\AdminController`
- `Modules\Admin\Http\Controllers\DashboardController`
- `Modules\Admin\Http\Controllers\MenuController`
- `Modules\Admin\Http\Controllers\ProfileController`

`DatabaseController` is explicitly quarantined and is not evidence that Admin owns the System/database domain.

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Admin relationship |
|---|---|---|
| Authentication/login/logout/Google authentication | Auth | consumes authentication boundary |
| Users/accounts | User | consumes identity/user data |
| Roles and role domain | Role | consumes authorization/role capability |
| Orders | Order | links/integrates into Admin shell |
| Products | Product | links/integrates into Admin shell |
| System settings, ENV, mail, module management, advanced configuration | System | links/integrates into Admin shell |
| Chat domain | Chat | Admin is an authenticated consumer/surface |
| Website presentation/business domains | Website | integrates where required; does not own Website business logic |
| Affiliate domain | Website | compatibility debt only where still present |
| Banner domain | Website | compatibility debt only where still present |
| Flash Sale domain | Website | compatibility debt only where still present |
| Website HeaderMenu persistence/business logic | Website | Admin may consume/import Website-owned menu data |

Business implementations for these domains must not be recreated in `Modules/Admin` without an approved architectural change.

## 5. Dependencies

### Direct dependencies

The module manifest currently declares:

| Module | Reason | Required |
|---|---|---|
| Auth | authenticated Admin boundary | Yes |
| User | Admin identity/profile/user integration | Yes |
| Role | Admin authorization/role integration | Yes |

These declarations must remain synchronized with `Modules/Admin/config/module.php`.

### Integration dependencies

Admin may consume capabilities from specialized modules such as Website, System, Order, Product, and Chat through explicit integration boundaries. Such consumption does not transfer business ownership to Admin.

A new hard dependency must not be introduced silently. If a runtime change makes another module mandatory for Admin to boot or operate, both the manifest and this contract must be reviewed and updated in the same PR.

## 6. Consumers

Admin is primarily a shell consumed by administrators and by specialized modules that expose their management surfaces under `/admin/*`.

A specialized module may register an Admin-facing route or render within the Admin shell while retaining ownership of its controller, Livewire components, services, models, and persistence.

Route prefix `/admin` therefore does not imply `Modules/Admin` ownership.

## 7. Canonical Routes

Canonical Admin-owned route groups are:

- `/admin`
- `/admin/menus*`
- `/admin/profile`
- `/admin/themes` compatibility redirect to the layout design surface
- `/admin/layout`
- `/admin/layout/general`
- `/admin/layout/header`
- `/admin/layout/sidebar`
- `/admin/layout/footer`
- `/admin/layout/design`
- `/admin/layout/navigation`

When auditing Admin ownership, route tracing must include other modules' route files because specialized modules legitimately own many `/admin/*` routes.

Route ownership must be traced as:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

- `AdminController`
- `DashboardController`
- `MenuController`
- `ProfileController`

### Layout/UI boundary

- canonical Admin master shell/layout;
- Admin dashboard;
- Admin menu management UI;
- Admin profile UI;
- Admin layout configuration UI;
- shared header/sidebar/footer/design/navigation shell components.

### Services

Only services whose responsibility is genuinely Admin shell/presentation/integration belong here. A service must not be considered Admin-owned merely because it is used from an Admin-facing page.

### Models

Models must represent Admin-owned persistence or an explicitly documented compatibility boundary. Domain models owned by specialized modules must remain in those modules.

This section intentionally describes canonical boundaries rather than enumerating every file in the directory tree.

## 9. Persistence Ownership

Admin-owned persistence must be documented and protected by migration/runtime contracts.

`ModuleRouteTitle` and its `module_route_titles` persistence contract are currently preserved. They must not be deleted or rehomed merely because a direct route caller is not obvious.

Any future persistence rehome requires a dedicated migration/data compatibility plan and explicit approval.

## 10. Integration Boundaries

Admin follows this ownership rule:

`Admin shell → specialized module capability`

The specialized module remains owner of its business logic.

Examples:

- Admin authentication uses Auth-owned runtime.
- Admin order surfaces use Order-owned runtime.
- Admin product surfaces use Product-owned runtime.
- Admin role capability uses Role-owned runtime.
- Admin system settings use System-owned runtime.
- Admin chat surfaces consume Chat-owned runtime.
- Admin header configuration may consume Website-owned HeaderMenu data while Admin owns only its shell/config import behavior.

Cross-module integration must preserve dependency direction and must not introduce duplicate implementations as a shortcut.

## 11. Compatibility / Deprecated Boundaries

Compatibility artifacts are not canonical ownership.

Remaining Website-related compatibility aliases/adapters involving Banner, FlashSale, Affiliate, or HeaderMenu models are deferred to the `Modules/Website` refactor. Their presence in Admin must not be used as evidence that Admin owns those domains.

Deprecated does not mean safe to delete. Removal requires caller/dependency proof and regression coverage, preferably in the refactor of the canonical owner module.

## 12. Quarantine

The following boundaries are intentionally preserved outside ordinary Admin cleanup:

- `Modules/Admin/Http/Controllers/DatabaseController.php`
- `Modules/Admin/Services/DatabaseService.php`
- destructive database operations reachable through that boundary;
- `Modules/Admin/Models/ModuleRouteTitle.php` and its persistence contract.

Quarantine means:

- do not expand;
- do not rehome;
- do not beautify as incidental cleanup;
- do not delete;

unless a separately approved phase provides migration, caller, safety, and regression proof.

## 13. Refactor Invariants

Every Admin refactor must preserve:

1. canonical Admin routes and `/admin/layout` as the central layout hub;
2. authentication and authorization boundaries;
3. route middleware and permissions;
4. Admin master shell behavior;
5. required Blade slots, stacks, scripts, styles, and assets;
6. Admin menu/profile/layout behavior;
7. persistence contracts unless migration is explicitly approved;
8. specialized module ownership of business domains;
9. valid cross-module dependency direction;
10. compatibility artifacts until removal is caller-proven;
11. quarantine boundaries unless separately authorized;
12. the distinction between `/admin/*` URL namespace and `Modules/Admin` ownership.

## 14. Required Refactor Audit

Before implementation, an Admin major refactor must perform:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

Each affected artifact must then be classified as one of:

- `KEEP`
- `REHOME`
- `DELETE`
- `QUARANTINE`
- `DEFER`

A similarly named implementation in another module is not sufficient deletion proof. Runtime ownership and callers must be established first.

If this document disagrees with runtime, record an `ARCHITECTURE DRIFT`, determine the intended target architecture, obtain approval, and only then implement the correction.

## 15. Required Regression Scope

Changes to Admin require focused verification based on affected boundaries. At minimum consider:

- `tests/Feature/Admin`;
- tests for directly changed dependency/consumer modules;
- canonical Admin route listing;
- route listing for rehomed specialized surfaces;
- Pint on changed PHP files;
- frontend production build when views/assets are affected;
- manual UI smoke for `/admin`, `/admin/layout`, and affected specialized Admin surfaces.

Contract tests must be updated in the same architectural slice when a legacy file or boundary is intentionally retired. Tests must protect the target architecture, not stale legacy paths.

## 16. Architectural Change Rules

`MODULE.md` is the architectural source of truth for this module.

Any PR that changes one or more of the following must update this document in the same PR:

- module purpose/responsibility;
- canonical ownership;
- explicit non-ownership;
- direct dependencies;
- canonical routes;
- public integration boundaries;
- persistence ownership;
- compatibility/deprecation boundaries;
- quarantine boundaries;
- refactor invariants.

Source code and `MODULE.md` must not be merged when their architectural contracts conflict.

`MODULE.md` is not a substitute for runtime proof. When documentation and runtime disagree, the discrepancy must be treated as architecture drift rather than silently assuming either side is correct.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Website Banner compatibility aliases/adapters remaining in Admin | Website | canonical business ownership is Website | Website refactor proves callers and removes/replaces compatibility boundary |
| Website FlashSale compatibility aliases/adapters remaining in Admin | Website | canonical business ownership is Website | Website refactor proves callers and removes/replaces compatibility boundary |
| Website Affiliate compatibility aliases/adapters remaining in Admin | Website | canonical business ownership is Website | Website refactor proves callers and removes/replaces compatibility boundary |
| Website HeaderMenu model compatibility aliases remaining in Admin | Website | canonical persistence/business ownership is Website | Website refactor proves callers and retires compatibility aliases safely |
| Database destructive boundary | separate approved database/system phase | high-risk operations require dedicated safety plan | explicit migration/safety/regression plan approved |
| ModuleRouteTitle persistence boundary | separate approved persistence phase if ever changed | persistence contract requires migration proof | explicit persistence migration plan approved |

## 18. Architecture Decisions

### 2026-08-31 — Admin becomes a narrow shell boundary

**Decision:** `Modules/Admin` is the authenticated Admin shell/navigation/layout integration module rather than the owner of business functionality exposed under `/admin/*`.

**Reason:** Order, Product, Role, Auth, System, Chat, Website, and other specialized modules now provide canonical implementations for their own domains.

**Impact:** legacy Admin controllers, duplicate Livewire components, and ownership adapters may be retired or rehomed only after runtime/caller proof. Specialized modules retain business ownership even when their routes use the `/admin` prefix.

### 2026-08-31 — `/admin/layout` is the canonical Admin layout hub

**Decision:** `/admin/layout` and its general/header/sidebar/footer/design/navigation sections are canonical Admin-owned shell configuration surfaces.

**Reason:** these concerns configure the shared Admin shell rather than a specialized business domain.

**Impact:** menu management remains a separate Admin capability under `/admin/menus`; sidebar configuration may link to menu management but does not absorb it.

### 2026-08-31 — Website compatibility debt is deferred to Website refactor

**Decision:** remaining Banner, FlashSale, Affiliate, and HeaderMenu compatibility aliases/adapters associated with Website ownership do not block Admin major-refactor closeout.

**Reason:** their canonical domain owner is Website and their final removal is safer when Website is audited end-to-end.

**Impact:** these artifacts remain non-canonical compatibility debt until the Website refactor provides caller/dependency proof and regression coverage.
