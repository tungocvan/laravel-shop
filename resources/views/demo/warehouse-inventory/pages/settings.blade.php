<section class="page" id="page-settings">
        <div class="card" style="padding:20px;margin-bottom:16px" id="demoLibrary">
          <h4 style="font-size:16px;margin-bottom:6px">Thư viện dữ liệu Demo theo ngành nghề</h4>
          <p style="color:var(--muted);font-size:13px;margin-bottom:12px">Chọn bộ dữ liệu mẫu có sẵn trên server. Mỗi khách hàng có thể thử nghiệm riêng trong trình duyệt, không làm thay đổi dữ liệu gốc.</p>
          <div class="row2">
            <div class="fg"><label class="f" for="demoDataset">Bộ dữ liệu</label><select class="inp" id="demoDataset"><option value="">Đang tải danh sách…</option></select></div>
            <div class="fg" style="justify-content:flex-end"><label class="f">&nbsp;</label><button type="button" class="btn pri" id="loadDemoDataset" onclick="loadSelectedDemoDataset()">Nạp dữ liệu demo</button></div>
          </div>
          <p id="demoDatasetInfo" style="color:var(--muted);font-size:12px;margin-top:8px" role="status" aria-live="polite"></p>
        </div>
        <div class="set-grid">
          <div class="card"><div class="set-ic" style="background:#e7eaf2;color:#1e1b4b"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"></path><path d="M17 21v-8H7v8M7 3v5h8"></path></svg></div>
            <h4>Sao lưu JSON về máy</h4><p>Dữ liệu demo tự lưu trong trình duyệt. Tải JSON về máy để lưu trữ hoặc chuyển sang máy khác (Ctrl + S).</p><button class="btn save" onclick="saveToFile()">Tải JSON về máy</button></div>
          <div class="card"><div class="set-ic" style="background:var(--accent2);color:var(--accent)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg></div>
            <h4>Sao lưu dữ liệu</h4><p>Tải JSON về máy và lưu thêm một phiên bản lên server. Cần mã quản trị để ghi server; khách hàng vẫn xem demo bình thường.</p><label class="f" for="backupDatasetName">Tên bộ dữ liệu</label><input class="inp" id="backupDatasetName" maxlength="100" placeholder="Ví dụ: Kho điện thoại 2026"><button class="btn pri" id="backupServerBtn" onclick="backupToServerAndDownload()" style="margin-top:10px">Sao lưu về máy và server</button><p id="backupServerStatus" role="status" aria-live="polite" style="font-size:12px;margin-top:8px"></p></div>
          <div class="card"><div class="set-ic" style="background:var(--inbg);color:var(--in)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9M7 14l5-5 5 5M4 3h16"></path></svg></div>
            <h4>Khôi phục dữ liệu</h4><p>Chọn file .json đã sao lưu trước đó. Dữ liệu hiện tại sẽ được thay bằng dữ liệu trong file.</p><button class="btn" onclick="$('#restoreFile').click()">Chọn file sao lưu</button><input type="file" id="restoreFile" accept=".json" hidden=""></div>
          <div class="card"><div class="set-ic" style="background:var(--warnbg);color:#b07a00"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.7L3 8"></path><path d="M3 3v5h5"></path></svg></div>
            <h4>Nạp lại dữ liệu mẫu</h4><p>Xóa dữ liệu hiện tại, nạp 12 sản phẩm kèm ảnh thật và 12 tháng giao dịch mẫu để trình diễn.</p><button class="btn" onclick="resetSample()">Nạp dữ liệu mẫu</button><p id="imgCredit" style="font-size:11px;margin:10px 0 0;cursor:help">Nguồn ảnh mẫu (rê chuột để xem)</p></div>
          <div class="card"><div class="set-ic" style="background:var(--dangerbg);color:var(--danger)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"></path></svg></div>
            <h4>Bắt đầu kho trống</h4><p>Xóa sạch sản phẩm và giao dịch để nhập dữ liệu thật của mình từ đầu.</p><button class="btn danger" onclick="clearAll()">Xóa toàn bộ dữ liệu</button></div>
        </div>
        <div class="card" style="margin-top:16px;padding:20px">
          <h4 style="font-size:15px;margin-bottom:4px">Thông tin in trên phiếu nhập / phiếu xuất</h4>
          <p style="color:var(--muted);font-size:13px;margin-bottom:14px">Điền một lần, các phiếu in ra sẽ tự có tên đơn vị, địa chỉ và tên kho.</p>
          <div class="row2"><div class="fg"><label class="f">Tên đơn vị / cửa hàng</label><input class="inp" id="coName" placeholder="VD: Công ty TNHH Minh An"></div><div class="fg"><label class="f">Điện thoại</label><input class="inp" id="coPhone"></div></div>
          <div class="row2"><div class="fg"><label class="f">Địa chỉ</label><input class="inp" id="coAddr"></div><div class="fg"><label class="f">Tên kho (Nhập tại kho / Xuất tại kho)</label><input class="inp" id="coKho" placeholder="VD: Kho chính"></div></div>
          <button class="btn pri" onclick="saveCompany()">Lưu thông tin</button>
        </div>
        <div class="card" style="margin-top:16px;padding:18px" id="storeInfo"></div>
      </section>
