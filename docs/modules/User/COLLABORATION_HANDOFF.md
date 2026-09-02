# User — Collaboration Handoff

## Current objective

Major/Clean refactor of `Modules/User` under `docs/GITHUB_COLLABORATION_WORKFLOW.md` and `docs/MODULE_REFACTOR_WORKFLOW.md`.

Approved by user on 2026-09-02 as one coherent delivery/PR to minimize repeated pull/test cycles.

## Branch

`refactor/user-module-contract-ui-export`

## Approved target

- Establish `docs/modules/User/MODULE.md` as the durable architecture contract.
- Preserve User as the shared account-directory shell.
- Keep Role as canonical owner of role/permission catalog; User only consumes/assigns allowed roles.
- Keep Auth as authentication-flow owner and Shared as generic import/export infrastructure owner.
- Refactor User Admin list/form/service boundaries without destructive schema/model re-homing.
- Bring all in-scope User Admin inputs/selects/search controls into `.codex/standards/ADMIN_UI_STANDARD.md`.
- Replace uncontrolled/default pagination rendering with approved Admin pagination behavior; bounded page sizes `10/25/50/100`, no unbounded `All`.
- Preserve visible-page header checkbox semantics.
- Export contract: selected IDs present → export selected only; no selected IDs → export all records in approved/filter scope, never current page only.
- Keep selected-export checkbox availability independent from delete permission when export permission allows it.
- Caller-proof any suspected duplicate/legacy API/customer/profile/address boundary before delete/rehome.

## Current state

- Read-only bootstrap/audit completed.
- Missing `MODULE.md` gate identified and approved target architecture presented.
- User explicitly approved the target plan.
- Implementation branch created.
- `MODULE.md` created on the implementation branch.
- No destructive migration/model ownership change is approved.

## Runtime evidence already confirmed

- `Modules/User/config/module.php`: shell module, enabled, direct dependency `Shared`; User CRUD/import/export permissions.
- Admin routes: `admin.user.index/create/edit` under `auth:admin` and User permissions.
- `UserTable`: search, role filter, bounded server normalization, visible-page selection and bulk delete.
- `UserService`: staff query/mutation boundary, current-account delete guard, Super Admin visibility/assignment safeguards.
- `ImportExport`: User-specific import/export service already reuses Shared base infrastructure but selected-ID export integration still needs implementation/verification.
- User list currently uses generic paginator links and form/list controls require Admin UI standard review.

## Implementation batch

1. Complete caller/runtime audit for User controllers/services/contracts/persistence and cross-module Auth/Role/Admin consumers.
2. Implement list/form UI normalization and explicit Admin pagination.
3. Integrate canonical Shared import/export UI where applicable.
4. Implement selected/all export semantics server-side and UI-side.
5. Add/update focused contract tests for selection, export, permissions, pagination and account safeguards.
6. Run Pint + focused User tests + User regression + impacted Auth/Role/Admin regression + routes + build.
7. Obtain manual UI PASS for inputs, pagination, responsive layout, checkbox/bulk/export behavior.
8. Update this handoff with exact files/tests/results before PR.
9. Create one consolidated PR after gates pass.

## Explicitly deferred/quarantined

- Re-homing `App\Models\User`.
- Destructive or ownership-changing user/profile/address migrations.
- Removing API/customer/profile/address surfaces without caller proof.
- Broad global pagination/UI migration outside the User delivery boundary.

## Merge gate

Do not merge until:

- approved source/contract changes are coherent;
- required focused/impacted tests pass;
- route/build/Pint gates pass where applicable;
- user reports Admin UI PASS;
- `MODULE.md` and this handoff reflect final runtime;
- PR is reviewed by the user according to the collaboration workflow.