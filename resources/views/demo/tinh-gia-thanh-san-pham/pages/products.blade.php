<section class="page" id="page-products">
<div class="toolbar">
<div class="search grow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg><input class="inp" id="pSearch" placeholder="Tìm theo mã, tên sản phẩm..."/></div>
<select class="inp" id="pGroup"></select>
<button class="btn" onclick="openImport('products')"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"></path><path d="M14 3v6h6M12 18v-6M9 15l3 3 3-3"></path></svg>Nhập từ Excel</button>
<button class="btn pri" onclick="openProduct()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2.4" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>Thêm sản phẩm</button>
</div>
<div class="card"><div class="tbl-wrap" id="pTable"></div></div>
</section>