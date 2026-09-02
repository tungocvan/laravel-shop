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

Implementation and agreed validation gates are complete. The branch is ready for the consolidated PR. No destructive migration/model ownership change was introduced.

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
- focused `UserRefactorContractTest` added for contract/UI/export/role-ownership behavior;
- Laravel Pint formatting normalized across the in-scope User module/test boundary.

## Files in the delivery boundary

- `Modules/User/Livewire/UserTable.php`
- `Modules/User/Services/UserService.php`
- `Modules/User/Services/ImportExport.php`
- `Modules/User/resources/views/livewire/user-table.blade.php`
- `Modules/User/resources/views/livewire/user-form.blade.php`
- `Modules/User/resources/views/pages/staff/index.blade.php`
- `Modules/User/resources/views/vendor/pagination/admin-users.blade.php`
- `Modules/User/Http/Controllers/Api/UsersController.php` (Pint-only normalization)
- `Modules/User/database/migrations/-0001_11_30_000006_create_users_table.php` (Pint-only normalization)
- `Modules/User/database/migrations/-0001_11_30_000007_create_password_reset_tokens_table.php` (Pint-only normalization)
- `Modules/User/database/migrations/-0001_11_30_000009_create_user_addresses_table.php` (Pint-only normalization)
- `Modules/User/database/migrations/2026_04_27_214255_add_profile_and_social_fields_to_users_table.php` (Pint-only normalization)
- `Modules/User/routes/api.php` (Pint-only normalization)
- `Modules/User/routes/web.php` (Pint-only normalization)
- `tests/Feature/User/UserRefactorContractTest.php`
- `docs/modules/User/MODULE.md`
- `docs/modules/User/COLLABORATION_HANDOFF.md`

## Final validation — PASS

User-reported local validation on 2026-09-02:

- `./vendor/bin/pint --test Modules/User tests/Feature/User` → PASS after one formatting normalization commit.
- `php artisan test tests/Feature/User` → **16 passed, 63 assertions**.
- `php artisan route:list --name=admin.user` → PASS; exactly 3 expected Admin User routes:
  - `admin.user.index`
  - `admin.user.create`
  - `admin.user.edit`
- `npm run build` → PASS; Vite built successfully.
- `git diff --check` → PASS as part of the final clean validation sequence.
- Manual Admin UI acceptance → **UI PASS**.
- Final working tree after local formatting commit → clean.

### Impacted Admin regression note

The broader Admin regression run completed with **253 passed and 3 failed (2032 assertions total)**. The three failures are outside the User delivery boundary and are recorded as unrelated/pre-existing ownership-contract debt rather than User regressions:

- `AdminAffiliateOwnershipContractTest` expects `Modules/Website/Services/AdminAffiliateService.php`, which is absent.
- `AdminAffiliateOwnershipContractTest` expects a legacy compatibility class to extend `Modules\Website\Services\AdminAffiliateService`.
- `AdminWebsitePresentationOwnershipContractTest` expects `Modules\Auth\Services\AuthService` usage in the Google controller.

No files in the User refactor delivery touched those Website/Auth/Admin ownership surfaces. These failures must not be opportunistically repaired in the User PR.

## UI acceptance checklist — PASS

Confirmed by the user:

- Empty search/select/form controls have visible borders and white background.
- Error inputs use the normalized Admin form treatment.
- `10/25/50/100` pagination choices and current Admin pagination styling are accepted.
- Header checkbox uses visible-page selection semantics.
- Export with selected rows exports the selected scope only.
- Export with no selected rows exports the full approved/filter scope, not the current page only.
- User create/edit Admin UI is accepted.
- Responsive User Admin presentation is accepted.

## Explicitly deferred/quarantined

- Re-homing `App\Models\User`.
- Destructive or ownership-changing user/profile/address migrations.
- Removing API/customer/profile/address surfaces without caller proof.
- Broad global pagination/UI migration outside the User delivery boundary.
- Unrelated Website/Auth/Admin ownership-contract debt identified by the impacted Admin regression run.

## Merge gate

All agreed User refactor gates are satisfied:

- focused Pint PASS;
- User tests PASS (`16 passed`, `63 assertions`);
- User route contract PASS;
- build PASS;
- manual UI PASS;
- branch is clean and pushed;
- branch comparison against `main` before handoff closeout showed `behind_by: 0`;
- this handoff records exact final validation results.

Next action: create one consolidated PR from `refactor/user-module-contract-ui-export` to `main`, provide the PR link for user review, and let the user merge manually after review.