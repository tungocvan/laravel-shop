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
      {title:'Tiện ích',items:[
        {id:'add-employee',label:'Thêm nhân viên',icon:'user-plus',href:'#add-employee'},
        {id:'backup-json',label:'Sao lưu JSON',icon:'download',href:'#backup-json'},
        {id:'library',label:'Thư viện JSON',icon:'database',href:'#library'}]},
      {title:'Hệ thống',items:[{id:'settings',label:'Cài đặt & dữ liệu',icon:'settings',href:'#settings'}]}
    ]
  });
  const actions = {
    dashboard: () => goPage('dashboard'), analytics: () => goPage('analytics'),
    employees: () => goFilter('',''), 'add-employee': () => openForm(),
    'backup-json': () => saveFile(), library: () => openHrLibrary(),
    settings: () => goPage('settings')
  };
  function apply(config) {
    nav.replaceChildren();
    for (const group of config.groups) {
      const h = document.createElement('div'); h.className='sec'; h.textContent=group.title; nav.append(h);
      for (const item of group.items) {
        const a = document.createElement('a'); a.className='item'; a.dataset.hrItem=item.id;
        const ic=document.createElement('span');ic.className='ic';ic.textContent='•';a.append(ic,document.createTextNode(item.label));
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
      const link=document.createElement('a');link.className='item';link.dataset.hrItem='settings';link.textContent='⚙ Cài đặt & dữ liệu';link.onclick=()=>{goPage('settings');toggleSide(false);};nav.append(link);
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
