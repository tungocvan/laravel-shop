# Ebook Module README

## 1. Module Overview

Ebook is a Laravel 12 domain module for managing an internal Markdown-based knowledge base / help center.

The module uses a dual-storage model:

- Markdown content is stored on the filesystem under `storage/app/ebooks/`.
- Metadata, hierarchy, favorites and recent-view history are stored in MySQL.

The module is designed for Admin users and is not a public document library.

## 2. Registration / Bootstrap

Ebook is discovered by the repository's root module bootstrap:

```text
Modules\ModuleServiceProvider.php
```

The module does not use `nwidart/laravel-modules`, `module.json`, or a second provider/registry system.

Manifest:

```text
Modules/Ebook/config/module.php
```

Current module contract:

- name: `Ebook`
- type: `domain`
- default enabled: `true`
- dependencies: `[]`

Runtime module enable/disable behavior uses the existing repository module-state infrastructure.

## 3. Main Routes

Main Admin routes use:

```text
web
admin guard
permission:ebook.view,admin
```

Primary paths:

```text
/admin/ebook
/admin/ebook/document/{document}
/admin/ebook/document/{document}/asset
```

All Ebook pages are internal Admin routes.

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

Route-level permission middleware protects page/asset access.

Mutating Livewire actions also perform server-side authorization. UI visibility is not treated as the security boundary.

## 5. Database Tables

### `ebook_folders`

Stores folder hierarchy and metadata.

Important fields:

- `parent_id`
- `name`
- `slug`
- `description`
- `sort_order`
- `is_active`

Sibling folder slugs are unique.

Folder delete is blocked while the folder contains child folders or files.

### `ebook_documents`

Stores Markdown document metadata.

Important fields:

- `folder_id`
- `title`
- `slug`
- `file_name`
- `file_path`
- `source_type`
- `description`
- `sort_order`
- `is_active`
- `is_favorite`
- `content_hash`
- `file_mtime`

Markdown content itself is not persisted in this table.

### `ebook_document_recents`

Stores Recently Viewed data per Admin user.

The `admin` guard uses the existing `users` provider and `App\Models\User`, therefore Recent records reference `users.id`; no separate Admin identity table/model is introduced.

History is bounded by configuration.

## 6. Filesystem

Configured Ebook root:

```text
storage/app/ebooks/
```

Filesystem content is private and must not be exposed as a raw public directory.

Application-created folders and files are accessed through Laravel Storage.

Database paths are canonical relative paths below the configured Ebook root.

Path rules include:

- no arbitrary absolute paths from requests
- no `../` traversal
- no path escape outside Ebook root
- no silent overwrite of an existing destination
- relative image assets are served through an authenticated Ebook asset endpoint

## 7. Folder Management

Implemented features:

- create folder
- edit folder
- nested folders
- move folder
- sort order
- active/inactive state
- sibling-slug collision protection
- descendant-cycle prevention
- safe delete guard
- filesystem + metadata coordination

When a folder path changes, document metadata paths below that folder are updated to remain consistent with the physical filesystem.

## 8. Document Management

Implemented features:

- create Markdown document
- upload `.md`
- edit Markdown content
- move document between folders
- delete document
- description/sort/active state
- title extraction from first H1 on upload
- slug/filename normalization
- duplicate destination protection
- external-edit conflict detection using SHA-256 `content_hash`

If a file has changed externally since the Admin began editing, the service rejects the save instead of silently overwriting the external change.

## 9. Markdown Viewer

The viewer provides:

```text
Desktop: Sidebar | Content | Table of Contents
```

It also supports responsive behavior and Reading Mode.

Implemented Markdown behavior includes:

- H1-H6
- paragraphs
- lists
- fenced code blocks
- language metadata such as `language-php`
- tables/task-list behavior provided by Laravel CommonMark support
- heading anchors
- Table of Contents
- relative image handling
- breadcrumb/navigation tree

Raw dangerous HTML is stripped and unsafe links are blocked by Markdown renderer configuration.

No third-party syntax-highlighting package/CDN was added during MVP. Code blocks preserve language metadata for future highlighting integration.

## 10. Search

`EbookSearchService` provides bounded synchronous search across:

- title
- filename
- description
- Markdown file content

Filesystem content search is constrained by configuration such as maximum documents, file size and total bytes processed per request.

Search results expose safe snippets; no Elasticsearch, vector database, semantic search or AI search is part of MVP.

## 11. Favorites

MVP Favorites are global metadata:

```text
ebook_documents.is_favorite
```

This matches the approved Phase 1 scope.

Per-user Favorites are deferred unless separately approved.

## 12. Recently Viewed

Recently Viewed is persisted per Admin user.

Viewing a document records or refreshes the `(user_id, ebook_document_id)` entry.

The retained set is bounded and the implementation is compatible with both MySQL and SQLite test execution.

## 13. Scan & Sync

Scan & Sync follows the mandatory workflow:

```text
Scan
-> Preview
-> User selection / confirmation
-> Apply
-> Re-scan
```

Preview does not mutate metadata.

Detected categories:

- New
- Changed
- Missing
- Moved/Renamed candidate
- Ambiguous

Safety rules:

- Missing files/folders do not trigger automatic metadata deletion.
- A move/rename is proposed only when a strong unique content-hash match exists.
- Ambiguous hash matches are never auto-moved.
- Apply performs a fresh server-side scan and does not trust stale browser preview data as authority.
- Stale candidates are skipped rather than forced.

Manual Local VPS verification completed successfully with a Markdown file placed directly under `storage/app/ebooks/`; Scan detected one folder + one file, Apply reconciled metadata, and the subsequent scan returned all categories to zero.

## 14. Main Services

### `EbookFolderService`

Folder tree, CRUD, move, path resolution, hierarchy validation and safe deletion.

### `EbookDocumentService`

Document CRUD, upload, content reads/writes, path coordination, content hash conflict protection and filesystem/DB compensation where applicable.

### `MarkdownService`

Markdown rendering, sanitization, heading anchors, TOC extraction and protected relative asset rewriting.

### `EbookNavigationService`

Viewer tree and breadcrumb construction.

### `EbookSearchService`

Bounded metadata and filesystem-content search.

### `EbookEngagementService`

Favorites and per-user Recently Viewed persistence.

### `EbookSyncService`

Filesystem scan, preview classification, conservative move detection and confirmed reconciliation.

## 15. Livewire Components

Main components include:

```text
ebook.folder.folder-index
ebook.document.document-index
ebook.ebook-viewer
ebook.ebook-search
ebook.ebook-sync-panel
```

Livewire manages UI state, validation and orchestration; core filesystem/business behavior remains in Services.

## 16. Admin UI

Ebook follows:

```text
.codex/standards/ADMIN_UI_STANDARD.md
```

The module uses `Admin::layouts.master` and repository-aligned Tailwind UI patterns.

Implemented UX includes:

- card/section hierarchy
- responsive grid
- clear labels and validation feedback
- loading/disabled mutation states
- empty states
- delete/sync confirmation
- responsive document list
- dark-mode-compatible classes
- Reading Mode

## 17. Configuration

Primary config:

```text
Modules/Ebook/config/ebook.php
Modules/Ebook/config/module.php
```

Runtime settings include Ebook disk/root, upload limits, search bounding and recent-history limit.

## 18. Tests

Focused Ebook test suites:

```text
EbookBootstrapTest
EbookFolderServiceTest
EbookDocumentServiceTest
EbookMarkdownServiceTest
EbookSearchEngagementTest
EbookSyncServiceTest
```

Latest completed Ebook regression before MR-7 final gate:

```text
34 tests
102 assertions
0 failed
```

MR-7 still requires final full-project regression before merge.

## 19. Local VPS Notes

Current implementation target is Local VPS directly, not Docker.

Before merge/deployment, verify:

- PHP-FPM user/group
- CLI user
- ownership and permissions of `storage/app/ebooks/`
- PHP-FPM can create/read/update files
- CLI maintenance does not leave files inaccessible to PHP-FPM

Do not use `chmod 777`.

Docker-specific persistence/volume verification is deferred to a later deployment validation phase.

## 20. Out of Scope / Future Work

Not included in Phase 1 MVP:

- tags
- version history
- bulk folder import/export
- Monaco/CodeMirror
- full internal Markdown-to-Markdown link resolver
- advanced syntax highlighter dependency
- per-user favorites
- AI summary/search/tags
- semantic/vector search
- Elasticsearch
- trash/soft-delete workflow
- Docker deployment changes

## 21. Source Documents

Design and implementation history:

```text
docs/modules/Ebook/REQUIREMENTS.md
docs/modules/Ebook/CREATE_PLAN.md
docs/modules/Ebook/README.md
docs/modules/Ebook/INFORMATION.md
```
