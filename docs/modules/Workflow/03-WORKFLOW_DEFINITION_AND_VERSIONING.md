# Workflow Definition and Versioning

> **DEFERRED — Request-first.** Retained as future analysis only; see `docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md`. Do not implement this surface while the ADR is active.

## 1. Definition package

A complete definition version is a canonical package containing:

```json
{
  "spec_version": "4.0",
  "definition": {"code": "leave-request", "name": "Leave Request"},
  "form": {"schema_version": 1, "sections": []},
  "workflow": {"start": "start", "nodes": [], "transitions": []},
  "policies": {},
  "localization": {"vi": {}},
  "metadata": {}
}
```

Canonicalization sorts object keys, normalizes enums/numbers, rejects unknown privileged keys, and excludes UI-only coordinates from execution checksum when appropriate. The stored checksum proves which executable definition an instance uses.

## 2. Draft and publication lifecycle

1. Create definition identity and draft version.
2. Edit metadata, form, graph and policies under optimistic concurrency.
3. Run validation; store structured errors/warnings.
4. Optionally simulate safe cases without persisting production instances.
5. Publisher reviews a diff against the current published version.
6. Publish inside one transaction:
   - lock definition and draft;
   - re-run validation;
   - canonicalize and calculate checksums;
   - mark version published and immutable;
   - update definition pointer;
   - create audit/outbox records.
7. New requests use the new published version; old drafts/instances remain pinned.

Publication requires `workflow.definition.publish`; editing permission alone is insufficient.

## 3. Validation pipeline

### Structural validation

- Exactly one start node.
- At least one end node.
- Unique node and transition keys.
- All edge endpoints exist.
- Nodes/configurations match registered schemas.
- No unreachable required nodes.
- No forbidden infinite paths.
- Parallel split/join compatibility.
- Subflow references exist and do not recurse.

### Behavioral validation

- Every non-terminal path can reach an end or explicit wait state.
- Conditions are type-safe and deterministic.
- Default path exists where multiple conditional paths are possible.
- Approval nodes have actor policy, quorum, outcome behavior, and SLA.
- Timer durations are bounded and valid.
- Return/reject/recall targets are valid.
- No task can be created with an impossible actor policy unless an approved fallback/escalation exists.

### Security validation

- No executable code or arbitrary class/service/model/table/path/URL/event/queue input.
- Resolver keys and node types are registry allowlisted.
- Form keys cannot shadow reserved context fields.
- Sensitive field metadata has explicit visibility/export policy.
- Notification templates use allowlisted variables and escaped rendering.

### Operational validation

- Queue/timer policies fit configured limits.
- Maximum nodes, edges, fields, subflow depth, fan-out, and payload size are enforced.
- Warnings flag excessive approval depth, broad Role assignment, missing SLA, and ambiguous fallback behavior.

## 4. Safe condition DSL

Conditions are JSON AST, for example:

```json
{
  "op": "and",
  "args": [
    {"op": "gte", "left": {"field": "amount"}, "right": {"value": 5000000}},
    {"op": "in", "left": {"context": "requester.role_codes"}, "right": {"value": ["employee", "manager"]}}
  ]
}
```

Allowlisted sources:

- `field:<published field key>`
- trusted context: requester ID/role codes, submission time, definition/version IDs
- prior Workflow node outcomes from the same instance
- safe constants

Disallowed sources include environment, secrets, filesystem, database queries, model properties, arbitrary context paths, HTTP, current mutable role membership during a pinned candidate decision, and dynamic function calls.

Evaluator semantics must define null, missing, numeric, currency, date/time, collection, locale, case, and timezone behavior. Any evaluation error follows the configured safe failure path, normally fail-closed and mark instance operationally failed rather than guessing an edge.

## 5. Node configuration contracts

### approval

- actor rule list
- candidate strategy and empty-candidate policy
- quorum type/value
- self-approval policy
- claim policy
- reject/return behavior
- due/reminder/escalation policy
- completion label/instructions

### manual_task

- actor rule
- required form fragment/checklist
- completion outcomes
- due/escalation policy

### condition

- ordered transition conditions
- mandatory default transition

### parallel_split / parallel_join

- branch identifiers
- matching join key
- join policy: all/any/count/percentage where semantically allowed
- remaining-branch cancellation policy

### timer_wait

- relative duration or allowlisted date field
- maximum/minimum boundaries
- overdue behavior

### notification

- allowlisted template ID or stored safe template snapshot
- recipients resolved through actor rules
- channels and failure policy

### subflow

- published target definition/version policy
- input/output field mapping
- maximum depth
- parent cancellation/failure propagation

### end

- outcome: approved/completed/rejected/cancelled as allowed by graph semantics

## 6. Version compatibility

- Existing versions never change behavior when registries gain new capabilities.
- Node type/evaluator versions are stored or derived from the definition spec version.
- Removing an evaluator/node handler is forbidden while published/running versions reference it.
- A compatibility matrix and migration tool are required before raising definition spec version.
- Definition package import must validate `spec_version`, checksums, supported capabilities, dependency rules, and collisions before creating a draft.

## 7. Simulation

SHOULD scope simulation executes the pure graph/evaluator against sanitized sample data. It must not create production tasks, notifications, files, audit events, outbox deliveries, or external calls. Simulation output includes visited nodes, chosen edges, candidate resolver summaries, timer estimates, validation warnings, and deterministic trace IDs.
