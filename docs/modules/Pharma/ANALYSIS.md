# Pharma Module Analysis

Last verified: 2026-08-30

Scope: `Modules/Pharma/**`, `docs/modules/Pharma/**`, and direct Shared/Admin dependencies used by Pharma.

## Executive Summary

The Pharma Major Refactor has completed its planned implementation sequence through MR-6 and has passed the MR-7 final acceptance gates.

The original recommendation was **Major Refactor, not rebuild**. That recommendation has now been executed successfully: the existing module architecture was retained while security boundaries, shared import/export safety, Admin workspaces, domain integrity, sync-ready metadata, and the PriceList pipeline were hardened.

Current conclusion: **Major Refactor accepted; no further structural refactor is required for closeout.** Remaining items are intentional future capabilities/non-goals and must not be treated as unfinished defects of this program.

## Current Module Shape

Pharma remains a domain module depending on `Shared`, with four primary Admin work areas:

1. Medicine / HSSP.
2. Drug Bid Awards.
3. Supplier Tracking.
4. Price List generation.

A dedicated Pharma Admin Dashboard is available at `/admin/pharma`.

The tracked manifest remains intentionally disabled:

```text
name: Pharma
type: domain
enabled: false
depends: Shared
permissions:
  view_pharma
  create_pharma
  edit_pharma
  delete_pharma
```

Production/runtime enablement was not part of this refactor.

## Accepted Architecture After Refactor

### Security and authorization

- Pharma Admin routes remain behind `web` + `auth:admin`.
- Dashboard/index routes require `view_pharma`; create/edit routes require their matching capabilities.
- Sensitive Livewire mutations use server-side Pharma capability authorization.
- The obsolete/broken public Pharma API contract was removed; `routes/api.php` intentionally exposes no public endpoint.
- Raw exception details are not used as normal operator-facing error messages in hardened workflows.

### Shared Import / Export

- Pharma continues to reuse the Shared Import/Export foundation; no competing engine was introduced.
- Shared service selection is server-owned/locked at action time rather than trusting mutable client state.
- Pharma import modes are constrained by domain usage.
- Generated Shared exports use private storage and authorized download behavior.
- Existing business import/export contracts remain covered by focused regression tests.

### Medicine / HSSP workspace

- Uses bounded `10/25/50/100` pagination; there is no `All` path.
- Selection and destructive bulk actions are page-scoped.
- Destructive bulk actions are permission-aware and confirmed explicitly.
- Search/filter/import/export/CRUD remain within the canonical Admin shell.
- Dashboard return navigation, responsive behavior, and loading states are present.

### Drug Bid Award workspace and sync-ready foundation

- Uses bounded workspace behavior and bounded Medicine lookup.
- Source metadata supports stable external identity through `source_type` + `source_id`, synchronization timestamp and payload hash.
- Source projection is idempotent and preserves explicit conflict handling for manual rows.
- Linked/unmatched source state is visible without fuzzy Medicine auto-matching.
- The foundation is sync-ready, but actual Muasamcong -> Pharma production synchronization is intentionally not wired by this refactor.

### Supplier Tracking integrity and workspace

- Canonical business key is Medicine + normalized supplier name + working date when working date is non-null.
- Database migration/backfill and unique constraint enforce the accepted duplicate rule.
- Multiple null-working-date rows remain allowed by design.
- Friendly duplicate-domain errors replace raw database failure behavior.
- Bounded workspace, page-scoped selection, confirmation, bounded Medicine lookup, filters and financial recalculation are preserved.
- Existing Medicine -> Supplier Tracking cascade-delete behavior was intentionally retained.

### Price List security and pipeline

- Full workbook analysis/product payload is no longer public Livewire state.
- Product workspace is bounded to `10/25/50/100` and does not auto-select the complete workbook.
- Header selection is page-scoped.
- `PriceListService::generate()` is the canonical workbook analysis/validation/generation boundary.
- Request/component input cannot choose an arbitrary production output path.
- Generated files are allocated under private PriceList export storage and partial output is cleaned on build failure.
- Successful UI downloads use delete-after-send behavior.
- Existing XLSX business format remains covered by focused tests and manual acceptance.

## Final Route Contract

MR-7 route acceptance reports exactly 11 Admin Pharma routes:

- dashboard;
- Medicine/HSSP index/create/edit;
- Drug Bid Award index/create/edit;
- Supplier Tracking index/create/edit;
- Price List create.

No public Pharma API route is part of the accepted contract.

## Final Acceptance Evidence

MR-7 acceptance was executed from `main` checkpoint `c5d6e4b341c5f99f2bf73d8104fba0975ddd5375` on the dedicated closeout branch.

```bash
php artisan route:list --path=admin/pharma
php artisan test tests/Feature/Pharma Modules/Pharma/Tests
npm run build
```

Results:

- Route inventory: **PASS — 11 Pharma Admin routes**.
- Focused Pharma regression: **PASS — 41 tests, 240 assertions**.
- Frontend production build: **PASS — 34 modules transformed**.
- MR-6 final manual PriceList UI acceptance immediately before merge: **PASS**.
- Earlier workspace MRs also completed their focused tests and manual UI acceptance before merge.

No full-project test suite was run; this follows the agreed module-focused verification policy.

## Major Refactor Delivery Record

- PR #88 — MR-1 Security Foundation + MR-2 Shared Import/Export Hardening.
- PR #89 — Pharma Admin Dashboard.
- PR #90 — MR-3 Medicine/HSSP Workspace.
- PR #91 — MR-4 Drug Bid Award Workspace + Sync-Ready Foundation.
- PR #92 — MR-5 Supplier Tracking Integrity + Workspace.
- PR #93 — MR-6 Price List Security + Pipeline; merged as `c5d6e4b341c5f99f2bf73d8104fba0975ddd5375`.
- MR-7 — Final Acceptance + documentation closeout.

## Intentional Deferred Scope / Non-goals

The following are not blockers for Major Refactor acceptance:

- actual Muasamcong -> Pharma production synchronization/wiring;
- automated fuzzy Medicine matching;
- production/runtime enablement of Pharma;
- PriceList database entity/table;
- PriceList queue/background generation unless future benchmarking justifies it;
- user upload/replacement of the PriceList source workbook;
- switching PriceList source data to Medicine database records;
- changing Medicine -> Supplier Tracking cascade-delete policy;
- broad project-wide regression unrelated to Pharma.

Any of these should begin as a separately approved future objective with its own analysis and acceptance criteria.

## Final Recommendation

Close the Pharma Major Refactor after MR-7 documentation is reviewed and merged.

Do not continue refactoring merely for architectural aesthetics. Future work should be driven by a concrete business objective, especially Muasamcong synchronization or production enablement, and should preserve the accepted boundaries established by this refactor.
