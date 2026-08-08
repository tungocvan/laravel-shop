<div>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quản lý Script .sh</h1>
            <p class="text-sm text-gray-500 mt-1">Quản lý, chỉnh sửa và thực thi các tệp tin shell script hệ thống.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full border border-gray-200 bg-gray-50 text-gray-600 text-xs font-medium">
                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Hệ thống ổn định
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sidebar: Selection & Creation -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Select Script Card -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <label for="scriptSelect" class="text-sm font-medium text-gray-600">Chọn tệp thực thi</label>
                <select 
                    id="scriptSelect" 
                    wire:model.live="selectedScript" 
                    wire:change="selectScript($event.target.value)"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all outline-none text-sm"
                >
                    <option value="">-- Danh sách File .sh --</option>
                    @foreach($scripts as $script)
                        <option value="{{ $script }}">{{ basename($script) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Create New Script Card -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Tạo Script mới</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tên File</label>
                        <input 
                            type="text" 
                            wire:model.live="newScriptName" 
                            placeholder="backup_db.sh" 
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all outline-none text-sm font-mono"
                        />
                    </div>
                    <button 
                        wire:click="saveScript"
                        class="w-full inline-flex items-center justify-center rounded-xl px-4 py-3 font-semibold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors focus:ring-4 focus:ring-gray-100"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Khởi tạo tệp
                    </button>
                </div>
            </div>

            @if($errorMessage)
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-100 text-rose-700 text-sm flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $errorMessage }}</span>
                </div>
            @endif
        </div>

        <!-- Main Editor & Terminal -->
        <div class="lg:col-span-8 space-y-6">
            @if($selectedScript)
                <!-- Code Editor Card -->
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition-all">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ basename($selectedScript) }}
                        </h3>
                        <div class="flex gap-2">
                            <button wire:click="deleteScript" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Xóa script">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-0">
                        <textarea 
                            wire:model.live="scriptContent" 
                            rows="12" 
                            class="w-full border-0 p-6 focus:ring-0 text-sm font-mono text-gray-800 leading-relaxed placeholder-gray-400 min-h-[300px] resize-none"
                            placeholder="#!/bin/bash..."
                        ></textarea>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap gap-3">
                        <button 
                            wire:click="saveScript" 
                            class="inline-flex items-center justify-center rounded-xl px-6 py-3 font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm focus:ring-4 focus:ring-blue-100"
                        >
                            Cập nhật nội dung
                        </button>
                        
                        <button 
                            wire:click="executeScript" 
                            class="inline-flex items-center justify-center rounded-xl px-6 py-3 font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm focus:ring-4 focus:ring-emerald-100"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Thực hiện Script
                        </button>
                    </div>
                </div>
            @endif

            <!-- Terminal Output Section -->
            <div class="bg-gray-900 rounded-2xl shadow-xl overflow-hidden flex flex-col h-full min-h-[350px]">
                <div class="bg-gray-800 px-4 py-3 flex items-center justify-between border-b border-gray-700">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                        <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="ml-2 text-xs font-medium text-gray-400 font-mono tracking-wider italic">bash — console output</span>
                    </div>
                    <div wire:loading wire:target="executeScript" class="flex items-center gap-2">
                         <svg class="animate-spin h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-[10px] text-emerald-400 font-mono">EXECUTING...</span>
                    </div>
                </div>
                <div class="p-6 flex-grow overflow-y-auto font-mono text-sm leading-relaxed custom-scrollbar bg-[#0d1117]">
                    @if($executionOutput)
                        <pre class="text-emerald-400 whitespace-pre-wrap">{{ $executionOutput }}</pre>
                    @else
                        <div class="h-full flex flex-col items-center justify-center text-gray-600 space-y-3 opacity-40 py-12">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-xs">Sẵn sàng thực thi lệnh...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #30363d; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #484f58; }
</style>
</div>