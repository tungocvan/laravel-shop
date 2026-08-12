<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="mb-6 pb-4 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Cấu hình Hệ thống & Queue</h3>
        <p class="text-sm text-gray-500">Thiết lập hàng đợi và kết nối Realtime server.</p>
        @unless($canUpdate)<p class="mt-2 text-xs font-bold text-amber-700">Tài khoản hiện tại chỉ có quyền xem.</p>@endunless
    </div>

    <div class="space-y-8">
        <section>
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Hàng đợi (Queue)</h4>
            <div class="max-w-md space-y-4">
                <select wire:model="form.QUEUE_CONNECTION" @disabled(!$canUpdate) class="w-full px-4 py-2 rounded-lg border border-gray-300">
                    <option value="sync">Sync</option><option value="database">Database</option><option value="redis">Redis</option>
                </select>
                @error('form.QUEUE_CONNECTION')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <div @if(str_contains($queueStatus, 'Pending') || str_contains($queueStatus, 'Processing')) wire:poll.2s="refreshQueueStatus" @endif>
                    <span class="text-xs font-bold">{{ $queueStatus ?: 'Chưa kiểm tra' }}</span>
                </div>
                <button wire:click="testQueue" wire:loading.attr="disabled" wire:target="testQueue" @disabled(!$canUpdate) class="px-3 py-2 bg-blue-600 text-white text-xs font-bold rounded disabled:opacity-50">CHẠY TEST JOB</button>
            </div>
        </section>

        <section class="pt-6 border-t border-gray-100">
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">NodeJS Server Bridge (Realtime)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div><label class="block text-sm font-semibold mb-1">NodeJS URL</label><input type="url" wire:model="form.NODEJS_SERVER_URL" @disabled(!$canUpdate) class="w-full px-4 py-2 rounded-lg border">@error('form.NODEJS_SERVER_URL')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-semibold mb-1">Bridge Secret Key</label><input type="password" wire:model="form.BRIDGE_SECRET_KEY" placeholder="Để trống để giữ secret hiện tại" @disabled(!$canUpdate) class="w-full px-4 py-2 rounded-lg border">@error('form.BRIDGE_SECRET_KEY')<p class="text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <button wire:click="checkNode" wire:loading.attr="disabled" wire:target="checkNode" @disabled(!$canUpdate) class="mt-4 px-4 py-2 bg-gray-100 text-xs font-bold rounded disabled:opacity-50">{{ $nodeStatus === 'online' ? '✅ NODEJS ONLINE' : ($nodeStatus === 'offline' ? '❌ NODEJS OFFLINE' : 'KIỂM TRA KẾT NỐI NODEJS') }}</button>
        </section>
    </div>

    <div class="mt-8 pt-6 border-t">
        <button wire:click="save" wire:confirm="Lưu thay đổi Queue/NodeJS vào .env?" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="px-8 py-2.5 bg-primary text-white font-bold rounded-lg disabled:opacity-50">LƯU CẤU HÌNH HỆ THỐNG</button>
    </div>
</div>
