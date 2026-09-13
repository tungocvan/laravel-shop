<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Quản lý Module</h2>
                <p class="mt-1 text-sm text-gray-600">Bật/tắt Module với kiểm tra dependency, migration, permission và queue impact trước khi thay đổi runtime.</p>
                <p class="mt-1 text-xs text-gray-500">Trạng thái runtime được lưu tại <code>storage/app/system/module-state.json</code>; thao tác trên UI không sửa manifest của Module.</p>
                @if (! $canUpdate)
                    <p class="mt-2 text-xs font-semibold text-amber-700">Chế độ chỉ xem — cần quyền system.modules.update để thay đổi cấu hình Module.</p>
                @endif
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">Tổng số:</span>
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800">{{ count($modules) }}</span>
            </div>
        </div>
    </div>

    @php
        $groupedModules = collect($modules)->groupBy('type');
        $typeLabels = [
            'shell' => ['label' => 'Shell Modules', 'color' => 'bg-red-100 text-red-800', 'description' => 'Modules cốt lõi của hệ thống'],
            'support' => ['label' => 'Support Modules', 'color' => 'bg-yellow-100 text-yellow-800', 'description' => 'Modules hỗ trợ'],
            'domain' => ['label' => 'Domain Modules', 'color' => 'bg-blue-100 text-blue-800', 'description' => 'Modules nghiệp vụ'],
        ];
    @endphp

    @foreach ($groupedModules as $type => $typeModules)
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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
                        <article class="rounded-xl border border-gray-200 bg-gray-50 p-4" wire:key="system-module-{{ $module['name'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $module['name'] }}</h4>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $module['enabled'] ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                            {{ $module['enabled'] ? 'Đang bật' : 'Đang tắt' }}
                                        </span>
                                    </div>
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
                                        <p class="mt-2 text-xs font-semibold text-red-600">Bắt buộc bật — không thể tắt</p>
                                    @elseif ($module['depends'])
                                        <p class="mt-2 text-xs text-gray-500">Phụ thuộc: {{ implode(', ', $module['depends']) }}</p>
                                    @endif

                                    @if ($module['used_by'])
                                        <p class="mt-1 text-xs font-medium text-amber-700">Đang được sử dụng bởi: {{ implode(', ', $module['used_by']) }}</p>
                                    @endif

                                    @if (! empty($module['database']['error']))
                                        <p class="mt-2 text-xs font-medium text-red-600">Không kiểm tra được trạng thái database.</p>
                                    @elseif (! empty($module['database']['missing_tables']))
                                        <p class="mt-2 text-xs font-medium text-amber-700">Thiếu bảng: {{ implode(', ', $module['database']['missing_tables']) }} — preflight sẽ kiểm tra khả năng migrate.</p>
                                    @elseif (! empty($module['database']['tables']))
                                        <p class="mt-2 text-xs font-medium text-emerald-700">Database đã sẵn sàng</p>
                                    @endif
                                </div>

                                <button
                                    type="button"
                                    wire:click="toggleModule('{{ $module['name'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleModule('{{ $module['name'] }}')"
                                    @disabled(! $canUpdate || $module['required'])
                                    role="switch"
                                    aria-checked="{{ $module['enabled'] ? 'true' : 'false' }}"
                                    class="relative mt-1 inline-flex h-6 w-11 shrink-0 rounded-full transition {{ $module['enabled'] ? 'bg-blue-600' : 'bg-gray-300' }} disabled:cursor-not-allowed disabled:opacity-50">
                                    <span class="pointer-events-none inline-block h-5 w-5 translate-y-0.5 rounded-full bg-white shadow transition {{ $module['enabled'] ? 'translate-x-[22px]' : 'translate-x-0.5' }}"></span>
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        <div class="font-semibold">Lifecycle safety</div>
        <p class="mt-1 leading-6">Shell Modules không thể tắt. Khi bật, hệ thống chỉ ghi runtime state sau khi dependency, migration và permission sync thành công. Khi tắt, dữ liệu và failed/pending jobs không bị xóa; queue <code>default</code> luôn thuộc System và không bị tắt theo Module.</p>
    </div>

    @if ($lifecycleModalOpen)
        @php
            $preflight = $lifecyclePreflight;
            $database = (array) ($preflight['database'] ?? []);
            $permission = (array) ($preflight['permission'] ?? []);
            $queues = (array) ($preflight['queues'] ?? []);
            $dependencies = (array) ($preflight['dependencies'] ?? []);
            $canExecute = (bool) ($preflight['can_execute'] ?? false);
            $targetEnabled = (bool) ($preflight['target_enabled'] ?? false);
        @endphp

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" wire:keydown.escape.window="closeLifecycleModal">
            <div class="max-h-[92vh] w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl" wire:click.stop>
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Module Lifecycle Control</div>
                        <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $lifecycleModule }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $targetEnabled ? 'Kiểm tra điều kiện trước khi bật Module.' : 'Kiểm tra dependency, dữ liệu và queue impact trước khi tắt Module.' }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeLifecycleModal" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Đóng</button>
                </div>

                <div class="max-h-[72vh] overflow-y-auto px-5 py-5 sm:px-6">
                    @if ($lifecycleResult)
                        <div class="mb-5 rounded-2xl border p-4 {{ ($lifecycleResult['ok'] ?? false) ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ ($lifecycleResult['ok'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    {{ ($lifecycleResult['ok'] ?? false) ? 'SUCCESS' : 'ACTION REQUIRED' }}
                                </span>
                                <span class="text-sm font-bold text-slate-900">{{ $lifecycleResult['title'] ?? 'Kết quả lifecycle' }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $lifecycleResult['message'] ?? '' }}</p>
                            @if (! empty($lifecycleResult['guidance']))
                                <div class="mt-3 rounded-xl bg-white/70 px-3 py-2 text-sm leading-6 text-slate-700">
                                    <span class="font-semibold">Hướng xử lý:</span> {{ $lifecycleResult['guidance'] }}
                                </div>
                            @endif

                            @if ($lifecycleResult['ok'] ?? false)
                                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-xl bg-white p-3">
                                        <div class="text-[11px] font-semibold uppercase text-slate-400">Migration</div>
                                        <div class="mt-1 text-sm font-bold text-slate-800">{{ ($lifecycleResult['migrated'] ?? false) ? 'Đã chạy' : 'Không cần' }}</div>
                                    </div>
                                    <div class="rounded-xl bg-white p-3">
                                        <div class="text-[11px] font-semibold uppercase text-slate-400">Permissions</div>
                                        <div class="mt-1 text-sm font-bold text-slate-800">{{ (int) ($lifecycleResult['permission_count'] ?? 0) }}</div>
                                    </div>
                                    <div class="rounded-xl bg-white p-3">
                                        <div class="text-[11px] font-semibold uppercase text-slate-400">Module queues</div>
                                        <div class="mt-1 text-sm font-bold text-slate-800">{{ count((array) ($lifecycleResult['queues'] ?? [])) }}</div>
                                    </div>
                                    <div class="rounded-xl bg-white p-3">
                                        <div class="text-[11px] font-semibold uppercase text-slate-400">Worker signal</div>
                                        <div class="mt-1 text-sm font-bold text-slate-800">{{ ($lifecycleResult['queue_restart_requested'] ?? false) ? 'Đã gửi' : 'Không cần' }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($preflight !== [])
                        <div class="grid gap-4 lg:grid-cols-2">
                            <section class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-bold text-slate-900">1. Dependency</h4>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ empty($preflight['blocking']) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ empty($preflight['blocking']) ? 'PASS' : 'CHECK' }}
                                    </span>
                                </div>
                                @if ($dependencies === [])
                                    <p class="mt-2 text-sm text-slate-500">Không khai báo Module phụ thuộc.</p>
                                @else
                                    <div class="mt-3 space-y-2">
                                        @foreach ($dependencies as $dependency)
                                            <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                                <span class="font-medium text-slate-700">{{ $dependency['name'] }}</span>
                                                <span class="font-semibold {{ ($dependency['enabled'] ?? false) ? 'text-emerald-700' : 'text-rose-700' }}">{{ ($dependency['enabled'] ?? false) ? 'ON' : 'OFF' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if (! empty($preflight['used_by']))
                                    <p class="mt-3 text-sm font-medium text-amber-700">Đang được dùng bởi: {{ implode(', ', $preflight['used_by']) }}</p>
                                @endif
                            </section>

                            <section class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-bold text-slate-900">2. Database / Migration</h4>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ($database['can_execute'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $database['label'] ?? 'Unknown' }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $database['message'] ?? 'Chưa có dữ liệu preflight.' }}</p>
                                @if (! empty($database['missing_tables']))
                                    <div class="mt-3 text-xs text-amber-700"><span class="font-semibold">Thiếu bảng:</span> {{ implode(', ', $database['missing_tables']) }}</div>
                                @endif
                                @if (! empty($database['missing_migration_records']))
                                    <div class="mt-2 text-xs text-amber-700"><span class="font-semibold">Migration ledger thiếu:</span> {{ implode(', ', $database['missing_migration_records']) }}</div>
                                @endif
                            </section>

                            <section class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-bold text-slate-900">3. Permissions</h4>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ($permission['can_execute'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $permission['label'] ?? 'Unknown' }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $permission['message'] ?? 'Chưa có dữ liệu preflight.' }}</p>
                                <div class="mt-3 text-xs text-slate-500">Permission khai báo: <span class="font-bold text-slate-800">{{ (int) ($permission['permission_count'] ?? 0) }}</span></div>
                            </section>

                            <section class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-bold text-slate-900">4. Queue impact</h4>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ count($queues) }} queue riêng</span>
                                </div>
                                @if ($queues === [])
                                    <p class="mt-2 text-sm text-slate-500">Module không khai báo queue riêng. Queue <code>default</code> không bị ảnh hưởng.</p>
                                @else
                                    <div class="mt-3 space-y-2">
                                        @foreach ($queues as $queue)
                                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                <div class="font-mono text-xs font-bold text-slate-800">{{ $queue['name'] }}</div>
                                                <div class="mt-1 text-xs text-slate-500">Pending {{ $queue['pending'] }} · Processing {{ $queue['reserved'] }} · Failed history {{ $queue['failed'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="mt-3 text-xs leading-5 text-slate-500">{{ $targetEnabled ? 'Queue ownership sẽ active sau khi Module bật thành công.' : 'Queue ownership sẽ inactive; pending/failed jobs được giữ nguyên. Laravel sẽ gửi queue:restart, không xóa dữ liệu queue.' }}</p>
                                @endif
                                <p class="mt-2 text-xs font-semibold text-indigo-700">System queue <code>default</code> luôn được giữ active.</p>
                            </section>
                        </div>

                        @if (! empty($preflight['blocking']))
                            <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                                <div class="font-bold text-rose-800">Preflight BLOCKED</div>
                                <ul class="mt-2 space-y-1 text-sm leading-6 text-rose-700">
                                    @foreach ($preflight['blocking'] as $reason)
                                        <li>• {{ $reason }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <button type="button" wire:click="closeLifecycleModal" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $lifecycleResult ? 'Đóng' : 'Hủy' }}</button>
                    @if (! $lifecycleResult)
                        <button
                            type="button"
                            wire:click="confirmLifecycleToggle"
                            wire:loading.attr="disabled"
                            wire:target="confirmLifecycleToggle"
                            @disabled(! $canExecute)
                            class="inline-flex h-10 items-center justify-center rounded-xl px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50 {{ $targetEnabled ? 'bg-blue-600 hover:bg-blue-700' : 'bg-rose-600 hover:bg-rose-700' }}">
                            {{ $targetEnabled ? 'Xác nhận bật Module' : 'Xác nhận tắt Module' }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
