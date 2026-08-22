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

Release status: Phases 1–8 are closed; Header Architecture Phase 9A–9E is completed and merged. Footer Architecture Phase 10A–10F is under active implementation/validation. Node.js must be upgraded to a Vite-supported LTS version before production deployment.

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

## Website Admin UI Standard

All new or refactored Website admin forms should follow:

```text
docs/modules/Website/ADMIN_UI_INPUT_STANDARD.md
```

This standard defines the approved treatment for visible editable fields, labels, textarea/select controls, checkboxes, file uploads, repeatable collection cards, empty states, validation feedback, responsive grids and primary save actions.

Core requirement:

```text
Editable controls must be visually distinguishable from static text before focus.
```

The Phase 10F Footer information form is the current reference implementation.

## Header Architecture — Phase 9

The approved Header target architecture is documented in:

- `docs/modules/Website/PHASE_9_ANALYSIS.md`

Implementation slices completed:

1. Phase 9A — Header Decomposition
2. Phase 9B — Global Design Tokens
3. Phase 9C — Header Schema & Component Registry
4. Phase 9D — Header Builder Admin
5. Phase 9E — Drag/drop & Responsive Preview

Header admin route:

```text
/admin/header-settings
```

## Footer Architecture — Phase 10

The approved Footer target architecture is documented in:

- `docs/modules/Website/PHASE_10_ANALYSIS.md`

Implementation sequence:

1. Phase 10A — Footer Decomposition
2. Phase 10B — Footer Design Tokens & Presentation
3. Phase 10C — Footer Schema & Component Registry
4. Phase 10D — Footer Builder Admin
5. Phase 10E — Drag/drop & Responsive Preview
6. Phase 10F — Footer Brand, Layout Themes and content/admin cleanup

Footer admin route:

```text
/admin/footer-settings
```

Phase 10F implementation notes:

```text
docs/modules/Website/PHASE_10F_IMPLEMENTATION.md
```

## Future Improvements

Current next-step priority:

1. Complete Phase 10F targeted tests and UI validation.
2. Keep Website admin form work aligned with `ADMIN_UI_INPUT_STANDARD.md`.
3. Continue to use module-scoped Website tests instead of the full project suite during normal implementation.

The current strategy remains controlled refactoring rather than a full rebuild.
