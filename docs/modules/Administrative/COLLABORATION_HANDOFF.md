# Administrative — Collaboration Handoff

## Current objective

Major/Clean Refactor of `Modules/Administrative` under:

- `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- `docs/GITHUB_COLLABORATION_WORKFLOW_REFACTOR_MODULE.md`
- `docs/MODULE_REFACTOR_WORKFLOW.md`
- `.codex/standards/ADMIN_UI_STANDARD.md` for Admin UI work

## Approved target

User approved the Administrative Module Contract and the refactor architecture on 2026-09-02.

Delivery strategy is intentionally consolidated to reduce repeated pull/test cycles. The primary architecture work and safe cleanup assessment are being kept on one branch/PR unless a later change introduces schema/authz/cross-module risk.

## Branch

`refactor/administrative-architecture-boundaries`

Base: `main`

## Contract gate

`docs/modules/Administrative/MODULE.md` was missing at refactor bootstrap. Runtime/ownership audit was performed first, target architecture was approved, and the durable contract has now been created and synchronized with the implemented boundary.

## Canonical ownership baseline

Administrative owns:

- administrative procedure definitions;
- public submission intake;
- submission reads plus lifecycle/processing;
- Administrative files and controlled downloads;
- status history;
- receipt generation/delivery;
- token-based public lookup/result access;
- Administrative-specific import/export mapping and domain rules.

Administrative does not own the global Admin shell, global identity/authorization infrastructure, generic Shared Import/Export infrastructure, or global branding/settings.

## Mandatory invariants

- Preserve anonymous public workflow vs authenticated `auth:admin` workflow separation.
- Preserve `administrative.*` permissions and server-side authorization.
- Preserve canonical public/admin route names and URL contracts unless separately approved.
- Preserve four-table persistence ownership.
- Do not weaken file privacy, token/receipt access, throttle, or no-store protections.
- Do not delete/rehome artifacts without caller/dependency proof.
- Admin UI changes must follow `.codex/standards/ADMIN_UI_STANDARD.md`, including bounded pagination.

## Audit conclusions

- `SubmissionService` mixed read/query concerns with write/workflow responsibilities. Admin read/query behavior is now separated into `SubmissionQueryService`; lifecycle/write operations remain in `SubmissionService`.
- Administrative `ImportExport` already consumes `Modules\Shared\Services\ImportExport\BaseImportExportService`; Administrative-specific mapping, validation, token security and audit behavior remain correctly domain-owned. No rehome is required.
- `PublicBrandingService` is a thin integration adapter over canonical System settings with optional Admission overlay only when Admission is enabled and its service exists. It remains `KEEP adapter` and is not a branding owner.
- `LookupService`, `ReceiptService` and `AdministrativeFileService` implement sensitive access/delivery boundaries and remain `KEEP` without security-semantic changes.
- Existing Administrative Admin tables already use bounded page-size options `[10, 25, 50, 100]` and a module-scoped indigo pagination view consistent with the current Admin UI contract.
- Route-source verification confirms the canonical public lookup, public procedure and authenticated Admin route groups retain their existing prefixes, names, middleware, permission checks, token regexes, throttle and private/no-store protections.
- No schema/persistence rehome or destructive cleanup is justified in this batch.
- No proven duplicate/dead artifact currently meets the workflow threshold for DELETE/REHOME; unproven legacy remains DEFER rather than speculative cleanup.

## Artifact classification

| Artifact/family | Classification | Decision |
|---|---|---|
| `SubmissionQueryService` | KEEP | Canonical Admin submission read/query boundary extracted during refactor |
| `SubmissionService` | KEEP / REFACTOR | Canonical intake/write/workflow boundary after read-side extraction |
| `ProcedureService` | KEEP | Cohesive procedure domain/query/file-template boundary |
| `AdministrativeFileService` | KEEP | Controlled Administrative file validation/storage/download boundary |
| `LookupService` | KEEP | Public token/session access boundary |
| `ReceiptService` | KEEP | Receipt/status notification delivery boundary |
| `ImportExport` | KEEP | Administrative mapping adapter on Shared Import/Export foundation |
| `PublicBrandingService` | KEEP adapter | Cross-module settings/branding adapter only; not canonical branding owner |
| Existing routes/permissions/tables | KEEP / QUARANTINE from incompatible changes | Compatibility/security/persistence contracts |
| Unproven duplicates/legacy | DEFER | No deletion without caller/reachability proof |

## Current implementation status

- [x] Refactor workflow loaded.
- [x] Route-first/runtime baseline audit completed for the primary architecture scope.
- [x] Missing `MODULE.md` gate identified and resolved.
- [x] Module Contract proposed and user-approved.
- [x] Feature branch created.
- [x] `docs/modules/Administrative/MODULE.md` created and synchronized with final architecture decisions.
- [x] Service/test audit completed for Submission, Procedure, ImportExport, file, lookup, receipt and branding boundaries.
- [x] Submission read/write boundary implemented.
- [x] Administrative refactor contract test updated to protect the new boundary.
- [x] Focused test PASS — `10 passed (57 assertions)` on 2026-09-02.
- [x] Administrative regression PASS — `17 passed (151 assertions)` on 2026-09-02.
- [x] Impacted regression: NOT APPLICABLE for current source scope; Shared/Admin infrastructure was not changed.
- [x] Pint PASS — 5 changed PHP files checked on 2026-09-02.
- [x] Runtime route verification PASS — 17 `administrative*` routes and 8 `admin.administrative*` routes listed on 2026-09-02.
- [x] Build: NOT APPLICABLE for current source scope; no Blade/asset UI change was made in this batch.
- [ ] Manual UI acceptance PASS.
- [ ] PR ready/created.

## Current changed source scope

- `Modules/Administrative/Services/SubmissionQueryService.php` — new read/query boundary.
- `Modules/Administrative/Services/SubmissionService.php` — Admin query methods removed; lifecycle/write behavior preserved.
- `Modules/Administrative/Livewire/Submissions/SubmissionTable.php` — query dependency switched to `SubmissionQueryService`.
- `Modules/Administrative/Livewire/Submissions/SubmissionDetail.php` — detail read dependency switched to `SubmissionQueryService`.
- `tests/Feature/Administrative/AdministrativeRefactorContractTest.php` — architecture contract updated for read/write separation.
- `docs/modules/Administrative/MODULE.md` — durable ownership/integration decisions synchronized with implementation.

No routes, migrations, database tables, permission IDs, public token/receipt contracts, Blade UI/assets or storage visibility rules were changed.

## Regression strategy

Default scope is module-scoped, not full-project:

- focused Administrative refactor contract test — PASS;
- Administrative Feature regression — PASS;
- Admin regression — NOT APPLICABLE because Admin shell/authz infrastructure was not changed;
- Shared/import-export regression — NOT APPLICABLE because Shared infrastructure was not changed;
- runtime route verification — PASS;
- Pint changed PHP files — PASS;
- frontend build — NOT APPLICABLE because no UI/assets changed;
- manual UI smoke — remaining final acceptance gate for Administrative list/detail/public lookup flows.

Full-project regression: `NOT APPLICABLE` for the current architecture batch because shared/core infrastructure has not been modified.

## Next action

A consolidated draft PR may be opened now because all automated/local technical gates are green. Before marking the PR ready for merge, perform one manual UI smoke of the affected Administrative submission list/detail plus one public lookup/procedure surface and record `UI PASS`.
