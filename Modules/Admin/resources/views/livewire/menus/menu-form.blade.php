<div class="max-w-2xl mx-auto px-4 sm:px-6 md:px-8 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900">{{ $isEdit ? 'Chỉnh sửa Menu' : 'Thêm Menu mới' }}</h1>
        <a href="{{ route('admin.menus.index') }}" class="text-sm text-gray-500 hover:text-gray-900 font-medium">Quay lại</a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="h-2 bg-indigo-600 w-full"></div>

        <form wire:submit="save" class="p-6 space-y-6">

            <div class="bg-indigo-50 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <span class="block text-sm font-bold text-indigo-900">Là Tiêu đề nhóm (Section)?</span>
                    <span class="text-xs text-indigo-600">Dùng để phân chia khu vực, không có link click.</span>
                </div>
                <div class="flex items-center">
                    <button type="button" wire:click="$toggle('is_section')" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_section ? 'bg-indigo-600' : 'bg-gray-300' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_section ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button> 
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Tên hiển thị <span class="text-red-500">*</span></label>
                <input type="text" wire:model="name" class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="Tên Menu">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Menu cha</label>
                <select wire:model="parent_id" class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="">-- Là mục gốc --</option>
                    @foreach($this->parents as $parent)
                        <option value="{{ $parent->getKey() }}">{{ $parent->view_name }}</option>
                    @endforeach
                </select>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div x-data="{ section: @entangle('is_section') }" x-show="!section" x-collapse class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Đường dẫn (URL)</label>
                    <div class="flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">{{ url('/') }}/</span>
                        <input type="text" wire:model="url" class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="admin/products">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Icon (Heroicons Outline)</label>
                    <div class="flex gap-3">
                        <input type="text" wire:model.live="icon" class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="home">
                        <div class="w-10 h-10 flex items-center justify-center bg-gray-100 rounded border border-gray-200 text-indigo-600">
                            @if($icon) <x-icon name="{{ $icon }}" class="w-6 h-6" /> @endif
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Yêu cầu quyền (Permission)</label>
                <select wire:model="can" class="block w-full rounded-md border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                    <option value="">-- Công khai --</option>
                    @foreach($permissions as $perm)
                        <option value="{{ $perm->name }}">{{ $perm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-6 flex items-center justify-between border-t border-gray-100">
                <div class="flex items-center">
                    <input id="active" type="checkbox" wire:model="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="active" class="ml-2 block text-sm text-gray-900 font-medium">Hiển thị menu này</label>
                </div>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow hover:bg-indigo-700 transition">
                    {{ $isEdit ? 'Lưu thay đổi' : 'Tạo mới' }}
                </button>
            </div>

        </form>
    </div>
</div>
