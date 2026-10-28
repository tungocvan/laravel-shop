'use strict';
/* Public read-only datasets, operator-protected server backup. */
const debtDatasetUrl = '/demo/quan-ly-cong-no/datasets';
function debtJson() { return JSON.parse(JSON.stringify({ ...DB, _app: 'congno' })); }
function debtServerMessage(msg, type = '') {
  const node = document.getElementById('debt-server-message');
  if (node) { node.textContent = msg; node.dataset.status = type; }
}
async function debtResponse(response) {
  if (!response.ok) {
    let reason = '';
    try { const body = await response.json(); reason = body.message || ''; } catch (e) {}
    throw new Error(reason || ('HTTP ' + response.status));
  }
  return response.json();
}
async function debtRefreshLibrary() {
  const list = document.getElementById('debt-dataset-list');
  if (!list) return;
  list.replaceChildren();
  debtServerMessage('Đang tải thư viện...');
  try {
    const payload = await debtResponse(await fetch(debtDatasetUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' }));
    const datasets = Array.isArray(payload.datasets) ? payload.datasets : [];
    const placeholder = document.createElement('option');
    placeholder.value = ''; placeholder.textContent = datasets.length ? 'Chọn bộ dữ liệu mẫu' : 'Chưa có bộ dữ liệu trên server';
    list.appendChild(placeholder);
    for (const d of datasets) {
      const option = document.createElement('option');
      option.value = d.id;
      option.textContent = d.name + ' · ' + d.partners + ' đối tác · ' + d.vouchers + ' chứng từ';
      list.appendChild(option);
    }
    debtServerMessage(datasets.length ? 'Đã tải danh sách bộ dữ liệu.' : 'Thư viện trống. Có thể lưu bộ dữ liệu đầu tiên bằng mã quản trị.');
  } catch (e) { debtServerMessage('Không tải được thư viện: ' + e.message, 'error'); }
}
async function debtLoadLibrary() {
  const selected = document.getElementById('debt-dataset-list')?.value;
  if (!selected) return debtServerMessage('Hãy chọn một bộ dữ liệu.');
  if (!await Modal.confirm('Nạp bộ dữ liệu này sẽ thay toàn bộ dữ liệu công nợ trong trình duyệt. Tiếp tục?', { title: 'Xác nhận nạp dữ liệu', ok: 'Nạp dữ liệu', danger: true })) return;
  debtServerMessage('Đang nạp dữ liệu...');
  try {
    const incoming = await debtResponse(await fetch(debtDatasetUrl + '/' + encodeURIComponent(selected), { headers: { Accept: 'application/json' }, cache: 'no-store' }));
    if (incoming._app !== 'congno' || !Array.isArray(incoming.partners) || !Array.isArray(incoming.vouchers) || !incoming.settings || typeof incoming.settings !== 'object') throw new Error('Định dạng dữ liệu không hợp lệ');
    const partnerIds = new Set(incoming.partners.map(p => p.id));
    if (partnerIds.size !== incoming.partners.length || incoming.vouchers.some(v => !partnerIds.has(v.partnerId))) throw new Error('Chứng từ không khớp đối tác');
    const next = structuredClone(incoming);
    delete next._app;
    App.cfg.migrate(next);
    DB = next;
    DB._meta = DB._meta || {};
    Store.changed();
    await Store.save();
    App.go('data');
    debtServerMessage('Đã nạp dữ liệu vào trình duyệt. Dữ liệu trên server không thay đổi.');
  } catch (e) { debtServerMessage('Không thể nạp: ' + e.message, 'error'); }
}
function debtServerBackup() {
  const m = Modal.open({
    title: 'Sao lưu công nợ lên server', width: 510,
    body: '<p>Chỉ người quản trị có mã hợp lệ mới được lưu. Bản sao lưu JSON sẽ được tải về máy trước khi gửi server.</p><label>Tên bộ dữ liệu<input class="inp" id="debt-backup-name" maxlength="100" autocomplete="off" placeholder="Ví dụ: Công nợ tháng 10"></label><label>Nhập YES để xác nhận<input class="inp" id="debt-backup-confirm" autocomplete="off" placeholder="YES"></label><label>Mã quản trị<input class="inp" id="debt-backup-code" type="password" autocomplete="off"></label><p id="debt-backup-error" role="alert"></p>',
    foot: '<button class="btn" data-cancel>Hủy</button><button class="btn pri" data-submit>Lưu server</button>'
  });
  m.el.querySelector('[data-cancel]').onclick = () => m.close();
  m.el.querySelector('[data-submit]').onclick = async () => {
    const name = m.el.querySelector('#debt-backup-name').value.trim();
    const yes = m.el.querySelector('#debt-backup-confirm').value;
    const code = m.el.querySelector('#debt-backup-code').value;
    const error = m.el.querySelector('#debt-backup-error');
    if (name.length < 2 || name.length > 100 || yes !== 'YES' || !code) {
      error.textContent = 'Nhập tên 2–100 ký tự, chính xác YES và mã quản trị.'; return;
    }
    const button = m.el.querySelector('[data-submit]');
    button.disabled = true;
    try {
      const data = debtJson();
      download(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }), 'Sao-luu-cong-no-' + D.today() + '.json');
      const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const response = await fetch(debtDatasetUrl, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Demo-Backup-Code': code },
        body: JSON.stringify({ name, data })
      });
      await debtResponse(response);
      m.close();
      debtServerMessage('Đã tải JSON về máy và lưu bản sao mới trên server.', 'success');
      await debtRefreshLibrary();
    } catch (e) {
      error.textContent = 'Đã thử tải JSON về máy, nhưng lưu server thất bại: ' + e.message;
    } finally {
      m.el.querySelector('#debt-backup-code')?.setAttribute('value', '');
      const input = m.el.querySelector('#debt-backup-code'); if (input) input.value = '';
      button.disabled = false;
    }
  };
}
const debtOldDataRender = DataScreen.render;
DataScreen.render = function (el) {
  debtOldDataRender.call(this, el);
  const panel = document.createElement('section');
  panel.className = 'card debt-server-card';
  panel.innerHTML = '<div class="card-h"><b>Thư viện JSON trên server</b></div><div class="card-b"><p class="muted">Nạp dữ liệu mẫu không thay đổi file server. Lưu server cần mã quản trị.</p><div class="row"><select id="debt-dataset-list" class="inp" aria-label="Chọn bộ dữ liệu"></select><button class="btn" id="debt-dataset-refresh">Làm mới</button><button class="btn" id="debt-dataset-load">Nạp vào trình duyệt</button><button class="btn pri" id="debt-dataset-backup">Sao lưu lên server</button></div><p id="debt-server-message" role="status"></p></div>';
  el.appendChild(panel);
  panel.querySelector('#debt-dataset-refresh').onclick = debtRefreshLibrary;
  panel.querySelector('#debt-dataset-load').onclick = debtLoadLibrary;
  panel.querySelector('#debt-dataset-backup').onclick = debtServerBackup;
  debtRefreshLibrary();
};
if (App.cur === 'data') App.go('data');
