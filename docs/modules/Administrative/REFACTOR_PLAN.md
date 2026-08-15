# Administrative Refactor Plan

## Status

**ROUND 2 — PLANNING COMPLETE / AWAITING APPROVAL — 2026-08-15**

This is a follow-up refactor/closure pass after the first Administrative refactor was merged and verified.

The first refactor remains valid and completed. This second pass exists because the module has since gained additional behavior and shared UI work:

- Public branding hotfix.
- Larger deterministic demo seeders.
- Delete-all and selected-delete UX.
- Custom bounded pagination styling.
- Administrative Import/Export.
- Shared Import/Export success modal behavior.
- Shared Admin form-control standard/components.
- Updates to Codex Admin UI / module / import-export task standards.

No application source should be changed further under `/refactor-module Administrative` until this Round 2 plan is approved.

## Verified Current State

User-supplied verification from the current branch includes:

```text
Administrative targeted tests:
14 passed / 120 assertions (earlier checkpoint)

Full project regression:
356 passed / 12,858 assertions
Duration: 19.00s
```

The user also manually verified the current Administrative UI flows including:

- Delete all.
- Export all when no checkbox is selected.
- Export only selected records when checkboxes are selected.
- Importing an exported file back into the module.

## Goal

Close the current Administrative branch safely by reconciling documentation/tests/contracts with the implemented behavior, finishing UI consistency work, and validating the final branch before merge.

This is **not** a rebuild and should not introduce new domain architecture.

## Findings / Drift To Resolve

### P1 — Documentation drift

`INFORMATION.md` and `README.md` still describe the earlier refactor state and do not fully document:

- `administrative.submission.import_export` permission.
- Import/Export service and shared panel integration.
- Selected export semantics.
- Delete-all and selected-delete modal behavior.
- Demo seeders.
- Public branding model fix.
- Current regression evidence (`356 / 12,858`).

`IMPORT_EXPORT_PLAN.md` is stale: it still states `AWAITING USER APPROVAL` even though implementation was explicitly approved, implemented and manually tested.

### P1 — Import/Export contract closure

Confirm and document the implemented contract:

```text
selected_ids empty     -> export all records in approved export scope
selected_ids not empty -> export only selected records
```

Also preserve:

- exported data can be imported back for update/upsert when safe;
- existing submission updates do not overwrite system-owned lookup/version fields;
- new rows require a safe lookup-token path;
- lookup secrets are never exported/logged;
- soft-deleted matching submissions can be restored when the import mode/business rule allows it;
- unsafe raw replace behavior remains blocked;
- successful import/dry-run/export uses the shared success modal + explicit refresh action.

### P1 — Destructive action UX closure

Confirm both destructive list actions use centered modal confirmation:

- Delete selected.
- Delete all.

Both must retain backend authorization, loading/disabled state, soft-delete semantics and audit history.

### P2 — Form UI consistency

Shared Admin form primitives now exist under:

```text
Modules/Admin/resources/views/components/form/input.blade.php
Modules/Admin/resources/views/components/form/textarea.blade.php
Modules/Admin/resources/views/components/form/select.blade.php
Modules/Admin/resources/views/components/form/error.blade.php
```

The Administrative refactor should adopt these components only where doing so is low-risk and directly improves the affected Administrative screens. Do not broaden scope into a repository-wide form migration.

Specialized Admin controls (`currency-input`, `category-select`) were aligned to the new visible-border visual language. `image-upload` remains specialized and does not need forced conversion.

### P2 — Regression contract updates

Ensure focused tests lock the implemented behaviors that are easy to regress:

- delete all remains permission-protected and audited;
- selected delete uses modal confirmation contract;
- bounded/indigo pagination remains in place;
- Import/Export uses shared foundation;
- selected export IDs are passed reactively;
- Import/Export permission is declared;
- lookup secret is excluded from export;
- shared success modal/refresh behavior remains available.

Do not add brittle tests for irrelevant Tailwind class ordering.

## Compatibility Constraints

Must preserve:

- Existing public/admin route names and URLs.
- Existing table/column names.
- Existing status values.
- Private storage paths/access semantics.
- Public lookup hashing/session semantics.
- Existing Livewire aliases.
- Existing permission strings and backward-compatible fallbacks.
- Soft-delete/archive semantics.
- Transaction + row lock + optimistic version behavior for workflow transitions.

## Database / Migration Impact

Expected: **none**.

Do not rewrite historical migrations.

## Security / Authorization

Final verification must confirm:

- sensitive mutations authorize server-side;
- checkbox visibility is not treated as authorization;
- Import/Export is gated by `administrative.submission.import_export`;
- lookup token plaintext is not exported, persisted accidentally, or leaked into error reports/logs;
- file downloads remain controlled/private;
- destructive operations remain soft-delete + audit rather than raw hard delete.

## Performance

Keep:

- bounded pagination (`10/25/50/100`);
- no user-triggered `All` query branch;
- export scope independent from the current paginator page;
- synchronous export bounded by the current Administrative Import/Export implementation limit.

Do not introduce an unbounded list query simply to support bulk UI.

## Files Expected To Change After Approval

Likely documentation/tests only, plus narrowly scoped Administrative UI adoption if necessary:

```text
docs/modules/Administrative/ANALYSIS.md
docs/modules/Administrative/INFORMATION.md
docs/modules/Administrative/README.md
docs/modules/Administrative/REFACTOR_PLAN.md
docs/modules/Administrative/IMPORT_EXPORT_PLAN.md
tests/Feature/Administrative/AdministrativeRefactorContractTest.php
```

Potential low-risk UI files only if verification finds drift from the new shared form standard:

```text
Modules/Administrative/resources/views/livewire/**
```

No unrelated module refactor is approved by this plan.

Shared files already changed on this branch should only receive further edits if required to fix a verified shared regression:

```text
Modules/Shared/Livewire/ImportExport/**
Modules/Admin/resources/views/components/form/**
.codex/standards/ADMIN_UI_STANDARD.md
.codex/tasks/create-module.md
.codex/tasks/refactor-module.md
.codex/tasks/refactor-livewire.md
.codex/tasks/create-import-export.md
```

## Test Strategy

After implementation/closure updates:

1. Formatting:

```bash
vendor/bin/pint --test Modules/Administrative Modules/Admin Modules/Shared/Livewire/ImportExport tests/Feature/Administrative
```

2. Administrative regression:

```bash
php artisan test tests/Feature/Administrative
```

3. Admin/shared regression if shared UI components changed:

```bash
php artisan test tests/Feature/Admin
```

4. Frontend build when Blade/Tailwind-visible changes are included:

```bash
npm run build
```

5. Full regression before merge:

```bash
php artisan test
```

## Manual UI Acceptance

Before merge verify:

- Public `/thu-tuc-hanh-chinh` loads without branding model error.
- Demo data renders correctly.
- Search/filter/reset and bounded pagination work.
- Selected delete opens centered modal and completes safely.
- Delete all opens centered modal and completes safely.
- No checkbox selected -> export all approved-scope records.
- Checkbox selected -> export only selected records.
- Exported file can be imported back for update/upsert where supported.
- Import validation/error reporting does not expose lookup secrets.
- Import/Export success modal appears and OK refresh synchronizes the list.
- Admin form controls remain visibly bounded when empty.

## Explicit Non-Goals

- Full rebuild of Administrative.
- New migrations/statuses/routes.
- Repository-wide migration of all legacy form controls.
- Import/export of attachment binaries.
- Export of lookup secrets.
- Raw hard-delete/replace semantics.
- New queue infrastructure without measured need.

## Acceptance Criteria

Round 2 is complete only when:

- [ ] Documentation matches current implemented reality.
- [ ] `IMPORT_EXPORT_PLAN.md` reflects implementation/verification rather than awaiting approval.
- [ ] Permission documentation includes `administrative.submission.import_export`.
- [ ] Selected export contract is documented and regression-covered.
- [ ] Destructive selected/all actions use modal confirmation.
- [ ] Shared success modal behavior is verified.
- [ ] No lookup secret leakage exists in export/error contracts.
- [ ] Administrative targeted tests pass.
- [ ] Pint passes for affected scope.
- [ ] Frontend build passes when required.
- [ ] Full regression passes.
- [ ] Manual Administrative UI smoke passes.

## Approval Gate

**AWAITING USER APPROVAL**

Do not modify additional application source for this `/refactor-module Administrative` round until the user explicitly approves this Round 2 plan.
