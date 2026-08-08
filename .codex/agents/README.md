# AI Agent Team For Laravel 12 Modules

This directory defines a reusable AI development team for the INAFO Pharma Laravel 12 modular project. The agents are plain markdown contracts that can be copied into any AI coding assistant.

## Required Context

Before any agent works, it must read:

1. `docs/CODEX_BOOTSTRAP.md`
2. `docs/AI_PROJECT_CONTEXT.md`
3. `docs/PROJECT_BOOTSTRAP.md`
4. `ROADMAP.md`
5. `Modules/ModuleServiceProvider.php`
6. `composer.json`

For module work, also read existing docs under:

```text
docs/modules/<ModuleName>/
```

## Agents

| Agent | Use When |
|---|---|
| `@architect` | Analyze module architecture, create refactor plans, or write rebuild specs. |
| `@laravel-developer` | Rebuild, refactor, or fix Laravel module code. |
| `@livewire-developer` | Create or improve Livewire 3 module UI. |
| `@import-export-specialist` | Build, refactor, or debug import/export features. |
| `@ui-designer` | Design clean admin CRUD UI and reusable Blade/Livewire screens. |
| `@database-architect` | Review migrations, models, casts, relationships, and indexes. |
| `@service-layer-specialist` | Extract service classes and improve service boundaries. |
| `@security-reviewer` | Review authorization, validation, file handling, and input safety. |
| `@performance-reviewer` | Review queries, pagination, indexes, rendering, and import/export memory use. |
| `@documentation-writer` | Generate module README and information docs. |
| `@reviewer` | Perform senior final review across architecture, code, UI, docs, security, and performance. |

## Example Commands

```text
@architect analyze Category
@architect plan Category
@architect rebuild-spec Category
@laravel-developer rebuild Category
@import-export-specialist create Pharma
@ui-designer design User
@reviewer review User
```

## Recommended Workflow

For module analysis:

```text
@architect analyze <ModuleName>
@database-architect analyze <ModuleName>
@service-layer-specialist refactor-plan <ModuleName>
@documentation-writer generate <ModuleName>
```

For module rebuild:

```text
@architect rebuild-spec <ModuleName>
@laravel-developer rebuild <ModuleName>
@livewire-developer refactor <ModuleName>
@reviewer review <ModuleName>
```

For import/export:

```text
@import-export-specialist create <ModuleName>
@livewire-developer fix-ui <ModuleName>
@reviewer review <ModuleName>
```

For final review:

```text
@security-reviewer review <ModuleName>
@performance-reviewer review <ModuleName>
@reviewer final-check <ModuleName>
```

## Copy To Another Project

Copy `.codex/agents`, `.codex/commands`, `.codex/workflows`, and the bootstrap documents. Then update:

- project stack
- module provider behavior
- route registration strategy
- shared service names
- UI framework
- import/export foundation
- documentation paths

## Commit To Git

```bash
git add .codex docs/modules/.gitkeep
git commit -m "Add Laravel AI agent workspace"
```

These files are account-neutral and machine-neutral. Do not commit API keys, local paths, editor cache files, or personal account state.
