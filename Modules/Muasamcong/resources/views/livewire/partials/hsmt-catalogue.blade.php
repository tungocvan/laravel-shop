<section @if (!$hsmt) wire:init="loadHsmt" @endif class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Danh mục mời thầu (HSMT)</h4>
                @if ($hsmt)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ number_format($hsmt['total'] ?? 0, 0, ',', '.') }} mặt hàng
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Đã lưu trên server</span>
                @else
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Đang kiểm tra server</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Khi mở KQLCNT, hệ thống kiểm tra snapshot HSMT theo mã TBMT trên server trước. Chỉ khi chưa có dữ liệu mới gọi API Mua sắm công, sau đó lưu JSON và Excel để những lần xem sau không phải gọi lại API.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($hsmt)
                <button type="button" wire:click="syncHsmt" wire:loading.attr="disabled" wire:target="syncHsmt"
                        class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm hover:bg-amber-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="syncHsmt">Đồng bộ lại HSMT</span>
                    <span wire:loading wire:target="syncHsmt">Đang gọi lại API...</span>
                </button>
            @else
                <button type="button" wire:click="loadHsmt" wire:loading.attr="disabled" wire:target="loadHsmt"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadHsmt">Thử tải lại HSMT</span>
                    <span wire:loading wire:target="loadHsmt">Đang kiểm tra / tải HSMT...</span>
                </button>
            @endif
        </div>
    </div>

    <div wire:loading.flex wire:target="loadHsmt" class="mt-4 items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-blue-600"></span>
        Đang kiểm tra HSMT {{ $kqlcnt['notify_no'] ?? '' }} trên server; nếu chưa có hệ thống sẽ tự gọi API và lưu lại...
    </div>

    @if ($hsmt)
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Mã TBMT</div>
                <div class="mt-1 font-semibold text-gray-900">{{ $hsmt['notify_no'] ?? ($kqlcnt['notify_no'] ?? '—') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Số mặt hàng HSMT</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format($hsmt['total'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Nguồn sử dụng</div>
                <div class="mt-1 font-semibold text-emerald-700">Snapshot server</div>
            </div>
        </div>

        @if (!empty($hsmt['investor_name']))
            <div class="mt-3 text-xs text-gray-500">Chủ đầu tư HSMT: <span class="font-medium text-gray-700">{{ $hsmt['investor_name'] }}</span></div>
        @endif
    @endif
</section>

@livewire('muasamcong.smart-pricing-verified-lots', [
    'notifyNo' => (string) ($kqlcnt['notify_no'] ?? ''),
    'contractorCode' => (string) ($kqlcnt['contractor_code'] ?? $contractorCode),
    'contractorName' => (string) $contractorName,
], key('smart-pricing-lots-'.($kqlcnt['notify_no'] ?? 'unknown').'-'.($kqlcnt['contractor_code'] ?? $contractorCode)))

@livewire('muasamcong.manual-contractor-lots', [
    'notifyNo' => (string) ($kqlcnt['notify_no'] ?? ''),
    'contractorCode' => (string) ($kqlcnt['contractor_code'] ?? $contractorCode),
    'contractorName' => (string) $contractorName,
], key('manual-lots-'.($kqlcnt['notify_no'] ?? 'unknown').'-'.($kqlcnt['contractor_code'] ?? $contractorCode)))
