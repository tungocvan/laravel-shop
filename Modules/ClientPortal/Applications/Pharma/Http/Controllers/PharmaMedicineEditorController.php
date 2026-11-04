<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use LogicException;
use Modules\Category\Models\Category;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Services\MedicineVariantCreationService;

final class PharmaMedicineEditorController extends Controller
{
    private function authorizeAction(Request $request, ApplicationRegistry $registry, string $action): void
    {
        abort_unless($request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.products.'.$action), 403);
    }

    private function formData(ApplicationRegistry $registry, ClientPortalSettingsService $settings, ?Medicine $medicine): array
    {
        $application = $registry->find('pharma');
        abort_if($application === null, 404);

        return [
            'application' => $application,
            'applicationPresentation' => $settings->applicationPresentation($application),
            'medicine' => $medicine,
            'categories' => Category::query()->where('type', 'medicine')->orderBy('sort_order')->orderBy('name')->get(),
            'selectedCategories' => $medicine ? $medicine->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all() : [],
        ];
    }

    public function create(Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings)
    {
        $this->authorizeAction($request, $registry, 'create');

        return view('ClientPortal::applications.pharma.medicine-editor', $this->formData($registry, $settings, null));
    }

    public function edit(int $medicine, Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, MedicineService $service)
    {
        $this->authorizeAction($request, $registry, 'edit');

        return view('ClientPortal::applications.pharma.medicine-editor', $this->formData($registry, $settings, $service->findOrFail($medicine)));
    }

    public function store(Request $request, ApplicationRegistry $registry, MedicineService $service)
    {
        $this->authorizeAction($request, $registry, 'create');
        try {
            $medicine = $service->store($this->validated($request));
        } catch (LogicException $e) {
            return back()->withInput()->withErrors(['medicine' => $e->getMessage()]);
        }

        return redirect()->route('client.pharma.products.edit', $medicine->id)->with('success', 'Đã thêm thuốc.');
    }

    public function update(int $medicine, Request $request, ApplicationRegistry $registry, MedicineService $service)
    {
        $this->authorizeAction($request, $registry, 'edit');
        try {
            $service->update($medicine, $this->validated($request));
        } catch (LogicException $e) {
            return back()->withInput()->withErrors(['medicine' => $e->getMessage()]);
        }

        return redirect()->route('client.pharma.products.edit', $medicine)->with('success', 'Đã cập nhật thuốc.');
    }

    public function destroy(int $medicine, Request $request, ApplicationRegistry $registry)
    {
        $this->authorizeAction($request, $registry, 'delete');

        $reason = DB::transaction(function () use ($medicine): ?string {
            $master = Medicine::query()->whereKey($medicine)->lockForUpdate()->firstOrFail();
            $variants = $master->variants()->lockForUpdate()->get();

            // Business records must never be removed as a side effect of deleting a medicine.
            $protectedRelations = [
                'aliases' => 'tên thuốc thay thế',
                'profiles' => 'hồ sơ sản phẩm',
                'sources' => 'nguồn dữ liệu',
                'priceListItems' => 'bảng giá',
                'supplierTrackings' => 'điều kiện nhà cung cấp',
                'drugBidAwards' => 'trúng thầu',
                'categories' => 'nhóm thuốc',
                'medicineCategories' => 'phân loại thuốc',
            ];
            foreach ($protectedRelations as $relation => $label) {
                if ($master->{$relation}()->exists()) {
                    return 'Thuốc đang liên kết với '.$label.'. Vui lòng xử lý liên kết trước khi xóa.';
                }
            }

            // Check every database FK referencing either the medicine or its SKUs.
            // This protects inventory, orders and future modules without hardcoding table names.
            $references = DB::select(
                "SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name, REFERENCED_TABLE_NAME AS referenced_table
                   FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND REFERENCED_TABLE_NAME IN ('pharma_medicines', 'pharma_medicine_variants')
                    AND REFERENCED_COLUMN_NAME = 'id'"
            );
            $variantIds = $variants->modelKeys();
            $importLinks = [];
            foreach ($references as $reference) {
                $table = (string) $reference->table_name;
                $column = (string) $reference->column_name;
                $parent = (string) $reference->referenced_table;
                if ($parent === 'pharma_medicines' && $table === 'pharma_medicine_variants') {
                    continue;
                }

                // Import staging rows are audit history, not stock/order transactions.
                // Only unlink their matched IDs when the FK column is nullable.
                if ($table === 'pharma_medicine_import_rows'
                    && (($parent === 'pharma_medicines' && $column === 'matched_medicine_id')
                        || ($parent === 'pharma_medicine_variants' && $column === 'matched_variant_id'))) {
                    $nullable = DB::table('information_schema.COLUMNS')
                        ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                        ->where('TABLE_NAME', $table)
                        ->where('COLUMN_NAME', $column)
                        ->where('IS_NULLABLE', 'YES')
                        ->exists();
                    if (! $nullable) {
                        return 'Không thể xóa: cột '.$table.'.'.$column.' không cho phép bỏ liên kết lịch sử import.';
                    }
                    $importLinks[] = [$column, $parent];
                    continue;
                }

                // All other FK references remain protected, including stock and orders.
                $query = DB::table($table);
                $exists = $parent === 'pharma_medicines'
                    ? $query->where($column, $master->id)->exists()
                    : ($variantIds !== [] && $query->whereIn($column, $variantIds)->exists());
                if ($exists) {
                    return 'Không thể xóa: đã có dữ liệu liên kết tại bảng '.$table.'.';
                }
            }

            // Model relations can exist without foreign-key constraints.
            foreach ($variants as $variant) {
                if ($variant->packages()->exists() || $variant->aliases()->exists()) {
                    return 'SKU '.$variant->sku.' đang có quy cách đóng gói hoặc tên thay thế.';
                }
            }

            // Keep import row payload, batch and audit status; only clear stale matches.
            foreach ($importLinks as [$column, $parent]) {
                $query = DB::table('pharma_medicine_import_rows');
                if ($parent === 'pharma_medicines') {
                    $query->where($column, $master->id)->update([$column => null]);
                } elseif ($variantIds !== []) {
                    $query->whereIn($column, $variantIds)->update([$column => null]);
                }
            }

            // All SKUs are unreferenced; remove them together with the master atomically.
            $master->variants()->delete();
            $master->delete();

            return null;
        });

        if ($reason !== null) {
            return redirect()->back()->with('error', $reason);
        }

        return redirect()->route('client.pharma.products')
            ->with('success', 'Đã xóa thuốc và các SKU chưa phát sinh dữ liệu nghiệp vụ.');
    }

    public function createVariant(int $medicine, Request $request, ApplicationRegistry $registry, ClientPortalSettingsService $settings, MedicineService $service)
    {
        $this->authorizeAction($request, $registry, 'create-variant');
        $master = $service->findOrFail($medicine);

        if ($master->variants()->exists()) {
            return redirect()->route('client.pharma.products', ['q' => $master->medicine_code])
                ->with('success', 'Thuốc đã có SKU trong danh mục.');
        }

        return view('ClientPortal::applications.pharma.variant-create', $this->formData($registry, $settings, $master));
    }

    public function storeVariant(int $medicine, Request $request, ApplicationRegistry $registry, MedicineVariantCreationService $creator)
    {
        $this->authorizeAction($request, $registry, 'create-variant');

        $data = $request->validate([
            'strength_text' => ['required', 'string', 'max:255'],
            'presentation_text' => ['required', 'string', 'max:255'],
            'base_unit' => ['required', 'string', 'max:100'],
            'declared_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $variant = $creator->createFirst($medicine, $data);

        return redirect()->route('client.pharma.products.show', $variant->id)
            ->with('success', 'Đã tạo SKU '. $variant->sku .'.');
    }

    private function validated(Request $request): array
    {
        $fields = [
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(array_keys(Medicine::productTypeOptions()))],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('type', 'medicine')],
            'declared_price' => ['nullable', 'numeric', 'min:0'],
            'is_special_control' => ['boolean'],
            'visa_validity_date' => ['nullable', 'date'],
            'gmp_certification_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
        foreach ([
            'active_ingredients', 'concentration', 'dosage_form', 'route_of_administration',
            'unit', 'packaging_specification', 'registration_number', 'shelf_life',
            'registered_company', 'manufacturing_company', 'manufacturing_country',
            'circular_order_number', 'circular_group', 'therapeutic_group',
        ] as $field) {
            $fields[$field] = ['nullable', 'string', 'max:255'];
        }

        $data = $request->validate($fields);
        $data['category_ids'] = $data['category_ids'] ?? [];
        $data['is_special_control'] = $request->boolean('is_special_control');

        return $data;
    }
}
