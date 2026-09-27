<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\PriceList;

final class PriceListDeactivationWorkflow
{
    public function request(int $userId, int $priceListId, string $reason): PriceList
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['deactivation_reason' => 'Vui lòng nhập lý do yêu cầu ngừng kích hoạt.']);
        }

        return DB::transaction(function () use ($userId, $priceListId, $reason): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            if ((int) $list->manager_user_id !== $userId || $list->status !== PriceList::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['price_list' => 'Chỉ User phụ trách mới được yêu cầu ngừng bảng giá đang ACTIVE.']);
            }

            $list->forceFill([
                'status' => PriceList::STATUS_PENDING_DEACTIVATION,
                'deactivation_requested_by' => $userId,
                'deactivation_requested_at' => now(),
                'deactivation_reason' => $reason,
                'deactivated_by' => null,
                'deactivated_at' => null,
            ])->save();

            return $list->refresh();
        });
    }

    public function approveRequest(int $approverUserId, int $priceListId): PriceList
    {
        return DB::transaction(function () use ($approverUserId, $priceListId): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            if ($list->status !== PriceList::STATUS_PENDING_DEACTIVATION) {
                throw ValidationException::withMessages(['price_list' => 'Bảng giá không còn chờ duyệt ngừng kích hoạt.']);
            }

            $list->forceFill([
                'status' => PriceList::STATUS_INACTIVE,
                'deactivated_by' => $approverUserId,
                'deactivated_at' => now(),
            ])->save();

            return $list->refresh();
        });
    }

    public function deactivateDirectly(int $approverUserId, int $priceListId, string $reason): PriceList
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['deactivation_reason' => 'Vui lòng nhập lý do ngừng kích hoạt.']);
        }

        return DB::transaction(function () use ($approverUserId, $priceListId, $reason): PriceList {
            $list = PriceList::query()->lockForUpdate()->findOrFail($priceListId);
            if ($list->status !== PriceList::STATUS_ACTIVE) {
                throw ValidationException::withMessages(['price_list' => 'Chỉ bảng giá ACTIVE mới được ngừng kích hoạt trực tiếp.']);
            }

            $list->forceFill([
                'status' => PriceList::STATUS_INACTIVE,
                'deactivation_requested_by' => null,
                'deactivation_requested_at' => null,
                'deactivation_reason' => $reason,
                'deactivated_by' => $approverUserId,
                'deactivated_at' => now(),
            ])->save();

            return $list->refresh();
        });
    }
}
