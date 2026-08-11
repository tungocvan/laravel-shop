# Website Phase 1B — Admin Authorization Implementation & Test Gate

## Status

- Phase: `1B — Admin Authorization`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- Automated runtime test: `PASS — 12 tests / 138 assertions`
- Permission sync: `PASS`
- Manual admin permission smoke: `PENDING USER RUNTIME`
- Approval: `NOT APPROVED`
- Rule: do not start Phase 1C until this gate is tested and explicitly approved.

## Implemented Scope

### Canonical permission provisioning

Phase 1B reuses the repository's existing permission infrastructure:

```text
Modules/Website/Config/module.php
    -> ModulePermissionManager
    -> RolesAndPermissionsSeeder
    -> Spatie permissions (guard=admin)
    -> Super Admin syncPermissions(...)
```

No second permission system or Website-specific request-time permission creation was introduced.

Legacy Website permissions were retained for backward compatibility:

```text
view_website
create_website
edit_website
delete_website
```

The new capability catalog is:

```text
website.view
website.home.manage
website.menu.manage
website.footer.manage
website.banner.manage
website.settings.manage

marketing.coupon.view
marketing.coupon.manage
marketing.flash-sale.view
marketing.flash-sale.manage

customer.view
customer.create
customer.update
customer.delete

affiliate.view
affiliate.manage
```

### Route-level authorization

All Website Admin pages keep `auth:admin` and now also require an explicit capability:

```text
/admin/homepage-settings   -> website.home.manage
/admin/header-settings     -> website.menu.manage
/admin/footer-settings     -> website.footer.manage
/admin/banners             -> website.banner.manage
/admin/flash-sales         -> marketing.flash-sale.view
/admin/coupons             -> marketing.coupon.view
/admin/coupons/create      -> marketing.coupon.manage
/admin/coupons/{id}/edit   -> marketing.coupon.manage
/admin/customers           -> customer.view
/admin/customers/create    -> customer.create
/admin/customers/{id}      -> customer.view
/admin/affiliate           -> affiliate.view
```

Middleware explicitly uses the `admin` guard, for example:

```text
permission:website.home.manage,admin
```

### Livewire mutation authorization

A shared helper was added:

```text
Modules/Website/Livewire/Concerns/AuthorizesAdminPermissions.php
```

It resolves the authenticated `admin` guard user and checks Spatie permission using the `admin` guard explicitly before persistence.

Protected persistent mutation areas:

```text
Homepage
Header / Menu
Footer columns / links / social links
Banner
Flash Sale
Coupon
Customer create/update/delete/address mutations
Affiliate commission/configuration mutations
```

Permission separation is enforced at method level where needed:

```text
customer.update != customer.delete
marketing.coupon.view != marketing.coupon.manage
affiliate.view != affiliate.manage
marketing.flash-sale.view != marketing.flash-sale.manage
```

### Compatibility boundaries preserved

Phase 1B did not:

- move Customers out of Website;
- move Coupon/FlashSale to another module;
- redesign admin authentication/provider;
- redesign database;
- redesign admin UI;
- rename Website routes;
- change Phase 1A checkout/payment behavior.

## Files Changed

```text
Modules/Website/Config/module.php
Modules/Website/routes/web.php
Modules/Website/Livewire/Concerns/AuthorizesAdminPermissions.php
Modules/Website/Livewire/Admin/Home/HomeSettings.php
Modules/Website/Livewire/Admin/Header/GeneralSettings.php
Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php
Modules/Website/Livewire/Admin/Header/MenuManager.php
Modules/Website/Livewire/Admin/Footer/FooterInfo.php
Modules/Website/Livewire/Admin/Footer/FooterColumns.php
Modules/Website/Livewire/Admin/Footer/SocialLinks.php
Modules/Website/Livewire/Admin/Banner/BannerManager.php
Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php
Modules/Website/Livewire/Admin/Coupon/CouponForm.php
Modules/Website/Livewire/Admin/Coupon/CouponTable.php
Modules/Website/Livewire/Admin/Customers/CustomerCreate.php
Modules/Website/Livewire/Admin/Customers/CustomerDetail.php
Modules/Website/Livewire/Admin/Customers/CustomerTable.php
Modules/Website/Livewire/Admin/Affiliate/CommissionList.php
Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php
tests/Feature/Website/WebsiteAdminAuthorizationConfigurationTest.php
```

## Permission Sync After Pull

This repository currently has a PSR-4 casing inconsistency in the Role seeder path on Linux:

```text
file path: Modules/Role/database/seeders/RolesAndPermissionsSeeder.php
namespace declared in file: Modules\Role\database\Seeders
```

The runtime-compatible direct Artisan class string in the current repository is:

```bash
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
```

User runtime verification:

```text
INFO Seeding database.
PASS
```

Then:

```bash
php artisan permission:cache-reset
```

Result:

```text
Permission cache flushed.
```

The casing mismatch is recorded as repository technical debt. It is not repaired inside Website Phase 1B because that would modify the Role module outside the active phase scope.

## Automated Runtime Results

User executed all focused Website Phase 1B regression tests after permission sync.

### Authorization configuration

```text
PASS Tests\Feature\Website\WebsiteAdminAuthorizationConfigurationTest
✓ website manifest exposes phase 1b permissions
✓ website admin routes have expected permission middleware
✓ persistent livewire mutations have capability checks
✓ authorization helper uses the admin guard explicitly

Tests: 4 passed (88 assertions)
```

Classification: `PASS`.

### Existing Website route regression

```text
PASS Tests\Feature\Website\WebsiteRouteConfigurationTest
✓ blog index route uses controller action
✓ website admin routes keep admin auth middleware
✓ registered website admin pages use module livewire aliases

Tests: 3 passed (30 assertions)
```

Classification: `PASS`.

### Phase 1A checkout regression

```text
PASS Tests\Feature\Website\WebsiteCheckoutConfigurationTest
✓ checkout accepts only approved payment methods
✓ momo routes map to real controller actions
✓ momo create payment uses config and returns pay url
✓ momo gateway failure throws controlled exception
✓ momo result signature is verified

Tests: 5 passed (20 assertions)
```

Classification: `PASS`.

### Combined Website feature scope

```text
Tests: 12 passed (138 assertions)
```

Classification: `PASS`.

Automated Phase 1B gate is therefore fully green.

## Regression Commands

```bash
php artisan optimize:clear
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
php artisan permission:cache-reset
php artisan test tests/Feature/Website/WebsiteAdminAuthorizationConfigurationTest.php
php artisan test tests/Feature/Website/WebsiteRouteConfigurationTest.php
php artisan test tests/Feature/Website/WebsiteCheckoutConfigurationTest.php
php artisan test tests/Feature/Website
```

## Manual Phase 1B Smoke Gate

### A. Super Admin

After permission sync, login with the existing `Super Admin` account and verify:

- [ ] Homepage settings opens and saves.
- [ ] Header/menu opens and saves.
- [ ] Footer opens and saves.
- [ ] Banner opens and can save/delete test data safely.
- [ ] Flash Sale opens.
- [ ] Coupon list/create/edit opens and a safe mutation works.
- [ ] Customer list/detail/create opens.
- [ ] Affiliate opens and existing read data loads.
- [ ] No unexpected 403 for Super Admin.

Avoid destructive production-data testing unless using disposable records.

### B. Limited-permission account

For a non-Super-Admin test account, assign only a small capability set and verify separation.

Recommended safe checks:

```text
website.home.manage only
- homepage settings: allowed
- customers: denied
- coupons: denied

marketing.coupon.view only
- coupon list: allowed
- coupon create/edit: denied

customer.view only
- customer list/detail: allowed
- customer create: denied
- direct destructive Livewire mutation: denied

affiliate.view only
- affiliate page/read: allowed
- approve/reject/config mutation: denied
```

Expected denial: HTTP 403 / Livewire authorization failure before persistence.

## Phase 1B Exit Gate

Phase 1B may be marked `TESTED / APPROVED` only after:

- [x] permission sync completes successfully;
- [x] authorization configuration test passes: `4 tests / 88 assertions`;
- [x] existing Website route test passes: `3 tests / 30 assertions`;
- [x] Phase 1A checkout regression test passes: `5 tests / 20 assertions`;
- [x] combined Website feature tests pass: `12 tests / 138 assertions`;
- [ ] Super Admin manual admin smoke passes;
- [ ] at least one limited-permission separation smoke passes;
- [ ] no denied mutation changes database state;
- [ ] user explicitly approves Phase 1B.

## Current Decision

**Automated gate: PASS. Manual authorization gate remains open. Do not start Phase 1C yet.**
