# Workflow Master Specification v4.0 Ultimate

> **Status: DEFERRED — Request-first.** This master specification is retained for future architecture work. The accepted decision in `docs/modules/Request/ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md` makes Request v1 the current owner of internal requests and prohibits Workflow implementation until a superseding ADR is approved. Do not interpret the ownership statements below as permission to create overlapping runtime artifacts.

## 1. Product vision

Workflow is a reusable internal-request platform for one company. It combines low-code request forms, controlled process design, a reliable execution engine, human work queues, SLA operations, and an evidence-grade audit trail while remaining native to the repository's Laravel modular monolith.

The engine must be predictable before it is clever. Every accepted command must have one explainable outcome, every assignment must be traceable, every published process must be immutable, and every retry must be safe.

## 2. Architectural principles

1. Definition and execution are separate: design metadata never mutates a running instance.
2. Published versions are immutable; changes create new versions.
3. One command, one transaction, one authoritative result.
4. Side effects leave the database through a transactional outbox.
5. All asynchronous work is idempotent and observable.
6. Conditions are data, never executable code.
7. Authorization combines capability, record policy, assignment, ownership, and state.
8. Audit is append-only and independent from mutable presentation history.
9. Workflow owns internal requests only.
10. Dependency direction is enforced: Workflow may depend only on shell modules.
11. Offline convenience never changes authoritative business state; submit, decision, publication, upload, and operational commands require an authenticated online request.
12. Workflow extends the platform PWA shell through an approved shell contract; it never registers a competing manifest, service worker, or global cache policy.

## 3. Logical architecture

```text
Admin/API boundary
  -> application commands/queries
    -> definition, request, execution, task, SLA and audit services
      -> Workflow-owned models/tables
      -> transactional outbox
        -> queued notifications and projections
```

UI flow follows the repository standard:

```text
Route -> Controller -> Page Blade -> Livewire -> Service/Action -> Model -> Database
```

Controllers return pages/responses. Livewire owns UI state and validation. Services/actions own transactions and domain behavior. Blade never queries.

## 4. Bounded contexts inside the module

- Definition: definition identity, version, nodes, transitions, validation, publication.
- Form: schema, field catalog, payload validation, safe calculations.
- Request: draft data, submission, cancellation, recall, archive.
- Execution: tokens, joins, conditions, subflows, completion.
- Work: tasks, candidates, assignees, claims, decisions, delegation.
- Time: due dates, timers, reminders, escalation, expiration.
- Collaboration: comments and attachments.
- Evidence: audit events and exportable timelines.
- Delivery: outbox, notifications, API and operational projections.

These are internal boundaries, not separate Laravel modules.

## 5. Lifecycle summary

### Definition

```text
draft -> validated -> published -> retired
          |              |
          +-> draft      +-> duplicate as new draft version
```

Only a draft is editable. Publication records the validated checksum and atomically makes the version available for new requests.

### Request/instance

```text
draft -> submitted -> running -> approved -> completed
                         +-----> rejected
                         +-----> returned -> draft
                         +-----> cancelled/recalled when policy allows
```

`approved` means approval requirements are satisfied. `completed` means all required post-approval nodes and delivery work have reached their terminal domain state. A definition may collapse these moments when no post-approval work exists, but both concepts remain explicit.

### Task

```text
pending -> claimed -> completed
   |          |          +-> approved/rejected/returned outcome
   |          +-> pending (unclaim when allowed)
   +-> skipped/cancelled/expired
```

## 6. Capability baseline

- Form-driven internal requests.
- Sequential and parallel branches.
- Boolean conditions and deterministic default paths.
- User/Role candidate resolution with extensible resolvers.
- All/any/quorum approval policies.
- Delegation, reassignment, reminders, escalation and timers.
- Nested subflows limited to Workflow definitions.
- Comments, private attachments, notifications and timelines.
- Searchable inbox/archive and operational dashboards.
- Responsive, touch-friendly requester and approver workspaces for mobile browser, tablet, and installed PWA display modes.
- Online-first PWA support with app-shell caching, selected sanitized read snapshots, and non-sensitive offline draft recovery.
- Versioned REST API and domain event outbox.
- Import/export of definitions as validated packages in SHOULD scope.

## 7. Non-goals

The engine is not a generic code runner, integration bus, cron replacement, domain orchestration platform, or legal signature product. It must not accept executable scripts, model/service class names, SQL, shell commands, HTTP destinations, storage paths, or event class names from workflow definitions.

## 8. Bootstrap Contract

```text
Manifest          : Modules/Workflow/config/module.php
Type              : domain
Dependencies      : shell modules only; proposed Admin/Auth/User/Role/Shared
Module Provider   : not required unless CREATE_PLAN proves special bindings are needed
Config            : yes
Web routes        : yes
API routes        : yes
Migrations        : yes
Livewire          : yes
Blade components  : yes, only when reusable within Workflow
Console commands  : yes
Runtime state     : supported through platform abstraction
Runtime storage   : yes; private attachments/exports and bounded temp files
```

## 9. Definition of done

The module is not complete when screens merely work. It is complete only when definition immutability, graph validation, transactional execution, concurrency, retry safety, record authorization, storage protection, audit completeness, operational recovery, module discovery, runtime state, regression coverage, responsive/touch accessibility, safe PWA offline behavior, and UI smoke tests all pass.
