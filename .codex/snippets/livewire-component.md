# Livewire Component Snippet

Use this pattern for module Livewire 3 components.

```php
<?php

namespace Modules\Example\Livewire\Items;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Example\Services\ItemService;

class ItemTable extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public function delete(int $itemId, ItemService $items): void
    {
        $this->authorize('example.item.delete');

        $items->delete($itemId);

        $this->resetPage();
        $this->dispatch('item-deleted');
    }

    public function render(ItemService $items)
    {
        return view('example::livewire.items.item-table', [
            'items' => $items->paginateForAdmin($this->search),
        ]);
    }
}
```

Expected alias for this example: `example.items.item-table`.
