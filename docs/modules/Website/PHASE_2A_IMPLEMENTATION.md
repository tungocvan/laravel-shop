# Website Phase 2A — Product + Category Read Ownership

## Status

- Slice: `2A — Product + Category read contracts`
- Implementation: `COMPLETE`
- Automated tests: `PASS`
- Manual storefront smoke: `PASS — user verified`
- Approval: `APPROVED`
- Decision: `CLOSED`

## Implemented

- Added the recursive tree, root alias, sorted scope and descendant-ID compatibility contracts required by Website to canonical `Modules/Category/Models/Category`.
- Migrated Website product listing/detail and category filter reads to canonical Product/Category models.
- Migrated homepage new-arrival, best-seller, featured-product and category-highlight reads.
- Migrated Website ProductService and CategoryService read queries.
- Removed direct `DB::table('categories')` and `DB::table('wp_products')` reads from homepage settings composition; it now uses canonical models.
- Migrated read-only dashboard product count, affiliate product lookup and wishlist product listing.
- Preserved Website checkout/cart stock mutation models for the separately tested Order slice.
- Did not delete duplicate Website models; seeders, relationships and transactional callers still exist.

## Compatibility Boundary

Both old and canonical models currently address the same `wp_products` and `categories` tables. This slice changes ownership namespaces/query contracts only and introduces no database migration.

The following remain intentionally unmigrated:

- CheckoutService and CartService product mutation paths.
- AddToCart and affiliate commission mutation paths.
- Product relationships embedded in Website FlashSale/Affiliate models.
- Post-category callers, reserved for Slice 2B.
- Seeder references, to be handled before zero-caller cleanup.

## Test Gate

- Canonical namespace usage configuration test.
- Canonical Product/Category table, relationship and scope contract test.
- Explicit assertion that checkout remains on the Phase 1A model path.
- Phase 1A checkout regression.
- Phase 1B authorization regression.
- Full Website regression.

## Manual Test Required

- Product listing: search, category, price and sort filters.
- Product detail and related products.
- Homepage category, featured, new-arrival and best-seller sections.
- Homepage admin category/product selectors.
- Wishlist product listing.
