const debounceTimers = new WeakMap();
const searchControllers = new WeakMap();

const submitForm = (form) => {
    if (!form) return;

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
    }

    form.submit();
};

const showNavigationFeedback = (element) => {
    const selector = element?.dataset?.pwaNavigationFeedback;
    if (!selector) return;

    const feedback = document.querySelector(selector);
    if (!feedback) return;

    feedback.classList.remove('hidden');
    feedback.classList.add('flex');
};

const bindNavigationFeedback = (root = document) => {
    root.querySelectorAll('[data-pwa-navigation-feedback]').forEach((element) => {
        if (element.dataset.pwaNavigationBound) return;
        element.dataset.pwaNavigationBound = '1';
        element.addEventListener('click', () => showNavigationFeedback(element));
    });
};

const bindPendingForms = (root = document) => {
    root.querySelectorAll('form[data-pwa-pending-feedback]').forEach((form) => {
        if (form.dataset.pwaPendingBound) return;
        form.dataset.pwaPendingBound = '1';
        form.addEventListener('submit', () => {
            const selector = form.dataset.pwaPendingFeedback;
            if (!selector) return;
            const feedback = document.querySelector(selector);
            if (!feedback) return;
            feedback.classList.remove('hidden');
            feedback.classList.add('flex');
        });
    });
};

const syncSearchClear = (input) => {
    const selector = input?.dataset?.pwaSearchClear;
    if (!selector) return;

    const clear = document.querySelector(selector);
    if (!clear) return;

    clear.classList.toggle('hidden', input.value === '');
};

const replaceSearchRegion = async (input) => {
    const form = input.form;
    const regionSelector = input.dataset.pwaSearchRegion;
    const region = regionSelector ? document.querySelector(regionSelector) : null;

    if (!form || !region || !window.fetch || !window.DOMParser) {
        submitForm(form);
        return;
    }

    searchControllers.get(input)?.abort();
    const controller = new AbortController();
    searchControllers.set(input, controller);

    const url = new URL(form.action, window.location.href);
    new FormData(form).forEach((value, key) => url.searchParams.set(key, value));
    url.searchParams.delete('page');

    input.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(url.toString(), {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            signal: controller.signal,
        });
        if (!response.ok) throw new Error('pwa-search');

        const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextRegion = doc.querySelector(regionSelector);
        if (!nextRegion) throw new Error('pwa-search-region');

        region.replaceWith(nextRegion);
        window.history.replaceState({}, '', url.toString());
        bindNativeInteractions(document);
        nextRegion.querySelector('[data-pwa-debounced-search]')?.focus({preventScroll: true});
    } catch (error) {
        if (error.name !== 'AbortError') submitForm(form);
    } finally {
        input.removeAttribute('aria-busy');
    }
};

const bindSearchClear = (root = document) => {
    root.querySelectorAll('[data-pwa-search-clear-button]').forEach((button) => {
        if (button.dataset.pwaSearchClearBound) return;
        const input = document.querySelector(button.dataset.pwaSearchClearButton);
        if (!input) return;

        button.dataset.pwaSearchClearBound = '1';
        syncSearchClear(input);
        input.addEventListener('input', () => syncSearchClear(input));
        button.addEventListener('click', () => {
            window.clearTimeout(debounceTimers.get(input));
            input.value = '';
            syncSearchClear(input);
            replaceSearchRegion(input);
        });
    });
};

const bindDebouncedSearch = (root = document) => {
    root.querySelectorAll('[data-pwa-debounced-search]').forEach((input) => {
        if (input.dataset.pwaSearchBound) return;
        input.dataset.pwaSearchBound = '1';
        syncSearchClear(input);

        input.addEventListener('input', () => {
            syncSearchClear(input);
            window.clearTimeout(debounceTimers.get(input));
            const delay = Number.parseInt(input.dataset.pwaDebouncedSearch || '600', 10);
            const timer = window.setTimeout(() => replaceSearchRegion(input), Number.isFinite(delay) ? delay : 800);
            debounceTimers.set(input, timer);
        });
    });
};

const bindLoadMore = (root = document) => {
    root.querySelectorAll('[data-pwa-load-more]').forEach((button) => {
        if (button.dataset.pwaLoadMoreBound) return;

        const listSelector = button.dataset.pwaLoadMoreTarget;
        const itemSelector = button.dataset.pwaLoadMoreItems;
        const wrapSelector = button.dataset.pwaLoadMoreWrap;
        const list = listSelector ? document.querySelector(listSelector) : null;
        if (!list || !itemSelector || !wrapSelector) return;

        button.dataset.pwaLoadMoreBound = '1';
        button.addEventListener('click', async (event) => {
            if (!window.fetch || !window.DOMParser || button.getAttribute('aria-busy') === 'true') return;

            event.preventDefault();
            const original = button.textContent;
            button.textContent = button.dataset.pwaPendingLabel || 'Đang tải…';
            button.setAttribute('aria-busy', 'true');
            button.classList.add('pointer-events-none', 'opacity-70');

            try {
                const response = await fetch(button.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok) throw new Error('pwa-load-more');

                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                doc.querySelectorAll(itemSelector).forEach((item) => list.appendChild(item));

                document.querySelector(wrapSelector)?.remove();
                const nextWrap = doc.querySelector(wrapSelector);
                if (nextWrap) {
                    const insertionAnchor = list.closest('table') || list;
                    insertionAnchor.insertAdjacentElement('afterend', nextWrap);
                }

                bindNativeInteractions(list);
                bindNativeInteractions(document);
            } catch (error) {
                window.location.assign(button.href);
            } finally {
                button.textContent = original;
                button.removeAttribute('aria-busy');
                button.classList.remove('pointer-events-none', 'opacity-70');
            }
        });
    });
};


const bindLocalFilters = (root = document) => {
    root.querySelectorAll('[data-pwa-local-filter]').forEach((input) => {
        if (input.dataset.pwaLocalFilterBound) return;

        const itemSelector = input.dataset.pwaLocalFilterItems;
        const clearSelector = input.dataset.pwaLocalFilterClear;
        const emptySelector = input.dataset.pwaLocalFilterEmpty;
        if (!itemSelector) return;

        input.dataset.pwaLocalFilterBound = '1';
        const clear = clearSelector ? document.querySelector(clearSelector) : null;
        const empty = emptySelector ? document.querySelector(emptySelector) : null;

        const apply = () => {
            const query = (input.value || '').trim().toLocaleLowerCase('vi');
            let visible = 0;
            document.querySelectorAll(itemSelector).forEach((item) => {
                const haystack = (item.dataset.search || item.dataset.name || item.textContent || '').toLocaleLowerCase('vi');
                const matches = !query || haystack.includes(query);
                item.classList.toggle('hidden', !matches);
                if (matches) visible += 1;
            });
            clear?.classList.toggle('hidden', input.value === '');
            empty?.classList.toggle('hidden', visible !== 0);
        };

        input.addEventListener('input', apply);
        clear?.addEventListener('click', () => {
            input.value = '';
            apply();
            input.focus({preventScroll: true});
        });
        apply();
    });
};

const bindPwaSelectSearch = (root = document) => {
    root.querySelectorAll('[data-pwa-select-search]').forEach((select) => {
        if (select.dataset.pwaSelectSearchBound) return;

        const trigger = select.querySelector('[data-pwa-select-search-trigger]');
        const panel = select.querySelector('[data-pwa-select-search-panel]');
        const search = select.querySelector('[data-pwa-select-search-input]');
        const clear = select.querySelector('[data-pwa-select-search-clear]');
        const value = select.querySelector('[data-pwa-select-search-value]');
        const label = select.querySelector('[data-pwa-select-search-label]');
        const empty = select.querySelector('[data-pwa-select-search-empty]');
        const options = [...select.querySelectorAll('[data-pwa-select-search-option]')];
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
            empty?.classList.add('hidden');
            clear?.classList.add('hidden');
            window.setTimeout(() => search.focus({preventScroll: true}), 0);
        };

        const syncLabel = () => {
            const selected = options.find((option) => String(option.dataset.value || '') === String(value.value || ''));
            label.textContent = selected?.dataset.label || select.dataset.pwaSelectSearchPlaceholder || '';
        };
        syncLabel();

        trigger.addEventListener('click', () => panel.classList.contains('hidden') ? open() : close());

        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase('vi');
            let visible = 0;
            options.forEach((option) => {
                const matches = !query || (option.dataset.search || option.textContent || '').toLocaleLowerCase('vi').includes(query);
                option.classList.toggle('hidden', !matches);
                if (matches) visible += 1;
            });
            empty?.classList.toggle('hidden', visible !== 0);
            clear?.classList.toggle('hidden', search.value === '');
            clear?.classList.toggle('flex', search.value !== '');
        });

        clear?.addEventListener('click', () => {
            search.value = '';
            search.dispatchEvent(new Event('input', {bubbles: true}));
            search.focus({preventScroll: true});
        });

        options.forEach((option) => option.addEventListener('click', () => {
            value.value = option.dataset.value || '';
            label.textContent = option.dataset.label || option.textContent.trim();
            value.dispatchEvent(new Event('change', {bubbles: true}));
            close();
            if (select.dataset.pwaSelectSearchSubmit === 'change') submitForm(value.form);
        }));

        document.addEventListener('click', (event) => {
            if (!select.contains(event.target)) close();
        });
    });
};


const bindCommissionWorkspace = (root = document) => {
    const workspace = root.matches?.('[data-commission-workspace]') ? root : root.querySelector?.('[data-commission-workspace]');
    if (!workspace || workspace.dataset.pwaCommissionBound) return;

    workspace.dataset.pwaCommissionBound = '1';
    const form = document.getElementById('commission-export-form');
    const desktopSelectAll = document.querySelector('[data-commission-select-all-desktop]');
    const inputs = document.getElementById('commission-selected-inputs');
    const rowCheckboxes = () => [...document.querySelectorAll('.commission-row-checkbox')];
    const selectedIds = () => [...new Set(rowCheckboxes().filter((box) => box.checked).map((box) => box.value))];

    const syncSelection = () => {
        if (!desktopSelectAll) return;
        const boxes = rowCheckboxes();
        const ids = selectedIds();
        desktopSelectAll.checked = boxes.length > 0 && boxes.every((box) => box.checked);
        desktopSelectAll.indeterminate = ids.length > 0 && !desktopSelectAll.checked;
    };

    desktopSelectAll?.addEventListener('change', () => {
        rowCheckboxes().forEach((box) => {
            box.checked = desktopSelectAll.checked;
        });
        syncSelection();
    });
    document.addEventListener('change', (event) => {
        if (event.target?.classList?.contains('commission-row-checkbox')) syncSelection();
    });

    form?.addEventListener('submit', () => {
        if (!inputs) return;
        inputs.innerHTML = '';
        selectedIds().forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            inputs.appendChild(input);
        });
    });

    const exportToggle = document.querySelector('[data-commission-export-toggle]');
    exportToggle?.addEventListener('click', () => {
        const content = document.querySelector('[data-commission-export-content]');
        const chevron = document.querySelector('[data-commission-export-chevron]');
        const expanded = exportToggle.getAttribute('aria-expanded') === 'true';
        exportToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        content?.classList.toggle('hidden', expanded);
        chevron?.classList.toggle('rotate-180', !expanded);
    });

    document.querySelectorAll('[data-commission-share-url]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const response = await fetch(button.dataset.commissionShareUrl, {credentials: 'same-origin', cache: 'no-store'});
                if (!response.ok) throw new Error('download');

                const blob = await response.blob();
                const file = new File([blob], button.dataset.commissionShareName, {
                    type: blob.type || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                });
                const payload = {files: [file]};
                if (typeof navigator.share === 'function'
                    && typeof navigator.canShare === 'function'
                    && navigator.canShare(payload)) {
                    await navigator.share(payload);
                    return;
                }

                window.location.assign(button.dataset.commissionShareUrl);
            } catch (error) {
                if (error?.name === 'AbortError') return;
                window.location.assign(button.dataset.commissionShareUrl);
            }
        });
    });

    

    syncSelection();
};

export const bindNativeInteractions = (root = document) => {
    bindNavigationFeedback(root);
    bindPendingForms(root);
    bindDebouncedSearch(root);
    bindSearchClear(root);
    bindLoadMore(root);
    bindLocalFilters(root);
    bindPwaSelectSearch(root);
    bindCommissionWorkspace(root);
};

window.ClientPortalNativeInteractions = {bind: bindNativeInteractions};

const boot = () => bindNativeInteractions(document);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, {once: true});
} else {
    boot();
}
