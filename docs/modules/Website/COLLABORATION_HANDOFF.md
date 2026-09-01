# Website Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Website`.

Website Batch 1 ownership cleanup and the persistence-safe consolidation of legacy `wp_settings` into canonical System-owned `settings` are merged to `main`.

The current implementation branch is `refactor/website-remaining-domain-boundaries`. It performs the approved remaining-domain convergence without destructive schema work.

## Completed persistence consolidation

PR `#119` — `refactor(settings): consolidate Website settings into System persistence` — was merged to `main`.

Merge commit: `a95aa215634262d06dc71144469ca9e16b00492b`.

Canonical ownership is now:

- `Modules\System\Models\Setting` is the canonical model;
- `Modules\System\Services\SettingsService` reads/writes only `settings`;
- Website does not own an independent settings persistence table;
- `Modules\Website\Models\Setting` remains only as a deprecated compatibility adapter extending the System model;
- production runtime no longer reads or writes `wp_settings` through the known System/Website settings paths;
- legacy cache aliases may still be invalidated temporarily to avoid stale values across deployments.

## Database and migration proof

Before implementation, the user supplied database proof showing:

- canonical `settings`: 55 rows;
- legacy `wp_settings`: 30 rows;
- 12 duplicate keys;
- 11 duplicate values matched;
- one real conflict existed at `header.topbar.help_url` (`settings=/`, `wp_settings=/help`).

The approved conflict rule was: canonical `settings` wins and legacy rows may only fill missing keys.

Migration `2026_09_01_000001_consolidate_wp_settings_into_settings` completed successfully and verified:

- canonical `settings`: `73` rows;
- legacy `wp_settings`: remains `30` rows;
- canonical `header.topbar.help_url`: `/`;
- legacy `header.topbar.help_url`: `/help`;
- legacy keys missing from canonical settings: `0`.

The migration is additive and non-destructive. It does not drop, rename or truncate `wp_settings`, does not rewrite migration history, and its `down()` is intentionally non-destructive.

## Remaining-domain convergence implemented on current branch

### Checkout / Order — CLEANED boundary

- Order remains the canonical checkout/order workflow owner through `Modules\Order\Services\CheckoutService` and Order-owned contracts.
- Website keeps the storefront `CheckoutController`, checkout Livewire presentation and `WebsiteCheckoutContext` adapter.
- Website does not own a duplicate CheckoutService.
- Existing MoMo callback/IPN route contracts are preserved.
- Payment/MoMo integration is not mechanically moved while config/public callback compatibility remains schema/config sensitive.

### Affiliate — CLEANED runtime ownership

Canonical affiliate business ownership is now Order:

- `Modules\Order\Services\AffiliateService` owns commission calculation, affiliate order statistics/history/detail and hybrid commission behavior;
- `Modules\Order\Services\AdminAffiliateService` owns admin affiliate query/business behavior;
- `Modules\Order\Services\AffiliateRankService` owns affiliate rank calculation;
- Order-owned `AffiliateLevel` and `AffiliateScheme` models are canonical.

Removed Website duplicates:

- `Modules/Website/Services/AffiliateService.php`;
- `Modules/Website/Services/AdminAffiliateService.php`;
- `Modules/Website/Services/AffiliateRankService.php`;
- `Modules/Website/Models/AffiliateLevel.php`;
- `Modules/Website/Models/AffiliateScheme.php`.

Website retains presentation and storefront attribution concerns:

- affiliate account/admin Livewire UI remains Website presentation;
- `TrackAffiliate` remains Website HTTP middleware because it captures `?ref=` and stores the storefront referral cookie;
- `WebsiteCheckoutContext` reads the referral cookie and prevents self-referral, then delegates commission business logic to Order.

### Wishlist — CLEANED runtime ownership

Product is the canonical Wishlist owner:

- canonical model: `Modules\Product\Models\Wishlist`;
- canonical service: `Modules\Product\Services\WishlistService`.

Removed:

- `Modules/Website/Services/WishlistService.php`.

Website keeps account Wishlist presentation and `ShareWishlistData` middleware, both of which now depend on Product's canonical WishlistService.

No wishlist schema/migration was moved or rewritten.

### Review — CLEANED runtime ownership / historical schema KEEP

- runtime Review model is Product-owned;
- Website has no duplicate Review runtime model;
- the historical Website migration that created the reviews table remains untouched because migration ledger history must not be rewritten.

### Customer / Account — CLEANED boundary

Caller proof established that current customer CRUD/address runtime ownership is User, not Website:

- `Modules\User\Services\CustomerService` is canonical for customer CRUD/query behavior;
- `Modules\User\Services\UserAddressService` is canonical for address behavior;
- `Modules\User\Services\UserProfileService` owns profile update behavior;
- Website retains storefront/admin customer presentation adapters.

Customer table pagination is bounded to `10 / 25 / 50 / 100`; legacy backend `all => 9999` behavior was removed in accordance with `ADMIN_UI_STANDARD.md`.

Stale controller constructor permissions (`view_customer`, `create_customer`, `edit_customer`, `delete_customer`) were removed. Admin customer routes retain canonical permission middleware such as `customer.view` and `customer.create`, while mutating Livewire components retain their own canonical authorization checks.

### Website route cleanup — CLEANED

The unused `$websitePrefix = config('website.route_prefix', 'website')` route variable was removed because it did not participate in route construction. Existing public/admin route URLs and route names remain unchanged.

## Remaining classifications after convergence

- Website shell / Home / Header / Footer / appearance / theme: `KEEP` — canonical Website presentation ownership.
- Banner: `KEEP` — Website presentation/content placement.
- Product/Post storefront rendering: `KEEP` — Website presentation over Product/Post canonical domain models/services.
- Settings: `CLEANED` — System canonical persistence; legacy `wp_settings` remains deferred safety copy.
- Checkout workflow: `CLEANED` — Order canonical business owner; Website storefront adapter retained.
- MoMo/payment integration: `QUARANTINE` — active and public-contract/config sensitive; no mechanical rehome in this batch.
- Affiliate: `CLEANED` — Order canonical business owner; Website presentation/referral adapter retained.
- Customer/profile/address: `CLEANED` — User canonical runtime owner; Website presentation retained. Account remains identity/profile-enrichment/import context and is not forced to own User customer CRUD.
- Wishlist: `CLEANED` — Product canonical owner; Website presentation retained.
- Review: `CLEANED` runtime ownership / historical migration `KEEP`.
- Cart: `QUARANTINE` — active Website storefront infrastructure; no independent canonical Cart module proven and no new module created for architectural aesthetics.
- Coupon / FlashSale: `QUARANTINE` — active promotion behavior without a proven canonical Promotion module; no schema move.
- Newsletter: `KEEP / DEFER` — Website lead-capture persistence remains valid until another canonical marketing owner exists.
- Tag: `QUARANTINE` — caller/ownership proof insufficient for destructive cleanup.

## Ownership regression guards

`tests/Feature/Website/WebsiteDomainOwnershipConfigurationTest.php` now prevents regression toward Website-owned duplicates. It guards, among other boundaries:

- Product/Category/Post/Order/User canonical models and services;
- Order-owned checkout workflow;
- Order-owned Affiliate services/models;
- Product-owned Wishlist service/model;
- absence of removed Website Affiliate/Wishlist duplicate namespaces;
- Website adapters depending on canonical owner services rather than recreating domain ownership.

## Legacy `wp_settings` retirement debt

The legacy table remains intentionally present as a safety copy. Its physical removal is a separate future task and is **not currently authorized**.

Before any drop migration is proposed, require all of the following proof:

1. Repository-wide caller proof that production runtime application code no longer reads/writes `wp_settings` outside historical/consolidation migrations, tests and documentation.
2. Production data proof that every required legacy key exists in canonical `settings` and no new legacy-only writes have appeared after deployment.
3. Deployment/observation period sufficient to rule out hidden scheduled jobs, queues, scripts or rarely reached admin flows using the legacy table.
4. Confirmation that no rollback procedure still depends on `wp_settings` as a safety copy.
5. Explicit user approval for a separate destructive removal branch/PR.

Until those conditions are met, classify `wp_settings` as `QUARANTINE / DEFERRED`, not `DEAD` and not safe to drop.

## Verification status

Previous settings consolidation verification:

- `154 passed (16325 assertions)`;
- post-migration proof: `settings=73`, `wp_settings=30`, missing legacy keys `0`.

Current remaining-domain branch:

- implementation and ownership guards: COMPLETE;
- destructive schema changes: NONE;
- migration ledger rewrites: NONE;
- public MoMo callback/IPN contract changes: NONE;
- final targeted local regression checkpoint: PENDING;
- UI smoke after regression: PENDING;
- PR/merge: PENDING USER VERIFICATION.

## Next checkpoint

Run one consolidated targeted verification covering Website and directly impacted Order/Product/User/Account/Admin boundaries. Do not run the full project test suite. After targeted tests pass, verify route sanity and UI smoke before opening the PR.
