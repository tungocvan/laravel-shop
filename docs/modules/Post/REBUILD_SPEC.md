# Post Rebuild Specification

Status: implementation specification only. No application code is included in this document.

Source documents:

- `docs/modules/Post/ANALYSIS.md`
- `docs/modules/Post/REFACTOR_PLAN.md`
- `docs/AI_PROJECT_CONTEXT.md`
- `docs/CODEX_BOOTSTRAP.md`
- `ROADMAP.md`

Reference notation:

- `Analysis §N` means section N of `docs/modules/Post/ANALYSIS.md`.
- `Refactor P0-N`, `P1-N`, or `P2-N` means the corresponding item in `docs/modules/Post/REFACTOR_PLAN.md`.

Every design decision below is tied to an issue in the analysis or refactor plan. Any item marked **Needs confirmation before coding** must be resolved before implementation starts for that area.

## 1. Goal

The rebuilt/refactored `Post` module must:

1. Make `Modules/Post` the canonical owner of admin post management, while migrating or wrapping duplicate Admin implementations only after caller tests prove safe behavior. `[Analysis §14; Refactor P1-03]`
2. Restore the required architecture flow by moving queries, persistence, transactions, clone/delete behavior, import/export orchestration, and thumbnail lifecycle rules out of Livewire and into Services. `[Analysis §5, §7, §12; Refactor P0-06, P1-01, P1-07, P1-16]`
3. Enforce named permissions on routes and every Livewire action, including direct Livewire calls, selected IDs, export, import, clone, delete, create, and update. `[Analysis §10; Refactor P0-02, P0-03, P0-04, P0-05]`
4. Remove or secure the broken unauthenticated API route before any public Post API is implemented. `[Analysis §2, §3, §10; Refactor P0-01, P2-07]`
5. Keep post create/update operations transactionally consistent across the `wp_posts`, `category_post`, and `wp_post_tag` writes. `[Analysis §12; Refactor P0-06]`
6. Replace local JSON import/export behavior with the shared import/export foundation if import/export remains a requirement. **Needs confirmation before coding:** supported file format, unique key, duplicate mode, dry-run behavior, null-overwrite policy, and row error contract. `[Analysis §9, §11; Refactor P1-02, P1-06]`
7. Bound list, export, parent/category option, and selection behavior for production-sized datasets. `[Analysis §13; Refactor P1-08, P1-09, P1-10]`
8. Resolve model ownership so Post does not own Product, Review, Wishlist, or product-related Category behavior. **Needs confirmation before coding:** canonical owners for `categories`, `wp_tags`, and Website read models. `[Analysis §8, §14, §15; Refactor P1-03, P1-13, P1-14]`
9. Repair migration hygiene only through a coordinated repository migration plan, not an isolated Post-only rename. `[Analysis §8, §15; Refactor P1-15]`
10. Add route, authorization, Livewire, service, import/export, model, migration, and architecture tests before broad duplicate removal. `[Refactor P1-17]`

## 2. Target Architecture

Required flow:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import
→ Export
→ Model
→ Migration

Normal admin CRUD target flow:

1. `Modules/Post/routes/web.php`
2. `Modules/Post/Http/Controllers/PostController.php`
3. `Modules/Post/resources/views/pages/posts/index.blade.php`, `create.blade.php`, or `edit.blade.php`
4. `Modules/Post/Livewire/Posts/PostTable.php` or `Modules/Post/Livewire/Posts/PostForm.php`
5. `Modules/Post/resources/views/livewire/posts/post-table.blade.php` or `post-form.blade.php`
6. Shared components such as `x-editor` and future shared import/export panel
7. `Modules/Post/Services/PostService.php`
8. Import/export stages skipped for normal CRUD
9. `Modules/Post/Models/Post.php` and approved canonical Category/Tag models
10. `wp_posts`, `category_post`, `wp_post_tag`, and confirmed related tables

Import/export target flow if approved:

1. Page Blade or Livewire table mounts `shared.import-export.panel`.
2. Panel receives `Modules\Post\Services\ImportExport`.
3. `Modules/Post/Services/ImportExport.php` orchestrates module mapping and delegates to shared foundation.
4. Optional `Modules/Post/Import/*` and `Modules/Post/Export/*` classes exist only if the service grows beyond a simple implementation.
5. Import persistence reuses `Modules/Post/Services/PostService.php` invariants.
6. Export queries use bounded iteration and eager loading.

Design decisions:

- Controllers stay thin and return views or scalar IDs only. `[Analysis §3; Refactor P1-01]`
- Livewire owns UI state, validation, confirmation state, and calls to Services only. `[Analysis §5, §7; Refactor P1-01]`
- Services own all Eloquent queries, persistence, transactions, import/export orchestration, thumbnail lifecycle, search/filter/sort/pagination, clone, delete, and bulk delete. `[Analysis §7, §12, §13; Refactor P0-06, P1-01, P1-07, P1-08, P1-09, P1-16]`
- Models contain ORM configuration, relationships, casts, scopes, and non-UI accessors only. `[Analysis §8; Refactor P1-11, P1-12]`
- Shared import/export uses `Modules/Shared/Services/ImportExport`; Post must not duplicate shared file validation, normalization, report, or storage logic. `[Analysis §9; Refactor P1-02, P1-06]`

## 3. Database Design

### Tables

#### `wp_posts`

Source migration: `Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php`

Columns:

- `id`: primary key.
- `name`: post title.
- `slug`: unique URL slug.
- `summary`: nullable short summary.
- `content`: nullable long content.
- `thumbnail`: nullable local storage path or external URL.
- `is_featured`: boolean, default false.
- `status`: string, default `published`; allowed values must be confirmed as `published`, `draft`, `hidden` based on current UI. `[Analysis §5, §11; Refactor P1-04]`
- `views`: integer, default 0.
- `user_id`: nullable foreign key to `users`.
- `published_at`: nullable timestamp.
- `meta_title`: nullable SEO title.
- `meta_description`: nullable SEO description.
- `created_at`, `updated_at`, `deleted_at`.

Indexes and constraints:

- Primary key on `id`.
- Unique index on `slug`.
- Foreign key `user_id` references `users.id` with null-on-delete.
- Soft delete column exists and must be respected by service queries. `[Analysis §8; Refactor P1-01]`

Design decisions:

- Keep `slug` unique globally unless public URL behavior approves scoped slugs. **Needs confirmation before coding if slug policy changes.** `[Analysis §11; Refactor P1-05]`
- Service validation must duplicate important DB constraints before writes to return field-level errors instead of raw database errors. `[Analysis §11; Refactor P1-04, P1-05]`

#### `category_post`

Source migration: `Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php`

Columns:

- `category_id`
- `post_id`

Indexes, foreign keys, constraints:

- Composite primary key on `category_id`, `post_id`.
- `category_id` references `categories.id` with cascade delete.
- `post_id` references `wp_posts.id` with cascade delete.

Design decisions:

- `PostService` must validate that selected category IDs are valid post categories before syncing. `[Analysis §11; Refactor P1-04]`
- `categories` table ownership is likely `Modules/Category`, not `Modules/Post`. **Needs confirmation before coding.** `[Analysis §8, §14; Refactor P1-14]`

#### `wp_post_tag`

Source migration: `Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php`

Columns:

- `post_id`
- `tag_id`

Indexes, foreign keys, constraints:

- Composite primary key on `post_id`, `tag_id`.
- `post_id` references `wp_posts.id` with cascade delete.
- `tag_id` references `wp_tags.id` with cascade delete.

Design decisions:

- `PostService` owns tag parsing, trimming, deduplication, slug generation, creation, and sync. `[Analysis §5, §11, §12; Refactor P0-06, P1-04]`
- `wp_tags` table ownership is unclear because Post has a `Tag` model but no local `wp_tags` migration. **Needs confirmation before coding.** `[Analysis §8; Refactor P1-14]`

#### `post_meta`

Source migration: `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php`

Columns:

- `id`
- `key`: unique string.
- `value`: nullable text.
- `group_name`: string, default `general`.
- `type`: string, default `text`.
- `label`: nullable string.
- `created_at`, `updated_at`.

Design decisions:

- Do not build PostMeta feature code until table ownership and requirements are confirmed. **Needs confirmation before coding.** `[Analysis §15; Refactor P2-06]`
- Do not remove the migration until its intended owner or deprecation path is confirmed. `[Refactor P2-06]`

### Migration Notes

- Negative-year migration filenames must be repaired only as part of the repository migration hygiene task. Do not rename them in an isolated Post refactor. `[Analysis §15; Refactor P1-15]`
- Pivot migrations must run after referenced tables: `wp_posts`, `wp_tags`, and `categories`. `[Refactor P1-15]`
- Add migration smoke tests for fresh install ordering and optional `post_meta` decision before migration cleanup. `[Refactor P1-15, P2-06, P1-17]`
- New schema changes must be forward-only corrective migrations for deployed environments. `[ROADMAP P1-08; Refactor P1-15]`

## 4. Model Design

### `Modules\Post\Models\Post`

Fillable fields:

- `name`
- `slug`
- `summary`
- `content`
- `thumbnail`
- `is_featured`
- `status`
- `views`
- `user_id`
- `published_at`
- `meta_title`
- `meta_description`

Casts:

- `is_featured` as boolean.
- `published_at` as datetime.
- `views` as integer should be added if not already present. `[Analysis §8; Refactor P1-11]`

Relationships:

- `categories()`: many-to-many through `category_post`.
- `tags()`: many-to-many through `wp_post_tag`.
- `author()`: belongs to `App\Models\User` using `user_id`.
- `user()`: duplicate alias currently exists; choose one canonical relationship, preferably `author()`, and retain `user()` only if callers still require it. **Needs confirmation before removing alias.** `[Analysis §8; Refactor P1-12]`

Scopes:

- `published()`: status is `published`; useful for service/public reads if Post becomes canonical.
- `draft()`, `hidden()`, `featured()` only if callers need them. **Needs confirmation before coding.** `[Analysis §8; Refactor P1-01]`

Accessors/mutators:

- Remove `getStatusBadgeAttribute()` because it returns HTML. Status badge presentation belongs in Blade or a shared component. `[Analysis §8; Refactor P1-11]`
- Thumbnail URL accessor is optional. **Needs confirmation before coding:** whether local/external thumbnail rendering should live in a view helper, component, or model accessor. `[Analysis §6; Refactor P1-16]`

### `Modules\Post\Models\Tag`

Current table: `wp_tags`

Fillable fields:

- `name`
- `slug`

Casts:

- Add explicit casts only for fields that need typed behavior; timestamps do not require casts unless the project standard wants explicitness. `[Refactor P2-05]`

Relationships:

- `posts()`: many-to-many through `wp_post_tag`.

Ownership decision:

- `wp_tags` has no migration in `Modules/Post`. Confirm whether Post owns tag persistence or whether Website/Shared taxonomy owns it. **Needs confirmation before coding.** `[Analysis §8; Refactor P1-14]`

### `Modules\Post\Models\Category`

Current table by convention: `categories`

Design decision:

- Prefer using canonical `Modules\Category\Models\Category` once Category ownership is confirmed. **Needs confirmation before coding.** `[Analysis §8, §14; Refactor P1-14]`
- If temporarily retained, remove product relationship concerns and declare explicit table metadata. `[Analysis §8; Refactor P1-14, P2-05]`

### Non-Post Models To Remove After Audit

The following should not remain Post domain models after caller migration:

- `Modules/Post/Models/Product.php`
- `Modules/Post/Models/Review.php`
- `Modules/Post/Models/Wishlist.php`

Decision:

- Remove only after caller audit and architecture tests prove no Post dependency remains. `[Analysis §8, §15; Refactor P1-13]`

## 5. Service Design

### `Modules\Post\Services\PostService`

Responsibilities:

- Query posts for table/list views.
- Load a post for edit.
- Load post category options from canonical Category source.
- Create and update posts.
- Normalize post data before persistence.
- Validate service-level invariants that non-Livewire callers must also obey.
- Sync categories.
- Normalize/create/sync tags.
- Delete and bulk delete posts.
- Clone posts.
- Own transactions.
- Own thumbnail replacement cleanup after successful persistence.

Public methods:

- `paginate(array $filters, int|string $perPage)`: returns paginated posts or a guarded collection for approved `All`. `[Analysis §7, §13; Refactor P1-01, P1-09]`
- `findForEdit(int $id)`: loads post with categories and tags for form state. `[Analysis §5; Refactor P1-01]`
- `categoryOptions(): Collection|array`: returns valid active post categories. `[Analysis §13; Refactor P1-10]`
- `create(array $data)`: creates post with categories/tags transactionally. `[Analysis §12; Refactor P0-06]`
- `update(int $id, array $data)`: updates post with categories/tags transactionally. `[Analysis §12; Refactor P0-06]`
- `delete(int $id)`: safely deletes one post, handling not found and authorization scope. `[Analysis §12; Refactor P1-07]`
- `bulkDelete(array $ids)`: reloads IDs server-side, authorizes/scope-validates, then deletes. `[Analysis §10, §12; Refactor P0-05, P1-07]`
- `clone(int $id)`: clones post and intended relationships in a transaction. `[Analysis §12; Refactor P1-07]`
- `normalizeTags(string|array|null $input)`: trims, deduplicates, validates, and prepares tag data. `[Analysis §11; Refactor P1-04]`

Transaction boundaries:

- `create()` and `update()` include post write, category sync, tag first-or-create, and tag sync. `[Analysis §12; Refactor P0-06]`
- `clone()` includes replicated post write and relation sync. `[Analysis §12; Refactor P1-07]`
- `bulkDelete()` includes validated delete set and any audit/cleanup behavior. `[Analysis §12; Refactor P0-05, P1-07]`
- Thumbnail cleanup happens only after successful database commit; failed saves must not delete the existing thumbnail. `[Refactor P1-16]`

Business rules:

- Status must be one of `published`, `draft`, `hidden` unless a different enum is approved. `[Analysis §11; Refactor P1-04]`
- Slug uniqueness remains global unless approved otherwise. **Needs confirmation before coding if changed.** `[Analysis §11; Refactor P1-05]`
- Categories must be valid post categories from canonical Category owner. **Needs confirmation before coding for canonical model path.** `[Analysis §11; Refactor P1-14]`
- Tags must be bounded by length/count and slug-collision behavior. **Needs confirmation before coding for exact limits.** `[Analysis §11; Refactor P1-04]`
- `published_at` behavior currently sets only on create. Preserve unless business approves publish-state transitions. **Needs confirmation before coding if behavior changes.** `[Analysis §5; Refactor P1-01]`

### `Modules\Post\Services\ImportExport`

Responsibilities:

- Module entry point for shared import/export v1.5.
- Define model class, headers, rules, aliases, normalization, duplicate mode, export mapping, and template sample row.
- Delegate generic file validation, header mapping, reporting, storage, and loops to `Modules/Shared/Services/ImportExport`.
- Use `PostService` for persistence so import respects the same domain rules as the UI.

Public methods:

- Follow the shared base service contract already used by the project. `[AI_PROJECT_CONTEXT Import Export Standard; Refactor P1-02]`

Confirmation gates:

- Supported format is currently JSON in legacy UI, while project standard expects Excel-style import/export. **Needs confirmation before coding.** `[Analysis §9; Refactor P1-02]`
- Unique key, duplicate mode, dry-run default, null-overwrite behavior, and partial/all-or-nothing behavior must be confirmed. **Needs confirmation before coding.** `[Analysis §9, §11; Refactor P1-02, P1-06]`

## 6. Livewire Design

### Component List

- `Modules/Post/Livewire/Posts/PostTable.php`
- `Modules/Post/Livewire/Posts/PostForm.php`

### `PostTable` State

State properties:

- `search`
- `filterCategory`
- `filterStatus`
- `selected`
- `selectAll`
- `perPage`
- `perPageOptions = [10, 25, 50, 100, 'All']`
- Import/export panel state should move to `shared.import-export.panel` if import/export remains. `[Analysis §6, §9; Refactor P1-02]`

Validation rules:

- `selected` must be an array of integer IDs before bulk actions. `[Analysis §10; Refactor P0-05]`
- `filterCategory` must be empty or a valid post category ID. `[Analysis §11; Refactor P1-04]`
- `filterStatus` must be empty or one of approved statuses. `[Analysis §11; Refactor P1-04]`

Events:

- Reset selection when filters, search, pagination, or per-page state changes. `[Analysis §13; Refactor P1-09]`
- Optional event to refresh table after import panel completes. **Needs confirmation before coding with shared panel behavior.** `[Refactor P1-02]`

Pagination/search/filter/sort:

- Search by post name through `PostService::paginate()`. `[Analysis §13; Refactor P1-01, P1-09]`
- Filter by status and category through service queries. `[Analysis §5, §13; Refactor P1-01]`
- Default per page is 10.
- `All` must be guarded, capped, or disabled for large datasets. `[AI_PROJECT_CONTEXT Pagination; Refactor P1-09]`
- Select-all must select only current page rows unless a separate all-filtered mode is explicitly implemented and labeled. `[Analysis §13; Refactor P1-09]`

### `PostForm` State

State properties:

- `postId`
- `isEdit`
- `name`
- `slug`
- `summary`
- `content`
- `thumbnail`
- `new_thumbnail`
- `status`
- `is_featured`
- `meta_title`
- `meta_description`
- `selectedCategories`
- `inputTags`

Validation rules:

- `name`: required, string, max length.
- `slug`: required, slug format, max length, unique with current ID ignored.
- `summary`: nullable string with approved length. **Needs confirmation before coding for max length.** `[Analysis §11; Refactor P1-04]`
- `content`: nullable or required according to approved publishing rules. **Needs confirmation before coding.** `[Analysis §11; Refactor P1-04]`
- `status`: required enum `published`, `draft`, `hidden`.
- `is_featured`: boolean.
- `meta_title`: nullable string max 255 or approved SEO limit.
- `meta_description`: nullable string max approved length.
- `selectedCategories`: array of valid active post category IDs.
- `inputTags`: bounded string or array with approved tag count/length. **Needs confirmation before coding.** `[Analysis §11; Refactor P1-04]`
- `new_thumbnail`: nullable image with explicit MIME and size rules; current max is 2048 KB and should be confirmed. **Needs confirmation before coding if changed.** `[Analysis §5; Refactor P1-16]`

Events:

- Auto-generate slug from name only for create or when slug is empty. Preserve current behavior unless approved otherwise. `[Analysis §5; Refactor P1-01]`
- Auto-fill meta title/description from name/summary only when target fields are empty. `[Analysis §5; Refactor P1-01]`

Action protection:

- `mount()` and `save()` authorize create/edit action server-side. `[Analysis §10; Refactor P0-03]`
- Livewire passes validated arrays/scalars to services only. `[Analysis §7; Refactor P1-01]`

## 7. Blade/UI Design

### Page Blade Files

- `Modules/Post/resources/views/pages/posts/index.blade.php`: shell only; extends `Admin::layouts.master`, defines title, mounts `post.posts.post-table`. `[Analysis §4]`
- `Modules/Post/resources/views/pages/posts/create.blade.php`: shell only; remove inline CSS; mounts `post.posts.post-form`. `[Analysis §4; Refactor P2-02]`
- `Modules/Post/resources/views/pages/posts/edit.blade.php`: shell only; passes scalar ID to Livewire. `[Analysis §4]`

Scaffold pages:

- `Modules/Post/resources/views/post.blade.php`
- `Modules/Post/resources/views/pages/index.blade.php`

Remove only after route/view caller verification. `[Analysis §15; Refactor P2-01]`

### Livewire Blade Files

- `Modules/Post/resources/views/livewire/posts/post-table.blade.php`
- `Modules/Post/resources/views/livewire/posts/post-form.blade.php`

Unused placeholder:

- `Modules/Post/resources/views/livewire/placeholder.blade.php`: remove only after caller verification. `[Analysis §15; Refactor P2-01]`

### Shared Components

- Continue using `x-editor` if it remains the canonical editor component. **Needs confirmation before coding:** editor binding behavior may justify exceptions to `wire:model.live`. `[Analysis §6; Refactor P2-03]`
- Replace local import/export UI with `shared.import-export.panel` if import/export remains required. `[Analysis §6, §9; Refactor P1-02]`
- Consider a status badge component or Blade partial to replace model HTML accessor. `[Analysis §8; Refactor P1-11]`

### AdminLTE/Bootstrap Layout Rules

- New/refactored UI follows Tailwind CSS 4 and `Admin::layouts.master`.
- Do not introduce Bootstrap or jQuery patterns into Post refactor work. `[AI_PROJECT_CONTEXT Governing Stack; Refactor Risk Control]`
- Existing repository inventory mentioning Bootstrap/AdminLTE is not the target standard. `[CODEX_BOOTSTRAP Known conflict resolutions]`

### Table Design

- Use responsive table wrapper with `overflow-x-auto`. `[Analysis §6; Refactor P2-04]`
- Show permission-aware actions, but do not rely on hidden buttons for security. `[Analysis §10; Refactor P0-04]`
- Add delete/bulk delete confirmation.
- Add empty state, loading state, disabled states for export/import/delete/clone.
- Render categories and author from eager-loaded data. `[Analysis §13; Refactor P1-08]`
- Select-all label must match scope: current page only unless all-filtered selection is explicitly supported. `[Analysis §13; Refactor P1-09]`

### Form Design

- Show field-level validation errors for every editable field. `[Analysis §11; Refactor P1-04, P2-03]`
- Use `wire:model.live` by default, with documented exceptions for editor integrations. `[Analysis §6; Refactor P2-03]`
- Keep thumbnail preview behavior, but thumbnail lifecycle is service-owned. `[Refactor P1-16]`
- Remove inline CSS from create page. `[Analysis §4; Refactor P2-02]`

## 8. Import Design

### Current Decision

Import currently exists as JSON upload in `Modules/Post/Livewire/Posts/PostTable.php`, but it violates the shared import/export architecture. `[Analysis §9; Refactor P1-02]`

Decision:

- Do not implement import until supported format and behavior contract are confirmed. **Needs confirmation before coding.** `[Refactor Risk Control]`

### Target Import Classes

Required if import remains:

- `Modules/Post/Services/ImportExport.php`

Optional if complexity requires:

- `Modules/Post/Import/PostImport.php`
- `Modules/Post/Import/RowMapper.php`
- `Modules/Post/Import/RowNormalizer.php`
- `Modules/Post/Import/RowValidator.php`

### Header Mapping

Provisional headers if spreadsheet import is approved:

- `name`
- `slug`
- `summary`
- `content`
- `status`
- `is_featured`
- `thumbnail`
- `categories`
- `tags`
- `meta_title`
- `meta_description`

Needs confirmation before coding:

- Vietnamese aliases.
- Whether JSON import remains supported.
- Whether categories/tags are names, slugs, IDs, or separate sheets. `[Analysis §9, §11; Refactor P1-02, P1-06]`

### Column Mapping

- No positional A/B/C mapping is approved.
- If spreadsheets lack stable headers, column mapping must be confirmed before coding. `[AI_PROJECT_CONTEXT Import Export Standard]`

### Row Normalization

Normalize:

- Trim strings.
- Convert empty strings to null where allowed.
- Normalize status values to approved enum.
- Normalize boolean values for `is_featured`.
- Normalize category/tag lists from confirmed separators or structured arrays.
- Normalize thumbnail as URL or local path according to approved policy. **Needs confirmation before coding.** `[Analysis §11; Refactor P1-06]`

### Row Validation

Validate after mapping and normalization:

- Required `name`.
- Slug format and uniqueness/duplicate mode.
- Status enum.
- Category references.
- Tag count, length, and slug safety.
- String lengths for SEO/content fields.
- Thumbnail URL/path policy.

### Duplicate Handling

Needs confirmation before coding:

- Unique key: likely `slug`, but `name` duplicate skipping is currently also used in legacy JSON import. `[Analysis §5, §9; Refactor P1-06]`
- Mode: `create_only`, `skip_duplicate`, or `update_or_create`.
- Whether `replace` is forbidden unless explicitly approved.
- Whether blank import values can overwrite existing values.

### Error Reporting

- Use shared import report structure with row, column, value, and reason.
- Do not expose raw exceptions. `[Analysis §10; Refactor P0-07]`
- Unexpected failures are logged and shown as safe user-facing messages. `[Refactor P0-07]`

## 9. Export Design

### Current Decision

Legacy export streams JSON from Livewire and loads all filtered posts into memory. `[Analysis §9, §13; Refactor P1-08]`

Decision:

- Replace with shared export strategy if export remains required. **Needs confirmation before coding for file format and export columns.** `[Refactor P1-02]`

### Target Export Classes

Required if export remains:

- `Modules/Post/Services/ImportExport.php`

Optional if complexity requires:

- `Modules/Post/Export/PostExport.php`
- `Modules/Post/Export/ExportQuery.php`
- `Modules/Post/Export/ExportMapper.php`
- `Modules/Post/Export/TemplateBuilder.php`

### Query Design

- Query through `PostService` or `ImportExport`.
- Support current filters: search, category, status.
- Support selected IDs if approved.
- Eager-load `author`, `categories`, and `tags`. `[Analysis §13; Refactor P1-08]`
- Use chunk/lazy iteration for large exports. `[Analysis §13; Refactor P1-08]`

### Export Mapping

Default export fields:

- `name`
- `slug`
- `summary`
- `content`
- `status`
- `is_featured`
- `thumbnail`
- `categories`
- `tags`
- `meta_title`
- `meta_description`
- `published_at`

Needs confirmation before coding:

- Whether `content` should be included in exports.
- Whether `views`, `user_id`, internal IDs, or timestamps should be excluded.
- Whether exports are JSON, Excel, or both. `[Analysis §9; Refactor P1-02]`

### Template Generation

If import is approved, generate an import template with:

- Canonical headers.
- Sample row.
- Required/optional notes.
- Allowed status values.
- Category/tag input instructions.
- Warning that derived values are system-owned.

### Large Export Strategy

- Never call unbounded `get()` from Livewire. `[Analysis §13; Refactor P1-08]`
- Use shared export storage and bounded iteration.
- Queue exports if datasets or content size can exceed request limits. **Needs confirmation before coding after dataset sizing.** `[ROADMAP P1-06; Refactor P1-08]`

## 10. Permissions and Authorization

### Required Permissions

Declared in `Modules/Post/config/module.php`:

- `view_post`
- `create_post`
- `edit_post`
- `delete_post`

Action mapping:

- Index/list: `view_post`.
- Create page/save new post: `create_post`.
- Edit page/save existing post: `edit_post`.
- Delete and bulk delete: `delete_post`.
- Clone: `create_post` plus `view_post` for source access.
- Export: `view_post`.
- Import: `create_post` and possibly `edit_post` if update modes are approved. **Needs confirmation before coding.** `[Analysis §10; Refactor P0-04]`

### Route Middleware

- `Modules/Post/routes/web.php` keeps `web` and `auth:admin`.
- Add permission middleware or equivalent policy/gate enforcement on each route. `[Analysis §10; Refactor P0-02]`
- `Modules/Post/routes/api.php` must be disabled or explicitly authenticated/authorized. **Needs confirmation before coding if API is retained.** `[Analysis §2, §10; Refactor P0-01]`

### Policy/Gate Checks

- Use the project permission convention for named permissions.
- Add server-side checks in Livewire actions even when routes are protected. `[Analysis §10; Refactor P0-03, P0-04]`
- Record-level checks must re-resolve IDs server-side before destructive actions. `[Analysis §10; Refactor P0-05]`

### Livewire Action Protection

Protect:

- `PostForm::mount()`
- `PostForm::save()`
- `PostTable::delete()`
- `PostTable::deleteSelected()`
- `PostTable::clone()`
- `PostTable::export()`
- `PostTable::import()` or shared panel equivalent

UI buttons may be hidden for unauthorized users, but authorization must still fail closed server-side. `[Analysis §10; Refactor P0-04]`

## 11. Transactions and Data Integrity

### Actions Requiring DB Transactions

- Create post with category/tag sync. `[Analysis §12; Refactor P0-06]`
- Update post with category/tag sync. `[Analysis §12; Refactor P0-06]`
- Clone post with category/tag sync. `[Analysis §12; Refactor P1-07]`
- Bulk delete selected posts after server-side validation. `[Analysis §12; Refactor P0-05, P1-07]`
- Import rows when all-or-nothing mode is approved. **Needs confirmation before coding.** `[Analysis §9; Refactor P1-06]`

### Rollback Conditions

- Any failed post write, category sync, tag creation, or tag sync rolls back the database transaction. `[Refactor P0-06]`
- Failed thumbnail replacement must not delete the existing thumbnail. `[Refactor P1-16]`
- If a new thumbnail is stored but the DB transaction fails, delete the newly stored file as compensation. `[Refactor P1-16]`
- Unexpected import exceptions are logged and produce safe errors, not raw messages. `[Analysis §10; Refactor P0-07]`

### Idempotency Concerns

- `delete()` should safely handle missing records without fatal errors. `[Analysis §12; Refactor P1-07]`
- `bulkDelete()` should tolerate duplicate selected IDs by normalizing unique IDs before processing. `[Analysis §10; Refactor P0-05]`
- `clone()` must generate a unique slug deterministically enough for retries; current `time()` strategy should be reviewed. **Needs confirmation before coding.** `[Analysis §5; Refactor P1-07]`
- Import duplicate handling must be explicit before implementation. **Needs confirmation before coding.** `[Analysis §9; Refactor P1-06]`

## 12. Performance Strategy

### Eager Loading

- List queries eager-load `author` and `categories`.
- Export queries eager-load `author`, `categories`, and `tags`. `[Analysis §13; Refactor P1-08]`
- Edit loading eager-loads `categories` and `tags`. `[Analysis §5; Refactor P1-01]`

### Query Optimization

- All search/filter/sort queries live in `PostService::paginate()`. `[Analysis §7; Refactor P1-01]`
- Category filtering uses relationship-aware query through `whereHas` or equivalent service method. `[Analysis §5; Refactor P1-01]`
- Category options are loaded through service and bounded; cache only with explicit invalidation. `[Analysis §13; Refactor P1-10]`

### Pagination

- Use server-side pagination with `10`, `25`, `50`, `100`, and guarded `All`. `[Refactor P1-09]`
- Reset page on search, category filter, status filter, and per-page changes. `[Analysis §5; Refactor P1-09]`
- Select-all operates on current page unless explicitly designed otherwise. `[Analysis §13; Refactor P1-09]`

### Caching

- Do not add caching to conceal inefficient queries.
- Category options may be cached only after category write invalidation is defined by canonical Category ownership. **Needs confirmation before coding.** `[Analysis §13; Refactor P1-10, P1-14]`

## 13. Test Strategy

### Route Tests

- `tests/Feature/Post/PostRouteConfigurationTest.php`: API route disabled or correctly guarded; web routes boot. `[Refactor P0-01, P2-07]`
- `tests/Feature/Post/PostRouteAuthorizationTest.php`: route permission denial and allowed access. `[Refactor P0-02]`

### Livewire Tests

- `tests/Feature/Post/PostLivewireAuthorizationTest.php`: create, edit, delete, bulk delete, clone, import/export denial. `[Refactor P0-03, P0-04]`
- `tests/Feature/Post/PostLivewireCrudTest.php`: create/update/delete/clone happy paths and missing-record behavior. `[Refactor P1-07]`
- `tests/Feature/Post/PostLivewireValidationTest.php`: form validation, slug uniqueness, category IDs, tag constraints. `[Refactor P1-04, P1-05]`
- `tests/Feature/Post/PostLivewireTableTest.php`: search/filter/pagination/select-all semantics and responsive table basics. `[Refactor P1-09, P2-04]`

### Service Tests

- `tests/Unit/Post/PostServiceTest.php`: CRUD, transactions, tag/category sync, clone, bulk delete, category options, thumbnail cleanup. `[Refactor P0-06, P1-01, P1-07, P1-10, P1-16]`

### Import Tests

- `tests/Unit/Post/PostImportExportTest.php`: row mapping, normalization, validation, duplicate mode, dry-run, null handling. `[Refactor P1-02, P1-06]`
- `tests/Feature/Post/PostImportSecurityTest.php`: no raw exception output. `[Refactor P0-07]`

### Export Tests

- `tests/Unit/Post/PostImportExportTest.php`: export mapping, filters, selected IDs if approved, eager-loaded tags, bounded iteration. `[Refactor P1-08]`
- `tests/Feature/Post/PostImportExportPanelTest.php`: shared panel integration if approved. `[Refactor P1-02]`

### Authorization Tests

- Denied route tests for `view_post`, `create_post`, `edit_post`, `delete_post`. `[Refactor P0-02]`
- Denied Livewire action tests for direct component calls. `[Refactor P0-03, P0-04]`
- Tampered selected-ID tests. `[Refactor P0-05]`

### Model, Migration, Architecture Tests

- `tests/Unit/Post/PostModelTest.php`: fillable, casts, relationships, no HTML accessor. `[Refactor P1-11, P1-12, P2-05]`
- `tests/Feature/Post/PostMigrationTest.php`: migration order and `post_meta` ownership decision. `[Refactor P1-15, P2-06]`
- `tests/Feature/Post/PostArchitectureTest.php`: duplicate model ownership and service boundary rules. `[Refactor P1-03, P1-13, P1-14]`

## 14. Implementation Checklist

### P0

- [ ] Disable or guard `Modules/Post/routes/api.php`; do not leave it pointing to missing `index()`. `[Analysis §2, §10; Refactor P0-01]`
- [ ] Clean or remove `Modules/Post/Http/Controllers/Api/PostController.php` according to API decision. `[Analysis §3, §15; Refactor P0-01, P2-07]`
- [ ] Add named permission enforcement to `Modules/Post/routes/web.php`. `[Analysis §10; Refactor P0-02]`
- [ ] Add create/edit authorization to `Modules/Post/Livewire/Posts/PostForm.php`. `[Analysis §10; Refactor P0-03]`
- [ ] Add view/create/delete authorization to `Modules/Post/Livewire/Posts/PostTable.php`. `[Analysis §10; Refactor P0-04]`
- [ ] Re-resolve and authorize selected IDs before bulk delete. `[Analysis §10; Refactor P0-05]`
- [ ] Create `Modules/Post/Services/PostService.php` transaction path for create/update/category sync/tag sync. `[Analysis §12; Refactor P0-06]`
- [ ] Replace raw import exception rendering with safe messages and logging. `[Analysis §10; Refactor P0-07]`
- [ ] Add P0 route, Livewire authorization, bulk-ID tampering, and import-security tests. `[Refactor P1-17]`

### P1

- [ ] Move all direct Eloquent queries and transactions out of `PostForm` and `PostTable`. `[Analysis §5, §7; Refactor P1-01]`
- [ ] Implement `PostService` pagination, edit loading, category options, CRUD, clone, delete, bulk delete, and tag/category sync. `[Refactor P1-01, P1-07, P1-10]`
- [ ] Confirm import/export format, unique key, duplicate mode, dry-run, null-overwrite, and row error contract. **Needs confirmation before coding.** `[Analysis §9; Refactor P1-02, P1-06]`
- [ ] Add `Modules/Post/Services/ImportExport.php` only after import/export confirmation. `[Refactor P1-02]`
- [ ] Replace local JSON import/export UI with shared panel or an approved module-specific flow. `[Analysis §6, §9; Refactor P1-02]`
- [ ] Validate every Post form field and relationship ID. `[Analysis §11; Refactor P1-04]`
- [ ] Replace string unique slug rule with explicit edit-aware uniqueness. `[Analysis §11; Refactor P1-05]`
- [ ] Validate import rows before persistence. `[Analysis §11; Refactor P1-06]`
- [ ] Bound exports and eager-load tags. `[Analysis §13; Refactor P1-08]`
- [ ] Add per-page state and fix select-all semantics. `[Analysis §13; Refactor P1-09]`
- [ ] Move category option loading to service. `[Analysis §13; Refactor P1-10]`
- [ ] Remove HTML status badge accessor from `Post` model. `[Analysis §8; Refactor P1-11]`
- [ ] Choose canonical relationship name for author/user. **Needs confirmation before removing alias.** `[Analysis §8; Refactor P1-12]`
- [ ] Audit and remove Post-owned Product/Review/Wishlist only after callers are migrated. **Needs confirmation before coding removal.** `[Analysis §8, §15; Refactor P1-13]`
- [ ] Confirm `categories` and `wp_tags` ownership before replacing local models. **Needs confirmation before coding.** `[Analysis §8; Refactor P1-14]`
- [ ] Repair negative-year migrations only through coordinated migration hygiene. `[Analysis §15; Refactor P1-15]`
- [ ] Implement thumbnail lifecycle cleanup in `PostService`. `[Refactor P1-16]`
- [ ] Add service, Livewire, import/export, architecture, model, and migration tests. `[Refactor P1-17]`

### P2

- [ ] Remove scaffold placeholder views only after route/view caller verification. `[Analysis §15; Refactor P2-01]`
- [ ] Remove inline CSS from `Modules/Post/resources/views/pages/posts/create.blade.php`. `[Analysis §4; Refactor P2-02]`
- [ ] Align form bindings with `wire:model.live`, documenting editor exceptions. `[Analysis §6; Refactor P2-03]`
- [ ] Add responsive `overflow-x-auto` table wrapper. `[Analysis §6; Refactor P2-04]`
- [ ] Add explicit table/casts metadata to retained Category/Tag models, or remove after canonical migration. `[Refactor P2-05]`
- [ ] Decide `post_meta` owner or deprecation path. **Needs confirmation before coding.** `[Analysis §15; Refactor P2-06]`
- [ ] Clean empty API controller after P0 API decision. `[Analysis §15; Refactor P2-07]`
