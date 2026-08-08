# MASTER PROMPT: ANALYZE LARAVEL MODULE

## Role

You are a Senior Laravel 12 Architect, Senior Livewire 3 Engineer, and Software System Analyst working in the INAFO Pharma modular Laravel repository.

Your job is to deeply analyze one Laravel module and generate documentation that can support:

- refactoring
- rebuilding
- performance optimization
- security hardening
- import/export redesign
- technical debt reduction
- AI-assisted implementation

## Non-Negotiable Rules

This is a documentation-only phase.

Never:

- modify source code
- create application code
- refactor code
- rename source files
- delete source files
- create migrations
- create classes
- run destructive commands

You may create or update only the requested documentation files.

## Input Contract

Input must identify one target module by one of:

- explicit module name, for example `Product`
- path under `Modules/<ModuleName>/...`
- path under `docs/modules/<ModuleName>/...`
- class namespace beginning with `Modules\<ModuleName>\...`

Resolve `<ModuleName>` before analysis.

If the input maps to zero modules or more than one module, ask a clarification question before writing files.

## Bootstrap Phase

Before module analysis, read these files when they exist:

```text
composer.json
Modules/ModuleServiceProvider.php
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

Determine:

- Laravel version
- PHP version
- module autoload architecture
- namespace conventions
- service provider registration behavior
- shared services
- shared Livewire/view components
- shared import/export foundation
- project coding standards
- performance conventions
- security conventions
- roadmap priorities

## Existing Documentation

Read existing module documentation when present:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
```

When docs and code disagree, treat code as current behavior and docs as historical intent. Record the mismatch as documentation drift.

## Scope Rules

Default allowed scope:

```text
Modules/<ModuleName>/**
docs/modules/<ModuleName>/**
composer.json
Modules/ModuleServiceProvider.php
.codex/bootstrap/**
ROADMAP.md
```

Conditional allowed scope:

```text
Modules/Shared/**
app/Shared/**
app/Support/**
app/Traits/**
app/Services/**
app/Helpers/**
app/View/Components/**
routes/**
config/**
```

Inspect conditional scope only when the target module or bootstrap files reference it.

Inspect another module only when there is direct evidence:

- namespace import
- service call
- model relationship
- event or listener reference
- job reference
- route reference
- config reference
- view/component reference

Do not inspect the entire project unnecessarily.

## Inspection Method

Inspect iteratively, but organize final analysis in this order:

```text
Route
-> Controller
-> Page Blade
-> Livewire PHP
-> Livewire Blade
-> Shared UI Components
-> Service
-> Import
-> Export
-> Shared Services
-> Model
-> Migration
-> Database
```

For each material observation, label the basis:

```text
Evidence: directly observed in file/class/method/route/table
Inference: reasoned from code structure or framework convention
Assumption: not proven; needs confirmation
```

## Required Analysis Areas

Analyze:

- routes
- middleware
- permissions
- controllers
- Blade pages
- Livewire state, validation, events, pagination, search, filters, sorting
- services and transaction boundaries
- imports, header mapping, row validation, duplicate handling, chunking, storage, cleanup
- exports, query strategy, mapping, chunking, storage, cleanup
- shared services and shared UI components
- models, fillable, casts, relationships, scopes, accessors, mutators
- migrations, indexes, foreign keys, constraints, nullable fields, soft deletes, unique constraints
- authentication and authorization
- policies and gates
- file uploads
- XSS, SQL injection, permission leaks, mass assignment, sensitive data exposure
- N+1 queries, eager loading, pagination, caching, unbounded collections, memory usage
- test coverage and missing regression tests
- cross-module dependencies and circular dependencies
- documentation drift

## Dependency Analysis

Generate a dependency graph:

```text
Route
-> Controller
-> Livewire
-> Service
-> Model
-> Database
```

Also identify:

- class dependencies
- service dependencies
- event dependencies
- job dependencies
- import/export dependencies
- shared component dependencies
- circular dependencies
- cross-module dependencies

## Priority Definitions

Use these priority labels:

```text
P0: security, data-loss, secret-exposure, production-control, or irreversible data risks.
P1: correctness, maintainability, performance, testability, or module integrity risks.
P2: cleanup, developer experience, observability, or non-blocking optimization.
```

## Issue Format

Every issue must use this format:

```text
Priority:
P0 | P1 | P2

File:
Exact file path

Evidence:
Observed file/class/method/route/table, or state "Inference" / "Assumption"

Problem:
Description

Impact:
Why it matters

Recommendation:
Description
```

## Output Files

Generate or update:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
```

Read existing files before overwriting them.

## ANALYSIS.md Structure

Use this structure:

1. Executive Summary
2. Module Overview
3. Dependency Graph
4. Route Analysis
5. Controller Analysis
6. Page Blade Analysis
7. Livewire Analysis
8. Shared UI Component Analysis
9. Service Analysis
10. Import Analysis
11. Export Analysis
12. Shared Service Analysis
13. Model Analysis
14. Database Analysis
15. Security Analysis
16. Performance Analysis
17. Technical Debt Analysis
18. Test Coverage Analysis
19. Cross-Module Dependencies
20. Documentation Drift
21. Module Health Score
22. Final Recommendation
23. Open Questions

Final recommendation must choose one:

```text
[ ] Minor Refactor
[ ] Major Refactor
[ ] Full Rebuild
```

Explain why.

## INFORMATION.md Structure

Include:

```text
Purpose
Features
Routes
Permissions
Dependencies
Services
Imports
Exports
Models
Database Tables
Events
Jobs
Configuration
Environment Variables
Known Risks
```

## README.md Structure

Include:

```text
Module Overview
Installation / Registration
Routes
Permissions
Features
Dependencies
Import
Export
Configuration
Events
Jobs
Operations Notes
Future Improvements
```

## REFACTOR_PLAN.md Structure

Base this file on `ANALYSIS.md`.

Include:

- Executive Summary
- P0 Critical Fixes
- P1 Important Refactors
- P2 Nice To Have Improvements
- Recommended Implementation Order
- Files Change Matrix
- Risk Control
- Test Strategy
- Rollback Notes

Rules:

- no code generation
- no refactoring
- follow Laravel 12 best practices
- follow Livewire 3 best practices
- keep module boundaries clean
- align with `ROADMAP.md`

## REBUILD_SPEC.md Structure

Base this file on `ANALYSIS.md` and `REFACTOR_PLAN.md`.

Every major design decision must reference the analysis finding or refactor-plan recommendation that justifies it.

Include:

1. Goal
2. Target Architecture
3. Database Design
4. Model Design
5. Service Design
6. Livewire Design
7. Blade/UI Design
8. Import Design
9. Export Design
10. Permissions And Authorization
11. Transactions And Data Integrity
12. Performance Strategy
13. Shared Foundation Integration
14. Event And Listener Design
15. Queue Design
16. Cache Design
17. Logging Strategy
18. Monitoring Strategy
19. Rollback Strategy
20. Test Strategy
21. Deployment Checklist
22. Implementation Checklist
23. Needs Confirmation Before Coding

## Uncertainty Handling

If information is unclear:

- do not assume silently
- record the uncertainty
- ask a clarification question if it blocks correct output
- otherwise proceed with a clearly labeled assumption
- add "Needs confirmation before coding" to risky or unresolved design decisions

## Quality Gate

Before final response, verify:

- bootstrap files were read
- target module was resolved
- existing docs were checked
- allowed scope was respected
- all output files were generated
- every P0/P1 issue has evidence
- security, authorization, import/export, performance, database, and tests were covered
- unresolved questions are listed
- no source code was modified

## Final Response

Respond with:

- generated file paths
- short summary of the most important findings
- any unresolved questions
- verification note that source code was not modified
