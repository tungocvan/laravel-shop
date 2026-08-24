# AI Implementation Contract

## 1. Mandatory stop gate

This specification completes `/analyze-new-module Request`; it does not authorize code.

Before changing application code, migrations, routes, configuration, tests, dependencies, PWA assets, or deployment files, run:

```text
/create-module Request
```

That task must create `docs/modules/Request/CREATE_PLAN.md`, list the exact files/slices/migrations/risks/verification, and obtain explicit user approval. Stop after the plan if approval is absent. Do not run `/create-module Workflow` while `ADR-001-REQUEST-FIRST-WORKFLOW-DEFERRED.md` is active.

## 2. Sources of truth

Read completely before planning/implementation:

1. Current repository status, root instructions, and relevant `.codex` tasks/bootstrap/standards.
2. `Modules/ModuleServiceProvider.php`, module-state resolver/repository, and current module manifests.
3. This directory in README read order.
4. Reference module code actually selected in `CREATE_PLAN.md`.

Never rely on generic Laravel modular-package conventions or the old shared conversation over current repository code.

## 3. Non-negotiable architecture

- Module path/name: `Modules/Request`; type `domain`.
- Repository-native `config/module.php`; no `module.json`, `nwidart/laravel-modules`, or second module registry.
- Direct module dependencies only: `Admin`, `Auth`, `User`, `Role`, `Shared`, and only while each resolves `type = shell`.
- No import/query/table/model/event/route dependency on a domain module.
- Request owns its tables, files, config, routes, permission prefix, events, queues, and UI domain components.
- Reuse shell layouts/components and approved Shared import/export; do not copy or fork global shell/PWA infrastructure.
- No Workflow runtime code/table/route and no generic Approval extraction in v1.

## 4. Required application layering

Controllers, Livewire components, console/queue adapters, and API handlers validate transport concerns and call application services/commands. They do not implement state transitions or update status columns directly.

Application services coordinate policies, transactions, locks, idempotency, aggregate behavior, audit, and outbox. Domain services/value objects implement schema validation, actor resolution contracts, transitions, and stage evaluation. Repositories/queries enforce visibility and bounded access paths. Jobs perform after-commit delivery/generation and cannot become business truth.

Exact folders/classes follow repository conventions and must be enumerated in `CREATE_PLAN.md`. Avoid speculative patterns not used in the repository.

## 5. Naming rules

- Use English stable names for PHP, database, config, route/event/permission/API identifiers.
- Use `InternalRequest` or another unambiguous approved aggregate/model name; do not shadow `Illuminate\Http\Request` in confusing contexts.
- Canonical table names follow `02-DATABASE_ERD_AND_SCHEMA.md`; any change requires the plan to update all docs/traceability before code.
- Prefix names with `request.` or `request_` as appropriate.
- User-facing copy uses localization keys, with Vietnamese initial translations where required.

## 6. Implementation order

The plan must use vertical slices and keep every merged slice secure/migratable:

1. Bootstrap/dependency architecture tests/config/permissions.
2. Definition schema/version validation/publication.
3. Dynamic form rendering/server validation/draft concurrency.
4. Submit/payload/run/first stage transaction.
5. Sequential single decisions.
6. Parallel ALL/ANY and concurrency.
7. Return/resubmit/cancel/reassign and complete workspaces.
8. Comments/private files/audit/outbox/notifications.
9. Responsive/PWA offline safety.
10. Reports/exports/definition package/operations.
11. Hardening, performance, accessibility, deployment/release verification.

Do not defer authorization, idempotency, audit, private storage, or tests to a cleanup slice.

## 7. Definition/version implementation rules

- Draft-only edit; authoritative publication validation under lock.
- Canonical JSON/checksum and immutable published rows.
- Server-owned registry for fields, validators, resolver keys, and any display calculators.
- Unknown keys/types/operators rejected.
- Published type version pinned to request and retained through return/resubmit.
- No EAV request field/value table.
- No browser-authored PHP/class/expression/template execution.

## 8. Approval implementation rules

- Sequential stages only; `single`, `parallel_all`, `parallel_any` exactly as documented.
- Candidates resolve/snapshot at each stage activation.
- Requester removed; cardinality enforced; no silent stage skip/default administrator.
- Decision locks/idempotency/expected-version/state/policy checks are in one service path.
- Exactly one effective transition under races.
- Return restarts from stage one on a new run.
- Reject terminal; pending cancel privileged/reasoned; reassignment creates replacement history.
- No delegation/quorum/conditions/timers/SLA/graph/subflow/signature hidden inside flexible JSON.

## 9. Security and data rules

- Query-level visibility before retrieval/serialization.
- DTO allowlists and guarded models; never mass-assign transport input.
- Private attachment/export storage and authorized streaming.
- JSON/count/file/query/export limits in configuration plus validation.
- Transactions append audit/outbox with business state.
- Logs/events/notifications/audit/cache exclude secrets and raw sensitive payloads.
- Queued jobs receive stable IDs and are idempotent.
- No `chmod 777`, public sensitive file, permanent unauthenticated link, or arbitrary retry class.

## 10. UX/PWA rules

- Use `Admin::layouts.master` and repository UI standards.
- Phone-first create/My Requests/inbox/decision; tablet/desktop-first builder.
- Bordered visible controls, search/filter/reset, bounded pagination, explicit loading/empty/error/offline/stale/conflict states.
- Accessibility and keyboard alternatives from first component implementation.
- Application Shell owns manifest/service worker/cache lifecycle; Request registers no second global asset.
- Offline is read/draft-only under field policy. Never queue/replay submit/decision/comment/upload/publish or another business command.

## 11. Reference-module guidance

The current analysis identifies:

- `Administrative` for thin transport/application services, private files, status history, optimistic versioning, bounded lists, and Shared import/export usage.
- `Admin` for shell/layout/navigation/components.
- `Shared` for actual reusable import/export capability.
- `Auth`, `User`, `Role` for Shell identity/capability contracts.

Copy conventions, not business behavior. Do not copy Administrative public/anonymous submission/lookup, its System coupling, CRUD procedure schema, or module-specific import assumptions. Re-inspect current code at plan time because reference modules may change.

## 12. Plan questions that must be resolved

`CREATE_PLAN.md` must explicitly decide and justify:

- exact current branch/base and conflicting user changes;
- migration order/types/constraints/indexes and request-number allocator;
- pre-first-submit draft pin/retirement grace behavior;
- later-stage activation failure representation and retry path;
- precise Shell User/Role interfaces and inactive/deleted behavior;
- provider bindings/resolver registry/bootstrap files;
- API/web route map and idempotency/optimistic-version transport;
- file MIME/size/count/storage/scan capability;
- notification infrastructure and queue/worker requirements;
- Shared import/export formats actually available;
- PWA Shell extension point actually available; if absent, safe online-only fallback;
- performance dataset/budgets and concurrency test mechanism;
- enablement, upgrade, backup/restore, and rollback runbook.

Unknowns must not be filled with guessed tables/classes/packages.

## 13. Verification commands and evidence

The plan lists concrete repository commands. At minimum evidence covers:

- formatting/static analysis used by repository;
- unit/feature/architecture/concurrency/security/accessibility tests;
- migration fresh/upgrade status;
- route/module/provider registration and enable/disable behavior;
- query counts/explain plans and bounded export;
- private storage/queue/mail/outbox behavior;
- responsive viewport and offline cache inspection;
- dependency scans using `rg`/architecture tests.

Do not report success if a command was not run or if the target environment differs materially; state the evidence and limitation.

## 14. Change control

If implementation discovers a required behavior outside this specification:

1. Stop the affected slice.
2. Describe the conflict and smallest options.
3. Update requirements/ADR/spec/traceability as needed.
4. Obtain approval for material scope/ownership/security/dependency changes.
5. Update `CREATE_PLAN.md` before code resumes.

Material changes include domain dependency, manager/department resolution, Workflow coexistence, shared Approval extraction, conditions/graph/timers/SLA/delegation, digital signature, external webhook/domain posting, offline mutation queue, public file access, or new package/infrastructure.

## 15. Completion handoff

Final implementation handoff must state:

- exact scope delivered and intentionally excluded;
- migrations/config/permissions/routes/workers/storage/deployment actions;
- tests and manual evidence with outcomes;
- remaining risks/follow-ups for release notes/team chat;
- confirmation that Workflow remains deferred and Request has no domain dependency.

The implementation may be called complete only when `10-TEST_AND_ACCEPTANCE.md` and `12-TRACEABILITY_MATRIX.md` are satisfied.
