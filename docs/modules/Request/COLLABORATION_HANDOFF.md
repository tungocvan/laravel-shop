# Request Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Active refactor branch: `refactor/request-contract-ui-export-demo-alignment`
- Delivery mode: **Refactor Module / consolidated batch**
- Branch status: **VERIFIED / READY FOR REVIEW**
- Pull request: **#152 — `refactor(request): align module contract UI export and demo data`**
- Durable contract: `docs/modules/Request/MODULE.md`

## Current objective

Refactor `Modules/Request` as one consolidated delivery while preserving the mature Request domain architecture and tightening the specific gaps approved on 2026-09-02:

1. establish the missing durable `MODULE.md` contract;
2. align Admin inputs/focus/error presentation with `ADMIN_UI_STANDARD.md`;
3. align bounded pagination with Request module settings;
4. make report export semantics explicit and functional: selected rows when checkboxes are selected, otherwise the complete authorized current filter scope;
5. add deterministic local/testing demo requests sufficient to exercise pagination, status reporting and export behavior;
6. update stale contract tests to protect the approved target architecture.

## Approved architecture boundary

### KEEP

- Request domain/application architecture and existing use-case services.
- Request route ownership in `Modules/Request/routes/web.php`.
- Existing permission and authorization semantics.
- Existing 18-table persistence ownership and production data.
- Request audit/outbox/idempotency contracts.
- Private export storage, expiry and download re-authorization.
- Existing requester/approver lifecycle behavior and definition-version compatibility.
- Thin Blade route shells and current ClientPortal integration boundaries.

### REHOME

None approved.

### DELETE

None approved.

### QUARANTINE

- schema/migration and production data boundaries;
- permission names/guard semantics;
- runtime Module-state persistence owned outside Request;
- export authorization snapshots/private storage/expiry;
- audit/outbox/idempotency history.

### DEFER

- cross-module shared paginator convergence;
- unrelated ClientPortal/PWA presentation cleanup.

## Implementation checkpoint

### Durable module contract

Created `docs/modules/Request/MODULE.md` documenting canonical Request ownership/non-ownership, direct dependencies, routes/controllers/Livewire/service boundaries, 18 Request-owned persistence tables, export security semantics, compatibility/quarantine rules, refactor invariants and regression requirements.

### Admin definition UI and pagination

Updated `Modules/Request/Livewire/Admin/DefinitionIndex.php` and `Modules/Request/resources/views/livewire/admin/definition-index.blade.php`:

- removed hard-coded `paginate(25)`;
- page sizes derive from `request.settings.page_sizes`, bounded by `max_page_size`;
- default derives from `request.settings.default_page_size`;
- changing page size resets pagination;
- added `Số dòng/trang` control;
- aligned visible borders, focus rings and form error presentation;
- pagination renders only when multiple pages exist.

### Reports pagination and UI

Updated `Modules/Request/Http/Controllers/RequestReportController.php` and `Modules/Request/resources/views/admin/reports.blade.php`:

- report `per_page` validation derives from Request settings instead of a duplicated literal list;
- filter inputs/selects/date controls retain visible borders and explicit focus treatment;
- report register remains responsive with mobile cards + desktop table;
- pagination remains query-string aware and bounded.

### Selected/all export alignment

Updated `RequestExportQuery`, `PlanRequestExport`, `RequestExportController` and Reports UI.

Canonical behavior:

```text
selected_request_public_ids is non-empty
    -> normalize/deduplicate/bound IDs
    -> apply current authorization scope
    -> export only the selected authorized records

no selected_request_public_ids
    -> preserve existing behavior
    -> export all records in the authorized current filter scope
```

Security invariants retained:

- selected IDs are ULID-validated and bounded to the maximum visible page size;
- client-supplied IDs do not bypass `RequestExportQuery` authorization scope;
- unauthorized selected IDs are excluded by the authorization intersection;
- max-row planning still applies;
- private file storage, expiry, owner check, permission check and current-scope re-authorization at download remain unchanged.

The UI deliberately does not claim a global select-all. Checkboxes represent visible report rows; no selection means export the complete current filter scope.

### Local/testing demo data

Added `Modules/Request/Database/Seeders/RequestWorkflowDemoSeeder.php` and integrated it through `RequestDemoSeeder`:

- hard local/testing guard;
- deterministic `REQ-DEMO-0001` ... `REQ-DEMO-0042` request instances;
- cycles all six Request statuses;
- uses existing Request type versions and local demo actor;
- uses `firstOrNew` for stable repeated seeding;
- 42 records intentionally exceed the default 25-row page size.

No migration or production seeding behavior was added.

## Tests added / aligned

Updated stale target-architecture contracts and added:

- `tests/Feature/Request/Architecture/RequestRefactorModuleContractTest.php`
- `tests/Feature/Request/Exports/RequestSelectedExportScopeTest.php`

Coverage protects module ownership/refactor invariants, config-driven pagination, selected-export wiring and authorization intersection, no-selection export-all behavior, bounded selection size, and deterministic local/testing demo seeding.

## Scope/risk result

No approved risk gate was crossed:

- schema/database migration change: **NONE**;
- authorization permission/guard contract change: **NONE**;
- production data deletion/change: **NONE**;
- feature deletion: **NONE**;
- significant cross-module source change: **NONE**;
- route rehome: **NONE**.

## Verification evidence — PASS

Recorded from local verification on 2026-09-02:

- `./vendor/bin/pint --test Modules/Request tests/Feature/Request` — **PASS, 264 files**;
- selected-export + architecture focused tests — **PASS** after correcting a stale case-sensitive UI-copy assertion;
- `php artisan test tests/Feature/Request` — **147 passed, 6250 assertions, 9.99s**;
- `php artisan route:list --name=request` — **PASS**;
- `npm run build` — **PASS**;
- `git diff --check main...HEAD` — **PASS**;
- manual UI smoke — **PASS**.

Full-project regression was intentionally not required for this Request-scoped refactor under the agreed targeted-test policy.

## Manual UI acceptance — PASS

Verified locally:

1. `/admin/requests/admin/types`
   - input/select borders and focus/error states;
   - page-size options and pagination behavior;
   - responsive usability.
2. `/admin/requests/admin/reports`
   - filters and page-size selector;
   - pagination preserving filters;
   - selected-row CSV/XLSX export behavior;
   - no-selection export of the complete current authorized filter scope;
   - responsive card/table layouts.

## PR/merge status

PR **#152** contains the complete consolidated Request refactor. Automated local gates and manual UI acceptance are complete. The PR is ready for manual review and merge; do not split this implementation into additional PRs.

## Next step

1. Review PR #152.
2. Merge manually when satisfied.
3. After merge, refresh this handoff with the actual `main` merge checkpoint per `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
