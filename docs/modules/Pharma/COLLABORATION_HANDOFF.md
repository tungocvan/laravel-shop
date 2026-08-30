# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Phase: Major Refactor
- Branch: `refactor/pharma-drug-bid-award-sync-foundation`
- Status: **DRUG BID AWARD WORKSPACE + SYNC-READY FOUNDATION ACCEPTED — READY FOR PR REVIEW**
- Date: 2026-08-30
- Application source modified: **YES**
- Production/runtime enablement changed: **NO**

## Already merged foundation

- PR #88 merged MR-1 Security Foundation + MR-2 Shared Import/Export Hardening.
- PR #89 merged the dedicated Pharma Admin Dashboard at `/admin/pharma`.
- PR #90 merged MR-3 Medicine/HSSP Workspace, which is the Admin workspace reference pattern for Pharma.

## Completed scope — MR-4 Drug Bid Award Workspace + Sync-Ready Foundation

### Workspace hardening

- Drug Bid Award pagination is bounded to `10`, `25`, `50`, `100`; the legacy `All` / `999999` path is removed.
- Selection and destructive bulk actions are page-scoped.
- Bulk delete requires a confirmation modal that states the selected count/scope.
- Search/filter/page changes reset selection deterministically.
- Create/edit/delete/import-export controls are permission-aware and retain action-level authorization.
- Loading-disabled states and responsive Admin workspace behavior were added.
- Existing `Quay về Dashboard Pharma` navigation remains available.

### Scalable filtering and Medicine/HSSP lookup

- Investor and winning-company filters no longer materialize unbounded distinct collections; they use debounced partial-name server filtering.
- Medicine/HSSP lookup is server-side and bounded to 25 candidates.
- Lookup supports medicine name, registration number and active ingredients.
- Drug Bid Award snapshot fields remain independent of the nullable Medicine/HSSP relation, so unmatched source records can exist without inventing a Medicine mapping.

### Source provenance and sync-ready boundary

Pharma remains the owner of the canonical Drug Bid Award projection. Muasamcong remains the owner of procurement ingestion/source data. Pharma does not couple its workspace/service directly to Muasamcong models.

Additive source metadata was introduced on `pharma_drug_bid_awards`:

- `source_type` — defaults to `manual`;
- `source_id` — nullable source UUID;
- `source_synced_at` — source projection timestamp;
- `source_payload_hash` — reserved for later reconciliation/change detection;
- unique source identity on `(source_type, source_id)`.

`DrugBidAwardSourceData` is the normalized Pharma-side projection contract. `DrugBidAwardService::projectFromSource()` provides the idempotent source-identity boundary for the later integration slice. Manual CRUD remains supported and explicitly stores `source_type=manual` with no source ID.

### Business-key collision policy

The existing business unique key `(bidding_notice_code, medicine_name, winning_company_name)` remains meaningful but is not used as external source identity.

Before source projection create/update, Pharma checks for another row with the same business key. A collision raises an explicit logic conflict and does not silently overwrite a manual or differently sourced record. The database unique constraints remain the final integrity boundary.

### Muasamcong integration mapping identified for the later integration slice

Expected normalized mapping:

- Muasamcong `source_id` -> Pharma source identity;
- `ma_tbmt` -> `bidding_notice_code`;
- `ten_cdt_bmt` -> `investor_name`;
- `ten_thuoc` -> `medicine_name`;
- `quy_cach_dong_goi` -> `packaging_specification`;
- `so_luong` -> `quantity` after an explicit numeric policy;
- `don_gia` -> `unit_price` after an explicit precision policy;
- `so_quyet_dinh` -> `decision_number`;
- `ngay_ban_hanh_quyet_dinh` -> `decision_date`;
- `winning_name` -> `winning_company_name` only after explicit array normalization;
- deterministic Medicine matching may populate nullable `medicine_id`.

Important unresolved source/schema differences are intentionally not guessed in MR-4:

- Muasamcong quantity is decimal while current Pharma quantity is unsigned integer.
- Muasamcong unit price has greater precision than current Pharma unit price.
- Muasamcong source fields may be nullable where current Pharma fields are required.
- No approved Muasamcong source mapping exists yet for `contract_duration_months`.
- No approved source mapping exists yet for `decision_document_url`.
- `winning_name` is structured/array source data and requires an explicit normalization rule.
- No fuzzy Medicine auto-matching is allowed without a later approved matching policy.

Therefore actual Muasamcong -> Pharma production synchronization is **NOT enabled by MR-4**.

## Local Drug Bid Award demo pack

A repeatable local-only UI/E2E dataset is available:

```bash
php artisan reset:pharma-drug-bid-award-demo
```

Safety and coverage:

- refuses to run outside `APP_ENV=local`;
- deletes only records carrying the dedicated `DEMO-PHARMA-` identifiers; it does not truncate Pharma tables;
- creates 12 identifiable demo Medicine/HSSP records;
- creates 30 Drug Bid Award records: 24 manual + 6 projected as `muasamcong` source;
- includes linked and unmatched HSSP scenarios;
- provides enough data for three pages at 10 records/page and for investor/company/source filtering;
- source demo records are created through the Pharma projection contract rather than by bypassing the service boundary.

## Verification completed

- Corrective focused Drug Bid Award workspace gate:
  - `php artisan test tests/Feature/Pharma/PharmaDrugBidAwardWorkspaceTest.php`
  - **PASS — 6 tests, 48 assertions**.
- Focused Pharma regression after workspace corrective:
  - `php artisan test tests/Feature/Pharma Modules/Pharma/Tests`
  - **PASS — 27 tests, 153 assertions**.
- Demo command safety/coverage test:
  - `php artisan test tests/Feature/Pharma/PharmaDrugBidAwardDemoCommandTest.php`
  - **PASS — 2 tests, 14 assertions**.
- Pint gate for the demo command/test:
  - **PASS — 2 files**.
- Demo reset runtime:
  - **PASS — 30 awards, 12 HSSP, 24 manual + 6 Muasamcong source records**.
- Earlier MR-4 frontend build after application/UI changes:
  - `npm run build`
  - **PASS — Vite production build completed**.
- Manual UI smoke against the standardized demo dataset:
  - **PASS**.
  - Verified Drug Bid Award workspace, bounded pagination/filtering, source provenance, HSSP lookup/link states, page-scoped selection/bulk confirmation, create/edit behavior, Import/Export presence, responsive behavior and Dashboard navigation.

No full-project regression was run; testing remains intentionally focused on Pharma and directly impacted behavior.

## Scope intentionally deferred

- Actual Muasamcong -> Pharma production synchronization/wiring.
- Final numeric/nullability/structured-name mapping policy for Muasamcong source data.
- Automated fuzzy Medicine matching.
- Local edit/delete lock policy for externally sourced rows unless separately approved.
- SupplierTracking business-key integrity, duplicate audit and bounded selection.
- PriceList public Livewire analysis state, repeated analysis and deeper pipeline hardening.
- Queue/performance work unless later benchmarking proves it necessary.
- Production enablement.

## Next approved Major Refactor sequence

After this MR-4 PR is reviewed and merged, continue only after explicit user confirmation with:

1. **MR-5 Supplier Tracking Integrity + Workspace**.
2. **MR-6 PriceList Security + Pipeline**.
3. **MR-7 Final Acceptance + closeout**.

The actual Muasamcong -> Pharma Drug Bid Award integration is a separate integration slice and must not be silently folded into MR-5. Before implementing it, explicitly approve the source normalization/mapping policies documented above.

Do not begin MR-5, production sync, production enablement or unrelated module cleanup until this PR is merged and the user confirms continuation.
