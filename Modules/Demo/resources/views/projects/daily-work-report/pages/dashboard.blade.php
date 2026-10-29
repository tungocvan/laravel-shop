<section class="page" id="page-dashboard">
<div class="toolbar">
<div class="rdate"><span>Xem ngày</span><input class="inp" id="dDay" type="date"/></div>
<div class="seg" id="dPer"><button class="on" data-v="week">Tuần này</button><button data-v="month">Tháng này</button><button data-v="quarter">Quý này</button><button data-v="all">Tất cả</button></div>
<span class="chip" id="dRange"></span>
<select class="inp" id="dDept" style="margin-left:auto"></select>
</div>
<div class="hero" id="dHero"></div>
<div class="kc-grid" id="dKpi"></div>
<div class="card" style="margin-bottom:18px">
<div class="card-h"><h3>Lịch nộp báo cáo 14 ngày gần nhất</h3><span class="hint">số trong ô là giờ làm, bấm vào ô để mở báo cáo</span><div class="r legend"><span><i style="background:var(--chu-dao)"></i>Đã nộp</span><span><i style="background:#fde2e5"></i>Chưa nộp</span><span><i style="background:#eef1f6"></i>Chủ nhật</span></div></div>
<div class="tbl-wrap" id="dCal"></div>
</div>
<div class="grid-a">
<div class="card">
<div class="card-h"><h3>Đầu việc theo nhân viên</h3><span class="hint" id="byNvHint"></span><div class="r legend"><span><i style="background:#16a36a"></i>Hoàn thành</span><span><i style="background:var(--chu-dao)"></i>Đang làm</span><span><i style="background:#c3cbda"></i>Chưa làm</span></div></div>
<div class="card-b" id="dByNv"></div>
</div>
<div class="card">
<div class="card-h"><h3>Giờ làm theo bộ phận</h3><span class="hint" id="dDeptHint"></span></div>
<div class="card-b" id="dHours"></div>
</div>
</div>
<div class="grid-b">
<div class="card"><div class="card-h"><h3>Chưa nộp báo cáo</h3><span class="cnt-b" id="dMissN"></span><span class="hint" id="dMissD"></span></div><div class="card-b" id="dMiss"></div></div>
<div class="card"><div class="card-h"><h3>Việc quá hạn</h3><span class="cnt-b" id="dLateN"></span></div><div class="card-b" id="dLate"></div></div>
<div class="card"><div class="card-h"><h3>Vướng mắc cần hỗ trợ</h3><div class="r"><button class="btn sm" onclick="go('ngay')">Xem tất cả</button></div></div><div class="card-b" id="dIssue"></div></div>
</div>
</section>