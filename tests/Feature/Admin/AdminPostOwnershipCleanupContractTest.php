<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminPostOwnershipCleanupContractTest extends TestCase
{
    public function test_post_admin_routes_are_owned_by_post_module(): void
    {
        foreach (['admin.posts.index', 'admin.posts.create', 'admin.posts.edit'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route [{$name}].");
            $this->assertStringContainsString('Modules\\Post\\Http\\Controllers\\PostController', $route->getActionName());
        }
    }

    public function test_canonical_post_pages_mount_post_livewire_components_without_duplicate_page_headings(): void
    {
        $index = file_get_contents(base_path('Modules/Post/resources/views/pages/posts/index.blade.php'));
        $create = file_get_contents(base_path('Modules/Post/resources/views/pages/posts/create.blade.php'));
        $edit = file_get_contents(base_path('Modules/Post/resources/views/pages/posts/edit.blade.php'));

        $this->assertStringContainsString("@livewire('post.posts.post-table')", $index);
        $this->assertStringContainsString("@livewire('post.posts.post-form')", $create);
        $this->assertStringContainsString("@livewire('post.posts.post-form'", $edit);
        $this->assertStringNotContainsString('<h1', $create);
        $this->assertStringNotContainsString('<h2', $create);
        $this->assertStringNotContainsString('<h1', $edit);
        $this->assertStringNotContainsString('<h2', $edit);
    }

    public function test_post_domain_owns_model_services_and_schema_contract(): void
    {
        foreach ([
            'Modules/Post/Models/Post.php',
            'Modules/Post/Models/Tag.php',
            'Modules/Post/Services/PostService.php',
            'Modules/Post/Services/ImportExport.php',
            'Modules/Post/database/migrations/-0001_11_30_000025_create_wp_posts_table.php',
            'Modules/Post/database/migrations/-0001_11_30_000026_create_wp_tags_table.php',
            'Modules/Post/database/migrations/-0001_11_30_000027_create_wp_post_tag_table.php',
            'Modules/Post/database/migrations/-0001_11_30_000028_create_category_post_table.php',
        ] as $file) {
            $this->assertFileExists(base_path($file), "Missing canonical Post ownership artifact: {$file}");
        }
    }

    public function test_legacy_admin_post_runtime_remains_absent(): void
    {
        foreach ([
            'Modules/Admin/Http/Controllers/PostController.php',
            'Modules/Admin/Livewire/Posts/PostForm.php',
            'Modules/Admin/Livewire/Posts/PostTable.php',
            'Modules/Admin/resources/views/livewire/posts/post-form.blade.php',
            'Modules/Admin/resources/views/livewire/posts/post-table.blade.php',
            'Modules/Admin/resources/views/pages/posts/index.blade.php',
            'Modules/Admin/resources/views/pages/posts/create.blade.php',
            'Modules/Admin/resources/views/pages/posts/edit.blade.php',
        ] as $file) {
            $this->assertFileDoesNotExist(base_path($file), "Legacy Admin Post runtime returned: {$file}");
        }
    }

    public function test_post_livewire_actions_keep_capability_authorization_and_service_boundary(): void
    {
        $form = file_get_contents(base_path('Modules/Post/Livewire/Posts/PostForm.php'));
        $table = file_get_contents(base_path('Modules/Post/Livewire/Posts/PostTable.php'));

        $this->assertStringContainsString("authorizeAdmin('create_post')", $form);
        $this->assertStringContainsString("authorizeAdmin('edit_post')", $form);
        $this->assertStringContainsString("authorizeAdmin('view_post')", $table);
        $this->assertStringContainsString("authorizeAdmin('create_post')", $table);
        $this->assertStringContainsString("authorizeAdmin('delete_post')", $table);
        $this->assertStringContainsString('PostService::class', $form);
        $this->assertStringContainsString('PostService::class', $table);
        $this->assertStringContainsString('ImportExport::class', $table);
        $this->assertStringContainsString('public function resetFilters(): void', $table);
    }

    public function test_post_category_selector_reuses_collapsed_recursive_product_pattern(): void
    {
        $form = file_get_contents(base_path('Modules/Post/Livewire/Posts/PostForm.php'));
        $view = file_get_contents(base_path('Modules/Post/resources/views/livewire/posts/post-form.blade.php'));
        $row = file_get_contents(base_path('Modules/Admin/resources/views/components/category-select-row.blade.php'));

        $this->assertStringContainsString("where('type', 'post')", $form);
        $this->assertStringContainsString("whereNull('parent_id')", $form);
        $this->assertStringContainsString("with('childrenRecursive')", $form);
        $this->assertStringContainsString('<x-admin::category-select', $view);
        $this->assertStringContainsString('wire:model="selectedCategories"', $view);
        $this->assertStringContainsString('x-data="{ open: false }"', $row);
        $this->assertStringContainsString("open ? '−' : '+'", $row);
        $this->assertStringContainsString('data-category-children', $row);
        $this->assertStringContainsString('input[type=checkbox]:checked', $row);
    }

    public function test_post_admin_list_uses_bounded_page_sizes_visible_filters_and_scoped_pagination(): void
    {
        $service = file_get_contents(base_path('Modules/Post/Services/PostService.php'));
        $table = file_get_contents(base_path('Modules/Post/resources/views/livewire/posts/post-table.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Post/resources/views/vendor/pagination/admin-posts.blade.php'));

        $this->assertStringContainsString('public const PER_PAGE_OPTIONS = [10, 25, 50, 100];', $service);
        $this->assertStringNotContainsString("'all'", strtolower($service));
        $this->assertStringContainsString("links('Post::vendor.pagination.admin-posts')", $table);
        $this->assertStringContainsString('wire:click="resetFilters"', $table);
        $this->assertStringContainsString('border border-gray-300 bg-white', $table);
        $this->assertStringNotContainsString('border-transparent bg-transparent', $table);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringContainsString('bg-white', $pagination);
        $this->assertStringContainsString('previousPage', $pagination);
        $this->assertStringContainsString('nextPage', $pagination);
        $this->assertStringContainsString('gotoPage', $pagination);
    }
}
