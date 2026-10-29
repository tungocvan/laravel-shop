<section class="page" id="page-trend">
<div class="toolbar">
<div class="seg" id="tMode"><button class="on" data-v="unit">Giá thành đơn vị</button><button data-v="total">Tổng giá thành</button><button data-v="qty">Sản lượng</button></div>
<select class="inp" id="tKm"><option value="">Cả 4 khoản mục</option><option value="nvl">Chỉ nguyên vật liệu</option><option value="bb">Chỉ bao bì</option><option value="labor">Chỉ nhân công trực tiếp</option><option value="oh">Chỉ sản xuất chung</option></select>
<span style="flex:1"></span>
<button class="btn" onclick="exportTrend()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Xuất Excel</button>
<button class="btn pri" onclick="window.print()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect height="7" width="12" x="6" y="14"></rect></svg>In</button>
</div>
<div class="print-h"><h2>GIÁ THÀNH SẢN PHẨM QUA CÁC THÁNG</h2><div id="tPrintLbl"></div></div>
<div class="card"><div class="card-h"><h3 id="tTitle"></h3><span class="hint">ô đỏ: tăng trên 3% so với tháng trước · ô xanh: giảm trên 3%</span></div><div class="tbl-wrap" id="tTable"></div></div>
</section>