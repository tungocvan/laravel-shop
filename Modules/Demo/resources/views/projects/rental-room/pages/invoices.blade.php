<section class="page" id="page-invoices">
        <div class="mstat" id="ivStat"></div>
        <div class="toolbar">
          <div class="search grow"><i data-ic="search"></i><input class="inp" id="ivSearch" placeholder="Tìm số hóa đơn, phòng, người thuê..."></div>
          <select class="inp" id="ivKy"></select>
          <select class="inp" id="ivSt"><option value="">Mọi trạng thái</option><option value="con">Còn phải thu</option><option value="qua">Quá hạn</option><option value="mot">Thu một phần</option><option value="chua">Chưa thu</option><option value="du">Đã thu đủ</option></select>
          <button class="btn" onclick="printKy()"><i data-ic="print"></i>In cả kỳ</button>
          <button class="btn" onclick="exportInv()"><i data-ic="down"></i>Xuất Excel</button>
        </div>
        <div class="card"><div class="tbl-wrap" id="ivTable"></div></div>
      </section>