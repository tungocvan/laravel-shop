# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Banner Legacy Runtime Tree Cleanup**

Status: **IMPLEMENTED — FOCUSED VERIFICATION PASS / UI PASS / PR READY**

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

`tests/Feature/Admin/AdminWebsitePresentationOwnershipContractTest.php` asserts that the four legacy Admin Banner runtime artifacts stay absent, Admin routes do not regain `BannerController`, and the canonical Website controller/view path remains present.

Existing Header/Footer, Flash Sale, Coupon, Affiliate, Database and compatibility-adapter assertions remain in place and are outside this deletion slice.

## Schema and data decision

No schema, migration, foreign-key or production-data change is authorized or included.

## Verification

Local focused verification reported PASS:

```text
AdminWebsitePresentationOwnershipContractTest: 6 passed, 57 assertions
AdminOwnershipBoundaryContractTest: 4 passed, 21 assertions
Total: 10 passed, 78 assertions
```

Route verification reported:

```text
GET|HEAD admin/banners admin.banners › Modules\Website\Http\Controllers\Admin\BannerController@index
```

Manual Banner UI smoke: **PASS**. The active Website-owned Banner management screen and tested UI behavior remain operational.

Working tree after verification: **clean** at implementation checkpoint `054646ed` before this documentation closeout.

## Acceptance criteria

- canonical Banner runtime owner: **Website — VERIFIED**;
- legacy Admin Banner controller/view/Livewire runtime tree: **REMOVED**;
- Admin Banner model/service compatibility adapters: **PRESERVED**;
- Admin shell controller boundary: **PRESERVED**;
- Website Banner runtime/business behavior: **UNCHANGED**;
- schema/migration/data changes: **NONE**;
- focused regression: **PASS — 10 tests / 78 assertions**;
- canonical Banner route: **PASS — Website controller-owned**;
- manual Banner UI smoke: **PASS**.

## Remaining compatibility debt

Separate scopes still remain for Header/Footer, Flash Sale, Coupon, Affiliate/Order residue, historical Admin controllers/scaffolds, environment/System adapters, and quarantined Database surfaces.

Deprecated Banner model/service adapters remain until complete dynamic/external caller proof authorizes their removal. GitHub code-search zero results are not accepted as sufficient caller proof.

## Next phase

Open and merge this Banner legacy runtime cleanup as a focused PR. Do not begin another Admin legacy family until this branch is merged and the next scope is explicitly approved.

After merge, resume Compatibility Debt Audit and choose exactly one coherent next legacy runtime family using route -> controller -> view -> Livewire reachability proof.
