# Administrative Refactor Plan

## Status

**ROUND 2 — IMPLEMENTED / FINAL VERIFICATION PENDING — 2026-08-15**

Round 1 remains completed/merged. Round 2 was explicitly approved by the user and has now been implemented as a closure pass for the newer Administrative features and UI standards.

## Implemented Round 2 Scope

- Public branding fix to the correct System Setting model.
- Deterministic demo seeders without Faker.
- Delete-all soft-delete/audit flow with centered confirmation modal.
- Selected-delete centered confirmation modal.
- Bounded pagination `10/25/50/100` with indigo active styling.
- Shared Administrative Import/Export implementation.
- Selected export semantics:

```text
selected_ids empty     -> export all records in approved export scope
selected_ids not empty -> export selected records only
```

- Safe export/import round-trip for supported update/upsert flows.
- Shared Import/Export success modal + explicit refresh action.
- Import/Export permission `administrative.submission.import_export`.
- Shared Admin visible-border form-control primitives and corresponding UI standard/task updates.
- Regression contract coverage for the high-value Round 2 behaviors.
- Documentation reconciliation across Administrative analysis/information/readme/import-export plan.

## Compatibility Preserved

- Existing public/admin route names and URLs.
- Existing database schema/status values.
- Existing storage paths/private file semantics.
- Existing Livewire aliases.
- Existing permission strings and backward-compatible processing/dashboard/history fallbacks.
- Public lookup hashing/session semantics.
- Soft-delete/archive rather than hard delete.
- Transaction + row lock + optimistic version processing semantics.

## Security / Integrity Guarantees

- Sensitive actions remain server-authorized.
- Import/Export uses a named permission.
- Lookup token plaintext is never exported.
- Import errors redact lookup secrets.
- Existing system-owned lookup/version fields are preserved during update/upsert.
- Unsafe raw replace mode is blocked.
- Destructive actions remain soft-delete + audit.
- File downloads remain controlled/private.

## Performance Guarantees

- No unbounded `All` page size.
- List screens remain paginated.
- Export scope is independent from the current paginator page.
- Selected export uses selected IDs as query scope.
- Synchronous export remains bounded by the Administrative implementation limit.

## Regression Contract

`tests/Feature/Administrative/AdministrativeRefactorContractTest.php` covers:

- file model resolution;
- bounded page-size/paginator contracts;
- archive audit;
- processing permission compatibility;
- delete-all authorization/audit;
- selected-delete modal confirmation;
- indigo pagination view;
- shared Import/Export foundation;
- reactive selected IDs;
- Import/Export permission;
- success modal/refresh contract;
- lookup secret exclusion/redaction.

## Verification Evidence So Far

User-supplied checkpoint:

```text
php artisan test
356 passed
12,858 assertions
0 failed
Duration: 19.00s
```

Manual UI smoke already passed for:

- Delete all.
- Export all with no checkbox selected.
- Export selected rows.
- Importing an exported Administrative file back into the system.

## Final Verification Required Before Merge

Run after pulling the latest Round 2 closure commits:

```bash
git pull origin agent/administrative-demo-seeders-fix
php artisan optimize:clear

vendor/bin/pint --test Modules/Administrative Modules/Admin Modules/Shared/Livewire/ImportExport tests/Feature/Administrative
php artisan test tests/Feature/Administrative
npm run build
php artisan test
```

If shared Admin tests exist for the touched shared form components, also run:

```bash
php artisan test tests/Feature/Admin
```

## Final Manual UI Acceptance

Confirm after the latest pull:

- `/thu-tuc-hanh-chinh` loads without branding error.
- Search/filter/reset/pagination remain correct.
- Selected delete modal opens and completes safely.
- Delete all modal opens and completes safely.
- No selected checkbox -> export all approved scope.
- Selected checkbox(es) -> export selected only.
- Exported data can be imported back for supported update/upsert.
- Import/Export success modal appears and OK refresh works.
- Empty Admin form controls remain visibly bounded.

## Acceptance Criteria

- [x] Documentation reconciled with current implementation.
- [x] Import/Export plan no longer says awaiting approval.
- [x] `administrative.submission.import_export` documented.
- [x] Selected export contract implemented/documented/regression-covered.
- [x] Selected/all destructive actions use modal confirmation.
- [x] Shared success modal contract implemented.
- [x] Lookup secret leakage protections documented and regression-covered.
- [x] Manual core Administrative UI flows verified at an earlier checkpoint.
- [ ] Final Pint pass on latest branch.
- [ ] Final Administrative targeted tests pass on latest branch.
- [ ] Frontend build pass on latest branch.
- [ ] Final full regression pass on latest branch.

## Final Decision

No further architecture refactor is planned in Round 2. If the final verification passes, the branch is ready for merge into `main`.
