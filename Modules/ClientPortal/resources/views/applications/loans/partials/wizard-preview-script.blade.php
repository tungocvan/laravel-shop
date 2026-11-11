    const wizard = document.querySelector('[data-loan-wizard]');
    if (wizard) {
        const type = wizard.querySelector('[data-loan-lender-type]');
        const lender = wizard.querySelector('[data-loan-lender]');
        const preview = wizard.querySelector('[data-loan-wizard-preview]');
        const refreshLenders = () => {
            for (const option of lender.options) {
                if (!option.value) continue;
                option.hidden = option.dataset.lenderType !== type.value;
                option.disabled = option.hidden;
            }
            if (lender.selectedOptions[0]?.disabled) lender.value = '';
        };
        type.addEventListener('change', refreshLenders);
        refreshLenders();
        const previewSchedule = () => {
            const amount = Number(wizard.querySelector('[name="committed_amount"]').value);
            const rate = Number(wizard.querySelector('[name="annual_rate_percent"]').value);
            const basis = Number(wizard.querySelector('[name="day_count_basis"]').value);
            const start = wizard.querySelector('[name="signed_at"]').value;
            const end = wizard.querySelector('[name="matures_at"]').value;
            const mode = wizard.querySelector('[name="repayment_mode"]:checked')?.value;
            if (!(amount > 0) || !(rate >= 0) || !start || !end || mode === 'manual') {
                preview.textContent = mode === 'manual' ? 'Bạn sẽ tự lập lịch trả nợ sau khi lưu hợp đồng.' : 'Nhập số tiền, lãi suất và thời hạn để xem dự tính.';
                return;
            }
            const from = Date.parse(start + 'T00:00:00Z');
            const to = Date.parse(end + 'T00:00:00Z');
            const days = Math.round((to - from) / 86400000);
            if (!(days > 0) || days > 3660) {
                preview.textContent = 'Ngày đáo hạn phải sau ngày ký; lịch tự động hỗ trợ tối đa 120 tháng.';
                return;
            }
            const interest = Math.round(amount * rate * days / (100 * basis));
            const format = n => new Intl.NumberFormat('vi-VN', {maximumFractionDigits: 0}).format(n) + ' VND';
            preview.textContent = 'Dự tính ' + days + ' ngày: gốc ' + format(amount) + ', tổng lãi khoảng ' + format(interest) + '. ' + (mode === 'interest_monthly' ? 'Lãi chia theo kỳ thực tế; gốc cuối kỳ.' : 'Gốc và lãi trả cuối kỳ.') + ' Chưa phát sinh thanh toán.';
        };
        wizard.addEventListener('input', previewSchedule);
        wizard.addEventListener('change', previewSchedule);
        previewSchedule();
    }

