# Administrative — Collaboration Handoff

## Current objective

Major/Clean Refactor of `Modules/Administrative` under:

- `docs/GITHUB_COLLABORATION_WORKFLOW.md`
- `docs/GITHUB_COLLABORATION_WORKFLOW_REFACTOR_MODULE.md`
- `docs/MODULE_REFACTOR_WORKFLOW.md`
- `.codex/standards/ADMIN_UI_STANDARD.md` for Admin UI work

## Status

**COMPLETED / MERGED** on 2026-09-02.

Primary implementation PR: **#139 — `refactor(administrative): establish architecture boundaries`**.

The user confirmed **UI PASS** after merge for the Administrative submission list/detail and public Administrative workflow smoke.

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

## Final architecture decisions

- `SubmissionQueryService` is the canonical Admin submission read/query boundary.
- `SubmissionService` remains the canonical intake/write/workflow boundary.
- `ProcedureService` remains cohesive and is not split.
- `AdministrativeFileService`, `LookupService` and `ReceiptService` remain dedicated security/delivery boundaries.
- Administrative `ImportExport` remains a domain adapter on top of Shared Import/Export infrastructure.
- `PublicBrandingService` remains a thin cross-module branding/settings adapter and is not a canonical branding owner.
- Existing Administrative routes, permissions, persistence ownership, token/receipt contracts and controlled file-access semantics remain unchanged.
- No schema/persistence rehome or destructive cleanup was justified.
- No duplicate/dead artifact met the caller-proof threshold for DELETE/REHOME; unproven legacy remains DEFER.

## Artifact classification

| Artifact/family | Classification | Decision |
|---|---|---|
| `SubmissionQueryService` | KEEP | Canonical Admin submission read/query boundary |
| `SubmissionService` | KEEP / REFACTORED | Canonical intake/write/workflow boundary |
| `ProcedureService` | KEEP | Cohesive procedure domain/query/file-template boundary |
| `AdministrativeFileService` | KEEP | Controlled Administrative file validation/storage/download boundary |
| `LookupService` | KEEP | Public token/session access boundary |
| `ReceiptService` | KEEP | Receipt/status notification delivery boundary |
| `ImportExport` | KEEP | Administrative mapping adapter on Shared Import/Export foundation |
| `PublicBrandingService` | KEEP adapter | Cross-module settings/branding adapter only |
| Existing routes/permissions/tables | KEEP / QUARANTINE from incompatible changes | Compatibility/security/persistence contracts |
| Unproven duplicates/legacy | DEFER | No deletion without caller/reachability proof |

## Final implementation scope

- `Modules/Administrative/Services/SubmissionQueryService.php` — new read/query boundary.
- `Modules/Administrative/Services/SubmissionService.php` — Admin query methods removed; lifecycle/write behavior preserved.
- `Modules/Administrative/Livewire/Submissions/SubmissionTable.php` — read dependency switched to `SubmissionQueryService`.
- `Modules/Administrative/Livewire/Submissions/SubmissionDetail.php` — detail read dependency switched to `SubmissionQueryService`.
- `tests/Feature/Administrative/AdministrativeRefactorContractTest.php` — architecture contract updated for read/write separation.
- `docs/modules/Administrative/MODULE.md` — durable ownership/integration contract established.

No routes, migrations, database tables, permission IDs, public token/receipt contracts, Blade UI/assets or storage visibility rules were changed.

## Final validation

- [x] Refactor workflow loaded.
- [x] Route-first/runtime baseline audit completed.
- [x] Missing `MODULE.md` gate resolved.
- [x] Module Contract approved and created.
- [x] Submission read/write boundary implemented.
- [x] Administrative refactor contract test updated.
- [x] Focused test PASS — `10 passed (57 assertions)`.
- [x] Administrative regression PASS — `17 passed (151 assertions)`.
- [x] Impacted regression: NOT APPLICABLE; Shared/Admin infrastructure unchanged.
- [x] Pint PASS — 5 changed PHP files.
- [x] Runtime route verification PASS — 17 `administrative*` routes and 8 `admin.administrative*` routes.
- [x] Build: NOT APPLICABLE; no Blade/assets changed.
- [x] Manual UI acceptance PASS — confirmed by user after merge.
- [x] PR #139 merged into `main`.

## Regression policy outcome

Full-project regression was **NOT APPLICABLE** for this refactor because shared/core infrastructure was not modified. Module-scoped tests, route verification, Pint and manual UI acceptance provided the required gates for the implemented scope.

## Durable invariants for future work

- Preserve anonymous public workflow vs authenticated `auth:admin` workflow separation.
- Preserve `administrative.*` permissions and server-side authorization.
- Preserve canonical public/admin route names and URL contracts unless separately approved.
- Preserve four-table persistence ownership.
- Do not weaken file privacy, token/receipt access, throttle, or no-store protections.
- Do not delete/rehome artifacts without caller/dependency proof.
- Admin UI changes must follow `.codex/standards/ADMIN_UI_STANDARD.md`, including bounded pagination.
- Update `docs/modules/Administrative/MODULE.md` whenever ownership, dependency, persistence or compatibility boundaries materially change.

## Deferred debt

No follow-up MR is required from this refactor solely for cleanup. Future work should only reopen deferred items when concrete runtime/caller evidence justifies it, especially branding-adapter simplification, further Submission workflow decomposition, or proven duplicate/dead-code removal.

## Next action

No immediate refactor action is required. `Administrative` is ready for normal feature/maintenance work under the established `MODULE.md` contract.
