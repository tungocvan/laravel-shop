/* Costing demo extensions: browser state stays authoritative. */
(() => {
  'use strict';
  const slug='tinh-gia-thanh-san-pham';
  const base='/demo/'+slug+'/datasets';
  const themeKey='product_costing_theme_v1';
  const themes={original:'Màu gốc',ruby:'Ruby',ocean:'Ocean',emerald:'Emerald',indigo:'Indigo'};
  const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
  const code=()=>document.getElementById('cost-dataset-code')?.value||'';
  const notice=(message,error=false)=>typeof toast==='function'?toast(message,error?'err':''):alert(message);
  function applyTheme(theme){
    if(!Object.hasOwn(themes,theme))theme='original';
    document.documentElement.dataset.costTheme=theme;
    try{localStorage.setItem(themeKey,theme)}catch(_){}
    document.querySelectorAll('[data-cost-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.costTheme===theme)));
  }
  try{applyTheme(localStorage.getItem(themeKey)||'original')}catch(_){applyTheme('original')}
  async function request(url,options={}){
    if(!code())throw Error('Vui lòng nhập mã bảo vệ server.');
    const response=await fetch(url,{credentials:'same-origin',cache:'no-store',...options,headers:{'Accept':'application/json','X-Demo-Backup-Code':code(),...(options.headers||{})}});
    if(!response.ok)throw Error('Server HTTP '+response.status);
    return response.json();
  }
  async function refresh(){
    const list=document.getElementById('cost-datasets-list');if(!list)return;
    list.textContent='Đang tải thư viện...';
    try{
      const result=await request(base);list.replaceChildren();
      if(!result.datasets?.length){list.textContent='Chưa có bản lưu.';return;}
      for(const entry of result.datasets){
        const row=document.createElement('div');row.className='cost-dataset-row';
        const label=document.createElement('span');label.textContent=entry.name+' · '+entry.savedAt;
        const button=document.createElement('button');button.type='button';button.className='btn sm';button.textContent='Khôi phục';
        button.onclick=async()=>{
          if(!confirm('Ghi đè dữ liệu hiện tại bằng bản "'+entry.name+'"? Hãy xuất JSON trước khi tiếp tục.'))return;
          try{
            const snapshot=await request(base+'/'+encodeURIComponent(entry.id));
            if(!APP.valid(snapshot.data))throw Error('Cấu trúc dữ liệu không tương thích.');
            S=snapshot.data;save();refreshChrome();rerender();notice('Đã khôi phục dữ liệu vào trình duyệt.');
          }catch(e){notice(e.message,true)}
        };
        row.append(label,button);list.append(row);
      }
    }catch(e){list.textContent='Không tải được thư viện: '+e.message}
  }
  async function upload(){
    const name=document.getElementById('cost-dataset-name')?.value.trim();
    if(!name||name.length<2||name.length>100){notice('Tên bản lưu phải từ 2 đến 100 ký tự.',true);return}
    if(!APP.valid(S)){notice('Dữ liệu không hợp lệ.',true);return}
    if(!confirm('Lưu một bản sao dữ liệu hiện tại lên server?'))return;
    try{
      await request(base,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({name,data:S})});
      notice('Đã lưu bản JSON trên server.');await refresh();
    }catch(e){notice(e.message,true)}
  }
  function mount(){
    const settings=document.getElementById('page-settings');if(!settings)return;
    const grid=settings.querySelector('.set-grid')||settings;
    const card=document.createElement('section');card.className='card cost-integrations';
    const title=document.createElement('h4');title.textContent='Giao diện và thư viện JSON server';
    const info=document.createElement('p');info.textContent='Dữ liệu làm việc lưu trong trình duyệt. Bản JSON server chỉ là bản sao.';
    const options=document.createElement('div');options.className='cost-theme-options';
    for(const [id,label] of Object.entries(themes)){
      const button=document.createElement('button');button.type='button';button.dataset.costTheme=id;button.textContent=label;
      button.onclick=()=>applyTheme(id);options.append(button);
    }
    const nameLabel=document.createElement('label');nameLabel.textContent='Tên bản lưu';nameLabel.htmlFor='cost-dataset-name';
    const name=document.createElement('input');name.id='cost-dataset-name';name.maxLength=100;name.placeholder='Ví dụ: Giá thành tháng 10';
    const codeLabel=document.createElement('label');codeLabel.textContent='Mã bảo vệ server';codeLabel.htmlFor='cost-dataset-code';
    const secret=document.createElement('input');secret.id='cost-dataset-code';secret.type='password';secret.autocomplete='off';
    const actions=document.createElement('div');actions.className='cost-dataset-actions';
    const saveBtn=document.createElement('button');saveBtn.type='button';saveBtn.className='btn pri';saveBtn.textContent='Lưu lên server';saveBtn.onclick=upload;
    const refreshBtn=document.createElement('button');refreshBtn.type='button';refreshBtn.className='btn';refreshBtn.textContent='Tải danh sách';refreshBtn.onclick=refresh;
    actions.append(saveBtn,refreshBtn);
    const list=document.createElement('div');list.id='cost-datasets-list';list.setAttribute('aria-live','polite');list.textContent='Nhập mã bảo vệ rồi tải danh sách.';
    card.append(title,options,info,nameLabel,name,codeLabel,secret,actions,list);grid.append(card);
    applyTheme(document.documentElement.dataset.costTheme||'original');
  }
  if(typeof APP!=='undefined'&&APP.pages?.settings){
    const old=APP.pages.settings[2];APP.pages.settings[2]=function(){old();mount()};
    if(typeof CUR!=='undefined'&&CUR==='settings')mount();
  }
})();
