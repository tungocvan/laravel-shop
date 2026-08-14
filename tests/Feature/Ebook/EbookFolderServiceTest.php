<?php

namespace Tests\Feature\Ebook;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookFolderService;
use Tests\TestCase;

class EbookFolderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'ebook.ebook.disk' => 'local',
            'ebook.ebook.root' => 'ebooks',
        ]);
    }

    public function test_create_folder_persists_metadata_and_physical_directory(): void
    {
        $folder = app(EbookFolderService::class)->create([
            'name' => 'Laravel',
            'description' => 'Laravel docs',
        ]);

        $this->assertDatabaseHas('ebook_folders', [
            'id' => $folder->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'parent_id' => null,
        ]);
        Storage::disk('local')->assertExists('ebooks/laravel');
    }

    public function test_nested_folder_uses_parent_path(): void
    {
        $service = app(EbookFolderService::class);
        $parent = $service->create(['name' => 'Laravel']);
        $child = $service->create(['name' => 'Livewire', 'parent_id' => $parent->id]);

        $this->assertSame($parent->id, $child->parent_id);
        Storage::disk('local')->assertExists('ebooks/laravel/livewire');
    }

    public function test_duplicate_root_slug_is_rejected(): void
    {
        $service = app(EbookFolderService::class);
        $service->create(['name' => 'Laravel']);

        $this->expectException(ValidationException::class);
        $service->create(['name' => 'Laravel']);
    }

    public function test_folder_cannot_move_under_its_descendant(): void
    {
        $service = app(EbookFolderService::class);
        $parent = $service->create(['name' => 'Laravel']);
        $child = $service->create(['name' => 'Livewire', 'parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);
        $service->update($parent->id, [
            'name' => 'Laravel',
            'slug' => 'laravel',
            'parent_id' => $child->id,
        ]);
    }

    public function test_non_empty_folder_delete_is_blocked(): void
    {
        $service = app(EbookFolderService::class);
        $parent = $service->create(['name' => 'Laravel']);
        $service->create(['name' => 'Livewire', 'parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);
        $service->delete($parent->id);
    }

    public function test_empty_folder_can_be_deleted(): void
    {
        $service = app(EbookFolderService::class);
        $folder = $service->create(['name' => 'Laravel']);

        $service->delete($folder->id);

        $this->assertDatabaseMissing('ebook_folders', ['id' => $folder->id]);
        Storage::disk('local')->assertMissing('ebooks/laravel');
    }

    public function test_tree_returns_nested_children_in_order(): void
    {
        $service = app(EbookFolderService::class);
        $root = $service->create(['name' => 'Laravel']);
        $service->create(['name' => 'Routing', 'parent_id' => $root->id, 'sort_order' => 20]);
        $service->create(['name' => 'Livewire', 'parent_id' => $root->id, 'sort_order' => 10]);

        $tree = $service->tree();

        $this->assertCount(1, $tree);
        $this->assertSame(['Livewire', 'Routing'], $tree->first()->childrenRecursive->pluck('name')->all());
    }
}
