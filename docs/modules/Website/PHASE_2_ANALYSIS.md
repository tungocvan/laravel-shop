# Website Phase 2 — Domain Ownership Analysis

## Status

- Phase: `2 — Domain Ownership`
- Analysis: `COMPLETE`
- Implementation: `NOT STARTED`
- Tests: `NOT STARTED`
- Decision: `READY FOR IMPLEMENTATION APPROVAL`
- Previous phase: `Phase 1 — CLOSED / TESTED / APPROVED`

## Scope

Phase 2 establishes one canonical owner for Product, Category, Post, Order and User/Account concepts consumed by Website. It does not redesign tables, rewrite migrations, rebuild UI, or begin the Phase 3 schema work.

## Current Ownership Map

| Concept | Canonical owner | Current Website duplicate | Shared table | Readiness |
|---|---|---|---|---|
| Product | `Modules/Product` | `Website/Models/WpProduct` and Website Product services | `wp_products` | High after relationship tests |
| Category | `Modules/Category` | `Website/Models/Category`; additional copies in Product/Post | `categories` | Medium; relationship/scopes differ |
| Post | `Modules/Post` | `Website/Models/Post`, ContentService | `wp_posts` | High after storefront tests |
| Order | `Modules/Order` | `Website/Models/Order*`, checkout/account/affiliate callers | `wp_orders`, order item/history tables | Medium; Phase 1A semantics must first be ported |
| User identity | `App/Models/User` backed by User/Account modules | Website customer/account UI | `users` | Medium |
| User address | Account/User owner to be completed | `Website/Models/UserAddress` and AddressService | `user_addresses` | Low; no equivalent canonical model found |
| Wishlist | Product/Account boundary requires explicit decision | Website model; Product model currently imports it | existing wishlist table | Medium-Low |

## Caller Inventory

### Product

Website Product callers exist in:

- storefront ProductList/ProductDetail/CategoryFilter;
- homepage NewArrivals/BestSellers/FeaturedProducts;
- cart, checkout and wishlist services/components;
- dashboard and affiliate administration;
- flash-sale and affiliate relationships;
- Website seeders.

`Modules/Product/Models/Product` uses the same `wp_products` table and already has stronger scalar casts. It still imports Website Wishlist, so deleting the Website model is not safe during the first slice.

### Category

Category is duplicated in Website, Product and Post, while `Modules/Category/Models/Category` is the intended canonical owner. Website depends on recursive children, post/product relations and type-specific scopes that are not all exposed with identical names by the canonical model.

The direct `DB::table('categories')` query in homepage administration must move to a Category-owned query/service contract during the Category slice, not via a schema change.

### Post

Website blog and homepage highlight use `Website/Models/Post`. `Modules/Post/Models/Post` maps to the same `wp_posts` table and has compatible publishing fields. The canonical Post model is suitable after verifying category/tag relations, frontend query shapes and view accessors.

### Order

Website checkout, MoMo callback, account orders, dashboard and affiliate flows use Website Order models. Canonical Order models map to the same tables, but currently differ from the Phase 1A compatibility contract:

- canonical status badge does not explicitly represent `pending_payment`;
- canonical payment label still references VNPAY and lacks MoMo;
- canonical OrderItem resolves `Modules/Order/Models/Product`, another duplicate product model;
- checkout creation/locking/idempotency remains implemented by Website CheckoutService.

Order migration must first port these compatibility semantics and add focused contract tests. A direct namespace replacement before that would be a regression risk.

### User / Account

Authentication uses `App/Models/User`. Account and User modules exist, but no production-ready canonical equivalent for Website `UserAddress` was identified. Customer administration and address/profile workflows must not be moved until the owner exposes equivalent models/services and authorization remains unchanged.

### System / Auth / Chat

No external production callers were found for Website's nested `Services/Services` system/auth/chat/env implementations during this inventory. They remain deletion candidates only after a zero-caller test/static gate; deletion itself should be the final cleanup slice, not mixed into domain migration.

## Locked Implementation Sequence

### Slice 2A — Product + Category read contracts

1. Add/complete canonical Product and Category relationships/scopes required by storefront.
2. Add compatibility tests comparing table, casts, relationships and query results.
3. Migrate Website read-only product/category callers and homepage admin category query.
4. Keep checkout stock mutation on its tested path until Order slice.
5. Do not delete duplicate models yet.

### Slice 2B — Post presentation migration

1. Protect blog list/detail and homepage highlight with focused tests.
2. Migrate Website Post/ContentService callers to Post-owned model/service contracts.
3. Keep routes and Blade contracts stable.
4. Do not delete Website Post until seeders and every caller reach zero.

### Slice 2C — Order compatibility and workflow ownership

1. Port `pending_payment`, COD/bank-transfer/MoMo labels and required relationships to canonical Order models.
2. Make canonical OrderItem reference canonical Product.
3. Introduce an Order-owned checkout/order creation contract preserving Phase 1A locking, atomicity and idempotency.
4. Migrate callback, checkout, account, dashboard and affiliate callers incrementally.
5. Run the complete checkout regression after every step.

### Slice 2D — User/account/address ownership

1. Declare `App/Models/User` as the runtime identity contract used by User/Account.
2. Add a canonical address model/service under the approved Account/User owner using the existing table.
3. Migrate Website profile/address and customer admin callers without changing permissions or routes.

### Slice 2E — Zero-caller cleanup

1. Prove duplicate Website models/services have no runtime, seeder or relationship callers.
2. Remove only zero-caller duplicates.
3. Re-run all Website and affected canonical-module tests.

## Test Gate

- Product list/detail and homepage product/category sections unchanged.
- Blog list/detail and homepage highlight unchanged.
- Cart behavior unchanged.
- Phase 1A checkout/payment suite remains green, including MoMo compatibility.
- Account order list/detail unchanged.
- Admin permissions from Phase 1B remain green.
- Phase 1C settings/cache behavior remains green.
- Static zero-caller assertions precede every duplicate deletion.

## Explicit Non-Goals

- No database/table redesign or migration rewrite.
- No Product/Order/Post UI rebuild.
- No route rename.
- No permission redesign.
- No deletion based only on similar class names.
- No Phase 3 schema work.

## Decision

**Recommended: implement Phase 2 as five independently tested slices, beginning with Product + Category read contracts.**

The analysis gate is complete. Production implementation should start only after approval of this locked sequence.
