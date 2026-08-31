# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Legacy Runtime Consolidation**

Status: **IMPLEMENTED — AWAITING FOCUSED VERIFICATION / UI PASS**

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

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` now locks the consolidated boundary:

- removed Admin runtime trees must stay absent;
- Website presentation routes remain canonical;
- Product/Role/Account route ownership remains outside Admin;
- Admin shell controllers/layout remain present;
- compatibility adapters and Database quarantine remain preserved.

## Schema and data

No schema, migration, foreign-key or production-data change is included.

## Required verification

Before PR readiness, run focused Admin ownership tests plus directly impacted Website/Product/Role/Account regression, route verification and frontend build. Do not run the whole project test suite solely for this cleanup.

Manual UI smoke should cover canonical Admin shell/layout plus the active Website management surfaces affected by removed wrappers, especially homepage settings, coupons, flash sales and affiliate management.

## Acceptance criteria

- Admin shell/layout/dashboard/menu/profile: **PRESERVED**
- Website canonical presentation/promotion routes: **PRESERVED**
- Product/Role/Account specialized ownership: **PRESERVED**
- approved legacy Admin runtime residue: **REMOVED**
- compatibility adapters with unresolved/dynamic callers: **PRESERVED / DEPRECATED**
- Database destructive boundary: **QUARANTINED / UNCHANGED**
- schema/data changes: **NONE**
- focused tests/routes/build/UI: **PENDING USER VERIFICATION**

## Next checkpoint

After focused verification and UI PASS are reported, update this handoff with exact results and prepare the PR. Do not merge before verification is recorded.
