# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Banner Legacy Runtime Tree Cleanup**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PENDING**

Branch/checkpoint: `refactor/admin-banner-legacy-runtime-cleanup`

This approved slice removes the proven obsolete Admin Banner controller/view/Livewire runtime tree after caller proof established the active Banner management path in `Modules/Website`.

## Ownership decision

- `Modules/Admin` remains the authenticated shell. Its canonical route-controller surface is limited to `AdminController`, `DashboardController`, `MenuController`, and `ProfileController` for the current architecture.
- `Modules/Website` owns the active Banner management route, controller, wrapper view, Livewire component, model and service behavior.
- Historical `Modules/Admin/Models/Banner.php` and `Modules/Admin/Services/BannerService.php` remain deprecated compatibility adapters and are intentionally outside this runtime-tree deletion slice.

## Runtime cleanup

The following obsolete Admin Banner runtime artifacts were removed:

- `Modules/Admin/Http/Controllers/BannerController.php`;
- `Modules/Admin/Livewire/Banner/BannerManager.php`;
- `Modules/Admin/resources/views/pages/banner/index.blade.php`;
- `Modules/Admin/resources/views/livewire/banner/banner-manager.blade.php`.

The canonical Website path remains unchanged:

`Website route -> Website Admin BannerController -> Website::pages.admin.banner.index -> website.admin.banner.banner-manager`.

No Website Banner business behavior was redesigned.

## Guardrail

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` now asserts that the four legacy Admin Banner runtime artifacts stay absent, Admin routes do not regain `BannerController`, and the canonical Website controller/view path remains present.

Existing Header/Footer, Flash Sale, Coupon, Affiliate, Database and compatibility-adapter assertions remain in place and are outside this deletion slice.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

## Verification

Focused verification is pending local execution after pulling the implementation commits.

Recommended commands:

```bash
php artisan test tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php
php artisan test tests/Feature/Admin/AdminOwnershipBoundaryContractTest.php
php artisan route:list --path=admin/banner
```

Manual Banner UI smoke should verify the active Website-owned Banner management screen still loads and mutations behave normally.

## Acceptance criteria

- canonical Banner runtime owner: **Website — VERIFIED by route/controller/view/Livewire proof**;
- legacy Admin Banner controller/view/Livewire runtime tree: **REMOVED**;
- Admin Banner model/service compatibility adapters: **PRESERVED**;
- Admin shell controller boundary: **PRESERVED**;
- Website Banner runtime/business behavior: **UNCHANGED**;
- schema/migration/data changes: **NONE**;
- focused regression: **PENDING LOCAL VERIFICATION**;
- manual Banner UI smoke: **PENDING**.

## Remaining compatibility debt

Separate scopes still remain for Header/Footer, Flash Sale, Coupon, Affiliate/Order residue, historical Admin controllers/scaffolds, environment/System adapters, and quarantined Database surfaces.

Deprecated Banner model/service adapters remain until complete dynamic/external caller proof authorizes their removal. GitHub code-search zero results are not accepted as sufficient caller proof.

## Next phase

Complete focused verification and Banner UI smoke for this branch. Do not begin another Admin legacy family until this slice is verified, documented, merged, and the next scope is explicitly approved.
