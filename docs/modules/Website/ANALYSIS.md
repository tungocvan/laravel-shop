# Website Module Analysis

## 1. Module Purpose

`Modules/Website` owns the public storefront and several website-admin screens: homepage, product listing/detail, blog/help pages, auth pages, cart, checkout, account/profile/wishlist/orders, affiliate dashboard, homepage/header/footer/banner/flash-sale/coupon/customer administration, and website-facing settings.

The module currently overlaps with other domains named in `ROADMAP.md` such as Product, Post, Order, Account, Chat, System, and Admin. This makes Website act as both storefront and duplicate domain owner.

## 2. Route List

Routes are declared in `Modules/Website/routes/web.php` and `Modules/Website/routes/api.php`.

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/login` | `login` | `AuthController@login` | `web` |
| GET | `/register` | `register` | `AuthController@register` | `web` |
| POST | `/{websitePrefix}/logout` | `logout` | `AuthController@logout` | `web`, `auth` |
| GET | `/` | `home` | `WebsiteController@home` | `web` |
| GET | `/help` | `help` | `WebsiteController@help` | `web` |
| GET | `/product` | `product.list` | `ProductController@index` | `web` |
| GET | `/product/{slug}` | `product.detail` | `ProductController@show` | `web` |
| GET | `/blog` | `blog.index` | closure | `web` |
| GET | `/blog/{slug}` | `blog.detail` | `PostController@detail` | `web` |
| GET | `/cart` | `cart.index` | `CartController@index` | `web` |
| GET | `/checkout` | `checkout.index` | `CheckoutController@index` | `web` |
| GET | `/checkout/success` | `checkout.success` | `CheckoutController@success` | `web` |
| GET | `/checkout/momo-callback` | `checkout.momo.callback` | `CheckoutController@momoCallback` | `web` |
| GET | `/account` | `account.dashboard` | `AccountController@index` | `web`, `auth` |
| GET | `/account/profile` | `account.profile` | `AccountController@profile` | `web`, `auth` |
| GET | `/account/affiliate` | `account.affiliate` | `AccountController@affiliate` | `web`, `auth` |
| GET | `/account/orders` | `account.orders` | `AccountController@orders` | `web`, `auth` |
| GET | `/account/orders/{code}` | `account.orders.detail` | `AccountController@orderDetail` | `web`, `auth` |
| GET | `/account/wishlist` | `account.wishlist` | `AccountController@wishlist` | `web`, `auth` |
| GET | `/admin/affiliate` | `admin.affiliate.index` | `Admin\AffiliateController@index` | `web`, `auth:admin` |
| GET | `/admin/homepage-settings` | `admin.home.settings` | `Admin\HomeSettingsController@index` | `web`, `auth:admin` |
| GET | `/admin/header-settings` | `admin.header.settings` | `Admin\HeaderController@index` | `web`, `auth:admin` |
| GET | `/admin/footer-settings` | `admin.footer.settings` | `Admin\FooterController@index` | `web`, `auth:admin` |
| GET | `/admin/banners` | `admin.banners` | `Admin\BannerController@index` | `web`, `auth:admin` |
| GET | `/admin/flash-sales` | `admin.flash-sales` | `Admin\FlashSaleController@index` | `web`, `auth:admin` |
| GET | `/admin/coupons` | `admin.coupons.index` | `Admin\CouponController@index` | `web`, `auth:admin` |
| GET | `/admin/coupons/create` | `admin.coupons.create` | `Admin\CouponController@create` | `web`, `auth:admin` |
| GET | `/admin/coupons/{id}/edit` | `admin.coupons.edit` | `Admin\CouponController@edit` | `web`, `auth:admin` |
| GET | `/admin/customers` | `admin.customers.index` | `Admin\CustomerController@index` | `web`, `auth:admin`, permission middleware in controller |
| GET | `/admin/customers/create` | `admin.customers.create` | `Admin\CustomerController@create` | `web`, `auth:admin`, permission middleware in controller |
| GET | `/admin/customers/{id}` | `admin.customers.show` | `Admin\CustomerController@show` | `web`, `auth:admin`, permission middleware in controller |
| GET | `/api/website` | unnamed | `Api\WebsiteController@index` | API route group |

Route-flow issues:

- P1: `Modules/Website/routes/web.php` uses a closure for `/blog`, which bypasses the required Route -> Controller flow.
- P0: `Modules/Website/routes/web.php` admin routes mostly rely only on `auth:admin`; only `Admin\CustomerController` adds permission middleware.
- P1: `Modules/Website/routes/web.php` declares `CheckoutController@momoCallback`, but the inspected controller scan did not show that method.

## 3. Controllers

Frontend controllers:

- `Modules/Website/Http/Controllers/AuthController.php`: `login`, `register`, `logout`.
- `Modules/Website/Http/Controllers/WebsiteController.php`: `home`, `help`, plus unused resource-style stubs.
- `Modules/Website/Http/Controllers/ProductController.php`: `index`, `show`, `detail`.
- `Modules/Website/Http/Controllers/PostController.php`: `index`, `detail`.
- `Modules/Website/Http/Controllers/CartController.php`: `index`.
- `Modules/Website/Http/Controllers/CheckoutController.php`: `index`, `success`.
- `Modules/Website/Http/Controllers/AccountController.php`: `index`, `orders`, `orderDetail`, `affiliate`, `profile`, `wishlist`.
- `Modules/Website/Http/Controllers/Api/WebsiteController.php`: `index`.

Admin controllers:

- `Modules/Website/Http/Controllers/Admin/AffiliateController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/HomeSettingsController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/HeaderController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/FooterController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/BannerController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/FlashSaleController.php`: `index`.
- `Modules/Website/Http/Controllers/Admin/CouponController.php`: `index`, `create`, `edit`.
- `Modules/Website/Http/Controllers/Admin/CustomerController.php`: `index`, `show`, `create`.
- `Modules/Website/Http/Controllers/Admin/ProductController.php`: `index`, but no route in `Modules/Website/routes/web.php` points to it.

Controller issues:

- P1: `Modules/Website/Http/Controllers/ProductController.php` queries `WpProduct` in `detail`, violating thin-controller and Service-layer rules.
- P1: `Modules/Website/Http/Controllers/AccountController.php` counts orders directly in `index`.
- P1: `Modules/Website/Http/Controllers/CheckoutController.php` queries `Cart` and `Order` directly in `index` and `success`.
- P2: `Modules/Website/Http/Controllers/WebsiteController.php` contains unused resource stubs (`create`, `store`, `show`, `edit`, `update`, `destroy`).

## 4. Page Blade Files

Page shells observed:

- Public/auth/cart/checkout/account: `Modules/Website/resources/views/auth/login.blade.php`, `auth/register.blade.php`, `cart/index.blade.php`, `checkout/index.blade.php`, `checkout/success.blade.php`, `account/dashboard.blade.php`, `account/orders/index.blade.php`, `account/orders/show.blade.php`, `account/affiliate.blade.php`, `pages/account/profile.blade.php`, `pages/account/wishlist.blade.php`.
- Product/blog/home/help: `pages/home/index.blade.php`, `pages/help/index.blade.php`, `pages/shop.blade.php`, `pages/blog/index.blade.php`, `pages/blog/detail.blade.php`, `products/show.blade.php`, `products/index.blade.php`, `products/detail.blade.php`.
- Admin: `pages/admin/flash-sale/index.blade.php`, `pages/admin/affiliate/index.blade.php`, `pages/admin/affiliate/product-commissions.blade.php`, `pages/admin/coupons/index.blade.php`, `pages/admin/coupons/create.blade.php`, `pages/admin/coupons/edit.blade.php`, `pages/admin/home/index.blade.php`, `pages/admin/customers/index.blade.php`, `pages/admin/customers/create.blade.php`, `pages/admin/customers/show.blade.php`, `pages/admin/header/index.blade.php`, `pages/admin/banner/index.blade.php`, `pages/admin/footer/index.blade.php`.

Page Blade issues:

- P1: `Modules/Website/resources/views/pages/admin/flash-sale/index.blade.php`, `pages/admin/home/index.blade.php`, `pages/admin/banner/index.blade.php`, `pages/admin/header/index.blade.php`, and `pages/admin/footer/index.blade.php` mount aliases like `admin.flash-sale.flash-sale-manager` rather than consistently using Website aliases.
- P1: `Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php` includes `Admin::livewire.header.partials.menu-tree-manager`, crossing module view ownership.
- P2: `Modules/Website/resources/views/pages/admin/affiliate/product-commissions.blade.php` references `admin.products.index`, but the Website admin product route is not registered in `Modules/Website/routes/web.php`.

## 5. Livewire PHP Classes

Livewire groups:

- Auth: `Auth/LoginForm.php`, `Auth/RegisterForm.php`.
- Cart/checkout: `Cart/AddToCart.php`, `Cart/CartList.php`, `Cart/CartIcon.php`, `Checkout/CheckoutForm.php`, `Checkout/OrderSummary.php`.
- Product/post/home/help: `Products/*`, `Post/*`, `Home/*`, `Help/HelpList.php`.
- Account: `Account/OrderList.php`, `Account/OrderDetail.php`, `Account/WishlistPage.php`, `Account/Affiliate/AffiliateDashboard.php`, `Account/Profile/UserProfile.php`, `Account/Profile/UserAddress.php`.
- Admin: `Admin/Affiliate/*`, `Admin/Footer/*`, `Admin/Header/*`, `Admin/FlashSale/FlashSaleManager.php`, `Admin/Customers/*`, `Admin/Home/HomeSettings.php`, `Admin/Coupon/*`, `Admin/Banner/BannerManager.php`.
- Other: `Chat/ChatWidget.php`, `Dashboard/*`, `Wishlist/WishlistIcon.php`.

Livewire issues:

- P0: `Modules/Website/Livewire/Cart/CartList.php` updates/removes `CartItem` by browser-supplied item ID without proving it belongs to the current cart.
- P0: `Modules/Website/Livewire/Admin/Customers/CustomerTable.php` toggles and deletes users directly from Livewire without method-level permission checks.
- P0: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` toggles, deletes, bulk deletes, imports, and exports coupons directly from Livewire without named permissions.
- P1: `Modules/Website/Livewire/Products/ProductDetail.php` and `Modules/Website/Livewire/Products/ProductList.php` duplicate cart item persistence instead of using `CartService`.
- P1: `Modules/Website/Livewire/Admin/Home/HomeSettings.php` performs settings persistence and product/category queries directly in Livewire instead of `HomeSettingService`.
- P1: Many homepage components query models directly, including `Home/FeaturedProducts.php`, `Home/BestSellers.php`, `Home/NewArrivals.php`, `Home/BlogHighlight.php`, `Home/HeroBanner.php`, `Home/PromoBanner.php`, and `Home/TrustBadges.php`.
- P1: `Modules/Website/Livewire/Post/PostList.php` and `Post/PostDetail.php` query `Post` and `Category` directly instead of a content service.

## 6. Livewire Blade Views

Livewire views are under `Modules/Website/resources/views/livewire/**`.

Main groups:

- `livewire/auth/*`
- `livewire/cart/*`
- `livewire/checkout/*`
- `livewire/products/*`
- `livewire/post/*`
- `livewire/home/*`
- `livewire/account/*`
- `livewire/admin/*`
- `livewire/chat/chat-widget.blade.php`
- `livewire/dashboard/*`
- `livewire/partials/pagination.blade.php`

View issues:

- P2: Several Livewire Blade files contain `@php` presentation calculations, including `components/product-card.blade.php`, `livewire/home/best-sellers.blade.php`, `livewire/home/hero-banner.blade.php`, and `livewire/cart/cart-list.blade.php`.
- P1: `Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php` is a very large settings UI with embedded JavaScript, increasing maintenance risk.
- P1: `Modules/Website/resources/views/livewire/admin/header/partials/*.blade.php` include `Admin::` partials from inside Website views.

## 7. Services and Public Methods

Root Website services:

- `ProductService`: `getFeaturedProducts`, `getNewArrivals`, `getFlashSaleProducts`, `getBestSellers`, `getBestSellingProducts`.
- `CartService`: `getCart`, `addItem`, `updateQuantity`, `removeItem`, `applyCoupon`, `removeCoupon`, `getCartSummary`.
- `CheckoutService`: `createOrder`.
- `CategoryService`: `getHomeCategories`.
- `WishlistService`: `getUserWishlistIds`, `toggle`, `count`, `getWishlistItems`.
- `ContentService`: `getLatestPosts`.
- `MomoService`: `createPayment`.
- `HeaderMenuService`: `getMenuTreeByLocation`, `createItem`, `updateItem`, `deleteItem`, `reorderItems`.
- `AffiliateRankService`: `checkAndUpdateRank`.
- `FlashSaleService`: `getAll`, `createFlashSale`, `updateFlashSale`, `delete`.
- `SettingsService`: `get`, `set`, `updateMany`.
- `FooterService`: social/footer column CRUD and ordering methods.
- `AffiliateService`: affiliate ref/stat/history/detail/commission methods.
- `AdminAffiliateService`: `getCommissions`, `reject`, `getOrderDetail`, `approve`.
- `MarketingService`: `getHeroSlides`, `getPromoBanner`, `getFlashSaleConfig`.
- `BannerService`: `getAll`, `save`, `delete`.
- `Account/ProfileService`: `updateInfo`, `updatePassword`.
- `Account/AddressService`: `getUserAddresses`, `create`, `update`, `delete`, `setDefault`.

Nested duplicate/system-like services under `Modules/Website/Services/Services`:

- `DatabaseService`, `HomeSettingService`, `AuthService`, `Database/DbConnectionService`, `Env/*`, duplicate `AffiliateRankService`, duplicate `FlashSaleService`, duplicate `AdminAffiliateService`, duplicate `BannerService`, `ChatService`.

Service issues:

- P0: `Modules/Website/Services/Services/DatabaseService.php` contains backup, restore, truncate, drop, download-path, and restore-from-file methods inside Website. This belongs to System, not Website, and matches roadmap P0 dangerous-operation concerns.
- P1: `Modules/Website/Services/Services/*` duplicates root services and crosses into Auth, Chat, System, and Env responsibilities.
- P1: `Modules/Website/Services/CheckoutService.php` validates stock before the transaction and then decrements inside the transaction without row locks, so concurrent checkout can oversell.
- P1: `Modules/Website/Services/CheckoutService.php` deletes `$cart` and then sets `coupon_id` and saves the deleted cart.
- P1: `Modules/Website/Services/CartService.php` `removeItem($itemId)` deletes by ID only, without cart ownership validation.
- P1: `Modules/Website/Services/Account/AddressService.php` multi-write default-address changes are not wrapped in transactions.

## 8. Models and Database Tables

Models observed:

- `AffiliateLevel`, `AffiliateScheme`, `Banner`, `Cart`, `CartItem`, `Category`, `Coupon`, `FlashSale`, `FlashSaleItem`, `FooterColumn`, `FooterLink`, `HeaderMenu`, `HeaderMenuItem`, `Newsletter`, `Order`, `OrderHistory`, `OrderItem`, `Post`, `Review`, `Setting`, `SocialLink`, `Tag`, `UserAddress`, `Website`, `Wishlist`, `WpProduct`.

Website migrations observed:

- `affiliate_levels`
- `coupons`
- `carts`
- `cart_items`
- `wp_tags`
- `newsletters`
- `reviews`
- `wp_banners`
- `wp_flash_sales`
- `wp_flash_sale_items`
- `footer_columns`
- `footer_links`
- `social_links`
- `wishlists`
- `wp_affiliate_schemes`
- `users` affiliate fields via `2026_04_27_214350_add_affilate_fields_to_users_table.php`

Model/table issues:

- P1: `Modules/Website/Models/Category.php`, `Post.php`, `WpProduct.php`, `Order.php`, and related migrations duplicate likely canonical Product/Post/Order/Category ownership described in `ROADMAP.md`.
- P1: Migrations with filenames like `Modules/Website/database/migrations/-0001_11_30_000018_create_coupons_table.php` have malformed negative-year timestamps, risking nondeterministic/failing fresh installs.
- P2: `Modules/Website/Models/Website.php` appears to be a placeholder model unless callers exist outside the inspected route flow.

## 9. Import/Export Classes

No dedicated Website import/export structure was found:

- No `Modules/Website/Services/ImportExport.php`.
- No `Modules/Website/Import`, `Modules/Website/Export`, `Modules/Website/Imports`, or `Modules/Website/Exports` files were found.

Current import/export behavior:

- `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` implements JSON export/import directly in Livewire.

Import/export issues:

- P1: Coupon import/export bypasses the shared `Modules/Shared/Services/ImportExport` foundation required by project standards.
- P1: Coupon export uses `$this->getQuery()->get()` and can load all matching coupons into memory.
- P1: Coupon import validates only file type/size, not row-level required fields, enum values, dates, duplicate behavior, null-overwrite behavior, or dry-run behavior.
- P1: Coupon import exposes raw exception messages via `$this->addError('importFile', 'Lỗi: ' . $e->getMessage())`.

## 10. Authorization/Security Risks

- P0: `Modules/Website/routes/web.php` admin routes for affiliate, homepage settings, header, footer, banners, flash sales, and coupons lack named permission middleware.
- P0: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` mutating methods (`toggleStatus`, `deleteSelected`, `delete`, `import`) have no server-side permission checks.
- P0: `Modules/Website/Livewire/Admin/Customers/CustomerTable.php` mutating methods (`toggleStatus`, `deleteSelected`, `delete`) have no method-level permission checks.
- P0: `Modules/Website/Livewire/Admin/Home/HomeSettings.php`, `Admin/Header/*`, `Admin/Footer/*`, `Admin/Banner/BannerManager.php`, and `Admin/FlashSale/FlashSaleManager.php` mutate website content/settings without visible named permission checks.
- P0: `Modules/Website/Livewire/Cart/CartList.php` and `Modules/Website/Services/CartService.php` allow item ID based cart changes without proven cart/session ownership.
- P0: `Modules/Website/Services/Services/DatabaseService.php` is a dangerous database-operation service inside Website.
- P1: `Modules/Website/Livewire/Auth/LoginForm.php` uses `Auth::attempt` without observed throttling/rate limiting.
- P1: `Modules/Website/Livewire/Products/ProductDetail.php` stores `ref` directly in session without visible validation that it identifies a valid affiliate user.

## 11. Validation Problems

- P1: `Modules/Website/Http/Requests/CheckoutRequest.php` exists, but checkout submission in `Modules/Website/Livewire/Checkout/CheckoutForm.php` uses Livewire validation; controller request validation is not part of the active flow.
- P1: `Modules/Website/Livewire/Admin/Home/HomeSettings.php` validates only image upload, not layout enum values, selected product/category IDs, counts, URLs, trust badge shape, or newsletter HTML/content.
- P1: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` import lacks row validation for `code`, `type`, `value`, date ordering, usage limits, and active flags.
- P1: `Modules/Website/Livewire/Admin/Coupon/CouponForm.php` does not validate `type` as an enum or require valid date ordering between `starts_at` and `expires_at`.
- P1: `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php` validates address fields minimally and does not validate default-address state with a transaction.
- P1: `Modules/Website/Livewire/Home/NewsletterSignup.php` writes newsletters directly and relies on component-level validation, not a service invariant.

## 12. Transaction Risks

- P1: `Modules/Website/Services/CheckoutService.php` stock validation happens before transaction and product decrement lacks locking.
- P1: `Modules/Website/Services/CheckoutService.php` cart cleanup is inconsistent because it deletes the cart and then saves it.
- P1: `Modules/Website/Livewire/Products/ProductDetail.php` and `Modules/Website/Livewire/Products/ProductList.php` perform multi-step cart updates outside `CartService` and outside transactions.
- P1: `Modules/Website/Livewire/Admin/Home/HomeSettings.php` saves many settings independently; partial failure can leave mixed homepage configuration.
- P1: `Modules/Website/Livewire/Admin/Customers/CustomerDetail.php` address default reset and address create/update are not transactional.
- P1: `Modules/Website/Services/FooterService.php` order updates loop through multiple records without wrapping every reorder method in a transaction.

## 13. N+1/Query Performance Risks

- P1: `Modules/Website/Providers/WebsiteServiceProvider.php` runs header/footer queries via view composers on every frontend page without an explicit cache policy.
- P1: `Modules/Website/Livewire/Products/ProductList.php` queries categories and product pagination directly in `render`; category counts and product-card nested Livewire components can multiply queries.
- P1: `Modules/Website/resources/views/components/product-card.blade.php` mounts wishlist and add-to-cart Livewire components for each product card.
- P1: `Modules/Website/Livewire/Home/*` components perform repeated settings/product/post/banner queries rather than using cached service data.
- P1: `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` `updatedSelectAll()` plucks all matching IDs, not only the current page.
- P1: `Modules/Website/Livewire/Admin/Customers/CustomerTable.php` uses `paginate(9999)` when `perPage === 'all'`.
- P2: `Modules/Website/Livewire/Dashboard/RevenueChart.php` queries one day at a time in a loop.

## 14. Duplicate Logic

- P1: Cart add/update logic exists in `Modules/Website/Services/CartService.php`, `Livewire/Products/ProductDetail.php`, `Livewire/Products/ProductList.php`, and `Livewire/Cart/AddToCart.php`.
- P1: Banner services are duplicated in `Modules/Website/Services/BannerService.php` and `Modules/Website/Services/Services/BannerService.php`.
- P1: Flash-sale services are duplicated in `Modules/Website/Services/FlashSaleService.php` and `Modules/Website/Services/Services/FlashSaleService.php`.
- P1: Affiliate-rank services are duplicated in `Modules/Website/Services/AffiliateRankService.php` and `Modules/Website/Services/Services/AffiliateRankService.php`.
- P1: Admin affiliate services are duplicated in `Modules/Website/Services/AdminAffiliateService.php` and `Modules/Website/Services/Services/AdminAffiliateService.php`.
- P1: Website owns duplicate-looking models for product, post, category, order, review, tag, and user address concepts that likely belong to canonical Product/Post/Category/Order/Account modules.

## 15. Files That Look Unused

These require confirmation with route/component tests before deletion:

- P2: `Modules/Website/Http/Controllers/Admin/ProductController.php` appears unregistered in `Modules/Website/routes/web.php`.
- P2: `Modules/Website/resources/views/admin/products/index.blade.php` appears tied to the unregistered admin product controller.
- P2: `Modules/Website/resources/views/pages/admin/affiliate/product-commissions.blade.php` appears unregistered.
- P2: `Modules/Website/resources/views/pages/dashboard.blade.php` has no observed Website route.
- P2: `Modules/Website/resources/views/admin.blade.php` and `Modules/Website/resources/views/website.blade.php` look like placeholder/module scaffold views.
- P2: `Modules/Website/Models/Website.php` looks like a scaffold model unless referenced outside the inspected flow.
- P2: `Modules/Website/Http/Requests/CheckoutRequest.php` appears unused by the active Livewire checkout flow.
- P2: `Modules/Website/Services/Services/*` looks like copied System/Admin/Auth/Chat functionality rather than active Website storefront functionality.
- P2: `Modules/Website/Http/Middleware/TrackAffiliate.php` and `ShareWishlistData.php` were not observed in `Modules/Website/routes/web.php`.

## 16. Refactor Plan

### P0 Critical

- P0: Add named permission middleware and Livewire method-level authorization for all admin Website routes/actions in `Modules/Website/routes/web.php` and `Modules/Website/Livewire/Admin/**`.
- P0: Move or disable dangerous database/env/system services under `Modules/Website/Services/Services/DatabaseService.php` and `Modules/Website/Services/Services/Env/**`; align with System-module P0 hardening before exposing any UI that calls them.
- P0: Enforce cart ownership in `Modules/Website/Services/CartService.php` and callers before updating/removing cart items by ID.
- P0: Add account/order ownership checks around `Modules/Website/Livewire/Account/OrderDetail.php` and `Modules/Website/Services/AffiliateService.php` order detail access.

### P1 Important

- P1: Consolidate cart behavior into `Modules/Website/Services/CartService.php`; migrate `Livewire/Products/ProductDetail.php`, `ProductList.php`, and `Cart/AddToCart.php` to use it.
- P1: Fix checkout consistency in `Modules/Website/Services/CheckoutService.php`: validate and lock stock inside the transaction, avoid saving deleted carts, and add retry/idempotency/payment-callback tests.
- P1: Move product/post/home queries out of Livewire/controllers into `ProductService`, `ContentService`, `MarketingService`, and `HomeSettingService`.
- P1: Replace coupon JSON import/export in `Modules/Website/Livewire/Admin/Coupon/CouponTable.php` with `Modules/Website/Services/ImportExport.php` and the shared import/export panel.
- P1: Repair malformed negative-year migration names in `Modules/Website/database/migrations/*` after migration-order analysis.
- P1: Define canonical ownership boundaries for Website versus Product, Post, Order, Category, Account, Chat, System, and Admin modules; migrate duplicate models/services after tests are in place.
- P1: Standardize Livewire aliases in Website page blades so Website components are mounted through one consistent namespace.
- P1: Add focused tests for route auth/permissions, cart ownership denial, checkout transaction rollback, coupon import validation, and admin mutation denial.

### P2 Nice To Have

- P2: Remove confirmed unused scaffold files after route/component tests prove they are unreachable.
- P2: Add cache policy for header/footer/homepage settings in `Modules/Website/Providers/WebsiteServiceProvider.php` and frontend service classes.
- P2: Reduce Blade `@php` calculations by moving display-only helpers into components/accessors where appropriate.
- P2: Replace unbounded `All`/large select-all behavior in admin tables with page-scoped selection or queued/batched operations.
- P2: Generate a Website module architecture catalog in CI after ownership boundaries are clarified.
