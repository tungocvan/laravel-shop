# Pharma Module Contract

Last reviewed: 2026-09-06

## Purpose

`Pharma` owns pharmaceutical product profiles, multi-source drug intelligence, supplier tracking, PriceList generation, Official Facility operational ingestion/mirroring, and Pharma Admin workspaces under `/admin/pharma/**`.

## Canonical ownership

### Medicine Master

Pharma owns `pharma_medicines` and `pharma_medicine_sources`.

### Drug Award business canonical

Pharma owns `pharma_drug_bid_awards` and `pharma_drug_bid_award_sources`.

### Procurement acquisition canonical

`Muasamcong` remains owner of external procurement acquisition/recovery and its canonical KQLCNT warehouse. Pharma consumes it only through explicit integration boundaries.

### Healthcare organization canonical

`Partner` remains the sole canonical organization master for hospitals/healthcare facilities. Pharma must not create a second Hospital/Facility business master.

Partner owns `partners` and `partner_source_references`. `partners.province_code` is source-independent. External identifiers such as BHXH province codes never belong in that canonical field. Official identities use generic `(source, external_id)` uniqueness; do not add `bhxh_id`, `moh_id`, or similar source-specific columns to Partner.

## Official Facility operational layers

Pharma owns non-canonical staging/audit tables `pharma_official_import_batches`, `pharma_official_import_rows` and source-mirror tables `pharma_official_source_sync_batches`, `pharma_official_source_facilities`.

Import flow:

`Official XLSX/CSV -> staging -> Validate/Normalize -> Match/Dedupe -> Preview -> Explicit selected rows -> Partner + PartnerSourceReference`.

Source mirror flow:

`External official source -> human-approved lookup -> server-side snapshot -> queue -> Pharma local mirror -> later Official Facility staging -> Partner`.

Neither upload nor source synchronization may write Partner directly.

## Official source identity and enrichment

Source mirror identity is `(source, external_id)`. For BHXH, `external_id` is `Mã CSKCB`, retained as the durable key for future detail enrichment.

`raw_payload` + `payload_hash` hold basic listing observations. `source_details` + `details_hash` + `details_synced_at` are reserved for later detail enrichment. Basic listing sync must not erase existing source details.

## Canonical geography vs external source geography

ERP canonical geography and external-source geography are separate concerns.

A canonical/human-facing province may map to one or more source partitions. A source partition retains the exact external source code required to query that source. Duplicate external province labels must not be interpreted as fallback aliases merely because their display names match.

Verified BHXH example:

`Tỉnh An Giang -> 89TTT (Khu vực An Giang cũ) + 91TTT (Khu vực Kiên Giang cũ)`.

BHXH source district names may reflect legacy administrative geography. They are source metadata and must not silently replace ERP canonical province/commune geography.

For unverified duplicate-name partitions, use a neutral operator label including the source code. Do not infer historical region names without evidence.

## BHXH interactive lookup boundary

Workspace: `/admin/pharma/official-facilities/bhxh`.

Lookup is explicitly human-in-the-loop:

1. ERP establishes the public BHXH source session.
2. ERP loads the CAPTCHA with that session.
3. User manually enters CAPTCHA.
4. User selects canonical `Tỉnh/Thành`, explicit `Vùng dữ liệu BHXH`, and optional `Địa bàn BHXH`.
5. ERP submits exactly one request against that source partition using the user-entered CAPTCHA.
6. A successful result may be held server-side for a subsequent explicit sync action.

No OCR, CAPTCHA solving/bypass, hidden alias fallback, CAPTCHA reuse across multiple source requests, or unattended CAPTCHA lookup is allowed.

The current listing parser treats `Mã CSKCB` and `Tên CSKCB` as the confirmed basic facility fields. Additional source detail must be separately verified before becoming part of the contract.

## Source mirror synchronization semantics

Permission `sync_pharma_official_facilities` governs external-source -> local-mirror synchronization.

Explicit sync creates `pharma_official_source_sync_batches` and dispatches `PersistOfficialSourceSnapshotJob`. Queue states are `QUEUED`, `RUNNING`, `COMPLETED`, `FAILED`. The UI may poll local batch state; polling must not trigger additional BHXH requests.

Completeness is explicit, never inferred from a successful response:

- `source_partition` snapshot: upsert/reactivate seen rows; never stale unseen rows;
- `district` snapshot: upsert/reactivate seen rows; never stale other rows;
- `province_complete`: reserved for a future workflow that proves complete province coverage; only this scope may stale absent records.

Stale records are retained, never hard-deleted by synchronization.

Bulk whole-province/district orchestration is deferred while CAPTCHA requires safe human interaction for each protected request.

## Source mirror workspace

Workspace: `/admin/pharma/official-facilities/source`.

The workspace uses `<x-search>` and live filters. Search covers facility code/name plus source province/district names/codes. Filters include source, canonical `Tỉnh/Thành`, `Vùng nguồn BHXH`, Active/Stale and bounded page size `10/25/50/100`; there is no `All` mode. Filter changes apply immediately and query state persists across pagination.

Multiple source partitions belonging to one canonical province are grouped under the same province filter while remaining independently inspectable through the source-partition filter/column.

## Official Facility Import invariants

Phase 1 supports XLSX and CSV, not PDF. Matching is deterministic:

1. source + external_id;
2. tax code;
3. normalized name + canonical province;
4. normalized name + normalized address.

Rows classify as `NEW`, `EXACT`, `LIKELY_MATCH`, `CONFLICT`, `INVALID`. No fuzzy/AI automatic merge. Ambiguous/conflicting rows require explicit resolution.

Partner mutation rules: only explicitly selected rows import; new Partner defaults to `legal_type=hospital`, `partner_types=['customer']`, `status=active`, `source=import`; existing name/phone/email/contact person are not automatically overwritten; address/tax/canonical province are safe-fill only with conflicts blocked/reviewed; same source identity is idempotent; Partner/source-reference writes are transactional per selected row.

File controls include XLSX/CSV extension/MIME/size checks, 10,000-row parser ceiling, normalized values, SHA-256 duplicate-file warning and duplicate-row staging outcomes.

## Authorization boundary

Pharma Admin routes require `web` + `auth:admin` and appropriate capability.

Base capabilities: `view_pharma`, `create_pharma`, `edit_pharma`, `delete_pharma`.

Official Facility capabilities: `view_pharma_official_facilities`, `sync_pharma_official_facilities`, `import_pharma_official_facilities`, `resolve_pharma_official_facility_conflicts`.

`view_*` covers browsing and human interactive lookup; `sync_*` means external source -> Pharma mirror; `import_*` means staging -> Partner; `resolve_*` means explicit conflict adjudication.

## Admin workspace contract

Canonical entry: `/admin/pharma`.

Primary workspaces:

- `/admin/pharma/hssp` — Medicine Master / Product Profile / Data Quality;
- `/admin/pharma/drug-bid-awards` — procurement award intelligence;
- `/admin/pharma/official-facilities/import` — XLSX/CSV staging/preview/conflict/selected-only Partner import;
- `/admin/pharma/official-facilities/bhxh` — human-in-the-loop BHXH lookup;
- `/admin/pharma/official-facilities/source` — local source mirror/history browser.

Admin lists follow `.codex/standards/ADMIN_UI_STANDARD.md`, including bounded `10/25/50/100` pagination and explicit loading/empty/error states.

## Medicine/award boundaries

Medicine Master and Drug Award remain separate entities. A Drug Award may link to a Medicine, but procurement-origin facts must not be overwritten by HSSP enrichment. Historical award source values win; otherwise deterministic HSSP values may provide effective medicine attributes with provenance metadata. Winning price is not declared price; procurement-only facts such as price, quantity, investor, contractor, decision and contract are never HSSP-enriched.

Medicine identity resolution remains deterministic. Weak/ambiguous records must not be silently auto-merged. `VALID RECORD != COMPLETE RECORD`; incomplete/provisional source records are allowed where the source-specific contract permits them.

## Muasamcong synchronization boundary

Pharma may synchronize KQLCNT only through `Modules\Pharma\Integrations\Muasamcong`. No Pharma controller/Livewire component calls Muasamcong controllers; HSSP pages do not directly query Muasamcong tables; external acquisition failure must not break existing Pharma browsing.

## Export contract

For list workspaces with checkbox selection: non-empty selection exports exactly selected records; no selection exports the complete dataset matching active export filters, not only the visible page; selected IDs take precedence; export selection does not depend on delete permission.

## Public/API boundary

Pharma exposes no public ERP API contract. BHXH integration is server-side consumption from authenticated Admin workspaces and does not bypass source CAPTCHA/session protections.

## Deferred scope

- fuzzy/AI automatic medicine/facility merging;
- automated ambiguous facility adjudication;
- CAPTCHA OCR/solving/bypass;
- bulk or scheduled BHXH synchronization requiring unattended CAPTCHA-protected requests;
- automatic source mirror -> Partner writes;
- source-specific IDs on Partner;
- PDF facility import;
- source-detail enrichment beyond reserved `source_details`;
- Pharma runtime enablement changes;
- unrelated Supplier Tracking/PriceList redesign.

## Refactor rule

Preserve ownership/provenance boundaries. Prefer explicit domain services/adapters over shared mega-components. Never collapse Medicine Master, Drug Award, procurement acquisition, source mirror, import staging and Partner organization master into one entity merely because fields overlap.
