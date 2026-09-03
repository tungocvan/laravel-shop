# User — Collaboration Handoff

## Current objective

Follow-up hardening for `Modules/User` import/export after the major User refactor was merged in PR #148.

The follow-up establishes a trusted backup/restore contract that preserves account status and password hashes without exposing plaintext passwords or double-hashing restored credentials.

## Branch

`fix/user-import-export-roundtrip`

## Merged baseline

PR #148 (`refactor/user-module-contract-ui-export`) is merged into `main` and remains the canonical User refactor baseline.

Baseline invariants remain unchanged:

- User is the shared account-directory shell.
- Role owns the role/permission catalog.
- Auth owns authentication flows.
- Shared owns generic import/export infrastructure.
- Selected IDs present → export selected approved rows only.
- No selected IDs → export all rows in the approved/filter scope, never current page only.
- User list pagination remains bounded to `10/25/50/100`.
- Non-Super-Admin visibility and Super Admin safeguards remain enforced.

## Approved follow-up contract

- Normal User export does **not** expose plaintext passwords.
- `is_active` is exported as explicit `1/0` so locked accounts are unambiguous and round-trip safely.
- A Super Admin with `export_user` may opt into credential backup; only then does export add `password_hash`.
- A non-Super-Admin cannot enable credential-hash export by tampering with the payload.
- Plaintext `password` import is hashed exactly once.
- `password_hash` import is Super-Admin-only and restores the trusted hash unchanged.
- `password` and `password_hash` cannot be supplied together.
- Supported backup hashes are restricted to bcrypt/Argon-style hashes.
- If neither credential field is supplied, existing import behavior remains unchanged.
- User import never creates missing roles. Role remains the canonical role/permission catalog owner.
- Role synchronization resolves existing roles from the `admin` guard before assigning them to imported users.

## Implementation closeout

Implemented on `fix/user-import-export-roundtrip`:

- `Modules/User/Services/ImportExport.php`
  - adds `password_hash` import alias/rule;
  - exports `is_active` as `1` or `0`;
  - adds Super-Admin-only `include_password_hash` export mode;
  - only hydrates the password column for trusted credential-backup export;
  - exports the raw stored credential hash only in trusted mode;
  - imports trusted `password_hash` without double hashing;
  - rejects simultaneous plaintext password + password hash;
  - rejects credential-hash import/export for non-Super-Admin actors;
  - validates supported bcrypt/Argon hash prefixes;
  - synchronizes imported roles using existing `admin`-guard Role models rather than resolving role names through the model's default `web` guard.
- `Modules/User/Livewire/UserTable.php`
  - adds reactive `includePasswordHash` backup option;
  - passes the backup flag through the existing export filter contract;
  - resets the option with filters;
  - exposes the option only for a Super Admin with `export_user`, while service-side authorization remains canonical.
- `Modules/User/resources/views/livewire/user-table.blade.php`
  - adds a warning-styled Super-Admin-only checkbox explaining that credential backup contains sensitive password hashes and must not be shared.
- `tests/Feature/User/UserRefactorContractTest.php`
  - covers locked-state + password-hash backup export;
  - covers password-hash restore without double hashing;
  - covers non-Super-Admin denial;
  - covers the canonical admin-guard role synchronization contract;
  - retains existing User refactor/export ownership contracts.

## Validation closeout

Local validation reported during this follow-up:

- User focused tests: **19 passed, 72 assertions** after the credential export and admin-guard role fixes.
- User routes remain present:
  - `GET admin/user` → `admin.user.index`
  - `GET admin/user/create` → `admin.user.create`
  - `GET admin/user/{id}/edit` → `admin.user.edit`
- Vite production build: **PASS**.
- Manual Excel round-trip: **PASS** after importing the User template in the correct User import panel.
- Manual UI acceptance: **UI PASS**.
- The earlier `storage/app/exports` write failure was an environment filesystem-permission issue, not a User import/export contract defect.
- A later “File thiếu cột bắt buộc” report was confirmed to come from importing the User workbook in the wrong module/import location; no cross-module state defect was reproduced.

Focused Pint initially reported formatting-only issues in `UserRefactorContractTest.php`; the branch contains the follow-up formatting correction. Final merge review should confirm focused Pint remains clean after pulling the latest branch head.

Do not run full-project `php artisan test` by default. This follow-up changes only the User import/export boundary and its Admin UI integration.

## Security notes

- `password_hash` is sensitive credential material even though it is not plaintext.
- Backup export is intentionally opt-in and Super-Admin-only.
- The normal export path remains credential-free.
- Service-side authorization is mandatory; UI visibility is only an additional usability guard.
- Missing roles are rejected instead of being created from spreadsheet input.

## Explicitly out of scope

- Plaintext password export.
- Re-homing `App\Models\User`.
- Destructive schema/migration changes.
- Changing Role/Auth/Shared ownership boundaries.
- Broad Admin import/export framework changes.
- Unrelated Website/Auth/Admin ownership-contract debt previously identified during PR #148 validation.

## Merge gate

Implementation and UI acceptance are complete. Before merge, confirm the latest branch head with focused Pint/User tests if needed, review the PR diff, and merge manually after approval.
