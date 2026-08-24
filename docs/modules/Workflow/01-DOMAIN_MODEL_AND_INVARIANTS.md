# Domain Model and Invariants

> **DEFERRED — Request-first.** Retained as future analysis only; see `docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`. Do not implement overlapping Workflow runtime ownership.

## 1. Aggregate map

### WorkflowDefinition

Stable business identity such as `purchase-request`. Owns name, category, ownership metadata, availability, and pointers to current draft/published versions. It does not contain mutable runtime state.

### WorkflowDefinitionVersion

Immutable after publication. Owns form schema snapshot, execution graph, policy snapshot, checksum, validation report, and publication evidence.

### WorkflowRequest

User-authored internal request. Owns request number, requester, definition/version reference, current status, payload, summary fields, and concurrency version.

### WorkflowInstance

Execution record for a submitted request. Owns current engine status, context snapshot, parent/subflow relationship, start/end timestamps, failure classification, and lock version.

### ExecutionToken

Represents active execution at a node/branch. Multiple tokens exist for parallel branches. Tokens are consumed exactly once and have deterministic lineage.

### WorkflowTask

Human work unit created from an approval/manual node. Candidate eligibility and actual assignment are stored separately so the engine can explain why a user could act.

### WorkflowDecision

Immutable outcome of a task action. Stores actor, acting-as/delegator context, outcome, reason/comment, request version, idempotency key, and timestamp.

### WorkflowDelegation

Time-bounded authority from one user to another, limited by roles/definitions/action scopes. Delegation never copies permissions and never bypasses task eligibility.

### WorkflowTimer

Durable scheduled work for wait, reminder, escalation, and expiration. It is leased and executed idempotently.

### WorkflowAuditEvent

Append-only evidence about who did what, to which aggregate, from which state, with correlation/causation identifiers and redacted metadata.

### WorkflowOutboxMessage

Durable transactionally-recorded event awaiting delivery to notifications, projections, or allowlisted internal consumers.

## 2. Core invariants

### Definition invariants

- Definition code is globally unique, stable, lower kebab-case, and never reused for another meaning.
- Version number is monotonically increasing per definition.
- At most one editable draft version per definition unless CREATE_PLAN explicitly adopts multiple named drafts.
- A published version is immutable at application and database policy boundaries.
- A published graph has exactly one start node and at least one reachable end node.
- Every reachable non-end node has a valid outgoing behavior.
- Every condition split has one deterministic default path.
- Every parallel split maps to a compatible join; unsafe cycles are rejected.
- Subflow references point only to published Workflow definitions and cannot form recursive cycles.

### Request invariants

- A request references exactly one definition version before submission and remains pinned thereafter.
- Only the requester or an authorized administrator may edit a draft.
- Submitted payload is validated against the pinned form schema.
- Request number is unique and generated server-side.
- Request payload cannot contain server-managed actor, status, audit, path, permission, or execution fields.
- Revision number increments on every mutable request update and is checked on concurrent commands.

### Execution invariants

- One request has at most one active root instance.
- Active token uniqueness is enforced per instance/branch/node occurrence.
- A token is consumed once; repeated consumption is a no-op or safe conflict.
- A node activation has a unique deterministic key.
- A parallel join fires once when its configured threshold is satisfied.
- Instance completion requires no active tokens and no blocking open tasks/timers.
- A failed engine transaction creates no partial tasks, tokens, decisions, or outbox messages.

### Task and decision invariants

- A task belongs to one instance, node occurrence, and request.
- Candidate snapshots are fixed when a task is activated unless the definition explicitly enables dynamic re-evaluation.
- Only an eligible candidate/assignee with backend permission can claim or decide.
- A task has at most one terminal decision.
- Approval quorum is computed from the activation snapshot, not from later role-membership changes.
- Reassignment/delegation preserves original candidate evidence and records the acting chain.
- Self-approval policy is explicit per definition; default is denied when requester equals approver.

### Audit/outbox invariants

- Audit rows are append-only through application code.
- Audit payloads are structured, size-bounded, redacted, and never store credentials or file bytes.
- Every committed state-changing command emits one command-level audit event.
- Domain events and their outbox rows are written in the same transaction as state changes.
- Outbox consumers are idempotent by event ID.

## 3. Value objects and enums

Required enums/value objects should include:

- DefinitionStatus: draft, published, retired.
- RequestStatus: draft, submitted, running, approved, completed, rejected, returned, recalled, cancelled, failed.
- InstanceStatus: pending, running, waiting, completed, rejected, cancelled, failed.
- NodeType: the allowlisted node types.
- TokenStatus: active, waiting, consumed, cancelled, failed.
- TaskType: approval, manual.
- TaskStatus: pending, claimed, completed, skipped, cancelled, expired.
- DecisionOutcome: approved, rejected, returned, completed, skipped.
- AssignmentType: user, role, resolver.
- QuorumType: all, any, count, percentage.
- TimerType: wait, reminder, escalation, expiration.
- OutboxStatus: pending, processing, delivered, failed, dead.
- RequestNumber and DefinitionCode value objects.
- CorrelationId, CausationId, IdempotencyKey, and ConcurrencyVersion value objects.

Enums are persisted as stable strings. Labels are presentation concerns and may be translated.

## 4. Domain services/actions

- DefinitionDraftService: create/duplicate/update draft.
- DefinitionValidationService: schema/graph/security validation.
- DefinitionPublicationService: checksum, immutable publish, retire.
- RequestDraftService: create/update/delete permitted drafts.
- RequestSubmissionService: validate, snapshot, submit, start instance.
- WorkflowExecutionService: advance tokens and activate nodes.
- ApprovalDecisionService: claim/approve/reject/return under lock.
- TaskAssignmentService: resolve/snapshot candidates and assignment.
- DelegationService: validate and manage delegation.
- TimerService: schedule/lease/execute due timers.
- RecallCancellationService: enforce recall/cancel policies.
- AuditService: append structured evidence.
- OutboxService: append delivery messages in the current transaction.
- WorkflowQueryService: bounded authorized lists and reports.

Avoid a single oversized `ApprovalEngineService`. Use a thin execution orchestrator with small explicit collaborators while preserving one transactional command boundary.

## 5. Domain exceptions

Use explicit safe exceptions such as:

- InvalidDefinitionException
- DefinitionNotPublishableException
- InvalidTransitionException
- StaleWorkflowVersionException
- TaskNotActionableException
- ActorNotEligibleException
- QuorumNotSatisfiedException
- IdempotencyConflictException
- ConcurrentWorkflowUpdateException
- ResolverUnavailableException
- UnsafeConditionException
- WorkflowOperationFailedException

Map these to stable UI/API errors. Never expose stack traces or raw database messages.
