@extends('demo.quan-ly-nhan-su.layouts.app')
@section('content')


<aside class="sidebar" id="sidebar">
  <div class="brand">
    <div class="logo">HR</div>
    <div><b>Nhân Sự Pro</b><small>Công ty TNHH ABC</small></div>
  </div>
  <nav>
    <div class="sec">Tổng quan</div>
    <a class="item" id="navDash" onclick="goPage('dashboard')"><span class="ic">🏠</span>Tổng quan</a>
    <a class="item" id="navAnalytics" onclick="goPage('analytics')"><span class="ic">📈</span>Dashboard</a>
    <a class="item" id="navAll" onclick="goFilter('','')"><span class="ic">👥</span>Tất cả nhân viên<span class="cnt" id="cntAll"></span></a>

    <div class="sec">Phòng ban</div>
    <div id="navDept"></div>

    <div class="sec">Trạng thái</div>
    <div id="navStatus"></div>

    <div class="sec">Tiện ích</div>
    <a class="item" onclick="openForm()"><span class="ic">➕</span>Thêm nhân viên</a>
    <a class="item" onclick="saveFile()"><span class="ic">💾</span>Xuất JSON</a>
    <a class="item" onclick="exportCSV()"><span class="ic">⬇</span>Xuất Excel</a>
    <a class="item" onclick="openHrLibrary()"><span class="ic">🗄</span>Thư viện JSON server</a>
    <a class="item" onclick="document.getElementById('hrImport').click()"><span class="ic">📂</span>Nhập JSON</a>
    <a class="item" onclick="resetData()"><span class="ic">↺</span>Khôi phục dữ liệu mẫu</a>
    <div class="sec">Hệ thống</div>
    <a class="item" data-hr-item="settings" onclick="goPage('settings')"><span class="ic">⚙</span>Cài đặt & dữ liệu</a>
  </nav>
  <div class="side-foot">
    <div class="av">QT</div>
    <div><b>Quản trị viên</b><small>Phòng Nhân sự</small></div>
  </div>
</aside>
<div class="overlay" id="overlay" onclick="toggleSide(false)"></div>

<div class="main">
  <header>
    <div style="display:flex;align-items:center;gap:12px">
      <button class="menu-btn" onclick="toggleSide(true)" aria-label="Mở menu">☰</button>
      <div>
        <h1 id="pageTitle">Tổng quan</h1>
        <p id="pageSub">Tổng quan tình hình nhân sự công ty</p>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <label for="hrTheme" class="hr-theme-label">Màu giao diện</label>
      <select id="hrTheme" aria-label="Chọn theme" onchange="hrSetTheme(this.value)"><option value="ruby">Ruby</option><option value="ocean">Ocean</option><option value="emerald" selected>Emerald</option><option value="indigo">Indigo</option></select>
      <button class="btn btn-light" id="btnSave" onclick="saveFile()" title="Xuất dữ liệu JSON (Ctrl+S)">💾 Lưu</button>
      <button class="btn btn-primary" id="btnAddTop" onclick="openForm()">＋ Thêm nhân viên</button>
    </div>
  </header>

  <div class="wrap">
    <!-- Trang tổng quan -->
    <section id="page-dashboard">
      <div class="stats" id="stats"></div>
      <div class="dash-grid">
        <div class="panel">
          <div class="panel-title">Nhân viên theo phòng ban</div>
          <div class="dept-bars" id="deptBars"></div>
        </div>
        <div class="panel">
          <div class="panel-title">Mới vào làm gần đây <a onclick="goFilter('','')">Xem tất cả →</a></div>
          <div class="mini-list" id="recent"></div>
        </div>
      </div>
      <div class="panel">
        <div class="panel-title" id="bdTitle">Sinh nhật trong tháng</div>
        <div class="mini-list" id="birthdays"></div>
      </div>
    </section>

    <!-- Trang Dashboard -->
    <section id="page-analytics" hidden="">
      <div class="db-filter">
        <label for="dbDept">Xem số liệu của:</label>
        <select id="dbDept" onchange="render()"><option value="">Toàn công ty</option></select>
      </div>
      <div class="kpis" id="kpis"></div>
      <div class="chart-grid">
        <div class="panel">
          <div class="panel-title"><span>Quỹ lương tháng theo phòng ban <span class="hint">(triệu đồng)</span></span></div>
          <div class="hb" id="chSalary"></div>
        </div>
        <div class="panel">
          <div class="panel-title">Cơ cấu theo trạng thái</div>
          <div class="donut-wrap" id="chStatus"></div>
        </div>
        <div class="panel">
          <div class="panel-title"><span>Phân bố độ tuổi <span class="hint">(số người)</span></span></div>
          <div class="cols" id="chAge"></div>
        </div>
        <div class="panel">
          <div class="panel-title">Cơ cấu giới tính</div>
          <div class="donut-wrap" id="chGender"></div>
        </div>
        <div class="panel">
          <div class="panel-title"><span>Số người vào làm theo năm <span class="hint">(số người)</span></span></div>
          <div class="cols" id="chHire"></div>
        </div>
        <div class="panel">
          <div class="panel-title">Top 5 lương cao nhất</div>
          <div class="mini-list" id="chTop"></div>
        </div>
      </div>
    </section>

    <!-- Trang danh sách -->
    <section id="page-employees" hidden="">
      <div class="toolbar">
        <input id="q" placeholder="🔍 Tìm theo tên, mã NV, số điện thoại, email..." oninput="render()">
        <select id="fDept" onchange="render()"><option value="">Tất cả phòng ban</option></select>
        <select id="fStatus" onchange="render()">
          <option value="">Tất cả trạng thái</option>
          <option>Đang làm</option><option>Thử việc</option><option>Nghỉ phép</option><option>Đã nghỉ việc</option>
        </select>
        <div class="view-toggle">
          <button id="vCard" class="on" onclick="setView('card')">▦ Thẻ</button>
          <button id="vTable" onclick="setView('table')">☰ Bảng</button>
        </div>
      </div>
      <div class="count" id="count"></div>
      <div id="list"></div>
    </section>

    <section id="page-settings" hidden>
      <div class="hr-settings">
        <div class="hr-settings-panel"><h3>Sidebar menu</h3><p>Chỉnh sửa nhóm, mục, header/footer và thư viện JSON riêng cho demo này.</p><button type="button" class="btn btn-primary" data-open-menu>Chỉnh sửa menu</button></div>
        <div class="hr-settings-panel"><h3>Giao diện & màu sắc</h3><p>Chọn một trong bốn theme. Màu sắc lưu riêng trong trình duyệt.</p>
          <div class="hr-theme-grid">
            <button type="button" data-hr-theme-choice="ruby" onclick="hrSetTheme('ruby')"><i style="background:#C62828"></i><span><b>Đỏ Ruby</b><small>Màu đỏ đậm</small></span></button>
            <button type="button" data-hr-theme-choice="ocean" onclick="hrSetTheme('ocean')"><i style="background:#087E9B"></i><span><b>Xanh Ocean</b><small>Đổi màu giao diện</small></span></button>
            <button type="button" data-hr-theme-choice="emerald" onclick="hrSetTheme('emerald')"><i style="background:#16835D"></i><span><b>Xanh Emerald</b><small>Đổi màu giao diện</small></span></button>
            <button type="button" data-hr-theme-choice="indigo" onclick="hrSetTheme('indigo')"><i style="background:#4F46E5"></i><span><b>Tím Indigo</b><small>Đổi màu giao diện</small></span></button>
          </div>
        </div>
        <div class="hr-settings-panel"><h3>Thư viện dữ liệu Demo nhân sự</h3><p>Chỉ dùng dữ liệu mẫu trên server. Dữ liệu trình duyệt được lưu riêng.</p><p id="hrSettingsCount" aria-live="polite"></p><button type="button" class="btn btn-primary" onclick="openHrLibrary()">Mở thư viện JSON server</button></div>
        <div class="hr-settings-grid">
          <div class="hr-settings-panel"><h3>Lưu trên trình duyệt</h3><p>Dữ liệu tự động lưu vào localStorage.</p><button type="button" class="btn btn-light" onclick="save()">Lưu dữ liệu</button></div>
          <div class="hr-settings-panel"><h3>Sao lưu dữ liệu</h3><p>Tải bản sao lưu JSON về máy.</p><button type="button" class="btn btn-primary" onclick="saveFile()">Tải JSON</button></div>
          <div class="hr-settings-panel"><h3>Khôi phục dữ liệu</h3><p>Nhập JSON và xác nhận thay thế dữ liệu.</p><button type="button" class="btn btn-light" onclick="document.getElementById('hrImport').click()">Chọn file sao lưu</button></div>
          <div class="hr-settings-panel"><h3>Nạp dữ liệu mẫu</h3><p>Khôi phục 14 hồ sơ nhân sự mẫu.</p><button type="button" class="btn btn-light" onclick="resetData()">Nạp dữ liệu mẫu</button></div>
          <div class="hr-settings-panel"><h3>Xuất Excel</h3><p>Xuất CSV để mở bằng Excel.</p><button type="button" class="btn btn-light" onclick="exportCSV()">Xuất CSV</button></div>
        </div>
      </div>
    </section>
  </div>
</div>

<!-- Form thêm / sửa -->
<div class="modal-bg" id="formModal">
  <div class="modal">
    <h2 id="formTitle">Thêm nhân viên</h2>
    <div class="form">
      <div class="photo-box">
        <img id="fPhotoPreview" class="avatar" alt="Ảnh">
        <div>
          <div style="font-weight:600;margin-bottom:6px">Ảnh nhân viên</div>
          <input type="file" id="fPhoto" accept="image/*" onchange="loadPhoto(event)">
          <div style="font-size:12px;color:var(--muted);margin-top:4px">Chọn ảnh từ máy. Ảnh sẽ được thu nhỏ và lưu ngay trong trình duyệt.</div>
        </div>
      </div>
      <label>Mã nhân viên<input id="fCode" readonly="" style="background:#f3f4f6"></label>
      <label>Họ và tên *<input id="fName"></label>
      <label>Giới tính<select id="fGender"><option>Nam</option><option>Nữ</option></select></label>
      <label>Ngày sinh<input type="date" id="fDob"></label>
      <label>Phòng ban<select id="fDeptIn"></select></label>
      <label>Chức vụ<input id="fRole"></label>
      <label>Số điện thoại<input id="fPhone"></label>
      <label>Email<input id="fEmail" type="email"></label>
      <label>Ngày vào làm<input type="date" id="fStart"></label>
      <label>Lương cơ bản (VNĐ)<input type="number" id="fSalary" step="100000"></label>
      <label>Trạng thái<select id="fStatusIn"><option>Đang làm</option><option>Thử việc</option><option>Nghỉ phép</option><option>Đã nghỉ việc</option></select></label>
    </div>
    <div class="modal-foot">
      <button class="btn btn-light" onclick="closeModal('formModal')">Hủy</button>
      <button class="btn btn-primary" onclick="saveForm()">💾 Lưu</button>
    </div>
  </div>
</div>

<!-- Xem chi tiết -->
<div class="modal-bg" id="detailModal">
  <div class="modal">
    <div id="detailBody"></div>
    <div class="modal-foot">
      <button class="btn btn-light" onclick="closeModal('detailModal')">Đóng</button>
      <button class="btn btn-primary" id="detailEdit">✎ Sửa</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>



<input id="hrImport" type="file" accept=".json,application/json" hidden onchange="hrImportFile(this)">
<dialog id="hrLibrary" aria-label="Thư viện dữ liệu nhân sự">
  <div class="hr-library">
    <h2>Thư viện dữ liệu JSON server</h2>
    <p>Dữ liệu trên server chỉ được ghi khi có mã quản trị. Khôi phục sẽ thay thế dữ liệu trong trình duyệt sau xác nhận.</p>
    <label>Tên bản sao lưu <input id="hrDatasetName" maxlength="100" placeholder="Ví dụ: Nhân sự tháng 10"></label>
    <label>Mã quản trị <input id="hrBackupCode" type="password" autocomplete="off" placeholder="Mã lưu server"></label>
    <div class="hr-library-actions"><button type="button" class="btn btn-primary" onclick="hrStoreLibrary()">Lưu lên server</button><button type="button" class="btn btn-light" onclick="hrRefreshLibrary()">Làm mới</button><button type="button" class="btn btn-light" onclick="document.getElementById('hrLibrary').close()">Đóng</button></div>
    <div id="hrDatasetList" class="hr-dataset-list" aria-live="polite"></div>
  </div>
</dialog>
<x-demo.sidebar-menu-builder slug="quan-ly-nhan-su" />
<script src="{{ asset('demo-assets/quan-ly-nhan-su/js/menu-adapter.js') }}"></script>
<script id="savedData" type="application/json">{"savedAt":"2026-10-13T07:33:21.373Z","data":[{"code":"NV001","name":"Nguyễn Văn An","gender":"Nam","dob":"1982-03-15","dept":"Ban Giám đốc","role":"Giám đốc","phone":"0912345678","email":"an.nguyen@congty.vn","start":"2015-01-05","salary":45000000,"status":"Đang làm","photo":"data:image/jpeg;base64,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"},{"code":"NV002","name":"Trần Thị Bích Ngọc","gender":"Nữ","dob":"1988-07-22","dept":"Kế toán","role":"Kế toán trưởng","phone":"0903456789","email":"ngoc.tran@congty.vn","start":"2016-04-12","salary":28000000,"status":"Đang làm","photo":"data:image/jpeg;base64,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"},{"code":"NV003","name":"Lê Minh Tuấn","gender":"Nam","dob":"1990-11-02","dept":"Kinh doanh","role":"Trưởng phòng KD","phone":"0987654321","email":"tuan.le@congty.vn","start":"2017-06-01","salary":30000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/men/45.jpg"},{"code":"NV004","name":"Phạm Thu Hà","gender":"Nữ","dob":"1993-05-18","dept":"Nhân sự","role":"Chuyên viên tuyển dụng","phone":"0934567890","email":"ha.pham@congty.vn","start":"2019-09-16","salary":16000000,"status":"Nghỉ phép","photo":"https://randomuser.me/api/portraits/women/65.jpg"},{"code":"NV005","name":"Hoàng Đức Thắng","gender":"Nam","dob":"1995-12-09","dept":"IT","role":"Lập trình viên","phone":"0976543210","email":"thang.hoang@congty.vn","start":"2020-02-10","salary":22000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/men/51.jpg"},{"code":"NV006","name":"Vũ Ngọc Lan","gender":"Nữ","dob":"1996-08-30","dept":"Marketing","role":"Chuyên viên Marketing","phone":"0945678901","email":"lan.vu@congty.vn","start":"2021-03-01","salary":15000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/women/68.jpg"},{"code":"NV007","name":"Đặng Quốc Bảo","gender":"Nam","dob":"1991-01-25","dept":"Kho vận","role":"Thủ kho","phone":"0967890123","email":"bao.dang@congty.vn","start":"2018-07-20","salary":13000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/men/75.jpg"},{"code":"NV008","name":"Bùi Thị Mai","gender":"Nữ","dob":"1998-04-14","dept":"Kế toán","role":"Kế toán viên","phone":"0918765432","email":"mai.bui@congty.vn","start":"2022-05-09","salary":12000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/women/79.jpg"},{"code":"NV009","name":"Đỗ Hoàng Long","gender":"Nam","dob":"1999-10-03","dept":"Kinh doanh","role":"Nhân viên kinh doanh","phone":"0929876543","email":"long.do@congty.vn","start":"2024-08-01","salary":10000000,"status":"Thử việc","photo":"https://randomuser.me/api/portraits/men/22.jpg"},{"code":"NV010","name":"Ngô Thanh Hương","gender":"Nữ","dob":"1994-02-11","dept":"Nhân sự","role":"Trưởng phòng Nhân sự","phone":"0908123456","email":"huong.ngo@congty.vn","start":"2017-11-13","salary":25000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/women/26.jpg"},{"code":"NV011","name":"Phan Văn Khánh","gender":"Nam","dob":"1987-06-27","dept":"Kho vận","role":"Trưởng kho","phone":"0938234567","email":"khanh.phan@congty.vn","start":"2016-10-03","salary":20000000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/men/60.jpg"},{"code":"NV012","name":"Lý Thị Thảo","gender":"Nữ","dob":"2000-09-19","dept":"Marketing","role":"Thiết kế đồ họa","phone":"0948345678","email":"thao.ly@congty.vn","start":"2025-01-06","salary":11000000,"status":"Thử việc","photo":"https://randomuser.me/api/portraits/women/90.jpg"},{"code":"NV013","name":"Trịnh Công Sơn","gender":"Nam","dob":"1992-12-01","dept":"IT","role":"Quản trị hệ thống","phone":"0958456789","email":"son.trinh@congty.vn","start":"2019-04-22","salary":21000000,"status":"Đã nghỉ việc","photo":"https://randomuser.me/api/portraits/men/11.jpg"},{"code":"NV014","name":"Mai Phương Thảo","gender":"Nữ","dob":"1997-03-08","dept":"Kinh doanh","role":"Nhân viên kinh doanh","phone":"0968567890","email":"thao.mai@congty.vn","start":"2023-02-15","salary":12500000,"status":"Đang làm","photo":"https://randomuser.me/api/portraits/women/33.jpg"}]}</script>
@endsection
