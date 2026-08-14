# Ebook Module Information

## 1. Current Status

- Module: `Ebook`
- Type: `domain`
- Bootstrap: `Modules\ModuleServiceProvider.php`
- Runtime state: repository module-state infrastructure
- Development target: Local VPS
- Phase: Phase 1 MVP + Final Hardening implementation complete in source
- Manual Scan & Sync smoke: previously PASS
- Final regression after Final Hardening: **PENDING Local VPS execution**

Do not reuse the earlier `34 tests / 102 assertions` or `44 tests / 130 assertions` counts as the final gate after these changes. A fresh run is required.

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

Filesystem is authoritative for Markdown content. Database is authoritative for managed metadata and Admin interaction/access state.

## 3. Filesystem Root

```text
storage/app/ebooks/
```

The root is private. Markdown and image assets are not exposed through a public storage symlink as an Ebook API. Relative images use the controlled authenticated Ebook asset route with path-boundary validation.

## 4. Database Tables

```text
ebook_folders
ebook_documents
ebook_document_recents
ebook_document_users
```

`ebook_document_users` is now part of the canonical module manifest table contract and stores per-document reader assignments to the existing `users` identity.

## 5. Admin Identity and Access

`auth:admin` uses the existing users provider and canonical `App\Models\User` model.

Document access rules:

```text
Super Admin
-> may view all Ebook documents

Other Admin user
-> requires normal Ebook capability permission
-> and must be assigned to the document through ebook_document_users
```

`EbookAccessService` is the central document visibility/access service.

Phase 1 Final Hardening extends this boundary to the management workspace:

- document list uses `visibleDocuments()`
- edit/update/delete/preview re-check document access
- an unassigned reader cannot discover restricted document metadata through the document management list

## 6. Permissions

```text
ebook.view
ebook.create
ebook.update
ebook.delete
ebook.upload
ebook.sync
```

Mutation authorization is checked server-side in addition to route/page access controls.

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
- per-document user assignment

Create/upload attach the acting Admin as a viewer. Final Hardening also attaches the authenticated Admin applying Scan & Sync to each newly reconciled document.

### Viewer

- Sidebar / Content / TOC
- breadcrumb
- navigation tree
- Reading Mode
- fullscreen
- document picker
- previous/next navigation
- TOC scroll spy
- copy code
- image lightbox
- safe external links
- responsive/dark-compatible Admin UI

### Markdown

- Laravel CommonMark rendering through framework support
- raw HTML stripping
- unsafe-link protection
- stable unique heading anchors
- Table of Contents
- protected relative image rewriting
- fenced-code language metadata
- real Phase 1 syntax token highlighting

`SyntaxHighlighterService` performs presentation-only token highlighting after Markdown rendering. Supported technical language families include PHP, JavaScript/TypeScript, SQL, JSON, Bash/Shell, CSS, HTML, YAML, Dockerfile, Nginx and Markdown; unknown languages fall back to safely escaped plain code.

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
- newly imported document becomes readable to the sync actor

## 8. Known Phase 1 Decisions

- Documents require a folder.
- Favorite is global, not per user.
- Recent is per user.
- Reader assignment is per document/user.
- Search is synchronous and bounded.
- Move/rename detection uses unique strong content-hash matching only.
- Ambiguous candidates require manual resolution.
- Missing metadata cleanup is not automatic.
- No recursive folder deletion.
- No raw public Ebook filesystem exposure.
- No new Markdown Composer dependency is required.
- Syntax highlighting is dependency-free and server-rendered.

## 9. Test Inventory

```text
tests/Feature/Ebook/EbookBootstrapTest.php
tests/Feature/Ebook/EbookDocumentAccessTest.php
tests/Feature/Ebook/EbookDocumentServiceTest.php
tests/Feature/Ebook/EbookFolderServiceTest.php
tests/Feature/Ebook/EbookMarkdownServiceTest.php
tests/Feature/Ebook/EbookSearchEngagementTest.php
tests/Feature/Ebook/EbookSyncServiceTest.php
tests/Feature/Ebook/EbookFinalHardeningTest.php
```

Final Hardening focused scenarios include:

- sync-created document grants reader access to authenticated sync actor
- highlighted fenced code contains real syntax token spans
- management workspace source is wired through `EbookAccessService::visibleDocuments()` and explicit document authorization

## 10. Manual Verification Already Completed

### Admin UI

The Ebook Admin page and viewer were manually reviewed on Local VPS during the Phase 1 polish cycle.

### Scan & Sync

A Markdown file placed directly under `storage/app/ebooks/` was detected, previewed, explicitly applied and returned a zero-difference re-scan. This verified the external-filesystem reconciliation path before Final Hardening.

Final Hardening requires re-running this smoke with a non-Super-Admin sync actor to verify immediate viewer access.

## 11. Phase 1 Delivery Status

```text
MR-1 Skeleton + Bootstrap + Permissions        PASS
MR-2 Database + Folder Domain                  PASS
MR-3 Document Storage + CRUD                   PASS
MR-4 Markdown Viewer + UI Compliance           PASS
MR-5 Search + Favorites + Recently Viewed      PASS
MR-6 Scan & Sync                               PASS
MR-7 UX + Regression + Docs                    IMPLEMENTED
Final Hardening                                IMPLEMENTED / TEST GATE PENDING
```

## 12. Final Hardening Changes

1. `EbookSyncService`: newly reconciled document attaches authenticated sync actor via `viewers()->syncWithoutDetaching()`.
2. `DocumentIndex`: management listing is filtered by `EbookAccessService::visibleDocuments()` and document actions enforce document-level authorization.
3. `MarkdownService` + `SyntaxHighlighterService`: fenced code receives real syntax token highlighting with safe fallback.
4. `config/module.php` + docs: `ebook_document_users` and current access/highlighting behavior are reflected in the canonical implementation documentation.

See `docs/modules/Ebook/PHASE_1_FINAL_HARDENING.md` for the requirements/plan reconciliation addendum.

## 13. Remaining Release Gates

Before merge to `main`:

1. Pull Final Hardening commits on Local VPS.
2. Run focused Ebook regression.
3. Run System/module-bootstrap regression.
4. Run full project regression.
5. Test non-Super-Admin sync actor access after importing a new Markdown file.
6. Perform final Viewer/Admin smoke, including syntax-highlighted code.
7. Verify storage ownership/write access.
8. Verify `git status` clean.
9. Review branch diff against `main`.
10. Merge only after all gates pass.

## 14. Local VPS Storage Verification

```bash
whoami
ps -eo user,group,comm | grep -E 'php-fpm|php8\.[0-9]-fpm' | grep -v grep
ls -ld storage storage/app storage/app/ebooks
find storage/app/ebooks -maxdepth 2 -printf '%u:%g %m %p\n' | head -50
```

Do not use `chmod 777`.

## 15. Final Regression Commands

```bash
php artisan test tests/Feature/Ebook
php artisan test tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
php artisan test
git status
```

If frontend CSS has not been rebuilt since pulling Final Hardening, also run the normal project Vite build so Tailwind includes the syntax-token utility classes referenced by `SyntaxHighlighterService`.

## 16. Definition of Done Tracking

### Functional

- [x] Folder CRUD/hierarchy
- [x] Document create/upload/edit/move/delete
- [x] Markdown content filesystem ownership
- [x] metadata database ownership
- [x] Viewer + TOC + breadcrumb
- [x] Reading Mode/fullscreen/navigation polish
- [x] metadata + content search
- [x] Favorite
- [x] Recently Viewed
- [x] Scan & Sync preview/apply
- [x] per-document reader assignment
- [x] sync actor assignment on new sync document
- [x] real fenced-code syntax highlighting

### Safety

- [x] path-boundary protections
- [x] raw HTML/unsafe links blocked
- [x] external edit conflict protection
- [x] duplicate destination protection
- [x] Missing does not auto-delete metadata
- [x] ambiguous sync does not auto-move
- [x] Apply revalidates stale preview
- [x] management document list/actions enforce document access

### Release gate

- [ ] fresh Ebook regression after Final Hardening
- [ ] final System runtime regression
- [ ] full project regression
- [ ] Local VPS sync-actor smoke
- [ ] final UI syntax-highlighting smoke
- [ ] Local VPS ownership/write verification
- [ ] final `git status` clean
- [ ] branch review / merge to `main`

## 17. Deferred Phase 2/3 Items

- Tags
- Version History
- Import Folder / Export
- complete internal Markdown link resolver
- advanced editor package
- drag/drop sorting
- expanded keyboard shortcuts
- per-user Favorites
- AI features
- semantic/vector search
- Elasticsearch
- Docker deployment/persistence validation

## 18. Canonical Documentation

```text
docs/modules/Ebook/REQUIREMENTS.md
docs/modules/Ebook/CREATE_PLAN.md
docs/modules/Ebook/README.md
docs/modules/Ebook/INFORMATION.md
docs/modules/Ebook/PHASE_1_FINAL_HARDENING.md
```
