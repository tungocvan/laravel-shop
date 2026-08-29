# Task: /analyze <ModuleName>

Analyze one Laravel module and generate reusable documentation only.

## Purpose

Use this task to understand the current module before any refactor or rebuild work.

The result must support later decisions about:

- security
- correctness
- performance
- maintainability
- database design
- import/export
- testing
- cross-module dependencies

This task MUST NOT modify application code.

## 1. Resolve Target Module

Input must resolve to exactly one module under:

```text
Modules/<ModuleName>/
```

If the target cannot be resolved safely, mark it as unknown and explain what must be verified.

## 2. Required Reading

Before analysis, read when present:

```text
composer.json
package.json
Modules/ModuleServiceProvider.php
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
docs/GITHUB_COLLABORATION_WORKFLOW.md
ROADMAP.md
```

If the task was entered through a repository workflow prompt such as `docs/chuyen_chat.md` or `docs/chuyen_may.md`, read and preserve that workflow context as well.

If the target module contains import/export behavior, also read the repository's canonical import/export guidance when present.

Determine the project conventions relevant to the target module:

- Laravel/PHP versions
- module registration and namespaces
- route/view/Livewire conventions
- service-layer rules
- shared services and components
- admin UI conventions
- security standards
- performance standards
- documentation standards
- collaboration / PR governance
- roadmap priorities

## 3. Instruction Priority

When guidance conflicts, use this order:

1. Current repository source/configuration and actual runtime architecture.
2. Repository workflow/governance that controls collaboration, branch, handoff and PR gates.
3. `.codex/bootstrap/*` and `ROADMAP.md`.
4. `.codex/standards/MODULE_STANDARD.md` and `.codex/standards/ADMIN_UI_STANDARD.md`.
5. Existing module documentation.
6. Older/general guidance files.

Source code is the source of truth for current behavior.

Standards define the expected target quality. A mismatch between current source and standards is a finding, not permission to rewrite source during `/analyze`.

Workflow metadata required by repository governance, especially `COLLABORATION_HANDOFF.md`, is not application source and is not considered a violation of the `/analyze` documentation-output scope.

Do not classify repository-specific architecture as defective merely because an older generic/master prompt uses another architecture.

## 4. Existing Documentation

Read existing files under:

```text
docs/modules/<ModuleName>/
```

At minimum inspect when present:

```text
ANALYSIS.md
INFORMATION.md
README.md
COLLABORATION_HANDOFF.md
```

Existing docs are context only. Verify them against source code. If docs and source differ, record documentation drift.

`COLLABORATION_HANDOFF.md` is workflow metadata. Preserve its historical/current checkpoint information and update only what is required to hand off the completed analysis safely.

## 5. Scope

Primary scope:

```text
Modules/<ModuleName>/**
docs/modules/<ModuleName>/**
```

Inspect shared or other-module files only when directly referenced by the target module, for example through:

- namespace imports
- model relationships
- service calls
- events/listeners/jobs
- route/config references
- Blade/Livewire/shared component references

Do not inspect unrelated modules or the entire project unnecessarily.

Repository-level workflow/task documentation may be updated only when the user explicitly approves a governance/task-standardization change. Such a change must remain documentation-only.

## 6. Analysis Flow

Follow this order:

```text
Route
-> Controller
-> Page Blade
-> Livewire PHP
-> Livewire Blade
-> Shared UI Components
-> Services
-> Import
-> Export
-> Shared Services
-> Model
-> Migration
-> Database
```

For each relevant layer identify:

- exact file paths
- responsibilities
- dependencies
- compliance with `MODULE_STANDARD.md`
- compliance with `ADMIN_UI_STANDARD.md` where UI exists
- validation
- authorization
- transaction boundaries
- security risks
- performance risks
- duplicated logic
- maintainability concerns
- test coverage

Do not recommend architectural changes only for stylistic consistency. Recommendations must improve correctness, security, performance, maintainability, testability, or repository consistency.

## 7. Evidence Rules

Do not present guesses as facts.

Use these concepts when needed:

```text
Evidence   = directly observed in source/config/schema/docs
Inference  = reasoned from current code or framework behavior
Assumption = not proven and requires verification
```

Unknown or unverified behavior must be stated explicitly with a verification method.

## 8. Dependency Analysis

Document the main dependency path, for example:

```text
Route
-> Controller
-> Blade / Livewire
-> Service
-> Model
-> Database
```

Also identify when present:

- shared components
- shared services
- import/export dependencies
- events/jobs
- cross-module dependencies
- circular dependencies

Cross-module dependencies should be checked against the canonical domain-owner and shared-foundation rules in `MODULE_STANDARD.md`.

## 9. Priority Rules

Use:

```text
P0 = security, data-loss, secret exposure, production-control, or irreversible data risk
P1 = correctness, performance, maintainability, testability, or module-integrity risk
P2 = cleanup, developer experience, observability, UI consistency, or non-blocking improvement
```

Material issues should include:

```text
Priority:
File:
Evidence:
Problem:
Impact:
Recommendation:
```

Do not create issues without evidence unless clearly labeled as Inference or Assumption.

## 10. Required Review Areas

Review where applicable:

- routes and middleware
- authentication and capability-specific permissions
- controller responsibilities
- Page Blade responsibilities
- Livewire state/actions/validation/events/pagination/search/filter/sort
- Livewire mutation authorization
- service responsibilities and transaction boundaries
- service-layer bypasses
- import mapping, validation, duplicate handling, chunking and cleanup
- export queries, mapping, memory usage and storage
- shared services/components
- UI consistency, responsive behavior, validation UX, empty/loading states
- reuse versus duplication of shared UI components
- models, fillable, casts, relationships, scopes and soft deletes
- migrations, columns, indexes, foreign keys, constraints and delete behavior
- file upload/download security
- private versus public storage
- sensitive data exposure and logging risks
- mass assignment
- SQL/XSS risks
- N+1 queries
- unbounded collections
- caching/queue opportunities
- concurrency/idempotency for high-risk writes
- cross-module ownership
- test coverage
- documentation drift

If a category does not exist in the module, state `Not present` rather than inventing content.

## 11. Output Files

### 11.1 Analysis deliverables

Create or update only these module-analysis deliverables:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
```

Do NOT create or update `REFACTOR_PLAN.md` or `REBUILD_SPEC.md` during `/analyze` unless explicitly requested in a separate task.

### 11.2 Workflow metadata

When repository workflow requires a handoff checkpoint before PR/merge, also update:

```text
docs/modules/<ModuleName>/COLLABORATION_HANDOFF.md
```

If it does not exist, create a minimal handoff file before the PR gate.

This file is workflow metadata, not an additional analysis deliverable. It must not expand the implementation scope.

The analysis handoff should record at minimum:

- task `/analyze <ModuleName>` completed
- analysis branch/checkpoint when known
- analysis deliverables updated
- final recommendation
- material P0/P1 risks or unresolved unknowns
- application-source modification status: none
- runtime/module/UI test applicability for this documentation-only task
- next implementation/refactor phase: `NOT AUTHORIZED` until separately proposed and approved

Do not treat completion of `/analyze` as authorization to create a refactor/rebuild branch or modify application code.

### ANALYSIS.md

Keep it technical and decision-oriented. Include:

```text
Executive Summary
Module Purpose and Overview
Bootstrap / Standards Context
Dependency Graph
Route / Controller / Blade / Livewire Analysis
Service Analysis
Import / Export Analysis
Shared Dependencies
Model / Migration / Database Analysis
Security
Performance
Validation and Authorization
Transactions, Concurrency and Data Integrity
Admin UI / UX Standard Review (when applicable)
Cross-Module Dependencies
Technical Debt
Test Coverage
Documentation Drift
Issue List (P0/P1/P2)
Module Health Summary
Final Recommendation
Open Questions / Unknowns
```

Final recommendation should choose exactly one when evidence is sufficient:

```text
Minor Refactor
Major Refactor
Full Rebuild
No Structural Refactor Required
```

### INFORMATION.md

Keep it factual and concise. Include as applicable:

```text
Purpose
Features
Routes
Permissions
Controllers
Livewire Components
Blade Views
Services
Imports / Exports
Models
Database Tables
Relationships
Shared / Cross-Module Dependencies
Events / Jobs
Configuration / Environment Variables
Known Limitations
Maintenance Notes
```

### README.md

Keep it short and developer-oriented. Include:

```text
Module Overview
Registration
Main Routes
Permissions
Features
Dependencies
Configuration
Operational Notes
Developer Notes
Future Improvements
```

## 12. Rules

- Documentation-only task.
- Never modify application source code.
- Never create migrations, controllers, Livewire components, services or models.
- Never delete or rename application files.
- Do not modify unrelated module docs.
- Read an existing documentation file before replacing it.
- Be idempotent.
- Prefer concise factual documentation over duplicated prose.
- Code is the source of truth for current behavior.
- Standards are the reference for target quality.
- Repository collaboration workflow governs handoff and PR metadata.
- Mark unknowns explicitly.
- Do not recommend introducing `nwidart`, `module.json`, a new CSS framework, or a new module-registration mechanism unless current repository infrastructure explicitly requires it.
- Completing `/analyze` never implicitly authorizes refactor/rebuild implementation.

## 13. Documentation-Only Verification

For `/analyze`, application runtime behavior is not changed. Therefore runtime-focused gates are normally:

```text
Focused application tests: NOT APPLICABLE — documentation-only
Module regression: NOT APPLICABLE — documentation-only
Manual UI smoke: NOT APPLICABLE — documentation-only
```

Do not run application tests merely to satisfy a generic gate when no application source/config/schema/runtime behavior changed.

Instead perform documentation verification:

1. source evidence and documentation statements are consistent
2. recommendation matches recorded evidence
3. required sections are present
4. documentation drift is explicitly recorded
5. diff contains no application source/config/schema/runtime changes
6. no `REFACTOR_PLAN.md` or `REBUILD_SPEC.md` mutation occurred unless separately authorized
7. handoff checkpoint satisfies the collaboration workflow before PR

If the analysis itself changes repository-level workflow/task documentation by explicit user approval, verify that those changes remain documentation/governance-only and do not weaken mutation approval gates.

## 14. Quality Gate

Before finishing verify:

- target module was resolved
- bootstrap/context files were read
- `MODULE_STANDARD.md` and `ADMIN_UI_STANDARD.md` were read
- collaboration workflow was read when present
- existing module docs were checked
- scope stayed within the target module plus direct dependencies
- analysis followed the required flow
- source-vs-standard mismatches were evaluated correctly
- security, authorization, performance, database and tests were considered
- UI standard was reviewed when the module has admin UI
- P0/P1 findings have evidence
- unknowns were identified
- exactly the three analysis deliverables were created or updated
- workflow metadata was updated when required by repository governance
- documentation-only verification passed
- no application code was modified
- next implementation/refactor phase remains unauthorized unless separately approved

## 15. PR / Handoff Gate

When `/analyze` is performed under `docs/GITHUB_COLLABORATION_WORKFLOW.md`:

```text
Full source inspection
-> ANALYSIS.md / INFORMATION.md / README.md
-> documentation drift check
-> exactly one final recommendation
-> COLLABORATION_HANDOFF.md checkpoint
-> diff-scope verification
-> documentation-only PR
```

Before creating the PR:

- working tree/checkpoint must be clean and synchronized where applicable
- handoff must describe the current analysis checkpoint
- PR must be clearly labeled/documented as documentation-only
- application tests/UI smoke must not be reported as PASS when they were not run; use `NOT APPLICABLE — documentation-only`
- no refactor/rebuild implementation may be bundled into the PR

Before merge, refresh the handoff if PR review changes the analysis conclusion, material findings, branch checkpoint or next-step status.

## Final Response

Return only a short summary containing:

- analysis documentation files created/updated
- workflow metadata updated when applicable
- most important findings
- unresolved unknowns, if any
- final recommendation
- confirmation that application source code was not modified
- next implementation/refactor authorization status
