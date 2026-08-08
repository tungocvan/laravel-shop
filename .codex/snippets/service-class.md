# Service Class Snippet

Use this pattern for module business services.

```php
<?php

namespace Modules\Example\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Example\Models\Item;

class ItemService
{
    public function paginateForAdmin(string $search = ''): LengthAwarePaginator
    {
        return Item::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15);
    }

    public function create(array $data): Item
    {
        return DB::transaction(function () use ($data): Item {
            return Item::query()->create($data);
        });
    }

    public function delete(int $itemId): void
    {
        DB::transaction(function () use ($itemId): void {
            Item::query()->findOrFail($itemId)->delete();
        });
    }
}
```
