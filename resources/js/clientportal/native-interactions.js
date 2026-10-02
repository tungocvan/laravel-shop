const debounceTimers = new WeakMap();

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

const bindDebouncedSearch = (root = document) => {
    root.querySelectorAll('[data-pwa-debounced-search]').forEach((input) => {
        if (input.dataset.pwaSearchBound) return;
        input.dataset.pwaSearchBound = '1';

        input.addEventListener('input', () => {
            window.clearTimeout(debounceTimers.get(input));
            const delay = Number.parseInt(input.dataset.pwaDebouncedSearch || '600', 10);
            const timer = window.setTimeout(() => input.form?.requestSubmit(), Number.isFinite(delay) ? delay : 600);
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
    bindLoadMore(root);
};

const boot = () => bindNativeInteractions(document);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, {once: true});
} else {
    boot();
}
