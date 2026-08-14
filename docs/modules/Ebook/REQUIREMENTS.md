# Ebook Module — REQUIREMENTS

## 1. Document Status

- Module: `Ebook`
- Module type: `domain`
- Status: approved requirements input for `/create-module Ebook`
- Source specification: `Ebook_Module_Specification.md`
- Repository architecture: first-party modular monolith under `Modules/`
- Authoritative bootstrap: `Modules/ModuleServiceProvider.php`
- Scope of first release: Phase 1 / MVP

This document records business and architecture requirements only. It does not authorize application-code implementation. The next task is `/create-module Ebook`, which must create `docs/modules/Ebook/CREATE_PLAN.md` and stop at its approval gate before application code is written.

## 2. Purpose

`Ebook` is an internal Markdown Knowledge Base / Help Center for Laravel Admin.

It must turn a collection of real Markdown (`.md`) files into a structured documentation system with folder navigation, professional reading UI, editing, upload, search, and filesystem synchronization.

The module is not merely a file manager or Markdown viewer.

Core ownership model:

```text
Markdown content -> filesystem
Metadata/structure -> MySQL
Business workflows -> Modules/Ebook/Services
UI state/interactions -> Modules/Ebook/Livewire
```

## 3. Confirmed Architectural Requirements

### 3.1 Repository module system

The project does **not** use `nwidart/laravel-modules`.

`Ebook` must integrate exclusively through the repository's existing `Modules\ModuleServiceProvider` contract.

Do not introduce:

- `module.json`
- nwidart infrastructure
- a second module registry
- custom duplicate module discovery
- manual global provider registration for resources already auto-discovered

### 3.2 Module boundary

All Ebook business/domain code belongs under:

```text
Modules/Ebook/
```

Do not place Ebook models/services/business workflows in `app/Models`, `app/Services`, `app/Http`, `Admin`, or unrelated modules.

`Admin` remains the presentation shell; `Ebook` owns the Ebook domain.

### 3.3 Preferred application flow

```text
Route
  -> Controller
  -> Page Blade
  -> Livewire PHP
  -> Livewire Blade
  -> Service
  -> Model
  -> Database
```

Filesystem/Markdown operations are also owned by Ebook services and must not be implemented directly in Blade or duplicated across Livewire components.

## 4. Actors / Roles

### Confirmed

- Authenticated Admin user: reads/searches/navigates Ebook content according to granted permissions.
- Authorized Ebook manager/editor: creates, uploads, edits, moves, deletes, and synchronizes Ebook content according to capability permissions.
- `Super Admin`: repository-wide `Gate::before` currently grants all abilities.

### No public actor in MVP

Ebook is an internal Admin feature. Public website routes and public API access are out of scope for MVP.

## 5. Content and Metadata Ownership

### 5.1 Filesystem is source of truth for Markdown content

Root:

```text
storage/app/ebooks/
```

Markdown body content remains in `.md` files and is not duplicated as full document content in MySQL for MVP.

All stored paths must be server-controlled, normalized relative paths below the Ebook root. Arbitrary absolute paths or paths escaping this root are forbidden.

### 5.2 Database is source of truth for metadata

Database owns document/folder metadata used for management, navigation, ordering, status and lookup.

### 5.3 Reconciliation rule

Because filesystem content and database metadata are separate persistence systems, mismatch is a valid state that `Scan & Sync` must detect and report.

A mismatch must not trigger silent destructive cleanup.

## 6. Main Business Workflows

### 6.1 Create Folder

```text
Authorized user
-> enter folder information
-> validate hierarchy/name/path
-> create physical folder when required
-> create/update metadata consistently
-> refresh navigation tree
```

The implementation must prevent invalid hierarchy operations such as making a folder its own descendant.

### 6.2 Create Document

```text
Authorized user
-> choose folder
-> enter title/description/Markdown/status/order
-> validate
-> derive safe filename/path
-> create .md file
-> create metadata
-> show document in navigation/viewer
```

### 6.3 Upload Markdown

```text
Select/drop .md
-> validate upload
-> resolve authorized destination folder
-> normalize destination path
-> store file
-> extract title from first H1 when available
-> otherwise derive title from filename
-> create metadata
-> document becomes readable
```

### 6.4 Edit Document

```text
Open document
-> edit metadata and/or Markdown
-> preview
-> validate
-> safe save
-> update filesystem + metadata
-> viewer reflects saved content
```

The service must not silently overwrite a conflicting destination or an externally changed file without an explicit safe-write policy in `CREATE_PLAN.md`.

### 6.5 Delete Document/Folder

Deletion is destructive and requires:

- backend authorization
- explicit confirmation
- precondition checks
- safe filesystem boundary validation
- clear failure reporting
- consistency/recovery strategy when filesystem and DB operations cannot both complete

Folder deletion must account for child folders/documents. Exact cascade/restrict UX is a technical design decision for `CREATE_PLAN.md`; no implicit recursive destructive delete is approved by this requirements document.

### 6.6 Scan & Sync

Required flow:

```text
Scan filesystem
-> compare with metadata
-> generate preview/report
-> user reviews
-> explicit Apply Sync
-> reconcile approved changes
-> return result/report
```

Detection scope:

- new folder
- missing/deleted folder
- changed/renamed folder
- new Markdown file
- missing/deleted Markdown file
- renamed Markdown file
- moved Markdown file
- changed path
- orphan metadata

Preview must distinguish at least:

```text
New
Changed
Missing/Deleted candidate
Moved/Renamed candidate
```

Rename/move detection must be conservative. When identity cannot be proven reliably, report ambiguity rather than automatically treating `missing + new` as a move and performing destructive changes.

### 6.7 Search

Search must cover:

1. title
2. filename
3. description
4. Markdown content

MVP search architecture:

- metadata: MySQL query through Ebook service
- content: bounded filesystem search through Ebook service

Results should provide title, folder context/path, relevant snippet and keyword highlighting where practical.

Do not introduce Elasticsearch, vector database or semantic/AI search in MVP.

## 7. Viewer / Help Center Requirements

Desktop layout:

```text
LEFT SIDEBAR | MARKDOWN CONTENT | TABLE OF CONTENTS
```

### Left

- hierarchical folder/document tree
- expand/collapse
- active document
- Favorites entry
- Recently Viewed entry
- responsive drawer/collapse behavior

### Center

- breadcrumb
- document title
- sanitized rendered Markdown
- readable technical-document typography
- code blocks and tables must not break responsive layout

### Right

- automatic Table of Contents generated from headings
- hierarchy reflecting heading levels
- stable heading anchors
- click-to-scroll
- URL hash may be updated
- collapsible/hidden on smaller screens

## 8. Markdown Requirements

Viewer must support at least:

- H1–H6
- paragraphs
- bold/italic
- inline code
- fenced code blocks
- ordered/unordered lists
- task/checkbox lists
- tables
- blockquotes
- links
- images
- horizontal rules
- stable heading anchors

Syntax highlighting should support common technical-document languages including PHP, Bash/Shell, JavaScript, TypeScript, HTML, CSS, SQL, JSON, YAML, Dockerfile, Nginx and Markdown.

MVP editor requirements:

- Markdown textarea/editor
- preview
- save
- validation
- loading/disabled state

Monaco/CodeMirror is not required in MVP.

## 9. Internal Links and Images

Relative Markdown images must be resolved safely under the Ebook storage boundary.

Example structure:

```text
storage/app/ebooks/Laravel/Livewire/upload.md
storage/app/ebooks/Laravel/Livewire/images/architecture.png
```

Path traversal outside Ebook root is forbidden.

The original specification also describes conversion of relative Markdown document links such as `../Architecture/service-layer.md` to Ebook document URLs. Full internal-link resolver behavior is classified as SHOULD HAVE / Phase 2. MVP architecture must not block this extension and must still render links safely.

## 10. Data Requirements

### 10.1 `ebook_folders`

Required concepts/columns:

```text
id
parent_id
name
slug
description
sort_order
is_active
created_at
updated_at
```

Relationships:

```text
parent
children
documents
```

Requirements:

- recursive hierarchy
- hierarchy cycle prevention
- indexes supporting parent/tree/order lookups
- appropriate FK/constraint behavior
- slug/path uniqueness rules must be defined in `CREATE_PLAN.md` from the chosen URL/path strategy

### 10.2 `ebook_documents`

Required concepts/columns:

```text
id
folder_id
title
slug
file_name
file_path
source_type
description
sort_order
is_active
created_at
updated_at
```

MVP:

```text
source_type = file
```

Requirements:

- `file_path` represents a controlled relative path under Ebook root
- database must prevent duplicate canonical document-path metadata
- indexes must support folder listing, active state, sorting and metadata search
- exact slug uniqueness scope is finalized in `CREATE_PLAN.md`

### 10.3 Favorites / Recent

Favorites and Recently Viewed are required MVP user-facing capabilities.

The original specification intentionally leaves simple/global versus per-admin persistence open. `CREATE_PLAN.md` must select the smallest repository-consistent persistence strategy without inventing a complex history/audit subsystem. If persistence is user-specific, it must use the canonical Admin/User ownership available in the repository rather than duplicate identity data.

## 11. Service Boundaries

### `EbookFolderService`

Owns:

- folder tree/navigation
- create/update/delete
- move/sort
- hierarchy validation
- safe folder-path coordination

### `EbookDocumentService`

Owns:

- create/update/delete
- move/sort
- metadata lookup/list/pagination
- canonical relative-path resolution
- safe coordination of filesystem + database mutations

### `MarkdownService`

Owns:

- read/parse/render Markdown
- sanitization
- title extraction
- heading extraction
- TOC generation
- stable anchors
- link normalization
- relative image handling
- code-block handling

### `EbookSearchService`

Owns:

- metadata search
- bounded content search
- ranking
- snippets/highlighting

### `EbookSyncService`

Owns:

- filesystem scan
- comparison/reconciliation planning
- new/missing/changed/move candidates
- preview report
- confirmed apply
- result report

Livewire must not duplicate these business responsibilities.

## 12. Permissions and Authorization

MVP requires capability-specific backend authorization in addition to `auth:admin`.

Proposed capability set to finalize in `CREATE_PLAN.md` using repository naming conventions:

```text
ebook.view
ebook.create
ebook.update
ebook.delete
ebook.upload
ebook.sync
```

If folder management requires a distinct capability after reference-module review, `ebook.manage_folders` may be separated in the plan.

Rules:

- hiding buttons is not authorization
- sensitive Livewire mutations must authorize server-side
- routes must use the Admin guard and applicable permission middleware/policy
- `Super Admin` behavior remains governed by the existing repository `Gate::before`

## 13. Routes / API / Public Web

### Admin web

Required Admin capability under `/admin/ebook`, including viewer, folder/document navigation, management and search entry points.

Exact route names and route decomposition belong in `CREATE_PLAN.md` and must follow existing module conventions.

### API

No dedicated Ebook API is required for MVP.

### Public web

No public Ebook frontend is required for MVP.

## 14. Bootstrap Contract

```text
Manifest          : config/module.php
Type              : domain
Dependencies      : minimal explicit list determined from canonical owners
Module Provider   : not required unless CREATE_PLAN identifies a special hook not handled by root provider
Config            : yes, when Ebook-specific server-controlled settings are required
Web routes        : yes
API routes        : no for MVP
Migrations        : yes
Livewire          : yes
Blade components  : reuse existing first; module-local only when justified
Console commands  : no requirement in MVP
Runtime state     : supported
Runtime storage   : yes — storage/app/ebooks/
```

The module must be discoverable and registered by `Modules\ModuleServiceProvider` without parallel bootstrap infrastructure.

## 15. Runtime-State Requirements

`Ebook` is a `domain` module, therefore it may be enabled/disabled through the repository runtime-state architecture.

Distinguish:

```text
Tracked manifest/default state
!=
Runtime enabled/disabled override
```

Runtime state is resolved through `ModuleStateRepository` / `ModuleStateResolver` and the configured state store.

Ebook business code must never read/write `storage/app/system/module-state.json` directly.

Runtime toggle must not modify tracked manifest source and must leave Git clean.

Dependencies declared by Ebook must be valid under effective runtime state.

## 16. Cross-Module Dependencies

No dependency should be added merely because Ebook is displayed inside Admin.

Confirmed architectural relationships:

- Admin provides the application shell/layout but does not own Ebook domain logic.
- Role/spatie permission infrastructure provides authorization conventions.
- Shared may be used only when an existing stable shared contract is genuinely needed.

`CREATE_PLAN.md` must inspect actual route/menu/permission integration and declare the **minimal** manifest `depends` list. Do not copy dependencies from Post, Admission, System or another module blindly.

## 17. Reference Modules / Conventions Reused

### Post

Relevant for:

- domain-module manifest pattern
- Admin route + permission middleware pattern
- service-owned pagination/query/mutation pattern

Do not copy Post's Category/User/Shared dependencies unless Ebook actually needs them.

### System

Relevant for:

- capability-style dotted permissions
- sensitive operation authorization
- runtime/module infrastructure awareness

Do not copy System's `shell` type or privileged system responsibilities.

### Admission

Relevant for:

- richer domain manifest conventions
- deterministic permission/table metadata patterns
- production-safe domain module organization

Do not copy queues/document-generation infrastructure unless Ebook later has a proven need.

## 18. Runtime Storage / Docker Requirements

Ebook requires persistent writable runtime storage:

```text
storage/app/ebooks/
```

Current Docker image/entrypoint already prepares and assigns `storage` to `www-data`, but `CREATE_PLAN.md` must explicitly verify that deployment volume persistence covers Ebook content.

Requirements:

- create/use storage through Laravel Storage abstractions where practical
- PHP-FPM (`www-data`) must be able to create/edit/delete Ebook files
- CLI operations run as root must not leave files unusable by `www-data`
- no `chmod 777`
- Ebook content must survive container replacement/redeploy when production deployment uses ephemeral containers
- asset/image serving must use a controlled strategy; do not expose arbitrary storage paths

## 19. Security Requirements

### Path traversal

Reject any operation that can resolve outside the Ebook root, including `..`, unsafe absolute paths, malformed normalized paths and unsafe symlink behavior.

### Markdown / XSS

- do not trust Markdown content
- raw dangerous HTML must not execute
- rendered HTML must be sanitized
- links and image sources require controlled handling
- do not expose raw exceptions to users

### Upload

- only `.md` for Markdown upload in MVP
- validate extension, MIME where reliable, size and destination
- filename/path must be server-controlled
- no silent overwrite

### Mutations

Create/update/delete/upload/sync require backend authorization, validation and loading/double-submit protection where applicable.

### Destructive operations

Delete and destructive sync decisions require confirmation and safe recovery/reconciliation behavior.

## 20. Filesystem + Database Consistency

There is no shared ACID transaction across MySQL and filesystem operations.

`CREATE_PLAN.md` must define operation ordering and compensation/recovery for at least:

- file write fails after DB work
- DB work fails after file write
- rename/move partially succeeds
- delete partially succeeds
- duplicate destination path
- external file modification during Admin editing
- sync racing with another mutation

Requirements:

1. validate before mutation
2. compute and validate canonical destination before write
3. use DB transactions for database portions where appropriate
4. do not silently overwrite
5. compensate/rollback when safely possible
6. return a clear failure state when compensation is incomplete
7. use Scan & Sync as reconciliation support, not automatic destructive cleanup

## 21. Pagination / Performance

Management lists must support bounded pagination.

Requested options:

```text
10
25
50
100
All
```

Default: `10`.

Repository Admin UI standards prohibit unbounded production datasets merely to implement `All`; therefore `All` is allowed only for a demonstrably bounded/small dataset. Otherwise the UI must omit/disable it or apply a documented safe cap.

Content search may scan Markdown files for small/medium libraries, but it must be bounded and structured so a future search backend can replace it without rewriting Livewire UI.

## 22. Responsive / Dark / Reading Mode

### Desktop

```text
Sidebar + Content + TOC
```

### Tablet

```text
Sidebar + Content
```

TOC may collapse.

### Mobile

```text
Content
```

Sidebar opens through a drawer/button.

Reading Mode hides Sidebar and TOC and expands content.

All Ebook UI must remain readable under the repository's current Admin dark-mode behavior, especially Markdown typography, tables, code blocks, editor, Sidebar and TOC.

## 23. MUST HAVE / SHOULD HAVE / FUTURE

### MUST HAVE — Phase 1 MVP

- first-party `Ebook` domain module integration
- Folder CRUD/tree
- Document CRUD
- `.md` upload
- Markdown edit + preview
- sanitized Markdown viewer
- Sidebar tree
- breadcrumb
- TOC
- syntax highlighting
- metadata + content search
- Scan & Sync preview/apply
- Favorites
- Recent Documents
- Reading Mode
- responsive UI
- dark-mode compatibility
- capability authorization
- path/storage security
- runtime-state compatibility
- Docker/persistence verification
- focused automated tests

### SHOULD HAVE — Phase 2

- Tags
- folder bulk import
- export document/folder/entire Ebook
- version history when Git alone is insufficient
- bookmark / continue reading enhancements
- drag/drop sorting
- full internal Markdown link resolver
- better editor
- keyboard shortcuts

### FUTURE — Phase 3

- AI Search
- semantic search
- AI Summary
- related/suggested documents
- automatic tags
- document analytics
- Elasticsearch/search engine only when scale proves necessary
- vector database only when an approved semantic-search requirement exists

## 24. Explicit Out of Scope — MVP

Do not introduce in the first implementation:

- nwidart/laravel-modules
- `module.json`
- public Ebook site
- dedicated Ebook API
- Elasticsearch
- vector database
- AI features
- Monaco Editor
- complex database versioning
- event sourcing
- microservice
- Repository Pattern solely for abstraction
- duplicate shared import/export infrastructure
- automatic destructive sync

## 25. Acceptance Criteria

### Primary workflow

```text
Create Folder
-> Upload/Create Markdown
-> Auto Extract Title
-> File stored in correct Ebook folder
-> Metadata created
-> Sidebar updates
-> Open document
-> Markdown renders safely
-> TOC generated
-> Search finds document
-> Edit Markdown
-> Save
-> Viewer reflects saved content
```

### External filesystem workflow

```text
Copy .md into storage/app/ebooks/
-> Scan & Sync
-> Preview differences
-> explicit Apply
-> metadata reconciled
-> document becomes readable
```

### Security/architecture acceptance

- module is discovered by `Modules\ModuleServiceProvider`
- no nwidart/module.json/parallel registry
- sensitive routes/actions enforce backend permissions
- traversal/outside-root paths are rejected
- dangerous Markdown HTML is sanitized
- invalid uploads are rejected
- filesystem/DB partial failure is handled explicitly
- runtime enable/disable uses repository state infrastructure and does not dirty Git
- Admin UI uses `Admin::layouts.master` where applicable
- responsive/dark/reading modes remain usable
- focused tests cover critical service, route, authorization, Markdown, path and sync behavior

## 26. Approved Decisions

1. Module name is `Ebook`.
2. Module type is `domain`.
3. Ebook is Admin-only in MVP.
4. Markdown files are the content source of truth.
5. MySQL stores metadata, not full Markdown body content in MVP.
6. Ebook root is `storage/app/ebooks/`.
7. Business workflows live in Ebook services.
8. Livewire is class-based and focused on UI state/orchestration.
9. Scan & Sync uses preview + explicit apply and is conservative about destructive changes.
10. Capability-specific backend permissions are required.
11. No dedicated API is required for MVP.
12. Runtime module state uses existing repository infrastructure.
13. No nwidart/module.json/parallel module bootstrap is allowed.
14. Phase 1 remains production-safe and intentionally avoids AI/search-engine over-engineering.

## 27. Remaining Non-Blocking Design Notes for CREATE_PLAN

These are implementation-design choices, not unresolved business requirements:

- exact manifest dependency list after menu/auth integration inspection
- exact route names/component decomposition
- Markdown parser + sanitizer + syntax-highlight package selection based on existing Composer/NPM dependencies and security posture
- slug uniqueness scope
- folder delete restrict/cascade UX, while destructive recursive deletion remains prohibited unless explicitly designed/confirmed
- Favorites/Recent persistence shape (minimal global/session vs per-admin) consistent with repository identity model
- controlled asset/image delivery strategy
- safe-write/concurrency mechanism for external edits
- conservative rename/move matching algorithm for sync
- safe behavior of pagination `All`

## 28. CREATE-MODULE READINESS

```text
Business requirements : READY
Module boundary       : READY
Bootstrap Contract    : READY
Dependencies          : READY (minimal list finalized in CREATE_PLAN)
Database              : READY
Permissions           : READY
Workflow              : READY
Runtime state         : READY
Docker/runtime storage: READY (persistence verification required in CREATE_PLAN)

Overall: READY FOR /create-module Ebook
```

No application source code, migrations, routes, models, services, Livewire components, providers, seeders, runtime module state or production data are created/modified by this requirements document.
