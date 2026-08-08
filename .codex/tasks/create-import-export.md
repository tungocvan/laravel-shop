# Task: /import-export <ModuleName>

Create or improve import/export for a module.

## Steps

1. Read all bootstrap documents and `ROADMAP.md`.
2. Read `Modules/Shared/Services/ImportExport`.
3. Read target module services, imports, exports, Livewire components, models, migrations, and docs.
4. Define the import/export contract in docs.
5. Implement header mapping, normalization, validation, transactions, duplicate detection, storage, reports, and cleanup.
6. Add queue/progress behavior for large workloads.
7. Add tests or document verification.
8. Update `docs/modules/<ModuleName>/INFORMATION.md`.

## Rules

- Use private storage for sensitive files.
- Never trust browser-provided paths.
- Never load production-sized files entirely into memory.
- Do not create a second shared framework when `Modules/Shared/Services/ImportExport` can be extended.
