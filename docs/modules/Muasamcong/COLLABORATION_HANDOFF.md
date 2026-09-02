# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Muasamcong Admin Dashboard — PR #72 MERGED / CLOSED**
- Active delivery: **Major/Clean Module Refactor**
- Active branch: `refactor/muasamcong-architecture-ui-export-alignment`
- Delivery status: **READY FOR PR — AUTOMATED + UI ACCEPTANCE PASS**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Approved target

The approved refactor keeps Muasamcong as the canonical procurement-domain owner and preserves dependency direction `ClientPortal -> Muasamcong`.

Priorities implemented in this slice:

1. capability-specific server-side authorization;
2. checkbox export semantics: selected rows when selected, otherwise all rows in the approved matching scope;
3. Admin input/pagination alignment;
4. bounded responsibility extraction without speculative deletion or schema migration.

No database schema, migration, model ownership, API URI, Admin URI, route name, storage contract or production setting was destructively changed.

## Architectural contract

`docs/modules/Muasamcong/MODULE.md` was missing at refactor bootstrap and is now present on the active branch.

The contract records that:

- Muasamcong is an active `domain` module;
- Admin and ClientPortal are presentation/consumer boundaries, not replacement domain owners;
- Muasamcong persistence is protected;
- Personal Session/raw snapshot/schema boundaries are quarantined from destructive cleanup;
- checkbox export semantics are a module invariant;
- full-project regression is not a default gate for this module-scoped refactor.

## Implemented batch

### Export scope adapters

Canonical route names and URIs are preserved while export endpoints now pass through thin scope/authorization adapters:

- `PricingExportController` -> existing Smart Pricing export implementation;
- `SyncedPricingScopedExportController` -> existing Synced Pricing exporter;
- `PricingWishlistExportController` -> existing Wishlist exporter.

Canonical behavior:

```text
selected_ids non-empty -> export selected
selected_ids empty     -> export all rows in the approved matching scope
```

The legacy Excel mapping/formatting implementations remain behind the adapters to minimize format-regression risk.

Compatibility caps remain explicit rather than silently truncating data:

- Smart Pricing: 2,000 rows;
- Wishlist: 2,000 rows;
- Synced Pricing: 5,000 rows.

If a full scope exceeds the inherited limit, the endpoint rejects the request instead of silently exporting a partial dataset. Removing these caps is deferred to a dedicated streaming/chunked-export slice.

### Authorization

- Smart Pricing export enforces `muasamcong.pricing.sync` server-side.
- Synced Pricing export enforces `muasamcong.pricing.sync` server-side.
- Wishlist export enforces `muasamcong.pricing.wishlist` server-side.
- Wishlist bulk delete has explicit `permission:muasamcong.pricing.wishlist,admin` route middleware in addition to the existing Admin/view boundary.
- No new permission was introduced.

### Wishlist extraction and UI alignment

Wishlist query ownership was extracted from the oversized `MuasamcongController` into:

- `PricingWishlistController` — thin page controller;
- `PricingWishlistQueryService` — reusable user/filter query scope.

Wishlist now uses bounded page-size choices:

```text
10 / 25 / 50 / 100
Default: 25
```

When no Wishlist rows are selected, export reuses the same `q` filter as the list so the action exports all matching rows rather than only the visible page.

The Wishlist UI now provides visible bordered search/select controls, explicit current-page selection semantics, selected count, selected-or-all export semantics, selected-only destructive delete, and module-scoped Admin pagination.

### Smart Pricing and Synced Pricing export actions

Canonical `Xuất Excel` actions now submit selected IDs when rows are selected. With no selection, the scope adapter resolves the full approved matching dataset.

`Xuất BBG` remains selected-only because it is a separate specialized operation outside the approved semantic change.

## Compatibility preserved

- `GET /admin/muasamcong/dashboard` remains the Admin dashboard.
- `GET /admin/muasamcong` remains Smart Pricing.
- Existing Admin route names and URIs are preserved.
- Muasamcong API routes are unchanged.
- ClientPortal source/contracts are unchanged in this batch.
- Existing Excel formatting implementations remain compatibility implementations behind the new adapters.
- No schema/migration/model/storage-path changes were introduced.

## Tests and acceptance

`tests/Feature/Muasamcong/MuasamcongRefactorArchitectureContractTest.php` covers canonical route ownership, authorization boundaries, nullable export selection, Wishlist page-size constraints, filter-aware all-scope export behavior, pagination contract and `MODULE.md` invariants.

Final local evidence supplied by the user:

| Gate | Status | Evidence |
|---|---|---|
| Muasamcong feature regression | PASS | 38 tests, 370 assertions, 2.05s |
| Refactor contract coverage | PASS within module regression | Contract test is included under `tests/Feature/Muasamcong` |
| Changed-file Pint | PASS | 7 changed PHP files |
| Module-wide Pint | KNOWN LEGACY DEBT | 4 pre-existing style issues outside branch diff; not reformatted in this delivery |
| Route registration | PASS | 47 `muasamcong`-named routes listed; canonical Admin/API/ClientPortal consumers present |
| Frontend build | PASS | Vite 7.3.6, 34 modules transformed, completed in 1.71s |
| Smart Pricing UI | PASS | User confirmed |
| Synced Pricing UI | PASS | User confirmed |
| Wishlist UI | PASS | User confirmed |
| Export selected/all acceptance | PASS | User confirmed selected rows export when selected; all matching export when no selection |
| Full project regression | NOT APPLICABLE | Approved module-scoped strategy |
| PR | READY TO CREATE | All approved gates complete |
| Merge | NOT AUTHORIZED | User reviews and merges manually |

## Deferred work

This slice deliberately does not rewrite the largest Livewire/export implementations in one step.

Deferred debt remains:

1. deeper decomposition of `SyncedPricingList`, `ContractorHistory` and `TracuuThuoctrungthau` after behavioral tests prove safe extraction boundaries;
2. broader page-size extraction for remaining fixed-page Smart Pricing/Synced surfaces where not covered by this slice;
3. chunked/streaming export to remove inherited 2,000/5,000 row compatibility caps;
4. atomic Personal Session import-token claim;
5. contractor job/sync idempotency;
6. snapshot/raw-payload/file retention thresholds;
7. unrelated historical Pint cleanup.

These are not grounds for speculative deletion. Any DELETE/REHOME still requires caller proof and a separately approved coherent slice.

## PR boundary

The active branch is accepted locally and ready for PR review. Before merge:

- review the PR diff and scope;
- do not add unrelated cleanup;
- user performs the merge manually after approval;
- production deployment/enablement remains outside this refactor.
