<section class="page" id="page-dashboard">
        <div class="toolbar">
          <div class="seg" id="dPeriod"><button data-v="month" class="on">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button></div>
          <span class="chip" id="dRange"></span>
        </div>
        <div class="hero" id="dHero"></div>
        <div class="kc-grid" id="kpis"></div>
        <div class="grid-a">
          <div class="card">
            <div class="card-h"><h3>Doanh thu 12 tháng gần nhất</h3><div class="r legend"><span><i style="background:#6A1B9A"></i>Tiền phòng</span><span><i style="background:#BA68C8"></i>Điện nước</span><span><i style="background:#E1BEE7"></i>Dịch vụ &amp; khác</span><span><i style="background:#16a36a;height:3px;vertical-align:3px"></i>Tiền đã thu</span></div></div>
            <div class="card-b"><div class="msum" id="mSum"></div><div id="chMonth"></div></div>
          </div>
          <div class="card">
            <div class="card-h"><h3>Cơ cấu khoản thu</h3><span class="hint" id="donutHint"></span></div>
            <div class="card-b" id="chCat"></div>
          </div>
        </div>
        <div class="grid-b">
          <div class="card"><div class="card-h"><h3>Phòng còn nợ tiền</h3><span class="cnt-b" id="debtCnt"></span><div class="r"><button class="btn sm" onclick="go('debts')">Xem tất cả</button></div></div><div class="card-b" id="debtList"></div></div>
          <div class="card"><div class="card-h"><h3>Hợp đồng sắp hết hạn</h3><span class="cnt-b w" id="expCnt"></span><div class="r"><button class="btn sm" onclick="TTAB='exp';go('tenants')">Xem tất cả</button></div></div><div class="card-b" id="expList"></div></div>
          <div class="card"><div class="card-h"><h3>Việc cần làm</h3><span class="hint" id="todoHint"></span></div><div class="card-b" id="todo"></div></div>
        </div>
      </section>