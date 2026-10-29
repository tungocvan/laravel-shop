<header class="top">
      <button class="burger" id="burger" aria-label="Mở menu"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
      <div><h1 id="pTitle">Dashboard</h1><div class="sub" id="pSub"></div></div>
      <div class="right">
        <span class="chip" id="today"></span>
        <button class="btn in noprint" onclick="go('in')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>Nhập</button>
        <button class="btn out noprint" onclick="go('out')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 12h14"></path></svg>Xuất</button>
        <div class="savebox noprint">
          <div class="savest" id="saveSt"></div>
          <button class="btn save" id="saveBtn" title="Lưu dữ liệu vào chính file này (Ctrl + S)" onclick="saveToFile()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg>Lưu</button>
        </div>
      </div>
    </header>
