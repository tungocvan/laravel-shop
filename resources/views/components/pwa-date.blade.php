@props([
    'name',
    'label' => null,
    'value' => null,
    'min' => null,
    'max' => null,
    'required' => false,
    'disabled' => false,
    'help' => null,
    'error' => null,
    'wrapperClass' => '',
])

<label class="block w-full min-w-0 max-w-full overflow-hidden text-xs font-bold uppercase tracking-wide text-slate-500 {{ $wrapperClass }}">
    @if($label)
        <span>{{ $label }}</span>
    @endif

    <input
        type="date"
        name="{{ $name }}"
        value="{{ $value }}"
        @if($min) min="{{ $min }}" @endif
        @if($max) max="{{ $max }}" @endif
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge([
            'class' => 'mt-1.5 block h-10 min-w-0 w-full max-w-full box-border appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium normal-case tracking-normal text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
        ]) }}
    >

    @if($error)
        <span class="mt-1.5 block text-xs font-semibold normal-case tracking-normal text-red-600">{{ $error }}</span>
    @elseif($help)
        <span class="mt-1.5 block text-xs font-medium normal-case tracking-normal text-slate-500">{{ $help }}</span>
    @endif
</label>
