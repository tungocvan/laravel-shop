<section class="page" id="page-thang">
        <div class="toolbar">
          <div class="rdate"><span>Năm</span><select class="inp" id="tYear"></select></div>
          <div class="seg" id="tMode"><button data-v="ds" class="on">Doanh số</button><button data-v="pct">% chỉ tiêu</button><button data-v="don">Số đơn</button></div>
          <select class="inp" id="tGrp"></select>
          <button class="btn" onclick="exportThang()" data-icon="xls" style="margin-left:auto">Xuất Excel</button>
          <button class="btn pri" onclick="window.print()" data-icon="print">In</button>
        </div>
        <div class="print-h"><h2>DOANH SỐ NHÂN VIÊN THEO THÁNG</h2><div id="tPrint"></div></div>
        <div class="card"><div class="card-h"><h3 id="tTitle">Doanh số theo tháng</h3><span class="hint">ô càng đậm doanh số càng cao</span></div><div class="tbl-wrap" id="tTable"></div></div>
      </section>