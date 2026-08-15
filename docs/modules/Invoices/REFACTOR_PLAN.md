# Invoices Module Refactor Plan

## Status

Planning complete. **Implementation is not approved yet.**

This plan is governed by `.codex/tasks/refactor-module.md`. Application source code must not be modified until explicit approval is given after this plan is reviewed.

## Refactor Goal

Refactor `Modules/Invoices` without rebuilding the module, preserving its current public contracts while addressing the verified security, authorization, performance, import/export, data-integrity and Admin UI findings documented in `ANALYSIS.md`.

Target outcome:

- keep the current first-party module architecture;
- preserve sold/purchase invoice behavior;
- preserve current route names and compatibility aliases;
- make sensitive Livewire actions enforce capability-specific authorization;
- prevent unsafe/unbounded list behavior;
- move invoice import/export toward the repository canonical Shared foundation;
- make import/export semantics explicit and testable;
- improve idempotency and monetary normalization;
- reduce repeated aggregate query work;
- improve the invoice Admin workspace according to `ADMIN_UI_STANDARD.md` without unrelated frontend churn.

Recommended classification: **Major Refactor**.

## Scope

Primary scope:

```text
Modules/Invoices/**
tests/Feature/InvoicesModuleTest.php
docs/modules/Invoices/**
```

Shared import/export files under `Modules/Shared` may be changed only if an approved Invoices implementation proves a generic capability is missing and the change is reusable rather than Invoices-specific.

No unrelated business module may be modified.

## Evidence Being Addressed

### P0

1. `GdtLogin::saveGdtConfig()` can mutate root `.env` and execute `config:clear` without an explicit action-level authorization check.
2. Runtime `.env` mutation is production-control-sensitive and may conflict with immutable/container deployments.

### P1

1. `GdtLogin` privileged token/config mutations do not consistently enforce `invoices-configure` at the Livewire action boundary.
2. `HoadonList::exportSelected()` and `downloadSelected()` do not explicitly enforce `invoices-export` / `invoices-download` inside the action.
3. Web routes use broader `invoices-list` / `invoices-create` capabilities even though the module declares more granular permissions.
4. `HoadonList` exposes an unbounded `All` page-size option.
5. Query-string-backed `perPage` is not normalized against an allowlist.
6. Statistics/dashboard queries can be repeatedly recalculated during a render cycle.
7. Invoice import uses a module-local implementation instead of the canonical Shared Import/Export foundation.
8. Import duplicate detection uses application-level `exists()` + `create()` and has no database-level uniqueness guarantee.
9. Import monetary normalization passes through `float`.
10. Export implementations are fragmented and some workbook generation is memory-bound.
11. Selected export behavior does not match the repository canonical contract: current UI requires a selection, while canonical behavior is no selection = export all approved scope, selection = export selected IDs.
12. Existing tests cover core GDT/import paths but do not adequately cover denied authorization, page-size tampering, selected/all export semantics, duplicate/idempotency behavior, or query behavior.

## Compatibility Constraints

The refactor must preserve unless explicitly approved otherwise:

### Routes and names

```text
admin.invoices.index
admin.invoices.create-token
admin.invoices.hoadon
admin.invoices.hoadon-list
admin.invoices.download
```

Backward-compatible `/invoices/*` aliases must remain functional.

### API

```text
GET  /api/invoices
POST /api/invoices
```

Default Sanctum protection must remain.

### Permissions

Existing permission strings must remain valid:

```text
invoices-list
invoices-create
invoices-export
invoices-download
invoices-configure
```

The refactor may make enforcement more granular but must not silently rename permissions.

### Livewire aliases

Existing `Invoices` Livewire component class paths/aliases must not be renamed during this refactor unless separately approved.

### Data contract

- table remains `invoices`;
- model remains `Modules\Invoices\Models\Invoices` for compatibility;
- `invoice_type` values remain `sold` and `purchase`;
- current invoice columns remain compatible;
- current GDT and MeInvoice storage/config paths remain readable.

### Storage

Existing defaults remain compatible:

```text
invoices.storage.export_directory = gdt
invoices.storage.pdf_directory    = hoadon_temp
```

### GDT token/security

- token remains server-side only;
- stored GDT password must never be hydrated into public Livewire state;
- no secret should be added to logs, exports or browser state.

## Planned Work

## Phase R1 — Authorization and Production-Control Hardening

Priority: **P0 / P1**

### Files expected to change

```text
Modules/Invoices/routes/web.php
Modules/Invoices/Livewire/GdtLogin.php
Modules/Invoices/Livewire/GdtInvoice.php              # only if sync actions need explicit capability enforcement
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Services/GdtConfigService.php
Modules/Invoices/config/invoices.php                   # if environment policy is introduced
tests/Feature/InvoicesModuleTest.php
```

### Changes

1. Add action-level authorization checks for privileged Livewire methods.
2. Align capabilities:
   - configuration/token mutation -> `invoices-configure`;
   - export -> `invoices-export`;
   - PDF/file download -> `invoices-download`;
   - invoice synchronization/create workflow -> preserve `invoices-create` unless source inspection proves a more specific existing capability is available.
3. Align route middleware with dedicated permissions where safe while preserving route names and URLs.
4. Keep authorization server-side even when the UI hides actions.
5. Harden GDT configuration mutation policy:
   - default to a configurable policy that can disable browser-driven `.env` writes in production/immutable deployments;
   - fail closed with a safe operator-facing message when runtime config mutation is disabled;
   - do not expose the root path or raw exception text;
   - preserve current behavior in explicitly allowed environments for backward compatibility.
6. Avoid introducing arbitrary Artisan execution. `config:clear` must remain a fixed server-controlled action only if still required by the approved configuration strategy.

### Acceptance criteria

- denied user cannot call privileged Livewire mutations even if manually invoking Livewire requests;
- allowed capability succeeds;
- no GDT secret/token appears in Livewire public state or response payload tests;
- runtime config mutation can be disabled safely without breaking read-only invoice listing;
- route compatibility remains unchanged.

## Phase R2 — Bounded Invoice Workspace and Admin UI Polish

Priority: **P1 / P2**

### Files expected to change

```text
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Services/InvoiceService.php
Modules/Invoices/resources/views/livewire/hoadon-list.blade.php
```

### Keyword search

**Applicable.**

Current UI has structured partner/MST/date/type/tax-rate filters but no simple keyword search. Add one concise keyword field only if it can search high-value invoice fields such as invoice number, lookup code, partner name and tax code without creating an unbounded or ambiguous query path.

If implementation profiling shows the wildcard search would be harmful on production data, retain the structured filters and document keyword search as deferred rather than adding it mechanically.

### Domain filters and reset behavior

**Applicable and already partly present.**

Keep:

- sold/purchase type;
- partner name;
- tax code;
- issued date range;
- tax rate;
- reset filters.

Normalize filter values and ensure page and selection state reset when filter scope changes.

### Bounded pagination

**Required.**

Replace:

```text
10, 25, 50, 100, All
```

with:

```text
10, 25, 50, 100
```

Tampered query-string values must normalize to a safe default (`10` or `25`, chosen during implementation based on existing behavior compatibility).

Remove service behavior that converts `All` into unbounded `get()`.

### Pagination visual treatment

**Applicable.**

Reuse the repository canonical pagination view if present. Otherwise retain Laravel pagination while ensuring it follows the current Admin UI accent treatment and remains responsive/accessibly positioned below the table.

### Row selection and selected count

**Applicable.**

- keep row checkboxes;
- sanitize selected IDs to positive unique integers before service use;
- reset selection when filters change if selection would otherwise become ambiguous;
- show selected count when non-zero;
- make checkbox visibility depend on export/download capability as appropriate, not unrelated delete permission.

A header checkbox may be added for "select visible page" only if selection semantics are explicit. It must not silently mean "all matching records".

### Bulk actions

**Applicable only to non-destructive export/PDF operations in current scope.**

No bulk delete/destructive invoice mutation is planned. Therefore a destructive confirmation modal is **not applicable** to this refactor unless a destructive action is discovered during implementation.

Bulk PDF download must expose loading/disabled state and clear success/error feedback.

### Empty/loading/error state

**Applicable.**

Keep the existing empty state and improve action-specific loading/disabled feedback. Do not add decorative complexity that competes with the list workspace.

## Phase R3 — Canonical Import / Export Refactor

Priority: **P1**

This phase must follow `.codex/tasks/create-import-export.md` and the repository-standard portions of `.codex/prompts/import-export.md`. Any older prompt statement that conflicts with current repository architecture (for example `nwidart/laravel-modules`) is ignored in favor of `Modules\ModuleServiceProvider`.

### Files expected to change/create

Likely:

```text
Modules/Invoices/Services/ImportExport.php
Modules/Invoices/Services/InvoiceImportService.php     # adapt, delegate or deprecate internally without breaking callers
Modules/Invoices/Services/InvoiceExportService.php     # adapt, delegate or retain specialized item export if still needed
Modules/Invoices/Exports/InvoicesSelectedExport.php    # replace/bridge if Shared service supersedes it
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/resources/views/livewire/hoadon-list.blade.php
Modules/Shared/...                                      # only if a genuinely generic gap is proven
tests/Feature/InvoicesModuleTest.php
```

### Import file formats

Keep current practical spreadsheet support:

```text
.xlsx
.csv  (only where Shared foundation behavior is verified)
```

Existing GDT-exported XLSX import must remain supported.

### Header contract

Preserve existing Vietnamese invoice import headers and add aliases only from evidence/current exports. Do not invent additional business headers.

Known current source headers include:

```text
Mã tra cứu
Ký hiệu
Số hóa đơn
Loại hóa đơn
Ngày lập
Mã số thuế
Đơn vị
Địa chỉ
Email
Phone
Thuế suất
Tiền VAT
Trước VAT
Thành tiền
```

### Mapping strategy

Primary strategy: **header mapping**, because current import/export already uses meaningful Vietnamese headers.

Column A/B/C mapping is not planned unless real source files demonstrate unstable headers.

### Import mode

Initial approved behavior should preserve current semantics:

```text
skip_duplicate
```

Do not silently switch to `update_or_create` because the current code skips an invoice that already matches its duplicate identity.

### Candidate unique identity

Current application-level duplicate identity:

```text
lookup_code + invoice_number + issued_date + tax_code
```

This remains the refactor candidate but is **not yet approved as a database unique key** until production data is checked for nulls/duplicates and GDT business semantics are confirmed.

Implementation may use this tuple in service-level `uniqueBy` first while deferring the DB unique constraint to Phase R4 if verification is incomplete.

### Normalization

- date: normalize `d/m/Y` safely;
- monetary values: normalize to decimal strings, not floating-point values;
- tax rate: normalize numeric/percentage representation without float-based money arithmetic;
- trim strings;
- preserve null/empty semantics intentionally.

### Validation

Add row-level validation for required/format-sensitive fields. Do not make optional contact fields globally required.

Minimum validation candidates:

- invoice direction in `sold,purchase` from trusted import invocation/options;
- issued date valid when provided;
- monetary fields decimal-compatible;
- tax rate decimal-compatible;
- string lengths bounded to database column capacity.

### Transaction semantics

Preserve current partial-import operator expectation only if Shared report semantics make partial success explicit.

Preferred approved behavior:

- validate/report every row;
- successful rows may persist in `skip_duplicate` mode;
- row failures appear in structured report;
- no raw exception text is shown to users;
- `replace` mode is not enabled for Invoices;
- destructive full-table replacement is explicitly out of scope.

If Shared base transaction behavior would make this ambiguous, implementation must adapt the module service or Shared generic contract in a reviewable manner.

### Row-level error reporting

**Required.** Use the Shared import report shape with row number, field/column and safe error message.

### Chunk / batch / queue strategy

The current Shared base loads imported rows into a collection, so simply extending it does not by itself satisfy production-scale import requirements. Implementation must not falsely claim large-file safety.

Approved first slice:

- migrate behavior to Shared mapping/report/storage contracts;
- keep existing synchronous behavior for current known file sizes;
- introduce explicit configurable threshold/guard against excessively large synchronous files if practical;
- queue/chunk large imports only when the repository Shared infrastructure supports it cleanly or as a separately reviewable follow-up.

### Temporary/private storage

Invoice imports/exports may contain business/customer data. New generated files should use private server-controlled storage unless a Shared component currently requires public storage. If Shared export storage is public today, this mismatch must be documented and either safely improved generically or kept as an explicit unresolved risk; do not silently expose more data.

### Cleanup/retention

Define a configurable retention policy for generated temporary export/import artifacts. Do not delete the existing GDT operational folders used by current sync logic.

### Selected-row export canonical contract

**Required change.**

New behavior:

```text
selected_ids empty     -> export all records matching the approved current filter scope
selected_ids not empty -> export only selected invoice IDs
```

Selected IDs take precedence over normal filters, matching the canonical task contract, unless implementation evidence proves the Invoices business workflow requires otherwise.

Current "Vui lòng chọn hóa đơn trước khi xuất" behavior will therefore intentionally change after approval.

### Export scope

When no rows are selected, export should apply the same approved business filters as the invoice list, not only the current pagination page.

### Round-trip compatibility

**Applicable.**

Where practical, the standard invoice list export should use headers that `InvoiceImportService` / new `ImportExport` can import back safely under `skip_duplicate` semantics.

Do not expose internal IDs, secrets, GDT token, API credentials, cache keys or hidden system values merely to achieve round-trip compatibility.

### Success/loading/refresh feedback

**Applicable.**

- export/import buttons must be loading-disabled;
- successful import/dry-run/export should use Shared success feedback/modal where supported;
- after import, the invoice list must have an explicit refresh path so totals/filter options reflect new data;
- row-level errors remain inspectable.

## Phase R4 — Data Integrity and Idempotency

Priority: **P1**

### Database/migration impact

Do **not** rewrite the existing historical invoice migration.

Potential new migration only after data verification:

- add a unique constraint/index for the approved invoice identity;
- or add a safer canonical identity column/index if the current nullable four-field tuple is not valid for MySQL uniqueness semantics/business rules.

Before any unique constraint is created:

1. inspect production-like data for duplicates;
2. inspect null distribution in candidate key fields;
3. verify GDT can/cannot reuse invoice numbers across symbols/tax entities/dates;
4. define cleanup strategy for existing duplicates.

If this cannot be proven during implementation, database uniqueness remains deferred and service idempotency is improved without schema change.

### Transaction / concurrency impact

- avoid race-prone `exists()` then `create()` where possible;
- use an idempotent persistence operation compatible with `skip_duplicate` semantics;
- catch duplicate-key conflicts safely if/when a DB constraint exists;
- queued sync retries must not create duplicate invoice rows.

## Phase R5 — Query and Statistics Optimization

Priority: **P1**

### Files expected to change

```text
Modules/Invoices/Services/InvoiceService.php
Modules/Invoices/Livewire/HoadonList.php
tests/Feature/InvoicesModuleTest.php
```

### Changes

1. Make list pagination always bounded.
2. Prevent repeated `statistics()` execution from multiple computed properties in the same UI request path.
3. Return one statistics payload to the view where practical.
4. Evaluate combining aggregate queries only when it materially reduces database work without making SQL brittle across MySQL/SQLite tests.
5. Do not add speculative indexes before query evidence/profile results.
6. Keep SQLite-aware test compatibility where current regression tests depend on it.

### Acceptance criteria

- no unbounded list query path remains;
- filtered totals remain numerically equivalent;
- query-count regression test or targeted evidence shows duplicate aggregate calls are reduced;
- MySQL-targeted SQL remains compatible while existing SQLite tests continue to work.

## Phase R6 — Tests and Documentation

Priority: **P1**

### Test strategy

Extend `tests/Feature/InvoicesModuleTest.php` or split into `tests/Feature/Invoices/*` when clearer.

Required coverage:

1. module registration/enabled state remains valid;
2. existing token-stays-server-side test remains green;
3. GDT cursor behavior remains green;
4. queue command behavior remains green;
5. sold/purchase import remains green;
6. denied `invoices-configure` cannot save GDT config or delete token;
7. allowed `invoices-configure` reaches the action boundary with filesystem/config behavior safely faked where possible;
8. denied `invoices-export` cannot export;
9. denied `invoices-download` cannot trigger selected PDF download;
10. route middleware maps sensitive endpoints to intended named permissions;
11. `perPage=All`, `perPage=0`, negative or oversized/tampered values normalize to a safe bounded value;
12. no-selection export uses all approved filtered records;
13. selection export uses only sanitized selected IDs;
14. invalid selected IDs do not escape the approved query scope;
15. money import normalization preserves exact decimal values;
16. duplicate invoice import is idempotent under the approved identity;
17. row-level import validation/reporting is safe and structured;
18. filtered statistics remain correct;
19. PDF path traversal protections remain green;
20. API filter pagination remains bounded.

### Verification commands

Targeted minimum:

```bash
php artisan test tests/Feature/InvoicesModuleTest.php
```

If tests are split:

```bash
php artisan test tests/Feature/Invoices
```

Then:

```bash
vendor/bin/pint --test Modules/Invoices tests/Feature/InvoicesModuleTest.php
php artisan test
```

If UI/Blade changes are made:

```bash
npm run build
```

Manual Admin UI smoke:

- desktop invoice list;
- mobile/tablet filter wrapping;
- bounded page size;
- filter reset;
- selection count;
- no-selection export;
- selected export;
- PDF download loading/success/error state;
- empty state;
- permission-denied state;
- GDT configure screen when runtime config writes are enabled and disabled.

## Files Expected to Change

Final implementation scope is expected to be concentrated in:

```text
Modules/Invoices/routes/web.php
Modules/Invoices/config/invoices.php
Modules/Invoices/Livewire/GdtLogin.php
Modules/Invoices/Livewire/GdtInvoice.php                 # only if needed
Modules/Invoices/Livewire/HoadonList.php
Modules/Invoices/Services/GdtConfigService.php
Modules/Invoices/Services/InvoiceService.php
Modules/Invoices/Services/InvoiceImportService.php
Modules/Invoices/Services/InvoiceExportService.php
Modules/Invoices/Services/ImportExport.php              # likely new
Modules/Invoices/Exports/InvoicesSelectedExport.php
Modules/Invoices/resources/views/livewire/hoadon-list.blade.php
Modules/Invoices/resources/views/livewire/gdt-login.blade.php  # only if permission/config policy UI requires it
tests/Feature/InvoicesModuleTest.php
```

Possible new migration is conditional and must not be created until invoice identity is verified.

Documentation after implementation:

```text
docs/modules/Invoices/ANALYSIS.md
docs/modules/Invoices/INFORMATION.md
docs/modules/Invoices/README.md
docs/modules/Invoices/REFACTOR_PLAN.md
```

## Security and Authorization Impact

Expected improvement is significant:

- least-privilege permission enforcement moves to the actual mutation boundary;
- route and action permissions become aligned;
- runtime integration configuration can be disabled in production policy;
- token/password browser exposure remains prohibited;
- selected IDs and page-size/filter state become server-normalized;
- generated business data files move toward controlled/private storage.

No new browser-supplied filesystem path, model class, command, table name or executable may be introduced.

## Performance Impact

Expected improvements:

- remove `All` rendering path;
- bound Livewire payload size;
- reduce repeated aggregate calls;
- make selected/all export query semantics explicit;
- prepare export/import for Shared chunk/queue evolution;
- reduce per-row duplicate-query pressure where idempotent persistence can replace `exists()` + `create()`.

## Rollback / Recovery

1. Keep refactor commits scoped by phase where practical.
2. Do not remove legacy routes or storage paths.
3. Do not delete old export/import classes until all callers are migrated and tests prove compatibility.
4. If Shared Import/Export integration causes regression, retain an adapter around the old `InvoiceImportService` while restoring the previous call path.
5. A database unique migration, if eventually approved, must have a safe `down()` and a preflight duplicate check. Do not apply it when duplicate data exists.
6. Runtime GDT config policy must allow operators to revert to deployment-managed `.env` values without database repair.

## Explicit Non-Goals

This refactor will not:

- rebuild the Invoices module;
- rename `Invoices` model to `Invoice`;
- rename the `invoices` table;
- replace the repository module system;
- introduce `nwidart/laravel-modules`;
- remove `/invoices/*` compatibility aliases;
- redesign unrelated Admin screens;
- introduce bulk invoice deletion;
- enable destructive `replace` import mode;
- expose GDT/MeInvoice secrets in exports or browser state;
- rewrite the historical invoice migration;
- add speculative indexes without query evidence;
- redesign external GDT or MeInvoice APIs;
- remove specialized invoice-item export behavior until active callers are verified.

## Implementation Order After Approval

Recommended sequence:

```text
R1 Authorization / config hardening
-> R2 bounded list + Admin UI state
-> R3 canonical import/export
-> R4 data integrity/idempotency
-> R5 statistics/query optimization
-> R6 regression + docs + full verification
```

R1 and R2 should land before broader import/export work because they reduce immediate security and operational risk with smaller reviewable changes.

## Approval Gate

Per `.codex/tasks/refactor-module.md`, this plan is the required stop point.

**No application source code has been modified by the `/refactor-module Invoices` planning phase.**

Implementation may start only after the user explicitly approves this `REFACTOR_PLAN.md`.