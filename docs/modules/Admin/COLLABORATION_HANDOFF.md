# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Core Boundary Hardening + Legacy Diagnostic Retirement**

Status: **IMPLEMENTED — LOCAL VERIFICATION PENDING**

Branch: `refactor/admin-core-boundary-hardening`

Base: `a0e14679` (merged PR #113)

This phase follows the Legacy Runtime Consolidation cleanup and narrows `Modules/Admin` further toward an authenticated Admin shell/integration boundary. It does not move business ownership back into Admin and does not broaden into Chat, Auth, Order, Website or database refactors.

## Canonical Admin boundary

`Modules/Admin/routes/web.php` remains the runtime allow-list for Admin-owned presentation. Canonical Admin controllers remain `AdminController`, `DashboardController`, `MenuController`, and `ProfileController`, with dashboard, shell/layout, menu management and profile behavior preserved.

`/admin/layout/header`, `/admin/layout/sidebar`, `/admin/layout/footer`, `/admin/layout/design` and related layout sections remain Admin shell configuration. `/admin/menus` remains a separate menu-management subsystem and is not merged into sidebar configuration.

## Specialized ownership proven in this phase

Admin authentication is Auth-owned. `Modules/Auth/Http/Controllers/AuthController::adminLogin()` renders the canonical Auth login page with the `admin` guard, and that page mounts `auth.auth.login-form`.

Chat is Chat-owned. `Modules/Chat` owns both the Admin-facing internal/customer-support chat routes and its Chat services/Livewire runtime. Admin is a consumer surface, not the owner of Chat broadcasting/domain behavior.

Order routes remain Order-owned, but `Modules/Admin/Livewire/Orders/OrderDetailModal.php` is intentionally preserved because it is still a concrete compatibility caller of `AdminAffiliateService`.

## Removed in this phase

The following legacy/diagnostic Admin artifacts are retired:

- `Modules/Admin/Livewire/Auth/LoginForm.php`
- `Modules/Admin/resources/views/livewire/auth/login-form.blade.php`
- `Modules/Admin/Events/MessageSent.php`
- `Modules/Admin/Jobs/TestQueueJob.php`

The removed Auth pair duplicated the canonical Auth module login runtime. The removed `MessageSent` event placed Chat-domain broadcasting ownership in Admin while Chat already owns its realtime runtime. `TestQueueJob` was a diagnostic queue/cache probe rather than Admin product runtime.

## Preserved compatibility, persistence and quarantine

Deprecated compatibility adapters remain where caller proof is incomplete or concrete callers still exist, including Banner/HeaderMenu, Flash Sale, Affiliate and Address compatibility boundaries.

`Modules/Admin/Livewire/Orders/OrderDetailModal.php` and `Modules/Admin/Services/AdminAffiliateService.php` remain preserved as a concrete compatibility chain.

`Modules/Admin/Models/ModuleRouteTitle.php` and migration `2026_08_04_000002_create_module_route_titles_table.php` remain preserved. The `module_route_titles` table is an explicit persistence contract; this phase does not infer that persistence is disposable merely from weak/static caller evidence.

`Modules/Admin/Services/DatabaseService.php` remains quarantined. No destructive database operation is reactivated, moved, beautified or deleted.

## Contract protection

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` now locks both the previous consolidation and this phase:

- retired Admin runtime/diagnostic artifacts must stay absent;
- Auth module remains canonical for Admin login;
- Chat module remains canonical for Admin-facing Chat routes/runtime;
- Website/Product/Role/Account specialized ownership remains protected;
- Admin shell controllers/layout remain canonical;
- Orders/Affiliate compatibility remains preserved;
- `ModuleRouteTitle` persistence remains preserved;
- Database quarantine remains preserved.

## Schema and data

No schema, migration, foreign-key or production-data change is included. No table is dropped.

## Verification plan

Run one consolidated local verification after pulling the completed branch:

1. `vendor/bin/pint --test Modules/Admin tests/Feature/Admin`
2. `php artisan test tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php`
3. `php artisan route:list --path=admin`
4. `npm run build`
5. Manual UI smoke: Admin login/logout, dashboard, `/admin/layout`, `/admin/menus`, profile, one Website-owned management surface, and Admin Chat routes if enabled/authorized in the local environment.

A full-project regression suite is not required for this ownership-only cleanup unless focused verification exposes an impacted dependency.

## Acceptance criteria

- Admin shell/layout/dashboard/menu/profile: **PRESERVED**
- canonical Auth admin login: **PRESERVED**
- canonical Chat ownership: **PRESERVED**
- Admin legacy Auth duplicate: **REMOVED**
- Admin legacy Chat event: **REMOVED**
- Admin diagnostic queue job: **REMOVED**
- Orders/Affiliate concrete compatibility chain: **PRESERVED**
- `ModuleRouteTitle` persistence: **PRESERVED**
- compatibility adapters with unresolved/dynamic callers: **PRESERVED / DEPRECATED**
- Database destructive boundary: **QUARANTINED / UNCHANGED**
- schema/data changes: **NONE**
- focused verification: **PENDING USER LOCAL RUN**
- UI smoke: **PENDING USER LOCAL RUN**

## Next checkpoint

Pull the completed branch and run the consolidated verification plan once. If it passes, update this handoff with the verification result and prepare one PR against `main`. Any deeper Chat duplication cleanup, compatibility-adapter retirement, `ModuleRouteTitle` lifecycle decision or Database work remains a separate caller-proofed phase.