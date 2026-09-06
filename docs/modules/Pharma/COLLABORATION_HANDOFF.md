# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Official Facility Import + BHXH Source Mirror**
- Branch: `feat/pharma-official-facility-import`
- Base: `main` at `1f77f1575050648c143d45339d0ec8535e9dba6e`
- Status: **IMPLEMENTATION COMPLETE FOR UI CONTRACT — UI PASS; final focused CLI gate pending**
- Date: 2026-09-06
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one PR**

## Canonical ownership

`Partner` remains the sole canonical organization master for hospitals/healthcare facilities. Pharma must not create a second Hospital/Facility master.

Pharma owns two non-canonical operational layers: Official Facility import staging/audit and Official Source mirror/cache. Neither may bypass the explicit matcher/importer flow to mutate Partner.

## Official import pipeline

`Official XLSX/CSV -> Upload -> Pharma staging -> Validate/Normalize -> Match/Dedupe -> Preview -> Explicit checkbox selection -> Partner + PartnerSourceReference`.

Partner-owned generic provenance remains `(source, external_id)` in `partner_source_references`. `partners.province_code` is canonical/source-independent; BHXH codes never belong there.

## BHXH interactive lookup

Workspace: `/admin/pharma/official-facilities/bhxh`.

The integration is human-in-the-loop: ERP bootstraps the public BHXH session, loads CAPTCHA with that session, the user manually enters CAPTCHA, and ERP submits the selected BHXH source geography. No OCR, CAPTCHA solving/bypass, unattended lookup, or hidden multi-request CAPTCHA reuse is implemented.

The observed BHXH facility listing exposes the basic facility identity used by this integration: `Mã CSKCB` and `Tên CSKCB`. `Mã CSKCB` is retained as the durable BHXH source identity for future detail enrichment.

Manual BHXH lookup/sync UI verification is **PASS** as of 2026-09-06.

## Canonical province vs BHXH source geography

ERP geography and BHXH source geography are intentionally separate.

A human-facing ERP province may map to one or more BHXH **source partitions**. Duplicate BHXH province labels are not treated as primary/fallback aliases.

Verified example:

`Tỉnh An Giang -> 89TTT (Khu vực An Giang cũ) + 91TTT (Khu vực Kiên Giang cũ)`.

The BHXH workspace therefore uses three concepts:

1. `Tỉnh/Thành` — ERP-facing/canonical province label.
2. `Vùng dữ liệu BHXH` — exact source partition/code used for BHXH requests.
3. `Địa bàn BHXH` — source district/geography returned for that partition; it may reflect legacy administrative geography.

For duplicate-name provinces whose historical partition meaning has not been verified, UI labels remain neutral and include the source code. Do not invent old-region names.

A CAPTCHA is consumed by exactly one lookup request against the explicitly selected source partition. The old alias-fallback behavior is removed.

## Official source mirror/cache

Approved flow:

`BHXH live lookup -> server-side snapshot -> queued persistence -> local source mirror -> later Official Facility staging -> Partner`.

Pharma-owned mirror tables:

- `pharma_official_source_sync_batches`;
- `pharma_official_source_facilities`.

Source identity is unique by `(source, external_id)`. The mirror keeps listing payload (`raw_payload`, `payload_hash`) separate from reserved future enrichment (`source_details`, `details_hash`, `details_synced_at`). Basic listing sync must never erase enrichment.

The server session owns the successful lookup snapshot; the browser does not resubmit arbitrary facility payloads for persistence.

## Synchronization and completeness safety

Explicit sync creates a batch and dispatches `PersistOfficialSourceSnapshotJob`. Batch states are `QUEUED`, `RUNNING`, `COMPLETED`, and `FAILED`; the BHXH UI polls local batch state and shows completion/failure without making additional BHXH lookup requests.

Current BHXH `-- Toàn vùng --` lookup is persisted with `sync_scope=source_partition`, not as a complete province snapshot. A source-partition or district snapshot may create/update/reactivate seen facilities but **must not stale unseen facilities**.

Only a future snapshot explicitly proven `province_complete` may stale previously active records that are absent from that complete snapshot. Records are never hard-deleted by synchronization.

This protects the mirror from false stale transitions when BHXH responses are incomplete, partitioned, paginated, or administratively transitional.

Bulk whole-province/district orchestration is currently **deferred** because CAPTCHA behavior prevents safe unattended multi-request synchronization.

## Source mirror workspace

Workspace: `/admin/pharma/official-facilities/source`.

The workspace uses `<x-search>` and live filters. Search covers `Mã CSKCB`, facility name, province name/code, district name/code. Dropdown changes apply immediately without a separate Filter button.

Filters include source, canonical `Tỉnh/Thành`, `Vùng nguồn BHXH`, Active/Stale, and bounded pagination `10/25/50/100`. Province filtering groups multiple source partitions under one ERP-facing province; partition filtering can inspect each source code independently. Query state is retained across pagination.

Manual verification of live filters and An Giang source-partition behavior is **UI PASS**.

## Matching / Partner protection

Matching priority remains deterministic: source+external_id, tax code, normalized name+canonical province, normalized name+address. Classifications remain `NEW`, `EXACT`, `LIKELY_MATCH`, `CONFLICT`, `INVALID`; no fuzzy/AI auto-merge.

Existing Partner fields remain protected: no automatic rename or overwrite of phone/email/contact person; address/tax/canonical province are safe-fill only with conflicts blocked/reviewed. Same source identity must not duplicate Partner.

## Authorization

Capabilities:

- `view_pharma_official_facilities`;
- `sync_pharma_official_facilities`;
- `import_pharma_official_facilities`;
- `resolve_pharma_official_facility_conflicts`.

`sync_*` means external source -> Pharma local mirror. `import_*` means staging -> Partner canonical master.

## Verified acceptance state

Confirmed locally during this objective:

- Official Facility Import focused gate previously: **13 tests / 33 assertions PASS**.
- `BhxhFacilityLookupClientTest`: **4 tests / 19 assertions PASS**.
- BHXH lookup UI: **PASS**.
- source mirror sync-status UI: **PASS**.
- `<x-search>` + live filter UI: **PASS**.
- An Giang canonical province + `89TTT/91TTT` source-partition UI: **PASS**.

The final combined focused CLI gate for the latest partition/completeness changes is still required before PR readiness. Do not claim final PR readiness until that gate passes locally.

## Final local gate before PR

Run:

```bash
php artisan test \
  Modules/Pharma/Tests/Unit/BhxhProvinceCatalogTest.php \
  Modules/Pharma/Tests/Unit/BhxhFacilityLookupContractTest.php \
  Modules/Pharma/Tests/Unit/BhxhFacilityLookupClientTest.php \
  Modules/Pharma/Tests/Unit/OfficialSourceSyncContractTest.php \
  Modules/Pharma/Tests/Unit/OfficialSourceMirrorServiceTest.php
```

Then run the focused Pharma regression required by the collaboration workflow plus Pint on changed PHP files. Manual UI does not need to be repeated unless those gates require a UI-affecting code change.

## Deferred scope

- automatic/unattended CAPTCHA solving or bypass;
- bulk whole-province/district synchronization requiring multiple CAPTCHA-protected requests;
- scheduled BHXH lookup requiring CAPTCHA;
- automatic source mirror -> Partner writes;
- fuzzy/AI facility matching;
- source-specific IDs on `partners`;
- source-detail enrichment implementation beyond reserved `source_details`;
- PDF import;
- unrelated delivery/inventory/invoice changes.

## Prior checkpoint

Drug Award Allocation & Hospital Contract Management was merged to `main` via PR #165 before this branch started. Partner remains the canonical hospital organization master established by that objective.
