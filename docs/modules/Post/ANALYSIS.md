# Post Module Analysis

## Reading Context

- Read `ROADMAP.md`.
- Read `docs/AI_PROJECT_CONTEXT.md`.
- Read `docs/CODEX_BOOTSTRAP.md`.
- Scope analyzed: `Modules/Post` only. Cross-module references are mentioned only when they affect ownership or runtime behavior of `Modules/Post`.
- No application code was changed.

## 1. Module Purpose

`Modules/Post` is a domain module for admin management of blog/news posts stored in `wp_posts`. It provides admin list, create, edit, delete, clone, JSON import, and JSON export screens through Livewire.

Current module intent from `Modules/Post/config/module.php`:

- Name: `Post`
- Type: `domain`
- Enabled flag: `false`
- Declared permissions: `view_post`, `create_post`, `edit_post`, `delete_post`

Main database tables owned or used by this module:

- `wp_posts`
- `wp_post_tag`
- `category_post`
- `post_meta`
- External dependency tables: `categories`, `wp_tags`, `users`

## Current Flow

Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade -> Shared Components -> Service -> Import/Export -> Model -> Migration

Observed reality:

- Route -> Controller -> Page Blade -> Livewire PHP -> Livewire Blade exists for admin post CRUD pages.
- Shared Blade components are used indirectly via `x-editor`; Post-local placeholder components are scaffold leftovers.
- Service layer is missing.
- Import/export classes are missing; JSON import/export is implemented directly in Livewire.
- Livewire classes query and mutate Eloquent models directly.

## 2. Route List

### Web Routes

File: `Modules/Post/routes/web.php`

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/admin/posts` | `admin.posts.index` | `web`, `auth:admin` | `Modules\Post\Http\Controllers\PostController@index` |
| GET | `/admin/posts/create` | `admin.posts.create` | `web`, `auth:admin` | `Modules\Post\Http\Controllers\PostController@create` |
| GET | `/admin/posts/{id}/edit` | `admin.posts.edit` | `web`, `auth:admin` | `Modules\Post\Http\Controllers\PostController@edit` |

### API Routes

File: `Modules/Post/routes/api.php`

| Method | URI | Middleware | Controller |
|---|---|---|---|
| GET | `/post` | none active | `Modules\Post\Http\Controllers\Api\PostController@index` |

Issue: `Modules/Post/routes/api.php` registers `index`, but `Modules/Post/Http/Controllers/Api/PostController.php` does not define an `index()` method.

## 3. Controllers

### Admin Controller

File: `Modules/Post/Http/Controllers/PostController.php`

- `index()` returns `Post::pages.posts.index`.
- `create()` returns `Post::pages.posts.create`.
- `edit($id)` returns `Post::pages.posts.edit` with scalar `$id`.

Assessment:

- Thin controller pattern is mostly correct.
- `edit($id)` does not type the ID as `int`, but it only passes the scalar through.
- No permission checks beyond route `auth:admin`.

### API Controller

File: `Modules/Post/Http/Controllers/Api/PostController.php`

- Empty controller.
- Imports `Illuminate\Http\Request` but does not use it.
- Does not implement the routed `index()` action.

## 4. Page Blade Files

Files:

- `Modules/Post/resources/views/pages/posts/index.blade.php`
- `Modules/Post/resources/views/pages/posts/create.blade.php`
- `Modules/Post/resources/views/pages/posts/edit.blade.php`
- `Modules/Post/resources/views/pages/index.blade.php`
- `Modules/Post/resources/views/post.blade.php`

Assessment:

- `pages/posts/index.blade.php` extends `Admin::layouts.master` and mounts `@livewire('post.posts.post-table')`.
- `pages/posts/create.blade.php` extends `Admin::layouts.master` and mounts `@livewire('post.posts.post-form')`.
- `pages/posts/edit.blade.php` extends `Admin::layouts.master` and mounts `@livewire('post.posts.post-form', ['id' => $id])`.
- `pages/posts/create.blade.php` contains inline CSS for `.custom-scrollbar`.
- `pages/index.blade.php` and `post.blade.php` appear to be scaffold placeholder pages and are not routed from `Modules/Post/routes/web.php`.

## 5. Livewire PHP Classes

### Post Form

File: `Modules/Post/Livewire/Posts/PostForm.php`

Responsibilities currently implemented:

- Create/edit UI state.
- Loads existing post with categories and tags.
- Auto-generates slug and meta fields.
- Validates basic fields.
- Handles image upload.
- Creates/updates post.
- Syncs categories.
- Creates tags and syncs tags.
- Loads post categories in `render()`.

Direct model/database usage:

- `Post::with(...)->findOrFail($id)`
- `Post::updateOrCreate(...)`
- `Category::where('type', 'post')->get()`
- `Tag::firstOrCreate(...)`
- `$post->categories()->sync(...)`
- `$post->tags()->sync(...)`

### Post Table

File: `Modules/Post/Livewire/Posts/PostTable.php`

Responsibilities currently implemented:

- Search/filter/pagination state.
- Query building for list screen.
- Single delete.
- Bulk delete.
- Clone post.
- JSON export.
- JSON import.
- Category/tag creation during import.

Direct model/database usage:

- `Post::with(['author', 'categories'])`
- `Post::whereIn(...)->delete()`
- `Post::with(['categories', 'tags'])->find($id)`
- `Post::where(...)->orWhere(...)->exists()`
- `Post::create(...)`
- `Category::firstOrCreate(...)`
- `Tag::firstOrCreate(...)`
- `Category::where('type', 'post')->get()`
- `DB::transaction(...)`

## 6. Livewire Blade Views

Files:

- `Modules/Post/resources/views/livewire/posts/post-form.blade.php`
- `Modules/Post/resources/views/livewire/posts/post-table.blade.php`
- `Modules/Post/resources/views/livewire/placeholder.blade.php`

Assessment:

- `post-form.blade.php` renders the create/edit form and uses `x-editor` for summary/content.
- `post-table.blade.php` renders filters, import/export controls, table, bulk selection, row actions, and pagination.
- `livewire/placeholder.blade.php` appears unused by routed Post pages.

Notable issues:

- `post-form.blade.php` uses `wire:model="slug"`, `wire:model="summary"`, `wire:model="content"`, `wire:model="meta_title"`, `wire:model="meta_description"`, `wire:model="status"`, `wire:model="selectedCategories"`, and `wire:model="inputTags"` instead of the project default `wire:model.live`.
- `post-table.blade.php` has import/export UI implemented locally instead of using the shared import/export panel.
- `post-table.blade.php` table wrapper lacks an explicit `overflow-x-auto` inner wrapper.

## 7. Services and Public Methods

No service classes exist under `Modules/Post/Services`.

Required service responsibilities are currently embedded in:

- `Modules/Post/Livewire/Posts/PostForm.php`
- `Modules/Post/Livewire/Posts/PostTable.php`

Expected future service entry points:

- `PostService::paginate(array $filters, int|string $perPage)`
- `PostService::findForEdit(int $id)`
- `PostService::create(array $data)`
- `PostService::update(int $id, array $data)`
- `PostService::delete(int $id)`
- `PostService::bulkDelete(array $ids)`
- `PostService::clone(int $id)`

Recommendation P1: Create `Modules/Post/Services/PostService.php` and move query, mutation, category/tag sync, clone, and transaction logic out of Livewire.

## 8. Models and Database Tables

### Post

File: `Modules/Post/Models/Post.php`

Table: `wp_posts`

Key fields:

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

Relationships:

- `categories()` many-to-many through `category_post`
- `tags()` many-to-many through `wp_post_tag`
- `user()` belongs to `App\Models\User`
- `author()` belongs to `App\Models\User`

Concern:

- `getStatusBadgeAttribute()` returns HTML from the model, mixing presentation with ORM.
- `user()` and `author()` duplicate the same relationship.

### Category

File: `Modules/Post/Models/Category.php`

Table: implicit `categories`

Concern:

- This duplicates category-domain behavior inside the Post module.
- It includes product relationships through `category_product`, pulling product concerns into Post.

### Tag

File: `Modules/Post/Models/Tag.php`

Table: `wp_tags`

Concern:

- `Modules/Post` has a model for `wp_tags`, but no migration for `wp_tags` inside the module.

### Product

File: `Modules/Post/Models/Product.php`

Table: `wp_products`

Concern:

- Product model is unrelated to Post admin flow and duplicates Product domain ownership.
- It imports `Modules\Category\Models\Category`, `Modules\Website\Models\Wishlist`, and `Modules\Post\Models\Review`, creating cross-module coupling.

### Review

File: `Modules/Post/Models/Review.php`

Table: `reviews`

Concern:

- Review model is product-related and unrelated to Post admin flow.

### Wishlist

File: `Modules/Post/Models/Wishlist.php`

Table: implicit `wishlists`

Concern:

- Wishlist model is product/customer-related and unrelated to Post admin flow.

## 9. Import/Export Classes

No import/export classes exist under:

- `Modules/Post/Imports`
- `Modules/Post/Exports`
- `Modules/Post/Import`
- `Modules/Post/Export`
- `Modules/Post/Services/ImportExport.php`

Current import/export implementation:

- Export: `Modules/Post/Livewire/Posts/PostTable.php` method `export()` streams all filtered posts as JSON.
- Import: `Modules/Post/Livewire/Posts/PostTable.php` method `import()` reads uploaded JSON and creates posts, categories, and tags.

Assessment:

- This does not follow the project import/export v1.5 standard.
- It bypasses `Modules/Shared/Services/ImportExport`.
- It has no dry-run, row-level error report, confirmed unique key, null-overwrite rule, or bounded export strategy.

## 10. Authorization/Security Risks

- P0: `Modules/Post/routes/api.php` exposes unauthenticated `GET /post`; it points to a missing controller method and currently has no `auth:sanctum` or admin guard.
- P0: `Modules/Post/routes/web.php` uses only `auth:admin`; it does not enforce declared permissions from `Modules/Post/config/module.php`.
- P0: `Modules/Post/Livewire/Posts/PostForm.php` allows any authenticated admin reaching the component to create or update posts without checking `create_post` or `edit_post`.
- P0: `Modules/Post/Livewire/Posts/PostTable.php` allows delete, bulk delete, clone, import, and export without permission checks such as `delete_post`, `create_post`, or `view_post`.
- P0: `Modules/Post/Livewire/Posts/PostTable.php` trusts client-provided selected IDs for `deleteSelected()` without server-side authorization per record.
- P0: `Modules/Post/Livewire/Posts/PostTable.php` catches import exceptions and exposes raw exception messages through `$this->addError('importFile', 'Lỗi: ' . $e->getMessage())`.
- P1: `Modules/Post/Livewire/Posts/PostForm.php` stores uploaded files but does not delete or clean up replaced thumbnails.

## 11. Validation Problems

- P1: `Modules/Post/Livewire/Posts/PostForm.php` validates only `name`, `slug`, `status`, and `new_thumbnail`; it does not validate `summary`, `content`, `is_featured`, `meta_title`, `meta_description`, or `selectedCategories`.
- P1: `Modules/Post/Livewire/Posts/PostForm.php` uses `unique:wp_posts,slug,{id}` but does not explicitly scope the ignored key column; prefer a Rule object for clarity and safety.
- P1: `Modules/Post/Livewire/Posts/PostForm.php` does not validate that category IDs belong to active post categories.
- P1: `Modules/Post/Livewire/Posts/PostForm.php` accepts comma-delimited tags without length, count, duplicate, or slug-collision validation.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` validates only file MIME and size for import; it does not validate each JSON row shape, required keys, status enum, category array type, tag array type, string lengths, or thumbnail URL/path.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` reads `$item['name']` and `$item['status']` directly, so malformed rows can produce notices/exceptions instead of structured row errors.
- P2: `Modules/Post/resources/views/livewire/posts/post-form.blade.php` does not display field-level validation errors for all user-editable fields.

## 12. Transaction Risks

- P0: `Modules/Post/Livewire/Posts/PostForm.php` creates/updates the post, syncs categories, and creates/syncs tags without a transaction; failure after the post write can leave partial data.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` correctly wraps clone in `DB::transaction()`, but transactions belong in a service, not Livewire.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` wraps JSON import in one transaction, but all import mapping, validation, persistence, and reporting are in Livewire.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` bulk delete does not explicitly coordinate related cleanup/audit behavior in a service.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` single delete calls `Post::find($id)->delete()` without a null check; missing records can cause an error.

## 13. N+1 / Query Performance Risks

- P1: `Modules/Post/Livewire/Posts/PostTable.php` `export()` calls `$this->getQuery()->get()` and loads all filtered posts into memory.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` `export()` maps `$post->tags`, but `getQuery()` eager loads only `author` and `categories`; this creates N+1 queries for tags.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` `updatedSelectAll()` calls `$this->getQuery()->pluck('id')` without applying current pagination, so it can select every filtered record, not only the current visible page as the comment claims.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` has hard-coded `paginate(10)` and no standard `perPage` selector or guarded `All` behavior.
- P1: `Modules/Post/Livewire/Posts/PostForm.php` loads all post categories with `Category::where('type', 'post')->get()` every render.
- P1: `Modules/Post/Models/Category.php` `getAllChildrenIds()` recursively walks `childrenRecursive`; callers can trigger recursive query/memory growth if relations are not loaded carefully.
- P1: `Modules/Post/Models/Product.php` accessors `getAverageRatingAttribute()` and `getReviewCountAttribute()` run aggregate queries per product; this is a duplicated product concern inside Post and can create N+1 behavior if used.

## 14. Duplicate Logic

- P1: `Modules/Post/Livewire/Posts/PostForm.php` duplicates the same admin post form logic found in `Modules/Admin/Livewire/Posts/PostForm.php`.
- P1: `Modules/Post/Livewire/Posts/PostTable.php` duplicates the same admin post table logic found in `Modules/Admin/Livewire/Posts/PostTable.php`.
- P1: `Modules/Post/resources/views/pages/posts/*.blade.php` duplicate admin post page wrappers found under `Modules/Admin/resources/views/pages/posts/`.
- P1: `Modules/Post/resources/views/livewire/posts/*.blade.php` duplicate admin post Livewire views found under `Modules/Admin/resources/views/livewire/posts/`.
- P1: `Modules/Post/Models/Post.php`, `Modules/Post/Models/Category.php`, and `Modules/Post/Models/Tag.php` overlap with Website-facing models for the same tables under `Modules/Website/Models`.
- P1: `Modules/Post/Models/Product.php`, `Modules/Post/Models/Review.php`, and `Modules/Post/Models/Wishlist.php` duplicate non-Post domain models.

## 15. Files That Look Unused

These files are not referenced by `Modules/Post/routes/web.php` or the routed Post admin flow:

- P2: `Modules/Post/resources/views/post.blade.php`
- P2: `Modules/Post/resources/views/pages/index.blade.php`
- P2: `Modules/Post/resources/views/components/placeholder.blade.php`
- P2: `Modules/Post/resources/views/livewire/placeholder.blade.php`
- P2: `Modules/Post/Models/Product.php`
- P2: `Modules/Post/Models/Review.php`
- P2: `Modules/Post/Models/Wishlist.php`
- P2: `Modules/Post/database/migrations/2026_05_08_111335_post_meta.php` creates `post_meta`, but no model, route, controller, Livewire class, or view in `Modules/Post` uses it.
- P2: `Modules/Post/Http/Controllers/Api/PostController.php` is empty while `Modules/Post/routes/api.php` references `index()`.

Do not delete these without a separate caller audit and module ownership decision.

## 16. Refactor Plan

### P0 Critical

- P0: Add explicit permission enforcement for Post web routes and Livewire actions in `Modules/Post/routes/web.php`, `Modules/Post/Livewire/Posts/PostForm.php`, and `Modules/Post/Livewire/Posts/PostTable.php`.
- P0: Fix or disable unauthenticated broken API route in `Modules/Post/routes/api.php` and `Modules/Post/Http/Controllers/Api/PostController.php`.
- P0: Move create/update/category sync/tag sync in `Modules/Post/Livewire/Posts/PostForm.php` into a transaction owned by a new `Modules/Post/Services/PostService.php`.
- P0: Add server-side authorization for client-provided IDs in `Modules/Post/Livewire/Posts/PostTable.php` before delete, bulk delete, clone, import, or export.
- P0: Replace raw import exception display in `Modules/Post/Livewire/Posts/PostTable.php` with logged internal errors and safe user-facing messages.

### P1 Important

- P1: Create `Modules/Post/Services/PostService.php` and move all Eloquent queries, persistence, clone, delete, bulk delete, filter, sort, and pagination behavior out of `Modules/Post/Livewire/Posts/PostForm.php` and `Modules/Post/Livewire/Posts/PostTable.php`.
- P1: Add `Modules/Post/Services/ImportExport.php` using `Modules/Shared/Services/ImportExport` and replace local JSON import/export in `Modules/Post/Livewire/Posts/PostTable.php` with the shared panel or a confirmed module-specific import/export service.
- P1: Decide canonical ownership between duplicated Post code in `Modules/Post`, `Modules/Admin`, and `Modules/Website`; migrate callers before removing duplicates.
- P1: Validate all form fields and relationship IDs in `Modules/Post/Livewire/Posts/PostForm.php`, including post category ownership and tag constraints.
- P1: Validate import row shape, status values, duplicate behavior, null handling, and row-level errors in the future import service replacing `Modules/Post/Livewire/Posts/PostTable.php`.
- P1: Bound export memory usage in `Modules/Post/Livewire/Posts/PostTable.php` replacement service with chunking/lazy iteration and eager-load tags.
- P1: Add standard pagination state to `Modules/Post/Livewire/Posts/PostTable.php` replacement flow, including `10`, `25`, `50`, `100`, and guarded `All`.
- P1: Fix `updatedSelectAll()` behavior in `Modules/Post/Livewire/Posts/PostTable.php` so it selects only current page rows or clearly labels all-filtered selection.
- P1: Move presentation HTML out of `Modules/Post/Models/Post.php` `getStatusBadgeAttribute()`.
- P1: Remove product/review/wishlist responsibilities from `Modules/Post/Models/Product.php`, `Modules/Post/Models/Review.php`, and `Modules/Post/Models/Wishlist.php` after confirming canonical module ownership and callers.
- P1: Repair migration hygiene for negative-year migrations in `Modules/Post/database/migrations/*` as part of the broader roadmap migration cleanup.

### P2 Nice To Have

- P2: Remove or repurpose placeholder files `Modules/Post/resources/views/post.blade.php`, `Modules/Post/resources/views/pages/index.blade.php`, `Modules/Post/resources/views/components/placeholder.blade.php`, and `Modules/Post/resources/views/livewire/placeholder.blade.php` after route/caller verification.
- P2: Replace inline CSS in `Modules/Post/resources/views/pages/posts/create.blade.php` with shared/Tailwind-friendly styling.
- P2: Update `Modules/Post/resources/views/livewire/posts/post-form.blade.php` bindings to the project default `wire:model.live` where appropriate.
- P2: Add responsive `overflow-x-auto` table wrapping in `Modules/Post/resources/views/livewire/posts/post-table.blade.php`.
- P2: Add explicit `$table = 'categories'` to `Modules/Post/Models/Category.php` if this model remains in the module.
- P2: Add explicit `$casts` for `Modules/Post/Models/Tag.php` timestamps or future typed fields if needed.
- P2: Add module route/Livewire/service tests once the service layer and authorization decisions are confirmed.
