# Pharma Module Information

Last verified: 2026-09-05

## Purpose

Pharma owns medicine master data, pharmaceutical procurement intelligence, supplier/commercial tracking and XLSX price-list generation.

## Module State

```text
Type: domain
Enabled in config: false
Direct module dependency: Shared
```

Declared permissions:

```text
view_pharma
create_pharma
edit_pharma
delete_pharma
```

Current Pharma Admin workspaces enforce these capabilities through route/component authorization patterns.

## Canonical Ownership

### Medicine Master Canonical — Pharma/HSSP

Tables:

- `pharma_medicines`
- `pharma_medicine_sources`

Purpose:

- canonical medicine/product profile;
- medicine identity and profile-quality state;
- source lineage;
- deterministic matching target for Drug Award records.

### Drug Award Business Canonical — Pharma

Tables:

- `pharma_drug_bid_awards`
- `pharma_drug_bid_award_sources`

Purpose:

- multi-source drug procurement result catalog;
- historical source snapshots;
- source lineage;
- linked/effective HSSP enrichment for medicine attributes.

### Procurement Canonical — Muasamcong

Pharma does not own `muasamcong_kqlcnt_award_items`.

Pharma consumes Muasamcong procurement data only through the explicit integration adapter/service boundary. Acquisition, recovery, raw payloads and procurement warehouse concerns remain owned by Muasamcong.

## Features

- HSSP Medicine Master CRUD/search/filter/data-quality workspace.
- Medicine identity/profile status and source lineage.
- Drug Bid Award CRUD, multi-source filters, HSSP match state and source provenance.
- Bounded manual Muasamcong KQLCNT synchronization.
- Deterministic Medicine resolution and provisional profile creation for strong source identities.
- Selected/all import-export behavior.
- Supplier Tracking CRUD/list/filter/commercial calculations/import-export.
- PriceList workbook analysis, selection and XLSX generation/download.

## Main Routes

Admin prefix: `/admin/pharma`.

Important routes include:

- `/admin/pharma`
- `/admin/pharma/hssp`
- `/admin/pharma/hssp/create`
- `/admin/pharma/hssp/{id}/edit`
- `/admin/pharma/drug-bid-awards`
- `/admin/pharma/drug-bid-awards/create`
- `/admin/pharma/drug-bid-awards/{id}/edit`
- Supplier Tracking workspace routes
- Price List creation route

Pharma exposes no accepted public API contract.

## Core Models

- `Medicine`
- `MedicineSource`
- `DrugBidAward`
- `DrugBidAwardSource`
- `SupplierTracking`

### Medicine

Important intelligence fields include:

- `canonical_identity_key`
- `identity_status`
- `profile_status`
- `shelf_life_months`
- `last_verified_at`

Relations:

- `sources()`
- `drugBidAwards()`

### DrugBidAward

Important intelligence fields include:

- lot identity;
- medicine code/snapshot attributes;
- match state;
- price plan/winning price/amount;
- contractor and investor identifiers;
- publication and contract metadata;
- active state.

Relations:

- `medicine()`
- `sources()`

`effectiveMedicineAttribute()` returns a medicine field value plus provenance origin: `award`, `hssp` or `missing`.

## Identity / Resolution

`MedicineIdentityResolver` uses deterministic matching only.

Strong identity:

- registration number + packaging specification.

Composite identity may use normalized:

- medicine name;
- active ingredient;
- concentration;
- dosage form;
- manufacturer.

Fuzzy matching does not auto-merge records.

Possible outcome/status concepts include verified, provisional, ambiguous and unresolved.

`registration_or_import_license` from procurement is not blindly written into HSSP `registration_number`.

## Partial Data Contract

The canonical invariant is:

```text
VALID RECORD != COMPLETE RECORD
```

Medicine and Drug Award runtime paths tolerate partial source data.

Missing Drug Award medicine attributes may be read from a deterministically linked HSSP profile, but:

- non-empty award snapshots win;
- HSSP-derived values remain visibly HSSP-origin;
- HSSP enrichment does not rewrite the historical source snapshot;
- procurement-only facts are never HSSP-enriched.

The existing Excel Medicine import remains stricter as a source-specific policy.

## Muasamcong Integration

Pharma-owned integration classes:

- `Integrations/Muasamcong/MuasamcongKqlcntAwardAdapter`
- `Integrations/Muasamcong/MuasamcongDrugAwardSyncService`

The sync boundary:

- reads `KqlcntAwardItem`;
- maps to the source-neutral `DrugAwardProjectionData` DTO;
- projects through `DrugAwardProjectionService`;
- does not call Muasamcong UI/controllers;
- does not copy raw recovery payloads into Pharma.

Manual sync behavior:

```text
default batch: 250
hard cap: 1000
continuation: last source ID
permission: edit_pharma
```

If the Muasamcong canonical table is unavailable, sync fails without making existing Pharma catalog pages unavailable.

## HSSP Workspace

`/admin/pharma/hssp` acts as Medicine Master / Product Profile / Data Quality.

Search includes:

- medicine name;
- active ingredient;
- registration number;
- concentration;
- manufacturer;
- manufacturing country.

Filters include:

- profile status;
- circular group;
- special-control state.

The table exposes identity/quality status, product details, source count and linked Drug Award count.

Pagination is bounded to `10/25/50/100`.

## Drug Award Workspace

`/admin/pharma/drug-bid-awards` acts as Multi-source Procurement Award Intelligence.

Search includes:

- medicine name;
- active ingredient;
- medicine code;
- bidding notice/TBMT;
- lot;
- decision.

Filters include:

- investor;
- winning contractor;
- source system;
- HSSP match status.

The UI may show `Bổ sung từ HSSP` for effective medicine values while preserving source snapshots.

Source lineage is displayed when the lineage table exists; legacy schemas fall back to `source_type`.

## Import / Export

Pharma reuses Shared Import/Export infrastructure.

Canonical list export rule:

- selected IDs present -> export exactly selected records;
- no selection -> export all records matching active filters.

Drug Award export supports search, investor, contractor, source and match-status filters.

It may export effective medicine values with provenance and richer procurement fields.

It does not export raw Muasamcong recovery payloads by default.

## Database Migrations Added for Intelligence

- `2026_09_05_010000_add_intelligence_fields_to_medicines_table.php`
- `2026_09_05_011000_create_medicine_sources_table.php`
- `2026_09_05_012000_add_intelligence_fields_to_drug_bid_awards_table.php`
- `2026_09_05_013000_create_drug_bid_award_sources_table.php`
- `2026_09_05_014000_relax_legacy_drug_award_constraints.php`

All five were reported applied successfully on the current development database.

## Verification Snapshot

Latest reported verification:

```text
Focused Pint: PASS
PharmaDrugBidAwardWorkspaceTest: 7 passed / 56 assertions
Full tests/Feature/Pharma: 47 passed / 265 assertions
```

Final UI smoke and frontend production build are still required before PR readiness.

## Maintenance Notes

- Do not merge Medicine Master and Drug Award entities.
- Do not duplicate the Muasamcong procurement warehouse inside Pharma merely for convenience.
- Do not introduce automatic fuzzy merges without a separately approved identity-review workflow.
- Preserve source lineage and provenance whenever adding future ingestion sources.
- Keep Pharma browsing independent of Muasamcong runtime availability; only synchronization should depend on the source module/table.
