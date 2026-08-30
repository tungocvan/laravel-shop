<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminChatOwnershipBoundaryContractTest extends TestCase
{
    public function test_admin_chat_routes_keep_urls_names_and_require_view_permission(): void
    {
        $routes = app('router')->getRoutes();

        $internal = $routes->getByName('admin.chat.index');
        $customer = $routes->getByName('admin.chat.cskh');

        $this->assertNotNull($internal);
        $this->assertNotNull($customer);

        $this->assertSame('admin/chat/internal-chat', $internal->uri());
        $this->assertSame('admin/chat', $customer->uri());
        $this->assertSame('Modules\\Chat\\Http\\Controllers\\ChatController@internalChat', $internal->getActionName());
        $this->assertSame('Modules\\Chat\\Http\\Controllers\\ChatController@chat', $customer->getActionName());

        foreach ([$internal, $customer] as $route) {
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('permission:view_chat,admin', $route->gatherMiddleware());
        }
    }

    public function test_canonical_chat_runtime_does_not_import_admin_chat_models_or_service(): void
    {
        $files = [
            'Modules/Chat/Services/ChatService.php',
            'Modules/Chat/Livewire/Chat/ChatManager.php',
            'Modules/Chat/Livewire/Chat/ChatWidget.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents(base_path($file));

            $this->assertNotFalse($source);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\ChatSession', $source);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\ChatMessage', $source);
            $this->assertStringNotContainsString('Modules\\Admin\\Services\\ChatService', $source);
        }
    }

    public function test_canonical_chat_models_and_service_are_present(): void
    {
        foreach ([
            'Modules/Chat/Models/ChatSession.php',
            'Modules/Chat/Models/ChatMessage.php',
            'Modules/Chat/Services/ChatService.php',
            'Modules/Chat/Livewire/Chat/ChatManager.php',
            'Modules/Chat/Livewire/Chat/InternalChatManager.php',
            'Modules/Chat/Livewire/Chat/ChatWidget.php',
            'Modules/Chat/resources/views/pages/chat/index.blade.php',
            'Modules/Chat/resources/views/chat.blade.php',
            'Modules/Chat/resources/views/livewire/chat/chat-manager.blade.php',
        ] as $file) {
            $this->assertFileExists(base_path($file));
        }

        $service = file_get_contents(base_path('Modules/Chat/Services/ChatService.php'));

        $this->assertNotFalse($service);
        $this->assertStringContainsString('public function deleteAllMessages(int $sessionId): bool', $service);
    }

    public function test_legacy_admin_chat_runtime_copies_are_removed(): void
    {
        foreach ([
            'Modules/Admin/Http/Controllers/ChatController.php',
            'Modules/Admin/Livewire/Chat/ChatManager.php',
            'Modules/Admin/Models/ChatSession.php',
            'Modules/Admin/Models/ChatMessage.php',
            'Modules/Admin/Services/ChatService.php',
            'Modules/Admin/resources/views/pages/chat/index.blade.php',
            'Modules/Admin/resources/views/livewire/chat/chat-manager.blade.php',
        ] as $file) {
            $this->assertFileDoesNotExist(base_path($file));
        }
    }

    public function test_admin_chat_livewire_actions_enforce_capability_specific_permissions(): void
    {
        $manager = file_get_contents(base_path('Modules/Chat/Livewire/Chat/ChatManager.php'));
        $internal = file_get_contents(base_path('Modules/Chat/Livewire/Chat/InternalChatManager.php'));

        $this->assertNotFalse($manager);
        $this->assertNotFalse($internal);

        $this->assertStringContainsString("authorizePermission('view_chat')", $manager);
        $this->assertStringContainsString("authorizePermission('create_chat')", $manager);
        $this->assertStringContainsString("authorizePermission('edit_chat')", $manager);
        $this->assertStringContainsString("authorizePermission('delete_chat')", $manager);

        $this->assertStringContainsString("authorizePermission('view_chat')", $internal);
        $this->assertStringContainsString("authorizePermission('create_chat')", $internal);
    }
}
