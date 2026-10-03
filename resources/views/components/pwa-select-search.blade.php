@props([
    'id',
    'name',
    'placeholder' => 'Chọn một mục...',
    'searchPlaceholder' => 'Tìm kiếm...',
    'selected' => '',
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
        class="flex h-[46px] w-full min-w-0 items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3 text-left text-sm font-semibold text-slate-950 shadow-sm transition focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100"
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
    const submitPwaSelectForm = (form) => {
        if (!form) return;
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.submit();
    };

    const bind = (root = document) => {
        root.querySelectorAll('[data-pwa-select-search]').forEach((select) => {
            if (select.dataset.pwaSelectSearchBound) return;

            const trigger = select.querySelector('[data-pwa-select-search-trigger]');
            const panel = select.querySelector('[data-pwa-select-search-panel]');
            const search = select.querySelector('[data-pwa-select-search-input]');
            const clear = select.querySelector('[data-pwa-select-search-clear]');
            const value = select.querySelector('[data-pwa-select-search-value]');
            const label = select.querySelector('[data-pwa-select-search-label]');
            const empty = select.querySelector('[data-pwa-select-search-empty]');
            const options = Array.from(select.querySelectorAll('[data-pwa-select-search-option]'));
            if (!trigger || !panel || !search || !value || !label) return;

            select.dataset.pwaSelectSearchBound = '1';

            const close = () => {
                panel.classList.add('hidden');
                trigger.setAttribute('aria-expanded', 'false');
            };
            const open = () => {
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                search.value = '';
                options.forEach((option) => option.classList.remove('hidden'));
                if (empty) empty.classList.add('hidden');
                if (clear) {
                    clear.classList.add('hidden');
                    clear.classList.remove('flex');
                }
                window.setTimeout(() => search.focus({preventScroll: true}), 0);
            };
            const selected = options.find((option) => String(option.dataset.value || '') === String(value.value || ''));
            label.textContent = selected?.dataset.label || select.dataset.pwaSelectSearchPlaceholder || '';

            trigger.addEventListener('click', () => panel.classList.contains('hidden') ? open() : close());
            search.addEventListener('input', () => {
                const query = search.value.trim().toLocaleLowerCase('vi');
                let visible = 0;
                options.forEach((option) => {
                    const matches = !query || (option.dataset.search || option.textContent || '').toLocaleLowerCase('vi').includes(query);
                    option.classList.toggle('hidden', !matches);
                    if (matches) visible += 1;
                });
                if (empty) empty.classList.toggle('hidden', visible !== 0);
                if (clear) {
                    clear.classList.toggle('hidden', search.value === '');
                    clear.classList.toggle('flex', search.value !== '');
                }
            });
            clear?.addEventListener('click', () => {
                search.value = '';
                search.dispatchEvent(new Event('input', {bubbles: true}));
                search.focus({preventScroll: true});
            });
            options.forEach((option) => option.addEventListener('click', () => {
                value.value = option.dataset.value || '';
                label.textContent = option.dataset.label || option.textContent.trim();
                close();
                if (select.dataset.pwaSelectSearchSubmit === 'change') submitPwaSelectForm(value.form);
            }));
            document.addEventListener('click', (event) => {
                if (!select.contains(event.target)) close();
            });
        });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => bind(document), {once: true});
    else bind(document);
})();
</script>
@endpush
@endonce
