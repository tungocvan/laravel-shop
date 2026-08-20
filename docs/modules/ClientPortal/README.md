# ClientPortal

`Modules/ClientPortal` is the project's authenticated Client/PWA application platform.

This documentation was generated from the implementation on `agent/price-list-excel-data-types` before merge to `main`.

For detailed findings and priorities, read:

- `docs/modules/ClientPortal/ANALYSIS.md`
- `docs/modules/ClientPortal/INFORMATION.md`

## Role in the project

ClientPortal is a **support module**, not a business-domain module.

Its job is to provide:

```text
Authenticated Client launcher
        ↓
Application registry + permissions
        ↓
Application adapter
        ↓
Source domain module/service
```

The intended dependency rule is:

```text
ClientPortal -> Muasamcong / other domain modules
Domain module -X-> ClientPortal
```

A domain module owns its data and business services. ClientPortal owns the Client/PWA presentation, Client permissions and Client-specific workflow state.

## Current application

The current adapter is:

```text
Applications/Muasamcong
```

It provides:

- drug pricing search;
- database-first lookup;
- queued selected-row synchronization;
- search/sync history;
- Wishlist;
- public drug sharing;
- share management;
- Price List creation;
- queued XLSX generation;
- queued PDF conversion;
- public Price List link;
- queued Price List email delivery.

## Application convention

New applications should follow:

```text
Modules/ClientPortal/Applications/{Application}/
├── manifest.php
├── routes.php
├── Http/
├── Jobs/
└── Services/
```

The manifest must declare `source_module`. The registry hides the adapter when that source module is disabled.

## Permission convention

Use:

```text
client.{application}.access
client.{application}.{feature}.view
client.{application}.{feature}.{action}
```

Client permissions use guard `web`.

Do not use a `.view` permission to authorize destructive/mutating actions unless that behavior is an explicit product contract. The current module analysis identifies places where action-level permission separation should be improved.

## PWA contract

The launcher is:

```text
/my-apps
```

PWA metadata/resources:

```text
/manifest.webmanifest
/service-worker.js
/pwa/*
```

Authenticated navigation must remain network-first and must not be stored in the service-worker cache.

## Domain access rule

Application adapters may consume source-domain public services/models, but persistence rules should live in the source domain when they are reusable business rules.

Preferred:

```text
Client Controller
    -> Client application service
    -> Muasamcong domain service
    -> Muasamcong model/database
```

Avoid duplicating canonical Muasamcong persistence rules inside ClientPortal controllers.

## Price List architecture

The rich Price List export is intentionally specialized and should not be replaced mechanically by the generic FastExcel import/export base.

It needs features beyond a normal dataset export:

- Admin-defined headers/columns;
- String/Number/Date typing;
- widths/decimals/alignment;
- logo and signature drawings;
- company header/footer;
- A4 page setup;
- PDF conversion;
- private file lifecycle;
- sharing/email delivery.

Preserve the specialized PhpSpreadsheet renderer while decomposing its responsibilities into smaller services.

## Private artifact rule

Price List XLSX/PDF files are private artifacts.

They should remain on private storage and be downloaded through authenticated/authorized routes, except for explicitly created high-entropy public share links.

Every generated artifact must have an immutable unique path. The current analysis identifies the XLSX second-level filename collision as a P1 issue that must be corrected before stable merge.

## Queue rule

Long-running operations remain queued:

```text
Drug pricing sync
XLSX generation
PDF conversion
Email delivery
```

Queue workflows should use durable state transitions:

```text
queued -> processing -> completed/failed
```

External side effects such as email should have explicit idempotency/retry semantics.

User-facing errors must be sanitized. Raw exception/process output belongs in structured logs, not Client responses.

## Sharing rule

Public sharing must define:

- ownership;
- high-entropy token;
- payload/file scope;
- expiry policy;
- revoke policy;
- audit/delivery semantics;
- retention.

Drug sharing already supports expiry/revoke. Price List sharing currently does not and is flagged in `ANALYSIS.md`.

## Export Profile rule

Price List uses Muasamcong Admin `SyncedExportProfile` configuration.

Before making this a stable public contract, decide whether profiles are:

```text
Global published templates
OR
User/organization-owned templates
OR
Explicitly shared templates
```

The underlying table has `user_id`, while the current Client controller reads all profiles. This scope must become explicit.

## Recommended refactor direction

The current analysis recommends **Major Refactor**, not rebuild.

Preserve the module and adapter architecture, but separate responsibilities approximately as:

```text
ClientPortal
├── Registry / permissions
├── Application shell
├── Applications/Muasamcong
│   ├── Search presentation service
│   └── Controllers
└── Price List workflow
    ├── Workspace service
    ├── Workbook builder
    ├── Artifact storage
    ├── Share lifecycle
    └── Delivery lifecycle
```

Queue jobs should be thin orchestrators around those services.

## Pre-merge P1 gate

Before merging the current Price List feature to `main`, address at least:

1. unique XLSX artifact path per export;
2. explicit export-profile visibility/publication scope;
3. action-level authorization for mutations;
4. safe Client-facing queue errors;
5. Price List share expiry/revoke policy;
6. delivery/share history correctness and concurrency;
7. PDF/email idempotency/retry semantics;
8. behavioral tests for ownership, authorization, XLSX, PDF, sharing and email.

The full evidence and verification plan is in `ANALYSIS.md`.

## Verification commands

After approved refactor implementation, the minimum targeted regression should include:

```bash
php artisan test tests/Feature/ClientApps
```

Then run the repository's full regression suite before merging `main`.

Manual checks should include:

- desktop + iPhone/PWA;
- search database/snapshot/API source display;
- selected synchronization;
- Wishlist;
- drug share + revoke/expiry;
- Price List creation;
- Excel formatting/Print Preview;
- PDF conversion;
- file permissions/download;
- share lifecycle;
- email Excel/PDF/both;
- multiple concurrent exports/conversions.

## Documentation status

`docs/modules/ClientPortal` was created by the docs-only `/analyze-module` workflow. No application source code is modified by that analysis task.