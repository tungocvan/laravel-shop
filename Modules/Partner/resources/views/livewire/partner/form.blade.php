<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                {{ $isEdit ? 'Cập nhật đối tác' : 'Thêm đối tác' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Nhập thông tin đối tác, nhà cung cấp, khách hàng hoặc hộ kinh doanh.
            </p>
        </div>

        <a href="{{ route('admin.partner.partners.index') }}"
            class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            Quay lại
        </a>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">

        {{-- Basic Info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Thông tin chính</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin định danh và phân loại đối tác.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Tên đối tác <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        wire:model.live="name"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Mã số thuế</label>
                    <input type="text"
                        wire:model.live="tax_code"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('tax_code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Loại pháp lý <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="legal_type"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        @foreach ($legalTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('legal_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Nguồn dữ liệu <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="source"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        @foreach ($sources as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('source')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">
                        Vai trò đối tác <span class="text-red-500">*</span>
                    </label>

                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($partnerTypes as $value => $label)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm hover:bg-gray-50">
                                <input type="checkbox"
                                    wire:model.live="partner_types"
                                    value="{{ $value }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="font-medium text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    @error('partner_types')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('partner_types.*')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Thông tin liên hệ</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin người liên hệ, email, số điện thoại và địa chỉ.</p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-gray-700">Người liên hệ</label>
                    <input type="text"
                        wire:model.live="contact_person"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('contact_person')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Số điện thoại</label>
                    <input type="text"
                        wire:model.live="phone"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">Email</label>
                    <input type="email"
                        wire:model.live="email"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="status"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Địa chỉ</label>
                    <textarea wire:model.live="address"
                        rows="3"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"></textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">Ghi chú nội bộ</h2>
                <p class="mt-1 text-sm text-gray-500">Thông tin bổ sung phục vụ tra cứu nội bộ.</p>
            </div>

            <div class="p-5">
                <label class="text-sm font-medium text-gray-700">Ghi chú</label>
                <textarea wire:model.live="note"
                    rows="4"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"></textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.partner.partners.index') }}"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                Hủy
            </a>

            <button type="submit"
                wire:loading.attr="disabled"
                class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70">
                <span wire:loading.remove wire:target="save">
                    {{ $isEdit ? 'Cập nhật' : 'Lưu đối tác' }}
                </span>
                <span wire:loading wire:target="save">
                    Đang lưu...
                </span>
            </button>
        </div>
    </form>
</div>
