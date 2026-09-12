# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Menu Snapshot Library — Named Google Drive Snapshots / Local Sync**

Status: **ACCEPTED — FOCUSED TESTS PASS / UI PASS / READY FOR PR**

Branch: `feat/admin-menu-google-drive-snapshot`

Latest local formatting commit: `8bfaaabc` (`style(admin): apply Pint to menu snapshot workflow`).

## Final approved semantics

The menu export and restore responsibilities are deliberately separated:

- `Export Excel` only creates/downloads Excel. It does not write `storage/app/menu/menus.json` and does not upload Google Drive snapshots.
- `Quản lý snapshot Google Drive` creates named JSON snapshots directly from the current `AdminMenu` database state and uploads them to `Laravel-Backup/Admin/Menu/*.json`.
- Snapshot creation supports either all menus or the currently selected menu IDs.
- `Đồng bộ về local` is the only cloud workflow that replaces `storage/app/menu/menus.json`; remote JSON is validated before replacement.
- `Khôi phục snapshot` reads `storage/app/menu/menus.json` and explicitly replaces the current menu database tree.

Therefore the working local snapshot is never changed as a side effect of Excel export or Drive snapshot creation.

## Google Drive library

Examples:

- `Laravel-Backup/Admin/Menu/menus-default.json`
- `Laravel-Backup/Admin/Menu/menus-kho.json`
- `Laravel-Backup/Admin/Menu/menus-ke-toan.json`
- legacy `menus.json` remains readable when present.

The library supports listing, selecting, syncing to local, renaming, and deleting selected Drive snapshot files. Same-name uploads update the existing file rather than intentionally creating a duplicate.

## Ownership boundary

`Modules/System` remains owner of Google Drive OAuth, token refresh, root-folder resolution and generic portable-file operations through `GoogleDrivePortableFileService`.

`Modules/Admin` owns menu snapshot semantics through `MenuSnapshotCloudSyncService` and consumes the System capability. No second Google Drive connection is introduced in Admin.

## Safety invariants

- Excel export is side-effect free with respect to local/Drive snapshots.
- Snapshot creation does not write the local restore file.
- Drive-to-local sync does not alter the database.
- Restore is a separate explicit destructive action guarded by `admin.menu.restore`.
- Invalid remote JSON must not overwrite a valid local snapshot.
- Snapshot file selection is restricted to safe `menus*.json` files under `Admin/Menu`.
- Selected Excel export remains selected-only.
- Selected Drive snapshot creation remains selected-only.
- No database schema migration is introduced.
- `storage/app/menu/menus.json` is runtime/local working state and must not be committed.

## Verification completed

Focused contract tests reported by the user after the final semantics split:

- `tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php`: **5 passed / 43 assertions**.
- `tests/Feature/Admin/MenuLivewireRefactorContractTest.php`: **8 passed / 56 assertions**.
- Pint: completed; local style fixes committed in `8bfaaabc`.
- Manual UI smoke on `/admin/menus`: **PASS**.

No GitHub Actions workflow run was attached to `8bfaaabc`; local focused verification is the acceptance evidence for this branch.

## Runtime acceptance scope

Verified workflow:

1. `Export Excel` downloads Excel without mutating the local restore snapshot.
2. Selected export remains selected-only.
3. Drive snapshot creation supports all-menu and selected-menu scope.
4. Drive snapshot creation does not mutate `storage/app/menu/menus.json`.
5. Snapshot library supports rename/delete and same-name update behavior.
6. `Đồng bộ về local` writes the validated Drive snapshot to `storage/app/menu/menus.json` without changing DB.
7. `Khôi phục snapshot` remains the explicit local-to-database restore step.

## Regression strategy

Full-project regression: **NOT APPLICABLE — module-scoped regression strategy**.

Reason: the change is limited to Admin Menu snapshot/export behavior plus the explicit generic System Google Drive portable-file boundary. It does not change schema, framework bootstrap, shared auth/security policy, or project-wide persistence.

Known prior Admin regression baseline remains **213 passed / 3 failed / 1868 assertions**, with those failures attributed to unrelated Website/Auth ownership-contract drift. Do not expand this branch into those unrelated failures.

## PR / merge note

Before merge, confirm the PR head includes this handoff closeout and `8bfaaabc`. After merge, local `storage/app/menu/menus.json` may remain untracked because it is runtime working state, not repository source.
