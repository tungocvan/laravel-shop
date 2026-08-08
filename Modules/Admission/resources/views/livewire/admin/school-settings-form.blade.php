<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Thông tin nhà trường</h1>
        <p class="mt-1 text-sm text-gray-600">Các thông tin này được sử dụng trên trang đăng nhập, biểu mẫu và biên nhận.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="max-w-3xl rounded-lg bg-white p-6 shadow">
        @php
            $fields = [
                'principal' => ['Hiệu trưởng', 'Hoàng Thụy Bích Thủy'],
                'school_year' => ['Năm học', '2026-2027'],
                'school_name' => ['Tên trường', 'TRƯỜNG TIỂU HỌC NGUYỄN VĂN HƯỞNG'],
                'school_managing_agency' => ['Cơ quan quản lý', 'ỦY BAN NHÂN DÂN PHƯỜNG PHÚ THUẬN'],
                'school_login_description' => ['Mô tả trang đăng nhập', 'Hệ thống quản trị & đăng nhập giáo viên / quản lý'],
            ];
        @endphp

        <div class="space-y-5">
            @foreach ($fields as $name => [$label, $placeholder])
                <div>
                    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>
                    <input
                        id="{{ $name }}"
                        type="text"
                        wire:model="{{ $name }}"
                        placeholder="{{ $placeholder }}"
                        class="block w-full rounded-md border px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 @error($name) border-red-500 @else border-gray-300 @enderror"
                    >
                    @error($name)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach

            <div class="border-t border-gray-200 pt-5">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-900">Hình ảnh Website</h2>
                    <p class="mt-1 text-sm text-gray-500">Đồng bộ với phần Hình ảnh trong System Settings.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <label class="block text-sm font-medium text-gray-900">Logo Website</label>

                        <div class="mt-3 flex items-center gap-5">
                            <div class="relative flex h-24 w-28 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 p-2">
                                @if ($new_logo)
                                    <img src="{{ $new_logo->temporaryUrl() }}" class="max-h-full max-w-full object-contain" alt="Logo mới">
                                    <span class="absolute -right-2 -top-2 rounded-full bg-green-500 px-2 py-0.5 text-xs text-white">Mới</span>
                                @elseif ($site_logo)
                                    <img src="{{ asset('storage/'.$site_logo) }}" class="max-h-full max-w-full object-contain" alt="Logo hiện tại">
                                @else
                                    <span class="text-xs text-gray-400">Chưa có logo</span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="inline-flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Chọn logo
                                    <input type="file" wire:model="new_logo" class="hidden" accept="image/png,image/jpeg,image/webp">
                                </label>
                                <div wire:loading wire:target="new_logo" class="text-xs text-indigo-600">Đang tải ảnh...</div>
                                @error('new_logo')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                @if ($site_logo || $new_logo)
                                    <button type="button" wire:click="removeImage('logo')" wire:confirm="Bạn muốn xóa logo hiện tại?" class="block text-xs text-red-600 hover:underline">Xóa logo</button>
                                @endif
                                <p class="text-xs text-gray-500">PNG, JPG hoặc WEBP. Tối đa 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <label class="block text-sm font-medium text-gray-900">Favicon (icon tab trình duyệt)</label>

                        <div class="mt-3 flex items-center gap-5">
                            <div class="flex h-16 w-16 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-1">
                                @if ($new_favicon)
                                    @if (strtolower($new_favicon->getClientOriginalExtension()) === 'ico')
                                        <span class="text-xs font-semibold text-indigo-600">ICO mới</span>
                                    @else
                                        <img src="{{ $new_favicon->temporaryUrl() }}" class="max-h-full max-w-full object-contain" alt="Favicon mới">
                                    @endif
                                @elseif ($site_favicon)
                                    <img src="{{ asset('storage/'.$site_favicon) }}" class="max-h-full max-w-full object-contain" alt="Favicon hiện tại">
                                @else
                                    <span class="text-center text-xs text-gray-400">Chưa có</span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <label class="inline-flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Chọn icon
                                    <input type="file" wire:model="new_favicon" class="hidden" accept=".png,.ico,image/png,image/x-icon,image/vnd.microsoft.icon">
                                </label>
                                <div wire:loading wire:target="new_favicon" class="text-xs text-indigo-600">Đang tải icon...</div>
                                @error('new_favicon')<p class="text-xs font-medium text-red-600">{{ $message }}</p>@enderror
                                @if ($site_favicon || $new_favicon)
                                    <button type="button" wire:click="removeImage('favicon')" wire:confirm="Bạn muốn xóa favicon hiện tại?" class="block text-xs text-red-600 hover:underline">Xóa favicon</button>
                                @endif
                                <p class="text-xs text-gray-500">PNG hoặc ICO, kích thước vuông. Tối đa 1MB.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-5">
                <div class="mb-3">
                    <h2 class="text-base font-semibold text-gray-900">Lớp đăng ký</h2>
                    <p class="mt-1 text-sm text-gray-500">Danh sách này được hiển thị ở bước xác nhận hồ sơ tuyển sinh.</p>
                </div>

                <div class="space-y-3">
                    @foreach ($registration_classes as $index => $class)
                        <div wire:key="registration-class-{{ $index }}" class="flex items-start gap-2">
                            <div class="flex-1">
                                <input
                                    type="text"
                                    wire:model="registration_classes.{{ $index }}"
                                    aria-label="Tên lớp đăng ký {{ $index + 1 }}"
                                    class="block w-full rounded-md border px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 @error('registration_classes.'.$index) border-red-500 @else border-gray-300 @enderror"
                                >
                                @error('registration_classes.'.$index)
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button
                                type="button"
                                wire:click="removeRegistrationClass({{ $index }})"
                                wire:confirm="Xóa lớp đăng ký này khỏi danh sách lựa chọn?"
                                class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                            >
                                Xóa
                            </button>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <button
                        type="button"
                        wire:click="addRegistrationClass"
                        class="rounded-md bg-slate-700 px-4 py-2 font-medium text-white hover:bg-slate-800"
                    >
                        + Thêm một lớp đăng ký
                    </button>
                    <p class="mt-2 text-xs text-gray-500">Có thể bấm nhiều lần để thêm nhiều dòng, sau đó nhập tên và nhấn “Lưu thay đổi”.</p>
                    @error('registration_classes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Lưu thay đổi</span>
                <span wire:loading wire:target="save">Đang lưu...</span>
            </button>
            <a href="{{ route('admin.admission.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Quay lại</a>
        </div>
    </form>
</div>
