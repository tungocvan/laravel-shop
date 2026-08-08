# Deep Architect Analysis: module-analysis.md

Source document:

```text
.codex/prompts/module-analysis.md
```

Task contract:

```text
.codex/tasks/analyze-md-architect.md
```

Assessment date: 2026-06-24

---

## 1. Executive Summary

The source document is a strong first-pass master prompt for analyzing Laravel modules in the INAFO Pharma repository. It correctly emphasizes Laravel 12, Livewire 3, modular boundaries, documentation-only output, security review, import/export review, performance analysis, and staged generation of module documentation.

The document is not weak in intent, but it is weak in execution control. It mixes rigid traversal instructions with real-world dependency discovery, uses placeholder output paths without defining how `<ModuleName>` is resolved, does not define evidence standards, and does not clearly separate observation, inference, assumption, recommendation, and unresolved question. It also contains encoding artifacts in rendered output and has a sibling copy under `docs/prompts` that references stale bootstrap paths.

Best next step: keep the purpose and documentation-only guardrail, but rewrite the prompt as an operational contract with explicit inputs, allowed reads, evidence rules, module-name resolution, uncertainty handling, output quality gates, and a final checklist.

---

## 2. Current Document Strengths

- Clear role definition: Senior Laravel 12 Architect, Livewire 3 Engineer, and System Analyst.
- Correctly forbids source-code modification during analysis.
- Requires bootstrap context before module inspection.
- Defines a module traversal order that matches normal Laravel request flow.
- Covers key architectural surfaces: routes, controllers, Blade, Livewire, services, imports, exports, models, migrations, database, security, performance, technical debt, and tests.
- Requires concrete output files for analysis, refactor planning, and rebuild planning.
- Includes issue reporting fields with priority, file, problem, and recommendation.
- Aligns with repository-level priorities from `ROADMAP.md`, especially security, authorization, import/export hardening, and module ownership.

---

## 3. Design Flaws And Risks

### Issue 1

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt requires output to `docs/modules/<ModuleName>/...` but does not define how `<ModuleName>` is discovered, validated, normalized, or confirmed. This creates a high risk of writing documentation to the wrong folder when the user gives a lowercase name, a file path, a Livewire class, or an ambiguous business term.

Recommendation:
Add a mandatory module resolution step:

```text
Resolve <ModuleName> from one of:
- explicit user argument
- existing Modules/<ModuleName> directory
- namespace found in provided file path
- existing docs/modules/<ModuleName> directory

If more than one module matches, ask for clarification before writing files.
```

### Issue 2

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The source document says "Analyze the module using this exact order", but Laravel modules often have dependencies that are easier to discover from classes, views, services, config, imports, exports, or Livewire aliases. A too-rigid order can cause missed dependencies or repeated passes.

Recommendation:
Change the language from strict execution order to reporting order. Let Codex inspect iteratively, but require the final report to be organized in the requested route-to-database sequence.

### Issue 3

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt does not define an evidence standard. It asks for deep analysis, but does not require every finding to be tied to concrete files, classes, methods, routes, migrations, or documentation.

Recommendation:
Require each material finding to include evidence:

```text
Evidence:
- file path
- class/function/view/route/table when available
- direct observation, inference, or assumption label
```

### Issue 4

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt forbids inspecting the entire project but allows broad folders such as `routes/**` and `config/**`. In this repository, module routes are under `Modules/<ModuleName>/routes`, and global route/config reads should be intentionally limited.

Recommendation:
Refine scope rules:

```text
Default allowed scope:
- Modules/<ModuleName>/**
- docs/modules/<ModuleName>/**
- .codex/bootstrap/**
- ROADMAP.md
- composer.json
- Modules/ModuleServiceProvider.php

Conditional scope:
- app/Shared, app/Support, app/Traits, app/Services, app/Helpers, app/View/Components
- Modules/Shared/**
- routes/** and config/** only when directly referenced by the target module or bootstrap docs
- another module only when a namespace import, service call, relation, event, job, route, config, or view reference proves the dependency
```

### Issue 5

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt requires reading existing docs if they exist, but does not say how to handle conflicts between documentation and code.

Recommendation:
Add conflict handling:

```text
When docs and code disagree, treat code as current behavior and docs as historical intent.
Record the mismatch in ANALYSIS.md with "Documentation Drift".
```

### Issue 6

Priority:
P2

File:
`.codex/prompts/module-analysis.md`

Problem:
The displayed Markdown contains encoding artifacts such as `â€”` and `â†“`. Even if caused by terminal decoding, these artifacts reduce prompt portability and readability.

Recommendation:
Keep prompt files in UTF-8 and prefer ASCII-safe arrows and separators in operational prompts:

```text
Route -> Controller -> Page Blade -> Livewire PHP -> ...
```

### Issue 7

Priority:
P1

File:
`docs/prompts/MASTER PROMPT - ANALYZE PROJECT MODULE LARAVEL v2.0 FINAL.md`

Problem:
The sibling prompt copy references `docs/bootstrap/...`, but this repository's canonical bootstrap files are under `.codex/bootstrap/...`. The `docs/*BOOTSTRAP.md` files are mirrors and explicitly point to the canonical `.codex/bootstrap` files.

Recommendation:
Update any maintained prompt copy to use:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

### Issue 8

Priority:
P2

File:
`.codex/prompts/module-analysis.md`

Problem:
The output file set is useful but heavy. The prompt does not define when to generate all five files in one pass versus stage them after review.

Recommendation:
Add an execution mode:

```text
Default mode: generate all five documentation files.
Staged mode: generate ANALYSIS.md, INFORMATION.md, and README.md first; generate REFACTOR_PLAN.md and REBUILD_SPEC.md only after ANALYSIS.md is reviewed.
```

### Issue 9

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt says "Every design decision must reference ANALYSIS.md and REFACTOR_PLAN.md", but `ANALYSIS.md` is produced before `REFACTOR_PLAN.md`. This rule only makes sense for `REBUILD_SPEC.md`.

Recommendation:
Scope the rule:

```text
In REBUILD_SPEC.md, every major design decision must reference the finding or recommendation from ANALYSIS.md and/or REFACTOR_PLAN.md.
```

### Issue 10

Priority:
P1

File:
`.codex/prompts/module-analysis.md`

Problem:
The prompt includes security categories but does not explicitly align priority labels with the repository roadmap's P0/P1/P2 definitions.

Recommendation:
Add priority definitions copied from the roadmap:

```text
P0: security, data-loss, secret-exposure, production-control, or irreversible data risks.
P1: correctness, maintainability, performance, testability, or module integrity risks.
P2: cleanup, developer experience, observability, or non-blocking optimization.
```

---

## 4. Missing Sections To Add

The optimized prompt should add these sections:

1. Input Contract
2. Module Resolution
3. Execution Mode
4. Evidence And Confidence Rules
5. Documentation Drift Handling
6. Dependency Boundary Rules
7. Output File Overwrite Rules
8. Quality Gate Checklist
9. Clarification Questions
10. Final Response Format

---

## 5. Clarification Questions

These questions should be asked before running the prompt only when the answer cannot be inferred from local context:

1. Which module should be analyzed if the input does not resolve to exactly one `Modules/<ModuleName>` directory?
2. Should Codex generate all five module docs in one pass, or stage the work after `ANALYSIS.md`?
3. Should existing documentation be overwritten, appended, or preserved with a timestamped backup when generated files already exist?
4. Are diagrams expected as plain text only, Mermaid, or both?

Recommended default answers when the user does not specify:

```text
Module: infer from explicit user argument or path; ask if ambiguous.
Execution mode: generate all five files.
Existing docs: read first, then overwrite only requested generated docs.
Diagrams: use plain text dependency diagrams for maximum portability.
```

---

## 6. Rewritten Content Strategy

The prompt should be reorganized into a cleaner flow:

1. Role and purpose
2. Non-negotiable guardrails
3. Inputs
4. Bootstrap reads
5. Module resolution
6. Allowed scope
7. Inspection method
8. Analysis requirements
9. Output files and structures
10. Issue format
11. Uncertainty handling
12. Quality gate
13. Final answer

This reduces ambiguity and makes the prompt easier to use as a repeatable Codex task.

---

## 7. Optimized Codex Prompt

```markdown
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

Generate:

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
```

---

## 8. Recommended File Updates

Recommended updates:

1. Replace `.codex/prompts/module-analysis.md` with the optimized prompt above.
2. Update the duplicate prompt under `docs/prompts/` if it is still maintained.
3. Keep `.codex/tasks/analyze-md-architect.md` as a meta-analysis task, but add a rule for deriving `<module_name>` from the input Markdown filename.

Suggested task output naming rule:

```text
If the input is a Markdown prompt file, use the base filename as <module_name>.
Example:
.codex/prompts/module-analysis.md
-> docs/analysis/module-analysis_DEEP_ANALYSIS.md
```

---

## 9. Final Recommendation

Recommendation:

```text
[x] Major Prompt Refactor
[ ] Minor Cleanup Only
[ ] Full Rewrite From Scratch
```

Reason:

The current prompt has the right architecture and the right safety intent, so it does not need to be discarded. It does need a major refactor to become reliable as an execution contract. The optimized version above preserves the original goals while adding module resolution, evidence requirements, uncertainty handling, scoped inspection, roadmap priority alignment, and final quality gates.

---

## 10. Needs Confirmation Before Applying To Source Prompt

Before replacing `.codex/prompts/module-analysis.md`, confirm:

1. Should the optimized prompt overwrite the current file, or should it be saved as a new version such as `.codex/prompts/module-analysis.v3.md`?
2. Should the duplicate prompt under `docs/prompts/` remain a mirror, or should `.codex/prompts/module-analysis.md` become the only canonical copy?
3. Should diagrams be plain text only, or should Mermaid diagrams be required for generated module docs?
