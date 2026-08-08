<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 1cm 1.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; line-height: 1.3; }
        .header { width: 100%; border: none; }
        .header td { text-align: center; vertical-align: top; }
        .title { text-align: center; font-weight: bold; font-size: 13pt; margin: 15px 0; }
        .mhs { font-weight: bold; margin-bottom: 5px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 2px 0; }
        .field-val { font-weight: bold; }
        .footer { width: 100%; margin-top: 20px; text-align: center; }
        .hieu-truong { border-top: 1px dashed #000; margin-top: 20px; padding-top: 10px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="50%">ỦY BAN NHÂN DÂN PHƯỜNG TÂN THUẬN<br><strong>TRƯỜNG TIỂU HỌC NGUYỄN THỊ ĐỊNH</strong></td>
            <td><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br>Độc lập – Tự do – Hạnh phúc</td>
        </tr>
    </table>

    <div class="mhs">MHS_ {{ $MHS }}</div>
    <div class="title">ĐƠN ĐĂNG KÝ NHẬP HỌC LỚP MỘT<br><span style="font-weight:normal">Năm học 2026 – 2027</span></div>

    <p style="text-align: center"><i>Kính gửi: Hội đồng tuyển sinh lớp 1 Trường Tiểu học Nguyễn Thị Định</i></p>

    <table class="data">
        <tr>
            <td colspan="2">Họ và tên học sinh: <span class="field-val">{{ $HoVaTenHocSinh }}</span></td>
            <td>Giới tính: <span class="field-val">{{ $GioiTinh }}</span></td>
        </tr>
        <tr>
            <td>Ngày sinh: <span class="field-val">{{ $NgaySinh }}</span></td>
            <td>Dân tộc: <span class="field-val">{{ $DanToc }}</span></td>
            <td>Mã định danh: <span class="field-val">{{ $MaDinhDanh }}</span></td>
        </tr>
        <tr>
            <td>Quốc tịch: <span class="field-val">{{ $QuocTich }}</span></td>
            <td>Tôn giáo: <span class="field-val">{{ $TonGiao }}</span></td>
            <td>SĐT (EnetViet): <span class="field-val">{{ $SDTEnetViet }}</span></td>
        </tr>
        <tr><td colspan="3">Nơi sinh: {{ $NoiSinh }}</td></tr>
        <tr><td colspan="3">Nơi đăng ký khai sinh: {{ $NoiDangKyKhaiSinh }}</td></tr>
        <tr><td colspan="3">Quê quán: {{ $QueQuan }}</td></tr>
    </table>

    <p><strong>* Địa chỉ thường trú:</strong> {{ $DiaChiThuongTru }}</p>
    <p><strong>* Nơi ở hiện tại:</strong> {{ $NoiOHienTai }}</p>

    <p>Học sinh ở chung với: {{ $OChungVoi }}. Con thứ: {{ $ConThu }} trong số {{ $TSAnhChiEm }} anh, chị em.</p>
    <p>Đã hoàn thành lớp Lá tại: {{ $TruongMamNon }}</p>

    <p><strong>* Thông tin Phụ huynh:</strong><br>
    - Cha: {{ $HoTenCha }} ({{ $NamSinhCha }}). Nghề: {{ $NgheNghiepCha }}. ĐT: {{ $DienThoaiCha }}<br>
    - Mẹ: {{ $HoTenMe }} ({{ $NamSinhMe }}). Nghề: {{ $NgheNghiepMe }}. ĐT: {{ $DienThoaiMe }}</p>

    <p><strong>* Đăng ký học:</strong> {{ $LoaiLopDangKy }}</p>

    <p><strong>Cam kết:</strong> Có góc học tập: [{{ $CK_GocHocTap ? 'x' : ' ' }}]; Đủ sách vở: [{{ $CK_SachVo ? 'x' : ' ' }}]; Họp đủ: [{{ $CK_HopPH ? 'x' : ' ' }}]</p>

    <table class="footer">
        <tr>
            <td width="50%"></td>
            <td>Ngày {{ date('d') }} tháng {{ date('m') }} năm 2026<br><strong>Người làm đơn</strong><br><br><br>{{ $NguoiLamDon }}</td>
        </tr>
    </table>

    <div class="hieu-truong">
        <strong>Phần ghi nhận của Hiệu trưởng</strong><br>
        Đồng ý nhận em: {{ $HoVaTenHocSinh }} vào lớp 1 năm học 2026-2027.<br>
        <div style="text-align:right; margin-right:50px"><strong>HIỆU TRƯỞNG</strong><br><br><br><strong>Hà Thanh Hải</strong></div>
    </div>
</body>
</html>