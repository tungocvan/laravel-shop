# Checkpoint 11G.2 — Báo cáo biến động công nợ theo kỳ

Branch: `feat/finance-11g2-period-report-20261025`. Kế thừa 11G First Gate đã được người dùng xác nhận focused tests, Finance regression và UI PASS.

## Phạm vi

- Tab **Theo kỳ 11G.2** trong Finance → Chứng từ công nợ.
- Lọc ngày bắt đầu/kết thúc, loại phải thu/phải trả, tìm kiếm; phân trang 15 dòng.
- Công nợ ghi nhận trước kỳ + điều chỉnh trước kỳ − thanh toán ròng trước kỳ = đầu kỳ.
- Gốc ghi nhận trong kỳ + điều chỉnh hiệu lực trong kỳ = phát sinh.
- Thanh toán theo `finance_ledger_entries.effective_at` posting; hoàn tác theo `effective_at` reversal, không lấy ngày phiếu gốc làm ngày đảo.
- Cuối kỳ = đầu kỳ + phát sinh − thanh toán ròng trong kỳ.
- Tổng chỉ cộng dòng chưa bị gắn cờ thiếu chứng cứ; dữ liệu tiền tính bằng SQL DECIMAL, không dùng float.
- Read-only; không thêm migration, không tạo phiếu, không sửa dữ liệu kế toán.

## Giới hạn

Đây là **báo cáo quản trị tham khảo**, chưa phải historical certified as-of/statutory ledger. Bút toán ghi muộn/backdate, điều chỉnh nguồn lịch sử, external allocations, dòng mapping bất thường, hoặc thay đổi trạng thái nguồn cần được đối soát độc lập. Cờ `provisional` **không** có nghĩa là đã kiểm toán. Dữ liệu bất thường không được đưa vào tổng tham khảo. Không sử dụng để xác nhận quyền thanh toán.

## Git Pull / Test (dừng tại gate)

1. `git pull --ff-only origin feat/finance-11g2-period-report-20261025`
2. `php artisan test Modules/Finance/Tests/Unit/FinanceDebtPeriodReport11G2ContractTest.php`
3. `php artisan test Modules/Finance/Tests`

Nếu test 1 FAIL, dừng và gửi log, không chạy test 2. Assistant chưa chạy PHPUnit hoặc xác nhận UI.

## UI acceptance (sau tests PASS)

- Chọn tab **Theo kỳ 11G.2**, đổi khoảng ngày, lọc phải thu/phải trả và tìm kiếm.
- Đối chiếu công nợ mới ghi nhận trong kỳ, điều chỉnh trước/trong kỳ, thanh toán một phần và hoàn tác qua ranh giới kỳ.
- Kiểm tra dữ liệu thiếu ledger/mapping hiển thị **Cần đối soát** và không cộng vào tổng.
- Kiểm tra desktop/mobile, cuộn bảng, phân trang, quyền finance.view, không lỗi 500/Livewire.
- UI PASS chỉ được ghi nhận khi người dùng xác nhận.

Không merge main hoặc deploy production khi chưa đủ gate và chưa được đồng ý.
