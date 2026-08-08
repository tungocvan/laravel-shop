# Facebook Module Analysis

Analysis date: 2026-07-20. Scope: `Modules/Facebook`, its documentation, directly referenced Admin/app/config/routes files, and the required project bootstrap files.

## 1. Executive Summary

The module has a sound initial separation of UI, application services, Graph transport, DTOs, models, jobs, and owned migrations. Tokens use encrypted casts and hidden serialization, API logs redact credential keys, routes have granular Spatie permissions, OAuth state is single-use, list queries eager-load their immediate relation, and scheduled dispatch is chunked.

It is not production-ready without a major refactor. The most important issue is an authorization gap: `Livewire\Posts\Form` authorizes only during `mount()`, while `saveDraft`, `schedulePost`, and `publishNow` do not authorize their individual capabilities. A user with create permission can therefore invoke publishing. Webhook deliveries are both operationally blocked by CSRF and unauthenticated at the payload level. Queue retries are internally inconsistent: failures are moved out of the statuses the retrying job may claim. Dispatching inside an open transaction can race a worker. Photo failures, replacement, duplication, and deletion also leave incorrect state or unmanaged files.

Recommendation: **Major Refactor**. The domain boundaries are usable and do not justify a full rebuild, but security, state-machine, media lifecycle, and regression coverage changes are substantial.

## 2. Module Overview

Evidence: `config/module.php` declares an enabled `domain` module and nine capabilities. `Modules/ModuleServiceProvider.php` dynamically registers its provider, routes, resources, migrations, Livewire components, and commands. The module provides OAuth connection, Page discovery/management, dashboard summaries, draft/scheduled posts, single-photo storage, queued Graph publishing, CLI operations, and a webhook endpoint.

Inference: The module is intended as a system-wide administrative integration rather than a multi-tenant integration; queries generally select the latest/global connection and do not scope by `user_id`.

## 3. Dependency Graph

```text
Modules/ModuleServiceProvider
  -> FacebookServiceProvider -> config('facebook.*')
  -> routes/web.php
     -> ConnectionController -> OAuthService -> GraphClient -> Meta Graph API
     |                       -> ConnectionService -> PageService
     -> PageController -> page Blade -> facebook.pages.index -> PageService
     -> PostController -> post Blades -> facebook.posts.* -> PostService
     |                                              -> MediaService -> Storage
     |                                              -> PublishFacebookPostJob
     -> WebhookController -> facebook log channel
  -> routes/console.php -> FacebookDispatchScheduledCommand -> PublishFacebookPostJob
  -> PublishFacebookPostJob -> PublishingService -> GraphClient -> Meta Graph API
  -> Models -> facebook_connections -> facebook_pages -> facebook_posts -> facebook_post_media

Cross-module: Facebook page Blades -> Admin::layouts.master
Cross-app: Facebook controllers -> App\Http\Controllers\Controller
Cross-app: module docs/dashboard -> app Console FacebookTestCommand
Authorization: auth:admin + Spatie permissions + global Super Admin Gate bypass
```

No circular class dependency was observed. `ConnectionService -> PageService -> GraphClient` and `OAuthService -> GraphClient` are acyclic.

## 4. Route Analysis

Evidence: `Modules/Facebook/routes/web.php` places admin pages behind `web`, `auth:admin`, and explicit permission middleware. Numeric post IDs have `whereNumber`. Public webhook methods are throttled.

Evidence: the POST webhook is in the `web` middleware group, while `bootstrap/app.php` declares no CSRF exception. Legitimate Meta POSTs cannot supply a Laravel CSRF token.

Evidence: webhook verification compares the verify token with `hash_equals`, but webhook delivery does not validate Meta's request signature.

## 5. Controller Analysis

Evidence: page/post controllers only return views. Connection orchestration is delegated to services. OAuth state and code are checked before exchange, and mapped Graph errors are flashed.

Evidence: `FacebookWebhookController::handle()` accepts any body, logs only object/count, and always returns 200. There is no signature validation, payload schema validation, event dispatch, idempotency, or raw-event persistence.

Inference: concatenating Meta's `error_description` into a flash message is escaped by normal Blade output in the observed dashboard, avoiding direct XSS there, but it exposes external text to users and should use a bounded safe message plus structured internal log.

## 6. Page Blade Analysis

Evidence: page templates extend `Admin::layouts.master` and mount module Livewire aliases correctly. Output uses escaped `{{ }}`. POST sync/disconnect forms use CSRF where present.

Evidence: dashboard/create links are displayed without `@can`; server route checks still protect navigation, but the UI exposes unavailable controls. `posts/index.blade.php` shows publish/retry/duplicate based on status, not capability. This is a UX leak and reinforces the action-level authorization defect.

Evidence: external `link_url` is rendered as an escaped `href` with `target="_blank"` but without `rel="noopener noreferrer"`.

## 7. Livewire Analysis

Evidence: list components use pagination, reset the page on filter changes, delegate queries, and authorize every mutating list action. Dashboard/detail authorize render access.

Evidence: `Posts\Form::mount()` checks create or update only on initial mount. `saveDraft()`, `publishNow()`, and `schedulePost()` do not call authorization. `publishNow()` never checks `facebook.posts.publish`; scheduling has no dedicated declared permission and implicitly uses update/create. Livewire public methods are callable endpoints, so initial Blade/route access is not sufficient action authorization.

Evidence: form validation constrains page existence/active state, title/message/type/link, and images to JPG/PNG/WebP and 5 MB. However, existing photo posts still require a newly uploaded image on every validation because the rule is unconditional for `post_type=photo`.

Evidence: `public $image` is untyped; `perPage` accepts `int|string` and includes `All`. Livewire-bound `perPage`, `search`, and filters have no explicit validation before service use. Casting prevents direct SQL injection, and Eloquent binds search text, but arbitrary/negative pagination values may cause errors.

No custom Livewire events are dispatched. There is no debounce declaration on live search, so each change may trigger a request depending on Livewire defaults.

## 8. Shared UI Component Analysis

Evidence: no shared UI component is referenced. Views depend only on the Admin layout and native Blade/Livewire markup. The shared import/export panel is irrelevant because this module has no import/export workflow.

## 9. Service Analysis

Strengths: constructor injection is common; multi-record draft creation/duplication and Page sync use transactions; queue claim uses an atomic conditional update; Graph errors are mapped; logs redact request credentials.

Evidence: `FacebookPostService::queueNow()` dispatches the job inside `DB::transaction()` without `afterCommit`. A fast worker can read before commit and return without retrying.

Evidence: `FacebookConnectionService::completeOAuth()` opens a DB transaction and calls `PageService::syncPages()`, which performs a network request before its nested transaction. The outer connection transaction remains open during Graph I/O, increasing lock duration and rollback ambiguity.

Evidence: `FacebookGraphClient::request()` performs synchronous retry delays (`usleep`) for both browser OAuth/sync and queue contexts. This can tie up web workers. Multipart publishing has no equivalent retry loop.

Evidence: `FacebookPostService::normalize()` always rewrites `created_by` during update, losing original creator provenance. CLI-created drafts receive null because only `admin` guard is read.

Evidence: service methods enforce state with HTTP `abort_*`, coupling domain behavior to HTTP exceptions and making CLI/job callers less explicit. `schedule()` is not transactional and accepts an unvalidated string from non-Livewire callers.

Evidence: `PageService::setDefault()` globally updates all non-deleted pages. This may be intended for a single integration, but there is no DB guarantee against two defaults under concurrency.

## 10. Import Analysis

Evidence: no import routes, Livewire components, services, jobs, storage, or shared import/export foundation usage exists. Header mapping, row validation, duplicate handling, chunking, reports, and cleanup are not applicable.

## 11. Export Analysis

Evidence: no export functionality exists. Query mapping, chunking, file storage, retention, and cleanup are not applicable.

## 12. Shared Service Analysis

Evidence: no `Modules/Shared` service is referenced. This is appropriate for the module-specific Graph integration. `app/Console/Commands/FacebookTestCommand.php` is a direct application-level dependency and duplicates Facebook configuration/transport concerns outside the canonical domain module.

Recommendation: move the diagnostic command behind module services during refactoring, after preserving its tests/behavior.

## 13. Model Analysis

Evidence: token attributes are hidden and encrypted; timestamps/JSON/booleans are cast; relationships and the due scope are explicit. Fillable arrays are broad but callers use normalized internal arrays, reducing current mass-assignment exposure.

Evidence: token masking is duplicated in `FacebookPage` accessor and `FacebookTokenMasker`. The accessor decrypts the token whenever rendered.

Evidence: `created_by` and connection `user_id` have no Eloquent relationships. Post status/type are free-form strings at the model/database boundary; constants provide labels but do not enforce transitions.

Evidence: soft-deleting a post does not delete its media rows because database cascade applies only to a physical delete. None of the models implement file cleanup.

## 14. Database Analysis

Evidence: four timestamp-ordered migrations create foreign keys for connection -> page -> post -> media, with cascade on physical delete; common status/time/search fields are indexed; page identity and post idempotency have unique constraints; soft deletes exist on connection/page/post.

Evidence: `user_id` and `created_by` are indexed unsigned integers without foreign keys. This avoids a hard cross-module schema dependency but permits orphan actor IDs.

Evidence: there are no check constraints for status/type, no unique guarantee for a single default Page, and no composite index tailored to the scheduler query `(status, scheduled_at, id)`.

Evidence: Page uniqueness includes a soft-deleted record, so reconnect/sync to the same `(connection,page)` restores via neither normal `updateOrCreate` nor a new row; it can hit the unique constraint if the old Page was soft-deleted.

Assumption: production uses MySQL, based on migration comments. Fresh migration behavior and query plans could not be executed because PHP is unavailable in the analysis shell.

## 15. Security Analysis

### Issue FB-SEC-01

Priority:
P0

File:
`Modules/Facebook/Livewire/Posts/Form.php`

Evidence:
`mount()`, `saveDraft()`, `publishNow()`, and `schedulePost()`; only `mount()` authorizes and `publishNow()` calls `queueNow()` without `facebook.posts.publish`.

Problem:
Action-level capabilities can be bypassed through callable Livewire methods.

Impact:
An admin granted draft creation/update but denied publishing can publish or schedule content to an external Facebook Page.

Recommendation:
Authorize each public mutation with its exact capability, define a schedule capability or documented mapping, add policy/service invariants, and test denied Livewire calls.

### Issue FB-SEC-02

Priority:
P0

File:
`Modules/Facebook/Http/Controllers/FacebookWebhookController.php`

Evidence:
`handle()` accepts and acknowledges any request; no `X-Hub-Signature-256` verification against the App Secret is present.

Problem:
Webhook authenticity is not established.

Impact:
Attackers can spoof events and pollute logs now; any future event side effects would inherit an unauthenticated public control path.

Recommendation:
Verify the raw body signature with constant-time comparison before decoding/processing, reject missing/invalid signatures, validate schema, add replay/idempotency controls, and test valid/invalid payloads.

### Issue FB-SEC-03

Priority:
P1

File:
`Modules/Facebook/Services/FacebookConnectionService.php`

Evidence:
`latest()`, `disconnect()`, and `syncLatestPages()` operate globally; `user_id` is recorded but never used for ownership.

Problem:
The ownership model is implicit and global.

Impact:
If the admin area becomes tenant/user scoped, one authorized admin may inspect, replace, sync, or disconnect another administrator's integration.

Recommendation:
Confirm system-wide ownership. If per-admin/tenant, scope every connection/Page/post query and authorize records through policies; otherwise remove misleading ownership or explicitly constrain a singleton.

Token handling is otherwise comparatively strong: encrypted casts, hidden attributes, redacted request logs, CSRF-protected admin forms, OAuth state validation, and no hard-coded secret were observed.

## 16. Performance Analysis

### Issue FB-PERF-01

Priority:
P1

File:
`Modules/Facebook/Services/FacebookPageService.php`; `Modules/Facebook/Services/FacebookPostService.php`

Evidence:
Both `paginate()` methods return `get()` when `perPage === 'All'`.

Problem:
User-selectable unbounded loading bypasses pagination.

Impact:
Large post/media histories can exhaust PHP memory and create slow Livewire responses.

Recommendation:
Remove `All`, cap page sizes server-side, and use cursor/chunk/lazy strategies for bulk operations.

### Issue FB-PERF-02

Priority:
P1

File:
`Modules/Facebook/database/migrations/2026_07_18_000003_create_facebook_posts_table.php`

Evidence:
Scheduler filters status and scheduled time, orders by scheduled time, then chunks by ID; only separate single-column indexes exist.

Problem:
The due-post query lacks a matching composite index and mixes `orderBy(scheduled_at)` with `chunkById()` ordering semantics.

Impact:
Scheduling scans/sorts grow with history and ordering can be surprising.

Recommendation:
Profile the production query, add an appropriate composite index, and use a deterministic chunk/claim strategy.

Dashboard executes seven small aggregate/first queries on each render. This is acceptable at low volume but should be measured/cached with explicit invalidation if frequently accessed. Immediate relation eager loading prevents obvious list N+1 queries.

## 17. Technical Debt Analysis

### Issue FB-COR-01

Priority:
P1

File:
`Modules/Facebook/Jobs/PublishFacebookPostJob.php`; `Modules/Facebook/Services/FacebookPublishingService.php`

Evidence:
`send()` marks retryable errors `failed` before rethrow; the job subsequently claims only `scheduled` or `queued`. Photo exceptions do not call `markFailed`, leaving `processing`.

Problem:
Queue retry and failure states contradict the job claim state machine.

Impact:
Automatic retries are no-ops, and photo failures may remain permanently processing.

Recommendation:
Define one transactional state machine. Keep retryable attempts claimable or explicitly requeue them; mark terminal failure in `failed()`/final-attempt handling; handle all post types consistently; recover stale processing records.

### Issue FB-COR-02

Priority:
P1

File:
`Modules/Facebook/Services/FacebookPostService.php`

Evidence:
`queueNow()` dispatches within the transaction without `afterCommit`.

Problem:
A worker may execute before the queued state commits.

Impact:
The job can observe stale state, return, and leave a queued post unpublished.

Recommendation:
Dispatch after commit or use an outbox, and add a race-focused integration test.

### Issue FB-COR-03

Priority:
P1

File:
`Modules/Facebook/Services/FacebookMediaService.php`; `Modules/Facebook/Services/FacebookPostService.php`

Evidence:
Updates append media; publishing selects the first photo; duplicate rows reuse the same storage path; soft delete/replacement never removes files.

Problem:
Photo replacement semantics and storage ownership/lifecycle are undefined.

Impact:
New uploads may be ignored, orphan files accumulate, and copies can share a file that future cleanup may remove unexpectedly.

Recommendation:
Define single/multi-photo behavior, atomically replace or order media, copy files for independent ownership where needed, and implement after-commit cleanup plus retention reconciliation.

### Issue FB-OPS-01

Priority:
P1

File:
`Modules/Facebook/routes/web.php`; `bootstrap/app.php`

Evidence:
The POST webhook uses `web`; no CSRF exclusion is configured.

Problem:
Meta delivery requests will fail CSRF validation.

Impact:
Webhook functionality is unavailable despite the route returning success when reached.

Recommendation:
Place the webhook in an intentionally stateless route/middleware boundary or narrowly exclude exactly this URI, while requiring signature verification and throttling.

### Issue FB-MAINT-01

Priority:
P2

File:
`Modules/Facebook/Models/FacebookPage.php`; `Modules/Facebook/Services/FacebookTokenMasker.php`; `Modules/Facebook/Config/config.php`

Evidence:
Masking is duplicated. `token_encryption` config is declared but ignored by unconditional encrypted casts.

Problem:
Duplicate and dead configuration paths invite drift.

Impact:
Operators may believe encryption is toggleable, and masking changes can diverge.

Recommendation:
Keep encryption mandatory, remove/rename the unused toggle, and centralize safe display formatting without broadly exposing decrypted tokens.

## 18. Test Coverage Analysis

Evidence: only three pure unit test classes cover error mapping, recursive redaction, and token masking. No module test bootstrap/factories were observed.

Missing regression coverage: route middleware and guards; Livewire allowed/denied actions; OAuth state/scope and transaction behavior with `Http::fake`; webhook CSRF/signature; Page ownership/sync; state transitions and concurrency; queue retry/final failure/after-commit; scheduler claims; media validation/storage/cleanup; encrypted serialization; model relationships/scopes; migration smoke tests and constraints; XSS-safe rendering and safe errors.

### Issue FB-TEST-01

Priority:
P1

File:
`Modules/Facebook/tests`

Evidence:
Only utility unit tests exist.

Problem:
Critical external publishing, authorization, and persistence behavior has no automated protection.

Impact:
Security and duplicate/missed-publish regressions can reach production undetected.

Recommendation:
Build a feature/service/job suite before behavioral refactoring, using fake HTTP, queue, storage, time, and admin permission fixtures.

## 19. Cross-Module Dependencies

Evidence: all page Blades extend `Admin::layouts.master`; routes authenticate `admin`; authorization relies on Spatie configuration usually owned by Role/Auth support. No Facebook class imports another domain module. The application-level `FacebookTestCommand` duplicates a module concern. There are no event/listener dependencies and no Shared dependency.

Inference: the direction `Facebook domain -> Admin shell view` violates the roadmap ideal that Admin composes domains. A future layout contract/shared shell component would reduce this inversion, but it is not urgent compared with correctness.

No circular module dependency was directly observed.

## 20. Documentation Drift

- `Modules/Facebook/README.md` says duplicate risk is mitigated by `idempotency_key`; evidence shows that key is only a local unique column and is not sent to Meta, so it cannot prevent a timeout-after-remote-success duplicate.
- The README says the Facebook log removes sensitive keys; this is true for `FacebookGraphClient` request payloads, not a universal log-channel feature.
- `FACEBOOK_TOKEN_ENCRYPTION` is documented but not used.
- Meta setup documentation says `facebook:test`; that command exists in `app`, not the module, and reads `services.facebook.*`, duplicating module config.
- Documentation describes a webhook but omits CSRF/signature limitations and media retention.

## 21. Module Health Score

| Dimension | Score | Rationale |
|---|---:|---|
| Architecture | 7/10 | Clear layers and ownership; a few app/shell inversions |
| Security | 4/10 | Good token hygiene, but publish authorization and webhook authenticity gaps |
| Correctness/data integrity | 4/10 | Retry, after-commit, photo state, and media lifecycle defects |
| Performance | 6/10 | Eager loading/chunking present; unbounded All and scheduler indexing remain |
| Operability | 5/10 | Queue/scheduler/logging documented; webhook and recovery semantics weak |
| Tests | 2/10 | Only three isolated utility tests |
| Documentation | 6/10 | Useful setup notes with material drift |
| **Overall** | **4.9/10** | Major refactor required before production reliance |

## 22. Final Recommendation

- [ ] Minor Refactor
- [x] Major Refactor
- [ ] Full Rebuild

The module should retain its current domain boundary, models, Graph client abstraction, DTO/error taxonomy, and general UI/service split. A full rebuild would discard useful structure. However, fixing capability checks, webhook trust/routing, job state transitions, transaction dispatch, media ownership/cleanup, database invariants, and comprehensive tests is beyond a minor refactor.

## 23. Open Questions

1. Is the Facebook integration intentionally singleton/system-wide, or owned by an admin/tenant?
2. Which permission should govern scheduling: create/update, publish, or a new `facebook.posts.schedule` capability?
3. Should photo posts support exactly one image or multiple images?
4. What is the required media retention policy after draft replacement, soft deletion, publication, and duplication?
5. What delivery events will the webhook process, and what replay/idempotency window is required?
6. Does the production queue connection guarantee `after_commit`, or must dispatch explicitly request it?
7. What is the acceptable response to uncertain Meta outcomes (remote success followed by timeout), given Meta endpoint idempotency limitations?
8. Should reconnect replace a singleton connection, preserve connection history, or scope connections per administrator?
