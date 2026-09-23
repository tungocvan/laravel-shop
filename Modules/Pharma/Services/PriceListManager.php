<?php

namespace Modules\Pharma\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Models\PriceListPurpose;

class PriceListManager
{
    public function validateHeader(array $data, ?PriceList $priceList = null): array
    {
        $type = (string) ($data['type'] ?? PriceList::TYPE_GLOBAL);
        $customerSource = $data['customer_source'] ?? ($type === PriceList::TYPE_CUSTOMER ? PriceList::CUSTOMER_SOURCE_PARTNER : null);
        $partnerId = $data['partner_id'] ?? null;
        $officialFacilityId = $data['official_facility_id'] ?? null;
        $managerUserId = $data['manager_user_id'] ?? null;
        $purposeId = $data['purpose_id'] ?? null;

        if (! in_array($type, [PriceList::TYPE_GLOBAL, PriceList::TYPE_CUSTOMER], true)) {
            throw ValidationException::withMessages(['type' => 'Loại bảng giá không hợp lệ.']);
        }
        if ($type === PriceList::TYPE_GLOBAL) {
            $customerSource = null;
            $partnerId = null;
            $officialFacilityId = null;
            $managerUserId = null;
            $purposeId = null;
        }
        if ($type === PriceList::TYPE_CUSTOMER) {
            if (! in_array($customerSource, [PriceList::CUSTOMER_SOURCE_PARTNER, PriceList::CUSTOMER_SOURCE_OFFICIAL_FACILITY], true)) {
                throw ValidationException::withMessages(['customer_source' => 'Nguồn khách hàng không hợp lệ.']);
            }
            if ($customerSource === PriceList::CUSTOMER_SOURCE_PARTNER) {
                if ($partnerId !== null) {
                    $partner = Partner::query()->find($partnerId);
                    $types = $partner?->partner_types ?? [];
                    if (! $partner || $partner->status !== 'active' || ! in_array('customer', $types, true)) {
                        throw ValidationException::withMessages(['partner_id' => 'Khách hàng phải là Partner đang hoạt động và có loại customer.']);
                    }
                }
                $officialFacilityId = null;
            } else {
                if ($officialFacilityId !== null && ! OfficialSourceFacility::query()->whereKey($officialFacilityId)->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['official_facility_id' => 'Cơ sở KCB phải tồn tại và đang hoạt động trong kho dữ liệu nguồn.']);
                }
                $partnerId = null;
            }
            if (! $managerUserId || ! User::query()->whereKey($managerUserId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['manager_user_id' => 'Người phụ trách phải là user đang hoạt động.']);
            }
            if (! $purposeId || ! PriceListPurpose::query()->whereKey($purposeId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['purpose_id' => 'Vui lòng chọn mục đích sử dụng bảng giá đang hoạt động.']);
            }
        }

        $from = $data['effective_from'] ?? null;
        $to = $data['effective_to'] ?? null;
        if ($from && $to && $to < $from) {
            throw ValidationException::withMessages(['effective_to' => 'Ngày hết hiệu lực không được trước ngày hiệu lực.']);
        }

        return array_merge($data, [
            'customer_source' => $customerSource,
            'partner_id' => $partnerId,
            'official_facility_id' => $officialFacilityId,
            'manager_user_id' => $managerUserId,
            'purpose_id' => $purposeId,
        ]);
    }

    public function validateItem(array $data): array
    {
        $variant = MedicineVariant::query()->with('medicine')->find($data['medicine_variant_id'] ?? null);
        if (! $variant) {
            throw ValidationException::withMessages(['medicine_variant_id' => 'SKU/variant không tồn tại.']);
        }

        $packageId = $data['medicine_package_id'] ?? null;
        if ($packageId !== null) {
            $package = MedicinePackage::query()->whereKey($packageId)->where('medicine_variant_id', $variant->id)->first();
            if (! $package) {
                throw ValidationException::withMessages(['medicine_package_id' => 'Quy cách không thuộc SKU đã chọn.']);
            }
        }

        $declared = $variant->medicine?->declared_price;
        $company = $data['company_sale_price'] ?? null;
        foreach (['company_sale_price', 'actual_receivable_price', 'invoice_price'] as $field) {
            if (isset($data[$field]) && (float) $data[$field] < 0) {
                throw ValidationException::withMessages([$field => 'Giá không được âm.']);
            }
        }
        if ($company !== null && $declared !== null && (float) $company > (float) $declared) {
            throw ValidationException::withMessages(['company_sale_price' => 'Giá bán công ty không được vượt quá giá kê khai '.number_format((float) $declared, 0, ',', '.').' ₫.']);
        }

        return array_merge($data, [
            'medicine_id' => $variant->medicine_id,
            'declared_price_snapshot' => $declared,
            'identity_key' => PriceListItem::makeIdentityKey($variant->id, $packageId ? (int) $packageId : null),
        ]);
    }

    public function activate(PriceList $priceList, ?int $approvedBy = null): PriceList
    {
        $priceList->load('items');
        if ($priceList->items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Không thể kích hoạt bảng giá chưa có sản phẩm.']);
        }

        $this->validateHeader($priceList->toArray(), $priceList);
        $identities = [];
        foreach ($priceList->items as $item) {
            if ($item->company_sale_price === null) {
                throw ValidationException::withMessages(['items' => "SKU {$item->medicine_variant_id} chưa có giá bán công ty."]);
            }
            if ($item->declared_price_snapshot !== null && (float) $item->company_sale_price > (float) $item->declared_price_snapshot) {
                throw ValidationException::withMessages(['items' => "SKU {$item->medicine_variant_id} có giá bán vượt giá kê khai snapshot."]);
            }
            if (in_array($item->identity_key, $identities, true)) {
                throw ValidationException::withMessages(['items' => 'Bảng giá có SKU/package bị trùng.']);
            }
            $identities[] = $item->identity_key;
        }

        $overlap = PriceList::query()->whereKeyNot($priceList->id)->where('status', PriceList::STATUS_ACTIVE)->where('type', $priceList->type)
            ->when($priceList->type === PriceList::TYPE_CUSTOMER, function ($query) use ($priceList): void {
                $query->where('customer_source', $priceList->customer_source);
                $priceList->customer_source === PriceList::CUSTOMER_SOURCE_OFFICIAL_FACILITY
                    ? $query->where('official_facility_id', $priceList->official_facility_id)
                    : $query->where('partner_id', $priceList->partner_id);
            }, fn ($query) => $query->whereNull('partner_id')->whereNull('official_facility_id'))
            ->where(function ($query) use ($priceList): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $priceList->effective_from ?? '1000-01-01');
            })
            ->where(function ($query) use ($priceList): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $priceList->effective_to ?? '9999-12-31');
            })->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['effective_from' => 'Đã có bảng giá ACTIVE bị chồng lấn thời gian trong cùng phạm vi.']);
        }

        $priceList->forceFill(['status' => PriceList::STATUS_ACTIVE, 'approved_by' => $approvedBy, 'approved_at' => now()])->save();

        return $priceList->refresh();
    }

    public function deactivate(PriceList $priceList): PriceList
    {
        $priceList->forceFill(['status' => PriceList::STATUS_INACTIVE])->save();

        return $priceList->refresh();
    }

    public function clone(PriceList $source, array $overrides = []): PriceList
    {
        return DB::transaction(function () use ($source, $overrides): PriceList {
            $source->load('items');
            $copy = $source->replicate(['code', 'status', 'approved_by', 'approved_at']);
            $copy->fill($overrides);
            $copy->code = $overrides['code'] ?? ($source->code.'-COPY-'.now()->format('YmdHis'));
            $copy->status = PriceList::STATUS_DRAFT;
            $copy->approved_by = null;
            $copy->approved_at = null;
            $copy->save();
            foreach ($source->items as $item) {
                $newItem = $item->replicate(['price_list_id']);
                $newItem->price_list_id = $copy->id;
                $newItem->save();
            }

            return $copy->load('items');
        });
    }

    public function deleteDraft(PriceList $priceList): void
    {
        $this->deleteRemovable($priceList);
    }

    public function deleteRemovable(PriceList $priceList): void
    {
        if (! in_array($priceList->status, [PriceList::STATUS_DRAFT, PriceList::STATUS_INACTIVE], true)) {
            throw ValidationException::withMessages([
                'price_list' => 'Chỉ bảng giá DRAFT hoặc INACTIVE mới được xóa. Bảng giá ACTIVE phải được ngưng trước khi xóa.',
            ]);
        }

        DB::transaction(function () use ($priceList): void {
            $priceList->items()->delete();
            $priceList->delete();
        });
    }

    public function deleteSelected(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids))));

        if ($ids === []) {
            throw ValidationException::withMessages(['price_list' => 'Vui lòng chọn ít nhất một bảng giá để xóa.']);
        }

        return DB::transaction(function () use ($ids): int {
            $priceLists = PriceList::query()->whereKey($ids)->lockForUpdate()->get();

            if ($priceLists->count() !== count($ids)) {
                throw ValidationException::withMessages(['price_list' => 'Một hoặc nhiều bảng giá đã thay đổi hoặc không còn tồn tại. Vui lòng tải lại danh sách.']);
            }

            $blocked = $priceLists->filter(
                fn (PriceList $priceList) => ! in_array($priceList->status, [PriceList::STATUS_DRAFT, PriceList::STATUS_INACTIVE], true)
            );

            if ($blocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'price_list' => 'Không thể xóa vì có bảng giá ACTIVE/ARCHIVED trong lựa chọn. Hãy ngưng bảng giá ACTIVE trước khi xóa.',
                ]);
            }

            foreach ($priceLists as $priceList) {
                $priceList->items()->delete();
                $priceList->delete();
            }

            return $priceLists->count();
        });
    }
}
