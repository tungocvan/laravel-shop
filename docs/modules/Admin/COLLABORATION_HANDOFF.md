# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Flash Sale Ownership Cleanup**

Status: **VERIFIED — PR READY**

Branch/checkpoint: `refactor/admin-flash-sale-ownership`

This approved slice follows the Website Presentation ownership cleanup. It removes independent Admin ownership of Flash Sale domain behavior while preserving the authenticated Admin management surface and the existing Website-owned route. No schema, migration, production-data, Coupon, Affiliate, or P0 database-administration change is authorized by this slice.

## Ownership decision

Responsibilities are intentionally split:

- `Modules/Website` is the canonical owner of FlashSale/FlashSaleItem models and FlashSaleService behavior;
- `Modules/Product` is the canonical owner of Product and the `wp_products` query boundary;
- `Modules/Admin` retains the Flash Sale Livewire management composition only;
- historical Admin Flash Sale model/service names remain deprecated compatibility adapters until complete external/dynamic caller proof authorizes deletion.

## Runtime changes

`Modules/Admin/Livewire/FlashSale/FlashSaleManager.php` now consumes:

- `Modules/Website/Models/FlashSale`;
- `Modules/Website/Services/FlashSaleService`;
- `Modules/Product/Models/Product`.

The product picker no longer performs raw `DB::table('wp_products')` access. Product discovery/addition now uses the canonical Product model and its active scope.

Edit loading uses Website `FlashSaleService::findWithProducts()` so Flash Sale item/product relations remain inside the canonical domain boundary.

Three historical Admin classes were reduced to deprecated compatibility adapters rather than deleted without complete caller proof:

- `Modules/Admin/Models/FlashSale.php`
- `Modules/Admin/Models/FlashSaleItem.php`
- `Modules/Admin/Services/FlashSaleService.php`

They no longer own independent table metadata, relationships, or Flash Sale CRUD behavior.

## Route boundary retained

The active management route remains unchanged and Website-owned:

```text
GET|HEAD admin/flash-sales  admin.flash-sales  Modules\Website\Http\Controllers\Admin\FlashSaleController@index
```

Admin Livewire remains a management surface rendered from that boundary; route ownership was not moved into Admin.

## Explicitly out of scope

- Coupon runtime/ownership changes;
- Affiliate commission/rank/scheme ownership;
- broader promotion architecture redesign;
- schema or migration movement;
- production data transformation;
- deletion of compatibility adapters without caller proof;
- environment/system ownership cleanup;
- P0 database administration redesign.

`Modules/Admin/Services/DatabaseService.php` remains quarantined and untouched.

## Verification completed

```text
AdminFlashSaleOwnershipContractTest + AdminOwnershipBoundaryContractTest: 8 passed, 55 assertions
admin.flash-sales route: PASS — Website-owned /admin/flash-sales route preserved
Manual Flash Sale management smoke including product picker: UI PASS
```

The focused ownership contract protects:

- Website ownership of Flash Sale model/service behavior;
- Product ownership of product querying rather than raw Admin table access;
- absence of independent persistence/business logic in retained Admin Flash Sale compatibility classes;
- continued exclusion of Coupon/Affiliate from this slice;
- continued P0 `DatabaseService` quarantine.

No full-project regression was required for this ownership-only slice.

## Acceptance criteria

- canonical FlashSale/FlashSaleItem model owner: **Website — VERIFIED**;
- canonical FlashSale service owner: **Website — VERIFIED**;
- canonical product-query owner: **Product — VERIFIED**;
- Admin management surface preserved: **VERIFIED**;
- active `admin.flash-sales` route preserved: **VERIFIED**;
- raw Admin `wp_products` query removed from FlashSaleManager: **VERIFIED**;
- legacy Admin Flash Sale classes retain independent domain logic: **NO**;
- compatibility adapters removed without complete caller proof: **NO**;
- schema/migration/data changes: **NONE**;
- Coupon/Affiliate changes: **NONE**;
- manual UI: **PASS**;
- focused Admin ownership regression: **PASS — 8 tests / 55 assertions**;
- P0 database quarantine: **UNCHANGED**;
- PR readiness: **READY**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Compatibility debt

The three deprecated Admin Flash Sale compatibility classes remain until repository/runtime caller proof is strong enough to authorize deletion. Their presence is compatibility debt, not canonical ownership.

The five deprecated Admin Banner/Header compatibility classes from the prior slice remain under the same deletion guardrail.

### Remaining Admin legacy families

Affiliate commission/rank/scheme ownership requires a dedicated architectural slice because current behavior spans Website, Order, Product and shared User concerns.

Coupon is currently aligned with Website domain ownership through the Admin management surface and does not require an ownership migration merely to group it with promotions.

Environment/system remains a separate ownership/reachability candidate.

## Next phase

Flash Sale ownership cleanup is closed out and PR-ready. Do not select or implement the next Admin legacy family until this branch is merged and the user explicitly authorizes the next scope.
