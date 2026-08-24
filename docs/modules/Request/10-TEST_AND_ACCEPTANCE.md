# Test and Acceptance Specification

## 1. Test strategy

Tests are organized by vertical behavior, not only by class. Each critical command needs happy path, authorization, invalid state, stale version, duplicate key, concurrency, audit/outbox, and failed side-effect evidence.

Use repository-standard PHPUnit/Laravel tooling. Static/architecture checks complement, not replace, feature tests. Production-like database behavior is required for lock/concurrency evidence.

## 2. Unit tests

- All enums and invalid value rejection.
- Public ID/request number/checksum/money/date value objects.
- Canonical JSON normalization and checksum stability.
- Form schema grammar, field validators, classification/offline rules, visibility grammar.
- Version validator: duplicate keys, invalid stages/positions/modes/resolvers/config/cardinality.
- Resolver registry, fixed users, role members, form user field, deduplication, self-removal, bounds.
- Request/run/task state-transition matrix.
- Stage evaluator for single/ALL/ANY every task outcome combination.
- Idempotency fingerprint/scope/result behavior.
- Audit redaction and event payload minimization.
- Export formula neutralization/field policy adapters.

## 3. Feature acceptance cases

### RT — Request type/version

- `RT-01` authorized designer creates group/type/draft; unauthorized user denied.
- `RT-02` invalid schema/stage/resolver yields structured path errors and no publication.
- `RT-03` publisher publishes valid version atomically; checksum/version/audit/outbox exist.
- `RT-04` published version cannot be edited/deleted.
- `RT-05` clone produces independent draft and diff.
- `RT-06` retire hides new creation but historical/running requests remain readable/actionable per policy.
- `RT-07` definition package dry-run/mapping creates draft only; malicious package rejected.

### RF — Form/draft

- `RF-01` eligible user discovers/creates; non-audience user cannot discover/create by direct URL/API.
- `RF-02` every field type normalizes/validates server-side.
- `RF-03` unknown/read-only/hidden invalid fields cannot alter authoritative payload.
- `RF-04` autosave honors optimistic revision and detects two-tab conflict.
- `RF-05` attachment reference must belong to request/user/field and be clean/private.
- `RF-06` draft cancellation is terminal and audited.

### RS — Submit/resubmit

- `RS-01` submit creates one payload revision/run/first stage in one transaction.
- `RS-02` schema, audience, type status, empty candidates, self-only candidates, or stale version fail with no partial rows.
- `RS-03` duplicate same idempotency key/fingerprint returns same safe result and one run.
- `RS-04` same key/different fingerprint conflicts.
- `RS-05` return/resubmit creates new payload revision/run, restarts stage one, retains type version/history.
- `RS-06` retired/pinned version behavior matches documented policy.

### AP — Approval

- `AP-01` two sequential single stages approve in order; later task absent until activation.
- `AP-02` `parallel_all` waits for all approvals then activates exactly one next stage.
- `AP-03` `parallel_all` reject/return cancels peers and reaches correct terminal/returned state.
- `AP-04` `parallel_any` first approval skips peers and advances once.
- `AP-05` `parallel_any` partial rejection leaves peers active; all reject makes request rejected.
- `AP-06` `parallel_any` return cancels peers and returns request.
- `AP-07` non-candidate/requester/old replacement candidate/wrong-stage user cannot decide.
- `AP-08` reason required for reject/return/reassign/cancel-any.
- `AP-09` later role stage resolves membership at activation and snapshots it.
- `AP-10` stage activation failure is visible/retry-safe and never skips/fabricates an approver.

### CC — Concurrency/idempotency

- `CC-01` same task double decision commits once.
- `CC-02` ANY concurrent approvals advance once.
- `CC-03` ALL final approvals concurrently advance once.
- `CC-04` ANY final reject versus approve yields one consistent outcome.
- `CC-05` decision versus reassignment/cancel yields one winner and safe conflict.
- `CC-06` duplicate job/outbox delivery creates no duplicate logical notification/event effect.

### CO — Collaboration/files

- `CO-01` visible authorized participant comments; unauthorized/terminal policy denied.
- `CO-02` comment XSS payload displays safely; immutable/redaction behavior proven.
- `CO-03` allowed private upload/download works and audits safely.
- `CO-04` oversized, executable, MIME-confused, path/archive attack is rejected/quarantined.
- `CO-05` direct storage/public URL and cross-request attachment IDOR fail.

### UI/PWA

- `UI-01` create/resume/review/submit at narrow phone width with no horizontal page scroll.
- `UI-02` inbox/detail/decision keyboard and screen-reader labels/focus/error association pass.
- `UI-03` designer stage reorder/config/validation/publish works on tablet landscape with touch and keyboard.
- `UI-04` offline cached reads are visibly stale/read-only; all mutation controls disabled.
- `UI-05` allowed local draft sync performs conflict check and never auto-submits.
- `UI-06` confidential fields/attachments are absent from offline stores.
- `UI-07` logout/account switch/revocation/expiry clears namespaced Request data.

### RE — Reports/exports/operations

- `RE-01` counts distinguish requests/runs/tasks under return/resubmit.
- `RE-02` record and field permissions constrain reports/export.
- `RE-03` export is private, audited, expiring, reauthorized and formula-safe.
- `RE-04` duplicate queued export/retry is idempotent.
- `RE-05` operation retry accepts only eligible allowlisted delivery/activation actions and not arbitrary jobs.

### AR — Architecture/release

- `AR-01` manifest type/dependencies/default state match approved bootstrap contract.
- `AR-02` scan finds no domain-module namespace/import/model/query/table dependency.
- `AR-03` module enabled/disabled and missing/disabled Shell dependency behavior fails safely.
- `AR-04` routes/resources/migrations/views/provider register exactly once through repository bootstrap.
- `AR-05` Workflow runtime tables/routes/code remain absent/deferred.

## 4. Performance/query acceptance

Seed production-like shapes agreed in `CREATE_PLAN.md`, including multiple types, versions, users, role candidates, requests, runs, tasks, comments, and audit events.

Verify:

- bounded response time budgets defined before implementation;
- query counts do not grow linearly with list rows;
- My Requests/inbox use documented indexes and no JSON scan;
- request detail paginates/bounds history and collaboration collections;
- role resolution enforces maximum candidates;
- large export queues rather than holding the web request;
- outbox batch locking does not starve business transactions.

No absolute millisecond target is invented in this analysis; `CREATE_PLAN.md` must record environment, dataset, percentile/budget, and measurement command.

## 5. Accessibility acceptance

- Automated scan has no critical violations on catalog, form, inbox, detail/decision, designer, operations.
- Complete critical journeys with keyboard only.
- Screen reader identifies page/field/task/status/action and announces validation/save/connectivity/conflict outcomes.
- Focus remains visible/restored across Livewire updates, validation, drawers, and dialogs.
- Contrast/reduced motion/zoom/reflow pass adopted standard.
- Color/icon are not sole status/decision cues.

## 6. Security acceptance

- Route/policy/visibility/field matrix proves deny-by-default and no IDOR.
- Mass-assignment/resolver/schema/operation injection fails.
- CSRF/Sanctum/rate limit and safe error contract pass.
- Audit/events/logs/notifications/cache/export contain no prohibited sensitive data.
- File/package/formula/XSS test corpus passes.
- Permission removal, user deactivation, role membership change, module disable, and stale offline data fail safely.

## 7. Operational acceptance

- Fresh install and upgrade/rollback procedure tested from supported predecessor.
- Migrations are safe for deployment strategy; rollback limitations for evidence tables are documented.
- Private storage directories/disks, ownership, capacity, backup, restoration, and cleanup verified.
- Queue workers/mail/database notifications/outbox retry/dead-letter visibility verified.
- Module disabled-by-default enablement runbook includes migrations, workers, storage, configuration, smoke test, and rollback/disable decision.
- Audit and historical request restore verified from backup sample.

## 8. Definition of done checklist

- Every traceability row has code/test/manual/operational evidence or an approved deferral outside v1.
- Zero open critical/high security defects.
- Zero ambiguous ownership or domain-module dependency.
- All MUST acceptance cases pass in CI/target database.
- Critical mobile/tablet/desktop journeys signed off.
- Vietnamese copy and identifiers/events/API schemas reviewed.
- Support/runbook/release-note changes prepared.
- Workflow remains explicitly deferred.

## 9. Not acceptable as proof

- Only happy-path browser screenshots.
- Sequential calls presented as concurrency testing.
- Controller/component tests that bypass database constraints or application service.
- Manual permission hiding without direct URL/API/IDOR tests.
- A mock resolver test without Shell membership-change scenarios.
- Public/local file existence without authorization/download/cache review.
- “Works offline” if it queues or replays a business mutation.
