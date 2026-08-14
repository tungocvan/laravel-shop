# Ebook Module Information

## 1. Current Status

- Module: `Ebook`
- Type: `domain`
- Bootstrap: `Modules\ModuleServiceProvider.php`
- Runtime state: repository module-state infrastructure
- Development target: Local VPS
- Phase: Phase 1 MVP implementation complete through MR-6; MR-7 final validation in progress
- Last completed Ebook regression: `34 tests / 102 assertions / 0 failed`
- Manual Scan & Sync smoke: PASS

## 2. Architecture Summary

```text
Route
  -> Controller
  -> Page Blade shell
  -> Livewire component
  -> Service
  -> Model / Laravel Storage
  -> MySQL / storage/app/ebooks/
```

Filesystem is authoritative for Markdown content.

Database is authoritative for managed metadata and Admin interaction state.

## 3. Filesystem Root

```text
storage/app/ebooks/
```

The root is private. Markdown and image assets are not exposed through a public storage symlink as an Ebook API.

Relative image assets are resolved through a controlled Admin Ebook asset route with path-boundary validation.

## 4. Database Tables

```text
ebook_folders
ebook_documents
ebook_document_recents
```

### Metadata ownership

`ebook_folders` owns hierarchy metadata.

`ebook_documents` owns document metadata, canonical file path and sync/hash hints.

`ebook_document_recents` owns per-user recent view entries.

## 5. Admin Identity

`auth:admin` uses the existing `users` provider.

Canonical identity model:

```text
App\Models\User
```

Recently Viewed references the existing `users.id` key.

## 6. Permissions

```text
ebook.view
ebook.create
ebook.update
ebook.delete
ebook.upload
ebook.sync
```

Mutation authorization is checked server-side in Livewire actions in addition to route/page access controls.

## 7. Implemented Capabilities

### Folder

- create/update/delete guard
- nesting
- move/sort
- active state
- duplicate sibling protection
- cycle prevention
- physical folder coordination

### Document

- create/update/delete
- upload Markdown
- move
- title extraction
- normalized slug/file naming
- external-edit conflict protection using `content_hash`

### Viewer

- Sidebar / Content / TOC layout
- breadcrumb
- navigation tree
- Reading Mode
- responsive/dark-compatible Admin UI
- protected relative images

### Markdown

- Laravel CommonMark rendering through framework support
- raw HTML stripping
- unsafe-link protection
- stable unique heading anchors
- Table of Contents
- fenced-code language metadata

### Search

- title
- filename
- description
- Markdown content
- configurable synchronous scan bounds
- safe snippets

### Engagement

- global Favorite flag
- per-user Recently Viewed
- bounded recent history

### Sync

- preview without mutation
- New
- Changed
- Missing
- conservative Moved/Renamed candidate
- Ambiguous
- explicit confirmed Apply
- server-side revalidation before Apply
- no automatic metadata delete for Missing items

## 8. Known MVP Decisions

- Documents require a folder in the current implementation.
- Favorite is global, not per user.
- Recent is per user.
- Search is synchronous and bounded.
- Move/rename detection uses unique strong content-hash matching only.
- Ambiguous candidates require manual resolution.
- Missing metadata cleanup is not automatic.
- No recursive folder deletion.
- No raw public Ebook filesystem exposure.
- No new Markdown Composer dependency was required for MVP.
- No syntax-highlighting JS dependency was added; language metadata is preserved for future integration.

## 9. Test Inventory

```text
tests/Feature/Ebook/EbookBootstrapTest.php
tests/Feature/Ebook/EbookFolderServiceTest.php
tests/Feature/Ebook/EbookDocumentServiceTest.php
tests/Feature/Ebook/EbookMarkdownServiceTest.php
tests/Feature/Ebook/EbookSearchEngagementTest.php
tests/Feature/Ebook/EbookSyncServiceTest.php
```

Verified scenarios include:

- module manifest/routes/runtime override
- folder create/nesting/cycle/delete/tree
- document create/upload/move/delete/conflict
- Markdown sanitization/TOC/code metadata/assets
- metadata/content search
- favorites
- Recent per-admin and retention
- Sync preview/apply/change/missing/move/ambiguity/stale revalidation

## 10. Manual Verification Completed

### Admin UI

The Ebook Admin page was manually reviewed on Local VPS after the UI compliance pass. Folder/document management UI was accepted as professional and aligned with the repository Admin style.

### Scan & Sync

A file was created directly on disk:

```text
storage/app/ebooks/manual-test/hello-sync.md
```

Observed flow:

```text
Quét lại
-> NEW = 2
-> new folder + new file displayed
-> both selected
-> Apply confirmed
-> re-scan
-> NEW/CHANGED/MISSING/MOVED/AMBIGUOUS = 0
```

This verifies the external-filesystem reconciliation path on Local VPS.

## 11. MR Delivery Status

```text
MR-1 Skeleton + Bootstrap + Permissions        PASS
MR-2 Database + Folder Domain                  PASS
MR-3 Document Storage + CRUD                   PASS
MR-4 Markdown Viewer + UI Compliance           PASS
MR-5 Search + Favorites + Recently Viewed      PASS
MR-6 Scan & Sync                               PASS
MR-7 UX + Regression + Docs                    IN PROGRESS
```

## 12. MR-7 Remaining Gates

Before merge to `main`:

1. Pull final MR-7 documentation changes on Local VPS.
2. Verify Local VPS ownership/write access for Ebook storage.
3. Run Ebook regression again.
4. Run System/module-bootstrap regression if applicable.
5. Run full project regression.
6. Perform final Admin UI smoke.
7. Verify `git status` is clean.
8. Review branch diff against `main`.
9. Merge only after all gates pass.

## 13. Local VPS Storage Verification

Recommended diagnostic commands (read-only except optional touch test):

```bash
whoami
ps -eo user,group,comm | grep -E 'php-fpm|php8\.[0-9]-fpm' | grep -v grep
ls -ld storage storage/app storage/app/ebooks
find storage/app/ebooks -maxdepth 2 -printf '%u:%g %m %p\n' | head -50
```

Do not apply `chmod 777`.

If web-created and CLI-created files use different owners, use the project's existing deployment group/ACL convention rather than broad world-write permissions.

## 14. Final Regression Commands

Focused Ebook:

```bash
php artisan test tests/Feature/Ebook
```

System runtime bootstrap regression:

```bash
php artisan test tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
```

Final project gate:

```bash
php artisan test
```

## 15. Definition of Done Tracking

### Functional

- [x] Folder CRUD/hierarchy
- [x] Document create/upload/edit/move/delete
- [x] Markdown content remains on filesystem
- [x] Metadata remains in database
- [x] Viewer + TOC + breadcrumb
- [x] Reading Mode
- [x] Search metadata + Markdown content
- [x] Favorite
- [x] Recently Viewed
- [x] Scan & Sync preview/apply

### Safety

- [x] path-boundary tests
- [x] raw HTML/unsafe links blocked
- [x] external edit conflict protection
- [x] duplicate destination protection
- [x] Missing does not auto-delete metadata
- [x] ambiguous sync does not auto-move
- [x] Apply revalidates stale preview

### UX

- [x] Admin layout standard
- [x] responsive structure
- [x] dark-compatible utilities
- [x] loading/disabled states
- [x] empty states
- [x] destructive confirmation
- [x] manual desktop UI smoke

### Release gate

- [ ] Local VPS ownership/write verification
- [ ] final Ebook regression after MR-7 docs pull
- [ ] final System runtime regression
- [ ] full project regression
- [ ] final `git status` clean
- [ ] branch review / merge to `main`

## 16. Deferred Phase 2/3 Items

- Tags
- Version History
- Import Folder / Export
- complete internal Markdown link resolver
- advanced editor
- advanced syntax highlighting package
- per-user Favorites
- AI features
- semantic/vector search
- Elasticsearch
- Docker deployment/persistence validation

## 17. Canonical Documentation

```text
docs/modules/Ebook/REQUIREMENTS.md   # approved requirements
docs/modules/Ebook/CREATE_PLAN.md    # approved implementation plan
docs/modules/Ebook/README.md         # developer/operator overview
docs/modules/Ebook/INFORMATION.md    # current implementation/release state
```
