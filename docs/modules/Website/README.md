# Website Module

## Module Overview

`Modules/Website` is the public storefront/domain presentation module. It currently covers storefront pages, cart/checkout, customer account features, affiliate features, website content/settings and several admin screens.

The module is enabled and registered automatically by `Modules/ModuleServiceProvider.php`.

## Registration

Manifest: `Modules/Website/Config/module.php`

- Type: `domain`
- Enabled: yes
- Depends on: `User`, `Product`, `Category`, `Post`, `Order`

Views are available through the `Website::` / `website::` namespaces. Livewire classes are auto-registered as `website.<kebab-path>` aliases.

## Main Routes

Public routes include:

- `/`
- `/help`
- `/product`
- `/product/{slug}`
- `/blog`
- `/blog/{slug}`
- `/cart`
- `/checkout`
- `/account/**`

Website admin pages are mounted under `/admin` for affiliate, homepage/header/footer settings, banners, flash sales, coupons and customers.

## Permissions

The manifest declares:

- `view_website`
- `create_website`
- `edit_website`
- `delete_website`

Current admin routes mainly rely on `auth:admin`; capability-level authorization must be strengthened before sensitive admin mutations are considered production-safe.

## Features

- Storefront homepage and product browsing.
- Blog/content display.
- Cart and coupons.
- Checkout/order creation.
- Customer profile, addresses, wishlist and orders.
- Affiliate dashboard/commission features.
- Header/footer/banner/home/flash-sale configuration.
- Customer and coupon administration.

## Dependencies

Declared domain dependencies:

`User -> Product -> Category -> Post -> Order` are all referenced as dependencies of Website; they are not a sequence.

Website currently also contains duplicate or cross-domain implementations that overlap with these canonical modules and with Auth/Chat/System concerns.

## Configuration

- `Modules/Website/Config/**`
- `Modules/Website/.env.example`

Do not treat Website's nested environment/database management services as canonical storefront responsibilities.

## Operational Notes

Known high-priority concerns:

- `checkout.momo.callback` points to a missing controller method.
- Some admin Livewire mutations do not enforce capability authorization.
- Checkout stock validation is vulnerable to concurrent overselling because inventory rows are not locked inside the transaction.
- Checkout deletes a cart and then attempts to save it.
- Some admin list/bulk behavior is effectively unbounded.

## Developer Notes

Use the repository-standard flow:

```text
Route
-> Controller
-> Page Blade
-> Livewire
-> Service
-> Model
-> Database
```

Keep public Website behavior stable while migrating Product/Post/Order/User ownership toward their canonical domain modules. Do not remove legacy Website classes until active callers and compatibility contracts have been verified.

See `ANALYSIS.md` for current findings and `INFORMATION.md` for the module catalog.

## Future Improvements

Priority order:

1. Fix payment callback and checkout data-integrity risks.
2. Add capability-specific authorization to admin mutations.
3. Establish canonical Product/Post/Order/User ownership.
4. Migrate callers and remove duplicate `Services/Services` code.
5. Bound bulk/list queries and profile performance.
6. Add Website route, Livewire, service, authorization, checkout and migration tests.

Current analysis recommendation: **Major Refactor** rather than Full Rebuild.
