<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
