<section class="page" id="page-tenants">
        <div class="toolbar">
          <div class="seg" id="tnTab"><button data-v="dang" class="on">Đang thuê</button><button data-v="exp">Sắp hết hạn</button><button data-v="tra">Đã trả phòng</button><button data-v="all">Tất cả</button></div>
          <div class="search grow"><i data-ic="search"></i><input class="inp" id="tnSearch" placeholder="Tìm tên, số điện thoại, CCCD, phòng..."></div>
          <button class="btn" onclick="exportTenants()"><i data-ic="down"></i>Xuất Excel</button>
          <button class="btn pri" onclick="openContract()"><i data-ic="plus"></i>Thêm người thuê</button>
        </div>
        <div class="card"><div class="tbl-wrap" id="tnTable"></div></div>
      </section>