# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Final Core Boundary Closeout**

Status: **COMPLETE — FINAL REGRESSION PASS / ROUTES PASS / PINT PASS / BUILD PASS / UI PASS / PR READY**

Branch: `refactor/admin-core-boundary-cleanup`

Base: `cae7051d` (merged PR #115)

This checkpoint closes the Admin major-refactor boundary. `Modules/Admin` is treated as the authenticated Admin shell, navigation and layout-integration module. Business-domain ownership remains in specialized modules.

## Final canonical Admin boundary

`Modules/Admin/routes/web.php` is the runtime allow-list for Admin-owned presentation.

Canonical Admin route controllers are:

- `Modules\Admin\Http\Controllers\AdminController`
- `Modules\Admin\Http\Controllers\DashboardController`
- `Modules\Admin\Http\Controllers\MenuController`
- `Modules\Admin\Http\Controllers\ProfileController`

Canonical Admin surfaces are dashboard, menu management, profile and the `/admin/layout` hub with `general`, `header`, `sidebar`, `footer`, `design` and `navigation` sections.

`/admin/layout/sidebar` remains shell/sidebar configuration and links to `/admin/menus`; menu management is not merged into layout configuration.

`DatabaseController` is not part of the normal canonical route allow-list and remains a quarantined legacy/database boundary.

## Ownership moved out of Admin in this checkpoint

### Auth

Admin login/logout/Google authentication is Auth-owned.

- legacy Admin `AuthController` removed;
- legacy Admin `Auth/GoogleController` removed;
- `AuthService` moved from `Modules/Admin/Services` to `Modules/Auth/Services`;
- canonical `Modules/Auth/Http/Controllers/GoogleController` now resolves `Modules\Auth\Services\AuthService`.

### Order / Product / Role / Staff

Legacy Admin controllers for Order, Product, Product Commission, Role and Staff are removed. Canonical runtime stays in their specialized modules.

The duplicate Admin `Livewire/Orders/OrderDetailModal` is removed; canonical Order Livewire/runtime remains owned by `Modules/Order`.

### System settings

Deprecated Admin Livewire adapters for Advanced, Database, ENV, Mail, Modules, Momo, Setting, Social and Storage settings are removed. Canonical implementations remain in `Modules/System`.

Admin-owned layout configuration components remain preserved:

- `AdminLayoutConfig`
- `AdminLayoutDashboard`
- `AdminThemeEditor`

### Website integration

The deprecated Admin `HeaderMenuService` bridge is removed. Admin header configuration and runtime now consume the Website-owned `HeaderMenuService` directly where legacy menu data is still needed. `/admin/layout/header` remains Admin-owned; Website remains owner of HeaderMenu persistence and behavior.

## Deferred compatibility debt — Website refactor

Final retirement of remaining Website-owned compatibility adapters inside `Modules/Admin` is **intentionally deferred to the upcoming `Modules/Website` refactor**. This is not a blocker for closing the Admin major refactor because canonical Website runtime already owns these domains.

Examples intentionally deferred include Banner, Flash Sale, Affiliate and HeaderMenu model/service compatibility aliases that still remain in Admin.

The Website refactor must perform caller-proofed retirement/update of those adapters and update this boundary if any external compatibility contract is still required.

## Explicit quarantine / preserved persistence

The following are outside this closeout and remain intentionally preserved:

- `Modules/Admin/Http/Controllers/DatabaseController.php`
- `Modules/Admin/Services/DatabaseService.php`
- `Modules/Admin/Models/ModuleRouteTitle.php`
- `Modules/Admin/database/migrations/2026_08_04_000002_create_module_route_titles_table.php`

No destructive database operation is reactivated, moved, beautified or deleted in this phase. No schema/table removal is included.

## Final architectural audit

Repository audit confirms:

- Admin routes import only `AdminController`, `DashboardController`, `MenuController` and `ProfileController`;
- `/admin/layout` and all six layout sections remain Admin-owned;
- Auth Google controller depends on Auth-owned `AuthService`;
- specialized Order/Product/Role/Account routes remain outside Admin;
- deprecated Admin System settings wrappers are retired;
- deprecated Admin HeaderMenu service bridge is retired while Admin header/menu integration remains functional through the Website service;
- Database and `ModuleRouteTitle` remain explicitly quarantined/preserved;
- remaining Website compatibility aliases are documented as deferred Website-refactor debt rather than Admin runtime ownership.

## Contract protection

`tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php` protects the canonical shell route/controller boundary, layout hub, menu ownership and closed Admin API surface.

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php`, `AdminAffiliateOwnershipContractTest.php`, `AdminFlashSaleOwnershipContractTest.php`, `AdminHeaderSettingsUiContractTest.php` and `AdminOrderOwnershipCleanupContractTest.php` protect retired legacy runtime, specialized ownership, Admin layout/header integration, deferred Website compatibility debt and explicit quarantine boundaries.

`tests/Feature/System/CanonicalSettingsServiceTest.php` and `SystemSettingsOwnershipTest.php` protect System ownership after retirement of Admin settings adapters.

## Verification status

User-confirmed final verification: **PASS GREEN**.

Verified gate:

- `tests/Feature/Admin`: **PASS**
- `tests/Feature/Auth`: **PASS**
- `tests/Feature/System`: **PASS**
- `tests/Feature/Order`: **PASS**
- `tests/Feature/Product`: **PASS**
- `tests/Feature/Role`: **PASS**
- Admin route ownership checks: **PASS**
- focused Pint / formatted `AdminLayoutConfig`: **PASS**
- Vite production build: **PASS**
- manual Admin UI smoke: **PASS**

The transient failures discovered during the closeout were resolved before final acceptance: stale ownership contracts were aligned with retired adapters, and the remaining Admin header runtime dependency was moved from the deleted Admin `HeaderMenuService` to the canonical Website `HeaderMenuService`.

## Acceptance criteria

- Admin shell/dashboard/menu/profile/layout boundary: **COMPLETE**
- `/admin/layout` canonical hub: **PRESERVED / PASS**
- Auth ownership moved to `Modules/Auth`: **COMPLETE / PASS**
- legacy Admin Order/Product/Role/Staff controllers: **REMOVED / PASS**
- duplicate Admin Order Livewire: **REMOVED / PASS**
- deprecated Admin System settings adapters: **REMOVED / PASS**
- Admin HeaderMenu service bridge: **REMOVED / INTEGRATION PASS**
- Website compatibility adapter retirement: **DEFERRED TO WEBSITE REFACTOR BY DESIGN**
- Database destructive boundary: **QUARANTINED / UNCHANGED**
- `ModuleRouteTitle` persistence: **PRESERVED**
- schema/data changes: **NONE**
- final automated regression: **PASS**
- Pint: **PASS**
- build: **PASS**
- manual Admin UI: **PASS**

## Next checkpoint

Open and merge the final Admin Core Boundary Cleanup PR against `main`.

After merge, the **Admin Major Refactor is COMPLETE**. The next separate phase is the `Modules/Website` refactor, which owns the deferred Banner/FlashSale/Affiliate/HeaderMenu compatibility-debt cleanup and must not move those business domains back into Admin.
