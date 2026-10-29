/* Coffee Shop Sales: optional server JSON library; business state remains browser-owned. */
(() => {
  'use strict';
  const base = '/demo/quan-ly-ban-hang-ca-phe/datasets';
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  const notice = (message, error = false) => typeof toast === 'function' ? toast(message, error ? 'err' : '') : alert(message);
  const code = () => document.getElementById('coffee-dataset-code')?.value || '';
  const get = async url => {
    if (!code()) throw Error('Nhập mã bảo vệ server trước khi truy cập thư viện.');
    const res = await fetch(url, {credentials:'same-origin', headers:{'Accept':'application/json','X-Demo-Backup-Code':code()},cache:'no-store'});
    if (!res.ok) throw Error('Máy chủ trả về HTTP ' + res.status);
    return res.json();
  };
  async function refresh() {
    const list = document.getElementById('coffee-datasets-list');
    if (!list) return;
    list.textContent = 'Đang tải thư viện...';
    try {
      const result = await get(base);
      list.replaceChildren();
      if (!result.datasets.length) { list.textContent = 'Chưa có bản lưu trên server.'; return; }
      for (const entry of result.datasets) {
        const row = document.createElement('div');
        row.className = 'coffee-dataset-row';
        const title = document.createElement('span');
        title.textContent = entry.name + ' · ' + (entry.savedAt || '');
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'btn sm'; button.textContent = 'Khôi phục';
        button.addEventListener('click', async () => {
          if (!confirm('Thay dữ liệu hiện tại bằng bản "' + entry.name + '"? Hãy sao lưu JSON trước khi tiếp tục.')) return;
          try {
            const payload = await get(base + '/' + encodeURIComponent(entry.id));
            if (!APP.valid(payload.data)) throw Error('Bản dữ liệu không tương thích.');
            S = payload.data;
            if (APP.fix) APP.fix(S);
            save(); refreshChrome(); rerender();
            notice('Đã khôi phục dữ liệu từ server vào trình duyệt.');
          } catch (error) { notice(error.message, true); }
        });
        row.append(title, button); list.append(row);
      }
    } catch (error) { list.textContent = 'Không tải được thư viện: ' + error.message; }
  }
  async function upload() {
    const name = document.getElementById('coffee-dataset-name')?.value.trim();
    const code = document.getElementById('coffee-dataset-code')?.value || '';
    if (!name || name.length < 2) { notice('Nhập tên bản lưu (ít nhất 2 ký tự).', true); return; }
    if (!APP.valid(S)) { notice('Dữ liệu không hợp lệ.', true); return; }
    if (!confirm('Lưu một bản sao dữ liệu hiện tại vào thư viện JSON server?')) return;
    try {
      const response = await fetch(base, {
        method:'POST', credentials:'same-origin',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token,'X-Demo-Backup-Code':code},
        body:JSON.stringify({name,data:S})
      });
      if (!response.ok) throw Error('Lưu thất bại (HTTP ' + response.status + ').');
      const secret = document.getElementById('coffee-dataset-code');
      notice('Đã lưu bản JSON trên server.'); await refresh();
    } catch (error) { notice(error.message, true); }
  }
  function mount() {
    const settings = document.getElementById('page-settings');
    if (!settings) return;
    const grid = settings.querySelector('.set-grid') || settings;
    const card = document.createElement('section');
    card.className = 'card coffee-integrations';
    card.innerHTML = '<h4>Giao diện & thư viện JSON server</h4><div id="coffee-theme-settings"></div>' +
      '<p>Thư viện server lưu bản sao JSON riêng. Dữ liệu đang làm việc vẫn nằm trong trình duyệt.</p>' +
      '<label for="coffee-dataset-name">Tên bản lưu</label><input id="coffee-dataset-name" maxlength="100" placeholder="Ví dụ: Dữ liệu tháng 10">' +
      '<label for="coffee-dataset-code">Mã bảo vệ sao lưu</label><input id="coffee-dataset-code" type="password" autocomplete="off" placeholder="Mã bảo vệ server">' +
      '<div class="coffee-dataset-actions"><button class="btn pri" id="coffee-dataset-upload" type="button">Lưu lên server</button>' +
      '<button class="btn" id="coffee-dataset-refresh" type="button">Tải danh sách</button></div>' +
      '<div id="coffee-datasets-list" aria-live="polite"></div>';
    grid.append(card);
    document.getElementById('coffee-dataset-upload').addEventListener('click', upload);
    document.getElementById('coffee-dataset-refresh').addEventListener('click', refresh);
    const themeHost = document.getElementById('coffee-theme-settings');
    const themes = {ruby:'#C62828',ocean:'#087E9B',emerald:'#16835D',indigo:'#4F46E5'};
    const apply = name => {
      if (!themes[name]) name='ruby';
      document.documentElement.dataset.coffeeTheme=name;
      for (const button of themeHost.querySelectorAll('button')) button.setAttribute('aria-pressed', String(button.dataset.coffeeTheme===name));
      try { localStorage.setItem('coffee_shop_sales_theme_v1',name); } catch (_) {}
    };
    themeHost.innerHTML = '<strong>Màu giao diện</strong><div class="coffee-theme-options">' +
      Object.entries(themes).map(([name,color]) => '<button type="button" data-coffee-theme="'+name+'" style="--swatch:'+color+'">'+escapeHtml(name)+'</button>').join('') + '</div>';
    themeHost.addEventListener('click', event => {
      const button = event.target.closest('button[data-coffee-theme]');
      if (button) apply(button.dataset.coffeeTheme);
    });
    apply(document.documentElement.dataset.coffeeTheme || 'ruby');
    const list = document.getElementById('coffee-datasets-list');
    if (list) list.textContent = 'Nhập mã bảo vệ rồi chọn “Tải danh sách”.';
  }
  if (typeof APP !== 'undefined' && APP.pages?.settings) {
    const original = APP.pages.settings[2];
    APP.pages.settings[2] = function () { original(); mount(); };
    if (typeof CUR !== 'undefined' && CUR === 'settings') mount();
  }
})();
