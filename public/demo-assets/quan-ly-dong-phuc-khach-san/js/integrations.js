/* Hotel uniform demo integrations; never change other demos. */
(() => {
  'use strict';
  const storageKey = 'hotel_uniform_theme_v1';
  const palettes = {
    original: ['#2e7d32','#246227','#143121','#0c1d14','#194124','#71a774','#549457','#e6efe6','#f5f9f5','#c9ddca','#97be99','#d1e2d2'],
    ruby: ['#ad3151','#85233d','#411829','#280e19','#572137','#e08096','#c95877','#f9e8ed','#fff6f8','#f5d9e2','#d9a8b7','#f4d7df'],
    ocean: ['#087d9b','#08647d','#103744','#09242e','#155065','#60b9d0','#2b9bb8','#e1f3f8','#f4fbfd','#cce7ee','#9bc9d6','#d3edf4'],
    emerald: ['#087f64','#07664f','#103d32','#092820','#185344','#58bb9e','#329f81','#e0f4eb','#f4fcf8','#ccebdd','#9fd2bd','#d6f0e4'],
    indigo: ['#5154b5','#3c408e','#24264f','#171831','#303463','#9295e2','#7176cf','#ececfa','#f8f8fe','#ddddf7','#aeb0d9','#e0e1f8']
  };
  const vars = ['--chu-dao','--cd-dam','--cd-sb','--cd-sb0','--cd-sb2','--cd-sang','--cd-sang2','--cd-nhat','--cd-nhat2','--cd-sbtext','--cd-sbmuted','--cd-hk'];
  const themeNames = {original:'Nguyên bản',ruby:'Ruby',ocean:'Ocean',emerald:'Emerald',indigo:'Indigo'};
  function setTheme(name) {
    if (!palettes[name]) name='original';
    vars.forEach((key,i)=>document.documentElement.style.setProperty(key,palettes[name][i]));
    try {localStorage.setItem(storageKey,name);}catch(e){}
    document.querySelectorAll('[data-uniform-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.uniformTheme===name)));
  }
  try {setTheme(localStorage.getItem(storageKey)||'original');}catch(e){setTheme('original');}
  function downloadJSON() {
    if (!window.S && typeof S==='undefined') return;
    const state = JSON.parse(JSON.stringify(S));
    const a=document.createElement('a'),url=URL.createObjectURL(new Blob([JSON.stringify(state,null,2)],{type:'application/json'}));
    a.href=url;a.download='dong-phuc-khach-san-'+new Date().toISOString().slice(0,10)+'.json';a.click();
    setTimeout(()=>URL.revokeObjectURL(url),1000);
    if (typeof toast==='function') toast('Đã tải bản sao lưu JSON. Dữ liệu hiện tại vẫn được lưu trong trình duyệt.');
  }
  window.saveToFile=downloadJSON;
  function mount() {
    const page=document.querySelector('#page-settings');
    if (!page) return;
    if (!page.querySelector('#uniformThemePanel')) {
      const box=document.createElement('div');box.className='card noprint';box.id='uniformThemePanel';box.style.cssText='padding:18px;margin-bottom:16px';
      const h=document.createElement('h3');h.textContent='Giao diện · 5 themes';box.append(h);
      const buttons=document.createElement('div');buttons.style.cssText='display:flex;flex-wrap:wrap;gap:8px;margin-top:12px';
      Object.entries(themeNames).forEach(([key,label])=>{const b=document.createElement('button');b.type='button';b.className='btn';b.dataset.uniformTheme=key;b.textContent=label;b.onclick=()=>setTheme(key);buttons.append(b);});
      box.append(buttons);page.prepend(box);
      let selected='original';try{selected=localStorage.getItem(storageKey)||selected}catch(e){}
      setTheme(selected);
    }
    if (!page.querySelector('#uniformJsonRestore')) {
      const card=document.createElement('div');card.className='card noprint';card.id='uniformJsonRestore';card.style.cssText='padding:18px;margin-bottom:16px';
      card.innerHTML='<h3>Sao lưu / khôi phục JSON</h3><p class="muted" style="margin:8px 0">Sao lưu độc lập, không ghi đè file Blade hoặc dữ liệu của demo khác.</p>';
      const save=document.createElement('button');save.className='btn pri';save.textContent='Tải bản sao lưu JSON';save.onclick=downloadJSON;
      const input=document.createElement('input');input.type='file';input.accept='.json,application/json';input.className='inp';input.style.cssText='margin-top:10px';
      input.onchange=async()=>{const f=input.files?.[0];if(!f)return;try{if(f.size>10*1024*1024)throw Error('File vượt 10 MB');const data=JSON.parse(await f.text());if(!APP.valid(data))throw Error('JSON không đúng cấu trúc dữ liệu đồng phục');if(!confirm('Khôi phục JSON sẽ thay thế dữ liệu đồng phục hiện tại trong trình duyệt. Tiếp tục?'))return;S=data;APP.fix?.(S);save();rerender();toast('Đã khôi phục dữ liệu JSON.');}catch(e){toast(e.message,'err')}finally{input.value=''}};
      card.append(save,input);page.prepend(card);
    }
    document.querySelectorAll('#saveBtn span.t').forEach(x=>x.textContent='Sao lưu JSON');
    document.querySelectorAll('#saveBtn').forEach(x=>x.title='Tải bản sao lưu JSON; dữ liệu làm việc tự lưu trong trình duyệt');
  }
  const settings=document.querySelector('#page-settings');
  if(settings){const observer=new MutationObserver(mount);observer.observe(settings,{childList:true});mount();}
  const saveBtn=document.querySelector('#saveBtn');if(saveBtn){saveBtn.title='Sao lưu dữ liệu JSON';const label=saveBtn.querySelector('.t');if(label)label.textContent='Sao lưu JSON';}
})();
