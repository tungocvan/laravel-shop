<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteLivewireRuntimeAssetContractTest extends TestCase
{
    public function test_frontend_runtime_loads_livewire_assets(): void
    {
        $head = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));
        $scripts = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));

        $this->assertNotFalse($head);
        $this->assertNotFalse($scripts);
        $this->assertStringContainsString('@livewireStyles', $head);
        $this->assertStringContainsString('@livewireScripts', $scripts);
        $this->assertLessThan(
            strpos($scripts, "@stack('scripts')"),
            strpos($scripts, '@livewireScripts')
        );
    }

    public function test_website_footer_chat_widget_is_livewire_backed(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $widget = file_get_contents(base_path('Modules/Website/resources/views/livewire/chat/chat-widget.blade.php'));

        $this->assertNotFalse($footer);
        $this->assertNotFalse($widget);
        $this->assertStringContainsString("@livewire('website.chat.chat-widget'", $footer);
        $this->assertStringContainsString('wire:click="toggleChat"', $widget);
        $this->assertStringContainsString('wire:click="startChat"', $widget);
    }
}
