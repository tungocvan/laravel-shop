# Task: /create-module-from-docs <SourceModule> <TargetModule>

Derive a specification for a new independent Laravel module from an existing module and its documentation.

This task does NOT directly implement the target module.

## Purpose

Use an existing module as a business/domain reference without cloning its implementation. Convert verified knowledge into a target specification that can later be implemented through the canonical `/create-module` workflow.

## 1. Required Context

Before planning, read when present:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
.codex/standards/MODULE_STANDARD.md
.codex/standards/ADMIN_UI_STANDARD.md
ROADMAP.md
```

Read source documentation when present:

```text
docs/modules/<SourceModule>/ANALYSIS.md
docs/modules/<SourceModule>/INFORMATION.md
docs/modules/<SourceModule>/README.md
```

Read `REFACTOR_PLAN.md` or `REBUILD_SPEC.md` only when they exist and are relevant to the requested target intent.

Verify material behavior against `Modules/<SourceModule>/**` when documentation is incomplete, stale, ambiguous, or when a target design decision depends on current implementation.

## 2. Source-of-Truth Rules

Use:

```text
Source documentation = business/design context
Source code          = verification of current behavior
Canonical standards = target architecture and quality rules
User request         = target intent
```

Do not treat stale documentation as stronger evidence than current source.

## 3. Independence Rules

The target module must be independent unless the specification explicitly defines a shared dependency.

Never silently reuse source-specific:

- route names/prefixes
- view namespaces
- permissions
- database tables
- migration timestamps
- cache keys
- storage paths
- config keys
- queue names
- public identifiers/tokens

Do not copy source files directly.

Do not modify `SourceModule`.

## 4. Specification Phase

Create or update only:

```text
docs/modules/<TargetModule>/CREATE_PLAN.md
```

The plan must define as applicable:

- target purpose and scope
- features to preserve, omit, or change
- module structure
- routes and route names
- permissions/authorization
- controllers
- Livewire components
- Blade/UI requirements
- services/workflows
- models and relationships
- target database tables
- migrations/indexes/constraints
- transactions/concurrency
- imports/exports
- events/jobs/queues
- shared dependencies
- cache/storage/config
- security requirements
- compatibility/isolation requirements
- seeder/sample-data requirement only when explicitly useful
- test/acceptance criteria
- unresolved decisions

Do not automatically generate `ANALYSIS.md`, `REFACTOR_PLAN.md`, or `REBUILD_SPEC.md` for the new module before it exists.

## 5. Approval Gate

After creating/updating `CREATE_PLAN.md`, STOP.

Implementation is forbidden until the user explicitly approves the target plan.

## 6. Implementation

After approval, implement the target module using the canonical rules in:

```text
.codex/tasks/create-module.md
```

Treat the approved `docs/modules/<TargetModule>/CREATE_PLAN.md` as the requirement input for `/create-module`.

Do not maintain a second independent module-creation engine in this task.

After implementation and verification, documentation may be generated/refreshed through `/analyze <TargetModule>`.

## 7. Seeder Rule

Seeder/sample data is optional, not automatic.

Generate it only when requested by the user or required by the approved plan. It must use target tables only, respect relationships, avoid overwriting real data, and separate required system data from demo/test data.

## 8. Uncertainty Rule

If source documentation/code does not establish a material business rule:

```text
Needs confirmation before coding
```

Do not guess.

## Final Response

Return a short summary containing:

- source module
- target module
- generated/updated `CREATE_PLAN.md`
- major preserved/changed behaviors
- unresolved decisions
- confirmation that no target application code was implemented before approval
