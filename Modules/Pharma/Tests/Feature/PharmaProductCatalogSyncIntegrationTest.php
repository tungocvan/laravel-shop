<?php

namespace Modules\Pharma\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Modules\Pharma\Jobs\SyncMedicineProducts;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\PharmaProductLink;
use Modules\Pharma\Services\PharmaProductCatalogSyncService;
use Modules\Product\Models\Product;
use Tests\TestCase;

class PharmaProductCatalogSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_projection_is_idempotent_and_preserves_commercial_fields(): void
    {
        $medicine = Medicine::query()->create(['name' => 'Thuốc kiểm thử đồng bộ', 'registration_number' => 'TEST-SYNC-1']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'SYNC-SKU-'. $medicine->id,
            'variant_identity_key' => hash('sha256', 'sync-variant-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'sync-basis-'.$medicine->id),
            'status' => 'active',
        ]);
        $package = MedicinePackage::query()->create([
            'medicine_variant_id' => $variant->id,
            'package_code' => 'SYNC-PKG-'.$variant->id,
            'packaging_text' => 'Hộp 10 viên',
            'package_identity_key' => hash('sha256', 'sync-package-'.$variant->id),
            'is_orderable' => true,
        ]);

        $service = app(PharmaProductCatalogSyncService::class);
        $first = $service->syncVariant($variant, $package);
        $this->assertSame(1, PharmaProductLink::query()->count());
        $this->assertSame(1, Product::query()->whereKey($first->product_id)->count());
        $this->assertFalse($first->product->is_active);
        $this->assertNull($first->product->regular_price);
        $this->assertSame(0, $first->product->quantity);

        $first->product->forceFill(['regular_price' => 125000, 'quantity' => 12])->save();
        $second = $service->syncVariant($variant, $package);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PharmaProductLink::query()->count());
        $this->assertSame('125000.00', $second->product->fresh()->regular_price);
        $this->assertSame(12, $second->product->fresh()->quantity);

        $medicine->update(['name' => 'Thuốc đã đổi tên']);
        $third = $service->syncVariant($variant, $package);
        $this->assertSame($first->product_id, $third->product_id);
        $this->assertStringContainsString('Thuốc đã đổi tên', $third->product->fresh()->title);
        $this->assertSame('125000.00', $third->product->fresh()->regular_price);
    }

    public function test_variant_with_nonorderable_package_does_not_create_product(): void
    {
        $medicine = Medicine::query()->create(['name' => 'Thuốc không bán']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'SYNC-NONORDER-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'sync-nonorder-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'sync-nonorder-basis-'.$medicine->id),
        ]);
        MedicinePackage::query()->create([
            'medicine_variant_id' => $variant->id,
            'package_code' => 'SYNC-NONORDER-PKG-'.$variant->id,
            'packaging_text' => 'Không đặt hàng',
            'package_identity_key' => hash('sha256', 'sync-nonorder-package-'.$variant->id),
            'is_orderable' => false,
        ]);

        $this->assertSame(0, app(PharmaProductCatalogSyncService::class)->syncMedicine($medicine));
        $this->assertSame(0, PharmaProductLink::query()->count());
    }
    public function test_existing_package_becomes_stale_when_nonorderable(): void
    {
        $medicine = Medicine::query()->create(['name' => 'Thuốc kiểm tra stale']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'STALE-SKU-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'stale-variant-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'stale-basis-'.$medicine->id),
            'status' => 'active',
        ]);
        $package = MedicinePackage::query()->create([
            'medicine_variant_id' => $variant->id,
            'package_code' => 'STALE-PKG-'.$variant->id,
            'packaging_text' => 'Hộp 20 viên',
            'package_identity_key' => hash('sha256', 'stale-package-'.$variant->id),
            'is_orderable' => true,
        ]);

        $sync = app(PharmaProductCatalogSyncService::class);
        $link = $sync->syncVariant($variant, $package);
        $link->product->forceFill(['is_active' => true, 'regular_price' => 90000])->save();

        $package->update(['is_orderable' => false]);
        $this->assertSame(0, $sync->syncMedicine($medicine));
        $this->assertSame('stale', $link->fresh()->sync_status);
        $this->assertFalse($link->product->fresh()->is_active);
        $this->assertSame('90000.00', $link->product->fresh()->regular_price);
        $this->assertSame(1, PharmaProductLink::query()->count());
    }

    public function test_variant_and_package_writes_dispatch_only_when_opted_in(): void
    {
        Bus::fake();
        config()->set('pharma.product_sync_enabled', false);
        $medicine = Medicine::query()->create(['name' => 'Thuốc kiểm thử queue']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'QUEUE-SKU-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'queue-variant-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'queue-basis-'.$medicine->id),
            'status' => 'active',
        ]);
        Bus::assertNotDispatched(SyncMedicineProducts::class);

        config()->set('pharma.product_sync_enabled', true);
        $variant->update(['presentation_text' => 'Vỉ 10 viên']);
        Bus::assertDispatched(SyncMedicineProducts::class, fn ($job) => $job->medicineId === $medicine->id);

        $package = MedicinePackage::query()->create([
            'medicine_variant_id' => $variant->id,
            'package_code' => 'QUEUE-PKG-'.$variant->id,
            'package_identity_key' => hash('sha256', 'queue-package-'.$variant->id),
            'packaging_text' => 'Hộp 10 viên',
            'is_orderable' => true,
        ]);
        $this->assertNotNull($package->id);
        Bus::assertDispatchedTimes(SyncMedicineProducts::class, 2);
        config()->set('pharma.product_sync_enabled', false);
    }

    public function test_backfill_command_is_registered_and_dry_run_does_not_write(): void
    {
        $medicine = Medicine::query()->create(['name' => 'Thuốc thử backfill']);
        MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'BACKFILL-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'backfill-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'backfill-basis-'.$medicine->id),
            'status' => 'active',
        ]);

        $this->artisan('pharma:products-backfill', ['--medicine-id' => $medicine->id])
            ->assertSuccessful();
        $this->assertSame(0, PharmaProductLink::query()->count());

        $this->artisan('pharma:products-backfill', [
            '--medicine-id' => $medicine->id,
            '--apply' => true,
        ])->assertSuccessful();
        $this->assertSame(1, PharmaProductLink::query()->count());
        $this->assertFalse(PharmaProductLink::query()->firstOrFail()->product->is_active);
    }

    public function test_sync_job_retry_is_idempotent_and_preserves_commercial_values(): void
    {
        config()->set('pharma.product_sync_enabled', false);
        $medicine = Medicine::query()->create(['name' => 'Thuốc retry']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'RETRY-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'retry-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'retry-basis-'.$medicine->id),
            'status' => 'active',
        ]);
        $job = new SyncMedicineProducts((int) $medicine->id);
        $sync = app(PharmaProductCatalogSyncService::class);
        $job->handle($sync);
        $link = PharmaProductLink::query()->firstOrFail();
        $link->product->forceFill(['regular_price' => 42000, 'quantity' => 7])->save();

        $job->handle($sync);
        $this->assertSame(1, PharmaProductLink::query()->count());
        $this->assertSame(1, Product::query()->whereKey($link->product_id)->count());
        $this->assertSame('42000.00', $link->product->fresh()->regular_price);
        $this->assertSame(7, $link->product->fresh()->quantity);
    }

    public function test_exhausted_job_marks_existing_link_error_without_changing_commercial_values(): void
    {
        config()->set('pharma.product_sync_enabled', false);
        $medicine = Medicine::query()->create(['name' => 'Thuốc lỗi queue']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'ERROR-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'error-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'error-basis-'.$medicine->id),
            'status' => 'active',
        ]);
        $link = app(PharmaProductCatalogSyncService::class)->syncVariant($variant);
        $link->product->forceFill(['regular_price' => 99000, 'quantity' => 3])->save();

        (new SyncMedicineProducts((int) $medicine->id))->failed(new RuntimeException('Simulated terminal failure'));
        $this->assertSame('error', $link->fresh()->sync_status);
        $this->assertStringContainsString('Simulated terminal failure', $link->fresh()->last_error);
        $this->assertSame('99000.00', $link->product->fresh()->regular_price);
        $this->assertSame(3, $link->product->fresh()->quantity);
    }

    public function test_database_rollback_does_not_persist_catalog_projection(): void
    {
        config()->set('pharma.product_sync_enabled', false);
        $medicine = Medicine::query()->create(['name' => 'Thuốc rollback']);
        $variant = MedicineVariant::query()->create([
            'medicine_id' => $medicine->id,
            'sku' => 'ROLLBACK-'.$medicine->id,
            'variant_identity_key' => hash('sha256', 'rollback-'.$medicine->id),
            'sku_basis_hash' => hash('sha256', 'rollback-basis-'.$medicine->id),
            'status' => 'active',
        ]);

        try {
            DB::transaction(function () use ($variant): void {
                app(PharmaProductCatalogSyncService::class)->syncVariant($variant);
                throw new RuntimeException('Force rollback');
            });
        } catch (RuntimeException $e) {
            $this->assertSame('Force rollback', $e->getMessage());
        }

        $this->assertSame(0, PharmaProductLink::query()->count());
        $this->assertSame(0, Product::query()->count());
    }

}
