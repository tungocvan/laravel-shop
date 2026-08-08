# Task: /analyze <ModuleName>

Analyze a module and generate reusable documentation.

## Steps

1. Read `.codex/bootstrap/CODEX_BOOTSTRAP.md`.
2. Read `.codex/bootstrap/PROJECT_BOOTSTRAP.md`.
3. Read `.codex/bootstrap/AI_PROJECT_CONTEXT.md`.
4. Read `ROADMAP.md`.
5. Read any existing docs in `docs/modules/<ModuleName>/`.
6. Inspect only `Modules/<ModuleName>/` plus directly referenced shared files.
7. Follow the module analysis flow:

```text
Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared UI Components -> Services -> Import -> Export -> Shared Services -> Model -> Migration -> Database
```

8. Create or update `docs/modules/<ModuleName>/ANALYSIS.md`.
9. Create or update `docs/modules/<ModuleName>/INFORMATION.md`.
10. Create or update `docs/modules/<ModuleName>/README.md`.

## Rules

- Do not modify application code.
- Do not modify unrelated module docs.
- Be idempotent.
- Mark unknowns explicitly and explain how to verify them.
 