# User — Collaboration Handoff

## Current objective

Follow-up hardening for `Modules/User` import/export after the major User refactor was merged in PR #148.

The user reported that an exported User file did not provide a reliable full restore path for locked status and credentials. The approved follow-up is a trusted backup/restore contract that preserves account status and password hashes without exposing plaintext passwords or double-hashing restored credentials.

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

- Normal User export must **not** expose plaintext passwords.
- `is_active` is exported as explicit `1/0` so locked accounts are unambiguous in Excel/CSV and can round-trip through import.
- A Super Admin with `export_user` may opt into **credential backup**; only then does export add `password_hash`.
- A non-Super-Admin cannot enable credential-hash export even by tampering with Livewire/filter payloads.
- Import continues to accept plaintext `password`; plaintext is hashed with `Hash::make()`.
- Import additionally accepts `password_hash` only from a Super Admin and persists that trusted hash unchanged.
- `password` and `password_hash` cannot be supplied together in one row.
- Supported backup hashes are restricted to bcrypt/Argon-style hashes.
- If neither credential field is supplied, existing import behavior remains unchanged to avoid an unrelated contract break.
- Role catalog and selected/all export semantics remain unchanged.

## Implementation checkpoint

Implemented on `fix/user-import-export-roundtrip`:

- `Modules/User/Services/ImportExport.php`
  - adds `password_hash` import alias/rule;
  - exports `is_active` as `1` or `0`;
  - adds Super-Admin-only `include_password_hash` export mode;
  - exports the raw stored credential hash only in that trusted mode;
  - imports trusted `password_hash` without applying `Hash::make()` again;
  - rejects simultaneous plaintext password + password hash;
  - rejects credential-hash import/export for non-Super-Admin actors;
  - validates supported bcrypt/Argon hash prefixes.
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
  - retains existing User refactor/export ownership contracts.

## Validation gate — pending local execution

Run on this branch after pulling it locally:

1. `./vendor/bin/pint --test Modules/User tests/Feature/User`
2. `php artisan test tests/Feature/User`
3. `php artisan route:list --name=admin.user`
4. `npm run build`
5. `git diff --check`
6. Manual UI check at `/admin/user` using a Super Admin account:
   - backup checkbox is visible only to Super Admin with `export_user`;
   - normal export does not contain `password` or `password_hash`;
   - backup export contains `password_hash`;
   - locked user exports `is_active = 0` rather than an empty cell;
   - importing that backup restores the locked state and preserves the working password;
   - selected/no-selection export semantics remain correct.

Do not run full-project `php artisan test` by default. This follow-up changes only the User import/export boundary and its Admin UI integration.

## Security notes

- `password_hash` is sensitive credential material even though it is not plaintext.
- Backup export is intentionally opt-in and Super-Admin-only.
- The normal export path remains credential-free.
- Service-side authorization is mandatory; the UI visibility rule is only an additional usability guard.

## Explicitly out of scope

- Plaintext password export.
- Re-homing `App\Models\User`.
- Destructive schema/migration changes.
- Changing Role/Auth/Shared ownership boundaries.
- Broad Admin import/export framework changes.
- Unrelated Website/Auth/Admin ownership-contract debt previously identified during PR #148 validation.

## Merge gate

Do not open/merge the follow-up PR until:

- focused Pint PASS;
- User focused tests PASS;
- route/build/diff checks PASS;
- user reports UI PASS for the backup/restore flow;
- this handoff is updated with exact final validation results;
- the user reviews the PR link and merges manually.
