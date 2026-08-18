<section class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/60 p-4">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-base font-bold text-gray-900">Danh mục mời thầu (HSMT)</h4>
                @if ($hsmt)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        {{ number_format($hsmt['total'] ?? 0, 0, ',', '.') }} mặt hàng
                    </span>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Snapshot server</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Đây là toàn bộ danh mục mời thầu của TBMT. Hệ thống sẽ ưu tiên Smart Pricing để xác minh trực tiếp TBMT ↔ thuốc ↔ nhà thầu; checkbox HSMT bên dưới chỉ là phương án fallback khi nguồn tự động không map được.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (!$hsmt)
                <button type="button" wire:click="loadHsmt" wire:loading.attr="disabled" wire:target="loadHsmt"
                        class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadHsmt">Tải danh mục HSMT</span>
                    <span wire:loading wire:target="loadHsmt">Đang tải dữ liệu lớn...</span>
                </button>
            @else
                <button type="button" wire:click="syncHsmt" wire:loading.attr="disabled" wire:target="syncHsmt"
                        class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-white px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm hover:bg-amber-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="syncHsmt">Đồng bộ lại HSMT</span>
                    <span wire:loading wire:target="syncHsmt">Đang đồng bộ lại...</span>
                </button>
            @endif
        </div>
    </div>

    @if ($hsmt)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>HSMT gốc:</strong> biểu mẫu BD.DT.02.1854 không chứa <code>winningContractorName</code>, <code>winningCode</code> hoặc <code>contractorCode</code> trên từng lô. Vì vậy tên nhà thầu không được suy đoán từ HSMT; quan hệ winner được lấy từ Smart Pricing khi có thể xác minh trực tiếp.
        </div>

        <div class="mt-3 text-xs text-gray-500">
            @if (!empty($hsmt['investor_name']))
                Chủ đầu tư HSMT: <span class="font-medium text-gray-700">{{ $hsmt['investor_name'] }}</span>
            @endif
        </div>
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
