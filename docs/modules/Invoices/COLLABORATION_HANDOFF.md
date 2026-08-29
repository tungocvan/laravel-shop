# Invoices Collaboration Handoff

## Current Status

- Module: `Invoices`
- Feature: read-only Admin Dashboard
- Branch: `feat/invoices-admin-dashboard`
- Base checkpoint: `main@e491aa44701c2303cef100207e632d199bbf0fa6`
- Verified source checkpoint: `7bae2084f912398fdbcbb2d22477d6d9cfd0ca53`
- Implementation status: **VERIFIED — READY FOR PR**
- Pull request: [#74 — feat(invoices): add read-only admin dashboard](https://github.com/tungocvan/laravel-shop/pull/74) — **OPEN FOR MANUAL REVIEW**
- Merge status: **NOT MERGED**

The user confirmed all scoped automated gates and desktop/mobile UI acceptance as PASS on 2026-08-29. PR #74 is open for manual review and must not be auto-merged.

## Approved Scope

Add a permission-aware read-only Dashboard at:

```text
GET /admin/invoices/dashboard
admin.invoices.dashboard
web, auth:admin, permission:invoices-list
```

The existing `admin.invoices.index` redirect to `admin.invoices.hoadon-list` remains unchanged.

The Dashboard may navigate to existing workspaces but must not directly perform delete, issue, cancel, synchronize, backup, export, download or any other mutation.

## Architecture

```text
InvoicesDashboardController
    -> InvoiceDashboardService
        -> InvoiceDashboardData (bounded safe DTO)
            -> Invoices::pages.invoices.dashboard
```

The Dashboard service:

- checks all three owned tables before querying;
- uses aggregate queries and explicit field selection;
- limits recent invoices and backup runs to five rows each;
- does not call GDT or MeInvoice over HTTP;
- does not enumerate per-job cache keys;
- returns generic unavailable states instead of raw exceptions;
- logs only section context and exception class;
- does not reuse `InvoiceService::dashboard()` because that method includes financial totals and partner-derived data.

## Permission Contract

| Capability | Dashboard behavior |
|---|---|
| `invoices-list` | Required to open the Dashboard; list/report navigation and safe count metrics |
| `invoices-create` | Shows the sync workspace link |
| `invoices-export` | Shows an export capability badge; no export action on Dashboard |
| `invoices-download` | Shows PDF counts and sanitized backup history |
| `invoices-configure` | Shows the GDT workspace and boolean configuration/session status |

No new permission, migration, seeder, menu entry or config key is introduced.

## Data Safety Contract

Allowed Dashboard data:

- record counts;
- sold/purchase direction;
- PDF available/missing/error counts;
- allowlisted backup mode/status and counts;
- timestamps;
- boolean configuration/session state.

The DTO and HTML must not contain:

- invoice number, lookup code or symbol;
- partner name, tax code, address, email or phone;
- VAT, amount before VAT or total amount;
- GDT/MeInvoice credentials or tokens;
- backup recipient;
- filename, path, fingerprint or raw file list;
- raw external payload, exception or persisted error message.

## Workspace Navigation

The following Admin pages include the shared permission-aware `Quay về Dashboard` partial:

- GDT authentication;
- GDT synchronization;
- invoice list;
- partner report.

The new Dashboard follows `ADMIN_UI_STANDARD.md`: Admin shell-owned width, responsive grids, semantic sections, keyboard focus states, readable empty/unavailable/error states and no direct mutation controls.

## ClientPortal / PWA Compatibility Boundary

Invoices is expected to be integrated into `Modules/ClientPortal` later for PWA use. This PR deliberately does not change ClientPortal.

Future integration must:

- register Invoices through the ClientPortal manifest/application registry;
- use ClientPortal authentication, permissions and adaptive navigation;
- use a client-specific route and presentation instead of embedding Admin Blade or `auth:admin` routes;
- reuse only safe domain read data where appropriate;
- keep route URLs and Admin presentation out of the reusable DTO contract;
- define a separate authorized external-file handoff if the PWA needs PDF download/open behavior;
- run focused ClientApps tests and the impacted Invoices tests for that future change.

## Files

### Added

```text
Modules/Invoices/Http/Controllers/InvoicesDashboardController.php
Modules/Invoices/Data/InvoiceDashboardData.php
Modules/Invoices/Services/InvoiceDashboardService.php
Modules/Invoices/resources/views/pages/invoices/dashboard.blade.php
Modules/Invoices/resources/views/partials/dashboard-return-link.blade.php
tests/Feature/InvoicesDashboardTest.php
docs/modules/Invoices/COLLABORATION_HANDOFF.md
```

### Updated

```text
Modules/Invoices/routes/web.php
Modules/Invoices/resources/views/pages/invoices/authenticate.blade.php
Modules/Invoices/resources/views/pages/invoices/sync.blade.php
Modules/Invoices/resources/views/pages/invoices/index.blade.php
Modules/Invoices/resources/views/pages/invoices/partner-report.blade.php
docs/modules/Invoices/README.md
docs/modules/Invoices/INFORMATION.md
Modules/Invoices/README.md
```

## Verification Gate

User-confirmed results on 2026-08-29:

```text
Pint changed PHP files                         PASS
Focused InvoicesDashboardTest                 PASS
Invoices module regression                    PASS
Admin Feature regression                      PASS
Route inspection                              PASS
Frontend production build                     PASS
Desktop UI acceptance                         PASS
Mobile UI acceptance                          PASS
Working tree clean                            PASS
```

Verification scope remained limited to Invoices and directly impacted Admin tests. No full-project regression was run or required.

Full-project regression is not required unless the implementation scope expands beyond Invoices and its direct dependencies.

## Deferred Existing Debt

The Dashboard does not expand or silently fix these existing concerns:

- runtime GDT `.env` mutation;
- broad/mixed capabilities inside the synchronization workspace;
- unbounded import/export, ZIP, filter option and backup fingerprint paths;
- potential per-row PDF status queries in the existing list;
- public export storage for financial spreadsheets;
- public-link Google Drive flow;
- lack of a persisted global GDT job registry;
- missing database unique constraint for invoice business identity;
- incomplete module table/dependency manifest and historical documentation drift.

## PR and Merge Gate

1. Focused Invoices, directly impacted Admin tests and UI acceptance: **COMPLETE**.
2. Verification results and source checkpoint recorded in this handoff: **COMPLETE**.
3. PR #74 opened for manual user review and merge: **COMPLETE**.
4. Do not auto-merge.
5. After merge, create a docs-only closeout only if the merged handoff lacks actual PR/merge verification details.
