<?php

namespace Tests\Feature\Muasamcong;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Models\PersonalSession;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingSearchSnapshot;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Services\MuasamcongDashboardService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MuasamcongDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_requires_admin_authentication_and_view_permission(): void
    {
        $this->get(route('muasamcong.dashboard'))->assertRedirect();

        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('muasamcong.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_only_capabilities_granted_to_the_admin(): void
    {
        $viewer = $this->adminWithPermissions(['view_muasamcong']);

        $this->actingAs($viewer, 'admin')
            ->get(route('muasamcong.dashboard'))
            ->assertOk()
            ->assertSee('Tổng quan Mua sắm công')
            ->assertSee('Smart Pricing')
            ->assertDontSee('Wishlist của tôi')
            ->assertDontSee('Cấu hình &amp; Personal Session', false);

        $secret = 'session-secret-must-not-be-rendered';
        PersonalSession::query()->create([
            'key' => 'personal-page',
            'cookie_encrypted' => Crypt::encryptString($secret),
            'verified_at' => now(),
            'updated_by' => $viewer->id,
        ]);

        $operator = $this->adminWithPermissions([
            'view_muasamcong',
            'muasamcong.config.manage',
            'muasamcong.pricing.wishlist',
        ]);

        $this->actingAs($operator, 'admin')
            ->get(route('muasamcong.dashboard'))
            ->assertOk()
            ->assertSee('Wishlist của tôi')
            ->assertSee('Cấu hình &amp; Personal Session', false)
            ->assertDontSee($secret)
            ->assertDontSee('cookie_encrypted');
    }

    public function test_dashboard_service_returns_bounded_safe_summaries_and_user_scoped_wishlist(): void
    {
        $admin = $this->adminWithPermissions([
            'view_muasamcong',
            'muasamcong.pricing.wishlist',
        ]);
        $other = User::factory()->create();

        PricingResult::query()->create([
            'source_id' => (string) Str::uuid(),
            'ten_thuoc' => 'Thuốc đã đồng bộ',
            'raw_payload' => ['payload-secret' => 'must-not-load'],
            'synced_at' => now(),
        ]);

        foreach (range(1, 7) as $index) {
            PricingSearchSnapshot::query()->create([
                'keyword' => 'Từ khóa '.$index,
                'keyword_normalized' => 'tu khoa '.$index,
                'keyword_hash' => hash('sha256', 'tu-khoa-'.$index),
                'result_payload' => ['payload-secret' => 'snapshot-'.$index],
                'source_total' => 10 + $index,
                'loaded_total' => 5 + $index,
                'source_partial' => $index % 2 === 0,
                'searched_by' => $admin->id,
                'searched_at' => now()->subMinutes(7 - $index),
            ]);
        }

        $contractor = ContractorSearch::query()->create([
            'contractor_code' => 'vn000000001',
            'contractor_name' => 'Nhà thầu Dashboard',
            'last_searched_at' => now(),
        ]);

        foreach (range(1, 7) as $index) {
            ContractorSearchJob::query()->create([
                'contractor_code' => 'vn00000000'.$index,
                'contractor_name' => 'Nhà thầu '.$index,
                'status' => $index === 7 ? 'failed' : ($index === 6 ? 'running' : 'completed'),
                'progress' => $index === 7 ? 30 : 100,
                'contractor_search_id' => $index === 6 ? $contractor->id : null,
                'requested_by' => $admin->id,
                'error_message' => 'job-error-secret-'.$index,
                'created_at' => now()->subMinutes(7 - $index),
                'updated_at' => now()->subMinutes(7 - $index),
            ]);
        }

        PricingWishlist::query()->create([
            'user_id' => $admin->id,
            'source_id' => (string) Str::uuid(),
            'search_keyword' => 'thuốc của tôi',
            'medicine_name' => 'Thuốc riêng',
            'snapshot' => ['wishlist-secret' => 'mine'],
        ]);
        PricingWishlist::query()->create([
            'user_id' => $other->id,
            'source_id' => (string) Str::uuid(),
            'search_keyword' => 'thuốc người khác',
            'medicine_name' => 'Không được tính',
            'snapshot' => ['wishlist-secret' => 'other'],
        ]);

        $dashboard = app(MuasamcongDashboardService::class)->forUser($admin);
        $serialized = json_encode($dashboard, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $dashboard['metrics']['pricing_results']['count']);
        $this->assertSame(7, $dashboard['metrics']['pricing_searches']['count']);
        $this->assertSame(1, $dashboard['metrics']['contractor_searches']['count']);
        $this->assertSame(1, $dashboard['metrics']['wishlist']['count']);
        $this->assertCount(5, $dashboard['recent_pricing_searches']);
        $this->assertCount(5, $dashboard['queue']['recent']);
        $this->assertSame(1, $dashboard['queue']['counts']['running']);
        $this->assertSame(1, $dashboard['queue']['counts']['failed']);
        $this->assertNull($dashboard['health']['configuration']);
        $this->assertStringNotContainsString('payload-secret', $serialized);
        $this->assertStringNotContainsString('job-error-secret', $serialized);
        $this->assertStringNotContainsString('wishlist-secret', $serialized);
        $this->assertStringNotContainsString('raw_payload', $serialized);
        $this->assertStringNotContainsString('result_payload', $serialized);
        $this->assertStringNotContainsString('error_message', $serialized);
    }

    private function adminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'admin'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin->fresh();
    }
}
