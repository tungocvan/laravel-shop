<div class="max-w-7xl mx-auto pb-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quản lý Banners</h1>
        <button wire:click="create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm Banner
        </button>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hình ảnh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiêu đề / Link</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vị trí</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thứ tự</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($banners as $banner)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="{{ asset('storage/'.$banner->image_desktop) }}" class="h-12 w-20 object-cover rounded border">
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $banner->title }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $banner->link ?? '#' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $banner->position }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ $banner->order }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $banner->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $banner->is_active ? 'Hiển thị' : 'Ẩn' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button wire:click="edit({{ $banner->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Sửa</button>
                        <button wire:confirm="Bạn có chắc muốn xóa banner này?" wire:click="delete({{ $banner->id }})" class="text-red-600 hover:text-red-900">Xóa</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Chưa có banner nào.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('isModalOpen', false)"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form wire:submit="save">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6">
                        <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">{{ $isEditMode ? 'Cập nhật Banner' : 'Thêm mới Banner' }}</h3>

                        <div class="space-y-5">

                            {{-- 1. ẢNH DESKTOP & MOBILE (Quan trọng cho Frontend Responsive) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Ảnh Desktop --}}
                                <div class="bg-gray-50 p-3 rounded border border-dashed border-gray-300">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Ảnh Desktop (*)</label>
                                    <p class="text-xs text-gray-500 mb-2">Kích thước chuẩn: <b>1920x600px</b> hoặc tỷ lệ <b>21:9</b>.</p>

                                    <input type="file" wire:model="newImageDesktop" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500">

                                    @if($newImageDesktop)
                                        <img src="{{ $newImageDesktop->temporaryUrl() }}" class="mt-2 w-full h-auto rounded border shadow-sm">
                                    @elseif($currentImageDesktop)
                                        <img src="{{ asset('storage/'.$currentImageDesktop) }}" class="mt-2 w-full h-auto rounded border shadow-sm">
                                    @endif
                                    @error('newImageDesktop') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Ảnh Mobile --}}
                                <div class="bg-gray-50 p-3 rounded border border-dashed border-gray-300">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Ảnh Mobile (Tùy chọn)</label>
                                    <p class="text-xs text-gray-500 mb-2">Nên dùng ảnh dọc. Kích thước chuẩn: <b>800x1000px</b> hoặc tỷ lệ <b>4:5</b>.</p>

                                    <input type="file" wire:model="newImageMobile" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-pink-600 file:text-white hover:file:bg-pink-500">

                                    @if($newImageMobile)
                                        <img src="{{ $newImageMobile->temporaryUrl() }}" class="mt-2 w-1/2 mx-auto h-auto rounded border shadow-sm">
                                    @elseif($currentImageMobile)
                                        <img src="{{ asset('storage/'.$currentImageMobile) }}" class="mt-2 w-1/2 mx-auto h-auto rounded border shadow-sm">
                                    @else
                                         <div class="mt-4 text-center text-xs text-gray-400 italic">Chưa có ảnh mobile (Sẽ dùng ảnh Desktop)</div>
                                    @endif
                                </div>
                            </div>

                            {{-- 2. THÔNG TIN CHỮ (TEXT CONTENT) --}}
                            <div class="space-y-3 pt-2 border-t">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tiêu đề chính (Title)</label>
                                    <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    @error('title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mô tả phụ (Sub Title)</label>
                                    <input type="text" wire:model="sub_title" placeholder="VD: BỘ SƯU TẬP MÙA HÈ" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Link liên kết</label>
                                        <input type="text" wire:model="link" placeholder="/khuyen-mai" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nút bấm (Button Text)</label>
                                        <input type="text" wire:model="btn_text" placeholder="Mua ngay" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                    </div>
                                </div>
                            </div>

                            {{-- 3. CẤU HÌNH --}}
                            <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Vị trí</label>
                                    <select wire:model="position" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                                        <option value="hero">Hero Slider (Đầu trang)</option>
                                        <option value="promo_1">Promo Banner 1</option>
                                        <option value="footer">Footer Banner</option>
                                    </select>
                                </div>
                                <div class="flex flex-col justify-end pb-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model="is_active" id="active" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        <label for="active" class="ml-2 block text-sm text-gray-900 font-bold">Hiển thị ngay</label>
                                    </div>
                                    <div class="flex items-center mt-2">
                                        <label class="text-sm text-gray-700 mr-2">Thứ tự:</label>
                                        <input type="number" wire:model="order" class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-1 text-center">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" wire:loading.attr="disabled" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">
                            <span wire:loading.remove>Lưu lại</span>
                            <span wire:loading>Đang lưu...</span>
                        </button>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    </div>
