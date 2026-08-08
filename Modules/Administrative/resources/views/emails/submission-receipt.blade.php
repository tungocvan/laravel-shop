<p>Xin chào {{ $submission->applicant_name }},</p>
<p>Hệ thống đã tiếp nhận hồ sơ <strong>{{ $submission->submission_code }}</strong> cho thủ tục “{{ $submission->procedure->name }}”.</p>
<p>Mã tra cứu bí mật: <strong>{{ $lookupToken }}</strong></p>
<p>Vui lòng giữ kín mã tra cứu. Biên nhận PDF được đính kèm email này.</p>
<p>Trân trọng.</p>
