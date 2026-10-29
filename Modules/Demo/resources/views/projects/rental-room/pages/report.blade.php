<section class="page" id="page-report">
        <div class="toolbar">
          <select class="inp" id="rpYear"></select>
          <div class="seg" id="rpView"><button data-v="month" class="on">Theo tháng</button><button data-v="room">Theo phòng</button></div>
          <div style="flex:1"></div>
          <button class="btn" onclick="exportReport()"><i data-ic="down"></i>Xuất Excel</button>
          <button class="btn pri" onclick="window.print()"><i data-ic="print"></i>In báo cáo</button>
        </div>
        <div class="print-h"><h2 id="rpPrintT">BÁO CÁO DOANH THU</h2><div id="rpPrintS"></div></div>
        <div class="rhero" id="rpHero"></div>
        <div class="card"><div class="card-h"><h3 id="rpTitle">Chi tiết</h3><span class="hint" id="rpHint"></span></div><div class="tbl-wrap" id="rpTable"></div></div>
      </section>