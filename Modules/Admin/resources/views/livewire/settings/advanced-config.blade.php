<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="mb-6 pb-4 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Cấu hình Hệ thống & Queue</h3>
        <p class="text-sm text-gray-500">Thiết lập hàng đợi và kết nối Realtime server.</p>
    </div>

    <div class="space-y-8">
        {{-- Queue Section --}}
        <section>
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Hàng đợi (Queue)
            </h4>
            <div class="max-w-md">

                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Queue Connection</label>
                <select wire:model="form.QUEUE_CONNECTION"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none">
                    <option value="sync">Sync (Tức thời - Không khuyên dùng)</option>
                    <option value="database">Database (Mặc định)</option>
                    <option value="redis">Redis (Khuyên dùng cho Production)</option>
                </select>
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-center justify-between">
                        <div class="p-4 bg-gray-50 rounded-lg" {{-- Nếu đang chờ hoặc đang chạy, tự động hỏi server mỗi 2 giây --}}
                            @if (str_contains($queueStatus, 'Pending') || str_contains($queueStatus, 'Processing')) wire:poll.2s="refreshQueueStatus" @endif>

                            <div class="flex items-center gap-3">
                                <span @class([
                                    'px-3 py-1 rounded-full text-xs font-bold transition-all duration-500',
                                    'bg-yellow-100 text-yellow-700' => $queueStatus === 'Pending...',
                                    'bg-blue-100 text-blue-700' => $queueStatus === 'Processing...',
                                    'bg-green-100 text-green-700 border border-green-200' => str_contains(
                                        $queueStatus,
                                        'Success'),
                                ])>
                                    {{ $queueStatus ?: 'Chưa kiểm tra' }}
                                </span>
                            </div>
                        </div>
                        {{-- Nút điều khiển --}}
                        <button wire:click="testQueue"
                            class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold rounded shadow-sm hover:bg-blue-700 transition">
                            CHẠY TEST JOB
                        </button>
                    </div>
                </div>
            </div>

        </section>

        {{-- NodeJS Bridge Section --}}
        <section class="pt-6 border-t border-gray-100">
            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                NodeJS Server Bridge (Realtime)
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">NodeJS URL</label>
                    <input type="text" wire:model="form.NODEJS_SERVER_URL" placeholder="http://127.0.0.1:3000"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Bridge Secret Key</label>
                    <input type="password" wire:model="form.BRIDGE_SECRET_KEY"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 outline-none focus:ring-2 focus:ring-primary/20">
                </div>
            </div>

            <div class="mt-4">
                <button wire:click="checkNode" wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-200 transition">
                    <span wire:loading wire:target="checkNode" class="mr-2 animate-spin">🌀</span>
                    {{ $nodeStatus === 'online' ? '✅ NODEJS ONLINE' : ($nodeStatus === 'offline' ? '❌ NODEJS OFFLINE' : 'KIỂM TRA KẾT NỐI NODEJS') }}
                </button>
            </div>
        </section>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100">
        <button wire:click="save"
            class="px-8 py-2.5 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition shadow-md">
            LƯU CẤU HÌNH HỆ THỐNG
        </button>
    </div>
</div>
