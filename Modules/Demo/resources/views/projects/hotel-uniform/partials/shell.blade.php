
<div class="app">
  <!-- ========== BẢNG ĐIỀU KHIỂN BÊN TRÁI ========== -->
  @include('demo::components.sidebar', ['brandView' => 'demo::projects.hotel-uniform.partials.sidebar-brand', 'navView' => 'demo::projects.hotel-uniform.partials.sidebar-nav', 'cardView' => 'demo::projects.hotel-uniform.partials.sidebar-card', 'footerView' => 'demo::projects.hotel-uniform.partials.sidebar-footer'])
  <div class="mask" id="mask"></div>

  <!-- ========== NỘI DUNG ========== -->
  <main class="main">
    <header class="top">
      <button class="burger" id="burger" aria-label="Mở menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
      <div><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
      <div class="right">
        <span class="chip" id="today"></span>
        <button class="btn in noprint" onclick="go('nhap')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg><span class="t">Nhập kho</span></button><button class="btn pri noprint" onclick="go('cap')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12H4M14 6l6 6-6 6"/></svg><span class="t">Cấp phát</span></button>
        <button class="btn xl noprint" onclick="exportAll()" title="Xuất toàn bộ các bảng ra file Excel .xlsx"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"></path><path d="M14 3v6h6M9 12l4 5M13 12l-4 5"></path></svg><span class="t">Xuất Excel</span></button>
        <div class="savebox noprint">
          <div class="savest" id="saveSt"></div>
          <button class="btn save" id="saveBtn" title="Lưu đè dữ liệu vào chính file này (Ctrl + S)" onclick="saveToFile()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg><span class="t">Lưu đè dữ liệu</span></button>
        </div>
      </div>
    </header>
    <div class="content">
      <!-- DASHBOARD -->
      <section class="page" id="page-dashboard">
        <div class="toolbar">
          <div class="seg" id="dPeriod"><button data-v="month" class="on">Tháng này</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button><button data-v="all">Tất cả</button></div>
          <span class="chip" id="dRange"></span>
        </div>
        <div class="hero" id="dHero"></div>
        <div class="kc-grid" id="kpis"></div>
        <div class="grid-a">
          <div class="card">
            <div class="card-h"><h3>Chi phí mua và giá trị cấp phát 12 tháng</h3><div class="r legend"><span><i style="background:var(--cd-sang)"></i>Nhập kho (mua)</span><span><i style="background:var(--chu-dao)"></i>Cấp phát</span><span><i style="background:#f0663a;height:3px;vertical-align:3px"></i>Số cái cấp</span></div></div>
            <div class="card-b"><div class="msum" id="mSum"></div><div id="chMonth"></div></div>
          </div>
          <div class="card">
            <div class="card-h"><h3>Cấp phát theo bộ phận</h3><span class="hint" id="donutHint"></span></div>
            <div class="card-b" id="chBp"></div>
          </div>
        </div>
        <div class="card" style="margin-bottom:18px"><div class="card-h"><h3>Tồn kho theo loại và size</h3><div class="r legend"><span><i style="background:var(--inbg);border:1px solid var(--in)"></i>Đủ</span><span><i style="background:var(--warnbg);border:1px solid #e6a100"></i>Sắp hết</span><span><i style="background:var(--dangerbg);border:1px solid var(--danger)"></i>Hết</span><button class="btn sm" onclick="go('tonkho')">Chi tiết</button></div></div><div class="card-b" id="heat"></div></div>
        <div class="grid-b">
          <div class="card"><div class="card-h"><h3>Đến hạn cấp mới</h3><span class="cnt-b" id="dueCnt"></span><div class="r"><button class="btn sm" onclick="go('canhbao')">Xem hết</button></div></div><div class="card-b" id="dueList"></div></div>
          <div class="card"><div class="card-h"><h3>Nghỉ việc chưa hoàn trả</h3><span class="cnt-b" id="owCnt"></span></div><div class="card-b" id="owList"></div></div>
          <div class="card"><div class="card-h"><h3>Phiếu gần đây</h3><div class="r"><button class="btn sm" onclick="go('lichsu')">Lịch sử</button></div></div><div class="card-b" id="recent"></div></div>
        </div>
      </section>

      <section class="page" id="page-canhbao"></section>
      <section class="page" id="page-nhap"></section>
      <section class="page" id="page-cap"></section>
      <section class="page" id="page-thu"></section>

      <!-- LỊCH SỬ -->
      <section class="page" id="page-lichsu">
        <div class="toolbar">
          <div class="search grow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg><input class="inp" id="hQ" placeholder="Tìm số phiếu, nhân viên, đồng phục, nhà cung cấp..."></div>
          <select class="inp" id="hType"><option value="">Mọi loại phiếu</option><option value="nhap">Nhập kho</option><option value="cap">Cấp phát</option><option value="thu">Thu hồi</option></select>
          <select class="inp" id="hBp"></select>
          <input type="date" class="inp" id="hFrom"><span class="muted">đến</span><input type="date" class="inp" id="hTo">
          <button class="btn" onclick="exportHist()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M9 12l4 5M13 12l-4 5"/></svg>Xuất Excel</button>
        </div>
        <div class="card"><div class="tbl-wrap" id="hTable"></div></div>
      </section>

      <section class="page" id="page-tonkho"></section>
      <section class="page" id="page-dongphuc"></section>
      <section class="page" id="page-nhanvien"></section>
      <section class="page" id="page-dinhmuc"></section>

      <!-- BÁO CÁO -->
      <section class="page" id="page-baocao">
        <div class="toolbar">
          <div class="seg" id="rQuick"><button data-v="month">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button></div>
          <input type="date" class="inp" id="rFrom"><span class="muted">đến</span><input type="date" class="inp" id="rTo">
          <button class="btn" onclick="exportRep()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M9 12l4 5M13 12l-4 5"/></svg>Xuất Excel</button>
          <button class="btn pri" onclick="window.print()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/></svg>In báo cáo</button>
        </div>
        <div class="print-h"><h2>BÁO CÁO ĐỒNG PHỤC NHÂN VIÊN</h2><div id="rPrint"></div></div>
        <div class="kc-grid" id="rKpi"></div>
        <div class="card" style="margin-bottom:18px"><div class="card-h"><h3>Theo bộ phận</h3><span class="hint" id="rHint"></span></div><div class="tbl-wrap" id="rBp"></div></div>
        <div class="card"><div class="card-h"><h3>Nhập, cấp, thu hồi, tồn theo từng loại đồng phục</h3></div><div class="tbl-wrap" id="rItem"></div></div>
      </section>

      <section class="page" id="page-settings"></section>

      <!-- HƯỚNG DẪN -->
      <section class="page" id="page-huongdan">
        <div class="card guide">
          <h2>Quản lý đồng phục nhân viên khách sạn</h2>
          <p class="muted">Theo dõi kho đồng phục theo từng size, cấp phát cho nhân viên theo định mức từng vị trí, chu kỳ thay mới, thu hồi khi nghỉ việc, đổi size, báo hỏng mất và khấu trừ. Chạy ngay trong trình duyệt, không cần cài đặt, không cần mạng.</p>
          <h4>Các bước dùng</h4>
          <ol>
            <li><b>Danh mục đồng phục</b>: khai báo từng loại (áo vest lễ tân, áo buồng phòng, áo bếp...), bộ phận dùng, kiểu size, các size có, đơn giá, chu kỳ thay mới (tháng) và mức tồn tối thiểu mỗi size.</li>
            <li><b>Nhân viên</b>: nhập danh sách theo bộ phận, vị trí, size áo, size quần, size giày. Có thể nhập nhanh từ file Excel.</li>
            <li><b>Định mức theo vị trí</b>: mỗi vị trí được cấp những gì, bao nhiêu cái (VD lễ tân 2 vest, 2 váy, 3 sơ mi).</li>
            <li><b>Nhập kho</b> khi mua đồng phục từ nhà cung cấp. <b>Cấp phát</b>: chọn nhân viên, bấm <b>Điền theo định mức</b>, phần mềm tự lấy đúng size của người đó và kiểm tra tồn kho. In <b>biên bản bàn giao</b> để nhân viên ký nhận.</li>
            <li><b>Thu hồi</b> khi nghỉ việc, đổi size, hỏng hoặc mất: ghi tình trạng (dùng lại được thì cộng lại vào kho) và số tiền khấu trừ nếu có. Trang <b>Đến hạn &amp; chưa trả</b> nhắc ai sắp đến hạn cấp mới và ai nghỉ việc chưa hoàn trả.</li>
          </ol>
          <h4>Lưu dữ liệu và xuất Excel</h4>
          <ul>
            <li>Bấm <b>Lưu đè dữ liệu</b> (hoặc Ctrl + S) để ghi dữ liệu vào chính file này. Lần đầu trình duyệt hỏi chỗ lưu: chọn đúng file đang mở để ghi đè. Trên điện thoại, máy sẽ tải về bản mới, hãy dùng file vừa tải.</li>
            <li>Mỗi lần sửa, dữ liệu được giữ tạm trong trình duyệt. Lỡ đóng file chưa lưu, mở lại sẽ được hỏi có khôi phục không.</li>
            <li>Bấm <b>Xuất Excel</b> để tải file .xlsx gồm danh mục, nhân viên, định mức, tồn kho theo size, lịch sử phiếu, đến hạn cấp mới.</li>
          </ul>
          <div class="note"><b>Lưu ý:</b> dữ liệu đang có là <b>dữ liệu mẫu</b> để xem thử. Trước khi dùng thật, vào <b>Cài đặt &amp; dữ liệu</b> bấm <b>Xóa dữ liệu mẫu</b> (danh mục đồng phục và định mức vẫn giữ lại để sửa tiếp).</div>
          <p class="gift">File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel.</p>
        </div>
      </section>

    </div>
  </main>
</div>

<div class="ov" id="ov"><div class="modal" id="modal"></div></div>
<div id="toasts"></div>
<div id="tip"></div>
<div id="printArea"></div>

<!-- DỮ LIỆU LƯU TRONG FILE: nút "Lưu đè dữ liệu" ghi đè phần này -->




<script id="du-lieu" type="application/json">null</script>

