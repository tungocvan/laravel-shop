<?php

namespace Tests\Feature\Muasamcong;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\ContractorSearch;
use Modules\Muasamcong\Models\ContractorSearchItem;
use Modules\Muasamcong\Models\ContractorSearchJob;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Models\KqlcntRecord;
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

    public function test_dashboard_renders_kqlcnt_primary_workspace_and_only_granted_capabilities(): void
    {
        $viewer = $this->adminWithPermissions(['view_muasamcong']);

        $this->actingAs($viewer, 'admin')
            ->get(route('muasamcong.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard dữ liệu KQLCNT')
            ->assertSee('Dữ liệu chi tiết KQLCNT đã chuẩn hóa')
            ->assertSee('Mở KQLCNT chuẩn hóa')
            ->assertSee('Quy trình nghiệp vụ')
            ->assertSee('Smart Pricing')
            ->assertDontSee('Wishlist')
            ->assertDontSee('Mở cấu hình');

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
            ->assertSee('Wishlist')
            ->assertSee('Mở cấu hình')
            ->assertSee('Session:')
            ->assertDontSee($secret)
            ->assertDontSee('cookie_encrypted');
    }

    public function test_dashboard_service_uses_active_canonical_kqlcnt_as_primary_metrics_without_double_counting_sources(): void
    {
        $admin = $this->adminWithPermissions([
            'view_muasamcong',
            'muasamcong.pricing.wishlist',
        ]);
        $other = User::factory()->create();
        $now = now();

        $contractor = ContractorSearch::query()->create([
            'contractor_code' => 'vn000000001',
            'contractor_name' => 'Nhà thầu Dashboard',
            'last_searched_at' => $now,
        ]);

        ContractorSearchItem::query()->create([
            'contractor_search_id' => $contractor->id,
            'notify_no' => 'IB-MISSING-001',
            'bid_name' => 'TBMT chưa có KQLCNT',
            'raw_payload' => [],
        ]);
        ContractorSearchItem::query()->create([
            'contractor_search_id' => $contractor->id,
            'notify_no' => 'IB-SNAPSHOT-001',
            'bid_name' => 'TBMT có snapshot',
            'raw_payload' => [],
        ]);

        KqlcntRecord::query()->create([
            'notify_no' => 'IB-SNAPSHOT-001',
            'contractor_code' => $contractor->contractor_code,
            'contractor_name' => $contractor->contractor_name,
            'investor_code' => 'INV-001',
            'investor_name' => 'Chủ đầu tư A',
            'data_source' => 'MIXED',
            'verified_lots' => [],
            'synced_at' => $now->subMinutes(20),
        ]);

        $this->canonicalAward([
            'notify_no' => 'IB-CANONICAL-001',
            'contractor_code' => 'VN-AWARD-001',
            'contractor_name' => 'Nhà thầu A',
            'investor_code' => 'INV-001',
            'investor_name' => 'Chủ đầu tư A',
            'medicine_name' => 'Thuốc A',
            'amount' => 100000000,
            'published_at' => $now->subDays(5),
            'synced_from_catalog_at' => $now->subMinutes(10),
        ]);
        $this->canonicalAward([
            'notify_no' => 'IB-CANONICAL-001',
            'contractor_code' => 'VN-AWARD-001',
            'contractor_name' => 'Nhà thầu A',
            'investor_code' => 'INV-001',
            'investor_name' => 'Chủ đầu tư A',
            'medicine_name' => 'Thuốc B',
            'amount' => 50000000,
            'published_at' => $now->subDays(45),
            'synced_from_catalog_at' => $now->subMinutes(5),
        ]);
        $this->canonicalAward([
            'notify_no' => 'IB-CANONICAL-002',
            'contractor_code' => 'VN-AWARD-002',
            'contractor_name' => 'Nhà thầu B',
            'investor_code' => 'INV-002',
            'investor_name' => 'Chủ đầu tư B',
            'medicine_name' => 'Thuốc C',
            'amount' => 25000000,
            'published_at' => $now->subDays(2),
            'synced_from_catalog_at' => $now,
        ]);

        // Dòng vật lý chưa canonical và dòng canonical đã ngưng không được tính vào KPI chính.
        $this->canonicalAward([
            'notify_no' => 'IB-RAW-ONLY',
            'contractor_code' => 'VN-RAW',
            'medicine_name' => 'Không được tính vì chưa sync canonical',
            'amount' => 999999999,
            'synced_from_catalog_at' => null,
        ]);
        $this->canonicalAward([
            'notify_no' => 'IB-INACTIVE',
            'contractor_code' => 'VN-INACTIVE',
            'medicine_name' => 'Không được tính vì inactive',
            'amount' => 999999999,
            'synced_from_catalog_at' => $now,
            'is_active' => false,
        ]);

        ContractorSearchJob::query()->create([
            'contractor_code' => 'vn000000009',
            'contractor_name' => 'Nhà thầu lỗi',
            'status' => 'failed',
            'progress' => 30,
            'requested_by' => $admin->id,
            'error_message' => 'job-error-secret',
        ]);
        ContractorSearchJob::query()->create([
            'contractor_code' => 'vn000000010',
            'contractor_name' => 'Nhà thầu đang tải',
            'status' => 'running',
            'progress' => 50,
            'requested_by' => $admin->id,
        ]);

        PricingResult::query()->create([
            'source_id' => (string) Str::uuid(),
            'ten_thuoc' => 'Thuốc đã đồng bộ',
            'raw_payload' => ['payload-secret' => 'must-not-load'],
            'synced_at' => $now,
        ]);
        PricingSearchSnapshot::query()->create([
            'keyword' => 'Từ khóa dashboard',
            'keyword_normalized' => 'tu khoa dashboard',
            'keyword_hash' => hash('sha256', 'tu-khoa-dashboard'),
            'result_payload' => ['payload-secret' => 'snapshot'],
            'source_total' => 10,
            'loaded_total' => 5,
            'source_partial' => false,
            'searched_by' => $admin->id,
            'searched_at' => $now,
        ]);
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

        $this->assertTrue($dashboard['metrics']['kqlcnt']['available']);
        $this->assertSame(2, $dashboard['metrics']['kqlcnt']['notifications']);
        $this->assertSame(3, $dashboard['metrics']['kqlcnt']['award_items']);
        $this->assertSame(2, $dashboard['metrics']['kqlcnt']['contractors']);
        $this->assertSame(2, $dashboard['metrics']['kqlcnt']['investors']);
        $this->assertSame(175000000.0, $dashboard['metrics']['kqlcnt']['total_amount']);
        $this->assertSame(125000000.0, $dashboard['metrics']['kqlcnt']['last_30_days_amount']);
        $this->assertCount(3, $dashboard['recent_kqlcnt']);
        $this->assertSame('IB-CANONICAL-002', $dashboard['recent_kqlcnt'][0]['notify_no']);

        $this->assertSame(1, $dashboard['attention']['missing_detail']);
        $this->assertSame(1, $dashboard['attention']['not_persisted']);
        $this->assertSame(1, $dashboard['attention']['imported_or_mixed']);
        $this->assertSame(1, $dashboard['attention']['failed_jobs']);
        $this->assertSame(1, $dashboard['queue']['counts']['running']);
        $this->assertSame(1, $dashboard['queue']['counts']['failed']);

        $this->assertSame(1, $dashboard['metrics']['pricing_results']['count']);
        $this->assertSame(1, $dashboard['metrics']['pricing_searches']['count']);
        $this->assertSame(1, $dashboard['metrics']['contractor_searches']['count']);
        $this->assertSame(1, $dashboard['metrics']['wishlist']['count']);
        $this->assertNull($dashboard['health']['configuration']);

        $this->assertStringNotContainsString('payload-secret', $serialized);
        $this->assertStringNotContainsString('job-error-secret', $serialized);
        $this->assertStringNotContainsString('wishlist-secret', $serialized);
        $this->assertStringNotContainsString('raw_payload', $serialized);
        $this->assertStringNotContainsString('result_payload', $serialized);
        $this->assertStringNotContainsString('error_message', $serialized);
    }

    public function test_admin_workspaces_expose_a_permission_aware_dashboard_return_link(): void
    {
        $viewer = $this->adminWithPermissions(['view_muasamcong']);

        $this->actingAs($viewer, 'admin');
        $rendered = view('Muasamcong::partials.dashboard-return-link')->render();

        $this->assertStringContainsString(route('muasamcong.dashboard'), $rendered);
        $this->assertStringContainsString('Quay về Dashboard', $rendered);

        $configManager = $this->adminWithPermissions(['muasamcong.config.manage']);

        $this->actingAs($configManager, 'admin');
        $renderedWithoutViewPermission = view('Muasamcong::partials.dashboard-return-link')->render();

        $this->assertStringNotContainsString(route('muasamcong.dashboard'), $renderedWithoutViewPermission);

        $workspaceViews = [
            'muasamcong.blade.php',
            'synced.blade.php',
            'wishlist.blade.php',
            'hsmt.blade.php',
            'contractors.blade.php',
            'contractor-searches.blade.php',
            'manual-contractor-lots.blade.php',
            'config.blade.php',
        ];

        foreach ($workspaceViews as $workspaceView) {
            $source = file_get_contents(base_path('Modules/Muasamcong/resources/views/'.$workspaceView));

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "@include('Muasamcong::partials.dashboard-return-link')",
                $source,
                $workspaceView
            );
        }
    }

    private function canonicalAward(array $overrides): KqlcntAwardItem
    {
        $identity = Str::uuid()->toString();

        return KqlcntAwardItem::query()->create(array_merge([
            'notify_no' => 'IB-'.Str::upper(Str::random(8)),
            'contractor_code' => 'VN-'.Str::upper(Str::random(8)),
            'contractor_name' => 'Nhà thầu canonical',
            'identity_key' => hash('sha256', $identity),
            'fingerprint' => hash('sha256', 'fingerprint-'.$identity),
            'medicine_name' => 'Thuốc canonical',
            'source' => 'api',
            'sync_source' => 'KQLCNT',
            'synced_from_catalog_at' => now(),
            'last_verified_at' => now(),
            'is_active' => true,
            'raw_payload' => ['award-secret' => 'must-not-load'],
        ], $overrides));
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
