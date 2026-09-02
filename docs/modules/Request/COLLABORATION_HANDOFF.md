# Request Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Active refactor branch: `refactor/request-contract-ui-export-demo-alignment`
- Delivery mode: **Refactor Module / consolidated batch**
- Branch status: **IMPLEMENTED / PR-CHECKPOINT PENDING CI + MANUAL UI**
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

Created:

```text
docs/modules/Request/MODULE.md
```

The contract records canonical Request ownership/non-ownership, direct dependencies, canonical routes/controllers/Livewire/service boundaries, 18 Request-owned persistence tables, export security semantics, compatibility/quarantine rules, refactor invariants and regression requirements.

### Admin definition UI and pagination

Updated:

```text
Modules/Request/Livewire/Admin/DefinitionIndex.php
Modules/Request/resources/views/livewire/admin/definition-index.blade.php
```

Key changes:

- removed hard-coded `paginate(25)`;
- page sizes now derive from `request.settings.page_sizes`, constrained by `max_page_size`;
- default derives from `request.settings.default_page_size`;
- changing page size resets pagination;
- added `Số dòng/trang` control;
- aligned visible borders, focus rings and form error presentation;
- pagination renders only when multiple pages exist.

### Reports pagination and UI

Updated:

```text
Modules/Request/Http/Controllers/RequestReportController.php
Modules/Request/resources/views/admin/reports.blade.php
```

Key changes:

- report `per_page` validation now derives from Request settings instead of a duplicated literal list;
- filter inputs/selects/date controls retain visible borders and explicit focus treatment;
- report register remains responsive with mobile cards + desktop table;
- pagination remains query-string aware and bounded.

### Selected/all export alignment

Updated:

```text
Modules/Request/Application/Services/RequestExportQuery.php
Modules/Request/Application/Services/PlanRequestExport.php
Modules/Request/Http/Controllers/RequestExportController.php
Modules/Request/resources/views/admin/reports.blade.php
```

Canonical behavior implemented:

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

The UI deliberately does not claim a global select-all. Checkboxes represent visible report rows; the user-facing copy explicitly states that no selection means export the complete current filter scope.

### Local/testing demo data

Added:

```text
Modules/Request/Database/Seeders/RequestWorkflowDemoSeeder.php
```

Integrated through:

```text
Modules/Request/Database/Seeders/RequestDemoSeeder.php
```

Behavior:

- hard local/testing guard inside the workflow fixture seeder;
- deterministic `REQ-DEMO-0001` ... `REQ-DEMO-0042` request instances;
- cycles all six Request statuses;
- uses existing Request type versions and the local demo actor resolved by `RequestDemoSeeder`;
- uses `firstOrNew` to keep repeated local seeding stable;
- 42 records intentionally exceed the default 25-row page size for pagination/report/export UI testing.

No migration or production seeding behavior was added.

## Tests added / aligned

Updated stale target-architecture contracts:

```text
tests/Feature/Request/Architecture/RequestDefinitionManagementWorkspaceContractTest.php
tests/Feature/Request/Architecture/RequestReportsExportWorkspaceContractTest.php
```

Added:

```text
tests/Feature/Request/Architecture/RequestRefactorModuleContractTest.php
tests/Feature/Request/Exports/RequestSelectedExportScopeTest.php
```

Coverage includes:

- module contract ownership/refactor invariants;
- config-driven definition/report pagination contract;
- selected-export UI/controller/query/planner wiring;
- selected rows intersecting current authorization scope;
- no-selection preserving export-all-authorized-scope behavior;
- selected-ID count bounded to visible-page maximum;
- deterministic local/testing demo seeder contract.

## Scope/risk result

No approved risk gate was crossed during implementation:

- schema/database migration change: **NONE**;
- authorization permission/guard contract change: **NONE**;
- production data deletion/change: **NONE**;
- feature deletion: **NONE**;
- significant cross-module source change: **NONE**;
- route rehome: **NONE**.

Current branch is based on the current `main` checkpoint used for this refactor and the implementation diff is Request-scoped plus Request docs/tests.

## Required verification before merge

Execute on the branch using the repository's normal local environment:

```bash
./vendor/bin/pint --test Modules/Request tests/Feature/Request
php artisan test tests/Feature/Request/Exports/RequestSelectedExportScopeTest.php
php artisan test tests/Feature/Request/Architecture/RequestDefinitionManagementWorkspaceContractTest.php tests/Feature/Request/Architecture/RequestReportsExportWorkspaceContractTest.php tests/Feature/Request/Architecture/RequestRefactorModuleContractTest.php
php artisan test tests/Feature/Request
php artisan route:list --name=request
npm run build
git diff --check main...HEAD
git status --short
```

Full-project regression is not required by default for this Request-scoped refactor. Run impacted Admin/ClientPortal regression only if CI/runtime evidence identifies a cross-module integration regression.

## Manual UI acceptance checklist

Required before merge:

1. `/admin/requests/admin/types`
   - inputs/selects visibly bordered;
   - focus/error states clear;
   - page-size options work and pagination resets correctly;
   - mobile/desktop layout remains usable.
2. `/admin/requests/admin/reports`
   - report filters and page-size selector render correctly;
   - pagination preserves filters;
   - selecting one/multiple row checkbox and exporting CSV/XLSX exports only those selected authorized rows;
   - no checkbox selected exports the complete current filtered authorized scope, not only the visible page;
   - responsive card/table layouts remain usable.
3. Local demo seed
   - run `RequestDemoSeeder` in local environment;
   - verify multiple pages and mixed statuses are visible for reporting/export checks.

## PR/merge status

Implementation is consolidated on one branch as requested. PR creation and CI/check results are the next checkpoint. Do not split this refactor into additional implementation PRs unless an independently gated risk is discovered.

## Next step

1. Create the single consolidated PR from `refactor/request-contract-ui-export-demo-alignment` to `main`.
2. Review CI/check results.
3. Run/record manual UI smoke locally.
4. Merge only after required automated + UI gates pass.
5. After merge, refresh this handoff with the actual merge checkpoint per `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
