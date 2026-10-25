<?php

namespace Modules\Finance\Livewire;

use Livewire\Component;

final class DebtSettings extends Component
{
    /**
     * Informational screen only: no persisted settings or mutable toggles.
     */
    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);

        return view('Finance::livewire.debt-settings', [
            'capabilities' => [
                ['label' => 'Tạo công nợ thủ công', 'state' => 'active', 'detail' => 'Tạo bản nháp tại Chứng từ; thao tác ghi yêu cầu quyền finance.debt.manage.'],
                ['label' => 'Xác nhận công nợ thủ công', 'state' => 'active', 'detail' => 'Chỉ xác nhận bản nháp thủ công hợp lệ; ghi ngày ghi nhận.'],
                ['label' => 'Điều chỉnh công nợ', 'state' => 'active', 'detail' => 'Ghi sự kiện điều chỉnh có dấu cho khoản thủ công đã xác nhận; không làm âm gốc sau điều chỉnh.'],
                ['label' => 'Dashboard / Đối tác / Sổ chi tiết', 'state' => 'readonly', 'detail' => 'Chỉ đọc giá trị gốc và điều chỉnh đã lưu.'],
                ['label' => 'Tổng hợp / Tuổi nợ', 'state' => 'readonly', 'detail' => 'Báo cáo gốc sau điều chỉnh, không phải số dư chưa thanh toán.'],
                ['label' => 'Đồng bộ hóa đơn', 'state' => 'pending', 'detail' => 'Chưa tự động ghi nhận công nợ từ vòng đời hóa đơn.'],
                ['label' => 'Thanh toán / đối trừ công nợ', 'state' => 'pending', 'detail' => 'Chưa phân bổ thanh toán và chưa khấu trừ vào các báo cáo công nợ.'],
                ['label' => 'Số dư đầu kỳ / cuối kỳ kế toán', 'state' => 'pending', 'detail' => 'Chưa có quy tắc đối soát đầy đủ để tính và công bố số dư kế toán.'],
                ['label' => 'Thiết lập quy tắc nghiệp vụ', 'state' => 'pending', 'detail' => 'Chưa có màn hình lưu cấu hình hoặc thay đổi quy tắc công nợ.'],
            ],
        ]);
    }
}
