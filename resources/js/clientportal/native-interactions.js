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
                if (nextWrap) list.insertAdjacentElement('afterend', nextWrap);

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

export const bindNativeInteractions = (root = document) => {
    bindNavigationFeedback(root);
    bindPendingForms(root);
    bindDebouncedSearch(root);
    bindSearchClear(root);
    bindLoadMore(root);
};

const boot = () => bindNativeInteractions(document);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, {once: true});
} else {
    boot();
}
