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

## Header Architecture — Proposed Phase 9

Before further header source refactoring, the proposed target architecture is documented in:

- `docs/modules/Website/PHASE_9_ANALYSIS.md`

The proposal separates global Website design tokens from Header layout configuration, decomposes the monolithic Header Blade into stable components, introduces a controlled component registry and slot-based Header Builder, and defines responsive defaults for width/height/colors while keeping typography global.

Status: `ANALYZED — WAITING FOR USER APPROVAL BEFORE IMPLEMENTATION`.

No Header application source should be changed under this proposal until the Phase 9 design is explicitly approved.

## Future Improvements

Current next-step priority:

1. Obtain UI/release approval for remaining Phase 8 gate items.
2. Approve the Phase 9 Header Architecture specification.
3. Refactor the Header into focused components while preserving current behavior.
4. Formalize global Website typography/color/layout design tokens.
5. Introduce validated Header component registry and slot schema.
6. Add Header Builder controls, then drag/drop and responsive preview in later slices.

The current strategy remains controlled refactoring rather than a full rebuild.
