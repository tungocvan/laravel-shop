# Facebook Module Rebuild Specification

## 1. Goal

Define the target design if the major refactor is implemented as a controlled internal rebuild. Preserve the current public admin workflows while guaranteeing action authorization, authenticated webhook ingress, durable publishing, bounded resource use, and recoverable media/state transitions. Justification: `ANALYSIS.md` findings `FB-SEC-01`, `FB-SEC-02`, `FB-COR-01` through `03`, `FB-PERF-01/02`, and `FB-TEST-01`.

## 2. Target Architecture

```text
Admin Route/Page -> Livewire Form/Table -> Authorization Policy
                                  -> Application Actions/Services
                                     -> DB transaction + Outbox
Outbox/Scheduler -> Queue Job -> Atomic Post State Machine -> Graph Gateway
                                                     -> Outcome persistence
Meta Webhook -> Signature Middleware -> Validated Event Handler -> Idempotency store
Media UI -> Media Service -> Private Storage -> retention/reconciliation job
```

Keep the Facebook module as canonical owner. Controllers remain thin. Livewire owns UI state only. Services/actions own invariants. A Graph gateway owns external HTTP. Policies own actor/record capability decisions. Justification: existing good separation plus Refactor Plan P0/P1 sections.

## 3. Database Design

- Retain connections, pages, posts, and post media as owned tables.
- Add explicit ownership/singleton scope after answering Open Question 1.
- Represent allowed lifecycle values with constrained strings or backed enums plus portable checks.
- Add a scheduler query index derived from production `EXPLAIN`, likely beginning with status and scheduled time.
- Add an outbox/dispatch record or equivalent after-commit strategy with unique post/operation identity.
- Add webhook event identity/hash, received/processed timestamps, status, and bounded sanitized metadata if events are processed.
- Define one-default-Page enforcement at the selected ownership scope.
- Resolve soft-delete uniqueness using restore/upsert semantics or an active-record key strategy.
- Keep token columns encrypted; never index plaintext tokens.

Justification: Database Analysis, `FB-PERF-02`, `FB-COR-02`, and `FB-SEC-03`.

## 4. Model Design

- `FacebookConnection`: ownership/singleton invariant, encrypted hidden token, Pages relation, explicit statuses.
- `FacebookPage`: encrypted hidden token, connection/posts relations, active/default domain methods.
- `FacebookPost`: enum-like type/status casts, transition methods/scopes, creator relationship contract, media relation.
- `FacebookPostMedia`: explicit ownership and lifecycle status; file deletion is service-driven after commit.
- Avoid accessors that duplicate the masking service or expose decrypted secrets incidentally.

Justification: Model Analysis and `FB-MAINT-01`.

## 5. Service Design

- OAuth service: URL/state/token/scope operations.
- Connection application service: external calls first, short persistence transaction second, explicit partial-failure result.
- Page service: scoped sync with reconciliation rules for missing/deleted Pages.
- Post command service: create/update/schedule/cancel/duplicate/delete with transition validation and actor context.
- Publishing orchestrator: claim, Graph call, success/retry/final-failure transition.
- Graph gateway: consistent timeout/retry policy by context, redaction, response DTOs; no browser-blocking sleeps beyond a small documented policy.
- Media service: validate ownership, store/replace/copy/quarantine/delete.

Every mutating entry receives an authorized actor or is invoked by a trusted system context and enforces domain invariants. Justification: Service Analysis and Refactor Plan P1.1-4.

## 6. Livewire Design

- Use typed state or Livewire Form objects.
- Validate/cap all filters and page sizes; remove `All`.
- Authorize every public method, including save, schedule, publish, retry, cancel, duplicate, delete, Page activation/default/verify.
- Separate draft save, schedule, and publish validation/permissions.
- Existing photo media satisfies photo validation unless replacement is explicitly requested.
- Keep query/business work in injected services and use explicit success/error result mapping.

Justification: `FB-SEC-01`, `FB-PERF-01`, and Livewire Analysis.

## 7. Blade/UI Design

- Continue escaped output by default.
- Render controls only when capability and record transition permit them.
- Add confirmation for external publish, disconnect, cancel, and delete.
- Show queued/processing/retry/final-failure states and correlation/trace references safely.
- Add `noopener noreferrer` to external targets.
- Use a stable shell layout contract rather than importing Admin internals when available.

Justification: Page Blade Analysis and Cross-Module Dependencies.

## 8. Import Design

No import is required. If bulk post import is later approved, it must extend the Shared import/export foundation, validate headers/rows/Page ownership, use private files, chunk/queue work, produce an error report, and define duplicate/idempotency and cleanup rules. This is a future requirement, not part of the rebuild.

## 9. Export Design

No export is required. Any future audit export must stream/chunk, enforce `facebook.posts.view` plus ownership, exclude tokens/raw sensitive payloads, store privately, and expire automatically.

## 10. Permissions And Authorization

- Retain existing granular capabilities.
- Add `facebook.posts.schedule` if scheduling is distinct; otherwise document that publish permission governs scheduling.
- Route middleware provides coarse access; policies/Livewire methods enforce actions and record scope.
- Webhook and workers use trusted-system authentication paths, never admin session assumptions.
- Super Admin bypass remains a project-level decision and must be covered by tests.

Justification: `FB-SEC-01/03` and Refactor Plan P0.1.

## 11. Transactions And Data Integrity

- Never hold a DB transaction across Graph network calls.
- Persist state transitions with conditional atomic updates/row locks.
- Dispatch after commit or through an outbox.
- Couple post/media row changes transactionally; perform file cleanup after commit with compensating reconciliation.
- Preserve creator identity on update and record the last editor separately if needed.
- Treat uncertain remote outcomes as `unknown/reconcile`, not immediately retryable.

Justification: `FB-COR-01/02/03` and Service Analysis.

## 12. Performance Strategy

- Cap normal pagination; use cursor/chunk processing for operations.
- Add scheduler and list indexes only after query-plan evidence.
- Debounce search and enforce maximum search length.
- Keep list eager loading and select only required columns where material.
- Cache dashboard aggregates only after measurement, with explicit invalidation or a short safe TTL.

Justification: `FB-PERF-01/02` and Refactor Plan P1.5.

## 13. Shared Foundation Integration

No Shared import/export integration is needed. Reuse framework queue, cache, storage, events, logging, and policy infrastructure. Extract a shared external-integration primitive only after a second module demonstrates a stable common contract.

## 14. Event And Listener Design

Define internal events after committed state changes: connection completed/disconnected, Pages synchronized, post scheduled/queued/published/retry-scheduled/failed, and authenticated webhook received. Listeners may audit, notify, invalidate dashboard cache, and emit metrics. Events must carry identifiers/safe DTOs, never access tokens.

Justification: absent event architecture and observability recommendations in Refactor Plan P2.

## 15. Queue Design

- Dedicated configurable queue.
- Unique work by post plus publish-operation generation, with lock duration exceeding worst-case execution/retry coordination.
- Atomic claim token and attempt record.
- Explicit retry classification; terminal failure only at exhaustion.
- Stale-processing sweeper and reconciliation queue.
- After-commit/outbox dispatch and deployment-safe worker draining.

Justification: `FB-COR-01/02`.

## 16. Cache Design

Use cache only for short-lived OAuth state/session, unique-job locks, rate coordination, and optionally measured dashboard summaries. Cache keys must include ownership scope. Role decisions must not be independently cached. Cache failure must not permit duplicate irreversible publishing.

## 17. Logging Strategy

Use the Facebook channel with correlation IDs, internal post/Page IDs, safe Meta error code/subcode/type/trace, duration, attempt, and transition. Redact recursively and never log tokens, secrets, authorization codes, raw webhook bodies, full personal content, or unbounded Meta responses. User messages remain bounded and translated; raw exception text goes only to redacted structured logs.

Justification: current redactor strength, Controller Analysis, and roadmap P1-12.

## 18. Monitoring Strategy

Track Graph latency/status/rate-limit, publish success/final failure, retry count, queue age, scheduled lateness, stale processing, invalid/expiring tokens, webhook signature failures/replay, worker heartbeat, and private media growth/orphans. Alert on sustained queue delay, final failures, signature spikes, and stale processing.

## 19. Rollback Strategy

Use additive schema changes and dual-readable statuses during migration. Pause scheduler, drain/stop old workers, deploy schema/application, migrate pending state, start new workers, then resume scheduler. Preserve database/media snapshots. Quarantine media before deletion. On publisher regression, stop dispatch and reconcile remote state before replay.

Justification: Refactor Plan Risk Control/Rollback Notes.

## 20. Test Strategy

- Unit: state machine, permission mapping, signature verification, redaction/error DTOs.
- Feature: route guard and every Livewire allowed/denied mutation.
- HTTP integration: OAuth/Graph success, malformed, rate-limit, network error, timeout-after-remote-commit simulations.
- Queue: after-commit, unique claim, retry/final failure, stale recovery, concurrent scheduler.
- Webhook: challenge, CSRF boundary, valid/invalid/missing signature, malformed/duplicate/oversize payload.
- Storage: upload MIME/size, replace, duplicate, cleanup, reconciliation.
- Database: fresh migration/rollback, constraints, reconnect/soft-delete/default concurrency.
- Performance: query-count and bounded-memory tests.

Justification: `FB-TEST-01` and Refactor Plan Test Strategy.

## 21. Deployment Checklist

- Confirm ownership, schedule permission, media semantics, retention, webhook events, and uncertain-outcome policy.
- Configure App ID/secret, redirect URI, verify token, scopes, Graph version, private disk, queue, log channel, and stable `APP_KEY`.
- Seed/assign permissions and test denied access.
- Run migrations and full test suite on production-equivalent MySQL/PHP.
- Register Meta redirect/webhook URL and validate signature flow.
- Start dedicated workers and scheduler; confirm after-commit behavior.
- Verify metrics, alerts, stale recovery, backup, and media quarantine.
- Perform sandbox Page publish/retry/reconciliation test before enabling production Page.

## 22. Implementation Checklist

- [ ] Characterization/security tests added
- [ ] Livewire action authorization enforced
- [ ] Ownership/singleton model implemented
- [ ] Webhook routing and signature verifier implemented
- [ ] Publishing state machine approved and implemented
- [ ] After-commit/outbox dispatch implemented
- [ ] Consistent text/link/photo failure handling implemented
- [ ] Stale/uncertain outcome reconciliation implemented
- [ ] Media replacement/ownership/cleanup implemented
- [ ] Pagination bounded and scheduler query profiled
- [ ] Database invariants added with reversible migrations
- [ ] Events/logging/metrics/alerts implemented
- [ ] Operations and Meta setup documentation updated
- [ ] Full test and deployment checklist passed

## 23. Needs Confirmation Before Coding

1. System-wide singleton versus admin/tenant ownership.
2. Capability governing scheduled publishing.
3. Exactly one photo versus multi-photo posts.
4. Media retention and recovery duration.
5. Supported webhook event types and business side effects.
6. Production database and queue connection/`after_commit` settings.
7. Policy for uncertain Meta outcomes and permitted manual reconciliation.
8. Whether connection history must be retained or reconnect replaces the active connection.
9. Whether Page disappearance during sync means deactivate, soft delete, or retain unchanged.
10. Required audit retention for publish attempts and webhook metadata.
