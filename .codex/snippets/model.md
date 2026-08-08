# Model Snippet

Use this pattern for module Eloquent models.

```php
<?php

namespace Modules\Example\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\User;

class Item extends Model
{
    protected $table = 'example_items';

    protected $fillable = [
        'name',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```
