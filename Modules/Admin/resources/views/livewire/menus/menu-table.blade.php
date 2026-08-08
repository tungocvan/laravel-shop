<div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Quản lý Menu</h1>
            <p class="mt-1 text-sm text-gray-500">Kéo thả để sắp xếp vị trí và phân cấp menu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="exportTemplate" wire:loading.attr="disabled" wire:target="exportTemplate"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-bold uppercase rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z" />
                </svg>
                Template Excel
            </button>

            <button wire:click="export" wire:loading.attr="disabled" wire:target="export"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-bold uppercase rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Excel
            </button>

            <button wire:click="$set('showImportModal', true)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-bold uppercase rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Import
            </button>

            <button type="button"
                onclick="if(!confirm('Xác nhận khôi phục menu mặc định từ file cấu hình?')) return false;"
                wire:click="restoreDefaultMenu"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-bold uppercase rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v6h6M20 20v-6h-6M4 14l6-6m10 10l-6 6" />
                </svg>
                Khôi phục
            </button>

            <a href="{{ route('admin.menus.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Thêm Mới
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Tổng số menu</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ $totalMenus }}</dd>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Menu đang hoạt động</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ $activeMenus }}</dd>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <dt class="text-sm font-medium text-gray-500 truncate">Menu không hoạt động</dt>
                    <dd class="text-lg font-semibold text-gray-900">{{ $totalMenus - $activeMenus }}</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc URL..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="sm:w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select wire:model.live="filterStatus"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">Tất cả</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Không hoạt động</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    @if (!empty($selectedMenus))
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-indigo-400 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-indigo-800">
                        Đã chọn {{ count($selectedMenus) }} menu
                    </span>
                </div>
                <div class="flex gap-2">
                    <button wire:click="bulkToggleStatus(true)"
                        class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition">
                        Bật tất cả
                    </button>
                    <button wire:click="bulkToggleStatus(false)"
                        class="px-3 py-1 bg-gray-600 text-white text-sm rounded hover:bg-gray-700 transition">
                        Tắt tất cả
                    </button>
                    <button wire:click="openBulkPermissionsModal"
                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition">
                        Phân quyền
                    </button>
                    <button wire:click="bulkDelete" wire:confirm="Xóa {{ count($selectedMenus) }} menu đã chọn?"
                        class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition">
                        Xóa tất cả
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div x-data="menuSortable()" x-init="initSortable()"
        class="bg-gray-50 rounded-xl border border-gray-200 p-6 min-h-[400px]">
        <!-- Select All Checkbox -->
        <div class="flex items-center mb-4 pb-2 border-b border-gray-200">
            <input type="checkbox" wire:model.live="selectAll"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label class="ml-2 text-sm text-gray-700 font-medium">Chọn tất cả</label>
        </div>

        <ul id="root-menu-list" class="space-y-3 menu-list">
            @foreach ($menus as $menu)
                <x-menu-item :menu="$menu" :selected="$selectedMenus" />
            @endforeach
        </ul>

        @if ($menus->isEmpty())
            <div class="text-center py-10 text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                @if ($search || $filterStatus !== 'all')
                    <p class="text-lg font-medium">Không tìm thấy menu nào</p>
                    <p class="text-sm mt-1">Thử điều chỉnh bộ lọc tìm kiếm</p>
                    <button wire:click="$set('search', ''); $set('filterStatus', 'all')"
                        class="mt-3 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        Xóa bộ lọc
                    </button>
                @else
                    Chưa có menu nào. Hãy Import hoặc Thêm mới!
                @endif
            </div>
        @endif
    </div>
    <div x-data="{ show: @entangle('showImportModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
            @click="$wire.closeImportModal(); fileName = null"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-md">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-6 w-6 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Import Menu (Excel)
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-800">
                                Upload file .xlsx hoac .csv voi cac cot:
                                <strong>key, parent_key, name, url, icon, can, is_active, sort_order</strong>.
                                Neu key da ton tai, he thong se bo qua dong trung.
                            </div>
                            <label x-data="{ fileName: null }"
                                class="block w-full rounded-xl border-2 border-dashed border-gray-300 p-8 text-center hover:bg-gray-50 hover:border-indigo-400 cursor-pointer transition">
                                <span class="text-sm text-gray-600 font-medium"
                                    x-text="fileName ? 'Da chon: ' + fileName : 'Click de chon file .xlsx hoac .csv'"></span>
                                <input type="file" wire:model.live="importFile" class="hidden" accept=".xlsx,.csv"
                                    @change="fileName = $event.target.files[0] ? $event.target.files[0].name : null">
                            </label>
                            <div wire:loading wire:target="importFile" class="text-xs font-semibold text-indigo-600">
                                Dang upload file, vui long cho...
                            </div>
                            @error('importFile')
                                <p class="text-red-500 text-xs font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button" @click="$wire.closeImportModal(); fileName = null"
                            wire:click="closeImportModal"
                            class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button>
                        <button type="button" wire:click="import" wire:loading.attr="disabled" wire:target="import,importFile"
                            @disabled(!$importFile)
                            class="px-4 py-2 bg-indigo-600 rounded-lg text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70">
                            <span wire:loading.remove wire:target="import,importFile">Tien hanh Import</span>
                            <span wire:loading wire:target="importFile">Dang upload...</span>
                            <span wire:loading wire:target="import">Dang import...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($importReport)
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Import report</h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Total: {{ $importReport['total_rows'] ?? 0 }},
                        success: {{ $importReport['success_rows'] ?? 0 }},
                        skipped: {{ $importReport['skipped_rows'] ?? 0 }},
                        errors: {{ $importReport['error_rows'] ?? 0 }}
                    </p>
                </div>
                <button type="button" wire:click="$set('importReport', null)"
                    class="text-xs font-semibold text-gray-500 hover:text-gray-900">
                    Close report
                </button>
            </div>

            @if (!empty($importReport['errors']))
                <div class="mt-4 overflow-hidden rounded-lg border border-red-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-red-100 text-xs">
                            <thead class="bg-red-50 text-red-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Row</th>
                                    <th class="px-3 py-2 text-left font-semibold">Column</th>
                                    <th class="px-3 py-2 text-left font-semibold">Value</th>
                                    <th class="px-3 py-2 text-left font-semibold">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-red-100 bg-white">
                                @foreach ($importReport['errors'] as $error)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-700">{{ $error['row'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $error['column'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700">{{ $error['value'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-red-700">{{ $error['reason'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
    
    <!-- Bulk Permissions Modal -->
    <div x-data="{ show: @entangle('showBulkPermissionsModal') }" x-show="show" style="display: none;" class="relative z-50">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="show = false"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="relative transform overflow-hidden rounded-xl bg-white shadow-xl transition-all w-full max-w-md">
                    <div class="px-6 py-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <svg class="h-6 w-6 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Phân quyền hàng loạt
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-800">
                                Ap dung quyen cho <strong>{{ count($selectedMenus) }} menu</strong> da chon.
                                De bo quyen, chon "Khong co".
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Chọn quyền</label>
                                <select wire:model="bulkPermission"
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">-- Không có --</option>
                                    @foreach (\Spatie\Permission\Models\Permission::orderBy('name')->get() as $perm)
                                        <option value="{{ $perm->name }}">{{ $perm->name }}</option>
                                    @endforeach
                                </select>
                                @error('bulkPermission')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-3">
                        <button type="button"
                            @click="show = false; $wire.showBulkPermissionsModal = false; $wire.bulkPermission = null;"
                            class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Hủy</button>
                        <button type="button" wire:click="bulkAssignPermissions" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-blue-600 rounded-lg text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-70">
                            <span wire:loading.remove wire:target="bulkAssignPermissions">Cập nhật quyền</span>
                            <span wire:loading wire:target="bulkAssignPermissions">Đang xử lý...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function menuSortable() {
            return {
                initSortable() {
                    // Tìm tất cả các list (cả cha và con)
                    const nestedSortables = [].slice.call(document.querySelectorAll('.menu-list'));

                    // Khởi tạo Sortable cho từng list
                    nestedSortables.forEach((el) => {
                        new Sortable(el, {
                            group: 'nested', // Cho phép kéo qua lại giữa các cấp
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            handle: '.drag-handle', // Chỉ kéo được khi nắm vào icon này
                            ghostClass: 'bg-indigo-50', // Class khi đang kéo
                            onEnd: (evt) => {
                                this.saveOrder();
                            }
                        });
                    });
                },
                saveOrder() {
                    // Hàm đệ quy để lấy cấu trúc ID
                    const getIds = (root) => {
                        const items = [];
                        // Lấy các thẻ li trực tiếp của ul hiện tại
                        const lis = root.children;

                        for (let i = 0; i < lis.length; i++) {
                            const li = lis[i];
                            // Bỏ qua nếu không phải element node (hoặc template)
                            if (li.tagName !== 'LI') continue;

                            const id = li.getAttribute('data-id');
                            // Tìm ul con bên trong li này (nếu có)
                            const childUl = li.querySelector('ul');

                            const item = {
                                id: id
                            };
                            if (childUl && childUl.children.length > 0) {
                                item.children = getIds(childUl);
                            }
                            items.push(item);
                        }
                        return items;
                    };

                    const rootList = document.getElementById('root-menu-list');
                    const payload = getIds(rootList);

                    // Gửi về Livewire
                    @this.updateMenuOrder(payload);
                }
            }
        }
    </script>
    {{-- <script>
        document.addEventListener('DOMContentLoaded', function() {

            window.addEventListener('notify', function(event) {
                const detail = event.detail || {};

                // DEBUG nếu cần
                // console.log('notify:', detail);

                if (detail.type === 'success') {
                    const msg = detail.content || 'Thành công';

                    if (confirm(msg)) {
                        window.location.reload();
                    }
                }

                // nếu muốn xử lý error/warning sau này
                if (detail.type === 'error') {
                    alert(detail.content || 'Có lỗi xảy ra');
                }

                if (detail.type === 'warning') {
                    alert(detail.content || 'Cảnh báo');
                }
            });

        });
    </script> --}}
    <style>
        /* Style cho placeholder khi kéo */
        .bg-indigo-50 {
            background-color: #eef2ff;
            border: 1px dashed #6366f1;
            opacity: 0.8;
        }
    </style>

</div>
