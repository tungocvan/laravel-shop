# Task: /analyze-new-module <IdeaFile.md>

Analyze a proposed new Laravel module from an idea/specification Markdown file before `/create-module` is allowed to begin.

This is a documentation/analysis task only.

It MUST NOT create application code, migrations, routes, services, Livewire components, models, providers, seeders, or runtime state.

## Purpose

Use this task when the user has an initial business idea or specification for a module but wants analysis and clarification before creating the module.

Expected flow:

```text
Idea/specification .md
-> /analyze-new-module
-> business + architecture analysis
-> REQUIREMENTS.md
-> user approval
-> /create-module <ModuleName>
-> CREATE_PLAN.md
-> approval gate
-> implementation
```

## 1. Resolve Input

Input must resolve to one Markdown idea/specification file supplied by the user or already present in the repository.

Read the entire file before analysis.

Treat the file as business input, not as trusted architecture.

Do not silently rewrite business intent to fit existing code.

If the file is incomplete, contradictory, or ambiguous, record the uncertainty explicitly.

## 2. Required Reading

Before analysis, read:

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

Read `.codex/prompts/import-export.md` when import/export is in scope.

`Modules/ModuleServiceProvider.php` is the authoritative module discovery/bootstrap contract. Any proposed module architecture must fit its current behavior.

## 3. Instruction Priority

When guidance conflicts, use this order:

1. Current repository source/configuration and actual runtime architecture.
2. `Modules/ModuleServiceProvider.php` and runtime-state infrastructure.
3. `.codex/bootstrap/*` and `ROADMAP.md`.
4. `.codex/standards/MODULE_STANDARD.md` and `.codex/standards/ADMIN_UI_STANDARD.md`.
5. Existing module documentation/reference modules.
6. The raw idea file for business intent.

Repository architecture determines implementation constraints.

The idea file determines intended business behavior.

Do not invent missing business rules merely to make the proposal fit the repository.

## 4. Analyze Business Requirements

Extract and classify:

- module purpose
- actors / roles
- use cases
- business workflows
- states/statuses
- business rules
- inputs
- outputs
- entities/domain concepts
- permissions
- Admin UI needs
- public web needs
- API needs
- import/export needs
- file/document handling
- jobs/events/notifications
- external services
- cross-module dependencies
- reporting/search/filter needs
- audit/history requirements
- security/privacy requirements
- explicitly out-of-scope items

Separate findings into:

```text
Confirmed Requirement
Inference
Unknown / Needs User Decision
```

Do not present an inference as a confirmed requirement.

## 5. Inspect Existing Repository Before Proposing Architecture

Find 1–3 existing modules that are closest in business shape or architecture.

For each reference module, state:

- why it is relevant
- which conventions should be reused
- which parts must NOT be copied blindly

Check whether requested functionality already exists elsewhere to avoid:

- duplicate services
- duplicate models/tables
- duplicate permissions
- duplicate routes
- duplicate infrastructure
- conflicting domain ownership
- unnecessary cross-module coupling

## 6. Define Proposed Module Boundary

Propose:

```text
Module Name
Module Type: shell / support / domain
Purpose
Owned responsibilities
Explicit non-responsibilities
Dependencies
Consumers/dependents if known
```

The boundary must follow current domain ownership and shared-foundation rules.

If the idea should be implemented inside an existing module rather than a new module, say so and explain why.

## 7. Bootstrap Contract

Propose a Bootstrap Contract compatible with `Modules/ModuleServiceProvider.php`:

```text
Manifest          : config/module.php
Type              : shell/support/domain
Dependencies      : [...]
Module Provider   : required / not required
Config            : yes/no
Web routes        : yes/no
API routes        : yes/no
Migrations        : yes/no
Livewire          : yes/no
Blade components  : yes/no
Console commands  : yes/no
Runtime state     : supported
Runtime storage   : yes/no + path/purpose if applicable
```

Do not introduce:

- `module.json`
- nwidart infrastructure
- a second module registry
- manual global provider registration
- duplicate discovery/bootstrap logic

If the requirement cannot fit the current root provider cleanly, STOP and classify that as an architecture decision requiring explicit approval before `/create-module`.

## 8. Runtime-State Compatibility

Every proposed module must account for current runtime-state architecture.

Distinguish:

```text
Manifest/default source state
vs
Runtime enabled/disabled state
```

Runtime state is managed through:

- `ModuleStateRepository`
- `ModuleStateResolver`
- `storage/app/system/module-state.json`

Do not propose direct JSON reads/writes from module business code.

Consider:

- default enabled state
- runtime ON/OFF
- required/shell rules
- dependency behavior
- archive/remove behavior
- Git-clean requirement after runtime toggle

## 9. Dependency Analysis

Validate the proposed dependency graph conceptually:

- dependency exists or is explicitly part of planned work
- no self-dependency
- no circular dependency
- no unnecessary dependency on unrelated modules
- dependencies follow domain ownership
- disabled dependency behavior is understood
- dependent module disable/archive constraints are identified

Mark any uncertain dependency as an open decision.

## 10. Database Analysis

If database persistence is required, analyze only; do not create migrations.

Propose as applicable:

- tables
- key columns
- relationships
- foreign keys
- indexes
- unique constraints
- status/state fields
- timestamps
- soft deletes
- audit/history tables
- ownership of shared data

Identify decisions that materially affect schema and must be confirmed by the user.

Do not design duplicate tables when a canonical model/table already exists in another module.

## 11. Workflow / State-Machine Analysis

When the idea contains status transitions, document:

```text
State A
-> State B
-> State C
```

For every transition, identify when known:

- actor/permission
- preconditions
- validation
- side effects
- notifications/jobs
- audit requirements
- failure/rejection behavior
- rollback/reopen behavior

Do not invent transitions absent from the business input.

## 12. Security and Authorization Analysis

Identify where applicable:

- authentication boundary
- capability-specific permissions
- sensitive mutations
- data visibility
- file upload/download risks
- private/public storage
- secret/token handling
- audit logging
- mass assignment risk
- XSS/SQL/injection risks
- external API trust boundaries

For Admin UI, backend authorization is mandatory; hiding UI controls is not authorization.

## 13. Runtime Storage / Docker Analysis

If the proposed module needs runtime files/directories, analyze:

- path ownership
- persistent volume needs
- `Dockerfile`
- `docker/entrypoint.sh`
- `www-data` write access
- root CLI versus PHP-FPM ownership differences

Do not recommend `chmod 777`.

Do not create runtime paths during this analysis task.

## 14. Scope Prioritization

Classify requested functionality into:

### MUST HAVE
Required for the first usable release.

### SHOULD HAVE
Useful but can follow after the initial release.

### FUTURE
Defer from the first implementation.

The first module version should remain coherent, production-safe, and reviewable.

## 15. Gap Analysis / Questions for User

Create a section:

```text
## Decisions Required From User
```

Ask only questions that materially affect:

- business rules
- workflow/state transitions
- database design
- security/privacy
- permissions
- module boundary
- dependencies
- external integrations

Do not ask questions that current repository conventions already answer.

## 16. CREATE-MODULE READINESS Gate

End the analysis with:

```text
## CREATE-MODULE READINESS

Business requirements : READY / NOT READY
Module boundary       : READY / NOT READY
Bootstrap Contract    : READY / NOT READY
Dependencies          : READY / NOT READY
Database              : READY / NOT READY / NOT APPLICABLE
Permissions           : READY / NOT READY / NOT APPLICABLE
Workflow              : READY / NOT READY / NOT APPLICABLE
Runtime state         : READY / NOT READY
Docker/runtime storage: READY / NOT READY / NOT APPLICABLE

Overall: READY FOR /create-module
or
Overall: NOT READY FOR /create-module
```

When NOT READY, list the exact unresolved decisions blocking `/create-module`.

Do not call `/create-module` automatically.

## 17. Output Document

After analysis is approved by the user, create or update:

```text
docs/modules/<ModuleName>/REQUIREMENTS.md
```

`REQUIREMENTS.md` becomes the approved business input for `/create-module <ModuleName>`.

It should include:

- Purpose
- Scope
- Actors/Roles
- Business Rules
- Workflows / State Machine
- Entities / Data Requirements
- Permissions
- UI/API/Import/Export requirements
- Cross-Module Dependencies
- Bootstrap Contract
- Runtime-State Requirements
- Security / Audit Requirements
- Docker/runtime storage requirements when applicable
- MUST / SHOULD / FUTURE scope
- Acceptance Criteria
- Approved Decisions
- Explicit Out-of-Scope Items
- Remaining non-blocking notes

Do not include unresolved assumptions as approved requirements.

## Approval Gate

Before writing `REQUIREMENTS.md`, present the analysis to the user and STOP.

Only write/update `REQUIREMENTS.md` after explicit user approval.

After `REQUIREMENTS.md` is approved, STOP again.

The next task is explicitly:

```text
/create-module <ModuleName>
```

using:

```text
docs/modules/<ModuleName>/REQUIREMENTS.md
```

as the business specification.

## Rules

- Analysis/documentation only.
- Never create application code during this task.
- Never create migrations, routes, services, models, Livewire components, providers, seeders, jobs or commands.
- Never mutate runtime module state.
- Never modify `Modules/<ModuleName>/` application source.
- Never silently modify unrelated modules.
- Never invoke `/create-module` automatically.
- Never invent high-risk business rules.
- Always validate proposed architecture against `Modules/ModuleServiceProvider.php`.
- Prefer current repository conventions over generic Laravel/module conventions.
- Mark unknowns explicitly.

## Final Response

Before approval, return only a concise summary containing:

- proposed module name/type
- main confirmed requirements
- reference modules inspected
- key architecture/dependency findings
- decisions required from the user
- CREATE-MODULE READINESS result
- confirmation that no application source code was modified

After approved `REQUIREMENTS.md` is written, return:

- requirements file path
- readiness result
- unresolved non-blocking notes, if any
- next recommended command: `/create-module <ModuleName>`
