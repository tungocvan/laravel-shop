<section class="page" id="page-tonghop">
<div class="toolbar">
<div class="seg" id="rPer"><button data-v="week">Tuần này</button><button class="on" data-v="month">Tháng này</button><button data-v="prev">Tháng trước</button><button data-v="quarter">Quý này</button><button data-v="all">Tất cả</button></div>
<div class="rdate"><input class="inp" id="rFrom" type="date"/><span>đến</span><input class="inp" id="rTo" type="date"/></div>
<select class="inp" id="rDept"></select>
<button class="btn" data-icon="xls" onclick="exportTH()">Xuất Excel</button>
<button class="btn pri" data-icon="print" onclick="window.print()">In báo cáo</button>
</div>
<div class="print-h"><h2>TỔNG HỢP BÁO CÁO CÔNG VIỆC</h2><div id="rPrint"></div></div>
<div class="card" style="margin-bottom:18px;padding:16px 18px"><div class="msum" id="rSum" style="margin:0"></div></div>
<div class="card"><div class="card-h"><h3>Tổng hợp theo nhân viên</h3><span class="hint" id="rHint"></span></div><div class="tbl-wrap" id="rTable"></div></div>
</section>