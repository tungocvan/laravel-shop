{{-- Admission-only native searchable select: Livewire owns the value, no wire:ignore/TomSelect. --}}
@php
    $items = collect($items ?? []);
    $value = (string) ($form[$field] ?? '');
    $names = $items->pluck($labelKey)->map(fn ($name) => (string) $name)->all();
@endphp
<div x-data="{ search: '', filter() { const q = this.search.toLocaleLowerCase('vi').trim(); for (const option of this.$refs.options.options) { option.hidden = !!q && !option.text.toLocaleLowerCase('vi').includes(q) && option.value !== this.$refs.options.value; } } }" class="min-w-0 space-y-1">
    <input type="search" x-model="search" x-on:input="filter()" placeholder="Tìm {{ mb_strtolower($placeholder) }}..." aria-label="Tìm {{ $placeholder }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
    <select x-ref="options" wire:model.live="form.{{ $field }}" aria-label="{{ $placeholder }}" class="w-full min-h-11 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        <option value="">-- Chọn --</option>
        @foreach ($items as $item)
            <option value="{{ $item[$labelKey] }}" @selected($value === (string) $item[$labelKey])>{{ $item[$labelKey] }}</option>
        @endforeach
        @if ($value !== '' && !in_array($value, $names, true))
            <option value="{{ $value }}" selected>{{ $value }} (chưa có trong danh mục)</option>
        @endif
    </select>
</div>
