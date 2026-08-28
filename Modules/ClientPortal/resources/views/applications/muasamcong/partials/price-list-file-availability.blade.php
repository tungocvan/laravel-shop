<script>
(() => {
    const hide = (element) => element?.style.setProperty('display', 'none', 'important');
    const show = (element) => element?.style.removeProperty('display');

    const warning = (card, message, tone = 'rose') => {
        let box = card.querySelector('[data-file-availability-warning]');
        if (!box) {
            box = document.createElement('div');
            box.dataset.fileAvailabilityWarning = '1';
            box.className = 'mt-3 rounded-xl px-3 py-2 text-xs';
            const actions = card.querySelector('.mt-4.flex.flex-wrap.items-center.gap-2');
            actions?.before(box);
        }
        box.className = `mt-3 rounded-xl px-3 py-2 text-xs ${tone === 'amber' ? 'bg-amber-50 text-amber-800' : 'bg-rose-50 text-rose-700'}`;
        box.textContent = message;
    };

    document.querySelectorAll('.export-card[data-status-url]').forEach((card) => {
        const excel = card.querySelector('a[href*="/download"]:not([href*="/pdf/"])');
        const pdfDownload = card.querySelector('a[href*="/pdf/download"]');
        const pdfForm = card.querySelector('form[action*="/pdf"]');
        const share = card.querySelector('.share-export');
        const email = card.querySelector('[data-email-open]');
        const status = card.querySelector('.export-status');

        [excel, pdfDownload, pdfForm, share, email].forEach(hide);

        fetch(card.dataset.statusUrl, {
            headers: {'Accept': 'application/json'},
            credentials: 'same-origin',
            cache: 'no-store',
        }).then((response) => {
            if (!response.ok) throw new Error('status-unavailable');
            return response.json();
        }).then((data) => {
            if (data.file_available === true) {
                show(excel);
                show(share);
                show(email);

                if (data.pdf_available === true) {
                    show(pdfDownload);
                } else if (!['queued', 'processing'].includes(data.pdf_status)) {
                    show(pdfForm);
                }

                return;
            }

            if (data.status === 'failed') {
                warning(card, `Không tạo được file Excel${data.error ? `: ${data.error}` : '.'}`);
                return;
            }

            if (data.status === 'completed') {
                if (status) {
                    status.textContent = 'Thiếu file';
                    status.className = 'export-status rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700';
                }
                warning(card, 'Bản ghi đã hoàn thành nhưng file Excel không tồn tại trên storage. Vui lòng chọn “Tạo lại Excel”.', 'amber');
            }
        }).catch(() => {
            warning(card, 'Chưa xác minh được file Excel. Các thao tác tải/gửi tạm thời được ẩn để tránh lỗi.', 'amber');
        });
    });
})();
</script>
