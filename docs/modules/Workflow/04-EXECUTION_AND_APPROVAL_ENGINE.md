# Execution and Approval Engine

## 1. Command boundary

Every state-changing operation is an explicit command such as:

- SubmitRequest
- ClaimTask
- ApproveTask
- RejectTask
- ReturnTask
- CompleteManualTask
- RecallRequest
- CancelRequest
- ReassignTask
- CreateDelegation
- FireTimer
- RetryFailedInstance

A command handler performs authentication/authorization at the caller boundary and domain invariants inside a transaction. It records audit/outbox data before commit and returns a stable result DTO.

## 2. Transaction algorithm

For request/task commands:

1. Begin database transaction with bounded retry for deadlocks.
2. Resolve idempotency record and compare request fingerprint.
3. Lock the authoritative request, instance, task/token rows in deterministic order.
4. Re-check status, concurrency version, actor eligibility, delegation, and policy.
5. Apply exactly one domain decision/mutation.
6. Advance the engine until it reaches human work, timer wait, subflow wait, terminal state, or configured synchronous-step budget.
7. Persist tasks/tokens/timers using unique activation keys.
8. Append audit events and outbox messages.
9. Store idempotent response snapshot.
10. Commit.
11. Dispatch outbox/queued work after commit.

No notification, mail, file generation, or external delivery occurs inside the state transaction.

## 3. Engine loop

The engine processes active tokens deterministically by stable order. Each node handler returns a typed result:

```text
Advance(edges)
WaitForTasks(task specifications)
WaitUntil(timer specification)
WaitForSubflow(child specification)
Complete(outcome)
Fail(code, safe context)
```

Handlers are registered server-side. Definition data supplies only validated configuration, never a handler class.

The synchronous loop has maximum step/depth/fan-out limits. When exceeded safely, continuation is queued with a unique continuation key.

## 4. Sequential approval

Activation snapshots candidates, creates task/group, and pauses the token. A valid terminal decision records evidence and advances to the configured edge. Empty candidate behavior must be one of:

- fail activation and alert operator (default)
- use a preconfigured fallback resolver
- skip only when the definition explicitly permits and validation warns

Silent auto-approval is forbidden.

## 5. Parallel approval and quorum

Parallel approval creates a quorum group with candidate/task snapshot. Supported policies:

- all: every required task approves
- any: first approval satisfies the group
- count: at least N approvals
- percentage: ceiling of candidate count × percentage

Rejection policy is independently configured:

- reject_fast: first valid rejection resolves group and cancels remaining tasks
- collect_all: wait for all/threshold before calculating outcome
- reject_threshold: reject after N/percentage rejections

Group resolution occurs under lock, records the counted decisions, cancels/skips residual tasks according to policy, and advances exactly once using a unique group-resolution key.

## 6. Conditional routing

Outgoing conditions are evaluated in stored priority order against the pinned payload/context. Exactly one edge must win unless the node explicitly supports multi-branch fan-out. If no condition matches, the validated default edge is used. Multiple matches without declared priority/default are invalid at publication.

Evaluation failure never means false silently. It creates a safe engine failure code and operator-visible incident.

## 7. Parallel split/join

- Split creates one child token per configured branch with deterministic keys.
- Join arrivals are unique and tied to split activation.
- Join policy is evaluated under lock.
- Once satisfied, the join creates one continuation token.
- Remaining branches are cancelled only when policy explicitly allows.
- A retry cannot create duplicate continuation tokens or double-count arrival.

## 8. Return, reject, recall and cancel

### Reject

Creates immutable decision/audit evidence and follows the definition rejection policy. Default v1 outcome is terminal `rejected`; a definition may route to a controlled return/revision path instead.

### Return

Cancels relevant open tasks/timers, changes request to `returned`, identifies the allowed revision target, and preserves all history. Resubmission starts a new execution attempt or continues according to an explicit versioned policy; it never overwrites prior decisions.

### Recall

Allowed only by requester/authorized operator when policy and current state permit. Default compatibility rule is before the first completed approval. Recall cancels active work, records reason, and returns request to draft without deleting history.

### Cancel

Available only in configured states. It cancels tokens/tasks/timers/subflows atomically and records the actor/reason. Completed/rejected records cannot be silently cancelled.

## 9. Delegation, claim and reassignment

- Delegation does not grant global permissions; it permits acting only on otherwise eligible task scope.
- Candidate resolution records original actor source and effective acting chain.
- Delegation cycles, expired delegation, excessive chain depth, and self-delegation are rejected.
- Claim uses compare-and-set/row lock so only one user succeeds.
- Reassignment requires explicit permission, eligible target, reason, and audit; it does not rewrite prior decisions.
- Role membership changes do not retroactively alter a task snapshot unless dynamic candidate policy was explicitly published.

## 10. Timers and SLA

Timers are durable rows, not in-memory delays. A bounded command leases due timers, fires them idempotently, and releases/marks failures safely. SLA rules may create reminders, escalate candidate/assignee, reassign, expire, or alert operators. Deadline recalculation after return/resubmission follows versioned policy.

## 11. Subflows

Subflows target only published Workflow definitions. Parent passes an allowlisted mapped payload; child has its own request/instance identity linked to the parent. Completion/failure/cancellation propagation is explicit. Maximum depth and recursion validation prevent loops. No subflow may target a domain module.

## 12. Failure and recovery

Failure classes:

- business rejection: expected terminal outcome
- validation failure: command rejected with no mutation
- concurrency conflict: safe retry/reload response
- resolver unavailable/empty: instance waiting/failed for operator action
- definition defect: fail closed with version/node evidence
- transient delivery/job failure: retry outbox/job without replaying decision
- poisoned message: move to dead state after bounded attempts

Operational retry requires permission, confirmation, audit, and the same idempotent handler. Operators can retry delivery or resume from a recorded safe point; they cannot edit tokens/tasks directly through UI.

## 13. Performance boundaries

- All inbox/archive queries are indexed and paginated.
- Candidate expansion, fan-out, graph size, payload, comments, attachments, and audit metadata are bounded.
- Engine step budget prevents runaway synchronous execution.
- Due timers/outbox are processed in chunked leased batches with skip-locked or equivalent safe strategy.
- Reports use indexed queries or rebuildable projections, never unbounded hydration.

