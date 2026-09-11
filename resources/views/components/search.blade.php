@props([
    'placeholder' => 'Tìm kiếm...',
    'inputClass' => '',
])

@php
    $listId = $attributes->get('list');
@endphp

<div
    {{ $attributes->only('class')->merge(['class' => 'relative']) }}
    @if($listId) data-search-autocomplete data-source-list="{{ $listId }}" @endif
>
    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 flex items-center pl-3 text-gray-400">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M9 3a6 6 0 104.472 10.004l3.762 3.762a1 1 0 001.414-1.414l-3.762-3.762A6 6 0 009 3zm-4 6a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
        </svg>
    </div>

    <input
        type="search"
        placeholder="{{ $placeholder }}"
        @if($listId) data-search-autocomplete-input aria-autocomplete="list" aria-expanded="false" @endif
        {{ $attributes->except(['class', 'inputClass', 'list'])->merge([
            'class' => trim('block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 '.$inputClass),
        ]) }}
    >

    @if($listId)
        <div
            data-search-autocomplete-menu
            class="absolute inset-x-0 top-full z-[10000] mt-2 hidden max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl ring-1 ring-slate-950/5"
            role="listbox"
        ></div>
    @endif
</div>

@once
    <style>
        [data-inventory-item-search-shell] {
            position: relative;
            z-index: 1;
            overflow: visible;
        }

        [data-inventory-item-search-shell]:focus-within {
            z-index: 9999;
        }

        [data-inventory-item-search-shell] [data-inventory-item-search-results] {
            z-index: 10000 !important;
        }

        article:has([data-inventory-item-search-shell]:focus-within) {
            position: relative;
            z-index: 9998;
            overflow: visible;
        }
    </style>
@endonce

@if($listId)
    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const normalize = (value) => (value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLocaleLowerCase('vi');

                document.querySelectorAll('[data-search-autocomplete]').forEach((root) => {
                    const input = root.querySelector('[data-search-autocomplete-input]');
                    const menu = root.querySelector('[data-search-autocomplete-menu]');
                    const source = document.getElementById(root.dataset.sourceList);

                    if (!input || !menu || !source) return;

                    const options = Array.from(source.querySelectorAll('option'))
                        .map((option) => ({
                            value: option.value,
                            label: option.label || option.textContent || option.value,
                        }))
                        .filter((option) => option.value);

                    const close = () => {
                        menu.classList.add('hidden');
                        input.setAttribute('aria-expanded', 'false');
                    };

                    const choose = (value) => {
                        input.value = value;
                        close();
                        input.closest('form')?.requestSubmit();
                    };

                    const render = () => {
                        const term = normalize(input.value.trim());
                        const matches = options
                            .filter((option) => !term || normalize(`${option.value} ${option.label}`).includes(term))
                            .slice(0, 8);

                        menu.replaceChildren();

                        if (matches.length === 0) {
                            close();
                            return;
                        }

                        matches.forEach((option) => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'block min-h-11 w-full rounded-xl px-3 py-2.5 text-left transition hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';
                            button.setAttribute('role', 'option');

                            const title = document.createElement('span');
                            title.className = 'block truncate text-sm font-bold text-slate-900';
                            title.textContent = option.value;
                            button.appendChild(title);

                            if (option.label && option.label !== option.value) {
                                const meta = document.createElement('span');
                                meta.className = 'mt-0.5 block truncate text-xs font-medium text-slate-500';
                                meta.textContent = option.label;
                                button.appendChild(meta);
                            }

                            button.addEventListener('pointerdown', (event) => event.preventDefault());
                            button.addEventListener('click', () => choose(option.value));
                            menu.appendChild(button);
                        });

                        menu.classList.remove('hidden');
                        input.setAttribute('aria-expanded', 'true');
                    };

                    input.addEventListener('focus', render);
                    input.addEventListener('input', render);
                    input.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') close();
                    });
                    input.addEventListener('blur', () => window.setTimeout(close, 120));
                });
            });
        </script>
    @endonce
@endif
