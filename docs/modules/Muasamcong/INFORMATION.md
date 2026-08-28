# Muasamcong Module Information

> Inventory date: 2026-08-28  
> Source reviewed: `main@3c755169ecb99610a0a00c6a023d57b80cfe6f2b`  
> Source/config/schema are authoritative. Runtime enablement, production data, and upstream availability were not verified.

## Purpose

`Modules/Muasamcong` owns the domain integration with `muasamcong.mpi.gov.vn` and the local persistence used for medicine pricing, procurement search snapshots, contractor history, KQLCNT/HSMT data, wishlists, synchronized pricing data, export profiles, and personal upstream sessions.

Customer-facing presentation is provided by the ClientPortal Muasamcong adapter. The domain module remains the owner of models, services, integration rules, and persistence.

## Features

- Smart Pricing search by medicine, active ingredient, TBMT code, and supported winning-company data.
- Exact/fallback normalization and bounded multi-page company/TBMT loading.
- Database-first pricing search snapshots with explicit refresh.
- Local filters, pagination, cross-page selection, and selected result sync.
- Per-user pricing wishlist.
- Synced pricing management and manual quote fields.
- User-scoped configurable Excel export profiles and BBG export.
- Contractor participation search, archived history, queued refresh, and persisted bid records.
- KQLCNT/contract/winner retrieval and persisted server snapshots.
- HSMT form/catalogue parsing, JSON/XLSX snapshots, and manual lot confirmation.
- Integration config/environment doctor and encrypted personal-session management.
- One-time Windows/browser session import workflow.
- Authenticated internal search API.
- ClientPortal dashboard, drug search, background sync, history, wishlist, price lists, sharing, and PWA external-file handoff through a direct adapter.

## Registration

- Canonical discovery/registration: `Modules\ModuleServiceProvider`.
- Module manifest: `Modules/Muasamcong/config/module.php`.
- Type: `domain`.
- Declared module dependencies: none.
- Module provider: `Modules\Muasamcong\Providers\MuasamcongServiceProvider`, used for configuration merge/publish.
- Manifest currently uses legacy `enabled => true`; the repository resolver supports it as a fallback.
- Effective production enablement is stored/resolved outside this tracked baseline and is **NOT VERIFIED**.

## Routes

### Admin routes

All Admin routes use the configured base middleware `web`, `auth:admin`. The first 16 routes below also use `permission:view_muasamcong,admin`; the final two use `permission:muasamcong.config.manage,admin`.

| Method | URI | Name | Handler / purpose |
|---|---|---|---|
| GET | `/admin/muasamcong` | `muasamcong.index` | `MuasamcongController@index`; Smart Pricing workspace, not a Dashboard |
| POST | `/admin/muasamcong/pricing/export-selected` | `muasamcong.pricing.export-selected` | Selected pricing snapshot export |
| DELETE | `/admin/muasamcong/pricing/history/item` | `muasamcong.pricing.history.destroy` | Delete one pricing search snapshot |
| DELETE | `/admin/muasamcong/pricing/history` | `muasamcong.pricing.history.clear` | Clear all pricing search snapshots |
| GET | `/admin/muasamcong/contractors` | `muasamcong.contractors` | Contractor lookup/queued history page |
| GET | `/admin/muasamcong/contractors/history` | `muasamcong.contractors.history` | Archived contractor searches |
| GET | `/admin/muasamcong/contractors/history/{contractorSearch}` | `muasamcong.contractors.history.show` | Archived contractor search detail |
| GET | `/admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots` | `muasamcong.contractors.manual-lots.show` | Manual contractor-lot confirmation page |
| GET | `/admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download` | `muasamcong.contractors.manual-lots.download` | Manual lot XLSX download |
| GET | `/admin/muasamcong/hsmt` | `muasamcong.hsmt` | HSMT search/export workspace |
| GET | `/admin/muasamcong/synced` | `muasamcong.synced` | Synced pricing management/export profiles |
| POST | `/admin/muasamcong/synced/export-selected` | `muasamcong.synced.export-selected` | Configurable selected Excel export |
| POST | `/admin/muasamcong/synced/export-bbg` | `muasamcong.synced.export-bbg` | Selected BBG export |
| GET | `/admin/muasamcong/wishlist` | `muasamcong.wishlist` | Admin user's pricing wishlist |
| POST | `/admin/muasamcong/wishlist/export-selected` | `muasamcong.wishlist.export-selected` | Selected wishlist export |
| DELETE | `/admin/muasamcong/wishlist/selected` | `muasamcong.wishlist.destroy-selected` | Delete selected wishlist items |
| GET | `/admin/muasamcong/config` | `muasamcong.config` | Integration configuration/environment doctor |
| GET | `/admin/muasamcong/session-tool/windows` | `muasamcong.session-tool.windows` | Download Windows session-tool package |

There is no Admin Dashboard route at this checkpoint. A separate capability is proposed in `ANALYSIS.md`. Route compatibility for the existing Smart Pricing index is not yet determined.

### API routes

| Method | URI | Name | Middleware / purpose |
|---|---|---|---|
| GET | `/api/muasamcong` | unnamed | `api`, `auth:sanctum`; availability response |
| POST | `/api/muasamcong/search-pricing` | unnamed | `api`, `auth:sanctum`; validated pricing search |
| POST | `/api/muasamcong/update-cookie` | `muasamcong.session-import` | `api`, `throttle:6,1`; one-time token cookie import |

### ClientPortal routes

ClientPortal conditionally registers customer routes when Muasamcong is enabled. The primary entry point is:

```text
GET /apps/muasamcong -> client.muasamcong.dashboard
```

Additional adapter routes cover drug pricing/detail/sync status, history, wishlist, price-list generation/status/download/PDF/share/email, share management, and public tokenized drug/price-list views. These routes are owned by `Modules/ClientPortal/Applications/Muasamcong/routes.php`, not by the domain route files.

## Permissions

Declared domain permissions:

| Permission | Intended/current use |
|---|---|
| `view_muasamcong` | Admin pages and current view-group routes |
| `muasamcong.config.manage` | Config, environment/session operations, Windows tool download |
| `muasamcong.pricing.sync` | Pricing sync and synced-list/profile mutations |
| `muasamcong.pricing.wishlist` | Wishlist actions in the Smart Pricing Livewire component |

Current enforcement is not uniform. Wishlist bulk deletion, pricing-history deletion, contractor refresh/delete/sync, KQLCNT/HSMT sync, manual-lot save, and verified-lot sync rely on the view route or omit an action-level capability check. See P1-01 in `ANALYSIS.md`.

ClientPortal defines separate `client.muasamcong.*` application/feature/action permissions through its manifest and middleware.

## Controllers

| Controller | Responsibilities |
|---|---|
| `Http/Controllers/MuasamcongController` | Admin page shells, contractor/manual-lot queries and downloads, selected pricing export, wishlist page, config page, Windows tool package |
| `Http/Controllers/PricingSearchHistoryController` | Delete one/all pricing search snapshots |
| `Http/Controllers/PricingWishlistBulkController` | Per-Admin selected wishlist delete/export |
| `Http/Controllers/SyncedPricingExportController` | Configurable PhpSpreadsheet XLSX export |
| `Http/Controllers/SyncedPricingBbgExportController` | Fixed-layout BBG XLSX export |
| `Http/Controllers/Api/MuasamcongController` | API availability and validated search proxy |
| `Http/Controllers/Api/PersonalSessionImportController` | One-time token cookie import, verification, and token consumption |

## Livewire Components

| Component | Responsibility |
|---|---|
| `TracuuThuoctrungthau` | Smart Pricing search/snapshot/filters/pagination/selection/sync/wishlist |
| `ContractorHistory` | Contractor history, selected bid sync, KQLCNT and HSMT workflows |
| `QueuedContractorHistory` | Dispatch/poll contractor-history queue jobs |
| `ContractorSearchList` | Archived contractor search list, refresh, delete |
| `ManualContractorLots` | Select and persist contractor manual lots from HSMT snapshot |
| `SmartPricingVerifiedLots` | Sync/display verified lots for a Smart Pricing row |
| `SyncedPricingList` | Synced records, edit/delete, selection, export profiles/assets/settings |
| `SearchHsmt` | HSMT search and FastExcel export |
| `ConfigManager` | Integration configuration, environment doctor, personal session/token tool |
| `Hsmt` | Empty legacy scaffold; no active behavior found |

## Blade Views

Admin page shells:

- `resources/views/muasamcong.blade.php`
- `resources/views/contractors.blade.php`
- `resources/views/contractor-searches.blade.php`
- `resources/views/manual-contractor-lots.blade.php`
- `resources/views/hsmt.blade.php`
- `resources/views/synced.blade.php`
- `resources/views/wishlist.blade.php`
- `resources/views/config.blade.php`

Livewire views:

- `config-manager`, `contractor-history`, `contractor-search-list`, `manual-contractor-lots`, `queued-contractor-history`, `search-hsmt`, `smart-pricing-verified-lots`, `synced-pricing-list`, `tracuu-thuoctrungthau`;
- partials for environment doctor, HSMT catalogue, KQLCNT, personal session, pricing detail, and synced export configuration;
- `livewire/hsmt.blade.php` is a legacy placeholder.

ClientPortal owns all customer-facing Muasamcong views under `Modules/ClientPortal/resources/views/applications/muasamcong` and the public share view.

## Services

| Service | Responsibility |
|---|---|
| `MuaSamCongService` | Core upstream pricing/HSMT requests, URL security, normalization, company search |
| `PricingTbmtPaginationService` | Bounded full-page loading for TBMT searches |
| `PricingSearchSnapshotService` | Normalize/hash keyword, store/retrieve/delete search payloads |
| `PricingResultSyncService` | Validate/map selected source rows and duplicate-safe persistence |
| `PricingWishlistService` | Per-user wishlist add/remove/recent state |
| `ContractorHistoryService` | Upstream contractor participation history |
| `ContractorSearchArchiveService` | Atomic contractor search archive and bounded detail page |
| `KqlcntService` | KQLCNT/contract/winner retrieval and normalization |
| `HsmtDetailService` | HSMT detail/form/catalogue parsing |
| `HsmtSnapshotService` | Private JSON/XLSX/metadata HSMT snapshots |
| `SmartPricingAwardService` | Verify Smart Pricing awards/lots from source records |
| `SyncedPricingExportPreferenceService` | User-scoped export profiles, assets, whitelists, defaults |
| `MuasamcongConfigService` | Allowlisted environment/config changes and validation |
| `PersonalSessionService` | Validate/encrypt/store/test personal session cookie |
| `SessionImportTokenService` | Issue, validate, and locked-consume one-time import tokens |

## Imports / Exports

### Imports

- Spreadsheet import: **Not present**.
- Personal session import: one-time token workflow through the Windows/browser tool and `/api/muasamcong/update-cookie`.
- Console entry point: `Modules\Muasamcong\Console\ImportPersonalSession`.

### Exports

- HSMT selected rows via `HsmtExport`/FastExcel.
- selected pricing snapshot rows via FastExcel.
- per-user wishlist selected rows via FastExcel.
- manual contractor lots via FastExcel.
- synced pricing configurable XLSX via PhpSpreadsheet, max 5,000 selected IDs.
- fixed-layout BBG XLSX via PhpSpreadsheet, max 5,000 selected IDs.
- user-scoped export profile JSON export/import within `SyncedPricingList`.
- Windows session-tool ZIP download.

Generated downloads use temporary/private behavior; synced exports set `Cache-Control: no-store, private`, `X-Content-Type-Options: nosniff`, and delete temporary files after sending.

## Models

Active persistence models:

- `PricingResult`
- `PricingWishlist`
- `PricingSearchSnapshot`
- `ContractorBid`
- `ContractorSearch`
- `ContractorSearchItem`
- `ContractorSearchJob`
- `ContractorManualLot`
- `KqlcntRecord`
- `PersonalSession`
- `SessionImportToken`
- `SyncedExportPreference`
- `SyncedExportProfile`

`Models/Muasamcong.php` is an unused scaffold. All active models currently use `protected $guarded = []` and define casts where applicable.

## Database Tables

| Table | Purpose / key constraints |
|---|---|
| `muasamcong_pricing_results` | Synced pricing rows; unique UUID `source_id`; search indexes; raw payload/audit/manual quote fields |
| `muasamcong_pricing_wishlists` | Per-user wishlist; unique `(user_id, source_id)` |
| `muasamcong_pricing_search_snapshots` | Full response by unique keyword hash; freshness/access metadata |
| `muasamcong_contractor_bids` | Persisted contractor participation; unique contractor + TBMT |
| `muasamcong_kqlcnt_records` | KQLCNT/contracts/winners/HSMT snapshot metadata; unique contractor + TBMT |
| `muasamcong_contractor_searches` | One archive per unique normalized contractor code |
| `muasamcong_contractor_search_items` | Archived TBMT items; FK to search, cascade delete, unique search + TBMT |
| `muasamcong_contractor_search_jobs` | Queue status/progress; nullable FK to archived search with null-on-delete |
| `muasamcong_contractor_manual_lots` | Manual lot confirmation; unique contractor + TBMT + lot key |
| `muasamcong_personal_sessions` | Encrypted session state by unique key |
| `muasamcong_session_import_tokens` | Hashed one-time tokens, expiry, used timestamp |
| `muasamcong_synced_export_preferences` | Legacy/current per-user single export preference; unique user |
| `muasamcong_synced_export_profiles` | Named per-user profiles; unique user + name; format/header/footer/page setup |

`muasamcong_price_list_profiles` is created and then dropped by historical migrations and is not an active domain table at this checkpoint.

## Relationships

- `ContractorSearch hasMany ContractorSearchItem`.
- `ContractorSearchItem belongsTo ContractorSearch`.
- `ContractorSearchJob.contractor_search_id` references `ContractorSearch` and becomes null when the archive is deleted.
- Other user/audit IDs are intentionally stored as unsigned IDs without database foreign keys; application logic owns their lifecycle.
- Wishlist and export profile scoping are enforced by user ID in service/controller queries.

## Shared / Cross-Module Dependencies

- Repository Admin authentication and Spatie-style permission middleware/Gates.
- Laravel queue/storage/HTTP/Eloquent/validation facilities.
- Livewire 3.1.
- FastExcel and PhpSpreadsheet.
- ClientPortal Muasamcong adapter directly imports domain models/services.

Dependency direction:

```text
ClientPortal -> Muasamcong domain
Muasamcong -/-> ClientPortal
```

No circular module dependency was observed.

## Events / Jobs

- Events/listeners: **Not present** in the reviewed module.
- Queue job: `FetchContractorHistoryJob` (`ShouldQueue`, timeout 900 seconds, one try).
- Job states stored in `muasamcong_contractor_search_jobs`: queued/running/saving/completed/failed behavior is represented in source.
- Concurrent job dispatch is not protected by a unique idempotency key.

## Configuration / Environment Variables

Core variables:

- `MUASAMCONG_ORIGIN`
- `MUASAMCONG_VERIFY_SSL`
- `MUASAMCONG_TIMEOUT`
- `MUASAMCONG_USER_AGENT`
- `MUASAMCONG_SMART_TOKEN`
- `MUASAMCONG_SESSION_COOKIE`
- `MUASAMCONG_PAGE_SIZE`
- `MUASAMCONG_CONTRACTOR_HISTORY_PAGE_SIZE`
- `MUASAMCONG_CONTRACTOR_HISTORY_MAX_PAGES`

Endpoint variables:

- `MUASAMCONG_PRICING_ENDPOINT`
- `MUASAMCONG_CONTRACTOR_ENDPOINT`
- `MUASAMCONG_CONTRACTOR_JOINED_BIDS_ENDPOINT`
- `MUASAMCONG_KQLCNT_TBMT_ENDPOINT`
- `MUASAMCONG_KQLCNT_CONTRACT_ENDPOINT`
- `MUASAMCONG_HSMT_DETAIL_ENDPOINT`

Referer variables:

- `MUASAMCONG_PORTAL_REFERER`
- `MUASAMCONG_PRICING_REFERER`
- `MUASAMCONG_CONTRACTOR_JOINED_BIDS_REFERER`
- `MUASAMCONG_KQLCNT_REFERER`

All configured destinations/referers must remain HTTPS on exact host `muasamcong.mpi.gov.vn`, with no URL credentials and only default/443 port. Production TLS verification is always enabled. Real token/cookie values must never be committed or printed.

The module `.env.example` lists only a subset of supported variables; `config/muasamcong.php` is the complete tracked inventory.

## Commands

- `msc:test --payload=<path>`
- `msc:test-hsmt <keyword> <YYYY-MM-DD:YYYY-MM-DD>`
- `msc:import-personal-session --stdin [--test]`

Console commands are registered through the repository module loader.

## Known Limitations

- No verified contractor/winner-to-exact-lot/medicine join key; do not use heuristics.
- Upstream endpoints/schema/token/cookie lifetime are external and may change.
- Several state-changing actions lack dedicated server-side capability checks.
- One-time session token claim is not atomic before cookie mutation.
- Contractor job dispatch can race and duplicate work.
- ClientPortal DB search returns a maximum of 500 matching rows while reporting a complete result.
- Large searches/exports can be synchronous and memory/latency intensive.
- Snapshot/raw payload/HSMT file retention is undefined.
- No Admin Dashboard exists; `/admin/muasamcong` is Smart Pricing.
- Intended permission model for Sanctum search API is not documented.

## Maintenance Notes

- Preserve route names, DB tables, storage paths, export profile formats, and ClientPortal contracts unless a compatibility plan is approved.
- Do not rewrite applied migrations; add new migrations for schema changes.
- Keep source data separate from manual administrative enrichment.
- Do not expose cookie/token values through Livewire, HTML, logs, Dashboard widgets, or error messages.
- Keep upstream redirects disabled and production TLS verification enabled.
- Use bounded queries for any future Dashboard summary.
- Prefer module-scoped verification for module-only changes; expand to ClientPortal/shared tests when direct contracts change.
- Existing tests were reviewed but not run during this docs-only baseline.
- `AI_HANDOFF.md` is legacy context. A canonical `COLLABORATION_HANDOFF.md` does not yet exist and requires a separate approved batch.
