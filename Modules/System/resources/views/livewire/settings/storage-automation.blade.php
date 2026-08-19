<div class="mt-6 rounded-xl border border-emerald-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Backup tự động</h3>
            <p class="mt-1 text-sm text-gray-500">Laravel Scheduler kiểm tra mỗi phút và chỉ chạy một lần/ngày đúng giờ đã cấu hình.</p>
        </div>
        <button wire:click="runAutomationNow" wire:loading.attr="disabled" wire:target="runAutomationNow" @disabled(!$canUpdate)
            wire:confirm="Tạo một backup database ngay bây giờ và áp dụng chính sách upload/retention hiện tại?"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-50">
            <span wire:loading.remove wire:target="runAutomationNow">CHẠY THỬ NGAY</span>
            <span wire:loading wire:target="runAutomationNow">ĐANG CHẠY...</span>
        </button>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="rounded-lg border border-gray-200 p-4">
            <span class="block text-xs font-bold uppercase text-gray-500">Trạng thái</span>
            <span class="mt-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                <input type="checkbox" wire:model="automation.enabled" @disabled(!$canUpdate) class="rounded border-gray-300">
                Bật backup hằng ngày
            </span>
        </label>
        <div class="rounded-lg border border-gray-200 p-4">
            <label class="block text-xs font-bold uppercase text-gray-500">Giờ chạy</label>
            <input type="time" wire:model="automation.time" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100">
            @error('automation.time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="rounded-lg border border-gray-200 p-4">
            <label class="block text-xs font-bold uppercase text-gray-500">Giữ local</label>
            <input type="number" min="1" max="365" wire:model="automation.local_retention" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100">
            <p class="mt-1 text-xs text-gray-400">Số full backup mới nhất.</p>
        </div>
        <div class="rounded-lg border border-gray-200 p-4">
            <label class="block text-xs font-bold uppercase text-gray-500">Giữ Google Drive</label>
            <input type="number" min="1" max="365" wire:model="automation.drive_retention" @disabled(!$canUpdate) class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-100">
            <p class="mt-1 text-xs text-gray-400">Số backup remote mới nhất.</p>
        </div>
    </div>

    <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model="automation.upload_drive" @disabled(!$canUpdate) class="rounded border-gray-300">
        Tự động đưa backup mới vào queue upload Google Drive
    </label>

    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5">
        <button wire:click="saveAutomation" wire:loading.attr="disabled" wire:target="saveAutomation" @disabled(!$canUpdate)
            class="rounded-lg bg-gray-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-black disabled:opacity-50">
            <span wire:loading.remove wire:target="saveAutomation">LƯU LỊCH BACKUP</span>
            <span wire:loading wire:target="saveAutomation">ĐANG LƯU...</span>
        </button>
        @if(!empty($automationStatus['last_run_at']))
            <div class="text-xs text-gray-500">Lần chạy gần nhất: <strong>{{ \Carbon\Carbon::parse($automationStatus['last_run_at'])->format('d/m/Y H:i') }}</strong> · {{ $automationStatus['last_status'] ?? '' }}</div>
        @endif
    </div>
    @if(!empty($automationStatus['last_message']))<div class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ $automationStatus['last_message'] }}</div>@endif

    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
        <strong>VPS/Docker:</strong> scheduler phải thực sự chạy. Có thể dùng cron gọi <code>php artisan schedule:run</code> mỗi phút hoặc chạy <code>php artisan schedule:work</code> bằng process/container riêng. Queue worker cũng phải chạy để upload Drive được xử lý.
    </div>
</div>
