# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Muasamcong Admin Dashboard — PR #72 MERGED / CLOSED**
- Active delivery: **Major/Clean Module Refactor**
- Active branch: `refactor/muasamcong-architecture-ui-export-alignment`
- Delivery status: **IMPLEMENTED BATCH / LOCAL TEST + UI PENDING**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Approved target

The approved refactor target keeps Muasamcong as the canonical procurement-domain owner and preserves the dependency direction `ClientPortal -> Muasamcong`.

Approved priorities:

1. capability-specific mutation authorization;
2. checkbox export semantics: selected rows when selected, otherwise full approved export scope;
3. Admin input/pagination alignment;
4. bounded responsibility extraction without speculative deletion or schema migration.

No database schema, migration, model ownership, API URI, Admin URI, route name or production setting is authorized for destructive change in this delivery.

## Architectural contract

`docs/modules/Muasamcong/MODULE.md` was missing at refactor bootstrap and has now been added on the active branch.

The contract records:

- Muasamcong is an active `domain` module;
- Admin and ClientPortal are presentation/consumer boundaries, not replacement domain owners;
- Muasamcong persistence is protected;
- Personal Session/raw snapshot/schema boundaries are quarantined from destructive cleanup;
- checkbox export semantics are a refactor invariant;
- full-project regression is not a default gate.

## Implemented batch

### Export scope adapters

Canonical URIs and route names are preserved while export endpoints now route through thin scope/authorization adapters:

- `PricingExportController` -> existing Smart Pricing export implementation;
- `SyncedPricingScopedExportController` -> existing Synced Pricing exporter;
- `PricingWishlistExportController` -> existing Wishlist exporter.

Behavior:

```text
selected_ids non-empty -> export selected
selected_ids empty     -> resolve full approved scope, then delegate
```

The existing Excel mapping/formatting implementations remain behind the adapters to minimize format-regression risk.

Current compatibility caps inherited from the legacy exporters are intentionally explicit rather than silently truncating data:

- Smart Pricing: 2,000 rows;
- Wishlist: 2,000 rows;
- Synced Pricing: 5,000 rows.

If a full scope exceeds the inherited limit, the endpoint returns a validation/business error asking the operator to narrow scope/select rows. Removing these exporter capacity limits is deferred to a dedicated streaming/chunked-export slice; silent partial export is not allowed.

### Authorization

- Smart Pricing export enforces `muasamcong.pricing.sync` server-side.
- Synced Pricing export enforces `muasamcong.pricing.sync` server-side.
- Wishlist export enforces `muasamcong.pricing.wishlist` server-side.
- Wishlist bulk delete now has explicit route middleware `permission:muasamcong.pricing.wishlist,admin` in addition to the existing Admin/view boundary.

No new permission was introduced.

### Wishlist extraction and UI alignment

Wishlist page query ownership was extracted from the oversized `MuasamcongController` into:

- `PricingWishlistController` — thin page controller;
- `PricingWishlistQueryService` — reusable user/filter query scope.

Wishlist now uses bounded page-size choices:

```text
10 / 25 / 50 / 100
Default: 25
```

The Wishlist export adapter reuses the same search filter `q` when no rows are selected, so `no selection` exports all matching Wishlist rows rather than only the visible page.

The Wishlist UI now provides:

- visible bordered search/select controls;
- explicit `Chọn trang hiện tại` semantics;
- selected count;
- `Xuất Excel — tất cả phù hợp` when no checkbox is selected;
- selected-only destructive delete;
- module-scoped Admin pagination with white inactive controls and indigo active page.

### Smart Pricing and Synced Pricing canonical export actions

The page headers expose canonical `Xuất Excel` actions that inspect the owning Livewire component:

- when rows are selected, selected IDs are submitted;
- when no rows are selected, no IDs are submitted and the scope adapter resolves the full approved scope.

`Xuất BBG` remains selected-only because it is a separate specialized operation and its semantics were not changed by this refactor slice.

## Compatibility preserved

- `GET /admin/muasamcong/dashboard` remains the Admin dashboard.
- `GET /admin/muasamcong` remains Smart Pricing.
- Existing Admin route names and URIs are preserved.
- Muasamcong API routes are unchanged.
- ClientPortal source/contracts are unchanged in this batch.
- Existing Excel formatting implementations remain the compatibility implementation behind the new adapters.
- No schema/migration/model/storage-path changes were introduced.

## Tests added

`tests/Feature/Muasamcong/MuasamcongRefactorArchitectureContractTest.php` covers:

- canonical route URI preservation and new controller ownership;
- Wishlist delete capability middleware;
- nullable selection contract for export adapters;
- server-side capability permissions;
- Wishlist bounded page sizes;
- filter-aware all-scope export UI contract;
- explicit Admin pagination view contract;
- existence/invariants of `docs/modules/Muasamcong/MODULE.md`.

## Verification state

| Gate | Status |
|---|---|
| GitHub implementation batch | COMPLETE |
| Focused refactor contract test | PENDING USER LOCAL RUN |
| Muasamcong module regression | PENDING USER LOCAL RUN |
| Changed-file Pint | PENDING after tests |
| Route verification | PENDING after tests |
| Frontend build | PENDING after tests |
| Wishlist desktop/mobile UI | PENDING USER UI |
| Smart Pricing selected/all export UI | PENDING USER UI |
| Synced Pricing selected/all export UI | PENDING USER UI |
| Full project regression | NOT APPLICABLE — module-scoped strategy |
| PR | NOT CREATED |
| Merge | NOT AUTHORIZED |

## Deferred / next internal refactor debt

The current batch deliberately does not rewrite the largest Livewire/export implementations in one step.

Still deferred inside Muasamcong:

1. bounded page-size extraction for oversized `SyncedPricingList` and Smart Pricing result pagination beyond the current fixed paging behavior;
2. deeper decomposition of `SyncedPricingList`, `ContractorHistory` and `TracuuThuoctrungthau` after behavioral tests prove safe extraction boundaries;
3. chunked/streaming export strategy to remove the inherited 2,000/5,000 row compatibility caps without memory regressions;
4. atomic Personal Session import-token claim;
5. contractor job/sync idempotency;
6. snapshot/raw-payload/file retention thresholds;
7. unrelated historical Pint cleanup.

These are not grounds for speculative deletion. Any DELETE/REHOME still requires caller proof and an approved coherent slice.

## Next gate

Pull the active branch and run the focused refactor contract test first. If it passes, run the complete Muasamcong feature regression. Do not create a PR until automated regression, Pint/routes/build as applicable and manual UI/export acceptance are complete.
