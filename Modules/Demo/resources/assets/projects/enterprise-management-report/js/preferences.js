/* Standalone UI preferences. No business-data mutation. */
(() => {
'use strict';
const prefix='enterprise_management_report_';
const themes={
  original:['#00838F','#006064','#26C6DA','#E0F5F7','#063B42','#0C4E57','#02252A','rgba(0,131,143,.30)'],
  ruby:['#C62828','#8E1B1B','#E57373','#FDE8E8','#4B171B','#641F25','#2C1115','rgba(198,40,40,.3)'],
  ocean:['#087E9B','#065B75','#38B7CD','#E0F5F7','#063B42','#0C4E57','#02252A','rgba(8,126,155,.3)'],
  emerald:['#16835D','#106445','#39B98B','#E4F7ED','#093D31','#115244','#05271F','rgba(22,131,93,.3)'],
  indigo:['#4F46E5','#3730A3','#818CF8','#EDEBFF','#252454','#32316C','#161537','rgba(79,70,229,.3)']
};
const tokens=['--chu-dao','--cd-dam','--cd-sang','--cd-nhat','--cd-toi','--cd-toi2','--cd-toi3','--cd-bong'];
const read=(k)=>{try{return localStorage.getItem(prefix+k)}catch{return null}};
const write=(k,v)=>{try{localStorage.setItem(prefix+k,v);return true}catch{return false}};
function applyTheme(name){
  const palette=themes[name]||themes.ocean;
  tokens.forEach((token,i)=>document.documentElement.style.setProperty(token,palette[i]));
  document.documentElement.dataset.enterpriseTheme=themes[name]?name:'ocean';
}
const defaultMenu=()=>({
 version:1,
 header:{title:'Báo Cáo Quản Trị',subtitle:'Báo cáo nội bộ hằng tháng',icon:'chart',href:'#dashboard'},
 footer:{credit:'File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel',href:'',showSave:false},
 groups:(()=>{let groups=[],group=null;APP.nav.forEach(n=>{if(typeof n==='string'){group={title:n,items:[]};groups.push(group)}else{if(!group){group={title:'Menu',items:[]};groups.push(group)}group.items.push({id:n.id,label:n.label,icon:n.icon,href:'#'+n.id,badge:n.badge||null})}});return groups})()
});
function validUrl(href,allowEmpty=false){
 if(allowEmpty&&!href)return true;
 return /^#[a-z0-9_-]+$/i.test(href)&&!!APP.pages[href.slice(1)] || /^https:\/\/[^\s/]+(?:\/[^\s]*)?$/i.test(href);
}
function normalizeMenu(raw){
 if(!raw||raw.version!==1||!Array.isArray(raw.groups)||raw.groups.length>30)throw Error('Cấu hình menu không hợp lệ');
 const clean=s=>String(s??'').trim().slice(0,100);
 const header={title:clean(raw.header?.title),subtitle:clean(raw.header?.subtitle),icon:clean(raw.header?.icon),href:clean(raw.header?.href)};
 const footer={credit:clean(raw.footer?.credit),href:clean(raw.footer?.href),showSave:false};
 if(!header.title||!validUrl(header.href)||!validUrl(footer.href,true))throw Error('Liên kết header/footer không hợp lệ');
 if(!IC[header.icon])throw Error('Icon header không hợp lệ');
 const ids=new Set();
 const groups=raw.groups.map(g=>{
  const title=clean(g.title);if(!title||!Array.isArray(g.items)||g.items.length>50)throw Error('Nhóm menu không hợp lệ');
  return {title,items:g.items.map(i=>{
   const id=clean(i.id),label=clean(i.label),icon=clean(i.icon),href=clean(i.href);
   if(!id||ids.has(id)||!label||!IC[icon]||!validUrl(href))throw Error('Mục menu trùng hoặc không hợp lệ');
   ids.add(id);return {id,label,icon,href,badge:i.badge||null};
  })};
 });
 return {version:1,header,footer,groups};
}
let menu=defaultMenu();
try{const saved=read('sidebar_v1');if(saved)menu=normalizeMenu(JSON.parse(saved))}catch{}
function renderMenu(){
 const brand=document.querySelector('#sb .brand'),nav=document.querySelector('#nav'),foot=document.querySelector('#sb .sbfoot');
 if(!brand||!nav||!foot)return;
 brand.innerHTML='<div class="logo">'+(IC[menu.header.icon]||IC.chart)+'</div><div><b>'+esc(menu.header.title)+'</b><small>'+esc(menu.header.subtitle)+'</small></div>';
 brand.setAttribute('role','link');brand.tabIndex=0;
 const openBrand=()=>{const h=menu.header.href;if(h.startsWith('#'))go(h.slice(1));else window.open(h,'_blank','noopener,noreferrer')};
 brand.onclick=openBrand;brand.onkeydown=e=>{if(e.key==='Enter')openBrand()};
 nav.innerHTML=menu.groups.map(g=>'<h6>'+esc(g.title)+'</h6>'+g.items.map(i=>'<a data-page="'+esc(i.href.startsWith('#')?i.href.slice(1):'')+'" data-href="'+esc(i.href)+'">'+IC[i.icon]+'<span>'+esc(i.label)+'</span>'+(i.badge?'<span class="badge" id="bd-'+esc(i.id)+'" style="display:none"></span>':'')+'</a>').join('')).join('');
 nav.querySelectorAll('a').forEach(a=>a.onclick=()=>{const h=a.dataset.href;if(h.startsWith('#'))go(h.slice(1));else window.open(h,'_blank','noopener,noreferrer')});
 foot.innerHTML=esc(menu.footer.credit)+(menu.footer.href?' <a target="_blank" rel="noopener noreferrer" href="'+esc(menu.footer.href)+'">Liên kết</a>':'');
 nav.querySelectorAll('a').forEach(a=>a.classList.toggle('active',a.dataset.page===CUR));
 refreshChrome();
}
const baseSettings=APP.pages.settings[2];
APP.pages.settings[2]=function(){
 baseSettings();
 const target=document.querySelector('#page-settings');if(!target)return;
 const wrap=document.createElement('section');wrap.className='enterprise-preferences';
 wrap.innerHTML='<div class="card"><h3>Giao diện & Sidebar Menu</h3><p class="muted">Tùy chỉnh riêng trên trình duyệt này; không ảnh hưởng dữ liệu báo cáo.</p><h4>Theme màu sắc</h4><div class="enterprise-themes" id="enterprise-themes"></div><button class="btn pri" id="enterprise-edit-menu">Chỉnh sửa menu sidebar và footer</button></div>';
 target.prepend(wrap);
 const palette=document.querySelector('#enterprise-themes');
 Object.keys(themes).forEach(name=>{
  const b=document.createElement('button');b.type='button';b.className='enterprise-theme-option';
  b.setAttribute('aria-label','Chọn giao diện '+(name==='original'?'Màu gốc':name));
  b.setAttribute('aria-pressed',String(document.documentElement.dataset.enterpriseTheme===name));
  const swatch=document.createElement('span');swatch.className='enterprise-theme-swatch';
  swatch.style.setProperty('--preview-dark',themes[name][4]);
  swatch.style.setProperty('--preview-light',themes[name][0]);
  swatch.setAttribute('aria-hidden','true');
  const label=document.createElement('span');label.className='enterprise-theme-name';
  label.textContent=name==='original'?'Màu gốc':name[0].toUpperCase()+name.slice(1);
  b.append(swatch,label);
  b.onclick=()=>{applyTheme(name);write('theme_v1',name);palette.querySelectorAll('button').forEach(x=>x.setAttribute('aria-pressed',String(x===b)))};
  palette.append(b);
 });
 document.querySelector('#enterprise-edit-menu').onclick=openEditor;
};

const menuApi='/demo/bao-cao-quan-tri-doanh-nghiep/menu';
const clone=v=>JSON.parse(JSON.stringify(v));
function messageDialog(title,detail){
 const box=document.createElement('div');box.className='enterprise-overlay';
 const card=document.createElement('div');card.className='enterprise-editor enterprise-dialog';card.setAttribute('role','alertdialog');card.setAttribute('aria-modal','true');
 const h=document.createElement('h3');h.textContent=title;
 const p=document.createElement('p');p.textContent=detail;
 const b=document.createElement('button');b.className='btn pri';b.textContent='Đã hiểu';b.onclick=()=>box.remove();
 card.append(h,p,b);box.append(card);document.body.append(box);b.focus();
}
async function requestMenu(path,method='GET',data=null,code=''){
 const headers={'Accept':'application/json'};
 if(method!=='GET'){headers['Content-Type']='application/json';headers['X-CSRF-TOKEN']=document.querySelector('meta[name="csrf-token"]')?.content||'';headers['X-Demo-Backup-Code']=code;}
 let response;
 try{response=await fetch(menuApi+path,{method,headers,body:data===null?undefined:JSON.stringify(data),cache:'no-store',credentials:'same-origin'})}
 catch{throw Error('Không kết nối được server. Kiểm tra mạng và thử lại.')}
 if(!response.ok){
  const info={403:'Sai mã quản trị (403).',419:'Phiên CSRF đã hết hạn (419). Tải lại trang.',422:'Cấu hình không hợp lệ (422).',429:'Thao tác quá nhanh (429).',503:'Chưa cấu hình mã quản trị trên server (503).',409:'Tên preset đã tồn tại (409).',413:'File JSON vượt giới hạn (413).' };
  throw Error(info[response.status]||'Server trả lỗi '+response.status);
 }
 return response.json();
}
function operatorCode(){
 return new Promise(resolve=>{
  const overlay=document.createElement('div');overlay.className='enterprise-overlay';
  const card=document.createElement('div');card.className='enterprise-editor enterprise-dialog';card.setAttribute('role','dialog');card.setAttribute('aria-modal','true');card.setAttribute('aria-label','Xác thực mã quản trị');
  const title=document.createElement('h3');title.textContent='Xác thực lưu trên server';
  const input=document.createElement('input');input.type='password';input.autocomplete='off';input.className='inp';input.placeholder='Mã quản trị';
  const reveal=document.createElement('button');reveal.type='button';reveal.className='btn sm';reveal.textContent='Hiện / Ẩn mã';reveal.onclick=()=>input.type=input.type==='password'?'text':'password';
  const buttons=document.createElement('div');buttons.className='enterprise-editor-actions';
  const cancel=document.createElement('button');cancel.className='btn';cancel.textContent='Hủy';
  const ok=document.createElement('button');ok.className='btn pri';ok.textContent='Xác nhận';
  const finish=value=>{input.value='';overlay.remove();resolve(value)};
  cancel.onclick=()=>finish(null);ok.onclick=()=>finish(input.value||null);
  input.onkeydown=e=>{if(e.key==='Enter')ok.click();if(e.key==='Escape')cancel.click()};
  buttons.append(cancel,ok);card.append(title,input,reveal,buttons);overlay.append(card);document.body.append(overlay);input.focus();
 });
}
async function protectedMenu(path,method,data){
 for(let attempt=0;attempt<3;attempt++){
  const code=await operatorCode();if(!code)return null;
  try{return await requestMenu(path,method,data,code)}
  catch(e){messageDialog('Không thể lưu',e.message);if(!e.message.includes('403'))return null}
 }
 return null;
}
function downloadMenu(data){
 const blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'});
 const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='sidebar-bao-cao-quan-tri.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
async function loadServerMenu(){
 if(read('sidebar_v1'))return;
 try{const response=await requestMenu('');if(response.config){menu=normalizeMenu(response.config);renderMenu()}}catch(e){console.warn('Sidebar server unavailable',e)}
}


function nameDialog(){
 return new Promise(resolve=>{
  const overlay=document.createElement('div');overlay.className='enterprise-overlay';
  const card=document.createElement('div');card.className='enterprise-editor enterprise-dialog';
  const title=document.createElement('h3');title.textContent='Tên preset JSON mới';
  const input=document.createElement('input');input.className='inp';input.maxLength=100;input.placeholder='Ví dụ: Menu phòng kinh doanh';
  const actions=document.createElement('div');actions.className='enterprise-editor-actions';
  const cancel=document.createElement('button');cancel.className='btn';cancel.textContent='Hủy';cancel.onclick=()=>{overlay.remove();resolve(null)};
  const save=document.createElement('button');save.className='btn pri';save.textContent='Tiếp tục';save.onclick=()=>{const v=input.value.trim();overlay.remove();resolve(v||null)};
  actions.append(cancel,save);card.append(title,input,actions);overlay.append(card);document.body.append(overlay);input.focus();
 });
}

function openEditor(){
 let draft=clone(menu);
 const overlay=document.createElement('div');overlay.className='enterprise-overlay';
 const panel=document.createElement('div');panel.className='enterprise-editor';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Chỉnh sửa sidebar');
 overlay.append(panel);document.body.append(overlay);
 const close=()=>overlay.remove();
 let presetSelect=null;
 let activeTab='menu';
 function field(label,value,change){
  const box=document.createElement('label');box.className='enterprise-field';box.textContent=label;
  const input=document.createElement('input');input.className='inp';input.value=value||'';input.oninput=()=>change(input.value);box.append(input);return box;
 }
 function action(label,fn){const b=document.createElement('button');b.type='button';b.className='btn sm';b.textContent=label;b.onclick=fn;return b}
 function draw(){
  panel.replaceChildren();
  const header=document.createElement('div');header.className='enterprise-editor-heading';
  const h=document.createElement('h3');h.textContent='Chỉnh sửa menu';
  const sub=document.createElement('p');sub.textContent='Thiết lập sidebar cho demo';
  const closeButton=action('×',close);closeButton.setAttribute('aria-label','Đóng chỉnh sửa menu');
  header.append(h,sub,closeButton);panel.append(header);
  const tabs=document.createElement('div');tabs.className='enterprise-editor-tabs';tabs.setAttribute('role','tablist');tabs.setAttribute('aria-label','Mục chỉnh sửa menu');panel.append(tabs);
  const body=document.createElement('div');body.className='enterprise-editor-body';body.setAttribute('role','tabpanel');panel.append(body);
  const panes={menu:document.createElement('div'),header:document.createElement('div'),footer:document.createElement('div'),library:document.createElement('div')};
  Object.entries(panes).forEach(([name,pane])=>{pane.className='enterprise-editor-pane enterprise-pane-'+name;body.append(pane)});
  const labels={menu:'Menu',header:'Header',footer:'Footer',library:'Thư viện JSON'};
  const buttons={};
  const selectTab=name=>{
   activeTab=name;
   Object.entries(panes).forEach(([id,pane])=>{pane.hidden=id!==name});
   Object.entries(buttons).forEach(([id,button])=>{button.setAttribute('aria-selected',String(id===name));button.tabIndex=id===name?0:-1});
   body.scrollTop=0;
  };
  Object.entries(labels).forEach(([name,label])=>{
   const button=action(label,()=>selectTab(name));button.classList.add('enterprise-tab');
   button.setAttribute('role','tab');buttons[name]=button;tabs.append(button);
  });
  panes.header.append(field('Tên ứng dụng',draft.header.title,v=>draft.header.title=v),field('Mô tả',draft.header.subtitle,v=>draft.header.subtitle=v),field('Icon header',draft.header.icon,v=>draft.header.icon=v),field('Liên kết header',draft.header.href,v=>draft.header.href=v));
  const footerTitle=document.createElement('h4');footerTitle.textContent='Footer Sidebar';panes.footer.append(footerTitle);
  panes.footer.append(field('Ghi chú footer',draft.footer.credit,v=>draft.footer.credit=v),field('Liên kết footer (HTTPS)',draft.footer.href,v=>draft.footer.href=v));
  draft.groups.forEach((g,gi)=>{
   const group=document.createElement('div');group.className='enterprise-group';panes.menu.append(group);
   group.append(field('Tên nhóm',g.title,v=>g.title=v));
   group.append(action('Nhóm lên',()=>{if(gi){[draft.groups[gi-1],draft.groups[gi]]=[draft.groups[gi],draft.groups[gi-1]];draw()}}),action('Nhóm xuống',()=>{if(gi<draft.groups.length-1){[draft.groups[gi+1],draft.groups[gi]]=[draft.groups[gi],draft.groups[gi+1]];draw()}}),action('Xóa nhóm',()=>{if(confirm('Xóa nhóm khỏi sidebar?')){draft.groups.splice(gi,1);draw()}}));
   g.items.forEach((item,ii)=>{
    const row=document.createElement('div');row.className='enterprise-item';group.append(row);
    const grip=document.createElement('button');grip.type='button';grip.className='btn sm enterprise-grip';grip.textContent='☰ Kéo thả';grip.draggable=true;grip.setAttribute('aria-label','Kéo để sắp xếp '+item.label);
    grip.ondragstart=e=>{e.dataTransfer.setData('application/x-enterprise-menu',JSON.stringify([gi,ii]));e.dataTransfer.effectAllowed='move'};
    row.ondragover=e=>{if(e.dataTransfer.types.includes('application/x-enterprise-menu'))e.preventDefault()};
    row.ondrop=e=>{e.preventDefault();try{const [fromG,fromI]=JSON.parse(e.dataTransfer.getData('application/x-enterprise-menu'));if(!draft.groups[fromG]?.items[fromI])return;const [moving]=draft.groups[fromG].items.splice(fromI,1);const at=fromG===gi&&fromI<ii?ii-1:ii;draft.groups[gi].items.splice(at,0,moving);draw()}catch{}};
    row.append(grip);
    row.append(field('ID',item.id,v=>item.id=v),field('Tên mục',item.label,v=>item.label=v),field('Icon',item.icon,v=>item.icon=v),field('Liên kết',item.href,v=>item.href=v));
    row.append(action('↑',()=>{if(ii){[g.items[ii-1],g.items[ii]]=[g.items[ii],g.items[ii-1]];draw()}}),action('↓',()=>{if(ii<g.items.length-1){[g.items[ii+1],g.items[ii]]=[g.items[ii],g.items[ii+1]];draw()}}),action('Nhân bản',()=>{g.items.splice(ii+1,0,{...item,id:item.id+'_copy'});draw()}),action('Xóa',()=>{if(confirm('Xóa mục khỏi sidebar?')){g.items.splice(ii,1);draw()}}));
   });
   group.ondragover=e=>{if(e.dataTransfer.types.includes('application/x-enterprise-menu'))e.preventDefault()};
   group.ondrop=e=>{if(e.target.closest('.enterprise-item'))return;e.preventDefault();try{const [fromG,fromI]=JSON.parse(e.dataTransfer.getData('application/x-enterprise-menu'));const [moving]=draft.groups[fromG].items.splice(fromI,1);g.items.push(moving);draw()}catch{}};
   group.append(action('Thêm mục',()=>{g.items.push({id:'new_'+Date.now(),label:'Mục mới',icon:'list',href:'#dashboard'});draw()}));
  });
  panes.menu.append(action('+ Nhóm',()=>{draft.groups.push({title:'Nhóm mới',items:[]});draw()}));
  const footer=document.createElement('div');footer.className='enterprise-editor-actions';panel.append(footer);
  
  footer.append(action('Đóng',close),action('Khôi phục mặc định',()=>{if(confirm('Khôi phục menu mặc định?')){draft=defaultMenu();draw()}}));
  footer.append(action('Xuất JSON',()=>{try{downloadMenu(normalizeMenu(draft))}catch(e){messageDialog('Lỗi xuất JSON',e.message)}}));
  const importLabel=document.createElement('label');importLabel.className='btn sm';importLabel.textContent='Nhập JSON';
  const importFile=document.createElement('input');importFile.type='file';importFile.accept='.json,application/json';importFile.hidden=true;
  importFile.onchange=async()=>{try{const file=importFile.files[0];if(!file||file.size>65536)throw Error('File không hợp lệ hoặc vượt 64 KiB');const next=normalizeMenu(JSON.parse(await file.text()));if(confirm('Nhập JSON vào bản chỉnh sửa? Chưa ghi lên server.')){draft=next;draw()}}catch(e){messageDialog('Lỗi nhập JSON',e.message)}};
  importLabel.append(importFile);footer.append(importLabel);
  footer.append(action('Lưu lên server',async()=>{try{const config=normalizeMenu(draft);const result=await protectedMenu('','POST',{config});if(result?.ok)messageDialog('Đã lưu trên server','Sidebar dùng chung đã được lưu. Các trình duyệt khác có thể tải cấu hình này.')}catch(e){messageDialog('Không thể lưu',e.message)}}));
  const server=document.createElement('div');server.className='enterprise-server-library';panes.library.append(server);
  const heading=document.createElement('h4');heading.textContent='Thư viện JSON trên server';server.append(heading);
  const select=document.createElement('select');select.className='inp';server.append(select);presetSelect=select;
  const refresh=async()=>{try{const data=await requestMenu('/presets');select.replaceChildren(new Option('Chọn file JSON',''));data.presets.forEach(p=>select.add(new Option(p.name,p.id)))}catch(e){messageDialog('Không tải được thư viện',e.message)}};
  server.append(action('Tải danh sách',refresh));
  server.append(action('Nhập từ server',async()=>{if(!select.value)return;try{const result=await requestMenu('/presets/'+encodeURIComponent(select.value));const next=normalizeMenu(result.config);if(confirm('Áp dụng preset này vào sidebar trên trình duyệt?')){draft=next;menu=next;write('sidebar_v1',JSON.stringify(next));renderMenu();draw();messageDialog('Đã nhập preset','Cấu hình đã áp dụng. Menu server đang hoạt động không bị ghi đè.')}}catch(e){messageDialog('Không thể nhập',e.message)}}));
  server.append(action('Tạo preset',async()=>{try{const config=normalizeMenu(draft);const name=await nameDialog();if(!name)return;const result=await protectedMenu('/presets','POST',{name,config});if(result?.id){await refresh();select.value=result.id;messageDialog('Đã lưu preset','File JSON mới đã được tạo trên server.')}}catch(e){messageDialog('Không thể lưu preset',e.message)}}));
  server.append(action('Ghi đè preset',async()=>{if(!select.value)return messageDialog('Chưa chọn preset','Hãy chọn file cần cập nhật.');if(!confirm('Ghi đè đúng file preset đang chọn?'))return;try{const config=normalizeMenu(draft);const result=await protectedMenu('/presets/'+encodeURIComponent(select.value),'PUT',{config});if(result?.updated)messageDialog('Đã cập nhật','Đã ghi đè đúng file preset được chọn.')}catch(e){messageDialog('Lỗi cập nhật',e.message)}}));
  server.append(action('Xóa preset',async()=>{if(!select.value)return;if(!confirm('Xóa file preset đã chọn? Menu đang áp dụng sẽ được giữ nguyên.'))return;try{const result=await protectedMenu('/presets/'+encodeURIComponent(select.value),'DELETE',{});if(result?.ok){await refresh();messageDialog('Đã xóa preset','Menu đang áp dụng không bị ảnh hưởng.')}}catch(e){messageDialog('Lỗi xóa',e.message)}}));
  refresh();
  selectTab(activeTab);

  const save=action('Cập nhật giao diện',()=>{try{const next=normalizeMenu(draft);menu=next;write('sidebar_v1',JSON.stringify(next));renderMenu();close();toast('Đã cập nhật Sidebar Menu')}catch(e){alert(e.message)}});save.classList.add('pri');footer.append(save);
 }
 draw();overlay.onclick=e=>{if(e.target===overlay)close()};
 panel.querySelector('input')?.focus();
}
applyTheme(read('theme_v1')||'ocean');
renderMenu();
loadServerMenu();
if(CUR==='settings')APP.pages.settings[2]();
})();
