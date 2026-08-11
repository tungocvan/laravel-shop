<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Models\Order;

class OrderQueryService
{
    public function countForCurrentUser(): int
    {
        $userId = Auth::id();

        return $userId === null
            ? 0
            : Order::query()->where('user_id', $userId)->count();
    }

    public function findByCode(string $code): ?Order
    {
        return Order::query()->where('order_code', $code)->first();
    }

    public function paginateForCurrentUser(int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()->where('user_id', Auth::id())->latest()->paginate($perPage);
    }

    public function findForCurrentUser(string $code): Order
    {
        return Order::query()
            ->with('items')
            ->where('order_code', $code)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }
}
