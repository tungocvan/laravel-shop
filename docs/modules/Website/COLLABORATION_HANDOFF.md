# Website Collaboration Handoff

## Current status

The approved Website Major/Clean Module Refactor is **CLOSED** for the currently authorized scope.

Website Batch 1 ownership cleanup, System-owned settings consolidation, and the remaining-domain convergence are all merged to `main`.

Latest completed PR:

- PR `#121` — `refactor(website): converge remaining domain boundaries`;
- merged to `main` on 2026-09-01;
- merge commit: `06981d73619b6499ac82884facd2979ebc19abb3`;
- implementation head: `04fc6fddc8127fa48766b4c56082064296daca7d`.

No new Website implementation objective is currently authorized.

## Completed settings consolidation

PR `#119` consolidated Website settings persistence into the System-owned canonical `settings` table.

Canonical ownership:

- `Modules\System\Models\Setting` is the canonical model;
- `Modules\System\Services\SettingsService` reads/writes canonical `settings`;
- Website does not own independent settings persistence;
- `Modules\Website\Models\Setting` is only a deprecated compatibility adapter;
- known production runtime settings paths no longer read/write `wp_settings`.

The additive consolidation migration verified:

- canonical `settings`: `73` rows;
- legacy `wp_settings`: `30` rows retained as safety copy;
- canonical `header.topbar.help_url`: `/`;
- legacy `header.topbar.help_url`: `/help`;
- legacy keys missing from canonical settings: `0`.

`wp_settings` physical removal remains **DEFERRED / NOT AUTHORIZED**.

## Completed remaining-domain convergence — PR #121

### Checkout / Order — CLEANED

- Order is the canonical checkout/order business owner through Order services/contracts.
- Website retains storefront checkout presentation and `WebsiteCheckoutContext`.
- Existing MoMo callback/IPN route contracts remain unchanged.
- MoMo/payment integration remains quarantined because rehome is public-contract/config sensitive.

### Affiliate — CLEANED

Order is the canonical affiliate business owner:

- `Modules\Order\Services\AffiliateService`;
- `Modules\Order\Services\AdminAffiliateService`;
- `Modules\Order\Services\AffiliateRankService`;
- Order-owned `AffiliateLevel` and `AffiliateScheme` models.

Removed Website duplicate affiliate services/models. Website retains affiliate presentation and storefront referral-cookie attribution adapters.

### Wishlist — CLEANED

Product is the canonical Wishlist owner:

- `Modules\Product\Models\Wishlist`;
- `Modules\Product\Services\WishlistService`.

The Website duplicate WishlistService was removed. Website retains Wishlist presentation and view-sharing middleware adapters.

### Review — CLEANED runtime / historical migration KEEP

- Product owns the runtime Review model.
- Website has no duplicate runtime Review model.
- Historical Website review migration remains untouched as migration-ledger history.

### Customer / Account — CLEANED

- User owns current customer CRUD/query, address and profile runtime behavior.
- Website retains storefront/admin customer presentation.
- Account remains account/identity/profile-enrichment/import context and is not forced to own User customer CRUD.
- Customer pagination is bounded to `10 / 25 / 50 / 100`; legacy `all => 9999` behavior was removed.
- stale Website customer controller permission aliases were removed while canonical route/Livewire authorization remains.

### Website route cleanup — CLEANED

Removed the unused Website route-prefix variable without changing public/admin route URLs or route names.

## Final ownership classifications

- Website shell / Home / Header / Footer / appearance / theme: `KEEP`.
- Banner: `KEEP`.
- Product/Post storefront rendering: `KEEP` as Website presentation over canonical Product/Post domains.
- Settings: `CLEANED`, canonical owner System.
- Checkout workflow: `CLEANED`, canonical owner Order.
- Affiliate: `CLEANED`, canonical business owner Order.
- Customer/profile/address runtime: `CLEANED`, canonical owner User with Website presentation adapters.
- Wishlist: `CLEANED`, canonical owner Product.
- Review: `CLEANED` runtime ownership; historical migration `KEEP`.
- MoMo/payment integration: `QUARANTINE / DEFER`.
- Cart: `QUARANTINE`.
- Coupon / FlashSale: `QUARANTINE`.
- Newsletter: `KEEP / DEFER` until a canonical marketing owner exists.
- Tag: `QUARANTINE` pending stronger caller/ownership proof.
- legacy `wp_settings`: `QUARANTINE / DEFERRED`, physical removal not authorized.

## Verification closeout

PR #121 was verified before merge with:

- targeted regression: **166 passed (16349 assertions)** in **12.85s** across Website, Order, Product and User feature suites;
- Pint changed-files gate against current `origin/main`: **PASS**;
- route sanity: **PASS** for checkout, MoMo callback/IPN, affiliate, wishlist and admin customer routes;
- UI smoke: **PASS** for `/checkout`, `/account/affiliate`, `/account/wishlist`, `/admin/affiliate`, `/admin/customers`.

No destructive schema changes or migration-ledger rewrites were included.

## Ownership regression guard

`tests/Feature/Website/WebsiteDomainOwnershipConfigurationTest.php` protects the converged ownership boundaries, including:

- canonical Product/Category/Post/Order/User dependencies;
- Order-owned checkout and affiliate business logic;
- Product-owned Wishlist business logic;
- absence of removed Website Affiliate/Wishlist duplicate namespaces;
- Website presentation adapters depending on canonical owner services rather than recreating domain ownership.

## Deferred debt and safety gates

### `wp_settings`

Do not physically remove `wp_settings` until a separately approved destructive-removal phase proves all of the following:

1. repository-wide production caller proof;
2. production data parity/no new legacy-only writes;
3. sufficient deployment observation for hidden jobs/scripts/admin flows;
4. rollback no longer depends on the legacy safety copy;
5. explicit user approval for the destructive migration/PR.

### Cart / Coupon / FlashSale / Tag / MoMo

These are not classified `DEAD`. They remain active or insufficiently proven and therefore require a future objective plus caller/schema/authz/public-contract proof before rehome or destructive cleanup.

Do not create speculative Cart/Promotion modules solely for architectural symmetry.

## Next objective

`NOT DETERMINED`.

A future Website change must begin from a newly stated objective and must not treat this handoff as authorization for additional destructive cleanup.
