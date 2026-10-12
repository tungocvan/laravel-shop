<?php

namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\MedicineCategory;

class MedicineCategoryController extends Controller
{
    public function index()
    {
        $categories = MedicineCategory::query()->with('parent:id,name')->withCount(['children', 'medicines'])->orderBy('sort_order')->orderBy('name')->paginate(50);

        return view('Pharma::pages.medicine-categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        MedicineCategory::query()->create($data);

        return back()->with('success', 'Đã tạo danh mục thuốc.');
    }

    public function update(Request $request, MedicineCategory $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        if ($data['parent_id'] !== null && $this->isDescendant($data['parent_id'], $category->id)) {
            throw ValidationException::withMessages(['parent_id' => 'Không thể chọn danh mục con làm cha.']);
        }
        $category->update($data);

        return back()->with('success', 'Đã cập nhật danh mục thuốc.');
    }

    public function destroy(MedicineCategory $category): RedirectResponse
    {
        if ($category->children()->exists() || $category->medicines()->exists()) {
            return back()->withErrors(['category' => 'Danh mục đang có danh mục con hoặc thuốc liên kết, không thể xóa.']);
        }
        $category->delete();

        return back()->with('success', 'Đã xóa danh mục thuốc.');
    }

    private function validated(Request $request, ?MedicineCategory $category = null): array
    {
        $slug = Str::slug((string) $request->input('slug', $request->input('name', '')));
        $request->merge(['slug' => $slug, 'parent_id' => $request->input('parent_id') ?: null]);

        return $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', Rule::unique('pharma_medicine_categories', 'slug')->ignore($category?->id)],
            'parent_id' => ['nullable', 'integer', Rule::exists('pharma_medicine_categories', 'id'), Rule::notIn(array_filter([$category?->id]))],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function isDescendant(int $candidate, int $ancestor): bool
    {
        $visited = [];
        while ($candidate > 0) {
            if ($candidate === $ancestor || isset($visited[$candidate])) {
                return true;
            }
            $visited[$candidate] = true;
            $candidate = (int) MedicineCategory::query()->whereKey($candidate)->value('parent_id');
        }

        return false;
    }
}
