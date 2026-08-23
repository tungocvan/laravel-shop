# AI Implementation Contract

## 1. Purpose

This document tells an AI/developer how to transform the approved Workflow v4.0 specification into a production-ready module without inventing architecture, bypassing approval gates, or generating a monolithic unsafe implementation.

It is not the CREATE_PLAN and does not authorize code. First run:

```text
/create-module Workflow
```

Create `docs/modules/Workflow/CREATE_PLAN.md`, present it, and stop for approval.

## 2. Mandatory reading

Before planning and before each implementation phase, read:

- `.codex/bootstrap/CODEX_BOOTSTRAP.md`
- `.codex/bootstrap/PROJECT_BOOTSTRAP.md`
- `.codex/bootstrap/AI_PROJECT_CONTEXT.md`
- `.codex/standards/MODULE_STANDARD.md`
- `.codex/standards/ADMIN_UI_STANDARD.md`
- `.codex/tasks/create-module.md`
- `Modules/ModuleServiceProvider.php`
- `app/Modules/ModuleStateRepository.php`
- `app/Modules/ModuleStateResolver.php`
- `config/modules.php`
- `ROADMAP.md`
- every document in `docs/modules/Workflow/` in README order

Inspect current source immediately before edits; repository reality outranks examples here.

## 3. Non-negotiable constraints

- Laravel 12, PHP 8.3, Livewire 3, first-party `Modules/` architecture.
- Namespace `Modules\\Workflow`.
- No nwidart, `module.json`, second module registry, or duplicate registration.
- Module type `domain`.
- Manifest dependencies only on modules that resolve to `shell`.
- No import/reference/query of any domain module.
- Workflow owns internal requests only.
- No full BPMN, multi-tenancy, PKI signature, domain-model polymorphism, arbitrary webhooks or code execution.
- Thin Controller/Livewire; business behavior in services/actions.
- Transactions, locking, idempotency, outbox and audit for state changes.
- Private files and backend authorization.
- Published versions immutable.
- No application code before CREATE_PLAN approval.

## 4. Proposed module structure

CREATE_PLAN must confirm exact files, but the intended responsibility map is:

```text
Modules/Workflow/
├── Actions/
│   ├── Definitions/
│   ├── Requests/
│   ├── Tasks/
│   └── Operations/
├── Contracts/
├── DTOs/
├── Enums/
├── Events/
├── Exceptions/
├── Http/Controllers/
├── Jobs/
├── Livewire/
├── Models/
├── Policies/
├── Services/
│   ├── Definition/
│   ├── Execution/
│   ├── Form/
│   ├── Assignment/
│   ├── SLA/
│   ├── Audit/
│   └── Query/
├── Support/
│   ├── Conditions/
│   ├── Nodes/
│   └── Registries/
├── Console/
├── config/
├── database/migrations/
├── database/seeders/
├── resources/views/
├── resources/lang/
└── routes/
```

Do not generate empty folders/classes only to match this tree. Use cohesive small classes and avoid premature abstraction.

## 5. Required implementation phases

### Phase 0 — CREATE_PLAN and contracts

- Verify module does not exist.
- Inventory reference modules and current shared shell contracts.
- Finalize manifest dependency allowlist.
- Finalize schema, permissions, public route/API contracts, queues, storage and test matrix.
- Enumerate exact files and migrations.
- Obtain approval.

### Phase 1 — Skeleton/bootstrap/security baseline

- Manifest, config, routes, permissions, policies, enums/value objects, module boot tests.
- No business UI beyond minimal authorized route proof.
- Verify runtime ON/OFF and Git-clean behavior.

### Phase 2 — Definition/form persistence

- Definition/version/node/transition schema and models.
- Safe form schema/condition registries and validation.
- Draft/publication immutability, checksums, diff DTOs.
- Unit/service/security tests.

### Phase 3 — Request/execution foundation

- Request/instance/token schema.
- Submission transaction, deterministic node loop, activation keys.
- Sequential/conditional/end nodes first.
- Audit/outbox/idempotency from the beginning.

### Phase 4 — Human work and concurrency

- Tasks, candidates, assignments, decisions.
- User/Role resolver registry, policy matrix, claim/decision.
- Parallel/quorum/join, return/reject/recall/cancel.
- Real concurrency/locking tests.

### Phase 5 — SLA/delegation/subflows/notifications

- Delegation and reassignment.
- Durable timers, reminder/escalation, subflows.
- Queue/outbox dispatch and notification dedupe.
- Operations/stuck recovery.

### Phase 6 — Admin/API/UI

- Definition library/designer/form builder.
- Request editor/detail/inbox/delegations.
- Versioned API and idempotency/concurrency responses.
- Reports/operations workspace.
- Responsive/accessibility/manual UI tests each batch.

### Phase 7 — Files, export, hardening

- Private attachments and authorized download.
- Queued report/definition export; import only if approved SHOULD scope.
- Retention/cleanup, observability, performance/query budgets.
- Security test closure.

### Phase 8 — Final verification/documentation

- Migration/boot/runtime state.
- Targeted/module/System/full regression.
- Pint/static/frontend build/UI smoke.
- Update README/INFORMATION/ANALYSIS or implementation docs required by repository.
- Clean status and merge readiness report.

Each phase follows:

```text
Analyze -> implement small coherent batch -> targeted tests -> UI smoke when applicable -> PASS -> document -> next phase
```

## 6. Required registries

Server-owned registries must map stable keys to code for:

- node handlers
- actor resolvers
- condition operators/functions
- form field handlers
- notification channels/templates
- safe operational actions
- domain event topics

Definition/API data contains only stable registered keys and validated configuration. Never resolve a user-supplied class name through the container.

## 7. Transaction template

Every mutating service/action should make the transaction boundary obvious:

```text
authorize caller
validate command DTO
begin transaction
resolve/check idempotency
lock aggregate rows in deterministic order
re-check state and actor invariants
mutate/advance engine
append audit and outbox
store stable result
commit
dispatch after commit
```

Do not catch every exception into success=false arrays. Use explicit domain exceptions, safe mapping, and structured operational logs.

## 8. Database generation rules

- Follow `02-DATABASE_ERD_AND_SCHEMA.md` and current MySQL conventions.
- One canonical owner per table.
- Add FK/index/unique constraints with the first migration that needs them.
- Preserve migration history; no negative/malformed dates.
- Avoid a giant single migration when ordered cohesive migrations improve rollback/review.
- No schema changes in shell/domain modules.
- Tests prove fresh install and supported rollback.

## 9. UI generation rules

- Use `Admin::layouts.master` and approved shared UI components.
- Page Blade is a shell; Livewire has UI state; services own queries/business logic.
- Workspace-first screens, visible controls, filters/reset, bounded pagination, loading/empty/error/success states.
- Authorize every Livewire mutation.
- Do not implement graph execution in Alpine/JavaScript; client graph is an editor representation only.
- UI work is not complete without real desktop/mobile visual smoke.

## 10. Stop conditions

Stop and ask for approval when:

- a proposed dependency is not shell
- a requirement needs a domain-module reference
- full BPMN, multi-tenancy, PKI, external webhook/integration, or polymorphic domain binding is requested
- a canonical shell identity/organization contract cannot be found
- schema/permission/business behavior contradicts REQUIREMENTS
- provider changes, shared infrastructure changes, or destructive migration is required
- tests reveal existing behavior that invalidates the plan

## 11. Completion output

Final implementation report must include:

- branch/commits and exact scope
- files/migrations/routes/permissions/queues/storage created
- architecture/dependency evidence
- tests and exact results
- UI smoke evidence
- known limitations/non-blocking follow-ups
- migration/deployment/worker/scheduler instructions
- rollback notes
- confirmation of clean Git status and no temp/debug files

