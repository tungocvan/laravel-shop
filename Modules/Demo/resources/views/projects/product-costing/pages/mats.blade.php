<section class="page" id="page-mats">
<div class="toolbar">
<div class="search grow"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2" viewbox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4-4"></path></svg><input class="inp" id="mSearch" placeholder="Tìm theo mã, tên nguyên vật liệu, bao bì..."/></div>
<div class="seg" id="mKind"><button class="on" data-v="">Tất cả</button><button data-v="NVL">Nguyên vật liệu</button><button data-v="BB">Bao bì</button></div>
<button class="btn" onclick="openImport('mats')"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"></path><path d="M14 3v6h6M12 18v-6M9 15l3 3 3-3"></path></svg>Nhập từ Excel</button>
<button class="btn pri" onclick="openMat()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="2.4" viewbox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg>Thêm NVL / bao bì</button>
</div>
<div class="card"><div class="tbl-wrap" id="mTable"></div></div>
</section>