# Website Rebuild Specification

This specification targets `Modules/Website`. The prompt mentions “Category module”, but `<Module_Name>` is explicitly `Website`; therefore this spec applies to Website. The Category ownership decisions below are treated only as cross-module dependency concerns.

## 1. Goal

The rebuilt/refactored Website module must remain the public storefront and website-CMS surface while becoming safer, thinner, and more modular.

Goals:

- Preserve current storefront capabilities: home, help, blog, product listing/detail, cart, checkout, account, wishlist, affiliate dashboard, and Website admin settings. Reference: `docs/modules/Website/ANALYSIS.md` sections 1-5.
- Close P0 authorization and ownership gaps before broad cleanup. Reference: `docs/modules/Website/ANALYSIS.md` section 10; `docs/modules/Website/REFACTOR_PLAN.md` P0-01 through P0-05.
- Enforce Laravel 12 and Livewire 3 flow: Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import -> Export -> Model -> Migration. Reference: `docs/CODEX_BOOTSTRAP.md` Architecture Rules; `docs/modules/Website/REFACTOR_PLAN.md` P1-03, P1-08, P1-09.
- Move database queries, transactions, business rules, import/export, and persistence out of controllers, Blade, and Livewire into services. Reference: `docs/modules/Website/ANALYSIS.md` sections 3, 5, 7, 11, 12; `docs/modules/Website/REFACTOR_PLAN.md` P1-03, P1-06, P1-08, P1-09, P1-12.
- Replace ad hoc coupon JSON import/export with the shared import/export foundation. Reference: `docs/modules/Website/ANALYSIS.md` section 9; `docs/modules/Website/REFACTOR_PLAN.md` P1-10.
- Define clean boundaries between Website and canonical Product, Post, Order, Category, Account, Chat, System, and Admin modules before deleting or moving duplicated models/services. Reference: `ROADMAP.md` P1-01/P1-02; `docs/modules/Website/ANALYSIS.md` sections 8, 14; `docs/modules/Website/REFACTOR_PLAN.md` P1-15, P1-16.

## 2. Target Architecture

Target flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
```

Architecture decisions:

- Routes define URL, name, middleware, and controller action only. The `/blog` closure must move to `PostController@index`. Reference: `ANALYSIS.md` route-flow P1 issue; `REFACTOR_PLAN.md` P1-01.
- Controllers return views, redirects, and scalar parameters only. Product/account/checkout queries must move to services. Reference: `ANALYSIS.md` controller issues; `REFACTOR_PLAN.md` P1-03.
- Page Blades are layout shells that mount Livewire components. Website admin pages must mount canonical Website aliases, not accidental `admin.*` aliases. Reference: `ANALYSIS.md` page Blade issues; `REFACTOR_PLAN.md` P1-04.
- Livewire components own UI state, validation, events, and service calls only. They must not query models, update models directly, or open transactions. Reference: `ANALYSIS.md` Livewire issues; `REFACTOR_PLAN.md` P1-06, P1-08, P1-09.
- Shared UI belongs in `Modules/Shared` when reused across modules. Website views must not include `Admin::livewire.*` partials. Reference: `ANALYSIS.md` view issues; `REFACTOR_PLAN.md` P1-05.
- Services own queries, business rules, authorization-dependent lookups, transactions, persistence, normalization, import/export orchestration, and cache invalidation. Reference: `AI_PROJECT_CONTEXT.md` Service rules; `REFACTOR_PLAN.md` P1-03, P1-08, P1-09, P1-12.
- Import/export flows must use `shared.import-export.panel` -> `Modules/Website/Services/ImportExport.php` -> optional import/export classes -> `Modules/Shared/Services/ImportExport`. Reference: `CODEX_BOOTSTRAP.md` Import/Export Rules; `REFACTOR_PLAN.md` P1-10.
- Models remain ORM definitions with fillable, casts, relationships, scopes, and accessors only. Reference: `AI_PROJECT_CONTEXT.md` Model rules; `REFACTOR_PLAN.md` P1-16.
- Migration cleanup must be delayed until canonical ownership is confirmed. Reference: `ANALYSIS.md` model/table issues; `REFACTOR_PLAN.md` P1-17 and Risk Control.

## 3. Database Design

### Tables

Current Website-owned or Website-used tables observed:

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
- `users` affiliate fields from `Modules/Website/database/migrations/2026_04_27_214350_add_affilate_fields_to_users_table.php`

Reference: `ANALYSIS.md` section 8.

Needs confirmation before coding:

- Exact column lists must be extracted from each migration before implementation. The analysis recorded table names but not full column definitions. Reference: `ANALYSIS.md` section 8; `REFACTOR_PLAN.md` P1-17.
- Canonical table ownership for product, post, order, category, review, tag, and address concepts must be confirmed before schema changes. Reference: `REFACTOR_PLAN.md` P1-16 and Risk Control.

### Columns

Column design principles:

- Money columns must be `decimal`, never `float`. Reference: `AI_PROJECT_CONTEXT.md` Database Standard; supports `REFACTOR_PLAN.md` P1-07/P1-10.
- Status fields must have documented allowed values and service-level validation. Reference: `ANALYSIS.md` validation problems; `REFACTOR_PLAN.md` P1-11.
- JSON settings fields are acceptable for flexible, shallow homepage/header/footer settings only when invalidation and validation are explicit. Reference: `AI_PROJECT_CONTEXT.md` Database Standard; `REFACTOR_PLAN.md` P1-09, P1-13.
- Cart/order snapshot fields must remain stable for historical order display. Reference: `ANALYSIS.md` transaction risks; `REFACTOR_PLAN.md` P1-07.

Needs confirmation before coding:

- Coupon columns for import/export must be confirmed from `Modules/Website/database/migrations/-0001_11_30_000018_create_coupons_table.php` before mapping headers. Reference: `REFACTOR_PLAN.md` P1-10.
- Affiliate commission columns and status values must be confirmed before changing affiliate approval/rejection. Reference: `REFACTOR_PLAN.md` P0-02, P0-05, P1-16.

### Indexes

Target indexes:

- `coupons.code` unique index for import duplicate handling. Reference: `ANALYSIS.md` coupon import/export issues; `REFACTOR_PLAN.md` P1-10.
- Cart lookup indexes on `carts.user_id`, `carts.session_id`, and `cart_items.cart_id/product_id`. Reference: `ANALYSIS.md` cart ownership issues; `REFACTOR_PLAN.md` P0-03, P1-06.
- Product/post/category filter indexes must exist in canonical owning modules or Website tables before optimizing listing queries. Reference: `ANALYSIS.md` N+1/query risks; `REFACTOR_PLAN.md` P1-08, P1-13, P1-16.
- Settings/menu/footer indexes on key/location/sort/order/status fields should support view composer and homepage reads. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-13.

Needs confirmation before coding:

- Do not add indexes blindly. Verify existing indexes in migrations and production query patterns first. Reference: `AI_PROJECT_CONTEXT.md` Indexes; `REFACTOR_PLAN.md` P1-17.

### Foreign Keys

Target foreign key policy:

- Cart items must reference their cart and product; deletes must not allow cross-cart mutation. Reference: `ANALYSIS.md` P0 cart ownership issue; `REFACTOR_PLAN.md` P0-03.
- Flash sale items must reference flash sales and products. Reference: `ANALYSIS.md` model/table list; `REFACTOR_PLAN.md` P1-16/P1-17.
- Footer links must reference footer columns. Reference: `ANALYSIS.md` model/table list; `REFACTOR_PLAN.md` P1-12.
- Wishlists must reference users and products. Reference: `ANALYSIS.md` model/table list; `REFACTOR_PLAN.md` P1-16.
- Orders/order items/order histories must follow canonical Order ownership after confirmation. Reference: `REFACTOR_PLAN.md` P1-16.

Needs confirmation before coding:

- Foreign keys must be added or changed only after canonical module ownership and current production constraints are confirmed. Reference: `REFACTOR_PLAN.md` Risk Control.

### Constraints

Target constraints:

- Coupon `code` must be unique and normalized consistently. Reference: `REFACTOR_PLAN.md` P1-10, P1-11.
- Cart item quantity must be positive. Reference: `ANALYSIS.md` cart/checkout risks; `REFACTOR_PLAN.md` P0-03, P1-06.
- Coupon type and order/payment/commission statuses must be enum-like validated values in services; database constraints may be added only after allowed values are confirmed. Reference: `ANALYSIS.md` validation problems; `REFACTOR_PLAN.md` P1-11.
- Destructive database/env operations must not remain reachable from Website tables or services. Reference: `ANALYSIS.md` service/security risks; `REFACTOR_PLAN.md` P0-04.

### Migration Notes

- Negative-year migration filenames under `Modules/Website/database/migrations/-0001_11_30_*` must be repaired in a dedicated migration hygiene pass. Reference: `ANALYSIS.md` model/table issues; `REFACTOR_PLAN.md` P1-17.
- Do not rename migrations before route/component tests and migration smoke tests exist. Reference: `REFACTOR_PLAN.md` Risk Control.
- Do not change table ownership during migration cleanup. Ownership decisions must come first. Reference: `REFACTOR_PLAN.md` P1-16/P1-17.

## 4. Model Design

### Model Classes

Current Website models:

- `Modules/Website/Models/AffiliateLevel.php`
- `Modules/Website/Models/AffiliateScheme.php`
- `Modules/Website/Models/Banner.php`
- `Modules/Website/Models/Cart.php`
- `Modules/Website/Models/CartItem.php`
- `Modules/Website/Models/Category.php`
- `Modules/Website/Models/Coupon.php`
- `Modules/Website/Models/FlashSale.php`
- `Modules/Website/Models/FlashSaleItem.php`
- `Modules/Website/Models/FooterColumn.php`
- `Modules/Website/Models/FooterLink.php`
- `Modules/Website/Models/HeaderMenu.php`
- `Modules/Website/Models/HeaderMenuItem.php`
- `Modules/Website/Models/Newsletter.php`
- `Modules/Website/Models/Order.php`
- `Modules/Website/Models/OrderHistory.php`
- `Modules/Website/Models/OrderItem.php`
- `Modules/Website/Models/Post.php`
- `Modules/Website/Models/Review.php`
- `Modules/Website/Models/Setting.php`
- `Modules/Website/Models/SocialLink.php`
- `Modules/Website/Models/Tag.php`
- `Modules/Website/Models/UserAddress.php`
- `Modules/Website/Models/Website.php`
- `Modules/Website/Models/Wishlist.php`
- `Modules/Website/Models/WpProduct.php`

Reference: `ANALYSIS.md` section 8.

Needs confirmation before coding:

- `Category`, `Post`, `WpProduct`, `Order`, `Review`, `Tag`, and `UserAddress` may move to or depend on canonical modules. Do not delete or rename before ownership is confirmed. Reference: `REFACTOR_PLAN.md` P1-16.
- `Website.php` appears scaffold-like and should not be removed until references are checked. Reference: `ANALYSIS.md` section 15; `REFACTOR_PLAN.md` P2-03.

### Fillable Fields

Design decisions:

- Each model must explicitly define `$fillable` for persisted fields. Reference: `AI_PROJECT_CONTEXT.md` Model rules.
- Import/export defaults must use `$fillable` minus explicit sensitive exclusions. Reference: `AI_PROJECT_CONTEXT.md` Export architecture; `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Exact `$fillable` lists must be derived from current models and migrations before implementation. Reference: `REFACTOR_PLAN.md` P1-17.

### Casts

Target cast policy:

- Money fields: decimal casts with appropriate precision. Reference: `AI_PROJECT_CONTEXT.md` Currency and Database rules; `REFACTOR_PLAN.md` P1-07/P1-10.
- Booleans: `is_active`, `is_default`, approval flags, show/hide flags. Reference: `ANALYSIS.md` validation problems; `REFACTOR_PLAN.md` P1-11.
- Dates: coupon `starts_at`, `expires_at`, flash sale start/end times, order timestamps where custom casts are needed. Reference: `ANALYSIS.md` coupon and flash-sale concerns; `REFACTOR_PLAN.md` P1-10/P1-11.
- Arrays/JSON: settings values only where schema confirms JSON-like values. Reference: `REFACTOR_PLAN.md` P1-09/P1-13.

Needs confirmation before coding:

- Do not add casts until actual column types are inspected. Reference: `REFACTOR_PLAN.md` P1-17.

### Relationships

Target relationships:

- `Cart` has many `CartItem`; `CartItem` belongs to `Cart` and product model. Reference: `REFACTOR_PLAN.md` P0-03/P1-06.
- `Order` has many `OrderItem` and `OrderHistory`; order ownership must be scoped by user or affiliate. Reference: `REFACTOR_PLAN.md` P0-05/P1-07.
- `FlashSale` has many `FlashSaleItem`; item belongs to product. Reference: `REFACTOR_PLAN.md` P1-12/P1-16.
- `FooterColumn` has many `FooterLink`; ordering must be service-owned. Reference: `REFACTOR_PLAN.md` P1-12.
- `HeaderMenu` has many `HeaderMenuItem`; menu tree construction belongs in `HeaderMenuService`. Reference: `REFACTOR_PLAN.md` P1-09/P1-13.
- `Wishlist` belongs to user and product. Reference: `ANALYSIS.md` model list; `REFACTOR_PLAN.md` P1-16.

Needs confirmation before coding:

- Product, category, post, tag, review, and order relationships must be aligned with canonical module ownership. Reference: `REFACTOR_PLAN.md` P1-16.

### Scopes

Recommended scopes:

- `active()` for banners, products, categories, footer columns, social links, flash sales, and coupons where models remain in Website. Reference: `REFACTOR_PLAN.md` P1-08/P1-13.
- `published()` for posts if Website retains `Post`. Reference: `ANALYSIS.md` Livewire direct post queries; `REFACTOR_PLAN.md` P1-08/P1-16.
- `forUser($userId)` for carts, wishlists, addresses, and orders where models remain in Website. Reference: `REFACTOR_PLAN.md` P0-03/P0-05.

Needs confirmation before coding:

- Add scopes only when they match existing query patterns and do not duplicate canonical module service behavior. Reference: `REFACTOR_PLAN.md` P1-16.

### Accessors / Mutators

Allowed accessors/mutators:

- Product final display price accessor only if canonical product ownership confirms Website retains product display behavior. Reference: `ANALYSIS.md` duplicate cart price logic; `REFACTOR_PLAN.md` P1-06/P1-16.
- Coupon code normalization mutator may uppercase/trim codes if existing behavior depends on uppercase codes. Reference: `ANALYSIS.md` coupon validation/import issues; `REFACTOR_PLAN.md` P1-10/P1-11.

Needs confirmation before coding:

- Do not move business rules like discount calculation, stock decrement, commission calculation, or order total calculation into accessors/mutators. Those remain services. Reference: `AI_PROJECT_CONTEXT.md` Model rules; `REFACTOR_PLAN.md` P1-07.

## 5. Service Design

### Service Classes

Target canonical services in Website:

- `Modules/Website/Services/CartService.php`
- `Modules/Website/Services/CheckoutService.php`
- `Modules/Website/Services/ProductService.php`
- `Modules/Website/Services/ContentService.php`
- `Modules/Website/Services/MarketingService.php`
- `Modules/Website/Services/CategoryService.php`
- `Modules/Website/Services/SettingsService.php`
- `Modules/Website/Services/HeaderMenuService.php`
- `Modules/Website/Services/FooterService.php`
- `Modules/Website/Services/WishlistService.php`
- `Modules/Website/Services/AffiliateService.php`
- `Modules/Website/Services/AdminAffiliateService.php`
- `Modules/Website/Services/BannerService.php`
- `Modules/Website/Services/FlashSaleService.php`
- `Modules/Website/Services/Account/ProfileService.php`
- `Modules/Website/Services/Account/AddressService.php`
- `Modules/Website/Services/ImportExport.php`

Reference: `ANALYSIS.md` section 7; `REFACTOR_PLAN.md` P1-03 through P1-16.

Services to migrate/disable from Website:

- `Modules/Website/Services/Services/DatabaseService.php`
- `Modules/Website/Services/Services/Database/DbConnectionService.php`
- `Modules/Website/Services/Services/Env/*`
- Duplicate nested `BannerService`, `FlashSaleService`, `AffiliateRankService`, `AdminAffiliateService`
- `Modules/Website/Services/Services/AuthService.php`
- `Modules/Website/Services/Services/ChatService.php`

Reference: `ANALYSIS.md` service issues; `REFACTOR_PLAN.md` P0-04, P1-15.

### Public Methods

Target methods:

- `CartService`: `getCart`, `addItem`, `updateQuantity`, `removeItem`, `applyCoupon`, `removeCoupon`, `getCartSummary`, plus ownership-safe private helpers. Reference: `REFACTOR_PLAN.md` P0-03/P1-06.
- `CheckoutService`: `createOrder`, callback/idempotency methods if MoMo callback remains Website-owned. Reference: `REFACTOR_PLAN.md` P1-02/P1-07.
- `ProductService`: product listing, detail, related products, featured/new/best-seller/flash-sale product reads. Reference: `REFACTOR_PLAN.md` P1-03/P1-08.
- `ContentService`: post listing, post detail, related posts, blog highlight reads. Reference: `REFACTOR_PLAN.md` P1-08.
- `SettingsService`: `get`, `set`, `updateMany`, typed setting readers, transactional multi-setting updates, cache invalidation. Reference: `REFACTOR_PLAN.md` P1-09/P1-13.
- `HeaderMenuService`: menu tree read, item create/update/delete/reorder, cache invalidation. Reference: `REFACTOR_PLAN.md` P1-09/P1-13.
- `FooterService`: footer column/social CRUD, ordering, frontend/admin reads, cache invalidation. Reference: `REFACTOR_PLAN.md` P1-09/P1-12/P1-13.
- `ImportExport`: coupon import/export service entry point. Reference: `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Payment callback method names and MoMo status rules must be confirmed before writing code. Reference: `REFACTOR_PLAN.md` P1-02.

### Responsibilities

- Services own all queries currently found in controllers/Livewire. Reference: `ANALYSIS.md` controller and Livewire issues; `REFACTOR_PLAN.md` P1-03/P1-08.
- Services own all multi-record writes and transaction boundaries. Reference: `ANALYSIS.md` transaction risks; `REFACTOR_PLAN.md` P1-12.
- Services own cache invalidation after settings/menu/footer/banner/homepage mutations. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-13.
- Services must not use Livewire state, return views, or expose raw exceptions. Reference: `CODEX_BOOTSTRAP.md` Architecture Rules; `REFACTOR_PLAN.md` P1-18.

### Transaction Boundaries

Transactions required:

- Checkout order creation: stock validation/locking, order, order items, order history, coupon usage, cart cleanup. Reference: `ANALYSIS.md` transaction risks; `REFACTOR_PLAN.md` P1-07.
- Cart merge/add/update/remove when multiple rows can change. Reference: `REFACTOR_PLAN.md` P0-03/P1-06/P1-12.
- Address default reset plus create/update/delete. Reference: `ANALYSIS.md` transaction risks; `REFACTOR_PLAN.md` P1-12.
- Footer column/link/social ordering. Reference: `ANALYSIS.md` transaction risks; `REFACTOR_PLAN.md` P1-12.
- Multi-key homepage/header/footer settings saves. Reference: `REFACTOR_PLAN.md` P1-09/P1-12.
- Coupon import persistence. Reference: `REFACTOR_PLAN.md` P1-10.
- Affiliate commission approval/rejection if it affects orders/users/commission records. Reference: `REFACTOR_PLAN.md` P0-02/P0-05/P1-12.

### Business Rules

- Cart item IDs must be resolved through the current cart. Reference: `REFACTOR_PLAN.md` P0-03.
- Checkout stock must be validated inside the transaction with row locking. Reference: `REFACTOR_PLAN.md` P1-07.
- Coupon codes must be unique and normalized. Reference: `REFACTOR_PLAN.md` P1-10/P1-11.
- Admin mutations require named permissions and Livewire method protection. Reference: `REFACTOR_PLAN.md` P0-01/P0-02.
- Account and affiliate order details require ownership checks. Reference: `REFACTOR_PLAN.md` P0-05.
- System/database/env operations must not be reachable from Website. Reference: `REFACTOR_PLAN.md` P0-04.

## 6. Livewire Design

### Component List

Retain and refactor existing component groups:

- Auth: `LoginForm`, `RegisterForm`
- Cart/checkout: `AddToCart`, `CartList`, `CartIcon`, `CheckoutForm`, `OrderSummary`
- Products/posts/home/help: `ProductList`, `ProductDetail`, `PostList`, `PostDetail`, `Home/*`, `HelpList`
- Account: `OrderList`, `OrderDetail`, `WishlistPage`, `AffiliateDashboard`, `UserProfile`, `UserAddress`
- Admin: Coupon, Customer, Home, Header, Footer, Banner, FlashSale, Affiliate components
- Other: `ChatWidget`, dashboard widgets, `WishlistIcon`

Reference: `ANALYSIS.md` section 5.

Needs confirmation before coding:

- `ChatWidget` ownership may belong to Chat module, not Website. Reference: `REFACTOR_PLAN.md` P1-15/P1-16.

### State Properties

State rules:

- State may hold search terms, filters, sort keys, pagination size, selected current-page IDs, form fields, modal flags, and upload handles. Reference: `CODEX_BOOTSTRAP.md` Livewire responsibilities; `REFACTOR_PLAN.md` P1-14.
- State must not contain trusted authorization decisions, raw model ownership assumptions, arbitrary file paths, table names, or command identifiers. Reference: `CODEX_BOOTSTRAP.md` Security Gates; `REFACTOR_PLAN.md` P0-03/P0-04.

### Validation Rules

Livewire validates UI/form/upload shape:

- Login/register form shape and throttling. Reference: `REFACTOR_PLAN.md` P1-18.
- Coupon form fields, type enum, date ordering, numeric ranges. Reference: `REFACTOR_PLAN.md` P1-11.
- Homepage settings: layout enum values, selected product/category IDs, counts, URLs, badge shape, newsletter content. Reference: `ANALYSIS.md` validation problems; `REFACTOR_PLAN.md` P1-11.
- Customer profile/address fields. Reference: `REFACTOR_PLAN.md` P1-11/P1-12.
- Checkout form fields, while totals, stock, coupons, and order state remain service-owned. Reference: `REFACTOR_PLAN.md` P1-07/P1-11.
- Import upload constraints remain in the shared import/export panel; row validation belongs to import service/classes. Reference: `REFACTOR_PLAN.md` P1-10.

### Events

Allowed events:

- `cart-updated` after `CartService` succeeds. Reference: `REFACTOR_PLAN.md` P1-06.
- Notification events after authorized admin/service mutations. Reference: `REFACTOR_PLAN.md` P0-02.
- Search/sort update events between product list/search/sort components, with service-owned query execution. Reference: `REFACTOR_PLAN.md` P1-08.

Needs confirmation before coding:

- Event names must preserve current frontend behavior unless tests approve changes. Reference: `REFACTOR_PLAN.md` Risk Control.

### Pagination

- Default server-side pagination through services. Reference: `AI_PROJECT_CONTEXT.md` Pagination; `REFACTOR_PLAN.md` P1-14.
- `All` must be guarded, capped, or disabled for large datasets. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-14.
- Select-all defaults to current page only unless an explicit global mode is designed and confirmed. Reference: `REFACTOR_PLAN.md` P1-14.

### Search/Filter/Sort Behavior

- Product search/filter/sort belongs in `ProductService`; Livewire holds state only. Reference: `REFACTOR_PLAN.md` P1-08.
- Post/category filtering belongs in `ContentService`; route closure removal must preserve category query behavior. Reference: `REFACTOR_PLAN.md` P1-01/P1-08.
- Coupon/customer admin search, filter, sort, and pagination belong in services. Reference: `REFACTOR_PLAN.md` P1-14.

## 7. Blade/UI Design

### Page Blade Files

Page Blades remain shells:

- `Modules/Website/resources/views/pages/home/index.blade.php`
- `Modules/Website/resources/views/pages/help/index.blade.php`
- `Modules/Website/resources/views/pages/shop.blade.php`
- `Modules/Website/resources/views/pages/blog/index.blade.php`
- `Modules/Website/resources/views/pages/blog/detail.blade.php`
- `Modules/Website/resources/views/cart/index.blade.php`
- `Modules/Website/resources/views/checkout/index.blade.php`
- `Modules/Website/resources/views/checkout/success.blade.php`
- `Modules/Website/resources/views/account/*`
- `Modules/Website/resources/views/pages/account/*`
- `Modules/Website/resources/views/pages/admin/*`

Reference: `ANALYSIS.md` section 4.

### Livewire Blade Files

Livewire Blade files remain under `Modules/Website/resources/views/livewire/**`.

Design decisions:

- Remove cross-module `Admin::livewire.*` includes. Reference: `REFACTOR_PLAN.md` P1-05.
- Split large admin homepage settings view after service behavior is stable. Reference: `REFACTOR_PLAN.md` P2-05.
- Move repeated `@php` display calculations out of Blade where practical. Reference: `ANALYSIS.md` view issues; `REFACTOR_PLAN.md` P2-04.

### Shared Components

- Use `shared.import-export.panel` for coupon import/export. Reference: `REFACTOR_PLAN.md` P1-10.
- Use `Modules/Shared` for header menu partials only if reused by Admin and Website. Reference: `REFACTOR_PLAN.md` P1-05.
- Use existing Blade components for product cards only after moving non-trivial calculations to service/model-safe display helpers. Reference: `REFACTOR_PLAN.md` P2-04.

### AdminLTE/Bootstrap Layout Rules

- New or refactored admin UI follows Tailwind CSS 4 and Admin UI v1.1, even though ROADMAP notes the current installed UI stack includes Bootstrap/AdminLTE inventory. Reference: `CODEX_BOOTSTRAP.md` Known conflict resolutions; `AI_PROJECT_CONTEXT.md` Admin UI Standard.
- Do not introduce Bootstrap or jQuery in new Website work. Reference: `AI_PROJECT_CONTEXT.md` Governing Stack; `REFACTOR_PLAN.md` Risk Control.
- Existing `Admin::layouts.master` may remain as the admin layout shell. Reference: `AI_PROJECT_CONTEXT.md` Governing Stack.

### Table Design

- Admin coupon/customer tables use server-side pagination, current-page selection, explicit actions, empty states, loading states, and permission-denied handling. Reference: `REFACTOR_PLAN.md` P0-02/P1-14.
- Dangerous delete/bulk delete/import actions require confirmation and authorization. Reference: `REFACTOR_PLAN.md` P0-02/P1-10.

### Form Design

- Forms use field-level validation messages, disabled/loading states, and consistent Tailwind/Admin UI classes. Reference: `AI_PROJECT_CONTEXT.md` Admin UI Standard.
- Coupon, customer, homepage/header/footer/banner/flash-sale forms must call services after Livewire validation. Reference: `REFACTOR_PLAN.md` P0-02/P1-09/P1-11.

## 8. Import Design

### Import Classes

Target:

- `Modules/Website/Services/ImportExport.php` as the module entry point.
- Optional `Modules/Website/Import/CouponImport.php`, `RowMapper.php`, `RowNormalizer.php`, or `RowValidator.php` only if `ImportExport.php` exceeds simple responsibility.

Reference: `AI_PROJECT_CONTEXT.md` Import Export Standard; `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Confirm accepted file format. Current implementation is JSON, but project standard expects Excel/shared import foundation. Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P1-10 and Risk Control.

### Header Mapping

Candidate coupon headers:

- `code`
- `description`
- `type`
- `value`
- `min_order_value`
- `usage_limit`
- `starts_at`
- `expires_at`
- `is_active`

Reference: current export fields described in `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Vietnamese aliases, required headers, and template labels must be confirmed from real import files. Reference: `CODEX_BOOTSTRAP.md` Import/Export Rules.

### Column Mapping

- Default design uses header-based mapping. Reference: `REFACTOR_PLAN.md` P1-10.
- Positional A/B/C mapping is not selected unless sample files prove headers are unstable.

Needs confirmation before coding:

- Confirm whether users import by headers or fixed columns. Reference: `AI_PROJECT_CONTEXT.md` Column mapping rules.

### Row Normalization

- Trim strings and convert empty optional strings to null. Reference: `AI_PROJECT_CONTEXT.md` Import normalization.
- Normalize coupon code to canonical casing if confirmed. Reference: `REFACTOR_PLAN.md` P1-10/P1-11.
- Normalize money/numeric values from locale formats. Reference: `AI_PROJECT_CONTEXT.md` Import normalization.
- Normalize booleans like `1/0`, `true/false`, `active/inactive`, and Vietnamese equivalents if needed. Reference: `AI_PROJECT_CONTEXT.md` Import normalization.
- Normalize dates using confirmed formats. Reference: `REFACTOR_PLAN.md` P1-10/P1-11.

### Row Validation

Rules:

- `code` required and unique by business key.
- `type` required and restricted to confirmed allowed values.
- `value` numeric, with percent max when `type=percent`.
- `min_order_value` numeric and non-negative.
- `usage_limit` nullable positive integer.
- `starts_at` and `expires_at` valid dates with confirmed ordering.
- `is_active` boolean.

Reference: `ANALYSIS.md` validation problems; `REFACTOR_PLAN.md` P1-10/P1-11.

Needs confirmation before coding:

- Allowed coupon types and date behavior must be confirmed from current schema/business rules. Reference: `REFACTOR_PLAN.md` Risk Control.

### Duplicate Handling

- Default candidate unique key: `code`. Reference: `REFACTOR_PLAN.md` P1-10.
- Supported modes should include `create_only`, `update_or_create`, and `skip_duplicate`.
- `replace` is not allowed unless explicitly confirmed.

Reference: `CODEX_BOOTSTRAP.md` Import/Export Rules; `REFACTOR_PLAN.md` P1-10.

### Error Reporting

- Return totals, success rows, error rows, skipped rows, and row-level errors with sheet, row, column, value, and reason. Reference: `AI_PROJECT_CONTEXT.md` Import result requirements.
- Do not expose raw exception text. Reference: `ANALYSIS.md` import/export issues; `REFACTOR_PLAN.md` P1-18.

## 9. Export Design

### Export Classes

Target:

- `Modules/Website/Services/ImportExport.php` handles simple coupon export.
- Optional `Modules/Website/Export/CouponExport.php`, `ExportQuery.php`, `ExportMapper.php`, or `TemplateBuilder.php` if responsibilities grow.

Reference: `AI_PROJECT_CONTEXT.md` Export architecture; `REFACTOR_PLAN.md` P1-10.

### Query Design

- Export queries are service-owned and support current filters/selected IDs where applicable. Reference: `REFACTOR_PLAN.md` P1-10/P1-14.
- Do not use unbounded `get()` for production-sized exports. Reference: `ANALYSIS.md` import/export and performance risks; `REFACTOR_PLAN.md` P1-10/P1-14.

### Export Mapping

Coupon export fields should initially match confirmed safe fillable fields:

- `code`
- `description`
- `type`
- `value`
- `min_order_value`
- `usage_limit`
- `starts_at`
- `expires_at`
- `is_active`

Reference: `ANALYSIS.md` section 9; `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Exclude sensitive/internal fields after inspecting `Coupon` model and migration. Reference: `AI_PROJECT_CONTEXT.md` Export architecture.

### Template Generation

- Generate a coupon import template with headers, sample row, required/optional notes, valid type list, date format notes, and duplicate-mode warning. Reference: `AI_PROJECT_CONTEXT.md` Export templates; `REFACTOR_PLAN.md` P1-10.

Needs confirmation before coding:

- Template sample values and Vietnamese labels must be confirmed. Reference: `CODEX_BOOTSTRAP.md` Import/Export Rules.

### Large Export Strategy

- Use chunk/lazy iteration or queued export when dataset size exceeds safe request-time bounds. Reference: `ROADMAP.md` P1-06; `REFACTOR_PLAN.md` P1-14.
- Store generated exports through the shared foundation. Reference: `AI_PROJECT_CONTEXT.md` Export architecture.

## 10. Permissions and Authorization

### Required Permissions

Proposed permission groups:

- `website.home.settings.view`
- `website.home.settings.update`
- `website.header.view`
- `website.header.update`
- `website.footer.view`
- `website.footer.update`
- `website.banner.view`
- `website.banner.create`
- `website.banner.update`
- `website.banner.delete`
- `website.flash_sale.view`
- `website.flash_sale.create`
- `website.flash_sale.update`
- `website.flash_sale.delete`
- `website.coupon.view`
- `website.coupon.create`
- `website.coupon.update`
- `website.coupon.delete`
- `website.coupon.import`
- `website.coupon.export`
- `website.customer.view`
- `website.customer.create`
- `website.customer.update`
- `website.customer.delete`
- `website.affiliate.view`
- `website.affiliate.approve`
- `website.affiliate.reject`
- `website.affiliate.scheme.manage`

Reference: `ANALYSIS.md` authorization/security risks; `REFACTOR_PLAN.md` P0-01/P0-02.

Needs confirmation before coding:

- Permission names must align with the existing Role/permission module naming convention. Reference: `REFACTOR_PLAN.md` P0-01.

### Policy/Gate Checks

- Use route middleware for page access and Livewire method checks for actions. Reference: `REFACTOR_PLAN.md` P0-01/P0-02.
- Add record ownership checks for cart, account orders, affiliate orders, and customer records where applicable. Reference: `REFACTOR_PLAN.md` P0-03/P0-05.

### Livewire Action Protection

- Every mutating admin Livewire method must call authorization before validation/persistence. Reference: `REFACTOR_PLAN.md` P0-02.
- Cart Livewire actions must only pass requested state to `CartService`, which enforces ownership. Reference: `REFACTOR_PLAN.md` P0-03/P1-06.

### Route Middleware

- All Website admin routes: `web`, `auth:admin`, named permission. Reference: `REFACTOR_PLAN.md` P0-01.
- Account routes: `web`, `auth`, plus service-level ownership checks. Reference: `REFACTOR_PLAN.md` P0-05.
- Public routes remain public unless they mutate data through Livewire, where validation and service invariants apply. Reference: `REFACTOR_PLAN.md` P1-11.

## 11. Transactions and Data Integrity

### Actions Requiring DB Transactions

- Checkout order creation. Reference: `REFACTOR_PLAN.md` P1-07.
- Cart merge/add/update/remove when multiple rows are touched. Reference: `REFACTOR_PLAN.md` P0-03/P1-06.
- Coupon import persistence. Reference: `REFACTOR_PLAN.md` P1-10.
- Multi-key homepage/header/footer settings save. Reference: `REFACTOR_PLAN.md` P1-09/P1-12.
- Footer column/link/social reordering. Reference: `REFACTOR_PLAN.md` P1-12.
- Address default changes. Reference: `REFACTOR_PLAN.md` P1-12.
- Affiliate approval/rejection when status and commissions are updated. Reference: `REFACTOR_PLAN.md` P0-02/P1-12.

### Rollback Conditions

- Insufficient stock, inactive product, invalid coupon, invalid payment state, failed order item creation, failed order history creation, or cart cleanup failure must rollback checkout. Reference: `REFACTOR_PLAN.md` P1-07.
- Invalid row or unconfirmed duplicate mode must rollback all-or-nothing coupon import unless partial import is explicitly confirmed. Reference: `REFACTOR_PLAN.md` P1-10.
- Failed multi-setting validation or persistence must rollback settings changes. Reference: `REFACTOR_PLAN.md` P1-09/P1-12.

Needs confirmation before coding:

- Coupon import partial-success versus all-or-nothing behavior must be confirmed. Reference: `CODEX_BOOTSTRAP.md` Import/Export Rules.

### Idempotency Concerns

- Payment callback handling must be idempotent before enabling `checkout.momo.callback`. Reference: `REFACTOR_PLAN.md` P1-02/P1-07.
- Checkout must not create duplicate orders on retry. Reference: `ROADMAP.md` P1-09; `REFACTOR_PLAN.md` P1-07.
- Coupon imports must define duplicate handling by confirmed unique key. Reference: `REFACTOR_PLAN.md` P1-10.
- Affiliate commission approval/rejection must not double-apply commission changes. Reference: `REFACTOR_PLAN.md` P0-02/P1-12.

## 12. Performance Strategy

### Eager Loading

- Product list/detail services must eager load relationships rendered by product cards/detail pages. Reference: `ANALYSIS.md` N+1 risks; `REFACTOR_PLAN.md` P1-08.
- Cart summary must eager load items/product/coupon through `CartService`. Reference: `REFACTOR_PLAN.md` P1-06.
- Customer admin table/detail must eager load or aggregate orders through service queries, not per-row queries. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-14.

### Query Optimization

- Move search/filter/sort/pagination to services. Reference: `REFACTOR_PLAN.md` P1-03/P1-08/P1-14.
- Replace revenue chart per-day loop with one grouped service query. Reference: `REFACTOR_PLAN.md` P2-06.
- Avoid nested Livewire components per product card where query cost is high; evaluate after query-count tests. Reference: `ANALYSIS.md` N+1 risks; `REFACTOR_PLAN.md` P1-13.

### Pagination

- Server-side pagination by default. Reference: `AI_PROJECT_CONTEXT.md` Pagination.
- Guard `All`; do not use `paginate(9999)` as production behavior. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-14.

### Caching If Needed

- Cache header menu, footer columns/social links, stable settings, homepage config, banners, and product/post highlights only with explicit invalidation from write services. Reference: `ANALYSIS.md` performance risks; `REFACTOR_PLAN.md` P1-13.
- Do not use caching to hide inefficient queries. Reference: `AI_PROJECT_CONTEXT.md` Caching; `REFACTOR_PLAN.md` Risk Control.

## 13. Test Strategy

### Route Tests

- Website public pages return expected views/components. Reference: `REFACTOR_PLAN.md` P1-01/P1-03.
- Admin routes require `auth:admin` plus named permission. Reference: `REFACTOR_PLAN.md` P0-01.
- `checkout.momo.callback` route either maps to implemented behavior or is removed after confirmation. Reference: `REFACTOR_PLAN.md` P1-02.

### Livewire Tests

- Admin mutating actions deny missing permissions and allow proper permissions. Reference: `REFACTOR_PLAN.md` P0-02.
- Cart actions cannot modify another cart item. Reference: `REFACTOR_PLAN.md` P0-03.
- Coupon/customer/home/header/footer/banner/flash-sale validation and disabled/loading states behave correctly. Reference: `REFACTOR_PLAN.md` P1-11.
- Product/post/home components call services and preserve search/filter/sort behavior. Reference: `REFACTOR_PLAN.md` P1-08.

### Service Tests

- `CartService` ownership, merge, add, update, remove, coupon summary. Reference: `REFACTOR_PLAN.md` P0-03/P1-06.
- `CheckoutService` transaction rollback, stock locking, coupon usage, cart cleanup, idempotency. Reference: `REFACTOR_PLAN.md` P1-07.
- `SettingsService`, `HeaderMenuService`, `FooterService` transactional writes and cache invalidation. Reference: `REFACTOR_PLAN.md` P1-09/P1-12/P1-13.
- `AffiliateService` ownership and commission detail access. Reference: `REFACTOR_PLAN.md` P0-05.

### Import Tests

- Coupon import valid rows, invalid rows, duplicate codes, blank fields, dates, booleans, dry-run, and rollback/partial behavior. Reference: `REFACTOR_PLAN.md` P1-10.

### Export Tests

- Coupon export uses fillable/safe fields, honors filters/selected IDs, creates template, and avoids unbounded memory behavior. Reference: `REFACTOR_PLAN.md` P1-10/P1-14.

### Authorization Tests

- P0 denial tests for every admin route and every mutating Livewire method. Reference: `REFACTOR_PLAN.md` P0-01/P0-02.
- Record ownership denial for cart, account order detail, affiliate order detail. Reference: `REFACTOR_PLAN.md` P0-03/P0-05.
- Dangerous database/env services are unreachable from Website. Reference: `REFACTOR_PLAN.md` P0-04.

## 14. Implementation Checklist

### P0

- [ ] Add named permissions to all Website admin routes in `Modules/Website/routes/web.php`. Reference: `REFACTOR_PLAN.md` P0-01.
- [ ] Protect all mutating admin Livewire actions in `Modules/Website/Livewire/Admin/**`. Reference: `REFACTOR_PLAN.md` P0-02.
- [ ] Enforce cart item ownership in `Modules/Website/Services/CartService.php` and callers. Reference: `REFACTOR_PLAN.md` P0-03.
- [ ] Disable or migrate dangerous database/env services from `Modules/Website/Services/Services/**`. Reference: `REFACTOR_PLAN.md` P0-04.
- [ ] Add account and affiliate order ownership checks. Reference: `REFACTOR_PLAN.md` P0-05.
- [ ] Add P0 route, Livewire authorization, cart ownership, order ownership, and dangerous-service denial tests. Reference: `ROADMAP.md` P0-06; `REFACTOR_PLAN.md` Phase 1.

### P1

- [ ] Replace `/blog` route closure with controller flow. Reference: `REFACTOR_PLAN.md` P1-01.
- [ ] Confirm and implement/remove MoMo callback behavior. Reference: `REFACTOR_PLAN.md` P1-02. Needs confirmation before coding.
- [ ] Move controller queries to services. Reference: `REFACTOR_PLAN.md` P1-03.
- [ ] Normalize Website Livewire aliases in page blades/providers. Reference: `REFACTOR_PLAN.md` P1-04.
- [ ] Remove `Admin::livewire.*` includes from Website views. Reference: `REFACTOR_PLAN.md` P1-05.
- [ ] Consolidate cart writes into `CartService`. Reference: `REFACTOR_PLAN.md` P1-06.
- [ ] Fix checkout transaction and concurrency behavior. Reference: `REFACTOR_PLAN.md` P1-07.
- [ ] Move product/post/home queries from Livewire to services. Reference: `REFACTOR_PLAN.md` P1-08.
- [ ] Move admin settings persistence to services with transactions and cache invalidation. Reference: `REFACTOR_PLAN.md` P1-09.
- [ ] Build shared coupon import/export flow. Reference: `REFACTOR_PLAN.md` P1-10. Needs confirmation before coding for file format, unique key, duplicate mode, null-overwrite behavior, dry-run behavior, and export columns.
- [ ] Strengthen validation across checkout, coupon, homepage settings, customer address, newsletter, and affiliate ref handling. Reference: `REFACTOR_PLAN.md` P1-11.
- [ ] Add transactions for address defaults, footer ordering, settings saves, cart writes, and affiliate approval/rejection. Reference: `REFACTOR_PLAN.md` P1-12.
- [ ] Add cache policy for header/footer/homepage/menu reads. Reference: `REFACTOR_PLAN.md` P1-13.
- [ ] Bound select-all, exports, and `All` pagination. Reference: `REFACTOR_PLAN.md` P1-14.
- [ ] Deduplicate root and nested services after caller migration. Reference: `REFACTOR_PLAN.md` P1-15.
- [ ] Confirm canonical module ownership for duplicated domain models. Reference: `REFACTOR_PLAN.md` P1-16. Needs confirmation before coding.
- [ ] Repair malformed migrations after ownership is settled. Reference: `REFACTOR_PLAN.md` P1-17. Needs confirmation before coding.
- [ ] Normalize raw exception handling and login throttling. Reference: `REFACTOR_PLAN.md` P1-18.

### P2

- [ ] Remove unused `WebsiteController` resource stubs after tests. Reference: `REFACTOR_PLAN.md` P2-01.
- [ ] Resolve unregistered admin product and affiliate commission pages. Reference: `REFACTOR_PLAN.md` P2-02. Needs confirmation before coding.
- [ ] Remove confirmed scaffold files only after reference/route tests. Reference: `REFACTOR_PLAN.md` P2-03.
- [ ] Move repeated Blade `@php` calculations out of views. Reference: `REFACTOR_PLAN.md` P2-04.
- [ ] Split oversized admin homepage settings view. Reference: `REFACTOR_PLAN.md` P2-05.
- [ ] Optimize dashboard revenue chart query. Reference: `REFACTOR_PLAN.md` P2-06.
- [ ] Confirm middleware usage for `TrackAffiliate` and `ShareWishlistData`. Reference: `REFACTOR_PLAN.md` P2-07. Needs confirmation before coding.
- [ ] Generate Website architecture catalog and tests. Reference: `REFACTOR_PLAN.md` P2-08.
