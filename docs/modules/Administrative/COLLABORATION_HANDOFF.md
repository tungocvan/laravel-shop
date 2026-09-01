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

## Current implementation status

- [x] Refactor workflow loaded.
- [x] Route-first/runtime baseline audit started.
- [x] Missing `MODULE.md` gate identified.
- [x] Module Contract proposed and user-approved.
- [x] Feature branch created.
- [x] `docs/modules/Administrative/MODULE.md` created.
- [ ] Deep caller/service/view/test audit completed.
- [ ] Primary architecture batch implemented.
- [ ] Focused tests PASS.
- [ ] Administrative regression PASS.
- [ ] Impacted regression PASS where applicable.
- [ ] Route verification PASS.
- [ ] Pint PASS.
- [ ] Build PASS when UI changes are present.
- [ ] Manual UI acceptance PASS.
- [ ] PR ready/created.

## Audit priorities

1. Trace all Administrative routes through controllers, Livewire/views, services, models and persistence.
2. Prove callers and cross-module dependencies before any rehome/delete decision.
3. Audit `SubmissionService` responsibility concentration.
4. Audit Administrative `ImportExport` against repository Shared Import/Export infrastructure.
5. Audit `PublicBrandingService` against the canonical global branding/settings owner.
6. Audit Admin tables/filters/pagination against the current Admin UI standard.
7. Add/update contract tests before destructive cleanup.

## Regression strategy

Default scope is module-scoped, not full-project:

- focused Administrative tests for changed capability;
- Administrative regression;
- Admin regression when Admin UI/shell/authz integration changes;
- Shared/import-export regression only when that boundary changes;
- route verification;
- Pint changed PHP files;
- frontend build for UI/assets changes;
- manual UI smoke for affected canonical surfaces.

Full-project regression: `NOT APPLICABLE` by default unless later changes touch shared/core infrastructure broadly.

## Next action

Complete deep read-only caller/runtime/test audit on the feature branch, then implement the first consolidated architecture batch without changing approved public/persistence/security contracts.
