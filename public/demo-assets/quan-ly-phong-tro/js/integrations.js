/* Rental room demo integrations: isolated theme, sidebar editor, JSON backups and private snapshots. */
(() => {
'use strict';
const slug='quan-ly-phong-tro', themeKey='rental_room_theme_v1', menuKey='rental_room_sidebar_v1';
const base='/demo/'+slug, csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
const palettes={
 original:['#6A1B9A','#4A148C','#8E24AA','#BA68C8','#F3E5F5','#FBF7FD','#2a0b3d','#3a1354','#d6c6e4'],
 ruby:['#C62828','#8E1B1B','#D84343','#E57373','#FCE8E8','#FFF8F8','#431619','#5B2328','#F4D6D6'],
 ocean:['#087E9B','#065E76','#139DB8','#65BED0','#E0F3F7','#F5FCFD','#103744','#155065','#D6F1F6'],
 emerald:['#16835D','#106344','#279A75','#71C5A7','#E0F5EC','#F5FCF8','#103D32','#185344','#D8F2E6'],
 indigo:['#4F46E5','#3730A3','#6861EC','#A5A0F3','#EBEAFE','#F8F8FF','#25234C','#353269','#E2E1FF']
};
const vars=['--chu-dao','--chu-dao-dam','--chu-dao-vua','--chu-dao-sang','--chu-dao-nhat','--chu-dao-mo','--sb','--sb2','--sbtext'];
function theme(name){if(!palettes[name])name='original';vars.forEach((v,i)=>document.documentElement.style.setProperty(v,palettes[name][i]));try{localStorage.setItem(themeKey,name)}catch(_){}document.querySelectorAll('[data-rental-theme]').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.rentalTheme===name)));}
try{theme(localStorage.getItem(themeKey)||'original')}catch(_){theme('original')}
const notify=(msg)=>typeof toast==='function'?toast(msg):alert(msg);
const settings=document.getElementById('page-settings'),nav=document.getElementById('nav');
if(!settings||!nav)return;
const panel=(title)=>{const p=document.createElement('div');p.className='card noprint';p.style.cssText='padding:18px;margin-bottom:16px';const h=document.createElement('h3');h.textContent=title;p.append(h);settings.prepend(p);return p};
const el=(tag,label,cls='btn')=>{const e=document.createElement(tag);if(tag==='button'){e.type='button';e.className=cls;}if(label)e.textContent=label;return e};
const row=()=>{const d=document.createElement('div');d.style.cssText='display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:12px';return d};
const themePanel=panel('Giao diện · 4 themes và màu nguyên bản'),themeRow=row();
for(const [id,name] of Object.entries({original:'Nguyên bản',ruby:'Ruby',ocean:'Ocean',emerald:'Emerald',indigo:'Indigo'})){const b=el('button',name);b.dataset.rentalTheme=id;b.onclick=()=>theme(id);themeRow.append(b)}
themePanel.append(themeRow);try{theme(localStorage.getItem(themeKey)||'original')}catch(_){theme('original')}
const navItems=[...nav.querySelectorAll('a[data-page]')];
const defaults={version:1,header:{title:'Quản Lý Phòng Trọ',subtitle:'Phòng · Hóa đơn · Công nợ',icon:'home',href:''},footer:{credit:'Quản lý phòng trọ',href:'',showSave:true},groups:[]};
let current=null;
for(const child of nav.children){if(child.tagName==='H6'){current={title:child.textContent.trim(),items:[]};defaults.groups.push(current)}else if(child.matches('a[data-page]')){if(!current){current={title:'Điều hướng',items:[]};defaults.groups.push(current)}const id=child.dataset.page;current.items.push({id,label:child.childNodes[child.childNodes.length-1]?.textContent?.trim()||id,icon:'menu',href:'#'+id})}}
const validMenu=m=>m&&m.version===1&&Array.isArray(m.groups)&&m.groups.length<=20&&m.groups.every(g=>typeof g.title==='string'&&Array.isArray(g.items)&&g.items.every(i=>/^[a-z0-9_-]{1,64}$/.test(i.id)&&typeof i.label==='string'&&/^(#[a-z0-9_-]{1,64}|https:\/\/[a-z0-9.-]+\/?.*)$/i.test(i.href)));
let menu=defaults;try{const saved=JSON.parse(localStorage.getItem(menuKey));if(validMenu(saved))menu=saved}catch(_){}
function renderMenu(){nav.replaceChildren();for(const group of menu.groups){const h=document.createElement('h6');h.textContent=group.title;nav.append(h);for(const item of group.items){const a=document.createElement('a');a.dataset.page=item.href.startsWith('#')?item.href.slice(1):'';a.textContent=item.label;if(item.href.startsWith('#'))a.onclick=()=>{if(typeof go==='function')go(a.dataset.page)};else{a.href=item.href;a.rel='noopener noreferrer';a.target='_blank'}nav.append(a)}}if(typeof CUR==='string')nav.querySelectorAll('a[data-page]').forEach(a=>a.classList.toggle('active',a.dataset.page===CUR));}
function persistMenu(){localStorage.setItem(menuKey,JSON.stringify(menu));renderMenu()}
renderMenu();
const menuPanel=panel('Chỉnh sửa menu sidebar'),editButton=el('button','Chỉnh sửa menu'),menuRow=row(),editor=document.createElement('div');
menuPanel.append(menuRow,editor);menuRow.append(editButton);
function input(value,placeholder){const i=el('input');i.className='inp';i.value=value||'';i.placeholder=placeholder;i.style.cssText='max-width:240px;flex:1;min-width:110px';return i}
function buildEditor(){editor.replaceChildren();const h=row(),title=input(menu.header.title,'Tiêu đề'),sub=input(menu.header.subtitle,'Mô tả');title.onchange=()=>{menu.header.title=title.value;persistMenu()};sub.onchange=()=>{menu.header.subtitle=sub.value;persistMenu()};h.append(title,sub);editor.append(h);
menu.groups.forEach((group,gi)=>{const wrap=document.createElement('div');wrap.style.cssText='border:1px solid var(--line);border-radius:10px;padding:12px;margin-top:12px';const gr=row(),gt=input(group.title,'Tên nhóm');gt.onchange=()=>{group.title=gt.value;persistMenu()};const up=el('button','↑ Nhóm'),down=el('button','↓ Nhóm'),del=el('button','Xóa nhóm');up.onclick=()=>move(menu.groups,gi,-1);down.onclick=()=>move(menu.groups,gi,1);del.onclick=()=>{if(confirm('Xóa nhóm và các mục bên trong?')){menu.groups.splice(gi,1);changed()}};gr.append(gt,up,down,del);wrap.append(gr);
group.items.forEach((item,ii)=>{const ir=row(),name=input(item.label,'Tên mục'),href=input(item.href,'#dashboard hoặc https://...'),iu=el('button','↑'),id=el('button','↓'),remove=el('button','Xóa');name.onchange=()=>{item.label=name.value;persistMenu()};href.onchange=()=>{if(!/^(#[a-z0-9_-]{1,64}|https:\/\/[a-z0-9.-]+\/?.*)$/i.test(href.value)){notify('Liên kết không hợp lệ');href.value=item.href;return}item.href=href.value;persistMenu()};iu.onclick=()=>move(group.items,ii,-1);id.onclick=()=>move(group.items,ii,1);remove.onclick=()=>{group.items.splice(ii,1);changed()};ir.append(name,href,iu,id,remove);wrap.append(ir)});
const add=el('button','+ Thêm mục');add.onclick=()=>{const id='link_'+Date.now();group.items.push({id,label:'Mục mới',icon:'menu',href:'#dashboard'});changed()};wrap.append(add);editor.append(wrap)});
const actions=row(),addGroup=el('button','+ Thêm nhóm'),reset=el('button','Khôi phục mặc định');addGroup.onclick=()=>{menu.groups.push({title:'Nhóm mới',items:[]});changed()};reset.onclick=()=>{if(confirm('Khôi phục menu nguyên bản?')){menu=JSON.parse(JSON.stringify(defaults));changed()}};actions.append(addGroup,reset);editor.append(actions);}
function move(items,i,d){const j=i+d;if(j<0||j>=items.length)return;[items[i],items[j]]=[items[j],items[i]];changed()}
function changed(){persistMenu();buildEditor()}
editButton.onclick=()=>{editor.hidden=!editor.hidden;if(!editor.hidden)buildEditor()};editor.hidden=true;
let adminCode='';
function code(){const v=prompt('Mã quản trị server (không lưu vào trình duyệt):',adminCode);if(v===null)return null;adminCode=v;return v}
async function api(url,method='GET',data){const secret=code();if(secret===null)return null;const response=await fetch(url,{method,cache:'no-store',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Demo-Backup-Code':secret},body:data?JSON.stringify(data):undefined});const json=await response.json().catch(()=>({}));if(!response.ok)throw Error(json.message||'HTTP '+response.status);return json}
const serverRow=row(),saveMenu=el('button','Lưu menu lên server'),loadMenu=el('button','Tải menu server'),presetName=input('','Tên preset'),savePreset=el('button','Lưu preset'),listPreset=el('button','Danh sách preset'),selectPreset=el('select');selectPreset.className='inp';const applyPreset=el('button','Áp dụng preset'),updatePreset=el('button','Ghi đè preset'),deletePreset=el('button','Xóa preset'),defaultPreset=el('button','Đặt mặc định');
saveMenu.onclick=async()=>{try{if(!confirm('Lưu menu server?'))return;await api(base+'/menu','POST',{config:menu});notify('Đã lưu menu server')}catch(e){notify(e.message)}};
loadMenu.onclick=async()=>{try{const r=await api(base+'/menu');if(r?.config&&validMenu(r.config)&&confirm('Thay menu trình duyệt bằng menu server?')){menu=r.config;changed()}}catch(e){notify(e.message)}};
savePreset.onclick=async()=>{try{if(!presetName.value.trim())return notify('Nhập tên preset');await api(base+'/menu/presets','POST',{name:presetName.value.trim(),config:menu});notify('Đã lưu preset')}catch(e){notify(e.message)}};
listPreset.onclick=async()=>{try{const r=await api(base+'/menu/presets');if(!r)return;selectPreset.replaceChildren(new Option('Chọn preset',''));for(const p of r.presets||[])selectPreset.add(new Option(p.name,p.id));if(r.defaultId)selectPreset.value=r.defaultId}catch(e){notify(e.message)}};
applyPreset.onclick=async()=>{try{if(!selectPreset.value)return;const r=await api(base+'/menu/presets/'+encodeURIComponent(selectPreset.value));if(r?.config&&validMenu(r.config)&&confirm('Áp dụng menu preset này?')){menu=r.config;changed()}}catch(e){notify(e.message)}};
updatePreset.onclick=async()=>{try{if(!selectPreset.value||!confirm('Ghi đè preset đã chọn?'))return;await api(base+'/menu/presets/'+encodeURIComponent(selectPreset.value),'PUT',{config:menu});notify('Đã cập nhật preset')}catch(e){notify(e.message)}};
deletePreset.onclick=async()=>{try{if(!selectPreset.value||!confirm('Xóa preset đã chọn?'))return;await api(base+'/menu/presets/'+encodeURIComponent(selectPreset.value),'DELETE');notify('Đã xóa preset')}catch(e){notify(e.message)}};
defaultPreset.onclick=async()=>{try{if(!selectPreset.value||!confirm('Đặt preset làm menu mặc định trên server?'))return;await api(base+'/menu/presets/'+encodeURIComponent(selectPreset.value)+'/default','PUT');notify('Đã đặt mặc định')}catch(e){notify(e.message)}};
serverRow.append(saveMenu,loadMenu,presetName,savePreset,listPreset,selectPreset,applyPreset,updatePreset,deletePreset,defaultPreset);menuPanel.append(serverRow);
const dataPanel=panel('Sao lưu JSON và dữ liệu mẫu trên server'),dataRow=row(),backupButton=el('button','Tải bản sao lưu JSON'),restoreInput=el('input');restoreInput.type='file';restoreInput.accept='.json,application/json';restoreInput.className='inp';const datasetName=input('','Tên bản dữ liệu'),saveData=el('button','Lưu dữ liệu lên server'),listData=el('button','Danh sách server'),selectData=el('select'),restoreData=el('button','Khôi phục server');selectData.className='inp';
backupButton.onclick=()=>{const blob=new Blob([JSON.stringify(S,null,2)],{type:'application/json'}),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='quan-ly-phong-tro-'+new Date().toISOString().slice(0,10)+'.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000)};
window.saveToFile=backupButton.onclick;
restoreInput.onchange=async()=>{const f=restoreInput.files?.[0];if(!f)return;try{if(f.size>10485760)throw Error('File vượt 10MB');const d=JSON.parse(await f.text());if(!valid(d))throw Error('JSON không đúng cấu trúc');if(!confirm('Khôi phục sẽ thay dữ liệu hiện tại trong trình duyệt. Tiếp tục?'))return;S=norm(d);save();rerender();notify('Đã khôi phục')}catch(e){notify(e.message)}finally{restoreInput.value=''}};
saveData.onclick=async()=>{try{if(!datasetName.value.trim()||!confirm('Lưu bản dữ liệu server?'))return;await api(base+'/datasets','POST',{name:datasetName.value.trim(),data:S});notify('Đã lưu dữ liệu server')}catch(e){notify(e.message)}};
listData.onclick=async()=>{try{const r=await api(base+'/datasets');if(!r)return;selectData.replaceChildren(new Option('Chọn bản dữ liệu',''));for(const d of r.datasets||[])selectData.add(new Option(d.name+' · '+d.savedAt,d.id))}catch(e){notify(e.message)}};
restoreData.onclick=async()=>{try{if(!selectData.value||!confirm('Khôi phục bản server và thay dữ liệu trình duyệt?'))return;const r=await api(base+'/datasets/'+encodeURIComponent(selectData.value));if(!r||!valid(r.data))throw Error('Dữ liệu server không hợp lệ');S=norm(r.data);save();rerender();notify('Đã khôi phục server')}catch(e){notify(e.message)}};
dataRow.append(backupButton,restoreInput,datasetName,saveData,listData,selectData,restoreData);dataPanel.append(dataRow);
})();