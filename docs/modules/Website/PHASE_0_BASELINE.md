# Website Phase 0 — Baseline & Safety Net

## Status

- Phase: `0 — Baseline & Safety Net`
- Static analysis: `PASS`
- Runtime smoke test: `PENDING`
- Automated regression coverage: `PARTIAL`
- Approval gate: `NOT APPROVED`
- Rule: Do not start Phase 1 until runtime baseline is reviewed and Phase 0 is explicitly approved.

## Purpose

This document freezes the current behavior and known defects of `Modules/Website` before refactoring. It is a regression reference, not an implementation plan.

Known defects documented here must not later be misclassified as refactor regressions.

## Baseline Scope

### Frontend

- Homepage
- Help
- Product listing
- Product detail
- Blog listing
- Blog detail
- Login
- Register
- Logout
- Cart
- Checkout
- Checkout success
- Account dashboard
- Account profile
- Account orders
- Account order detail
- Wishlist
- Affiliate dashboard
- Supporting chat/widgets where enabled

### Admin Website

- Homepage settings
- Header settings
- Footer settings
- Banner management
- Flash sale management
- Coupon management
- Customer management
- Affiliate management

## Current Module Dependencies

`Modules/Website/Config/module.php` declares Website as a domain module with dependencies on:

- User
- Product
- Category
- Post
- Order

This dependency declaration is the intended direction for later ownership cleanup: Website should consume canonical domain owners rather than duplicate them.

## Current Architecture Baseline

Observed flow patterns include:

```text
Route
-> Controller
-> Page Blade
-> Livewire
-> Service
-> Model
-> Database
```

However, current source also contains bypasses that are part of the baseline:

- Controllers directly query models in several flows.
- Livewire components directly query models or `DB::table()`.
- Blade layout reads `Setting` directly.
- Website contains duplicated Product/Post/Order/Category/account-related models.
- Website contains system-like Database/Env/Auth/Chat services under `Services/Services`.

These are architectural debt to be handled in later phases, not in Phase 0.

## Known Broken Before Refactor

### B0-01 — MoMo callback route/controller mismatch

Status: `BROKEN BEFORE REFACTOR`

`checkout.momo.callback` points to `CheckoutController@momoCallback`, while the inspected controller does not define that method.

Expected Phase: `1A — Checkout Stabilization`

### B0-02 — Checkout cart lifecycle defect

Status: `BROKEN BEFORE REFACTOR`

The current checkout workflow deletes the cart and then mutates/saves that deleted model.

Expected Phase: `1A — Checkout Stabilization`

### B0-03 — Checkout concurrency risk

Status: `KNOWN CORRECTNESS RISK`

Stock is validated before the transaction and decremented later without an observed row-locking strategy. Concurrent checkout may oversell.

Expected Phase: `1A — Checkout Stabilization`

### B0-04 — Admin authorization is incomplete

Status: `KNOWN SECURITY RISK`

Website admin routes generally require `auth:admin`, but capability-specific authorization is not consistently enforced at route and Livewire mutation boundaries.

Expected Phase: `1B — Authorization`

### B0-05 — Settings cache can become stale

Status: `KNOWN CORRECTNESS RISK`

`Setting::getValue()` uses long-lived cache, while code paths such as Homepage Settings can persist through direct `Setting::updateOrCreate()` and bypass centralized invalidation.

Expected Phase: `1C — Settings / Cache / XSS`

### B0-06 — Raw configurable header script

Status: `KNOWN SECURITY / GOVERNANCE RISK`

Frontend layout renders a stored `header_script` value as raw HTML. This must be treated as privileged configuration and reviewed for authorization and sanitization policy.

Expected Phase: `1C — Settings / Cache / XSS`

## Confirmed Improvements Already Present

These items were reported in older documentation but are no longer current defects.

### C0-01 — Blog route closure

Current source routes `blog.index` to `PostController@index` and a Website route test protects this behavior.

Do not reopen the old closure finding unless source changes again.

### C0-02 — Cart item ownership lookup

Current `CartService` resolves update/remove operations through the current cart relationship via `getCartItemForCurrentCart()`.

The older finding that cart deletion trusts an arbitrary primary key directly is stale.

Ownership behavior still requires runtime/automated regression tests before later cart refactors.

## Domain Ownership Debt Baseline

The following Website-owned or duplicated concepts require review in Phase 2.

### Product / Category

- `Modules/Website/Models/WpProduct.php`
- `Modules/Website/Models/Category.php`
- Website Product query/service logic

Target canonical owners: `Product`, `Category`.

### Post

- `Modules/Website/Models/Post.php`
- Website Post query components/services

Target canonical owner: `Post`.

### Order

- `Modules/Website/Models/Order.php`
- `Modules/Website/Models/OrderItem.php`
- `Modules/Website/Models/OrderHistory.php`
- Website checkout/order query logic

Target canonical owner: `Order`.

### Account / Customer

- User address/profile/customer management behavior inside Website

Target canonical owner: `User` / `Account`, based on repository contracts confirmed in Phase 2.

### System-like services that should not be Website-owned

- `Modules/Website/Services/Services/DatabaseService.php`
- `Modules/Website/Services/Services/Database/*`
- `Modules/Website/Services/Services/Env/*`
- `Modules/Website/Services/Services/AuthService.php`
- `Modules/Website/Services/Services/ChatService.php`

No file is to be deleted in Phase 0.

## Database Baseline

Website currently contains persistence for several concerns.

### Website/CMS-like data

- `wp_settings`
- `wp_banners`
- header/menu data
- footer columns
- footer links
- social links

### Commerce / marketing-like data

- carts
- cart items
- coupons
- flash sales
- flash sale items

### Engagement/account-like data

- newsletters
- reviews
- wishlists
- affiliate data
- user affiliate fields

### Migration hygiene

Legacy migration filenames using the `-0001_11_30_*` pattern are preserved during Phase 0. They must not be renamed or rewritten without an explicit migration strategy in Phase 3.

## Existing Automated Website Coverage

Current dedicated Website test observed:

```text
tests/Feature/Website/WebsiteRouteConfigurationTest.php
```

It currently protects selected route/Livewire alias configuration but does not provide sufficient behavioral coverage for cart, checkout, payment, authorization, settings, homepage CMS, or database integrity.

## Runtime Smoke Test Checklist

Use the current application before Phase 1 changes and classify every item as:

- `PASS`
- `BROKEN`
- `PARTIAL`
- `NOT USED`

### Frontend

```text
[ ] GET /                         Homepage
[ ] GET /help                     Help
[ ] GET /product                  Product listing
[ ] GET /product/{slug}           Product detail
[ ] GET /blog                     Blog listing
[ ] GET /blog/{slug}              Blog detail
[ ] GET /login                    Login
[ ] GET /register                 Register
[ ] POST logout                   Logout

[ ] Cart: add product
[ ] Cart: increment quantity
[ ] Cart: decrement quantity
[ ] Cart: remove product
[ ] Cart: apply valid coupon
[ ] Cart: reject invalid coupon

[ ] GET /checkout                 Checkout page
[ ] Checkout validation
[ ] Checkout COD if enabled
[ ] GET /checkout/success         Success page
[ ] MoMo callback                 Expected BROKEN unless environment/source differs

[ ] GET /account                  Dashboard
[ ] GET /account/profile          Profile
[ ] GET /account/orders           Orders
[ ] GET /account/orders/{code}    Order detail
[ ] GET /account/wishlist         Wishlist
[ ] GET /account/affiliate        Affiliate
```

### Admin

```text
[ ] /admin/homepage-settings
[ ] /admin/header-settings
[ ] /admin/footer-settings
[ ] /admin/banners
[ ] /admin/flash-sales
[ ] /admin/coupons
[ ] /admin/customers
[ ] /admin/affiliate
```

For each admin screen also record:

```text
[ ] Page loads
[ ] Read-only state works
[ ] Create/update action works where applicable
[ ] Delete/destructive action works where applicable
[ ] Validation errors are understandable
[ ] No unexpected 500 error
```

Do not use Phase 0 to judge visual quality. UI redesign belongs to later phases.

## Baseline Commands

Run from the project runtime environment.

```bash
php artisan optimize:clear
php artisan route:list
php artisan test tests/Feature/Website/WebsiteRouteConfigurationTest.php
php artisan test
```

For Docker, adapt the PHP service name, for example:

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan route:list
docker compose exec app php artisan test tests/Feature/Website/WebsiteRouteConfigurationTest.php
docker compose exec app php artisan test
```

Do not assume the Docker service is named `app`; use the service defined by this installation.

## Runtime Result Template

Paste this section into the test report or send it back in chat after running the baseline.

```text
PHASE 0 RUNTIME RESULT

Environment:
Commit:
PHP:
Laravel:
Database:

WebsiteRouteConfigurationTest: PASS / FAIL
Full test suite: PASS / FAIL

Frontend smoke:
Homepage:
Product list:
Product detail:
Blog:
Cart:
Checkout:
Account:
Wishlist:
Affiliate:

Admin smoke:
Homepage settings:
Header:
Footer:
Banner:
Flash sale:
Coupon:
Customers:
Affiliate:

Known additional errors:
- 
```

## Phase 0 Exit Gate

Phase 0 can be marked `APPROVED` only when:

- [x] Static inventory completed.
- [x] Routes/controllers/Livewire/services/models/database reviewed.
- [x] Cross-module debt mapped.
- [x] Known pre-refactor defects recorded.
- [x] Existing Website tests inventoried.
- [ ] Website-specific route test executed successfully or failure recorded.
- [ ] Full current test suite executed or blockers recorded.
- [ ] Frontend smoke test completed.
- [ ] Admin smoke test completed.
- [ ] Any runtime-only defects added to this baseline.
- [ ] User explicitly approves Phase 0.

## Change Rules During Phase 0

- Do not refactor application code.
- Do not rename routes, Livewire aliases, tables or columns.
- Do not delete duplicate files yet.
- Do not rewrite legacy migrations.
- Do not silently fix baseline defects.
- Documentation updates are allowed.
- Test execution is allowed.
- Test additions for upcoming risky changes should be introduced at the start of the relevant implementation phase.
