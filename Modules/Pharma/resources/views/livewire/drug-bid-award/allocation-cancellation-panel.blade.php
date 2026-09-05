@php
    $admin = auth('admin')->user();
    $canCancelAllocation = $admin?->can('cancel_pharma_allocations') ?? false;
    $canCancelContract = $admin?->can('cancel_pharma_contracts') ?? false;
@endphp
@if ($canCancelAllocation || $canCancelContract)
<section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/40 p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-rose-950">Hủy có kiểm soát</h2><p class="mt-1 text-sm text-rose-700">Không hard-delete. Mỗi thao tác yêu cầu lý do và lưu audit metadata; danh sách chọn được giới hạn 100 bản ghi gần nhất.</p>
    <div class="mt-4 grid gap-5 lg:grid-cols-2">
        @if ($canCancelAllocation)<div class="rounded-xl border border-rose-200 bg-white p-4"><h3 class="font-semibold text-slate-900">Hủy phân bổ</h3><select wire:model="allocationId" class="mt-3 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Chọn phân bổ</option>@foreach ($allocations as $allocation)<option value="{{ $allocation->id }}">#{{ $allocation->id }} · {{ $allocation->partner?->name }} · {{ $allocation->allocated_quantity }}</option>@endforeach</select><textarea wire:model="allocationReason" rows="2" placeholder="Lý do hủy" class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></textarea><button type="button" wire:click="cancelAllocation" wire:confirm="Xác nhận hủy phân bổ này?" wire:loading.attr="disabled" class="mt-3 min-h-11 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Hủy phân bổ</button></div>@endif
        @if ($canCancelContract)<div class="rounded-xl border border-rose-200 bg-white p-4"><h3 class="font-semibold text-slate-900">Hủy hợp đồng</h3><select wire:model="contractId" class="mt-3 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Chọn hợp đồng</option>@foreach ($contracts as $contract)<option value="{{ $contract->id }}">#{{ $contract->id }} · {{ $contract->allocation?->partner?->name }} · {{ $contract->contract_number }}</option>@endforeach</select><textarea wire:model="contractReason" rows="2" placeholder="Lý do hủy" class="mt-3 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></textarea><button type="button" wire:click="cancelContract" wire:confirm="Xác nhận hủy hợp đồng này?" wire:loading.attr="disabled" class="mt-3 min-h-11 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Hủy hợp đồng</button></div>@endif
    </div>
</section>
@endif
