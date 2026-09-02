# Invoices — Module Contract

## 1. Identity

- Module: `Invoices`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Invoices/config/module.php`
- Routes: `Modules/Invoices/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

Invoices is the canonical domain owner for electronic invoice ingestion, local invoice persistence, GDT synchronization, invoice import/export, invoice-related PDF/file metadata, and invoice backup execution metadata.

Admin Invoices owns its permission-aware `/admin/invoices/*` workspaces. ClientPortal/PWA presentation is a consumer boundary and does not transfer domain ownership to ClientPortal.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Electronic invoice persistence and querying | Invoices | `Invoice` model / invoice services |
| GDT authentication and invoice synchronization | Invoices | GDT Livewire/services/commands |
| Invoice Excel import/export | Invoices | import/export services and exports |
| Invoice PDF retrieval and file metadata | Invoices | PDF/file services and protected download routes |
| Invoice backup execution metadata | Invoices | backup services / `invoice_backup_runs` |
| Admin invoice workspace | Invoices | `/admin/invoices/*` |
| Read-only Admin dashboard | Invoices | `admin.invoices.dashboard` / `InvoicesDashboardController` |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Admin authentication and Admin shell | Admin/Auth | Invoices consumes the existing admin guard/layout |
| ClientPortal authentication/navigation/adaptive shell | ClientPortal | ClientPortal may consume approved safe Invoices boundaries |
| PWA presentation | ClientPortal | Invoices must not embed Admin routes, guards, or Blade into PWA surfaces |
| Generic shared import/export infrastructure | Shared | Invoices consumes shared infrastructure when applicable |

## 5. Dependencies

### Direct dependencies

The runtime manifest currently declares no hard module dependencies. Refactor work must not add a hard dependency without updating both this contract and `Modules/Invoices/config/module.php`.

### Integration dependencies

Invoices integrates with external GDT and MeInvoice capabilities through server-side services. Credentials/tokens remain server-side.

Invoices may consume repository Admin/Auth/Shared infrastructure without transferring Invoices domain ownership.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Admin operators | Admin invoice dashboard, synchronization, list, reports, export and protected file actions |
| ClientPortal (future/approved integration only) | Explicitly approved safe invoice application/domain read boundaries and protected external-file handoff |
| Console/queue runtime | Synchronization, backup and invoice processing operations |

## 7. Canonical Routes

Canonical Admin route group: `/admin/invoices/*`, including:

- `admin.invoices.index`
- `admin.invoices.dashboard`
- `admin.invoices.create-token`
- `admin.invoices.hoadon`
- `admin.invoices.hoadon-list`
- `admin.invoices.reports.partners`
- protected invoice download routes

Legacy `/invoices/*` routes are compatibility aliases and are not canonical ownership evidence.

Ownership audit follows:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

- `InvoicesController` owns Admin route adaptation/orchestration only; business logic should remain in services.
- `InvoicesDashboardController` is the read-only Admin dashboard entry.
- API controllers must retain authentication/validation and expose only approved contracts.

### Livewire / UI Components

- `HoadonList` is the canonical invoice list workspace but must remain UI/state orchestration rather than a business-logic owner.
- GDT authentication/synchronization components remain canonical UI boundaries for their workflows.
- `PartnerReport` and backup UI remain specialized workspace boundaries where runtime callers prove use.
- `InvoiceList` and `InvoiceManager` are quarantined pending caller/reachability proof; similar names or thin implementations are not deletion proof.

### Services

Services own GDT integration, invoice querying/filtering, PDF/file operations, dashboard aggregation, backup operations, and other domain/application orchestration. Refactor should move business orchestration out of Livewire/controllers into explicit service/action boundaries rather than creating additional competing UI components.

### Models

Models represent invoice persistence, invoice file metadata, and backup execution metadata according to owned migrations.

## 9. Persistence Ownership

| Table / storage | Owner | Migration/source | Notes |
|---|---|---|---|
| `invoices` | Invoices | `2025_11_21_045614_invoices.php` | Canonical local invoice records |
| `invoice_files` | Invoices | `2026_08_15_120000_create_invoice_files_table.php` | Invoice PDF/file metadata |
| `invoice_backup_runs` | Invoices | `2026_08_15_230000_create_invoice_backup_runs_table.php` | Backup execution metadata |
| Module invoice/PDF storage | Invoices | storage services/config | Protected file state; do not make private files public as a convenience workaround |

The historical manifest declaration of only `invoices` is architecture drift and must be synchronized to all three owned tables.

No table rename, destructive migration, migration-history rewrite, or new uniqueness constraint is authorized by this refactor contract without a separate data audit and explicit approval.

## 10. Integration Boundaries

### GDT / MeInvoice

- Business owner: Invoices.
- Consumer: Invoices Admin/console/queue workflows.
- Direction: UI/commands → Invoices services → external integration.
- Credentials and tokens must never appear in Livewire public state, DTOs, rendered HTML, logs, or ClientPortal payloads.

### Admin

- Business owner: Invoices for invoice capabilities; Admin/Auth for shell/authentication.
- Invoices uses `auth:admin` and permission middleware/server-side authorization.
- UI visibility is not a substitute for authorization.

### ClientPortal / PWA

- Invoices remains domain owner.
- ClientPortal owns client routes, guards, permissions, adaptive navigation and presentation.
- ClientPortal may consume only explicitly approved safe Invoices application/domain boundaries.
- PDF/file handoff must follow `docs/PWA_EXTERNAL_FILE_HANDOFF.md`; protected files must not be exposed through permanent public URLs.

## 11. Compatibility / Deprecated Boundaries

| Artifact | Canonical replacement | Status | Removal condition |
|---|---|---|---|
| `/invoices/*` aliases | `/admin/invoices/*` | deprecated compatibility | caller/bookmark proof + regression + explicit approval |
| `InvoiceList` | canonical list workspace to be proven | quarantined | caller/reachability proof and canonical replacement proof |
| `InvoiceManager` | canonical workspace to be proven | quarantined | caller/reachability proof and canonical replacement proof |

Deprecated or thin code is not dead code.

## 12. Quarantine

The following boundaries must not be deleted or materially expanded without focused proof/approval:

- `InvoiceList` and `InvoiceManager` until caller/reachability audit is complete;
- persistence/schema changes affecting invoice business identity or uniqueness;
- runtime GDT `.env` mutation;
- public-link Google Drive/backup behavior;
- broad backup fingerprint/storage changes;
- ClientPortal/PWA PDF integration until an approved client boundary is implemented.

## 13. Refactor Invariants

Every Invoices refactor must preserve:

1. canonical `/admin/invoices/*` route names and authorization unless an approved contract change says otherwise;
2. `auth:admin` and permission-aware server-side authorization;
3. owned persistence contracts for `invoices`, `invoice_files`, and `invoice_backup_runs`;
4. server-side secrecy of GDT/MeInvoice credentials and tokens;
5. supported compatibility aliases until caller proof and explicit approval authorize retirement;
6. ClientPortal/PWA non-ownership and dependency direction;
7. protected file authorization and non-public storage boundaries;
8. bounded Admin pagination and normalized page-size values;
9. explicit page-selection versus all-matching-selection semantics;
10. export contract: selected rows take precedence; when no rows are selected, export the complete approved current filtered result set, never only the current page;
11. no destructive schema/data migration without a separately approved persistence plan.

## 14. Required Refactor Audit

Before deleting or rehoming affected runtime artifacts, trace:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

Classification target for the approved 2026-09-02 refactor:

- `KEEP`: GDT auth/API, synchronization, invoice persistence, invoice files, backup-run persistence, Excel import/export, PDF/MeInvoice, Admin routes/dashboard, canonical list workspace.
- `KEEP + REFACTOR/HARDEN`: `HoadonList`, query/filter boundaries, export, PDF/file orchestration, backup orchestration, Admin UI.
- `QUARANTINE`: `InvoiceList`, `InvoiceManager`, persistence-sensitive uniqueness/schema changes until proof exists.
- `DEFER / DEPRECATION`: legacy `/invoices/*` aliases until caller proof.
- `NON-OWNERSHIP`: ClientPortal/PWA presentation, Admin authentication/shell.

## 15. Required Regression Scope

Minimum closeout gate for affected slices:

1. syntax/lint as applicable;
2. focused tests for changed Invoices boundaries;
3. Invoices module regression;
4. impacted Admin regression when Admin surfaces/boundaries change;
5. ClientPortal regression only when its integration boundary changes;
6. canonical and compatibility route verification;
7. Pint on changed PHP files;
8. frontend build when Blade/assets/UI change;
9. manual desktop/mobile Admin UI smoke for changed canonical surfaces;
10. installed-PWA file-handoff acceptance only when PWA download/open behavior changes.

Full-project regression is not automatic.

## 16. Architectural Change Rules

`MODULE.md` is the architectural source of truth for Invoices.

Update this file in the same PR whenever changing responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine, or refactor invariants.

Source and `MODULE.md` must not merge with conflicting architectural contracts.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Runtime GDT `.env` mutation | Invoices/System boundary | Sensitive runtime configuration behavior requires separate design | approved secure configuration boundary + regression |
| Broad/mixed synchronization workspace capabilities | Invoices | Existing workspace contains multiple operational responsibilities | service/action extraction with stable UI contract |
| Potentially unbounded import/export, ZIP, option and backup fingerprint paths | Invoices | Production scale/safety concern | bounded/streamed design + focused performance/contract tests |
| Potential per-row PDF status queries | Invoices | Query-efficiency concern | query profiling + batch/eager status boundary |
| Public export storage for financial spreadsheets | Invoices | Data exposure/storage lifecycle concern | approved private delivery/storage lifecycle |
| Public-link Google Drive flow | Invoices | Security/integration concern | approved protected sharing model |
| Missing persisted global GDT job registry | Invoices | Operational observability debt | approved job-state model |
| Missing DB unique constraint for invoice business identity | Invoices | Data migration risk | duplicate audit + defined business key + explicit approval |
| Legacy `/invoices/*` aliases | Invoices | Compatibility | caller/bookmark proof + explicit removal approval |
| ClientPortal/PWA invoice presentation and PDF handoff | ClientPortal + Invoices integration | Separate client ownership boundary | approved client integration slice following PWA handoff contract |

## 18. Architecture Decisions

### 2026-09-02 — Establish Invoices canonical ownership contract

**Decision:** Invoices remains the canonical domain owner for electronic invoice ingestion, GDT synchronization, local invoice persistence, invoice import/export, invoice PDF/file metadata, and backup execution metadata. Its owned persistence is explicitly `invoices`, `invoice_files`, and `invoice_backup_runs`.

**Reason:** Runtime migrations and the existing Admin dashboard demonstrate broader persistence ownership than the historical module manifest, while ClientPortal/PWA and Admin shell/auth remain separate ownership boundaries.

**Impact:** The module manifest must be synchronized; legacy aliases and thin components require caller proof before removal; the approved major refactor may make `HoadonList` thinner and harden query/export/PDF/backup/UI boundaries without destructive schema changes.
