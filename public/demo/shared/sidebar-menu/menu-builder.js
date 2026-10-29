/* Shared demo menu editor: DOM adapter, no business data mutations. */
(() => {
  'use strict';
  const root = document.querySelector('[data-demo-menu]');
  if (!root) return;
  const slug = root.dataset.demoMenu;
  const url = '/demo/' + slug + '/menu';
  const key = slug === 'quan-ly-cong-no' ? 'debt_management_sidebar_menu_v1' : 'shared_demo_sidebar_menu_v1_' + slug;
  const debtMode = slug === 'quan-ly-cong-no' || slug === 'quan-ly-nhan-su' || slug === 'cham-cong-tinh-luong';
  const bridge = debtMode ? (slug === 'quan-ly-nhan-su' ? window.HrDemoMenuBridge : slug === 'cham-cong-tinh-luong' ? window.AttendancePayrollMenuBridge : window.DebtDemoMenuBridge) : null;
  const nav = document.querySelector('#nav');
  const sidebar = document.querySelector('#sb');
  if (debtMode ? !bridge : (!nav || !sidebar)) return;
  const original = nav ? Array.from(nav.children) : [];
  const brand = sidebar?.querySelector('.brand');
  const footer = sidebar?.querySelector('.sbfoot');
  const originalBrand = brand?.cloneNode(true);
  const originalFooter = footer?.textContent || '';
  const known = new Map(Array.from(nav?.querySelectorAll('a[data-page]') || []).map(a => [a.dataset.page, a]));
  const defaultConfig = () => {
    const groups = [];
    for (const node of original) {
      if (node.tagName === 'H6') groups.push({title: node.textContent.trim(), items: []});
      else if (node.matches('a[data-page]')) {
        if (!groups.length) groups.push({title:'Menu',items:[]});
        groups[groups.length - 1].items.push({
          id: node.dataset.page, label: node.textContent.trim(),
          icon: node.dataset.ic || 'link', href: '#' + node.dataset.page
        });
      }
    }
    return {version:1, header:{title:brand?.querySelector('b')?.textContent || '',subtitle:brand?.querySelector('small')?.textContent || '',icon:'menu',href:'#dashboard'}, footer:{credit:originalFooter,href:'',showSave:false}, groups};
  };
  const clone = x => JSON.parse(JSON.stringify(x));
  const migrate = config => slug === 'cham-cong-tinh-luong' ? bridge.normalize(clone(config)) : config;
  const defaults = debtMode ? bridge.defaults() : defaultConfig();
  let applied = clone(defaults), working = null;
  const validHref = (s, empty=false) => typeof s === 'string' && ((empty && !s) || /^#[a-z0-9-]{1,64}$/.test(s) || /^https:\/\/[a-z0-9][a-z0-9.-]+(?:\/[^\s]*)?$/i.test(s) && s.length <= 250);
  function validate(c) {
    if (!c || c.version !== 1 || !Array.isArray(c.groups) || c.groups.length > 20) throw Error('Nhóm menu không hợp lệ.');
    for (const [section, fields] of Object.entries({header:['title','subtitle','icon','href'],footer:['credit','href']})) {
      if (!c[section] || fields.some(f => typeof c[section][f] !== 'string' || c[section][f].length > 250) || !validHref(c[section].href,true)) throw Error('Header/footer không hợp lệ.');
    }
    if (!/^[a-z0-9-]{1,48}$/.test(c.header.icon) || typeof c.footer.showSave !== 'boolean') throw Error('Icon/footer không hợp lệ.');
    const ids = new Set(); let count = 0;
    for (const g of c.groups) {
      if (typeof g.title !== 'string' || g.title.length > 60 || !Array.isArray(g.items)) throw Error('Nhóm không hợp lệ.');
      for (const i of g.items) {
        if (++count > 100 || !i || !/^[a-z0-9-]{1,64}$/.test(i.id) || ids.has(i.id) ||
          typeof i.label !== 'string' || !i.label.trim() || i.label.length > 80 ||
          typeof i.icon !== 'string' || !/^[a-z0-9-]{1,48}$/.test(i.icon) || !validHref(i.href)) throw Error('Mục menu không hợp lệ.');
        ids.add(i.id);
      }
    }
    return c;
  }
  function apply(c) {
    c = migrate(c);
    applied = clone(validate(c));
    if (debtMode) { bridge.apply(c); document.dispatchEvent(new CustomEvent('demo-menu:applied',{detail:{slug}})); return; }
    nav.replaceChildren();
    for (const group of c.groups) {
      const h = document.createElement('h6'); h.textContent = group.title; nav.append(h);
      for (const item of group.items) {
        const page = item.href.startsWith('#') ? item.href.slice(1) : '';
        const existing = known.get(page);
        const a = existing ? existing.cloneNode(true) : document.createElement('a');
        if (existing) {
          // Preserve the demo's SVG icons and live badge nodes.
          const textNode = Array.from(a.childNodes).find(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim());
          if (textNode) textNode.textContent = item.label;
          else {
            // Some demos wrap their label in a span beside the SVG and badge.
            // Update that span rather than adding a duplicate text node.
            const labelSpan = Array.from(a.children).find(el =>
              el.tagName === 'SPAN' && !el.classList.contains('badge'));
            if (labelSpan) labelSpan.textContent = item.label;
            else a.append(document.createTextNode(item.label));
          }
        } else a.textContent = item.label;
        if (existing) {
          a.dataset.page = page;
          // Original detached anchor retains the demo's own go() handler.
          a.onclick = () => existing.click();
        } else if (item.href.startsWith('https://')) {
          a.href = item.href; a.target = '_blank'; a.rel = 'noopener noreferrer';
        } else {
          a.href = '#'; a.setAttribute('aria-disabled', 'true');
        }
        a.dataset.menuItem = item.id;
        nav.append(a);
      }
    }
    if (brand) {
      const b=brand.querySelector('b'),small=brand.querySelector('small');
      if (b) b.textContent=c.header.title;
      if (small) small.textContent=c.header.subtitle;
    }
    if (footer) {
      footer.replaceChildren();
      if (c.footer.href) {
        const link = document.createElement('a');
        link.textContent = c.footer.credit;
        link.href = c.footer.href;
        if (c.footer.href.startsWith('https://')) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
        else if (c.footer.href.startsWith('#')) { const page = c.footer.href.slice(1); link.onclick = e => { const existing = known.get(page); if (existing) { e.preventDefault(); existing.click(); } }; }
        footer.append(link);
      } else footer.textContent = c.footer.credit;
    }
    document.dispatchEvent(new CustomEvent('demo-menu:applied',{detail:{slug}}));
  }
  const dlg = document.createElement('dialog'); dlg.className='shared-menu-dialog';
  dlg.innerHTML='<div class="shared-menu-shell"><header><div><h3>Chỉnh sửa menu</h3><p>Thiết lập sidebar cho demo</p></div><button type="button" data-close aria-label="Đóng">×</button></header><nav class="shared-menu-tabs" role="tablist" aria-label="Các phần cấu hình"><button type="button" role="tab" data-tab="menu">Menu</button><button type="button" role="tab" data-tab="header">Header</button><button type="button" role="tab" data-tab="footer">Footer</button><button type="button" role="tab" data-tab="library">Thư viện JSON</button></nav><div class="shared-menu-body"><div data-panel="menu" role="tabpanel"><div data-groups></div></div><div data-panel="header" role="tabpanel" hidden><div data-header-fields class="shared-menu-fields"></div></div><div data-panel="footer" role="tabpanel" hidden><div data-footer-fields class="shared-menu-fields"></div></div><div data-panel="library" role="tabpanel" hidden><section class="shared-menu-library"><h4>Thư viện JSON server</h4><input data-name placeholder="Tên cấu hình" aria-label="Tên cấu hình"><select data-presets aria-label="Cấu hình đã lưu"><option value="">Chọn file</option></select><button data-refresh>Làm mới</button><button data-load>Nạp JSON</button><button data-store-preset>Lưu file</button><button data-delete-preset>Xóa file</button></section></div></div><footer><div class="shared-menu-actions"><button data-add-group>+ Nhóm</button><button data-reset>Mặc định</button><button data-export>Xuất JSON</button><label>Nhập JSON<input type="file" accept=".json,application/json" data-import hidden></label><button data-preview>Cập nhật giao diện</button><button data-save>Lưu lên server</button></div><p role="status" aria-live="polite" data-status></p></footer></div>';
  if (slug === 'quan-ly-thiet-bi-bao-tri') {
    const button=document.createElement('button');button.type='button';button.dataset.makeDefault='';button.textContent='Đặt làm mặc định';
    dlg.querySelector('[data-delete-preset]').after(button);
  }
  document.body.append(dlg);
  const $ = s => dlg.querySelector(s);
  const status = (s) => { $('[data-status]').textContent=s; };
  function field(parent,label,value,change) {
    const wrap=document.createElement('label'); wrap.textContent=label;
    const input=document.createElement('input'); input.value=value; input.maxLength=250;
    input.oninput=()=>change(input.value); wrap.append(input); parent.append(wrap);
  }
  function selectTab(tab) {
    dlg.querySelectorAll('[data-tab]').forEach(button => {
      const active = button.dataset.tab === tab;
      button.setAttribute('aria-selected', String(active));
      button.tabIndex = active ? 0 : -1;
    });
    dlg.querySelectorAll('[data-panel]').forEach(panel => { panel.hidden = panel.dataset.panel !== tab; });
    $('[data-add-group]').hidden = tab !== 'menu';
  }
  dlg.querySelectorAll('[data-tab]').forEach(button => {
    button.onclick = () => selectTab(button.dataset.tab);
    button.onkeydown = e => {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      e.preventDefault();
      const tabs = Array.from(dlg.querySelectorAll('[data-tab]'));
      const next = tabs[(tabs.indexOf(button) + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
      selectTab(next.dataset.tab);
      next.focus();
    };
  });
  selectTab('menu');
  function render() {
    for (const [section,names] of Object.entries({header:['title','subtitle','icon','href'],footer:['credit','href']})) {
      const fields=$('[data-'+section+'-fields]');fields.replaceChildren();
      for(const name of names) field(fields,name,working[section][name],v=>working[section][name]=v);
    }
    const groups=$('[data-groups]');groups.replaceChildren();
    working.groups.forEach((g,gi)=>{
      const box=document.createElement('section');box.className='shared-menu-group';
      field(box,'Tên nhóm',g.title,v=>g.title=v);
      const remove=document.createElement('button');remove.textContent='Xóa nhóm';remove.onclick=()=>{if(confirm('Xóa nhóm?')){working.groups.splice(gi,1);render();}};box.append(remove);
      const add=document.createElement('button');add.textContent='+ Mục';add.onclick=()=>{g.items.push({id:'custom-'+Date.now().toString(36),label:'Mục mới',icon:'link',href:'#dashboard'});render();};box.append(add);
      g.items.forEach((item,ii)=>{
        const line=document.createElement('div');line.className='shared-menu-item';line.draggable=true;
        line.ondragstart=e=>e.dataTransfer.setData('text/plain',JSON.stringify([gi,ii]));
        line.ondragover=e=>e.preventDefault();
        line.ondrop=e=>{e.preventDefault();try{const [from,i]=JSON.parse(e.dataTransfer.getData('text/plain'));const source=working.groups[from]?.items;if(!source?.[i])return;const [moved]=source.splice(i,1);g.items.splice(from===gi&&i<ii?ii-1:ii,0,moved);render();}catch{}};
        for(const k of ['label','icon','href'])field(line,k,item[k],v=>item[k]=v);
        const dup=document.createElement('button');dup.textContent='Nhân bản';dup.onclick=()=>{g.items.splice(ii+1,0,{...item,id:'custom-'+Date.now().toString(36)});render();};line.append(dup);
        const del=document.createElement('button');del.textContent='Xóa';del.onclick=()=>{g.items.splice(ii,1);render();};line.append(del);
        box.append(line);
      });
      groups.append(box);
    });
  }
  async function refresh() {
    try {const r=await fetch(url+'/presets');if(!r.ok)throw Error('HTTP '+r.status);const data=await r.json();const select=$('[data-presets]');select.replaceChildren(new Option('Chọn file',''));for(const p of data.presets||[])select.add(new Option(p.name+(p.id===data.defaultId?' (Mặc định)':''),p.id));}
    catch(e){status('Không tải được thư viện: '+e.message);}
  }
  async function authorized(method,path,body) {
    const code=prompt('Nhập mã quản trị để ghi server:');
    if(code===null)return null;
    const r=await fetch(path,{method,headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','X-Demo-Backup-Code':code},body:body?JSON.stringify(body):undefined});
    if(!r.ok)throw Error('Server từ chối (HTTP '+r.status+').');
    return r;
  }
  document.addEventListener('click',e=>{if(!e.target.closest('[data-open-menu]'))return;working=clone(applied);render();selectTab('menu');refresh();dlg.showModal();});
  const settings = debtMode ? null : document.querySelector('#page-settings');
  if (debtMode) bridge.mount(() => { working=clone(applied);render();selectTab('menu');refresh();dlg.showModal(); });
  if (settings) {
    const mount = () => {
      if (settings.querySelector('[data-open-menu]')) return;
      const card = document.createElement('div');
      card.className = 'shared-menu-entry';
      card.innerHTML = '<h4>Sidebar menu</h4><p>Chỉnh sửa nhóm, mục, header/footer và thư viện JSON riêng cho demo này.</p><button type="button" class="btn pri" data-open-menu>Chỉnh sửa menu</button>';
      settings.prepend(card);
    };
    const observer = new MutationObserver(() => mount());
    observer.observe(settings, {childList:true});
    mount();
  }
  $('[data-close]').onclick=()=>dlg.close();
  $('[data-add-group]').onclick=()=>{working.groups.push({title:'Nhóm mới',items:[]});render();};
  $('[data-reset]').onclick=()=>{if(confirm('Khôi phục menu mặc định?')){working=clone(defaults);render();}};
  $('[data-preview]').onclick=()=>{try{validate(working);localStorage.setItem(key,JSON.stringify(working));apply(working);status('Đã cập nhật và lưu trong trình duyệt.');}catch(e){status(e.message);}};
  $('[data-save]').onclick=async()=>{try{validate(working);const r=await authorized('POST',url,{config:working});if(!r)return;localStorage.removeItem(key);apply(working);status('Đã lưu menu lên server.');}catch(e){status(e.message);}};
  $('[data-refresh]').onclick=refresh;
  $('[data-load]').onclick=async()=>{try{const id=$('[data-presets]').value;if(!id)return;const r=await fetch(url+'/presets/'+encodeURIComponent(id));if(!r.ok)throw Error('HTTP '+r.status);const data=await r.json();validate(migrate(data.config));if(confirm('Thay bản chỉnh sửa bằng JSON trên server?')){working=migrate(data.config);render();status('Đã nhập vào bản chỉnh sửa.');}}catch(e){status(e.message);}};
  $('[data-store-preset]').onclick=async()=>{try{validate(working);const name=$('[data-name]').value.trim();if(!name)throw Error('Nhập tên file.');const r=await authorized('POST',url+'/presets',{name,config:working});if(r){await refresh();status('Đã lưu file JSON.');}}catch(e){status(e.message);}};
  const defaultButton=$('[data-make-default]');
  if(defaultButton)defaultButton.onclick=async()=>{
    try {
      const id=$('[data-presets]').value;
      if(!id)throw Error('Chọn file JSON trước.');
      if(!confirm('Đặt file đã chọn làm menu mặc định trên server?'))return;
      const r=await authorized('PUT',url+'/presets/'+encodeURIComponent(id)+'/default');
      if(!r)return;
      localStorage.removeItem(key);
      const response=await fetch(url,{cache:'no-store'});
      if(!response.ok)throw Error('Không tải được menu mặc định.');
      const data=await response.json();
      apply(validate(migrate(data.config)));
      working=clone(applied);render();
      await refresh();$('[data-presets]').value=id;
      status('Đã đặt làm mặc định và áp dụng menu trên server.');
    }catch(e){status(e.message);}
  };
  $('[data-delete-preset]').onclick=async()=>{try{const id=$('[data-presets]').value;if(!id||!confirm('Xóa file JSON này?'))return;const r=await authorized('DELETE',url+'/presets/'+encodeURIComponent(id));if(r){await refresh();status('Đã xóa file.');}}catch(e){status(e.message);}};
  $('[data-export]').onclick=()=>{try{validate(working);const blob=new Blob([JSON.stringify(working,null,2)],{type:'application/json'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=slug+'-menu.json';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);}catch(e){status(e.message);}};
  $('[data-import]').onchange=async e=>{const file=e.target.files[0];if(!file)return;try{if(file.size>65536)throw Error('File quá 64 KiB.');const c=validate(migrate(JSON.parse(await file.text())));if(confirm('Nhập menu JSON?')){working=clone(c);render();}}catch(err){status(err.message);}e.target.value='';};
  (async()=>{let c=defaults;try{const r=await fetch(url,{cache:'no-store'});if(r.ok){const data=await r.json();if(data.config)c=validate(migrate(data.config));}}catch{}try{const local=localStorage.getItem(key);if(local)c=validate(migrate(JSON.parse(local)));}catch{}apply(c);})();
})();
