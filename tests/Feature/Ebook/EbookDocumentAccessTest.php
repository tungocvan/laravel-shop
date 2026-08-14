<?php

namespace Tests\Feature\Ebook;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;
use Modules\Ebook\Services\EbookAccessService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EbookDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_user_can_view_document_and_unassigned_user_cannot(): void
    {
        $allowed = $this->user('allowed@example.test');
        $blocked = $this->user('blocked@example.test');
        $document = $this->document('Restricted Guide');
        $document->viewers()->attach($allowed->id);

        $access = app(EbookAccessService::class);

        $this->assertTrue($access->canView($allowed, $document));
        $this->assertFalse($access->canView($blocked, $document));
    }

    public function test_visible_documents_only_returns_documents_assigned_to_user(): void
    {
        $user = $this->user('reader@example.test');
        $allowed = $this->document('Allowed Guide');
        $blocked = $this->document('Blocked Guide');
        $allowed->viewers()->attach($user->id);

        $ids = app(EbookAccessService::class)
            ->visibleDocuments($user)
            ->pluck('ebook_documents.id')
            ->all();

        $this->assertSame([$allowed->id], $ids);
        $this->assertNotContains($blocked->id, $ids);
    }

    public function test_super_admin_bypasses_document_assignment(): void
    {
        $superAdmin = $this->user('super@example.test');
        $role = Role::query()->firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->assignRole($role);
        $document = $this->document('Admin Only Guide');

        $this->assertTrue(app(EbookAccessService::class)->canView($superAdmin, $document));
        $this->assertTrue(
            app(EbookAccessService::class)->visibleDocuments($superAdmin)->whereKey($document->id)->exists()
        );
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'Access Test',
            'email' => $email,
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
    }

    private function document(string $title): EbookDocument
    {
        $folder = EbookFolder::query()->firstOrCreate([
            'parent_id' => null,
            'slug' => 'access-tests',
        ], [
            'name' => 'Access Tests',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $slug = str($title)->slug()->toString();

        return EbookDocument::query()->create([
            'folder_id' => $folder->id,
            'title' => $title,
            'slug' => $slug,
            'file_name' => $slug.'.md',
            'file_path' => 'ebooks/access-tests/'.$slug.'.md',
            'source_type' => 'file',
            'sort_order' => 0,
            'is_active' => true,
            'is_favorite' => false,
            'content_hash' => hash('sha256', $title),
        ]);
    }
}
