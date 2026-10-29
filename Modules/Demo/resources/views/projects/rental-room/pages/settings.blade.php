<section class="page" id="page-settings">
        <div class="grid-a" style="grid-template-columns:1fr 1fr">
          <div class="card" style="padding:20px">
            <h4 style="font-size:15px;margin-bottom:4px">Đơn giá điện, nước và dịch vụ</h4>
            <p style="color:var(--muted);font-size:13px;margin-bottom:14px">Đổi đơn giá chỉ áp dụng cho hóa đơn lập sau. Hóa đơn đã lập giữ nguyên giá cũ.</p>
            <div class="row2"><div class="fg"><label class="f">Giá điện (₫/kWh)</label><input class="inp" id="cGiaDien" type="number" min="0"></div><div class="fg"><label class="f">Cách tính tiền nước</label><select class="inp" id="cNuocKieu"><option value="khoi">Theo đồng hồ (₫/m³)</option><option value="nguoi">Khoán theo người (₫/người/tháng)</option></select></div></div>
            <div class="row2"><div class="fg"><label class="f">Giá nước theo khối (₫/m³)</label><input class="inp" id="cGiaNuoc" type="number" min="0"></div><div class="fg"><label class="f">Giá nước khoán (₫/người/tháng)</label><input class="inp" id="cGiaNuocNguoi" type="number" min="0"></div></div>
            <div class="row2"><div class="fg"><label class="f">Tiền rác (₫/tháng)</label><input class="inp" id="cRac" type="number" min="0"></div><div class="fg"><label class="f">Tính tiền rác theo</label><select class="inp" id="cRacKieu"><option value="phong">Mỗi phòng</option><option value="nguoi">Mỗi người</option></select></div></div>
            <div class="row2"><div class="fg"><label class="f">Wifi (₫/phòng/tháng, phòng có dùng)</label><input class="inp" id="cWifi" type="number" min="0"></div><div class="fg"><label class="f">Gửi xe (₫/xe/tháng)</label><input class="inp" id="cXe" type="number" min="0"></div></div>
            <div class="row2"><div class="fg"><label class="f">Hạn thanh toán: ngày mấy tháng sau</label><input class="inp" id="cHanTT" type="number" min="1" max="28"></div><div class="fg"><label class="f">Báo hợp đồng sắp hết hạn trước (ngày)</label><input class="inp" id="cCanhBao" type="number" min="1"></div></div>
            <button class="btn pri" onclick="saveCfg()">Lưu đơn giá</button>
          </div>
          <div class="card" style="padding:20px">
            <h4 style="font-size:15px;margin-bottom:4px">Thông tin nhà trọ</h4>
            <p style="color:var(--muted);font-size:13px;margin-bottom:14px">In trên hóa đơn, phiếu thu, hợp đồng và tin nhắn gửi người thuê.</p>
            <div class="fg"><label class="f">Tên nhà trọ</label><input class="inp" id="cTen"></div>
            <div class="fg"><label class="f">Địa chỉ</label><input class="inp" id="cDiaChi"></div>
            <div class="row2"><div class="fg"><label class="f">Chủ nhà trọ</label><input class="inp" id="cChu"></div><div class="fg"><label class="f">Điện thoại</label><input class="inp" id="cSdt"></div></div>
            <div class="row3"><div class="fg"><label class="f">Ngân hàng</label><input class="inp" id="cNganHang"></div><div class="fg"><label class="f">Số tài khoản</label><input class="inp" id="cStk"></div><div class="fg"><label class="f">Chủ tài khoản</label><input class="inp" id="cChuTK"></div></div>
            <button class="btn pri" onclick="saveCfg()">Lưu thông tin</button>
          </div>
        </div>
        <div class="set-grid">
          <div class="card"><div class="set-ic" style="background:var(--chu-dao-nhat);color:var(--chu-dao)"><i data-ic="save"></i></div>
            <h4>Lưu đè dữ liệu vào file</h4><p>Ghi toàn bộ dữ liệu vào chính file .html này. Lần sau mở file là thấy dữ liệu mới nhất. Phím tắt: Ctrl + S.</p><button class="btn save" onclick="saveToFile()">Lưu đè dữ liệu</button> <button class="btn sm" onclick="forgetFile()" title="Chọn lại file để ghi đè">Chọn lại file</button></div>
          <div class="card"><div class="set-ic" style="background:var(--infobg);color:var(--info)"><i data-ic="down"></i></div>
            <h4>Sao lưu dữ liệu</h4><p>Tải toàn bộ phòng, người thuê, chỉ số, hóa đơn, phiếu thu về một file .json để cất riêng.</p><button class="btn" onclick="backup()">Tải file sao lưu</button></div>
          <div class="card"><div class="set-ic" style="background:var(--inbg);color:var(--in)"><i data-ic="up"></i></div>
            <h4>Khôi phục dữ liệu</h4><p>Chọn file .json đã sao lưu. Dữ liệu hiện tại sẽ được thay bằng dữ liệu trong file.</p><button class="btn" onclick="$('#restoreFile').click()">Chọn file sao lưu</button><input type="file" id="restoreFile" accept=".json" hidden></div>
          <div class="card"><div class="set-ic" style="background:var(--warnbg);color:#a87200"><i data-ic="renew"></i></div>
            <h4>Nạp lại dữ liệu mẫu</h4><p>Xóa dữ liệu hiện tại, nạp 18 phòng mẫu, người thuê và 12 tháng hóa đơn để xem thử.</p><button class="btn" onclick="resetSample()">Nạp dữ liệu mẫu</button></div>
          <div class="card"><div class="set-ic" style="background:var(--dangerbg);color:var(--danger)"><i data-ic="trash"></i></div>
            <h4>Bắt đầu dùng thật</h4><p>Xóa người thuê, chỉ số, hóa đơn mẫu. Có thể giữ lại danh sách phòng để sửa cho đúng nhà mình.</p><button class="btn danger" onclick="clearAll(true)">Giữ phòng, xóa phần còn lại</button> <button class="btn sm danger" onclick="clearAll(false)">Xóa sạch</button></div>
        </div>
        <div class="card" style="margin-top:16px;padding:18px" id="storeInfo"></div>
      </section>