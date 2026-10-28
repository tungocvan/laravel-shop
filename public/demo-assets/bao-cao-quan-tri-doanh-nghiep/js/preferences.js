/* Standalone UI preferences. No business-data mutation. */
(() => {
'use strict';
const prefix='enterprise_management_report_';
const themes={
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
 footer:{credit:'File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel',href:''},
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
 const footer={credit:clean(raw.footer?.credit),href:clean(raw.footer?.href)};
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
 Object.keys(themes).forEach(name=>{const b=document.createElement('button');b.type='button';b.className='btn';b.style.borderLeft='8px solid '+themes[name][0];b.textContent=name[0].toUpperCase()+name.slice(1);b.setAttribute('aria-pressed',String(document.documentElement.dataset.enterpriseTheme===name));b.onclick=()=>{applyTheme(name);write('theme_v1',name);palette.querySelectorAll('button').forEach(x=>x.setAttribute('aria-pressed',String(x===b)))};palette.append(b)});
 document.querySelector('#enterprise-edit-menu').onclick=openEditor;
};
function openEditor(){
 const draft=structuredClone(menu);
 const overlay=document.createElement('div');overlay.className='enterprise-overlay';
 const panel=document.createElement('div');panel.className='enterprise-editor';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Chỉnh sửa sidebar');
 overlay.append(panel);document.body.append(overlay);
 const close=()=>overlay.remove();
 function field(label,value,change){
  const box=document.createElement('label');box.className='enterprise-field';box.textContent=label;
  const input=document.createElement('input');input.className='inp';input.value=value||'';input.oninput=()=>change(input.value);box.append(input);return box;
 }
 function action(label,fn){const b=document.createElement('button');b.type='button';b.className='btn sm';b.textContent=label;b.onclick=fn;return b}
 function draw(){
  panel.replaceChildren();
  const h=document.createElement('h3');h.textContent='Chỉnh sửa Sidebar Menu';panel.append(h);
  const body=document.createElement('div');body.className='enterprise-editor-body';panel.append(body);
  body.append(field('Tên ứng dụng',draft.header.title,v=>draft.header.title=v),field('Mô tả',draft.header.subtitle,v=>draft.header.subtitle=v),field('Icon header',draft.header.icon,v=>draft.header.icon=v),field('Liên kết header',draft.header.href,v=>draft.header.href=v));
  const ft=document.createElement('h4');ft.textContent='Footer Sidebar';body.append(ft);
  body.append(field('Ghi chú footer',draft.footer.credit,v=>draft.footer.credit=v),field('Liên kết footer (HTTPS)',draft.footer.href,v=>draft.footer.href=v));
  draft.groups.forEach((g,gi)=>{
   const group=document.createElement('div');group.className='enterprise-group';body.append(group);
   group.append(field('Tên nhóm',g.title,v=>g.title=v));
   group.append(action('Nhóm lên',()=>{if(gi){[draft.groups[gi-1],draft.groups[gi]]=[draft.groups[gi],draft.groups[gi-1]];draw()}}),action('Nhóm xuống',()=>{if(gi<draft.groups.length-1){[draft.groups[gi+1],draft.groups[gi]]=[draft.groups[gi],draft.groups[gi+1]];draw()}}),action('Xóa nhóm',()=>{if(confirm('Xóa nhóm khỏi sidebar?')){draft.groups.splice(gi,1);draw()}}));
   g.items.forEach((item,ii)=>{
    const row=document.createElement('div');row.className='enterprise-item';group.append(row);
    row.append(field('ID',item.id,v=>item.id=v),field('Tên mục',item.label,v=>item.label=v),field('Icon',item.icon,v=>item.icon=v),field('Liên kết',item.href,v=>item.href=v));
    row.append(action('↑',()=>{if(ii){[g.items[ii-1],g.items[ii]]=[g.items[ii],g.items[ii-1]];draw()}}),action('↓',()=>{if(ii<g.items.length-1){[g.items[ii+1],g.items[ii]]=[g.items[ii],g.items[ii+1]];draw()}}),action('Nhân bản',()=>{g.items.splice(ii+1,0,{...item,id:item.id+'_copy'});draw()}),action('Xóa',()=>{if(confirm('Xóa mục khỏi sidebar?')){g.items.splice(ii,1);draw()}}));
   });
   group.append(action('Thêm mục',()=>{g.items.push({id:'new_'+Date.now(),label:'Mục mới',icon:'list',href:'#dashboard'});draw()}));
  });
  body.append(action('Thêm nhóm',()=>{draft.groups.push({title:'Nhóm mới',items:[]});draw()}));
  const footer=document.createElement('div');footer.className='enterprise-editor-actions';panel.append(footer);
  footer.append(action('Đóng',close),action('Khôi phục mặc định',()=>{if(confirm('Khôi phục menu mặc định?')){Object.assign(draft,defaultMenu());draw()}}));
  const save=action('Cập nhật giao diện',()=>{try{const next=normalizeMenu(draft);menu=next;write('sidebar_v1',JSON.stringify(next));renderMenu();close();toast('Đã cập nhật Sidebar Menu')}catch(e){alert(e.message)}});save.classList.add('pri');footer.append(save);
 }
 draw();overlay.onclick=e=>{if(e.target===overlay)close()};
 panel.querySelector('input')?.focus();
}
applyTheme(read('theme_v1')||'ocean');
renderMenu();
if(CUR==='settings')APP.pages.settings[2]();
})();
