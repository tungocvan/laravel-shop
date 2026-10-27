'use strict';
/* Standalone menu editor. Never modifies business data or global routes. */
(() => {
  const url = '/demo/quan-ly-cong-no/menu';
  const fallback = () => ({
    version: 1,
    groups: [...App.cfg.nav.map(g => ({
      title: g.group,
      items: g.items.map(id => ({ id, label: App.views[id].label || App.views[id].title, icon: App.views[id].icon, href: '#' + id }))
    })), { title: 'Hệ thống', items: [{ id: 'data', label: 'Dữ liệu & sao lưu', icon: 'database', href: '#data' }] }]
  });
  let config = null;
  let editor = null;
  const copy = v => JSON.parse(JSON.stringify(v));
  const safeHref = href => /^#[a-z0-9-]{1,64}$/.test(href) || /^https:\/\/[a-z0-9][a-z0-9.-]+(?:\/[^\s]*)?$/i.test(href);
  const safeIcon = name => /^[a-z0-9-]{1,48}$/.test(name);
  const validate = value => {
    if (!value || value.version !== 1 || !Array.isArray(value.groups) || value.groups.length > 20) throw Error('Phiên bản hoặc nhóm menu không hợp lệ.');
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
      head.append(title, add);
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
  function openEditor() {
    config = copy(config || fallback());
    editor = Modal.open({
      title: 'Chỉnh sửa sidebar menu', width: 1060,
      body: '<p class="muted">Kéo biểu tượng ☰ để đổi thứ tự, kể cả giữa các nhóm. Đường dẫn nội bộ dùng #dashboard, #doitac, #chungtu, #chitiet, #tonghop, #tuoino, #caidat hoặc #data. URL ngoài chỉ hỗ trợ HTTPS.</p><div id="menu-edit-groups"></div><p id="menu-edit-status" role="status"></p>',
      foot: '<button class="btn" id="menu-add-group">+ Nhóm</button><button class="btn" id="menu-reset">Mặc định</button><button class="btn" id="menu-export">Xuất JSON</button><label class="btn">Nhập JSON<input id="menu-import" type="file" accept=".json,application/json" hidden></label><button class="btn" id="menu-cancel">Đóng</button><button class="btn pri" id="menu-save">Lưu lên server</button>',
      onClose: () => { editor = null; loadMenu(); }
    });
    drawEditor();
    const el = editor.el;
    el.querySelector('#menu-add-group').onclick = () => { config.groups.push({ title: 'Nhóm mới', items: [] }); drawEditor(); };
    el.querySelector('#menu-reset').onclick = async () => { if (await Modal.confirm('Khôi phục menu mặc định trong bản chỉnh sửa?', { ok: 'Khôi phục' })) { config = fallback(); drawEditor(); } };
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
          config = copy(imported); drawEditor(); message('Đã nhập JSON. Nhấn Lưu lên server để áp dụng cho mọi thiết bị.');
        }
      } catch (err) { message('Không thể nhập JSON: ' + err.message, true); }
      e.target.value = '';
    };
    el.querySelector('#menu-cancel').onclick = () => editor?.close();
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
        renderSidebar();
        message('Đã lưu JSON menu trên server.');
        editor.close();
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
