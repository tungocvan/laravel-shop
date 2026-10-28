  function render() {
    const fields=$('[data-fields]');fields.replaceChildren();
    for (const [section,names] of Object.entries({header:['title','subtitle','icon','href'],footer:['credit','href']})) {
      const h=document.createElement('h4');h.textContent=section==='header'?'Header':'Footer';fields.append(h);
      for(const name of names) field(fields,name,working[section][name],v=>working[section][name]=v);
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
    try {const r=await fetch(url+'/presets');if(!r.ok)throw Error('HTTP '+r.status);const data=await r.json();const select=$('[data-presets]');select.replaceChildren(new Option('Chọn file',''));for(const p of data.presets||[])select.add(new Option(p.name,p.id));}
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
  const settings = document.querySelector('#page-settings');
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
  $('[data-load]').onclick=async()=>{try{const id=$('[data-presets]').value;if(!id)return;const r=await fetch(url+'/presets/'+encodeURIComponent(id));if(!r.ok)throw Error('HTTP '+r.status);const data=await r.json();validate(data.config);if(confirm('Thay bản chỉnh sửa bằng JSON trên server?')){working=clone(data.config);render();status('Đã nhập vào bản chỉnh sửa.');}}catch(e){status(e.message);}};
  $('[data-store-preset]').onclick=async()=>{try{validate(working);const name=$('[data-name]').value.trim();if(!name)throw Error('Nhập tên file.');const r=await authorized('POST',url+'/presets',{name,config:working});if(r){await refresh();status('Đã lưu file JSON.');}}catch(e){status(e.message);}};
  $('[data-delete-preset]').onclick=async()=>{try{const id=$('[data-presets]').value;if(!id||!confirm('Xóa file JSON này?'))return;const r=await authorized('DELETE',url+'/presets/'+encodeURIComponent(id));if(r){await refresh();status('Đã xóa file.');}}catch(e){status(e.message);}};
  $('[data-export]').onclick=()=>{try{validate(working);const blob=new Blob([JSON.stringify(working,null,2)],{type:'application/json'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=slug+'-menu.json';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);}catch(e){status(e.message);}};
  $('[data-import]').onchange=async e=>{const file=e.target.files[0];if(!file)return;try{if(file.size>65536)throw Error('File quá 64 KiB.');const c=validate(JSON.parse(await file.text()));if(confirm('Nhập menu JSON?')){working=clone(c);render();}}catch(err){status(err.message);}e.target.value='';};
  (async()=>{let c=defaults;try{const r=await fetch(url,{cache:'no-store'});if(r.ok){const data=await r.json();if(data.config)c=validate(data.config);}}catch{}try{const local=localStorage.getItem(key);if(local)c=validate(JSON.parse(local));}catch{}apply(c);})();
})();
