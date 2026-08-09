# Task: /analyze-livewire <ModuleName> <Component>

Analyze one Livewire component and its direct dependencies. Documentation only.

## Purpose

Use this task when the user wants to understand, extend, or refactor one Livewire component without opening the scope to the entire module.

Example:

```text
/analyze-livewire Administrative Submissions/SubmissionDetail
```

## 1. Required Context

Before analysis, read when present:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
ROADMAP.md
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/INFORMATION.md
docs/modules/<ModuleName>/README.md
```

## 2. Resolve Component

Resolve exactly one Livewire PHP component under:

```text
Modules/<ModuleName>/Livewire/**
```

Also resolve its corresponding Livewire Blade view.

If multiple components match, do not guess. Mark the target as unresolved.

## 3. Inspection Scope

Inspect the component plus only direct dependencies needed to understand its behavior:

```text
Route / Page Blade
      ↓
Livewire PHP
      ↔
Livewire Blade
      ↓
Service(s)
      ↓
Model / Database

+ Shared UI components
+ Permissions / policies
+ Events / jobs
+ Upload/download paths
+ Related tests
```

Do not analyze unrelated parts of the module.

## 4. Required Analysis

Review as applicable:

- component path and alias
- purpose/responsibility
- public/locked state
- lifecycle methods (`mount`, `boot`, `hydrate`, etc.)
- validation rules/messages
- computed properties
- actions/mutations
- authorization per sensitive action
- events/dispatch/listeners
- pagination/search/filter/sort
- file upload/download behavior
- service dependencies
- direct model/database access
- transaction/concurrency concerns
- render-time queries / repeated queries
- N+1 risks
- unbounded collections
- `wire:model`, `wire:key`, loading/disabled states
- empty/error states
- shared component reuse
- Admin UI Standard compliance
- test coverage

If business logic or database writes exist directly in Livewire, record it as an architecture issue unless explicitly justified by the repository standard.

## 5. Evidence Rules

Use:

```text
Evidence   = directly observed
Inference  = reasoned from code
Assumption = unverified
Unknown    = cannot be verified from allowed scope
```

Do not invent behavior.

## 6. Output

Create or update:

```text
docs/modules/<ModuleName>/livewire/<component-key>/ANALYSIS.md
```

Use a stable lower-case component key derived from the Livewire path, for example:

```text
Submissions/SubmissionDetail
→ submissions-submission-detail
```

Recommended sections:

```text
Executive Summary
Component Purpose
Dependency Flow
Livewire PHP Analysis
Livewire Blade Analysis
State / Validation / Actions
Authorization
Service / Model Dependencies
Performance
Security / Data Integrity
UI/UX Compliance
Test Coverage
Issue List (P0/P1/P2)
Recommended Direction
Open Questions / Unknowns
```

## 7. Rules

- Documentation only.
- Never modify source code.
- Do not modify unrelated module docs.
- Source code is the truth for current behavior.
- Keep the scope at component level plus direct dependencies.
- Mark unknowns explicitly.

## Final Response

Return a short summary with:

- analyzed component
- generated/updated analysis path
- most important findings
- whether the next step should be feature change, refactor, or no change
- confirmation that application source code was not modified
