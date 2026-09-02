# Administrative — Collaboration Handoff

## Current objective

Major/Clean Refactor of `Modules/Administrative` under:

- `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- `docs/GITHUB_COLLABORATION_WORKFLOW_REFACTOR_MODULE.md`
- `docs/MODULE_REFACTOR_WORKFLOW.md`
- `.codex/standards/ADMIN_UI_STANDARD.md` for Admin UI work

## Approved target

User approved the Administrative Module Contract and the refactor architecture on 2026-09-02.

Delivery strategy is intentionally consolidated to reduce repeated pull/test cycles:

1. **Primary architecture MR** — contract + Procedure + Submission lifecycle + file/receipt/lookup + Import/Export/internal service boundaries when caller proof permits.
2. **UI/cleanup MR** — Admin UI normalization, bounded pagination, proven dead/duplicate cleanup, final regression and closeout.

Additional MR split is allowed only when schema/authz/cross-module risk makes consolidation unsafe.

## Branch

`refactor/administrative-architecture-boundaries`

Base: `main`

## Contract gate

`docs/modules/Administrative/MODULE.md` was missing at refactor bootstrap. Runtime/ownership audit was performed first, target architecture was approved, and the durable contract has now been created on this branch.

Implementation may proceed only within that approved contract.

## Canonical ownership baseline

Administrative owns:

- administrative procedure definitions;
- public submission intake;
- submission lifecycle and processing;
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

- `SubmissionService` mixed read/query concerns with write/workflow responsibilities. The read side is now separated into `SubmissionQueryService`; write/lifecycle operations remain in `SubmissionService`.
- Administrative `ImportExport` already consumes `Modules\Shared\Services\ImportExport\BaseImportExportService`; Administrative-specific mapping, validation and audit behavior remain correctly domain-owned. No rehome is required.
- `PublicBrandingService` is a thin integration adapter over canonical System settings with optional Admission settings fallback; it remains `KEEP` as an adapter and must not become a branding owner.
- `LookupService`, `ReceiptService` and `AdministrativeFileService` implement sensitive access/delivery boundaries and remain `KEEP` without security-semantic changes.
- Existing Administrative admin tables already use bounded page-size options `[10, 25, 50, 100]` and a module-scoped indigo pagination view consistent with the current Admin UI contract.
- No schema/persistence rehome or destructive cleanup has been approved or required in this batch.

## Artifact classification

| Artifact/family | Classification | Decision |
|---|---|---|
| `SubmissionQueryService` | KEEP | Canonical read/query boundary extracted during refactor |
| `SubmissionService` | KEEP / REFACTOR | Canonical write/workflow boundary after read-side extraction |
| `ProcedureService` | KEEP | Cohesive procedure domain/query/file-template boundary |
| `AdministrativeFileService` | KEEP | Controlled Administrative file validation/storage/download boundary |
| `LookupService` | KEEP | Public token/session access boundary |
| `ReceiptService` | KEEP | Receipt/status notification delivery boundary |
| `ImportExport` | KEEP | Administrative mapping on Shared Import/Export foundation |
| `PublicBrandingService` | KEEP adapter | Cross-module branding adapter only; not canonical branding owner |
| Existing routes/permissions/tables | KEEP / QUARANTINE from incompatible changes | Compatibility/security/persistence contracts |
| Unproven duplicates/legacy | DEFER | No deletion without caller proof |

## Current implementation status

- [x] Refactor workflow loaded.
- [x] Route-first/runtime baseline audit completed for the primary architecture scope.
- [x] Missing `MODULE.md` gate identified and resolved.
- [x] Module Contract proposed and user-approved.
- [x] Feature branch created.
- [x] `docs/modules/Administrative/MODULE.md` created.
- [x] Service/test audit completed for Submission, Procedure, ImportExport, file, lookup, receipt and branding boundaries.
- [x] Submission read/write boundary implemented.
- [x] Administrative refactor contract test updated to protect the new boundary.
- [ ] Focused tests PASS.
- [ ] Administrative regression PASS.
- [ ] Impacted regression PASS where applicable.
- [ ] Route verification PASS.
- [ ] Pint PASS.
- [ ] Build PASS when UI changes are present.
- [ ] Manual UI acceptance PASS.
- [ ] PR ready/created.

## Current changed source scope

- `Modules/Administrative/Services/SubmissionQueryService.php` — new read/query boundary.
- `Modules/Administrative/Services/SubmissionService.php` — read methods removed; lifecycle/write behavior preserved.
- `Modules/Administrative/Livewire/Submissions/SubmissionTable.php` — query dependency switched to `SubmissionQueryService`.
- `Modules/Administrative/Livewire/Submissions/SubmissionDetail.php` — detail read dependency switched to `SubmissionQueryService`.
- `tests/Feature/Administrative/AdministrativeRefactorContractTest.php` — architecture contract updated for read/write separation.

No routes, migrations, database tables, permission IDs, public token/receipt contracts or storage visibility rules were changed.

## Regression strategy

Default scope is module-scoped, not full-project:

- focused Administrative refactor contract test;
- Administrative Feature regression;
- Admin regression only if a later Admin shell/UI integration change is introduced;
- Shared/import-export regression only if Shared infrastructure itself changes;
- route verification;
- Pint changed PHP files;
- frontend build only if UI/assets change;
- manual UI smoke for affected canonical surfaces.

Full-project regression: `NOT APPLICABLE` for the current architecture batch because shared/core infrastructure has not been modified.

## Next action

Run the consolidated architecture checkpoint locally: pull the branch, run the focused refactor contract test first, then the full Administrative Feature regression. If both pass, continue with route/Pint checks and only then decide whether a separate UI/cleanup MR is actually necessary.
