<section class="page" id="page-rooms">
        <div class="mstat" id="roomStat"></div>
        <div class="toolbar">
          <div class="search grow"><i data-ic="search"></i><input class="inp" id="rmSearch" placeholder="Tìm mã phòng, tên người thuê, số điện thoại..."></div>
          <select class="inp" id="rmDay"></select>
          <select class="inp" id="rmSt"><option value="">Mọi trạng thái</option><option value="thue">Đang thuê</option><option value="trong">Phòng trống</option><option value="sua">Đang sửa chữa</option><option value="no">Còn nợ tiền</option></select>
          <div class="seg" id="rmView"><button data-v="map" class="on">Sơ đồ</button><button data-v="table">Bảng</button></div>
          <button class="btn" onclick="openImport()"><i data-ic="up"></i>Nhập danh sách phòng</button>
          <button class="btn pri" onclick="openRoomForm()"><i data-ic="plus"></i>Thêm phòng</button>
        </div>
        <div class="rlegend"><span><i style="background:#6A1B9A"></i>Đang thuê</span><span><i style="background:#16a36a"></i>Phòng trống</span><span><i style="background:#e6a100"></i>Đang sửa chữa</span><span><i style="background:#e0384a;border-radius:50%"></i>Nợ quá hạn</span><span><i style="background:#e6a100;border-radius:50%"></i>Chờ đóng tiền tháng này</span></div>
        <div id="rmList"></div>
      </section>