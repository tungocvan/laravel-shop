<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Quản lý Module</h2>
                <p class="text-sm text-gray-600 mt-1">Bật hoặc tắt các module trong hệ thống</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Tổng số:</span>
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                    {{ count($modules) }}
                </span>
            </div>
        </div>
    </div>

    <x-realtime-control :enabled="$realtimeEnabled" :status="$realtimeStatus" />

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Modules by Type -->
    @php
        $groupedModules = collect($modules)->groupBy('type');
        $typeLabels = [
            'shell' => ['label' => 'Shell Modules', 'color' => 'bg-red-100 text-red-800', 'description' => 'Modules cốt lõi của hệ thống'],
            'support' => ['label' => 'Support Modules', 'color' => 'bg-yellow-100 text-yellow-800', 'description' => 'Modules hỗ trợ'],
            'domain' => ['label' => 'Domain Modules', 'color' => 'bg-blue-100 text-blue-800', 'description' => 'Modules nghiệp vụ']
        ];
    @endphp

    @foreach($groupedModules as $type => $typeModules)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-medium text-gray-900">{{ $typeLabels[$type]['label'] ?? ucfirst($type) }}</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeLabels[$type]['color'] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $typeModules->count() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $typeLabels[$type]['description'] ?? '' }}</p>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($typeModules as $module)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $module['name'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ ucfirst($module['type']) }} • {{ $module['source'] === 'manifest' ? 'Config' : 'Default' }}
                                    </p>
                                    @if ($module['required'])
                                        <p class="mt-1 text-xs font-semibold text-red-600">Bắt buộc bật — không thể tắt</p>
                                    @elseif ($module['depends'])
                                        <p class="mt-1 text-xs text-gray-500">Phụ thuộc: {{ implode(', ', $module['depends']) }}</p>
                                    @endif
                                    @if ($module['used_by'])
                                        <p class="mt-1 text-xs font-medium text-amber-700">Đang được sử dụng bởi: {{ implode(', ', $module['used_by']) }}</p>
                                    @endif
                                    @if (!empty($module['database']['error']))
                                        <p class="mt-1 text-xs font-medium text-red-600">Không kiểm tra được database: {{ $module['database']['error'] }}</p>
                                    @elseif (!empty($module['database']['missing_tables']))
                                        <p class="mt-1 text-xs font-medium text-amber-700">Thiếu bảng: {{ implode(', ', $module['database']['missing_tables']) }} — sẽ migrate khi bật</p>
                                    @elseif (!empty($module['database']['tables']))
                                        <p class="mt-1 text-xs font-medium text-emerald-700">Database đã sẵn sàng</p>
                                    @endif
                                </div>
                                <div class="ml-4 flex items-center gap-3">
                                    @if (! $module['required'])
                                        <button
                                            type="button"
                                            wire:click="deleteModule('{{ $module['name'] }}')"
                                            wire:confirm="Gỡ module {{ $module['name'] }}? Mã nguồn sẽ được lưu trong module-trash và database được giữ nguyên."
                                            {{ $module['enabled'] ? 'disabled' : '' }}
                                            class="rounded-md px-2.5 py-1.5 text-xs font-semibold {{ $module['enabled'] ? 'cursor-not-allowed bg-gray-200 text-gray-400' : 'bg-red-50 text-red-700 hover:bg-red-100' }}"
                                        >Gỡ</button>
                                    @endif
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleModule('{{ $module['name'] }}')"
                                            {{ $module['enabled'] ? 'checked' : '' }}
                                            {{ $module['required'] ? 'disabled' : '' }}
                                            class="sr-only peer"
                                        >
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <x-module-routes-table
        :routes="$this->filteredModuleRoutes"
        :total="count($moduleRoutes)"
        :modules="collect($moduleRoutes)->pluck('module')->unique()->sort()->values()->all()"
        :editing-route-key="$editingRouteKey"
    />

    <!-- Info Section -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Lưu ý</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Shell Modules không thể tắt hoặc gỡ. Khi bật, hệ thống kiểm tra bảng và tự chạy migration còn thiếu. Chỉ module đã tắt và không có module khác phụ thuộc mới được gỡ; database luôn được giữ lại.</p>
                </div>
            </div>
        </div>
    </div>
</div>
