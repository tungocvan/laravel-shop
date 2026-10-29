<section class="page" id="page-meter">
        <div class="toolbar">
          <div class="kybar"><button class="btn" onclick="mtKy(-1)" title="Tháng trước">‹</button><input type="month" class="inp" id="mtKy" style="width:170px"><button class="btn" onclick="mtKy(1)" title="Tháng sau">›</button></div>
          <span class="chip" id="mtProg"></span>
          <div style="flex:1"></div>
          <button class="btn" onclick="exportMeter()"><i data-ic="down"></i>Xuất Excel</button>
          <button class="btn pri" onclick="lapHD(MKY)"><i data-ic="receipt"></i>Lập / cập nhật hóa đơn kỳ này</button>
        </div>
        <div class="note" id="mtNote" style="margin-bottom:14px"></div>
        <div class="card"><div class="tbl-wrap" id="mtTable"></div></div>
      </section>