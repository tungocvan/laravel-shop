# Task: /refactor <ModuleName>

Refactor a module safely according to its docs.

## Steps

1. Read all bootstrap documents.
2. Read `ROADMAP.md`.
3. Read module docs under `docs/modules/<ModuleName>/`.
4. If `ANALYSIS.md` does not exist, run the `/analyze` workflow first.
5. Create or update `REFACTOR_PLAN.md`.
6. Implement only the scoped refactor.
7. Update tests or add focused tests for changed behavior.
8. Update `INFORMATION.md` and `README.md`.
9. Run targeted verification.

## Rules

- Never modify unrelated modules.
- Preserve behavior unless the plan documents a change.
- Keep changes reversible and reviewable.
- Do not rename public contracts without migration notes.
