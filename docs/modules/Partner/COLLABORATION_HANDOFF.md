# Partner Collaboration Handoff

## Current branch

Original Partner refactor branch: `refactor/partner-master-hub-dashboard-lookup-sync`.

Current cross-module follow-up branch: `feat/invoices-auto-upload-google-drive` (PR #171).

## Objective

Partner remains the ERP master hub for customers, suppliers and other external organizations. The current follow-up adds an explicit review boundary for Partner candidates discovered while importing invoice workbooks.

## Decisions locked

- `Partner` remains canonical ERP data.
- `PartnerSourceReference` remains canonical accepted external provenance.
- `PartnerSyncCandidate` is staging only; it is not canonical master data.
- Invoices may submit normalized candidates, but Invoices never creates or updates Partner master rows directly.
- Tax code is the primary invoice-source match signal.
- Candidate ingestion is idempotent by `(source, tax_code)`.
- Existing Partner values are never silently overwritten.
- Existing source-reference ownership is never silently reassigned.
- Human review is required before create/merge/ignore.
- Explicit merge applies only operator-selected fields; unselected ERP fields stay unchanged.
- `sold` invoice counterparties map to `customer`; `purchase` counterparties map to `supplier`.
- Candidate statuses are `pending`, `matched`, `conflict`, and `ignored`.

## Existing Partner baseline

The previously accepted Partner refactor remains intact:

- `/admin/partners/dashboard` Partner Dashboard;
- `/admin/partners/business-lookup` external business lookup;
- provider abstraction and selectable source;
- `PartnerMatcher`, `PartnerSyncPlanner`, `PartnerSyncService`;
- `PartnerSourceReference` provenance;
- canonical list/query/export boundary;
- bounded pagination and Admin UI standard;
- action-level authorization;
- legacy `/admin/partner/partners/*` compatibility routes.

User acceptance for that baseline was `UI PASS` on 2026-09-07.

## Invoice candidate follow-up implemented

### Persistence

Added `partner_sync_candidates` owned by Partner with:

- `source` + `tax_code` idempotent identity;
- name/address/email/phone snapshot;
- customer/supplier roles;
- `pending / matched / conflict / ignored` status;
- optional matched Partner ID;
- field-level conflict metadata;
- source metadata and first/last-seen timestamps.

`Modules/Partner/config/module.php` now declares:

```text
partners
partner_source_references
partner_sync_candidates
```

### Intake boundary

`PartnerCandidateIntakeService` accepts source-module candidates without mutating Partner master data.

Behavior:

- no Partner with MST -> `pending`;
- Partner exists and source fields match -> `matched`;
- Partner exists and fields differ or source can safely enrich a missing local field -> `conflict` for review;
- ignored candidates remain ignored when seen again;
- repeated invoice sightings update the candidate instead of creating duplicates;
- roles are merged at candidate level so one MST may become both customer and supplier.

### Review boundary

Added `/admin/partners/sync/invoices` and `InvoiceCandidateReview`.

The operator can:

- inspect/filter candidates;
- create a new Partner only after explicit confirmation;
- link/merge with an existing MST match;
- choose exactly which `name / address / email / phone / partner_types` fields are applied;
- keep all unselected ERP values unchanged;
- ignore a candidate without deleting invoice data.

Accepted candidates promote provenance into `PartnerSourceReference`. If `(source, external_id)` already belongs to a different Partner, the operation fails rather than reassigning ownership.

### Authorization

- inspect workspace: `view_partner`;
- create new Partner: `create_partner`;
- merge/ignore: `edit_partner`.

## Cross-module contract with Invoices

```text
Invoices workbook
    -> invoice validation/import
    -> Invoices DB
    -> normalized counterparty extraction
    -> PartnerCandidateIntakeService
    -> partner_sync_candidates
    -> human review in Partner
    -> Partner + PartnerSourceReference
```

Candidate extraction only accepts workbook rows whose invoice business identity exists in the Invoices table after import. This prevents unrelated/invalid workbook rows from becoming Partner candidates.

## Validation status

Prior Partner refactor validation remains PASS. The new invoice-candidate follow-up adds focused coverage for:

- invoice import creates candidate but not Partner;
- existing Partner conflict detection does not overwrite master data;
- explicit candidate review can create Partner and provenance;
- explicit merge updates only selected fields and merges selected roles.

Latest local Pint/focused-test/build/UI verification for this follow-up is still required before PR #171 merge.

Do not run full-project regression unless a focused failure proves wider impact. Validate Invoices + Partner + affected routes/build/UI.

## Deferred

Still deferred:

- automatic/bulk Partner master mutation;
- automatic conflict overwrite;
- unattended candidate approval;
- scheduled external crawling or CAPTCHA/browser-challenge handling;
- automatic dedup/merge beyond current tax-code identity rules;
- source arbitration when multiple external sources disagree;
- removal of tax-code uniqueness;
- promotion of every source-only field into Partner master columns.
