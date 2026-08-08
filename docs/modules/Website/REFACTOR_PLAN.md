# Website Refactor Plan

## 1. Executive Summary

`Modules/Website` currently mixes storefront, account, checkout, admin CMS, coupon import/export, affiliate, duplicated domain models, and system-like database/env services. The highest-risk problems are authorization gaps on admin and Livewire actions, browser-supplied cart item IDs without ownership checks, and dangerous database/system services living inside the Website module.

The refactor should happen in guarded phases. First, close P0 security and ownership gaps without changing business behavior. Second, move logic back into services, fix checkout/cart consistency, standardize validation, and replace ad hoc import/export with the shared foundation. Third, clean up unused files, cache stable frontend settings, and remove duplicates only after route/component tests prove callers have migrated.

This plan does not authorize code changes yet. It defines the files, risks, acceptance criteria, and order for later implementation.

## 2. P0 Critical Fixes

### P0-01 Admin Website routes lack named permissions

* Issue: `Modules/Website/routes/web.php` protects most admin Website routes with only `auth:admin`.
* Root Cause: Admin route registration relies on authentication instead of capability-level permission middleware.
* Business Impact: Any authenticated admin may edit homepage, header, footer, banners, flash sales, coupons, and affiliate settings.
* Technical Impact: Authorization is inconsistent with roadmap P0-01 and cannot be tested at permission granularity.
* Proposed Solution: Add explicit named permission middleware for every Website admin route and align route names/slugs with the permission model.
* Files To Change: `Modules/Website/routes/web.php`, `Modules/Website/Http/Controllers/Admin/AffiliateController.php`, `Modules/Website/Http/Controllers/Admin/HomeSettingsController.php`, `Modules/Website/Http/Controllers/Admin/HeaderController.php`, `Modules/Website/Http/Controllers/Admin/FooterController.php`, `Modules/Website/Http/Controllers/Admin/BannerController.php`, `Modules/Website/Http/Controllers/Admin/FlashSaleController.php`, `Modules/Website/Http/Controllers/Admin/CouponController.php`, `Modules/Website/Http/Controllers/Admin/CustomerController.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Every Website admin route requires `auth:admin` plus a named permission; denied users receive 403; feature tests cover allowed and denied access for all admin route groups.

### P0-02 Admin Livewire mutations lack method-level authorization

* Issue: Mutating Livewire methods do not enforce named permissions server-side.
* Root Cause: The UI and route layer are treated as sufficient authorization boundaries.
* Business Impact: A user who can reach or invoke a Livewire endpoint may mutate website content, customer records, coupons, banners, flash sales, header/footer settings, or affiliate commissions without the intended capability.
* Technical Impact: Livewire actions violate Laravel and Livewire best practices for policy/gate enforcement at action boundaries.
* Proposed Solution: Add authorization checks to each mutating Livewire method, backed by the same permissions used by the routes.
* Files To Change: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php`, `Modules/Website/Livewire/Admin/Coupon/CouponForm.php`, `Modules/Website/Livewire/Admin/Customers/CustomerTable.php`, `Modules/Website/Livewire/Admin/Customers/CustomerCreate.php`, `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Livewire/Admin/Header/MenuManager.php`, `Modules/Website/Livewire/Admin/Header/GeneralSettings.php`, `Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php`, `Modules/Website/Livewire/Admin/Footer/FooterInfo.php`, `Modules/Website/Livewire/Admin/Footer/FooterColumns.php`, `Modules/Website/Livewire/Admin/Footer/SocialLinks.php`, `Modules/Website/Livewire/Admin/Banner/BannerManager.php`, `Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php`, `Modules/Website/Livewire/Admin/Affiliate/CommissionList.php`, `Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php`.
* Risk Level: Critical.
* Complexity: High.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Every mutating Livewire action checks permission before persistence; denied actions do not change data; Livewire tests cover denial for coupon, customer, homepage, header, footer, banner, flash-sale, and affiliate actions.

### P0-03 Cart item updates trust browser-supplied item IDs

* Issue: Cart item update/remove actions accept item IDs without proving the item belongs to the current authenticated user or session cart.
* Root Cause: `CartItem` is fetched/deleted by primary key instead of through the current cart aggregate.
* Business Impact: A user could modify or delete another user's cart item if they can guess an ID.
* Technical Impact: Record ownership is unenforced in the service and Livewire layers.
* Proposed Solution: Resolve cart items only through `CartService::getCart()` and current cart relationships; reject IDs outside the current cart.
* Files To Change: `Modules/Website/Livewire/Cart/CartList.php`, `Modules/Website/Services/CartService.php`, `Modules/Website/Livewire/Products/ProductDetail.php`, `Modules/Website/Livewire/Products/ProductList.php`, `Modules/Website/Livewire/Cart/AddToCart.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Update/remove operations fail for cart item IDs not owned by the current cart; cart tests cover guest carts, authenticated carts, merged carts, and denied foreign item IDs.

### P0-04 Dangerous database/env services are inside Website

* Issue: Website contains backup, restore, truncate, drop, download-path, env, and database connection services.
* Root Cause: System administration concerns were copied into `Modules/Website/Services/Services` instead of staying in a hardened System module.
* Business Impact: A storefront/CMS module may expose production-control or data-loss capabilities.
* Technical Impact: Module boundaries are broken and roadmap P0-02/P0-03 risks are duplicated.
* Proposed Solution: Confirm callers, then disable Website access to these services; migrate any required callers to the canonical System module after P0 hardening.
* Files To Change: `Modules/Website/Services/Services/DatabaseService.php`, `Modules/Website/Services/Services/Database/DbConnectionService.php`, `Modules/Website/Services/Services/Env/EnvBackupService.php`, `Modules/Website/Services/Services/Env/EnvManagerService.php`, `Modules/Website/Services/Services/Env/SystemConfigService.php`, `Modules/Website/Services/Services/Env/MailConfigService.php`, `Modules/Website/Services/Services/Env/SocialConfigService.php`.
* Risk Level: Critical.
* Complexity: High.
* Estimated Effort: 2-5 days depending on discovered callers.
* Acceptance Criteria: No Website route, Livewire component, controller, or provider can invoke database destructive or env-management services; any remaining System implementation requires named permissions, allowlists, safe path handling, audit logs, and denial tests.

### P0-05 Account and affiliate order access needs ownership checks

* Issue: Order detail and affiliate order detail flows need explicit ownership/authorization enforcement.
* Root Cause: Route authentication exists, but record-level authorization is not documented as enforced for every order-like access path.
* Business Impact: Customers or affiliates may view order data they do not own.
* Technical Impact: Account and affiliate flows violate roadmap P0-05 record-ownership requirements.
* Proposed Solution: Centralize order lookups in services that scope by current user or affiliate ID; add explicit not-found/denied handling.
* Files To Change: `Modules/Website/Livewire/Account/OrderDetail.php`, `Modules/Website/Livewire/Account/OrderList.php`, `Modules/Website/Livewire/Account/Affiliate/AffiliateDashboard.php`, `Modules/Website/Services/AffiliateService.php`, `Modules/Website/Http/Controllers/AccountController.php`.
* Risk Level: Critical.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Users cannot access another user's order by code; affiliates cannot access unrelated commission/order details; tests cover allowed and denied paths.

## 3. P1 Important Refactors

### P1-01 Replace blog route closure with controller flow

* Issue: `/blog` is handled by a closure in `Modules/Website/routes/web.php`.
* Root Cause: Route file contains page logic instead of routing only to a controller action.
* Business Impact: Harder to authorize, test, and maintain blog entry behavior.
* Technical Impact: Violates required Route -> Controller -> Page Blade flow.
* Proposed Solution: Route `/blog` to `PostController@index` and pass only scalar query-derived data to the page blade.
* Files To Change: `Modules/Website/routes/web.php`, `Modules/Website/Http/Controllers/PostController.php`, `Modules/Website/resources/views/pages/blog/index.blade.php`.
* Risk Level: Medium.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: `blog.index` uses `PostController@index`; route test confirms the page still mounts `website.post.post-list` with the same category filter behavior.

### P1-02 Resolve missing MoMo callback action

* Issue: `Modules/Website/routes/web.php` declares `CheckoutController@momoCallback`, but the analysis did not find that method.
* Root Cause: Payment callback route and controller implementation are out of sync.
* Business Impact: Payment callback requests may fail or leave orders in the wrong status.
* Technical Impact: Checkout/payment integration is not route-complete.
* Proposed Solution: Decide whether the MoMo callback belongs in Website checkout or a payment service; implement or remove the route only after confirming behavior.
* Files To Change: `Modules/Website/routes/web.php`, `Modules/Website/Http/Controllers/CheckoutController.php`, `Modules/Website/Services/MomoService.php`, `Modules/Website/Services/CheckoutService.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 1-2 days after payment rules are confirmed.
* Acceptance Criteria: Callback route maps to an existing method; payment signature/status handling is service-owned; tests cover success, failed, duplicate, and tampered callback cases.

### P1-03 Remove direct model queries from controllers

* Issue: Controllers query models in product, account, and checkout flows.
* Root Cause: Controllers contain data access instead of delegating to services.
* Business Impact: Business behavior becomes inconsistent across controller and Livewire entry points.
* Technical Impact: Violates thin-controller and service-layer rules.
* Proposed Solution: Move product detail, account dashboard counts, checkout cart checks, and checkout success order lookups into services.
* Files To Change: `Modules/Website/Http/Controllers/ProductController.php`, `Modules/Website/Http/Controllers/AccountController.php`, `Modules/Website/Http/Controllers/CheckoutController.php`, `Modules/Website/Services/ProductService.php`, `Modules/Website/Services/CartService.php`, `Modules/Website/Services/CheckoutService.php`, `Modules/Website/Services/Account/ProfileService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-3 days.
* Acceptance Criteria: Controllers return views/redirects and pass scalar parameters only; service tests cover moved query behavior; route tests preserve responses.

### P1-04 Standardize Website Livewire aliases and page mounts

* Issue: Some Website page blades mount components using `admin.*` aliases instead of `website.*`.
* Root Cause: Component aliases are inconsistent between Website and Admin namespaces.
* Business Impact: Components may fail to mount or accidentally bind to another module's component.
* Technical Impact: Module boot behavior is fragile and hard to validate.
* Proposed Solution: Register and use one canonical Website Livewire alias set for Website components.
* Files To Change: `Modules/Website/resources/views/pages/admin/flash-sale/index.blade.php`, `Modules/Website/resources/views/pages/admin/home/index.blade.php`, `Modules/Website/resources/views/pages/admin/banner/index.blade.php`, `Modules/Website/resources/views/pages/admin/header/index.blade.php`, `Modules/Website/resources/views/pages/admin/footer/index.blade.php`, `Modules/Website/Providers/WebsiteServiceProvider.php`, `Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Livewire/Admin/Banner/BannerManager.php`, `Modules/Website/Livewire/Admin/Header/GeneralSettings.php`, `Modules/Website/Livewire/Admin/Header/MenuManager.php`, `Modules/Website/Livewire/Admin/Footer/FooterInfo.php`, `Modules/Website/Livewire/Admin/Footer/FooterColumns.php`, `Modules/Website/Livewire/Admin/Footer/SocialLinks.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: All Website views mount Website components by canonical aliases; module boot tests verify aliases resolve.

### P1-05 Remove cross-module Admin view includes from Website views

* Issue: Website header settings views include `Admin::livewire.header.partials.*`.
* Root Cause: Website view rendering reuses Admin module partials directly.
* Business Impact: Admin module changes can break Website settings screens.
* Technical Impact: Violates module boundaries and creates hidden cross-module coupling.
* Proposed Solution: Move shared UI into `Modules/Shared` or copy intentionally into Website during a controlled migration.
* Files To Change: `Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php`, `Modules/Website/resources/views/livewire/admin/header/partials/menu-tree-manager.blade.php`, `Modules/Website/resources/views/livewire/admin/header/partials/menu-item-row.blade.php`, `Modules/Admin/resources/views/livewire/header/partials/menu-tree-manager.blade.php`, `Modules/Admin/resources/views/livewire/header/partials/menu-item-row.blade.php`, `Modules/Shared/resources/views/components/*`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Website views no longer include `Admin::` partials; shared view contract is documented; rendering tests cover header settings.

### P1-06 Move cart add/update logic into CartService

* Issue: Cart item persistence is duplicated across services and product/cart Livewire classes.
* Root Cause: Livewire components implement business logic directly.
* Business Impact: Stock checks, pricing, ownership checks, and cart merge behavior can diverge by screen.
* Technical Impact: Violates service-layer and DRY rules.
* Proposed Solution: Make `CartService` the only write path for add, update, remove, coupon apply/remove, and summary.
* Files To Change: `Modules/Website/Services/CartService.php`, `Modules/Website/Livewire/Products/ProductDetail.php`, `Modules/Website/Livewire/Products/ProductList.php`, `Modules/Website/Livewire/Cart/AddToCart.php`, `Modules/Website/Livewire/Cart/CartList.php`, `Modules/Website/Livewire/Cart/CartIcon.php`, `Modules/Website/Livewire/Checkout/OrderSummary.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 2-3 days.
* Acceptance Criteria: No Livewire product/cart component writes `Cart` or `CartItem` directly; cart service tests cover all write paths; UI behavior remains unchanged.

### P1-07 Fix checkout transaction and concurrency behavior

* Issue: Checkout validates stock before the transaction, decrements products without locks, and saves a deleted cart.
* Root Cause: Multi-record order creation mixes preflight checks and transactional writes without row-level locking.
* Business Impact: Concurrent checkout can oversell stock or leave inconsistent order/cart state.
* Technical Impact: Transaction boundary is incomplete and cleanup has a correctness bug.
* Proposed Solution: Move stock validation into the transaction, lock product rows, create order/items/history/coupon usage atomically, and clean up cart consistently.
* Files To Change: `Modules/Website/Services/CheckoutService.php`, `Modules/Website/Livewire/Checkout/CheckoutForm.php`, `Modules/Website/Http/Controllers/CheckoutController.php`, `Modules/Website/Models/Order.php`, `Modules/Website/Models/OrderItem.php`, `Modules/Website/Models/Cart.php`, `Modules/Website/Models/CartItem.php`, `Modules/Website/Models/WpProduct.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 3-5 days.
* Acceptance Criteria: Checkout cannot oversell under concurrent requests; deleted cart is not saved; rollback leaves stock/cart/order data consistent; tests cover empty cart, insufficient stock, coupon usage, and rollback.

### P1-08 Move homepage, product, and post queries out of Livewire

* Issue: Many Livewire components query models directly.
* Root Cause: Livewire components own query construction instead of delegating to services.
* Business Impact: Filtering and display rules become duplicated and hard to optimize.
* Technical Impact: Violates Livewire 3 best practices and service-layer rules.
* Proposed Solution: Move product/post/homepage query logic into services and keep Livewire components focused on state and rendering.
* Files To Change: `Modules/Website/Livewire/Products/ProductList.php`, `Modules/Website/Livewire/Products/ProductDetail.php`, `Modules/Website/Livewire/Post/PostList.php`, `Modules/Website/Livewire/Post/PostDetail.php`, `Modules/Website/Livewire/Home/FeaturedProducts.php`, `Modules/Website/Livewire/Home/BestSellers.php`, `Modules/Website/Livewire/Home/NewArrivals.php`, `Modules/Website/Livewire/Home/BlogHighlight.php`, `Modules/Website/Livewire/Home/HeroBanner.php`, `Modules/Website/Livewire/Home/PromoBanner.php`, `Modules/Website/Livewire/Home/TrustBadges.php`, `Modules/Website/Services/ProductService.php`, `Modules/Website/Services/ContentService.php`, `Modules/Website/Services/MarketingService.php`, `Modules/Website/Services/CategoryService.php`, `Modules/Website/Services/SettingsService.php`.
* Risk Level: Medium.
* Complexity: High.
* Estimated Effort: 3-6 days.
* Acceptance Criteria: Listed Livewire components no longer query Eloquent/DB directly; services expose paginated/searchable methods; tests cover filters, sorting, related products, homepage sections, and blog category filtering.

### P1-09 Move admin settings persistence to services

* Issue: Admin homepage/header/footer settings components query and write settings directly.
* Root Cause: Settings UI owns persistence and multi-key updates.
* Business Impact: Partial saves can create inconsistent homepage/header/footer configuration.
* Technical Impact: Validation, transactions, and cache invalidation cannot be centralized.
* Proposed Solution: Use dedicated services for all settings reads/writes, with transactions for multi-key saves and explicit cache invalidation.
* Files To Change: `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Livewire/Admin/Header/GeneralSettings.php`, `Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php`, `Modules/Website/Livewire/Admin/Footer/FooterInfo.php`, `Modules/Website/Services/SettingsService.php`, `Modules/Website/Services/Services/HomeSettingService.php`, `Modules/Website/Services/HeaderMenuService.php`, `Modules/Website/Services/FooterService.php`.
* Risk Level: Medium.
* Complexity: High.
* Estimated Effort: 3-5 days.
* Acceptance Criteria: Multi-setting saves are service-owned and transactional; validation is explicit; cache invalidation happens after successful commits.

### P1-10 Replace coupon JSON import/export with shared ImportExport architecture

* Issue: Coupon import/export is implemented directly inside a Livewire table and uses JSON instead of the project import/export standard.
* Root Cause: Import/export UI, file parsing, row validation, persistence, and export streaming are coupled in one Livewire class.
* Business Impact: Invalid or partial coupon data can be imported without enough diagnostics or dry-run safety.
* Technical Impact: Bypasses `Modules/Shared/Services/ImportExport`, row reports, bounded exports, and service-owned persistence.
* Proposed Solution: Create a Website coupon import/export service using the shared foundation; mount `shared.import-export.panel`; define unique key, aliases, validation, dry-run behavior, and export columns before implementation.
* Files To Change: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php`, `Modules/Website/resources/views/pages/admin/coupons/index.blade.php`, `Modules/Website/Services/ImportExport.php`, `Modules/Website/Models/Coupon.php`, `Modules/Website/database/migrations/-0001_11_30_000018_create_coupons_table.php`, `Modules/Shared/Services/ImportExport/BaseImportExportService.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 3-5 days.
* Acceptance Criteria: Coupon import/export flows through shared panel and service; dry-run and row errors work; export is bounded; raw exception messages are not shown; tests cover valid, invalid, duplicate, blank, and date cases.

### P1-11 Strengthen validation in Website forms and services

* Issue: Several forms validate only minimal UI fields and do not enforce business invariants in services.
* Root Cause: Validation is scattered across Livewire components and not backed by service-level rules.
* Business Impact: Invalid settings, coupons, addresses, newsletter signups, or affiliate refs can enter the system.
* Technical Impact: Data consistency depends on each UI component implementing the same rules.
* Proposed Solution: Define Livewire rules for UI shape and service validations for business invariants.
* Files To Change: `Modules/Website/Livewire/Checkout/CheckoutForm.php`, `Modules/Website/Http/Requests/CheckoutRequest.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Livewire/Admin/Coupon/CouponForm.php`, `Modules/Website/Livewire/Admin/Coupon/CouponTable.php`, `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php`, `Modules/Website/Livewire/Home/NewsletterSignup.php`, `Modules/Website/Livewire/Products/ProductDetail.php`, `Modules/Website/Services/CheckoutService.php`, `Modules/Website/Services/SettingsService.php`, `Modules/Website/Services/Account/AddressService.php`, `Modules/Website/Services/AffiliateService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Enum/status/date/url/ID/address/newsletter/coupon rules are explicit; services reject invalid data from non-UI callers; field-level validation remains user friendly.

### P1-12 Add transactions for multi-write operations

* Issue: Several multi-record writes are not transactional.
* Root Cause: Livewire and service methods perform multi-step updates without a transaction boundary.
* Business Impact: Partial failure can leave default addresses, footer ordering, homepage settings, or cart/order data inconsistent.
* Technical Impact: Rollback behavior is not deterministic.
* Proposed Solution: Wrap multi-record writes in services using `DB::transaction()` and keep transactions out of Livewire.
* Files To Change: `Modules/Website/Services/Account/AddressService.php`, `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php`, `Modules/Website/Services/FooterService.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Services/SettingsService.php`, `Modules/Website/Services/CartService.php`, `Modules/Website/Services/CheckoutService.php`.
* Risk Level: High.
* Complexity: Medium.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Default address changes, footer/social ordering, multi-key settings saves, cart merges, and checkout writes are transactional; rollback tests prove no partial state.

### P1-13 Fix header/footer/homepage query performance with explicit cache policy

* Issue: Header/footer composers and homepage components repeatedly query stable settings and menus.
* Root Cause: Read-heavy frontend data has no service-level cache with invalidation.
* Business Impact: Every storefront page pays repeated database cost for stable CMS data.
* Technical Impact: Performance hotspots are hidden in view composers and Livewire render paths.
* Proposed Solution: Cache stable header/footer/homepage/menu data in services and invalidate after admin writes.
* Files To Change: `Modules/Website/Providers/WebsiteServiceProvider.php`, `Modules/Website/Services/SettingsService.php`, `Modules/Website/Services/HeaderMenuService.php`, `Modules/Website/Services/FooterService.php`, `Modules/Website/Services/MarketingService.php`, `Modules/Website/Services/ProductService.php`, `Modules/Website/Services/ContentService.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/Livewire/Admin/Header/*`, `Modules/Website/Livewire/Admin/Footer/*`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 2-3 days.
* Acceptance Criteria: Cache keys and invalidation rules are documented; admin updates invalidate affected cache; query-count tests or profiling confirm fewer repeated queries.

### P1-14 Bound select-all, export, and `All` pagination behavior

* Issue: Admin tables pluck all IDs or paginate with large hard-coded limits.
* Root Cause: Convenience actions treat large datasets as safe in request memory.
* Business Impact: Coupon/customer admin screens can time out or exhaust memory on production-sized data.
* Technical Impact: Violates bounded pagination and export guidance.
* Proposed Solution: Make select-all page-scoped by default, guard `All`, and move large export/import work to bounded service iteration.
* Files To Change: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php`, `Modules/Website/Livewire/Admin/Customers/CustomerTable.php`, `Modules/Website/resources/views/livewire/admin/coupon/coupon-table.blade.php`, `Modules/Website/resources/views/livewire/admin/customers/customer-table.blade.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Select-all affects only current page or uses explicit confirmed global mode; `All` is capped/guarded; large exports do not call unbounded `get()`.

### P1-15 Deduplicate root and nested services

* Issue: Several services exist both at `Modules/Website/Services/*` and `Modules/Website/Services/Services/*`.
* Root Cause: Parallel service tree was copied into Website.
* Business Impact: Callers can depend on different implementations for the same concept.
* Technical Impact: Increases maintenance and test scope and blocks canonical ownership.
* Proposed Solution: Identify callers, pick canonical service owners, migrate callers, then remove duplicates only after tests pass.
* Files To Change: `Modules/Website/Services/BannerService.php`, `Modules/Website/Services/Services/BannerService.php`, `Modules/Website/Services/FlashSaleService.php`, `Modules/Website/Services/Services/FlashSaleService.php`, `Modules/Website/Services/AffiliateRankService.php`, `Modules/Website/Services/Services/AffiliateRankService.php`, `Modules/Website/Services/AdminAffiliateService.php`, `Modules/Website/Services/Services/AdminAffiliateService.php`, `Modules/Website/Services/Services/AuthService.php`, `Modules/Website/Services/Services/ChatService.php`, `Modules/Website/Services/Services/HomeSettingService.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 3-7 days depending on callers.
* Acceptance Criteria: Each service concept has one canonical class; architecture tests prevent reintroducing `Services/Services`; deleted duplicates have no references.

### P1-16 Define canonical module ownership for duplicated domain models

* Issue: Website owns product, post, category, order, review, tag, and address-like models that overlap with canonical modules.
* Root Cause: Website grew into a domain owner instead of a presentation/storefront module.
* Business Impact: Data rules and behavior can diverge across admin, storefront, and domain modules.
* Technical Impact: Blocks P1-01/P1-02 roadmap architecture cleanup.
* Proposed Solution: Document canonical ownership and migrate Website callers to canonical Product/Post/Order/Category/Account services over multiple PRs.
* Files To Change: `Modules/Website/Models/Category.php`, `Modules/Website/Models/Post.php`, `Modules/Website/Models/WpProduct.php`, `Modules/Website/Models/Order.php`, `Modules/Website/Models/Review.php`, `Modules/Website/Models/Tag.php`, `Modules/Website/Models/UserAddress.php`, `Modules/Product/Models/*`, `Modules/Post/Models/*`, `Modules/Order/Models/*`, `Modules/Category/Models/*`, `Modules/Account/Models/*`, `docs/modules/Website/ANALYSIS.md`.
* Risk Level: High.
* Complexity: Critical.
* Estimated Effort: 1-2 weeks after P0 coverage exists.
* Acceptance Criteria: Ownership document names one canonical module per business concept; Website callers use canonical services; no table ownership changes happen without migration smoke tests.

### P1-17 Repair malformed negative-year migrations

* Issue: Website migrations use filenames such as `-0001_11_30_000018_create_coupons_table.php`.
* Root Cause: Generated/imported migration timestamps are malformed.
* Business Impact: Fresh install and migration ordering may fail unpredictably.
* Technical Impact: CI migration tests cannot be trusted until ordering is deterministic.
* Proposed Solution: Plan a migration hygiene pass after table ownership is confirmed; rename or replace malformed migrations without changing schema unexpectedly.
* Files To Change: `Modules/Website/database/migrations/-0001_11_30_000005_create_affiliate_levels_table.php`, `Modules/Website/database/migrations/-0001_11_30_000018_create_coupons_table.php`, `Modules/Website/database/migrations/-0001_11_30_000019_create_carts_table.php`, `Modules/Website/database/migrations/-0001_11_30_000020_create_cart_items_table.php`, `Modules/Website/database/migrations/-0001_11_30_000026_create_wp_tags_table.php`, `Modules/Website/database/migrations/-0001_11_30_000029_create_newsletters_table.php`, `Modules/Website/database/migrations/-0001_11_30_000030_create_reviews_table.php`, `Modules/Website/database/migrations/-0001_11_30_000031_create_wp_banners_table.php`, `Modules/Website/database/migrations/-0001_11_30_000032_create_wp_flash_sales_table.php`, `Modules/Website/database/migrations/-0001_11_30_000033_create_wp_flash_sale_items_table.php`, `Modules/Website/database/migrations/-0001_11_30_000036_create_footer_columns_table.php`, `Modules/Website/database/migrations/-0001_11_30_000037_create_footer_links_table.php`, `Modules/Website/database/migrations/-0001_11_30_000038_create_social_links_table.php`, `Modules/Website/database/migrations/-0001_11_30_000039_create_wishlists_table.php`, `Modules/Website/database/migrations/-0001_11_30_000040_create_wp_affiliate_schemes_table.php`.
* Risk Level: High.
* Complexity: High.
* Estimated Effort: 2-4 days.
* Acceptance Criteria: Fresh migration order is deterministic; migration smoke tests pass; no duplicate table ownership remains unresolved.

### P1-18 Normalize raw exception handling and auth throttling

* Issue: Coupon import exposes raw exception messages and login has no observed throttling.
* Root Cause: User-facing error handling and authentication hardening are not standardized in Website.
* Business Impact: Users may see internal details; login attempts may be abused.
* Technical Impact: Violates safe error handling and auth best practices.
* Proposed Solution: Use safe user-facing messages, log internal exceptions, and add Laravel rate limiting to login actions.
* Files To Change: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php`, `Modules/Website/Livewire/Auth/LoginForm.php`, `Modules/Website/Http/Controllers/AuthController.php`, `Modules/Website/Services/Services/AuthService.php`.
* Risk Level: Medium.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: Raw exception text is not shown; failed logins are throttled; logs preserve internal diagnostics with redaction.

## 4. P2 Nice To Have Improvements

### P2-01 Remove unused resource stubs from WebsiteController

* Issue: `WebsiteController` contains unused resource-style methods.
* Root Cause: Scaffold methods were left after the module shifted to storefront pages.
* Business Impact: Low; unused code adds confusion.
* Technical Impact: Increases maintenance surface and may mislead future route additions.
* Proposed Solution: Remove stubs only after route tests confirm they are unused.
* Files To Change: `Modules/Website/Http/Controllers/WebsiteController.php`, `Modules/Website/routes/web.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Route tests pass; no route references removed methods; controller contains only active actions.

### P2-02 Resolve unregistered admin product and affiliate commission pages

* Issue: Some admin product/affiliate commission files appear unregistered or reference missing routes.
* Root Cause: Views/controllers were added without matching route ownership.
* Business Impact: Admin users may hit broken links or dead screens.
* Technical Impact: Module route catalog is inaccurate.
* Proposed Solution: Confirm whether Product module owns these screens; either register intentionally or remove after tests.
* Files To Change: `Modules/Website/Http/Controllers/Admin/ProductController.php`, `Modules/Website/resources/views/admin/products/index.blade.php`, `Modules/Website/resources/views/pages/admin/affiliate/product-commissions.blade.php`, `Modules/Website/resources/views/livewire/admin/affiliate/commission-matrix.blade.php`, `Modules/Website/routes/web.php`, `Modules/Product/routes/web.php`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1 day.
* Acceptance Criteria: No broken `admin.products.index` references; ownership of product commission management is documented.

### P2-03 Remove placeholder/scaffold files after confirmation

* Issue: Several files look like scaffolds or unused placeholders.
* Root Cause: Module generated default files were not pruned.
* Business Impact: Low, but it slows navigation and analysis.
* Technical Impact: Increases false positives in code search and architecture catalogs.
* Proposed Solution: Delete only after route/component/reference tests prove no callers.
* Files To Change: `Modules/Website/resources/views/admin.blade.php`, `Modules/Website/resources/views/website.blade.php`, `Modules/Website/resources/views/pages/dashboard.blade.php`, `Modules/Website/Models/Website.php`, `Modules/Website/Http/Requests/CheckoutRequest.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Reference search and route tests confirm files are unused; removal does not change runtime behavior.

### P2-04 Move Blade presentation calculations out of views

* Issue: Several Blade files contain `@php` calculations for presentation values.
* Root Cause: Display formatting is embedded in templates.
* Business Impact: Low; templates are harder to read and reuse.
* Technical Impact: Presentation logic is scattered and harder to test.
* Proposed Solution: Move repeated display calculations to view models, accessors, component props, or small Blade components where appropriate.
* Files To Change: `Modules/Website/resources/views/components/product-card.blade.php`, `Modules/Website/resources/views/livewire/home/best-sellers.blade.php`, `Modules/Website/resources/views/livewire/home/hero-banner.blade.php`, `Modules/Website/resources/views/livewire/cart/cart-list.blade.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 1 day.
* Acceptance Criteria: Blade files no longer contain repeated `@php` calculations; visual output remains unchanged.

### P2-05 Split oversized admin homepage settings view

* Issue: `home-settings.blade.php` is large and contains embedded JavaScript.
* Root Cause: A multi-tab admin settings screen was built as one large view.
* Business Impact: Medium; future changes are harder and more regression-prone.
* Technical Impact: View is difficult to review, test, and maintain.
* Proposed Solution: Split view into smaller partials or components after service/validation refactors stabilize behavior.
* Files To Change: `Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php`, `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Modules/Website/resources/views/livewire/admin/home/*`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: UI behavior is unchanged; partials have clear responsibilities; no inline JavaScript is introduced unless unavoidable.

### P2-06 Optimize dashboard revenue chart queries

* Issue: Revenue chart queries one day at a time.
* Root Cause: Chart data is assembled with a loop instead of a grouped aggregate query.
* Business Impact: Low unless dashboard traffic grows.
* Technical Impact: Adds unnecessary database round-trips.
* Proposed Solution: Use one service-owned grouped query over the date range.
* Files To Change: `Modules/Website/Livewire/Dashboard/RevenueChart.php`, `Modules/Website/Services/AdminAffiliateService.php`, `Modules/Website/Services/MarketingService.php` or a new reporting service under `Modules/Website/Services`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5-1 day.
* Acceptance Criteria: Chart data uses one bounded query; rendered chart output is unchanged.

### P2-07 Confirm unused middleware before deletion or registration

* Issue: `TrackAffiliate` and `ShareWishlistData` were not observed in Website routes.
* Root Cause: Middleware may be unused, globally registered elsewhere, or planned but not wired.
* Business Impact: Low to medium depending on affiliate tracking intent.
* Technical Impact: Behavior is unclear and hard to test.
* Proposed Solution: Search global middleware registration and route groups; register intentionally or remove after confirmation.
* Files To Change: `Modules/Website/Http/Middleware/TrackAffiliate.php`, `Modules/Website/Http/Middleware/ShareWishlistData.php`, `Modules/Website/routes/web.php`, `Modules/Website/Providers/WebsiteServiceProvider.php`, `bootstrap/app.php`.
* Risk Level: Low.
* Complexity: Low.
* Estimated Effort: 0.5 day.
* Acceptance Criteria: Each middleware is either intentionally registered and tested, or confirmed unused and removed.

### P2-08 Generate Website architecture catalog

* Issue: Website has many routes, Livewire classes, services, models, duplicate services, and likely unused files.
* Root Cause: No automated catalog or module boundary check exists.
* Business Impact: Low immediately, but future refactors are slower and riskier.
* Technical Impact: Duplicate implementations can reappear unnoticed.
* Proposed Solution: Add CI-generated catalog and architecture tests after P1 ownership decisions.
* Files To Change: `docs/modules/Website/ANALYSIS.md`, `docs/modules/Website/REFACTOR_PLAN.md`, `tests/Feature/Website/*`, `tests/Architecture/*`.
* Risk Level: Low.
* Complexity: Medium.
* Estimated Effort: 1-2 days.
* Acceptance Criteria: CI reports Website routes/components/services/models; architecture tests flag forbidden `Services/Services` and direct Admin view includes.

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

1. P0-01: Add named permissions to `Modules/Website/routes/web.php`.
2. P0-02: Add method-level authorization to `Modules/Website/Livewire/Admin/**`.
3. P0-03: Enforce cart item ownership in `Modules/Website/Services/CartService.php` and cart/product Livewire callers.
4. P0-05: Add account/order ownership checks in `Modules/Website/Livewire/Account/**` and `Modules/Website/Services/AffiliateService.php`.
5. P0-04: Disable or migrate dangerous services under `Modules/Website/Services/Services/**`.
6. Add P0 tests for route denial, Livewire denial, cart ownership denial, account order ownership denial, and disabled dangerous operations.

### Phase 2: Correctness and Maintainability

1. P1-02: Resolve `CheckoutController@momoCallback` before deeper checkout work.
2. P1-07: Fix checkout transaction, stock locking, and cart cleanup.
3. P1-06: Consolidate cart add/update/remove behavior into `CartService`.
4. P1-03: Move direct controller queries into services.
5. P1-08 and P1-09: Move Livewire query/persistence logic into services.
6. P1-11 and P1-12: Standardize validation and transactions across settings, coupons, addresses, newsletter, affiliate, and checkout.
7. P1-10: Replace coupon import/export with shared import/export architecture.
8. P1-04 and P1-05: Normalize Livewire aliases and remove Admin view includes.

### Phase 3: Performance and Cleanup

1. P1-13: Add cache policy for header/footer/homepage/menu data.
2. P1-14: Bound select-all, `All`, and export behavior.
3. P1-15 and P1-16: Deduplicate services and define canonical model ownership after tests exist.
4. P1-17: Repair malformed migrations after ownership is settled.
5. P2-01 through P2-07: Remove confirmed unused files, clean Blade calculations, split oversized views, optimize dashboard queries, and resolve middleware status.
6. P2-08: Add architecture catalogs and tests to prevent regression.

## 6. Files Change Matrix

| File Path | Change Type | Priority | Reason |
|---|---|---|---|
| `Modules/Website/routes/web.php` | Modify | P0 | Add named permissions, replace blog closure, resolve callback/admin route issues. |
| `Modules/Website/Http/Controllers/Admin/AffiliateController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/HomeSettingsController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/HeaderController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/FooterController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/BannerController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/FlashSaleController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/CouponController.php` | Modify | P0 | Enforce route/controller-level admin capability boundary. |
| `Modules/Website/Http/Controllers/Admin/CustomerController.php` | Modify | P0 | Align controller permissions with Livewire method checks. |
| `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` | Modify | P0 | Add authorization; move import/export and query logic to services. |
| `Modules/Website/Livewire/Admin/Coupon/CouponForm.php` | Modify | P0 | Add authorization and stronger coupon validation. |
| `Modules/Website/Livewire/Admin/Customers/CustomerTable.php` | Modify | P0 | Add authorization and bounded selection behavior. |
| `Modules/Website/Livewire/Admin/Customers/CustomerCreate.php` | Modify | P0 | Add create-customer permission checks. |
| `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php` | Modify | P0 | Add authorization and service-owned address/profile writes. |
| `Modules/Website/Livewire/Admin/Home/HomeSettings.php` | Modify | P0 | Add authorization; move multi-setting writes to service. |
| `Modules/Website/Livewire/Admin/Header/MenuManager.php` | Modify | P0 | Add authorization and keep menu writes service-owned. |
| `Modules/Website/Livewire/Admin/Header/GeneralSettings.php` | Modify | P0 | Add authorization and service-owned settings writes. |
| `Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php` | Modify | P0 | Add authorization and remove Admin partial coupling. |
| `Modules/Website/Livewire/Admin/Footer/FooterInfo.php` | Modify | P0 | Add authorization and service-owned settings writes. |
| `Modules/Website/Livewire/Admin/Footer/FooterColumns.php` | Modify | P0 | Add authorization and transactional footer changes. |
| `Modules/Website/Livewire/Admin/Footer/SocialLinks.php` | Modify | P0 | Add authorization and transactional social link changes. |
| `Modules/Website/Livewire/Admin/Banner/BannerManager.php` | Modify | P0 | Add authorization and use canonical banner service. |
| `Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php` | Modify | P0 | Add authorization and use canonical flash-sale service. |
| `Modules/Website/Livewire/Admin/Affiliate/CommissionList.php` | Modify | P0 | Add authorization for commission approval/rejection. |
| `Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php` | Modify | P0 | Add authorization for commission scheme changes. |
| `Modules/Website/Livewire/Cart/CartList.php` | Modify | P0 | Enforce cart item ownership through `CartService`. |
| `Modules/Website/Services/CartService.php` | Modify | P0 | Centralize cart ownership and write behavior. |
| `Modules/Website/Livewire/Account/OrderDetail.php` | Modify | P0 | Enforce account order ownership. |
| `Modules/Website/Livewire/Account/OrderList.php` | Modify | P0 | Scope orders through authorized service methods. |
| `Modules/Website/Livewire/Account/Affiliate/AffiliateDashboard.php` | Modify | P0 | Enforce affiliate ownership on order details. |
| `Modules/Website/Services/AffiliateService.php` | Modify | P0 | Centralize affiliate ref and order authorization rules. |
| `Modules/Website/Services/Services/DatabaseService.php` | Migrate/Disable | P0 | Remove dangerous database operations from Website. |
| `Modules/Website/Services/Services/Database/DbConnectionService.php` | Migrate/Disable | P0 | Move database admin behavior to canonical System module. |
| `Modules/Website/Services/Services/Env/EnvBackupService.php` | Migrate/Disable | P0 | Move env backup behavior to hardened System owner. |
| `Modules/Website/Services/Services/Env/EnvManagerService.php` | Migrate/Disable | P0 | Move env mutation behavior to hardened System owner. |
| `Modules/Website/Services/Services/Env/SystemConfigService.php` | Migrate/Disable | P0 | Move system config behavior out of Website. |
| `Modules/Website/Services/Services/Env/MailConfigService.php` | Migrate/Disable | P0 | Move mail env behavior out of Website. |
| `Modules/Website/Services/Services/Env/SocialConfigService.php` | Migrate/Disable | P0 | Move social env behavior out of Website. |
| `Modules/Website/Http/Controllers/PostController.php` | Modify | P1 | Replace blog route closure with controller flow. |
| `Modules/Website/Http/Controllers/ProductController.php` | Modify | P1 | Remove direct model queries from controller. |
| `Modules/Website/Http/Controllers/AccountController.php` | Modify | P1 | Remove direct order count/query responsibility. |
| `Modules/Website/Http/Controllers/CheckoutController.php` | Modify | P1 | Resolve callback and move cart/order queries to services. |
| `Modules/Website/Services/CheckoutService.php` | Modify | P1 | Fix checkout transaction, stock locking, cart cleanup, and payment consistency. |
| `Modules/Website/Services/MomoService.php` | Modify | P1 | Align payment callback creation/validation with checkout service. |
| `Modules/Website/Services/ProductService.php` | Modify | P1 | Own product list/detail/home query behavior. |
| `Modules/Website/Services/ContentService.php` | Modify | P1 | Own blog/post query behavior. |
| `Modules/Website/Services/MarketingService.php` | Modify | P1 | Own homepage marketing/banner query behavior. |
| `Modules/Website/Services/CategoryService.php` | Modify | P1 | Own category selection/filter data. |
| `Modules/Website/Services/SettingsService.php` | Modify | P1 | Own settings validation, transactions, and cache invalidation. |
| `Modules/Website/Services/HeaderMenuService.php` | Modify | P1 | Own header menu reads/writes and cache invalidation. |
| `Modules/Website/Services/FooterService.php` | Modify | P1 | Own footer/social reads/writes and transactional ordering. |
| `Modules/Website/Services/Account/AddressService.php` | Modify | P1 | Add transactions for default-address changes. |
| `Modules/Website/Services/Account/ProfileService.php` | Modify | P1 | Support controller/service boundary for account profile behavior. |
| `Modules/Website/Livewire/Products/ProductList.php` | Modify | P1 | Remove direct queries and cart writes. |
| `Modules/Website/Livewire/Products/ProductDetail.php` | Modify | P1 | Remove direct queries/cart writes and validate affiliate refs. |
| `Modules/Website/Livewire/Cart/AddToCart.php` | Modify | P1 | Delegate all cart writes to `CartService`. |
| `Modules/Website/Livewire/Cart/CartIcon.php` | Modify | P1 | Keep count reads service-owned. |
| `Modules/Website/Livewire/Checkout/CheckoutForm.php` | Modify | P1 | Align Livewire validation with service invariants. |
| `Modules/Website/Livewire/Checkout/OrderSummary.php` | Modify | P1 | Keep coupon/summary operations service-owned. |
| `Modules/Website/Livewire/Post/PostList.php` | Modify | P1 | Move post/category queries to `ContentService`. |
| `Modules/Website/Livewire/Post/PostDetail.php` | Modify | P1 | Move post detail/related query to `ContentService`. |
| `Modules/Website/Livewire/Home/FeaturedProducts.php` | Modify | P1 | Move product/settings queries to services. |
| `Modules/Website/Livewire/Home/BestSellers.php` | Modify | P1 | Move product/settings queries to services. |
| `Modules/Website/Livewire/Home/NewArrivals.php` | Modify | P1 | Move product/settings queries to services. |
| `Modules/Website/Livewire/Home/BlogHighlight.php` | Modify | P1 | Move post/settings queries to services. |
| `Modules/Website/Livewire/Home/HeroBanner.php` | Modify | P1 | Move banner queries to services. |
| `Modules/Website/Livewire/Home/PromoBanner.php` | Modify | P1 | Move setting queries to services. |
| `Modules/Website/Livewire/Home/TrustBadges.php` | Modify | P1 | Move setting queries to services. |
| `Modules/Website/Livewire/Home/NewsletterSignup.php` | Modify | P1 | Move newsletter persistence to service validation. |
| `Modules/Website/Providers/WebsiteServiceProvider.php` | Modify | P1 | Add cache policy and normalize Livewire aliases if provider owns them. |
| `Modules/Website/Services/ImportExport.php` | Create | P1 | Add shared coupon import/export entry point. |
| `Modules/Shared/Services/ImportExport/BaseImportExportService.php` | Reuse/Review | P1 | Ensure Website coupon import/export uses shared foundation. |
| `Modules/Website/Models/Coupon.php` | Modify | P1 | Support safe import/export fillable/casts/exclusions. |
| `Modules/Website/Models/Category.php` | Migrate/Review | P1 | Resolve canonical Category ownership. |
| `Modules/Website/Models/Post.php` | Migrate/Review | P1 | Resolve canonical Post ownership. |
| `Modules/Website/Models/WpProduct.php` | Migrate/Review | P1 | Resolve canonical Product ownership. |
| `Modules/Website/Models/Order.php` | Migrate/Review | P1 | Resolve canonical Order ownership. |
| `Modules/Website/Models/Review.php` | Migrate/Review | P1 | Resolve canonical Review ownership. |
| `Modules/Website/Models/Tag.php` | Migrate/Review | P1 | Resolve canonical Tag ownership. |
| `Modules/Website/Models/UserAddress.php` | Migrate/Review | P1 | Resolve canonical Account address ownership. |
| `Modules/Website/database/migrations/-0001_11_30_000005_create_affiliate_levels_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000018_create_coupons_table.php` | Rename/Review | P1 | Repair malformed migration timestamp and import/export schema checks. |
| `Modules/Website/database/migrations/-0001_11_30_000019_create_carts_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000020_create_cart_items_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000026_create_wp_tags_table.php` | Rename/Review | P1 | Repair malformed migration timestamp and canonical ownership. |
| `Modules/Website/database/migrations/-0001_11_30_000029_create_newsletters_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000030_create_reviews_table.php` | Rename/Review | P1 | Repair malformed migration timestamp and canonical ownership. |
| `Modules/Website/database/migrations/-0001_11_30_000031_create_wp_banners_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000032_create_wp_flash_sales_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000033_create_wp_flash_sale_items_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000036_create_footer_columns_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000037_create_footer_links_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000038_create_social_links_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000039_create_wishlists_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/database/migrations/-0001_11_30_000040_create_wp_affiliate_schemes_table.php` | Rename/Review | P1 | Repair malformed migration timestamp. |
| `Modules/Website/Services/BannerService.php` | Keep/Migrate | P1 | Select canonical banner service. |
| `Modules/Website/Services/Services/BannerService.php` | Remove/Migrate | P1 | Remove duplicate after caller migration. |
| `Modules/Website/Services/FlashSaleService.php` | Keep/Migrate | P1 | Select canonical flash-sale service. |
| `Modules/Website/Services/Services/FlashSaleService.php` | Remove/Migrate | P1 | Remove duplicate after caller migration. |
| `Modules/Website/Services/AffiliateRankService.php` | Keep/Migrate | P1 | Select canonical affiliate-rank service. |
| `Modules/Website/Services/Services/AffiliateRankService.php` | Remove/Migrate | P1 | Remove duplicate after caller migration. |
| `Modules/Website/Services/AdminAffiliateService.php` | Keep/Migrate | P1 | Select canonical admin affiliate service. |
| `Modules/Website/Services/Services/AdminAffiliateService.php` | Remove/Migrate | P1 | Remove duplicate after caller migration. |
| `Modules/Website/Services/Services/AuthService.php` | Migrate/Review | P1 | Resolve Auth ownership and login behavior. |
| `Modules/Website/Services/Services/ChatService.php` | Migrate/Review | P1 | Resolve Chat ownership. |
| `Modules/Website/Services/Services/HomeSettingService.php` | Keep/Migrate | P1 | Resolve homepage setting service ownership. |
| `Modules/Website/resources/views/pages/blog/index.blade.php` | Modify | P1 | Preserve category filter after removing route closure. |
| `Modules/Website/resources/views/pages/admin/flash-sale/index.blade.php` | Modify | P1 | Normalize Website Livewire alias. |
| `Modules/Website/resources/views/pages/admin/home/index.blade.php` | Modify | P1 | Normalize Website Livewire alias. |
| `Modules/Website/resources/views/pages/admin/banner/index.blade.php` | Modify | P1 | Normalize Website Livewire alias. |
| `Modules/Website/resources/views/pages/admin/header/index.blade.php` | Modify | P1 | Normalize Website Livewire alias. |
| `Modules/Website/resources/views/pages/admin/footer/index.blade.php` | Modify | P1 | Normalize Website Livewire alias. |
| `Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php` | Modify | P1 | Remove Admin partial include. |
| `Modules/Website/resources/views/livewire/admin/header/partials/menu-tree-manager.blade.php` | Modify | P1 | Own Website header partial or move to Shared. |
| `Modules/Website/resources/views/livewire/admin/header/partials/menu-item-row.blade.php` | Modify | P1 | Own Website header partial or move to Shared. |
| `Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php` | Split/Modify | P1/P2 | Reduce oversized view after service refactor. |
| `Modules/Website/resources/views/livewire/admin/coupon/coupon-table.blade.php` | Modify | P1 | Support bounded selection and shared import/export UI. |
| `Modules/Website/resources/views/livewire/admin/customers/customer-table.blade.php` | Modify | P1 | Support bounded selection and permission-denied states. |
| `Modules/Website/resources/views/components/product-card.blade.php` | Modify | P2 | Move repeated presentation calculations out of Blade. |
| `Modules/Website/resources/views/livewire/home/best-sellers.blade.php` | Modify | P2 | Move repeated presentation calculations out of Blade. |
| `Modules/Website/resources/views/livewire/home/hero-banner.blade.php` | Modify | P2 | Move repeated presentation calculations out of Blade. |
| `Modules/Website/resources/views/livewire/cart/cart-list.blade.php` | Modify | P2 | Move repeated presentation calculations out of Blade. |
| `Modules/Website/Http/Controllers/WebsiteController.php` | Remove stubs | P2 | Remove unused resource methods after tests. |
| `Modules/Website/Http/Controllers/Admin/ProductController.php` | Remove/Register | P2 | Resolve unregistered admin product screen. |
| `Modules/Website/resources/views/admin/products/index.blade.php` | Remove/Register | P2 | Resolve unregistered admin product screen. |
| `Modules/Website/resources/views/pages/admin/affiliate/product-commissions.blade.php` | Remove/Register | P2 | Resolve unregistered affiliate commission page. |
| `Modules/Website/resources/views/livewire/admin/affiliate/commission-matrix.blade.php` | Modify/Review | P2 | Fix route references and ownership. |
| `Modules/Website/resources/views/admin.blade.php` | Remove | P2 | Remove confirmed scaffold view. |
| `Modules/Website/resources/views/website.blade.php` | Remove | P2 | Remove confirmed scaffold view. |
| `Modules/Website/resources/views/pages/dashboard.blade.php` | Remove/Register | P2 | Resolve page with no observed Website route. |
| `Modules/Website/Models/Website.php` | Remove | P2 | Remove confirmed scaffold model. |
| `Modules/Website/Http/Requests/CheckoutRequest.php` | Remove/Reuse | P2 | Confirm whether Livewire checkout needs this request. |
| `Modules/Website/Http/Middleware/TrackAffiliate.php` | Register/Remove | P2 | Confirm affiliate tracking middleware usage. |
| `Modules/Website/Http/Middleware/ShareWishlistData.php` | Register/Remove | P2 | Confirm wishlist data middleware usage. |
| `Modules/Website/Livewire/Dashboard/RevenueChart.php` | Modify | P2 | Replace per-day query loop with grouped service query. |
| `tests/Feature/Website/*` | Create | P0/P1 | Add route, authorization, ownership, checkout, and import/export tests. |
| `tests/Unit/Website/*` | Create | P1 | Add service validation, transaction, and query behavior tests. |
| `tests/Architecture/*` | Create | P1/P2 | Enforce module boundaries and duplicate-service rules. |

## 7. Risk Control

Do not change business behavior until P0 tests exist for authorization, cart ownership, account/order ownership, and dangerous operation denial.

Do not delete duplicate services, duplicate models, migrations, middleware, or scaffold files until route/component/reference tests prove they are unused and callers have migrated.

Do not rename malformed migrations before canonical table ownership is decided for Product, Post, Order, Category, Account, and Website tables. Migration cleanup must be a controlled pass with fresh-install tests.

Do not implement coupon import/export until the unique key, accepted file format, null-overwrite behavior, dry-run behavior, duplicate mode, and export columns are confirmed.

Do not move Website domain behavior into `app/Models`, `app/Services`, or `app/Http`; keep module boundaries intact and prefer canonical module services.

Do not expose or expand database/env/system behavior from `Modules/Website/Services/Services/**`; System-like capabilities must remain disabled or migrated to a hardened System owner.

Do not introduce DTOs, Bootstrap, jQuery, or new UI patterns during this refactor. Use validated arrays, Livewire 3 class components, service-owned business logic, and the existing Tailwind/Admin UI standard.

Do not optimize with caching until invalidation rules are explicit for settings, menus, footer columns, social links, homepage data, product highlights, and banners.
