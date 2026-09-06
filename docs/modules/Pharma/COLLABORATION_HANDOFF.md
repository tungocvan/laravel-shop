# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Official Facility Import + BHXH Source Mirror**
- Branch: `feat/pharma-official-facility-import`
- Base: `main` at `1f77f1575050648c143d45339d0ec8535e9dba6e`
- Status: **IMPLEMENTATION IN PROGRESS — BHXH lookup UI PASS; source mirror/queue awaiting local verification**
- Date: 2026-09-06
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one PR**

## Canonical ownership

`Partner` remains the sole canonical organization master for hospitals/healthcare facilities. Pharma must not create a second Hospital/Facility master.

Pharma now owns two non-canonical operational layers:

1. Official import staging/audit.
2. Official source mirror/cache for external source snapshots.

Neither layer may write Partner directly except through the explicit Official Facility Import matcher/importer flow.

## Official import pipeline

`Official XLSX/CSV -> Upload -> Pharma staging -> Validate/Normalize -> Match/Dedupe -> Preview -> Explicit checkbox selection -> Partner + PartnerSourceReference`.

Upload/staging never writes Partner.

Partner-owned canonical changes:

- nullable `partners.province_code` as a generic, source-independent province attribute;
- `partner_source_references` with unique `(source, external_id)` provenance identity.

Pharma-owned import tables:

- `pharma_official_import_batches`;
- `pharma_official_import_rows`.

## BHXH interactive lookup

Workspace: `/admin/pharma/official-facilities/bhxh`.

The BHXH integration is human-in-the-loop:

- ERP bootstraps the public BHXH page/session;
- ERP loads the CAPTCHA image from BHXH with the same session cookie;
- the user manually enters CAPTCHA;
- ERP posts `MaTinh`, `MaQuanHuyen`, and `tokenRecaptch` to the public BHXH facility endpoint;
- no OCR, CAPTCHA solving, bypass, or unattended lookup is implemented.

Province selection uses a deduplicated display catalog while preserving duplicate/legacy BHXH codes as aliases. District selection is loaded dynamically from the BHXH `GetHuyenByLstmatinh` endpoint.

Manual UI verification for the BHXH lookup flow is **PASS** as of 2026-09-06.

## Official source mirror/cache

Approved flow:

`BHXH live lookup -> server-side lookup snapshot -> queued persistence -> local source mirror -> later Official Facility staging -> Partner`.

The local mirror exists so historic/source data remains searchable when the external API is unavailable or changes.

Pharma-owned source mirror tables:

- `pharma_official_source_sync_batches`;
- `pharma_official_source_facilities`.

Source facility identity is unique by `(source, external_id)`. For BHXH, `external_id` is the official `Mã CSKCB` and is intentionally retained as the stable source key for future detail enrichment.

The mirror stores basic listing payload separately from future source detail enrichment:

- `raw_payload` + `payload_hash` for listing snapshots;
- `source_details` + `details_hash` + `details_synced_at` reserved for future source-specific detail lookup by `Mã CSKCB`.

Regular listing sync must not erase `source_details` enrichment.

## Queue synchronization semantics

After a successful BHXH lookup, the backend stores the returned snapshot in the server session. The browser does not submit the facility payload back for synchronization.

When the user clicks **Đồng bộ dữ liệu tỉnh này**:

- `OfficialSourceSyncController` creates a sync batch;
- `PersistOfficialSourceSnapshotJob` is dispatched;
- `OfficialSourceMirrorService` performs source upsert;
- source mirror persistence never writes Partner.

Batch statuses include `QUEUED`, `RUNNING`, `COMPLETED`, and `FAILED`.

Full-province sync semantics:

- seen facilities are created/updated/reactivated;
- previously active facilities in that province that are missing from the new complete province snapshot become `STALE` (`is_active = false`);
- records are never hard-deleted.

District sync semantics:

- only seen facilities are upserted;
- it must never mark other facilities in the province stale.

## Source mirror workspace

Workspace: `/admin/pharma/official-facilities/source`.

It supports:

- search by `Mã CSKCB` or facility name;
- source filter;
- province filter;
- Active/Stale filter;
- bounded pagination `10/25/50/100`;
- recent sync batch visibility with fetched/created/updated/unchanged/stale counters.

This workspace remains usable from local database even when BHXH is unavailable.

## Matching / Partner protection

Official Facility Import matching remains deterministic:

1. `source + external_id`;
2. tax code;
3. normalized name + canonical province;
4. normalized name + normalized address.

Classifications remain `NEW`, `EXACT`, `LIKELY_MATCH`, `CONFLICT`, `INVALID`.

No fuzzy/AI matching. `LIKELY_MATCH` and `CONFLICT` require explicit resolution.

Existing Partner protection remains:

- no automatic rename;
- no overwrite of phone/email/contact_person;
- address/tax/province are safe-fill only with conflicts blocked/reviewed;
- same source identity must not duplicate Partner.

## Authorization

Current capabilities:

- `view_pharma_official_facilities`;
- `sync_pharma_official_facilities`;
- `import_pharma_official_facilities`;
- `resolve_pharma_official_facility_conflicts`.

`sync_*` means external source -> Pharma local mirror.

`import_*` means staging -> Partner canonical master.

These responsibilities intentionally remain separate.

## Current test state

Previously confirmed locally:

- Official Facility Import focused gate: **13 tests / 33 assertions PASS**.
- BHXH live lookup UI: **PASS**.

New source mirror/queue tests are committed but not yet locally verified:

- `OfficialSourceMirrorServiceTest`;
- `OfficialSourceSyncContractTest`;
- impacted BHXH lookup/catalog tests.

Do not claim final PR readiness until these new migrations/tests plus focused Pharma regression and queue/UI verification pass locally.

## Next local gate

1. Pull branch.
2. Run new Pharma migrations.
3. Run focused source mirror/BHXH tests.
4. Ensure a queue worker is running when queue connection is asynchronous.
5. Tra cứu one province in BHXH UI, click sync, then inspect `/admin/pharma/official-facilities/source`.
6. Confirm second sync is idempotent/unchanged and full-province missing records become stale only when appropriate.

## Deferred scope

- automatic/unattended CAPTCHA solving or bypass;
- scheduled BHXH lookup that requires CAPTCHA;
- automatic source mirror -> Partner writes;
- fuzzy/AI facility matching;
- source-specific IDs on `partners`;
- detail enrichment API implementation beyond the reserved `source_details` boundary;
- PDF import;
- unrelated delivery/inventory/invoice changes.

## Prior checkpoint

Drug Award Allocation & Hospital Contract Management was merged to `main` via PR #165 before this branch started. Partner remains the canonical hospital organization master established by that objective.
