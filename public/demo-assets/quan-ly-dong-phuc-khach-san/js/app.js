
const CD={"main":"#2e7d32","dam":"#246227","sb":"#143121","sb0":"#0c1d14","sb2":"#194124","sang":"#71a774","sang2":"#549457","nhat":"#e6efe6","nhat2":"#f5f9f5","sbtext":"#c9ddca","sbmuted":"#97be99","hk":"#d1e2d2"};
const APP={"key":"dong_phuc_khach_san_v1","file":"Quản lý đồng phục nhân viên khách sạn.html","xlsx":"Quan ly dong phuc"};
/* Chụp lại mã gốc của trang TRƯỚC khi vẽ giao diện, để khi Lưu chỉ thay phần dữ liệu */
const TEMPLATE=document.documentElement.outerHTML;

/* =========================================================
   TIỆN ÍCH
   ========================================================= */
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN').format(Math.round(n||0));
const fmt1=n=>new Intl.NumberFormat('vi-VN',{maximumFractionDigits:1}).format(n||0);
const money=n=>fmt(n)+' ₫';
const short=n=>{n=Math.round(n||0);const a=Math.abs(n);
  if(a>=1e9)return (n/1e9).toFixed(a>=1e10?0:1).replace('.',',')+' tỷ';
  if(a>=1e6)return (n/1e6).toFixed(a>=1e8?0:1).replace('.',',')+' tr';
  if(a>=1e3)return Math.round(n/1e3)+'k';return String(n)};
const pad=n=>String(n).padStart(2,'0');
const ymd=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const vnDate=s=>{if(!s)return '';const [y,m,d]=String(s).slice(0,10).split('-');return d+'/'+m+'/'+y};
const hm=t=>{const d=new Date(t);return pad(d.getHours())+':'+pad(d.getMinutes())+' '+pad(d.getDate())+'/'+pad(d.getMonth()+1)};
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2,7);
const TODAY=new Date(), TODAYS=ymd(TODAY);
const toD=s=>{const [y,m,d]=String(s).split('-').map(Number);return new Date(y,m-1,d)};
const addDays=(s,n)=>{const d=toD(s);d.setDate(d.getDate()+n);return ymd(d)};
const addMonths=(s,n)=>{const d=toD(s),day=d.getDate();d.setDate(1);d.setMonth(d.getMonth()+n);d.setDate(Math.min(day,new Date(d.getFullYear(),d.getMonth()+1,0).getDate()));return ymd(d)};
const diffDays=(a,b)=>Math.round((toD(b)-toD(a))/864e5);
const fold=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase();
const normH=s=>fold(s).replace(/[^a-z0-9]/g,'');
const toNum=v=>{if(typeof v==='number')return v;const s=String(v??'').trim();if(!s)return 0;
  if(/^-?\d+[.,]\d{1,2}$/.test(s))return parseFloat(s.replace(',','.'));const n=parseInt(s.replace(/[^\d-]/g,''),10);return isNaN(n)?0:n};
const sum=(a,f)=>a.reduce((s,x)=>s+(+(f?f(x):x)||0),0);
const monthsBack=(n)=>{const o=[];for(let i=n-1;i>=0;i--){const d=new Date(TODAY.getFullYear(),TODAY.getMonth()-i,1);o.push(d.getFullYear()+'-'+pad(d.getMonth()+1))}return o};
const mLabel=(k,i)=>{const [y,m]=k.split('-');return 'T'+(+m)+(m==='01'||i===0?'/'+y.slice(2):'')};
const PAL=[CD.main,CD.sang,CD.dam,'#16a36a','#f0663a','#e6a100','#8b5cf6','#0ea5b7','#e0384a','#64748b'];
const ICO={
  edit:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>',
  del:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>',
  print:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/></svg>',
  xl:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M9 12l4 5M13 12l-4 5"/></svg>',
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
  up:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9M7 14l5-5 5 5M4 3h16"/></svg>',
  down:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>',
  search:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>',
  warn:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
  ok:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/></svg>',
  clock:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
  doc:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>',
  user:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg>',
  money:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>',
  trend:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>',
  box:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>',
  cal:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>'
};

/* =========================================================
   DỮ LIỆU
   - Nguồn chính: khối du-lieu nằm ngay trong file (nút Lưu đè dữ liệu ghi vào)
   - Bản tạm: bộ nhớ trình duyệt (localStorage), giữ thay đổi chưa kịp lưu vào file
   ========================================================= */
const ONLINE=/^https?:$/.test(location.protocol);
const FILE_NAME=ONLINE?APP.file:(decodeURIComponent(location.pathname.split('/').pop()||'')||APP.file);
const LS=APP.key+'|'+location.pathname;
const EMB=(()=>{try{return JSON.parse($('#du-lieu').textContent)}catch(e){return null}})();
let S=null,DIRTY=false,SAVED_AT=0,ASK_DRAFT=null;
function persist(d){try{localStorage.setItem(LS,JSON.stringify(d));return true}catch(e){return false}}
function save(){S.updated=Date.now();DIRTY=true;
  if(!persist(S))toast('Bộ nhớ tạm của trình duyệt đã đầy, bấm "Lưu đè dữ liệu" để ghi vào file ngay','err');
  refreshSave();if(APP.chrome)APP.chrome()}

/* CHIA TRANG */
const PG={},PSIZES=[10,20,50,100];let PRINT_ALL=false;
function pgState(k,def){return PG[k]=PG[k]||{p:0,n:def||20}}
function pgReset(k){if(PG[k])PG[k].p=0}
function pgSlice(k,list,def){const st=pgState(k,def);if(PRINT_ALL)return list;const pages=Math.max(1,Math.ceil(list.length/st.n));if(st.p>=pages)st.p=pages-1;if(st.p<0)st.p=0;return list.slice(st.p*st.n,st.p*st.n+st.n)}
function pgBar(k,total,fn){const st=pgState(k),pages=Math.max(1,Math.ceil(total/st.n)),p=st.p;
  if(PRINT_ALL||total<=10)return '';
  const from=total?p*st.n+1:0,to=Math.min(total,(p+1)*st.n);const b=i=>`<button class="${i===p?'on':''}" onclick="pgGo('${k}',${i},'${fn}')">${i+1}</button>`;
  let nums='';if(pages<=7)for(let i=0;i<pages;i++)nums+=b(i);else{nums+=b(0);const a=Math.max(1,p-1),e=Math.min(pages-2,p+1);if(a>1)nums+='<span>…</span>';for(let i=a;i<=e;i++)nums+=b(i);if(e<pages-2)nums+='<span>…</span>';nums+=b(pages-1)}
  return `<div class="pgbar"><span>Hiển thị <b>${fmt(from)}-${fmt(to)}</b> / ${fmt(total)} dòng</span><span class="pgs">Số dòng/trang<select onchange="pgSize('${k}',this.value,'${fn}')">${PSIZES.map(n=>`<option ${n===st.n?'selected':''}>${n}</option>`).join('')}</select>${pages>1?`<button ${p?'':'disabled'} onclick="pgGo('${k}',${p-1},'${fn}')">‹</button>${nums}<button ${p<pages-1?'':'disabled'} onclick="pgGo('${k}',${p+1},'${fn}')">›</button>`:''}</span></div>`}
function pgGo(k,p,fn){pgState(k).p=p;callFn(fn)}
function pgSize(k,n,fn){const st=pgState(k);st.n=+n;st.p=0;callFn(fn)}
function callFn(fn){if(fn.includes(':')){const [f,a]=fn.split(':');window[f](a)}else window[fn]()}
window.addEventListener('beforeprint',()=>{PRINT_ALL=true;if(!document.body.classList.contains('pdoc'))try{rerender()}catch(e){}});
window.addEventListener('afterprint',()=>{PRINT_ALL=false;const wasDoc=document.body.classList.contains('pdoc');document.body.classList.remove('pdoc');if(!wasDoc)try{rerender()}catch(e){}});

/* =========================================================
   LƯU ĐÈ VÀO CHÍNH FILE HTML
   ========================================================= */
let FH=null;
const idb=(mode,fn)=>new Promise((ok,no)=>{const rq=indexedDB.open(APP.key+'_file',1);rq.onupgradeneeded=()=>rq.result.createObjectStore('h');
  rq.onsuccess=()=>{const tx=rq.result.transaction('h',mode),r=fn(tx.objectStore('h'));tx.oncomplete=()=>ok(r&&r.result);tx.onerror=()=>no(tx.error)};rq.onerror=()=>no(rq.error)});
const idbGet=k=>idb('readonly',s=>s.get(k)).catch(()=>null);
const idbSet=(k,v)=>idb('readwrite',s=>s.put(v,k)).catch(()=>null);
function buildHtml(data){
  const json=JSON.stringify(data).replace(/</g,'\\u003c');
  const open='<script id="du-lieu" type="application/json">',close='</scr'+'ipt>';
  const a=TEMPLATE.indexOf(open),b=TEMPLATE.indexOf(close,a);
  return '<!DOCTYPE html>\n'+TEMPLATE.slice(0,a+open.length)+json+TEMPLATE.slice(b)}
function dl(blob,name){const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},2000)}
async function saveToFile(){
  const data={...S,savedAt:Date.now()};data.updated=Math.max(S.updated||0,data.savedAt);
  const html=buildHtml(data);
  if(!window.showSaveFilePicker||ONLINE)return downloadHtml(html,data);
  try{
    let h=FH;
    if(!h){const kept=await idbGet(LS);if(kept&&kept.name===FILE_NAME)h=kept}
    if(h){let p=await h.queryPermission({mode:'readwrite'});if(p!=='granted')p=await h.requestPermission({mode:'readwrite'});if(p!=='granted')h=null}
    if(!h){if(!await askPick())return;
      h=await showSaveFilePicker({suggestedName:FILE_NAME,types:[{description:'Trang HTML',accept:{'text/html':['.html']}}]})}
    const w=await h.createWritable();await w.write(html);await w.close();
    FH=h;idbSet(LS,h);done(data);
    toast('Đã lưu vào file '+h.name);
  }catch(e){if(e&&e.name==='AbortError')return;console.error(e);downloadHtml(html,data)}
}
function done(data){S.updated=data.updated;S.savedAt=data.savedAt;SAVED_AT=data.savedAt;DIRTY=false;persist(S);refreshSave()}
function downloadHtml(html,data){dl(new Blob([html],{type:'text/html'}),FILE_NAME);done(data);
  toast('Đã tải bản mới về thư mục Tải xuống. Hãy dùng file vừa tải (chép đè lên file cũ) để giữ dữ liệu mới nhất.','err')}
function askPick(){return new Promise(res=>{
  openModal(`<div class="modal-h"><h3>Lưu lần đầu vào file</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p>Trình duyệt cần chỉ đúng file để ghi đè <b>một lần duy nhất</b>:</p>
  <ol><li>Bấm <b>Chọn file để ghi đè</b> bên dưới.</li><li>Tìm tới thư mục chứa file đang mở, chọn đúng file <b>${esc(FILE_NAME)}</b>.</li><li>Bấm <b>Save / Lưu</b>, rồi bấm <b>Yes / Có</b> khi được hỏi có thay file cũ không.</li><li>Nếu trình duyệt hỏi quyền chỉnh sửa, bấm <b>Allow / Cho phép</b>.</li></ol>
  <p class="muted">Từ lần sau chỉ cần bấm "Lưu đè dữ liệu" hoặc Ctrl + S là xong.</p></div>
  <div class="modal-f"><button class="btn" id="pkNo">Để sau</button><button class="btn pri" id="pkYes">Chọn file để ghi đè</button></div>`,'small');
  $('#pkNo').onclick=()=>{closeModal();res(false)};$('#pkYes').onclick=()=>{closeModal();res(true)}})}
async function forgetFile(){FH=null;await idbSet(LS,null);saveToFile()}
function refreshSave(){
  const st=$('#saveSt'),bt=$('#saveBtn');if(!st)return;
  bt.classList.toggle('dirty',DIRTY);st.className='savest '+(DIRTY?'dirty':'clean');
  st.innerHTML=DIRTY?'<b>Chưa lưu vào file</b>có thay đổi mới':SAVED_AT?'<b>Đã lưu vào file</b>lúc '+hm(SAVED_AT):'<b>Dữ liệu mẫu</b>chưa lưu lần nào'}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveToFile()}});
window.addEventListener('beforeunload',e=>{if(DIRTY){e.preventDefault();e.returnValue=''}});

/* sao lưu / khôi phục / làm mới */
function backup(){dl(new Blob([JSON.stringify(S,null,1)],{type:'application/json'}),FILE_NAME.replace(/\.html?$/i,'')+' - sao lưu '+TODAYS+'.json');toast('Đã tải file sao lưu .json')}
function restoreFrom(file){const fr=new FileReader();fr.onload=()=>{try{const d=JSON.parse(fr.result);if(!APP.valid(d))throw 0;
  if(!confirm('Thay toàn bộ dữ liệu hiện tại bằng dữ liệu trong file sao lưu?'))return;S=d;APP.fix&&APP.fix(S);save();toast('Đã khôi phục dữ liệu');rerender()}catch(e){toast('File sao lưu không đúng định dạng','err')}};fr.readAsText(file)}
function resetSample(){if(!confirm('Xóa dữ liệu hiện tại và nạp lại dữ liệu mẫu?'))return;S=APP.seed();save();toast('Đã nạp lại dữ liệu mẫu');rerender()}
function clearAll(){if(!confirm('Xóa sạch dữ liệu để bắt đầu nhập dữ liệu thật?\n\nNên tải file sao lưu trước nếu cần giữ lại.'))return;
  const keep=S.company;S=APP.empty();S.company=keep||{};save();toast('Đã xóa dữ liệu mẫu. Nhớ bấm "Lưu đè dữ liệu" để ghi vào file.');rerender()}

/* =========================================================
   ĐIỀU HƯỚNG, MODAL, THÔNG BÁO
   ========================================================= */
let CUR='dashboard';
function go(pg){
  if(!APP.pages[pg])pg='dashboard';CUR=pg;
  $$('#nav a').forEach(a=>a.classList.toggle('active',a.dataset.page===pg));
  $$('.page').forEach(s=>s.classList.toggle('show',s.id==='page-'+pg));
  $('#pTitle').textContent=APP.pages[pg][0];$('#pSub').textContent=APP.pages[pg][1];
  APP.pages[pg][2]();
  try{if(location.hash!=='#'+pg)history.replaceState(null,'','#'+pg)}catch(e){}
  $('#sb').classList.remove('open');$('#mask').classList.remove('show');window.scrollTo(0,0);
}
function rerender(){APP.chrome&&APP.chrome();APP.pages[CUR][2]()}
function openModal(html,size){const m=$('#modal');m.className='modal'+(size?' '+size:'');m.innerHTML=html;$('#ov').classList.add('show');m.scrollTop=0}
function closeModal(){$('#ov').classList.remove('show')}
function toast(msg,type){const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;$('#toasts').appendChild(t);setTimeout(()=>{t.style.opacity='0';t.style.transition='.3s'},type==='err'?5200:3200);setTimeout(()=>t.remove(),type==='err'?5600:3600)}
function printHtml(html){$('#printArea').innerHTML=html;document.body.classList.add('pdoc');setTimeout(()=>window.print(),80)}
function coHead(right){const co=S.company||{},dots='................................................';
  return `<div class="hd"><div><b>Đơn vị: ${esc(co.name||dots)}</b><br>Địa chỉ: ${esc(co.addr||dots)}${co.phone?'<br>Điện thoại: '+esc(co.phone):''}${co.tax?'<br>Mã số thuế: '+esc(co.tax):''}</div><div class="r">${right||''}</div></div>`}
function signs(list,dateS){const [y,m,d]=(dateS||TODAYS).split('-');return `<div class="sdate">Ngày ${d} tháng ${m} năm ${y}</div><div class="sign" style="--n:${list.length}">${list.map(s=>`<div><b>${s}</b><i>(Ký, họ tên)</i></div>`).join('')}</div>`}

/* tooltip */
document.addEventListener('mousemove',e=>{const tip=$('#tip'),t=e.target.closest&&e.target.closest('[data-tip]');
  if(!t){tip.style.display='none';return}tip.textContent=t.dataset.tip;tip.style.display='block';
  const w=tip.offsetWidth;tip.style.left=Math.max(4,Math.min(e.clientX+14,innerWidth-w-8))+'px';tip.style.top=(e.clientY-36)+'px'});

/* =========================================================
   BIỂU ĐỒ (SVG tự vẽ, không cần mạng)
   ========================================================= */
function niceMax(v){if(v<=0)return 1;const p=Math.pow(10,Math.floor(Math.log10(v)));const n=v/p;return (n<=1?1:n<=2?2:n<=2.5?2.5:n<=5?5:10)*p}
let GID=0;
/* cột + đường: o={labels,bars:[{name,data,color,c2}],lines:[{name,data,color}],f:hàm định dạng} */
function comboChart(o){
  const W=760,H=300,pl=56,pr=12,pt=16,pb=34,ih=H-pt-pb,n=o.labels.length,f=o.f||money,ax=o.ax||short;
  const all=[...o.bars.flatMap(b=>b.data),...(o.lines||[]).flatMap(l=>l.data)];
  const max=niceMax(Math.max(1,...all));
  const cw=(W-pl-pr)/n,nb=o.bars.length||1,bw=Math.min(o.bars.length>1?16:26,(cw-12)/nb),Y=v=>pt+ih*(1-Math.max(0,v)/max);
  let g='<defs>'+o.bars.map((b,j)=>`<linearGradient id="cg${GID}_${j}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${b.c2||b.color}"/><stop offset="1" stop-color="${b.color}"/></linearGradient>`).join('')+'</defs>';
  if(o.hlLast!==false)g+=`<rect class="hl" x="${pl+cw*(n-1)+2}" y="${pt-8}" width="${cw-4}" height="${ih+8}" rx="10" fill="${CD.nhat2}"/>`;
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${ax(max*i/4)}</text>`}
  o.labels.forEach((l,i)=>{const cx=pl+cw*i+cw/2,tw=bw*nb+4*(nb-1);
    o.bars.forEach((b,j)=>{const v=b.data[i]||0,hh=Math.max(v>0?2:0,ih*Math.max(0,v)/max);
      g+=`<rect class="bar" style="animation-delay:${i*40+j*20}ms" x="${cx-tw/2+j*(bw+4)}" y="${pt+ih-hh}" width="${bw}" height="${hh}" rx="5" fill="url(#cg${GID}_${j})" data-tip="${esc(b.name+' '+l+': '+f(v))}"/>`});
    g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax"${i===n-1&&o.hlLast!==false?` style="font-weight:700;fill:${CD.dam}"`:''}>${esc(l)}</text>`});
  (o.lines||[]).forEach(L=>{const pts=L.data.map((v,i)=>[pl+cw*i+cw/2,Y(v)]);
    g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="${L.color}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>`;
    g+=pts.map((p,i)=>`<circle class="seg" cx="${p[0]}" cy="${p[1]}" r="4" fill="#fff" stroke="${L.color}" stroke-width="2.5" data-tip="${esc(L.name+' '+o.labels[i]+': '+f(L.data[i]))}"/>`).join('')});
  GID++;return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function donut(items,o){o=o||{};const f=o.f||short;
  const tot=items.reduce((s,i)=>s+i.value,0);
  if(!tot)return `<div class="empty">${o.empty||'Chưa có số liệu'}</div>`;
  const R=70,C=2*Math.PI*R;let off=0,segs='';
  items.forEach(it=>{const len=it.value/tot*C;
    segs+=`<circle class="seg" cx="100" cy="100" r="${R}" fill="none" stroke="${it.color}" stroke-width="26" stroke-dasharray="${Math.max(0,len-2)} ${C}" stroke-dashoffset="${-off}" transform="rotate(-90 100 100)" data-tip="${esc(it.label+': '+(o.tf||f)(it.value))}"/>`;off+=len});
  return `<div class="donut-wrap"><svg viewBox="0 0 200 200" class="chart">${segs}<text x="100" y="94" text-anchor="middle" class="ax" style="font-size:12px">${esc(o.center||'Tổng')}</text><text x="100" y="117" text-anchor="middle" style="font-size:19px;font-weight:800;fill:var(--text)">${f(tot)}</text></svg>
  <div class="dl">${items.map(it=>`<div><i style="background:${it.color}"></i>${esc(it.label)}<span>${f(it.value)}</span><em>${Math.round(it.value/tot*100)}%</em></div>`).join('')}</div></div>`}
function hbars(items,o){o=o||{};if(!items.length)return `<div class="empty">${o.empty||'Chưa có số liệu'}</div>`;const mx=o.max||Math.max(1,...items.map(i=>i.value));
  return items.map((it,i)=>`<div class="hb" ${it.tip?`data-tip="${esc(it.tip)}"`:''}><div class="hb-h"><b>${esc(it.label)}</b><span>${it.text!==undefined?it.text:(o.f||fmt)(it.value)}</span></div><div class="pbar"><i style="width:${Math.max(1.5,it.value/mx*100)}%;background:${it.color||CD.main};animation-delay:${i*60}ms"></i></div>${it.sub?`<div class="muted" style="font-size:11.5px;margin-top:3px">${it.sub}</div>`:''}</div>`).join('')}
function spark(data,color){const W=240,H=46,n=Math.max(2,data.length),mx=Math.max(...data,0),mn=Math.min(0,...data),r=(mx-mn)||1,id='spg'+(++GID);
  const pts=data.map((v,i)=>[i*W/(n-1),H-4-(v-mn)/r*(H-12)]),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L');
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`}
function heroArea(data,labels,tipF){
  const W=440,H=150,pl=8,pr=8,pt=12,pb=22,mx=Math.max(1,...data),mn=Math.min(...data),span=(mx-mn)||mx||1,id='ha'+(++GID);
  const x=i=>pl+i*(W-pl-pr)/Math.max(1,data.length-1),y=v=>pt+(H-pt-pb)*(1-((v-mn)/span*0.85+0.08));
  const line='M'+data.map((v,i)=>x(i).toFixed(1)+','+y(v).toFixed(1)).join('L'),L=data.length-1;
  return `<svg viewBox="0 0 ${W} ${H}" class="chart" style="overflow:visible"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs>
  <path d="${line}L${x(L)},${H-pb}L${x(0)},${H-pb}Z" fill="url(#${id})"/>
  <path d="${line}" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>
  ${data.map((v,i)=>`<circle class="seg" cx="${x(i)}" cy="${y(v)}" r="${i===L?5.5:3.5}" fill="${i===L?'#fff':'rgba(255,255,255,.75)'}" stroke="${i===L?CD.main:'none'}" stroke-width="3" data-tip="${esc(tipF(i,v))}"/>`).join('')}
  ${labels.map((l,i)=>i%2===1?`<text x="${x(i)}" y="${H-3}" text-anchor="middle" style="font-size:10.5px;fill:rgba(255,255,255,.75)">${esc(l)}</text>`:'').join('')}</svg>`}
function ringSvg(pct,sub){const C=2*Math.PI*50,p=Math.max(0,Math.min(100,pct));
  return `<svg viewBox="0 0 120 120"><defs><linearGradient id="rg${++GID}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9af5cf"/><stop offset="1" stop-color="#22c58b"/></linearGradient></defs>
  <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="11"/>
  <circle class="ring-a" cx="60" cy="60" r="50" fill="none" stroke="url(#rg${GID})" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C*p/100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
  <text x="60" y="61" text-anchor="middle" style="font-size:26px;font-weight:800;fill:#fff">${Math.round(pct)}%</text><text x="60" y="79" text-anchor="middle" style="font-size:9.5px;fill:rgba(255,255,255,.8)">${esc(sub)}</text></svg>`}
function countUp(root){
  const els=[...root.querySelectorAll('[data-count]')],fin=()=>els.forEach(e=>e.textContent=fmt(+e.dataset.count));
  if(PRINT_ALL||matchMedia('(prefers-reduced-motion: reduce)').matches)return fin();
  const t0=performance.now(),D=900;els.forEach(e=>e.textContent='0');
  const step=t=>{const k=Math.min(1,(t-t0)/D),ez=1-Math.pow(1-k,3);els.forEach(e=>e.textContent=fmt(+e.dataset.count*ez));if(k<1)requestAnimationFrame(step)};
  requestAnimationFrame(step);setTimeout(fin,1300)}
function pill(cur,prev,cmp,rev){
  if(prev===undefined||prev===null)return '<span class="pill na">Toàn bộ</span>';
  if(!prev)return `<span class="pill na" title="${cmp||''}">${cur?'Mới':'-'}</span>`;
  const p=(cur-prev)/Math.abs(prev)*100,up=p>=0,good=rev?!up:up;
  return `<span class="pill ${good?'pu':'pd'}" title="${cmp||''}">${up?'▲':'▼'} ${Math.abs(p).toFixed(1).replace('.',',')}%</span>`}
function kc(o){return `<div class="card kc" style="--c:${o.c};--cb:${o.cb}"><div class="kc-h"><span class="kc-ic">${o.icon}</span><span class="kc-l">${o.label}</span>${o.pill||''}</div><div class="kc-v"><span data-count="${Math.round(o.value)}">0</span>${o.unit?` <small>${o.unit}</small>`:''}</div><div class="kc-s">${o.sub||''}</div>${o.data?spark(o.data,o.c):'<div style="height:16px"></div>'}</div>`}
function rangeOf(k){
  const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t,pf,pt,lbl,cmp;
  if(k==='month'){f=new Date(y,m,1);t=new Date(y,m+1,0);pf=new Date(y,m-1,1);pt=new Date(y,m,0);lbl='Tháng '+(m+1)+'/'+y;cmp='so với tháng trước'}
  else if(k==='quarter'){const q=Math.floor(m/3);f=new Date(y,q*3,1);t=new Date(y,q*3+3,0);pf=new Date(y,q*3-3,1);pt=new Date(y,q*3,0);lbl='Quý '+(q+1)+'/'+y;cmp='so với quý trước'}
  else if(k==='year'){f=new Date(y,0,1);t=new Date(y,11,31);pf=new Date(y-1,0,1);pt=new Date(y-1,11,31);lbl='Năm '+y;cmp='so với năm trước'}
  else return {from:'0000-01-01',to:'9999-12-31',label:'Toàn bộ thời gian',cmp:''};
  return {from:ymd(f),to:ymd(t),pfrom:ymd(pf),pto:ymd(pt),label:lbl,cmp}}
function segInit(id,cb){$$('#'+id+' button').forEach(b=>b.onclick=()=>{$$('#'+id+' button').forEach(x=>x.classList.toggle('on',x===b));cb(b.dataset.v)})}
function quickRange(v){const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t;
  if(v==='month'){f=new Date(y,m,1);t=new Date(y,m+1,0)}else if(v==='prev'){f=new Date(y,m-1,1);t=new Date(y,m,0)}
  else if(v==='quarter'){const q=Math.floor(m/3);f=new Date(y,q*3,1);t=new Date(y,q*3+3,0)}else if(v==='year'){f=new Date(y,0,1);t=new Date(y,11,31)}
  else{f=new Date(y-5,0,1);t=new Date(y+1,11,31)}return [ymd(f),ymd(t)]}
function docSo(n){
  n=Math.round(Math.abs(+n||0));if(n===0)return 'Không đồng';
  const cs=['không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'],units=['',' nghìn',' triệu',' tỷ',' nghìn tỷ',' triệu tỷ'];
  const doc3=(x,full)=>{const tr=Math.floor(x/100),ch=Math.floor(x%100/10),dv=x%10;let s='';
    if(full||tr>0)s+=cs[tr]+' trăm';
    if(ch>1){s+=' '+cs[ch]+' mươi';if(dv===1)s+=' mốt';else if(dv===5)s+=' lăm';else if(dv===4)s+=' tư';else if(dv>0)s+=' '+cs[dv]}
    else if(ch===1){s+=' mười';if(dv===5)s+=' lăm';else if(dv>0)s+=' '+cs[dv]}
    else if(dv>0){if(full||tr>0)s+=' lẻ';s+=' '+cs[dv]}
    return s.trim()};
  const g=[];let t=n;while(t>0){g.push(t%1000);t=Math.floor(t/1000)}
  const out=[];for(let i=g.length-1;i>=0;i--){if(g[i]===0)continue;out.push(doc3(g[i],i!==g.length-1)+units[i])}
  const s=out.join(' ').replace(/\s+/g,' ').trim();return s.charAt(0).toUpperCase()+s.slice(1)+' đồng'}

/* =========================================================
   ĐỌC / GHI FILE EXCEL .xlsx (tự viết, không cần mạng)
   ========================================================= */
const XLSX=(()=>{
  const serial=s=>{const [y,m,d]=String(s).slice(0,10).split('-').map(Number);return (Date.UTC(y,m-1,d)-Date.UTC(1899,11,30))/864e5};
  const crcT=(()=>{const t=new Uint32Array(256);for(let n=0;n<256;n++){let c=n;for(let k=0;k<8;k++)c=c&1?0xEDB88320^(c>>>1):c>>>1;t[n]=c>>>0}return t})();
  const crc32=b=>{let c=0xFFFFFFFF;for(let i=0;i<b.length;i++)c=crcT[(c^b[i])&0xFF]^(c>>>8);return (c^0xFFFFFFFF)>>>0};
  const enc=new TextEncoder();
  function zip(files){
    const parts=[],cd=[];let off=0;
    const now=new Date(),dt=((now.getFullYear()-1980)<<9)|((now.getMonth()+1)<<5)|now.getDate(),tm=(now.getHours()<<11)|(now.getMinutes()<<5)|(now.getSeconds()>>1);
    for(const f of files){
      const name=enc.encode(f.name),data=typeof f.data==='string'?enc.encode(f.data):f.data,crc=crc32(data);
      const h=new DataView(new ArrayBuffer(30));
      h.setUint32(0,0x04034b50,true);h.setUint16(4,20,true);h.setUint16(6,0x0800,true);h.setUint16(8,0,true);h.setUint16(10,tm,true);h.setUint16(12,dt,true);
      h.setUint32(14,crc,true);h.setUint32(18,data.length,true);h.setUint32(22,data.length,true);h.setUint16(26,name.length,true);h.setUint16(28,0,true);
      parts.push(new Uint8Array(h.buffer),name,data);
      const c=new DataView(new ArrayBuffer(46));
      c.setUint32(0,0x02014b50,true);c.setUint16(4,20,true);c.setUint16(6,20,true);c.setUint16(8,0x0800,true);c.setUint16(10,0,true);c.setUint16(12,tm,true);c.setUint16(14,dt,true);
      c.setUint32(16,crc,true);c.setUint32(20,data.length,true);c.setUint32(24,data.length,true);c.setUint16(28,name.length,true);c.setUint32(42,off,true);
      cd.push(new Uint8Array(c.buffer),name);off+=30+name.length+data.length}
    const cdSize=cd.reduce((s,a)=>s+a.length,0),e=new DataView(new ArrayBuffer(22));
    e.setUint32(0,0x06054b50,true);e.setUint16(8,files.length,true);e.setUint16(10,files.length,true);e.setUint32(12,cdSize,true);e.setUint32(16,off,true);
    return new Blob([...parts,...cd,new Uint8Array(e.buffer)],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'})}
  const x=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g,'');
  const colName=i=>{let s='';i++;while(i>0){const m=(i-1)%26;s=String.fromCharCode(65+m)+s;i=Math.floor((i-1)/26)}return s};
  // Kiểu ô: 1 tiêu đề cột, 2 chữ, 3 số #,##0, 4 ngày, 5 tiêu đề lớn, 6 tổng số, 7 %, 8 tổng chữ, 9 dòng phụ, 10 số lẻ, 11 chữ giữa, 12 tổng số lẻ
  const ST={text:2,money:3,int:3,date:4,pct:7,num:10,center:11};
  function styles(hex){const c='FF'+hex.replace('#','').toUpperCase();
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="0.0%"/><numFmt numFmtId="167" formatCode="#,##0.0"/></numFmts>
<fonts count="5"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="${c}"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="${c}"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEF1F6"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD0D5DD"/></left><right style="thin"><color rgb="FFD0D5DD"/></right><top style="thin"><color rgb="FFD0D5DD"/></top><bottom style="thin"><color rgb="FFD0D5DD"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="13">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>
<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="164" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
<xf numFmtId="166" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>
<xf numFmtId="167" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>
<xf numFmtId="167" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>`}
  function cell(ref,v,type,s){
    if(v===null||v===undefined||v==='')return `<c r="${ref}" s="${s}"/>`;
    if(type==='date'){if(!/^\d{4}-\d{2}-\d{2}/.test(String(v)))return `<c r="${ref}" s="11" t="inlineStr"><is><t>${x(v)}</t></is></c>`;return `<c r="${ref}" s="${s}"><v>${serial(v)}</v></c>`}
    if(['money','int','num','pct'].includes(type)){const n=type==='pct'?(+v||0)/100:+v||0;return `<c r="${ref}" s="${s}"><v>${n}</v></c>`}
    return `<c r="${ref}" s="${s}" t="inlineStr"><is><t xml:space="preserve">${x(v)}</t></is></c>`}
  function hdrRow(s){return (s.title?1:0)+(s.subtitles||[]).length+((s.title||(s.subtitles||[]).length)?1:0)+1}
  function sheetXML(sh){
    const cols=sh.columns,nc=cols.length,last=colName(nc-1);let r=0;const rows=[],merges=[];
    if(sh.title){r++;rows.push(`<row r="${r}" ht="24" customHeight="1">${cell('A'+r,sh.title,'text',5)}</row>`);merges.push(`A${r}:${last}${r}`)}
    (sh.subtitles||[]).forEach(t=>{r++;rows.push(`<row r="${r}">${cell('A'+r,t,'text',9)}</row>`);merges.push(`A${r}:${last}${r}`)});
    if(r)r++;
    r++;const hr=r;
    rows.push(`<row r="${r}" ht="30" customHeight="1">${cols.map((c,i)=>cell(colName(i)+r,c.label,'text',1)).join('')}</row>`);
    for(const row of sh.rows){r++;rows.push(`<row r="${r}">${cols.map((c,i)=>{const t=c.type||'text';return cell(colName(i)+r,row[i],t,ST[t]||2)}).join('')}</row>`)}
    const lastData=r;
    if(sh.totals&&sh.rows.length){r++;
      rows.push(`<row r="${r}">${cols.map((c,i)=>{const ref=colName(i)+r;
        if(c.sum){const f=`SUBTOTAL(9,${colName(i)}${hr+1}:${colName(i)}${lastData})`,v=sum(sh.rows,row=>row[i]);return `<c r="${ref}" s="${c.type==='num'?12:6}"><f>${f}</f><v>${v}</v></c>`}
        return cell(ref,i===0?(sh.totalLabel||'TỔNG CỘNG'):'','text',8)}).join('')}</row>`)}
    const colsXml=`<cols>${cols.map((c,i)=>`<col min="${i+1}" max="${i+1}" width="${c.width||({money:15,date:12,pct:9,num:9,int:9}[c.type]||18)}" customWidth="1"/>`).join('')}</cols>`;
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:${last}${Math.max(r,1)}"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="${hr}" topLeftCell="A${hr+1}" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>${colsXml}<sheetData>${rows.join('')}</sheetData>${sh.rows.length&&!sh.noFilter?`<autoFilter ref="A${hr}:${last}${lastData}"/>`:''}${merges.length?`<mergeCells count="${merges.length}">${merges.map(m=>`<mergeCell ref="${m}"/>`).join('')}</mergeCells>`:''}<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="${sh.portrait?'portrait':'landscape'}" fitToWidth="1" fitToHeight="0"/></worksheet>`}
  function save(fileName,sheets){
    const used={};
    const names=sheets.map(s=>{let n=String(s.name||'Sheet').replace(/[\[\]:*?\/\\]/g,' ').slice(0,31)||'Sheet';let k=n,i=2;while(used[k.toLowerCase()])k=n.slice(0,28)+' '+i++;used[k.toLowerCase()]=1;return k});
    const files=[
      {name:'[Content_Types].xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>${sheets.map((_,i)=>`<Override PartName="/xl/worksheets/sheet${i+1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}</Types>`},
      {name:'_rels/.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`},
      {name:'xl/workbook.xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>${names.map((n,i)=>`<sheet name="${x(n)}" sheetId="${i+1}" r:id="rId${i+1}"/>`).join('')}</sheets><definedNames>${sheets.map((s,i)=>s.rows.length&&!s.noFilter?`<definedName name="_xlnm._FilterDatabase" localSheetId="${i}" hidden="1">'${x(names[i]).replace(/'/g,"''")}'!$A$${hdrRow(s)}:$${colName(s.columns.length-1)}$${hdrRow(s)+s.rows.length}</definedName>`:'').join('')}</definedNames></workbook>`},
      {name:'xl/_rels/workbook.xml.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${sheets.map((_,i)=>`<Relationship Id="rId${i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${i+1}.xml"/>`).join('')}<Relationship Id="rId${sheets.length+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`},
      {name:'xl/styles.xml',data:styles(CD.main)},
      ...sheets.map((s,i)=>({name:`xl/worksheets/sheet${i+1}.xml`,data:sheetXML(s)}))];
    dl(zip(files),fileName.replace(/\.xlsx$/i,'')+'.xlsx');
    toast('Đã tải file Excel '+fileName+'.xlsx')}
  async function unzip(buf){
    const u=new Uint8Array(buf),v=new DataView(buf),dec=new TextDecoder(),out={};
    let e=u.length-22;while(e>=0&&v.getUint32(e,true)!==0x06054b50)e--;
    if(e<0)throw new Error('Không phải file Excel (.xlsx)');
    let p=v.getUint32(e+16,true);const n=v.getUint16(e+10,true);
    for(let i=0;i<n;i++){
      const method=v.getUint16(p+10,true),size=v.getUint32(p+20,true),nl=v.getUint16(p+28,true),xl=v.getUint16(p+30,true),cl=v.getUint16(p+32,true),lo=v.getUint32(p+42,true);
      const name=dec.decode(u.subarray(p+46,p+46+nl));p+=46+nl+xl+cl;
      const start=lo+30+v.getUint16(lo+26,true)+v.getUint16(lo+28,true),raw=u.subarray(start,start+size);
      out[name]=method===0?raw:method===8?new Uint8Array(await new Response(new Blob([raw]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).arrayBuffer()):null}
    return out}
  const xmlOf=(z,k)=>z[k]?new DOMParser().parseFromString(new TextDecoder().decode(z[k]),'application/xml'):null;
  const tagAll=(el,t)=>[...el.getElementsByTagNameNS('*',t)];
  const colIdx=ref=>{let n=0;for(const ch of ref.replace(/\d+/g,''))n=n*26+ch.charCodeAt(0)-64;return n-1};
  function csvRows(t){
    const sep=(t.split('\n')[0].match(/;/g)||[]).length>(t.split('\n')[0].match(/,/g)||[]).length?';':',';
    const rows=[];let row=[],cur='',q=false;
    for(let i=0;i<t.length;i++){const ch=t[i];
      if(q){if(ch==='"'&&t[i+1]==='"'){cur+='"';i++}else if(ch==='"')q=false;else cur+=ch}
      else if(ch==='"')q=true;else if(ch===sep){row.push(cur);cur=''}else if(ch==='\n'){row.push(cur.replace(/\r$/,''));rows.push(row);row=[];cur=''}else cur+=ch}
    if(cur||row.length){row.push(cur);rows.push(row)}
    return rows}
  async function read(file){
    if(/\.csv$/i.test(file.name))return csvRows((await file.text()).replace(/^﻿/,''));
    if(/\.xls$/i.test(file.name))throw new Error('File .xls đời cũ chưa đọc được. Mở bằng Excel rồi lưu lại thành .xlsx');
    const z=await unzip(await file.arrayBuffer());
    const ss=xmlOf(z,'xl/sharedStrings.xml'),strs=ss?tagAll(ss.documentElement,'si').map(si=>tagAll(si,'t').map(t=>t.textContent).join('')):[];
    const wb=xmlOf(z,'xl/workbook.xml'),rels=xmlOf(z,'xl/_rels/workbook.xml.rels');
    let path='xl/worksheets/sheet1.xml';
    if(wb&&rels){const sh=tagAll(wb.documentElement,'sheet')[0],rid=sh&&(sh.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships','id')||sh.getAttribute('r:id'));
      const rel=tagAll(rels.documentElement,'Relationship').find(r=>r.getAttribute('Id')===rid);
      if(rel){const t=rel.getAttribute('Target');path=t.startsWith('/')?t.slice(1):'xl/'+t.replace(/^\.\//,'')}}
    const doc=xmlOf(z,path);if(!doc)throw new Error('Không tìm thấy sheet dữ liệu trong file');
    return tagAll(doc.documentElement,'row').map(r=>{const row=[];
      tagAll(r,'c').forEach((c,k)=>{const i=c.getAttribute('r')?colIdx(c.getAttribute('r')):k,t=c.getAttribute('t'),ve=tagAll(c,'v')[0];
        let val=ve?ve.textContent:'';
        if(t==='s')val=strs[+val]??'';else if(t==='inlineStr')val=tagAll(c,'t').map(x=>x.textContent).join('');else if(t==='b')val=val==='1';else if(val!==''&&t!=='str'&&t!=='e')val=+val;
        row[i]=val});
      return Array.from(row,x=>x??'')})}
  return {save,read,serial};
})();
const xlDate=v=>{if(typeof v==='number'&&v>20000){const d=new Date(Date.UTC(1899,11,30)+v*864e5);return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate())}
  const s=String(v||'').trim();let m=s.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/);if(m)return m[3]+'-'+pad(m[2])+'-'+pad(m[1]);m=s.match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[0]:''};
function exportAll(){XLSX.save(APP.xlsx+' '+vnDate(TODAYS).replace(/\//g,'-'),APP.sheets())}

/* =========================================================
   DANH MỤC DÙNG CHUNG: bảng + tìm + lọc + thêm/sửa/xóa + nhập Excel
   ent = {coll, name, code:{k,prefix,len}, fields:[{k,l,t,o,req,full,def,hint}], cols:[{l,v,n,c,x,xt,w}],
          filters:[{k,l,o,f}], text:r=>'', sort, canDel, onSave, imp, impSample, tools, foot}
   ========================================================= */
const ENTS={},CST={};
function entList(key){return S[ENTS[key].coll]}
function nextCode(key){const e=ENTS[key],k=e.code.k;let m=0;entList(key).forEach(r=>{const n=parseInt(String(r[k]).replace(/\D/g,''),10)||0;if(n>m)m=n});return e.code.prefix+String(m+1).padStart(e.code.len||3,'0')}
const optsOf=o=>(typeof o==='function'?o():o||[]).map(v=>Array.isArray(v)?v:[v,v]);
function crudPage(key,elId){
  const e=ENTS[key],el=$('#'+elId),st=CST[key]=CST[key]||{q:''};
  el.innerHTML=`${e.top?e.top():''}<div class="toolbar">
    <div class="search grow">${ICO.search}<input class="inp" id="${key}Q" placeholder="${esc(e.ph||'Tìm kiếm...')}" value="${esc(st.q)}"></div>
    ${(e.filters||[]).map(f=>`<select class="inp" id="${key}F_${f.k}"><option value="">${esc(f.l)}</option>${optsOf(f.o).map(([v,l])=>`<option value="${esc(v)}" ${st[f.k]===v?'selected':''}>${esc(l)}</option>`).join('')}</select>`).join('')}
    ${e.tools?e.tools():''}
    ${e.imp?`<button class="btn" onclick="openImp('${key}')">${ICO.up}Nhập từ Excel</button>`:''}
    <button class="btn" onclick="XLSX.save('${esc(e.name)}',[entSheet('${key}')])">${ICO.xl}Xuất Excel</button>
    ${e.noAdd?'':`<button class="btn pri" onclick="openForm('${key}')">${ICO.plus}Thêm ${esc(e.one||e.name.toLowerCase())}</button>`}
  </div><div class="card"><div class="tbl-wrap" id="${key}Tb"></div></div>`;
  $('#'+key+'Q').oninput=ev=>{st.q=ev.target.value;pgReset(key);crudTable(key)};
  (e.filters||[]).forEach(f=>$('#'+key+'F_'+f.k).onchange=ev=>{st[f.k]=ev.target.value;pgReset(key);crudTable(key)});
  crudTable(key)}
function crudRows(key){const e=ENTS[key],st=CST[key]||{q:''},q=fold(st.q||'').trim().split(/\s+/).filter(Boolean);
  let list=entList(key).filter(r=>{const t=fold(e.text(r));return q.every(w=>t.includes(w))&&(e.filters||[]).every(f=>!st[f.k]||(f.f?f.f(r,st[f.k]):String(r[f.k])===st[f.k]))});
  if(e.sort)list=[...list].sort(e.sort);return list}
function crudTable(key){
  const e=ENTS[key],list=crudRows(key),box=$('#'+key+'Tb');if(!box)return;
  if(!list.length){box.innerHTML=`<div class="empty">${entList(key).length?'Không có dòng nào phù hợp.':'Chưa có dữ liệu.'}${e.noAdd?'':`<br><br><button class="btn pri" onclick="openForm('${key}')">${ICO.plus}Thêm ${esc(e.one||e.name.toLowerCase())}</button>`}</div>`;return}
  const off=PRINT_ALL?0:pgState(key).p*pgState(key).n;
  box.innerHTML=`<table><thead><tr><th class="c">#</th>${e.cols.map(c=>`<th class="${c.n?'n':c.c?'c':''}">${c.l}</th>`).join('')}<th></th></tr></thead><tbody>${pgSlice(key,list).map((r,i)=>`<tr><td class="c muted">${off+i+1}</td>${e.cols.map(c=>`<td class="${c.n?'n':c.c?'c':''}">${c.v(r)}</td>`).join('')}<td class="acts">${e.actions?e.actions(r):''}<button class="btn sm ico" title="Sửa" onclick="openForm('${key}','${r.id}')">${ICO.edit}</button> <button class="btn sm ico danger" title="Xóa" onclick="delRow('${key}','${r.id}')">${ICO.del}</button></td></tr>`).join('')}</tbody>${e.foot?`<tfoot>${e.foot(list)}</tfoot>`:''}</table>${pgBar(key,list.length,'crudTable:'+key)}`}
function entSheet(key,list){const e=ENTS[key];list=list||crudRows(key);const cols=e.cols.filter(c=>c.x);
  return {name:e.name,title:(e.xtitle||e.name).toUpperCase(),subtitles:[(S.company&&S.company.name?S.company.name+' · ':'')+'Xuất ngày '+vnDate(TODAYS)],
    columns:cols.map(c=>({label:c.xl||c.l,type:c.xt||'text',width:c.w,sum:c.sum})),rows:list.map(r=>cols.map(c=>c.x(r))),totals:cols.some(c=>c.sum)}}
function fieldHtml(f,v){const id='ff_'+f.k,req=f.req?' *':'';
  let inp;
  if(f.t==='sel')inp=`<select class="inp" id="${id}">${f.blank!==false?'<option value=""></option>':''}${optsOf(f.o).map(([a,b])=>`<option value="${esc(a)}" ${String(v)===String(a)?'selected':''}>${esc(b)}</option>`).join('')}</select>`;
  else if(f.t==='area')inp=`<textarea class="inp" id="${id}" rows="2">${esc(v)}</textarea>`;
  else if(f.t==='date')inp=`<input class="inp" type="date" id="${id}" value="${esc(v)}">`;
  else if(f.t==='num'||f.t==='int')inp=`<input class="inp" type="number" step="any" id="${id}" value="${v===''||v===undefined?'':v}">`;
  else if(f.t==='money')inp=`<input class="inp" inputmode="numeric" id="${id}" value="${v===''||v===undefined?'':fmt(v)}" onblur="this.value=this.value?fmt(toNum(this.value)):''">`;
  else inp=`<input class="inp" id="${id}" value="${esc(v)}" ${f.o?`list="dl_${f.k}"`:''} ${f.ph?`placeholder="${esc(f.ph)}"`:''}>${f.o?`<datalist id="dl_${f.k}">${optsOf(f.o).map(([a])=>`<option value="${esc(a)}">`).join('')}</datalist>`:''}`;
  return `<div class="fg ${f.full?'full':''}"><label class="f">${esc(f.l)}${req}</label>${inp}${f.hint?`<div class="hint">${f.hint}</div>`:''}</div>`}
let FEDIT=null;
function openForm(key,id,preset){
  const e=ENTS[key],r=id?entList(key).find(x=>x.id===id):null;FEDIT={key,r};
  const base={};e.fields.forEach(f=>base[f.k]=typeof f.def==='function'?f.def():(f.def??''));if(e.code&&!r&&!e.code.manual)base[e.code.k]=nextCode(key);
  const v=r||Object.assign(base,preset||{});
  openModal(`<div class="modal-h"><h3>${r?'Sửa':'Thêm'} ${esc(e.one||e.name.toLowerCase())}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><div class="fgrid">${e.fields.map(f=>fieldHtml(f,v[f.k]??'')).join('')}</div>${e.formExtra?e.formExtra(r):''}</div>
  <div class="modal-f">${r?`<button class="btn danger" style="margin-right:auto" onclick="delRow('${key}','${r.id}')">${ICO.del}Xóa</button>`:''}<button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="saveForm()">Lưu</button></div>`,e.wide?'wide':'');
  if(e.formInit)e.formInit(v);
  setTimeout(()=>{const f=$('#modal .inp');f&&f.focus()},60)}
function saveForm(){
  const {key,r}=FEDIT,e=ENTS[key],o={};
  for(const f of e.fields){const el=$('#ff_'+f.k);let v=el?el.value.trim():'';
    if(f.t==='num'||f.t==='int'||f.t==='money')v=v===''?0:toNum(v);
    if(f.req&&(v===''||(f.t==='money'&&!v&&f.req==='pos')))return toast('Chưa điền: '+f.l,'err');o[f.k]=v}
  if(e.code){o[e.code.k]=String(o[e.code.k]||'').toUpperCase();if(!o[e.code.k]){if(e.code.manual)return toast('Chưa điền mã','err');o[e.code.k]=nextCode(key)}
    if(entList(key).some(x=>x[e.code.k]===o[e.code.k]&&(!r||x.id!==r.id)))return toast('Mã '+o[e.code.k]+' đã có, chọn mã khác','err')}
  if(e.validate){const m=e.validate(o,r);if(m)return toast(m,'err')}
  if(r){const old={...r};Object.assign(r,o);e.onSave&&e.onSave(r,old)}else{const n={id:uid(),...o};entList(key).push(n);e.onSave&&e.onSave(n,null)}
  save();closeModal();toast(r?'Đã cập nhật':'Đã thêm mới');rerender()}
function delRow(key,id){const e=ENTS[key],r=entList(key).find(x=>x.id===id);if(!r)return;
  const block=e.canDel&&e.canDel(r);if(block)return toast(block,'err');
  if(!confirm('Xóa '+(e.one||'dòng')+' "'+(e.label?e.label(r):r.name||r.code||'')+'"?'))return;
  S[e.coll]=entList(key).filter(x=>x.id!==id);e.onDel&&e.onDel(r);save();closeModal();toast('Đã xóa');rerender()}

/* nhập danh mục từ file Excel / CSV / dán */
let IMP={key:'',items:[]};
function openImp(key){const e=ENTS[key];IMP={key,items:[]};
  openModal(`<div class="modal-h"><h3>Nhập ${esc(e.name.toLowerCase())} từ Excel</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><label class="btn pri" style="cursor:pointer">${ICO.up}Chọn file Excel<input type="file" id="impFile" accept=".xlsx,.csv" hidden></label>
    <button class="btn" onclick="impTemplate('${key}')">${ICO.down}Tải file mẫu</button><span id="impName" class="muted" style="font-size:13px">Chưa chọn file</span></div>
    <p class="hint2">File cần có dòng tiêu đề với các cột: <b>${e.fields.filter(f=>!f.noImp).map(f=>esc(f.l)).join(', ')}</b>. Cột thiếu cũng được, trùng mã thì cập nhật dòng cũ.</p>
    <label class="f">Hoặc dán thẳng từ Excel (bôi đen cả dòng tiêu đề, Ctrl + C rồi Ctrl + V vào ô dưới)</label>
    <textarea class="inp" id="impPaste" rows="3" placeholder="Dán dữ liệu từ Excel vào đây..." style="font-size:12.5px"></textarea>
    <div id="impT"></div></div>
  <div class="modal-f"><span id="impSum" class="muted" style="margin-right:auto;align-self:center;font-size:13px"></span><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" id="impOk" disabled onclick="impApply()">Nhập dữ liệu</button></div>`,'wide');
  $('#impFile').onchange=async ev=>{const f=ev.target.files[0];if(!f)return;ev.target.value='';
    try{IMP.items=impParse(key,await XLSX.read(f));$('#impName').innerHTML=`<b>${esc(f.name)}</b> · đọc được ${IMP.items.length} dòng`}catch(err){IMP.items=[];toast(err.message||'Không đọc được file','err')}drawImp()};
  let tm;$('#impPaste').oninput=ev=>{clearTimeout(tm);tm=setTimeout(()=>{const t=ev.target.value;IMP.items=t.trim()?impParse(key,t.replace(/\r/g,'').split('\n').map(l=>l.split('\t'))):[];drawImp()},250)};
  drawImp()}
function impParse(key,rows){const e=ENTS[key],F=e.fields.filter(f=>!f.noImp);
  rows=rows.filter(r=>r&&r.some(c=>String(c??'').trim()!==''));let hr=-1,map={};
  for(let r=0;r<Math.min(rows.length,15)&&hr<0;r++){const m={};rows[r].forEach((h,i)=>{const n=normH(h);const f=F.find(f=>normH(f.l)===n||normH(f.k)===n||(f.alias||[]).includes(n));if(f&&m[f.k]===undefined)m[f.k]=i});
    if(Object.keys(m).length>=2){hr=r;map=m}}
  if(hr<0)F.forEach((f,i)=>map[f.k]=i);
  return rows.slice(hr+1).map(r=>{const o={},has={};F.forEach(f=>{if(map[f.k]===undefined)return;let v=r[map[f.k]];if(String(v??'').trim()==='')return;has[f.k]=1;
    o[f.k]=f.t==='num'||f.t==='int'||f.t==='money'?toNum(v):f.t==='date'?xlDate(v):String(v).trim()});o._has=has;return o}).filter(o=>Object.keys(o._has).length&&(!e.impReq||o[e.impReq]))}
function drawImp(){const e=ENTS[IMP.key],it=IMP.items,ck=e.code&&e.code.k,F=e.fields.filter(f=>!f.noImp);
  const nOld=ck?it.filter(o=>o[ck]&&entList(IMP.key).some(x=>x[ck]===String(o[ck]).toUpperCase())).length:0;
  $('#impSum').textContent=it.length?`${it.length-nOld} dòng mới · ${nOld} dòng cập nhật`:'';
  const ok=$('#impOk');ok.disabled=!it.length;ok.textContent=it.length?`Nhập ${it.length} dòng`:'Nhập dữ liệu';
  $('#impT').innerHTML=it.length?`<div class="imp-prev"><table><thead><tr>${F.map(f=>`<th>${esc(f.l)}</th>`).join('')}</tr></thead><tbody>${it.slice(0,30).map(o=>`<tr>${F.map(f=>`<td>${esc(f.t==='date'?vnDate(o[f.k]):(f.t==='money'||f.t==='num')&&o[f.k]!==undefined?fmt(o[f.k]):o[f.k]??'')}</td>`).join('')}</tr>`).join('')}</tbody></table></div>${it.length>30?`<p class="hint2">... và ${it.length-30} dòng nữa</p>`:''}`:'<div class="empty" style="padding:18px">Chọn file Excel hoặc dán dữ liệu để xem trước</div>'}
function impApply(){const key=IMP.key,e=ENTS[key],ck=e.code&&e.code.k;let add=0,upd=0;
  IMP.items.forEach(o=>{const has=o._has;delete o._has;
    if(ck&&o[ck])o[ck]=String(o[ck]).toUpperCase();
    const old=ck&&o[ck]?entList(key).find(x=>x[ck]===o[ck]):null;
    if(old){Object.keys(has).forEach(k=>old[k]=o[k]);upd++}
    else{const n={id:uid()};e.fields.forEach(f=>n[f.k]=o[f.k]!==undefined?o[f.k]:(typeof f.def==='function'?f.def():(f.def??'')));if(ck&&!n[ck]){if(e.code.manual)return;n[ck]=nextCode(key)}entList(key).push(n);e.onSave&&e.onSave(n,null);add++}});
  save();closeModal();rerender();toast(`Đã thêm ${add} dòng${upd?', cập nhật '+upd+' dòng':''}. Nhớ bấm "Lưu đè dữ liệu".`)}
function impTemplate(key){const e=ENTS[key],F=e.fields.filter(f=>!f.noImp);
  XLSX.save('Mau nhap '+e.name,[{name:e.name,title:'MẪU NHẬP '+e.name.toUpperCase(),subtitles:['Điền từ dòng dưới tiêu đề. Số ghi dạng số, ngày ghi dd/mm/yyyy.'],noFilter:true,
    columns:F.map(f=>({label:f.l,type:f.t==='money'||f.t==='num'||f.t==='int'?'int':f.t==='date'?'date':'text',width:f.w||18})),rows:(e.impSample||[]).map(r=>F.map(f=>r[f.k]??''))}])}

/* =========================================================
   CÀI ĐẶT & DỮ LIỆU (dùng chung)
   ========================================================= */
function renderSettings(){
  const co=S.company||{},cf=APP.coFields||[['name','Tên đơn vị'],['addr','Địa chỉ'],['phone','Điện thoại'],['tax','Mã số thuế']];
  $('#page-settings').innerHTML=`<div class="set-grid">
    <div class="card"><div class="set-ic" style="background:var(--cd-nhat);color:var(--cd-sb)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></div>
      <h4>Lưu đè dữ liệu vào file</h4><p>Ghi toàn bộ dữ liệu hiện tại vào chính file .html này. Lần sau mở file là thấy dữ liệu mới nhất. Phím tắt: Ctrl + S.</p><button class="btn save" onclick="saveToFile()">Lưu đè dữ liệu</button> <button class="btn sm" onclick="forgetFile()">Chọn lại file</button></div>
    <div class="card"><div class="set-ic" style="background:#e7f6ee;color:#1d7a46">${ICO.xl}</div>
      <h4>Xuất Excel</h4><p>Xuất toàn bộ các bảng ra một file .xlsx, mỗi bảng một sheet, số liệu để dạng số tính tiếp được.</p><button class="btn xl" onclick="exportAll()">${ICO.xl}Xuất Excel</button></div>
    <div class="card"><div class="set-ic" style="background:var(--cd-nhat);color:var(--chu-dao)">${ICO.down}</div>
      <h4>Sao lưu dữ liệu</h4><p>Tải toàn bộ dữ liệu về một file .json để cất giữ riêng.</p><button class="btn pri" onclick="backup()">Tải file sao lưu</button></div>
    <div class="card"><div class="set-ic" style="background:var(--inbg);color:var(--in)">${ICO.up}</div>
      <h4>Khôi phục dữ liệu</h4><p>Chọn file .json đã sao lưu. Dữ liệu hiện tại sẽ được thay bằng dữ liệu trong file.</p><label class="btn" style="cursor:pointer">Chọn file sao lưu<input type="file" accept=".json" hidden onchange="this.files[0]&&restoreFrom(this.files[0]);this.value=''"></label></div>
    <div class="card"><div class="set-ic" style="background:var(--warnbg);color:#a36d00"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.7L3 8"/><path d="M3 3v5h5"/></svg></div>
      <h4>Nạp lại dữ liệu mẫu</h4><p>Xóa dữ liệu hiện tại, nạp lại dữ liệu mẫu để xem thử.</p><button class="btn" onclick="resetSample()">Nạp dữ liệu mẫu</button></div>
    <div class="card"><div class="set-ic" style="background:var(--dangerbg);color:var(--danger)">${ICO.del}</div>
      <h4>Xóa dữ liệu mẫu, dùng thật</h4><p>${APP.clearNote||'Xóa sạch dữ liệu mẫu để nhập dữ liệu thật của mình từ đầu.'}</p><button class="btn danger" onclick="clearAll()">Xóa dữ liệu mẫu</button></div>
  </div>
  <div class="card" style="margin-top:16px;padding:20px">
    <h4 style="font-size:15px;margin-bottom:4px">Thông tin đơn vị (in trên phiếu, sổ, báo cáo)</h4>
    <p class="muted" style="font-size:13px;margin-bottom:14px">Điền một lần, các mẫu in ra sẽ tự có tên đơn vị, địa chỉ.</p>
    <div class="fgrid">${cf.map(([k,l])=>`<div class="fg"><label class="f">${esc(l)}</label><input class="inp" id="co_${k}" value="${esc(co[k]||'')}"></div>`).join('')}</div>
    <button class="btn pri" onclick="saveCompany()">Lưu thông tin</button></div>
  ${APP.settingsExtra?APP.settingsExtra():''}
  <div class="card" style="margin-top:16px;padding:18px;font-size:13px;color:var(--muted)">Dữ liệu nằm ngay trong file <b>${esc(FILE_NAME)}</b>${SAVED_AT?', lưu lần cuối lúc '+hm(SAVED_AT):''}. Không gửi đi đâu, không cần mạng.<br>File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel.</div>`}
function saveCompany(){const cf=APP.coFields||[['name'],['addr'],['phone'],['tax']];S.company=S.company||{};cf.forEach(([k])=>S.company[k]=$('#co_'+k).value.trim());save();toast('Đã lưu thông tin đơn vị')}

/* =========================================================
   KHỞI ĐỘNG
   ========================================================= */
function boot(){
  let draft=null;try{draft=JSON.parse(localStorage.getItem(LS))}catch(e){}
  if(APP.valid(EMB)){S=EMB;SAVED_AT=EMB.savedAt||0;if(APP.valid(draft)&&(draft.updated||0)>(EMB.updated||0))ASK_DRAFT=draft}
  else{S=APP.seed();if(APP.valid(draft)&&(draft.updated||0)>0)ASK_DRAFT=draft}
  APP.fix&&APP.fix(S);
  $('#today').textContent='Hôm nay '+vnDate(TODAYS);
  $$('#nav a').forEach(a=>a.onclick=()=>go(a.dataset.page));
  $('#burger').onclick=()=>{$('#sb').classList.add('open');$('#mask').classList.add('show')};
  $('#mask').onclick=()=>{$('#sb').classList.remove('open');$('#mask').classList.remove('show')};
  $('#ov').addEventListener('mousedown',e=>{if(e.target.id==='ov')closeModal()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
  APP.init&&APP.init();
  refreshSave();APP.chrome&&APP.chrome();
  go((location.hash||'').slice(1)||'dashboard');
  if(ASK_DRAFT){const d=ASK_DRAFT;
    openModal(`<div class="modal-h"><h3>Có bản tạm chưa lưu vào file</h3><button class="x" onclick="closeModal()">×</button></div>
    <div class="modal-b"><p>Trình duyệt còn giữ một bản dữ liệu sửa lúc <b>${hm(d.updated)}</b>, mới hơn dữ liệu đang có trong file (có thể lần trước chưa bấm "Lưu đè dữ liệu").</p><p style="margin-top:8px">Khôi phục bản tạm này không?</p></div>
    <div class="modal-f"><button class="btn" id="drNo">Không, dùng dữ liệu trong file</button><button class="btn pri" id="drYes">Khôi phục bản tạm</button></div>`,'small');
    $('#drYes').onclick=()=>{S=d;APP.fix&&APP.fix(S);DIRTY=true;closeModal();refreshSave();rerender();toast('Đã khôi phục bản tạm. Bấm "Lưu đè dữ liệu" để ghi vào file.')};
    $('#drNo').onclick=()=>{try{localStorage.removeItem(LS)}catch(e){}closeModal()}}
}

/* =========================================================
   QUẢN LÝ ĐỒNG PHỤC NHÂN VIÊN KHÁCH SẠN
   ========================================================= */
const BP=['Lễ tân','Buồng phòng','Nhà hàng (F&B)','Bếp','Bảo vệ','Kỹ thuật'];
const LOAIDP=['Áo','Quần','Váy','Giày','Phụ kiện'];
const KIEU={ao:'Theo size áo',quan:'Theo size quần, váy',giay:'Theo size giày',free:'Một cỡ (Free size)'};
const SZ_ORDER=['XS','S','M','L','XL','XXL','3XL','35','36','37','38','39','40','41','42','43','44','Free'];
const szSort=(a,b)=>{const i=SZ_ORDER.indexOf(a),j=SZ_ORDER.indexOf(b);return (i<0?99:i)-(j<0?99:j)||String(a).localeCompare(String(b))};
const TYPES={
  nhap:{t:'PHIẾU NHẬP KHO ĐỒNG PHỤC',name:'Nhập kho',pre:'PN',btn:'Lưu phiếu nhập',tag:'in',verb:'nhập'},
  cap:{t:'PHIẾU CẤP PHÁT ĐỒNG PHỤC',name:'Cấp phát',pre:'CP',btn:'Lưu phiếu cấp',tag:'cat',verb:'cấp',reasons:['Cấp mới (nhân viên mới)','Cấp thay định kỳ','Đổi size','Cấp bù do hỏng, mất','Cấp bổ sung']},
  thu:{t:'PHIẾU THU HỒI ĐỒNG PHỤC',name:'Thu hồi',pre:'TH',btn:'Lưu phiếu thu hồi',tag:'out',verb:'thu',reasons:['Nghỉ việc','Đổi size','Báo hỏng','Báo mất','Thu đồ cũ khi thay mới','Thu hồi khác']}};
const TT=['Dùng lại được','Hủy bỏ','Mất'];
const NCC0=['Xưởng may Đồng phục Hòa Bình','Công ty TNHH Đồng phục Sao Mai','Cơ sở giày da Phú Gia'];

/* ---------- tra cứu, tính tồn ---------- */
const itemOf=c=>S.items.find(i=>i.code===c);
const nvOf=c=>S.staff.find(s=>s.code===c);
const sizesOf=it=>it?String(it.sizes||'Free').split(/[,;]/).map(s=>s.trim()).filter(Boolean).sort(szSort):[];
const nvSize=(nv,it)=>{if(!it||!nv)return '';const s=it.kieu==='ao'?nv.sAo:it.kieu==='quan'?nv.sQuan:it.kieu==='giay'?nv.sGiay:'Free';const L=sizesOf(it);return L.includes(s)?s:(L.length===1?L[0]:'')};
const active=()=>S.staff.filter(s=>s.tt!=='Nghỉ việc');
function stockMap(upto){const m={};S.mv.forEach(v=>{if(upto&&v.date>upto)return;const k=v.item+'|'+v.size;
  if(v.type==='nhap')m[k]=(m[k]||0)+v.sl;else if(v.type==='cap')m[k]=(m[k]||0)-v.sl;else if(v.tinhtrang==='Dùng lại được')m[k]=(m[k]||0)+v.sl});return m}
function holdMap(){const m={};S.mv.forEach(v=>{if(v.type==='nhap')return;const k=v.nv+'|'+v.item+'|'+v.size;m[k]=(m[k]||0)+(v.type==='cap'?v.sl:-v.sl)});return m}
function holdsOf(nv,H){H=H||holdMap();return Object.entries(H).filter(([k,q])=>q>0&&k.startsWith(nv+'|')).map(([k,q])=>{const [,item,size]=k.split('|');return {item,size,q,it:itemOf(item)}})}
function lastIssue(){const m={};S.mv.forEach(v=>{if(v.type!=='cap')return;const k=v.nv+'|'+v.item;if(!m[k]||v.date>m[k])m[k]=v.date});return m}
const cellSt=(q,min)=>q<=0?'zero':q<=min?'low':'ok';
function dueList(){const L=lastIssue(),out=[];
  active().forEach(nv=>S.norms.filter(n=>n.vitri===nv.vitri).forEach(n=>{const it=itemOf(n.item);if(!it)return;const last=L[nv.code+'|'+it.code];
    const due=last?addMonths(last,+it.chuky||12):(nv.ngayvao||TODAYS),days=diffDays(TODAYS,due);
    if(days<=30)out.push({nv,it,n,last,due,days,never:!last})}));
  return out.sort((a,b)=>a.days-b.days)}
function oweList(){const H=holdMap();return S.staff.filter(s=>s.tt==='Nghỉ việc').map(s=>{const h=holdsOf(s.code,H);return {s,h,q:sum(h,x=>x.q),v:sum(h,x=>x.q*(x.it?+x.it.gia:0))}}).filter(o=>o.q>0)}
function lowCells(){const m=stockMap(),out=[];S.items.forEach(it=>sizesOf(it).forEach(sz=>{const q=m[it.code+'|'+sz]||0;if(q<=(+it.min||0))out.push({it,sz,q})}));return out.sort((a,b)=>a.q-b.q)}
const stockVal=()=>{const m=stockMap();return sum(Object.entries(m),([k,q])=>{const it=itemOf(k.split('|')[0]);return Math.max(0,q)*(it?+it.gia:0)})};
function docsOf(type){const m={};S.mv.forEach(v=>{if(type&&v.type!==type)return;const g=m[v.doc]=m[v.doc]||{doc:v.doc,type:v.type,date:v.date,nv:v.nv,ncc:v.ncc,lydo:v.lydo,note:v.note,lines:[],q:0,val:0,kt:0};g.lines.push(v);g.q+=v.sl;g.val+=v.sl*(+v.gia||0);g.kt+=+v.kt||0});
  return Object.values(m).sort((a,b)=>b.date.localeCompare(a.date)||b.doc.localeCompare(a.doc))}
function nextDoc(type){const pre=TYPES[type].pre;let mx=0;S.mv.forEach(v=>{if(v.type===type){const n=parseInt(String(v.doc).replace(/\D/g,''),10)||0;if(n>mx)mx=n}});return pre+String(mx+1).padStart(4,'0')}

/* ---------- dữ liệu mẫu ---------- */
const ITEMS0=[
 ['DP01','Áo vest lễ tân nữ','Áo','ao','Lễ tân',650000,'S,M,L,XL',12,2],['DP02','Váy công sở lễ tân','Váy','quan','Lễ tân',380000,'S,M,L,XL',12,2],
 ['DP03','Áo sơ mi trắng dài tay','Áo','ao','Lễ tân',220000,'S,M,L,XL,XXL',6,4],['DP04','Quần âu đen','Quần','quan','Nhà hàng (F&B)',280000,'S,M,L,XL,XXL',12,3],
 ['DP05','Áo buồng phòng','Áo','ao','Buồng phòng',210000,'S,M,L,XL,XXL',6,4],['DP06','Quần buồng phòng','Quần','quan','Buồng phòng',180000,'S,M,L,XL,XXL',6,4],
 ['DP07','Tạp dề phục vụ','Phụ kiện','free','Nhà hàng (F&B)',90000,'Free',6,8],['DP08','Áo phục vụ nhà hàng','Áo','ao','Nhà hàng (F&B)',240000,'S,M,L,XL',6,3],
 ['DP09','Áo bếp (chef coat)','Áo','ao','Bếp',320000,'M,L,XL,XXL',6,3],['DP10','Mũ bếp','Phụ kiện','free','Bếp',60000,'Free',6,8],
 ['DP11','Quần bếp caro','Quần','quan','Bếp',200000,'M,L,XL,XXL',6,3],['DP12','Áo bảo vệ','Áo','ao','Bảo vệ',260000,'M,L,XL,XXL',12,2],
 ['DP13','Giày da đen công sở','Giày','giay','Lễ tân',450000,'36,37,38,39,40,41,42,43',12,1],['DP14','Bảng tên nhân viên','Phụ kiện','free','Lễ tân',35000,'Free',24,10],
 ['DP15','Bộ quần áo kỹ thuật','Áo','ao','Kỹ thuật',350000,'M,L,XL,XXL',12,2]];
const NORM0={'Lễ tân':[['DP01',2],['DP02',2],['DP03',3],['DP13',1],['DP14',1]],'Hành lý (Bellman)':[['DP03',3],['DP04',2],['DP13',1],['DP14',1]],
 'Nhân viên buồng phòng':[['DP05',3],['DP06',3],['DP14',1]],'Giám sát buồng':[['DP05',2],['DP06',2],['DP03',2],['DP14',1]],
 'Phục vụ nhà hàng':[['DP08',3],['DP04',2],['DP07',2],['DP14',1]],'Pha chế':[['DP08',3],['DP04',2],['DP07',2],['DP14',1]],
 'Đầu bếp':[['DP09',3],['DP10',3],['DP11',3],['DP14',1]],'Phụ bếp':[['DP09',2],['DP10',2],['DP11',2]],
 'Bảo vệ':[['DP12',2],['DP04',2],['DP13',1],['DP14',1]],'Kỹ thuật viên':[['DP15',2],['DP13',1],['DP14',1]]};
// [mã, tên, giới tính, bộ phận, vị trí, vào (tháng thứ mấy của dữ liệu, null = vào từ trước), nghỉ (tháng), size áo, quần, giày, không trả]
const STAFF0=[
 ['NV001','Nguyễn Thu Trang','Nữ','Lễ tân','Lễ tân',null,null,'S','S','37'],['NV002','Trần Minh Anh','Nữ','Lễ tân','Lễ tân',null,null,'M','M','38'],
 ['NV003','Lê Hoàng Yến','Nữ','Lễ tân','Lễ tân',null,4,'S','S','36'],['NV004','Phạm Gia Huy','Nam','Lễ tân','Hành lý (Bellman)',null,null,'L','L','41'],
 ['NV005','Vũ Ngọc Hân','Nữ','Lễ tân','Lễ tân',5,null,'M','M','37'],
 ['NV006','Đỗ Thị Hoa','Nữ','Buồng phòng','Giám sát buồng',null,null,'L','L',''],['NV007','Bùi Thị Lan','Nữ','Buồng phòng','Nhân viên buồng phòng',null,null,'M','M',''],
 ['NV008','Hoàng Văn Nam','Nam','Buồng phòng','Nhân viên buồng phòng',null,null,'XL','XL',''],['NV009','Ngô Thị Mai','Nữ','Buồng phòng','Nhân viên buồng phòng',null,7,'M','L',''],
 ['NV010','Đặng Thị Thu','Nữ','Buồng phòng','Nhân viên buồng phòng',null,null,'S','M',''],['NV011','Lý Văn Tâm','Nam','Buồng phòng','Nhân viên buồng phòng',3,null,'L','L',''],
 ['NV012','Phan Thị Hạnh','Nữ','Buồng phòng','Nhân viên buồng phòng',8,null,'M','M',''],
 ['NV013','Trịnh Quốc Bảo','Nam','Nhà hàng (F&B)','Phục vụ nhà hàng',null,null,'L','L',''],['NV014','Mai Phương Thảo','Nữ','Nhà hàng (F&B)','Phục vụ nhà hàng',null,null,'S','S',''],
 ['NV015','Đinh Tuấn Kiệt','Nam','Nhà hàng (F&B)','Phục vụ nhà hàng',null,11,'M','M','',1],['NV016','Lương Khánh Linh','Nữ','Nhà hàng (F&B)','Phục vụ nhà hàng',11,null,'S','S',''],
 ['NV017','Tạ Minh Đức','Nam','Nhà hàng (F&B)','Pha chế',null,null,'L','L',''],['NV018','Hồ Thị Ngọc','Nữ','Nhà hàng (F&B)','Phục vụ nhà hàng',null,null,'M','M',''],
 ['NV019','Nguyễn Văn Hùng','Nam','Bếp','Đầu bếp',null,null,'XL','XL',''],['NV020','Lê Văn Phúc','Nam','Bếp','Đầu bếp',null,null,'L','L',''],
 ['NV021','Trần Thị Kim','Nữ','Bếp','Phụ bếp',null,null,'M','M',''],['NV022','Phạm Văn Lộc','Nam','Bếp','Phụ bếp',6,null,'L','XL',''],
 ['NV023','Võ Văn Sơn','Nam','Bảo vệ','Bảo vệ',null,null,'XL','XL','42'],['NV024','Dương Văn Hải','Nam','Bảo vệ','Bảo vệ',null,null,'L','L','41'],
 ['NV025','Châu Văn Tài','Nam','Bảo vệ','Bảo vệ',9,null,'XXL','XXL','43'],
 ['NV026','Kiều Văn Long','Nam','Kỹ thuật','Kỹ thuật viên',null,null,'L','L','42'],['NV027','Mạc Đình Quân','Nam','Kỹ thuật','Kỹ thuật viên',null,null,'XL','XL','41']];
function baseItems(){return ITEMS0.map(a=>({id:uid()+a[0],code:a[0],name:a[1],loai:a[2],kieu:a[3],bp:a[4],dvt:'Cái',gia:a[5],sizes:a[6],chuky:a[7],min:a[8],note:''}))}
function baseNorms(){return Object.entries(NORM0).flatMap(([vt,l])=>l.map(([c,q])=>({id:uid()+vt+c,vitri:vt,item:c,sl:q})))}
function seed(){
  let r=23;const rnd=()=>{r=(r*16807)%2147483647;return (r-1)/2147483646},ri=(a,b)=>a+Math.floor(rnd()*(b-a+1)),pick=a=>a[ri(0,a.length-1)];
  const y0=TODAY.getFullYear(),m0=TODAY.getMonth()-11,M=(m,d)=>{const dim=new Date(y0,m0+m+1,0).getDate();return ymd(new Date(y0,m0+m,Math.min(d,dim)))};
  const items=baseItems(),norms=baseNorms();
  const staff=STAFF0.map(a=>({id:uid()+a[0],code:a[0],name:a[1],gt:a[2],bp:a[3],vitri:a[4],ngayvao:a[5]===null?ymd(new Date(y0-ri(1,5),ri(0,11),ri(1,28))):M(a[5],ri(2,20)),tt:'Đang làm',ngaynghi:'',phone:'09'+ri(10000000,99999999),sAo:a[7],sQuan:a[8],sGiay:a[9],note:'',_q:a[6],_owe:a[10]}));
  S={ver:1,items,staff,norms,mv:[],company:{name:'Khách sạn Hoàng Gia Riverside',addr:'Số 88 đường Bạch Đằng, phường Hải Châu, TP Đà Nẵng',phone:'0236 3888 888',kho:'Kho Hành chính - Nhân sự'},updated:0};
  const stock={},hold={},last={},seq={PN:0,CP:0,TH:0};
  const post=(type,date,hdr,lines)=>{if(date>TODAYS||!lines.length)return;const pre=TYPES[type].pre,doc=pre+String(++seq[pre]).padStart(4,'0');
    lines.forEach(l=>{const it=itemOf.call(null,l.item)||items.find(x=>x.code===l.item);const v={id:uid()+S.mv.length,doc,type,date,item:l.item,size:l.size,sl:l.sl,gia:l.gia??it.gia,nv:hdr.nv||'',ncc:hdr.ncc||'',lydo:hdr.lydo||'',tinhtrang:l.tt||'',kt:l.kt||0,note:hdr.note||''};S.mv.push(v);
      const k=v.item+'|'+v.size,hk=v.nv+'|'+v.item+'|'+v.size;
      if(type==='nhap')stock[k]=(stock[k]||0)+v.sl;else if(type==='cap'){stock[k]=(stock[k]||0)-v.sl;hold[hk]=(hold[hk]||0)+v.sl;last[v.nv+'|'+v.item]=date}else{hold[hk]=(hold[hk]||0)-v.sl;if(v.tinhtrang==='Dùng lại được')stock[k]=(stock[k]||0)+v.sl}})};
  const restock=(date,lines)=>{const need={};lines.forEach(l=>{const k=l.item+'|'+l.size;need[k]=(need[k]||0)+l.sl});
    const add=Object.entries(need).filter(([k,q])=>(stock[k]||0)<q).map(([k,q])=>{const [item,size]=k.split('|'),it=items.find(x=>x.code===item);return {item,size,sl:Math.ceil((q-(stock[k]||0)+it.min+ri(1,4))/5)*5}});
    if(add.length)post('nhap',date,{ncc:pick(NCC0),note:'Mua bổ sung'},add)};
  const issue=(date,nv,lydo,list)=>{const lines=list.map(([c,q])=>{const it=items.find(x=>x.code===c);return {item:c,size:nvSize(nv,it)||sizesOf(it)[1]||sizesOf(it)[0],sl:q}});restock(date,lines);post('cap',date,{nv:nv.code,lydo},lines)};
  const normOf=nv=>NORM0[nv.vitri]||[];
  // đầu kỳ: nhập lớn
  const big=[];items.forEach(it=>sizesOf(it).forEach(sz=>big.push({item:it.code,size:sz,sl:it.kieu==='free'?40:it.kieu==='giay'?3:({S:5,M:10,L:10,XL:7,XXL:4}[sz]||4)})));
  post('nhap',M(0,2),{ncc:NCC0[0],note:'Nhập đầu kỳ'},big.filter(l=>items.find(x=>x.code===l.item).kieu!=='giay'));
  post('nhap',M(0,2),{ncc:NCC0[2],note:'Nhập giày đầu kỳ'},big.filter(l=>items.find(x=>x.code===l.item).kieu==='giay'));
  staff.filter(s=>s.ngayvao<M(0,1)).forEach((s,i)=>issue(M(0,4+Math.floor(i/4)),s,'Cấp thay định kỳ',normOf(s)));
  for(let m=0;m<12;m++){
    const acts=[];
    staff.filter(s=>s.ngayvao>=M(m,1)&&s.ngayvao<=M(m,31)).forEach(s=>acts.push([s.ngayvao,()=>issue(s.ngayvao,s,'Cấp mới (nhân viên mới)',normOf(s))]));
    staff.filter(s=>s._q===m).forEach(s=>{const d=M(m,ri(10,20));s.tt='Nghỉ việc';s.ngaynghi=d;
      if(!s._owe)acts.push([d,()=>{const h=Object.entries(hold).filter(([k,q])=>q>0&&k.startsWith(s.code+'|')).map(([k,q])=>{const [,item,size]=k.split('|');const lost=m===7&&item==='DP05';return {item,size,sl:q,tt:lost?'Mất':rnd()<0.5?'Dùng lại được':'Hủy bỏ',kt:lost?Math.round(items.find(x=>x.code===item).gia*0.5/1000)*1000:0}});post('thu',d,{nv:s.code,lydo:'Nghỉ việc'},h)}])});
    if(m>0)staff.filter(s=>s.tt!=='Nghỉ việc'||s.ngaynghi>M(m,28)).forEach(s=>{const d=M(m,ri(5,25)),due=normOf(s).filter(([c])=>{const it=items.find(x=>x.code===c),l=last[s.code+'|'+c];return l&&addMonths(l,it.chuky)<=d&&rnd()<0.72});
      if(due.length&&d<=TODAYS&&s.ngayvao<d)acts.push([d,()=>{post('thu',d,{nv:s.code,lydo:'Thu đồ cũ khi thay mới',note:'Đồ cũ hết hạn sử dụng'},due.map(([c,q])=>({item:c,size:nvSize(s,items.find(x=>x.code===c))||sizesOf(items.find(x=>x.code===c))[1],sl:Math.min(q,hold[s.code+'|'+c+'|'+(nvSize(s,items.find(x=>x.code===c))||'')]||0),tt:'Hủy bỏ'})).filter(l=>l.sl>0));issue(d,s,'Cấp thay định kỳ',due)}])});
    if(m===2||m===6){const s=staff[m===2?13:20],c=m===2?'DP08':'DP09',it=items.find(x=>x.code===c),d=M(m,14),old=nvSize(s,it),nw=sizesOf(it)[Math.min(sizesOf(it).indexOf(old)+1,sizesOf(it).length-1)];
      acts.push([d,()=>{post('thu',d,{nv:s.code,lydo:'Đổi size',note:'Size '+old+' chật'},[{item:c,size:old,sl:1,tt:'Dùng lại được'}]);if(m===2)s.sAo=nw;else s.sAo=nw;issue(d,s,'Đổi size',[[c,1]])}])}
    if(m===5||m===9||m===11){const s=staff[m===5?6:m===9?18:23],c=m===5?'DP05':m===9?'DP09':'DP12',it=items.find(x=>x.code===c),d=M(m,m===11?ri(8,16):ri(8,18)),lost=m!==9;
      acts.push([d,()=>{post('thu',d,{nv:s.code,lydo:lost?'Báo mất':'Báo hỏng',note:m===5?'Làm mất khi giặt ủi':m===9?'Rách do bỏng dầu':'Để quên khi đi công tác'},[{item:c,size:nvSize(s,it),sl:1,tt:lost?'Mất':'Hủy bỏ',kt:lost?Math.round(it.gia*0.5/1000)*1000:0}]);issue(d,s,'Cấp bù do hỏng, mất',[[c,1]])}])}
    if(m===11){const s=staff[1],d=M(m,ri(3,12));acts.push([d,()=>issue(d,s,'Cấp bổ sung',[['DP03',1]])]);
      acts.push([M(m,2),()=>post('nhap',M(m,2),{ncc:NCC0[1],note:'Chuẩn bị thay đồng phục định kỳ'},[['DP01','S',4],['DP01','M',4],['DP02','S',4],['DP02','M',4],['DP09','L',5],['DP12','L',3],['DP12','XL',3]].map(([item,size,sl])=>({item,size,sl})))])}
    if(m>0&&m<9)acts.push([M(m,ri(1,6)),()=>{const add=[];items.forEach(it=>sizesOf(it).forEach(sz=>{const k=it.code+'|'+sz;if((stock[k]||0)<=it.min&&rnd()<0.8)add.push({item:it.code,size:sz,sl:Math.ceil((it.min*2+2-(stock[k]||0))/5)*5})}));
      const g={};add.forEach(l=>{const n=items.find(x=>x.code===l.item).kieu==='giay'?NCC0[2]:pick(NCC0.slice(0,2));(g[n]=g[n]||[]).push(l)});Object.entries(g).forEach(([n,ls])=>post('nhap',M(m,ri(1,6)),{ncc:n,note:'Mua bổ sung định kỳ'},ls))}]);
    acts.sort((a,b)=>a[0].localeCompare(b[0])).forEach(a=>a[1]());
  }
  staff.forEach(s=>{delete s._q;delete s._owe});
  return S}

/* =========================================================
   KHAI BÁO CHUNG
   ========================================================= */
let DUE=[],OWE=[],LOW=[];
Object.assign(APP,{
  valid:d=>d&&Array.isArray(d.items)&&Array.isArray(d.staff)&&Array.isArray(d.mv),
  fix:d=>{d.norms=d.norms||[];d.company=d.company||{}},
  seed,
  empty:()=>({ver:1,items:S.items,norms:S.norms,staff:[],mv:[]}),
  clearNote:'Xóa nhân viên và toàn bộ phiếu nhập, cấp, thu mẫu. Danh mục đồng phục và định mức vẫn giữ lại để sửa tiếp.',
  coFields:[['name','Tên khách sạn / đơn vị'],['addr','Địa chỉ'],['phone','Điện thoại'],['kho','Bộ phận quản lý kho đồng phục']],
  chrome(){DUE=dueList();OWE=oweList();LOW=lowCells();
    $('#dueBadge').textContent=(DUE.filter(x=>x.days<=0).length+OWE.length)||'';$('#lowBadge').textContent=LOW.length||'';
    const A=active(),H=holdMap(),okN=A.filter(nv=>fullNorm(nv,H)).length,pct=A.length?Math.round(okN/A.length*100):0;
    $('#sbCard').innerHTML=`<small>Giá trị đồng phục tồn kho</small><b>${money(stockVal())}</b><div class="bar"><i style="width:${pct}%"></i></div><p>${A.length} nhân viên · ${pct}% đủ định mức</p>`},
  init(){
    segInit('dPeriod',v=>{PERIOD=v;renderDashboard()});
    $('#hBp').innerHTML='<option value="">Mọi bộ phận</option>'+BP.map(b=>`<option>${b}</option>`).join('');
    ['#hQ','#hType','#hBp','#hFrom','#hTo'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('hist');renderHist()}));
    $$('#rQuick button').forEach(b=>b.onclick=()=>{const [f,t]=quickRange(b.dataset.v);$('#rFrom').value=f;$('#rTo').value=t;$$('#rQuick button').forEach(x=>x.classList.toggle('on',x===b));renderRep()});
    ['#rFrom','#rTo'].forEach(s=>$(s).addEventListener('input',()=>{$$('#rQuick button').forEach(x=>x.classList.remove('on'));renderRep()}))},
  pages:{
    dashboard:['Dashboard','Tổng quan kho và cấp phát đồng phục',()=>renderDashboard()],
    canhbao:['Đến hạn & chưa hoàn trả','Nhân viên đến hạn cấp mới, nhân viên nghỉ việc chưa trả đồng phục',()=>renderCB()],
    nhap:['Nhập kho','Nhập đồng phục mua từ nhà cung cấp',()=>renderPhieu('nhap')],
    cap:['Cấp phát cho nhân viên','Cấp theo định mức vị trí, đúng size, in biên bản bàn giao',()=>renderPhieu('cap')],
    thu:['Thu hồi, đổi, báo hỏng','Thu hồi khi nghỉ việc, đổi size, hỏng, mất và khấu trừ',()=>renderPhieu('thu')],
    lichsu:['Lịch sử phiếu','Toàn bộ phiếu nhập, cấp, thu hồi',()=>renderHist()],
    tonkho:['Tồn kho theo size','Số lượng tồn từng loại đồng phục theo size',()=>renderTon()],
    dongphuc:['Danh mục đồng phục','Loại đồng phục, bộ phận, size, đơn giá, chu kỳ thay mới',()=>crudPage('dp','page-dongphuc')],
    nhanvien:['Nhân viên','Danh sách nhân viên theo bộ phận, size và đồng phục đang giữ',()=>crudPage('nv','page-nhanvien')],
    dinhmuc:['Định mức theo vị trí','Mỗi vị trí được cấp những gì, bao nhiêu',()=>crudPage('dm','page-dinhmuc')],
    baocao:['Báo cáo theo kỳ','Cấp phát, thu hồi, khấu trừ theo bộ phận và theo loại đồng phục',()=>renderRep()],
    settings:['Cài đặt & dữ liệu','Lưu đè, sao lưu, khôi phục, thông tin đơn vị',renderSettings],
    huongdan:['Hướng dẫn','Cách dùng file quản lý đồng phục',()=>{}]},
  sheets(){const m=stockMap(),H=holdMap();
    return [entSheet('dp',S.items),entSheet('nv',S.staff),entSheet('dm',S.norms),
      {name:'Tồn kho theo size',title:'TỒN KHO ĐỒNG PHỤC THEO SIZE',subtitles:['Đến ngày '+vnDate(TODAYS)],columns:[{label:'Mã',width:9},{label:'Đồng phục',width:30},{label:'Bộ phận',width:16},{label:'Size',type:'center',width:8},{label:'Tồn',type:'int',sum:1},{label:'Tối thiểu',type:'int'},{label:'Đơn giá',type:'money'},{label:'Giá trị tồn',type:'money',sum:1},{label:'Tình trạng',width:12}],
        rows:S.items.flatMap(it=>sizesOf(it).map(sz=>{const q=m[it.code+'|'+sz]||0;return [it.code,it.name,it.bp,sz,q,+it.min||0,+it.gia||0,Math.max(0,q)*(+it.gia||0),{ok:'Đủ',low:'Sắp hết',zero:'Hết'}[cellSt(q,+it.min||0)]]})),totals:1},
      histSheet(S.mv),
      {name:'Đến hạn cấp mới',title:'NHÂN VIÊN ĐẾN HẠN CẤP MỚI (30 NGÀY TỚI)',columns:[{label:'Mã NV',width:9},{label:'Họ tên',width:24},{label:'Bộ phận',width:16},{label:'Vị trí',width:20},{label:'Đồng phục',width:28},{label:'Size',type:'center',width:7},{label:'Định mức',type:'int'},{label:'Cấp lần cuối',type:'date'},{label:'Hạn cấp mới',type:'date'},{label:'Còn (ngày)',type:'int'}],
        rows:DUE.map(d=>[d.nv.code,d.nv.name,d.nv.bp,d.nv.vitri,d.it.name,nvSize(d.nv,d.it),d.n.sl,d.last||'',d.due,d.days])},
      {name:'Chưa hoàn trả',title:'NHÂN VIÊN NGHỈ VIỆC CHƯA HOÀN TRẢ ĐỒNG PHỤC',columns:[{label:'Mã NV',width:9},{label:'Họ tên',width:24},{label:'Bộ phận',width:16},{label:'Ngày nghỉ',type:'date'},{label:'Đồng phục còn giữ',width:50},{label:'Số cái',type:'int',sum:1},{label:'Giá trị',type:'money',sum:1}],
        rows:OWE.map(o=>[o.s.code,o.s.name,o.s.bp,o.s.ngaynghi,o.h.map(x=>(x.it?x.it.name:x.item)+' size '+x.size+' x'+x.q).join('; '),o.q,o.v]),totals:1}]}
});
function fullNorm(nv,H){const ns=S.norms.filter(n=>n.vitri===nv.vitri);if(!ns.length)return true;return ns.every(n=>sum(Object.entries(H).filter(([k])=>k.startsWith(nv.code+'|'+n.item+'|')),([,q])=>q)>=n.sl)}

/* ---------- danh mục ---------- */
ENTS.dp={coll:'items',name:'Danh mục đồng phục',one:'đồng phục',code:{k:'code',prefix:'DP',len:2},ph:'Tìm mã, tên đồng phục...',
  fields:[{k:'code',l:'Mã đồng phục',alias:['ma','madp']},{k:'name',l:'Tên đồng phục',req:1,alias:['ten','tendongphuc']},{k:'loai',l:'Loại',t:'sel',o:LOAIDP,def:'Áo'},
    {k:'bp',l:'Bộ phận dùng',o:BP,def:'Lễ tân',alias:['bophan']},{k:'kieu',l:'Kiểu size',t:'sel',o:Object.entries(KIEU),def:'ao',blank:false,noImp:1},
    {k:'sizes',l:'Các size có',def:'S,M,L,XL',hint:'Cách nhau dấu phẩy. VD: S,M,L,XL hoặc 36,37,38 hoặc Free'},{k:'dvt',l:'Đơn vị tính',def:'Cái'},{k:'gia',l:'Đơn giá (₫)',t:'money',alias:['dongia','gia']},
    {k:'chuky',l:'Chu kỳ thay mới (tháng)',t:'int',def:12},{k:'min',l:'Tồn tối thiểu mỗi size',t:'int',def:3},{k:'note',l:'Ghi chú',full:1}],
  cols:[{l:'Mã',v:i=>`<b>${esc(i.code)}</b>`,x:i=>i.code,w:9},{l:'Đồng phục',v:i=>`<div class="cell2"><b>${esc(i.name)}</b><small>${esc(i.loai)} · ${esc(KIEU[i.kieu]||'')}</small></div>`,x:i=>i.name,w:30},
    {l:'Bộ phận',v:i=>`<span class="tag cat">${esc(i.bp)}</span>`,x:i=>i.bp,w:16},{l:'Size',v:i=>`<span class="muted">${esc(sizesOf(i).join(', '))}</span>`,x:i=>sizesOf(i).join(', '),w:22},
    {l:'Đơn giá',n:1,v:i=>fmt(i.gia),x:i=>+i.gia||0,xt:'money'},{l:'Chu kỳ thay',c:1,v:i=>(i.chuky||'-')+' tháng',x:i=>+i.chuky||0,xt:'int'},
    {l:'Tồn kho',n:1,v:i=>{const m=stockMap(),q=sum(sizesOf(i),s=>m[i.code+'|'+s]||0),low=sizesOf(i).filter(s=>(m[i.code+'|'+s]||0)<=(+i.min||0)).length;return `<b>${fmt(q)}</b>${low?` <span class="tag low">${low} size sắp hết</span>`:''}`},x:i=>{const m=stockMap();return sum(sizesOf(i),s=>m[i.code+'|'+s]||0)},xt:'int',sum:1}],
  filters:[{k:'bp',l:'Mọi bộ phận',o:BP},{k:'loai',l:'Mọi loại',o:LOAIDP}],
  text:i=>i.code+' '+i.name+' '+i.bp,sort:(a,b)=>a.code.localeCompare(b.code),imp:1,impReq:'name',
  impSample:[{code:'DP16',name:'Áo khoác lễ tân mùa đông',loai:'Áo',bp:'Lễ tân',sizes:'S,M,L,XL',dvt:'Cái',gia:550000,chuky:24,min:2}],
  validate:o=>{o.sizes=sizesOf(o).join(',')||'Free';if(!o.kieu)o.kieu=/^\d/.test(o.sizes)?'giay':o.sizes==='Free'?'free':'ao'},
  canDel:i=>S.mv.some(v=>v.item===i.code)?'Đồng phục này đã có phiếu nhập, cấp, thu, không xóa được':'',
  onSave:(i,old)=>{if(!old&&!i.kieu)i.kieu='ao';if(!old&&/^\d/.test(String(i.sizes)))i.kieu='giay';if(!old&&String(i.sizes).trim()==='Free')i.kieu='free';
    if(old&&old.code!==i.code){S.mv.forEach(v=>{if(v.item===old.code)v.item=i.code});S.norms.forEach(n=>{if(n.item===old.code)n.item=i.code})}}};
const vitris=()=>[...new Set([...Object.keys(NORM0),...S.norms.map(n=>n.vitri),...S.staff.map(s=>s.vitri)].filter(Boolean))].sort((a,b)=>a.localeCompare(b,'vi'));
ENTS.nv={coll:'staff',name:'Nhân viên',one:'nhân viên',code:{k:'code',prefix:'NV',len:3},ph:'Tìm mã, họ tên, vị trí...',
  fields:[{k:'code',l:'Mã nhân viên',alias:['ma','manv']},{k:'name',l:'Họ và tên',req:1,alias:['hoten','ten','hovaten']},{k:'gt',l:'Giới tính',t:'sel',o:['Nữ','Nam'],def:'Nữ'},
    {k:'bp',l:'Bộ phận',t:'sel',o:BP,req:1,alias:['bophan']},{k:'vitri',l:'Vị trí',o:vitris,req:1,hint:'Định mức cấp phát lấy theo vị trí',alias:['vitri','chucvu']},{k:'ngayvao',l:'Ngày vào làm',t:'date',def:()=>TODAYS},
    {k:'tt',l:'Trạng thái',t:'sel',o:['Đang làm','Nghỉ việc'],def:'Đang làm',blank:false},{k:'ngaynghi',l:'Ngày nghỉ việc',t:'date'},
    {k:'sAo',l:'Size áo',o:['XS','S','M','L','XL','XXL','3XL']},{k:'sQuan',l:'Size quần, váy',o:['XS','S','M','L','XL','XXL','3XL']},{k:'sGiay',l:'Size giày',o:['35','36','37','38','39','40','41','42','43','44']},
    {k:'phone',l:'Điện thoại'},{k:'note',l:'Ghi chú',full:1}],
  cols:[{l:'Mã',v:s=>`<b>${esc(s.code)}</b>`,x:s=>s.code,w:9},{l:'Nhân viên',v:s=>`<div class="cell2"><b>${esc(s.name)}</b><small>${esc(s.vitri)}</small></div>`,x:s=>s.name,w:24},
    {l:'Bộ phận',v:s=>`<span class="tag cat">${esc(s.bp)}</span>`,x:s=>s.bp,w:16},{l:'Vị trí',v:s=>esc(s.vitri),x:s=>s.vitri,w:20},
    {l:'Size áo / quần / giày',c:1,v:s=>`${esc(s.sAo||'-')} / ${esc(s.sQuan||'-')} / ${esc(s.sGiay||'-')}`,x:s=>(s.sAo||'-')+' / '+(s.sQuan||'-')+' / '+(s.sGiay||'-'),w:16},
    {l:'Ngày vào',v:s=>vnDate(s.ngayvao),x:s=>s.ngayvao,xt:'date'},
    {l:'Trạng thái',v:s=>s.tt==='Nghỉ việc'?`<span class="tag gray">Nghỉ ${vnDate(s.ngaynghi)}</span>`:'<span class="tag ok">Đang làm</span>',x:s=>s.tt+(s.ngaynghi?' '+vnDate(s.ngaynghi):''),w:16},
    {l:'Đang giữ',n:1,v:s=>{const H=holdMap(),h=holdsOf(s.code,H),q=sum(h,x=>x.q);return q?`<b>${q}</b> cái${s.tt!=='Nghỉ việc'&&!fullNorm(s,H)?' <span class="tag low">Thiếu định mức</span>':''}${s.tt==='Nghỉ việc'?' <span class="tag zero">Chưa trả</span>':''}`:(s.tt==='Nghỉ việc'?'<span class="tag ok">Đã trả đủ</span>':'<span class="tag zero">Chưa cấp</span>')},x:s=>sum(holdsOf(s.code),x=>x.q),xt:'int'}],
  filters:[{k:'bp',l:'Mọi bộ phận',o:BP},{k:'tt',l:'Mọi trạng thái',o:['Đang làm','Nghỉ việc']},{k:'vitri',l:'Mọi vị trí',o:vitris}],
  actions:s=>s.tt==='Nghỉ việc'?`<button class="btn sm" onclick="startPhieu('thu','${s.code}')">Thu hồi</button> `:`<button class="btn sm" onclick="startPhieu('cap','${s.code}')">Cấp</button> `,
  text:s=>s.code+' '+s.name+' '+s.vitri+' '+s.bp,sort:(a,b)=>a.code.localeCompare(b.code),imp:1,impReq:'name',
  impSample:[{code:'NV101',name:'Nguyễn Văn An',gt:'Nam',bp:'Lễ tân',vitri:'Lễ tân',ngayvao:'2026-01-05',tt:'Đang làm',sAo:'M',sQuan:'M',sGiay:'41'}],
  validate:o=>{if(o.tt==='Nghỉ việc'&&!o.ngaynghi)o.ngaynghi=TODAYS;if(o.tt!=='Nghỉ việc')o.ngaynghi=''},
  canDel:s=>S.mv.some(v=>v.nv===s.code)?'Nhân viên đã có phiếu cấp, thu đồng phục. Chuyển trạng thái sang Nghỉ việc thay vì xóa.':'',
  onSave:(s,old)=>{if(old&&old.code!==s.code)S.mv.forEach(v=>{if(v.nv===old.code)v.nv=s.code})}};
ENTS.dm={coll:'norms',name:'Định mức cấp phát',one:'định mức',ph:'Tìm vị trí, đồng phục...',
  fields:[{k:'vitri',l:'Vị trí',o:vitris,req:1},{k:'item',l:'Đồng phục',t:'sel',o:()=>S.items.map(i=>[i.code,i.code+' - '+i.name]),req:1,alias:['madp','madongphuc']},{k:'sl',l:'Số lượng mỗi người',t:'int',def:1,req:1,alias:['soluong','sl']}],
  cols:[{l:'Vị trí',v:n=>`<b>${esc(n.vitri)}</b>`,x:n=>n.vitri,w:22},{l:'Đồng phục',v:n=>{const i=itemOf(n.item);return i?`<div class="cell2"><b>${esc(i.name)}</b><small>${esc(i.code)} · thay mỗi ${i.chuky} tháng</small></div>`:esc(n.item)},x:n=>{const i=itemOf(n.item);return i?i.name:n.item},w:30},
    {l:'Số lượng / người',c:1,v:n=>`<b>${n.sl}</b>`,x:n=>+n.sl,xt:'int'},{l:'Số người đang làm',c:1,v:n=>active().filter(s=>s.vitri===n.vitri).length,x:n=>active().filter(s=>s.vitri===n.vitri).length,xt:'int'},
    {l:'Giá trị định mức / người',n:1,v:n=>{const i=itemOf(n.item);return fmt(i?n.sl*i.gia:0)},x:n=>{const i=itemOf(n.item);return i?n.sl*i.gia:0},xt:'money'}],
  filters:[{k:'vitri',l:'Mọi vị trí',o:vitris}],text:n=>n.vitri+' '+n.item+' '+((itemOf(n.item)||{}).name||''),sort:(a,b)=>a.vitri.localeCompare(b.vitri,'vi')||a.item.localeCompare(b.item),
  label:n=>n.vitri+' - '+n.item,imp:1,impReq:'vitri',impSample:[{vitri:'Lễ tân',item:'DP03',sl:3}],
  validate:(o,r)=>{o.sl=Math.max(1,Math.round(+o.sl||1));if(!itemOf(o.item))return 'Chọn đồng phục';if(S.norms.some(n=>n.vitri===o.vitri&&n.item===o.item&&(!r||n.id!==r.id)))return 'Vị trí này đã có định mức cho đồng phục đó, sửa dòng cũ'}};

/* =========================================================
   DASHBOARD
   ========================================================= */
let PERIOD='month';
function sumMv(from,to){const o={nhapV:0,capV:0,capQ:0,thuQ:0,kt:0,nCap:0};const d=new Set();
  S.mv.forEach(v=>{if(v.date<from||v.date>to)return;const val=v.sl*(+v.gia||0);if(v.type==='nhap')o.nhapV+=val;else if(v.type==='cap'){o.capV+=val;o.capQ+=v.sl;d.add(v.doc)}else{o.thuQ+=v.sl;o.kt+=+v.kt||0}});o.nCap=d.size;return o}
function series(){const months=monthsBack(12),ix={};months.forEach((k,i)=>ix[k]=i);const z=()=>months.map(()=>0),o={months,labels:months.map(mLabel),nhap:z(),cap:z(),capQ:z(),val:z()};
  S.mv.forEach(v=>{const i=ix[v.date.slice(0,7)];if(i===undefined)return;const val=v.sl*(+v.gia||0);if(v.type==='nhap')o.nhap[i]+=val;else if(v.type==='cap'){o.cap[i]+=val;o.capQ[i]+=v.sl}});
  months.forEach((k,i)=>{const m=stockMap(ymd(new Date(+k.slice(0,4),+k.slice(5),0)));o.val[i]=sum(Object.entries(m),([kk,q])=>{const it=itemOf(kk.split('|')[0]);return Math.max(0,q)*(it?+it.gia:0)})});return o}
function heatHtml(list){const m=stockMap();
  return list.map(it=>{const sz=sizesOf(it),tq=sum(sz,s=>m[it.code+'|'+s]||0);
    return `<div class="hm-row"><div><b>${esc(it.name)}</b><small>${esc(it.code)} · ${esc(it.bp)}</small></div><div class="chips">${sz.map(s=>{const q=m[it.code+'|'+s]||0,st=cellSt(q,+it.min||0);return `<span class="sz ${st}" data-tip="${esc(it.name+' size '+s+': tồn '+q+', tối thiểu '+(it.min||0))}">${esc(s)}<em>${fmt(q)}</em></span>`}).join('')}</div><div class="hm-v">${fmt(tq)} ${esc(it.dvt||'cái')}<small class="muted">${short(Math.max(0,tq)*(+it.gia||0))} ₫</small></div></div>`}).join('')||'<div class="empty">Chưa có đồng phục trong danh mục</div>'}
function renderDashboard(){
  const r=rangeOf(PERIOD);$('#dRange').textContent=r.label;
  const c=sumMv(r.from,r.to),p=r.pfrom?sumMv(r.pfrom,r.pto):null,ser=series(),val=stockVal(),m=stockMap(),tq=sum(Object.values(m),q=>Math.max(0,q));
  const A=active(),H=holdMap(),okN=A.filter(nv=>fullNorm(nv,H)).length,pct=A.length?okN/A.length*100:0;
  $('#dHero').innerHTML=`<div><div class="h-k">GIÁ TRỊ ĐỒNG PHỤC TỒN KHO</div>
      <div class="h-v"><span data-count="${Math.round(val)}">0</span> <small>₫</small></div>
      <div class="h-sub">${pill(val,ser.val[10],'so với cuối tháng trước')}<span>so với cuối tháng trước (${short(ser.val[10])} ₫)</span></div>
      <div class="h-stats"><div><b data-count="${tq}">0</b><span>Cái đang tồn</span></div><div><b data-count="${S.items.length}">0</b><span>Loại đồng phục</span></div><div><b data-count="${A.length}">0</b><span>Nhân viên đang làm</span></div></div>
      <div class="h-act"><button class="hbtn" onclick="go('cap')">+ Cấp phát</button><button class="hbtn ghost" onclick="go('nhap')">Nhập kho</button></div></div>
    <div class="h-chart"><div class="h-ct"><span>Giá trị tồn kho cuối mỗi tháng</span><span>Cao nhất <b>${short(Math.max(...ser.val))} ₫</b></span></div>${heroArea(ser.val,ser.labels,(i,v)=>'Tồn cuối '+ser.labels[i]+': '+money(v))}</div>
    <div class="h-ring">${ringSvg(pct,'đủ định mức')}<b>${okN}/${A.length} nhân viên</b><span>${DUE.filter(x=>x.days<=0).length} quá hạn thay · ${OWE.length} chưa trả</span></div>`;
  $('#kpis').innerHTML=
    kc({c:CD.sang,cb:CD.nhat2,icon:ICO.down,label:'Chi phí mua',value:c.nhapV,unit:'₫',pill:pill(c.nhapV,p?p.nhapV:undefined,r.cmp,1),sub:'Giá trị đồng phục nhập trong kỳ',data:ser.nhap})+
    kc({c:CD.main,cb:CD.nhat,icon:ICO.user,label:'Giá trị cấp',value:c.capV,unit:'₫',pill:pill(c.capV,p?p.capV:undefined,r.cmp,1),sub:`${c.nCap} phiếu cấp cho nhân viên`,data:ser.cap})+
    kc({c:'#f0663a',cb:'var(--outbg)',icon:ICO.box,label:'Số cái cấp phát',value:c.capQ,unit:'cái',pill:pill(c.capQ,p?p.capQ:undefined,r.cmp,1),sub:`Thu hồi ${fmt(c.thuQ)} cái · khấu trừ ${short(c.kt)} ₫`,data:ser.capQ})+
    kc({c:'#e0384a',cb:'var(--dangerbg)',icon:ICO.clock,label:'Đến hạn thay',value:DUE.length,unit:'lượt',pill:`<span class="pill ${DUE.filter(x=>x.days<=0).length?'pd':'pu'}">${DUE.filter(x=>x.days<=0).length} quá hạn</span>`,sub:'Trong 30 ngày tới, theo chu kỳ thay'});
  const sm=a=>a.reduce((s,v)=>s+v,0);
  $('#mSum').innerHTML=`<div><small>Nhập 12 tháng</small><b style="color:${CD.dam}">${short(sm(ser.nhap))} ₫</b></div><div><small>Cấp phát 12 tháng</small><b style="color:var(--chu-dao)">${short(sm(ser.cap))} ₫</b></div><div><small>Số cái đã cấp</small><b style="color:#f0663a">${fmt(sm(ser.capQ))} cái</b></div>`;
  const mxV=Math.max(1,...ser.nhap,...ser.cap),mxQ=Math.max(1,...ser.capQ);
  $('#chMonth').innerHTML=comboChart({labels:ser.labels,bars:[{name:'Nhập kho',data:ser.nhap,color:CD.sang,c2:CD.nhat},{name:'Cấp phát',data:ser.cap,color:CD.main,c2:CD.sang}],lines:[{name:'Số cái cấp (quy đổi)',data:ser.capQ.map(q=>q/mxQ*mxV*0.9),color:'#f0663a'}],f:v=>money(v)});
  $$('#chMonth circle.seg').forEach((el,i)=>el.dataset.tip='Số cái cấp '+ser.labels[i]+': '+fmt(ser.capQ[i])+' cái');
  $('#donutHint').textContent=r.label;
  const byBp={};S.mv.forEach(v=>{if(v.type!=='cap'||v.date<r.from||v.date>r.to)return;const nv=nvOf(v.nv),b=nv?nv.bp:'Khác';byBp[b]=(byBp[b]||0)+v.sl*(+v.gia||0)});
  $('#chBp').innerHTML=donut(Object.entries(byBp).sort((a,b)=>b[1]-a[1]).map(([l,v],i)=>({label:l,value:v,color:PAL[i%PAL.length]})),{center:'Giá trị cấp',empty:'Chưa có cấp phát trong kỳ'});
  $('#heat').innerHTML=heatHtml([...S.items].sort((a,b)=>BP.indexOf(a.bp)-BP.indexOf(b.bp)||a.code.localeCompare(b.code)));
  $('#dueCnt').textContent=DUE.length?DUE.length+' lượt':'';
  $('#dueList').innerHTML=DUE.length?DUE.slice(0,6).map(d=>`<div class="li click" onclick="startPhieu('cap','${d.nv.code}','Cấp thay định kỳ')"><span class="tico ${d.days<=0?'bad':'warn'}">${ICO.clock}</span><div class="meta"><b>${esc(d.nv.name)}</b><small>${esc(d.it.name)} · size ${esc(nvSize(d.nv,d.it)||'?')} x${d.n.sl}</small></div><div class="v" style="color:${d.days<=0?'var(--danger)':'#a36d00'}">${d.never?'Chưa cấp':d.days<=0?'Quá '+(-d.days)+' ngày':'Còn '+d.days+' ngày'}<small>hạn ${vnDate(d.due)}</small></div></div>`).join(''):'<div class="empty">Không có ai đến hạn trong 30 ngày tới</div>';
  $('#owCnt').textContent=OWE.length?OWE.length+' người':'';
  $('#owList').innerHTML=OWE.length?OWE.map(o=>`<div class="li click" onclick="startPhieu('thu','${o.s.code}','Nghỉ việc')"><span class="tico bad">${ICO.user}</span><div class="meta"><b>${esc(o.s.name)}</b><small>${esc(o.s.bp)} · nghỉ ${vnDate(o.s.ngaynghi)} · ${o.q} cái</small></div><div class="v" style="color:var(--danger)">${short(o.v)} ₫<small>chưa trả</small></div></div>`).join('')+`<p class="muted" style="font-size:12px;margin-top:8px">Bấm vào tên để lập phiếu thu hồi.</p>`:`<div class="empty">${ICO.ok.replace('<svg','<svg style="width:34px;height:34px;color:var(--in)"')}<br>Nhân viên nghỉ việc đã hoàn trả đủ</div>`;
  const rec=docsOf().slice(0,6),IC3={nhap:ICO.down,cap:ICO.user,thu:ICO.up};
  $('#recent').innerHTML=rec.length?rec.map(g=>{const nv=nvOf(g.nv);return `<div class="li click" onclick="printDoc('${g.doc}')" title="Bấm để in phiếu"><span class="tico ${g.type==='nhap'?'in':g.type==='thu'?'out':''}">${IC3[g.type]}</span><div class="meta"><b>${esc(g.type==='nhap'?g.ncc:(nv?nv.name:g.nv))}</b><small>${g.doc} · ${vnDate(g.date)} · ${esc(g.lydo||TYPES[g.type].name)}</small></div><div class="v">${fmt(g.q)} cái<small>${short(g.val)} ₫</small></div></div>`}).join(''):'<div class="empty">Chưa có phiếu</div>';
  countUp($('#page-dashboard'))}

/* =========================================================
   ĐẾN HẠN & CHƯA HOÀN TRẢ
   ========================================================= */
function renderCB(){
  const el=$('#page-canhbao'),over=DUE.filter(d=>d.days<=0),soon=DUE.filter(d=>d.days>0);
  el.innerHTML=`<div class="kc-grid">${[['Quá hạn cấp mới',over.length,'lượt','#e0384a','var(--dangerbg)',ICO.warn],['Đến hạn trong 30 ngày',soon.length,'lượt','#e6a100','var(--warnbg)',ICO.clock],['Nghỉ việc chưa trả',OWE.length,'người','#f0663a','var(--outbg)',ICO.user],['Giá trị chưa thu hồi',sum(OWE,o=>o.v),'₫',CD.main,CD.nhat,ICO.money]].map(([l,v,u,c,cb,ic])=>kc({c,cb,icon:ic,label:l,value:v,unit:u})).join('')}</div>
  <div class="card" style="margin-bottom:18px"><div class="card-h"><h3>Nhân viên đến hạn cấp mới (theo chu kỳ thay của từng loại)</h3><div class="r"><button class="btn sm" onclick="XLSX.save('Den han cap moi',[APP.sheets()[5]])">${ICO.xl}Xuất Excel</button></div></div><div class="tbl-wrap">${DUE.length?`<table><thead><tr><th>Nhân viên</th><th>Bộ phận</th><th>Đồng phục</th><th class="c">Size</th><th class="c">Định mức</th><th>Cấp lần cuối</th><th>Hạn cấp mới</th><th>Tình trạng</th><th></th></tr></thead><tbody>${DUE.map(d=>`<tr><td><div class="cell2"><b>${esc(d.nv.name)}</b><small>${esc(d.nv.code)} · ${esc(d.nv.vitri)}</small></div></td><td>${esc(d.nv.bp)}</td><td>${esc(d.it.name)}</td><td class="c">${esc(nvSize(d.nv,d.it)||'?')}</td><td class="c">${d.n.sl}</td><td>${d.last?vnDate(d.last):'<span class="muted">Chưa cấp</span>'}</td><td>${vnDate(d.due)}</td><td>${d.days<=0?`<span class="tag zero">${d.never?'Chưa cấp':'Quá '+(-d.days)+' ngày'}</span>`:`<span class="tag low">Còn ${d.days} ngày</span>`}</td><td class="acts"><button class="btn sm pri" onclick="startPhieu('cap','${d.nv.code}','Cấp thay định kỳ')">Lập phiếu cấp</button></td></tr>`).join('')}</tbody></table>`:'<div class="empty">Không có nhân viên nào đến hạn trong 30 ngày tới</div>'}</div></div>
  <div class="card"><div class="card-h"><h3>Nhân viên nghỉ việc chưa hoàn trả đồng phục</h3></div><div class="tbl-wrap">${OWE.length?`<table><thead><tr><th>Nhân viên</th><th>Bộ phận</th><th>Ngày nghỉ</th><th>Đồng phục còn giữ</th><th class="n">Số cái</th><th class="n">Giá trị</th><th></th></tr></thead><tbody>${OWE.map(o=>`<tr><td><div class="cell2"><b>${esc(o.s.name)}</b><small>${esc(o.s.code)} · ${esc(o.s.phone||'')}</small></div></td><td>${esc(o.s.bp)}</td><td>${vnDate(o.s.ngaynghi)}</td><td>${o.h.map(x=>`<span class="tag gray" style="margin:2px">${esc(x.it?x.it.name:x.item)} · ${esc(x.size)} x${x.q}</span>`).join(' ')}</td><td class="n"><b>${o.q}</b></td><td class="n"><b>${fmt(o.v)}</b></td><td class="acts"><button class="btn sm pri" onclick="startPhieu('thu','${o.s.code}','Nghỉ việc')">Thu hồi</button></td></tr>`).join('')}</tbody></table>`:'<div class="empty">Không có nhân viên nghỉ việc nào còn giữ đồng phục</div>'}</div></div>`;
  countUp(el)}

/* =========================================================
   PHIẾU NHẬP / CẤP / THU HỒI (nhiều dòng)
   ========================================================= */
const DRAFT={};
const draft=t=>DRAFT[t]=DRAFT[t]||{date:TODAYS,nv:'',ncc:'',lydo:TYPES[t].reasons?TYPES[t].reasons[0]:'',note:'',lines:[]};
const nl=()=>({k:uid(),item:'',size:'',sl:1,gia:'',tt:'Dùng lại được',kt:0});
function startPhieu(t,nv,lydo){DRAFT[t]=null;const d=draft(t);d.nv=nv||'';if(lydo)d.lydo=lydo;
  const s=nvOf(nv);if(t==='thu'&&s&&s.tt==='Nghỉ việc')d.lydo='Nghỉ việc';go(t);
  if(t==='cap'&&s)fillNorm(lydo==='Cấp thay định kỳ');if(t==='thu'&&s&&d.lydo==='Nghỉ việc')fillHold()}
const itemOpts=sel=>'<option value="">Chọn đồng phục</option>'+BP.concat(['Khác']).map(b=>{const L=S.items.filter(i=>(BP.includes(i.bp)?i.bp:'Khác')===b);return L.length?`<optgroup label="${esc(b)}">${L.map(i=>`<option value="${i.code}" ${i.code===sel?'selected':''}>${esc(i.code+' - '+i.name)}</option>`).join('')}</optgroup>`:''}).join('');
function renderPhieu(t){
  const T=TYPES[t],d=draft(t),el=$('#page-'+t),nv=nvOf(d.nv);
  const staffOpts=(t==='thu'?S.staff:active()).slice().sort((a,b)=>BP.indexOf(a.bp)-BP.indexOf(b.bp)||a.name.localeCompare(b.name,'vi'));
  const nccs=[...new Set(NCC0.concat(S.mv.map(v=>v.ncc).filter(Boolean)))];
  el.innerHTML=`<div class="txl"><div class="card" style="border-top:4px solid ${t==='nhap'?'var(--in)':t==='thu'?'var(--out)':'var(--chu-dao)'}">
    <div class="card-h"><h3>${T.t}</h3><span class="tag ${T.tag}">Số ${nextDoc(t)}</span></div>
    <div class="card-b"><div class="hgrid">
      <div class="fg"><label class="f">Ngày ${T.verb} *</label><input class="inp" type="date" id="pDate" value="${d.date}"></div>
      ${t==='nhap'?`<div class="fg"><label class="f">Nhà cung cấp</label><input class="inp" id="pNcc" list="dlNcc" value="${esc(d.ncc)}" placeholder="Gõ tên hoặc chọn"><datalist id="dlNcc">${nccs.map(x=>`<option value="${esc(x)}">`).join('')}</datalist></div>`
       :`<div class="fg"><label class="f">Nhân viên *</label><select class="inp" id="pNv"><option value="">Chọn nhân viên</option>${BP.map(b=>{const L=staffOpts.filter(s=>s.bp===b);return L.length?`<optgroup label="${esc(b)}">${L.map(s=>`<option value="${s.code}" ${s.code===d.nv?'selected':''}>${esc(s.name)} (${esc(s.code)}${s.tt==='Nghỉ việc'?', đã nghỉ':''})</option>`).join('')}</optgroup>`:''}).join('')}</select></div>
         <div class="fg"><label class="f">${t==='cap'?'Loại cấp phát':'Lý do thu hồi'}</label><select class="inp" id="pLydo">${T.reasons.map(x=>`<option ${x===d.lydo?'selected':''}>${x}</option>`).join('')}</select></div>`}
      ${t==='nhap'?`<div class="fg"><label class="f">Ghi chú</label><input class="inp" id="pNote" value="${esc(d.note)}"></div>`:`<div class="fg full" style="grid-column:1/-1"><label class="f">Ghi chú</label><input class="inp" id="pNote" value="${esc(d.note)}" placeholder="${t==='thu'?'VD: rách tay áo, làm mất khi giặt ủi...':'VD: nhân viên mới ca sáng'}"></div>`}
    </div>${nv?nvCard(nv):''}</div>
    <div class="sec"><div class="sec-t">Đồng phục trong phiếu <span class="cnt" id="pCnt">0</span>
      <span style="margin-left:auto;display:flex;gap:6px;text-transform:none;letter-spacing:0">${t==='cap'?`<button class="btn sm" onclick="fillNorm(false)">Điền theo định mức</button><button class="btn sm" onclick="fillNorm(true)">Chỉ món đến hạn thay</button>`:''}${t==='thu'?`<button class="btn sm" onclick="fillHold()">Thu hồi tất cả đang giữ</button>`:''}${t==='nhap'?`<button class="btn sm" onclick="fillLow()">${ICO.warn}Thêm các size sắp hết</button>`:''}</span></div>
      <div class="tbl-wrap"><table class="lines"><thead><tr><th class="c" style="width:34px">#</th><th>Đồng phục</th><th style="width:90px">Size</th>${t==='cap'?'<th class="n" style="width:70px">Tồn kho</th>':t==='thu'?'<th class="n" style="width:70px">Đang giữ</th>':''}<th class="n" style="width:84px">Số lượng</th>${t==='thu'?'<th style="width:140px">Tình trạng</th><th class="n" style="width:120px">Khấu trừ (₫)</th>':'<th class="n" style="width:120px">Đơn giá</th><th class="n" style="width:120px">Thành tiền</th>'}<th style="width:34px"></th></tr></thead><tbody id="pLines"></tbody></table></div>
      <div class="addl"><button class="btn sm" onclick="draft('${t}').lines.push(nl());drawP('${t}')">${ICO.plus}Thêm dòng</button>${t==='cap'?'<span class="muted" style="font-size:12px;align-self:center">Cấp thay định kỳ: nhớ lập phiếu thu hồi đồ cũ (lý do "Thu đồ cũ khi thay mới").</span>':''}</div></div></div>
    <div class="side"><div class="card sum" style="border-top:4px solid var(--chu-dao)">
      <small class="lb">${t==='thu'?'Tổng tiền khấu trừ':'Tổng giá trị phiếu'}</small><div class="big" id="pBig">0 ₫</div>
      <div class="kv2"><span>Số dòng</span><b id="pN">0</b></div><div class="kv2"><span>Tổng số lượng</span><b id="pQ">0</b></div>${t==='thu'?'<div class="kv2"><span>Nhập lại kho (dùng lại được)</span><b id="pRe">0</b></div>':''}
      <div class="words" id="pW"></div><div id="pOk"></div>
      <div class="acts"><button class="btn pri" onclick="saveP('${t}')">${T.btn}</button><button class="btn" onclick="saveP('${t}',true)">${ICO.print}Lưu và in ${t==='nhap'?'phiếu':'biên bản'}</button><button class="lnk" onclick="DRAFT['${t}']=null;renderPhieu('${t}')">Làm lại phiếu</button></div></div></div></div>
  <div class="card"><div class="card-h"><h3>Phiếu ${T.verb} gần đây</h3><div class="r"><button class="btn sm" onclick="$('#hType').value='${t}';go('lichsu')">Xem tất cả</button></div></div><div id="pRecent"></div></div>`;
  $('#pDate').oninput=e=>d.date=e.target.value;$('#pNote').oninput=e=>d.note=e.target.value;
  if($('#pNcc'))$('#pNcc').oninput=e=>d.ncc=e.target.value;
  if($('#pLydo'))$('#pLydo').onchange=e=>{d.lydo=e.target.value;if(t==='thu')d.lines.forEach(l=>{l.tt=d.lydo==='Báo mất'?'Mất':d.lydo==='Báo hỏng'||d.lydo==='Thu đồ cũ khi thay mới'?'Hủy bỏ':l.tt});drawP(t)};
  if($('#pNv'))$('#pNv').onchange=e=>{d.nv=e.target.value;const s=nvOf(d.nv);d.lines.forEach(l=>{const it=itemOf(l.item);if(it&&s)l.size=nvSize(s,it)||l.size});renderPhieu(t)};
  if(!d.lines.length)d.lines.push(nl());drawP(t);
  const list=docsOf(t);$('#pRecent').innerHTML=list.length?`<div class="tbl-wrap"><table><thead><tr><th>Số phiếu</th><th>Ngày</th><th>${t==='nhap'?'Nhà cung cấp':'Nhân viên'}</th>${t==='nhap'?'':'<th>Lý do</th>'}<th>Đồng phục</th><th class="n">Số lượng</th><th class="n">${t==='thu'?'Khấu trừ':'Giá trị'}</th><th></th></tr></thead><tbody>${pgSlice('rec'+t,list,10).map(g=>docRow(g,t)).join('')}</tbody></table></div>${pgBar('rec'+t,list.length,'renderRecentP:'+t)}`:'<div class="empty">Chưa có phiếu nào</div>'}
function renderRecentP(t){renderPhieu(t)}
function docRow(g,t){const nv=nvOf(g.nv),names=g.lines.map(v=>{const it=itemOf(v.item);return (it?it.name:v.item)+' '+v.size});
  return `<tr><td><b>${g.doc}</b></td><td class="nw">${vnDate(g.date)}</td><td>${esc(g.type==='nhap'?g.ncc:(nv?nv.name:g.nv))}</td>${t==='nhap'?'':`<td>${esc(g.lydo)}</td>`}<td><div class="muted" style="font-size:12px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(names.join(', '))}">${esc(names.slice(0,3).join(', '))}${names.length>3?' và '+(names.length-3)+' món khác':''}</div></td><td class="n">${fmt(g.q)}</td><td class="n"><b>${fmt(g.type==='thu'?g.kt:g.val)}</b></td><td class="acts"><button class="btn sm ico" title="In" onclick="printDoc('${g.doc}')">${ICO.print}</button> <button class="btn sm ico danger" title="Xóa phiếu" onclick="delDoc('${g.doc}')">${ICO.del}</button></td></tr>`}
function nvCard(s){const h=holdsOf(s.code),ns=S.norms.filter(n=>n.vitri===s.vitri);
  return `<div class="nvcard"><div class="av">${esc(s.name.split(' ').pop().charAt(0))}</div><div style="flex:1;min-width:200px"><b>${esc(s.name)}</b> <span class="muted">· ${esc(s.code)} · ${esc(s.bp)} · ${esc(s.vitri)}${s.tt==='Nghỉ việc'?' · <b style="color:var(--danger)">đã nghỉ '+vnDate(s.ngaynghi)+'</b>':''}</span>
    <div class="holds">${h.length?h.map(x=>`<span>${esc(x.it?x.it.name:x.item)} · ${esc(x.size)} x${x.q}</span>`).join(''):'<span>Chưa giữ đồng phục nào</span>'}</div></div>
    <div class="kv"><div>Size áo<b>${esc(s.sAo||'-')}</b></div><div>Quần, váy<b>${esc(s.sQuan||'-')}</b></div><div>Giày<b>${esc(s.sGiay||'-')}</b></div><div>Định mức<b>${ns.length} món</b></div></div></div>`}
function drawP(t){
  const d=draft(t),tb=$('#pLines');if(!tb)return;const m=stockMap(),H=holdMap();
  tb.innerHTML=d.lines.map((l,i)=>{const it=itemOf(l.item),szs=sizesOf(it),st=m[l.item+'|'+l.size]||0,hd=H[d.nv+'|'+l.item+'|'+l.size]||0,price=l.gia===''?(it?+it.gia:0):+l.gia;
    return `<tr data-k="${l.k}"><td class="c muted">${i+1}</td><td><select class="cin sel" data-f="item">${itemOpts(l.item)}</select></td>
    <td><select class="cin sel" data-f="size">${szs.length?szs.map(s=>`<option ${s===l.size?'selected':''}>${esc(s)}</option>`).join(''):'<option></option>'}</select></td>
    ${t==='cap'?`<td class="n" style="font-weight:700;color:${st<=0?'var(--danger)':st<(+l.sl||0)?'#a36d00':'inherit'}">${l.item?fmt(st):''}</td>`:t==='thu'?`<td class="n" style="font-weight:700">${l.item?fmt(hd):''}</td>`:''}
    <td><input class="cin r" type="number" min="0" data-f="sl" value="${l.sl}"></td>
    ${t==='thu'?`<td><select class="cin sel" data-f="tt">${TT.map(x=>`<option ${x===l.tt?'selected':''}>${x}</option>`).join('')}</select></td><td><input class="cin r" inputmode="numeric" data-f="kt" value="${l.kt?fmt(l.kt):''}" placeholder="0"></td>`
     :`<td><input class="cin r" inputmode="numeric" data-f="gia" value="${it||l.gia!==''?fmt(price):''}"></td><td class="amt" data-amt>${fmt((+l.sl||0)*price)}</td>`}
    <td class="c"><button class="xbtn" title="Bỏ dòng" onclick="draft('${t}').lines=draft('${t}').lines.filter(x=>x.k!=='${l.k}');drawP('${t}')">✕</button></td></tr>`}).join('');
  tb.querySelectorAll('tr').forEach(tr=>{const l=d.lines.find(x=>x.k===tr.dataset.k);
    tr.querySelectorAll('[data-f]').forEach(inp=>{const f=inp.dataset.f;
      inp.onchange=inp.oninput=()=>{if(f==='item'){l.item=inp.value;const it=itemOf(l.item),s=nvOf(d.nv);l.size=(s&&nvSize(s,it))||sizesOf(it)[0]||'';l.gia='';drawP(t);return}
        if(f==='size'){l.size=inp.value;drawP(t);return}
        if(f==='gia'||f==='kt')l[f]=toNum(inp.value);else if(f==='sl')l.sl=Math.max(0,Math.round(+inp.value||0));else l[f]=inp.value;
        const it=itemOf(l.item),price=l.gia===''?(it?+it.gia:0):+l.gia,a=tr.querySelector('[data-amt]');if(a)a.textContent=fmt((+l.sl||0)*price);totP(t)};
      if(f==='gia'||f==='kt')inp.onblur=()=>{inp.value=l[f]?fmt(l[f]):''}})});
  totP(t)}
function lineIssues(t){const d=draft(t),m=stockMap(),H=holdMap(),need={},errs=[];
  const ls=d.lines.filter(l=>l.item);ls.forEach(l=>{const k=l.item+'|'+l.size;need[k]=(need[k]||0)+(+l.sl||0)});
  ls.forEach((l,i)=>{const it=itemOf(l.item),k=l.item+'|'+l.size;if(!l.size)errs.push(`${it.name}: chưa chọn size`);if(!(+l.sl>0))errs.push(`${it.name}: số lượng phải lớn hơn 0`);
    if(t==='cap'&&need[k]>(m[k]||0))errs.push(`Không đủ hàng: ${it.name} size ${l.size} còn ${m[k]||0}, cần ${need[k]}`);
    if(t==='thu'&&need[k]>(H[d.nv+'|'+k]||0))errs.push(`${it.name} size ${l.size}: nhân viên chỉ đang giữ ${H[d.nv+'|'+k]||0}`)});
  return {ls,errs:[...new Set(errs)]}}
function totP(t){const d=draft(t),{ls,errs}=lineIssues(t),q=sum(ls,l=>l.sl),v=sum(ls,l=>{const it=itemOf(l.item);return (+l.sl||0)*(l.gia===''?+it.gia:+l.gia)}),kt=sum(ls,l=>l.kt);
  $('#pCnt').textContent=ls.length;$('#pN').textContent=ls.length;$('#pQ').textContent=fmt(q);$('#pBig').textContent=money(t==='thu'?kt:v);
  if($('#pRe'))$('#pRe').textContent=fmt(sum(ls.filter(l=>l.tt==='Dùng lại được'),l=>l.sl))+' cái';
  $('#pW').innerHTML=(t==='thu'?kt:v)?'Bằng chữ: <b>'+docSo(t==='thu'?kt:v)+'</b>':'';
  $('#pOk').innerHTML=!ls.length?'':errs.length?`<div class="okbox bad">${ICO.warn}<span>${esc(errs[0])}${errs.length>1?' (và '+(errs.length-1)+' lỗi khác)':''}</span></div>`:`<div class="okbox ok">${ICO.ok}Phiếu hợp lệ, sẵn sàng lưu</div>`}
function fillNorm(onlyDue){const d=draft('cap'),s=nvOf(d.nv);if(!s)return toast('Chọn nhân viên trước','err');
  const ns=S.norms.filter(n=>n.vitri===s.vitri);if(!ns.length)return toast('Vị trí "'+s.vitri+'" chưa có định mức. Khai báo ở trang Định mức theo vị trí.','err');
  const H=holdMap(),L=lastIssue(),add=[];
  ns.forEach(n=>{const it=itemOf(n.item);if(!it)return;const held=sum(Object.entries(H).filter(([k])=>k.startsWith(s.code+'|'+it.code+'|')),([,q])=>q),last=L[s.code+'|'+it.code];
    let q=onlyDue?(last&&diffDays(TODAYS,addMonths(last,+it.chuky||12))<=30?n.sl:(!last?n.sl:0)):Math.max(0,n.sl-held);if(q>0)add.push({k:uid(),item:it.code,size:nvSize(s,it)||sizesOf(it)[0],sl:q,gia:'',tt:'',kt:0})});
  d.lines=d.lines.filter(l=>l.item).concat(add);if(onlyDue)d.lydo='Cấp thay định kỳ';else if(!S.mv.some(v=>v.type==='cap'&&v.nv===s.code))d.lydo=TYPES.cap.reasons[0];
  renderPhieu('cap');toast(add.length?'Đã thêm '+add.length+' món theo '+(onlyDue?'hạn thay':'định mức')+', đúng size của '+s.name:'Nhân viên này đã đủ '+(onlyDue?'(chưa món nào đến hạn)':'định mức'),add.length?'':'err')}
function fillHold(){const d=draft('thu'),s=nvOf(d.nv);if(!s)return toast('Chọn nhân viên trước','err');const h=holdsOf(s.code);if(!h.length)return toast(s.name+' không còn giữ đồng phục nào','err');
  d.lines=h.map(x=>({k:uid(),item:x.item,size:x.size,sl:x.q,gia:'',tt:d.lydo==='Báo mất'?'Mất':'Dùng lại được',kt:0}));renderPhieu('thu')}
function fillLow(){const d=draft('nhap'),add=LOW.filter(x=>!d.lines.some(l=>l.item===x.it.code&&l.size===x.sz)).map(x=>({k:uid(),item:x.it.code,size:x.sz,sl:Math.max(5,(+x.it.min||0)*2-Math.max(0,x.q)),gia:'',tt:'',kt:0}));
  if(!add.length)return toast('Không có size nào sắp hết chưa có trong phiếu');d.lines=d.lines.filter(l=>l.item).concat(add);drawP('nhap');toast('Đã thêm '+add.length+' dòng, số lượng là gợi ý')}
function saveP(t,andPrint){const d=draft(t),T=TYPES[t];d.date=$('#pDate').value;
  const {ls,errs}=lineIssues(t);if(!d.date)return toast('Chưa chọn ngày','err');if(t!=='nhap'&&!d.nv)return toast('Chưa chọn nhân viên','err');
  if(!ls.length)return toast('Phiếu chưa có đồng phục nào','err');if(errs.length)return toast(errs[0],'err');
  const doc=nextDoc(t);ls.forEach(l=>{const it=itemOf(l.item);S.mv.push({id:uid(),doc,type:t,date:d.date,item:l.item,size:l.size,sl:+l.sl,gia:t==='thu'?+it.gia:(l.gia===''?+it.gia:+l.gia),nv:t==='nhap'?'':d.nv,ncc:t==='nhap'?d.ncc:'',lydo:t==='nhap'?'':d.lydo,tinhtrang:t==='thu'?l.tt:'',kt:t==='thu'?+l.kt||0:0,note:d.note})});
  save();DRAFT[t]=null;toast(`Đã lưu ${doc}: ${ls.length} dòng, ${fmt(sum(ls,l=>l.sl))} cái`);renderPhieu(t);if(andPrint)setTimeout(()=>printDoc(doc),300)}
function delDoc(doc){const ls=S.mv.filter(v=>v.doc===doc);if(!ls.length||!confirm('Xóa phiếu '+doc+' ('+ls.length+' dòng)?'))return;S.mv=S.mv.filter(v=>v.doc!==doc);save();toast('Đã xóa phiếu '+doc);rerender()}
function printDoc(doc){const ls=S.mv.filter(v=>v.doc===doc);if(!ls.length)return;const g=ls[0],t=g.type,nv=nvOf(g.nv),co=S.company||{},[y,m,dd]=g.date.split('-'),dots='......................................';
  const tq=sum(ls,v=>v.sl),tv=sum(ls,v=>v.sl*v.gia),kt=sum(ls,v=>v.kt);
  const title=t==='nhap'?'PHIẾU NHẬP KHO ĐỒNG PHỤC':t==='cap'?'BIÊN BẢN BÀN GIAO ĐỒNG PHỤC':'BIÊN BẢN THU HỒI ĐỒNG PHỤC';
  const rows=ls.map((v,i)=>{const it=itemOf(v.item)||{};return `<tr><td class="c">${i+1}</td><td class="c">${esc(v.item)}</td><td>${esc(it.name||'')}</td><td class="c">${esc(v.size)}</td><td class="c">${esc(it.dvt||'Cái')}</td><td class="n">${fmt(v.sl)}</td>${t==='thu'?`<td class="c">${esc(v.tinhtrang)}</td><td class="n">${v.kt?fmt(v.kt):''}</td>`:`<td class="n">${fmt(v.gia)}</td><td class="n">${fmt(v.sl*v.gia)}</td>`}</tr>`}).join('');
  printHtml(`<div class="vt">${coHead(co.kho?'<b>'+esc(co.kho)+'</b>':'')}<h2>${title}</h2><div class="dt">Ngày ${dd} tháng ${m} năm ${y}</div><div class="so">Số: <b>${doc}</b></div>
   ${t==='nhap'?`<div class="ln">Nhà cung cấp: <b>${esc(g.ncc||dots)}</b></div><div class="ln">Người giao hàng: ${dots}</div><div class="ln">Ghi chú: ${esc(g.note||'')}</div>`
   :`<div class="ln"><b>Bên ${t==='cap'?'giao':'nhận lại'}:</b> ${esc(co.kho||'Bộ phận quản lý đồng phục')}</div><div class="ln"><b>Bên ${t==='cap'?'nhận':'trả lại'}:</b> Ông/Bà <b>${esc(nv?nv.name:g.nv)}</b> &nbsp; Mã NV: ${esc(g.nv)} &nbsp; Bộ phận: ${esc(nv?nv.bp:'')} &nbsp; Vị trí: ${esc(nv?nv.vitri:'')}</div><div class="ln">${t==='cap'?'Loại cấp phát':'Lý do thu hồi'}: <b>${esc(g.lydo)}</b>${g.note?' · '+esc(g.note):''}</div>`}
   <table><thead><tr><th style="width:36px">STT</th><th style="width:60px">Mã</th><th>Tên đồng phục</th><th style="width:50px">Size</th><th style="width:46px">ĐVT</th><th style="width:60px">Số lượng</th>${t==='thu'?'<th style="width:110px">Tình trạng</th><th style="width:100px">Khấu trừ (đ)</th>':'<th style="width:90px">Đơn giá</th><th style="width:110px">Thành tiền</th>'}</tr></thead><tbody>${rows}</tbody>
   <tfoot><tr><td></td><td></td><td class="c">Cộng</td><td></td><td></td><td class="n">${fmt(tq)}</td><td></td><td class="n">${fmt(t==='thu'?kt:tv)}</td></tr></tfoot></table>
   ${t==='thu'?(kt?`<div class="ln">Số tiền khấu trừ vào lương (viết bằng chữ): <b><i>${docSo(kt)}</i></b></div>`:''):`<div class="ln">Tổng giá trị (viết bằng chữ): <b><i>${docSo(tv)}</i></b></div>`}
   ${t==='cap'?'<div class="ln" style="margin-top:6px"><i>Nhân viên có trách nhiệm giữ gìn, sử dụng đồng phục đúng mục đích. Khi nghỉ việc phải hoàn trả theo quy định. Làm mất hoặc hư hỏng do lỗi cá nhân sẽ bồi hoàn theo quy định của đơn vị.</i></div>':''}
   ${signs(t==='nhap'?['Người lập phiếu','Người giao hàng','Thủ kho','Kế toán']:t==='cap'?['Người nhận','Người giao','Trưởng bộ phận','Phòng Nhân sự']:['Người trả lại','Người nhận lại','Trưởng bộ phận','Phòng Nhân sự'],g.date)}</div>`)}

/* =========================================================
   LỊCH SỬ
   ========================================================= */
function histRows(){const q=fold($('#hQ').value).trim(),ty=$('#hType').value,bp=$('#hBp').value,f=$('#hFrom').value,t=$('#hTo').value;
  return S.mv.filter(v=>{const nv=nvOf(v.nv),it=itemOf(v.item);return (!ty||v.type===ty)&&(!bp||(nv&&nv.bp===bp))&&(!f||v.date>=f)&&(!t||v.date<=t)&&(!q||fold(v.doc+' '+(nv?nv.name+' '+nv.code:'')+' '+(it?it.name+' '+it.code:'')+' '+v.ncc+' '+v.lydo+' '+v.note).includes(q))})
    .sort((a,b)=>b.date.localeCompare(a.date)||b.doc.localeCompare(a.doc))}
function renderHist(){const rows=histRows();
  $('#hTable').innerHTML=rows.length?`<table><thead><tr><th>Ngày</th><th>Số phiếu</th><th>Loại</th><th>Đồng phục</th><th class="c">Size</th><th class="n">SL</th><th class="n">Giá trị</th><th>Nhân viên / NCC</th><th>Lý do, tình trạng</th><th></th></tr></thead><tbody>${pgSlice('hist',rows).map(v=>{const nv=nvOf(v.nv),it=itemOf(v.item);
    return `<tr><td class="nw">${vnDate(v.date)}</td><td><b>${v.doc}</b></td><td><span class="tag ${TYPES[v.type].tag}">${TYPES[v.type].name}</span></td><td>${esc(it?it.name:v.item)}</td><td class="c">${esc(v.size)}</td><td class="n">${fmt(v.sl)}</td><td class="n">${fmt(v.sl*v.gia)}</td><td>${esc(v.type==='nhap'?v.ncc:(nv?nv.name:v.nv))}${nv?`<br><small class="muted">${esc(nv.bp)}</small>`:''}</td><td>${esc([v.lydo,v.tinhtrang,v.kt?'khấu trừ '+fmt(v.kt):''].filter(Boolean).join(' · '))}</td><td class="acts"><button class="btn sm ico" title="In phiếu" onclick="printDoc('${v.doc}')">${ICO.print}</button> <button class="btn sm ico danger" title="Xóa dòng" onclick="delMv('${v.id}')">${ICO.del}</button></td></tr>`}).join('')}</tbody>
  <tfoot><tr><td colspan="5">${fmt(rows.length)} dòng</td><td class="n">${fmt(sum(rows,v=>v.sl))}</td><td class="n">${fmt(sum(rows,v=>v.sl*v.gia))}</td><td colspan="3"></td></tr></tfoot></table>${pgBar('hist',rows.length,'renderHist')}`:'<div class="empty">Không có phiếu phù hợp</div>'}
function delMv(id){const v=S.mv.find(x=>x.id===id);if(!v||!confirm('Xóa dòng này khỏi phiếu '+v.doc+'?'))return;S.mv=S.mv.filter(x=>x.id!==id);save();toast('Đã xóa');renderHist()}
function histSheet(rows){return {name:'Lịch sử phiếu',title:'LỊCH SỬ NHẬP, CẤP, THU HỒI ĐỒNG PHỤC',columns:[{label:'Ngày',type:'date'},{label:'Số phiếu',width:10},{label:'Loại',width:10},{label:'Mã ĐP',width:8},{label:'Đồng phục',width:28},{label:'Size',type:'center',width:7},{label:'Số lượng',type:'int',sum:1},{label:'Đơn giá',type:'money'},{label:'Giá trị',type:'money',sum:1},{label:'Mã NV',width:9},{label:'Nhân viên',width:22},{label:'Bộ phận',width:16},{label:'Nhà cung cấp',width:28},{label:'Lý do',width:24},{label:'Tình trạng',width:14},{label:'Khấu trừ',type:'money',sum:1},{label:'Ghi chú',width:24}],
  rows:[...rows].sort((a,b)=>a.date.localeCompare(b.date)||a.doc.localeCompare(b.doc)).map(v=>{const nv=nvOf(v.nv),it=itemOf(v.item);return [v.date,v.doc,TYPES[v.type].name,v.item,it?it.name:'',v.size,v.sl,v.gia,v.sl*v.gia,v.nv,nv?nv.name:'',nv?nv.bp:'',v.ncc,v.lydo,v.tinhtrang,+v.kt||0,v.note]}),totals:1}}
function exportHist(){XLSX.save('Lich su phieu dong phuc',[histSheet(histRows())])}

/* =========================================================
   TỒN KHO THEO SIZE
   ========================================================= */
let TONF='';
function renderTon(){const el=$('#page-tonkho'),m=stockMap(),list=S.items.filter(i=>!TONF||i.bp===TONF).sort((a,b)=>BP.indexOf(a.bp)-BP.indexOf(b.bp)||a.code.localeCompare(b.code));
  const cells=list.flatMap(it=>sizesOf(it).map(sz=>({it,sz,q:m[it.code+'|'+sz]||0})));
  el.innerHTML=`<div class="kc-grid">${[['Tổng số cái tồn',sum(cells,c=>Math.max(0,c.q)),'cái',CD.main,CD.nhat,ICO.box],['Giá trị tồn',sum(cells,c=>Math.max(0,c.q)*c.it.gia),'₫',CD.dam,CD.nhat2,ICO.money],['Size sắp hết',cells.filter(c=>c.q>0&&c.q<=c.it.min).length,'size','#e6a100','var(--warnbg)',ICO.warn],['Size hết hàng',cells.filter(c=>c.q<=0).length,'size','#e0384a','var(--dangerbg)',ICO.warn]].map(([l,v,u,c,cb,ic])=>kc({c,cb,icon:ic,label:l,value:v,unit:u})).join('')}</div>
  <div class="toolbar"><select class="inp" id="tonBp" onchange="TONF=this.value;renderTon()"><option value="">Mọi bộ phận</option>${BP.map(b=>`<option ${b===TONF?'selected':''}>${b}</option>`).join('')}</select><button class="btn" onclick="XLSX.save('Ton kho dong phuc',[APP.sheets()[3]])">${ICO.xl}Xuất Excel</button><button class="btn in" onclick="go('nhap');fillLow()">${ICO.down}Lập phiếu nhập cho size sắp hết</button></div>
  <div class="card" style="margin-bottom:18px"><div class="card-h"><h3>Bản đồ tồn kho theo size</h3><span class="hint">Xanh: đủ · Vàng: sắp hết (≤ tồn tối thiểu) · Đỏ: hết</span></div><div class="card-b">${heatHtml(list)}</div></div>
  <div class="card"><div class="card-h"><h3>Chi tiết từng size</h3></div><div class="tbl-wrap"><table><thead><tr><th>Mã</th><th>Đồng phục</th><th>Bộ phận</th><th class="c">Size</th><th class="n">Nhập</th><th class="n">Cấp</th><th class="n">Thu về kho</th><th class="n">Tồn</th><th class="n">Tối thiểu</th><th class="n">Giá trị tồn</th><th>Tình trạng</th></tr></thead><tbody>${pgSlice('ton',cells,20).map(c=>{const mv=S.mv.filter(v=>v.item===c.it.code&&v.size===c.sz),st=cellSt(c.q,+c.it.min||0);
    return `<tr><td><b>${esc(c.it.code)}</b></td><td>${esc(c.it.name)}</td><td>${esc(c.it.bp)}</td><td class="c"><b>${esc(c.sz)}</b></td><td class="n">${fmt(sum(mv.filter(v=>v.type==='nhap'),v=>v.sl))}</td><td class="n">${fmt(sum(mv.filter(v=>v.type==='cap'),v=>v.sl))}</td><td class="n">${fmt(sum(mv.filter(v=>v.type==='thu'&&v.tinhtrang==='Dùng lại được'),v=>v.sl))}</td><td class="n"><b>${fmt(c.q)}</b></td><td class="n">${fmt(c.it.min)}</td><td class="n">${fmt(Math.max(0,c.q)*c.it.gia)}</td><td><span class="tag ${st}">${{ok:'Đủ',low:'Sắp hết',zero:'Hết hàng'}[st]}</span></td></tr>`}).join('')}</tbody></table>${pgBar('ton',cells.length,'renderTon')}</div></div>`;
  countUp(el)}

/* =========================================================
   BÁO CÁO THEO KỲ
   ========================================================= */
function repData(){if(!$('#rFrom').value){const [f,t]=quickRange('month');$('#rFrom').value=f;$('#rTo').value=t;$('#rQuick [data-v=month]').classList.add('on')}
  const f=$('#rFrom').value,t=$('#rTo').value,inR=v=>v.date>=f&&v.date<=t;
  const bp=BP.map(b=>{const mv=S.mv.filter(v=>inR(v)&&v.type!=='nhap'&&(nvOf(v.nv)||{}).bp===b),cap=mv.filter(v=>v.type==='cap'),thu=mv.filter(v=>v.type==='thu');
    return {b,nv:active().filter(s=>s.bp===b).length,nCap:new Set(cap.map(v=>v.nv)).size,capQ:sum(cap,v=>v.sl),capV:sum(cap,v=>v.sl*v.gia),thuQ:sum(thu,v=>v.sl),kt:sum(thu,v=>v.kt)}});
  const items=S.items.map(it=>{const all=S.mv.filter(v=>v.item===it.code),sg=v=>v.type==='nhap'?v.sl:v.type==='cap'?-v.sl:v.tinhtrang==='Dùng lại được'?v.sl:0;
    const open=sum(all.filter(v=>v.date<f),sg),r=all.filter(inR),nh=sum(r.filter(v=>v.type==='nhap'),v=>v.sl),cp=sum(r.filter(v=>v.type==='cap'),v=>v.sl),th=sum(r.filter(v=>v.type==='thu'&&v.tinhtrang==='Dùng lại được'),v=>v.sl),close=open+nh-cp+th;
    return {it,open,nh,nhV:sum(r.filter(v=>v.type==='nhap'),v=>v.sl*v.gia),cp,cpV:sum(r.filter(v=>v.type==='cap'),v=>v.sl*v.gia),th,close,val:Math.max(0,close)*it.gia}});
  return {f,t,bp,items}}
function renderRep(){const d=repData(),rng=`Từ ngày ${vnDate(d.f)} đến ngày ${vnDate(d.t)}`;$('#rHint').textContent=rng;$('#rPrint').textContent=rng;
  $('#rKpi').innerHTML=[['Chi phí mua',sum(d.items,x=>x.nhV),'₫',CD.sang,CD.nhat2,ICO.down],['Giá trị cấp phát',sum(d.items,x=>x.cpV),'₫',CD.main,CD.nhat,ICO.user],['Số cái cấp',sum(d.items,x=>x.cp),'cái','#f0663a','var(--outbg)',ICO.box],['Khấu trừ thu hồi',sum(d.bp,x=>x.kt),'₫','#e0384a','var(--dangerbg)',ICO.money]].map(([l,v,u,c,cb,ic])=>kc({c,cb,icon:ic,label:l,value:v,unit:u})).join('');
  $('#rBp').innerHTML=`<table><thead><tr><th>Bộ phận</th><th class="n">NV đang làm</th><th class="n">NV được cấp</th><th class="n">Số cái cấp</th><th class="n">Giá trị cấp</th><th class="n">Số cái thu hồi</th><th class="n">Khấu trừ</th><th style="width:22%">Tỷ trọng giá trị cấp</th></tr></thead><tbody>${d.bp.map(r=>{const tot=sum(d.bp,x=>x.capV)||1;return `<tr><td><b>${esc(r.b)}</b></td><td class="n">${r.nv}</td><td class="n">${r.nCap}</td><td class="n">${fmt(r.capQ)}</td><td class="n"><b>${fmt(r.capV)}</b></td><td class="n">${fmt(r.thuQ)}</td><td class="n">${fmt(r.kt)}</td><td><div class="pbar"><i style="width:${r.capV/tot*100}%"></i></div></td></tr>`}).join('')}</tbody>
    <tfoot><tr><td>Cộng</td>${['nv','nCap','capQ','capV','thuQ','kt'].map(k=>`<td class="n">${fmt(sum(d.bp,x=>x[k]))}</td>`).join('')}<td></td></tr></tfoot></table>`;
  $('#rItem').innerHTML=`<table><thead><tr><th>Mã</th><th>Đồng phục</th><th class="n">Tồn đầu kỳ</th><th class="n">Nhập</th><th class="n">Cấp</th><th class="n">Thu về kho</th><th class="n">Tồn cuối kỳ</th><th class="n">Giá trị tồn cuối</th></tr></thead><tbody>${d.items.map(r=>`<tr><td><b>${esc(r.it.code)}</b></td><td>${esc(r.it.name)}</td><td class="n">${fmt(r.open)}</td><td class="n">${fmt(r.nh)}</td><td class="n">${fmt(r.cp)}</td><td class="n">${fmt(r.th)}</td><td class="n"><b>${fmt(r.close)}</b></td><td class="n">${fmt(r.val)}</td></tr>`).join('')}</tbody>
    <tfoot><tr><td colspan="2">Cộng</td>${['open','nh','cp','th','close','val'].map(k=>`<td class="n">${fmt(sum(d.items,x=>x[k]))}</td>`).join('')}</tr></tfoot></table>`;
  countUp($('#rKpi'))}
function exportRep(){const d=repData(),rng='Từ ngày '+vnDate(d.f)+' đến ngày '+vnDate(d.t);
  XLSX.save('Bao cao dong phuc',[{name:'Theo bộ phận',title:'CẤP PHÁT ĐỒNG PHỤC THEO BỘ PHẬN',subtitles:[rng],columns:[{label:'Bộ phận',width:18},{label:'NV đang làm',type:'int'},{label:'NV được cấp',type:'int'},{label:'Số cái cấp',type:'int',sum:1},{label:'Giá trị cấp',type:'money',sum:1},{label:'Số cái thu hồi',type:'int',sum:1},{label:'Khấu trừ',type:'money',sum:1}],rows:d.bp.map(r=>[r.b,r.nv,r.nCap,r.capQ,r.capV,r.thuQ,r.kt]),totals:1},
    {name:'Theo loại đồng phục',title:'NHẬP, CẤP, THU HỒI, TỒN THEO LOẠI ĐỒNG PHỤC',subtitles:[rng],columns:[{label:'Mã',width:8},{label:'Đồng phục',width:28},{label:'Tồn đầu kỳ',type:'int',sum:1},{label:'Nhập',type:'int',sum:1},{label:'Giá trị nhập',type:'money',sum:1},{label:'Cấp',type:'int',sum:1},{label:'Giá trị cấp',type:'money',sum:1},{label:'Thu về kho',type:'int',sum:1},{label:'Tồn cuối kỳ',type:'int',sum:1},{label:'Giá trị tồn cuối',type:'money',sum:1}],rows:d.items.map(r=>[r.it.code,r.it.name,r.open,r.nh,r.nhV,r.cp,r.cpV,r.th,r.close,r.val]),totals:1}])}

boot();
