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

    const updateSelection = () => {
        const checked = rows.filter(row => row.checked).length;
        if (count) count.textContent = String(checked);
        if (selectAll) {
            selectAll.checked = rows.length > 0 && checked === rows.length;
            selectAll.indeterminate = checked > 0 && checked < rows.length;
        }
    };
    rows.forEach(row => row.addEventListener('change', updateSelection));
    if (selectAll) selectAll.addEventListener('change', () => {
        rows.forEach(row => { row.checked = selectAll.checked; });
        updateSelection();
    });
    if (syncForm) syncForm.addEventListener('submit', event => {
        if (rows.every(row => !row.checked)) {
            event.preventDefault();
            window.alert('Vui lòng chọn ít nhất một thuốc để xem trước đồng bộ.');
        }
    });
    updateSelection();
})();
