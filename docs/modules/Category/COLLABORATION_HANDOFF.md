# Category — Collaboration Handoff (2026-10-15)

## Scope
- Branch: `fix/category-import-row-context`
- Base: `main` at `3f9cb306e`.
- Issue: mixed category types in Excel caused parent validation to use the last normalized row's shared context instead of the current row.
- Change: removed mutable row context and resolved parents by the current row's `type` and `parent_slug`; kept dependency ordering, transactional rollback and dry-run behavior.

## Tests and acceptance
- Previously reported on Admin WSL: `CategoryImportRowContextContractTest` 2 passed (10 assertions); `--filter=Category` 29 passed (177 assertions).
- Admin WSL manual UI dry-run of 38-row workbook: operator reported PASS.
- New behavioral tests: `tests/Feature/Category/CategoryImportBehaviorTest.php` cover mixed `medicine/post/product` rows with children before parents, dry-run rollback, and wrong-type parent rejection with atomic rollback.
- **New tests NOT YET RUN**: require Admin WSL pull and focused/category regression output. Do not treat these as PASS until confirmed.
- Full project regression: NOT APPLICABLE — module-scoped change.

## Production safety
- Production `/opt/projects/tnv` restored to `main` `3f9cb306e`, PHP-FPM reloaded, health healthy and HTTP 200 on `/` and `/product`.
- Never pull this feature branch into Production `main`. Deploy only after review and merge.
- No production category import performed.

## Next steps
1. On Admin WSL branch `fix/category-import-row-context`: `git pull --ff-only origin fix/category-import-row-context`.
2. Run `php artisan test tests/Feature/Category/CategoryImportBehaviorTest.php`. If FAIL, stop and report.
3. If PASS, run `php artisan test --filter=Category`.
4. Verify UI as necessary, check Git clean, review PR, obtain approval before merge.
