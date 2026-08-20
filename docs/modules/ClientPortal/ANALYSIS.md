# ClientPortal — Module Analysis

Assessment branch: `agent/price-list-excel-data-types`  
Assessment date: 2026-08-20  
Scope: `Modules/ClientPortal` plus the minimum adjacent source required to verify contracts and dependencies.

## 1. Executive summary

`ClientPortal` has a sound top-level direction: it is a separate support module for authenticated Client/PWA experiences, while domain modules remain canonical owners of business data. The current `Muasamcong` adapter follows that direction in many important places: it is activated only when the source module is enabled, uses `web`-guard client permissions, keeps generated price-list files on private local storage, queues long-running sync/export/PDF/email work, and scopes user-owned operational records before download or status access.

The module is not ready to be considered structurally finished, however. The recent Price List feature expanded responsibilities significantly and exposed several P1 correctness, authorization, lifecycle, test, and maintainability gaps. The most urgent confirmed issue is an Excel storage collision: generated XLSX files use a per-user directory and a filename with second-level timestamp only, so two exports for the same user in the same second can overwrite one another. Other important issues include ambiguous export-profile publication scope, mutation permissions that currently collapse into `view`, public price-list links without expiry/revocation lifecycle, raw internal exception text exposed to clients, non-idempotent external side effects, and weak behavioral regression coverage for the newest workflow.

No confirmed P0 security/data-loss issue was found in the inspected scope. Several P1 findings should be closed before merging this feature branch to `main`.

### Recommendation

**Major Refactor** — preserve the current module/application-adapter architecture, routes, database contracts, PWA UX and domain ownership, but refactor workflow boundaries, authorization actions, file/share lifecycle, and regression coverage before treating `ClientPortal` as a stable reusable application platform.

This is **not** a Full Rebuild recommendation. The foundation is good and should be retained.

---

## 2. Intended module boundary

The module-local `Modules/ClientPortal/README.md` defines the intended ownership correctly:

- `ClientPortal` owns `/my-apps`, `/apps/*`, PWA application UI, application/feature permissions, Client jobs and application adapters.
- Domain modules such as `Muasamcong` own domain models, database, services and Admin workflows.
- Client adapters may depend on source-domain services, but domain modules must not depend on `ClientPortal`.
- Disabling `ClientPortal` must not change Admin/domain routes.

The module manifest declares:

```text
name            ClientPortal
type            support
default_enabled true
depends         Auth
```

`ApplicationRegistry` then adds adapter-level runtime dependency through each application manifest's `source_module`. This is a reasonable design for optional client adapters.

### Architecture assessment

**Good:**

- Separate support module instead of continuing to grow `Modules/Admin`.
- Application adapters are isolated under `Applications/{Application}`.
- Source module enablement is checked before an adapter is exposed.
- The current dependency direction is `ClientPortal -> Muasamcong`, not the reverse.
- Client permissions use guard `web`; Admin management permissions remain guard `admin`.
- Private file downloads are served through controlled routes rather than public storage URLs.

**Needs improvement:**

- Some adapter controllers directly persist/query Muasamcong domain models instead of delegating domain persistence to Muasamcong services.
- The generic ClientPortal layout contains Muasamcong-specific queue status logic.
- Large workflow logic is concentrated in controllers and jobs instead of smaller services/builders.

---

## 3. Route and authorization analysis

### 3.1 Portal routes

`/my-apps` correctly requires `web` + `auth:web` and uses `ApplicationRegistry::forUser()`.

The Admin application-management area correctly uses `auth:admin` and named admin permissions. Existing routes use generic role/user permissions such as `view_role`, `edit_role`, and `edit_user`.

### Finding CP-P1-01 — Client application administration uses broad generic permissions

**Priority:** P1  
**Evidence:** `Modules/ClientPortal/routes/web.php`

Application permission synchronization and Super Admin synchronization can change client access system-wide, but they are authorized by the existing generic `edit_role` permission. User client-permission mutation uses `edit_user`.

**Impact:** An administrator allowed to edit ordinary roles/users implicitly gains authority to generate and assign the full `client.*` permission surface. That may be intended today, but it is less explicit than the repository standard's capability-specific authorization boundary.

**Recommendation:** Introduce or map explicit admin capabilities such as:

```text
client-apps.view
client-apps.permissions.sync
client-apps.users.manage
client-apps.roles.manage
```

If the project intentionally treats `edit_role`/`edit_user` as the canonical authority, document that decision and lock it with allow/deny tests.

**Verification:** Feature tests must exercise real forbidden/allowed requests, not only inspect middleware strings.

### 3.2 Application/feature middleware

`EnsureApplicationAccess` and `EnsureFeatureAccess` are small and fail closed when manifests are absent or permission checks fail. This is good.

The middleware only enforces application and feature-level permissions. Action permissions therefore must be enforced by routes/controllers.

### Finding CP-P1-02 — Several mutations are authorized only by a feature `view` permission

**Priority:** P1  
**Evidence:** `Applications/Muasamcong/manifest.php`, `Applications/Muasamcong/routes.php`

Current examples:

- Wishlist GET/store/toggle/delete all sit under `client.muasamcong.wishlist.view`.
- Public drug share creation, share expiry update and share revoke sit under `client.muasamcong.drug-pricing.view`.
- Price-list delete is owner-scoped but does not require the `price-list.export` action or a delete/manage action.

Sync correctly has an explicit action permission and controller check. Price-list create/recreate/PDF/share/email also call `exportUser()` and require `client.muasamcong.price-list.export`.

**Impact:** A read-only feature permission is not actually read-only. This makes future role design difficult and violates capability-level mutation semantics.

**Recommendation:** Expand manifests with action permissions and enforce them at the action boundary. Example:

```text
client.muasamcong.wishlist.manage
client.muasamcong.drug-pricing.share
client.muasamcong.shares.manage
client.muasamcong.price-list.export
client.muasamcong.price-list.delete   (if delete must be separable)
```

Do not add permissions mechanically if the product intentionally defines view = manage; document that explicitly if retained.

**Verification:** Test each mutation with user A having view only, action permission, and no permission.

---

## 4. Application registry and permission services

`ApplicationRegistry` provides a clean manifest normalization layer and correctly hides adapters whose source modules are disabled.

### Finding CP-P2-01 — Registry rescans and requires manifests repeatedly per request

**Priority:** P2  
**Evidence:** `Modules/ClientPortal/Services/ApplicationRegistry.php`

`find()` and `forUser()` call `all()`, which scans application directories and requires manifest PHP files every time.

**Impact:** Small today with one adapter, but unnecessary filesystem work as application count grows.

**Recommendation:** Memoize normalized manifests for the current request/process with an invalidation strategy appropriate to module configuration/cache clearing.

### Finding CP-P1-03 — Permission sync occurs during GET edit screens

**Priority:** P1  
**Evidence:** `ApplicationAdminController::editUser()` and `editRole()`

Both GET actions call `$permissions->sync()`, which can create database permission records and clear Spatie permission cache.

**Impact:** GET requests mutate persistent state, making request semantics, caching, auditability and testing less predictable.

**Recommendation:** Keep synchronization behind the existing explicit POST `sync-permissions` operation or an idempotent deployment/seed step. GET screens should read only.

---

## 5. PWA shell and UI architecture

The launcher and application shell are responsive and intentionally simple. The service worker does **not** cache authenticated navigation responses and only caches static shell assets, which is an important positive security property.

### Finding CP-P1-04 — Generic application layout contains Muasamcong-specific workflow logic

**Priority:** P1  
**Evidence:** `resources/views/layouts/application.blade.php`

The generic layout reads `sync_request_id`, checks `client.muasamcong.drug-pricing.sync-status`, renders Mua sắm công sync copy, and polls that endpoint.

**Impact:** Adding a second Client application makes the shared shell aware of one application's queue protocol and terminology. This undermines the adapter boundary.

**Recommendation:** Move Muasamcong queue status into an application partial/Blade section, or define a generic ClientPortal job-status contract/component that adapters populate.

**Preserve:** Existing mobile-safe shell, PWA registration and application context.

---

## 6. Muasamcong search adapter

`ClientPricingSearchService` implements the desired database-first flow:

```text
synced PricingResult -> PricingSearchSnapshot -> external MuaSamCong API
```

A force refresh bypasses local database and snapshots. Local synced lookup is bounded at 500 results. This is a good operational strategy.

### Finding CP-P1-05 — Controller owns filtering, sorting, pagination preparation and summary business logic

**Priority:** P1  
**Evidence:** `MuasamcongApplicationController`

`drugPricing()` performs validation, orchestrates search, filters result collections, sorts by price, computes summaries, constructs a paginator, checks synced IDs and resolves Wishlist state.

**Impact:** The controller is difficult to test in isolation and is accumulating domain/application logic contrary to the repository's thin-controller standard.

**Recommendation:** Move result filtering/sorting/summary/pagination preparation into a Client application service/DTO. Keep the controller focused on request validation, authorization and view response.

### Finding CP-P2-02 — Local database failure silently becomes external API fallback

**Priority:** P2  
**Evidence:** `ClientPricingSearchService::searchSyncedResults()`

All exceptions are caught and converted to an empty local result.

**Impact:** A database/query regression can silently increase API traffic and hide an outage.

**Recommendation:** Keep graceful fallback, but emit structured/redacted warning logs and distinguish `empty` from `local_error` internally.

### Finding CP-P1-06 — Snapshot/API result size is not bounded by ClientPortal before in-memory filtering

**Priority:** P1 (conditional)  
**Evidence:** `MuasamcongApplicationController::drugPricing()`

Synced DB results are capped at 500, but snapshot/API payloads are collected and filtered in memory. The inspected ClientPortal layer does not guarantee an upper bound for those payloads.

**Impact:** High-cardinality API/snapshot responses can increase memory/response time.

**Recommendation:** Verify the source service's hard maximum. If it is not guaranteed, enforce a bounded result contract or server-side/chunked paging before client-side filtering.

**Verification needed:** Load test a high-cardinality keyword and inspect source service pagination semantics.

---

## 7. Queue synchronization and history

`SyncRequest` gives the client a persistent queue status record. Status lookup is user/application/feature scoped, which is good. The sync job has finite tries/timeout and delegates persistence to `PricingResultSyncService`.

### Finding CP-P1-07 — Raw exception messages are persisted and returned to clients

**Priority:** P1  
**Evidence:** `SyncPricingResultsJob::failed()`, `drugPricingSyncStatus()`, history mapping

The raw exception message is stored in `error_message`, returned by the status endpoint and available to history UI.

The same pattern also exists in price-list generation/PDF status.

**Impact:** Internal paths, process output, provider details or implementation information can leak to authenticated clients. It also violates the project standard to avoid raw exception text in user-facing responses.

**Recommendation:** Persist a user-safe failure code/message separately from structured internal log context. Never expose raw Process/exception output to the UI.

### Finding CP-P2-03 — History is capped rather than paginated

**Priority:** P2  
**Evidence:** `MuasamcongHistoryController`

Search and sync history each load at most 100 records, concatenate and sort in memory.

**Impact:** Bounded and safe, but older history becomes inaccessible.

**Recommendation:** Move to an explicit paginated activity feed or clearly label the screen as recent history.

---

## 8. Wishlist boundary

Wishlist index is user-scoped and paginated. Delete verifies ownership. Store/toggle re-resolve the source record instead of trusting browser-supplied drug payloads, which is good.

### Finding CP-P1-08 — Client adapter persists Muasamcong domain model directly

**Priority:** P1  
**Evidence:** `MuasamcongWishlistController`

The Client adapter builds and persists `PricingWishlist::updateOrCreate()` itself.

**Impact:** Wishlist normalization/persistence rules can diverge if another Muasamcong interface writes the same domain object.

**Recommendation:** Move canonical Wishlist persistence semantics into a `Muasamcong` domain service and let ClientPortal call that service. Preserve the adapter-specific request/UX in ClientPortal.

---

## 9. Public drug sharing

Positive findings:

- Tokens are random and unique.
- Public payload is explicitly allowlisted rather than dumping a raw result.
- Revoked/expired links return unavailable.
- Share management is owner/application/feature scoped.

### Finding CP-P2-04 — Public view counter increment is not concurrency-safe

**Priority:** P2  
**Evidence:** `PublicDrugShareController::show()`

`views_count` is read, incremented in PHP and saved. Concurrent views can lose increments.

**Recommendation:** Use an atomic database increment and update `last_viewed_at` independently/atomically.

### Open decision — default expiry

New drug shares currently have `expires_at = null` until changed by the owner. This means they are permanent by default. That can be acceptable, but it should be an explicit product/security decision.

---

## 10. Price List workflow

The Price List feature has several strong implementation choices:

- selected rows are bounded (`max:200`);
- Excel/PDF work is queued;
- XLSX uses explicit String/Number/Date handling;
- workbook page setup is profile-driven;
- generated files use private local storage;
- download routes check record ownership;
- PDF conversion uses Laravel Process argument arrays, not a shell command string;
- PDF temp files are cleaned in `finally`;
- email attachment choice is server-validated;
- PDF file paths are export-ID scoped.

These should be preserved.

### Finding CP-P1-09 — Excel exports can overwrite each other when created in the same second

**Priority:** P1 — merge blocker  
**Evidence:** `GeneratePriceListExport`

Current XLSX path:

```text
client-portal/price-lists/{user_id}/bang-gia-{Ymd-His}.xlsx
```

Two queued exports for the same user generated within one second use the same path.

**Impact:** One job can overwrite another file, leaving multiple `PriceListExport` rows pointing at the same XLSX. Subsequent delete/recreate/share/email actions can affect the wrong physical artifact.

**Recommendation:** Make every export artifact path immutable and export-ID scoped, for example:

```text
client-portal/price-lists/{user_id}/{export_id}/bang-gia-{timestamp}.xlsx
```

or include UUID/random uniqueness in the filename.

**Verification:** Dispatch two exports for the same user with the same frozen clock and assert distinct paths and contents.

### Finding CP-P1-10 — Export Profile publication/ownership scope is ambiguous and currently unbounded

**Priority:** P1 — merge decision required  
**Evidence:**

- `muasamcong_synced_export_profiles` has `user_id` and unique `(user_id, name)`.
- Admin `SyncedPricingList` saves/duplicates/deletes profiles with the authenticated admin ID.
- Client `MuasamcongPriceListController::index()` loads `SyncedExportProfile::query()->...->get()` without a user/public/active scope.
- `validatedExportRequest()` accepts any existing profile ID.

**Impact:** In a multi-admin/multi-organization future, every Client user can discover/use all stored export profiles, including company metadata/logo/signature configuration belonging to another profile owner. In a single-organization deployment this may currently be intentional, but the schema expresses ownership while the Client layer treats profiles as global.

**Recommendation:** Define one explicit contract before merge:

1. Global published templates: add a publication/active scope controlled by Admin; or
2. User/organization-scoped templates: filter to the authorized owner/tenant; or
3. System defaults + explicitly shared profiles.

Do not leave `user_id` ownership and global Client visibility implicit.

### Finding CP-P1-11 — Public price-list links have no expiry or revoke lifecycle

**Priority:** P1  
**Evidence:** `PriceListExport.share_token` + `publicDownload()`

Unlike `PublicShare`, price-list share tokens have no `expires_at` or `revoked_at`. Once generated, the link works until the export/file is deleted.

**Impact:** A leaked quote URL remains valid indefinitely and there is no independent revoke action.

**Recommendation:** Reuse/generalize the existing `PublicShare` lifecycle or add expiry/revoke fields and management UI for price-list links. Keep the physical file private.

### Finding CP-P1-12 — Share history is recorded before the user actually shares/copies successfully

**Priority:** P1  
**Evidence:** `MuasamcongPriceListController::share()` and client-side Web Share flow

The server appends `delivery_history` as soon as it creates/returns a share URL. The browser may then cancel `navigator.share()` or clipboard operation.

**Impact:** UI can say a quote was sent/shared when only a link was generated.

**Recommendation:** Distinguish lifecycle events such as `link_created` and `shared_confirmed`, or post an acknowledgement only after client sharing/copy succeeds. Do not claim receipt by a recipient unless that can actually be proven.

### Finding CP-P1-13 — Delivery history JSON has lost-update risk

**Priority:** P1  
**Evidence:** email/share workflows read the full `delivery_history` array, append, then update it.

**Impact:** Concurrent email/share jobs can overwrite each other's newest entries.

**Recommendation:** Prefer a normalized `client_portal_price_list_deliveries` table with one row per delivery event. This also provides better status, failure, recipient, attachment and retention modeling.

### Finding CP-P1-14 — Email queue is not idempotent and has no durable delivery status

**Priority:** P1  
**Evidence:** `SendPriceListExportEmail`

The job sends email and only afterward appends history. If mail is accepted and the job then fails before the database update, a retry can send a duplicate email. If files disappear before execution, the job can simply return with no user-visible failed status.

**Recommendation:** Add a delivery record before dispatch, transition `queued -> processing -> sent/failed`, use an idempotency key, and decide retry semantics for external mail side effects.

### Finding CP-P1-15 — PDF queue dispatch is race-prone

**Priority:** P1  
**Evidence:** `queuePdf()` checks current state then updates/dispatches without an atomic transition.

**Impact:** Two concurrent requests can both observe a non-processing state and dispatch duplicate conversions.

**Recommendation:** Use a conditional update/lock/unique job mechanism so only one transition from eligible state to `queued` wins.

### Finding CP-P1-16 — Price List controller and export job have too many responsibilities

**Priority:** P1  
**Evidence:** `MuasamcongPriceListController`, `GeneratePriceListExport`

The controller owns queries, selection validation, ownership, file lifecycle, share lifecycle and queue dispatch. The XLSX job owns data retrieval, normalization, type conversion, workbook drawing/layout, page setup and storage lifecycle.

**Impact:** Regression risk is already visible: recent fixes for permissions, PDF placement, file permissions and Excel formatting all accumulate in the same classes.

**Recommendation:** Preserve behavior while extracting cohesive services, e.g.:

```text
PriceListWorkspaceService
PriceListExportAuthorizer / policy
PriceListWorkbookBuilder
PriceListArtifactStorage
PriceListDeliveryService
PriceListShareService
```

The queue jobs should orchestrate status transitions and call these services.

### Finding CP-P1-17 — Deployment-specific `www-data` permission repair is embedded in the PDF job

**Priority:** P1/P2 operational portability  
**Evidence:** `GeneratePriceListPdf::normalizeStorageAccess()`

The job hard-codes group `www-data` and suppresses permission errors.

**Impact:** It works around the current root queue worker vs PHP-FPM ownership problem, but is tied to Debian-style deployment identity and can silently fail on Docker/Herd/other users.

**Recommendation:** Standardize queue workers to the same application filesystem group/user where possible. If code-level normalization remains necessary, move identity/mode to infrastructure configuration and log failures safely.

### Inference to verify — concurrent LibreOffice workers

The PDF job invokes headless LibreOffice without an explicit per-job user profile (`UserInstallation`). LibreOffice can serialize/fail concurrent headless processes depending on environment/profile locking.

**Verification:** Dispatch multiple PDF conversions concurrently on the target Docker/VPS environment. If collisions occur, use a unique temporary LibreOffice profile per job.

---

## 11. Database analysis

ClientPortal currently owns:

```text
client_portal_sync_requests
client_portal_public_shares
client_portal_price_list_exports
```

The tables correctly keep Client workflow state outside Muasamcong domain tables.

### Finding CP-P2-05 — Missing composite indexes for primary access patterns

**Priority:** P2  
**Evidence:** current migrations use mostly single-column indexes.

Frequent patterns suggest composite indexes may become useful:

```text
sync_requests:  (user_id, application_key, feature_key, created_at)
public_shares:  (created_by, application_key, feature_key, created_at)
price_exports:  (user_id, status, created_at)
```

Add only after query-plan/volume verification.

### Finding CP-P1-18 — Cross-record integrity is mostly application-enforced

**Priority:** P1/P2  

`user_id`, `created_by` and `profile_id` do not have foreign keys. Cross-module profile FK constraints may be intentionally avoided, but deletion behavior is therefore fully application-owned.

**Recommendation:** Document lifecycle rules. Add safe FKs where module dependency and migration order permit; otherwise add explicit cleanup/retention jobs and tests.

### Finding CP-P2-06 — Models use broad `guarded = []`

**Priority:** P2  

Current controllers use server-built arrays, so no direct mass-assignment vulnerability was confirmed. Still, workflow models contain sensitive state (`share_token`, file paths, statuses, ownership IDs). Prefer explicit fillable/guarding or keep construction behind services to reduce future accidental assignment risk.

---

## 12. Import/export architecture assessment

The repository standard says to reuse `Modules/Shared/Services/ImportExport` when applicable. ClientPortal's Price List XLSX is a **specialized document export**, not a normal dataset round-trip:

- rich multi-row company header/footer;
- logo and signature drawings;
- custom column types/widths/decimals;
- print/page setup;
- queued private artifact lifecycle;
- PDF conversion and delivery.

The current shared `BaseImportExportService` uses FastExcel and its storage concern currently creates files on `disk('public')`. Therefore replacing the Price List generator mechanically with the shared base would reduce required functionality and privacy.

**Recommendation:** Keep the specialized renderer, but extract generic private artifact storage/retention/status helpers into reusable Shared infrastructure only if another module needs the same behavior. Do not create a second generic import/export framework inside ClientPortal.

---

## 13. Test coverage analysis

The module has a useful foundation under `tests/Feature/ClientApps`, including:

- module extraction/enablement behavior;
- application registry discovery;
- launcher auth behavior;
- PWA manifest/service worker behavior;
- client admin route middleware;
- database-first search behavior;
- sync queue status transitions;
- history feature presence;
- Wishlist route configuration;
- public share route/model availability;
- Price List route/profile/page setup structure.

### Finding CP-P1-19 — Price List automated coverage is not sufficient for the current production workflow

**Priority:** P1 — merge blocker

`MuasamcongPriceListTest` currently checks route middleware/URI, manifest permissions, public route auth absence and page-setup defaults. It does not exercise the newest behavior end-to-end.

**Required pre-merge regression additions:**

- two same-second exports generate distinct XLSX paths;
- export owner A cannot access/delete/status/share/email owner B's export;
- view-only user cannot execute action-level mutations after permission model is corrected;
- selected source IDs are constrained to actual allowed records;
- generated XLSX contains expected String/Number/Date semantics;
- `Nhóm thuốc` and `Hạn dùng` normalization;
- signature/footer remains in last four columns;
- PDF status transition and private download;
- PDF failure returns safe public error, internal details only in logs;
- public share expiry/revoke lifecycle once implemented;
- email attachment combinations Excel/PDF/both and durable sent/failed status;
- delivery history/concurrent delivery behavior;
- profile visibility/publication scope.

Manual UI PASS is valuable but does not replace these regression gates.

### Finding CP-P1-20 — Several tests assert middleware strings rather than authorization behavior

**Priority:** P1

Route-structure tests are useful, but sensitive operations should also prove denied and allowed requests with realistic users/permissions.

---

## 14. Findings by priority

### P0

No confirmed P0 finding in the inspected ClientPortal scope.

### P1 — address before stable merge/release

1. CP-P1-09 — same-second Excel path collision / overwrite.
2. CP-P1-10 — ambiguous/global export-profile visibility despite user-owned schema.
3. CP-P1-02 — mutations protected only by feature view permissions.
4. CP-P1-11 — public price-list links have no expiry/revoke lifecycle.
5. CP-P1-12 — share history can falsely imply a completed share.
6. CP-P1-13 — delivery-history JSON lost-update race.
7. CP-P1-14 — email side effect lacks idempotency/durable status.
8. CP-P1-15 — duplicate PDF queue race.
9. CP-P1-07 — raw exception/process errors exposed to clients.
10. CP-P1-19/20 — insufficient behavioral regression tests for new workflow/authorization.
11. CP-P1-03 — GET permission screens mutate permission data.
12. CP-P1-04/05/08/16 — generic-shell coupling and controller/job/domain-boundary debt.
13. CP-P1-06 — API/snapshot in-memory result bound must be verified.
18. CP-P1-18 — lifecycle/integrity mostly application-enforced.

### P2

- Registry request memoization.
- Structured warning logging for local-search fallback.
- Paginated/full history UX.
- Atomic public share view count.
- Composite indexes after query-plan verification.
- Narrow model mass-assignment surface.
- Deployment portability of filesystem permission normalization.

---

## 15. Suggested implementation order before merge

### Gate A — correctness and security semantics

1. Make XLSX artifact paths export-ID/UUID unique.
2. Decide and enforce Export Profile publication/ownership scope.
3. Define action-level mutation permissions.
4. Sanitize user-facing queue errors.
5. Add price-list public share expiry/revoke policy.

### Gate B — delivery reliability

6. Normalize delivery events into a durable table/status model.
7. Make PDF queue transition idempotent.
8. Make email dispatch/retry semantics idempotent or explicitly at-most/at-least-once with user-visible status.
9. Correct share history semantics.

### Gate C — architecture cleanup

10. Extract Price List workspace/workbook/storage/delivery services.
11. Move Wishlist persistence semantics to Muasamcong domain service.
12. Move app-specific queue UI out of the generic application layout.
13. Remove permission writes from GET screens.

### Gate D — regression

14. Add behavioral authorization/ownership tests.
15. Add XLSX/PDF/delivery regression tests.
16. Run all `tests/Feature/ClientApps` and full project regression before merge.

---

## 16. Unknowns and verification required

The following were not assumed as facts and should be verified during refactor planning:

- Whether the deployment is permanently single-organization/single-admin or must support multiple Admin profile owners.
- Whether Price List public links are intentionally permanent.
- Maximum item count returned by MuaSamCong API/snapshot for a broad keyword.
- Whether `PricingResultSyncService` provides complete retry/idempotency guarantees under queue retries.
- Whether concurrent LibreOffice conversions are reliable in every production runtime.
- Required retention period for XLSX/PDF files, public links, sync history and email delivery metadata.
- Whether storing full email body in delivery history is required or should be data-minimized.

---

## 17. Final architecture decision

**Recommendation: Major Refactor.**

Retain:

- `ClientPortal` as a separate support module;
- application adapter convention;
- manifest registry;
- web-guard client permissions;
- PWA launcher/shell;
- database-first search strategy;
- domain direction `ClientPortal -> Muasamcong`;
- queued sync/export/PDF/email;
- private artifact downloads;
- rich PhpSpreadsheet renderer.

Refactor before declaring the module stable:

- capability/action authorization;
- service/workflow boundaries;
- artifact uniqueness/lifecycle;
- public share lifecycle;
- delivery idempotency/status;
- profile publication scope;
- safe error reporting;
- behavioral regression coverage.

A Full Rebuild is not justified because the module boundary and core adapter model are already correct.