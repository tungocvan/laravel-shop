<header class="top">
      <button class="burger" id="burger" aria-label="Mở menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
      <div><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
      <div class="right">
        <span class="chip" id="today"></span>
        <button class="btn pri qa noprint" onclick="crudForm('ban')" data-icon="plus">Thêm đơn bán</button>
        <button class="btn noprint" onclick="exportAll()" data-icon="xls" title="Xuất mọi bảng ra file Excel .xlsx"><span class="lbl">Xuất Excel</span></button>
        <div class="savebox noprint">
          <div class="savest" id="saveSt"></div>
          <button class="btn save" id="saveBtn" title="Lưu dữ liệu vào trình duyệt (Ctrl + S)" onclick="saveToFile()" data-icon="save"><span class="lbl">Lưu dữ liệu</span></button>
        </div>
      </div>
    </header>