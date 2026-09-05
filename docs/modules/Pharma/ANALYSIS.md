# Pharma Module Analysis

Last verified: 2026-09-05

Scope: `Modules/Pharma/**`, `docs/modules/Pharma/**`, direct Shared/Admin dependencies, and the explicit Pharma-owned Muasamcong KQLCNT integration boundary.

## Executive Summary

Pharma has moved from a sync-ready Drug Bid Award workspace to the approved **Option B+ — Pharma Multi-source Medicine Intelligence Architecture**.

The accepted ownership model is now:

1. **Procurement Canonical — Muasamcong owner**: `muasamcong_kqlcnt_award_items` and source acquisition/recovery concerns.
2. **Medicine Master Canonical — Pharma/HSSP owner**: `pharma_medicines` + `pharma_medicine_sources`.
3. **Drug Award Business Canonical — Pharma owner**: `pharma_drug_bid_awards` + `pharma_drug_bid_award_sources`.

HSSP describes the medicine/product. Drug Award describes a procurement result. They link by `medicine_id`, but they remain separate business entities.

The core invariant is:

> **VALID RECORD != COMPLETE RECORD.** Missing medicine attributes may be enriched from a deterministically linked HSSP profile for read/export purposes, but enrichment must never overwrite a non-empty historical award snapshot or be represented as procurement-origin data.

## Implemented Architecture

### Medicine Master / HSSP

`pharma_medicines` now supports canonical identity, identity status, profile-quality status, nullable partial-source fields, shelf-life metadata and verification timestamps.

`pharma_medicine_sources` records source-system lineage for one canonical Medicine profile.

`MedicineIdentityResolver` performs deterministic resolution only. Fuzzy matching remains suggestion-only and does not auto-merge records.

Identity outcomes currently include verified, provisional, ambiguous and unresolved states. Profile-quality states distinguish incomplete, complete, verified and needs-review profiles.

Manual/runtime paths allow incomplete Medicine profiles without fake placeholder values. The legacy Excel Medicine import remains intentionally strict as a source-specific contract.

### Drug Award Business Catalog

`pharma_drug_bid_awards` now carries richer KQLCNT-compatible procurement fields including lot, medicine code/snapshot attributes, plan/winning pricing, contractor/investor identifiers, publication metadata and contract fields.

Legacy constraints that prevented partial source records were relaxed. Quantity now supports decimal source values.

`pharma_drug_bid_award_sources` provides many-source-to-one-award lineage through the unique source identity `(source_system, source_record_type, source_record_key)`.

`DrugAwardProjectionService` is the canonical multi-source write boundary. It:

- resolves Medicine deterministically;
- optionally creates provisional HSSP profiles only when strong identity signals exist;
- preserves non-empty historical source values;
- performs idempotent source-lineage upserts;
- keeps source payload/recovery internals outside Pharma business tables.

`DrugBidAward::effectiveMedicineAttribute()` provides read-layer provenance with `award`, `hssp` or `missing` origin.

Procurement-only facts — price, quantity, investor, contractor, decision and contract — are never HSSP-enriched.

### Muasamcong Boundary

Pharma owns the explicit integration adapter:

- `Integrations/Muasamcong/MuasamcongKqlcntAwardAdapter.php`
- `Integrations/Muasamcong/MuasamcongDrugAwardSyncService.php`

There is no Pharma dependency on Muasamcong UI/controllers and no direct HSSP page query of Muasamcong tables.

The manual sync action is bounded:

- default 250 rows per request;
- hard service cap 1000;
- cursor continues from the last Muasamcong source ID;
- guarded by `edit_pharma`;
- missing Muasamcong canonical table affects sync only, not browsing existing Pharma data.

### Legacy-schema Compatibility

List/export behavior detects whether `pharma_drug_bid_award_sources` exists before eager-loading or filtering source lineage. This preserves compatibility with focused tests or legacy schemas that still expose only `source_type`, while production intelligence schemas use both legacy source metadata and lineage.

## Workspace Analysis

### `/admin/pharma/hssp`

The HSSP workspace is now the **Medicine Master / Product Profile / Data Quality** surface.

It supports:

- expanded search by name, ingredient, registration, concentration, manufacturer and country;
- profile-quality filtering;
- existing circular-group and special-control filters;
- bounded `10/25/50/100` pagination;
- identity/profile status visibility;
- source count and linked award count;
- selected/all export semantics.

### `/admin/pharma/drug-bid-awards`

The workspace is now **Multi-source Procurement Award Intelligence**.

It supports:

- search across medicine, ingredient, medicine code, TBMT, lot and decision;
- investor, contractor, source-system and HSSP match-status filters;
- source lineage display;
- visible `Bổ sung từ HSSP` provenance for effective medicine fields;
- bounded manual KQLCNT synchronization;
- safe rendering of nullable/decimal source data;
- selected/all export semantics.

## Export Analysis

For Drug Bid Awards:

- selected IDs non-empty -> export exactly selected records;
- no selection -> export the complete active filtered dataset;
- active filters include search, investor, contractor, source and Medicine match state;
- exported medicine attributes may use effective values with provenance;
- raw Muasamcong `raw_payload`, recovery internals and contractor-search/import-batch implementation details are excluded.

The existing strict Excel import mapping remains source-specific and is not treated as the universal ingestion contract.

## Verification Evidence

Latest local verification reported by the user:

```text
Focused Pint: PASS
PharmaDrugBidAwardWorkspaceTest: 7 passed, 56 assertions
Full tests/Feature/Pharma: 47 passed, 265 assertions
```

All five intelligence migrations were previously applied successfully:

- `2026_09_05_010000_add_intelligence_fields_to_medicines_table`
- `2026_09_05_011000_create_medicine_sources_table`
- `2026_09_05_012000_add_intelligence_fields_to_drug_bid_awards_table`
- `2026_09_05_013000_create_drug_bid_award_sources_table`
- `2026_09_05_014000_relax_legacy_drug_award_constraints`

No full-project regression is required by the agreed workflow; validation remains scoped to Pharma and directly impacted behavior.

## Remaining Acceptance Gates

Before PR readiness:

1. run production frontend build;
2. manually smoke `/admin/pharma/hssp`;
3. manually smoke `/admin/pharma/drug-bid-awards`;
4. verify bounded KQLCNT sync as an `edit_pharma` admin;
5. verify selected-row export;
6. verify no-selection filtered export;
7. verify pagination and input treatment;
8. record UI PASS and final build evidence in `COLLABORATION_HANDOFF.md`.

## Final Recommendation

Keep the approved B+ architecture. Do not collapse Medicine Master and Drug Award into one table, do not duplicate Muasamcong procurement warehouse data, and do not introduce fuzzy automatic merging in this phase.

Future work should build on the explicit source-lineage and deterministic identity boundaries rather than reintroducing UI/controller coupling between Pharma and Muasamcong.
