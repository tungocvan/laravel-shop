/* Standalone payroll adapter: original calculation engine remains unchanged. */
(() => {
  'use strict';
  const slug = 'cham-cong-tinh-luong';
  const themeKey = 'attendance_payroll_theme_v1';
  const themeNames = ['original', 'ruby', 'ocean', 'emerald', 'indigo'];
  const colors = [['#0284c7','#082f49'], ['#c62828','#451a1a'], ['#087e9b','#082f49'], ['#16835d','#073d32'], ['#4f46e5','#24204c']];
  const datasetBase = document.body.dataset.datasetBase;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const status = (message) => { const node = document.querySelector('#payroll-status'); if (node) node.textContent = message; };
  const safeTheme = (name) => themeNames.includes(name) ? name : 'ocean';
  const applyTheme = (name) => {
    name = safeTheme(name);
    document.body.dataset.payrollTheme = name;
    try { localStorage.setItem(themeKey, name); } catch {}
    document.querySelectorAll('[data-payroll-theme]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.payrollTheme === name)));
  };
  let initial = 'ocean';
  try { initial = localStorage.getItem(themeKey) || initial; } catch {}
  applyTheme(initial);

  const json = async (url, options = {}) => {
    const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store', ...options});
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw Error(payload.message || ('HTTP ' + response.status));
    return payload;
  };
  const downloadJSON = () => {
    const blob = new Blob([JSON.stringify({name:'Chấm công & Tính lương',data:DB},null,2)], {type:'application/json'});
    const link = document.createElement('a'); link.href = URL.createObjectURL(blob);
    link.download = 'cham-cong-tinh-luong.json'; link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    status('Đã tải JSON về máy; chưa lưu lên server.');
  };
  const validDB = data => data && typeof data === 'object' && !Array.isArray(data) && data.settings && Array.isArray(data.employees) && data.cham && data.extra;
  const loadDB = (data) => {
    if (!validDB(data)) throw Error('Dữ liệu không đúng cấu trúc bảng lương.');
    if (!confirm('Thay thế toàn bộ dữ liệu chấm công và tính lương hiện tại trong trình duyệt?')) return;
    DB = JSON.parse(JSON.stringify(data)); App.cfg.migrate(DB); App.changed();
    Store.save(); status('Đã nạp dữ liệu vào trình duyệt.');
  };
  const refreshDatasets = async () => {
    status('Đang tải danh sách...');
    try {
      const payload = await json(datasetBase);
      const select = document.querySelector('#payroll-datasets');
      select.replaceChildren(new Option('Chọn bộ dữ liệu', ''));
      for (const row of payload.datasets || []) select.add(new Option(row.name + ' (' + row.employees + ' NV)', row.id));
      status('Đã tải danh sách JSON server.');
    } catch (error) { status('Không thể tải danh sách: ' + error.message); }
  };
  const saveServer = async () => {
    const name = document.querySelector('#payroll-dataset-name')?.value.trim();
    if (!name || name.length < 2) return status('Nhập tên bộ dữ liệu (ít nhất 2 ký tự).');
    const code = document.querySelector('#payroll-admin-code')?.value || '';
    if (!code) return status('Cần mã quản trị để ghi JSON lên server.');
    if (!confirm('Lưu bản sao chấm công và lương lên server? Dữ liệu có thể chứa thông tin nhạy cảm.')) return;
    status('Đang lưu...');
    try {
      await json(datasetBase, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Demo-Backup-Code':code},body:JSON.stringify({name,data:DB})});
      status('Đã lưu bộ JSON mới lên server.');
      await refreshDatasets();
    } catch (error) { status('Lưu server thất bại: ' + error.message); }
    finally { const input = document.querySelector('#payroll-admin-code'); if (input) input.value = ''; }
  };
  const originalSettingsRender = App.views.caidat.render.bind(App.views.caidat);
  const renderDemoSettings = (el) => {
      const demoSection = document.createElement('section');
      demoSection.id = 'payroll-demo-settings';
      demoSection.setAttribute('aria-label', 'Giao diện và dữ liệu demo');
      demoSection.innerHTML = `<h2 class="payroll-section-title">Giao diện &amp; dữ liệu demo</h2><div class="payroll-grid">
        <div class="card"><h3>5 theme màu sắc</h3><div class="payroll-theme-grid">
        ${themeNames.map((name,i)=>`<button type="button" data-payroll-theme="${name}" aria-pressed="false"><span class="payroll-palette"><i style="background:${colors[i][0]}"></i><i style="background:${colors[i][1]}"></i></span><span>${name === "original" ? "Màu gốc" : name[0].toUpperCase()+name.slice(1)}</span><span class="payroll-theme-check" aria-hidden="true">✓</span></button>`).join('')}
        </div><p class="muted">Theme được lưu riêng, không ảnh hưởng dữ liệu lương.</p></div>
        <div class="card"><h3>Sidebar menu</h3><p>Chỉnh sửa header, footer, nhóm menu và thư viện preset.</p><button type="button" class="btn pri" data-open-menu>Chỉnh sửa menu</button></div>
        <div class="card" style="grid-column:1/-1"><h3>Thư viện JSON server</h3><p class="muted">Dữ liệu làm việc vẫn lưu trong trình duyệt. Chỉ mã quản trị hợp lệ mới được lưu bộ JSON lên server.</p>
        <div class="payroll-data-actions"><button class="btn" id="payroll-export-json">Xuất JSON về máy</button><label class="btn">Nhập JSON<input id="payroll-import-json" type="file" accept=".json,application/json" hidden></label></div>
        <div class="payroll-data-actions"><select id="payroll-datasets" aria-label="Bộ JSON server"><option value="">Chọn bộ dữ liệu</option></select><button class="btn" id="payroll-refresh">Làm mới</button><button class="btn" id="payroll-load">Nạp vào trình duyệt</button></div>
        <div class="payroll-data-actions"><input id="payroll-dataset-name" maxlength="100" placeholder="Tên bộ JSON mới" aria-label="Tên bộ dữ liệu"><input id="payroll-admin-code" type="password" autocomplete="off" placeholder="Mã quản trị" aria-label="Mã quản trị"><button class="btn pri" id="payroll-save-server">Lưu JSON lên server</button></div>
        <p id="payroll-status" class="payroll-status" role="status" aria-live="polite"></p></div></div>`;
      el.append(demoSection);
      demoSection.querySelectorAll('[data-payroll-theme]').forEach(button => button.onclick = () => applyTheme(button.dataset.payrollTheme));
      applyTheme(document.body.dataset.payrollTheme);
      demoSection.querySelector('#payroll-export-json').onclick = downloadJSON;
      demoSection.querySelector('#payroll-refresh').onclick = refreshDatasets;
      demoSection.querySelector('#payroll-save-server').onclick = saveServer;
      demoSection.querySelector('#payroll-load').onclick = async () => {
        const id = el.querySelector('#payroll-datasets').value;
        if (!/^[a-z0-9][a-z0-9-]{0,79}$/.test(id)) return status('Hãy chọn một bộ JSON.');
        try { const payload = await json(datasetBase + '/' + encodeURIComponent(id)); loadDB(payload.data); }
        catch(error) { status('Nạp JSON thất bại: ' + error.message); }
      };
      demoSection.querySelector('#payroll-import-json').onchange = async event => {
        const file = event.target.files[0]; if (!file) return;
        try { if(file.size>10485760) throw Error('File vượt quá 10 MiB.'); const payload = JSON.parse(await file.text()); loadDB(payload.data || payload); }
        catch(error) { status('Nhập JSON thất bại: ' + error.message); }
        event.target.value = '';
      };
      refreshDatasets();
  };
  App.views.caidat.render = function(el) {
    originalSettingsRender(el);
    renderDemoSettings(el);
  };
  // Backward-compatible deep link, without adding a second sidebar entry.
  if (location.hash === '#demo-settings') App.go('caidat');
  window.AttendancePayrollMenuBridge = {
    mount() { /* The shared editor uses document-level data-open-menu delegation. */ },
    normalize(config) {
      if (!config || !Array.isArray(config.groups)) return config;
      const seen = new Set();
      for (const group of config.groups) {
        if (!Array.isArray(group.items)) continue;
        group.items = group.items.filter(item => {
          if (item.id === 'demo-settings' || item.href === '#demo-settings') {
            item.id = 'caidat'; item.href = '#caidat'; item.label = 'Cài đặt'; item.icon = 'settings';
          }
          // Only de-duplicate the legacy settings entry; retain custom menu items.
          if (item.id === 'caidat') { if (seen.has('caidat')) return false; seen.add('caidat'); }
          return true;
        });
      }
      return config;
    },
    defaults() {
      const groups = App.cfg.nav.map(group => ({title:group.group,items:group.items.filter(id => id !== 'caidat').map(id => ({id,label:App.views[id].label||App.views[id].title,icon:App.views[id].icon||'link',href:'#'+id}))})).filter(group => group.items.length);
      groups.push({title:'Hệ thống',items:[{id:'data',label:'Dữ liệu & sao lưu',icon:'database',href:'#data'},{id:'caidat',label:'Cài đặt',icon:'settings',href:'#caidat'}]});
      return {version:1,header:{title:App.cfg.title,subtitle:App.cfg.tagline,icon:'wallet',href:'#dashboard'},footer:{credit:'Phong Excel Academy',href:'',showSave:true},groups};
    },
    apply(config) {
      const sidebar = document.querySelector('.sidebar'), nav = sidebar.querySelector('.nav');
      config = this.normalize(JSON.parse(JSON.stringify(config)));
      nav.replaceChildren();
      for (const group of config.groups) {
        const title = document.createElement('div'); title.className = 'nav-group'; title.textContent = group.title; nav.append(title);
        for (const item of group.items) {
          const link = document.createElement('a'); const page = item.href.startsWith('#') ? item.href.slice(1) : '';
          link.dataset.nav = App.views[page] ? page : '';
          if (item.href.startsWith('https://')) {link.href=item.href;link.target='_blank';link.rel='noopener noreferrer';link.removeAttribute('data-nav');}
          else if (!App.views[page]) {link.setAttribute('aria-disabled','true');}
          // The original demo uses inline SVG icons from its own icon registry.
          // Map Lucide-style names to supported icons without injecting user JSON as HTML.
          const iconAliases = {'layout-dashboard':'dashboard','users-round':'users','calendar-check':'calcheck','settings-2':'settings','database-backup':'database','percent':'percent'};
          const iconName = iconAliases[item.icon] || item.icon;
          const fallbackIcon = App.views[page]?.icon || 'file';
          const svgName = typeof ICONS[iconName] === 'string' ? iconName : fallbackIcon;
          if (typeof ICONS[svgName] === 'string') {
            const iconNode = document.createElement('span');
            iconNode.className = 'payroll-menu-icon';
            iconNode.innerHTML = ic(svgName);
            iconNode.setAttribute('aria-hidden', 'true');
            link.append(iconNode);
          }
          const label=document.createElement('span');label.textContent=item.label;
          link.append(label);nav.append(link);
        }
      }
      const title=sidebar.querySelector('.brand b'),subtitle=sidebar.querySelector('.brand small');
      if(title)title.textContent=config.header.title;
      if(subtitle)subtitle.textContent=config.header.subtitle;
      const credit=sidebar.querySelector('.credit');if(credit)credit.textContent=config.footer.credit;
      const save=sidebar.querySelector('.btn-save');if(save)save.hidden=!config.footer.showSave;
    }
  };
})();
