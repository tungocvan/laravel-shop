<section class="page" id="page-debts">
        <div class="mstat" id="dbStat"></div>
        <div class="card" style="margin-bottom:18px">
          <div class="card-h"><h3>Công nợ theo phòng</h3><span class="hint">Cộng dồn các hóa đơn chưa thu đủ, cũ nhất xếp trước</span><div class="r"><button class="btn sm" onclick="exportDebt()"><i data-ic="down"></i>Xuất Excel</button></div></div>
          <div class="tbl-wrap" id="dbTable"></div>
        </div>
        <div class="card">
          <div class="card-h"><h3>Sổ thu tiền</h3><span class="hint">Mỗi dòng là một phiếu thu</span></div>
          <div class="toolbar" style="padding:8px 18px 0">
            <div class="search grow"><i data-ic="search"></i><input class="inp" id="pySearch" placeholder="Tìm số phiếu, phòng, người nộp..."></div>
            <select class="inp" id="pyHt"><option value="">Mọi hình thức</option><option>Tiền mặt</option><option>Chuyển khoản</option><option>Trừ tiền cọc</option></select>
            <input type="date" class="inp" id="pyFrom"><input type="date" class="inp" id="pyTo">
            <button class="btn" onclick="exportPay()"><i data-ic="down"></i>Xuất Excel</button>
          </div>
          <div class="tbl-wrap" id="pyTable"></div>
        </div>
      </section>