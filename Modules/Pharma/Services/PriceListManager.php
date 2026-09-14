<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;

class PriceListManager
{
    public function validateHeader(array $data, ?PriceList $priceList = null): array
    {
        $type = (string) ($data['type'] ?? PriceList::TYPE_GLOBAL);
        $partnerId = $data['partner_id'] ?? null;

        if (! in_array($type, [PriceList::TYPE_GLOBAL, PriceList::TYPE_CUSTOMER], true)) {
            throw ValidationException::withMessages(['type' => 'Loại bảng giá không hợp lệ.']);
        }
        if ($type === PriceList::TYPE_GLOBAL) {
            $partnerId = null;
        }
        if ($type === PriceList::TYPE_CUSTOMER) {
            $partner = Partner::query()->find($partnerId);
            $types = $partner?->partner_types ?? [];
            if (! $partner || $partner->status !== 'active' || ! in_array('customer', $types, true)) {
                throw ValidationException::withMessages(['partner_id' => 'Khách hàng phải là Partner đang hoạt động và có loại customer.']);
            }
        }

        $from = $data['effective_from'] ?? null;
        $to = $data['effective_to'] ?? null;
        if ($from && $to && $to < $from) {
            throw ValidationException::withMessages(['effective_to' => 'Ngày hết hiệu lực không được trước ngày hiệu lực.']);
        }

        return array_merge($data, ['partner_id' => $partnerId]);
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
            ->when($priceList->type === PriceList::TYPE_CUSTOMER, fn ($query) => $query->where('partner_id', $priceList->partner_id), fn ($query) => $query->whereNull('partner_id'))
            ->where(function ($query) use ($priceList): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $priceList->effective_from ?? '1000-01-01');
            })
            ->where(function ($query) use ($priceList): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $priceList->effective_to ?? '9999-12-31');
            })
            ->exists();
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
        if (! $priceList->isDraft()) {
            throw ValidationException::withMessages([
                'price_list' => 'Chỉ bảng giá DRAFT mới được xóa. Bảng giá đã kích hoạt phải Deactivate/Archive để giữ lịch sử.',
            ]);
        }

        DB::transaction(function () use ($priceList): void {
            $priceList->items()->delete();
            $priceList->delete();
        });
    }
}
