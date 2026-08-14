# Ebook Phase 1 — Final Hardening Addendum

## 1. Purpose

This document reconciles the approved Phase 1 requirements and implementation plan with the final implemented state on `agent/ebook-phase-1-viewer-polish`.

It is an addendum to:

```text
docs/modules/Ebook/REQUIREMENTS.md
docs/modules/Ebook/CREATE_PLAN.md
```

The original requirements and plan remain the historical approval baseline. This addendum records implementation decisions that were added or tightened during the final Phase 1 audit without changing the module architecture.

## 2. Requirement Reconciliation

### 2.1 Scan & Sync readability after Apply

The Phase 1 acceptance flow requires externally added Markdown content to become readable after explicit Scan & Sync Apply.

Final rule:

```text
Authenticated Admin applies a NEW Markdown file
-> EbookSyncService creates metadata
-> current Admin is attached through ebook_document_users
-> document is immediately visible to that Admin
```

This closes the edge case where a non-Super-Admin sync actor could successfully reconcile a document but remain unable to read it.

### 2.2 Document-level access boundary

Phase 1 capability permissions remain required. Final Hardening adds a second document-level visibility boundary for non-Super-Admin users.

```text
Super Admin
-> document assignment bypass

Other Admin
-> route/capability authorization
-> document must be assigned through ebook_document_users
```

This boundary applies not only to the Viewer/Search/navigation flow but also to the document management workspace. Management lists must use `EbookAccessService::visibleDocuments()`, and edit/update/delete/preview operations must re-authorize the target document.

The purpose is to prevent restricted document metadata from appearing to an unassigned reader merely because the user can enter `/admin/ebook`.

### 2.3 Syntax highlighting

`REQUIREMENTS.md` classifies syntax highlighting as Phase 1 MUST HAVE. Earlier implementation preserved `language-*` metadata but did not colorize syntax tokens.

Final Hardening closes that requirement through:

```text
Modules/Ebook/Services/SyntaxHighlighterService.php
```

`MarkdownService` passes fenced code through the highlighter after CommonMark safe rendering.

Supported Phase 1 language families include:

```text
PHP
Bash / Shell / sh
JavaScript
TypeScript
HTML
CSS
SQL
JSON
YAML / YML
Dockerfile
Nginx
Markdown
```

The implementation is dependency-free, server-rendered and presentation-only. Unknown languages fall back to safely escaped plain code. The highlighter does not replace the Markdown parser or sanitizer.

### 2.4 Manifest metadata

The implemented access model introduced:

```text
ebook_document_users
```

The final module manifest table contract therefore includes:

```text
ebook_folders
ebook_documents
ebook_document_recents
ebook_document_users
```

## 3. CREATE_PLAN Actual-State Addendum

The original implementation sequence MR-1 through MR-7 remains valid historically. Actual Phase 1 scope exceeded the initial UI plan through later polish work, including viewer fullscreen, document picker, previous/next navigation, scroll spy, code copy, image lightbox, source/split/preview editor modes and document-level reader assignment.

Final implementation status:

```text
MR-1 Skeleton + Bootstrap + Permissions        COMPLETE
MR-2 Database + Folder Domain                  COMPLETE
MR-3 Document Storage + CRUD                   COMPLETE
MR-4 Markdown Viewer                           COMPLETE
MR-5 Search + Favorites + Recent               COMPLETE
MR-6 Scan & Sync                               COMPLETE
MR-7 UX + Regression + Docs                    IMPLEMENTED
Phase 1 Final Hardening                        IMPLEMENTED
Final Local VPS regression                     PENDING
```

No new provider, public API, nwidart infrastructure, public storage exposure, semantic search or new module architecture was introduced by Final Hardening.

## 4. Files Changed by Final Hardening

Primary implementation files:

```text
Modules/Ebook/Services/EbookSyncService.php
Modules/Ebook/Services/EbookAccessService.php              # existing central access contract reused
Modules/Ebook/Livewire/Document/DocumentIndex.php
Modules/Ebook/Services/MarkdownService.php
Modules/Ebook/Services/SyntaxHighlighterService.php
Modules/Ebook/config/module.php
```

Focused test addition:

```text
tests/Feature/Ebook/EbookFinalHardeningTest.php
```

Documentation reconciliation:

```text
docs/modules/Ebook/README.md
docs/modules/Ebook/INFORMATION.md
docs/modules/Ebook/PHASE_1_FINAL_HARDENING.md
```

## 5. Acceptance Additions

Before Phase 1 is merged, verify these additional scenarios:

### Sync actor access

```text
Non-Super-Admin with ebook.view + ebook.sync
-> copy new .md into storage/app/ebooks/
-> Scan
-> Apply
-> document metadata created
-> user can immediately open the document
```

### Management access

```text
User assigned Document A only
-> management document list shows A
-> restricted Document B is not listed
-> direct edit/update/delete attempt for B is denied/not found
```

### Syntax highlighting

```text
Open fenced PHP/JS/SQL/JSON example
-> language label remains correct
-> keywords/strings/numbers/comments render as distinct syntax tokens
-> Copy Code still returns original plain code text
-> unknown language remains safely readable
```

## 6. Test Gate

Run on Local VPS after pulling Final Hardening:

```bash
php artisan test tests/Feature/Ebook
php artisan test tests/Feature/System/ModuleBootstrapRuntimeStateTest.php
php artisan test
npm run build
git status
```

A new passing test/assertion count must be recorded only after these commands actually run. Historical counts are not the Final Hardening gate.

## 7. Phase 1 Closure Rule

Phase 1 may be considered CLOSED only after:

- focused Ebook regression passes
- System runtime/bootstrap regression passes
- full project regression passes
- syntax highlighting is manually smoke-tested
- non-Super-Admin Sync actor can read newly imported content
- Local VPS storage ownership/write verification passes
- Git working tree is clean
- branch diff is reviewed before merge to `main`

After that point, new product work should be specified as Ebook Phase 2 rather than extending Phase 1 further.
