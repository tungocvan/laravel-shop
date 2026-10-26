<section class="page" id="page-report">
        <div class="toolbar">
          <div class="seg" id="rQuick"><button data-v="month">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button></div>
          <div class="rdate"><input type="date" class="inp" id="rFrom"><span>đến</span><input type="date" class="inp" id="rTo"></div>
          <select class="inp" id="rCat"></select>
          <div class="search" style="min-width:190px;flex:1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg><input class="inp" id="rSearch" placeholder="Tìm mã, tên sản phẩm..."></div>
          <button class="btn" onclick="exportReport()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Xuất Excel</button>
          <button class="btn pri" onclick="window.print()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="7"></rect></svg>In báo cáo</button>
        </div>
        <div class="print-h"><h2>BÁO CÁO XUẤT NHẬP TỒN</h2><div id="rPrintRange"></div></div>
        <div class="rhero" id="rHero"></div>
        <div class="card"><div class="card-h"><h3>Chi tiết từng mặt hàng</h3><span class="hint" id="rCount"></span><div class="r noprint"><div class="seg" id="rSort"><button data-v="code" class="on">Theo mã</button><button data-v="out">Xuất nhiều</button><button data-v="val">Tồn giá trị cao</button></div></div></div><div class="tbl-wrap" id="rTable"></div></div>
      </section>
