# Pharma Collaboration Handoff

## Current checkpoint

- Module: `Pharma`
- Objective: **Official Facility Import + BHXH Source Mirror**
- Implementation branch: `feat/pharma-official-facility-import`
- Merged PR: **#166** — `feat(pharma): add official facility import and BHXH source mirror`
- Merge commit: `c8abca6a39d3cfaadd8a086f43497313c85e93b7`
- Status: **MERGED TO `main` — objective complete; post-merge closeout documentation only**
- Date: 2026-09-06
- Workflow: `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- Consolidation: **one implementation branch / one implementation PR**

## Follow-up checkpoint — MaSoThue lookup CLI

- Branch: `feat/mst-lookup-command`
- Scope: reusable application-level CLI/service for manual legal/tax enrichment of healthcare facility master-data work.
- Command: `php artisan mst:lookup "Bệnh viện đa khoa Kiên Giang"`.
- JSON mode: `php artisan mst:lookup "Bệnh viện đa khoa Kiên Giang" --json`.
- Source: `https://masothue.com` search HTML + canonical detail page; no search token is required.
- Matching: normalized exact legal-name match is preferred; otherwise the first parsed search result is explicitly marked `first_result` for caller review.
- Canonical detail URL is taken from the search result href; the service does not construct `/{mst}` or invent slugs.
- MaSoThue remains a third-party enrichment/reference source, not the authoritative healthcare facility master. Partner remains canonical and this CLI does not write to Partner or Pharma staging automatically.
- Focused test gate: **3 tests / 12 assertions PASS**.
- Pint focused gate: **3 files PASS**.
- Live smoke after syncing current `main`: **PASS**, returning MST `1700285659`, `match_type=exact`, canonical URL, active status, representative, active date, tax authority and organization type for `BỆNH VIỆN ĐA KHOA KIÊN GIANG`.
- Working tree before final push: **clean**.
- Branch after current-main synchronization: **ahead of `main`, behind 0**; functional diff before this handoff contains only the new command, lookup service and focused test.

### MaSoThue safety boundary

The CLI is intentionally human-triggered and read-only. Before any future bulk or scheduled use, add explicit throttling/cache/retry/telemetry and re-check source terms/robots constraints. Do not treat a noisy search result as canonical without deterministic matching/review, and verify production master data against authoritative sources where required.

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

Confirmed locally before merge:

- Official Facility Import focused gate previously: **13 tests / 33 assertions PASS**.
- Latest combined BHXH/source-mirror focused gate: **19 tests / 102 assertions PASS**.
- Final Pharma Unit regression: **40 tests / 170 assertions PASS**.
- Pint focused gate: **16 files PASS**.
- Official Facility route inventory: **13 routes present**.
- Vite production build: **PASS**.
- Working tree before final regression: **clean and synchronized with origin**.
- BHXH lookup UI: **PASS**.
- source mirror sync-status UI: **PASS**.
- `<x-search>` + live filter UI: **PASS**.
- An Giang canonical province + `89TTT/91TTT` source-partition UI: **PASS**.

A stale test expectation that asserted `permission:view_pharma_official_facilities` was corrected to the actual canonical Laravel middleware contract `can:view_pharma_official_facilities`; runtime authorization was not weakened or changed.

PR #166 was merged to `main` on 2026-09-06. No further implementation gate is required for this completed objective unless a new code change affects behavior or UI.

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

Drug Award Allocation & Hospital Contract Management was merged to `main` via PR #165 before this objective. Partner remains the canonical hospital organization master established by that objective.
