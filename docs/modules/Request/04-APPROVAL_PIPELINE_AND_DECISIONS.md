# Approval Pipeline and Decisions

## 1. Execution model

An approval run is an ordered list of published stage definitions. Only one stage position is active. Activating a stage resolves users and creates tasks/candidate evidence in one transaction. Completing it activates the next stage or approves the request.

There are no edges, gateways, conditions, loops, timers, or subflows in v1.

## 2. Stage modes

### 2.1 `single`

- Resolver must return exactly one eligible non-requester user.
- One active task is created.
- Approve completes the stage; reject terminates the request; return returns it.

### 2.2 `parallel_all`

- Resolver returns one or more eligible non-requester users.
- One task is created per deduplicated user in the same activation transaction.
- Stage completes only when every task is approved.
- A valid reject terminates the request immediately.
- A valid return returns the request immediately.
- Remaining tasks become cancelled after reject/return.

### 2.3 `parallel_any`

- Resolver returns one or more eligible non-requester users.
- First committed approval completes the stage; all other active tasks become skipped atomically.
- A reject closes only that actor's task while another active candidate remains.
- When the final actionable task rejects and none approved, the request is rejected.
- A return returns the request immediately and cancels other active tasks.

This gives Base Request-like ANY behavior without ambiguous “first action wins” rejection.

## 3. Stage activation algorithm

Under the run/request transaction lock:

1. Load the pinned stage definition by next position.
2. Resolve the registered resolver against trusted context.
3. Normalize, deduplicate, and verify active Shell users.
4. Remove requester and unauthorized/unavailable users.
5. Enforce cardinality for the stage mode.
6. Create task/candidate snapshots with stage and resolver metadata.
7. Set run current stage and activation timestamps.
8. Append one audit event and minimal outbox events.

Failure creates no partial task set. At initial submit, failure rolls back submission. At a later-stage transition, the deciding transaction must not lose the valid completed decision. Recommended behavior: record decision/stage completion and transition the run to a safe operational `failed_activation` condition inside the same aggregate status model or an explicit failure marker while request remains non-terminal; operators fix only resolvable Shell data and invoke an allowlisted idempotent activation retry. `CREATE_PLAN.md` must choose the precise representation and UI, but it may not skip the stage or fabricate a candidate.

## 4. Decision command contract

Input:

- task public ULID;
- decision enum;
- reason when required and optional bounded comment for approve;
- expected task/request optimistic version;
- idempotency key;
- authenticated actor and correlation context from server.

The service:

1. Authorizes route capability and record policy.
2. Opens transaction and acquires locks in canonical order.
3. Loads/claims idempotency record.
4. Revalidates module, request/run/stage/task state and candidacy.
5. Appends immutable decision and updates the task.
6. Evaluates stage outcome and applies next activation/terminal transition atomically.
7. Advances optimistic version, appends audit/outbox, stores safe idempotent result.
8. Commits; delivery side effects occur after commit.

No controller, Livewire component, API resource, mail handler, or job may update task/request status directly.

## 5. Outcome table

| Current mode | Decision | Stage effect | Request/run effect |
|---|---|---|---|
| `single` | approve | complete | next stage or approved |
| `single` | reject | terminate | rejected |
| `single` | return | terminate current run | returned |
| `parallel_all` | approve | wait until all approved | next stage/approved when complete |
| `parallel_all` | reject | cancel peers | rejected |
| `parallel_all` | return | cancel peers | returned |
| `parallel_any` | approve | skip peers, complete | next stage or approved |
| `parallel_any` | reject | close actor task; wait if peers remain | rejected only when all reject |
| `parallel_any` | return | cancel peers | returned |

## 6. Return/resubmit behavior

- Return reason is shown prominently to requester and in the run timeline.
- Returned form starts from the last payload revision but saves into a new mutable draft state/revision path.
- Pinned version does not change.
- Resubmit cannot reuse a prior run or task.
- All stages restart in v1. Selective restart at the returning stage is future scope because it complicates validity when data changes.
- Notifications clearly identify run sequence to avoid acting from a stale email/link.

## 7. Reassignment

- Reassignment is available only where stage definition allows it and policy grants it.
- Actor selects one active Shell user visible under the approved user selector policy and supplies a reason.
- It creates a replacement task linked to the original; old task becomes `reassigned`.
- Stage completion evaluates only current effective tasks.
- Reassignment cannot target requester, revive a terminal run, or change definition/resolver/stage mode.
- Notifications to old/new assignees are after-commit and idempotent.

Forwarding, delegation date ranges, proxy acting, and ad-hoc insertion are not synonyms for reassignment and are not in v1.

## 8. Concurrency scenarios

Required deterministic outcomes:

- Two approvals of the same task: one decision commits; the other returns original idempotent result or stale conflict.
- Two ANY approvers approve simultaneously: one completes stage; the other becomes a safe conflict and its task is skipped.
- ALL last two approvals simultaneously: exactly one transaction activates the next stage/final approval.
- ANY final rejection races with another approval: database commit order plus locks yields either approved or all-rejected, never both; only committed valid state is exposed.
- Reassignment races with decision: one wins; no replacement plus decision both become effective.
- Cancel races with decision: one terminal transition wins; the other fails safe.
- Duplicate submit/resubmit: one run/payload revision only.
- Outbox/job redelivery: no repeated business state transition or duplicate logical notification.

Tests must use separate database connections/processes where necessary; sequential test calls are not sufficient proof.

## 9. Stale and offline behavior

- Task UI displays current server revision and revalidates immediately before confirmation.
- An email/PWA cached task may be stale; opening it refreshes online before enabling actions.
- Offline decision controls are disabled, never queued locally.
- A conflict response returns safe current status and a refresh action, not raw internals.
- Success is displayed only after authoritative server commit.

## 10. Notifications

Minimum triggers:

- request submitted/resubmitted;
- task activated/reassigned;
- request approved/rejected/returned/cancelled;
- comment/attachment activity where configured and authorized;
- delivery/export failure only to permitted operators.

Notification content is versioned, localized, minimal, and links to an authorization-protected route. It never contains confidential field values by default.
