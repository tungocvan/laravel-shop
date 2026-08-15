# Administrative Module Analysis

## Executive Summary

`Modules/Administrative` remains a healthy domain module; **Full Rebuild is not warranted**.

Round 1 resolved the original correctness, permission, pagination and archive-audit gaps. Round 2 reconciles the additional capabilities added afterward: deterministic demo seeders, public branding fix, delete-all/delete-selected modal UX, Import/Export, selected-export semantics and shared Admin UI standards.

Current status: **Round 2 implemented; final automated verification pending before merge**.

## Architecture Assessment

Core architecture remains unchanged and appropriate:

```text
Routes / Controllers
-> Livewire components
-> Administrative services
-> Models / private storage / history
```

Key services now include:

```text
ProcedureService
SubmissionService
AdministrativeFileService
LookupService
ReceiptService
PublicBrandingService
ImportExport
```

Import/Export correctly reuses the shared repository foundation instead of introducing a competing module framework.

## Round 2 Implemented Findings

### Public branding

Resolved the runtime model reference by using `Modules\System\Models\Setting` for Administrative public branding.

### Demo seeders

Administrative now has deterministic demo data suitable for repeated UI/workflow testing on Local, Docker and VPS without Faker dependency.

### List / destructive UX

- bounded page sizes remain `10/25/50/100`;
- pagination uses accent/indigo active styling;
- selected-delete uses centered modal confirmation;
- delete-all uses centered modal confirmation;
- destructive backend actions remain permission-protected, soft-delete based and audited.

### Import / Export

Implemented through shared `BaseImportExportService` and `shared.import-export.panel`.

Canonical selected export behavior:

```text
selected_ids empty     -> export all records in approved export scope
selected_ids not empty -> export selected records only
```

Round-trip behavior is supported for safe update/upsert of exported Administrative data without exporting lookup secrets.

Security/integrity rules:

- lookup token plaintext is never exported;
- import errors redact lookup secrets;
- existing lookup/version fields remain system-owned during update;
- unsafe raw replace mode is blocked;
- import status changes create Administrative history metadata;
- soft-deleted matching records can be restored only through the implemented safe upsert path.

### Shared success UX

Import/dry-run/export success uses the shared modal with explicit `OK — tải lại` refresh behavior so parent/child Livewire state can be synchronized predictably.

### Shared form UI standard

Repository Admin form primitives now provide visible bordered controls and consistent focus/error/disabled/read-only states. Administrative should reuse these primitives opportunistically rather than duplicating long class strings.

## Security Assessment

Post-Round-2 guarantees:

- admin guard and named permission boundaries remain;
- sensitive mutations authorize server-side;
- checkbox visibility is not authorization;
- private storage remains controlled by application routes;
- public lookup hashing/session/rate-limit semantics are unchanged;
- Import/Export is gated by `administrative.submission.import_export`;
- lookup secrets are not exported or logged;
- archive/delete remains soft delete + audit;
- processing retains transaction + row lock + optimistic version checks.

## Performance Assessment

- no `All` page-size option;
- list queries remain paginated;
- selected export is query scope, not current paginator page;
- synchronous export is bounded by the Administrative export limit;
- no new unbounded UI list query was introduced.

## Regression Contract

`AdministrativeRefactorContractTest` now locks the high-value Round 1/2 contracts including:

- file model resolution;
- bounded pagination;
- archive audit;
- permission compatibility;
- delete-all protection/audit;
- selected-delete modal;
- custom indigo pagination;
- shared Import/Export foundation and permission;
- reactive selected IDs;
- success modal/refresh contract;
- lookup-secret exclusion/redaction.

## Verification Evidence

Latest user-supplied full regression checkpoint before final closure:

```text
356 passed
12,858 assertions
0 failed
Duration: 19.00s
```

User manual UI verification also confirmed delete-all, export-all, export-selected and import-back behavior.

Because shared Admin form components and documentation were changed after that checkpoint, one final Pint/targeted/build/full regression run is required before merge.

## Remaining Non-Blocking Improvements

- deeper behavioral tests for MIME upload cleanup;
- explicit concurrency integration tests for every processing transition;
- lookup-session expiry/result-file integration tests;
- production search profiling before adding search indexes/strategy changes;
- repository-level dependency metadata cleanup if standardized later.

## Final Assessment

`Modules/Administrative` remains a **Major Refactor success**. Round 2 is a contract/UI/import-export closure pass, not an architectural rebuild.
