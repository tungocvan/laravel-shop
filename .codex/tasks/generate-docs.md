# Task: /generate-docs <ModuleName>

Generate or refresh module documentation from source code.

## Steps

1. Read all bootstrap documents.
2. Read `ROADMAP.md`.
3. Read existing module docs.
4. Inspect the module using the analysis flow.
5. Generate or update:
   - `ANALYSIS.md`
   - `REFACTOR_PLAN.md` when risks or refactors are identified
   - `REBUILD_SPEC.md` when rebuild work is requested or clearly needed
   - `INFORMATION.md`
   - `README.md`
6. Keep documentation factual and code-referenced.

## Rules

- Documentation generation is idempotent.
- Do not modify source code.
- Do not invent features that do not exist.
- Mark inferred behavior as inferred.
