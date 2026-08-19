<div class="mt-6 rounded-xl border border-emerald-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex items-center gap-2"><h3 class="text-lg font-bold text-gray-900">Lịch Backup tự động</h3>@if($automationStatus['enabled'] ?? false)<span class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">ĐANG HOẠT ĐỘNG</span>@else<span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-bold text-gray-500">ĐÃ TẠM DỪNG</span>@endif</div>
            <p class="mt-1 text-sm text-gray-500">Sắp xếp lịch backup hằng ngày, theo dõi lần chạy và hủy/tạm dừng lịch ngay trên giao diện.</p>
        </div>
        <button wire:click="runAutomationNow" wire:loading.attr="disabled" wire:target="runAutomationNow" @disabled(!$canUpdate)
            wire:confirm="Tạo một backup database ngay bây giờ và áp dụng chính sách upload/retention hiện tại?"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="runAutomationNow">CHẠY BACKUP NGAY</span><span wire:loading wire:target="runAutomationNow">ĐANG CHẠY...</span>
        </button>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4"><div class="text-[10px] font-bold uppercase text-gray-400">Lịch hiện tại</div><div class="mt-2 text-sm font-bold text-gray-800">Hằng ngày lúc {{ $automation['time'] }}</div><div class="mt-1 text-xs text-gray-500">{{ ($automationStatus['enabled'] ?? false) ? 'Scheduler sẽ kiểm tra mỗi phút.' : 'Lịch đang tạm dừng.' }}</div></div>
        <div class="rounded-xl border border-sky-100 bg-sky-50 p-4"><div class="text-[10px] font-bold uppercase text-sky-500">Lần chạy kế tiếp</div><div class="mt-2 text-sm font-bold text-sky-800">@if(!empty($automationStatus['next_run_at'])){{ \Carbon\Carbon::parse($automationStatus['next_run_at'])->format('d/m/Y H:i') }}@else Chưa có lịch @endif</div><div class="mt-1 text-xs text-sky-600">Theo timezone ứng dụng: {{ config('app.timezone') }}</div></div>
        <div class="rounded-xl border {{ ($automationStatus['last_status'] ?? '') === 'failed' ? 'border-red-100 bg-red-50' : 'border-emerald-100 bg-emerald-50' }} p-4"><div class="text-[10px] font-bold uppercase text-gray-500">Lần chạy gần nhất</div><div class="mt-2 text-sm font-bold text-gray-800">@if(!empty($automationStatus['last_run_at'])){{ \Carbon\Carbon::parse($automationStatus['last_run_at'])->format('d/m/Y H:i') }}@else Chưa chạy @endif</div><div class="mt-1 text-xs text-gray-600">Trạng thái: {{ $automationStatus['last_status'] ?: '—' }}</div></div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="rounded-lg border border-gray-200 p-4"><span class="block text-xs font-bold uppercase text-gray-500">Trạng thái lịch</span><span class="mt-3 flex items-center gap-2 text-sm font-semibold text-gray-800"><input type="checkbox" wire:model="automation.enabled" @disabled(!$canUpdate) class="rounded border-gray-300">Bật backup hằng ngày</span></label>
        <div class="rounded-lg border border-gray-200 p-4"><label class="block text-xs font-bold uppercase text-gray-500">Giờ chạy</label><input type="time" wire:model="automation.time" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100">@error('automation.time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div class="rounded-lg border border-gray-200 p-4"><label class="block text-xs font-bold uppercase text-gray-500">Giữ local</label><input type="number" min="1" max="365" wire:model="automation.local_retention" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"><p class="mt-1 text-xs text-gray-400">Số full backup mới nhất.</p></div>
        <div class="rounded-lg border border-gray-200 p-4"><label class="block text-xs font-bold uppercase text-gray-500">Giữ Google Drive</label><input type="number" min="1" max="365" wire:model="automation.drive_retention" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100"><p class="mt-1 text-xs text-gray-400">Số backup remote mới nhất.</p></div>
    </div>

    <label class="mt-4 flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model="automation.upload_drive" @disabled(!$canUpdate) class="rounded border-gray-300">Tự động đưa backup mới vào queue upload Google Drive</label>

    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
        <button wire:click="saveAutomation" wire:loading.attr="disabled" wire:target="saveAutomation" @disabled(!$canUpdate) class="rounded-lg bg-gray-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-black disabled:opacity-50"><span wire:loading.remove wire:target="saveAutomation">LƯU / CẬP NHẬT LỊCH</span><span wire:loading wire:target="saveAutomation">ĐANG LƯU...</span></button>
        @if($automationStatus['enabled'] ?? false)<button wire:click="cancelAutomation" wire:confirm="Tạm dừng lịch backup tự động? Giờ chạy và chính sách retention vẫn được giữ để có thể bật lại sau." @disabled(!$canUpdate) class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-xs font-bold text-red-700 hover:bg-red-100 disabled:opacity-50">HỦY / TẠM DỪNG LỊCH</button>@endif
    </div>

    @if(!empty($automationStatus['last_message']))<div class="mt-4 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600"><strong>Log lần chạy:</strong> {{ $automationStatus['last_message'] }}</div>@endif

    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900"><strong>Theo dõi trên VPS/Docker:</strong> UI phản ánh lịch đã cấu hình và kết quả command gần nhất. Scheduler của server vẫn phải chạy bằng cron <code>php artisan schedule:run</code> mỗi phút hoặc process <code>php artisan schedule:work</code>; queue worker phải chạy để upload Drive được xử lý.</div>
</div>
