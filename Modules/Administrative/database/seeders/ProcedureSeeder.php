<?php

namespace Modules\Administrative\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Administrative\Models\AdministrativeProcedure;

class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = [
            'HC-001' => ['Điều chỉnh thông tin học sinh', 'Cập nhật thông tin cá nhân, liên hệ hoặc thông tin học tập của học sinh.', ['Đơn đề nghị điều chỉnh thông tin', 'Bản sao giấy khai sinh hoặc giấy tờ chứng minh thông tin cần điều chỉnh']],
            'HC-002' => ['Cấp lại bản sao chứng nhận hoàn thành chương trình tiểu học', 'Đề nghị cấp lại bản sao chứng nhận cho học sinh đã hoàn thành chương trình tiểu học.', ['Đơn đề nghị cấp bản sao', 'Giấy tờ tùy thân của người đề nghị']],
            'HC-003' => ['Chuyển đến hoặc đi từ trường trong nước', 'Tiếp nhận yêu cầu chuyển trường giữa các cơ sở giáo dục trong nước.', ['Đơn xin chuyển trường', 'Học bạ hoặc bảng kết quả học tập', 'Giấy giới thiệu chuyển trường nếu có']],
            'HC-004' => ['Chuyển đến từ trường ngoài nước', 'Tiếp nhận học sinh chuyển về từ cơ sở giáo dục ngoài Việt Nam.', ['Đơn xin nhập học', 'Học bạ hoặc bảng điểm nước ngoài', 'Bản dịch giấy tờ học tập nếu cần']],
            'HC-005' => ['Chuyển lớp', 'Đề nghị chuyển lớp trong cùng cơ sở giáo dục.', ['Đơn đề nghị chuyển lớp', 'Ý kiến phụ huynh hoặc người giám hộ']],
            'HC-006' => ['Đơn miễn giảm học phí', 'Tiếp nhận hồ sơ đề nghị miễn, giảm hoặc hỗ trợ học phí theo đối tượng.', ['Đơn đề nghị miễn giảm học phí', 'Giấy tờ chứng minh đối tượng được hưởng chính sách']],
            'HC-007' => ['Xác nhận học sinh đang học tại trường', 'Cấp giấy xác nhận tình trạng học tập hiện tại của học sinh.', ['Đơn đề nghị xác nhận', 'Giấy tờ tùy thân người đề nghị']],
            'HC-008' => ['Đơn đề nghị cấp bảng điểm', 'Đề nghị cấp bảng điểm hoặc kết quả học tập phục vụ thủ tục cá nhân.', ['Đơn đề nghị cấp bảng điểm']],
            'HC-009' => ['Xác nhận kết quả học tập', 'Cấp xác nhận kết quả học tập theo năm học hoặc giai đoạn học tập.', ['Đơn đề nghị xác nhận kết quả học tập']],
            'HC-010' => ['Đề nghị xác nhận hạnh kiểm', 'Cấp xác nhận rèn luyện, hạnh kiểm theo hồ sơ nhà trường.', ['Đơn đề nghị xác nhận', 'Thông tin năm học cần xác nhận']],
            'HC-011' => ['Đăng ký học bán trú', 'Tiếp nhận đăng ký tham gia chương trình bán trú.', ['Đơn đăng ký bán trú', 'Thông tin sức khỏe và người liên hệ']],
            'HC-012' => ['Đề nghị thôi học bán trú', 'Tiếp nhận yêu cầu chấm dứt đăng ký bán trú.', ['Đơn đề nghị thôi bán trú']],
            'HC-013' => ['Xin nghỉ học dài ngày', 'Tiếp nhận đề nghị nghỉ học nhiều ngày vì lý do sức khỏe hoặc gia đình.', ['Đơn xin nghỉ học', 'Giấy xác nhận y tế nếu nghỉ vì sức khỏe']],
            'HC-014' => ['Xác nhận để hưởng chế độ chính sách', 'Cấp giấy xác nhận học sinh phục vụ hồ sơ hưởng chế độ, trợ cấp hoặc chính sách xã hội.', ['Đơn đề nghị xác nhận', 'Thông tin chính sách cần xác nhận']],
            'HC-015' => ['Đề nghị điều chỉnh thông tin phụ huynh', 'Cập nhật thông tin cha, mẹ hoặc người giám hộ trong hồ sơ học sinh.', ['Đơn đề nghị điều chỉnh', 'Giấy tờ chứng minh thông tin phụ huynh']],
            'HC-016' => ['Đề nghị cấp lại thẻ học sinh', 'Tiếp nhận yêu cầu cấp lại thẻ do mất, hỏng hoặc thay đổi thông tin.', ['Đơn đề nghị cấp lại thẻ', 'Ảnh học sinh mới nếu cần']],
        ];

        foreach ($procedures as $code => [$name, $description, $documents]) {
            $procedure = AdministrativeProcedure::withTrashed()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => $description,
                    'instructions' => 'Điền đầy đủ thông tin, chuẩn bị giấy tờ theo danh mục, tải file rõ ràng và kiểm tra lại trước khi gửi hồ sơ.',
                    'required_documents' => $documents,
                    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                    'max_file_size_kb' => 10240,
                    'max_files' => 5,
                    'is_active' => true,
                    'sort_order' => (int) Str::after($code, 'HC-'),
                ]
            );

            if ($procedure->trashed()) {
                $procedure->restore();
            }
        }
    }
}
