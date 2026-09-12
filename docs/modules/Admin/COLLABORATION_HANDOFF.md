# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Menu Snapshot — Google Drive Portable Backup / Local Sync**

Status: **IMPLEMENTED ON BRANCH — AWAITING LOCAL TEST / UI PASS**

Branch: `feat/admin-menu-google-drive-snapshot`

This checkpoint keeps `/admin/menus` owned by `Modules/Admin` while consuming the existing Google Drive capability owned by `Modules/System`.

## Approved target architecture

Canonical portable snapshot:

`Laravel-Backup/Admin/Menu/menus.json`

Local working snapshot:

`storage/app/menu/menus.json`

Flow:

`AdminMenu database -> full Export -> local menus.json -> Google Drive menus.json`

and, on another machine:

`Google Drive menus.json -> explicit sync -> validated local menus.json -> restore -> AdminMenu database`

Google Drive is the portable cross-machine copy. Restore remains local-first and continues to use the existing destructive `replace` import boundary only after the snapshot is present locally.

## Implementation completed

### System-owned Google Drive boundary

Added:

`Modules/System/Services/Cloud/GoogleDrivePortableFileService.php`

Responsibilities:

- reuse `GoogleDriveConnectionService` OAuth/access-token/root-folder ownership;
- resolve/create child folders beneath the configured `Laravel-Backup` root;
- upload/update a portable file at a scoped relative path;
- download a portable file by relative path;
- update an existing same-name file instead of creating a new menu snapshot on each export;
- enforce bounded path segments and bounded download size.

No Google OAuth/token ownership was duplicated inside `Modules/Admin`.

### Admin menu snapshot orchestration

Added:

`Modules/Admin/Services/MenuSnapshotCloudSyncService.php`

Contract:

- cloud path is fixed to `Admin/Menu/menus.json`;
- local path remains `storage/app/menu/menus.json`;
- full snapshot upload validates local JSON before Google Drive upload;
- export cloud sync is best-effort so Drive failure does not block Excel download;
- cloud download validates JSON before replacing local snapshot;
- local replacement uses a temporary file then move;
- invalid/empty remote JSON must not overwrite the existing local snapshot;
- status exposes local existence/mtime and Google Drive connection/path for the Admin UI.

### `/admin/menus` Livewire integration

Updated `Modules/Admin/Livewire/Menus/MenuTable.php`:

- injects `MenuSnapshotCloudSyncService`;
- full export (`selectedMenus === []`) keeps the existing local snapshot refresh and then best-effort pushes it to Google Drive;
- selected export remains selected-only and does not refresh/push the canonical snapshot;
- Drive upload failure reports a warning but does not fail the Excel export;
- adds `syncSnapshotFromGoogleDrive()` guarded by existing `admin.menu.restore` permission;
- render payload includes snapshot status.

### Admin UI

Updated `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php` according to `.codex/standards/ADMIN_UI_STANDARD.md`:

- snapshot operations remain secondary inside the existing `Công cụ` menu;
- added `Đồng bộ snapshot từ Drive` with loading state and overwrite confirmation;
- restore confirmation now explicitly states that the local snapshot replaces the current menu structure;
- added compact status cards for local snapshot, Google Drive connection/path and local modified time;
- no new competing form/control pattern introduced.

## Safety / compatibility invariants

- Existing `MenuImportExportService::refreshRestoreSnapshot()` remains the canonical local snapshot writer.
- Existing restore behavior and `admin.menu.restore` authorization remain intact.
- Existing selected-versus-full export semantics remain intact.
- Google Drive failure must not block Excel export.
- Cloud pull must validate before replacing local snapshot.
- `Modules/System` remains canonical owner of Google Drive connection/token/API integration.
- No database schema or permission migration is introduced.
- No unrelated Admin/System refactor is included.

## Focused test added

`tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php`

Covers the source-level contract for:

- System-owned Drive boundary;
- fixed `Admin/Menu/menus.json` portable path;
- full export best-effort cloud push versus selected export;
- validate-before-local-replace ordering;
- atomic local replacement;
- UI sync/status/restore controls and authorization reuse.

Existing focused Menu contract remains:

`tests/Feature/Admin/MenuLivewireRefactorContractTest.php`

## Required local verification

Run only the affected gates first:

```bash
php artisan test tests/Feature/Admin/MenuLivewireRefactorContractTest.php
php artisan test tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php
./vendor/bin/pint Modules/Admin/Livewire/Menus/MenuTable.php Modules/Admin/Services/MenuSnapshotCloudSyncService.php Modules/System/Services/Cloud/GoogleDrivePortableFileService.php tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php
```

Then UI/runtime smoke on `/admin/menus`:

1. confirm Google Drive shows connected and path `Laravel-Backup/Admin/Menu/menus.json`;
2. perform full Export with no selected rows;
3. verify local `storage/app/menu/menus.json` is refreshed;
4. verify Drive contains exactly the canonical `Admin/Menu/menus.json` file and subsequent full export updates it rather than creating duplicates;
5. select rows and run selected export; confirm canonical snapshot is not changed by selected export;
6. temporarily remove/rename local `menus.json`, click `Đồng bộ snapshot từ Drive`, confirm local file is recreated;
7. confirm invalid/missing Drive data does not overwrite a valid local snapshot;
8. run `Khôi phục snapshot` only after confirming the synced local snapshot, then verify menu tree is restored;
9. confirm Drive/API failure still allows Excel export and surfaces only a warning.

## Known prior Admin regression baseline

The previous Menu workspace checkpoint recorded Admin regression as **213 passed / 3 failed / 1868 assertions**, with the three failures attributed to unrelated Website/Auth ownership-contract drift. Do not opportunistically fix those failures in this branch unless the new local run proves a direct regression from this snapshot slice.

## Next checkpoint

User pulls `feat/admin-menu-google-drive-snapshot`, runs the focused tests and `/admin/menus` runtime smoke above, then returns the exact CLI/UI results. Fix only failures attributable to this branch, then complete UI PASS and prepare one consolidated PR according to `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
