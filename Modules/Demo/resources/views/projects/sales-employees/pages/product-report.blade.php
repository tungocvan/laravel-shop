<section class="page" id="page-sanpham">
        <div class="toolbar">
          <div class="seg" id="sPer"><button data-v="month" class="on">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button><button data-v="all">Tất cả</button></div>
          <span class="chip" id="sRange"></span>
          <select class="inp" id="sNv"></select>
          <button class="btn" onclick="exportSp()" data-icon="xls" style="margin-left:auto">Xuất Excel</button>
        </div>
        <div class="grid-a">
          <div class="card"><div class="card-h"><h3>Doanh số theo sản phẩm</h3></div><div class="card-b" id="sChart"></div></div>
          <div class="card"><div class="card-h"><h3>Theo nhóm hàng</h3></div><div class="card-b" id="sCat"></div></div>
        </div>
        <div class="card"><div class="card-h"><h3>Bảng nhân viên x sản phẩm</h3><span class="hint">số lượng bán, rê chuột để xem doanh số</span></div><div class="tbl-wrap" id="sMatrix"></div></div>
      </section>