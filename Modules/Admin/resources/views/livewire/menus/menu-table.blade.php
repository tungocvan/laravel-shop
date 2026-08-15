<div class="px-4 sm:px-6 md:px-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Menu</h1>
            <p class="mt-1 text-sm text-gray-500">Kéo thả để sắp xếp vị trí, phân cấp và quản lý menu quản trị.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openRouteScannerModal" wire:loading.attr="disabled" wire:target="openRouteScannerModal" class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-bold uppercase text-indigo-700 shadow-sm hover:bg-indigo-100 disabled:opacity-50"><span wire:loading.remove wire:target="openRouteScannerModal">Quét Module chưa có trong Menu</span><span wire:loading wire:target="openRouteScannerModal">Đang quét...</span></button>
            <button type="button" wire:click="exportTemplate" wire:loading.attr="disabled" wire:target="exportTemplate" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold uppercase text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"><span wire:loading.remove wire:target="exportTemplate">Template Excel</span><span wire:loading wire:target="exportTemplate">Đang tạo...</span></button>
            <button type="button" wire:click="export" wire:loading.attr="disabled" wire:target="export" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold uppercase text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"><span wire:loading.remove wire:target="export">{{ empty($selectedMenus) ? 'Export Excel' : 'Export '.count($selectedMenus).' đã chọn' }}</span><span wire:loading wire:target="export">Đang export...</span></button>
            <button type="button" wire:click="openImportModal" wire:loading.attr="disabled" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold uppercase text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50">Import</button>
            <button type="button" wire:click="restoreDefaultMenu" wire:confirm="Khôi phục menu từ snapshot gần nhất tại storage/app/menu/menus.json?" wire:loading.attr="disabled" wire:target="restoreDefaultMenu" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold uppercase text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"><span wire:loading.remove wire:target="restoreDefaultMenu">Khôi phục snapshot</span><span wire:loading wire:target="restoreDefaultMenu">Đang khôi phục...</span></button>
            <a href="{{ route('admin.menus.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold uppercase tracking-widest text-white hover:bg-indigo-700">Thêm mới</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="text-sm font-medium text-gray-500">Tổng số menu</div><div class="mt-1 text-xl font-semibold text-gray-900">{{ $totalMenus }}</div></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="text-sm font-medium text-gray-500">Menu đang hoạt động</div><div class="mt-1 text-xl font-semibold text-green-700">{{ $activeMenus }}</div></div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="text-sm font-medium text-gray-500">Menu không hoạt động</div><div class="mt-1 text-xl font-semibold text-gray-900">{{ $totalMenus - $activeMenus }}</div></div>
    </div>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="flex-1"><x-admin::form.input label="Tìm kiếm" type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc URL..." /></div>
            <div class="sm:w-56"><x-admin::form.select label="Trạng thái" wire:model.live="filterStatus"><option value="all">Tất cả</option><option value="active">Đang hoạt động</option><option value="inactive">Không hoạt động</option></x-admin::form.select></div>
        </div>
    </div>

    @if (! empty($selectedMenus))
        <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm font-medium text-indigo-800">Đã chọn {{ count($selectedMenus) }} menu. Chọn menu cha sẽ chọn toàn bộ menu con.</span>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="bulkToggleStatus(true)" wire:loading.attr="disabled" class="rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50">Bật đã chọn</button>
                    <button type="button" wire:click="bulkToggleStatus(false)" wire:loading.attr="disabled" class="rounded-lg bg-gray-600 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50">Tắt đã chọn</button>
                    <button type="button" wire:click="openBulkPermissionsModal" wire:loading.attr="disabled" class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">Phân quyền</button>
                    <button type="button" wire:click="requestBulkDelete" wire:loading.attr="disabled" class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">Xóa đã chọn</button>
                </div>
            </div>
        </div>
    @endif

    <div x-data="menuSortable()" x-init="initSortable()" class="min-h-[400px] rounded-xl border border-gray-200 bg-gray-50 p-4 sm:p-6">
        <div class="mb-4 flex items-center border-b border-gray-200 pb-3"><input id="menu-select-all" type="checkbox" wire:model.live="selectAll" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><label for="menu-select-all" class="ml-2 text-sm font-medium text-gray-700">Chọn tất cả menu theo bộ lọc hiện tại</label></div>
        <ul id="root-menu-list" class="menu-list space-y-3">@foreach ($menus as $menu)<x-menu-item :menu="$menu" :selected="$selectedMenus" />@endforeach</ul>
        @if ($menus->isEmpty())<div class="rounded-lg border-2 border-dashed border-gray-300 py-10 text-center text-gray-400">@if ($search || $filterStatus !== 'all')<p class="text-lg font-medium">Không tìm thấy menu nào</p><p class="mt-1 text-sm">Thử điều chỉnh bộ lọc tìm kiếm.</p>@else<p>Chưa có menu nào. Hãy Quét Module, Import hoặc Thêm mới.</p>@endif</div>@endif
    </div>

    @if ($showRouteScannerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div><h3 class="text-lg font-bold text-gray-900">Quét GET routes chưa có trong Menu</h3><p class="mt-1 text-sm text-gray-500">Chỉ hiển thị named GET Admin routes không có tham số bắt buộc và chưa tồn tại trong menu.</p></div>
                        @if($routeCandidates !== [])<button type="button" wire:click="selectAllRouteCandidates" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Chọn tất cả {{ count($routeCandidates) }}</button>@endif
                    </div>
                </div>

                <div class="overflow-y-auto p-6">
                    @forelse(collect($routeCandidates)->groupBy('group') as $group => $candidates)
                        <div class="mb-6 last:mb-0">
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">{{ \Illuminate\Support\Str::headline($group) }}</h4>
                            <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                                @foreach($candidates as $candidate)
                                    <label class="flex cursor-pointer items-start gap-3 bg-white p-4 hover:bg-indigo-50/40">
                                        <input type="checkbox" wire:model.live="selectedRouteCandidates" value="{{ $candidate['id'] }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-gray-900">{{ $candidate['name'] }}</span><span class="mt-1 block truncate font-mono text-xs text-gray-500">{{ $candidate['route_name'] }} · {{ $candidate['url'] }}</span>@if($candidate['permission'])<span class="mt-1 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">{{ $candidate['permission'] }}</span>@endif</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center"><p class="font-semibold text-gray-800">Không có GET Admin route mới</p><p class="mt-1 text-sm text-gray-500">Các route đủ điều kiện đã có trong Menu hoặc không phù hợp để tạo menu trực tiếp.</p></div>
                    @endforelse
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <span class="text-sm text-gray-600">Đã chọn: <strong>{{ count($selectedRouteCandidates) }}</strong></span>
                    <div class="flex gap-3"><button type="button" wire:click="closeRouteScannerModal" wire:loading.attr="disabled" wire:target="addSelectedRouteCandidates" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">Hủy</button><button type="button" wire:click="addSelectedRouteCandidates" wire:loading.attr="disabled" wire:target="addSelectedRouteCandidates" @disabled($selectedRouteCandidates === []) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="addSelectedRouteCandidates">Thêm vào Menu</span><span wire:loading wire:target="addSelectedRouteCandidates">Đang thêm...</span></button></div>
                </div>
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
                        this.sortables.push(new Sortable(element, { group: 'nested', animation: 150, fallbackOnBody: true, swapThreshold: 0.65, handle: '.drag-handle', ghostClass: 'bg-indigo-50', onEnd: () => this.saveOrder() }));
                    });
                },
                destroySortables() { this.sortables.forEach((sortable) => sortable.destroy()); this.sortables = []; },
                saveOrder() {
                    const getIds = (root) => Array.from(root.children).filter((element) => element.tagName === 'LI').map((element) => { const item = { id: element.getAttribute('data-id') }; const childList = element.querySelector(':scope > ul'); if (childList && childList.children.length > 0) item.children = getIds(childList); return item; });
                    const rootList = document.getElementById('root-menu-list'); if (rootList) @this.updateMenuOrder(getIds(rootList));
                }
            }
        }
    </script>
    <style>.bg-indigo-50 { background-color: #eef2ff; border: 1px dashed #6366f1; opacity: 0.8; }</style>
</div>
