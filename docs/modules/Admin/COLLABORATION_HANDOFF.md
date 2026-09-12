# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Menu Snapshot Library — Named Google Drive Snapshots / Local Sync**

Status: **IMPLEMENTED ON BRANCH — AWAITING LOCAL TEST / UI PASS**

Branch: `feat/admin-menu-google-drive-snapshot`

This checkpoint keeps `/admin/menus` owned by `Modules/Admin` while consuming the existing Google Drive capability owned by `Modules/System`.

## Approved target architecture

Google Drive portable snapshot library:

`Laravel-Backup/Admin/Menu/*.json`

Examples:

- `menus-default.json`
- `menus-kho.json`
- `menus-ke-toan.json`
- `menus-pwa.json`
- legacy `menus.json` remains readable if already present.

Local working snapshot remains fixed:

`storage/app/menu/menus.json`

Flow for full export:

`AdminMenu database -> full Export -> local menus.json -> named Google Drive snapshot -> Excel download`

Flow on another machine:

`choose Google Drive snapshot -> validate -> local menus.json -> explicit restore -> AdminMenu database`

Google Drive is a portable snapshot library. The local `menus.json` remains the single working restore source. Selecting/syncing a Drive snapshot never changes the database by itself.

## Implementation completed

### System-owned Google Drive boundary

Updated:

`Modules/System/Services/Cloud/GoogleDrivePortableFileService.php`

Responsibilities now include:

- reuse `GoogleDriveConnectionService` OAuth/access-token/root-folder ownership;
- resolve/create child folders beneath the configured `Laravel-Backup` root;
- upload/update a portable file at a scoped relative path;
- download a portable file by relative path;
- list portable files in an existing scoped directory with pagination;
- return file id/name/size/mime type/modified time/path metadata;
- update an existing same-name file instead of creating duplicates;
- enforce bounded path segments and bounded download size, including a post-download content-size guard.

No Google OAuth/token ownership is duplicated inside `Modules/Admin`.

### Admin named snapshot orchestration

Updated:

`Modules/Admin/Services/MenuSnapshotCloudSyncService.php`

Contract:

- cloud directory is fixed to `Admin/Menu`;
- local path remains `storage/app/menu/menus.json`;
- human snapshot names are normalized to safe `menus-<slug>.json` file names;
- Drive listing is filtered to allowed menu snapshot JSON names only;
- full snapshot upload validates local JSON before upload;
- same-name snapshot upload updates that Drive file;
- cloud pull accepts an explicit allowed snapshot file and validates JSON before replacing local snapshot;
- local replacement uses a temporary file then move;
- invalid/empty remote JSON must not overwrite an existing valid local snapshot;
- Drive upload can remain best-effort so Excel export is not blocked by cloud failure.

### `/admin/menus` Livewire integration

Updated `Modules/Admin/Livewire/Menus/MenuTable.php`:

- adds snapshot modal state, name preview, Drive snapshot list/error state and selected local source indicator;
- full `Export Excel` with no selected rows opens the snapshot modal instead of downloading immediately;
- `Export Excel & lưu snapshot` performs the existing full export, refreshes local `menus.json`, uploads/updates the named Drive snapshot, then downloads Excel;
- selected export remains selected-only Excel and does not create/update canonical restore snapshots;
- adds snapshot library loading/refresh actions;
- `syncSnapshotFromGoogleDrive(string $snapshotFile)` pulls the chosen Drive snapshot to local only;
- restore remains a separate destructive action guarded by existing `admin.menu.restore` permission.

### Admin UI

Updated `Modules/Admin/resources/views/livewire/menus/menu-table.blade.php` according to `.codex/standards/ADMIN_UI_STANDARD.md`:

- `Công cụ > Export Excel` opens the named snapshot workflow for full export;
- added `Công cụ > Quản lý snapshot Google Drive`;
- modal contains snapshot name input and normalized Drive destination preview;
- modal lists snapshots on Google Drive with file name, size and modified time;
- each Drive snapshot exposes `Đồng bộ về local` with confirmation;
- the modal clearly distinguishes selected Drive snapshot, local working `storage/app/menu/menus.json`, and unchanged DB state;
- restore remains a separate secondary action with destructive confirmation;
- status cards now describe the Drive snapshot directory instead of a single canonical file.

## Safety / compatibility invariants

- Existing `MenuImportExportService::refreshRestoreSnapshot()` remains the canonical local snapshot writer.
- Existing restore behavior and `admin.menu.restore` authorization remain intact.
- Selected export continues to export only selected menu rows and does not refresh/push a restore snapshot.
- Full export creates/updates the user-named Drive snapshot.
- Same-name Drive snapshots are updated rather than duplicated.
- Google Drive failure must not block Excel download.
- Cloud pull must validate before replacing local snapshot.
- Remote file selection is restricted to safe `menus*.json` names under `Admin/Menu`; arbitrary paths are not accepted from UI.
- `Modules/System` remains canonical owner of Google Drive connection/token/API integration.
- No database schema or permission migration is introduced.
- No unrelated Admin/System refactor is included.

## Focused tests

Updated:

`tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php`

Covers source-level contracts for:

- System-owned Drive boundary and portable-directory listing;
- fixed `Admin/Menu` snapshot library directory;
- safe normalized `menus-<slug>.json` naming;
- selected export remaining Excel-only;
- full export opening named snapshot workflow and best-effort Drive push;
- chosen snapshot pull + validate-before-local-replace ordering;
- snapshot listing restricted to menu JSON files;
- modal/library/specific-sync UI contracts.

Existing focused Menu contract remains:

`tests/Feature/Admin/MenuLivewireRefactorContractTest.php`

## Required local verification

Run only the affected gates first:

```bash
php artisan test tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php
php artisan test tests/Feature/Admin/MenuLivewireRefactorContractTest.php

./vendor/bin/pint \
Modules/Admin/Livewire/Menus/MenuTable.php \
Modules/Admin/Services/MenuSnapshotCloudSyncService.php \
Modules/System/Services/Cloud/GoogleDrivePortableFileService.php \
tests/Feature/Admin/MenuSnapshotGoogleDriveContractTest.php
```

Then UI/runtime smoke on `/admin/menus`:

1. confirm Google Drive status points to `Laravel-Backup/Admin/Menu/*.json`;
2. with no menu selected, click `Export Excel` and confirm the snapshot modal opens;
3. enter `Menu Kho`, verify preview becomes `menus-menu-kho.json` with the current normalization rule;
4. click `Export Excel & lưu snapshot`, verify Excel downloads, local `storage/app/menu/menus.json` refreshes and Drive receives the named snapshot;
5. export again with the same name and confirm the existing Drive file is updated rather than duplicated;
6. export another name and confirm both snapshots appear in the modal library;
7. select menu rows and run `Export đã chọn`; confirm it downloads selected Excel only and does not create/update a Drive snapshot;
8. choose any Drive snapshot and click `Đồng bộ về local`; confirm `storage/app/menu/menus.json` changes to that validated snapshot while DB/menu tree remains unchanged;
9. click `Khôi phục snapshot` only after confirming the local snapshot, then verify the menu tree is replaced from local;
10. confirm invalid/missing Drive snapshot data never overwrites a valid local file;
11. confirm Drive/API failure still allows full Excel export and surfaces only a warning.

## Known prior Admin regression baseline

The previous Menu workspace checkpoint recorded Admin regression as **213 passed / 3 failed / 1868 assertions**, with the three failures attributed to unrelated Website/Auth ownership-contract drift. Do not opportunistically fix those failures in this branch unless a new local run proves a direct regression from this snapshot slice.

## Next checkpoint

User pulls `feat/admin-menu-google-drive-snapshot`, runs both focused tests, Pint and `/admin/menus` runtime smoke above, then returns exact CLI/UI results. Fix only failures attributable to this branch, then complete UI PASS and prepare one consolidated PR according to `docs/GITHUB_COLLABORATION_WORKFLOW.md`.
