<div class="px-4 sm:px-6 md:px-8" x-data="{ toolsOpen: false }">
    <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Menu</h1>
            <p class="mt-1 text-sm text-gray-500">Tổ chức cấu trúc menu quản trị, phân quyền và thứ tự hiển thị.</p>
            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                <span><strong class="font-semibold text-gray-900">{{ $totalMenus }}</strong> menu</span>
                <span class="text-gray-300">•</span>
                <span><strong class="font-semibold text-emerald-700">{{ $activeMenus }}</strong> hoạt động</span>
                <span class="text-gray-300">•</span>
                <span><strong class="font-semibold text-gray-700">{{ $totalMenus - $activeMenus }}</strong> đang tắt</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="openRouteScannerModal" wire:loading.attr="disabled" wire:target="openRouteScannerModal" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100 disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h10M4 18h7"/></svg>
                <span wire:loading.remove wire:target="openRouteScannerModal">Quét route</span>
                <span wire:loading wire:target="openRouteScannerModal">Đang quét...</span>
            </button>
            <a href="{{ route('admin.menus.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm menu
            </a>
            <div class="relative" @click.outside="toolsOpen = false">
                <button type="button" @click="toolsOpen = !toolsOpen" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50" :aria-expanded="toolsOpen.toString()">
                    Công cụ
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="toolsOpen" x-transition x-cloak class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                    <a href="{{ route('admin.layout.design') }}#sidebar-menu" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">Thiết lập giao diện Menu</a>
                    <button type="button" wire:click="exportTemplate" @click="toolsOpen = false" class="flex w-full items-center px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">Tải Template Excel</button>
                    <button type="button" wire:click="export" @click="toolsOpen = false" class="flex w-full items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50"><span>Export Excel</span>@if(!empty($selectedMenus))<span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">{{ count($selectedMenus) }} đã chọn</span>@endif</button>
                    <button type="button" wire:click="openImportModal" @click="toolsOpen = false" class="flex w-full items-center px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50">Import Menu</button>
                    <div class="my-1 border-t border-gray-100"></div>
                    <button type="button" wire:click="restoreDefaultMenu" wire:confirm="Khôi phục menu từ snapshot gần nhất tại storage/app/menu/menus.json?" @click="toolsOpen = false" class="flex w-full items-center px-4 py-2.5 text-left text-sm font-medium text-amber-700 hover:bg-amber-50">Khôi phục snapshot</button>
                </div>
            </div>
        </div>
    </div>

    <section class="overflow-visible rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4 sm:p-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                <div class="flex-1">
                    <x-admin::form.input label="Tìm kiếm" type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc URL..." />
                </div>
                <div class="sm:w-56">
                    <x-admin::form.select label="Trạng thái" wire:model.live="filterStatus">
                        <option value="all">Tất cả</option>
                        <option value="active">Đang hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                    </x-admin::form.select>
                </div>
                <div class="flex flex-wrap items-center gap-2 xl:pb-0.5">
                    <button type="button" @click="$dispatch('menu-tree-expand')" class="rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Mở rộng tất cả</button>
                    <button type="button" @click="$dispatch('menu-tree-collapse')" class="rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Thu gọn tất cả</button>
                </div>
            </div>
        </div>

        <div x-data="menuSortable()" x-init="initSortable()" class="min-h-[420px] bg-gray-50/70 p-3 sm:p-4">
            <div class="mb-3 flex flex-col gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                <label for="menu-select-all" class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700">
                    <input id="menu-select-all" type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Chọn tất cả theo bộ lọc hiện tại
                </label>
                <span class="text-xs text-gray-500">Kéo biểu tượng chấm để thay đổi thứ tự và phân cấp.</span>
            </div>

            <div class="hidden grid-cols-[minmax(0,1.15fr)_minmax(180px,1fr)_minmax(170px,0.8fr)_auto] gap-4 px-[126px] pb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-400 lg:grid">
                <span>Tên menu</span><span>URL</span><span>Quyền</span><span class="text-right">Trạng thái</span>
            </div>

            <ul id="root-menu-list" class="menu-list space-y-2">
                @foreach ($menus as $menu)
                    <x-menu-item :menu="$menu" :selected="$selectedMenus" />
                @endforeach
            </ul>

            @if ($menus->isEmpty())
                <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white py-12 text-center text-gray-500">
                    @if ($search || $filterStatus !== 'all')
                        <p class="text-base font-semibold text-gray-800">Không tìm thấy menu phù hợp</p>
                        <p class="mt-1 text-sm">Thử thay đổi từ khóa hoặc trạng thái lọc.</p>
                    @else
                        <p class="text-base font-semibold text-gray-800">Chưa có menu nào</p>
                        <p class="mt-1 text-sm">Hãy Quét route, Import hoặc Thêm menu mới.</p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    @if (! empty($selectedMenus))
        <div class="sticky bottom-4 z-40 mx-auto mt-4 max-w-5xl rounded-2xl border border-indigo-200 bg-white/95 p-3 shadow-2xl backdrop-blur">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-700"><span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-indigo-600 px-2 font-bold text-white">{{ count($selectedMenus) }}</span><span>menu đã chọn</span></div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="bulkToggleStatus(true)" wire:loading.attr="disabled" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-50">Bật</button>
                    <button type="button" wire:click="bulkToggleStatus(false)" wire:loading.attr="disabled" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">Tắt</button>
                    <button type="button" wire:click="openBulkPermissionsModal" wire:loading.attr="disabled" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50">Gán quyền</button>
                    <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 disabled:opacity-50">Export đã chọn</button>
                    <button type="button" wire:click="requestBulkDelete" wire:loading.attr="disabled" class="rounded-xl bg-red-600 px-3 py-2 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50">Xóa</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showRouteScannerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm">
            <div x-data="{ moduleFilter: 'all' }" class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div><h3 class="text-lg font-bold text-gray-900">Quét GET routes chưa có trong Menu</h3><p class="mt-1 text-sm text-gray-500">Tên hiển thị được gợi ý tự động và có thể chỉnh sửa trước khi thêm vào Menu.</p></div>
                        @if($routeCandidates !== [])
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end"><div class="min-w-52"><label for="route-module-filter" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Lọc theo Module</label><select id="route-module-filter" x-model="moduleFilter" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"><option value="all">Tất cả Module</option>@foreach(collect($routeCandidates)->pluck('group')->unique()->sort()->values() as $moduleGroup)<option value="{{ $moduleGroup }}">{{ \Illuminate\Support\Str::headline($moduleGroup) }}</option>@endforeach</select></div><button type="button" wire:click="selectAllRouteCandidates" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Chọn tất cả {{ count($routeCandidates) }}</button></div>
                        @endif
                    </div>
                </div>
                <div class="overflow-y-auto p-6">
                    @forelse(collect($routeCandidates)->groupBy('group') as $group => $candidates)
                        <div x-show="moduleFilter === 'all' || moduleFilter === @js((string) $group)" x-cloak class="mb-6 last:mb-0"><h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">{{ \Illuminate\Support\Str::headline($group) }} <span class="font-medium text-gray-400">({{ count($candidates) }})</span></h4><div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">@foreach($candidates as $candidate)<div class="grid gap-3 bg-white p-4 hover:bg-indigo-50/40 md:grid-cols-[auto,minmax(0,1fr),minmax(220px,0.8fr)] md:items-start"><input type="checkbox" wire:model.live="selectedRouteCandidates" value="{{ $candidate['id'] }}" class="mt-3 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" aria-label="Chọn route {{ $candidate['route_name'] }}"><div class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Route</span><span class="mt-1 block truncate font-mono text-xs text-gray-600">{{ $candidate['route_name'] }}</span><span class="mt-1 block truncate font-mono text-xs text-gray-400">{{ $candidate['url'] }}</span>@if($candidate['permission'])<span class="mt-2 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">{{ $candidate['permission'] }}</span>@endif</div><div><label for="route-name-{{ $candidate['id'] }}" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tên hiển thị gợi ý</label><input id="route-name-{{ $candidate['id'] }}" type="text" wire:model="routeCandidateNames.{{ $candidate['id'] }}" maxlength="255" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="Tên menu hiển thị"></div></div>@endforeach</div></div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center"><p class="font-semibold text-gray-800">Không có GET Admin route mới</p><p class="mt-1 text-sm text-gray-500">Các route đủ điều kiện đã có trong Menu hoặc không phù hợp để tạo menu trực tiếp.</p></div>
                    @endforelse
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4"><span class="text-sm text-gray-600">Đã chọn: <strong>{{ count($selectedRouteCandidates) }}</strong></span><div class="flex gap-3"><button type="button" wire:click="closeRouteScannerModal" wire:loading.attr="disabled" wire:target="addSelectedRouteCandidates" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Hủy</button><button type="button" wire:click="addSelectedRouteCandidates" wire:loading.attr="disabled" wire:target="addSelectedRouteCandidates" @disabled($selectedRouteCandidates === []) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="addSelectedRouteCandidates">Thêm vào Menu</span><span wire:loading wire:target="addSelectedRouteCandidates">Đang thêm...</span></button></div></div>
            </div>
        </div>
    @endif

    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-xl bg-white shadow-xl"><div class="p-6"><h3 class="text-lg font-bold text-gray-900">Import Menu</h3><p class="mt-2 text-xs text-gray-600">Chấp nhận .xlsx hoặc .csv với các cột: key, parent_key, name, url, icon, can, is_active, sort_order.</p><div class="mt-5"><label class="block rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:border-indigo-400 hover:bg-gray-50"><span class="text-sm font-medium text-gray-600">{{ $this->importFileName ?: 'Chọn file .xlsx hoặc .csv' }}</span><input type="file" wire:model="importFile" class="hidden" accept=".xlsx,.csv"></label><div wire:loading wire:target="importFile" class="mt-2 text-xs font-semibold text-indigo-600">Đang upload file...</div>@error('importFile')<p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror</div><div class="mt-5"><label class="mb-2 block text-sm font-bold text-gray-700">Khi key menu đã tồn tại</label><div class="space-y-2 rounded-lg border border-gray-200 p-3"><label class="flex items-start gap-3"><input type="radio" wire:model="importMode" value="skip_duplicate" class="mt-1 border-gray-300 text-indigo-600 focus:ring-indigo-500"><span><span class="block text-sm font-medium text-gray-800">Bỏ qua dữ liệu đã tồn tại</span><span class="block text-xs text-gray-500">An toàn mặc định, không thay đổi menu hiện có.</span></span></label><label class="flex items-start gap-3"><input type="radio" wire:model="importMode" value="update_or_create" class="mt-1 border-gray-300 text-indigo-600 focus:ring-indigo-500"><span><span class="block text-sm font-medium text-gray-800">Cập nhật dữ liệu đã tồn tại</span><span class="block text-xs text-gray-500">Giữ nguyên ID và cập nhật nội dung theo key trong file.</span></span></label></div>@error('importMode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div><div class="flex justify-end gap-3 rounded-b-xl bg-gray-50 px-6 py-4"><button type="button" wire:click="closeImportModal" wire:loading.attr="disabled" wire:target="import" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Hủy</button><button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import,importFile" @disabled(!$importFile) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="import">Tiến hành Import</span><span wire:loading wire:target="import">Đang import...</span></button></div></div></div>
    @endif

    @if ($importReport)
        <div class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-bold text-gray-900">Import report</h3><p class="mt-1 text-xs text-gray-500">Total: {{ $importReport['total_rows'] ?? 0 }}, success: {{ $importReport['success_rows'] ?? 0 }}, skipped: {{ $importReport['skipped_rows'] ?? 0 }}, errors: {{ $importReport['error_rows'] ?? 0 }}</p></div><button type="button" wire:click="$set('importReport', null)" class="text-xs font-semibold text-gray-500 hover:text-gray-900">Đóng report</button></div>@if (! empty($importReport['errors']))<div class="mt-4 overflow-x-auto rounded-lg border border-red-100"><table class="min-w-full divide-y divide-red-100 text-xs"><thead class="bg-red-50 text-red-700"><tr><th class="px-3 py-2 text-left font-semibold">Row</th><th class="px-3 py-2 text-left font-semibold">Column</th><th class="px-3 py-2 text-left font-semibold">Value</th><th class="px-3 py-2 text-left font-semibold">Reason</th></tr></thead><tbody class="divide-y divide-red-100 bg-white">@foreach ($importReport['errors'] as $error)<tr><td class="px-3 py-2 text-gray-700">{{ $error['row'] ?? '-' }}</td><td class="px-3 py-2 text-gray-700">{{ $error['column'] ?? '-' }}</td><td class="px-3 py-2 text-gray-700">{{ $error['value'] ?? '-' }}</td><td class="px-3 py-2 text-red-700">{{ $error['reason'] ?? '-' }}</td></tr>@endforeach</tbody></table></div>@endif</div>
    @endif

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-2xl border border-red-100 bg-white shadow-2xl"><div class="p-6"><h3 class="text-lg font-bold text-gray-900">Xóa {{ count($selectedMenus) }} menu đã chọn?</h3><p class="mt-2 text-sm text-gray-600">Thao tác này áp dụng đúng các menu đang được chọn. Hãy kiểm tra phạm vi trước khi xác nhận.</p></div><div class="flex justify-end gap-3 rounded-b-2xl bg-gray-50 px-6 py-4"><button type="button" wire:click="closeBulkDeleteModal" wire:loading.attr="disabled" wire:target="bulkDelete" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Hủy</button><button type="button" wire:click="bulkDelete" wire:loading.attr="disabled" wire:target="bulkDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50"><span wire:loading.remove wire:target="bulkDelete">Xác nhận xóa</span><span wire:loading wire:target="bulkDelete">Đang xóa...</span></button></div></div></div>
    @endif

    @if ($showBulkPermissionsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-xl bg-white shadow-xl"><div class="p-6"><h3 class="text-lg font-bold text-gray-900">Phân quyền hàng loạt</h3><p class="mt-2 text-xs text-gray-600">Áp dụng quyền cho {{ count($selectedMenus) }} menu đã chọn.</p><div class="mt-5"><x-admin::form.select label="Chọn quyền" wire:model="bulkPermission"><option value="">-- Không có --</option>@foreach ($permissionOptions as $permissionName)<option value="{{ $permissionName }}">{{ $permissionName }}</option>@endforeach</x-admin::form.select></div></div><div class="flex justify-end gap-3 rounded-b-xl bg-gray-50 px-6 py-4"><button type="button" wire:click="closeBulkPermissionsModal" wire:loading.attr="disabled" wire:target="bulkAssignPermissions" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Hủy</button><button type="button" wire:click="bulkAssignPermissions" wire:loading.attr="disabled" wire:target="bulkAssignPermissions" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50"><span wire:loading.remove wire:target="bulkAssignPermissions">Cập nhật quyền</span><span wire:loading wire:target="bulkAssignPermissions">Đang xử lý...</span></button></div></div></div>
    @endif

    <script>
        function menuSortable() {
            return {
                sortables: [],
                initSortable() {
                    this.destroySortables();
                    document.querySelectorAll('.menu-list').forEach((element) => {
                        this.sortables.push(new Sortable(element, {
                            group: 'nested',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            handle: '.drag-handle',
                            ghostClass: 'menu-sortable-ghost',
                            chosenClass: 'menu-sortable-chosen',
                            onEnd: () => this.saveOrder(),
                        }));
                    });
                },
                destroySortables() {
                    this.sortables.forEach((sortable) => sortable.destroy());
                    this.sortables = [];
                },
                saveOrder() {
                    const getIds = (root) => Array.from(root.children)
                        .filter((element) => element.tagName === 'LI')
                        .map((element) => {
                            const item = { id: element.getAttribute('data-id') };
                            const childList = element.querySelector(':scope > ul');
                            if (childList && childList.children.length > 0) item.children = getIds(childList);
                            return item;
                        });
                    const rootList = document.getElementById('root-menu-list');
                    if (rootList) @this.updateMenuOrder(getIds(rootList));
                }
            }
        }
    </script>
    <style>
        .menu-sortable-ghost { background-color: #eef2ff; border: 1px dashed #6366f1; opacity: .65; border-radius: .75rem; }
        .menu-sortable-chosen { box-shadow: 0 12px 28px rgba(15, 23, 42, .12); }
    </style>
</div>
