# User — Collaboration Handoff

## Current objective

Add an explicit one-time Google auto-link approval to the User edit flow so an existing account may be linked to a verified Google identity with the same email without requiring a prior password/OTP verification path.

## Branch

`feat/user-google-auto-link-approval`

## Merged baseline

- PR #148 completed the major User refactor.
- PR #160 completed trusted User import/export credential round-trip support.
- User remains the shared account-directory shell.
- Auth remains the canonical owner of Google authentication/linking behavior.
- Role remains the canonical role/permission catalog owner.

## Approved contract

- `/admin/user/{id}/edit` exposes Google-link status.
- An unlinked existing account may be granted **one-time** Google auto-link approval.
- The approval is account-level state owned by User administration.
- `Modules/Auth` is the only boundary allowed to consume the approval and persist `google_id`.
- Google must still report a verified email.
- Google email must exactly match the existing User email after normalization.
- The User must be active and not soft-deleted.
- The Google ID must not already belong to another User.
- A User already linked to a different Google ID remains blocked.
- Without explicit approval, the previous OTP/password-linking safeguards remain unchanged.
- On successful approved auto-link, Auth sets `google_id`, ensures `email_verified_at`, and automatically clears the approval flag.
- No fake OTP record is created and User administration never writes `google_id` directly.

## Implementation checkpoint

Implemented on `feat/user-google-auto-link-approval`:

- `Modules/User/database/migrations/2026_09_03_113500_add_google_auto_link_enabled_to_users_table.php`
  - adds `users.google_auto_link_enabled` boolean, default `false`.
- `app/Models/User.php`
  - exposes/casts the approval state as boolean.
- `Modules/User/Services/UserService.php`
  - loads Google link state for the edit form;
  - persists approval only for existing, currently unlinked users;
  - forces approval off when the account is already linked or is newly created.
- `Modules/User/Livewire/UserForm.php`
  - hydrates `googleLinked` and `googleAutoLinkEnabled`;
  - saves the one-time approval through `UserService`.
- `Modules/User/resources/views/livewire/user-form.blade.php`
  - adds a canonical Admin UI section named **Liên kết Google**;
  - shows `Đã liên kết Google` / `Chưa liên kết Google`;
  - provides the checkbox **Cho phép Google tự động liên kết ở lần đăng nhập tiếp theo** only when the account is not linked;
  - explains the one-time and safety semantics.
- `Modules/Auth/Services/GoogleIdentityService.php`
  - preserves all existing collision, active-account, deleted-account and verified-Google-email checks;
  - permits an existing matching email to bypass the prior OTP requirement only when `google_auto_link_enabled = true`;
  - clears the flag after successful link;
  - ensures `email_verified_at` on successful approved link;
  - preserves the previous OTP/password linking path when approval is false.
- `tests/Feature/Auth/AdminGoogleAuthenticationTest.php`
  - covers successful approved auto-link;
  - confirms the flag is consumed/cleared;
  - confirms an unapproved existing email remains blocked;
  - retains existing unknown-account and soft-delete safeguards.

## Validation gate — pending local execution

Run after pulling this branch locally:

1. `php artisan migrate`
2. `./vendor/bin/pint --test Modules/User Modules/Auth tests/Feature/User tests/Feature/Auth`
3. `php artisan test tests/Feature/User tests/Feature/Auth`
4. `php artisan route:list --name=admin.user`
5. `npm run build`
6. `git diff --check`
7. Manual UI flow:
   - open `/admin/user/{id}/edit` for an existing account with no `google_id`;
   - confirm Google section and status are visible;
   - enable one-time auto-link and save;
   - login with Google using the exact same verified email;
   - confirm login succeeds, `google_id` is linked, and returning to User edit shows `Đã liên kết Google`;
   - confirm the approval checkbox no longer applies after successful link;
   - verify an inactive account remains blocked;
   - verify a mismatched Google email remains blocked.

Do not open a PR until focused tests, migration, build and UI acceptance PASS.

## Security notes

- The approval is one-time and consumed after successful linking.
- It does not weaken Google email verification.
- It does not allow cross-email linking.
- It does not allow takeover of a Google ID already owned by another User.
- It does not reactivate deleted or inactive accounts.
- It does not manufacture OTP verification history.

## Explicitly out of scope

- Automatic linking for every matching email globally.
- Permanent always-on Google auto-link permission.
- User module directly writing or replacing `google_id`.
- Unlinking/replacing an existing Google identity.
- Broad Auth architecture changes.
- Role or permission catalog changes.

## Merge gate

Pending local focused validation and explicit UI PASS from the user. After that, update this handoff with exact results and open one PR for review/manual merge.
