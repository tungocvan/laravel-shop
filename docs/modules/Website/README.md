# Website Module

## Module Overview

`Modules/Website` is the public storefront and CMS presentation module. Domain
ownership for users, products, categories, posts and orders belongs to their
canonical modules; Website composes those contracts for storefront delivery.

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
- `/sitemap.xml`

Website admin pages are mounted under `/admin` for affiliate, homepage/header/footer settings, banners, flash sales, coupons and customers.

## Permissions

Website admin routes and persistent Livewire mutations use named permissions in
addition to the `admin` guard. Canonical permissions cover Website view, homepage,
menu, banner, footer and settings management.

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

Duplicate cross-domain models/services were removed during Phases 2–8. Homepage
legacy settings remain temporarily as an explicit compatibility write contract.

## Configuration

- `Modules/Website/Config/**`
- `Modules/Website/.env.example`

Do not treat Website's nested environment/database management services as canonical storefront responsibilities.

## Operational Notes

Release status: Phases 1–7 are closed; Phase 8 release CLI gate passes. Node.js
must be upgraded to a Vite-supported LTS version before production deployment.

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
