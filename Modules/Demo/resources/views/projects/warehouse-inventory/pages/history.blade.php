<section class="page" id="page-history">
        <div class="toolbar">
          <div class="search grow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg><input class="inp" id="hSearch" placeholder="Tìm số phiếu, sản phẩm, đối tác..."></div>
          <select class="inp" id="hType"><option value="">Nhập &amp; Xuất</option><option value="in">Chỉ phiếu nhập</option><option value="out">Chỉ phiếu xuất</option></select>
          <input type="date" class="inp" id="hFrom"> <input type="date" class="inp" id="hTo">
          <button class="btn" onclick="exportHistory()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Xuất Excel (CSV)</button>
        </div>
        <div class="card"><div class="tbl-wrap" id="hTable"></div></div>
      </section>
