# Task: /analyze-project <ModuleName>

# MASTER PROMPT — ANALYZE PROJECT MODULE LARAVEL v2.0 FINAL

## 0. Command Purpose

This command is used by Codex or any AI coding agent to analyze a Laravel module and automatically generate full reusable documentation for that module.

Command format:

```text
/analyze-project <ModuleName>
```

Example:

```text
/analyze-project Category
/analyze-project User
/analyze-project Pharma
/analyze-project Admin
```

When this command is executed, replace `<ModuleName>` with the actual module name.

Target module path:

```text
Modules/<ModuleName>
```

Target documentation path:

```text
docs/modules/<ModuleName>/
```

---

## 1. Role

You are a:

- Senior Laravel 12 Architect
- Senior Livewire 3 Engineer
- Senior Software System Analyst
- Senior Database Reviewer
- Senior Security Reviewer
- Senior Import/Export Architecture Reviewer

Your task is to analyze the requested module and generate high-quality technical documentation that can later be used for:

- module refactoring
- module rebuilding
- security hardening
- performance optimization
- database improvement
- import/export redesign
- AI-assisted implementation
- long-term project maintenance

---

## 2. Absolute Rules

This task is documentation-only.

You MUST NOT:

- modify application source code
- refactor source code
- generate implementation code
- rename source files
- delete source files
- create migrations
- create controllers
- create Livewire components
- create services
- create models
- modify unrelated modules

You MAY ONLY create or update documentation files under:

```text
docs/modules/<ModuleName>/
```

Allowed output files:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
```

---

## 3. Bootstrap Phase

Before analyzing the module, read these files if they exist:

```text
composer.json
Modules/ModuleServiceProvider.php

docs/CODEX_BOOTSTRAP.md
docs/PROJECT_BOOTSTRAP.md
docs/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

From these files, determine:

- Laravel version
- PHP version
- module autoload architecture
- module namespace rules
- service provider registration rules
- route loading rules
- view loading rules
- Livewire component conventions
- shared service conventions
- shared UI component conventions
- shared import/export foundation
- project coding standards
- project security standards
- project performance standards
- project documentation standards

If any bootstrap file does not exist, continue without failing and mention it in `ANALYSIS.md`.

---

## 4. Existing Documentation Phase

Read existing documentation if it exists:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
```

Use existing documentation as context, but verify it against the actual source code.

If existing documentation is outdated or inconsistent with the current code, mention it clearly.

---

## 5. Scope Rules

Primary allowed paths:

```text
Modules/<ModuleName>/**
resources/views/modules/<ModuleName>/**
resources/views/<ModuleName>/**
routes/**
config/**
database/migrations/**
database/seeders/**
```

Shared paths allowed only when directly referenced:

```text
app/Shared/**
app/Support/**
app/Traits/**
app/Services/**
app/Helpers/**
app/View/Components/**
resources/views/components/**
resources/views/livewire/**
```

You may inspect another module only when:

- it is directly imported by namespace
- it is directly referenced by a model relationship
- it is directly called by a service
- it is required to understand the current module dependency

Do NOT inspect the entire project unnecessarily.

Do NOT analyze unrelated modules.

---

## 6. Module Analysis Flow

Analyze the module in this exact order:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared UI Components
→ Service
→ Import
→ Export
→ Shared Services
→ Model
→ Migration
→ Database
```

For each layer, identify:

- file paths
- responsibilities
- dependencies
- problems
- risks
- duplicated logic
- missing validation
- missing authorization
- performance concerns
- maintainability concerns
- recommendations

---

## 7. Dependency Analysis

Generate a dependency graph for the module.

Include:

- route dependencies
- controller dependencies
- Livewire dependencies
- blade dependencies
- service dependencies
- import dependencies
- export dependencies
- model dependencies
- database dependencies
- shared component dependencies
- cross-module dependencies
- circular dependencies if any

Use this format when possible:

```text
Route
↓
Controller
↓
Page Blade
↓
Livewire Component
↓
Service
↓
Model
↓
Database
```

---

## 8. Priority Rules

Every issue and recommendation must use one of these priorities:

```text
P0 = Critical issue. Must fix before rebuild/refactor.
P1 = Important issue. Should fix during refactor.
P2 = Nice-to-have improvement. Can be scheduled later.
```

Every issue MUST contain:

```text
Priority:
File:
Problem:
Root Cause:
Business Impact:
Technical Impact:
Recommendation:
```

Every file path must be exact.

Do not write vague recommendations.

---

# STEP 01 — Generate Module Analysis Documentation

Create or update these files:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
```

---

## 9. ANALYSIS.md Required Structure

Generate `docs/modules/<ModuleName>/ANALYSIS.md` with this structure:

```md
# <ModuleName> Module Analysis

## 1. Executive Summary

## 2. Bootstrap Context

## 3. Module Purpose

## 4. Module Overview

## 5. Dependency Graph

## 6. Route Analysis

## 7. Controller Analysis

## 8. Page Blade Analysis

## 9. Livewire PHP Analysis

## 10. Livewire Blade Analysis

## 11. Shared UI Component Analysis

## 12. Service Analysis

## 13. Import Analysis

## 14. Export Analysis

## 15. Shared Service Analysis

## 16. Model Analysis

## 17. Migration and Database Analysis

## 18. Security Analysis

## 19. Performance Analysis

## 20. Validation Analysis

## 21. Authorization and Permission Analysis

## 22. Transaction and Data Integrity Analysis

## 23. Cross Module Dependency Analysis

## 24. Technical Debt Analysis

## 25. Test Coverage Analysis

## 26. Module Health Score

## 27. Issue List

## 28. Final Recommendation
```

---

### 9.1 Executive Summary

Include:

- what the module does
- business purpose
- current condition
- main risks
- whether it should be refactored or rebuilt

---

### 9.2 Bootstrap Context

Include:

- detected Laravel version
- detected PHP version
- detected module architecture
- detected namespace conventions
- missing bootstrap files if any

---

### 9.3 Module Overview

Include:

- main features
- routes
- controllers
- Livewire components
- services
- models
- database tables
- import/export classes
- shared dependencies

---

### 9.4 Route Analysis

Create a table:

```md
| Method | URI | Name | Middleware | Controller/Action | Permission | Notes |
|--------|-----|------|------------|-------------------|------------|-------|
```

---

### 9.5 Controller Analysis

For each controller:

```md
### <ControllerFilePath>

Responsibilities:

Issues:

Recommendations:
```

---

### 9.6 Livewire Analysis

For each Livewire component:

```md
### <LivewireFilePath>

Responsibilities:
State Properties:
Validation:
Events:
Pagination:
Search/Filter/Sort:
Performance Concerns:
Issues:
Recommendations:
```

---

### 9.7 Service Analysis

For each service:

```md
### <ServiceFilePath>

Responsibilities:
Public Methods:
Transaction Boundaries:
Business Rules:
Issues:
Recommendations:
```

---

### 9.8 Import Analysis

Include:

- import classes
- Excel handling
- header mapping
- column mapping
- row normalization
- row validation
- duplicate handling
- error reporting
- memory/chunk strategy

---

### 9.9 Export Analysis

Include:

- export classes
- export query
- export mapping
- template generation
- large export strategy
- memory usage

---

### 9.10 Model Analysis

Include:

- model class
- table name
- fillable fields
- casts
- relationships
- scopes
- accessors/mutators
- soft delete
- mass assignment risks

---

### 9.11 Database Analysis

Include:

- tables
- columns
- indexes
- foreign keys
- constraints
- missing indexes
- nullable issues
- unique constraints
- cascade behavior
- migration risks

---

### 9.12 Security Analysis

Review:

- authentication
- authorization
- route middleware
- policy/gate usage
- Livewire action protection
- validation
- mass assignment
- file upload
- XSS
- SQL injection
- sensitive data exposure
- permission leaks

---

### 9.13 Performance Analysis

Review:

- N+1 queries
- eager loading
- pagination
- heavy queries
- unnecessary collections
- caching opportunities
- import performance
- export performance
- memory usage
- chunk processing
- queue opportunities

---

### 9.14 Technical Debt Analysis

Score each area from 0 to 100:

```md
| Area | Score | Notes |
|------|-------|-------|
| Architecture |  |  |
| Security |  |  |
| Performance |  |  |
| Maintainability |  |  |
| Testability |  |  |
| Documentation |  |  |
```

---

### 9.15 Test Coverage Analysis

Review or recommend:

- route tests
- feature tests
- Livewire tests
- service tests
- import tests
- export tests
- authorization tests
- validation tests

---

### 9.16 Module Health Score

Use this format:

```md
## Module Health Score

| Area | Score |
|------|-------|
| Architecture |  |
| Security |  |
| Performance |  |
| Maintainability |  |
| Testability |  |
| Documentation |  |

Overall Grade: A / B / C / D / F
```

---

### 9.17 Final Recommendation

Choose exactly one:

```md
- [ ] Minor Refactor
- [ ] Major Refactor
- [ ] Full Rebuild
```

Explain the reason.

---

## 10. INFORMATION.md Required Structure

Generate `docs/modules/<ModuleName>/INFORMATION.md`:

```md
# <ModuleName> Module Information

## 1. Purpose

## 2. Features

## 3. Routes

## 4. Permissions

## 5. Controllers

## 6. Livewire Components

## 7. Blade Views

## 8. Services

## 9. Import Classes

## 10. Export Classes

## 11. Models

## 12. Database Tables

## 13. Relationships

## 14. Shared Dependencies

## 15. Events

## 16. Jobs and Queues

## 17. Configuration

## 18. Environment Variables

## 19. Known Limitations

## 20. Maintenance Notes
```

This file should be factual and descriptive.

Do not include long refactor plans here.

---

## 11. README.md Required Structure

Generate `docs/modules/<ModuleName>/README.md`:

```md
# <ModuleName> Module README

## 1. Module Overview

## 2. Installation / Registration

## 3. Routes

## 4. Permissions

## 5. Features

## 6. UI Pages

## 7. Livewire Components

## 8. Import

## 9. Export

## 10. Configuration

## 11. Events

## 12. Jobs

## 13. Developer Notes

## 14. Future Improvements
```

This file should help another developer quickly understand and work with the module.

---

# STEP 02 — Generate Refactor Plan

After completing STEP 01, read:

```text
docs/modules/<ModuleName>/ANALYSIS.md
```

Then create or update:

```text
docs/modules/<ModuleName>/REFACTOR_PLAN.md
```

---

## 12. REFACTOR_PLAN.md Required Structure

```md
# <ModuleName> Refactor Plan

## 1. Executive Summary

## 2. P0 Critical Fixes

## 3. P1 Important Refactors

## 4. P2 Nice To Have Improvements

## 5. Recommended Implementation Order

### Phase 1: Safety and Security

### Phase 2: Correctness and Maintainability

### Phase 3: Performance and Cleanup

## 6. Files Change Matrix

## 7. Risk Control

## 8. Acceptance Criteria Summary
```

---

### 12.1 Each P0/P1/P2 Item Must Include

```md
### <Issue Title>

- Issue:
- Root Cause:
- Business Impact:
- Technical Impact:
- Proposed Solution:
- Files To Change:
- Risk Level:
- Complexity:
- Estimated Effort:
- Acceptance Criteria:
```

---

### 12.2 Files Change Matrix

Create a table:

```md
| File Path | Change Type | Priority | Reason |
|----------|-------------|----------|--------|
```

---

### 12.3 Risk Control

Explain:

- what must not be changed yet
- migration risks
- backward compatibility risks
- user data risks
- permission risks
- import/export data risks

---

# STEP 03 — Generate Rebuild Specification

After completing STEP 02, read:

```text
docs/modules/<ModuleName>/REFACTOR_PLAN.md
```

Then create or update:

```text
docs/modules/<ModuleName>/REBUILD_SPEC.md
```

---

## 13. REBUILD_SPEC.md Required Structure

```md
# <ModuleName> Rebuild Specification

## 1. Goal

## 2. Target Architecture

## 3. Database Design

## 4. Model Design

## 5. Service Design

## 6. Livewire Design

## 7. Blade/UI Design

## 8. Import Design

## 9. Export Design

## 10. Permissions and Authorization

## 11. Transactions and Data Integrity

## 12. Performance Strategy

## 13. Shared Foundation Integration

## 14. Event and Listener Design

## 15. Queue Design

## 16. Cache Design

## 17. Logging Strategy

## 18. Monitoring Strategy

## 19. Rollback Strategy

## 20. Test Strategy

## 21. Deployment Checklist

## 22. Implementation Checklist
```

---

### 13.1 Target Architecture

Use this flow:

```text
Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration
→ Database
```

---

### 13.2 Database Design

Include:

- tables
- columns
- indexes
- foreign keys
- constraints
- migration notes
- compatibility risks

---

### 13.3 Model Design

Include:

- model classes
- fillable fields
- casts
- relationships
- scopes
- accessors
- mutators
- soft delete strategy

---

### 13.4 Service Design

Include:

- service classes
- public methods
- responsibilities
- transaction boundaries
- business rules
- validation boundaries

---

### 13.5 Livewire Design

Include:

- component list
- state properties
- validation rules
- events
- pagination
- search/filter/sort behavior
- bulk actions
- modal behavior
- upload behavior if applicable

---

### 13.6 Blade/UI Design

Include:

- page blade files
- Livewire blade files
- shared components
- AdminLTE layout rules
- Bootstrap rules
- table design
- form design
- filter design
- import/export panel design
- empty state design
- loading state design

---

### 13.7 Import Design

Include:

- import classes
- header mapping
- column mapping
- row normalization
- row validation
- duplicate handling
- error reporting
- dry run strategy if needed
- chunk processing
- transaction strategy

---

### 13.8 Export Design

Include:

- export classes
- export query
- export mapping
- template generation if needed
- large export strategy
- memory strategy
- queued export strategy if needed

---

### 13.9 Permissions and Authorization

Include:

- required permissions
- route middleware
- policy checks
- gate checks
- Livewire action protection
- bulk action protection

---

### 13.10 Transactions and Data Integrity

Include:

- actions requiring DB transactions
- rollback conditions
- idempotency concerns
- duplicate prevention
- audit log needs if applicable

---

### 13.11 Performance Strategy

Include:

- eager loading
- query optimization
- indexes
- pagination
- caching
- chunking
- queues
- large import/export protection

---

### 13.12 Shared Foundation Integration

Include:

- shared services
- shared import/export foundation
- shared UI components
- traits
- helpers
- base classes
- reusable patterns

---

### 13.13 Test Strategy

Include:

- route tests
- feature tests
- Livewire tests
- service tests
- import tests
- export tests
- authorization tests
- validation tests
- database tests

---

### 13.14 Implementation Checklist

Group checklist by:

```md
### P0

### P1

### P2
```

Every checklist item should reference the related issue from `ANALYSIS.md` or `REFACTOR_PLAN.md`.

---

## 14. Final Validation Checklist

Before finishing, verify:

```md
- [ ] ANALYSIS.md created or updated
- [ ] INFORMATION.md created or updated
- [ ] README.md created or updated
- [ ] REFACTOR_PLAN.md created or updated
- [ ] REBUILD_SPEC.md created or updated
- [ ] All issues include priority
- [ ] All recommendations include exact file path
- [ ] No source code was modified
- [ ] No unrelated module was touched
- [ ] All uncertain decisions are marked as "Needs confirmation before coding"
```

---

## 15. Final Response Format

After completing the task, respond with:

```md
# /analyze-project <ModuleName> Completed

Generated files:

- docs/modules/<ModuleName>/ANALYSIS.md
- docs/modules/<ModuleName>/INFORMATION.md
- docs/modules/<ModuleName>/README.md
- docs/modules/<ModuleName>/REFACTOR_PLAN.md
- docs/modules/<ModuleName>/REBUILD_SPEC.md

Summary:

- Overall module status:
- Recommendation:
- P0 issues:
- P1 issues:
- P2 issues:
- Next suggested command:
```

Recommended next commands:

```text
/refactor-module <ModuleName>
/rebuild-module <ModuleName>
/review-module <ModuleName>
```

---

## 16. Uncertainty Rule

If any design decision cannot be confirmed from the source code or documentation, write:

```text
Needs confirmation before coding
```

Do not guess.

Do not invent missing architecture.

Do not assume a service exists if no file exists.

Do not assume authorization exists if no middleware, policy, gate, or permission check is found.

---

## 17. Quality Standard

The generated documentation must be:

- specific
- file-path based
- actionable
- prioritized
- suitable for Laravel 12
- suitable for Livewire 3
- suitable for modular architecture
- useful for future AI coding agents
- safe for incremental refactor
- clear enough for a human developer to implement later
