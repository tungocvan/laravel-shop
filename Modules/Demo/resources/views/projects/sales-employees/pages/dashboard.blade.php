<section class="page" id="page-dashboard">
        <div class="toolbar">
          <div class="seg" id="dPer"><button data-v="month" class="on">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="year">Năm nay</button><button data-v="all">Tất cả</button></div>
          <span class="chip" id="dRange"></span>
          <select class="inp" id="dGrp" style="margin-left:auto"></select>
        </div>
        <div class="hero" id="dHero"></div>
        <div class="kc-grid" id="dKpi"></div>
        <div class="grid-a">
          <div class="card">
            <div class="card-h"><h3>Doanh số từng nhân viên so với chỉ tiêu</h3><span class="hint" id="nvHint"></span><div class="r legend"><span><i style="background:var(--chu-dao)"></i>Đạt chỉ tiêu</span><span><i style="background:#f4a3a3"></i>Chưa đạt</span><span><i style="background:#1b2333;height:3px;vertical-align:3px"></i>Chỉ tiêu</span></div></div>
            <div class="card-b" id="dByNv"></div>
          </div>
          <div class="card">
            <div class="card-h"><h3>Cơ cấu theo nhóm hàng</h3></div>
            <div class="card-b" id="dCat"></div>
          </div>
        </div>
        <div class="card" style="margin-bottom:18px">
          <div class="card-h"><h3>Xu hướng doanh số 12 tháng</h3><span class="hint">chia theo nhóm / chi nhánh</span><div class="r legend" id="trLeg"></div></div>
          <div class="card-b"><div class="msum" id="trSum"></div><div id="dTrend"></div></div>
        </div>
        <div class="grid-b">
          <div class="card"><div class="card-h"><h3>Top nhân viên</h3><span class="hint" id="topHint"></span></div><div class="card-b" id="dTopNv"></div></div>
          <div class="card"><div class="card-h"><h3>Top sản phẩm</h3><span class="hint">theo doanh số</span></div><div class="card-b" id="dTopSp"></div></div>
          <div class="card"><div class="card-h"><h3>Cần hỗ trợ</h3><span class="cnt-b" id="dLowN"></span><span class="hint">dưới 80% chỉ tiêu</span></div><div class="card-b" id="dLow"></div></div>
        </div>
      </section>