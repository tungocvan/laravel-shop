# Muasamcong Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Stable branch: `main`
- Previous stable delivery: **Muasamcong Admin Dashboard — PR #72 MERGED / CLOSED**
- Latest delivery: **Major/Clean Module Refactor — PR #154 MERGED / CLOSED**
- Refactor branch: `refactor/muasamcong-architecture-ui-export-alignment`
- Merge commit: `f440217ee96ec2929b1a67495ddf2c0144031ecd`
- Delivery status: **COMPLETE / MERGED**
- Production enablement/deployment: **NOT AUTHORIZED / NOT CHANGED**

## Delivered scope

The merged refactor keeps Muasamcong as the canonical procurement-domain owner and preserves dependency direction `ClientPortal -> Muasamcong`.

Completed priorities:

1. capability-specific server-side authorization;
2. checkbox export semantics: selected rows when selected, otherwise all rows in the approved matching scope;
3. Admin input/pagination alignment;
4. bounded responsibility extraction without speculative deletion or schema migration.

No database schema, migration, model ownership, API URI, Admin URI, route name, storage contract or production setting was destructively changed.

## Architectural contract

`docs/modules/Muasamcong/MODULE.md` is now the canonical module contract on `main`.

The contract records that:

- Muasamcong is an active `domain` module;
- Admin and ClientPortal are presentation/consumer boundaries, not replacement domain owners;
- Muasamcong persistence is protected;
- Personal Session/raw snapshot/schema boundaries are quarantined from destructive cleanup;
- checkbox export semantics are a module invariant;
- full-project regression is not a default gate for module-scoped refactors.

## Implemented refactor

### Export scope adapters

Canonical route names and URIs are preserved while export endpoints pass through thin scope/authorization adapters:

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

Wishlist uses bounded page-size choices:

```text
10 / 25 / 50 / 100
Default: 25
```

When no Wishlist rows are selected, export reuses the same `q` filter as the list so the action exports all matching rows rather than only the visible page.

The Wishlist UI provides visible bordered search/select controls, explicit current-page selection semantics, selected count, selected-or-all export semantics, selected-only destructive delete, and module-scoped Admin pagination.

### Smart Pricing and Synced Pricing export actions

Canonical `Xuất Excel` actions submit selected IDs when rows are selected. With no selection, the scope adapter resolves the full approved matching dataset.

`Xuất BBG` remains selected-only because it is a separate specialized operation outside this semantic change.

## Compatibility preserved

- `GET /admin/muasamcong/dashboard` remains the Admin dashboard.
- `GET /admin/muasamcong` remains Smart Pricing.
- Existing Admin route names and URIs are preserved.
- Muasamcong API routes are unchanged.
- ClientPortal source/contracts are unchanged in this delivery.
- Existing Excel formatting implementations remain compatibility implementations behind the new adapters.
- No schema/migration/model/storage-path changes were introduced.

## Final verification and acceptance

| Gate | Status | Evidence |
|---|---|---|
| Muasamcong feature regression | PASS | 38 tests, 370 assertions, 2.05s |
| Refactor contract coverage | PASS within module regression | Contract test included under `tests/Feature/Muasamcong` |
| Changed-file Pint | PASS | 7 changed PHP files |
| Module-wide Pint | KNOWN LEGACY DEBT | 4 pre-existing style issues outside PR #154 diff; not reformatted |
| Route registration | PASS | 47 `muasamcong`-named routes listed |
| Frontend build | PASS | Vite 7.3.6, 34 modules transformed, 1.71s |
| Smart Pricing UI | PASS | User confirmed |
| Synced Pricing UI | PASS | User confirmed |
| Wishlist UI | PASS | User confirmed |
| Export selected/all acceptance | PASS | User confirmed |
| Full project regression | NOT APPLICABLE | Approved module-scoped strategy |
| PR #154 | MERGED / CLOSED | 13 files changed |
| Merge commit | COMPLETE | `f440217ee96ec2929b1a67495ddf2c0144031ecd` |
| Local `main` sync | PASS | `main` up to date with `origin/main`; working tree clean |

## Deferred work

Deferred debt remains for separately approved coherent slices:

1. deeper decomposition of `SyncedPricingList`, `ContractorHistory` and `TracuuThuoctrungthau` after behavioral tests prove safe extraction boundaries;
2. broader page-size extraction for remaining fixed-page Smart Pricing/Synced surfaces where not covered by this slice;
3. chunked/streaming export to remove inherited 2,000/5,000 row compatibility caps;
4. atomic Personal Session import-token claim;
5. contractor job/sync idempotency;
6. snapshot/raw-payload/file retention thresholds;
7. unrelated historical Pint cleanup.

These are not grounds for speculative deletion. Any future DELETE/REHOME still requires caller proof and a separately approved coherent slice.

## Closeout

Muasamcong Major/Clean Module Refactor is complete and merged into `main` via PR #154. The local `main` checkout was synchronized after merge and confirmed clean. No additional code changes, production enablement, deployment, schema operation or data migration are part of this closeout.
