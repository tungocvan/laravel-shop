const initPharmaPriceListWizard = () => {
    const form = document.getElementById('price-list-editor');
    if (!form || form.dataset.viteWizardInitialized === '1') return;
    form.dataset.viteWizardInitialized = '1';

    const panels = [...document.querySelectorAll('[data-wizard-panel]')];
    const steps = [...document.querySelectorAll('[data-step-jump]')];
    const back = document.getElementById('wizard-back');
    const next = document.getElementById('wizard-next');
    const submit = document.getElementById('wizard-submit');
    const source = document.getElementById('source-price-list');
    const loadSource = document.getElementById('load-source-price-list');
    let currentStep = panels.find(panel => !panel.classList.contains('hidden'))?.dataset.wizardPanel === '2' ? 2 : 1;

    const value = name => String(form.elements.namedItem(name)?.value || '').trim();
    const stepTwoReady = () => {
        const globalScope = document.querySelector('[data-global-user-scope]');
        if (globalScope) {
            const applyAll = document.getElementById('apply-all-global-users')?.checked;
            const selectedUsers = document.querySelectorAll('input[name="global_user_ids[]"]:checked').length;
            return ['name','effective_from','effective_to'].every(name => value(name) !== '') && (applyAll || selectedUsers > 0);
        }
        return ['name','partner_id','purpose_id','effective_from','effective_to'].every(name => value(name) !== '');
    };
    const selectedCount = () => document.querySelectorAll('[data-source-product-checkbox]:checked').length;
    const displayDate = raw => /^\d{4}-\d{2}-\d{2}$/.test(raw || '') ? raw.split('-').reverse().join('/') : String(raw || '');
    const formatDate = raw => {
        const displayed = displayDate(raw);
        return /^\d{2}\/\d{2}\/\d{4}$/.test(displayed) ? displayed : '—';
    };

    const syncReview = () => {
        document.querySelector('[data-review-name]')?.replaceChildren(document.createTextNode(value('name') || '—'));
        document.querySelector('[data-review-dates]')?.replaceChildren(document.createTextNode(formatDate(value('effective_from')) + ' → ' + formatDate(value('effective_to'))));
        document.querySelector('[data-review-products]')?.replaceChildren(document.createTextNode(selectedCount() + ' sản phẩm'));
    };
    const syncActions = () => {
        if (back) back.classList.toggle('hidden', currentStep === 1);
        if (next) {
            next.classList.toggle('hidden', currentStep === 4);
            const isGlobalStepTwo = currentStep === 2 && !!document.querySelector('[data-global-user-scope]');
            next.disabled = currentStep === 1
                ? !source?.value
                : currentStep === 2 && !isGlobalStepTwo
                    ? !stepTwoReady()
                    : false;
        }
        if (submit) {
            submit.classList.toggle('hidden', currentStep !== 4);
            submit.disabled = selectedCount() === 0;
        }
    };
    const showStep = step => {
        currentStep = Math.max(1, Math.min(4, Number(step) || 1));
        panels.forEach(panel => panel.classList.toggle('hidden', Number(panel.dataset.wizardPanel) !== currentStep));
        steps.forEach(button => {
            const number = Number(button.dataset.stepJump);
            const active = number === currentStep;
            const completed = number < currentStep;
            button.dataset.state = active ? 'active' : completed ? 'completed' : 'pending';
            button.classList.toggle('bg-slate-950', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-slate-100', completed);
            button.classList.toggle('text-slate-700', completed);
            button.classList.toggle('text-slate-400', !active && !completed);
            const dot = button.querySelector('.step-dot');
            if (dot) {
                dot.textContent = completed ? '✓' : String(number);
                dot.classList.toggle('bg-white', active);
                dot.classList.toggle('text-slate-950', active);
                dot.classList.toggle('border-white', active);
            }
        });
        if (currentStep === 4) syncReview();
        syncActions();
        window.scrollTo({top: 0, behavior: 'smooth'});
    };

    source?.addEventListener('change', () => {
        document.getElementById('source-price-list-error')?.classList.add('hidden');
        syncActions();
    });
    loadSource?.addEventListener('click', event => {
        if (source && !source.value) {
            event.preventDefault();
            document.getElementById('source-price-list-error')?.classList.remove('hidden');
            source.focus();
        }
    });
    back?.addEventListener('click', () => showStep(currentStep - 1));
    next?.addEventListener('click', () => {
        if (currentStep === 1) {
            if (loadSource) loadSource.click(); else showStep(2);
            return;
        }
        if (currentStep === 2 && !stepTwoReady()) {
            const globalScope = document.querySelector('[data-global-user-scope]');
            if (globalScope) {
                const applyAll = document.getElementById('apply-all-global-users')?.checked;
                const selectedUsers = document.querySelectorAll('input[name="global_user_ids[]"]:checked').length;
                globalUserError?.classList.toggle('hidden', !!applyAll || selectedUsers > 0);
                if (!applyAll && selectedUsers === 0) {
                    globalUserPicker?.classList.remove('hidden');
                    globalUserSearch?.focus();
                    return;
                }
            }
            form.reportValidity();
            return;
        }
        showStep(currentStep + 1);
    });
    steps.forEach(button => button.addEventListener('click', () => {
        const target = Number(button.dataset.stepJump);
        if (target <= currentStep) showStep(target);
    }));
    form.addEventListener('input', syncActions);
    form.addEventListener('change', syncActions);

    const managerBox = document.querySelector('[data-price-list-manager-combobox]');
    const managerToggle = document.getElementById('client-price-list-manager-toggle');
    const managerPanel = document.getElementById('client-price-list-manager-panel');
    const managerSearch = document.getElementById('client-price-list-manager-search');
    const managerId = document.getElementById('client-price-list-manager');
    const managerLabel = document.getElementById('client-price-list-manager-label');
    const managerOptions = [...document.querySelectorAll('[data-manager-option]')];
    const renderManagers = () => {
        if (!managerPanel) return;
        const q = (managerSearch?.value || '').toLocaleLowerCase('vi').trim();
        managerOptions.forEach(option => option.classList.toggle('hidden', q !== '' && !(option.dataset.search || '').includes(q)));
    };
    managerToggle?.addEventListener('click', () => {
        managerPanel?.classList.toggle('hidden');
        if (!managerPanel?.classList.contains('hidden')) {
            renderManagers();
            setTimeout(() => managerSearch?.focus(), 0);
        }
    });
    managerSearch?.addEventListener('input', renderManagers);
    managerOptions.forEach(option => option.addEventListener('click', () => {
        if (managerId) managerId.value = option.dataset.value || '';
        if (managerLabel) managerLabel.textContent = option.dataset.label || option.textContent.trim();
        managerPanel?.classList.add('hidden');
        syncActions();
    }));

    const applyAllGlobalUsers = document.getElementById('apply-all-global-users');
    const globalUserPicker = document.getElementById('global-user-picker');
     const globalUserOptions = [...document.querySelectorAll('[data-global-user-option]')];
    const globalUserError = document.getElementById('global-user-scope-error');
    const syncGlobalUserScope = () => {
        if (!applyAllGlobalUsers) return;
        globalUserPicker?.classList.toggle('hidden', applyAllGlobalUsers.checked);
        globalUserError?.classList.toggle('hidden', applyAllGlobalUsers.checked || document.querySelectorAll('input[name="global_user_ids[]"]:checked').length > 0);
        syncActions();
    };
    applyAllGlobalUsers?.addEventListener('change', syncGlobalUserScope);
     globalUserOptions.forEach(option => option.querySelector('input')?.addEventListener('change', syncGlobalUserScope));
    document.getElementById('global-user-select-all')?.addEventListener('click', () => {
        globalUserOptions.filter(option => !option.classList.contains('hidden')).forEach(option => {
            const checkbox = option.querySelector('input');
            if (checkbox) checkbox.checked = true;
        });
        syncGlobalUserScope();
    });
    syncGlobalUserScope();

    const customerId = document.getElementById('client-price-list-customer');
    customerId?.addEventListener('change', syncActions);

    const productSearch = document.getElementById('source-product-search');
    const productClear = document.getElementById('source-product-search-clear');
    const productRows = [...document.querySelectorAll('.source-product-row')];
    const filterProducts = () => {
        const q = (productSearch?.value || '').toLocaleLowerCase('vi').trim();
        productRows.forEach(row => row.classList.toggle('hidden', q !== '' && !(row.dataset.search || '').includes(q)));
        productClear?.classList.toggle('hidden', !productSearch?.value);
    };
    productSearch?.addEventListener('input', filterProducts);
    productClear?.addEventListener('click', () => {
        productSearch.value = '';
        filterProducts();
        productSearch.focus({preventScroll: true});
    });

    const selectAll = document.getElementById('select-all-source-products');
    selectAll?.addEventListener('change', () => {
        productRows.filter(row => !row.classList.contains('hidden')).forEach(row => {
            const checkbox = row.querySelector('[data-source-product-checkbox]');
            if (checkbox) checkbox.checked = selectAll.checked;
        });
        syncActions();
    });
    document.querySelectorAll('[data-source-product-checkbox]').forEach(box => box.addEventListener('change', syncActions));

    const moneyInputs = [...document.querySelectorAll('[data-money-input]')];
    const digits = input => String(input || '').replace(/\D/g, '');
    const money = input => digits(input).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    moneyInputs.forEach(input => {
        input.value = money(input.value);
        input.addEventListener('input', () => { input.value = money(input.value); });
    });
    form.addEventListener('submit', () => {
        moneyInputs.forEach(input => { input.value = digits(input.value); });
    });

    showStep(currentStep);
};

document.addEventListener('DOMContentLoaded', initPharmaPriceListWizard);
if (document.readyState !== 'loading') initPharmaPriceListWizard();
