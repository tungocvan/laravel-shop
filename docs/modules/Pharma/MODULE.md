# Pharma Module Contract

Last reviewed: 2026-09-05

## Purpose

`Pharma` is the business owner for pharmaceutical product profiles and multi-source drug intelligence. It owns the HSSP Medicine Master, the Pharma Drug Award Business Catalog, supplier tracking, PriceList generation and Pharma Admin workspaces under `/admin/pharma/**`.

## Canonical ownership

### Medicine Master canonical

Pharma owns `pharma_medicines` and `pharma_medicine_sources`.

HSSP describes **what the medicine/product is**: medicine identity, active ingredient, concentration, dosage form, route, packaging, manufacturer, country, regulatory profile, source lineage and data-quality state.

### Drug Award business canonical

Pharma owns `pharma_drug_bid_awards` and `pharma_drug_bid_award_sources`.

Drug Award describes **what medicine won where and under which procurement result**: TBMT/lot, quantity, plan/winning price, investor, contractor, decision, contract and historical medicine snapshot.

### Procurement acquisition canonical

`Muasamcong` remains owner of external procurement acquisition/recovery and its canonical KQLCNT warehouse. Pharma may consume that canonical through an explicit integration adapter/service, but must not depend on Muasamcong UI/controllers or rewrite Muasamcong persistence.

## Multi-source architecture

Canonical flow:

`Muasamcong | Excel Pharma | Internal Pharma | Future Sources`

-> normalize source payload
-> deterministic medicine identity resolution
-> Pharma Medicine Master + source lineage
-> Pharma Drug Award Business Catalog + source lineage
-> Pharma search / reports / analytics / export.

Multiple physical source records may resolve to one Pharma business record. Source lineage must remain auditable.

## Persistence ownership

Canonical Pharma persistence includes:

- `pharma_medicines`;
- `pharma_medicine_sources`;
- `pharma_drug_bid_awards`;
- `pharma_drug_bid_award_sources`;
- `pharma_supplier_trackings`.

Cross-module code must use an explicit Pharma integration/service boundary rather than writing these tables directly.

## Medicine identity and quality

Medicine identity resolution is deterministic in the current phase.

Strong identity may use a verified registration identifier plus packaging. Exact normalized product composites may use medicine name, active ingredient, concentration, dosage form and manufacturer. Weak or ambiguous matches must not be silently auto-merged.

Current identity states include:

- `verified_registration`;
- `exact_normalized`;
- `provisional`;
- `ambiguous`;
- `unverified`.

Current profile-quality states include:

- `incomplete`;
- `complete`;
- `verified`;
- `needs_review`.

**VALID RECORD != COMPLETE RECORD.** Pharma must tolerate partial source records and must not invent values merely to satisfy old NOT NULL/UI assumptions.

Automated fuzzy matching is not part of the current contract.

## HSSP enrichment invariant

A Drug Award may be linked to a Pharma Medicine by `medicine_id`, but the two entities remain separate.

When rendering/exporting medicine attributes from a Drug Award:

1. a non-empty historical Drug Award source value wins;
2. otherwise a deterministically linked HSSP value may provide an effective value;
3. the effective value must retain origin metadata (`award`, `hssp`, or `missing`).

HSSP enrichment must never overwrite or impersonate procurement-origin data.

HSSP must never enrich procurement-only facts such as winning price, plan price, quantity, investor, contractor, decision or contract.

`winning_price` is not `declared_price`. `registration_or_import_license` must not be blindly copied into HSSP `registration_number`.

## Source lineage

`pharma_medicine_sources` and `pharma_drug_bid_award_sources` are the canonical lineage tables.

Lineage identity uses `(source_system, source_record_type, source_record_key)` and records source reference/hash/observation/sync/verification state where available.

Legacy `source_type/source_id` on Drug Award remains a compatibility surface during migration, not the long-term lineage model.

Raw Muasamcong recovery payloads, contractor-search internals, import-batch internals and recovery state do not belong in the default Pharma business record/export.

## Muasamcong synchronization boundary

Pharma may synchronize KQLCNT through `Modules\Pharma\Integrations\Muasamcong`.

Rules:

- no Pharma controller/Livewire component calls a Muasamcong controller;
- no HSSP page directly queries Muasamcong tables;
- Muasamcong model access is confined to the explicit integration adapter/sync service;
- web-triggered sync must be bounded; the current workspace sync processes at most 250 source rows per action and can continue from the last source ID;
- if the Muasamcong canonical table/module is unavailable, synchronization may fail gracefully while existing Pharma Medicine/Drug Award browsing remains usable;
- the sync path uses `edit_pharma`; no new permission is introduced for this objective.

## Authorization boundary

- Pharma Admin routes require `web` + `auth:admin` and the appropriate Pharma capability.
- Canonical capabilities are `view_pharma`, `create_pharma`, `edit_pharma`, and `delete_pharma`.
- Livewire mutations must authorize server-side.
- Row selection is available to export/edit-capable users independently of delete permission.
- Destructive actions remain independently guarded by `delete_pharma`.

## Admin workspace contract

- Canonical entry point: `/admin/pharma`.
- HSSP: `/admin/pharma/hssp` = Medicine Master / Product Profile / Data Quality workspace.
- Drug Awards: `/admin/pharma/drug-bid-awards` = Multi-source Procurement Award Intelligence workspace.
- Production list workspaces use bounded page sizes `10/25/50/100`; there is no `All` mode.
- Filtering, pagination, selection, loading, empty and error states follow `.codex/standards/ADMIN_UI_STANDARD.md`.
- Inputs/selects have visible boundaries in the default/empty state and explicit focus/disabled states.
- Selection is page-scoped; changing page or filters clears the current page selection.

## Export contract

For Pharma list workspaces using row selection:

- selected checkboxes non-empty -> export exactly the selected records;
- no selection -> export the complete dataset matching active export filters, not only the visible page;
- `selected_ids` takes precedence over ordinary filters when determining the exported record set;
- export selection does not depend on `delete_pharma`;
- Drug Award default export may include source/effective provenance but must not export raw recovery payloads.

## Source-specific import policy

The ability of canonical HSSP persistence to represent incomplete profiles does not require every source importer to accept incomplete input.

The existing Medicine Excel importer may continue to require its historical registration/product fields as a source-specific validation contract. Manual/profile and source-projection paths may create or retain incomplete/provisional HSSP records without fake placeholder values.

## Accepted additional invariants

- Drug Award projection must be idempotent by source lineage and canonical business identity.
- Null source updates must not erase better non-null canonical/source snapshot values.
- A contract period is mapped to `contract_duration_months` only when the source unit is months.
- Supplier Tracking retains its accepted business-key and delete rules until separately changed.
- PriceList generation remains service-controlled, server-side and private.

## Public/API boundary

Pharma currently exposes no public API contract. `routes/api.php` must not gain public Pharma endpoints without a separately approved objective and authorization design.

## Deferred scope

- fuzzy/AI automatic Medicine merging;
- unattended/background large-volume KQLCNT synchronization;
- automated adjudication of ambiguous Medicine matches;
- Pharma runtime enablement changes;
- unrelated Supplier Tracking/PriceList redesign.

## Refactor rule

Refactoring Pharma must preserve the ownership and provenance boundaries above. Prefer explicit domain services/adapters over shared mega-components, and never collapse Medicine Master, Drug Award business records and external procurement acquisition into one table/entity merely because fields overlap.
