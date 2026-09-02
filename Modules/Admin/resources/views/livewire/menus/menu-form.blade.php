<div class="px-4 py-6 sm:px-6 md:px-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-indigo-600">
                    <a href="{{ route('admin.menus.index') }}" class="hover:text-indigo-800">Quản lý Menu</a>
                    <span class="text-gray-300">/</span>
                    <span>{{ $isEdit ? 'Chỉnh sửa' : 'Thêm mới' }}</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $isEdit ? 'Chỉnh sửa Menu' : 'Thêm Menu mới' }}</h1>
                <p class="mt-1 text-sm text-gray-500">Cấu hình cấu trúc, liên kết, quyền truy cập và cách menu hiển thị trong Admin.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.menus.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">Hủy</a>
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo menu' }}</span>
                    <span wire:loading wire:target="save">Đang lưu...</span>
                </button>
            </div>
        </div>

        <form wire:submit="save" class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.75fr)] lg:items-start">
            <div class="space-y-6">
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                        <h2 class="text-sm font-bold text-gray-900">Thông tin chính</h2>
                        <p class="mt-1 text-xs text-gray-500">Tên, vị trí trong cây menu và đích điều hướng.</p>
                    </div>
                    <div class="space-y-5 p-5 sm:p-6">
                        <x-admin::form.input id="menu-name" label="Tên hiển thị" wire:model="name" type="text" placeholder="Ví dụ: Quản lý sản phẩm" required />

                        <div>
                            <x-admin::form.select id="menu-parent" label="Menu cha" wire:model="parent_id">
                                <option value="">— Là mục gốc —</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent['id'] }}">{{ $parent['name'] }}</option>
                                @endforeach
                            </x-admin::form.select>
                            <p class="mt-1.5 text-xs text-gray-500">Chọn vị trí cha để tạo đúng phân cấp. Có thể sắp xếp lại bằng kéo thả sau khi lưu.</p>
                        </div>

                        <div x-data="{ section: @entangle('is_section') }">
                            <label class="block text-sm font-medium text-gray-700">Loại menu</label>
                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                <button type="button" wire:click="$set('is_section', false)" class="rounded-xl border p-4 text-left transition {{ ! $is_section ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                    <span class="block text-sm font-bold {{ ! $is_section ? 'text-indigo-800' : 'text-gray-800' }}">Liên kết</span>
                                    <span class="mt-1 block text-xs text-gray-500">Mục menu mở một trang hoặc route cụ thể.</span>
                                </button>
                                <button type="button" wire:click="$set('is_section', true)" class="rounded-xl border p-4 text-left transition {{ $is_section ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                    <span class="block text-sm font-bold {{ $is_section ? 'text-indigo-800' : 'text-gray-800' }}">Section / Nhóm</span>
                                    <span class="mt-1 block text-xs text-gray-500">Chỉ dùng để nhóm menu con, không có liên kết click.</span>
                                </button>
                            </div>

                            <div x-show="!section" x-collapse class="mt-5">
                                <label for="menu-url" class="block text-sm font-medium text-gray-700">Đường dẫn (URL)</label>
                                <div class="mt-1 flex rounded-xl shadow-sm">
                                    <span class="inline-flex items-center rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">{{ url('/') }}/</span>
                                    <input id="menu-url" type="text" wire:model="url" class="w-full rounded-r-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="admin/products">
                                </div>
                                @error('url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                        <h2 class="text-sm font-bold text-gray-900">Quyền truy cập</h2>
                        <p class="mt-1 text-xs text-gray-500">Giới hạn menu theo permission. Để trống nếu menu dùng chung cho người đã vào Admin.</p>
                    </div>
                    <div class="p-5 sm:p-6">
                        <x-admin::form.select id="menu-permission" label="Yêu cầu quyền (Permission)" wire:model="can">
                            <option value="">— Public trong Admin —</option>
                            @foreach($permissions as $permissionName)
                                <option value="{{ $permissionName }}">{{ $permissionName }}</option>
                            @endforeach
                        </x-admin::form.select>
                    </div>
                </section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-6">
                <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-bold text-gray-900">Hiển thị</h2>
                    </div>
                    <div class="space-y-5 p-5">
                        <label for="active" class="flex items-start justify-between gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <span>
                                <span class="block text-sm font-semibold text-gray-900">Hiển thị menu</span>
                                <span class="mt-1 block text-xs text-gray-500">Tắt để tạm ẩn mà không xóa cấu hình.</span>
                            </span>
                            <input id="active" type="checkbox" wire:model="is_active" class="mt-0.5 h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </label>

                        <div x-data="{ section: @entangle('is_section') }" x-show="!section" x-collapse>
                            <label for="menu-icon" class="block text-sm font-medium text-gray-700">Icon</label>
                            <p class="mt-1 text-xs text-gray-500">Chọn nhanh icon phổ biến hoặc nhập tên Heroicons Outline.</p>

                            <div class="mt-3 grid grid-cols-4 gap-2">
                                @foreach(['home', 'users', 'cog', 'document-text', 'folder', 'shopping-cart', 'chart-bar', 'clipboard-document-list'] as $iconName)
                                    <button type="button" wire:click="$set('icon', '{{ $iconName }}')" title="{{ $iconName }}" class="flex h-11 items-center justify-center rounded-xl border transition {{ $icon === $iconName ? 'border-indigo-500 bg-indigo-50 text-indigo-600 ring-2 ring-indigo-100' : 'border-gray-200 bg-white text-gray-500 hover:border-indigo-200 hover:bg-indigo-50/50 hover:text-indigo-600' }}">
                                        <x-icon name="{{ $iconName }}" class="h-5 w-5" />
                                    </button>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <x-admin::form.input id="menu-icon" wire:model.live="icon" type="text" placeholder="Ví dụ: home" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-bold text-gray-900">Xem trước Sidebar</h2>
                        <p class="mt-1 text-xs text-gray-500">Preview nhanh của mục menu hiện tại.</p>
                    </div>
                    <div class="bg-slate-950 p-4">
                        <div class="rounded-xl bg-slate-900 p-2">
                            <div class="flex items-center gap-3 rounded-lg bg-slate-800 px-3 py-2.5 text-slate-100">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-indigo-300">
                                    @if($icon && ! $is_section)
                                        <x-icon name="{{ $icon }}" class="h-5 w-5" />
                                    @else
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold">{{ $name ?: 'Tên menu' }}</div>
                                    <div class="truncate text-[11px] text-slate-400">{{ $is_section ? 'Section / Nhóm' : ($url ?: 'Chưa cấu hình URL') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>

            <div class="lg:col-span-2 flex items-center justify-end gap-2 border-t border-gray-200 pt-5">
                <a href="{{ route('admin.menus.index') }}" class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Hủy</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo menu' }}</span>
                    <span wire:loading wire:target="save">Đang lưu...</span>
                </button>
            </div>
        </form>
    </div>
</div>
