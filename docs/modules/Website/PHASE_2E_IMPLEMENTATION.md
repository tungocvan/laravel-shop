# Website Phase 2E — Zero-Caller Cleanup

## Status

- Slice: `2E — zero-caller cleanup`
- Implementation: `COMPLETE`
- Automated ownership/Website gate: `PASS`
- Manual regression: `PASS — USER VERIFIED`
- Decision: `CLOSED`

## Implemented

- Migrated Website seeders from duplicate Product/Category/Post/Order models to canonical models.
- Migrated remaining cart, affiliate, flash-sale, review and wishlist relationships/callers to canonical Product-owned models.
- Migrated legacy Admin callers to canonical Product, Category, Post, Order and UserAddress models without changing their UI logic.
- Corrected Order-owned AdminAffiliateService so it no longer queried Website Order.
- Removed zero-caller Website duplicates for Product, Category, Post, Order, OrderItem, OrderHistory, UserAddress, Review and Wishlist.
- Removed the obsolete Website Account AddressService after all callers moved to UserAddressService.
- Removed the entire zero-caller `Website/Services/Services` duplicate/cross-domain tree.
- Removed zero-caller Category/Product copies from Product, Post and Order modules.
- Regenerated optimized Composer autoload files successfully.

## Automated Evidence

```text
Website domain ownership + checkout + authorization:
16 PASS / 10,374 assertions

Website + Product + Category + Post + User affected suites:
PASS

Website full suite within combined run:
22 PASS (including cleanup coverage); 2 settings DB checks skipped
```

The cleanup test scans PHP source under `app` and `Modules` and rejects every removed legacy namespace. It also asserts every deleted duplicate file remains absent.

## Unrelated Test Classification

`Tests/Feature/Admin/AdminRouteConfigurationTest` reports eight permission-middleware failures. No Admin route file was changed by Slice 2E; the failures concern the pre-existing Admin route permission contract and are not caused by model ownership migration. They remain an Admin-module gate rather than a Website Phase 2 regression.

Composer also reports existing PSR-4 casing warnings in several module seeders/services. Those paths were not renamed because migration/casing cleanup is outside this slice.

## Manual Test Result

User confirmed the requested storefront, checkout, account, admin and supporting
feature regression checks passed.

## Remaining Phase 2 Items

- Decide whether customer/profile presentation components must physically move from Website or may remain Website-hosted consumers of User services.
- Move the checkout order-creation service class into an Order-owned workflow only after a dependency-safe Cart/Coupon input contract exists.
- Existing Admin route authorization failures require a separate Admin-owned correction.
