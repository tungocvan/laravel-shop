# Codex Workspace For INAFO Pharma

This `.codex` directory is a reusable AI development workspace for this Laravel 12 project. It is designed to be committed to Git and reused across machines, operating systems, and OpenAI accounts.

## What Is Included

- `bootstrap/`: project rules and architecture context that every AI session should read.
- `prompts/`: reusable prompts for module analysis, refactoring, rebuilds, imports/exports, Livewire, migrations, services, admin UI, and testing.
- `tasks/`: command-style workflows for `/analyze`, `/refactor`, `/rebuild`, `/create-module`, `/import-export`, and `/generate-docs`.
- `agents/`: role-based AI team contracts for Laravel module architecture, development, review, UI, database, services, import/export, security, performance, and documentation.
- `commands/`: reusable `@agent command <ModuleName>` command definitions.
- `workflows/`: multi-agent workflows for analysis, rebuild, import/export, and review.
- `templates/`: markdown structures for module documentation.
- `snippets/`: implementation patterns for common Laravel module files.
- `checklists/`: module, security, performance, and deployment review checklists.
- `settings/`: shared AI workspace preferences.

## AI Agent Team For Laravel 12 Modules

This workspace includes a professional AI agent team under `.codex/agents/`. Use these agents when you want a role-specific workflow instead of a single general prompt.

Examples:

```text
@architect analyze Category
@laravel-developer rebuild Category
@import-export-specialist create Pharma
@ui-designer design User
@reviewer review User
```

Recommended agent workflows:

```text
@architect analyze <ModuleName>
@database-architect analyze <ModuleName>
@service-layer-specialist refactor-plan <ModuleName>
@documentation-writer generate <ModuleName>
```

```text
@architect rebuild-spec <ModuleName>
@laravel-developer rebuild <ModuleName>
@livewire-developer refactor <ModuleName>
@reviewer review <ModuleName>
```

```text
@import-export-specialist create <ModuleName>
@livewire-developer fix-ui <ModuleName>
@reviewer review <ModuleName>
```

```text
@security-reviewer review <ModuleName>
@performance-reviewer review <ModuleName>
@reviewer final-check <ModuleName>
```

Read `.codex/agents/README.md` for the complete agent roster, when to use each agent, and how to copy the system to another project.

## Install

Commit this directory with the repository:

```bash
git add .codex docs/modules/.gitkeep
git commit -m "Add reusable Codex workspace"
```

On another machine, clone the repository and the workspace is available immediately. No personal OpenAI account state is required because the instructions live in Git.

## Required Reading For AI Sessions

Before code work, ask the AI agent to read:

```text
.codex/bootstrap/CODEX_BOOTSTRAP.md
.codex/bootstrap/PROJECT_BOOTSTRAP.md
.codex/bootstrap/AI_PROJECT_CONTEXT.md
ROADMAP.md
```

For module work, also read:

```text
docs/modules/<ModuleName>/ANALYSIS.md
docs/modules/<ModuleName>/REFACTOR_PLAN.md
docs/modules/<ModuleName>/REBUILD_SPEC.md
docs/modules/<ModuleName>/INFORMATION.md
```

Only read module docs that exist.

## How To Use Tasks

Use these command-style prompts in any AI coding assistant:

```text
/analyze Product
/refactor Product
/rebuild Product
/create-module Supplier
/import-export Product
/generate-docs Product
```

The task definitions are stored in `.codex/tasks/`. Paste the relevant task file into the AI chat or configure your AI tool to load it as a reusable command.

Agent command definitions are stored in `.codex/commands/`. Multi-agent playbooks are stored in `.codex/workflows/`.

## How To Create Module Docs

Run:

```text
/analyze <ModuleName>
```

The AI should inspect the module in this order:

```text
Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared UI Components -> Services -> Import -> Export -> Shared Services -> Model -> Migration -> Database
```

Generated docs belong in:

```text
docs/modules/<ModuleName>/
```

Recommended files:

- `ANALYSIS.md`
- `REFACTOR_PLAN.md`
- `REBUILD_SPEC.md`
- `INFORMATION.md`
- `README.md`

## How To Run Safely

- Always begin with analysis for an existing module.
- Never modify unrelated modules.
- Never overwrite files before reading them.
- Keep changes idempotent.
- Follow the roadmap priorities, especially P0 security tasks.
- Use shared import/export services before creating new import/export infrastructure.
- Run tests or document why tests could not be run.

## How To Share Via Git

The workspace is intentionally plain markdown. Commit it like normal source code:

```bash
git add .codex docs/modules/.gitkeep
git commit -m "Add Codex AI development workspace"
git push
```

Future contributors can use the same prompts and task workflows regardless of their editor, AI account, or local operating system.
