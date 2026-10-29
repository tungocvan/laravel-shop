<section class="page" id="page-tonghop">
        <div class="toolbar">
          <div class="seg" id="rPer"><button data-v="month" class="on">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button></div>
          <div class="rdate"><input type="date" class="inp" id="rFrom"><span>đến</span><input type="date" class="inp" id="rTo"></div>
          <select class="inp" id="rGrp"></select>
          <button class="btn" onclick="exportTH()" data-icon="xls">Xuất Excel</button>
          <button class="btn pri" onclick="window.print()" data-icon="print">In báo cáo</button>
        </div>
        <div class="print-h"><h2>TỔNG HỢP DOANH SỐ THEO NHÂN VIÊN</h2><div id="rPrint"></div></div>
        <div class="card" style="margin-bottom:18px;padding:16px 18px"><div class="msum" id="rSum" style="margin:0"></div></div>
        <div class="card"><div class="card-h"><h3>Bảng xếp hạng nhân viên</h3><span class="hint" id="rHint"></span></div><div class="tbl-wrap" id="rTable"></div></div>
      </section>