# Website Module Contract

## Purpose

`Website` owns the public website presentation/composition layer and the Admin surfaces used to configure that presentation.

It is not the canonical owner of Product, Post, Order, Account/customer identity, payment, affiliate, promotion, or other business domains merely because those domains are rendered or consumed by the public website.

## Canonical ownership

Website owns:

- public website shell, layout and presentation composition;
- homepage composition and structured homepage content;
- header, navigation/menu presentation and footer composition;
- website appearance, design/theme and presentation settings;
- website pages, sections and section items;
- social/presentation links owned by the website shell;
- presentation-owned banners, subject to future promotion-domain extraction if their semantics become business promotion rather than website composition;
- sitemap, website manifest/PWA presentation integration and website-specific cache composition;
- Admin Website dashboard/settings/header/footer/home presentation workspaces.

## Integration boundaries

Website may consume domain data or contracts required to render the public site, but consumption does not transfer ownership.

Current important integration boundaries include:

- Product/Category: public catalogue/search/detail presentation. Product remains the domain owner.
- Post: public blog/content presentation. Post remains the content owner.
- Order: checkout/order presentation may integrate with Order contracts. Order remains the order domain owner.
- System: Website may consume canonical system settings/services where appropriate; it must not duplicate System ownership.

The existing `Order\\Contracts\\CheckoutContext` to Website checkout-context adapter is an integration boundary and must not be removed or relocated without caller/contract proof.

## Non-ownership and quarantine

The following Website-resident families are not automatically canonical Website ownership and must not be expanded as Website responsibilities:

- cart and cart persistence;
- checkout business orchestration;
- payment/MoMo processing and callbacks;
- coupon and flash-sale business rules;
- affiliate schemes, ranks, commissions and administration;
- customer/account domain administration;
- wishlist/review/newsletter/tag business ownership where no explicit Website presentation contract proves otherwise.

These families are `QUARANTINE` or `DEFER` until caller, schema, authorization and target-owner proof exists. Their current physical location is not proof of canonical ownership.

## Persistence safety

Website currently contains persistence/migrations/seeders that cross presentation, commerce, promotion, affiliate and demo-data concerns.

Major Refactor rules:

- do not drop, rename, move or recreate a table merely to align file ownership;
- do not rewrite migration history without migration-ledger and deployed-schema proof;
- do not delete models/services/seeders with persistence implications until callers and production compatibility are proven;
- schema-sensitive cleanup remains `QUARANTINE` until an explicitly approved persistence phase.

## Module runtime-state invariant

Module enable/disable state is dynamic project runtime state. `Modules/<Module>/config/module.php` or `Config/module.php` is not the runtime source of truth for whether a module is enabled.

Canonical runtime behavior is defined by the project module-state infrastructure documented in `docs/GITHUB_COLLABORATION_WORKFLOW.md`, including `ModuleStateResolver`, `ModuleStateRepository` and the runtime module-state repository.

Rules:

- never change manifest `enabled` solely to enable/disable a module at runtime;
- runtime overrides take precedence according to `ModuleStateResolver`;
- `default_enabled`/legacy `enabled` are manifest defaults/compatibility fallback only;
- ownership decisions must not be inferred from a legacy manifest `enabled` value;
- refactor documentation that instructs runtime toggling by editing `config/module.php` is stale and must not be followed.

Manifest metadata such as module type, required status and dependency declarations remains subject to architecture validation against runtime/source reality.

## Refactor classification

Use the Clean Module Refactor classifications defined by repository workflow:

- `KEEP`
- `REHOME`
- `DELETE`
- `QUARANTINE`
- `DEFER`

Physical location alone is never sufficient proof for `KEEP` or `REHOME`. `DELETE` requires caller proof and compatibility review.

## Current target classification

### KEEP

- Website public shell/layout/presentation.
- Header/menu/footer/social presentation.
- Homepage builder/composition/presentation.
- Website pages/sections/section items.
- Website design/appearance/themes/settings surfaces.
- Website Admin dashboard and presentation configuration surfaces.
- Sitemap and website-specific composition/cache integration.
- Public Product/Post rendering adapters where they remain presentation-only.

### REHOME candidates requiring proof

- stale Website Admin Product ownership artifacts to Product;
- account order-history/order-detail domain ownership toward Order while preserving consumer presentation;
- customer administration toward the canonical account/user boundary;
- cart/checkout toward an approved commerce/order boundary;
- coupon/flash-sale toward an approved promotion owner;
- affiliate toward an approved affiliate owner.

A target module must exist and have an approved contract before rehome. Do not create a new domain merely to empty Website.

### QUARANTINE

- payment/MoMo;
- commerce/promotion/affiliate schema and migration ownership;
- cross-domain permissions whose canonical owner is not proven;
- compatibility routes/services that still have callers.

### DEFER

- wishlist/review/newsletter/tag ownership until caller/schema proof;
- extraction of new Marketing/Affiliate-style domains until separately approved.

## Admin UI contract

Admin Website changes must follow `.codex/standards/ADMIN_UI_STANDARD.md`.

Key invariants:

- use the canonical Admin shell/layout when applicable;
- workspace-first navigation for feature-rich settings;
- page Blade acts as shell; interactive feature UI belongs in class-based Livewire where appropriate;
- reuse repository shared components before introducing module-local duplicates;
- bounded pagination only; no unbounded `All` option;
- standard page-size choices should be `10`, `25`, `50`, `100` where applicable, with invalid values normalized to a safe default;
- destructive actions are permission-aware and confirmed;
- responsive/accessibility/manual rendered UI acceptance is required for material UI changes.

## Refactor execution invariants

- Prefer coherent architectural batches over tiny cleanup PRs.
- Minimize user pull/test cycles by completing a safe batch before requesting local verification.
- Do not combine schema-destructive or payment-sensitive work into a low-risk presentation cleanup merely to reduce test cycles.
- Keep compatibility until replacement routes/callers are proven.
- Update this contract in the same PR whenever canonical responsibility, non-responsibility, dependency, persistence or compatibility boundaries change.
- Update `COLLABORATION_HANDOFF.md` before PR/merge according to repository workflow.

## Approved Batch 1 target

Batch 1 combines the low-risk portions of the originally proposed first three refactor phases:

1. Contract + ownership baseline.
2. Canonical Website core cleanup.
3. Product/Post presentation integration cleanup.

Batch 1 excludes destructive persistence work, payment/MoMo relocation, broad cart/checkout extraction, affiliate-domain extraction and promotion-domain creation.

Before deleting/rehome of runtime artifacts in Batch 1, caller proof and replacement ownership must be established.