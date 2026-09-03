# User — Collaboration Handoff

## Current objective

Deliver explicit one-time Google auto-link approval from User administration and close the `/admin/user` list UX with status filtering plus active-first ordering.

## Branch

`feat/user-google-auto-link-approval`

## Merged baseline

- PR #148 completed the major User refactor.
- PR #160 completed trusted User import/export credential round-trip support.
- User remains the shared account-directory shell.
- Auth remains the canonical owner of Google authentication/linking behavior.
- Role remains the canonical role/permission catalog owner.

## Delivered contract

### One-time Google auto-link approval

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
- Approval is persisted immediately from the edit UI through a dedicated service action instead of depending on the general staff save transaction.
- Approval is cleared if the account email changes, the account is inactive, or a Google identity is already linked.

### `/admin/user` status filtering and ordering

- Adds a `Trạng thái` filter with:
  - `Tất cả trạng thái`
  - `Đang hoạt động`
  - `Ngừng hoạt động`
- Filter changes reset pagination and current row selection.
- `Xóa bộ lọc` resets status to all.
- Export receives the same `status` filter so no-selection export remains the full approved/filter scope rather than only the current page.
- Default ordering is active-first, then newest User first inside each status group.

## Key implementation

- `Modules/User/database/migrations/2026_09_03_113500_add_google_auto_link_enabled_to_users_table.php`
  - adds `users.google_auto_link_enabled` boolean, default `false`.
- `app/Models/User.php`
  - exposes/casts approval state as boolean.
- `Modules/User/Services/UserService.php`
  - owns account-directory query scope;
  - provides dedicated `setGoogleAutoLinkApproval()` persistence;
  - uses admin-guard Role models for role sync;
  - preserves/clears approval according to email/status/link safety rules;
  - supports status filter and active-first ordering.
- `Modules/User/Livewire/UserForm.php` and `user-form.blade.php`
  - show Google-link status;
  - persist approval immediately on checkbox change;
  - keep canonical Admin form styling.
- `Modules/User/Livewire/UserTable.php` and `user-table.blade.php`
  - add status filter;
  - propagate status to list/select/export scopes;
  - preserve standard pagination/input UI.
- `Modules/Auth/Services/GoogleIdentityService.php`
  - preserves collision, active/deleted-account and verified-Google-email checks;
  - permits approved existing matching email to bypass only the prior local OTP prerequisite;
  - consumes the approval after successful link.
- Tests cover approved/unapproved Google flow, approval persistence and invalidation, status filter/export behavior, active-first ordering, credential round-trip, and User ownership contracts.

## Validation checkpoint

Accepted local validation on 2026-09-03:

- focused User contract suite: **11 passed (44 assertions)**;
- Vite production build: **PASS — 34 modules transformed**;
- working tree: **clean** before final handoff update;
- Google auto-link approval edit UI: **PASS**;
- `/admin/user` status filter and active-first ordering UI: **PASS**.

Earlier combined focused Auth/User validation on this branch also passed before the final status-filter addition, including Google approved/unapproved callback coverage. The final status-filter change is isolated to User list/query/filter behavior.

## Security notes

- Approval is one-time and consumed after successful linking.
- It does not weaken Google email verification.
- It does not allow cross-email linking.
- It does not allow takeover of a Google ID already owned by another User.
- It does not reactivate deleted or inactive accounts.
- It does not manufacture OTP verification history.
- User administration never directly writes/replaces `google_id`.
- Role catalog ownership remains in Role; User only syncs existing admin-guard Role models.

## Explicitly out of scope

- Automatic linking for every matching email globally.
- Permanent always-on Google auto-link permission.
- Unlinking/replacing an existing Google identity.
- Broad Auth architecture changes.
- Role or permission catalog changes.
- Destructive schema changes.

## Merge gate

**READY FOR PR.** Focused tests and UI acceptance are PASS. Open one PR from `feat/user-google-auto-link-approval` to `main` for user review and manual merge.
