<?php

namespace Modules\Category\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Category\Models\CategoryType;

class CategoryTypeService
{
    public function listForAdmin(bool $activeOnly = false): Collection
    {
        return CategoryType::query()
            ->when($activeOnly, fn ($query) => $query->active())
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function firstActiveType(): ?string
    {
        return CategoryType::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->value('type');
    }

    public function find(string $type): CategoryType
    {
        return CategoryType::query()->findOrFail($type);
    }

    public function create(array $data): CategoryType
    {
        $validated = Validator::make($data, [
            'type' => ['required', 'alpha_dash', 'max:255', 'unique:category_types,type'],
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($validated) {
            $sortOrder = ((int) CategoryType::query()
                ->lockForUpdate()
                ->get(['sort_order'])
                ->max('sort_order')) + 1;

            return CategoryType::query()->create([
                ...$validated,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
        });
    }

    public function update(string $type, array $data): CategoryType
    {
        $validated = Validator::make($data, [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($type, $validated) {
            $categoryType = CategoryType::query()->lockForUpdate()->findOrFail($type);
            $categoryType->update($validated);

            return $categoryType->refresh();
        });
    }

    public function delete(string $type): void
    {
        DB::transaction(function () use ($type) {
            $categoryType = CategoryType::query()->lockForUpdate()->findOrFail($type);

            if (Category::query()->where('type', $type)->exists()) {
                throw ValidationException::withMessages([
                    'selectedType' => 'Không thể xóa loại đang có danh mục.',
                ]);
            }

            $categoryType->delete();
        });
    }
}
