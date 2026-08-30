# Admin Collaboration Handoff

## Current checkpoint

Task: **Admin Major Refactor — Slice 7: Post/content Legacy Ownership Cleanup**

Status: **VERIFIED — PR READY**

Branch/checkpoint: `refactor/admin-post-content-ownership`

Slice 7 was explicitly approved after the previous Admin ownership cleanup work had been merged. The user also supplied the active `/admin/posts` and `/admin/posts/create` UI as runtime evidence and approved a professional UI refactor while preserving capability behavior.

## Ownership decision

`Modules/Post` is the canonical owner of Post/content behavior.

Preserved contracts:

- `/admin/posts`, `/admin/posts/create`, and `/admin/posts/{id}/edit` remain unchanged;
- `admin.posts.index`, `admin.posts.create`, and `admin.posts.edit` remain Post-owned;
- the active controller is `Modules\\Post\\Http\\Controllers\\PostController`;
- canonical pages mount `post.posts.post-table` and `post.posts.post-form`;
- `Modules/Post/Models/Post.php`, `Modules/Post/Models/Tag.php`, `PostService`, `ImportExport`, Post migrations and Post permissions remain canonical;
- `wp_posts`, tag/pivot/category-post schema and existing production data remain unchanged;
- Post remains enabled as a domain module with `User`, `Category`, and `Shared` dependencies.

The earlier assumption that Post might not exist as a module was corrected by direct runtime/tree proof: `Modules/Post` is present and owns the active Post runtime. The `/admin/posts` UI supplied by the user is therefore Post-owned, while the similarly named Admin files were legacy duplicates.

## Removed legacy Admin Post runtime

The following proven duplicate files were removed:

- `Modules/Admin/Http/Controllers/PostController.php`
- `Modules/Admin/Livewire/Posts/PostForm.php`
- `Modules/Admin/Livewire/Posts/PostTable.php`
- `Modules/Admin/resources/views/livewire/posts/post-form.blade.php`
- `Modules/Admin/resources/views/livewire/posts/post-table.blade.php`
- `Modules/Admin/resources/views/pages/posts/index.blade.php`
- `Modules/Admin/resources/views/pages/posts/create.blade.php`
- `Modules/Admin/resources/views/pages/posts/edit.blade.php`

Canonical equivalents remain in `Modules/Post`.

## Canonical Post architecture retained

The canonical Post runtime already provides a substantially safer boundary than the removed Admin duplicate:

- `PostForm` authorizes `create_post` or `edit_post` and delegates persistence to `PostService`;
- `PostTable` authorizes view/create/delete capabilities and delegates query, clone and delete behavior to `PostService`;
- import/export uses `Modules\\Post\\Services\\ImportExport` and the shared import/export panel;
- schema ownership remains in Post migrations;
- category validation remains constrained to `type = post`;
- tag persistence remains Post-owned.

No schema rewrite or destructive migration was introduced.

## Post list UI refactor

The Post management list was retained as a desktop/tablet management workspace but aligned with `.codex/standards/ADMIN_UI_STANDARD.md`:

- explicit bordered search/filter controls instead of visually ambiguous transparent controls;
- search covers title or slug;
- category/status filters remain visible;
- bounded `10/25/50/100` per-page options;
- reset-filters action when filters are active;
- mutation buttons expose Livewire loading/disabled states;
- bulk selection remains scoped to the current page;
- table remains horizontally safe on smaller widths;
- Post-scoped pagination prevents global theme pagination from overriding the approved Admin treatment.

The Post pagination visual contract is:

- inactive page controls: white background with neutral border/text;
- active page: indigo-600 with white text;
- Previous/Next: white background;
- disabled controls visibly neutral/disabled;
- no unbounded `all` option.

## Post Create/Edit editor workspace

The existing editor-first layout was preserved rather than converted into a generic CRUD form. Duplicate wrapper headings in the Post page views were removed so the Livewire editor owns the page hierarchy.

The workspace retains:

- title/slug;
- summary/content editors;
- SEO metadata;
- publication status and featured flag;
- category selection;
- tags;
- featured image upload;
- capability authorization and save loading states.

## Category tree UI improvement

User feedback requested that Post `Chuyên mục` match Product `Phân loại`.

The Post editor now reuses the shared Admin recursive category selector already used by Product:

- only root `type = post` categories are loaded initially;
- descendants are loaded through `childrenRecursive`;
- parent categories are collapsed by default and show `+`;
- clicking expands the branch and changes the control to `−`;
- child levels are visually indented;
- leaf categories do not display a fake expand control;
- on Edit, a branch containing an already-selected descendant automatically opens so the current selection is visible;
- scroll behavior remains bounded.

The user manually confirmed this UI as **PASS**.

## Authorization assessment

Post capability vocabulary remains:

- `view_post`
- `create_post`
- `edit_post`
- `delete_post`

The cleanup removes the weaker duplicate Admin implementation and preserves the canonical Post authorization checks. No authorization weakening was introduced.

## Runtime / schema impact

Route URL/name change: **NONE**

Canonical owner change: **DOCUMENTED, not runtime-breaking — Post was already active owner**

Authentication/authorization weakening: **NONE**

Post schema/table/migration change: **NONE**

Production Post data mutation: **NONE**

Category/tag schema change: **NONE**

Post import/export contract: **PRESERVED IN CANONICAL POST SERVICE**

P0 database administration quarantine: **UNCHANGED**

## Verification completed

Focused ownership/UI contract verification completed successfully:

```text
AdminPostOwnershipCleanupContractTest: 7 passed, 58 assertions
PostRouteConfigurationTest + AdminOwnershipBoundaryContractTest: 7 passed, 32 assertions
admin.posts route list: PASS — 3 canonical Modules\\Post\\Http\\Controllers\\PostController routes
Vite production build: PASS — 34 modules transformed, built in 4.72s
Manual Post category-tree UI: PASS
```

The focused contract protects:

- canonical Post route/controller ownership;
- canonical Post page Livewire aliases;
- Post model/service/schema ownership;
- absence of the eight legacy Admin Post runtime files;
- capability authorization/service boundaries;
- bounded Post pagination and scoped pagination view;
- visible/resettable list filters;
- recursive Post category tree using the shared Admin selector.

No full-project regression was required for this ownership slice.

## Acceptance criteria

- canonical Post ownership confirmed: **VERIFIED**;
- eight proven Admin Post duplicates absent: **VERIFIED**;
- route names/URLs preserved: **VERIFIED — 3 canonical Post routes**;
- capability authorization preserved: **VERIFIED**;
- Post service/import-export boundary preserved: **VERIFIED**;
- bounded scoped pagination: **VERIFIED BY CONTRACT**;
- Post recursive category tree with default `+`: **UI PASS**;
- schema/migration/data changes: **NONE**;
- P0 database quarantine: **UNCHANGED**;
- final impacted regression/build: **PASS**;
- PR readiness: **READY**.

## Material risks still open

### P0

`Modules/Admin/Services/DatabaseService.php` remains quarantined and must stay unreachable.

### Remaining Admin legacy families

Customer/address, roles/staff, marketing/public-site, Affiliate/promotion and system/environment remain separate ownership/reachability candidates.

Production migration-ledger/table ownership for unrelated Admin legacy families remains unresolved and out of scope.

## Next phase

Slice 7 is closed out and PR-ready. Do not select or implement the next Admin legacy family until this branch is merged and the user explicitly authorizes the next scope.
