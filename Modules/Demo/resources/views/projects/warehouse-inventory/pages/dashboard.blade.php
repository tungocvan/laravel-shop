<section class="page" id="page-dashboard">
        <div class="toolbar">
          <div class="seg" id="dPeriod">
            <button data-v="month" class="on">Tháng này</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button><button data-v="all">Tất cả</button>
          </div>
          <span class="chip" id="dRange"></span>
        </div>
        <div class="hero" id="dHero"></div>
        <div class="kc-grid" id="kpis"></div>
        <div class="grid-a">
          <div class="card">
            <div class="card-h"><h3>Nhập, xuất và lãi gộp 12 tháng gần nhất</h3><div class="r legend"><span><i style="background:var(--in)"></i>Nhập kho</span><span><i style="background:var(--out)"></i>Xuất kho</span><span><i style="background:#8b5cf6;height:3px;vertical-align:3px"></i>Lãi gộp</span></div></div>
            <div class="card-b"><div class="msum" id="mSum"></div><div id="chMonth"></div></div>
          </div>
          <div class="card">
            <div class="card-h"><h3>Cơ cấu tồn kho theo nhóm</h3></div>
            <div class="card-b" id="chCat"></div>
          </div>
        </div>
        <div class="grid-b">
          <div class="card"><div class="card-h"><h3>Top 5 xuất nhiều nhất</h3><span class="hint" id="topHint"></span></div><div class="card-b" id="top5"></div></div>
          <div class="card"><div class="card-h"><h3>Cảnh báo tồn kho</h3><span class="cnt-b" id="lowCnt"></span><div class="r"><button class="btn sm" onclick="go('products')">Xem tất cả</button></div></div><div class="card-b" id="lowList"></div></div>
          <div class="card"><div class="card-h"><h3>Phiếu gần đây</h3><div class="r"><button class="btn sm" onclick="go('history')">Lịch sử</button></div></div><div class="card-b" id="recent"></div></div>
        </div>
      </section>
