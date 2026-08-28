# Muasamcong Baseline Analysis

> Baseline date: 2026-08-28  
> Source checkpoint reviewed: `main@3c755169ecb99610a0a00c6a023d57b80cfe6f2b`  
> Scope: `Modules/Muasamcong`, its tests, module documentation, and directly referenced ClientPortal adapters.  
> Verification status: source/config/schema/tests were statically reviewed. No fresh runtime, database, upstream API, or automated test execution was performed for this documentation-only batch.

## Executive Summary

`Modules/Muasamcong` is an established domain module that integrates with `muasamcong.mpi.gov.vn`, persists search and procurement data, supports administrative workflows, and supplies domain models/services to the ClientPortal presentation adapter.

The main architecture is viable and should be preserved. HTTP access is centralized in services, destination validation and production TLS controls are present, sensitive session data is encrypted or kept out of Livewire public state, large result workflows are generally bounded, and critical pricing sync paths already use dedicated permissions and duplicate-safe inserts.

No P0 issue was found in the reviewed source. The baseline does identify multiple P1 risks:

- several state-changing Admin/Livewire actions require only `view_muasamcong` and do not enforce a capability at the mutation boundary;
- the one-time personal-session import token is validated before cookie mutation but consumed afterward, allowing concurrent replay to reach the mutation;
- contractor queue creation and some sync flows are not fully idempotent under concurrent requests;
- ClientPortal database search silently limits matching rows to 500 while reporting the result as complete;
- snapshots/raw payloads/files have no documented retention policy;
- large controllers and Livewire components combine presentation, persistence, export, and orchestration responsibilities.

The requested Admin Dashboard does not currently exist. `/admin/muasamcong` is the Smart Pricing workspace, while ClientPortal already owns a separate end-user dashboard at `/apps/muasamcong`. The Admin Dashboard should be implemented as a separate capability after this baseline, with a compatibility decision for the existing index route.

```text
Final recommendation: Major Refactor
Delivery approach: incremental hardening and extraction; no full rebuild
```

## Module Purpose and Overview

The module currently provides:

- Smart Pricing search and normalized upstream results;
- database-first search snapshots and explicit upstream refresh;
- TBMT multi-page loading with local filtering/pagination;
- selected pricing result synchronization;
- per-user wishlist;
- contractor participation history, archived searches, and queued refresh;
- KQLCNT/contract/winner retrieval and persistence;
- HSMT catalogue parsing, server snapshots, manual lot confirmation, and downloads;
- configurable synced-pricing Excel/BBG export;
- privileged integration configuration and personal-session import;
- authenticated internal API endpoints;
- domain data/services consumed by the ClientPortal Muasamcong application.

The unresolved business constraint remains unchanged: the reviewed upstream responses do not establish a reliable key from a winning contractor to an exact HSMT lot/medicine. Heuristic mapping must not be introduced.

## Bootstrap / Standards Context

- Repository stack: Laravel 12, PHP 8.2+, Livewire 3.1 class-based, MySQL, modular monolith.
- Canonical loader: `Modules\ModuleServiceProvider`.
- Module type: `domain`; manifest declares no module dependencies.
- Admin layout: `Admin::layouts.master`.
- Expected flow: `Route -> Controller -> Page Blade -> Livewire -> Service -> Model -> Database`.
- Sensitive mutations must enforce authorization at the action boundary.
- Controllers should remain thin; services own queries, transactions, exports, and concurrency control.
- Production datasets and exports must be bounded.
- Source/config/schema are authoritative when older documentation differs.

`config/module.php` uses the supported legacy key `enabled` rather than the newer `default_enabled` convention. Runtime enablement is resolved outside the tracked source; the effective production state was not verified and was not changed.

## Dependency Graph

```text
Admin routes
  -> Muasamcong controllers / page Blades
  -> Livewire components
  -> domain and integration services
  -> Eloquent models / private local storage / queue
  -> MySQL or HTTPS upstream at muasamcong.mpi.gov.vn

ClientPortal Muasamcong adapter
  -> ClientPortal controllers/jobs/views
  -> Muasamcong models and services
  -> Muasamcong-owned persistence/integration
```

The cross-module direction is one-way: ClientPortal imports Muasamcong domain types; Muasamcong does not import ClientPortal. No circular dependency was observed.

## Route / Controller / Blade / Livewire Analysis

### Routes

The domain route set contains 21 routes: 18 Admin web routes and 3 API routes.

- Admin read/workspace routes use `web`, `auth:admin`, and `permission:view_muasamcong,admin`.
- Config and Windows session-tool routes use `permission:muasamcong.config.manage,admin`.
- Authenticated API routes use `api` and `auth:sanctum`.
- Session import is unauthenticated by design but requires a one-time token header and is throttled to 6 requests/minute.

The route layer is clearly namespaced and has no duplicate legacy root routes. However, destructive history/wishlist routes and several mutation-capable page routes remain inside the view-only middleware group.

### Admin Dashboard capability assessment

Evidence:

- `GET /admin/muasamcong` (`muasamcong.index`) currently renders Smart Pricing.
- No Admin route/page aggregates module navigation, health, counts, queue state, or configuration status.
- ClientPortal already has an end-user dashboard at `GET /apps/muasamcong` (`client.muasamcong.dashboard`); it is not an Admin management dashboard.

Recommended separate implementation:

1. Build a permission-aware Admin Dashboard with links to Smart Pricing, contractor/history/manual-lot workflows, HSMT, synced data/export, wishlist, and configuration/session tools.
2. Keep the dashboard controller/page shell thin. Obtain bounded summary DTOs from a service; do not query models from Blade.
3. Show only authorized actions. Summary widgets may include record counts, recent jobs, stale/failed queue status, snapshot freshness, and configuration/session health without exposing secrets.
4. Follow `ADMIN_UI_STANDARD.md`: clear module header, responsive card/workspace layout, loading/empty/error states, keyboard-accessible links, and compact mobile navigation.
5. Default to the lowest-risk route contract: add `/admin/muasamcong/dashboard` and keep the current index unchanged. If the user instead approves `/admin/muasamcong` as the canonical Dashboard, move Smart Pricing to `/admin/muasamcong/pricing` and document the unavoidable behavior change for existing index bookmarks/links, plus the route-name/redirect strategy.

This baseline does not choose or implement the route migration. That requires user acceptance of the compatibility behavior and its own tests/docs batch.

### Controllers

`MuasamcongController` is not consistently thin. Besides returning page shells, it queries domain models, builds FastExcel datasets, maps manual-lot exports, and packages the Windows session tool. `SyncedPricingExportController` and `SyncedPricingBbgExportController` directly own query, workbook construction, formatting, file lifecycle, and response delivery.

The API controller is small and validates the search keyword. Its Sanctum middleware does not include a Muasamcong capability; the intended API caller/permission model must be confirmed before changing this contract.

### Page Blades and Livewire Blades

Page Blades generally follow the shell pattern and use the canonical Admin layout. Feature views include responsive overflow, modal scrolling, validation feedback, loading states, bounded pagination, and selection feedback in the principal workspaces.

The UI does not use one unified Admin landing page. Several workflows are discoverable only through direct page links or existing module navigation.

### Livewire components

Positive evidence:

- `TracuuThuoctrungthau` enforces dedicated sync and wishlist permissions at mutation boundaries.
- `SyncedPricingList` enforces `muasamcong.pricing.sync` for edits, deletes, and export-profile mutations.
- `ConfigManager` enforces `muasamcong.config.manage` for every sensitive action and does not hydrate raw secrets into public state.
- potentially large lists use bounded pagination or local page slices.

Material gaps:

- `ContractorSearchList::refreshSearch` and `deleteSearch` mutate queue/archive state without action-level authorization;
- `QueuedContractorHistory::dispatchFreshSearch` creates jobs without action-level authorization;
- `ContractorHistory::syncSelected`, `syncKqlcnt`, and `syncHsmt` persist upstream data without a dedicated capability check;
- `ManualContractorLots::saveSelections` replaces confirmations without a dedicated capability check;
- `SmartPricingVerifiedLots::sync` persists verified lots without a dedicated capability check.

`ContractorHistory`, `SyncedPricingList`, and `TracuuThuoctrungthau` also carry substantial UI state plus orchestration/persistence behavior. They should be reduced incrementally by extracting commands/query DTOs and domain workflows into services.

## Service Analysis

Strengths:

- `MuaSamCongService`, `ContractorHistoryService`, `KqlcntService`, and `HsmtDetailService` centralize upstream calls and response normalization.
- exact HTTPS host validation rejects credentials, non-443 ports, and redirects; production TLS verification cannot be disabled.
- `PricingTbmtPaginationService` bounds upstream expansion to 100 pages and marks capped/partial responses.
- `PricingResultSyncService` maps explicit source fields, uses a transaction plus `insertOrIgnore`, and relies on a unique `source_id`.
- `ContractorSearchArchiveService` uses a transaction and inserts archived items in chunks of 250.
- `SyncedPricingExportPreferenceService` scopes profiles/assets by Admin user and applies column/type/format whitelists.

Risks:

- synchronous TBMT expansion can issue up to 100 upstream requests in one UI flow;
- company pricing search can issue multiple strategies across up to 25 pages each;
- search snapshots and HSMT snapshots persist full payloads with no cleanup/retention workflow;
- `ContractorSearchArchiveService::findByName` returns all matching searches, although current callers may constrain usage;
- ClientPortal `ClientPricingSearchService` applies its database filters but returns at most 500 matching `PricingResult` rows, then reports `partial=false`/`capped=false`, which can hide additional matches.

## Import / Export Analysis

### Imports

No spreadsheet/database bulk import exists in this module.

The personal-session workflow is an operational cookie import:

- a random 64-character token is stored only as SHA-256 with a 5-minute expiry;
- the browser URL uses a fragment so the plaintext token is not sent as a normal query parameter;
- the API validates the cookie format, stores it encrypted, tests the upstream session, and redacts errors;
- `POST /api/muasamcong/update-cookie` is throttled.

The token is consumed only after the cookie has been stored/tested. Concurrent requests can both pass the initial unlocked validation and reach the mutation; consumption is locked too late. The token must be atomically claimed before secret mutation, with an explicit failure/retry policy.

### Exports

- HSMT search export uses `HsmtExport`/FastExcel.
- selected pricing and wishlist exports validate IDs and query server-side data.
- synced Excel and BBG exports accept at most 5,000 IDs and generate private temporary files with `no-store`, `nosniff`, and delete-after-send.
- export profiles are user-scoped and whitelist columns, types, decimals, alignments, widths, header/footer, logo/signature, and page setup.
- manual contractor lot downloads are generated from persisted selections.

Exports are bounded, but workbook generation and full `get()` occur in-process. A 5,000-row richly formatted PhpSpreadsheet export can be memory-intensive and should be measured; queue/streaming thresholds may be appropriate. Export construction currently bypasses a dedicated service and the repository's shared import/export foundation, so any refactor should first verify compatibility with the existing profile format and output contract.

## Shared Dependencies

- Laravel HTTP client, Eloquent, validation, authorization, queue, storage, and response APIs.
- Livewire 3.1.
- `rap2hpoutre/fast-excel` for simple exports.
- `phpoffice/phpspreadsheet` for configurable formatted workbooks.
- repository Admin authentication/permission infrastructure.
- ClientPortal application registry, feature permissions, jobs, file availability, and PWA external-file handoff as direct presentation consumers.

No event/listener integration was observed. One domain queue job exists: `FetchContractorHistoryJob`.

## Model / Migration / Database Analysis

The module defines 14 models, including one unused scaffold model, and 19 migration files.

Positive evidence:

- unique keys protect pricing source IDs, per-user wishlist entries, normalized search snapshots, contractor search codes/items, contractor bid identity, KQLCNT identity, manual lot identity, token hashes, personal-session keys, and user/profile names;
- contractor search items and job-to-search references use foreign keys with appropriate cascade/null behavior;
- money/quantity-like fields use decimal columns/casts rather than float;
- lookup and sort paths generally have indexes;
- JSON/raw payload fields use array casts.

Risks and maintenance notes:

- every active model uses `protected $guarded = []`; current callers mostly pass explicit/validated arrays, but the model boundary permits accidental mass assignment;
- user/audit identifiers are unsigned IDs without database foreign keys, likely preserving cross-module decoupling but leaving referential cleanup to application logic;
- snapshot/raw payload columns and private HSMT files have no retention policy or storage observability;
- active contractor job uniqueness is checked in application code only; the schema has an index but no idempotency key/active-job constraint;
- the historical `muasamcong_price_list_profiles` create-then-drop migration is obsolete scaffolding; preserve applied history, but document why its rollback intentionally does not recreate a duplicate table;
- `Models/Muasamcong.php` and `Livewire/Hsmt.php` are unused scaffolds and should be removed only in a dedicated compatibility-checked cleanup.

## Security

Controls confirmed in source:

- exact allowlisted HTTPS host and port validation;
- redirects disabled for upstream requests;
- production TLS verification forced on;
- cookie validation rejects control characters;
- personal cookie encrypted at rest;
- raw secret values are not placed in Livewire public state;
- config changes are allowlisted, permission-protected, and disabled in Docker environments;
- export files use private/temp storage behavior and no-store download headers;
- ClientPortal external-file handoff uses authenticated same-origin retrieval and an explicit user gesture.

No direct SQL string interpolation, path traversal in HSMT snapshot keys, public secret logging, or P0 secret exposure was observed.

P1 authorization risk remains because view-only Admins can reach several server-side state changes. Hiding buttons is not an authorization boundary. The API search capability and data scope are also not expressed beyond `auth:sanctum`; intended consumers must be verified.

## Performance

Positive evidence:

- Admin list views are paginated or locally sliced to bounded page sizes;
- snapshot reuse reduces repeated upstream calls;
- pricing, wishlist, synced, and BBG export selection sizes are bounded;
- contractor archive inserts are chunked;
- the queue is used for fresh contractor-history searches;
- current-page contractor list related data is fetched in batches, avoiding obvious row-by-row N+1 queries.

Material risks:

- synchronous multi-page upstream searches can occupy a PHP worker for a long time;
- large HSMT/search arrays are stored in Livewire public state and filtered/sliced in memory;
- PhpSpreadsheet builds formatted workbooks in memory for up to 5,000 rows;
- snapshot/raw payload/file growth is unbounded over time;
- ClientPortal local search silently truncates matching rows to 500;
- Dashboard summaries, when implemented, must use bounded aggregate queries and must not load full result payloads.

## Validation and Authorization

Validation is generally explicit for route inputs, search terms, UUID/source IDs, selected export IDs, configuration values, uploaded export assets, and manual-lot fields.

Authorization is inconsistent:

- good: config, pricing sync, wishlist actions in `TracuuThuoctrungthau`, and synced-list mutations use dedicated capabilities;
- gap: wishlist bulk deletion, search-history deletion, contractor refresh/delete/sync, HSMT/KQLCNT sync, manual-lot replacement, and verified-lot sync rely on view middleware or no action check;
- unknown: whether all Sanctum users are intended to call the search API.

The existing route authorization test explicitly expects view permission on destructive routes, so it verifies the current weakness instead of denying unauthorized mutation. The next hardening task must update both middleware/action guards and negative-path tests.

## Transactions, Concurrency and Data Integrity

Strong boundaries:

- pricing sync uses a transaction, `insertOrIgnore`, and a unique source key;
- contractor archive replacement is atomic and chunked;
- manual-lot replacement runs in a transaction;
- token consumption itself uses `lockForUpdate`.

Gaps:

- token validation and consumption do not form one atomic claim before cookie mutation;
- contractor job dispatch performs check-then-create without a unique idempotency key, so concurrent requests can enqueue duplicates;
- `ContractorHistory::syncSelected` checks existence and then creates rows individually; the unique constraint prevents duplicate storage but a race can throw and leave a partial operation;
- manual-lot replacement is last-write-wins with no version/optimistic conflict check.

Upstream source data and manual administrative enrichment are stored separately. Existing code correctly avoids inventing a contractor-to-medicine mapping without a verified join key.

## Admin UI / UX Standard Review

Current pages generally use the canonical layout, visible form controls, responsive overflow, bounded pagination, selection feedback, loading states, and scrollable modal content. Feature workspaces are split into dedicated pages instead of one unbounded screen.

Gaps/improvements:

- no Admin overview/dashboard or permission-aware module navigation hub;
- mutation affordances are not consistently aligned with server-side capabilities;
- large Livewire modal/workspace payloads may degrade responsiveness;
- Dashboard implementation needs distinct empty, loading, stale, failed-job, and configuration-warning states without exposing secrets;
- destructive operations need explicit confirmation and scope messaging where not already present.

## Cross-Module Dependencies

ClientPortal is the presentation owner for customer-facing Muasamcong routes and views. Its adapter directly consumes Muasamcong models/services, includes an end-user dashboard, background sync, history, wishlist, price-list generation, public sharing, and PWA file handoff.

This is an acceptable one-way dependency because the domain owner remains Muasamcong. Future domain changes must preserve ClientPortal contracts or update both modules in one explicitly scoped change. Admin Dashboard work belongs to Muasamcong and must not move domain logic into ClientPortal.

## Technical Debt

- oversized `MuasamcongController`, export controllers, and Livewire components;
- inconsistent action-level authorization;
- no explicit job idempotency/locking policy;
- no snapshot/file retention and capacity monitoring;
- broad mass-assignment model policy;
- unused scaffold model/component/view;
- legacy manifest enablement key;
- historical duplicate price-list migration;
- brittle route test based on total route count;
- no canonical `COLLABORATION_HANDOFF.md`; `AI_HANDOFF.md` is historical context, not the current workflow handoff.

## Test Coverage

Reviewed test inventory:

- 14 Muasamcong test files (`tests/Feature/MuasamcongModuleTest.php` plus 13 files under `tests/Feature/Muasamcong`);
- 11 directly related ClientPortal/ClientApps feature tests.

Covered areas include route registration/middleware, host/TLS controls, response schema normalization, pricing sync and wishlist scoping, snapshots, contractor archive/history, KQLCNT/HSMT parsing, award verification, export preference settings, environment doctor, manual quote fields, session import command registration, ClientPortal search/queue/history/wishlist/price-list/sharing, and PWA file handoff.

Important missing cases:

- deny mutation for an Admin who has only `view_muasamcong`;
- dedicated permission coverage for wishlist bulk, contractor refresh/delete/sync, KQLCNT/HSMT sync, manual lots, and verified lots;
- concurrent one-time token replay;
- concurrent contractor job dispatch and selected contractor sync;
- ClientPortal search behavior beyond 500 candidates;
- snapshot/file retention behavior;
- Admin Dashboard route, permission filtering, bounded summaries, and responsive states once implemented.

The route suite's global assertion of exactly 21 Muasamcong routes is brittle. Dashboard implementation will require updating it; semantic route/middleware assertions should remain the primary contract.

No tests were executed during this docs-only baseline. Existing test files are evidence of coverage intent, not a fresh PASS result.

## Documentation Drift

The previous `ANALYSIS.md` and `INFORMATION.md` were dated 2026-08-16 and `README.md` was dated 2026-08-18, while source evolved through 2026-08-20 and ClientPortal presentation ownership was consolidated on 2026-08-28.

Drift corrected by this baseline:

- complete Admin/API route inventory, queued contractor history, manual lots, session import, and export page setup;
- current models/tables/services/jobs;
- explicit ClientPortal ownership and end-user dashboard;
- actual capability authorization gaps instead of the prior claim that mutation permissions were uniformly specialized;
- current module-scoped verification guidance instead of requiring unrelated full-project regression for a documentation-only change;
- canonical recommendation vocabulary (`Major Refactor`);
- separation of historical `AI_HANDOFF.md` from a future canonical `COLLABORATION_HANDOFF.md`.

`ROUTES.md`, `SYNCED.md`, `ENV_DOCTOR.md`, and `AI_HANDOFF.md` were reviewed as historical/supporting context but are outside this task's authorized output set and may still contain stale details.

## Issue List

### P0

No P0 issue was observed in the reviewed source. Runtime secrets, database contents, production module state, and upstream behavior were not inspected.

### P1-01 — Mutation authorization is inconsistent

**Priority:** P1  
**File:** `Modules/Muasamcong/routes/web.php`; `Http/Controllers/PricingWishlistBulkController.php`; `Http/Controllers/PricingSearchHistoryController.php`; `Livewire/ContractorSearchList.php`; `Livewire/QueuedContractorHistory.php`; `Livewire/ContractorHistory.php`; `Livewire/ManualContractorLots.php`; `Livewire/SmartPricingVerifiedLots.php`  
**Evidence:** state-changing routes/actions rely on `view_muasamcong` and omit action-level `Gate` checks, while dedicated sync/wishlist permissions already exist.  
**Problem:** a view-only Admin can invoke writes/deletes/queue work server-side.  
**Impact:** privilege escalation within the authenticated Admin boundary; unintended data mutation and upstream load.  
**Recommendation:** define the required capability for every mutation, enforce it in route middleware and/or the action itself, and add negative authorization tests. Split additional permissions only where `pricing.sync`/`pricing.wishlist` do not express contractor or archive ownership.

### P1-02 — One-time session token can be replayed concurrently before consumption

**Priority:** P1  
**File:** `Http/Controllers/Api/PersonalSessionImportController.php`; `Services/SessionImportTokenService.php`; `Services/PersonalSessionService.php`  
**Evidence:** controller validates the token, saves/tests the cookie, and calls locked `consume()` last.  
**Problem:** two concurrent requests can both validate and reach secret mutation before only one consumes the token.  
**Impact:** one-time semantics and session integrity are not guaranteed.  
**Recommendation:** atomically claim/consume the token before mutation, with an explicit claimed/failed state or a safe retry/rollback design; add a concurrency/replay test.

### P1-03 — Contractor jobs and selected sync are not fully idempotent

**Priority:** P1  
**File:** `Livewire/QueuedContractorHistory.php`; `Livewire/ContractorSearchList.php`; `Livewire/ContractorHistory.php`; migration `2026_08_17_120000_create_muasamcong_contractor_search_jobs_table.php`  
**Evidence:** job dispatch uses check-then-create without a unique active-job key; selected bid sync checks existence then creates rows individually.  
**Problem:** concurrent requests can enqueue duplicate work or hit uniqueness exceptions mid-sync.  
**Impact:** duplicate upstream load, inconsistent UX, partial writes, and noisy failures.  
**Recommendation:** introduce a transactional/idempotent dispatch command and duplicate-safe bulk persistence (`upsert`/`insertOrIgnore` as appropriate), with concurrency tests.

### P1-04 — ClientPortal local search can silently omit matching rows

**Priority:** P1  
**File:** `Modules/ClientPortal/Applications/Muasamcong/Services/ClientPricingSearchService.php`  
**Evidence:** the filtered query returns at most 500 `PricingResult` rows, then reports `partial=false` and `capped=false` without checking whether more matches exist.  
**Problem:** matching results beyond the first 500 are invisible without a completeness warning.  
**Impact:** incorrect search results and misleading UI.  
**Recommendation:** move supported filters/pagination into the database, or accurately mark capped/partial results and provide a deterministic continuation strategy.

### P1-05 — Snapshot and raw-payload retention is undefined

**Priority:** P1  
**File:** `Services/PricingSearchSnapshotService.php`; `Services/HsmtSnapshotService.php`; related snapshot/raw-payload migrations  
**Evidence:** full upstream payloads and HSMT JSON/XLSX files are persisted; no cleanup command, TTL, quota, or retention documentation was found.  
**Problem:** database and private storage growth are unbounded over module lifetime.  
**Impact:** capacity/performance degradation and operational uncertainty.  
**Recommendation:** define retention by data class, protect actively referenced snapshots, add scheduled cleanup/dry-run reporting, and expose bounded storage health on the future Dashboard.

### P1-06 — Core orchestration classes exceed the preferred responsibility boundary

**Priority:** P1  
**File:** `Http/Controllers/MuasamcongController.php`; `Http/Controllers/SyncedPricingExportController.php`; `Livewire/ContractorHistory.php`; `Livewire/SyncedPricingList.php`; `Livewire/TracuuThuoctrungthau.php`  
**Evidence:** these classes combine presentation state, queries, workbook construction, file packaging, persistence, and upstream orchestration.  
**Problem:** behavior is hard to authorize, test, profile, and change independently.  
**Impact:** regression risk and slower maintenance across multiple workflows.  
**Recommendation:** extract bounded command/query/export services incrementally while preserving routes, Livewire aliases, schemas, profile formats, and output files.

### P1-07 — Long synchronous upstream/export workflows need operational thresholds

**Priority:** P1  
**File:** `Services/PricingTbmtPaginationService.php`; `Services/MuaSamCongService.php`; synced export controllers; large Livewire components  
**Evidence:** a request may fetch up to 100 TBMT pages; company search uses multiple strategies; formatted exports build up to 5,000 rows in memory.  
**Problem:** current hard caps prevent infinity but not worker starvation or memory pressure.  
**Impact:** slow requests, timeouts, and degraded Admin responsiveness under load.  
**Recommendation:** measure actual latency/memory, define synchronous thresholds, queue larger jobs, report progress/partial state, and keep downloads private.

### P2-01 — Admin Dashboard is a missing management capability

**Priority:** P2  
**File:** `Modules/Muasamcong/routes/web.php`; Admin page views  
**Evidence:** the Admin index is Smart Pricing and no management overview exists; ClientPortal's dashboard serves a different audience.  
**Problem:** module functions and operational status lack one discoverable Admin entry point.  
**Impact:** fragmented navigation and slower operations.  
**Recommendation:** implement the separate Dashboard capability described above after choosing route compatibility.

### P2-02 — Model mass-assignment policy is broad

**Priority:** P2  
**File:** all active files under `Modules/Muasamcong/Models`  
**Evidence:** every model uses `protected $guarded = []`.  
**Problem:** future callers can accidentally assign audit, ownership, or raw-payload fields.  
**Impact:** latent integrity/security risk as workflows evolve.  
**Recommendation:** adopt explicit `$fillable` or service-owned `forceFill` patterns in a compatibility-checked cleanup, with tests for protected fields.

### P2-03 — Scaffolds and legacy metadata remain

**Priority:** P2  
**File:** `Models/Muasamcong.php`; `Livewire/Hsmt.php`; `resources/views/livewire/hsmt.blade.php`; `config/module.php`; price-list profile migrations  
**Evidence:** empty scaffolds are unused, manifest uses legacy `enabled`, and a historical table is created then dropped.  
**Problem:** repository intent is harder to read.  
**Impact:** developer confusion and accidental reuse of obsolete artifacts.  
**Recommendation:** clean up only after reference search and migration-history review; do not rewrite applied migrations.

### P2-04 — Observability and tests do not express critical contracts fully

**Priority:** P2  
**File:** Muasamcong/ClientApps tests and operational docs  
**Evidence:** no fresh queue/storage dashboard, mutation denial, concurrency, or retention coverage; route count is asserted globally.  
**Problem:** failures may be detected late and route additions create brittle test maintenance.  
**Impact:** weaker confidence in hardening and future Dashboard changes.  
**Recommendation:** add semantic authorization/concurrency tests and bounded operational metrics as each capability is implemented.

## Module Health Summary

| Area | Assessment | Notes |
|---|---|---|
| Domain architecture | Healthy foundation | Clear domain ownership; no circular ClientPortal dependency |
| Security controls | Mixed | Strong upstream/secret controls; P1 mutation and token-race gaps |
| Correctness/data integrity | Mixed | Good unique keys/transactions; concurrency and 500-row completeness risks |
| Performance | Needs hardening | Bounded but potentially expensive synchronous/search/export paths |
| Database design | Generally sound | Useful keys/indexes; retention and job idempotency missing |
| Admin UI | Functional, fragmented | Good feature workspaces; no Admin Dashboard |
| Maintainability | Needs major incremental refactor | Several oversized controllers/components |
| Tests | Broad baseline, critical gaps | Good feature inventory; no fresh run and missing denial/concurrency cases |
| Documentation | Baseline refreshed | Supporting historical docs may still drift |

## Final Recommendation

Choose **Major Refactor**, delivered as small compatible batches rather than a rebuild:

1. authorization and negative-path tests;
2. atomic session-token claim and concurrency tests;
3. contractor job/sync idempotency;
4. ClientPortal search completeness;
5. retention/observability policy;
6. Admin Dashboard capability with an approved route-compatibility contract;
7. incremental controller/Livewire/export extraction.

Preserve existing domain tables, route names, ClientPortal contracts, export profile formats, private storage behavior, and the no-heuristic winner-to-lot invariant unless an explicitly approved migration/compatibility plan says otherwise.

## Open Questions / Unknowns

- Effective production module enablement, configuration, queue worker state, secrets, database volume, and storage usage: **NOT VERIFIED**. Verify in the authorized runtime environment without exposing secret values.
- Intended callers and capability policy for `GET/POST /api/muasamcong`: **NOT DETERMINED**. Confirm before tightening middleware.
- Admin Dashboard route choice and backward-compatibility behavior: **NOT DETERMINED**. User acceptance is required before implementation.
- Retention periods for search snapshots, raw payloads, exports, jobs, and HSMT files: **NOT DETERMINED**. Product/operations input is required.
- Reliable upstream join key from winning contractor to exact lot/medicine: **NOT FOUND** in reviewed evidence. Continue official Network/API investigation; do not infer heuristically.
- Fresh automated/manual test results at this checkpoint: **NOT RUN** in this documentation-only batch.
- Canonical `docs/modules/Muasamcong/COLLABORATION_HANDOFF.md`: **NOT PRESENT**. Create/update only in a separately approved handoff batch after baseline acceptance.
