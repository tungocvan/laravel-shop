# Website Collaboration Handoff

## Current objective

Major/Clean Module Refactor for `Website`.

Approved strategy: minimize local pull/test cycles by grouping coherent low-risk work into a larger batch while keeping high-risk persistence/payment/domain-extraction work isolated.

## Current branch

`refactor/website-contract-ownership-baseline`

Base: `main`.

Draft PR: `#118`.

## Approved Batch 1

Batch 1 combines:

1. Website Module Contract + ownership baseline.
2. Canonical Website core cleanup.
3. Product/Post presentation integration boundary cleanup.

The user approved this combined-batch strategy to reduce repeated pull/test cycles.

## Runtime module-state rule

The project uses the dynamic module-state mechanism documented in `docs/GITHUB_COLLABORATION_WORKFLOW.md`.

Do not treat `Modules/<Module>/config/module.php` or `Config/module.php` as runtime enable/disable state.

Do not modify manifest `enabled` merely to toggle a module. Runtime state is resolved by the canonical module-state infrastructure (`ModuleStateResolver` / repository runtime state). Legacy manifest `enabled` is compatibility fallback only when applicable.

Any older workflow/refactor guidance that instructs runtime toggling through `config/module.php` is stale for this project and must not be applied.

## Ownership baseline

Canonical Website responsibility is public website presentation/composition and its Admin presentation configuration surfaces.

KEEP families include:

- public website shell/layout;
- header/menu/footer/social presentation;
- homepage composition/builder;
- website pages/sections;
- design/appearance/theme/settings;
- Website Admin dashboard/presentation settings;
- sitemap/PWA/presentation composition;
- Product/Post public presentation adapters where they do not own domain state.

Cross-domain Website-resident families require proof before movement/removal:

- cart/checkout/order-account surfaces;
- payment/MoMo;
- coupon/flash sale;
- customer administration;
- affiliate;
- wishlist/review/newsletter/tag;
- related persistence/migrations/seeders/permissions.

See `docs/modules/Website/MODULE.md` for the canonical contract and classifications.

## Batch 1 completed cleanup

Proof-backed safe removals completed on the refactor branch:

- removed dead `Modules/Website/Http/Controllers/Admin/ProductController.php`; canonical Admin Product routes/runtime are owned by `Modules/Product`;
- removed dead placeholder `Modules/Website/resources/views/admin/products/index.blade.php`;
- removed unreachable `ProductController::detail()` action from Website storefront controller;
- removed duplicate `Website::products.index` view because the storefront index uses `Website::pages.shop` and both mounted the same Website ProductList presentation component;
- removed duplicate `Website::products.detail` view because it was byte-identical to canonical `Website::products.show` and had no route/caller proof;
- removed dead legacy `Modules/Website/resources/views/admin.blade.php` landing view after caller search found no active use;
- extended `AdminWebsitePresentationOwnershipContractTest` so the cleaned Website runtime cannot silently return and the canonical storefront Product presentation remains explicit.

## Product/Post integration result

Product and Post public presentation remain in Website as intentional presentation adapters.

KEEP:

- Website storefront Product controller/views/Livewire presentation;
- Website blog controller/views/Livewire presentation;
- integration through canonical `Modules\Product\Services\ProductService` and `Modules\Post\Services\PostService`.

No Product/Post domain model, schema, Admin CRUD ownership, or business-domain service ownership is moved into Website.

The Website Product detail presentation currently also touches Cart and affiliate-ref session behavior. That path is intentionally left unchanged in Batch 1 because cart/affiliate extraction is outside the approved low-risk boundary.

## Explicit quarantine / deferred debt

The following remain in place despite possible ownership smell because removing or moving them is persistence-sensitive or conflicts with existing canonical ownership contracts:

- Coupon / FlashSale / Affiliate Website runtime and related models/services;
- Cart / Checkout / MoMo runtime;
- Customer/account-adjacent runtime;
- Wishlist / Review / Newsletter / Tag persistence.

Existing Admin ownership contract tests intentionally preserve Website as canonical runtime for several presentation/compatibility surfaces including coupon, flash sale and affiliate routes. Batch 1 must not reverse those contracts without a separately approved target owner.

## Approved settings persistence follow-up

The canonical persistence target for settings is the `settings` table.

The user approved the architectural objective that runtime still using `wp_settings` must ultimately migrate to `settings`.

This is deliberately NOT implemented in Batch 1. It requires a separate persistence-safe follow-up that must first prove:

- the deployed schema of both `settings` and `wp_settings`;
- migration-ledger state;
- field/key/value compatibility and any required mapping;
- all readers/writers and service/model callers;
- production data migration/backfill behavior;
- rollback/compatibility behavior until no runtime caller depends on `wp_settings`.

Do not mechanically change `Modules\Website\Models\Setting::$table` from `wp_settings` to `settings`, and do not drop/rename `wp_settings`, until that proof and migration plan are complete.

## Safety boundaries

Batch 1 must not perform destructive schema/migration work.

Payment/MoMo, broad cart/checkout extraction, affiliate extraction, promotion-domain creation and persistence-ledger cleanup remain outside Batch 1.

No runtime artifact is deleted/rehome solely because its filename or directory appears misplaced. Caller/replacement proof is required.

## UI standard

Any Admin UI touched by Batch 1 must comply with `.codex/standards/ADMIN_UI_STANDARD.md`, including bounded pagination and shared-component reuse.

Batch 1 does not intentionally redesign a material Admin UI surface; manual UI smoke is therefore a regression check rather than a redesign acceptance pass.

## Verification checkpoint

Batch 1 implementation is now coherent enough for one local verification cycle.

Recommended verification scope:

1. focused Website ownership contract, including the new cleanup guard;
2. Website Feature regression;
3. impacted Admin Product/Post/Website ownership contracts only;
4. route sanity for Website storefront and Website Admin presentation routes;
5. manual smoke of storefront shop/product/blog and Website Admin dashboard/settings surfaces if automated tests pass.

Full-project regression remains outside the default gate.

## Current status

- Target architecture: APPROVED.
- Combined Batch 1 strategy: APPROVED.
- Module Contract: CREATED.
- Handoff baseline: CREATED and UPDATED.
- Dead/duplicate Product Admin and storefront artifacts: CLEANED.
- Product/Post presentation ownership boundary: CONFIRMED / KEEP.
- Batch 1 ownership regression guard: ADDED.
- Persistence-sensitive and payment-sensitive families: QUARANTINED / DEFERRED.
- `wp_settings` -> canonical `settings` migration objective: APPROVED AS SEPARATE PERSISTENCE FOLLOW-UP; NOT IMPLEMENTED IN BATCH 1.
- Runtime source cleanup: BATCH 1 IMPLEMENTATION CHECKPOINT COMPLETE.
- Local verification: READY / PENDING USER RUN.
- Draft PR `#118`: keep draft until verification results are recorded.

After verification passes, record exact test/route/UI results here before making PR `#118` ready for review.