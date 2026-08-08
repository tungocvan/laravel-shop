<p>Xin chào {{ $submission->applicant_name }},</p>
<p>Hồ sơ <strong>{{ $submission->submission_code }}</strong> của thủ tục “{{ $submission->procedure->name }}” đã được cập nhật.</p>
<p>Trạng thái: <strong>{{ ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Bị từ chối', 'need_supplement' => 'Yêu cầu bổ sung'][$submission->status->value] ?? $submission->status->value }}</strong></p>
@if($submission->supplement_reason)<p>Nội dung cần bổ sung: {{ $submission->supplement_reason }}</p>@endif
@if($submission->rejection_reason)<p>Lý do từ chối: {{ $submission->rejection_reason }}</p>@endif
@if($submission->response)<p>Phản hồi: {{ $submission->response }}</p>@endif
<p>Vui lòng sử dụng mã hồ sơ và mã tra cứu đã nhận để xem chi tiết.</p>
<p>Trân trọng.</p>
