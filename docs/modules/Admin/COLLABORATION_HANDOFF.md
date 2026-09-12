# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Menu Snapshot Library — Named Google Drive Snapshots / Local Sync**

Status: **IMPLEMENTED ON BRANCH — FOCUSED RETEST REQUIRED**

Branch: `feat/admin-menu-google-drive-snapshot`

## Final approved semantics

The menu export and restore responsibilities are deliberately separated:

- `Export Excel` only creates/downloads Excel. It must not write `storage/app/menu/menus.json` and must not upload Google Drive snapshots.
- `Quản lý snapshot Google Drive` creates named JSON snapshots directly from the current `AdminMenu` database state and uploads them to `Laravel-Backup/Admin/Menu/*.json`.
- Snapshot creation supports either all menus or the currently selected menu IDs.
- `Đồng bộ về local` is the only cloud workflow that replaces `storage/app/menu/menus.json`; remote JSON is validated before replacement.
- `Khôi phục snapshot` reads `storage/app/menu/menus.json` and explicitly replaces the current menu database tree.

Therefore the working local snapshot is never changed as a side effect of Excel export.

## Google Drive library

Examples:

- `Laravel-Backup/Admin/Menu/menus-default.json`
- `Laravel-Backup/Admin/Menu/menus-kho.json`
- `Laravel-Backup/Admin/Menu/menus-ke-toan.json`
- legacy `menus.json` remains readable when present.

The library supports listing, selecting, syncing to local, renaming, and deleting selected Drive snapshot files. Same-name uploads update the existing file rather than intentionally creating a new duplicate.

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

## Focused verification

Run:

```bash
php artisan test tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php
php artisan test tests/Feature/Admin/MenuLivewireRefactorContractTest.php

./vendor/bin/pint \
Modules/Admin/Livewire/Menus/MenuTable.php \
Modules/Admin/Services/MenuImportExportService.php \
Modules/Admin/Services/MenuSnapshotCloudSyncService.php \
Modules/System/Services/Cloud/GoogleDrivePortableFileService.php \
tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php \
tests/Feature/Admin/MenuLivewireRefactorContractTest.php
```

Runtime smoke on `/admin/menus`:

1. Note timestamp/hash of `storage/app/menu/menus.json`.
2. Run `Export Excel` with no selection and verify Excel downloads while local `menus.json` remains unchanged.
3. Select several menu rows, run `Export đã chọn`, verify selected-only Excel and local `menus.json` remains unchanged.
4. Open `Quản lý snapshot Google Drive`, create an all-menu snapshot and verify Drive JSON is created without changing local `menus.json`.
5. Create a selected-menu snapshot and verify Drive JSON contains only the selected menu tree.
6. Rename a Drive snapshot and verify the library refreshes with the new name.
7. Select one or more Drive snapshots and delete them.
8. Choose a Drive snapshot and `Đồng bộ về local`; only now should `storage/app/menu/menus.json` change while DB remains unchanged.
9. Use `Khôi phục snapshot` only after confirming the local snapshot; verify DB is then replaced from local.

## Prior verified baseline

Before the final Excel/snapshot semantics split, user reported:

- `MenuSnapshotGoogleDriveContractTest`: 5 passed / 30 assertions.
- `MenuLivewireRefactorContractTest`: 8 passed / 56 assertions.
- UI snapshot library: PASS.

The final semantics change requires rerunning the focused gates above.

Known prior Admin regression baseline remains **213 passed / 3 failed / 1868 assertions**, with those failures attributed to unrelated Website/Auth ownership-contract drift. Do not expand this branch into those unrelated failures.
