<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Services\MuasamcongSettingsRepository;
use Tests\TestCase;

class MuasamcongSettingsRepositoryTest extends TestCase
{
    public function test_overrides_are_persisted_and_secrets_are_encrypted(): void
    {
        Schema::dropIfExists('muasamcong_settings');
        Schema::create('muasamcong_settings', function (Blueprint $table): void {
            $table->string('key', 100)->primary();
            $table->longText('value');
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        $settings = app(MuasamcongSettingsRepository::class);
        $settings->save([
            'pricing_endpoint' => 'https://muasamcong.mof.gov.vn/o/egp-portal-personal-page/services/smart/search_prc',
            'timeout' => '35',
            'smart_token' => 'private-test-token',
        ]);

        $this->assertSame(35, $settings->overrides()['timeout']);
        $this->assertSame('private-test-token', $settings->overrides()['smart_token']);
        $this->assertNotSame('private-test-token', DB::table('muasamcong_settings')->where('key', 'smart_token')->value('value'));

        $settings->apply();
        $this->assertSame(35, config('muasamcong.timeout'));
    }

    public function test_missing_settings_table_preserves_module_defaults(): void
    {
        Schema::dropIfExists('muasamcong_settings');

        $settings = app(MuasamcongSettingsRepository::class);
        $this->assertFalse($settings->available());
        $this->assertSame([], $settings->overrides());
    }
}
