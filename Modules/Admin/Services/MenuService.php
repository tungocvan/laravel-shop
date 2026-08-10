<?php

namespace Modules\Admin\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;
use Spatie\Permission\Models\Permission;

class MenuService
{
    private const MAX_SORT_ITEMS = 500;
    private const MAX_SORT_DEPTH = 10;

    public function idsForSelection(array $filters = []): array
    {
        return $this->query($filters)->pluck('id')->map(fn ($id): string => (string) $id)->toArray();
    }

    public function menusByIds(array $ids): Collection
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return new Collection();
        }

        return AdminMenu::menu()
            ->with('parent')
            ->whereKey($ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function rootTree(array $filters = []): Collection
    {
        return $this->query($filters)->with('children')->whereNull('parent_id')->get();
    }

    public function stats(array $filters = []): array
    {
        $query = $this->query($filters);
        return [
            'totalMenus' => (clone $query)->count(),
            'activeMenus' => (clone $query)->where('is_active', true)->count(),
        ];
    }

    public function permissionOptions(): array
    {
        return Permission::query()->orderBy('name')->pluck('name')->all();
    }

    public function delete(int|string $id): bool
    {
        $menu = $this->findMenu($id);
        if (! $menu) return false;
        DB::transaction(fn () => $menu->delete());
        AdminMenu::clearMenuCache();
        return true;
    }

    public function toggleStatus(int|string $id): bool
    {
        $menu = $this->findMenu($id);
        if (! $menu) return false;
        DB::transaction(fn () => $menu->update(['is_active' => ! $menu->is_active]));
        AdminMenu::clearMenuCache();
        return true;
    }

    public function duplicate(int|string $id): bool
    {
        $original = AdminMenu::menu()->with('children')->whereKey($id)->first();
        if (! $original) return false;
        DB::transaction(fn () => $this->duplicateRecursive($original, $original->parent_id));
        AdminMenu::clearMenuCache();
        return true;
    }

    public function bulkDelete(array $ids): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) return 0;
        $count = DB::transaction(function () use ($ids): int {
            $menus = AdminMenu::menu()->whereKey($ids)->get();
            $menus->each->delete();
            return $menus->count();
        });
        AdminMenu::clearMenuCache();
        return $count;
    }

    public function bulkToggleStatus(array $ids, bool $status): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) return 0;
        $count = DB::transaction(function () use ($ids, $status): int {
            $menus = AdminMenu::menu()->whereKey($ids)->get();
            $menus->each(fn (AdminMenu $menu) => $menu->update(['is_active' => $status]));
            return $menus->count();
        });
        AdminMenu::clearMenuCache();
        return $count;
    }

    public function bulkAssignPermission(array $ids, ?string $permission): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) return 0;
        $count = DB::transaction(function () use ($ids, $permission): int {
            $menus = AdminMenu::menu()->whereKey($ids)->get();
            $menus->each(fn (AdminMenu $menu) => $menu->update(['can' => $permission]));
            return $menus->count();
        });
        AdminMenu::clearMenuCache();
        return $count;
    }

    public function updateOrder(array $items): void
    {
        $this->validateOrderPayload($items);
        DB::transaction(fn () => $this->updateOrderRecursive($items, null));
        AdminMenu::clearMenuCache();
    }

    public function query(array $filters = []): Builder
    {
        $query = AdminMenu::menu();
        $search = mb_substr(trim((string) ($filters['search'] ?? '')), 0, 100);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")->orWhere('url', 'like', "%{$search}%");
            });
        }
        $status = $filters['status'] ?? 'all';
        if ($status === 'active') $query->where('is_active', true);
        elseif ($status === 'inactive') $query->where('is_active', false);
        return $query->orderBy('sort_order');
    }

    private function findMenu(int|string $id): ?AdminMenu
    {
        return AdminMenu::menu()->whereKey($id)->first();
    }

    private function duplicateRecursive(AdminMenu $node, ?int $parentId): void
    {
        $new = $node->replicate();
        $new->name = $node->name.' (Copy)';
        $new->parent_id = $parentId;
        $new->slug = $this->generateUniqueSlug($node);
        $new->sort_order = $node->sort_order + 1;
        $new->save();
        foreach ($node->children as $child) $this->duplicateRecursive($child, $new->id);
    }

    private function generateUniqueSlug(AdminMenu $original): string
    {
        $base = $original->slug ? $original->slug.'-copy' : Str::slug($original->name.' copy');
        $slug = $base;
        $i = 1;
        while (AdminMenu::query()->where('slug', $slug)->exists()) $slug = "{$base}-".$i++;
        return $slug;
    }

    private function updateOrderRecursive(array $items, ?int $parentId): void
    {
        foreach ($items as $index => $item) {
            AdminMenu::menu()->whereKey((int) $item['id'])->update(['parent_id' => $parentId, 'sort_order' => $index]);
            if (! empty($item['children'])) $this->updateOrderRecursive($item['children'], (int) $item['id']);
        }
    }

    private function validateOrderPayload(array $items): void
    {
        $ids = [];
        $this->collectAndValidateOrderIds($items, $ids, 1);
        if (count($ids) > self::MAX_SORT_ITEMS) throw new \InvalidArgumentException('Payload sap xep menu vuot qua gioi han cho phep.');
        if (count($ids) !== count(array_unique($ids))) throw new \InvalidArgumentException('Payload sap xep menu co ID bi trung.');
        if ($ids === []) return;
        if (AdminMenu::menu()->whereKey($ids)->count() !== count($ids)) throw new \InvalidArgumentException('Payload sap xep menu co ID khong hop le.');
    }

    private function collectAndValidateOrderIds(array $items, array &$ids, int $depth): void
    {
        if ($depth > self::MAX_SORT_DEPTH) throw new \InvalidArgumentException('Payload sap xep menu vuot qua do sau cho phep.');
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['id']) || ! is_numeric($item['id'])) throw new \InvalidArgumentException('Payload sap xep menu khong hop le.');
            $ids[] = (int) $item['id'];
            if (! empty($item['children'])) {
                if (! is_array($item['children'])) throw new \InvalidArgumentException('Payload children khong hop le.');
                $this->collectAndValidateOrderIds($item['children'], $ids, $depth + 1);
            }
        }
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)->filter(fn ($id): bool => is_numeric($id))->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }
}
