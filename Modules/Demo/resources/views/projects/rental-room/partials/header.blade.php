<header class="top">
      <button class="burger" id="burger" aria-label="Mở menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
      <div class="ttl"><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
      <div class="right">
        <span class="chip" id="today"></span>
        <button class="btn hide-m noprint" onclick="go('meter')"><i data-ic="bolt"></i>Ghi điện nước</button>
        <button class="btn noprint" onclick="exportAll()" title="Xuất toàn bộ dữ liệu ra file Excel .xlsx"><i data-ic="down"></i>Xuất Excel</button>
        <div class="savebox noprint">
          <div class="savest" id="saveSt"></div>
          <button class="btn save" id="saveBtn" title="Ghi dữ liệu vào chính file này (Ctrl + S)" onclick="saveToFile()"><i data-ic="save"></i><span>Lưu đè dữ liệu</span></button>
        </div>
      </div>
    </header>