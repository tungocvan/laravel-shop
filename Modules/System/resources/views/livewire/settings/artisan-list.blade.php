<div>
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Artisan Terminal</h1>
            <p class="text-sm text-gray-500 mt-1">Thực thi các câu lệnh hệ thống Laravel trực tiếp từ giao diện.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-blue-100 bg-blue-50 text-blue-700 text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-blue-500 mr-1.5"></span>
                Production Mode
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Command Input & Instructions -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cấu hình lệnh</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Câu lệnh Artisan</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-mono text-sm select-none">
                                php artisan
                            </span>
                            <input 
                                type="text" 
                                wire:model.live.debounce.500ms="artisanCommand" 
                                placeholder="list, migrate, optimize:clear..." 
                                class="w-full rounded-xl border border-gray-300 pl-[6.5rem] pr-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all outline-none text-sm font-mono"
                            />
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Lưu ý: Bỏ qua tiền tố "php artisan"</p>
                    </div>

                    @if($errorMessage)
                        <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-sm">
                            <div class="flex">
                                <svg class="h-5 w-5 text-rose-400 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                {{ $errorMessage }}
                            </div>
                        </div>
                    @endif

                    <button 
                        wire:click="executeArtisanCommand"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center rounded-xl px-4 py-3 font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors focus:ring-4 focus:ring-blue-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="executeArtisanCommand">Thực thi lệnh</span>
                        <span wire:loading wire:target="executeArtisanCommand" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Đang xử lý...
                        </span>
                    </button>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6">
                <h4 class="text-sm font-semibold text-gray-800 mb-3 uppercase tracking-wider">Gợi ý lệnh phổ biến</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach(['list', 'key:generate', 'optimize:clear', 'db:seed', 'migrate:fresh'] as $cmd)
                        <button 
                            wire:click="$set('artisanCommand', '{{ $cmd }}')"
                            class="px-3 py-1.5 bg-white border border-gray-200 hover:border-blue-400 hover:text-blue-600 rounded-lg text-xs font-medium text-gray-600 transition-all"
                        >
                            {{ $cmd }}
                        </button>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        <span class="font-bold text-gray-700">Ví dụ Livewire:</span><br>
                        make:livewire user.user-list
                    </p>
                </div>
            </div>
        </div>

        <!-- Terminal Output -->
        <div class="lg:col-span-2">
            <div class="bg-gray-900 rounded-2xl shadow-xl overflow-hidden flex flex-col h-full min-h-[500px]">
                <div class="bg-gray-800 px-4 py-3 flex items-center justify-between border-b border-gray-700">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="ml-2 text-xs font-medium text-gray-400 font-mono">terminal — artisan@laravel</span>
                    </div>
                    <button 
                        onclick="navigator.clipboard.writeText(document.getElementById('terminal-output').innerText)"
                        class="text-gray-400 hover:text-white transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 flex-grow overflow-y-auto font-mono text-sm leading-relaxed custom-scrollbar">
                    @if($commandOutput)
                        <pre id="terminal-output" class="text-emerald-400 whitespace-pre-wrap">{{ $commandOutput }}</pre>
                    @else
                        <div class="h-full flex flex-col items-center justify-center text-gray-500 space-y-3">
                            <svg class="w-12 h-12 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-xs">Chưa có dữ liệu output. Vui lòng nhập lệnh và nhấn thực thi.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #4b5563; }
</style>
</div>