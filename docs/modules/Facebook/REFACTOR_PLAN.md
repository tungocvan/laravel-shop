# Facebook Module Refactor Plan

## Executive Summary

Retain the module boundary and layer structure, but harden authorization and webhook ingress first, then repair the publishing state machine and media lifecycle, and finally optimize/query-test the module. This plan implements the **Major Refactor** recommendation from `ANALYSIS.md` and aligns with roadmap P0-05, P0-06, P1-04, P1-07, P1-09, P1-10, and P1-12.

## P0 Critical Fixes

### 1. Enforce action-level authorization

Basis: `FB-SEC-01`.

- Authorize every public Livewire mutation at invocation time.
- Require `facebook.posts.publish` for immediate publishing and retry.
- Confirm and enforce the capability for scheduling.
- Add record policies/ownership scoping if the integration is not intentionally global.
- Hide unavailable controls for UX, while preserving server enforcement.
- Add allowed and denied Livewire tests for every capability.

### 2. Authenticate webhook deliveries

Basis: `FB-SEC-02` and `FB-OPS-01`.

- Create a narrow stateless or CSRF-exempt webhook boundary.
- Verify `X-Hub-Signature-256` over the exact raw request body using the App Secret and constant-time comparison.
- Reject absent/invalid signatures before parsing or logging event data.
- Validate supported object/event shapes, limit payload size, and add replay/idempotency handling.
- Keep verify-token challenge handling separate from delivery authentication.

## P1 Important Refactors

### 1. Define a durable publishing state machine

Basis: `FB-COR-01`.

- Specify allowed transitions and terminal/retryable outcomes.
- Atomically claim work, consistently handle text/link/photo exceptions, and keep retryable work claimable.
- Mark final failure only after retry policy is exhausted.
- Add stale-processing recovery and operational visibility.
- Preserve remote identifiers before any secondary enrichment.

### 2. Dispatch only after commit

Basis: `FB-COR-02`.

- Use after-commit dispatch or an outbox for immediate and scheduled publishing.
- Ensure scheduler claims and dispatch failures are recoverable/idempotent.
- Add race and duplicate-dispatch tests.

### 3. Shorten transaction boundaries

Basis: Service Analysis.

- Keep Graph network calls outside DB transactions.
- Persist connection and synchronized Page changes in short transactions with explicit compensation/status if the external call succeeds but persistence fails.
- Replace HTTP `abort_*` in domain services with explicit domain exceptions/results.

### 4. Repair media lifecycle

Basis: `FB-COR-03`.

- Decide single-photo versus multi-photo semantics.
- Make replacement select the intended asset and avoid append-only ambiguity.
- Give duplicates independent storage ownership or formal reference counting.
- Delete superseded/orphaned files after commit and add scheduled reconciliation/retention.
- Preserve existing media when editing a photo without uploading a replacement.

### 5. Bound and profile queries

Basis: `FB-PERF-01` and `FB-PERF-02`.

- Remove `All`; validate/cap `perPage` and filters server-side.
- Add a scheduler-oriented composite index after `EXPLAIN` against production MySQL.
- Make chunk ordering deterministic.
- Add query-count/budget tests for dashboard, Page list, post list/detail.

### 6. Strengthen database invariants

Basis: Database Analysis.

- Decide how to enforce one default Page at the correct ownership scope.
- Add validated status/type transitions and database checks where portable.
- Define actor foreign-key strategy without violating module ownership.
- Resolve soft-delete uniqueness and reconnect behavior.
- Add fresh-migration and rollback smoke tests on supported databases.

### 7. Establish integration coverage

Basis: `FB-TEST-01`.

- Cover routes/guards/permissions, OAuth, Graph mapping, Page sync, Livewire CRUD/actions, queue retries, scheduler, webhook signatures, encryption/serialization, storage cleanup, and migrations.
- Use `Http::fake`, `Queue::fake`, `Storage::fake`, frozen time, and permission fixtures.
- Include uncertain remote-outcome and stale-processing scenarios.

## P2 Nice To Have Improvements

- Consolidate token masking and remove the unused encryption toggle (`FB-MAINT-01`).
- Move `FacebookTestCommand` into the module and reuse module config/client contracts.
- Add typed Livewire properties/form objects and debounced search.
- Add domain events for connection, Page sync, publish success/failure, and webhook receipt.
- Add structured metrics/alerts for queue latency, API latency/rate limits, retry exhaustion, stale processing, token expiry, and storage growth.
- Add `rel="noopener noreferrer"` to external links and capability-aware controls.
- Decouple the domain UI from the concrete Admin layout when a stable shell contract exists.

## Recommended Implementation Order

1. Add characterization and denied-access tests around current routes and Livewire actions.
2. Fix action authorization and deploy as the first containment release.
3. Add webhook signature tests, then correct CSRF/stateless routing and authentication.
4. Specify publishing transitions and retry policy in an architecture decision record.
5. Refactor queue claim/failure/final-attempt handling and after-commit dispatch.
6. Refactor connection transaction boundaries.
7. Decide and implement media semantics, cleanup, and retention.
8. Add database constraints/indexes with production query evidence and migration rollback tests.
9. Remove unbounded list mode and add query budgets.
10. Consolidate config/commands/masking, events, metrics, and documentation.

## Files Change Matrix

| Area | Expected files | Purpose |
|---|---|---|
| Authorization | `Livewire/Posts/Form.php`, post/page Blades, new Policies/tests, manifest if scheduling permission added | Enforce capabilities |
| Webhook | `routes/web.php` or dedicated API route, `FacebookWebhookController.php`, config, tests | CSRF boundary, signature, schema/idempotency |
| Publishing | `PublishFacebookPostJob.php`, `FacebookPublishingService.php`, `FacebookPostService.php`, model/tests | State machine, retries, after-commit |
| Transactions | `FacebookConnectionService.php`, `FacebookPageService.php` | Avoid external I/O in transactions |
| Media | Form/rules, `FacebookMediaService.php`, post/media models, cleanup job/tests | Replacement, ownership, retention |
| Database | posts/pages/media migrations (new forward migrations only), tests | Constraints and indexes |
| Performance | Page/Post/Dashboard services and Livewire lists | Bounds, query budgets, cache if justified |
| Operations | commands, events/listeners, logging/monitoring config, docs | Recovery and observability |

## Risk Control

- Freeze the current state/status vocabulary in characterization tests before changing it.
- Roll out capability changes with a permission seeder/migration plan so existing roles do not unexpectedly lose required access.
- Do not acknowledge webhook events until authentication passes; initially log metadata only.
- Feature-flag the new publisher worker or use a dedicated queue during transition; never run old and new claim logic concurrently without compatibility proof.
- Back up Facebook tables and inventory stored media before lifecycle cleanup.
- Treat uncertain remote outcomes as reconciliation work, not blind retry.

## Test Strategy

- Unit: error mapping, redaction, state transition rules, signature verifier, DTOs.
- Feature: every route and Livewire action across unauthenticated/denied/allowed/Super Admin cases.
- Integration: OAuth and publishing with fake HTTP sequences, including timeouts/rate limits and malformed responses.
- Queue: after-commit visibility, unique jobs, retryable versus terminal failure, stale processing, scheduler concurrency.
- Storage: validation, replacement, duplicate ownership, soft-delete retention, cleanup/reconciliation.
- Database: fresh migrate/rollback, unique/default/reconnect behavior, query plans or query-count budgets.
- UI: escaped external content, permission-aware controls, existing-photo edit.

PHP was unavailable during analysis, so the implementation phase must run the full Laravel test suite in a configured runtime.

## Rollback Notes

- Authorization/webhook containment changes are independently reversible, but do not roll back to unauthenticated publishing or webhook processing in production.
- Use additive forward migrations; keep old columns/status compatibility until all workers are drained and deployed.
- Pause scheduler and Facebook queue, drain/inspect in-flight jobs, deploy state-machine changes, then resume.
- Preserve a database snapshot and media inventory. File deletion should begin in report-only mode, then use a recoverable quarantine period.
- If the new publisher fails, disable dispatch, retain queued records, and replay only after confirming remote status to avoid duplicate Facebook posts.
