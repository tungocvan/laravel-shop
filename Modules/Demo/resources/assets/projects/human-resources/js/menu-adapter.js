/* Adapter preserving HR's original dynamic department/status sidebar. */
window.HrDemoMenuBridge = (() => {
  const sidebar = document.getElementById('sidebar');
  const nav = sidebar.querySelector('nav');
  const original = Array.from(nav.children);
  const brand = sidebar.querySelector('.brand');
  const defaults = () => ({
    version: 1,
    header: {title: 'Nhân Sự Pro', subtitle: 'Công ty TNHH ABC', icon: 'users', href: '#dashboard'},
    footer: {credit: 'Quản trị viên · Phòng Nhân sự', href: '', showSave: false},
    groups: [
      {title: 'Tổng quan', items: [
        {id:'dashboard',label:'Tổng quan',icon:'house',href:'#dashboard'},
        {id:'analytics',label:'Dashboard',icon:'chart-no-axes-column',href:'#analytics'},
        {id:'employees',label:'Tất cả nhân viên',icon:'users',href:'#employees'}]},
      {title:'Nhân sự',items:[{id:'add-employee',label:'Thêm nhân viên',icon:'user-round-plus',href:'#add-employee'}]},
      {title:'Hệ thống',items:[{id:'settings',label:'Cài đặt & dữ liệu',icon:'settings-2',href:'#settings'}]}
    ]
  });
  const actions = {
    dashboard: () => goPage('dashboard'), analytics: () => goPage('analytics'),
    employees: () => goFilter('',''), 'add-employee': () => openForm(),
    'backup-json': () => saveFile(), library: () => openHrLibrary(),
    settings: () => goPage('settings')
  };
  const iconPaths={house:'<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>', 'chart-no-axes-column':'<path d="M5 20V12M12 20V4M19 20v-9"/>', users:'<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5"/>','user-round-plus':'<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M19 8v8M15 12h8"/>','settings-2':'<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="2"/><circle cx="16" cy="17" r="2"/>',download:'<path d="M12 3v12m-5-5 5 5 5-5M4 19h16"/>',database:'<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 4 16 4 16 0V5M4 12c0 4 16 4 16 0"/>'};
  const iconSvg=(name)=>'<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'+(iconPaths[name]||iconPaths.house)+'</svg>';
  function apply(config) {
    nav.replaceChildren();
    for (const group of config.groups) {
      const visibleItems=group.items.filter(item=>!['backup-json','library'].includes(item.id));
      if(!visibleItems.length)continue;
      const h = document.createElement('div'); h.className='sec'; h.textContent=group.title; nav.append(h);
      for (const item of visibleItems) {
        const a = document.createElement('a'); a.className='item'; a.dataset.hrItem=item.id;
        const ic=document.createElement('span');ic.className='ic';ic.innerHTML=iconSvg(item.icon);a.append(ic,document.createTextNode(item.label));
        const page = item.href.startsWith('#') ? item.href.slice(1) : '';
        if (actions[page]) a.onclick=()=>{actions[page]();toggleSide(false);};
        else if (item.href.startsWith('https://')) {a.href=item.href;a.target='_blank';a.rel='noopener noreferrer';}
        else {a.setAttribute('aria-disabled','true');}
        if (page==='employees') {a.id='navAll';const count=document.createElement('span');count.id='cntAll';count.className='cnt';a.append(count);}
        if (page==='dashboard') a.id='navDash';
        if (page==='analytics') a.id='navAnalytics';
        nav.append(a);
      }
    }
    // Always expose the settings entry even for older saved sidebar configurations.
    if(!nav.querySelector('[data-hr-item="settings"]')){
      const group=document.createElement('div');group.className='sec';group.textContent='Hệ thống';nav.append(group);
      const link=document.createElement('a');link.className='item';link.dataset.hrItem='settings';link.innerHTML='<span class="ic">'+iconSvg('settings-2')+'</span>Cài đặt & dữ liệu';link.onclick=()=>{goPage('settings');toggleSide(false);};nav.append(link);
    }
    // HR's renderNav() expects these elements and updates dynamic filters.
    for (const id of ['navDash','navAnalytics','navAll']) {
      if (!document.getElementById(id)) {
        const hidden=document.createElement('a');hidden.id=id;hidden.hidden=true;nav.append(hidden);
        if (id==='navAll') {const count=document.createElement('span');count.id='cntAll';hidden.append(count);}
      }
    }
    const deptTitle=document.createElement('div');deptTitle.className='sec';deptTitle.textContent='Phòng ban';nav.append(deptTitle);
    const dept=document.createElement('div');dept.id='navDept';nav.append(dept);
    const statusTitle=document.createElement('div');statusTitle.className='sec';statusTitle.textContent='Trạng thái';nav.append(statusTitle);
    const status=document.createElement('div');status.id='navStatus';nav.append(status);
    const system=Array.from(nav.querySelectorAll('.sec')).find(el=>el.textContent==='Hệ thống');
    if(system){const settings=nav.querySelector('[data-hr-item="settings"]');nav.append(system);if(settings)nav.append(settings);}
    brand.querySelector('b').textContent=config.header.title;
    brand.querySelector('small').textContent=config.header.subtitle;
    if (typeof renderNav === 'function') renderNav();
  }
  function mount(open) {
    const button=document.createElement('button');button.type='button';button.className='btn btn-light';button.textContent='⚙ Cấu hình menu';button.dataset.openMenu='';
    const header=document.querySelector('.main header > div:last-child');
    if (header) header.prepend(button);

  }
  return {defaults,apply,mount};
})();
