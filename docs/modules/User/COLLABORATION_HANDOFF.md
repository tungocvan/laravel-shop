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

Implementation is complete enough for the local validation/UI gate. No destructive migration/model ownership change was introduced.

Implemented on `refactor/user-module-contract-ui-export`:

- durable `MODULE.md` contract and this handoff;
- User list canonical search/select inputs with visible borders/background/focus states;
- bounded page sizes `10/25/50/100` with invalid values normalized to `10`;
- module-scoped Admin pagination view with white inactive controls and indigo active page;
- explicit reset-filter action and visible-page checkbox semantics;
- checkbox availability for `delete_user` OR `export_user`;
- selected count and clearer bulk-delete copy;
- Shared Import/Export panel moved into the reactive User table boundary so filters and selected IDs are supplied together;
- export contract: non-empty `selected_ids` restricts export, empty `selected_ids` exports all records in current approved filter scope;
- list/export visibility now share `UserService` staff scope, including non-Super-Admin exclusion of Super Admin accounts;
- User import no longer creates arbitrary Role catalog entries; unknown admin roles are rejected and role assignment uses existing Role catalog;
- User form inputs normalized to current Admin input/error/focus standard and duplicate Blade Super Admin filtering removed;
- focused `UserRefactorContractTest` added for contract/UI/export/role-ownership behavior.

## Files in the delivery boundary

- `Modules/User/Livewire/UserTable.php`
- `Modules/User/Services/UserService.php`
- `Modules/User/Services/ImportExport.php`
- `Modules/User/resources/views/livewire/user-table.blade.php`
- `Modules/User/resources/views/livewire/user-form.blade.php`
- `Modules/User/resources/views/pages/staff/index.blade.php`
- `Modules/User/resources/views/vendor/pagination/admin-users.blade.php`
- `tests/Feature/User/UserRefactorContractTest.php`
- `docs/modules/User/MODULE.md`
- `docs/modules/User/COLLABORATION_HANDOFF.md`

## Local validation gate — pending user execution

Run once after pulling this branch:

1. `./vendor/bin/pint --test Modules/User tests/Feature/User`
2. `php artisan test tests/Feature/User`
3. Run directly impacted Auth/Role/Admin tests available in the local checkout.
4. `php artisan route:list --name=admin.user`
5. `npm run build`
6. Manual Admin UI check for `/admin/user`, create/edit form, pagination, filters, checkbox selection, selected export and no-selection export.

Do not run full-project `php artisan test` by default; the agreed gate is User + directly impacted regressions.

## UI acceptance checklist

- Empty search/select/form controls have visible borders and white background.
- Error inputs visibly use red border/focus state.
- `10/25/50/100` page-size choices work and filter/page-size changes reset page/selection.
- Pagination inactive controls are white/light-border; active page is indigo with white text.
- Header checkbox selects only the visible page, not all matching records.
- Selected count is accurate; bulk delete says exactly how many selected accounts will be deleted.
- A user with export permission can use row checkboxes even without delete permission.
- With selected rows, export contains only selected rows that remain inside the approved filter/visibility scope.
- With no selected rows, export contains all records in the approved filter scope, not only the visible page.
- Non-Super-Admin cannot list/export/assign Super Admin accounts/role.
- Responsive layout remains usable on desktop/tablet/mobile widths.

## Explicitly deferred/quarantined

- Re-homing `App\Models\User`.
- Destructive or ownership-changing user/profile/address migrations.
- Removing API/customer/profile/address surfaces without caller proof.
- Broad global pagination/UI migration outside the User delivery boundary.

## Merge gate

Do not create/merge the consolidated PR until:

- local Pint + User + impacted regression gates pass;
- route/build gates pass;
- user reports Admin UI PASS;
- any test/UI defect is corrected on this same branch;
- this handoff is updated with exact final PASS results;
- the user reviews the single consolidated PR link and merges manually.