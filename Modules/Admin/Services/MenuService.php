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

    public function idsForBranch(int|string $menuId): array
    {
        if (! is_numeric($menuId)) {
            return [];
        }

        $root = AdminMenu::menu()->whereKey((int) $menuId)->first();
        if (! $root) {
            return [];
        }

        $ids = [];
        $queue = [(int) $root->getKey()];
        $depth = 0;

        while ($queue !== [] && $depth < self::MAX_SORT_DEPTH) {
            $current = array_values(array_unique(array_map('intval', $queue)));
            $ids = array_merge($ids, $current);
            $queue = AdminMenu::menu()->whereIn('parent_id', $current)->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $depth++;
        }

        return collect($ids)->unique()->map(fn ($id): string => (string) $id)->values()->all();
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

    public function findForForm(int|string $id): AdminMenu
    {
        return AdminMenu::menu()->with('children')->whereKey($id)->firstOrFail();
    }

    public function parentOptions(int|string|null $excludeId = null): Collection
    {
        $query = AdminMenu::menu();

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->orderBy('sort_order')->orderBy('name')->get();
    }

    public function saveForm(array $data, int|string|null $menuId = null): AdminMenu
    {
        return DB::transaction(function () use ($data, $menuId): AdminMenu {
            $menu = $menuId !== null ? $this->findForForm($menuId) : new AdminMenu();
            $name = trim((string) $data['name']);
            $parentId = $this->validParentId($data['parent_id'] ?? null, $menuId);
            $isSection = (bool) ($data['is_section'] ?? false);

            $menu->name = $name;
            $menu->url = $isSection ? null : $this->nullableString($data['url'] ?? null);
            $menu->icon = $this->nullableString($data['icon'] ?? null);
            $menu->can = $this->nullableString($data['can'] ?? null);
            $menu->parent_id = $parentId;
            $menu->is_active = (bool) ($data['is_active'] ?? true);

            if (! $menu->exists || $menu->isDirty('name')) {
                $menu->slug = $this->generateFormSlug($name, $menu->getKey());
            }

            if (! $menu->exists) {
                $menu->sort_order = ((int) AdminMenu::menu()->max('sort_order')) + 1;
            }

            $menu->save();
            AdminMenu::clearMenuCache();

            return $menu;
        });
    }

    public function createScannedMenus(array $candidates): int
    {
        if ($candidates === []) {
            return 0;
        }

        $created = DB::transaction(function () use ($candidates): int {
            $count = 0;
            $parents = [];

            foreach ($candidates as $candidate) {
                $routeName = trim((string) ($candidate['route_name'] ?? ''));
                $url = $this->nullableString($candidate['url'] ?? null);
                $name = trim((string) ($candidate['name'] ?? ''));
                $group = trim((string) ($candidate['group'] ?? ''));

                if ($routeName === '' || $url === null || $name === '') {
                    continue;
                }

                $exists = AdminMenu::menu()
                    ->where(function (Builder $query) use ($routeName, $url): void {
                        $query->where('slug', $routeName)->orWhere('url', $url);
                    })
                    ->exists();

                if ($exists) {
                    continue;
                }

                $parentId = null;
                if ($group !== '') {
                    if (! array_key_exists($group, $parents)) {
                        $parentSlug = 'module-'.Str::slug($group);
                        $parent = AdminMenu::menu()->where('slug', $parentSlug)->first();

                        if (! $parent) {
                            $parent = AdminMenu::query()->create([
                                'name' => Str::headline($group),
                                'slug' => $parentSlug,
                                'url' => null,
                                'icon' => 'folder',
                                'can' => null,
                                'parent_id' => null,
                                'is_active' => true,
                                'sort_order' => ((int) AdminMenu::menu()->max('sort_order')) + 1,
                            ]);
                        }

                        $parents[$group] = (int) $parent->getKey();
                    }

                    $parentId = $parents[$group];
                }

                AdminMenu::query()->create([
                    'name' => $name,
                    'slug' => $routeName,
                    'url' => $url,
                    'icon' => $this->nullableString($candidate['icon'] ?? null),
                    'can' => $this->nullableString($candidate['permission'] ?? null),
                    'parent_id' => $parentId,
                    'is_active' => true,
                    'sort_order' => ((int) AdminMenu::menu()->where('parent_id', $parentId)->max('sort_order')) + 1,
                ]);
                $count++;
            }

            return $count;
        });

        AdminMenu::clearMenuCache();

        return $created;
    }

    public function delete(int|string $id): bool
    {
        $menu = $this->findMenu($id);
        if (! $menu) {
            return false;
        }

        DB::transaction(fn () => $menu->delete());
        AdminMenu::clearMenuCache();

        return true;
    }

    public function toggleStatus(int|string $id): bool
    {
        $menu = $this->findMenu($id);
        if (! $menu) {
            return false;
        }

        DB::transaction(fn () => $menu->update(['is_active' => ! $menu->is_active]));
        AdminMenu::clearMenuCache();

        return true;
    }

    public function duplicate(int|string $id): bool
    {
        $original = AdminMenu::menu()->with('children')->whereKey($id)->first();
        if (! $original) {
            return false;
        }

        DB::transaction(fn () => $this->duplicateRecursive($original, $original->parent_id));
        AdminMenu::clearMenuCache();

        return true;
    }

    public function bulkDelete(array $ids): int
    {
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return 0;
        }

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
        if ($ids === []) {
            return 0;
        }

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
        if ($ids === []) {
            return 0;
        }

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
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('sort_order');
    }

    private function findMenu(int|string $id): ?AdminMenu
    {
        return AdminMenu::menu()->whereKey($id)->first();
    }

    private function validParentId(mixed $parentId, int|string|null $menuId): ?int
    {
        if (! is_numeric($parentId)) {
            return null;
        }

        $parentId = (int) $parentId;
        if ($menuId !== null && (int) $menuId === $parentId) {
            return null;
        }

        return AdminMenu::menu()->whereKey($parentId)->exists() ? $parentId : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function generateFormSlug(string $name, int|string|null $excludeId = null): string
    {
        $base = Str::slug($name) ?: 'menu';
        $slug = $base;
        $counter = 1;

        while (AdminMenu::query()
            ->where('slug', $slug)
            ->when($excludeId !== null, fn (Builder $query) => $query->whereKeyNot($excludeId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function duplicateRecursive(AdminMenu $node, ?int $parentId): void
    {
        $new = $node->replicate();
        $new->name = $node->name.' (Copy)';
        $new->parent_id = $parentId;
        $new->slug = $this->generateDuplicateSlug($node);
        $new->sort_order = $node->sort_order + 1;
        $new->save();

        foreach ($node->children as $child) {
            $this->duplicateRecursive($child, $new->id);
        }
    }

    private function generateDuplicateSlug(AdminMenu $original): string
    {
        $base = $original->slug ? $original->slug.'-copy' : Str::slug($original->name.' copy');
        $slug = $base;
        $i = 1;
        while (AdminMenu::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-".$i++;
        }

        return $slug;
    }

    private function updateOrderRecursive(array $items, ?int $parentId): void
    {
        foreach ($items as $index => $item) {
            AdminMenu::menu()->whereKey((int) $item['id'])->update(['parent_id' => $parentId, 'sort_order' => $index]);
            if (! empty($item['children'])) {
                $this->updateOrderRecursive($item['children'], (int) $item['id']);
            }
        }
    }

    private function validateOrderPayload(array $items): void
    {
        $ids = [];
        $this->collectAndValidateOrderIds($items, $ids, 1);
        if (count($ids) > self::MAX_SORT_ITEMS) {
            throw new \InvalidArgumentException('Payload sap xep menu vuot qua gioi han cho phep.');
        }
        if (count($ids) !== count(array_unique($ids))) {
            throw new \InvalidArgumentException('Payload sap xep menu co ID bi trung.');
        }
        if ($ids === []) {
            return;
        }
        if (AdminMenu::menu()->whereKey($ids)->count() !== count($ids)) {
            throw new \InvalidArgumentException('Payload sap xep menu co ID khong hop le.');
        }
    }

    private function collectAndValidateOrderIds(array $items, array &$ids, int $depth): void
    {
        if ($depth > self::MAX_SORT_DEPTH) {
            throw new \InvalidArgumentException('Payload sap xep menu vuot qua do sau cho phep.');
        }

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['id']) || ! is_numeric($item['id'])) {
                throw new \InvalidArgumentException('Payload sap xep menu khong hop le.');
            }

            $ids[] = (int) $item['id'];
            if (! empty($item['children'])) {
                if (! is_array($item['children'])) {
                    throw new \InvalidArgumentException('Payload children khong hop le.');
                }
                $this->collectAndValidateOrderIds($item['children'], $ids, $depth + 1);
            }
        }
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)->filter(fn ($id): bool => is_numeric($id))->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }
}
