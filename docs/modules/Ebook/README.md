# Ebook Module README

## 1. Module Overview

Ebook is a Laravel 12 domain module for managing an internal Markdown-based knowledge base / help center.

The module uses a dual-storage model:

- Markdown content is stored on the filesystem under `storage/app/ebooks/`.
- Metadata, hierarchy, favorites, recent-view history and per-document reader assignments are stored in MySQL.

The module is designed for Admin users and is not a public document library.

## 2. Registration / Bootstrap

Ebook is discovered by the repository root bootstrap `Modules\ModuleServiceProvider.php` and does not use `nwidart/laravel-modules`, `module.json`, or a second provider/registry system.

Manifest: `Modules/Ebook/config/module.php`.

Current contract:

- name: `Ebook`
- type: `domain`
- default enabled: `true`
- dependencies: `[]`
- runtime enable/disable: existing repository module-state infrastructure

## 3. Main Routes

Admin routes use the existing Admin guard and capability middleware. Primary paths are:

```text
/admin/ebook
/admin/ebook/document/{document}
/admin/ebook/document/{document}/asset
```

All Ebook pages and protected assets are internal Admin routes.

## 4. Permissions

Declared permissions:

```text
ebook.view
ebook.create
ebook.update
ebook.delete
ebook.upload
ebook.sync
```

Route-level permission middleware protects page/asset access. Mutating Livewire actions perform server-side authorization; UI visibility is not treated as the security boundary.

Document-level read access is additionally enforced for non-Super-Admin users through `EbookAccessService` and `ebook_document_users`.

## 5. Database Tables

### `ebook_folders`

Stores hierarchy and folder metadata. Sibling folder slugs are unique. A folder cannot be moved below itself/its descendant and non-empty folder deletion is blocked.

### `ebook_documents`

Stores document metadata, canonical file path, favorite state, content hash and file mtime. Markdown content itself remains on the filesystem.

### `ebook_document_recents`

Stores bounded Recently Viewed entries per Admin user using the existing `users.id` identity.

### `ebook_document_users`

Stores per-document reader assignments between `ebook_documents` and the canonical `users` table.

The module manifest declares all four Ebook-owned tables.

## 6. Filesystem

Configured root:

```text
storage/app/ebooks/
```

Filesystem content is private and is not exposed through a public storage symlink. Application-created folders/files use Laravel Storage. Database paths are canonical relative paths below the configured Ebook root.

Security rules include no arbitrary absolute paths, no `../` escape, no silent overwrite, protected relative-image delivery, and external-edit conflict detection.

## 7. Folder Management

Implemented features:

- create/edit/nested folders
- move and sort order
- active/inactive state
- sibling-slug collision protection
- descendant-cycle prevention
- safe delete guard
- filesystem + metadata coordination

When a folder path changes, descendant document paths are coordinated with the physical filesystem.

## 8. Document Management

Implemented features:

- create/edit/delete Markdown document
- upload `.md`
- move between folders
- description/sort/active state
- title extraction from first H1 on upload
- slug/filename normalization
- duplicate destination protection
- SHA-256 `content_hash` conflict protection
- per-document reader assignment

Create and upload attach the current Admin as a reader. Final Hardening also guarantees that a new document imported through Scan & Sync is attached to the authenticated Admin who applies the sync, so the sync actor can immediately read the reconciled document.

The management workspace now filters its document list through `EbookAccessService::visibleDocuments()` and checks document access before edit/update/delete/preview operations. This prevents a user with only `ebook.view` from seeing or mutating metadata for documents that were not assigned to that user.

## 9. Markdown Viewer

The viewer provides a responsive technical-document reading experience with Sidebar/Content/TOC behavior, breadcrumb, Reading Mode, fullscreen mode, document picker, previous/next navigation, TOC scroll spy, copy-code action, image lightbox and safe external-link behavior.

Markdown support includes H1-H6, paragraphs, lists, fenced code blocks, tables/task-list behavior through Laravel CommonMark support, stable heading anchors, relative images and TOC generation.

Raw dangerous HTML is stripped and unsafe links are blocked by renderer configuration.

### Syntax Highlighting

Phase 1 Final Hardening adds real dependency-free token highlighting for fenced technical code blocks while preserving `language-*` metadata. The highlighter supports the Phase 1 language families used by the Ebook viewer, including PHP, JavaScript/TypeScript, SQL, JSON, Bash/Shell, CSS, HTML, YAML, Dockerfile, Nginx and Markdown. Unsupported/unknown languages safely fall back to escaped plain code.

Highlighting is presentation-only and runs after safe Markdown rendering; it does not replace the Markdown parser or sanitizer.

## 10. Search

`EbookSearchService` provides bounded synchronous search across title, filename, description and Markdown file content. Search limits are configuration-driven and result snippets remain escaped/safe.

No Elasticsearch, vector database, semantic search or AI search is part of Phase 1.

## 11. Favorites

Phase 1 Favorites remain global metadata through `ebook_documents.is_favorite`. Per-user Favorites remain a later enhancement.

## 12. Recently Viewed

Recently Viewed is persisted per Admin user, updated on view and bounded by configuration.

## 13. Scan & Sync

Scan & Sync follows:

```text
Scan -> Preview -> explicit selection/confirmation -> Apply -> Re-scan
```

Detected categories are New, Changed, Missing, Moved/Renamed candidate and Ambiguous.

Safety rules:

- Preview does not mutate metadata.
- Missing content never causes automatic metadata deletion.
- Move/rename requires a unique strong content-hash match.
- Ambiguous matches never auto-move.
- Apply performs a fresh server-side scan and stale candidates are skipped.
- Newly reconciled documents are assigned to the authenticated sync actor.

Manual Local VPS Scan & Sync verification has previously passed with direct filesystem content.

## 14. Main Services

```text
EbookFolderService       folder tree/CRUD/path safety
EbookDocumentService     document CRUD/storage/conflict handling
MarkdownService          safe rendering/TOC/assets/code decoration
SyntaxHighlighterService fenced-code token highlighting
EbookNavigationService   viewer tree/breadcrumb
EbookSearchService       bounded search
EbookEngagementService   favorites/recent
EbookAccessService       document-level visibility/access
EbookSyncService         scan/preview/conservative apply
```

Livewire owns UI state/orchestration; filesystem/domain behavior remains service-owned.

## 15. Livewire / Admin UI

Main components include folder management, document workspace, viewer, search, access manager and sync panel. The module uses `Admin::layouts.master` and repository-aligned Tailwind patterns with responsive/dark-mode-compatible classes, loading states, validation feedback, empty states and destructive confirmations.

The document workspace provides source/split/preview modes and an editor-first workflow without introducing Monaco/CodeMirror.

## 16. Configuration

Primary configuration:

```text
Modules/Ebook/config/ebook.php
Modules/Ebook/config/module.php
```

Runtime settings include Ebook disk/root, upload limits, search bounds and recent-history limit.

## 17. Tests

Focused Ebook suites cover bootstrap, folder service, document service, Markdown/security, search/engagement, sync, document access and Final Hardening behavior.

Final Hardening adds focused coverage for:

- sync actor automatically gaining reader access to newly reconciled documents
- real syntax tokens in fenced PHP rendering

A fresh Local VPS test run is required after pulling these changes; documentation must not claim a new passing count until that run completes.

## 18. Runtime / Deployment Notes

Current implementation target remains Local VPS. Before merge/deployment verify PHP-FPM user/group, CLI user, ownership/permissions of `storage/app/ebooks/`, web write access and CLI interoperability. Do not use `chmod 777`.

Docker-specific persistence/volume verification remains deferred to a later deployment validation phase.

## 19. Phase 1 Final Hardening

Final Hardening closes four audit items discovered after comparing Phase 1 implementation against `REQUIREMENTS.md` and `CREATE_PLAN.md`:

1. Sync-created documents grant access to the authenticated sync actor.
2. Management document listing/actions enforce document-level access.
3. Fenced code blocks receive real syntax highlighting rather than language metadata only.
4. Documentation and manifest metadata are reconciled with the implemented access table and current Phase 1 state.

These changes do not introduce a new module architecture or public API.

## 20. Out of Scope / Future Work

Still deferred beyond Phase 1:

- tags
- version history
- bulk folder import/export
- full internal Markdown-to-Markdown link resolver
- advanced editor package
- keyboard shortcuts beyond current viewer/editor interactions
- drag/drop sorting
- per-user Favorites
- AI/semantic/vector search
- Elasticsearch
- trash/soft-delete workflow
- Docker deployment changes

## 21. Canonical Documentation

```text
docs/modules/Ebook/REQUIREMENTS.md
docs/modules/Ebook/CREATE_PLAN.md
docs/modules/Ebook/README.md
docs/modules/Ebook/INFORMATION.md
docs/modules/Ebook/PHASE_1_FINAL_HARDENING.md
```
