# Ebook Module — CREATE_PLAN

## 1. Status

- Module: `Ebook`
- Type: `domain`
- Source: `docs/modules/Ebook/REQUIREMENTS.md`
- Repository integration: first-party modular monolith through `Modules\ModuleServiceProvider`
- Target release: Phase 1 / MVP
- Current execution environment: Local VPS directly; Docker is not part of the first implementation/test loop
- Status: **PLAN ONLY — APPLICATION CODE NOT YET AUTHORIZED**

This document is the implementation plan required by `/create-module Ebook`. After this file is created, work stops at the approval gate. No application source is created until the user explicitly approves this plan.

---

## 2. Purpose and Scope

`Ebook` will provide an internal Laravel Admin Knowledge Base / Help Center backed by real Markdown files.

Ownership model:

```text
Markdown content        -> storage/app/ebooks/
Folder/document metadata -> MySQL
Business workflows       -> Modules/Ebook/Services
Interactive Admin UI     -> Modules/Ebook/Livewire
Bootstrap/runtime state  -> existing Modules\ModuleServiceProvider + App\Modules infrastructure
```

The first release must provide:

- Folder hierarchy CRUD
- Document CRUD
- Markdown upload/edit/preview/view
- Safe Markdown rendering
- Sidebar tree
- Breadcrumb
- Table of Contents
- Syntax highlighting
- Metadata + bounded Markdown-content search
- Scan & Sync with preview before apply
- Favorites
- Recently Viewed
- Reading Mode
- Responsive/dark-mode compatible UI
- Path traversal/XSS/upload protections
- Runtime module enable/disable compatibility

Not in Phase 1:

- Tags
- Version history
- Bulk folder import/export
- Monaco/CodeMirror
- Full internal Markdown-link graph/backlinks
- AI/semantic search
- Elasticsearch/vector database
- Public API/public website

---

## 3. Repository Conventions Reused

### 3.1 `Modules\ModuleServiceProvider` — authoritative bootstrap

Reuse:

- module discovery from `Modules/`
- `config/module.php` manifest
- module type/dependency validation
- runtime enabled state through `ModuleStateResolver`
- automatic web route loading
- automatic view namespace loading
- automatic migrations
- automatic Livewire registration
- optional module provider only when a special hook is genuinely required

Do not introduce:

- `module.json`
- `nwidart/laravel-modules`
- another registry
- duplicate discovery code
- manual global registration for resources already handled by the root provider

### 3.2 `Post` — primary domain/UI/service reference

Reuse:

- domain manifest convention
- Admin route grouping with `web`, `auth:admin`, capability permission middleware
- thin Controller + Page Blade + Livewire + Service separation
- service-owned pagination and mutations

Do not copy:

- Post's Category/User/Shared dependencies
- Post-specific schema/statuses
- import/export behavior that Ebook does not need

### 3.3 `System` — permission/runtime-safety reference

Reuse:

- explicit dotted capability names
- backend authorization for sensitive actions
- runtime-state awareness

Do not copy:

- `shell` module type
- System privileged/runtime administration responsibilities

### 3.4 `Admission` — richer domain manifest reference

Reuse where appropriate:

- deterministic manifest metadata
- explicit permission/table metadata
- production-safe module organization

Do not copy queues/document-generation behavior into MVP.

---

## 4. Bootstrap Contract

| Contract | Ebook Plan |
|---|---|
| Manifest | `Modules/Ebook/config/module.php` |
| Type | `domain` |
| Dependencies | `[]` initially; add only if implementation inspection proves a hard runtime dependency |
| Module Provider | **Not required initially** |
| Config | **Yes** — Ebook storage/search/render settings |
| Web routes | **Yes** |
| API routes | **No** |
| Migrations | **Yes** |
| Livewire | **Yes** |
| Blade components | Reuse shared/current UI first; module-local only if justified |
| Console commands | **No** in MVP |
| Runtime state | **Supported** through existing resolver/repository |
| Runtime storage | **Yes** — `storage/app/ebooks/` |

### Manifest proposal

```php
return [
    'name' => 'Ebook',
    'type' => 'domain',
    'default_enabled' => true,
    'depends' => [],
    'permissions' => [
        'ebook.view',
        'ebook.create',
        'ebook.update',
        'ebook.delete',
        'ebook.upload',
        'ebook.sync',
    ],
    'tables' => [
        'ebook_folders',
        'ebook_documents',
        'ebook_document_recents',
    ],
];
```

`ebook_document_recents` is included only if the user-specific recent strategy in section 9 is approved. If implementation discovery shows a better canonical repository mechanism, the manifest table list will be adjusted before migration creation.

No module-specific provider is planned because root discovery already covers routes, config, resources, migrations and Livewire.

---

## 5. Proposed Module Structure

```text
Modules/Ebook/
├── config/
│   ├── module.php
│   └── ebook.php
├── database/
│   └── migrations/
├── Http/
│   └── Controllers/
│       └── EbookController.php
├── Livewire/
│   ├── EbookViewer.php
│   ├── Folder/
│   │   ├── FolderIndex.php
│   │   └── FolderForm.php
│   ├── Document/
│   │   ├── DocumentIndex.php
│   │   ├── DocumentForm.php
│   │   └── DocumentUpload.php
│   ├── Search/
│   │   └── SearchPanel.php
│   └── Sync/
│       └── SyncPanel.php
├── Models/
│   ├── EbookFolder.php
│   ├── EbookDocument.php
│   └── EbookDocumentRecent.php      # only if approved persistence strategy requires it
├── Services/
│   ├── EbookFolderService.php
│   ├── EbookDocumentService.php
│   ├── MarkdownService.php
│   ├── EbookSearchService.php
│   └── EbookSyncService.php
├── resources/
│   └── views/
│       ├── pages/
│       └── livewire/
└── routes/
    └── web.php
```

Exact Livewire granularity may be reduced during implementation if a smaller component set remains readable and testable. The goal is not to create classes merely to match a diagram.

---

## 6. Config Design

`config/ebook.php` should centralize server-controlled settings, for example:

```text
root_disk / root directory
allowed_extensions
upload_max_size
search file-count/byte limits
recent limit
render options
```

Proposed storage abstraction:

- Laravel `Storage` API
- default disk: local/private disk
- module-relative root: `ebooks`
- physical default: `storage/app/ebooks/`

Browser-provided paths must never select arbitrary disks or roots.

---

## 7. Route and Authorization Plan

Admin route group:

```text
middleware: web, auth:admin
prefix:     /admin/ebook
name:       admin.ebook.*
```

Initial page routes:

```text
GET /admin/ebook
GET /admin/ebook/folder/{folder}
GET /admin/ebook/document/{document}
```

Search, CRUD, upload and sync can remain Livewire actions unless a dedicated HTTP endpoint is justified.

Permission plan:

```text
ebook.view
ebook.create
ebook.update
ebook.delete
ebook.upload
ebook.sync
```

Authorization rules:

- route access uses applicable permission middleware
- all mutating Livewire methods authorize again server-side
- UI visibility is convenience only, never the security boundary
- `Super Admin` retains repository-wide `Gate::before` behavior

No separate `ebook.manage_folders` permission is planned for MVP; folder mutations map to create/update/delete capabilities unless later business requirements require independent delegation.

---

## 8. Database and Model Plan

### 8.1 `ebook_folders`

Proposed columns:

```text
id                  bigint PK
parent_id           nullable FK -> ebook_folders.id
name                varchar
slug                varchar
description         text nullable
sort_order          unsigned integer default 0
is_active           boolean default true
created_at
updated_at
```

Constraints/indexes:

- index `(parent_id, sort_order)`
- index `(is_active)` when useful for navigation
- unique `(parent_id, slug)` so sibling folders cannot collide
- `parent_id` uses restrictive/safe delete behavior rather than automatic recursive cascade

Service invariant:

- a folder cannot move under itself or any descendant

### 8.2 `ebook_documents`

Proposed columns:

```text
id                  bigint PK
folder_id           nullable/required FK -> ebook_folders.id (finalized with root-document decision)
title               varchar
slug                varchar
file_name           varchar
file_path           varchar
source_type         varchar default 'file'
description         text nullable
sort_order          unsigned integer default 0
is_active           boolean default true
is_favorite         boolean default false   # MVP global favorite strategy
content_hash        char(64) nullable        # optimistic external-change/sync support
file_mtime          unsigned bigint nullable # sync hint, not identity proof
created_at
updated_at
```

Constraints/indexes:

- unique canonical `file_path`
- unique `(folder_id, slug)` for sibling URL identity
- index `(folder_id, sort_order)`
- index `(is_active)`
- metadata search indexes as justified by actual queries

### 8.3 Favorites strategy

Phase 1 chooses the smallest coherent strategy:

```text
ebook_documents.is_favorite
```

This makes Favorites global across Admin users.

Reason:

- specification explicitly allows a simple MVP approach
- avoids introducing a user-preference subsystem before it is required

If per-admin Favorites are required later, migrate to a dedicated relation without changing document content ownership.

### 8.4 Recently Viewed strategy

Recommended MVP: **per-admin persistence** in `ebook_document_recents` because "recently viewed" is inherently user-contextual and global recent history would create confusing UX.

Proposed columns:

```text
id
admin_id / canonical admin identity FK strategy determined from actual Admin auth model
ebook_document_id
viewed_at
```

Constraints:

- unique `(admin_id, ebook_document_id)`
- update timestamp/viewed_at on view
- retain only a bounded recent set in service logic

Before migration creation, implementation must inspect the actual Admin guard/provider model and canonical key. Do not invent or duplicate identity storage.

---

## 9. Filesystem Identity and Path Strategy

Canonical rule:

```text
DB stores relative paths below `storage/app/ebooks/`
```

Example:

```text
Laravel/Livewire/upload.md
```

Never store an arbitrary absolute path supplied by a request.

Path processing sequence:

```text
user intent
-> server-side folder/document lookup
-> derive relative path
-> normalize separators/components
-> reject traversal/absolute/symlink escape
-> resolve through configured Ebook disk/root
```

### Folder/file naming

- filename derived server-side from slug/title when created in UI
- uploaded original filename is sanitized and collision-checked
- no silent overwrite
- duplicate destination returns validation/conflict state

### External modification detection

`content_hash` is planned to detect whether content changed between read/edit/save and to help reconciliation.

`file_mtime` is only an optimization/hint. It must not be treated as durable identity.

Rename/move detection remains conservative:

- exact relative path -> same known document
- path missing + a new path with matching strong content signal -> move/rename candidate
- ambiguous case -> show New + Missing candidate, require user decision

---

## 10. Filesystem + DB Mutation Safety

MySQL and filesystem do not share a single transaction, so services must use staged workflows and compensation.

### Create document

Preferred order:

```text
validate all input/path
-> reserve/check destination
-> write file to temporary path
-> DB transaction creates metadata
-> atomic rename temp -> final path
-> if finalization fails, compensate DB record
```

Exact ordering may be adjusted based on Laravel Storage filesystem capabilities on Local VPS, but partial success must never be hidden.

### Update document

```text
load metadata + expected content_hash
-> verify current disk hash still matches expected version
-> write replacement to temporary file
-> DB transaction update metadata
-> atomic replace when supported
-> compensate/reconcile on failure
```

If external content changed after editing started, return a conflict instead of overwriting silently.

### Move/rename

```text
validate source + destination
-> conflict check
-> stage filesystem move/rename
-> DB transaction update canonical path metadata
-> compensate when safe if either side fails
```

### Delete

Default MVP policy:

- no implicit recursive folder delete
- folder delete blocked while child folders/documents exist
- document delete requires explicit confirmation
- deletion must report partial failure clearly

A later Trash/soft-delete workflow is not introduced unless approved separately.

---

## 11. Service Boundaries

### `EbookFolderService`

Responsibilities:

- CRUD folder metadata + physical folder coordination
- tree building
- hierarchy validation
- move/sort
- safe delete guards
- canonical folder-relative path resolution

### `EbookDocumentService`

Responsibilities:

- document list/pagination
- create/update/delete/move
- safe upload orchestration
- metadata lookup
- conflict/hash handling
- content reads through Markdown/filesystem abstractions
- recent/favorite orchestration where appropriate

### `MarkdownService`

Responsibilities:

- Markdown parsing/rendering
- title extraction
- heading/TOC extraction
- stable anchor generation
- sanitization
- relative image/link normalization
- code-block metadata

### `EbookSearchService`

Responsibilities:

- metadata search
- bounded Markdown-content search
- ranking
- snippets/highlight-safe result data

### `EbookSyncService`

Responsibilities:

- scan root
- build immutable preview/plan data
- classify New/Changed/Missing/Move candidate
- apply only explicitly confirmed operations
- update hashes/mtime metadata
- return result report

Livewire does not read/write the filesystem directly.

---

## 12. Markdown Rendering Dependency Gate

Current top-level `composer.json` does not explicitly declare a Markdown parser/sanitizer package.

Before implementation MR involving `MarkdownService`:

1. inspect `composer.lock` / installed dependency tree for an already available supported Markdown parser;
2. inspect whether the existing Laravel framework path exposes a suitable supported Markdown rendering API;
3. verify HTML sanitization requirements independently — Markdown parsing alone is not sufficient XSS protection;
4. if a production dependency is required, add it only as an explicit approved dependency change.

Preferred evaluation order:

- reuse a maintained package already present transitively only if its use is stable/appropriate;
- otherwise explicitly add a maintained CommonMark-compatible parser;
- use a maintained HTML sanitizer or a parser configuration that safely disables dangerous raw HTML, with tests proving the behavior.

Do not implement a custom Markdown parser or regex-based sanitizer.

Syntax highlighting should prefer the repository's existing frontend asset/build stack. A new JS highlighting dependency requires explicit package change and build verification.

---

## 13. Markdown Assets and Serving

Markdown images may use safe relative paths under Ebook root.

Do **not** expose `storage/app/ebooks` as a raw public directory.

MVP plan:

- render relative image references into a controlled authenticated Ebook asset route/controller
- route resolves document-relative asset paths server-side
- authorization requires `ebook.view`
- path is normalized and constrained below Ebook root
- response returns only permitted image assets

This keeps the knowledge base internal and avoids arbitrary storage exposure.

Full Markdown-to-Markdown document link conversion remains Phase 2; MVP must preserve safe links and leave a resolver extension point.

---

## 14. Search Plan

### Metadata

Use bounded MySQL queries against:

- title
- file_name
- description

### Content

MVP filesystem content search:

- search only active/known Markdown documents unless explicitly running discovery mode
- configurable maximum documents/filesize/total bytes per request
- normalize query length
- generate short escaped snippets
- no raw HTML injection from result highlights

No Elasticsearch/vector DB/AI.

If library size later exceeds safe synchronous scanning, introduce an index/search backend behind `EbookSearchService` without changing Livewire contracts.

---

## 15. Scan & Sync Plan

### Stage 1 — Scan/Preview

No mutation.

Produce categories:

```text
New folders/files
Changed content/metadata hints
Missing folders/files
Moved/Renamed candidates
Ambiguous candidates
```

Each candidate must include enough server-side identity/context for a later validated apply step.

### Stage 2 — Apply

- explicit `ebook.sync` authorization
- user confirmation
- server revalidates current filesystem state; never trust stale browser plan payload as authority
- apply bounded operations through services
- record result/errors
- no silent recursive deletion

Missing filesystem content does not automatically delete DB metadata.

---

## 16. Admin UI / Livewire Plan

Main viewer:

```text
Desktop: Sidebar | Content | TOC
Tablet : Sidebar | Content, TOC collapsible
Mobile : Content, Sidebar drawer, TOC collapsible
```

Must reuse `Admin::layouts.master` and current Admin UI conventions.

### Viewer state

- selected folder/document
- expanded folders
- reading mode
- TOC state
- favorite action
- recent tracking

### Management states

- loading/disabled buttons
- inline validation errors
- confirmation for delete/sync
- empty states
- conflict/error state for external file changes
- responsive overflow for code/table

No business logic in Blade.

Pagination options:

```text
10 / 25 / 50 / 100
```

`All` is not enabled by default because repository Admin UI rules reject unbounded production datasets. It may be exposed only when a safe hard cap is proven and documented.

---

## 17. Runtime State Plan

Manifest default state and runtime override are separate.

Ebook must rely on:

- `ModuleStateRepository`
- `ModuleStateResolver`
- existing `storage/app/system/module-state.json` state backend

Never read/write that JSON directly from Ebook.

Verification must include:

1. default manifest state
2. runtime OFF
3. runtime ON
4. route/Livewire absence/presence consistent with effective state
5. runtime toggle does not modify tracked manifest
6. Git working tree remains clean

Because Ebook initially declares no module dependencies, disabling another domain module should not unexpectedly disable Ebook.

---

## 18. Local VPS Runtime Storage Plan

The first development/testing environment is Local VPS directly, not Docker.

Implementation verification on VPS must establish:

- actual PHP-FPM user/group
- CLI user used for Artisan commands
- `storage/app/ebooks/` ownership and permissions
- both web and approved CLI workflows can access Ebook files safely
- no files are left unreadable/unwritable because CLI and PHP-FPM run as different users

Do not use `chmod 777`.

Preferred approach:

- application creates directories/files through Laravel Storage
- group/ownership follows the existing deployment convention
- runtime content remains outside Git-tracked source

Docker compatibility remains documented for later deployment validation, but no Docker-specific changes are planned in the first implementation batch.

---

## 19. Seeder Plan

No demo content seeder is required for the first implementation.

If permission seeding is integrated through the repository's existing module/role seeding flow, it must be deterministic and production-safe.

Do not use Faker for production-required Ebook seeders.

Do not create demo Markdown files automatically in production storage.

---

## 20. Tests

### Bootstrap / architecture

- module discovered by `Modules\ModuleServiceProvider`
- manifest type/dependencies valid
- web routes registered only when enabled
- Livewire aliases register correctly
- no duplicate provider/route/resource registration

### Routes / permissions

- authenticated + permitted Admin can view
- denied permission cannot view/mutate
- each sensitive mutation checks backend authorization

### Folder service

- create/update
- nested tree
- move
- descendant-cycle rejection
- duplicate sibling slug/path rejection
- delete blocked when non-empty

### Document service

- create file + metadata
- upload `.md`
- reject invalid extension/path
- update content
- conflict when external file hash changed
- move/rename
- duplicate destination rejected
- delete
- compensation/recovery paths where testable

### Markdown

- H1 title extraction
- filename fallback title
- heading/TOC anchors
- code/table/task-list render
- raw dangerous HTML/XSS blocked
- malicious links/images handled safely
- relative image path traversal rejected

### Search

- title
- filename
- description
- content
- bounded search limits
- safe snippets/highlight

### Sync

- scan preview does not mutate
- new file/folder
- changed file
- missing file
- move candidate
- ambiguous rename does not auto-delete
- apply requires explicit action/authorization
- stale plan revalidation

### Recent/Favorite

- favorite toggle
- recent update is user-specific if recent table strategy is approved
- recent result limit/bounding

### Runtime storage/state

- root boundary enforced
- runtime module OFF/ON behavior
- manifest unchanged by toggle

---

## 21. Acceptance Criteria

MVP is ready only when this end-to-end flow passes:

```text
Create folder
-> create/upload Markdown
-> extract title
-> safely persist file + metadata
-> navigation updates
-> open document
-> sanitized Markdown renders
-> TOC works
-> search finds document
-> edit/preview/save
-> viewer reflects content
```

External filesystem flow:

```text
copy .md into storage/app/ebooks/
-> Scan & Sync preview
-> review candidate changes
-> Apply Sync explicitly
-> metadata reconciled
-> document readable
```

And all applicable gates pass:

- route/permission tests
- path traversal/XSS tests
- focused service/Livewire tests
- module regression
- System regression if shared runtime/module infrastructure changes
- full project regression before merge
- manual Admin UI smoke
- Local VPS ownership/write verification
- `git status` clean

---

## 22. Planned Files to Create

Documentation first:

```text
docs/modules/Ebook/CREATE_PLAN.md
```

After approval, anticipated application files include:

```text
Modules/Ebook/config/module.php
Modules/Ebook/config/ebook.php
Modules/Ebook/routes/web.php
Modules/Ebook/Http/Controllers/EbookController.php
Modules/Ebook/Models/EbookFolder.php
Modules/Ebook/Models/EbookDocument.php
Modules/Ebook/Models/EbookDocumentRecent.php        # conditional on approved recent strategy
Modules/Ebook/Services/EbookFolderService.php
Modules/Ebook/Services/EbookDocumentService.php
Modules/Ebook/Services/MarkdownService.php
Modules/Ebook/Services/EbookSearchService.php
Modules/Ebook/Services/EbookSyncService.php
Modules/Ebook/Livewire/...
Modules/Ebook/resources/views/...
Modules/Ebook/database/migrations/...
tests/Feature/Ebook/...
```

Potential dependency-file changes, only if approved and proven necessary:

```text
composer.json
composer.lock
package.json
package-lock.json
```

Admin menu/permission integration files may need targeted changes after inspecting the exact canonical integration path. They must not be modified broadly or copied blindly.

---

## 23. Suggested MR / Implementation Breakdown

### MR-1 — Skeleton + Bootstrap + Permissions

- `Modules/Ebook` skeleton
- manifest/config
- route shell/controller/page
- permission declarations/integration
- module discovery/runtime-state tests

No real CRUD yet.

### MR-2 — Database + Folder Domain

- folder migration/model/service
- folder hierarchy validation
- folder Livewire management
- focused tests

### MR-3 — Document Storage + CRUD

- documents migration/model
- storage/path abstraction
- document service
- upload/create/edit/delete/move
- hash/conflict behavior
- focused tests

### MR-4 — Markdown Viewer

- Markdown parser/sanitizer dependency decision
- MarkdownService
- viewer
- TOC/breadcrumb
- syntax highlight
- controlled image asset serving
- security tests

### MR-5 — Search + Favorites + Recent

- bounded metadata/content search
- favorite
- recent persistence after canonical Admin identity inspection
- tests

### MR-6 — Scan & Sync

- preview model/DTO/result shape
- conservative detection
- confirmed apply
- reconciliation/error handling
- tests

### MR-7 — UX + Regression + Docs

- responsive/dark/reading mode
- loading/empty/conflict states
- module regression
- full regression
- manual UI smoke
- Local VPS ownership verification
- final module docs

These are coherent delivery batches, not mandatory separate GitHub pull requests. They may remain on one feature branch and be committed/tested in batches according to `docs/GITHUB_COLLABORATION_WORKFLOW.md`.

---

## 24. Risks and Decisions Locked by This Plan

### Locked proposal pending user approval

1. Module type: `domain`.
2. No `nwidart`, no `module.json`, no parallel provider/registry.
3. Manifest dependencies start empty.
4. Runtime storage: private `storage/app/ebooks/` via Laravel Storage.
5. Folder slug unique within parent.
6. Document slug unique within folder.
7. Canonical document `file_path` globally unique.
8. Folder delete is blocked while non-empty; no recursive destructive delete in MVP.
9. Favorites are global in MVP via `ebook_documents.is_favorite`.
10. Recently Viewed is recommended per-admin via a dedicated bounded table.
11. `content_hash` is used for external-edit conflict detection and sync assistance.
12. Scan & Sync is preview-first and conservative; ambiguity never triggers automatic destructive changes.
13. Markdown content/images remain private behind authenticated/authorized access.
14. No `All` pagination by default unless a safe bounded cap is proven.
15. Local VPS is the first runtime/test target; Docker changes are deferred.

### Implementation-time verification, non-blocking for plan approval

1. Actual Admin guard model/key for Recent foreign key.
2. Existing permission/menu seeding integration path.
3. Installed/transitive Markdown package availability in lockfile.
4. Exact sanitizer package/config needed to satisfy XSS tests.
5. Existing frontend syntax-highlighting package availability.
6. Actual PHP-FPM and CLI ownership conventions on Local VPS.
7. Whether root-level documents (without folder) are needed; default implementation should require a folder unless current UI/product decision says otherwise.

No item above requires a new module architecture. If verification reveals a provider-level or cross-module architectural change, implementation must stop and request explicit approval.

---

## 25. Approval Gate

**STOP after this plan.**

Do not create:

- `Modules/Ebook/`
- migrations
- models
- services
- routes
- Livewire components
- application config
- dependency changes

until the user explicitly approves `docs/modules/Ebook/CREATE_PLAN.md`.

After approval, implementation starts with **MR-1 — Skeleton + Bootstrap + Permissions** and follows `docs/GITHUB_COLLABORATION_WORKFLOW.md`.