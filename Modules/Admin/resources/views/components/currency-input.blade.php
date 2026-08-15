@props([
    'label' => null,
    'placeholder' => '0',
    'suffix' => 'VNĐ',
    'icon' => '₫',
    'required' => false,
    'helper' => null,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $hasError = $model && $errors->has($model);

    $baseClass = 'block w-full rounded-xl border bg-white py-3 pl-9 pr-20 text-sm font-semibold text-gray-900 placeholder:text-gray-400 transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-100 disabled:text-gray-500 disabled:opacity-75';
    $stateClass = $hasError
        ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 hover:border-gray-400 focus:border-indigo-500 focus:ring-indigo-100';
    $inputClass = $baseClass.' '.$stateClass;
@endphp

<div class="{{ $attributes->get('class') }}"
    x-data="{
        value: @entangle($model),
        displayValue: '',
        format(val) {
            if (val === null || val === undefined || val === '') return '';
            return val.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        handleInput(e) {
            const raw = e.target.value.replace(/\./g, '');
            this.displayValue = this.format(raw);
            this.value = raw ? parseInt(raw) : null;
        },
        handleFocus(e) {
            e.target.select();
        },
        init() {
            if (this.value) this.displayValue = this.format(this.value);
            this.$watch('value', (newVal) => {
                if (newVal != this.displayValue.replace(/\./g, '')) {
                    this.displayValue = this.format(newVal);
                }
            });
        }
    }"
    x-init="init()"
>
    @if($label)
        <label class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-sm font-semibold {{ $hasError ? 'text-red-500' : 'text-gray-500' }}">{{ $icon }}</span>
        </div>

        <input
            type="text"
            inputmode="numeric"
            x-model="displayValue"
            @input="handleInput"
            @focus="handleFocus"
            {{ $attributes->whereDoesntStartWith('wire:model')->except('class')->merge(['class' => $inputClass, 'aria-invalid' => $hasError ? 'true' : 'false']) }}
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
        >

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $hasError ? 'bg-red-50 text-red-600' : 'bg-gray-100 text-gray-500' }}">{{ $suffix }}</span>
        </div>
    </div>

    @if($hasError)
        <p class="mt-1.5 text-sm text-red-600">{{ $errors->first($model) }}</p>
    @elseif($helper)
        <p class="mt-1.5 text-xs text-gray-500">{{ $helper }}</p>
    @endif
</div>
