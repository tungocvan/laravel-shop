# Test Strategy and Acceptance Scenarios

## 1. Test layers

1. Static/architecture contracts.
2. Pure unit tests for graph, DSL, quorum, schema and value objects.
3. Service/action tests with database transactions and concurrency assumptions.
4. Feature/API/Livewire/policy tests.
5. Queue/timer/outbox/storage integration tests.
6. Module boot/runtime-state/migration tests.
7. Module regression, System regression when shared operations are touched, full project regression, frontend build and manual UI smoke.

Tests assert behavior and composed contracts, not whitespace or incidental implementation strings.

## 2. Architecture tests

- Workflow manifest type is `domain`.
- Every declared dependency resolves to `shell`.
- No Workflow PHP source imports a domain-module namespace.
- No `module.json`, nwidart, second registry, or manual global provider registration.
- Controllers contain no domain queries/transactions.
- Blade contains no model/service/database queries.
- Sensitive Livewire/API actions call backend authorization.
- No arbitrary eval, shell, SQL, class/model/service/event/queue/path/URL registry from definition input.

## 3. Definition tests

- Create/duplicate/edit draft succeeds with permission.
- Published version cannot be edited/deleted.
- Unique code/version constraints.
- Publication revalidates under lock and stores checksums/audit/outbox.
- Concurrent publish produces one valid published pointer.
- Invalid node/edge/type/config/condition/default/join/subflow/recursion is rejected.
- Unreachable node and path without terminal/wait are reported.
- New version does not change running instance behavior.
- Import package creates draft only and rejects unsupported/unsafe keys.

## 4. Form tests

- Every field type validates canonical good/bad/boundary values.
- Draft partial validation differs safely from submit validation.
- Reserved/duplicate/invalid field keys rejected.
- Hidden/required rules recomputed server-side.
- Computed fields ignore client value and recalculate.
- Formula cycles and unsafe operators rejected.
- Money precision/scale and date/timezone behavior deterministic.
- Sensitive values redacted from logs/audit/notifications/exports.
- Payload depth/size/field/option bounds enforced.

## 5. Execution scenarios

### Sequential

- Submit creates instance, token, first task and audit/outbox.
- Approve advances to next approval; final approval completes.
- Reject terminal behavior.
- Return to draft preserves prior evidence and supports valid resubmission.

### Conditional

- Each typed operator and null/missing behavior.
- Priority/default path deterministic.
- Multiple/no matches handled according to validation contract.
- Evaluation error fails closed and produces operator-visible code.

### Parallel/quorum

- All, any, count and percentage policies.
- Reject-fast, collect-all and threshold policies.
- Concurrent decisions resolve group once.
- Remaining tasks cancel/skip exactly as policy says.
- Retry does not duplicate join arrival/continuation.

### Timer/subflow

- Wait timer fires once across multiple workers.
- Reminder/escalation levels fire once.
- Parent waits for and resumes from child completion.
- Child rejection/cancel propagation follows policy.
- Recursion/depth limits enforced.

## 6. Concurrency and idempotency

- Same idempotency key/fingerprint returns same outcome.
- Same key/different fingerprint returns conflict.
- Two actors deciding one task produce one decision.
- Two users claiming one task produce one claimant.
- Stale request/task version returns conflict without mutation.
- Duplicate queued continuation/timer/outbox delivery is safe.
- Deadlock retry remains bounded and cannot duplicate audit/outbox.
- Unique activation keys prevent duplicate tasks/tokens/notifications.

Where the test database cannot model production locking, add MySQL integration coverage or document the limitation and verify constraints/algorithm separately.

## 7. Actor/RBAC tests

- User and Role resolution snapshots eligible identities.
- Empty/disabled users handled safely.
- Own/participant/all visibility matrix.
- Candidate versus non-candidate task access.
- Self-approval denied by default and explicit exception audited.
- Delegation valid/expired/revoked/scope/cycle/depth cases.
- Reassignment permission/target/reason requirements.
- Publisher separation from designer permission.
- Operator retry does not imply business payload view.

## 8. Security tests

- Unauthenticated/forbidden web/API/Livewire/console paths.
- Record enumeration and cross-user access.
- Mass assignment of status, actor, internal IDs, path, audit, permissions.
- Stored/reflected XSS through labels, fields, comments, filenames, templates.
- Malicious DSL/schema/import definitions.
- File path traversal, MIME spoof, size/count, unauthorized download.
- Rate limit/idempotency abuse.
- Safe error and log redaction.
- Audit mutation paths absent/tamper verification detects fixture change.

## 9. Queue/outbox/operations tests

- Jobs dispatch after commit; rollback dispatches nothing.
- Queue names come from trusted config.
- Retry/backoff/max attempts/dead state.
- Outbox lease concurrency and recovery after expired lease.
- Notification dedupe by event/user/channel.
- Stuck detector is read-only.
- Retry/resume registry accepts only known operation and typed ID.
- Cleanup respects active/legal retention and dry-run.

## 10. Query and performance tests

- Inbox/my requests/archive use bounded page sizes 10/25/50/100.
- Invalid page size normalizes safely.
- Search/filter changes reset page/selection.
- Query-count budgets prevent N+1 on lists/details/timeline.
- Candidate/inbox queries use expected indexes and bounded result sets.
- Timer/outbox processing chunk sizes and leases.
- Maximum graph/payload/fan-out/subflow depth prevents denial of service.
- Performance scenario for at least 100 concurrent approval attempts verifies correctness first; throughput target is established on production-like MySQL in CREATE_PLAN.

## 11. Module/runtime tests

- Root provider discovers Workflow routes/views/migrations/Livewire/commands.
- Manifest default state resolves.
- Runtime override ON/OFF works through abstractions.
- Shell-only dependency graph passes; injected domain dependency fails.
- Disabled dependency behavior is safe.
- Runtime toggle does not change manifest and Git remains clean.
- Fresh migrations and rollback on supported database.
- Queue manifest is discoverable through existing System conventions.

## 12. UI tests and manual smoke

- Menu/route/capability visibility.
- Definition designer validation, diff, publish, immutable state.
- Form rendering, draft autosave, submit, conflict, return/resubmit.
- Inbox filters/reset/pagination/claim/decision/stale state.
- Detail tabs, comments, attachments, timeline/audit redaction.
- Delegation create/revoke.
- Reports filters/export scope.
- Operations retry confirmation and feedback.
- Empty/loading/success/error/permission states.
- Desktop/mobile/keyboard/focus/contrast and no important console errors.

## 13. Release gate

Required sequence:

```text
Architecture/static PASS
-> targeted unit/service PASS
-> feature/API/Livewire PASS
-> queue/storage/MySQL integration PASS where applicable
-> Workflow regression PASS
-> System regression PASS when touched
-> full project regression PASS
-> Pint/static checks/frontend build PASS
-> UI smoke PASS
-> clean Git status
```

No skipped critical test, flaky concurrency result, unresolved authorization issue, or undocumented environment limitation may be called PASS.

