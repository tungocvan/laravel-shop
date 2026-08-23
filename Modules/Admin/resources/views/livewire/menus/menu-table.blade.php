<div class="px-4 sm:px-6 md:px-8">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Menu</h1>
            <p class="mt-1 text-sm text-gray-500">Kéo thả để sắp xếp vị trí, phân cấp và quản lý menu quản trị.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.layout.design') }}#sidebar-menu" class="inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold uppercase text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-100" title="Thiết lập Font, màu sắc, icon, padding và khoảng cách của Sidebar Menu">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M3 12h2m14 0h2m-3.64-6.36-1.42 1.42M8.06 15.94l-1.42 1.42m0-11.72 1.42 1.42m7.88 8.88 1.42 1.42M8.5 12a3.5 3.5 0 1 0 7 0 3.5 3.5 0 0 0-7 0Z"/></svg>
                Thiết lập giao diện Menu
            </a>
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
            <div x-data="{ moduleFilter: 'all' }" class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Quét GET routes chưa có trong Menu</h3>
                            <p class="mt-1 text-sm text-gray-500">Tên hiển thị được gợi ý tự động và có thể chỉnh sửa trước khi thêm vào Menu.</p>
                        </div>
                        @if($routeCandidates !== [])
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                                <div class="min-w-52">
                                    <label for="route-module-filter" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Lọc theo Module</label>
                                    <select id="route-module-filter" x-model="moduleFilter" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                        <option value="all">Tất cả Module</option>
                                        @foreach(collect($routeCandidates)->pluck('group')->unique()->sort()->values() as $moduleGroup)
                                            <option value="{{ $moduleGroup }}">{{ \Illuminate\Support\Str::headline($moduleGroup) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" wire:click="selectAllRouteCandidates" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Chọn tất cả {{ count($routeCandidates) }}</button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="overflow-y-auto p-6">
                    @forelse(collect($routeCandidates)->groupBy('group') as $group => $candidates)
                        <div x-show="moduleFilter === 'all' || moduleFilter === @js((string) $group)" x-cloak class="mb-6 last:mb-0">
                            <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">{{ \Illuminate\Support\Str::headline($group) }} <span class="font-medium text-gray-400">({{ count($candidates) }})</span></h4>
                            <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                                @foreach($candidates as $candidate)
                                    <div class="grid gap-3 bg-white p-4 hover:bg-indigo-50/40 md:grid-cols-[auto,minmax(0,1fr),minmax(220px,0.8fr)] md:items-start">
                                        <input type="checkbox" wire:model.live="selectedRouteCandidates" value="{{ $candidate['id'] }}" class="mt-3 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" aria-label="Chọn route {{ $candidate['route_name'] }}">
                                        <div class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Route</span><span class="mt-1 block truncate font-mono text-xs text-gray-600">{{ $candidate['route_name'] }}</span><span class="mt-1 block truncate font-mono text-xs text-gray-400">{{ $candidate['url'] }}</span>@if($candidate['permission'])<span class="mt-2 inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">{{ $candidate['permission'] }}</span>@endif</div>
                                        <div><label for="route-name-{{ $candidate['id'] }}" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tên hiển thị gợi ý</label><input id="route-name-{{ $candidate['id'] }}" type="text" wire:model="routeCandidateNames.{{ $candidate['id'] }}" maxlength="255" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-medium text-gray-900 placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="Tên menu hiển thị"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center"><p class="font-medium text-gray-700">Không còn GET route hợp lệ nào cần thêm.</p><p class="mt-1 text-sm text-gray-500">Các route hệ thống, auth, API, Livewire, Telescope và route không có tên đã được loại bỏ.</p></div>
                    @endforelse
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 p-4 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeRouteScannerModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Đóng</button>
                    <button type="button" wire:click="addSelectedRouteCandidates" wire:loading.attr="disabled" wire:target="addSelectedRouteCandidates" @disabled(empty($selectedRouteCandidates)) class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="addSelectedRouteCandidates">Thêm {{ count($selectedRouteCandidates) }} route đã chọn</span><span wire:loading wire:target="addSelectedRouteCandidates">Đang thêm...</span></button>
                </div>
            </div>
        </div>
    @endif

    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6"><h3 class="text-lg font-bold text-gray-900">Import Menu từ Excel</h3><p class="mt-1 text-sm text-gray-500">Chọn file Excel theo template hệ thống.</p></div>
                <div class="space-y-4 p-6"><input type="file" wire:model="importFile" accept=".xlsx,.xls" class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-700">@error('importFile')<p class="text-sm text-red-600">{{ $message }}</p>@enderror @if($importFile)<p class="text-sm text-gray-600">Đã chọn: <span class="font-medium">{{ $importFile->getClientOriginalName() }}</span></p>@endif</div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 p-4"><button type="button" wire:click="closeImportModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button><button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import,importFile" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="import">Import</span><span wire:loading wire:target="import">Đang import...</span></button></div>
            </div>
        </div>
    @endif

    @if ($showBulkPermissionsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm">
            <div class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-200 p-6"><h3 class="text-lg font-bold text-gray-900">Phân quyền hàng loạt</h3><p class="mt-1 text-sm text-gray-500">Áp dụng permission cho {{ count($selectedMenus) }} menu đã chọn.</p></div>
                <div class="overflow-y-auto p-6"><div class="grid grid-cols-1 gap-3 sm:grid-cols-2">@foreach($permissions as $permission)<label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50"><input type="checkbox" wire:model="bulkPermissionIds" value="{{ $permission->id }}" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"><span><span class="block text-sm font-medium text-gray-800">{{ $permission->name }}</span><span class="block text-xs text-gray-500">{{ $permission->guard_name }}</span></span></label>@endforeach</div></div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 p-4"><button type="button" wire:click="closeBulkPermissionsModal" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button><button type="button" wire:click="applyBulkPermissions" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Áp dụng</button></div>
            </div>
        </div>
    @endif

    @if ($pendingDeleteMenuId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-gray-900">Xác nhận xóa Menu</h3><p class="mt-2 text-sm text-gray-600">Menu có thể chứa menu con. Bạn có chắc muốn xóa?</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="cancelDelete" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">Hủy</button><button type="button" wire:click="deleteMenu" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Xóa</button></div></div></div>
    @endif

    @if ($showBulkDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><h3 class="text-lg font-bold text-gray-900">Xác nhận xóa hàng loạt</h3><p class="mt-2 text-sm text-gray-600">Bạn sắp xóa {{ count($selectedMenus) }} menu đã chọn và các menu con liên quan.</p><div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="cancelBulkDelete" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">Hủy</button><button type="button" wire:click="bulkDelete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Xóa đã chọn</button></div></div></div>
    @endif
</div>

@script
<script>
    window.menuSortable = window.menuSortable || function () {
        return {
            instances: [],
            initSortable() {
                this.$nextTick(() => this.refreshSortable());
                Livewire.hook('morph.updated', () => this.$nextTick(() => this.refreshSortable()));
            },
            refreshSortable() {
                this.instances.forEach(instance => instance.destroy());
                this.instances = [];
                document.querySelectorAll('.menu-list').forEach(list => {
                    this.instances.push(Sortable.create(list, {
                        group: 'nested-menu', animation: 150, fallbackOnBody: true, swapThreshold: 0.65,
                        handle: '.drag-handle', draggable: '.menu-item',
                        onEnd: () => this.persist()
                    }));
                });
            },
            persist() {
                const walk = (list, parentId = null) => Array.from(list.children).filter(el => el.classList.contains('menu-item')).map((el, index) => {
                    const childList = el.querySelector(':scope > .menu-children');
                    return { id: Number(el.dataset.id), parent_id: parentId, order: index, children: childList ? walk(childList, Number(el.dataset.id)) : [] };
                });
                const root = document.getElementById('root-menu-list');
                if (root) this.$wire.updateMenuOrder(walk(root));
            }
        };
    };
</script>
@endscript
