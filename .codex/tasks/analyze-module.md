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

## 2. Bootstrap

Before analysis, read when present:

```text
composer.json
Modules/ModuleServiceProvider.php
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

Determine the project conventions relevant to the target module:

- Laravel/PHP versions
- module registration and namespaces
- route/view/Livewire conventions
- shared services and components
- security standards
- performance standards
- documentation standards
- roadmap priorities

## 3. Existing Documentation

Read existing files under:

```text
docs/modules/<ModuleName>/
```

At minimum inspect when present:

```text
ANALYSIS.md
INFORMATION.md
README.md
```

Existing docs are context only. Verify them against source code. If docs and source differ, record documentation drift.

## 4. Scope

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

## 5. Analysis Flow

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
- validation
- authorization
- transaction boundaries
- security risks
- performance risks
- duplicated logic
- maintainability concerns
- test coverage

## 6. Evidence Rules

Do not present guesses as facts.

Use these concepts when needed:

```text
Evidence   = directly observed in source/config/schema/docs
Inference  = reasoned from current code or framework behavior
Assumption = not proven and requires verification
```

Unknown or unverified behavior must be stated explicitly with a verification method.

## 7. Dependency Analysis

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

## 8. Priority Rules

Use:

```text
P0 = security, data-loss, secret exposure, production-control, or irreversible data risk
P1 = correctness, performance, maintainability, testability, or module-integrity risk
P2 = cleanup, developer experience, observability, or non-blocking improvement
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

## 9. Required Review Areas

Review where applicable:

- routes and middleware
- authentication and permissions
- controller responsibilities
- Blade pages
- Livewire state/actions/validation/events/pagination/search/filter/sort
- service responsibilities and transactions
- import mapping, validation, duplicate handling, chunking and cleanup
- export queries, mapping, memory usage and storage
- shared services/components
- models, fillable, casts, relationships, scopes and soft deletes
- migrations, columns, indexes, foreign keys, constraints and delete behavior
- file upload/download security
- sensitive data exposure
- mass assignment
- SQL/XSS risks
- N+1 queries
- unbounded collections
- caching/queue opportunities
- cross-module ownership
- test coverage
- documentation drift

If a category does not exist in the module, state `Not present` rather than inventing content.

## 10. Output Files

Create or update only:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
```

Do NOT create `REFACTOR_PLAN.md` or `REBUILD_SPEC.md` during `/analyze` unless explicitly requested in a separate task.

### ANALYSIS.md

Keep it technical and decision-oriented. Include:

```text
Executive Summary
Module Purpose and Overview
Dependency Graph
Route / Controller / Blade / Livewire Analysis
Service Analysis
Import / Export Analysis
Shared Dependencies
Model / Migration / Database Analysis
Security
Performance
Validation and Authorization
Transactions and Data Integrity
Cross-Module Dependencies
Technical Debt
Test Coverage
Documentation Drift
Issue List (P0/P1/P2)
Module Health Summary
Final Recommendation
Open Questions / Unknowns
```

Final recommendation should choose one when evidence is sufficient:

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

## 11. Rules

- Documentation-only task.
- Never modify application source code.
- Never create migrations, controllers, Livewire components, services or models.
- Never delete or rename application files.
- Do not modify unrelated module docs.
- Read an existing documentation file before replacing it.
- Be idempotent.
- Prefer concise factual documentation over duplicated prose.
- Code is the source of truth for current behavior.
- Mark unknowns explicitly.

## 12. Quality Gate

Before finishing verify:

- target module was resolved
- bootstrap/context files were read
- existing module docs were checked
- scope stayed within the target module plus direct dependencies
- analysis followed the required flow
- security, authorization, performance, database and tests were considered
- P0/P1 findings have evidence
- unknowns were identified
- exactly the requested documentation files were created or updated
- no application code was modified

## Final Response

Return only a short summary containing:

- documentation files created/updated
- most important findings
- unresolved unknowns, if any
- confirmation that application source code was not modified
