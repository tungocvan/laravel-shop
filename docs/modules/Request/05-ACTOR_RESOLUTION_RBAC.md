# Actor Resolution and RBAC

## 1. Principles

- Authentication, permission, record policy, and actor resolution are separate checks.
- Resolver keys/configuration are server-owned, typed, allowlisted, and versioned.
- Resolver output is candidate input; it never grants general request visibility by itself outside documented participant policy.
- Historical candidate evidence is snapshotted; current Shell membership is not used to rewrite history.
- Request depends only on Shell capabilities and never queries a domain module to find an approver.

## 2. Resolver interface contract

A Request-owned interface conceptually accepts:

- published resolver configuration;
- requester Shell User identifier;
- pinned normalized payload revision;
- request/type/stage identifiers and current trusted timestamp;
- a bounded authorization-aware Shell identity lookup contract.

It returns a typed result containing ordered user IDs plus safe source evidence and validation warnings. It may also return a structured failure code. It must be deterministic for the same trusted inputs and current Shell state at activation, side-effect free, bounded, and observable without logging private payloads.

The registry maps a stable key to a trusted implementation. No client-provided class/container binding is accepted.

## 3. Built-in resolvers

### 3.1 `fixed_users`

Configuration: bounded array of Shell User identifiers.

- Publication validates existence/visibility and warns about inactive users.
- Activation revalidates active status.
- Duplicates are removed.
- Missing/inactive users are excluded and cardinality rules then apply.

### 3.2 `role_members`

Configuration: exactly one Shell Role identifier plus optional safe ordering rule.

- Publication validates role existence.
- Activation queries active current members through Role/User Shell contracts.
- Output is bounded and deterministically ordered.
- A large/unbounded role is rejected according to configured maximum candidates.
- Membership is snapshotted per activated stage.

### 3.3 `form_user_field`

Configuration: exactly one field key whose pinned type is `user`.

- Publication validates the field exists, is user-valued, and is eligible for resolver use.
- Submit/activation reads only normalized server-validated payload.
- Missing/inactive/unauthorized value fails cardinality validation.
- Browser-supplied display name/email is ignored.

## 4. Reserved extension resolvers

`manager_of_requester`, `department_manager`, and `department_role` are reserved examples only. They are not registered in v1 and must not appear as selectable options.

Adding one requires:

1. A canonical organization capability owned by an approved Shell Module.
2. A new/updated ADR explaining dependency and failure semantics.
3. Typed contract and versioning rules.
4. Cycle/missing-manager/multiple-manager/temporary-manager behavior.
5. Authorization, cache, audit, load, and deactivation tests.
6. Updated manifest only if the dependency remains Shell; a domain dependency is forbidden.

Do not infer manager from arbitrary profile metadata, email domain, role name, creation user, or a business-domain table.

## 5. Candidate normalization

After resolver output:

1. Reject malformed identifiers and unknown source types.
2. Resolve active Shell users in a bounded query.
3. Deduplicate by stable user ID.
4. Remove requester.
5. Apply configured maximum candidate count.
6. Sort deterministically for stable task creation/display.
7. Enforce `single == 1`; other modes `>= 1`.
8. Store safe user/source snapshots.

Failure codes include `resolver_not_registered`, `resolver_config_invalid`, `user_unavailable`, `role_unavailable`, `candidate_limit_exceeded`, `self_approval_only`, and `candidate_set_empty`. User messages are localized and do not reveal unauthorized identities.

## 6. Permission and policy matrix

| Action | Named permission | Additional policy predicates |
|---|---|---|
| Discover/create type | `request.instance.create` | published/available, audience, active requester |
| Edit own draft/returned | `request.instance.update-own` | owner, editable state, pinned version rules, expected version |
| Submit/resubmit | `request.instance.submit` | owner, audience/grace policy, schema/actors valid, online |
| View own | `request.instance.view-own` | owner and not forbidden by retention policy |
| View participant | `request.instance.view-participant` | current/historical task participant as defined |
| View all | `request.instance.view-all` | admin scope and data classification |
| Decide | `request.task.decide` | active effective task/candidate, non-requester, online, expected version |
| Reassign | `request.task.reassign` | active stage allows, target valid, reason, expected version |
| Cancel own | `request.instance.cancel-own` | owner and status draft/returned |
| Cancel any | `request.instance.cancel-any` | authorized scope, eligible pending, reason |
| Comment/upload | respective permission | request visible, state permits, classification/limits |
| Download attachment | `request.attachment.download` | request/attachment visible and current authorization |
| Publish type | `request.type.publish` | valid draft, publisher scope, expected version |
| Audit/report/export | respective permission | field/classification/record scope and explicit filters |
| Retry operation | `request.operation.retry` | allowlisted retry type, failed state, idempotent, reason if required |

Super Admin bypass follows repository policy evaluation but does not suppress audit, idempotency, or state invariants.

## 7. Visibility scopes

- Owner: requester can see the full allowed request snapshot and history.
- Active approver: sees the minimum request context/fields allowed by type classification and task presentation policy.
- Historical participant: may retain read access only if configured by product policy; default recommendation is request summary and own decision, with confidential fields reauthorized.
- Administrator/auditor: named permission plus configured record/data scope; not automatic because a user can access admin shell.
- Comment mention/notification recipient: does not independently grant access.

Every query builder must apply a named visibility scope. Fetch-by-ID followed only by UI hiding is forbidden.

## 8. Resolver administration UX

- Stage editor lists only registered resolvers with plain-language behavior and requirements.
- User/role selectors are server-searched, bounded, keyboard accessible, and show inactive/unavailable states.
- A live “resolution preview” is advisory, shows timestamp/context, and cannot promise the activation result.
- Publication errors distinguish static invalid configuration from runtime-dependent warnings.
- Manager/department options are absent until actually registered.

## 9. Security tests

- Inject unregistered resolver/class/container names.
- Submit a user ID not visible/active/allowed.
- Change role membership between publication, submission, later stage activation, and decision.
- Resolve only requester, empty role, too-large role, duplicated users, deleted users.
- Attempt decision by non-candidate, old candidate after reassignment, candidate on non-current stage, or candidate from another request.
- Attempt IDOR through request/task/file/export public IDs.
- Verify cached reads and notifications do not widen access after role change.
