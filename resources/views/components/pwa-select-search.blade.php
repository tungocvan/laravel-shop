@props([
    'id',
    'name',
    'placeholder' => 'Chọn một mục...',
    'searchPlaceholder' => 'Tìm kiếm...',
    'selected' => '',
    'disabled' => false,
])

<div
    {{ $attributes->merge(['class' => 'relative min-w-0']) }}
    data-pwa-select-search
    data-pwa-select-search-placeholder="{{ $placeholder }}"
>
    <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $selected }}" data-pwa-select-search-value>

    <button
        type="button"
        data-pwa-select-search-trigger
        aria-haspopup="listbox"
        aria-expanded="false"
        @disabled($disabled)
        class="flex h-[46px] w-full min-w-0 items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3 text-left text-sm font-semibold text-slate-950 shadow-sm transition focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
    >
        <span data-pwa-select-search-label class="min-w-0 flex-1 truncate">{{ $placeholder }}</span>
        <span aria-hidden="true" class="shrink-0 text-slate-400">⌄</span>
    </button>

    <div data-pwa-select-search-panel class="absolute left-0 right-0 z-[100] mt-2 hidden min-w-0 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
        <div class="relative">
            <input
                type="search"
                data-pwa-select-search-input
                autocomplete="off"
                placeholder="{{ $searchPlaceholder }}"
                class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 pr-10 text-sm text-slate-950 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
            >
            <button type="button" data-pwa-select-search-clear aria-label="Xóa tìm kiếm" class="absolute inset-y-0 right-1 hidden w-9 items-center justify-center rounded-lg text-lg font-bold text-slate-400">×</button>
        </div>
        <div data-pwa-select-search-options role="listbox" class="mt-2 max-h-64 overflow-y-auto overscroll-contain">
            {{ $slot }}
        </div>
        <p data-pwa-select-search-empty class="hidden px-3 py-4 text-center text-sm font-semibold text-slate-400">Không tìm thấy kết quả</p>
    </div>
</div>


@once
@push('application-scripts')
<script>
(() => {
    const bind = () => window.ClientPortalNativeInteractions?.bindSelectSearch?.(document);
    if (window.ClientPortalNativeInteractions?.bindSelectSearch) {
        bind();
        return;
    }
    document.addEventListener('clientportal:native-interactions-ready', bind, {once: true});
})();
</script>
@endpush
@endonce
