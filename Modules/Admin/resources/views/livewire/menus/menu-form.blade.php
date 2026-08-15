<div class="mx-auto max-w-2xl px-4 py-6 sm:px-6 md:px-8">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $isEdit ? 'Chỉnh sửa Menu' : 'Thêm Menu mới' }}</h1>
            <p class="mt-1 text-sm text-gray-500">Cấu hình vị trí, đường dẫn, quyền và trạng thái hiển thị của menu quản trị.</p>
        </div>
        <a href="{{ route('admin.menus.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">Quay lại</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="h-1.5 w-full bg-indigo-600"></div>

        <form wire:submit="save" class="space-y-6 p-6">
            <div class="flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                <div>
                    <span class="block text-sm font-bold text-indigo-900">Là Tiêu đề nhóm (Section)?</span>
                    <span class="text-xs text-indigo-600">Dùng để phân chia khu vực, không có link click.</span>
                </div>
                <button type="button" wire:click="$toggle('is_section')" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $is_section ? 'bg-indigo-600' : 'bg-gray-300' }}" aria-pressed="{{ $is_section ? 'true' : 'false' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $is_section ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>

            <x-admin::form.input
                id="menu-name"
                label="Tên hiển thị"
                wire:model="name"
                type="text"
                placeholder="Tên Menu"
                required
            />

            <x-admin::form.select id="menu-parent" label="Menu cha" wire:model="parent_id">
                <option value="">-- Là mục gốc --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent['id'] }}">{{ $parent['name'] }}</option>
                @endforeach
            </x-admin::form.select>

            <div x-data="{ section: @entangle('is_section') }" x-show="!section" x-collapse class="space-y-6">
                <div>
                    <label for="menu-url" class="block text-sm font-medium text-gray-700">Đường dẫn (URL)</label>
                    <div class="mt-1 flex rounded-xl shadow-sm">
                        <span class="inline-flex items-center rounded-l-xl border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500">{{ url('/') }}/</span>
                        <input id="menu-url" type="text" wire:model="url" class="w-full rounded-r-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" placeholder="admin/products">
                    </div>
                    @error('url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="menu-icon" class="block text-sm font-medium text-gray-700">Icon (Heroicons Outline)</label>
                    <div class="mt-1 flex gap-3">
                        <div class="flex-1">
                            <x-admin::form.input id="menu-icon" wire:model.live="icon" type="text" placeholder="home" />
                        </div>
                        <div class="flex h-12 w-12 flex-none items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-indigo-600">
                            @if($icon)
                                <x-icon name="{{ $icon }}" class="h-6 w-6" />
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <x-admin::form.select id="menu-permission" label="Yêu cầu quyền (Permission)" wire:model="can">
                <option value="">-- Công khai --</option>
                @foreach($permissions as $permissionName)
                    <option value="{{ $permissionName }}">{{ $permissionName }}</option>
                @endforeach
            </x-admin::form.select>

            <div class="flex items-center justify-between border-t border-gray-100 pt-6">
                <label for="active" class="flex items-center gap-2 text-sm font-medium text-gray-900">
                    <input id="active" type="checkbox" wire:model="is_active" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Hiển thị menu này
                </label>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">{{ $isEdit ? 'Lưu thay đổi' : 'Tạo mới' }}</span>
                    <span wire:loading wire:target="save">Đang lưu...</span>
                </button>
            </div>
        </form>
    </div>
</div>
