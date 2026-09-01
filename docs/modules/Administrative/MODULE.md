# Administrative — Module Contract

## 1. Identity

- Module: `Administrative`
- Type: `domain`
- Status: `active`
- Manifest: `Modules/Administrative/config/module.php`
- Routes: `Modules/Administrative/routes/web.php`
- Last architecture review: `2026-09-02`

## 2. Purpose

`Administrative` is the canonical domain module for administrative procedures and their public/internal processing lifecycle. It owns procedure definitions, public submission intake, administrative submissions, submission files, status history, receipt generation, token-based public lookup, and result/file delivery through controlled routes.

The Module exposes two distinct runtime surfaces that must remain separate:

- a public anonymous self-service surface for procedure discovery, submission, receipts, supplementation, lookup and controlled downloads;
- an authenticated Admin surface for procedure management and submission processing.

## 3. Canonical Ownership

| Capability | Canonical owner | Runtime entry |
|---|---|---|
| Administrative procedure definitions | Administrative | `administrative.public.*`, `admin.administrative.procedures.*`, `ProcedureController`, `ProcedureService` |
| Public submission intake | Administrative | `administrative.public.submit`, `SubmissionForm`, `SubmissionService` |
| Administrative submission lifecycle | Administrative | `admin.administrative.submissions.*`, submission Livewire components, `SubmissionService` |
| Submission files and controlled downloads | Administrative | `AdministrativeFileService`, public/admin file download routes |
| Submission status history | Administrative | `AdministrativeStatusHistory` and submission processing runtime |
| Public receipt generation/delivery | Administrative | `ReceiptService`, `administrative.public.success`, `administrative.public.receipt.download` |
| Token-based public lookup/result access | Administrative | `LookupService`, `administrative.lookup.*` |
| Administrative import/export behavior | Administrative | Administrative import/export runtime, subject to refactor toward shared infrastructure where applicable |

## 4. Explicit Non-Ownership

| Capability | Canonical owner | Relationship |
|---|---|---|
| Admin shell/layout/navigation framework | Admin | Administrative consumes the canonical Admin shell for authenticated back-office UI |
| Authentication accounts and global authorization infrastructure | Account/Admin/System as defined by repository contracts | Administrative consumes guards/permissions; it does not own global identity infrastructure |
| Global/shared Import/Export infrastructure | Shared | Administrative may consume shared Import/Export infrastructure while retaining ownership of Administrative data mapping and domain rules |
| Global branding/site settings | Website/System as defined by runtime | Administrative may consume branding data through an adapter; it must not duplicate global branding ownership |

## 5. Dependencies

### Direct dependencies

The current `Modules/Administrative/config/module.php` declares no hard Module dependencies. Refactor work must not add an implicit hard dependency without updating both manifest/runtime and this contract.

### Integration dependencies

- Admin shell and authorization middleware for authenticated Administrative routes.
- Shared Import/Export infrastructure when the repository-standard implementation is applicable.
- Global branding/settings through an integration boundary only; Administrative remains consumer, not owner.

## 6. Consumers

| Consumer | Capability |
|---|---|
| Public website users | Procedure discovery, anonymous submission, receipt, supplement and token-based lookup |
| Admin operators | Procedure management, submission review/processing, files, history and data operations |
| Repository tests/operational tooling | Administrative route, permission, persistence and workflow contracts |

## 7. Canonical Routes

Canonical route groups owned by this Module:

- `administrative.public.*` under `/thu-tuc-hanh-chinh/*`;
- `administrative.lookup.*` under `/tra-cuu-ho-so/*`;
- `admin.administrative.*` under `/admin/administrative/*`.

Route ownership belongs to `Administrative` even when the URL uses the shared `/admin` prefix.

The canonical ownership audit is:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

## 8. Canonical Runtime Components

### Controllers

- `PublicProcedureController`: public procedure pages, submit/success/receipt/template entry points.
- `PublicLookupController`: token-based public lookup and controlled result download entry points.
- `ProcedureController`: authenticated procedure management shell/routes.
- `SubmissionController`: authenticated submission dashboard/detail/file entry points.

Controllers should remain transport/orchestration boundaries and must not become alternate owners of domain rules already owned by services.

### Livewire / UI Components

- `Livewire/Procedures/*`: procedure table/editor interaction.
- `Livewire/Submissions/*`: submission list/detail/processing interaction.
- `Livewire/Public/*`: anonymous lookup, submission, supplement and public header adapters.

Admin-facing UI must reuse the canonical Admin layout/shared components and comply with `.codex/standards/ADMIN_UI_STANDARD.md`.

### Services

- `ProcedureService`: canonical procedure-domain operations.
- `SubmissionService`: canonical submission lifecycle operations; may be decomposed internally when responsibility boundaries are proven.
- `AdministrativeFileService`: canonical Administrative file handling boundary.
- `LookupService`: token-based lookup/query boundary.
- `ReceiptService`: receipt generation boundary.
- `PublicBrandingService`: integration adapter only; it must not become canonical global branding owner.
- `ImportExport`: existing Administrative import/export implementation; audit for convergence on Shared infrastructure without moving Administrative domain mapping/rules out of this Module.

### Models

- `AdministrativeProcedure`
- `AdministrativeSubmission`
- `AdministrativeFile`
- `AdministrativeStatusHistory`

These models correspond to persistence owned by this Module.

## 9. Persistence Ownership

| Table / storage | Owner | Migration/source | Notes |
|---|---|---|---|
| `administrative_procedures` | Administrative | Administrative migrations | Procedure definition persistence |
| `administrative_submissions` | Administrative | Administrative migrations | Submission lifecycle persistence |
| `administrative_files` | Administrative | Administrative migrations | Submission/template/result file metadata owned by Administrative |
| `administrative_status_histories` | Administrative | Administrative migrations | Submission status audit/history |
| Administrative private file storage | Administrative | `AdministrativeFileService` / configured storage | Must remain behind controlled download/access boundaries; do not make submission/result files directly public as a refactor shortcut |

Persistence ownership must not be rehomed or deleted without explicit data compatibility/migration approval.

## 10. Integration Boundaries

### Admin shell/authz

- Business owner: Administrative for feature behavior; Admin/global auth infrastructure for shell/identity capability.
- Direction: Administrative consumes the Admin shell, `auth:admin`, and permission middleware.
- Administrative must preserve server-side permission enforcement; UI visibility alone is not authorization.

### Shared Import/Export

- Business owner: Administrative owns its exported/imported domain data, validation and mapping rules.
- Infrastructure owner: Shared owns generic import/export machinery when applicable.
- Direction: Administrative may consume Shared infrastructure; generic infrastructure must not absorb Administrative business rules.

### Global branding/settings

- Administrative may read canonical global branding/settings through a thin adapter.
- Branding configuration must not be duplicated or forked inside Administrative.

## 11. Compatibility / Deprecated Boundaries

No artifact is classified as safely removable solely because a similarly named implementation exists elsewhere.

Existing routes, route names, public URLs, access-token format, receipt contract and controlled file endpoints are compatibility boundaries until caller/runtime proof and an approved migration plan establish otherwise.

## 12. Quarantine

Until caller/dependency proof is complete:

- persistence schema and migrations are quarantined from destructive cleanup;
- public access-token and receipt contracts are quarantined from incompatible changes;
- file storage/access mechanics are quarantined from visibility/security weakening;
- cross-module branding and Import/Export overlap is quarantined from deletion/rehome based on naming similarity alone.

## 13. Refactor Invariants

Every refactor must preserve:

1. `Administrative` as canonical owner of procedures, submissions, files and status history;
2. separation between anonymous public workflows and authenticated Admin workflows;
3. existing route names/public URL contracts unless an explicitly approved migration changes them;
4. `auth:admin` and `administrative.*` permission enforcement for Admin operations;
5. token/receipt entropy and controlled public access behavior;
6. private/no-store/throttle protections on sensitive public responses where currently required;
7. persistence ownership of the four Administrative tables;
8. controlled file downloads rather than direct-public storage shortcuts;
9. Admin UI conformance with the repository standard, including bounded pagination for production-capable datasets;
10. dependency direction: Administrative may consume shared infrastructure, but shared modules must not absorb Administrative domain rules;
11. compatibility/deprecated artifacts until caller proof supports removal;
12. quarantine boundaries unless separately approved.

## 14. Required Refactor Audit

Before each destructive/rehome change, trace:

`Route → Controller → View/Livewire → Service → Model/Persistence → Callers → Cross-module dependencies`.

Affected artifacts must be classified `KEEP`, `REHOME`, `DELETE`, `QUARANTINE`, or `DEFER`.

## 15. Required Regression Scope

Minimum refactor gates:

- focused tests for the changed Administrative capability;
- Administrative Module regression;
- Admin regression when Admin shell/UI/authz contracts are touched;
- Shared/import-export regression when that integration boundary is changed;
- route verification for public lookup/public procedure/Admin Administrative routes;
- Pint for changed PHP files;
- frontend build when UI/assets change;
- manual UI smoke for affected public and Admin canonical surfaces.

Full-project regression is not automatic; it is required only when shared/core infrastructure or broader release scope justifies it.

## 16. Architectural Change Rules

Update this file in the same PR whenever changing responsibility, ownership/non-ownership, direct dependencies, canonical routes, integration boundaries, persistence ownership, compatibility/deprecation, quarantine, or refactor invariants.

Source and this contract must not merge while architecturally inconsistent.

## 17. Deferred Debt

| Debt | Owner/target module | Reason | Exit condition |
|---|---|---|---|
| Determine whether `PublicBrandingService` is only an adapter or contains duplicated global-branding logic | Administrative + canonical branding owner | Requires caller/runtime comparison | Canonical branding owner proven and Administrative adapter reduced without behavior regression |
| Evaluate convergence of Administrative `ImportExport` onto Shared Import/Export infrastructure | Administrative + Shared | Domain mapping must remain owned by Administrative and caller proof is required | Shared capability fit proven; Administrative mapping/validation retained; focused + impacted regression PASS |
| Decompose `SubmissionService` if audit proves unrelated responsibilities | Administrative | Large service size alone is not sufficient proof | Stable lifecycle boundaries and tests prove safe internal split |

## 18. Architecture Decisions

### 2026-09-02 — Establish Administrative domain ownership before Major Refactor

**Decision:** Keep `Administrative` as the canonical owner of administrative procedures, submissions, files, status histories, public receipt/lookup flows and authenticated processing workflows. Treat Admin, Shared and global branding/settings as integration boundaries rather than alternate domain owners.

**Reason:** Runtime routes, manifest permissions/tables, models and service boundaries identify Administrative as the active domain owner. URL prefixes or similarly named shared capabilities are not sufficient rehome/delete proof.

**Impact:** Major refactor may simplify internal services/UI and converge on shared infrastructure, but must preserve public/admin security boundaries, persistence ownership and compatibility contracts unless separately approved.
