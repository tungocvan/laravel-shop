'use strict';
/* Standalone menu editor. Never modifies business data or global routes. */
(() => {
  const url = '/demo/quan-ly-cong-no/menu';
  const localKey = 'debt_management_sidebar_menu_v1';
  const fallback = () => ({
    version: 1,
    header: { title: App.cfg.title, subtitle: App.cfg.tagline, icon: App.cfg.icon, href: '#dashboard' },
    footer: { credit: 'Phong Excel Academy', href: '', showSave: true },
    groups: [...App.cfg.nav.map(g => ({
      title: g.group,
      items: g.items.map(id => ({ id, label: App.views[id].label || App.views[id].title, icon: App.views[id].icon, href: '#' + id }))
    })), { title: 'Hệ thống', items: [{ id: 'data', label: 'Dữ liệu & sao lưu', icon: 'database', href: '#data' }] }]
  });
  let config = null;
  let editor = null;
  let appliedConfig = null;
  const copy = v => JSON.parse(JSON.stringify(v));
  const showUpdated = () => {
    const modal = Modal.open({
      title: 'Cập nhật thành công', width: 440,
      body: '<p>Sidebar đã được cập nhật và lưu trên trình duyệt. Các thay đổi sẽ được giữ sau khi tải lại trang trên thiết bị này.</p><p class="muted">Để đồng bộ cho các thiết bị khác, hãy chọn Lưu lên server.</p>',
      foot: '<button class="btn pri" data-ok>Đã hiểu</button>'
    });
    modal.el.querySelector('[data-ok]').onclick = () => modal.close();
  };
  const showNotice = (title, detail) => {
    const m = Modal.open({ title, width: 440, body: '<p data-notice></p>', foot: '<button class="btn pri" data-ok>Đã hiểu</button>' });
    m.el.querySelector('[data-notice]').textContent = detail;
    m.el.querySelector('[data-ok]').onclick = () => m.close();
  };
  const safeHref = href => /^#[a-z0-9-]{1,64}$/.test(href) || /^https:\/\/[a-z0-9][a-z0-9.-]+(?:\/[^\s]*)?$/i.test(href);
  const safeIcon = name => /^[a-z0-9-]{1,48}$/.test(name);
  const validate = value => {
    if (!value || value.version !== 1 || !Array.isArray(value.groups) || value.groups.length > 20) throw Error('Phiên bản hoặc nhóm menu không hợp lệ.');
    for (const section of ['header', 'footer']) {
      if (!value[section]) continue;
      const fields = section === 'header' ? ['title','subtitle','icon','href'] : ['credit','href'];
      if (fields.some(key => typeof value[section][key] !== 'string') || !safeHref(value[section].href) && value[section].href !== '') throw Error('Header/footer không hợp lệ.');
      if (section === 'header' && (!safeIcon(value.header.icon) || value.header.title.length > 100 || value.header.subtitle.length > 160)) throw Error('Header không hợp lệ.');
      if (section === 'footer' && (typeof value.footer.showSave !== 'boolean' || value.footer.credit.length > 160)) throw Error('Footer không hợp lệ.');
    }
    const ids = new Set();
    let total = 0;
    for (const group of value.groups) {
      if (typeof group.title !== 'string' || group.title.length > 60 || !Array.isArray(group.items)) throw Error('Nhóm menu không hợp lệ.');
      for (const item of group.items) {
        if (++total > 100 || !item || !/^[a-z0-9-]{1,64}$/.test(item.id) || ids.has(item.id) ||
          typeof item.label !== 'string' || !item.label.trim() || item.label.length > 80 ||
          !safeIcon(item.icon) || typeof item.href !== 'string' || item.href.length > 250 || !safeHref(item.href)) {
          throw Error('Menu không hợp lệ: trùng ID, icon, tên hoặc href.');
        }
        ids.add(item.id);
      }
    }
    return value;
  };
  function renderSidebar() {
    if (!config) return;
    const header = config.header || fallback().header;
    const footer = config.footer || fallback().footer;
    const brand = document.querySelector('.sidebar .brand');
    if (brand) {
      const logo = brand.querySelector('.logo');
      if (logo) logo.innerHTML = ic(header.icon);
      const title = brand.querySelector('b');
      if (title) title.textContent = header.title;
      const subtitle = brand.querySelector('small');
      if (subtitle) subtitle.textContent = header.subtitle;
      brand.style.cursor = header.href ? 'pointer' : '';
      brand.onclick = () => {
        if (!header.href) return;
        if (header.href.startsWith('#') && App.views[header.href.slice(1)]) App.go(header.href.slice(1));
        else if (header.href.startsWith('https://')) window.open(header.href, '_blank', 'noopener,noreferrer');
      };
    }
    const credit = document.querySelector('.sidebar .credit');
    if (credit) {
      credit.textContent = footer.credit;
      credit.style.cursor = footer.href ? 'pointer' : '';
      credit.onclick = () => { if (footer.href.startsWith('https://')) window.open(footer.href, '_blank', 'noopener,noreferrer'); else if (footer.href.startsWith('#') && App.views[footer.href.slice(1)]) App.go(footer.href.slice(1)); };
    }
    const save = document.querySelector('.sidebar .btn-save');
    if (save) save.hidden = !footer.showSave;
    const nav = document.querySelector('.sidebar .nav');
    if (!nav) return;
    nav.replaceChildren();
    for (const group of config.groups) {
      const title = document.createElement('div');
      title.className = 'nav-group';
      title.textContent = group.title;
      nav.appendChild(title);
      for (const item of group.items) {
        const a = document.createElement('a');
        a.dataset.menuId = item.id;
        a.setAttribute('href', item.href);
        if (item.href.startsWith('#') && App.views[item.href.slice(1)]) {
          a.dataset.nav = item.href.slice(1);
          a.classList.toggle('on', App.cur === a.dataset.nav);
        } else if (item.href.startsWith('https://')) {
          a.target = '_blank'; a.rel = 'noopener noreferrer';
        } else {
          a.href = '#'; a.setAttribute('aria-disabled', 'true');
        }
        const span = document.createElement('span');
        span.textContent = item.label;
        const icon = document.createElement('span');
        icon.innerHTML = ic(item.icon);
        a.append(icon, span);
        nav.appendChild(a);
      }
    }
  }
  function message(text, error = false) {
    const el = editor?.el.querySelector('#menu-edit-status');
    if (el) { el.textContent = text; el.dataset.error = String(error); }
  }
  function drawEditor() {
    if (!editor) return;
    const area = editor.el.querySelector('#menu-edit-groups');
    area.replaceChildren();
    config.groups.forEach((group, gi) => {
      const section = document.createElement('section');
      section.className = 'menu-edit-group';
      const head = document.createElement('div');
      head.className = 'menu-edit-head';
      const title = document.createElement('input');
      title.className = 'inp'; title.value = group.title; title.maxLength = 60;
      title.setAttribute('aria-label', 'Tên nhóm menu');
      title.addEventListener('input', () => { group.title = title.value; });
      const add = document.createElement('button');
      add.className = 'btn sm'; add.textContent = '+ Mục';
      add.onclick = () => { const id = 'custom-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5); group.items.push({ id, label: 'Mục mới', icon: 'link', href: '#dashboard' }); drawEditor(); };
      const removeGroup = document.createElement('button');
      removeGroup.className = 'btn sm danger';
      removeGroup.textContent = 'Xóa nhóm';
      removeGroup.title = 'Xóa nhóm và các mục menu trong nhóm này';
      removeGroup.onclick = async () => {
        if (!await Modal.confirm('Xóa nhóm "' + group.title + '" và ' + group.items.length + ' mục menu? Chỉ thay đổi bản chỉnh sửa, không xóa dữ liệu công nợ.', { ok: 'Xóa nhóm' })) return;
        config.groups.splice(gi, 1);
        drawEditor();
        message('Đã xóa nhóm trong bản chỉnh sửa. Nhấn Cập nhật để xem trước.');
      };
      head.append(title, add, removeGroup);
      const list = document.createElement('div');
      list.className = 'menu-edit-list';
      list.dataset.group = gi;
      list.addEventListener('dragover', e => { e.preventDefault(); });
      list.addEventListener('drop', e => {
        e.preventDefault();
        try {
          const [fromGroup, fromIndex] = JSON.parse(e.dataTransfer.getData('application/x-debt-menu'));
          const target = e.target.closest('[data-item-index]');
          const toIndex = target ? Number(target.dataset.itemIndex) : group.items.length;
          const source = config.groups[fromGroup]?.items;
          if (!source || !source[fromIndex]) return;
          const [item] = source.splice(fromIndex, 1);
          const index = fromGroup === gi && fromIndex < toIndex ? toIndex - 1 : toIndex;
          group.items.splice(index, 0, item);
          drawEditor();
        } catch (err) { message('Không thể kéo thả mục menu.', true); }
      });
      group.items.forEach((item, ii) => {
        const line = document.createElement('div');
        line.className = 'menu-edit-item';
        line.dataset.itemIndex = ii;
        const grip = document.createElement('span');
        grip.className = 'menu-edit-grip'; grip.textContent = '☰'; grip.draggable = true;
        grip.title = 'Kéo để sắp xếp';
        grip.addEventListener('dragstart', e => e.dataTransfer.setData('application/x-debt-menu', JSON.stringify([gi, ii])));
        const name = document.createElement('input');
        name.className = 'inp'; name.value = item.label; name.maxLength = 80; name.placeholder = 'Tên menu';
        name.setAttribute('aria-label', 'Tên menu');
        name.addEventListener('input', () => { item.label = name.value; });
        const icon = document.createElement('input');
        icon.className = 'inp'; icon.value = item.icon; icon.maxLength = 48; icon.placeholder = 'icon';
        icon.setAttribute('aria-label', 'Icon menu');
        icon.addEventListener('input', () => { item.icon = icon.value; });
        const href = document.createElement('input');
        href.className = 'inp'; href.value = item.href; href.maxLength = 250; href.placeholder = '#dashboard hoặc https://';
        href.setAttribute('aria-label', 'Đường dẫn menu');
        href.addEventListener('input', () => { item.href = href.value; });
        const duplicate = document.createElement('button');
        duplicate.className = 'btn sm'; duplicate.textContent = 'Nhân bản';
        duplicate.onclick = () => { const clone = copy(item); clone.id = 'custom-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5); clone.label += ' (bản sao)'; group.items.splice(ii + 1, 0, clone); drawEditor(); };
        const remove = document.createElement('button');
        remove.className = 'btn sm danger'; remove.textContent = 'Xóa';
        remove.onclick = () => { group.items.splice(ii, 1); drawEditor(); };
        const up = document.createElement('button');
        up.className = 'btn sm'; up.textContent = '↑'; up.title = 'Lên một vị trí';
        up.onclick = () => { if (ii > 0) { [group.items[ii - 1], group.items[ii]] = [group.items[ii], group.items[ii - 1]]; drawEditor(); } };
        const down = document.createElement('button');
        down.className = 'btn sm'; down.textContent = '↓'; down.title = 'Xuống một vị trí';
        down.onclick = () => { if (ii < group.items.length - 1) { [group.items[ii + 1], group.items[ii]] = [group.items[ii], group.items[ii + 1]]; drawEditor(); } };
        line.append(grip, name, icon, href, up, down, duplicate, remove);
        list.appendChild(line);
      });
      section.append(head, list);
      area.appendChild(section);
    });
  }
  function drawHeaderFooter() {
    const area = editor?.el.querySelector('#menu-brand-footer');
    if (!area) return;
    area.replaceChildren();
    for (const [section, fields] of [
      ['header', [['title','Tên ứng dụng'],['subtitle','Dòng mô tả'],['icon','Icon Lucide'],['href','Liên kết logo']]],
      ['footer', [['credit','Dòng ghi chú'],['href','Liên kết footer']]]
    ]) {
      const card = document.createElement('fieldset');
      card.className = 'menu-edit-group';
      const legend = document.createElement('legend');
      legend.textContent = section === 'header' ? 'Header sidebar' : 'Footer sidebar';
      card.appendChild(legend);
      config[section] ||= copy(fallback()[section]);
      for (const [key, label] of fields) {
        const wrapper = document.createElement('label');
        wrapper.className = 'menu-edit-field';
        wrapper.textContent = label;
        const input = document.createElement('input');
        input.className = 'inp'; input.value = config[section][key] || '';
        input.maxLength = key === 'href' ? 250 : key === 'subtitle' || key === 'credit' ? 160 : 100;
        input.oninput = () => { config[section][key] = input.value; };
        wrapper.appendChild(input);
        card.appendChild(wrapper);
      }
      if (section === 'footer') {
        const label = document.createElement('label');
        const box = document.createElement('input');
        box.type = 'checkbox'; box.checked = config.footer.showSave;
        box.onchange = () => { config.footer.showSave = box.checked; };
        label.append(box, document.createTextNode(' Hiện nút lưu dữ liệu'));
        card.appendChild(label);
      }
      area.appendChild(card);
    }
  }
  async function refreshPresets() {
    const select = editor?.el.querySelector('#menu-server-presets');
    if (!select) return;
    select.replaceChildren(new Option('Đang tải thư viện...', ''));
    try {
      const response = await fetch(url + '/presets', { cache: 'no-store', headers: { Accept: 'application/json' } });
      if (!response.ok) throw Error('HTTP ' + response.status);
      const payload = await response.json();
      select.replaceChildren(new Option('Chọn file JSON trên server', ''));
      for (const preset of payload.presets || []) select.add(new Option(preset.name, preset.id));
      message('Đã cập nhật danh sách cấu hình trên server.');
    } catch (e) { message('Không tải được thư viện: ' + e.message, true); }
  }
  async function importServerPreset() {
    const id = editor?.el.querySelector('#menu-server-presets')?.value;
    if (!id) return message('Chọn một file JSON trên server.', true);
    try {
      const response = await fetch(url + '/presets/' + encodeURIComponent(id), { cache: 'no-store' });
      if (!response.ok) throw Error('HTTP ' + response.status);
      const payload = await response.json();
      const imported = validate(payload.config);
      if (!await Modal.confirm('Nhập cấu hình từ server vào trình chỉnh sửa? Chưa áp dụng lên sidebar.', { ok: 'Nhập' })) return;
      config = copy(imported);
      drawHeaderFooter(); drawEditor();
      showNotice('Nhập JSON thành công', 'Đã nhập từ server vào bản chỉnh sửa. Nhấn Cập nhật giao diện hoặc Lưu lên server để áp dụng.');
    } catch (e) { message('Nhập server thất bại: ' + e.message, true); }
  }
  async function saveNamedPreset() {
    try { validate(config); } catch (e) { return message(e.message, true); }
    const name = editor?.el.querySelector('#menu-preset-name')?.value.trim();
    if (!name || name.length < 2 || name.length > 100) return message('Nhập tên file 2–100 ký tự.', true);
    const selected = editor?.el.querySelector('#menu-server-presets');
    const overwrite = !!selected?.value && selected.selectedOptions[0].textContent === name;
    if (overwrite && !await Modal.confirm('Cập nhật file JSON "' + name + '"? Dữ liệu cũ của file này sẽ bị thay thế.', { ok: 'Cập nhật file' })) return;
    const code = window.prompt('Nhập mã quản trị để lưu JSON trên server:');
    if (!code) return;
    try {
      const response = await fetch(overwrite ? url + '/presets/' + encodeURIComponent(selected.value) : url + '/presets', {
        method: overwrite ? 'PUT' : 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-Demo-Backup-Code': code },
        body: JSON.stringify(overwrite ? { config } : { name, config })
      });
      if (response.status === 409) return message('Tên file đã tồn tại. Chọn file cùng tên trong danh sách để xác nhận cập nhật.', true);
      if (!response.ok) throw Error('HTTP ' + response.status);
      const result = await response.json();
      await refreshPresets();
      if (editor?.el.querySelector('#menu-server-presets')) editor.el.querySelector('#menu-server-presets').value = result.id;
      showNotice(overwrite ? 'Cập nhật file thành công' : 'Lưu file thành công', 'Đã lưu cấu hình "' + name + '" trên server. Menu hiện hành chưa thay đổi.');
    } catch (e) { message('Không lưu được file: ' + e.message, true); }
  }
  function openEditor() {
    config = copy(appliedConfig || config || fallback());
    editor = Modal.open({
      title: 'Chỉnh sửa sidebar menu', width: 1060,
      body: '<div id="menu-brand-footer" class="menu-edit-brand-footer"></div><p class="muted">Kéo biểu tượng ☰ để đổi thứ tự, kể cả giữa các nhóm. Đường dẫn nội bộ dùng #dashboard, #doitac, #chungtu, #chitiet, #tonghop, #tuoino, #caidat hoặc #data. URL ngoài chỉ hỗ trợ HTTPS.</p><div id="menu-edit-groups"></div><section class="menu-edit-group"><b>Thư viện JSON trên server</b><p class="muted">Lưu thành file mới hoặc chọn file đã lưu để nhập vào bản chỉnh sửa.</p><div class="menu-preset-row"><input id="menu-preset-name" class="inp" placeholder="Tên file cấu hình mới" maxlength="100"><button class="btn" id="menu-save-preset">Lưu file JSON</button></div><div class="menu-preset-row"><select id="menu-server-presets" class="inp"></select><button class="btn" id="menu-refresh-presets">Làm mới</button><button class="btn" id="menu-import-server">Nhập từ server</button></div></section><p id="menu-edit-status" role="status"></p>',
      foot: '<button class="btn" id="menu-add-group">+ Nhóm</button><button class="btn" id="menu-reset">Mặc định</button><button class="btn" id="menu-export">Xuất JSON</button><label class="btn">Nhập JSON<input id="menu-import" type="file" accept=".json,application/json" hidden></label><button class="btn" id="menu-cancel">Đóng</button><button class="btn pri" id="menu-preview">Cập nhật giao diện</button><button class="btn pri" id="menu-save">Lưu lên server</button>',
      onClose: () => {
        editor = null;
        config = copy(appliedConfig || fallback());
        renderSidebar();
      }
    });
    drawHeaderFooter(); drawEditor();
    const el = editor.el;
    el.querySelector('#menu-save-preset').onclick = saveNamedPreset;
    el.querySelector('#menu-server-presets').onchange = e => { if (e.target.value) el.querySelector('#menu-preset-name').value = e.target.selectedOptions[0].textContent; };
    el.querySelector('#menu-refresh-presets').onclick = refreshPresets;
    el.querySelector('#menu-import-server').onclick = importServerPreset;
    refreshPresets();
    el.querySelector('#menu-add-group').onclick = () => { config.groups.push({ title: 'Nhóm mới', items: [] }); drawEditor(); };
    el.querySelector('#menu-reset').onclick = async () => { if (await Modal.confirm('Khôi phục menu mặc định trong bản chỉnh sửa?', { ok: 'Khôi phục' })) { config = fallback(); drawHeaderFooter(); drawEditor(); showNotice('Khôi phục mặc định thành công', 'Đã khôi phục trong bản chỉnh sửa. Nhấn Cập nhật giao diện hoặc Lưu lên server để áp dụng.'); } };
    el.querySelector('#menu-export').onclick = () => {
      try { validate(config); download(new Blob([JSON.stringify(config, null, 2)], { type: 'application/json' }), 'cong-no-sidebar-menu.json'); }
      catch (e) { message(e.message, true); }
    };
    el.querySelector('#menu-import').onchange = async e => {
      const file = e.target.files[0];
      if (!file) return;
      if (file.size > 65536) return message('JSON vượt 64 KiB.', true);
      try {
        const imported = validate(JSON.parse(await file.text()));
        if (await Modal.confirm('Thay bản menu đang chỉnh sửa bằng JSON nhập vào?', { ok: 'Nhập JSON' })) {
          config = copy(imported); drawHeaderFooter(); drawEditor(); showNotice('Nhập JSON thành công', 'Đã nhập JSON vào bản chỉnh sửa. Nhấn Cập nhật giao diện hoặc Lưu lên server để áp dụng.');
        }
      } catch (err) { message('Không thể nhập JSON: ' + err.message, true); }
      e.target.value = '';
    };
    el.querySelector('#menu-cancel').onclick = () => editor?.close();
    el.querySelector('#menu-preview').onclick = () => {
      try {
        validate(config);
        const snapshot = copy(config);
        localStorage.setItem(localKey, JSON.stringify(snapshot));
        appliedConfig = snapshot;
        renderSidebar();
        showUpdated();
      } catch (e) { message('Không thể cập nhật: ' + e.message, true); }
    };
    el.querySelector('#menu-save').onclick = async () => {
      try { validate(config); } catch (e) { return message(e.message, true); }
      const code = window.prompt('Nhập mã quản trị để lưu menu lên server (mã không được lưu trong trình duyệt):');
      if (!code) return;
      const button = el.querySelector('#menu-save');
      button.disabled = true;
      try {
        const response = await fetch(url, {
          method: 'POST', credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'X-Demo-Backup-Code': code },
          body: JSON.stringify({ config })
        });
        if (!response.ok) throw Error('HTTP ' + response.status);
        localStorage.removeItem(localKey);
        appliedConfig = copy(config);
        renderSidebar();
        editor.close();
        showNotice('Lưu lên server thành công', 'Cấu hình sidebar hiện hành đã được lưu trên server.');
      } catch (e) { message('Lưu server thất bại: ' + e.message, true); }
      finally { button.disabled = false; }
    };
  }
  async function loadMenu() {
    try {
      const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      if (!response.ok) throw Error('HTTP ' + response.status);
      const data = await response.json();
      config = data.config ? validate(data.config) : fallback();
    } catch (e) { config = fallback(); }
    // Local device overrides the shared server menu until an explicit server save.
    // Validate imported browser data before rendering and ignore malformed JSON.
    try {
      const local = localStorage.getItem(localKey);
      if (local) config = validate(JSON.parse(local));
    } catch (e) {
      // Storage may be disabled or JSON may be malformed; retain server/default.
    }
    appliedConfig = copy(config);
    renderSidebar();
  }
  const originalInit = App.init;
  App.init = function (cfg) {
    const settings = cfg.views.caidat;
    const originalRender = settings.render;
    settings.render = function (el) {
      originalRender.call(this, el);
      const wrapper = document.createElement('div');
      wrapper.className = 'menu-settings-entry';
      wrapper.innerHTML = '<div class="card"><div class="card-h"><b>Sidebar menu</b></div><div class="card-b"><p class="muted">Tùy chỉnh thứ tự, icon, tên, href; nhập/xuất JSON và lưu trên server.</p><button class="btn pri" id="menu-editor-open">Chỉnh sửa menu</button></div></div>';
      el.appendChild(wrapper);
      wrapper.querySelector('#menu-editor-open').onclick = openEditor;
    };
    originalInit.call(this, cfg);
    loadMenu();
  };
})();
