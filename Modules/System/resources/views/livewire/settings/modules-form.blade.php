<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Quản lý Module</h2>
                <p class="mt-1 text-sm text-gray-600">Bật/tắt, kiểm tra dependency, route và trạng thái database của các module.</p>
                <p class="mt-1 text-xs text-gray-500">Trạng thái bật/tắt runtime được lưu tại <code>storage/app/system/module-state.json</code> và không sửa manifest của module.</p>
                @if (! $canUpdate)
                    <p class="mt-2 text-xs font-semibold text-amber-700">Chế độ chỉ xem — cần quyền system.modules.update để thay đổi cấu hình module.</p>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Tổng số:</span>
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800">{{ count($modules) }}</span>
            </div>
        </div>
    </div>

    <x-realtime-control :enabled="$realtimeEnabled" :status="$realtimeStatus" :can-update="$canUpdate" />

    @if (session()->has('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">{{ session('message') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">{{ session('error') }}</div>
    @endif

    @php
        $groupedModules = collect($modules)->groupBy('type');
        $typeLabels = [
            'shell' => ['label' => 'Shell Modules', 'color' => 'bg-red-100 text-red-800', 'description' => 'Modules cốt lõi của hệ thống'],
            'support' => ['label' => 'Support Modules', 'color' => 'bg-yellow-100 text-yellow-800', 'description' => 'Modules hỗ trợ'],
            'domain' => ['label' => 'Domain Modules', 'color' => 'bg-blue-100 text-blue-800', 'description' => 'Modules nghiệp vụ'],
        ];
    @endphp

    @foreach ($groupedModules as $type => $typeModules)
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-medium text-gray-900">{{ $typeLabels[$type]['label'] ?? ucfirst($type) }}</h3>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeLabels[$type]['color'] ?? 'bg-gray-100 text-gray-800' }}">{{ $typeModules->count() }}</span>
                    </div>
                    <p class="text-sm text-gray-600">{{ $typeLabels[$type]['description'] ?? '' }}</p>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($typeModules as $module)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4" wire:key="system-module-{{ $module['name'] }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $module['name'] }}</h4>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ ucfirst($module['type']) }} •
                                        @if ($module['source'] === 'runtime')
                                            <span class="font-semibold text-indigo-700">Runtime</span>
                                        @elseif ($module['source'] === 'manifest')
                                            <span>Manifest</span>
                                        @else
                                            <span>Default</span>
                                        @endif
                                    </p>

                                    @if ($module['required'])
                                        <p class="mt-1 text-xs font-semibold text-red-600">Bắt buộc bật — không thể tắt</p>
                                    @elseif ($module['depends'])
                                        <p class="mt-1 text-xs text-gray-500">Phụ thuộc: {{ implode(', ', $module['depends']) }}</p>
                                    @endif

                                    @if ($module['used_by'])
                                        <p class="mt-1 text-xs font-medium text-amber-700">Đang được sử dụng bởi: {{ implode(', ', $module['used_by']) }}</p>
                                    @endif

                                    @if (! empty($module['database']['error']))
                                        <p class="mt-1 text-xs font-medium text-red-600">Không kiểm tra được trạng thái database.</p>
                                    @elseif (! empty($module['database']['missing_tables']))
                                        <p class="mt-1 text-xs font-medium text-amber-700">Thiếu bảng: {{ implode(', ', $module['database']['missing_tables']) }} — sẽ migrate khi bật</p>
                                    @elseif (! empty($module['database']['tables']))
                                        <p class="mt-1 text-xs font-medium text-emerald-700">Database đã sẵn sàng</p>
                                    @endif
                                </div>

                                <div class="ml-2 flex items-center gap-3">
                                    <label class="relative inline-flex items-center {{ (! $canUpdate || $module['required']) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleModule('{{ $module['name'] }}')"
                                            wire:confirm="{{ $module['enabled'] ? 'Tắt' : 'Bật' }} module {{ $module['name'] }}? {{ $module['enabled'] ? 'Các module phụ thuộc phải được tắt trước.' : 'Hệ thống có thể chạy migration và đồng bộ permission trước khi bật.' }}"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleModule"
                                            @checked($module['enabled'])
                                            @disabled(! $canUpdate || $module['required'])
                                            class="peer sr-only"
                                        >
                                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300"></div>
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
        :can-update="$canUpdate"
    />

    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Lưu ý</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Shell Modules không thể tắt. Khi bật, hệ thống kiểm tra bảng, có thể chạy migration còn thiếu rồi đồng bộ quyền. Việc thêm hoặc gỡ mã nguồn module phải thực hiện qua quy trình triển khai có kiểm soát, không qua trình duyệt.</p>
                </div>
            </div>
        </div>
    </div>
</div>
