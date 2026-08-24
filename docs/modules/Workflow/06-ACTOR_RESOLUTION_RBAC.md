# Actor Resolution, RBAC, and Visibility

> **DEFERRED — Request-first.** Retained as future analysis only; see `docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`. Do not implement this Workflow registry while the ADR is active.

## 1. Identity boundary

Workflow does not own users or roles. It references canonical identities exposed by shell modules and Spatie Permission conventions. Direct dependency on domain modules is forbidden.

Built-in resolver types:

- user: one or more explicit canonical user IDs
- role: users holding one or more canonical roles under the approved guard
- requester: the request owner
- requester_role: users in an allowlisted role related only by Workflow policy
- resolver: a server-registered `ActorResolverContract` key

Department/supervisor resolution is not implemented until a canonical shell-owned source exists. The contract exists so it can be added without changing published definition shape.

## 2. Resolver contract

Conceptual contract:

```text
supports(resolverKey, specVersion): bool
validate(configuration, definitionContext): ValidationResult
resolve(configuration, actorContext): ActorResolutionResult
describe(configuration): safe human-readable summary
```

Result includes resolved user IDs, source type/ID, resolver version, timestamp, and redacted evidence. Resolver output is bounded, deterministic for the supplied snapshot, and never grants permissions.

Registries map allowlisted keys to server-side implementations. Definitions cannot submit class names or container bindings.

## 3. Candidate snapshot policy

At task activation:

1. Resolve actor rules against trusted context.
2. Filter inactive/invalid identities.
3. Apply self-approval/separation-of-duty rules.
4. Store candidate source and resolved user snapshot.
5. Calculate quorum from that snapshot.
6. Create assignment/notification records.

Later role changes do not alter the snapshot by default. Definitions requiring dynamic re-evaluation must opt in, specify when it occurs, and preserve both old/new evidence.

## 4. Authorization layers

An action is allowed only when all applicable checks pass:

1. Correct authenticated guard.
2. Named capability permission.
3. Module enabled and route/action available.
4. Record visibility policy.
5. Request/task state precondition.
6. Candidate/assignee eligibility.
7. Delegation validity when acting for another user.
8. Separation-of-duty/self-approval policy.
9. Concurrency and idempotency checks.

Hiding a control in Blade is not authorization. Controller, Livewire, API, console, and job entry points must share policy/domain invariant behavior.

## 5. Visibility scopes

- own: requester can view own permitted requests.
- participant: user is current/prior candidate, assignee, decision actor, commenter, or explicitly included watcher.
- role_scope: permitted role plus policy-approved definition/request scope.
- all: privileged operator/auditor permission, still subject to sensitive-field redaction.
- operator: operational metadata without automatically exposing full request payload.

Attachment visibility is checked separately and never inferred only from knowing the file ID.

## 6. Permission catalog

Use capability names from `REQUIREMENTS.md`. Publication, broad viewing, reassignment, audit, report, operation retry, and settings are separate permissions. Avoid one catch-all `workflow.manage` permission except optional Super Admin composition.

Seeders must be deterministic and idempotent. Role assignment is not silently changed for existing production users; documentation lists recommended roles and administrators assign them deliberately.

## 7. Delegation

Delegation requirements:

- authenticated delegator or privileged manager
- delegate is active and not the same user
- explicit time range
- explicit scope: all eligible Workflow tasks, selected role source, or selected definition codes
- no permission elevation beyond both task eligibility and caller permission
- configurable maximum chain depth, default one
- no cycles
- original actor, effective actor, delegation ID, and reason recorded on every action
- revocation affects future actions, not history

Absence calendars and automatic delegation are SHOULD scope unless a stable shell source is available.

## 8. Separation of duties

Policies may prohibit:

- requester approving own request
- same user approving two designated incompatible stages
- delegate approving on behalf of a user when delegate already acted in a conflicting role
- publisher being sole designer/reviewer of a high-risk definition

Default is deny self-approval at approval nodes. Exceptions must be explicit, visible in validation/publish diff, and audited.

## 9. Dependency enforcement

Required automated checks:

- Read Workflow manifest and resolve every declared dependency; each must be `shell`.
- Scan Workflow PHP imports/references; reject `Modules\\<DomainModule>\\...`.
- Reject direct DB table references outside `wf_*` and approved canonical shell identity/permission tables/contracts.
- Reject definition configuration that names module/model/service classes.
- Ensure no domain module is required for Workflow module boot tests.
