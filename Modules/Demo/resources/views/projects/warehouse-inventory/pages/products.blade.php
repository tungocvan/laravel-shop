<section class="page" id="page-products">
        <div class="toolbar">
          <div class="search grow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg><input class="inp" id="pSearch" placeholder="Tìm theo mã, tên sản phẩm..."></div>
          <select class="inp" id="pCat"></select>
          <select class="inp" id="pStatus"><option value="">Mọi trạng thái</option><option value="ok">Còn hàng</option><option value="low">Sắp hết</option><option value="zero">Hết hàng</option></select>
          <div class="seg" id="pView"><button data-v="grid" class="on">Thẻ ảnh</button><button data-v="table">Bảng</button></div>
          <button class="btn" onclick="openImport()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"></path><path d="M14 3v6h6M12 18v-6M9 15l3 3 3-3"></path></svg>Nhập danh sách sản phẩm</button>
          <button class="btn pri" onclick="openProduct()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"></path></svg>Thêm sản phẩm</button>
        </div>
        <div id="pList"></div>
      </section>
