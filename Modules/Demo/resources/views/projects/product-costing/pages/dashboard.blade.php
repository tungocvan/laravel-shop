<section class="page" id="page-dashboard">
<div class="toolbar">
<label class="f" style="margin:0">Kỳ tính giá thành</label>
<select class="inp" id="dPer" style="min-width:170px"></select>
<span class="chip" id="dCmp"></span>
</div>
<div class="hero" id="dHero"></div>
<div class="kc-grid" id="kpis"></div>
<div class="grid-a">
<div class="card"><div class="card-h"><h3>Giá thành đơn vị theo sản phẩm</h3><span class="hint">cơ cấu 4 khoản trên 1 đơn vị sản phẩm, vạch đen là giá bán</span><div class="r legend" id="kmLeg1"></div></div><div class="card-b" id="unitBars"></div></div>
<div class="card"><div class="card-h"><h3>Cơ cấu giá thành</h3><span class="hint" id="donHint"></span></div><div class="card-b" id="chDonut"></div></div>
</div>
<div class="grid-c">
<div class="card"><div class="card-h"><h3>Xu hướng tổng giá thành 12 tháng</h3><div class="r legend" id="kmLeg2"></div></div><div class="card-b"><div class="msum" id="mSum"></div><div id="chTrend"></div></div></div>
<div class="card"><div class="card-h"><h3>Giá thành đơn vị qua các tháng</h3><div class="r"><select class="inp" id="dProd" style="width:auto;padding:6px 10px"></select></div></div><div class="card-b"><div class="msum" id="pSum"></div><div id="chProd"></div></div></div>
</div>
<div class="grid-b">
<div class="card"><div class="card-h"><h3>Biến động giá thành đơn vị</h3><span class="hint">so với tháng trước</span></div><div class="card-b" id="dVar"></div></div>
<div class="card"><div class="card-h"><h3>Biên lãi gộp theo sản phẩm</h3><span class="hint">theo giá bán khai báo</span></div><div class="card-b" id="dMargin"></div></div>
<div class="card"><div class="card-h"><h3>Nguyên liệu chiếm chi phí lớn nhất</h3><div class="r"><button class="btn sm" onclick="go('mats')">Danh mục</button></div></div><div class="card-b" id="dMat"></div></div>
</div>
</section>