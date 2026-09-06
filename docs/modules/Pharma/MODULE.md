# Pharma Module Contract

Last reviewed: 2026-09-06

## Purpose

`Pharma` is the business owner for pharmaceutical product profiles and multi-source drug intelligence. It owns the HSSP Medicine Master, the Pharma Drug Award Business Catalog, supplier tracking, PriceList generation, Official Facility operational ingestion/mirroring, and Pharma Admin workspaces under `/admin/pharma/**`.

## Canonical ownership

### Medicine Master canonical

Pharma owns `pharma_medicines` and `pharma_medicine_sources`.

HSSP describes what the medicine/product is: medicine identity, active ingredient, concentration, dosage form, route, packaging, manufacturer, country, regulatory profile, source lineage and data-quality state.

### Drug Award business canonical

Pharma owns `pharma_drug_bid_awards` and `pharma_drug_bid_award_sources`.

Drug Award describes what medicine won where and under which procurement result: TBMT/lot, quantity, plan/winning price, investor, contractor, decision, contract and historical medicine snapshot.

### Procurement acquisition canonical

`Muasamcong` remains owner of external procurement acquisition/recovery and its canonical KQLCNT warehouse. Pharma may consume that canonical through an explicit integration adapter/service, but must not depend on Muasamcong UI/controllers or rewrite Muasamcong persistence.

### Healthcare organization canonical

`Partner` remains the sole canonical organization master for hospitals/healthcare facilities. Pharma must not create a second Hospital/Facility business master.

Partner owns:

- `partners`;
- `partner_source_references`.

`partners.province_code` is nullable and source-independent. Source-specific province identifiers such as BHXH `92TTT` never belong in `partners.province_code`.

Healthcare official identities are Partner-owned and use generic `(source, external_id)` uniqueness in `partner_source_references`. Do not add source-specific `bhxh_id`, `moh_id`, or similar columns to `partners`.

## Official Facility operational layers

Pharma owns two non-canonical operational layers for official healthcare facilities.

### Import staging/audit

- `pharma_official_import_batches`;
- `pharma_official_import_rows`.

Canonical import flow:

`Official XLSX/CSV -> Upload -> Pharma staging -> Validate/Normalize -> Match/Dedupe -> Preview -> Explicit checkbox selection -> Partner + Partner source reference`.

Upload/staging must never mutate Partner.

### Official source mirror/cache

- `pharma_official_source_sync_batches`;
- `pharma_official_source_facilities`.

Canonical source-mirror flow:

`External official source -> human-approved lookup -> server-side snapshot -> queue -> Pharma local mirror -> later Official Facility staging -> Partner`.

The source mirror is a source cache/history surface, not a Hospital/Facility business master. It exists so source data remains searchable when an external API is temporarily unavailable or later changes.

## Official source identity and enrichment

For the source mirror, identity is unique by `(source, external_id)`.

For BHXH, `external_id` is the official `Mã CSKCB`. This identifier is intentionally retained as the stable source key for future detail enrichment.

`pharma_official_source_facilities` separates list-snapshot data from future detail enrichment:

- `raw_payload` + `payload_hash` = basic listing/source snapshot;
- `source_details` + `details_hash` + `details_synced_at` = future source-specific detail enrichment by source identity.

A basic listing sync must never erase previously fetched `source_details`.

## BHXH interactive lookup boundary

Workspace: `/admin/pharma/official-facilities/bhxh`.

Current BHXH lookup is explicitly human-in-the-loop:

1. ERP loads the public BHXH lookup page to establish source session state.
2. ERP loads the BHXH CAPTCHA image with the same source session cookie.
3. The user reads and manually enters CAPTCHA.
4. ERP posts the public form fields `MaTinh`, `MaQuanHuyen`, and `tokenRecaptch` to the BHXH facility lookup endpoint.
5. ERP may store the successfully returned snapshot in its own server session for a subsequent explicit sync action.

No OCR, CAPTCHA solving, CAPTCHA bypass, credential circumvention, unattended CAPTCHA lookup, or hidden retry automation is part of the contract.

Province selection is presented by human-readable Tỉnh/Thành names. Duplicate/legacy BHXH province codes may be preserved as aliases internally while UI presents one display option per name. District selection is loaded from the public BHXH district endpoint based on the selected source province code.

## Source mirror synchronization semantics

Permission `sync_pharma_official_facilities` governs external-source -> local-mirror synchronization.

After a successful BHXH lookup, clicking the explicit sync action creates `pharma_official_source_sync_batches` and dispatches `PersistOfficialSourceSnapshotJob`.

Queue states include:

- `QUEUED`;
- `RUNNING`;
- `COMPLETED`;
- `FAILED`.

Full-province sync rules:

- seen source records are created, updated, or reactivated;
- previously active source records in the same province that are absent from the new complete province snapshot become stale (`is_active = false`);
- stale records are retained and never hard-deleted by sync.

District sync rules:

- seen records are upserted;
- district sync must never mark other records in the same province stale.

Source mirror synchronization must not write Partner directly.

## Multi-source architecture

Medicine/award flow:

`Muasamcong | Excel Pharma | Internal Pharma | Future Sources`

-> normalize source payload
-> deterministic medicine identity resolution
-> Pharma Medicine Master + source lineage
-> Pharma Drug Award Business Catalog + source lineage
-> Pharma search / reports / analytics / export.

Multiple physical source records may resolve to one Pharma business record. Source lineage must remain auditable.

## Persistence ownership

Canonical and operational Pharma persistence includes:

- `pharma_medicines`;
- `pharma_medicine_sources`;
- `pharma_drug_bid_awards`;
- `pharma_drug_bid_award_sources`;
- `pharma_supplier_trackings`;
- `pharma_drug_bid_award_allocations`;
- `pharma_drug_bid_award_contracts`;
- `pharma_official_import_batches`;
- `pharma_official_import_rows`;
- `pharma_official_source_sync_batches`;
- `pharma_official_source_facilities`.

Cross-module code must use explicit module/service boundaries rather than writing these tables directly.

## Medicine identity and quality

Medicine identity resolution is deterministic in the current phase.

Strong identity may use a verified registration identifier plus packaging. Exact normalized product composites may use medicine name, active ingredient, concentration, dosage form and manufacturer. Weak or ambiguous matches must not be silently auto-merged.

Current identity states include `verified_registration`, `exact_normalized`, `provisional`, `ambiguous`, and `unverified`.

Current profile-quality states include `incomplete`, `complete`, `verified`, and `needs_review`.

**VALID RECORD != COMPLETE RECORD.** Pharma must tolerate partial source records and must not invent values merely to satisfy old NOT NULL/UI assumptions.

Automated fuzzy matching is not part of the current contract.

## HSSP enrichment invariant

A Drug Award may be linked to a Pharma Medicine by `medicine_id`, but the two entities remain separate.

When rendering/exporting medicine attributes from a Drug Award:

1. a non-empty historical Drug Award source value wins;
2. otherwise a deterministically linked HSSP value may provide an effective value;
3. the effective value must retain origin metadata (`award`, `hssp`, or `missing`).

HSSP enrichment must never overwrite or impersonate procurement-origin data and must never enrich procurement-only facts such as winning price, plan price, quantity, investor, contractor, decision or contract.

`winning_price` is not `declared_price`. `registration_or_import_license` must not be blindly copied into HSSP `registration_number`.

## Source lineage

`pharma_medicine_sources` and `pharma_drug_bid_award_sources` are the canonical Pharma medicine/award lineage tables.

Lineage identity uses `(source_system, source_record_type, source_record_key)` and records source reference/hash/observation/sync/verification state where available.

Legacy `source_type/source_id` on Drug Award remains a compatibility surface during migration, not the long-term lineage model.

Raw Muasamcong recovery payloads, contractor-search internals, import-batch internals and recovery state do not belong in the default Pharma business record/export.

## Muasamcong synchronization boundary

Pharma may synchronize KQLCNT through `Modules\Pharma\Integrations\Muasamcong`.

Rules:

- no Pharma controller/Livewire component calls a Muasamcong controller;
- no HSSP page directly queries Muasamcong tables;
- Muasamcong model access is confined to the explicit integration adapter/sync service;
- web-triggered sync must be bounded;
- if Muasamcong canonical persistence is unavailable, synchronization may fail gracefully while existing Pharma browsing remains usable.

## Authorization boundary

Pharma Admin routes require `web` + `auth:admin` and the appropriate capability.

Base capabilities:

- `view_pharma`;
- `create_pharma`;
- `edit_pharma`;
- `delete_pharma`.

Official Facility capabilities:

- `view_pharma_official_facilities`;
- `sync_pharma_official_facilities`;
- `import_pharma_official_facilities`;
- `resolve_pharma_official_facility_conflicts`.

Meaning:

- `view_*` = browse/import/source workspaces and interactive read-only source lookup;
- `sync_*` = external official source -> Pharma local mirror;
- `import_*` = staging -> Partner canonical master;
- `resolve_*` = explicit ambiguous/conflict adjudication.

Mutations must authorize server-side through route middleware/service boundaries.

## Admin workspace contract

Canonical entry point: `/admin/pharma`.

Primary workspaces:

- `/admin/pharma/hssp` = Medicine Master / Product Profile / Data Quality;
- `/admin/pharma/drug-bid-awards` = Multi-source Procurement Award Intelligence;
- `/admin/pharma/official-facilities/import` = XLSX/CSV staging, preview, conflict and selected-only Partner import;
- `/admin/pharma/official-facilities/bhxh` = human-in-the-loop live BHXH lookup;
- `/admin/pharma/official-facilities/source` = local official-source mirror/history browser.

Production list workspaces use bounded page sizes `10/25/50/100`; there is no `All` mode. Filtering, pagination, selection, loading, empty and error states follow `.codex/standards/ADMIN_UI_STANDARD.md`.

## Official Facility Import invariants

Phase 1 import supports XLSX and CSV. No PDF or fuzzy/AI matching.

Matching priority is deterministic:

1. `source + external_id`;
2. `tax_code`;
3. normalized name + canonical province;
4. normalized name + normalized address.

Rows are classified as `NEW`, `EXACT`, `LIKELY_MATCH`, `CONFLICT`, or `INVALID`. `LIKELY_MATCH` and `CONFLICT` require explicit resolution and must never be silently auto-merged.

Partner mutation rules:

- explicit selected rows only;
- new Partner defaults to `legal_type=hospital`, `partner_types=['customer']`, `status=active`, `source=import`;
- existing `phone`, `email`, `contact_person`, and name are not automatically overwritten;
- address and canonical province are safe-fill only when empty; conflicting non-empty canonical province blocks/requires review;
- tax code is safe-fill only when empty; conflicting non-empty tax code blocks/requires review;
- re-importing the same official identity updates source observation metadata/`last_seen_at` and must not duplicate Partner;
- Partner/source-reference writes are transactional per selected row.

File controls include XLSX/CSV extension/MIME/size checks, a 10,000-row parser ceiling, normalized input values, SHA-256 duplicate-file warning and duplicate-row staging outcomes. Duplicate-file detection warns but does not itself block legitimate idempotent re-import.

## Export contract

For Pharma list workspaces using row selection:

- selected checkboxes non-empty -> export exactly selected records;
- no selection -> export the complete dataset matching active export filters, not only the visible page;
- `selected_ids` takes precedence over ordinary filters;
- export selection does not depend on `delete_pharma`;
- Drug Award default export may include source/effective provenance but must not export raw recovery payloads.

## Source-specific import policy

Canonical HSSP persistence may represent incomplete profiles without requiring every source importer to accept incomplete input.

The existing Medicine Excel importer may continue to require historical registration/product fields as a source-specific validation contract. Manual/profile and source-projection paths may create or retain incomplete/provisional HSSP records without fake placeholder values.

## Accepted additional invariants

- Drug Award projection must be idempotent by source lineage and canonical business identity.
- Null source updates must not erase better non-null canonical/source snapshot values.
- A contract period maps to `contract_duration_months` only when the source unit is months.
- Supplier Tracking retains its accepted business-key and delete rules until separately changed.
- PriceList generation remains service-controlled, server-side and private.

## Public/API boundary

Pharma exposes no public ERP API contract. `routes/api.php` must not gain public Pharma endpoints without separately approved authorization design.

The BHXH integration is server-side consumption of public lookup surfaces from authenticated Admin workspaces; it does not expose a public Pharma API and does not bypass CAPTCHA/session protections.

## Deferred scope

- fuzzy/AI automatic Medicine or facility merging;
- automated adjudication of ambiguous facility matches;
- CAPTCHA OCR/solving/bypass;
- unattended scheduled BHXH facility lookup that requires CAPTCHA;
- automatic source mirror -> Partner writes;
- source-specific IDs on `partners`;
- PDF facility import;
- source-detail enrichment implementation beyond the reserved `source_details` boundary;
- Pharma runtime enablement changes;
- unrelated Supplier Tracking/PriceList redesign.

## Refactor rule

Refactoring Pharma must preserve the ownership and provenance boundaries above. Prefer explicit domain services/adapters over shared mega-components, and never collapse Medicine Master, Drug Award business records, external procurement acquisition, source mirror, import staging and Partner organization master into one table/entity merely because fields overlap.
