<div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200">
    {{-- Header & Status --}}
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Cấu hình Cơ sở dữ liệu</h3>
            <p class="text-sm text-gray-500">Thiết lập kết nối MySQL/PostgreSQL cho hệ thống</p>
        </div>
        <div class="flex items-center gap-2">
            @if($connectionStatus === 'connected')
                <span class="flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 text-xs font-bold rounded-full border border-green-200">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    ĐÃ KẾT NỐI
                </span>
            @elseif($connectionStatus === 'failed')
                <span class="px-3 py-1 bg-red-50 text-red-700 text-xs font-bold rounded-full border border-red-200">
                    LỖI KẾT NỐI
                </span>
            @endif
        </div>
    </div>

    {{-- Form Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach(['DB_HOST' => 'Host', 'DB_PORT' => 'Port', 'DB_DATABASE' => 'Tên Database', 'DB_USERNAME' => 'Username'] as $key => $label)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $label }}</label>
                <input type="text"
                       wire:model.blur="form.{{ $key }}"
                       placeholder="Nhập {{ strtolower($label) }}..."
                       class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all shadow-sm">
            </div>
        @endforeach

        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
            <input type="password"
                   wire:model.blur="form.DB_PASSWORD"
                   placeholder="••••••••"
                   class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all shadow-sm">
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mt-8 pt-6 border-t border-gray-100 flex flex-wrap gap-4">
        {{-- Nút TEST CONNECTION --}}
        <button type="button"
                wire:click="testConnection"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-6 py-2.5 bg-gray-800 text-white text-sm font-bold rounded-lg hover:bg-gray-700 active:transform active:scale-95 transition-all disabled:opacity-50">
            <span wire:loading wire:target="testConnection" class="mr-2 border-2 border-white/30 border-t-white rounded-full w-4 h-4 animate-spin"></span>
            <svg wire:loading.remove wire:target="testConnection" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            KIỂM TRA KẾT NỐI
        </button>

        {{-- Nút SAVE CONFIG --}}
        <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                @if($connectionStatus !== 'connected') disabled @endif
                class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg hover:bg-blue-700 active:transform active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
            <span wire:loading wire:target="save" class="mr-2 border-2 border-white/30 border-t-white rounded-full w-4 h-4 animate-spin"></span>
            LƯU CẤU HÌNH
        </button>
    </div>
</div>
