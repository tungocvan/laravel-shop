<section class="page" id="page-report">
<div class="toolbar">
<label class="f" style="margin:0">Kỳ</label>
<select class="inp" id="rPer" style="min-width:170px"></select>
<div class="seg" id="rSort"><button class="on" data-v="code">Theo mã</button><button data-v="total">Tổng giá thành lớn</button><button data-v="margin">Biên lãi thấp</button></div>
<span style="flex:1"></span>
<button class="btn" onclick="exportReport()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Xuất Excel</button>
<button class="btn pri" onclick="window.print()"><svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewbox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect height="7" width="12" x="6" y="14"></rect></svg>In bảng</button>
</div>
<div class="print-h"><h2>BẢNG TÍNH GIÁ THÀNH SẢN PHẨM</h2><div id="rPrintLbl"></div></div>
<div class="kc-grid" id="rKpi"></div>
<div class="card"><div class="card-h"><h3 id="rTitle">Bảng tính giá thành</h3><span class="hint" id="rHint"></span></div><div class="tbl-wrap" id="rTable"></div></div>
</section>