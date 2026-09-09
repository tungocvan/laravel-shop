# ClientPortal Invoices PWA — Closeout

Date: 2026-09-09

Branch: `feat/clientportal-invoices-pwa`
Base: `main`

## Delivery status

**READY FOR PR**

The branch is fully synchronized with its remote working branch and the latest comparison against `main` shows:

```text
status: ahead
ahead_by: 42
behind_by: 0
merge base: ed5b9078d17ebcf0142a261d876ae4d64d3a378c
```

No rebase/merge-from-main is required before opening the PR at this checkpoint.

## Delivered scope

ClientPortal now exposes Invoices as a Mobile/Tablet-first PWA application while retaining the ownership boundary:

```text
Modules/Invoices
  owns models, schema, query/filter rules, GDT integration, jobs,
  PDF/file services, exports and reporting data services.

Modules/ClientPortal/Applications/Invoices
  owns web-guard authorization, PWA routes, client-safe orchestration,
  executive reporting presentation and responsive UX.
```

Delivered client surfaces include:

- Executive Dashboard with year/month drill-down, current-period KPIs, 12-month trend and multi-year revenue summary.
- Responsive invoice list for Desktop/Tablet/Mobile.
- Executive filters: invoice type, partner search by name/MST, month/year and business-oriented sorting including highest value.
- Compact partner autocomplete with bounded suggestion list.
- Invoice detail screen.
- Authorized PDF handoff/download.
- Export selected when rows are selected; otherwise export all filtered records.
- Queue-backed GDT sync without exposing GDT credentials/tokens in browser state/storage.
- Google Drive configuration, backup/restore and destructive operations remain outside ClientPortal PWA.

## Validation baseline reused at closeout

Per `COLLABORATION_HANDOFF.md`, previously PASSed scopes are not rerun unless invalidated by later changes.

Recorded acceptance:

```text
Manual UI — Executive Dashboard: PASS
Manual UI — Invoice list Desktop: PASS
Manual UI — Invoice list Tablet: PASS
Manual UI — Invoice list Mobile: PASS
Manual UI — compact partner autocomplete: PASS
Manual UI — selected-vs-filtered export UX: PASS
ClientPortal/ClientApps focused regression: PASS during delivery
Invoices PWA focused/contract validation: PASS during delivery
Latest recorded focused batch: 62 passed (378 assertions)
npm run build: PASS
Working tree on user machine: clean and tracking origin branch
```

Full-repository Pint is intentionally not a merge gate for this bounded delivery because the repository currently contains broad pre-existing/legacy formatting debt outside this branch scope. Changed/impacted PHP Pint is the applicable gate.

## Change footprint

The PR is intentionally bounded to ClientPortal Invoices presentation/integration plus the minimum shared/domain support required for that presentation:

- `Modules/ClientPortal/Applications/Invoices/*`
- `Modules/ClientPortal/resources/views/applications/invoices/*`
- canonical Website PWA manifest reuse in ClientPortal launcher
- `Modules/Invoices/Services/InvoiceService.php` query/reporting support
- shared `resources/views/components/search.blade.php` autocomplete capability
- Invoices PWA contract test
- ClientPortal handoff/closeout documentation

No schema migration, destructive data migration or transfer of Invoices business ownership into ClientPortal is included.

## Merge checkpoint

This closeout prepares the branch for a PR targeting `main`.

Merge should occur only after the PR is opened and its final GitHub mergeability/check status is reviewed. No additional broad regression should be requested unless a subsequent commit invalidates an already recorded PASS scope.
