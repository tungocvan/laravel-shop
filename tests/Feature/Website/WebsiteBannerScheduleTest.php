<?php

namespace Tests\Feature\Website;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Website\Models\Banner;
use Tests\TestCase;

class WebsiteBannerScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('wp_banners');
        Schema::create('wp_banners', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('wp_banners');
        parent::tearDown();
    }

    public function test_active_scope_respects_enabled_state_and_schedule_window(): void
    {
        Banner::query()->create(['title' => 'Always', 'is_active' => true]);
        Banner::query()->create(['title' => 'Running', 'is_active' => true, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        Banner::query()->create(['title' => 'Future', 'is_active' => true, 'starts_at' => now()->addHour()]);
        Banner::query()->create(['title' => 'Expired', 'is_active' => true, 'ends_at' => now()->subHour()]);
        Banner::query()->create(['title' => 'Disabled', 'is_active' => false]);

        $this->assertSame(['Always', 'Running'], Banner::query()->active()->orderBy('id')->pluck('title')->all());
        $this->assertSame('scheduled', Banner::query()->where('title', 'Future')->firstOrFail()->schedule_status);
        $this->assertSame('expired', Banner::query()->where('title', 'Expired')->firstOrFail()->schedule_status);
        $this->assertSame('inactive', Banner::query()->where('title', 'Disabled')->firstOrFail()->schedule_status);
    }
}
