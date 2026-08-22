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

## Header Architecture — Phase 9

The approved target architecture is documented in:

- `docs/modules/Website/PHASE_9_ANALYSIS.md`

The approved design separates global Website design tokens from Header layout configuration, decomposes the monolithic Header Blade into stable components, introduces a controlled component registry and slot-based Header Builder, and defines responsive defaults for width/height/colors while keeping typography global.

Status: `ANALYZED / APPROVED — READY FOR PHASE 9A IMPLEMENTATION`.

Phase 9 implementation must proceed in controlled slices, starting with Header decomposition that preserves existing visual/runtime behavior before introducing design tokens, schema/registry, builder controls, drag/drop or responsive preview.

## Future Improvements

Current next-step priority:

1. Obtain UI/release approval for remaining Phase 8 gate items.
2. Implement Phase 9A Header decomposition with regression protection.
3. Formalize global Website typography/color/layout design tokens in Phase 9B.
4. Introduce validated Header component registry and slot schema in Phase 9C.
5. Add Header Builder controls in Phase 9D.
6. Add drag/drop and responsive preview in Phase 9E.

The current strategy remains controlled refactoring rather than a full rebuild.
