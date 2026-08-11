# Website Phase 1B — Admin Authorization Implementation & Test Gate

## Status

- Phase: `1B — Admin Authorization`
- Analysis: `COMPLETE`
- Implementation: `COMPLETE`
- Automated runtime test: `PASS — 12 tests / 138 assertions`
- Permission sync: `PASS`
- Manual admin permission smoke: `PASS — user verified`
- Approval: `APPROVED / CLOSED`
- Next phase: `1C — Settings / Cache / XSS`

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

Middleware explicitly uses the `admin` guard.

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

Phase 1B did not move cross-domain features, redesign admin authentication, redesign database, rebuild admin UI, rename Website routes, or change Phase 1A checkout/payment behavior.

## Permission Sync Runtime Note

The current repository has a PSR-4 casing inconsistency in the Role seeder path on Linux:

```text
file path: Modules/Role/database/seeders/RolesAndPermissionsSeeder.php
namespace declared in file: Modules\Role\database\Seeders
```

The runtime-compatible direct Artisan class string is:

```bash
php artisan db:seed --class='Modules\Role\database\seeders\RolesAndPermissionsSeeder'
```

This casing mismatch is repository technical debt and remains outside Website Phase 1B scope.

## Automated Runtime Results

```text
WebsiteAdminAuthorizationConfigurationTest: PASS — 4 tests / 88 assertions
WebsiteRouteConfigurationTest: PASS — 3 tests / 30 assertions
WebsiteCheckoutConfigurationTest: PASS — 5 tests / 20 assertions
Combined Website feature tests: PASS — 12 tests / 138 assertions
```

## Manual Runtime Results

User verified the Phase 1B authorization smoke gate as `PASS`, including administrative access/permission behavior after permission sync.

No Phase 1B regression was reported.

## Phase 1B Exit Gate

- [x] permission sync completes successfully;
- [x] authorization configuration test passes;
- [x] existing Website route regression passes;
- [x] Phase 1A checkout regression passes;
- [x] combined Website feature tests pass;
- [x] Super Admin manual admin smoke passes;
- [x] limited-permission separation smoke passes;
- [x] denied mutation behavior verified by manual gate;
- [x] user explicitly approves Phase 1B.

## Decision

**PHASE 1B: TESTED / APPROVED / CLOSED**

Proceed to `Phase 1C — Settings / Cache / XSS`. Do not start Phase 2 until the complete Phase 1 gate is approved.
