@props(['name' => 'search', 'value' => '', 'placeholder' => 'Tìm kiếm...', 'label' => 'Tìm kiếm'])
<label class="block min-w-0 text-xs font-semibold text-slate-700">
    <span>{{ $label }}</span>
    <span class="relative mt-1 block">
        <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m16 16 5 5" stroke-width="2"/></svg>
        <input type="search" name="{{ $name }}" value="{{ $value }}" maxlength="255" placeholder="{{ $placeholder }}"
            {{ $attributes->class(['min-h-11 w-full rounded-xl border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100']) }}>
    </span>
</label>
