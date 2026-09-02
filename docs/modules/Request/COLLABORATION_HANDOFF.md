# Request Module — Collaboration Handoff

- Last updated: 2026-09-02
- Repository: `tungocvan/laravel-shop`
- Base branch: `main`
- Delivery mode: **Refactor Module / consolidated batch**
- Status: **MERGED / CLOSED OUT**
- Pull request: **#152 — `refactor(request): align module contract UI export and demo data`**
- Merge commit: `803d0e688440c066e7a05bb8bbe4170a9c834b32`
- Durable contract: `docs/modules/Request/MODULE.md`

## Closeout summary

The approved consolidated refactor of `Modules/Request` was completed and merged into `main` on 2026-09-02.

Delivered scope:

- established the durable Request module contract in `docs/modules/Request/MODULE.md`;
- aligned Admin definition inputs/focus/error presentation and pagination with `ADMIN_UI_STANDARD.md` and Request settings;
- aligned report pagination with `request.settings.page_sizes`;
- implemented canonical export behavior: selected rows when checkbox selection exists, otherwise the complete current authorized filter scope;
- preserved export authorization, private storage, expiry, owner/permission checks and download-time re-authorization;
- added deterministic local/testing Request workflow demo data across all statuses and more than one default page;
- updated stale architecture contracts and added behavioral coverage for selected export and the refactor module contract.

## Architecture result

### KEEP

- Request domain/application architecture and use-case services.
- Request route ownership in `Modules/Request/routes/web.php`.
- Existing permission and authorization semantics.
- Existing Request persistence ownership and production data.
- Request audit/outbox/idempotency contracts.
- Private export storage, expiry and re-authorization boundaries.
- Existing requester/approver lifecycle and definition-version compatibility.

### REHOME

None.

### DELETE

None.

### QUARANTINE / protected boundaries

- schema/migration and production data boundaries;
- permission names and guard semantics;
- runtime Module-state persistence owned outside Request;
- export authorization snapshots/private storage/expiry;
- audit/outbox/idempotency history.

### DEFER

- cross-module shared paginator convergence;
- unrelated ClientPortal/PWA presentation cleanup.

## Key implementation checkpoints

### Admin definition UI and pagination

`DefinitionIndex` no longer hard-codes `paginate(25)`. Page sizes and default page size derive from Request settings, changing page size resets pagination, and the Admin UI now exposes a standard `Số dòng/trang` control with visible borders and focus/error treatment.

### Reports and export

Report `per_page` validation derives from Request settings. The Reports workspace keeps responsive mobile-card/desktop-table layouts and query-aware pagination.

Canonical export behavior is now:

```text
selected_request_public_ids is non-empty
    -> normalize/deduplicate/bound IDs
    -> intersect with current authorization scope
    -> export only selected authorized records

no selected_request_public_ids
    -> export all records in the current authorized filter scope
```

The UI deliberately does not claim a global select-all. Visible-row checkboxes select visible rows; no selection means the full current authorized filter scope.

### Local/testing demo data

`RequestWorkflowDemoSeeder` is guarded to local/testing environments and creates deterministic `REQ-DEMO-0001` through `REQ-DEMO-0042` records across all six Request statuses. The fixtures intentionally exceed the default 25-row page size and use stable repeated seeding behavior.

No migration or production seeding behavior was introduced.

## Verification evidence — PASS

Recorded before merge on 2026-09-02:

- Pint — **PASS, 264 files**;
- focused selected-export and architecture tests — **PASS**;
- Request regression — **147 passed, 6250 assertions, 9.99s**;
- Request routes — **PASS**;
- Vite build — **PASS**;
- `git diff --check` — **PASS**;
- manual UI smoke — **PASS**.

Manual UI acceptance covered `/admin/requests/admin/types` and `/admin/requests/admin/reports`, including input/focus/error states, page-size/pagination behavior, responsive layouts, selected-row CSV/XLSX export, and no-selection export of the complete current authorized filter scope.

## Risk result

No gated risk was crossed:

- schema/database migration change: **NONE**;
- authorization permission/guard contract change: **NONE**;
- production data deletion/change: **NONE**;
- feature deletion: **NONE**;
- significant cross-module source change: **NONE**;
- route/domain ownership rehome: **NONE**.

## Merge checkpoint

PR **#152** was merged into `main` on 2026-09-02.

```text
merge commit: 803d0e688440c066e7a05bb8bbe4170a9c834b32
source branch: refactor/request-contract-ui-export-demo-alignment
base branch: main
```

The Request refactor is complete. No further implementation action is pending from this delivery. Future Request work should start from current `main` and use this handoff plus `docs/modules/Request/MODULE.md` as the baseline.
