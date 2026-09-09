<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class InventoryAuthorizationService
{
    public function authorize(User $actor, string $ability): void
    {
        Gate::forUser($actor)->authorize($ability);
    }
}
