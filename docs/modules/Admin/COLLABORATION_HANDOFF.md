# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Website Presentation Ownership Cleanup**

Status: **VERIFIED — PR READY**

Branch/checkpoint: `refactor/admin-website-presentation-ownership`

This slice was explicitly approved after Role / Staff / Admin Identity ownership cleanup merged. It separates public Website presentation ownership from the canonical Admin shell and moves the active Banner/public-header management dependencies onto the Website domain without changing routes, schema, migrations, or production data.

## Ownership decision

Responsibilities are intentionally split:

- `Modules/Website` is the canonical owner of Banner and public HeaderMenu/HeaderMenuItem models and services;
- `Modules/Admin` may retain authenticated management surfaces that compose Website-owned presentation behavior;
- Admin shell layout/header/footer/design/navigation remains canonical Admin ownership;
- shared header/footer textual settings may continue through `Modules/System/Services/SettingsService` where already established;
- Footer columns/social links already use Website models/services and required no ownership rewrite.

## Runtime changes

The surviving Admin Website-management components now depend on Website ownership:

- `Modules/Admin/Livewire/Banner/BannerManager.php` uses `Modules/Website/Services/BannerService` and Website Banner model behavior;
- `Modules/Admin/Livewire/Header/MenuManager.php` uses Website HeaderMenu/HeaderMenuItem models and `Modules/Website/Services/HeaderMenuService`;
- `Modules/Admin/Livewire/Header/HeaderSettingsHub.php` uses Website HeaderMenuItem and HeaderMenuService while retaining shared `SettingsService` for header settings.

Five historical Admin classes were reduced to deprecated compatibility adapters rather than deleted without complete external/dynamic caller proof:

- `Modules/Admin/Models/Banner.php`
- `Modules/Admin/Models/HeaderMenu.php`
- `Modules/Admin/Models/HeaderMenuItem.php`
- `Modules/Admin/Services/BannerService.php`
- `Modules/Admin/Services/HeaderMenuService.php`

They no longer own independent persistence/service logic; canonical behavior resolves through `Modules/Website`.

## Admin shell boundary retained

`/admin/admin-header` remains the authenticated Admin management surface for Website presentation.

The Admin shell layout routes remain distinct and unchanged, including:

- `/admin/layout/header`
- `/admin/layout/footer`
- `/admin/layout/general`
- `/admin/layout/sidebar`
- `/admin/layout/design`
- `/admin/layout/navigation`

No Admin shell layout service/model was moved into Website.

## Explicitly out of scope

- Coupon/FlashSale/Affiliate/promotion ownership;
- environment/system ownership cleanup;
- schema or migration movement;
- production data transformation;
- removal of compatibility adapters without caller proof;
- P0 database administration redesign.

`Modules/Admin/Services/DatabaseService.php` remains quarantined and untouched.

## Verification completed

```text
AdminWebsitePresentationOwnershipContractTest + AdminOwnershipBoundaryContractTest: 10 passed, 70 assertions
admin.header route list: PASS — /admin/admin-header plus Website-owned /admin/header-settings
admin.layout route list: PASS — 7 canonical Admin shell layout routes
Manual /admin/admin-header + /admin/layout/header + /admin/layout/footer smoke: UI PASS
```

The focused ownership contract protects:

- Website ownership of Banner and public HeaderMenu runtime;
- absence of independent persistence logic in the retained Admin compatibility classes;
- Footer's already-correct Website/shared-settings boundaries;
- separation between Admin shell layout and Website presentation management;
- continued exclusion of Coupon/FlashSale/Affiliate from this slice;
- continued P0 `DatabaseService` quarantine.

No full-project regression was required for this ownership-only slice.

## Acceptance criteria

- canonical Banner model/service owner: **Website — VERIFIED**;
- canonical public HeaderMenu/HeaderMenuItem model/service owner: **Website — VERIFIED**;
- Admin management surfaces preserved: **VERIFIED**;
- Admin shell layout ownership preserved: **VERIFIED**;
- Footer ownership rewrite required: **NO**;
- legacy Admin presentation classes retain independent domain logic: **NO**;
- compatibility adapters removed without complete caller proof: **NO**;
- schema/migration/data changes: **NONE**;
- Coupon/FlashSale/Affiliate changes: **NONE**;
- manual UI: **PASS**;
- focused Admin ownership regression: **PASS — 10 tests / 70 assertions**;
- P0 database quarantine: **UNCHANGED**;
- PR readiness: **READY**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Compatibility debt

The five deprecated Admin Banner/Header compatibility classes remain until repository/runtime caller proof is strong enough to authorize deletion. Their presence is compatibility debt, not canonical ownership.

### Remaining Admin legacy families

Affiliate/FlashSale/Coupon/promotion and environment/system remain separate ownership/reachability candidates.

Production migration-ledger/table ownership for unrelated Admin legacy families remains unresolved and out of scope.

## Next phase

Website Presentation ownership cleanup is closed out and PR-ready. Do not select or implement the next Admin legacy family until this branch is merged and the user explicitly authorizes the next scope.
