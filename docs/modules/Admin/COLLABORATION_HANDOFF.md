# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Legacy Runtime Consolidation**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PASS / ROUTES PASS / BUILD PASS / UI PASS / PR READY**

Branch: `refactor/admin-home-settings-legacy-wrapper-cleanup`

The approved batch consolidates historical Admin runtime residue after specialized modules became canonical owners. `Modules/Admin` remains the authenticated Admin shell; this change does not move business ownership back into Admin.

## Canonical Admin boundary

`Modules/Admin/routes/web.php` remains the primary runtime allow-list for Admin-owned presentation. Canonical Admin controllers remain `AdminController`, `DashboardController`, `MenuController`, and `ProfileController`, with Admin shell/layout, dashboard, menu management and profile behavior preserved.

`/admin/layout/header`, `/admin/layout/sidebar`, `/admin/layout/footer`, `/admin/layout/design` and related layout sections remain Admin shell configuration. `/admin/menus` remains a separate menu-management subsystem and is not merged into sidebar configuration.

## Removed legacy runtime residue

The batch removes historical Admin controllers for Affiliate, Coupon, Flash Sale, Home Settings, Env Config and the unused Admin API stub; legacy Admin Flash Sale/Coupon presentation components; historical Product/Role/Staff wrapper pages; and historical Admin Role/Staff Livewire components.

Website remains canonical for homepage/promotion/presentation management. Product, Role and Account retain their own route ownership. Env configuration remains System-owned.

## Preserved compatibility and quarantine

This cleanup intentionally preserves deprecated compatibility adapters where caller proof is incomplete or concrete callers remain, including Banner/HeaderMenu, Flash Sale and Affiliate compatibility boundaries. Affiliate compatibility Livewire aliases remain. Orders are not classified as dead by this batch.

`Modules/Admin/Services/DatabaseService.php` and the existing Database containment boundary remain quarantined and are not reactivated, moved or deleted.

Auth is also outside deletion scope unless its independent runtime chain is separately proven obsolete.

## Contract protection

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` locks the consolidated boundary:

- removed Admin runtime trees must stay absent;
- Website presentation routes remain canonical, including grouped Coupon routes;
- Product/Role/Account route ownership remains outside Admin;
- Admin shell controllers/layout remain present;
- compatibility adapters and Database quarantine remain preserved.

## Schema and data

No schema, migration, foreign-key or production-data change is included.

## Verification

Focused verification reported **PASS** after correcting the contract assertion for Website's grouped `Route::prefix('coupons')` declaration.

The focused Admin ownership test set passed:

- `tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php`
- `tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php`

Route verification reported **PASS**. The active route table confirms canonical ownership after cleanup, including:

- Admin shell/layout/menu/profile -> `Modules\\Admin`
- `/admin/login` and `/admin/logout` -> `Modules\\Auth`
- `/admin/homepage-settings`, `/admin/coupons*`, `/admin/flash-sales`, `/admin/affiliate`, Banner/Header/Footer settings -> `Modules\\Website`
- `/admin/products*` -> `Modules\\Product`
- `/admin/posts*` -> `Modules\\Post`
- `/admin/category*` -> `Modules\\Category`
- `/admin/roles*` -> `Modules\\Role`
- `/admin/accounts*` -> `Modules\\Account`
- `/admin/system/settings/env` -> `Modules\\System`

Frontend verification: `npm run build` **PASS** with Vite v7.3.6.

Manual UI smoke reported **PASS** for the agreed Admin shell and canonical Website management surfaces, including layout/menu/profile, homepage settings, coupons, flash sales and affiliate management.

No full-project regression suite was required for this ownership cleanup.

## Acceptance criteria

- Admin shell/layout/dashboard/menu/profile: **PRESERVED / PASS**
- Website canonical presentation/promotion routes: **PRESERVED / PASS**
- Product/Role/Account specialized ownership: **PRESERVED / PASS**
- approved legacy Admin runtime residue: **REMOVED**
- compatibility adapters with unresolved/dynamic callers: **PRESERVED / DEPRECATED**
- Database destructive boundary: **QUARANTINED / UNCHANGED**
- schema/data changes: **NONE**
- focused tests: **PASS**
- route ownership verification: **PASS**
- frontend build: **PASS**
- manual UI smoke: **PASS**

## Next checkpoint

Open the Legacy Runtime Consolidation PR against `main` and review it before merge. After merge, sync `main` and treat any remaining compatibility adapters or quarantined families as separate, caller-proofed follow-up work rather than reopening this batch.
