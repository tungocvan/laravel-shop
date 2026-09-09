# ClientPortal Module — Collaboration Handoff

## Current delivery — Invoices Partner Report PWA

- Last updated: 2026-09-09
- Active branch: `feat/clientportal-invoices-partner-report`
- Base branch: `main`
- Base merge checkpoint: `b0f4d18ee7430e16b302ac43914c3dbac3065e40`
- Status: **IMPLEMENTED — UI PASS — BOUNDED CLI PASS — READY FOR PR**
- Closeout: `docs/modules/ClientPortal/INVOICES_PARTNER_REPORT_CLOSEOUT.md`

### Objective

Expose the Admin partner-report capability as a dedicated executive ClientPortal/PWA experience without moving Invoices business ownership into ClientPortal and without reusing Admin authentication or Admin presentation.

### Canonical ownership

```text
Modules/Invoices
  owns invoice/partner business data
  owns partner aggregation/query rules
  owns InvoicePartnerReportService

Modules/ClientPortal/Applications/Invoices
  owns PWA routes
  owns web-guard/client-feature authorization
  owns executive responsive presentation
  owns client-safe orchestration
```

ClientPortal does not copy invoice models/schema/core services and does not reuse `auth:admin`, Admin Blade or Admin Livewire for this delivery.

### Delivered client capability

```text
GET /apps/invoices/partners
name: client.invoices.partners
permission: client.invoices.partners.view
```

Implemented Partner Report UX:

- executive partner KPIs;
- Top bán ra;
- Top mua vào;
- partner search by name or MST using the shared compact autocomplete pattern;
- relationship filter: all / customer / supplier;
- relationship-aware default sorting: customer => sold descending, supplier => purchase descending;
- partner detail;
- pagination;
- Mobile/Tablet card presentation below `xl`;
- Desktop table presentation from `xl` upward;
- money values use stable single-line presentation where appropriate;
- responsive filter panel for Mobile/Tablet;
- Desktop horizontal filter workspace remains available.

### Invoice List follow-up included in this branch

`/apps/invoices/list` now supports `Tháng = Cả năm`.

Contract:

- empty `month` => full selected year (`01/01` through `31/12`);
- explicit month => selected month only;
- no `month` parameter on a normal initial request keeps the established current-month default;
- header text reflects either `Cả năm YYYY` or `Tháng MM/YYYY`;
- search, pagination and export preserve the selected yearly/monthly scope;
- export contract remains selected rows => selected only, no selection => all filtered.

### Validation evidence

Manual UI acceptance: **PASS**

```text
Partner report Desktop: PASS
Partner report Tablet: PASS
Partner report Mobile/PWA: PASS
Partner autocomplete: PASS
Relationship-aware sort: PASS
Top sold/top purchase amount visibility on Tablet/Mobile: PASS
Invoice List `Cả năm`: PASS
```

Final bounded CLI acceptance: **PASS**

```text
php artisan test tests/Feature/ClientPortal/InvoicesPartnerReportPwaContractTest.php
PASS

vendor/bin/pint --test <changed PHP files for this delivery>
PASS
```

The first focused run exposed only stale responsive assertions (`md` breakpoint) and one Pint style issue in `InvoicePartnerReportService.php`; both were corrected. The contract now protects the current responsive boundary (`xl:hidden` cards / `xl:block` desktop table).

### Validation policy

Previously PASSed Invoices PWA tests/build/UI scopes are not rerun unless later changes invalidate them. Full-repository Pint is not a merge gate because the repository contains unrelated legacy formatting debt. Only changed/invalidated scopes are gates for this delivery.

### Security / operations boundary

- GDT credentials/tokens remain server-side only.
- No GDT token is placed in localStorage, IndexedDB, PWA cache, JS, Blade or public component state.
- Google Drive configuration, backup/restore and destructive operations remain Admin/Invoices-only.
- No migration, schema rewrite or destructive data operation is part of this delivery.

## Previous completed Invoices PWA delivery

The base Invoices PWA delivery was merged through PR #173 and subsequently corrected by selected-month KPI hotfix PR #175.

Canonical Invoices PWA routes already on `main` include:

```text
GET  /apps/invoices
GET  /apps/invoices/list
GET  /apps/invoices/list/{invoice}
POST /apps/invoices/export
GET  /apps/invoices/list/{invoice}/pdf
GET  /apps/invoices/sync
POST /apps/invoices/sync
```

Base PWA validation previously recorded:

```text
Executive Dashboard UI: PASS
Invoice List Desktop: PASS
Invoice List Tablet: PASS
Invoice List Mobile: PASS
Partner autocomplete: PASS
Selected-vs-filtered export UX: PASS
Focused ClientPortal/Invoices tests: PASS
Latest recorded focused batch from base delivery: 62 passed (378 assertions)
npm run build: PASS
```

## Stable ClientPortal architecture

ClientPortal remains an authenticated Client/WebApp platform that can host multiple applications without placing module-specific business logic into ClientPortal core.

Core rule:

> Không được thêm logic đặc thù Module vào ClientPortal core.

Auth owns authentication/session/logout behavior. ClientPortal owns PWA presentation and consumes canonical module/application contracts.

Historical stable checkpoints:

```text
MR-1 Portal Architecture Foundation: MERGED
MR-2 Adaptive Navigation: MERGED
MR-3 Dynamic Portal Home: MERGED
MR-4 Muasamcong reference migration: MERGED
MR-5 PWA External File Download & Return UX: MERGED — PR #64
MR-6 PWA Install UX: MERGED — PR #65
MR-7 PWA Account Registration & Google Authentication: MERGED — PR #67
MR-8 PWA Header Account Menu: MERGED — PR #68
Canonical web logout corrective: MERGED — PR #86
ClientPortal architecture boundaries refactor: MERGED — PR #124
Invoices executive PWA: MERGED — PR #173
Invoices selected-month KPI hotfix: MERGED — PR #175
Invoices Partner Report PWA: READY FOR PR
```

## Deferred debt

- relocate/remove legacy root Muasamcong export jobs only with explicit queued-payload proof;
- remove root model aliases only after caller proof;
- avoid speculative consolidation of small ClientPortal resolver/presenter services without concrete duplication or caller evidence;
- keep Invoices Google Drive configuration, backup/restore and destructive recovery outside ClientPortal PWA unless a separate security/operations scope explicitly authorizes them.
