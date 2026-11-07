<?php

namespace Modules\Muasamcong\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KqlcntAwardImportTemplateExport implements WithMultipleSheets
{
    public const MEDICINE_HEADINGS = [
        'Mã lô', 'Tên lô', 'Mã thuốc', 'Tên thuốc', 'Nhóm thuốc', 'Hoạt chất',
        'Hàm lượng', 'Đường dùng', 'Dạng bào chế', 'Quy cách', 'Hạn dùng (tháng)',
        'GĐKLH hoặc GPNK', 'ĐVT', 'Số lượng', 'Giá kế hoạch', 'Giá trúng thầu',
        'Thành tiền', 'Nhà sản xuất', 'Nước SX',
    ];

    public function sheets(): array
    {
        return [
            new KqlcntArraySheetExport('Huong_dan_nhap_lieu', ['Nội dung', 'Hướng dẫn'], [
                ['Phạm vi', 'Mỗi file chỉ chứa một TBMT và một nhà thầu trúng thầu.'],
                ['Bước 1', 'Điền mã TBMT tại Sheet Thong_tin_trung_thau, cột B, dòng Mã TBMT.'],
                ['Bước 2', 'Điền mã nhà thầu tại cột C bên dưới dòng DANH SÁCH NHÀ THẦU.'],
                ['Bước 3', 'Điền thuốc tại Sheet Danh_muc_trung_thau; giữ nguyên 19 tiêu đề A-S.'],
                ['Định danh', 'Nên có Mã lô và Mã thuốc để hệ thống đối chiếu chính xác.'],
                ['Số liệu', 'Số lượng, giá và thành tiền dùng số không âm; không kèm ký hiệu tiền tệ.'],
                ['Ngày tháng', 'Ngày quyết định dd/mm/yyyy; ngày đăng dd/mm/yyyy hh:mm.'],
                ['Kiểm tra', 'Import luôn xem trước và đối chiếu trước khi xác nhận.'],
                ['Giới hạn hiện tại', 'Thuốc chưa có bản ghi canonical sẽ báo lỗi; chưa hỗ trợ tạo mới.'],
                ['Lưu ý', 'Không sửa tên sheet, không thêm cột quản trị hoặc nhiều nhà thầu.'],
            ]),
            new KqlcntArraySheetExport('Thong_tin_trung_thau',
                ['Trường', 'Giá trị', 'Mã nhà thầu', 'Tên nhà thầu', 'Số hợp đồng', 'Thời gian thực hiện', 'Đơn vị thời gian'],
                [
                    ['Mã TBMT', null],
                    ['Số quyết định KQLCNT', null],
                    ['Ngày quyết định', null],
                    ['Ngày đăng KQLCNT', null],
                    ['Mã chủ đầu tư', null],
                    ['Chủ đầu tư', null],
                    ['Thời gian thực hiện gói thầu', null],
                    ['Thời hạn hiệu lực', null],
                    ['DANH SÁCH NHÀ THẦU', null],
                    [null, null, null, null, null, null, null],
                ]),
            new KqlcntArraySheetExport('Danh_muc_trung_thau', self::MEDICINE_HEADINGS, []),
        ];
    }
}
