(() => {
    'use strict';
    const root = document.getElementById('pharma-award-lookup');
    if (!root) return;

    const input = root.querySelector('#award-keyword');
    const clear = root.querySelector('#award-clear');
    const syncForm = root.querySelector('#award-select-form');
    const count = root.querySelector('#award-selected-count');
    const selectAll = root.querySelector('#award-select-all');
    const rows = Array.from(root.querySelectorAll('.award-row-select'));
    const resultRows = Array.from(root.querySelectorAll('.award-result-row'));
    const localSearch = root.querySelector('#award-local-search');
    const group = root.querySelector('#award-group-filter');
    const filterCount = root.querySelector('#award-filter-count');
    const noResults = root.querySelector('#award-no-filter-results');

    if (input && clear) {
        const updateClear = () => clear.classList.toggle('hidden', input.value.length === 0);
        input.addEventListener('input', updateClear);
        clear.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        });
        updateClear();
    }

    const visibleSelections = () => rows.filter(row => row.closest('tr')?.style.display !== 'none');
    const updateSelection = () => {
        const checked = rows.filter(row => row.checked).length;
        if (count) count.textContent = String(checked);
        if (selectAll) {
            const visible = visibleSelections();
            const selectedVisible = visible.filter(row => row.checked).length;
            selectAll.checked = visible.length > 0 && selectedVisible === visible.length;
            selectAll.indeterminate = selectedVisible > 0 && selectedVisible < visible.length;
            selectAll.disabled = visible.length === 0;
        }
    };

    const normalize = value => String(value || '').normalize('NFC').toLocaleLowerCase('vi-VN').trim();
    const filterRows = () => {
        const term = normalize(localSearch?.value);
        const selectedGroup = group?.value || '';
        let shown = 0;
        resultRows.forEach(row => {
            const data = row.dataset;
            const haystack = normalize([data.name, data.ingredient, data.strength].join(' '));
            const matches = (!term || haystack.includes(term))
                && (!selectedGroup || data.group === selectedGroup);
            row.hidden = !matches;
            row.style.display = matches ? '' : 'none';
            // Preserve checked rows across filter changes; they remain part of the sync form.
            if (matches) shown++;
        });
        if (filterCount) filterCount.textContent = 'Đang hiển thị ' + shown + ' / ' + resultRows.length + ' bản ghi trong trang hiện tại';
        if (noResults) noResults.classList.toggle('hidden', shown !== 0);
        updateSelection();
    };
    [localSearch, group].forEach(field => {
        if (field) {
            field.addEventListener('input', filterRows);
            field.addEventListener('change', filterRows);
        }
    });
    root.querySelector('#award-reset-filters')?.addEventListener('click', () => {
        [localSearch, group, priceMin, priceMax].forEach(field => { if (field) field.value = ''; });
        filterRows();
    });
    rows.forEach(row => row.addEventListener('change', updateSelection));
    if (selectAll) selectAll.addEventListener('change', () => {
        visibleSelections().forEach(row => { row.checked = selectAll.checked; });
        updateSelection();
    });
    if (syncForm) syncForm.addEventListener('submit', event => {
        if (rows.every(row => !row.checked)) {
            event.preventDefault();
            window.alert('Vui lòng chọn ít nhất một thuốc để xem trước đồng bộ.');
        }
    });
    filterRows();
})();
