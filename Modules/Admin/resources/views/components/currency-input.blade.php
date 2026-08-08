@props([
    'label' => null,       // Nhãn (VD: Giá bán)
    'placeholder' => '0',
    'suffix' => 'VNĐ',     // Đơn vị tiền tệ
    'icon' => '₫',         // Icon tiền tệ bên trái
    'required' => false,   // Bắt buộc nhập?
    'helper' => null,      // Dòng chú thích nhỏ bên dưới
])

@php
    // 1. Tự động bắt tên biến wire:model
    // VD: wire:model="price" -> $model = 'price'
    $model = $attributes->whereStartsWith('wire:model')->first();

    // 2. Kiểm tra xem trường này có đang bị lỗi không
    $hasError = $model && $errors->has($model);

    // 3. Định nghĩa Style cho Input dựa trên trạng thái (Thường vs Lỗi)
    $baseClass = "block w-full rounded-lg border-0 py-2.5 pl-9 pr-16 sm:text-sm sm:leading-6 font-bold font-mono tracking-wide transition-all duration-200 shadow-sm focus:ring-2 focus:ring-inset";

    // Style mặc định (Xám/Indigo)
    $normalClass = "text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-indigo-600 bg-white";

    // Style khi LỖI (Đỏ/Đỏ đậm)
    $errorClass = "text-red-900 ring-1 ring-inset ring-red-300 placeholder:text-red-300 focus:ring-red-500 bg-red-50/30";

    $inputClass = $hasError ? $baseClass . ' ' . $errorClass : $baseClass . ' ' . $normalClass;
@endphp

<div class="{{ $attributes->get('class') }}"
    x-data="{
        value: @entangle($model),
        displayValue: '',

        // Logic format tiền: 100000 -> 100.000
        format(val) {
            if (val === null || val === undefined || val === '') return '';
            return val.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        // Logic khi gõ phím
        handleInput(e) {
            let raw = e.target.value.replace(/\./g, ''); // Bỏ dấu chấm
            this.displayValue = this.format(raw);        // Format lại hiển thị
            this.value = raw ? parseInt(raw) : null;     // Gán số nguyên cho Livewire
        },

        // Logic khi Focus (Bôi đen để sửa nhanh)
        handleFocus(e) {
            e.target.select();
        },

        init() {
            // Khởi tạo hiển thị lần đầu
            if (this.value) this.displayValue = this.format(this.value);

            // Lắng nghe thay đổi từ Server (VD: Edit form)
            this.$watch('value', (newVal) => {
                // Chỉ update hiển thị nếu giá trị mới khác với giá trị đang hiển thị (đã bỏ format)
                // để tránh conflict khi đang gõ
                if (newVal != this.displayValue.replace(/\./g, '')) {
                     this.displayValue = this.format(newVal);
                }
            });
        }
    }"
    x-init="init()"
>
    @if($label)
        <label class="block text-sm font-bold leading-6 text-gray-900 mb-1.5">
            {{ $label }}
            @if($required) <span class="text-red-500 select-none">*</span> @endif
        </label>
    @endif

    <div class="relative rounded-lg shadow-sm">

        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="{{ $hasError ? 'text-red-500' : 'text-gray-500' }} font-bold sm:text-sm transition-colors duration-200">
                {{ $icon }}
            </span>
        </div>

        <input
            type="text"
            inputmode="numeric"
            x-model="displayValue"
            @input="handleInput"
            @focus="handleFocus"
            {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => $inputClass]) }}
            placeholder="{{ $placeholder }}"
        >

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">

            @if($hasError)
                <svg class="h-5 w-5 text-red-500 mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            @endif

            <span class="{{ $hasError ? 'text-red-600 bg-red-100/50' : 'text-gray-500 bg-gray-100' }} sm:text-xs font-bold px-2 py-1 rounded transition-colors duration-200">
                {{ $suffix }}
            </span>
        </div>
    </div>

    @error($model)
        <p class="mt-1.5 text-xs text-red-600 font-semibold flex items-center animate-pulse">
            {{ $message }}
        </p>
    @enderror

    @if($helper && !$hasError)
        <p class="mt-1.5 text-xs text-gray-500">{{ $helper }}</p>
    @endif
</div>
