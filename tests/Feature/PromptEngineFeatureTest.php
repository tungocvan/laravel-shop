<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PromptEngineFeatureTest extends TestCase
{
    public function test_web_form_is_available(): void
    {
        $this->get('/prompt-engine')
            ->assertOk()
            ->assertSee('Tạo ảnh bằng ChatGPT')
            ->assertSee('generate-image', false);
    }

    public function test_api_generates_gemini_prompt_and_preserves_unicode(): void
    {
        $this->postJson('/api/prompt-engine/generate', ['character' => '福'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.analysis.character', '福')
            ->assertJsonStructure(['data' => ['explanation_vi' => [
                'y_nghia_chu_han', 'cau_tao', 'y_nghia_chiet_tu', 'bo_cuc',
                'phong_cach', 'mau_sac', 'anh_sang', 'mood', 'diem_nhan_thiet_ke',
            ]]]);
    }

    public function test_chatgpt_image_endpoint_returns_stored_image_link(): void
    {
        Storage::fake('public');
        config([
            'promptengine.config.image_generation.enabled' => true,
            'promptengine.config.image_generation.disk' => 'public',
            'promptengine.config.image_generation.chatgpt-image.api_key' => 'test-key',
            'promptengine.config.image_generation.chatgpt-image.model' => 'gpt-image-2',
        ]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'data' => [['b64_json' => base64_encode('fake-image-bytes')]],
            ]),
        ]);

        $response = $this->postJson('/api/prompt-engine/generate-image', [
            'character' => '福',
            'platform' => 'chatgpt-image',
            'style' => 'museum-editorial',
            'aspect_ratio' => '2:3',
            'save' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.image.provider', 'chatgpt-image')
            ->assertJsonPath('data.image.status', 'completed')
            ->assertJsonStructure(['data' => ['image' => ['path', 'url', 'mime_type']]]);

        Storage::disk('public')->assertExists($response->json('data.image.path'));
    }

    public function test_api_validation_returns_422(): void
    {
        $this->postJson('/api/prompt-engine/generate', ['character' => 'Latin'])->assertStatus(422);
    }

    public function test_artisan_command_works(): void
    {
        $this->artisan('prompt-engine:generate', ['character' => '龍', '--json' => true])->assertSuccessful();
    }
}
