
/* Chụp mã gốc của trang TRƯỚC khi vẽ giao diện: khi Lưu đè chỉ thay khối dữ liệu */
const TEMPLATE=document.documentElement.outerHTML;

/* =========================================================
   TIỆN ÍCH
   ========================================================= */
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN').format(Math.round(+n||0));
const fmtD=(n,d)=>new Intl.NumberFormat('vi-VN',{maximumFractionDigits:d==null?2:d}).format(+n||0);
const money=n=>fmt(n)+' ₫';
const pct=(n,d)=>fmtD(n,d==null?1:d)+'%';
const short=n=>{n=Math.round(+n||0);const a=Math.abs(n);
  if(a>=1e9)return (n/1e9).toFixed(a>=1e10?0:1).replace('.',',')+' tỷ';
  if(a>=1e6)return (n/1e6).toFixed(a>=1e8?0:1).replace('.',',')+' tr';
  if(a>=1e3)return Math.round(n/1e3)+'k';return String(n)};
const pad=n=>String(n).padStart(2,'0');
const ymd=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const pd=s=>{const [y,m,d]=String(s).slice(0,10).split('-').map(Number);return new Date(y,(m||1)-1,d||1)};
const vnDate=s=>{if(!s)return '';const [y,m,d]=String(s).slice(0,10).split('-');return d+'/'+m+'/'+y};
const hm=t=>{const d=new Date(t);return pad(d.getHours())+':'+pad(d.getMinutes())+' '+pad(d.getDate())+'/'+pad(d.getMonth()+1)};
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2,7);
const TODAY=new Date(), TODAYS=ymd(TODAY);
const addDays=(s,n)=>{const d=pd(s);d.setDate(d.getDate()+n);return ymd(d)};
const addMonths=(s,n)=>{const d=pd(s),day=d.getDate();const t=new Date(d.getFullYear(),d.getMonth()+n,1);t.setDate(Math.min(day,new Date(t.getFullYear(),t.getMonth()+1,0).getDate()));return ymd(t)};
const diffDays=(a,b)=>{const x=pd(a),y=pd(b);return Math.round((Date.UTC(y.getFullYear(),y.getMonth(),y.getDate())-Date.UTC(x.getFullYear(),x.getMonth(),x.getDate()))/864e5)};
const nowHM=()=>{const d=new Date();return pad(d.getHours())+':'+pad(d.getMinutes())};
const fold=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase();
const normH=s=>fold(s).replace(/[^a-z0-9]/g,'');
const parseNum=v=>{if(typeof v==='number')return v;let s=String(v??'').trim();if(!s)return 0;
  if(/^-?\d{1,3}(\.\d{3})+(,\d+)?$/.test(s))s=s.replace(/\./g,'').replace(',','.');
  else if(/^-?\d+,\d+$/.test(s))s=s.replace(',','.');
  else s=s.replace(/[^\d.\-]/g,'');const n=parseFloat(s);return isNaN(n)?0:n};
const sum=(a,f)=>a.reduce((s,x)=>s+(+(f?f(x):x)||0),0);
const groupBy=(a,f)=>{const m={};a.forEach(x=>{const k=f(x);(m[k]=m[k]||[]).push(x)});return m};
const mKey=s=>String(s).slice(0,7);
function monthsBack(n,end){const e=end?pd(end):TODAY,o=[];for(let i=n-1;i>=0;i--){const d=new Date(e.getFullYear(),e.getMonth()-i,1);o.push(d.getFullYear()+'-'+pad(d.getMonth()+1))}return o}
const mLabel=(k,i)=>{const [y,m]=k.split('-');return 'T'+(+m)+(m==='01'||i===0?'/'+y.slice(2):'')};
const mName=k=>{const [y,m]=k.split('-');return 'Tháng '+(+m)+'/'+y};
const WD=['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
function rangeOf(k){
  const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t,pf,pt,lbl,cmp;
  if(k==='today'){f=t=TODAY;pf=pt=new Date(y,m,TODAY.getDate()-1);lbl='Hôm nay '+vnDate(TODAYS);cmp='so với hôm qua'}
  else if(k==='7d'){t=TODAY;f=new Date(y,m,TODAY.getDate()-6);pt=new Date(y,m,TODAY.getDate()-7);pf=new Date(y,m,TODAY.getDate()-13);lbl='7 ngày gần nhất';cmp='so với 7 ngày trước đó'}
  else if(k==='month'){f=new Date(y,m,1);t=new Date(y,m+1,0);pf=new Date(y,m-1,1);pt=new Date(y,m,0);lbl='Tháng '+(m+1)+'/'+y;cmp='so với tháng trước'}
  else if(k==='prev'){f=new Date(y,m-1,1);t=new Date(y,m,0);pf=new Date(y,m-2,1);pt=new Date(y,m-1,0);lbl='Tháng '+(f.getMonth()+1)+'/'+f.getFullYear();cmp='so với tháng trước đó'}
  else if(k==='quarter'){const q=Math.floor(m/3);f=new Date(y,q*3,1);t=new Date(y,q*3+3,0);pf=new Date(y,q*3-3,1);pt=new Date(y,q*3,0);lbl='Quý '+(q+1)+'/'+y;cmp='so với quý trước'}
  else if(k==='year'){f=new Date(y,0,1);t=new Date(y,11,31);pf=new Date(y-1,0,1);pt=new Date(y-1,11,31);lbl='Năm '+y;cmp='so với năm trước'}
  else return {from:'0000-01-01',to:'9999-12-31',label:'Toàn bộ thời gian'};
  /* kỳ chưa hết: so với cùng số ngày của kỳ trước cho công bằng */
  if(t>TODAY&&['month','quarter','year'].includes(k)){const el=diffDays(ymd(f),TODAYS);const p2=new Date(pf.getFullYear(),pf.getMonth(),pf.getDate()+el);if(p2<pt)pt=p2;cmp=cmp.replace('so với','so với cùng kỳ')}
  return {from:ymd(f),to:ymd(t),pfrom:ymd(pf),pto:ymd(pt),label:lbl,cmp}}
const CSSV=n=>getComputedStyle(document.documentElement).getPropertyValue(n).trim();

/* biểu tượng nét mảnh */
const I=p=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${p}</svg>`;
const IC={
  dash:I('<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>'),
  plus:I('<path d="M12 5v14M5 12h14"/>'),
  edit:I('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>'),
  trash:I('<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>'),
  print:I('<path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/>'),
  xl:I('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M9 13l6 5M15 13l-6 5"/>'),
  down:I('<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>'),
  up:I('<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>'),
  imp:I('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M12 18v-6M9 15l3 3 3-3"/>'),
  search:I('<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>'),
  save:I('<path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>'),
  box:I('<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'),
  in:I('<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>'),
  out:I('<path d="M12 15V3M7 8l5-5 5 5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>'),
  cup:I('<path d="M17 8h1a4 4 0 010 8h-1"/><path d="M3 8h14v9a4 4 0 01-4 4H7a4 4 0 01-4-4z"/><path d="M6 2v3M10 2v3M14 2v3"/>'),
  cart:I('<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 002 1.6h8.6a2 2 0 002-1.6L22 7H6"/>'),
  receipt:I('<path d="M4 2v20l3-2 3 2 3-2 3 2 3-2 1 .7V2l-1 .7-3-2-3 2-3-2-3 2-3-2z"/><path d="M8 8h8M8 12h8M8 16h5"/>'),
  list:I('<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>'),
  file:I('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>'),
  chart:I('<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>'),
  bars:I('<path d="M3 21h18"/><rect x="5" y="11" width="3" height="7" rx="1"/><rect x="10.5" y="6" width="3" height="12" rx="1"/><rect x="16" y="9" width="3" height="9" rx="1"/>'),
  pie:I('<path d="M21.2 15.9A10 10 0 118 2.8"/><path d="M22 12A10 10 0 0012 2v10z"/>'),
  trend:I('<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>'),
  trendd:I('<path d="M3 7l6 6 4-4 8 8"/><path d="M14 17h7v-7"/>'),
  warn:I('<path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/>'),
  check:I('<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>'),
  clock:I('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
  cal:I('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
  users:I('<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8"/>'),
  user:I('<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'),
  truck:I('<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'),
  scale:I('<path d="M12 3v18M5 7h14M3 21h18"/><path d="M5 7l-3 7a3 3 0 006 0zM19 7l-3 7a3 3 0 006 0z"/>'),
  money:I('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>'),
  wallet:I('<path d="M20 12V8H6a2 2 0 010-4h12v4"/><path d="M4 6v12a2 2 0 002 2h14v-4"/><path d="M18 12a2 2 0 000 4h4v-4z"/>'),
  tool:I('<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.8-3.8a6 6 0 01-7.9 7.9l-6.9 6.9a2.1 2.1 0 01-3-3l6.9-6.9a6 6 0 017.9-7.9z"/>'),
  cpu:I('<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/>'),
  shield:I('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'),
  store:I('<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v1a3 3 0 01-6 0 3 3 0 01-6 0 3 3 0 01-6 0z"/><path d="M5 12v9h14v-9"/>'),
  factory:I('<path d="M2 20V9l6 4V9l6 4V5h4l2 15z"/><path d="M2 20h20"/>'),
  target:I('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>'),
  flag:I('<path d="M4 22V4M4 4h13l-2 4 2 4H4"/>'),
  set:I('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>'),
  book:I('<path d="M4 19.5A2.5 2.5 0 016.5 17H20V3H6.5A2.5 2.5 0 004 5.5z"/><path d="M4 19.5A2.5 2.5 0 006.5 22H20v-5"/>'),
  refresh:I('<path d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.7L3 8"/><path d="M3 3v5h5"/>'),
  menu:I('<path d="M4 6h16M4 12h16M4 18h16"/>'),
  eye:I('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'),
  layers:I('<path d="M12 2l10 5-10 5L2 7z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>'),
  pin:I('<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>'),
  history:I('<path d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.7L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/>'),
  leaf:I('<path d="M11 20A7 7 0 019.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10z"/><path d="M2 21c0-3 1.9-5.4 5.1-6"/>')
};

/* =========================================================
   DỮ LIỆU: khối du-lieu trong file + bản tạm trong trình duyệt
   ========================================================= */
let S=null, DIRTY=false, DRAFT_FOUND=null, FH=null, SAVED_AT=0;
const ONLINE=/^https?:$/.test(location.protocol);
const LSKEY=()=>APP.key+'|'+location.pathname;
const FILE_NAME=()=>{let n='';try{n=decodeURIComponent(location.pathname.split('/').pop()||'')}catch(e){}return /\.html?$/i.test(n)?n:APP.fileName};
function loadData(){
  let emb=null,draft=null;
  try{emb=JSON.parse($('#du-lieu').textContent)}catch(e){}
  try{draft=JSON.parse(localStorage.getItem(LSKEY()))}catch(e){}
  if(APP.valid(emb)){S=emb;SAVED_AT=emb.savedAt||0;
    if(APP.valid(draft)&&(draft.updated||0)>(emb.updated||0))DRAFT_FOUND=draft}
  else{S=APP.seed();S.sample=true;S.updated=0;
    if(APP.valid(draft)&&(draft.updated||0)>0)DRAFT_FOUND=draft}
  if(APP.fix)APP.fix(S);
}
function persist(){try{localStorage.setItem(LSKEY(),JSON.stringify(S));return true}catch(e){return false}}
function save(){S.updated=Date.now();DIRTY=true;
  if(!persist())toast('Bộ nhớ tạm của trình duyệt đã đầy, hãy bấm Lưu đè dữ liệu ngay để ghi vào file','err');
  refreshChrome();refreshSave()}
function askRestore(){const d=DRAFT_FOUND;
  openModal(`<div class="modal-h"><h3>Có bản tạm chưa lưu vào file</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p>Lần trước có thay đổi lúc <b>${hm(d.updated)}</b> nhưng chưa bấm <b>Lưu đè dữ liệu</b>. Bản tạm này mới hơn dữ liệu đang có trong file.</p><p class="muted" style="margin-top:8px">Khôi phục để làm tiếp, hoặc bỏ bản tạm để dùng đúng dữ liệu trong file.</p></div>
  <div class="modal-f"><button class="btn" id="rsNo">Bỏ bản tạm</button><button class="btn pri" id="rsYes">Khôi phục bản tạm</button></div>`,'small');
  $('#rsNo').onclick=()=>{try{localStorage.removeItem(LSKEY())}catch(e){}DRAFT_FOUND=null;closeModal();toast('Đã bỏ bản tạm, đang dùng dữ liệu trong file')};
  $('#rsYes').onclick=()=>{S=d;if(APP.fix)APP.fix(S);DIRTY=true;DRAFT_FOUND=null;closeModal();refreshChrome();refreshSave();rerender();toast('Đã khôi phục bản tạm. Nhớ bấm Lưu đè dữ liệu để ghi vào file.')}}

/* =========================================================
   LƯU ĐÈ VÀO CHÍNH FILE HTML
   ========================================================= */
const idb=(mode,fn)=>new Promise((ok,no)=>{const rq=indexedDB.open('file_mau_phongexcel',1);rq.onupgradeneeded=()=>rq.result.createObjectStore('h');
  rq.onsuccess=()=>{const tx=rq.result.transaction('h',mode),r=fn(tx.objectStore('h'));tx.oncomplete=()=>ok(r&&r.result);tx.onerror=()=>no(tx.error)};rq.onerror=()=>no(rq.error)});
const idbGet=k=>idb('readonly',s=>s.get(k)).catch(()=>null);
const idbSet=(k,v)=>idb('readwrite',s=>s.put(v,k)).catch(()=>null);
async function buildHtml(data){
  const response=await fetch('/demo-assets/bao-cao-quan-tri-doanh-nghiep/original.html',{cache:'no-store'});
  if(!response.ok)throw new Error('Không thể tải mẫu HTML gốc để xuất bản sao');
  const template=await response.text();
  const json=JSON.stringify(data).replace(/</g,'\u003c');
  const open='<script id="du-lieu" type="application/json">',close='</scr'+'ipt>';
  const a=template.indexOf(open),b=template.indexOf(close,a);
  if(a<0||b<0)throw new Error('Không tìm thấy khối dữ liệu trong mẫu HTML');
  return template.slice(0,a+open.length)+json+template.slice(b);
}async function saveToFile(){
  const data={...S,savedAt:Date.now()};data.updated=Math.max(S.updated||0,data.savedAt);
  const html=await buildHtml(data),name=FILE_NAME();
  if(ONLINE||!window.showSaveFilePicker)return downloadHtml(html,data);
  try{
    let h=FH;
    if(!h){const kept=await idbGet(LSKEY());if(kept&&kept.name===name)h=kept}
    if(h){let p=await h.queryPermission({mode:'readwrite'});if(p!=='granted')p=await h.requestPermission({mode:'readwrite'});if(p!=='granted')h=null}
    if(!h){if(!await askPick())return;
      h=await showSaveFilePicker({suggestedName:name,startIn:'downloads',types:[{description:'Trang HTML',accept:{'text/html':['.html']}}]})}
    const w=await h.createWritable();await w.write(html);await w.close();
    FH=h;idbSet(LSKEY(),h);done(data);
    toast(h.name===name?'Đã lưu đè dữ liệu vào file '+h.name:'Đã lưu vào file '+h.name+' (khác tên file đang mở)');
  }catch(e){if(e&&e.name==='AbortError')return;console.error(e);downloadHtml(html,data)}
}
function done(data){S.updated=data.updated;S.savedAt=data.savedAt;SAVED_AT=data.savedAt;DIRTY=false;persist();refreshSave()}
function downloadHtml(html,data){
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([html],{type:'text/html'}));a.download=FILE_NAME();document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},2000);
  done(data);toast('Trình duyệt này không cho ghi đè trực tiếp. Đã tải bản mới về máy (thư mục Tải xuống), hãy dùng file vừa tải từ giờ.','err')}
function askPick(){return new Promise(res=>{
  openModal(`<div class="modal-h"><h3>Lưu đè lần đầu vào file</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p>Trình duyệt cần chỉ đúng file để ghi đè <b>một lần duy nhất</b>:</p>
  <ol><li>Bấm <b>Chọn file để ghi đè</b> bên dưới.</li><li>Tìm tới thư mục chứa file đang mở, chọn đúng file <b>${esc(FILE_NAME())}</b>.</li><li>Bấm <b>Save / Lưu</b>, rồi bấm <b>Yes / Có</b> khi được hỏi có thay file cũ không.</li><li>Nếu trình duyệt hỏi quyền chỉnh sửa, bấm <b>Allow / Cho phép</b>.</li></ol>
  <p class="muted">Từ lần sau chỉ cần bấm Lưu đè dữ liệu hoặc Ctrl + S là xong.</p></div>
  <div class="modal-f"><button class="btn" id="pkNo">Để sau</button><button class="btn pri" id="pkYes">Chọn file để ghi đè</button></div>`,'small');
  $('#pkNo').onclick=()=>{closeModal();res(false)};$('#pkYes').onclick=()=>{closeModal();res(true)}})}
async function forgetFile(){FH=null;await idbSet(LSKEY(),null);saveToFile()}
function refreshSave(){
  const st=$('#saveSt'),bt=$('#saveBtn');if(!st)return;
  bt.classList.toggle('dirty',DIRTY);st.className='savest '+(DIRTY?'dirty':'clean');
  st.innerHTML=DIRTY?'<b>Chưa lưu vào file</b>có thay đổi mới':SAVED_AT?'<b>Đã lưu vào file</b>lúc '+hm(SAVED_AT):'<b>Dữ liệu mẫu</b>chưa lưu lần nào'}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveToFile()}});
window.addEventListener('beforeunload',e=>{if(DIRTY){e.preventDefault();e.returnValue=''}});

/* =========================================================
   KHUNG GIAO DIỆN: sidebar, thanh trên, điều hướng
   ========================================================= */
let CUR='';
function buildShell(){
  const nav=APP.nav.map(n=>typeof n==='string'?`<h6>${esc(n)}</h6>`:`<a data-page="${n.id}">${IC[n.icon]||IC.list}<span>${esc(n.label)}</span>${n.badge?`<span class="badge${n.badge==='w'?' w':''}" id="bd-${n.id}" style="display:none"></span>`:''}</a>`).join('');
  $('#sb').innerHTML=`<div class="brand"><div class="logo">${APP.brand.icon}</div><div><b>${esc(APP.brand.name)}</b><small>${esc(APP.brand.sub)}</small></div></div>
    <nav class="nav" id="nav">${nav}</nav>
    <div class="sbcard"><small id="sbLabel"></small><b id="sbValue">0</b><div class="bar"><i id="sbBar" style="width:0"></i></div><p id="sbNote"></p></div>
    <div class="sbfoot">File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel</div>`;
  $('#top').innerHTML=`<button class="burger" id="burger" aria-label="Mở menu">${IC.menu}</button>
    <div style="min-width:0"><h1 id="pTitle"></h1><div class="sub" id="pSub"></div></div>
    <div class="right">
      <span class="chip" id="today"></span>
      ${(APP.quick||[]).map(q=>`<button class="btn ${q.cls||'pri'} qk noprint" onclick="${q.fn}">${IC[q.icon]||IC.plus}${esc(q.label)}</button>`).join('')}
      <button class="btn xl noprint" onclick="exportAll()" title="Xuất toàn bộ bảng ra file Excel .xlsx, mỗi bảng một sheet">${IC.xl}<span class="t">Xuất Excel</span></button>
      <div class="savebox noprint"><div class="savest" id="saveSt"></div>
        <button class="btn save" id="saveBtn" title="Ghi dữ liệu vào chính file này (Ctrl + S)" onclick="saveToFile()">${IC.save}<span class="t">Lưu đè dữ liệu</span></button></div>
    </div>`;
  $('#content').innerHTML=Object.keys(APP.pages).map(k=>`<section class="page" id="page-${k}"></section>`).join('');
  $$('#nav a').forEach(a=>a.onclick=()=>go(a.dataset.page));
  $('#burger').onclick=()=>{$('#sb').classList.add('open');$('#mask').classList.add('show')};
  $('#mask').onclick=()=>{$('#sb').classList.remove('open');$('#mask').classList.remove('show')};
  $('#today').textContent=TODAY.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'});
}
function go(pg){
  if(!APP.pages[pg])pg=Object.keys(APP.pages)[0];CUR=pg;
  $$('#nav a').forEach(a=>a.classList.toggle('active',a.dataset.page===pg));
  $$('.page').forEach(s=>s.classList.toggle('show',s.id==='page-'+pg));
  $('#pTitle').textContent=APP.pages[pg][0];$('#pSub').textContent=APP.pages[pg][1];
  APP.pages[pg][2]();
  try{if(location.hash!=='#'+pg)history.replaceState(null,'','#'+pg)}catch(e){}
  $('#sb').classList.remove('open');$('#mask').classList.remove('show');window.scrollTo(0,0);
}
function rerender(){if(CUR)APP.pages[CUR][2]()}
function refreshChrome(){
  const b=APP.badges?APP.badges():{};
  Object.keys(b).forEach(k=>{const el=$('#bd-'+k);if(el){el.style.display=b[k]?'':'none';el.textContent=b[k]}});
  const c=APP.sbcard();$('#sbLabel').textContent=c.label;$('#sbValue').textContent=c.value;$('#sbBar').style.width=Math.max(0,Math.min(100,c.pct||0))+'%';$('#sbNote').textContent=c.note||'';
}
function boot(){
  loadData();buildShell();refreshChrome();refreshSave();
  go((location.hash||'').slice(1));
  if(DRAFT_FOUND)setTimeout(askRestore,400);
}

/* =========================================================
   MODAL, HỎI LẠI, TOAST, TOOLTIP, IN
   ========================================================= */
function openModal(html,size){const m=$('#modal');m.className='modal'+(size?' '+size:'');m.innerHTML=html;$('#ov').classList.add('show')}
function closeModal(){$('#ov').classList.remove('show');$$('.ac-list').forEach(b=>b.classList.remove('show'))}
document.addEventListener('mousedown',e=>{if(e.target.id==='ov')closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
function ask(msg,ok,danger){return new Promise(res=>{
  openModal(`<div class="modal-h"><h3>Xác nhận</h3><button class="x" id="akX">×</button></div><div class="modal-b">${msg}</div>
  <div class="modal-f"><button class="btn" id="akNo">Thôi</button><button class="btn ${danger===false?'pri':'pri'}" id="akYes" style="${danger===false?'':'background:var(--danger);border-color:var(--danger)'}">${esc(ok||'Đồng ý xóa')}</button></div>`,'small');
  const end=v=>{closeModal();res(v)};$('#akNo').onclick=()=>end(false);$('#akX').onclick=()=>end(false);$('#akYes').onclick=()=>end(true)})}
function toast(msg,type){const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;$('#toasts').appendChild(t);setTimeout(()=>{t.style.opacity=0;t.style.transition='.3s'},3800);setTimeout(()=>t.remove(),4200)}
document.addEventListener('mousemove',e=>{const tip=$('#tip');const t=e.target.closest&&e.target.closest('[data-tip]');
  if(!t){tip.style.display='none';return}tip.textContent=t.dataset.tip;tip.style.display='block';
  const w=tip.offsetWidth;tip.style.left=Math.max(8,Math.min(e.clientX+14,innerWidth-w-8))+'px';tip.style.top=(e.clientY-36)+'px'});
function printHtml(html){$('#printArea').innerHTML=html;document.body.classList.add('pdoc');setTimeout(()=>window.print(),80)}
window.addEventListener('afterprint',()=>document.body.classList.remove('pdoc'));
const co=()=>S.company||{};
const DOTS='..........................................................';
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
   CHIA TRANG
   ========================================================= */
const PG={},PSIZES=[10,20,50,100];let PRINT_ALL=false;
function pgState(k,def){return PG[k]=PG[k]||{p:0,n:def||20}}
function pgReset(k){if(PG[k])PG[k].p=0}
function pgSlice(k,list,def){const st=pgState(k,def);if(PRINT_ALL)return list;const pages=Math.max(1,Math.ceil(list.length/st.n));if(st.p>=pages)st.p=pages-1;if(st.p<0)st.p=0;return list.slice(st.p*st.n,st.p*st.n+st.n)}
function pgOff(k){const st=pgState(k);return PRINT_ALL?0:st.p*st.n}
const PGFN={};
function pgBar(k,total,fn){const st=pgState(k),pages=Math.max(1,Math.ceil(total/st.n)),p=st.p;PGFN[k]=fn;
  if(PRINT_ALL||total<=10)return '';
  const from=total?p*st.n+1:0,to=Math.min(total,(p+1)*st.n);const b=i=>`<button class="${i===p?'on':''}" onclick="pgGo('${k}',${i})">${i+1}</button>`;
  let nums='';if(pages<=7)for(let i=0;i<pages;i++)nums+=b(i);else{nums+=b(0);const a=Math.max(1,p-1),e=Math.min(pages-2,p+1);if(a>1)nums+='<span>…</span>';for(let i=a;i<=e;i++)nums+=b(i);if(e<pages-2)nums+='<span>…</span>';nums+=b(pages-1)}
  return `<div class="pgbar"><span>Hiển thị <b>${fmt(from)}-${fmt(to)}</b> / ${fmt(total)} dòng</span><span class="pgs">Số dòng/trang<select onchange="pgSize('${k}',this.value)">${PSIZES.map(n=>`<option ${n===st.n?'selected':''}>${n}</option>`).join('')}</select>${pages>1?`<button ${p?'':'disabled'} onclick="pgGo('${k}',${p-1})">‹</button>${nums}<button ${p<pages-1?'':'disabled'} onclick="pgGo('${k}',${p+1})">›</button>`:''}</span></div>`}
function pgGo(k,p){pgState(k).p=p;PGFN[k]&&PGFN[k]()}
function pgSize(k,n){const st=pgState(k);st.n=+n;st.p=0;PGFN[k]&&PGFN[k]()}
window.addEventListener('beforeprint',()=>{if(document.body.classList.contains('pdoc'))return;PRINT_ALL=true;try{rerender()}catch(e){}});
window.addEventListener('afterprint',()=>{if(!PRINT_ALL)return;PRINT_ALL=false;try{rerender()}catch(e){}});

/* =========================================================
   BIỂU ĐỒ SVG TỰ VẼ (không cần mạng)
   ========================================================= */
function niceMax(v){if(v<=0)return 1;const p=Math.pow(10,Math.floor(Math.log10(v)));const n=v/p;return (n<=1?1:n<=2?2:n<=2.5?2.5:n<=5?5:10)*p}
let GID=0;
/* o={labels, bars:[{name,data,color}], lines:[{name,data,color}], stack, fv, H, hl(index nổi bật), ax(fn trục)} */
function combo(o){
  const W=o.W||760,H=o.H||300,pl=56,pr=12,pt=16,pb=34,ih=H-pt-pb,n=o.labels.length,fv=o.fv||money,ax=o.ax||short;
  const bars=o.bars||[],lines=o.lines||[];
  const tops=o.labels.map((_,i)=>o.stack?sum(bars,b=>Math.max(0,b.data[i])):Math.max(0,...bars.map(b=>b.data[i])));
  const max=niceMax(Math.max(1,...tops,...lines.flatMap(l=>l.data)));
  const cw=(W-pl-pr)/n,nb=o.stack?1:Math.max(1,bars.length),bw=Math.max(3,Math.min(o.bw||18,(cw-10)/nb-3)),Y=v=>pt+ih*(1-Math.max(0,v)/max);
  let g='';
  if(o.hl!=null&&o.hl>=0)g+=`<rect class="hl" x="${pl+cw*o.hl+2}" y="${pt-8}" width="${cw-4}" height="${ih+8}" rx="10" fill="${CSSV('--cd-nhat')}"/>`;
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${ax(max*i/4)}</text>`}
  const every=n>24?Math.ceil(n/12):n>14?2:1;
  o.labels.forEach((l,i)=>{const cx=pl+cw*i+cw/2;
    if(o.stack){let acc=0;bars.forEach((b,j)=>{const v=Math.max(0,b.data[i]||0),hh=ih*v/max;if(v>0)g+=`<rect class="bar" style="animation-delay:${i*30}ms" x="${cx-bw/2}" y="${pt+ih-acc-hh}" width="${bw}" height="${Math.max(1,hh)}" rx="${j===bars.length-1?4:1}" fill="${b.color}" data-tip="${esc(b.name+' '+(o.tipL?o.tipL[i]:l)+': '+fv(v))}"/>`;acc+=hh})}
    else{const tw=bw*nb+3*(nb-1);bars.forEach((b,j)=>{const v=b.data[i]||0,hh=Math.max(v>0?2:0,ih*Math.max(0,v)/max);
      g+=`<rect class="bar" style="animation-delay:${i*30+j*15}ms" x="${cx-tw/2+j*(bw+3)}" y="${pt+ih-hh}" width="${bw}" height="${hh}" rx="${Math.min(5,bw/2)}" fill="${b.color}" data-tip="${esc(b.name+' '+(o.tipL?o.tipL[i]:l)+': '+fv(v))}"/>`})}
    if(i%every===0||i===n-1&&every===1)g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax"${i===o.hl?` style="font-weight:700;fill:${CSSV('--chu-dao')}"`:''}>${l}</text>`});
  lines.forEach(ln=>{const pts=ln.data.map((v,i)=>[pl+cw*i+cw/2,Y(v)]);
    g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="${ln.color}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"${ln.dash?' stroke-dasharray="5 4" style="animation:none;stroke-dashoffset:0"':''}/>`;
    if(n<=40)g+=pts.map((p,i)=>`<circle class="seg" cx="${p[0]}" cy="${p[1]}" r="${n>20?3:4}" fill="#fff" stroke="${ln.color}" stroke-width="2.5" data-tip="${esc(ln.name+' '+(o.tipL?o.tipL[i]:o.labels[i])+': '+fv(ln.data[i]))}"/>`).join('')});
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function donut(items,center,fv){fv=fv||short;
  const tot=sum(items,i=>i.value);
  if(!tot)return '<div class="empty">Chưa có số liệu</div>';
  const R=70,C=2*Math.PI*R;let off=0,segs='';
  items.forEach(it=>{const len=it.value/tot*C;
    segs+=`<circle class="seg" cx="100" cy="100" r="${R}" fill="none" stroke="${it.color}" stroke-width="26" stroke-dasharray="${Math.max(0,len-2)} ${C}" stroke-dashoffset="${-off}" transform="rotate(-90 100 100)" data-tip="${esc(it.label+': '+fv(it.value)+' ('+Math.round(it.value/tot*100)+'%)')}"/>`;off+=len});
  return `<div class="donut-wrap"><svg viewBox="0 0 200 200" class="chart">${segs}<text x="100" y="94" text-anchor="middle" class="ax" style="font-size:12px">${esc(center||'Tổng')}</text><text x="100" y="117" text-anchor="middle" style="font-size:19px;font-weight:800;fill:var(--text)">${esc(fv(tot))}</text></svg>
  <div class="dl">${items.map(it=>`<div><i style="background:${it.color}"></i>${esc(it.label)}<span>${esc(fv(it.value))}</span><em>${Math.round(it.value/tot*100)}%</em></div>`).join('')}</div></div>`}
function spark(data,color){if(!data||data.length<2)return '';const W=240,H=46,n=data.length,mx=Math.max(...data),mn=Math.min(0,...data),r=(mx-mn)||1;
  const pts=data.map((v,i)=>[i*W/(n-1),H-4-(v-mn)/r*(H-12)]),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L'),id='spg'+(++GID);
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`}
function heroArea(labels,d,fv,tipPre){
  const W=440,H=150,pl=8,pr=8,pt=12,pb=22,mx=Math.max(1,...d),mn=Math.min(...d),span=(mx-mn)||mx||1,n=d.length;
  const x=i=>pl+i*(W-pl-pr)/Math.max(1,n-1),y=v=>pt+(H-pt-pb)*(1-((v-mn)/span*0.85+0.08));
  const line='M'+d.map((v,i)=>x(i).toFixed(1)+','+y(v).toFixed(1)).join('L'),L=n-1,id='hg'+(++GID),every=Math.max(1,Math.ceil(n/7));
  return `<svg viewBox="0 0 ${W} ${H}" class="chart" style="overflow:visible"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs>
  <path d="${line}L${x(L)},${H-pb}L${x(0)},${H-pb}Z" fill="url(#${id})"/>
  <path d="${line}" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>
  ${d.map((v,i)=>`<circle class="seg" cx="${x(i)}" cy="${y(v)}" r="${i===L?5.5:n>20?2.5:3.5}" fill="${i===L?'#fff':'rgba(255,255,255,.75)'}" stroke="${i===L?CSSV('--chu-dao'):'none'}" stroke-width="3" data-tip="${esc((tipPre||'')+labels[i]+': '+fv(v))}"/>`).join('')}
  ${labels.map((l,i)=>(L-i)%every===0?`<text x="${x(i)}" y="${H-3}" text-anchor="middle" style="font-size:10.5px;fill:rgba(255,255,255,.75)">${l}</text>`:'').join('')}</svg>`}
function ring(p,big,small){const C=2*Math.PI*50,id='rg'+(++GID);p=Math.max(0,Math.min(100,p||0));
  return `<svg viewBox="0 0 120 120"><defs><linearGradient id="${id}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a7f3d0"/><stop offset="1" stop-color="#34d399"/></linearGradient></defs>
  <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="11"/>
  <circle class="ring-a" cx="60" cy="60" r="50" fill="none" stroke="url(#${id})" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C*p/100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
  <text x="60" y="60" text-anchor="middle" style="font-size:25px;font-weight:800;fill:#fff">${esc(big)}</text><text x="60" y="78" text-anchor="middle" style="font-size:9.5px;fill:rgba(255,255,255,.8)">${esc(small)}</text></svg>`}
function hbars(items,fv,empty){fv=fv||short;if(!items.length)return `<div class="empty">${empty||'Chưa có số liệu'}</div>`;
  const mx=Math.max(1,...items.map(i=>Math.abs(i.value)));
  return items.map((it,k)=>`<div class="hb" ${it.tip?`data-tip="${esc(it.tip)}"`:''}><div class="hb-h"><b>${esc(it.label)}</b><span>${esc(fv(it.value))}${it.sub?`<small>${esc(it.sub)}</small>`:''}</span></div><div class="bar"><i style="width:${Math.max(2,Math.abs(it.value)/mx*100)}%;background:${it.color||CSSV('--chu-dao')};animation-delay:${k*50}ms"></i></div></div>`).join('')}
function pill(cur,prev,cmp,inverse){
  if(prev===undefined||prev===null)return '<span class="pill na">Toàn bộ</span>';
  if(!prev)return `<span class="pill na" title="${esc(cmp||'')}">${cur?'Mới':'-'}</span>`;
  const p=(cur-prev)/Math.abs(prev)*100,up=p>=0,good=inverse?!up:up;
  return `<span class="pill ${good?'pu':'pd'}" title="${esc(cmp||'')}">${up?'▲':'▼'} ${Math.abs(p).toFixed(1).replace('.',',')}%</span>`}
function kc(o){return `<div class="card kc${o.data?'':' nospk'}" style="--c:${o.c};--cb:${o.cb}"><div class="kc-h"><span class="kc-ic">${o.icon}</span><span class="kc-l">${esc(o.label)}</span>${o.pill||''}</div><div class="kc-v">${o.text!=null?esc(o.text):`<span data-count="${Math.round(o.value)}">0</span>`}${o.unit?` <small>${esc(o.unit)}</small>`:''}</div><div class="kc-s">${o.sub||''}</div>${o.data?spark(o.data,o.c):''}</div>`}
function countUp(root){
  const els=[...root.querySelectorAll('[data-count]')],fin=()=>els.forEach(e=>e.textContent=fmt(+e.dataset.count));
  if(PRINT_ALL||matchMedia('(prefers-reduced-motion: reduce)').matches)return fin();
  const t0=performance.now(),D=900;els.forEach(e=>e.textContent='0');
  const step=t=>{const k=Math.min(1,(t-t0)/D),ez=1-Math.pow(1-k,3);els.forEach(e=>e.textContent=fmt(+e.dataset.count*ez));if(k<1)requestAnimationFrame(step)};
  requestAnimationFrame(step);setTimeout(fin,1300)}
const MED=['#f5b301','#9aa5b8','#cd7f32','#c3cbda','#c3cbda'];
function segBind(sel,cb){$$(sel+' button').forEach(b=>b.onclick=()=>{$$(sel+' button').forEach(x=>x.classList.toggle('on',x===b));cb(b.dataset.v)})}

/* =========================================================
   Ô GỢI Ý KHI GÕ (chọn từ danh mục)
   ========================================================= */
['scroll','resize'].forEach(ev=>window.addEventListener(ev,()=>$$('.ac-list.show').forEach(b=>b._place&&b._place()),true));
/* items(q) -> [{id, title, sub, right}] */
function acBind(inp,items,onPick){
  let box=document.createElement('div');box.className='ac-list';document.body.appendChild(box);let list=[],on=0;
  const place=()=>{const r=inp.getBoundingClientRect();box.style.left=r.left+'px';box.style.width=Math.max(260,r.width)+'px';
    const below=innerHeight-r.bottom;if(below<240&&r.top>below){box.style.top='';box.style.bottom=(innerHeight-r.top+4)+'px'}else{box.style.bottom='';box.style.top=(r.bottom+4)+'px'}};
  box._place=place;
  const draw=()=>{list=items(inp.value.trim()).slice(0,40);on=Math.min(on,Math.max(0,list.length-1));
    box.innerHTML=list.length?list.map((it,i)=>`<div class="ac-it${i===on?' on':''}" data-i="${i}"><div class="m"><b>${esc(it.title)}</b>${it.sub?`<small>${it.sub}</small>`:''}</div>${it.right||''}</div>`).join(''):'<div class="ac-none">Không tìm thấy</div>';
    box.classList.add('show');place()};
  const pick=i=>{const it=list[i];if(!it)return;box.classList.remove('show');on=0;onPick(it)};
  inp.setAttribute('autocomplete','off');
  inp.addEventListener('focus',()=>{on=0;draw()});
  inp.addEventListener('input',()=>{on=0;draw()});
  inp.addEventListener('blur',()=>setTimeout(()=>box.classList.remove('show'),150));
  inp.addEventListener('keydown',e=>{
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();if(!box.classList.contains('show'))draw();on=(on+(e.key==='ArrowDown'?1:-1)+list.length)%Math.max(1,list.length);draw();const el=box.querySelector('.ac-it.on');el&&el.scrollIntoView({block:'nearest'})}
    else if(e.key==='Enter'){e.preventDefault();if(list.length)pick(on)}
    else if(e.key==='Escape'){box.classList.remove('show')}});
  box.onmousedown=e=>{e.preventDefault();const it=e.target.closest('.ac-it');if(it)pick(+it.dataset.i)};
  return box}
const matchQ=(q,text)=>{const w=fold(q).split(/\s+/).filter(Boolean),t=fold(text);return w.every(x=>t.includes(x))};

/* =========================================================
   GHI / ĐỌC FILE EXCEL .xlsx (tự viết, không cần mạng)
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
      const name=enc.encode(f.name),raw=f.raw,crc=crc32(raw),data=f.comp||raw,method=f.comp?8:0;
      const h=new DataView(new ArrayBuffer(30));
      h.setUint32(0,0x04034b50,true);h.setUint16(4,20,true);h.setUint16(6,0x0800,true);h.setUint16(8,method,true);h.setUint16(10,tm,true);h.setUint16(12,dt,true);
      h.setUint32(14,crc,true);h.setUint32(18,data.length,true);h.setUint32(22,raw.length,true);h.setUint16(26,name.length,true);h.setUint16(28,0,true);
      parts.push(new Uint8Array(h.buffer),name,data);
      const c=new DataView(new ArrayBuffer(46));
      c.setUint32(0,0x02014b50,true);c.setUint16(4,20,true);c.setUint16(6,20,true);c.setUint16(8,0x0800,true);c.setUint16(10,method,true);c.setUint16(12,tm,true);c.setUint16(14,dt,true);
      c.setUint32(16,crc,true);c.setUint32(20,data.length,true);c.setUint32(24,raw.length,true);c.setUint16(28,name.length,true);c.setUint32(42,off,true);
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
<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="0.0%"/><numFmt numFmtId="167" formatCode="#,##0.00"/></numFmts>
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
  const hdrRow=s=>(s.title?1:0)+(s.subtitles||[]).length+((s.title||(s.subtitles||[]).length)?1:0)+1;
  function sheetXML(sh){
    const cols=sh.columns,nc=cols.length,last=colName(nc-1);let r=0;const rows=[],merges=[];
    if(sh.title){r++;rows.push(`<row r="${r}" ht="24" customHeight="1">${cell('A'+r,sh.title,'text',5)}</row>`);merges.push(`A${r}:${last}${r}`)}
    (sh.subtitles||[]).forEach(t=>{r++;rows.push(`<row r="${r}">${cell('A'+r,t,'text',9)}</row>`);merges.push(`A${r}:${last}${r}`)});
    if(r)r++;r++;const hr=r;
    rows.push(`<row r="${r}" ht="30" customHeight="1">${cols.map((c,i)=>cell(colName(i)+r,c.label,'text',1)).join('')}</row>`);
    for(const row of sh.rows){r++;rows.push(`<row r="${r}">${cols.map((c,i)=>{const t=c.type||'text';return cell(colName(i)+r,row[i],t,ST[t]||2)}).join('')}</row>`)}
    const lastData=r;
    if(sh.totals&&sh.rows.length){r++;
      rows.push(`<row r="${r}">${cols.map((c,i)=>{const ref=colName(i)+r;
        if(c.sum){const f=`SUBTOTAL(9,${colName(i)}${hr+1}:${colName(i)}${lastData})`;const v=sum(sh.rows,row=>row[i]);return `<c r="${ref}" s="${c.type==='num'?12:6}"><f>${f}</f><v>${v}</v></c>`}
        return cell(ref,i===0?(sh.totalLabel||'TỔNG CỘNG'):'','text',8)}).join('')}</row>`)}
    const colsXml=`<cols>${cols.map((c,i)=>`<col min="${i+1}" max="${i+1}" width="${c.width||({money:15,date:12,pct:9,num:10,int:9}[c.type]||18)}" customWidth="1"/>`).join('')}</cols>`;
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:${last}${Math.max(r,1)}"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="${hr}" topLeftCell="A${hr+1}" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>${colsXml}<sheetData>${rows.join('')}</sheetData>${sh.rows.length&&!sh.noFilter?`<autoFilter ref="A${hr}:${last}${lastData}"/>`:''}${merges.length?`<mergeCells count="${merges.length}">${merges.map(m=>`<mergeCell ref="${m}"/>`).join('')}</mergeCells>`:''}<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="${sh.portrait?'portrait':'landscape'}" fitToWidth="1" fitToHeight="0"/></worksheet>`}
  async function deflate(u8){try{if(!window.CompressionStream)return null;return new Uint8Array(await new Response(new Blob([u8]).stream().pipeThrough(new CompressionStream('deflate-raw'))).arrayBuffer())}catch(e){return null}}
  async function save(fileName,sheets){
    const used={};
    const names=sheets.map(s=>{let n=String(s.name||'Sheet').replace(/[\[\]:*?\/\\]/g,' ').slice(0,31)||'Sheet';let k=n,i=2;while(used[k.toLowerCase()])k=n.slice(0,28)+' '+i++;used[k.toLowerCase()]=1;return k});
    const files=[
      {name:'[Content_Types].xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>${sheets.map((_,i)=>`<Override PartName="/xl/worksheets/sheet${i+1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}</Types>`},
      {name:'_rels/.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`},
      {name:'xl/workbook.xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>${names.map((n,i)=>`<sheet name="${x(n)}" sheetId="${i+1}" r:id="rId${i+1}"/>`).join('')}</sheets><definedNames>${sheets.map((s,i)=>s.rows.length&&!s.noFilter?`<definedName name="_xlnm._FilterDatabase" localSheetId="${i}" hidden="1">'${x(names[i]).replace(/'/g,"''")}'!$A$${hdrRow(s)}:$${colName(s.columns.length-1)}$${hdrRow(s)+s.rows.length}</definedName>`:'').join('')}</definedNames></workbook>`},
      {name:'xl/_rels/workbook.xml.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${sheets.map((_,i)=>`<Relationship Id="rId${i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${i+1}.xml"/>`).join('')}<Relationship Id="rId${sheets.length+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`},
      {name:'xl/styles.xml',data:styles(APP.color)},
      ...sheets.map((s,i)=>({name:`xl/worksheets/sheet${i+1}.xml`,data:sheetXML(s)}))];
    for(const f of files){f.raw=enc.encode(f.data);f.comp=await deflate(f.raw)}
    const a=document.createElement('a');a.href=URL.createObjectURL(zip(files));a.download=fileName.replace(/\.xlsx$/i,'')+'.xlsx';document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},1500);
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
    const first=t.split('\n')[0],sep=(first.match(/;/g)||[]).length>(first.match(/,/g)||[]).length?';':(first.includes('\t')?'\t':',');
    const rows=[];let row=[],cur='',q=false;
    for(let i=0;i<t.length;i++){const ch=t[i];
      if(q){if(ch==='"'&&t[i+1]==='"'){cur+='"';i++}else if(ch==='"')q=false;else cur+=ch}
      else if(ch==='"')q=true;else if(ch===sep){row.push(cur);cur=''}else if(ch==='\n'){row.push(cur.replace(/\r$/,''));rows.push(row);row=[];cur=''}else cur+=ch}
    if(cur||row.length){row.push(cur);rows.push(row)}
    return rows}
  async function read(file){
    if(/\.csv$/i.test(file.name))return csvRows((await file.text()).replace(/^﻿/,''));
    if(/\.xls$/i.test(file.name))throw new Error('File .xls đời cũ chưa đọc được. Mở bằng Excel rồi lưu lại thành .xlsx nhé');
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
  return {save,read,csvRows};
})();
function exportAll(){const sh=APP.sheets().filter(s=>s);const c=co();
  sh.forEach(s=>{if(!s.subtitles)s.subtitles=[[c.name,c.addr].filter(Boolean).join(' · ')||APP.brand.name,'Xuất ngày '+vnDate(TODAYS)]});
  XLSX.save(APP.xlsName+' '+TODAYS,sh)}
/* ngày từ ô Excel: số serial, dd/mm/yyyy hoặc yyyy-mm-dd */
function impDate(v){if(v===''||v==null)return '';if(typeof v==='number'&&v>20000){const d=new Date(Date.UTC(1899,11,30)+Math.round(v)*864e5);return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate())}
  const s=String(v).trim();let m=s.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})/);if(m)return m[3]+'-'+pad(m[2])+'-'+pad(m[1]);
  m=s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);if(m)return m[1]+'-'+pad(m[2])+'-'+pad(m[3]);return ''}

/* =========================================================
   DANH MỤC DÙNG CHUNG: bảng + tìm + lọc + thêm/sửa/xóa + nhập Excel
   ========================================================= */
const CRUD={};
function crud(cfg){
  const C=cfg,el=()=>$(C.el),F={q:'',f:{}};CRUD[C.id]=C;
  const opts=f=>{const o=typeof f.opts==='function'?f.opts():f.opts||[];return o.map(x=>Array.isArray(x)?x:[x,x])};
  const autoCode=()=>{const k=C.code;let m=0;C.arr().forEach(r=>{const n=parseInt(String(r[k.k]).replace(/\D/g,''))||0;if(n>m)m=n});return k.pre+String(m+1).padStart(k.len||3,'0')};
  C.autoCode=autoCode;
  C.render=()=>{const e=el();
    if(!e.dataset.built){
      e.innerHTML=`${C.top?`<div id="${C.id}Top"></div>`:''}<div class="toolbar">
        <div class="search grow">${IC.search}<input class="inp" id="${C.id}Q" placeholder="${esc(C.ph||'Tìm kiếm...')}"></div>
        ${(C.filters||[]).map((f,i)=>`<select class="inp" id="${C.id}F${i}"></select>`).join('')}
        ${C.tools||''}
        ${C.imp!==false?`<button class="btn" onclick="CRUD['${C.id}'].importOpen()">${IC.imp}Nhập từ Excel</button>`:''}
        ${C.noAdd?'':`<button class="btn pri" onclick="CRUD['${C.id}'].open()">${IC.plus}Thêm ${esc(C.name)}</button>`}
      </div><div class="card"><div class="tbl-wrap" id="${C.id}T"></div></div>`;
      $('#'+C.id+'Q').oninput=e=>{F.q=e.target.value;pgReset(C.id);C.list()};
      (C.filters||[]).forEach((f,i)=>{$('#'+C.id+'F'+i).onchange=e=>{F.f[i]=e.target.value;pgReset(C.id);C.list()}});
      e.dataset.built=1}
    (C.filters||[]).forEach((f,i)=>{const s=$('#'+C.id+'F'+i),v=F.f[i]||'';const o=typeof f.opts==='function'?f.opts():f.opts;
      s.innerHTML=`<option value="">${esc(f.all)}</option>`+o.map(x=>Array.isArray(x)?`<option value="${esc(x[0])}">${esc(x[1])}</option>`:`<option>${esc(x)}</option>`).join('');s.value=v;if(s.value!==v)F.f[i]=''});
    if(C.top)$('#'+C.id+'Top').innerHTML=C.top();
    C.list()};
  C.rows=()=>C.arr().filter(r=>(!F.q||matchQ(F.q,C.search(r)))&&(C.filters||[]).every((f,i)=>!F.f[i]||f.test(r,F.f[i]))).sort(C.sort||(()=>0));
  C.list=()=>{const rows=C.rows(),box=$('#'+C.id+'T');if(!box)return;
    if(!rows.length){box.innerHTML=`<div class="empty">Chưa có ${esc(C.name)} nào phù hợp.<br><br>${C.noAdd?'':`<button class="btn pri" onclick="CRUD['${C.id}'].open()">${IC.plus}Thêm ${esc(C.name)}</button>`}</div>`;return}
    const off=pgOff(C.id);
    box.innerHTML=`<table><thead><tr>${C.stt!==false?'<th class="c">STT</th>':''}${C.cols.map(c=>`<th class="${c.cls||''}">${esc(c.l)}</th>`).join('')}<th></th></tr></thead><tbody>
      ${pgSlice(C.id,rows).map((r,i)=>`<tr>${C.stt!==false?`<td class="c muted">${off+i+1}</td>`:''}${C.cols.map(c=>`<td class="${c.cls||''}">${c.h(r)}</td>`).join('')}<td class="acts-c">${C.rowAct?C.rowAct(r):''}<button class="btn sm ico" title="Sửa" onclick="CRUD['${C.id}'].open('${r.id}')">${IC.edit}</button> <button class="btn sm ico danger" title="Xóa" onclick="CRUD['${C.id}'].del('${r.id}')">${IC.trash}</button></td></tr>`).join('')}
      </tbody>${C.foot?`<tfoot>${C.foot(rows)}</tfoot>`:''}</table>${pgBar(C.id,rows.length,C.list)}`};
  const inputOf=(f,v)=>{const id='cf_'+f.k,ro=f.ro?' readonly':'';
    if(f.t==='select')return `<select class="inp" id="${id}">${f.blank?`<option value="">${esc(f.blank)}</option>`:''}${opts(f).map(([a,b])=>`<option value="${esc(a)}"${String(a)===String(v)?' selected':''}>${esc(b)}</option>`).join('')}</select>`;
    if(f.t==='textarea')return `<textarea class="inp" id="${id}" rows="${f.rows||2}">${esc(v)}</textarea>`;
    if(f.t==='combo')return `<input class="inp" id="${id}" list="dl_${f.k}" value="${esc(v)}" placeholder="${esc(f.ph||'Chọn hoặc gõ mới')}"><datalist id="dl_${f.k}">${opts(f).map(([a])=>`<option>${esc(a)}</option>`).join('')}</datalist>`;
    if(f.t==='money')return `<input class="inp" id="${id}" inputmode="numeric" value="${v===''||v==null?'':fmt(v)}" onblur="this.value=this.value?fmt(parseNum(this.value)):''" placeholder="0"${ro}>`;
    if(f.t==='num')return `<input class="inp" id="${id}" type="number" step="any" value="${v??''}"${ro}>`;
    if(f.t==='date')return `<input class="inp" id="${id}" type="date" value="${esc(v||'')}">`;
    return `<input class="inp" id="${id}" value="${esc(v??'')}" placeholder="${esc(f.ph||'')}"${ro}>`};
  C.open=(id,pre)=>{const r=id?C.arr().find(x=>x.id===id):null,d=r||{};
    const val=f=>r?d[f.k]:(pre&&pre[f.k]!==undefined?pre[f.k]:f.k===(C.code&&C.code.k)?autoCode():typeof f.def==='function'?f.def():f.def??'');
    openModal(`<div class="modal-h"><h3>${r?'Sửa':'Thêm'} ${esc(C.name)}</h3><button class="x" onclick="closeModal()">×</button></div>
    <div class="modal-b">${C.formTop?C.formTop(r):''}<div class="fgrid">${C.fields.map(f=>`<div class="fg${f.w2?' w2':''}"><label class="f">${esc(f.l)}${f.req?' *':''}</label>${inputOf(f,val(f))}${f.hint?`<div class="fhint">${f.hint}</div>`:''}</div>`).join('')}</div>${C.formBottom?C.formBottom(r):''}</div>
    <div class="modal-f">${r?`<button class="btn danger" style="margin-right:auto" onclick="CRUD['${C.id}'].del('${r.id}')">${IC.trash}Xóa</button>`:''}<button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="CRUD['${C.id}'].saveForm('${r?r.id:''}')">${IC.save}Lưu</button></div>`,C.modal||'');
    if(C.afterOpen)C.afterOpen(r);
    setTimeout(()=>{const f=C.fields.find(f=>!f.ro&&f.t!=='select');const i=f&&$('#cf_'+f.k);if(i&&!r)i.focus()},60)};
  C.read=()=>{const o={};C.fields.forEach(f=>{const i=$('#cf_'+f.k);if(!i)return;let v=i.value;
      if(f.t==='money'||f.t==='num')v=v===''?'':parseNum(v);else v=String(v).trim();o[f.k]=v});return o};
  C.saveForm=id=>{const r=id?C.arr().find(x=>x.id===id):null,o=C.read();
    for(const f of C.fields)if(f.req&&(o[f.k]===''||o[f.k]==null))return toast('Chưa điền '+f.l,'err');
    if(C.code&&C.arr().some(x=>String(x[C.code.k]).toLowerCase()===String(o[C.code.k]).toLowerCase()&&x.id!==id))return toast((C.code.l||'Mã')+' '+o[C.code.k]+' đã có','err');
    if(C.validate){const m=C.validate(o,r);if(m)return toast(m,'err')}
    if(r){const old={...r};Object.assign(r,o);C.onSave&&C.onSave(r,false,old)}else{const n={id:C.id+uid(),...o};C.arr().push(n);C.onSave&&C.onSave(n,true)}
    save();closeModal();toast(r?'Đã cập nhật '+C.name:'Đã thêm '+C.name);rerender()};
  C.del=async id=>{const r=C.arr().find(x=>x.id===id);if(!r)return;
    const block=C.canDel&&C.canDel(r);if(block)return toast(block,'err');
    if(!await ask(`Xóa ${esc(C.name)} <b>${esc(C.label(r))}</b>?${C.delNote?'<br><br><span class="muted">'+C.delNote(r)+'</span>':''}`))return;
    C.set(C.arr().filter(x=>x.id!==id));C.onDel&&C.onDel(r);save();toast('Đã xóa '+C.name);rerender()};
  C.sheet=()=>({name:C.sheetName||C.title,title:(C.title||'').toUpperCase(),columns:C.xcols.map(c=>({label:c.l,type:c.t,width:c.w,sum:c.sum})),rows:C.arr().slice().sort(C.sort||(()=>0)).map(r=>C.xcols.map(c=>c.v(r))),totals:C.xcols.some(c=>c.sum)});
  /* nhập từ Excel */
  let IMP={items:[],mode:'update'};
  const impFields=()=>C.fields.filter(f=>!f.noimp);
  const conv=(f,v)=>{if(f.t==='money'||f.t==='num')return String(v).trim()===''?'':parseNum(v);if(f.t==='date')return impDate(v);
    if(f.t==='select'){const o=opts(f),s=fold(String(v).trim());const hit=o.find(([a,b])=>fold(a)===s||fold(b)===s);return hit?hit[0]:(f.imp?f.imp(String(v).trim()):'')}
    return String(v??'').trim()};
  C.parse=rows=>{rows=rows.filter(r=>r&&r.some(c=>String(c??'').trim()!==''));const fs=impFields();let hr=-1,map={};
    const key=C.need||fs.find(f=>f.req&&f.k!==(C.code&&C.code.k)).k;
    for(let r=0;r<Math.min(rows.length,20)&&hr<0;r++){const m={};rows[r].forEach((h,i)=>{const n=normH(h);const f=fs.find(f=>normH(f.l)===n||normH(f.k)===n||(f.alias||[]).includes(n));if(f&&m[f.k]===undefined)m[f.k]=i});if(m[key]!==undefined){hr=r;map=m}}
    if(hr<0)fs.forEach((f,i)=>map[f.k]=i);
    const out=[],seen=new Set();let mx=0;if(C.code)C.arr().forEach(x=>{const n=parseInt(String(x[C.code.k]).replace(/\D/g,''))||0;if(n>mx)mx=n});
    rows.slice(hr+1).forEach((r,k)=>{const o={row:hr+k+2,has:{}};fs.forEach(f=>{const raw=map[f.k]===undefined?'':r[map[f.k]];o.has[f.k]=map[f.k]!==undefined&&String(raw??'').trim()!=='';o[f.k]=o.has[f.k]?conv(f,raw):''});
      if(String(o[key]??'').trim()==='')return;
      if(C.code){const ck=C.code.k;if(!o[ck]){let c;do c=C.code.pre+String(++mx).padStart(C.code.len||3,'0');while(C.arr().some(x=>x[ck]===c)||seen.has(c));o[ck]=c}
        if(seen.has(o[ck]))o.st='dup';else{seen.add(o[ck]);o.old=C.arr().find(x=>String(x[ck]).toLowerCase()===String(o[ck]).toLowerCase());o.st=o.old?'old':'new'}}else o.st='new';
      out.push(o)});return out};
  C.importOpen=()=>{IMP={items:[],mode:'update'};pgReset(C.id+'imp');
    openModal(`<div class="modal-h"><h3>Nhập danh sách ${esc(C.name)} từ Excel</h3><button class="x" onclick="closeModal()">×</button></div>
    <div class="modal-b"><div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><label class="btn pri" style="cursor:pointer">${IC.up}Chọn file Excel<input type="file" id="impFile" accept=".xlsx,.csv" hidden></label>
      <button class="btn" onclick="CRUD['${C.id}'].impTemplate()">${IC.down}Tải file mẫu</button><span id="impName" class="muted" style="font-size:13px">Chưa chọn file</span></div>
      <p class="hint2">File cần có dòng tiêu đề với các cột: <b>${impFields().map(f=>esc(f.l)).join(', ')}</b>. Thiếu cột nào cũng được, trừ cột bắt buộc.${C.code?' Để trống mã thì tự đánh mã.':''}</p>
      <label class="f">Hoặc dán thẳng từ Excel: bôi đen vùng dữ liệu (có cả dòng tiêu đề), Ctrl + C rồi bấm vào ô dưới, Ctrl + V</label>
      <textarea class="inp" id="impPaste" rows="3" placeholder="Dán dữ liệu từ Excel vào đây..." style="font-size:12.5px"></textarea>
      <div class="imp-opt" id="impOpt" style="display:none"><b>Trùng mã đã có:</b><label><input type="radio" name="impMode" value="update" checked> Cập nhật theo danh sách mới</label><label><input type="radio" name="impMode" value="skip"> Bỏ qua, giữ bản cũ</label></div>
      <div id="impT" style="margin-top:10px"></div></div>
    <div class="modal-f"><span id="impSum" class="muted" style="margin-right:auto;align-self:center;font-size:13px"></span><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" id="impOk" disabled onclick="CRUD['${C.id}'].impApply()">Nhập</button></div>`,'wide');
    $$('[name=impMode]').forEach(r=>r.onchange=()=>{IMP.mode=r.value;C.impDraw()});
    $('#impFile').onchange=async e=>{const f=e.target.files[0];if(!f)return;e.target.value='';$('#impName').textContent='Đang đọc '+f.name+'...';
      try{IMP.items=C.parse(await XLSX.read(f));$('#impName').innerHTML=`<b>${esc(f.name)}</b> · đọc được ${IMP.items.length} dòng`}catch(err){IMP.items=[];$('#impName').textContent='Chưa đọc được file';toast(err.message||'Không đọc được file','err')}C.impDraw()};
    let tm;$('#impPaste').oninput=e=>{clearTimeout(tm);tm=setTimeout(()=>{const t=e.target.value;IMP.items=t.trim()?C.parse(t.replace(/\r/g,'').split('\n').map(l=>l.split('\t'))):[];$('#impName').textContent=t.trim()?'Dữ liệu dán vào · đọc được '+IMP.items.length+' dòng':'Chưa chọn file';C.impDraw()},250)};
    C.impDraw()};
  C.impDraw=()=>{const it=IMP.items,nNew=it.filter(o=>o.st==='new').length,nOld=it.filter(o=>o.st==='old').length,nDup=it.filter(o=>o.st==='dup').length,n=nNew+(IMP.mode==='update'?nOld:0),ok=$('#impOk');if(!ok)return;
    $('#impOpt').style.display=nOld?'':'none';$('#impSum').textContent=it.length?`${nNew} mới · ${nOld} trùng mã đã có${nDup?' · '+nDup+' trùng trong file (bỏ qua)':''}`:'';
    ok.disabled=!n;ok.textContent=n?`Nhập ${n} dòng`:'Nhập';const fs=impFields();
    const tag=o=>o.st==='dup'?'<span class="tag zero">Trùng, bỏ qua</span>':o.st==='new'?'<span class="tag ok">Thêm mới</span>':IMP.mode==='update'?'<span class="tag low">Cập nhật</span>':'<span class="tag gray">Bỏ qua</span>';
    $('#impT').innerHTML=it.length?`<div class="card" style="box-shadow:none"><div class="tbl-wrap"><table><thead><tr><th class="c">Dòng</th>${fs.map(f=>`<th>${esc(f.l)}</th>`).join('')}<th>Tình trạng</th></tr></thead><tbody>${pgSlice(C.id+'imp',it,10).map(o=>`<tr><td class="c">${o.row}</td>${fs.map(f=>{const v=o[f.k];const sh=f.t==='select'?(opts(f).find(x=>x[0]===v)||['',v])[1]:f.t==='money'||f.t==='num'?(v===''?'':fmtD(v)):f.t==='date'?vnDate(v):v;return `<td>${esc(sh)}</td>`}).join('')}<td>${tag(o)}</td></tr>`).join('')}</tbody></table></div>${pgBar(C.id+'imp',it.length,C.impDraw)}</div>`:'<div class="empty" style="padding:20px">Chọn file Excel hoặc dán dữ liệu để xem trước</div>'};
  C.impApply=()=>{let add=0,upd=0;
    IMP.items.forEach(o=>{if(o.st==='dup')return;
      if(o.old){if(IMP.mode!=='update')return;impFields().forEach(f=>{if(o.has[f.k])o.old[f.k]=o[f.k]});upd++}
      else{const n={id:C.id+uid()};C.fields.forEach(f=>{n[f.k]=o.has[f.k]||f.k===(C.code&&C.code.k)?o[f.k]:(typeof f.def==='function'?f.def():f.def??'')});if(C.onImport)C.onImport(n);C.arr().push(n);add++}});
    save();closeModal();pgReset(C.id);rerender();toast(`Đã thêm ${add} dòng${upd?', cập nhật '+upd+' dòng':''}. Nhớ bấm Lưu đè dữ liệu để ghi vào file.`)};
  C.impTemplate=()=>{const fs=impFields();XLSX.save('Mau nhap '+C.name,[{name:C.title||C.name,title:'DANH SÁCH '+String(C.title||C.name).toUpperCase(),subtitles:['Điền từ dòng dưới tiêu đề. Số tiền, số lượng ghi số, không cần chữ "đ". Ngày ghi dạng ngày của Excel.'],noFilter:true,
    columns:fs.map(f=>({label:f.l,type:f.t==='money'?'money':f.t==='num'?'num':f.t==='date'?'date':'text',width:f.xw||(f.t==='textarea'?30:f.k==='name'?30:16)})),
    rows:(C.sample||C.arr().slice(0,3).map(r=>fs.map(f=>f.t==='select'?(opts(f).find(x=>x[0]===r[f.k])||['',r[f.k]])[1]:r[f.k])))}])};
  return C}

/* =========================================================
   CÀI ĐẶT & DỮ LIỆU, HƯỚNG DẪN (dùng chung)
   ========================================================= */
function renderSettings(){
  const e=$('#page-settings');const c=co();
  e.innerHTML=`<div class="set-grid">
    <div class="card"><div class="set-ic" style="background:var(--cd-nhat);color:var(--cd-dam)">${IC.save}</div><h4>Lưu đè dữ liệu vào file</h4><p>Ghi toàn bộ dữ liệu hiện tại vào chính file .html này. Lần sau mở file là thấy dữ liệu mới nhất. Phím tắt: Ctrl + S.</p><button class="btn save" onclick="saveToFile()">${IC.save}Lưu đè dữ liệu</button> <button class="btn sm" onclick="forgetFile()" title="Chọn lại file để ghi đè">Chọn lại file</button></div>
    <div class="card"><div class="set-ic" style="background:#e7f6ee;color:#137a48">${IC.xl}</div><h4>Xuất Excel</h4><p>Tải file .xlsx gồm mọi bảng dữ liệu, mỗi bảng một sheet, tiêu đề in đậm, số là số để tính tiếp trong Excel.</p><button class="btn xl" onclick="exportAll()">${IC.xl}Xuất Excel</button></div>
    <div class="card"><div class="set-ic" style="background:var(--inbg);color:var(--in)">${IC.down}</div><h4>Sao lưu dữ liệu</h4><p>Tải toàn bộ dữ liệu về một file .json để cất giữ riêng, phòng khi file bị hỏng hoặc xóa nhầm.</p><button class="btn" onclick="backupJson()">Tải file sao lưu</button></div>
    <div class="card"><div class="set-ic" style="background:#e0f2fe;color:#0369a1">${IC.up}</div><h4>Khôi phục dữ liệu</h4><p>Chọn file .json đã sao lưu trước đó. Dữ liệu hiện tại sẽ được thay bằng dữ liệu trong file.</p><label class="btn" style="cursor:pointer">Chọn file sao lưu<input type="file" accept=".json" hidden onchange="restoreJson(this)"></label></div>
    <div class="card"><div class="set-ic" style="background:var(--warnbg);color:#b07a00">${IC.refresh}</div><h4>Nạp lại dữ liệu mẫu</h4><p>Thay dữ liệu hiện tại bằng dữ liệu mẫu để xem thử các tính năng.</p><button class="btn" onclick="resetSample()">Nạp dữ liệu mẫu</button></div>
    <div class="card"><div class="set-ic" style="background:var(--dangerbg);color:var(--danger)">${IC.trash}</div><h4>Xóa dữ liệu mẫu, dùng thật</h4><p>${esc(APP.clearNote||'Xóa sạch dữ liệu mẫu để nhập dữ liệu thật của mình từ đầu.')}</p><button class="btn danger" onclick="clearAll()">Xóa toàn bộ dữ liệu</button></div>
  </div>
  <div class="card" style="margin-top:16px;padding:20px">
    <h4 style="font-size:15px;margin-bottom:4px">Thông tin đơn vị</h4><p class="muted" style="font-size:13px;margin-bottom:14px">Điền một lần, phiếu và báo cáo in ra sẽ tự có tên đơn vị, địa chỉ.</p>
    <div class="fgrid">${(APP.companyFields||[['name','Tên đơn vị'],['phone','Điện thoại'],['addr','Địa chỉ']]).map(([k,l,ph])=>`<div class="fg"><label class="f">${esc(l)}</label><input class="inp" id="co_${k}" value="${esc(c[k]||'')}" placeholder="${esc(ph||'')}"></div>`).join('')}</div>
    <button class="btn pri" onclick="saveCompany()">${IC.save}Lưu thông tin</button></div>
  <div class="card" style="margin-top:16px;padding:18px">${(()=>{const size=JSON.stringify(S).length;return `<b>Dung lượng dữ liệu:</b> ${(size/1024).toFixed(0)} KB
    <div class="pbar" style="height:8px;margin:10px 0"><i style="width:${Math.min(100,size/5/1024/1024*100)}%;background:var(--chu-dao)"></i></div>
    <span class="muted">${APP.storeInfo?APP.storeInfo():''} Dữ liệu nằm ngay trong file <b>${esc(FILE_NAME())}</b> sau mỗi lần bấm Lưu đè dữ liệu. ${DIRTY?'<b style="color:#c26a00">Hiện có thay đổi chưa lưu vào file.</b>':''}</span>`})()}</div>`}
function saveCompany(){const o={};(APP.companyFields||[['name'],['phone'],['addr']]).forEach(([k])=>o[k]=$('#co_'+k).value.trim());S.company=o;save();toast('Đã lưu thông tin đơn vị')}
function backupJson(){const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(S)],{type:'application/json'}));a.download=`Sao luu ${APP.xlsName} ${TODAYS}.json`;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},1500);toast('Đã tải file sao lưu')}
function restoreJson(inp){const f=inp.files[0];if(!f)return;inp.value='';const fr=new FileReader();fr.onload=async()=>{try{const d=JSON.parse(fr.result);if(!APP.valid(d))throw 0;
  if(!await ask('Khôi phục dữ liệu từ file sao lưu? Dữ liệu hiện tại sẽ được thay thế.','Khôi phục'))return;S=d;if(APP.fix)APP.fix(S);save();toast('Khôi phục xong, nhớ bấm Lưu đè dữ liệu');rerender()}catch(_){toast('File sao lưu không hợp lệ','err')}};fr.readAsText(f)}
async function resetSample(){if(!await ask('Thay toàn bộ dữ liệu hiện tại bằng dữ liệu mẫu?','Nạp dữ liệu mẫu'))return;const c=S.company;S=APP.seed();S.sample=true;if(c)S.company=c;if(APP.fix)APP.fix(S);save();toast('Đã nạp dữ liệu mẫu');rerender()}
async function clearAll(){if(!await ask('Xóa SẠCH toàn bộ dữ liệu để nhập dữ liệu thật? Nên tải file sao lưu trước khi xóa.','Xóa toàn bộ'))return;const c=S.company;S=APP.empty();if(c)S.company=c;if(APP.fix)APP.fix(S);save();toast('Đã xóa toàn bộ dữ liệu, bắt đầu nhập dữ liệu thật');rerender()}
function renderGuide(){const g=APP.guide;
  $('#page-guide').innerHTML=`<div class="guide">
    <div class="card"><h3>File này dùng để làm gì?</h3>${g.what}</div>
    <div class="card"><h3>Các bước dùng</h3><div class="steps">${g.steps.map((s,i)=>`<div class="step"><span class="no">${i+1}</span><div><b>${esc(s[0])}</b><span>${s[1]}</span></div></div>`).join('')}</div></div>
    ${g.extra||''}
    <div class="card"><h3>Lưu đè dữ liệu và Xuất Excel</h3><ul>
      <li><b>Lưu đè dữ liệu</b> (góc trên bên phải, hoặc Ctrl + S): ghi dữ liệu vào chính file .html này. Lần đầu trên Chrome/Edge máy tính, trình duyệt hỏi chọn file: chọn đúng file đang mở, bấm Lưu và đồng ý thay thế. Từ lần sau bấm là lưu luôn.</li>
      <li>Trên điện thoại hoặc trình duyệt không cho ghi đè, file mới sẽ được tải về thư mục Tải xuống: từ đó dùng file vừa tải.</li>
      <li>Mọi thay đổi được giữ tạm trong trình duyệt. Lỡ đóng file chưa lưu, mở lại sẽ được hỏi có khôi phục bản tạm không.</li>
      <li><b>Xuất Excel</b>: tải file .xlsx gồm tất cả bảng, mỗi bảng một sheet, mở bằng Excel để lọc, tính tiếp hoặc gửi báo cáo.</li>
      <li>Nên thỉnh thoảng vào <b>Cài đặt & dữ liệu</b> tải file sao lưu .json để cất riêng.</li></ul></div>
    <div class="card"><h3>Trước khi dùng thật</h3><div class="note-w">Dữ liệu đang có là <b>dữ liệu mẫu</b> để xem thử. Khi dùng thật, vào <b>Cài đặt & dữ liệu</b> bấm <b>Xóa toàn bộ dữ liệu</b>, điền thông tin đơn vị, rồi nhập danh mục của mình (gõ tay hoặc nhập từ Excel).</div>${g.note||''}</div>
    <div class="gift">${IC.book}<span>File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel</span></div></div>`}

/* =========================================================
   BÁO CÁO QUẢN TRỊ TÌNH HÌNH DOANH NGHIỆP (bản gọn phần chính)
   ========================================================= */
const APP={
  key:'baocao_quantri_phongexcel_v1',fileName:'Báo cáo quản trị doanh nghiệp.html',color:'#00838F',xlsName:'Bao cao quan tri doanh nghiep',
  brand:{name:'Báo Cáo Quản Trị',sub:'Báo cáo nội bộ hằng tháng',icon:IC.chart},
  nav:['TỔNG QUAN',{id:'dashboard',label:'Dashboard',icon:'dash'},
    'NHẬP LIỆU',{id:'nhap',label:'Số liệu kế hoạch, thực hiện',icon:'edit'},{id:'cl',label:'Phân tích chênh lệch',icon:'target',badge:'w'},{id:'hd',label:'Kế hoạch hành động',icon:'flag',badge:1},
    'BÁO CÁO',{id:'bc',label:'Báo cáo quản trị (in)',icon:'file'},
    'HỆ THỐNG',{id:'cfg',label:'Cấu hình báo cáo',icon:'layers'},{id:'settings',label:'Cài đặt & dữ liệu',icon:'set'},{id:'guide',label:'Hướng dẫn',icon:'book'}],
  pages:{
    dashboard:['Dashboard','Chỉ số chính, xu hướng 12 tháng, chỉ tiêu đạt và chưa đạt',renderDashboard],
    nhap:['Số liệu kế hoạch, thực hiện','Nhập kế hoạch và thực hiện từng tháng: tài chính, dòng tiền, sản xuất, nhân sự, kho, bán hàng',renderNhap],
    cl:['Phân tích chênh lệch','Thực hiện so với kế hoạch, ghi nguyên nhân các chỉ tiêu lệch nhiều',renderCl],
    hd:['Kế hoạch hành động','Việc cần làm kỳ tới: người phụ trách, hạn hoàn thành, tiến độ',()=>HDC.render()],
    bc:['Báo cáo quản trị','Báo cáo nội bộ đủ 5 phần, in A4 hoặc xuất Excel',renderBc],
    cfg:['Cấu hình báo cáo','Kênh bán, nhóm sản phẩm, ngưỡng chênh lệch, thuế suất, tiền đầu kỳ',renderCfg],
    settings:['Cài đặt & dữ liệu','Lưu đè, sao lưu, khôi phục, thông tin doanh nghiệp',renderSettings],
    guide:['Hướng dẫn','Cách dùng file báo cáo quản trị',renderGuide]},
  quick:[{label:'Nhập số liệu',icon:'edit',fn:"go('nhap')"},{label:'Xem báo cáo',icon:'file',cls:'',fn:"go('bc')"}],
  valid:d=>d&&d.periods&&typeof d.periods==='object'&&Array.isArray(d.actions),
  fix:d=>{d.causes=d.causes||{};d.notes=d.notes||{};d.company=d.company||{};d.cfg=Object.assign({ch:[],sp:[],thr:5,thrPt:1,tax:20,cash0:0,cash0m:''},d.cfg||{})},
  seed,empty:()=>{const m=TODAYS.slice(0,7);return {periods:{[m]:{plan:{},act:{}}},actions:[],causes:{},notes:{},cfg:{ch:[{id:'c1',n:'Kênh 1'},{id:'c2',n:'Kênh 2'}],sp:[{id:'s1',n:'Nhóm sản phẩm 1'},{id:'s2',n:'Nhóm sản phẩm 2'}],thr:5,thrPt:1,tax:20,cash0:0,cash0m:m},ver:1}},
  companyFields:[['name','Tên doanh nghiệp'],['field','Ngành nghề'],['addr','Địa chỉ'],['ceo','Giám đốc'],['acc','Kế toán trưởng'],['preparer','Người lập báo cáo']],
  clearNote:'Xóa sạch số liệu mẫu để nhập kế hoạch, thực hiện của doanh nghiệp mình.',
  storeInfo:()=>`${Object.keys(S.periods).length} tháng số liệu · ${S.actions.length} việc hành động.`,
  badges:()=>{const lm=lastAct();if(!lm)return {};const P={t:'m',k:lm};return {cl:rowsOf(P).filter(r=>r.flag&&!S.causes[pk(P)+'|'+r.ind.k]).length,hd:S.actions.filter(a=>a.status!=='Hoàn thành'&&a.due&&a.due<TODAYS).length}},
  sbcard:()=>{const lm=lastAct();if(!lm)return {label:'Chưa có số liệu',value:'-',pct:0};const v=aggV({t:'m',k:lm});const p=v.plan.rev?v.act.rev/v.plan.rev*100:0;return {label:'Doanh thu '+mName(lm).toLowerCase(),value:tr(v.act.rev),pct:p,note:`Đạt ${pct(p,0)} kế hoạch · LNST ${tr(v.act.np)}`}},
  sheets:()=>sheetsAll(),
  guide:{what:`<p>File giúp lập <b>báo cáo quản trị nội bộ hằng tháng</b> gửi ban giám đốc, gồm 5 phần: <b>(1)</b> thông tin tổng quan và các chỉ số chính, <b>(2)</b> tình hình tài chính, kết quả kinh doanh, dòng tiền, <b>(3)</b> quản trị vận hành: sản xuất, nhân sự, kho, bán hàng, <b>(4)</b> phân tích chênh lệch thực hiện so với kế hoạch và nguyên nhân, <b>(5)</b> phương hướng hành động kỳ tới.</p><p>Đây là <b>bản gọn phần chính</b>: chỉ cần nhập số kế hoạch và thực hiện mỗi tháng, file tự tính các chỉ tiêu (lợi nhuận, biên lợi nhuận, dòng tiền, tỷ lệ nghỉ việc, số ngày tồn kho...), tự so với kế hoạch và kỳ trước, tự viết đoạn tóm tắt, cộng dồn theo quý và lũy kế năm.</p>`,
    steps:[['Cấu hình','Vào <b>Cấu hình báo cáo</b>: đặt tên kênh bán, nhóm sản phẩm, ngưỡng chênh lệch cần giải trình, thuế suất, tiền tồn đầu kỳ. Điền thông tin doanh nghiệp ở <b>Cài đặt</b>.'],
      ['Nhập kế hoạch','Vào <b>Số liệu kế hoạch, thực hiện</b>, chọn tháng, nhập cột Kế hoạch (có nút chép kế hoạch tháng trước cho nhanh).'],
      ['Nhập thực hiện','Hết tháng nhập cột Thực hiện. Các dòng có dấu = là chỉ tiêu tự tính, không cần nhập.'],
      ['Giải trình chênh lệch','Vào <b>Phân tích chênh lệch</b>: chỉ tiêu nào lệch quá ngưỡng sẽ được đánh dấu, ghi nguyên nhân ngay tại dòng đó.'],
      ['Lập việc kỳ tới','Vào <b>Kế hoạch hành động</b>: ghi việc, người phụ trách, hạn, theo dõi tiến độ.'],
      ['In báo cáo','Vào <b>Báo cáo quản trị</b>, chọn tháng / quý / lũy kế năm, viết nhận định chung rồi bấm In báo cáo hoặc Xuất Excel.']],
    extra:`<div class="card"><h3>Cách cộng số theo quý và lũy kế năm</h3><ul><li>Chỉ tiêu phát sinh (doanh thu, chi phí, sản lượng, tuyển mới...) thì cộng dồn các tháng.</li><li>Chỉ tiêu số dư (tồn kho, phải thu, nhân sự cuối kỳ, tiền tồn) lấy số của tháng cuối kỳ.</li><li>Chỉ tiêu tỷ lệ (phế phẩm, hiệu suất máy) lấy bình quân các tháng.</li><li>Đánh giá: chỉ tiêu càng cao càng tốt (doanh thu, lợi nhuận) thì thực hiện cao hơn kế hoạch là <span class="tag ok">Đạt</span>; chỉ tiêu chi phí, phế phẩm, nghỉ việc thì thấp hơn kế hoạch mới là đạt. Lệch quá ngưỡng thì cần ghi nguyên nhân.</li></ul></div>`,
    note:'<p class="muted" style="margin-top:8px">Đây là bản gọn phần chính. Doanh nghiệp có thêm chỉ tiêu riêng (chất lượng, an toàn, dự án...) thì bổ sung thêm khi chỉnh sửa file.</p>'}
};

/* ---------- danh mục chỉ tiêu ---------- */
const SECS={tc:'Tài chính, kết quả kinh doanh',dt:'Dòng tiền và công nợ',sx:'Sản xuất',ns:'Nhân sự',kho:'Kho',bh:'Bán hàng'};
const TAX=()=>(+S.cfg.tax||0)/100;
const PBT=v=>v.rev-v.cogs-v.sell-v.adm-v.fin+v.oth;
/* t: f cộng dồn, s số cuối kỳ, a bình quân, c tự tính. g: 1 càng cao càng tốt, -1 càng thấp càng tốt, 0 theo dõi */
const IND=[
  {k:'rev',l:'Doanh thu thuần',u:'tr',s:'tc',t:'f',g:1,key:1},{k:'cogs',l:'Giá vốn hàng bán',u:'tr',s:'tc',t:'f',g:-1},
  {k:'gp',l:'Lợi nhuận gộp',u:'tr',s:'tc',t:'c',g:1,f:v=>v.rev-v.cogs},{k:'gpm',l:'Biên lợi nhuận gộp',u:'%',s:'tc',t:'c',g:1,f:v=>v.rev?(v.rev-v.cogs)/v.rev*100:0},
  {k:'sell',l:'Chi phí bán hàng',u:'tr',s:'tc',t:'f',g:-1},{k:'adm',l:'Chi phí quản lý doanh nghiệp',u:'tr',s:'tc',t:'f',g:-1},{k:'fin',l:'Chi phí tài chính (lãi vay)',u:'tr',s:'tc',t:'f',g:-1},{k:'oth',l:'Thu nhập khác (ròng)',u:'tr',s:'tc',t:'f',g:0},
  {k:'pbt',l:'Lợi nhuận trước thuế',u:'tr',s:'tc',t:'c',g:1,f:PBT},{k:'tax',l:'Thuế TNDN',u:'tr',s:'tc',t:'c',g:0,f:v=>Math.max(0,PBT(v))*TAX()},
  {k:'np',l:'Lợi nhuận sau thuế',u:'tr',s:'tc',t:'c',g:1,key:1,f:v=>PBT(v)-Math.max(0,PBT(v))*TAX()},{k:'npm',l:'Biên lợi nhuận ròng',u:'%',s:'tc',t:'c',g:1,f:v=>v.rev?(PBT(v)-Math.max(0,PBT(v))*TAX())/v.rev*100:0},
  {k:'cin',l:'Tiền thu từ khách hàng',u:'tr',s:'dt',t:'f',g:1},{k:'cout',l:'Tiền chi hoạt động (NCC, lương, chi phí)',u:'tr',s:'dt',t:'f',g:-1},{k:'cinv',l:'Tiền chi đầu tư tài sản',u:'tr',s:'dt',t:'f',g:0},{k:'cfin',l:'Vay mới trừ trả nợ (ròng)',u:'tr',s:'dt',t:'f',g:0},
  {k:'ncf',l:'Dòng tiền thuần trong kỳ',u:'tr',s:'dt',t:'c',g:1,key:1,f:v=>v.cin-v.cout-v.cinv+v.cfin},{k:'cash',l:'Tiền tồn cuối kỳ',u:'tr',s:'dt',t:'c',g:1,f:(v,x)=>x.cash},
  {k:'ar',l:'Phải thu khách hàng cuối kỳ',u:'tr',s:'dt',t:'s',g:-1},{k:'ap',l:'Phải trả nhà cung cấp cuối kỳ',u:'tr',s:'dt',t:'s',g:0},
  {k:'pq',l:'Sản lượng sản xuất',u:'sp',s:'sx',t:'f',g:1,key:1},{k:'def',l:'Tỷ lệ phế phẩm',u:'%',s:'sx',t:'a',g:-1},{k:'oee',l:'Hiệu suất sử dụng máy',u:'%',s:'sx',t:'a',g:1},
  {k:'hc',l:'Nhân sự cuối kỳ',u:'người',s:'ns',t:'s',g:0,key:1},{k:'hire',l:'Tuyển mới',u:'người',s:'ns',t:'f',g:0},{k:'quit',l:'Nghỉ việc',u:'người',s:'ns',t:'f',g:-1},
  {k:'to',l:'Tỷ lệ nghỉ việc',u:'%',s:'ns',t:'c',g:-1,f:(v,x)=>x.hcAvg?v.quit/x.hcAvg*100:0},{k:'pay',l:'Quỹ lương',u:'tr',s:'ns',t:'f',g:-1},
  {k:'invIn',l:'Giá trị nhập kho',u:'tr',s:'kho',t:'f',g:0},{k:'invOut',l:'Giá trị xuất kho',u:'tr',s:'kho',t:'f',g:0},{k:'inv',l:'Tồn kho cuối kỳ',u:'tr',s:'kho',t:'s',g:-1,key:1},
  {k:'invd',l:'Số ngày tồn kho',u:'ngày',s:'kho',t:'c',g:-1,f:(v,x)=>v.cogs?v.inv/(v.cogs/x.days):0}];
function INDS(){return [...IND,...S.cfg.ch.map(c=>({k:'ch_'+c.id,l:'Doanh số kênh '+c.n,u:'tr',s:'bh',t:'f',g:1,grp:'ch',n:c.n})),...S.cfg.sp.map(c=>({k:'sp_'+c.id,l:'Doanh số '+c.n,u:'tr',s:'bh',t:'f',g:1,grp:'sp',n:c.n}))]}
const UN={tr:'tr đ','%':'%',sp:'sp','người':'người','ngày':'ngày'};
function fv(ind,v){if(v==null||isNaN(v))return '-';return ind.u==='%'?fmtD(v,1)+'%':ind.u==='ngày'?fmtD(v,1):fmt(v)}
const tr=v=>{v=+v||0;return Math.abs(v)>=1000?fmtD(v/1000,2)+' tỷ':fmt(v)+' tr'};

/* ---------- kỳ báo cáo và cộng số ---------- */
const months=()=>Object.keys(S.periods).sort();
const hasAct=m=>{const a=(S.periods[m]||{}).act||{};return Object.keys(a).some(k=>a[k]!==''&&a[k]!=null)};
const lastAct=()=>{const ms=months().filter(hasAct);return ms[ms.length-1]||months()[months().length-1]||''};
const qOf=m=>Math.floor((+m.slice(5)-1)/3)+1;
function monthsOf(P){const [y,mo]=P.k.split('-').map(Number);if(P.t==='m')return [P.k];if(P.t==='q'){const q=qOf(P.k),o=[];for(let i=(q-1)*3+1;i<=q*3;i++)o.push(y+'-'+pad(i));return o}const o=[];for(let i=1;i<=mo;i++)o.push(y+'-'+pad(i));return o}
function prevP(P){const [y,mo]=P.k.split('-').map(Number);if(P.t==='m'){const d=new Date(y,mo-2,1);return {t:'m',k:d.getFullYear()+'-'+pad(d.getMonth()+1)}}if(P.t==='q'){const d=new Date(y,mo-4,1);return {t:'q',k:d.getFullYear()+'-'+pad(d.getMonth()+1)}}return {t:'y',k:(y-1)+'-'+pad(mo)}}
const pk=P=>P.t+':'+P.k;
function pName(P){const [y,mo]=P.k.split('-').map(Number);return P.t==='m'?'Tháng '+mo+'/'+y:P.t==='q'?'Quý '+qOf(P.k)+'/'+y:'Lũy kế 01/'+y+' - '+pad(mo)+'/'+y}
const gv=(m,side,k)=>{const p=S.periods[m];if(!p||!p[side])return 0;const v=p[side][k];return v===''||v==null?0:+v};
function cashAt(m,side){let c=+S.cfg.cash0||0;months().forEach(x=>{if(x<=m&&(!S.cfg.cash0m||x>=S.cfg.cash0m))c+=gv(x,side,'cin')-gv(x,side,'cout')-gv(x,side,'cinv')+gv(x,side,'cfin')});return c}
function aggSide(ms,side){const L=INDS(),v={},use=ms.filter(m=>S.periods[m]);const last=use[use.length-1];
  L.forEach(i=>{if(i.t==='c')return;if(i.t==='f')v[i.k]=sum(use,m=>gv(m,side,i.k));else if(i.t==='s')v[i.k]=last?gv(last,side,i.k):0;else{const a=use.map(m=>gv(m,side,i.k)).filter(x=>x);v[i.k]=a.length?sum(a)/a.length:0}});
  const days=sum(use,m=>{const [y,mo]=m.split('-').map(Number);return new Date(y,mo,0).getDate()})||30;
  const hcs=use.map(m=>gv(m,side,'hc')).filter(x=>x);const x={days,cash:last?cashAt(last,side):0,hcAvg:hcs.length?sum(hcs)/hcs.length:0};
  L.forEach(i=>{if(i.t==='c')v[i.k]=i.f(v,x)});return v}
let AGG={},AGGU=null,AGGS=null;
function aggV(P){if(AGGU!==S.updated||AGGS!==S){AGG={};AGGU=S.updated;AGGS=S}const key=pk(P)+'|'+S.updated;if(AGG[key]&&AGG[key].S===S)return AGG[key].v;const ms=monthsOf(P),v={plan:aggSide(ms,'plan'),act:aggSide(ms.filter(hasAct),'act'),has:ms.some(hasAct)};AGG[key]={S,v};return v}
function judge(ind,pl,ac){const thr=ind.u==='%'?(+S.cfg.thrPt||1):(+S.cfg.thr||5);const d=ac-pl,rel=ind.u==='%'?d:(pl?d/Math.abs(pl)*100:(ac?100:0));const ht=pl?ac/pl*100:null;
  let cls='n',lb='Theo dõi';if(ind.g){const good=ind.g*rel;cls=good>=0?'g':Math.abs(rel)<thr?'w':'b';lb=good>=thr?'Vượt kế hoạch':good>=0?'Đạt':Math.abs(rel)<thr?'Gần đạt':'Chưa đạt'}
  return {d,rel,ht,cls,lb,flag:!!ind.g&&Math.abs(rel)>=thr}}
function rowsOf(P){const v=aggV(P),pv=aggV(prevP(P));return INDS().map(ind=>{const pl=v.plan[ind.k],ac=v.act[ind.k],j=judge(ind,pl,ac),pr=pv.has?pv.act[ind.k]:null;return {ind,pl,ac,pr,...j,flag:v.has&&j.flag,vsPrev:pr?(ac-pr)/Math.abs(pr)*100:null}})}
const htCell=r=>r.ht==null?'<span class="muted">-</span>':`<span class="ht ${r.cls}">${fmtD(r.ht,1)}%</span>`;
const tagJ=r=>`<span class="tag ${{g:'ok',w:'low',b:'zero',n:'gray'}[r.cls]}">${r.lb}</span>`;
const vsP=r=>r.vsPrev==null||Math.abs(r.vsPrev)>500?'<span class="muted">-</span>':`<span style="color:${(r.ind.g||1)*r.vsPrev>=0?'var(--in)':'var(--danger)'};font-weight:700">${r.vsPrev>=0?'▲':'▼'} ${fmtD(Math.abs(r.vsPrev),1)}%</span>`;

/* =========================================================
   DỮ LIỆU MẪU: 13 tháng thực hiện + kế hoạch tới cuối quý sau
   ========================================================= */
function seed(){
  let r=21;const rnd=()=>{r=(r*16807)%2147483647;return (r-1)/2147483646};const rr=(a,b)=>a+rnd()*(b-a);const R=x=>Math.round(x);
  const lastM=monthsBack(2)[0],ms=monthsBack(13,lastM+'-01'),fut=[1,2,3].map(i=>{const d=new Date(TODAY.getFullYear(),TODAY.getMonth()+i-1,1);return d.getFullYear()+'-'+pad(d.getMonth()+1)});
  const ch=[{id:'c1',n:'Đại lý, nhà phân phối'},{id:'c2',n:'Siêu thị, chuỗi bán lẻ'},{id:'c3',n:'Bán online'},{id:'c4',n:'Xuất khẩu'}],chs=[.46,.24,.1,.2];
  const sp=[{id:'s1',n:'Thùng, sọt nhựa'},{id:'s2',n:'Ghế, bàn nhựa'},{id:'s3',n:'Kệ, tủ nhựa'},{id:'s4',n:'Hộp thực phẩm'}],sps=[.34,.26,.22,.18];
  const SEA=[1.18,0.82,0.92,0.95,0.97,0.95,0.96,0.98,1.02,1.05,1.08,1.25];
  const periods={};let hcP=318,hcA=318,invP=10400,invA=10400;
  const all=[...ms,...fut.filter(m=>!ms.includes(m))];
  all.forEach((m,i)=>{const mo=+m.slice(5),isAct=ms.includes(m),isLast=m===lastM;
    const P={},A={};const rev=R(8600*SEA[mo-1]*(1+0.012*i)/10)*10;
    P.rev=rev;P.cogs=R(rev*0.71);P.sell=R(rev*0.056);P.adm=R(rev*0.047);P.fin=110;P.oth=20;
    ch.forEach((c,j)=>P['ch_'+c.id]=R(rev*chs[j]));sp.forEach((c,j)=>P['sp_'+c.id]=R(rev*sps[j]));
    P.cin=R(rev*1.02);P.cout=R(rev*0.9);P.cinv=mo%3===0?1500:0;P.cfin=mo%3===0?800:-300;P.ar=R(rev*1.35);P.ap=R(rev*0.62);
    P.pq=R(rev*56);P.def=2;P.oee=85;P.hire=10;P.quit=8;hcP=hcP+P.hire-P.quit;P.hc=hcP;P.pay=R(hcP*10.4);P.invIn=R(P.cogs*0.99);P.invOut=P.cogs;invP=invP+P.invIn-P.invOut;P.inv=R(invP);
    periods[m]={plan:P,act:{}};
    if(!isAct)return;
    const k=isLast?1:rr(0.93,1.06);ch.forEach((c,j)=>{let f=k*rr(0.95,1.05);if(isLast&&c.id==='c4')f=0.8;if(isLast&&c.id==='c3')f=1.24;if(isLast&&c.id==='c1')f=1.02;A['ch_'+c.id]=R(P['ch_'+c.id]*f)});
    A.rev=sum(ch,c=>A['ch_'+c.id]);let sa=0;const SPL=[1.02,0.9,1.04,1.02];sp.forEach((c,j)=>{const x=R(A.rev*sps[j]*(isLast?SPL[j]:rr(0.95,1.05)));A['sp_'+c.id]=x;sa+=x});const fx=A.rev/sa;sp.forEach(c=>A['sp_'+c.id]=R(A['sp_'+c.id]*fx));
    A.cogs=R(A.rev*(isLast?0.712:rr(0.702,0.72)));A.sell=R(A.rev*(isLast?0.064:rr(0.053,0.059)));A.adm=R(A.rev*(isLast?0.047:rr(0.045,0.049)));A.fin=isLast?112:R(rr(102,118));A.oth=R(rr(5,40));
    A.cin=R(A.rev*(isLast?1:rr(0.995,1.045)));A.cout=R(A.rev*(isLast?0.895:rr(0.885,0.915)));A.cinv=mo%3===0?R(rr(1350,1650)):R(rr(0,150));A.cfin=P.cfin;A.ar=R(P.ar*(isLast?1.07:rr(0.97,1.04)));A.ap=R(P.ap*rr(0.95,1.04));
    A.pq=R(P.pq*k*(isLast?1.004:rr(0.97,1.03)));A.def=isLast?3.1:+rr(1.6,2.4).toFixed(1);A.oee=isLast?79.5:+rr(80,88).toFixed(1);
    A.hire=isLast?13:R(rr(8,12));A.quit=isLast?16:R(rr(6,10));hcA=hcA+A.hire-A.quit;A.hc=hcA;A.pay=R(hcA*rr(10.3,10.55));A.invIn=R(A.cogs*rr(0.985,1.015));A.invOut=A.cogs;invA=invA+A.invIn-A.invOut;A.inv=R(invA);
    periods[m].act=A});
  const L='m:'+lastM,lm=+lastM.slice(5);
  const causes={[L+'|ch_c4']:'Đơn hàng xuất đi Nhật (2 container) khách đổi lịch tàu, lùi giao sang tuần đầu tháng sau.',[L+'|ch_c3']:'Chạy chương trình khuyến mãi giữa tháng trên sàn, doanh số online tăng mạnh.',
    [L+'|sell']:'Tăng chi phí khuyến mãi sàn và hỗ trợ trưng bày cho đại lý quý '+qOf(lastM)+'.',[L+'|def']:'Khuôn ghế mẫu G02 bị mòn, lô sản xuất giữa tháng phế phẩm cao. Đã đặt sửa khuôn.',
    [L+'|np']:'Chi phí bán hàng tăng và phế phẩm cao làm lợi nhuận thấp hơn kế hoạch dù doanh thu đạt.',[L+'|pbt']:'Như lợi nhuận sau thuế.',[L+'|ncf']:'Thu tiền khách hàng chậm hơn kế hoạch, công nợ đại lý tăng cuối quý.',[L+'|ar']:'Một số đại lý lớn xin gia hạn thanh toán 30 ngày cho đơn hàng quý '+qOf(lastM)+'.',[L+'|inv']:'Tồn thành phẩm tăng do đã sản xuất sẵn cho đơn xuất khẩu bị lùi giao.',[L+'|invd']:'Như tồn kho cuối kỳ, sẽ giảm khi giao đơn xuất khẩu đầu tháng sau.',[L+'|cash']:'Lũy kế thu tiền thấp hơn kế hoạch, vẫn đủ chi lương và trả nhà cung cấp.',[L+'|sp_s2']:'Dòng ghế G02 phải dừng một phần để xử lý khuôn, thiếu hàng giao.',[L+'|oee']:'Dừng máy ép số 3 hai ngày để sửa khuôn G02.',[L+'|quit']:'Công nhân nghỉ sau kỳ nhận lương, các công ty trong khu công nghiệp tuyển dụng cạnh tranh.',[L+'|to']:'Như trên, tập trung ở tổ ép và tổ đóng gói.'};
  const nm=fut[0],O=(d,t,a,o,s,res,per)=>({id:'a'+uid(),period:per||nm,area:a,task:t,owner:o,due:addDays(TODAYS,d),status:s,result:res||''});
  const actions=[O(10,'Hoàn tất giao đơn xuất khẩu Nhật bị lùi, chốt thêm 1 container cho tháng sau','Bán hàng','Trần Quốc Việt - P. Kinh doanh','Đang làm'),
    O(-3,'Sửa khuôn G02, kiểm tra 100% lô ghế đầu ca trong 2 tuần','Sản xuất','Lê Văn Nam - Quản đốc','Hoàn thành','Đã sửa khuôn, phế phẩm tuần này còn 1,9%'),
    O(20,'Rà soát lương công nhân vận hành, áp dụng thưởng chuyên cần','Nhân sự','Phạm Thu Hà - HCNS','Chưa bắt đầu'),
    O(12,'Thu hồi công nợ quá hạn trên 60 ngày của 3 đại lý lớn','Tài chính','Nguyễn Thị Lan - Kế toán trưởng','Đang làm'),
    O(25,'Giảm tồn kho bán thành phẩm, đưa số ngày tồn kho về dưới 40 ngày','Kho','Đỗ Minh Khoa - Thủ kho','Đang làm'),
    O(-5,'Đàm phán lại giá hạt nhựa PP với nhà cung cấp cho quý tới','Tài chính','Nguyễn Thị Lan - Kế toán trưởng','Đang làm'),
    O(-28,'Mở thêm 15 đại lý cấp 1 khu vực miền Tây','Bán hàng','Trần Quốc Việt - P. Kinh doanh','Hoàn thành','Đã ký 12 đại lý, còn 3 đang thương lượng',lastM),
    O(-20,'Bảo dưỡng tổng thể máy ép trước mùa cao điểm','Sản xuất','Lê Văn Nam - Quản đốc','Hoàn thành','Xong 8/8 máy',lastM)];
  const notes={[L]:`${mName(lastM)} doanh thu bám sát kế hoạch nhờ kênh đại lý và online tăng tốt, bù cho đơn xuất khẩu bị lùi. Lợi nhuận bị ảnh hưởng do chi phí bán hàng tăng và phế phẩm cao ở dòng ghế. Tháng tới tập trung giao bù đơn xuất khẩu, ổn định chất lượng khuôn và giữ chân công nhân.`};
  return {periods,actions,causes,notes,cfg:{ch,sp,thr:5,thrPt:1,tax:20,cash0:6500,cash0m:ms[0]},company:{name:'Công ty CP Sản xuất Thương mại Hưng Thịnh',field:'Sản xuất đồ nhựa gia dụng',addr:'KCN Tân Đô, Đức Hòa, Long An',ceo:'Nguyễn Hưng Thịnh',acc:'Nguyễn Thị Lan',preparer:'Võ Thị Mai Anh'},ver:1}}

/* =========================================================
   DASHBOARD
   ========================================================= */
let DM='';
function mSelect(id,val,onchg,all){const ms=months();return `<select class="inp" id="${id}">${(all?ms:ms).slice().reverse().map(m=>`<option value="${m}" ${m===val?'selected':''}>${mName(m)}${hasAct(m)?'':' (chưa có thực hiện)'}</option>`).join('')}</select>`}
function renderDashboard(){const e=$('#page-dashboard');DM=DM&&S.periods[DM]?DM:lastAct();
  if(!DM){e.innerHTML='<div class="card empty">Chưa có số liệu. <button class="btn pri" onclick="go(\'nhap\')">Nhập số liệu</button></div>';return}
  const P={t:'m',k:DM},v=aggV(P),pv=aggV(prevP(P)),rows=rowsOf(P),R=Object.fromEntries(rows.map(x=>[x.ind.k,x]));
  const ms12=monthsBack(12,DM+'-01').filter(m=>S.periods[m]),ser=k=>ms12.map(m=>hasAct(m)?aggV({t:'m',k:m}).act[k]:0),serP=k=>ms12.map(m=>aggV({t:'m',k:m}).plan[k]);
  const a=v.act,p=v.plan,ht=p.rev?a.rev/p.rev*100:0;
  e.innerHTML=`<div class="toolbar"><div class="mpick"><span class="muted">Kỳ xem</span>${mSelect('dM',DM)}</div><span class="chip">${esc(co().name||'')}</span><div style="margin-left:auto;display:flex;gap:8px"><button class="btn" onclick="go('nhap')">${IC.edit}Nhập số liệu</button><button class="btn pri" onclick="BP={t:'m',k:DM};go('bc')">${IC.file}Báo cáo tháng này</button></div></div>
  <div class="hero"><div><div class="h-k">DOANH THU ${mName(DM).toUpperCase()}</div><div class="h-v"><span data-count="${Math.round(a.rev)}">0</span> <small>tr đ</small></div>
    <div class="h-sub">${pill(a.rev,pv.has?pv.act.rev:0,'so với tháng trước')}<span>so với tháng trước · kế hoạch ${tr(p.rev)}</span></div>
    <div class="h-stats"><div><b>${tr(a.np)}</b><span>Lợi nhuận sau thuế</span></div><div><b>${fmtD(a.gpm,1)}%</b><span>Biên lợi nhuận gộp</span></div><div><b>${tr(a.ncf)}</b><span>Dòng tiền thuần</span></div></div>
    <div class="h-act"><button class="hbtn" onclick="go('nhap')">Nhập số liệu tháng</button><button class="hbtn ghost" onclick="go('cl')">Phân tích chênh lệch</button></div></div>
    <div class="h-chart"><div class="h-ct"><span>Doanh thu thực hiện ${ms12.length} tháng</span><span>Lũy kế năm <b>${tr(aggV({t:'y',k:DM}).act.rev)}</b></span></div>${heroArea(ms12.map(mLabel),ser('rev'),x=>fmt(x)+' tr đ','Doanh thu ')}</div>
    <div class="h-ring">${ring(Math.min(100,ht),Math.round(ht)+'%','đạt kế hoạch')}<b>Doanh thu đạt ${pct(ht,1)}</b><span>${ht>=100?'Vượt':'Thiếu'} ${tr(Math.abs(a.rev-p.rev))} so với kế hoạch</span></div></div>
  <div class="kc-grid">${[['np',IC.trend,'#16a36a','var(--inbg)'],['ncf',IC.wallet,CSSV('--chu-dao'),'var(--cd-nhat)'],['hc',IC.users,'#8b5cf6','#f1ebff'],['inv',IC.box,'#f0663a','var(--outbg)']].map(([k,ic,c,cb])=>{const x=R[k];
    const sub={np:`Kế hoạch ${tr(x.pl)} · biên ${fmtD(a.npm,1)}%`,ncf:`Tiền tồn cuối kỳ ${tr(a.cash)}`,hc:`Tuyển ${fmt(a.hire)} · nghỉ ${fmt(a.quit)} · tỷ lệ nghỉ ${fmtD(a.to,1)}%`,inv:`Số ngày tồn kho ${fmtD(a.invd,0)} ngày`}[k];
    return kc({c,cb,icon:ic,label:{np:'Lợi nhuận sau thuế',ncf:'Dòng tiền thuần',hc:'Nhân sự',inv:'Tồn kho'}[k],value:x.ac,unit:UN[x.ind.u],pill:x.ind.g?`<span class="pill ${x.cls==='g'?'pu':x.cls==='w'?'na':'pd'}">${x.ht==null?'-':fmtD(x.ht,0)+'% KH'}</span>`:`<span class="pill na">${x.vsPrev==null?'-':(x.vsPrev>=0?'+':'')+fmtD(x.vsPrev,1)+'%'}</span>`,sub,data:ser(k)})}).join('')}</div>
  <div class="grid-a"><div class="card"><div class="card-h"><h3>Doanh thu và lợi nhuận: thực hiện so với kế hoạch</h3><div class="r legend"><span><i style="background:var(--chu-dao)"></i>Doanh thu thực hiện</span><span><i style="background:#94a3b8;height:3px;vertical-align:3px"></i>Doanh thu kế hoạch</span><span><i style="background:#16a36a;height:3px;vertical-align:3px"></i>LNST</span></div></div>
    <div class="card-b"><div class="msum">${[['rev','Doanh thu lũy kế'],['np','LNST lũy kế'],['ncf','Dòng tiền thuần lũy kế']].map(([k,l])=>{const y=aggV({t:'y',k:DM});return `<div><small>${l} năm ${DM.slice(0,4)}</small><b>${tr(y.act[k])}</b> <span class="muted" style="font-size:12px">· ${y.plan[k]?pct(y.act[k]/y.plan[k]*100,0):'-'} KH</span></div>`}).join('')}</div>
    ${combo({labels:ms12.map(mLabel),tipL:ms12.map(mName),bars:[{name:'Doanh thu thực hiện',data:ser('rev'),color:CSSV('--chu-dao')}],lines:[{name:'Doanh thu kế hoạch',data:serP('rev'),color:'#94a3b8',dash:1},{name:'Lợi nhuận sau thuế',data:ser('np'),color:'#16a36a'}],hl:ms12.length-1,H:290,fv:x=>fmt(x)+' tr đ',ax:x=>fmt(x)})}</div></div>
    <div class="card"><div class="card-h"><h3>Cơ cấu chi phí ${mName(DM).toLowerCase()}</h3></div><div class="card-b">${donut([['Giá vốn','cogs','#00838F'],['Bán hàng','sell','#26C6DA'],['Quản lý','adm','#8b5cf6'],['Tài chính','fin','#e6a100']].map(([l,k,c])=>({label:l,value:a[k],color:c})),'Tổng chi phí',x=>tr(x))}</div></div></div>
  <div class="grid-b"><div class="card"><div class="card-h"><h3>Bảng điểm chỉ tiêu</h3><span class="hint">so với kế hoạch</span></div><div class="card-b">${rows.filter(x=>x.ind.g&&!x.ind.grp&&['rev','gp','np','sell','adm','ncf','pq','def','oee','quit','inv','ar'].includes(x.ind.k)).map(x=>`<div class="sc-li"><span><i class="dot ${x.cls}"></i><b>${esc(x.ind.l)}</b></span><span class="muted">${fv(x.ind,x.ac)} / ${fv(x.ind,x.pl)}</span>${htCell(x)}</div>`).join('')}</div></div>
    <div class="card"><div class="card-h"><h3>Doanh số theo kênh bán</h3></div><div class="card-b">${hbars(rows.filter(x=>x.ind.grp==='ch').sort((x,y)=>y.ac-x.ac).map(x=>({label:x.ind.n,value:x.ac,sub:x.ht==null?'':pct(x.ht,0)+' KH',color:x.cls==='b'?'#e0384a':x.cls==='w'?'#e6a100':CSSV('--chu-dao'),tip:x.ind.n+': thực hiện '+fmt(x.ac)+' / kế hoạch '+fmt(x.pl)+' tr đ'})),tr)}
      <div class="sec-t" style="margin-top:14px">Theo nhóm sản phẩm</div>${hbars(rows.filter(x=>x.ind.grp==='sp').sort((x,y)=>y.ac-x.ac).map(x=>({label:x.ind.n,value:x.ac,sub:x.ht==null?'':pct(x.ht,0)+' KH',color:CSSV('--cd-sang')})),tr)}</div></div>
    <div class="card"><div class="card-h"><h3>Việc hành động đang mở</h3><div class="r"><button class="btn sm" onclick="go('hd')">Tất cả</button></div></div><div class="card-b">${(()=>{const op=S.actions.filter(x=>x.status!=='Hoàn thành').sort((x,y)=>(x.due||'').localeCompare(y.due||''));return op.length?op.slice(0,6).map(x=>{const late=x.due&&x.due<TODAYS;return `<div class="li"><span class="tico ${late?'bad':''}">${IC.flag}</span><div class="meta"><b>${esc(x.task)}</b><small>${esc(x.owner)} · hạn ${vnDate(x.due)}</small></div><div class="v">${late?'<span class="tag zero">Trễ hạn</span>':`<span class="tag ${x.status==='Đang làm'?'info':'gray'}">${esc(x.status)}</span>`}</div></div>`}).join(''):'<div class="empty">Không có việc đang mở</div>'})()}</div></div></div>
  <div class="grid-c"><div class="card"><div class="card-h"><h3>Nhân sự: tuyển mới, nghỉ việc</h3><div class="r legend"><span><i style="background:#16a36a"></i>Tuyển mới</span><span><i style="background:#e0384a"></i>Nghỉ việc</span></div></div><div class="card-b">${combo({labels:ms12.map(mLabel),tipL:ms12.map(mName),bars:[{name:'Tuyển mới',data:ser('hire'),color:'#16a36a'},{name:'Nghỉ việc',data:ser('quit'),color:'#e0384a'}],H:240,W:560,bw:14,fv:x=>fmt(x)+' người',ax:x=>fmt(x)})}<div class="muted" style="font-size:12.5px">Nhân sự cuối kỳ: <b style="color:var(--text)">${fmt(a.hc)} người</b> · quỹ lương ${tr(a.pay)}</div></div></div>
    <div class="card"><div class="card-h"><h3>Sản xuất: sản lượng và phế phẩm</h3><div class="r legend"><span><i style="background:var(--cd-sang)"></i>Thực hiện</span><span><i style="background:#94a3b8;height:3px;vertical-align:3px"></i>Kế hoạch</span></div></div><div class="card-b">${combo({labels:ms12.map(mLabel),tipL:ms12.map(mName),bars:[{name:'Sản lượng',data:ser('pq'),color:CSSV('--cd-sang')}],lines:[{name:'Kế hoạch',data:serP('pq'),color:'#94a3b8',dash:1}],H:240,W:560,bw:20,fv:x=>fmt(x)+' sp',ax:short})}<div class="muted" style="font-size:12.5px">Phế phẩm ${mName(DM).toLowerCase()}: <b style="color:${R.def.cls==='g'?'var(--in)':'var(--danger)'}">${fmtD(a.def,1)}%</b> (kế hoạch ${fmtD(p.def,1)}%) · hiệu suất máy ${fmtD(a.oee,1)}%</div></div></div></div>`;
  $('#dM').onchange=ev=>{DM=ev.target.value;renderDashboard()};countUp(e)}

/* =========================================================
   NHẬP SỐ LIỆU KẾ HOẠCH, THỰC HIỆN
   ========================================================= */
let NM='';
function renderNhap(){const e=$('#page-nhap');NM=NM&&S.periods[NM]?NM:(months().find(m=>!hasAct(m)&&m>=lastAct())&&hasAct(lastAct())?lastAct():lastAct())||TODAYS.slice(0,7);if(!S.periods[NM])S.periods[NM]={plan:{},act:{}};
  const P={t:'m',k:NM},L=INDS();
  e.innerHTML=`<div class="toolbar"><div class="mpick"><span class="muted">Tháng</span>${mSelect('nM',NM)}</div><button class="btn" onclick="addMonth()">${IC.plus}Thêm tháng</button><button class="btn" onclick="copyPlan()">${IC.refresh}Chép kế hoạch tháng trước</button>
    <span class="muted" style="font-size:12.5px">Đơn vị tiền: triệu đồng. Dòng có dấu = là tự tính.</span><div style="margin-left:auto;display:flex;gap:8px"><button class="btn xl" onclick="exportMonth()">${IC.xl}Xuất Excel tháng này</button></div></div>
    <div class="ent">${Object.keys(SECS).map(s=>`<div class="card"><div class="card-h"><h3>${SECS[s]}</h3>${s==='bh'?'<span class="hint" id="bhChk"></span>':''}</div><div class="tbl-wrap"><table class="it"><thead><tr><th>Chỉ tiêu</th><th class="c">ĐVT</th><th class="n">Kế hoạch</th><th class="n">Thực hiện</th><th class="n">% TH/KH</th></tr></thead><tbody>
      ${L.filter(i=>i.s===s).map(i=>`<tr class="${i.t==='c'?'calc':''}"><td class="lbl">${esc(i.l)}${i.t==='s'?' <small class="muted">(cuối kỳ)</small>':i.t==='a'?' <small class="muted">(bình quân)</small>':''}</td><td class="c muted">${UN[i.u]}</td>
        ${i.t==='c'?`<td class="n" data-c="plan|${i.k}"></td><td class="n" data-c="act|${i.k}"></td>`:['plan','act'].map(sd=>`<td><input class="cin" data-k="${sd}|${i.k}" inputmode="decimal" value="${(S.periods[NM][sd]||{})[i.k]===undefined||(S.periods[NM][sd]||{})[i.k]===''?'':i.u==='%'?String((S.periods[NM][sd]||{})[i.k]).replace('.',','):fmt((S.periods[NM][sd]||{})[i.k])}" placeholder="-"></td>`).join('')}
        <td class="n" data-h="${i.k}"></td></tr>`).join('')}</tbody></table></div></div>`).join('')}
    <div class="card" style="grid-column:1/-1"><div class="card-h"><h3>Nhận định chung của ${mName(NM).toLowerCase()}</h3><span class="hint">Hiện trong phần I của báo cáo tháng</span></div><div class="card-b"><textarea class="rnote" id="nNote" placeholder="Tóm tắt tình hình tháng, điểm nổi bật, rủi ro...">${esc(S.notes['m:'+NM]||'')}</textarea></div></div></div>`;
  $('#nM').onchange=ev=>{NM=ev.target.value;renderNhap()};
  let tm;$('#nNote').oninput=ev=>{clearTimeout(tm);tm=setTimeout(()=>{S.notes['m:'+NM]=ev.target.value;save()},400)};
  $$('#page-nhap .cin').forEach(inp=>{const [sd,k]=inp.dataset.k.split('|'),ind=L.find(x=>x.k===k);
    inp.oninput=()=>{const t=inp.value.trim();S.periods[NM][sd]=S.periods[NM][sd]||{};S.periods[NM][sd][k]=t===''?'':ind.u==='%'?parseNum(t.replace(',','.')):parseNum(t);save();nhapCalc()};
    inp.onblur=()=>{const v=S.periods[NM][sd][k];inp.value=v===''||v==null?'':ind.u==='%'?String(v).replace('.',','):fmt(v)};
    inp.onkeydown=ev=>{if(ev.key==='Enter'){ev.preventDefault();const all=$$('#page-nhap .cin'),i=all.indexOf(inp);if(all[i+1])all[i+1].focus()}}});
  nhapCalc()}
function nhapCalc(){const rows=rowsOf({t:'m',k:NM}),v=aggV({t:'m',k:NM});
  rows.forEach(r=>{['plan','act'].forEach(sd=>{const c=$(`[data-c="${sd}|${r.ind.k}"]`);if(c)c.textContent=sd==='act'&&!v.has?'-':fv(r.ind,sd==='plan'?r.pl:r.ac)});const h=$(`[data-h="${r.ind.k}"]`);if(h)h.innerHTML=v.has&&r.ind.g?htCell(r):'<span class="muted">-</span>'});
  const chk=$('#bhChk');if(chk){const c=sum(S.cfg.ch,x=>gv(NM,'act','ch_'+x.id)),rv=gv(NM,'act','rev');chk.innerHTML=hasAct(NM)&&rv&&Math.abs(c-rv)>1?`<span style="color:var(--danger)">Tổng các kênh ${fmt(c)} khác doanh thu ${fmt(rv)}</span>`:hasAct(NM)?'<span style="color:var(--in)">Tổng các kênh khớp doanh thu</span>':''}}
async function addMonth(){const ms=months(),l=ms[ms.length-1]||TODAYS.slice(0,7),[y,m]=l.split('-').map(Number),d=new Date(y,m,1),k=d.getFullYear()+'-'+pad(d.getMonth()+1);
  if(!await ask(`Thêm <b>${mName(k)}</b> và chép kế hoạch của ${mName(l).toLowerCase()} sang?`,'Thêm tháng',false))return;S.periods[k]={plan:{...((S.periods[l]||{}).plan||{})},act:{}};NM=k;save();renderNhap();toast('Đã thêm '+mName(k))}
async function copyPlan(){const [y,m]=NM.split('-').map(Number),d=new Date(y,m-2,1),k=d.getFullYear()+'-'+pad(d.getMonth()+1);if(!S.periods[k])return toast('Chưa có số liệu '+mName(k),'err');
  if(!await ask(`Chép toàn bộ cột Kế hoạch của ${mName(k)} sang ${mName(NM)}? Kế hoạch đang có của ${mName(NM)} sẽ bị thay.`,'Chép kế hoạch',false))return;S.periods[NM].plan={...S.periods[k].plan};save();renderNhap();toast('Đã chép kế hoạch')}

/* =========================================================
   PHÂN TÍCH CHÊNH LỆCH
   ========================================================= */
let CP=null,CF=true;
function perPicker(id,P){return `<div class="seg" id="${id}T">${[['m','Tháng'],['q','Quý'],['y','Lũy kế năm']].map(([v,l])=>`<button data-v="${v}" class="${P.t===v?'on':''}">${l}</button>`).join('')}</div><div class="mpick">${mSelect(id+'M',P.k)}</div>`}
function bindPicker(id,P,cb){segBind('#'+id+'T',v=>{P.t=v;cb()});$('#'+id+'M').onchange=ev=>{P.k=ev.target.value;cb()}}
function renderCl(){const e=$('#page-cl');CP=CP||{t:'m',k:lastAct()};if(!CP.k)return e.innerHTML='<div class="card empty">Chưa có số liệu</div>';
  const rows=rowsOf(CP),v=aggV(CP),key=pk(CP),fl=rows.filter(r=>r.flag),list=CF?fl:rows.filter(r=>v.has);
  e.innerHTML=`<div class="toolbar">${perPicker('cl',CP)}<label style="display:flex;gap:6px;align-items:center;font-size:13px"><input type="checkbox" id="clF" ${CF?'checked':''}> Chỉ chỉ tiêu lệch từ ${S.cfg.thr}% (hoặc ${S.cfg.thrPt} điểm %) trở lên</label><div style="margin-left:auto;display:flex;gap:8px"><button class="btn xl" onclick="exportCl()">${IC.xl}Xuất Excel</button></div></div>
  ${v.has?'':'<div class="note-w">Kỳ này chưa có số thực hiện. Vào Số liệu kế hoạch, thực hiện để nhập.</div>'}
  <div class="kc-grid">${kc({c:'#16a36a',cb:'var(--inbg)',icon:IC.check,label:'Chỉ tiêu đạt, vượt',text:rows.filter(r=>v.has&&r.cls==='g').length,unit:'chỉ tiêu',sub:'Theo hướng tốt của từng chỉ tiêu'})}${kc({c:'#e6a100',cb:'var(--warnbg)',icon:IC.clock,label:'Gần đạt',text:rows.filter(r=>v.has&&r.cls==='w').length,unit:'chỉ tiêu',sub:'Lệch dưới ngưỡng'})}${kc({c:'#e0384a',cb:'var(--dangerbg)',icon:IC.warn,label:'Chưa đạt',text:rows.filter(r=>v.has&&r.cls==='b').length,unit:'chỉ tiêu',sub:'Lệch xấu quá ngưỡng'})}${kc({c:CSSV('--chu-dao'),cb:'var(--cd-nhat)',icon:IC.edit,label:'Cần ghi nguyên nhân',text:fl.filter(r=>!S.causes[key+'|'+r.ind.k]).length+' / '+fl.length,unit:'',sub:'Lệch quá ngưỡng, chưa giải trình'})}</div>
  <div class="card"><div class="card-h"><h3>Thực hiện so với kế hoạch · ${pName(CP)}</h3><span class="hint">Nguyên nhân tự lưu khi gõ, hiện trong phần IV của báo cáo</span></div><div class="tbl-wrap"><table><thead><tr><th>Nhóm</th><th>Chỉ tiêu</th><th class="n">Kế hoạch</th><th class="n">Thực hiện</th><th class="n">Chênh lệch</th><th class="n">% TH/KH</th><th>Đánh giá</th><th style="min-width:280px">Nguyên nhân</th><th></th></tr></thead><tbody>
  ${list.map(r=>`<tr style="${r.flag?'background:#fffdf5':''}"><td class="muted" style="font-size:12px">${SECS[r.ind.s]}</td><td><b>${esc(r.ind.l)}</b><br><small class="muted">${UN[r.ind.u]}</small></td><td class="n">${fv(r.ind,r.pl)}</td><td class="n"><b>${fv(r.ind,r.ac)}</b></td><td class="n" style="color:${r.cls==='b'?'var(--danger)':r.cls==='g'?'var(--in)':'inherit'}">${r.d>=0?'+':''}${fv(r.ind,r.d).replace('%',r.ind.u==='%'?' điểm':'%')}</td><td class="n">${htCell(r)}</td><td>${tagJ(r)}</td>
    <td><textarea class="rnote" style="min-height:38px;padding:6px 9px;font-size:12.5px" data-c="${r.ind.k}" placeholder="${r.flag?'Ghi nguyên nhân...':''}">${esc(S.causes[key+'|'+r.ind.k]||'')}</textarea></td><td><button class="btn sm" title="Tạo việc hành động cho chỉ tiêu này" onclick="newAct('${r.ind.k}')">${IC.flag}</button></td></tr>`).join('')||`<tr><td colspan="9" class="empty">${v.has?'Không có chỉ tiêu nào lệch quá ngưỡng':'Chưa có số thực hiện'}</td></tr>`}</tbody></table></div></div>`;
  bindPicker('cl',CP,renderCl);$('#clF').onchange=ev=>{CF=ev.target.checked;renderCl()};
  $$('#page-cl textarea[data-c]').forEach(t=>{let tm;t.oninput=()=>{clearTimeout(tm);tm=setTimeout(()=>{const k=key+'|'+t.dataset.c,val=t.value.trim();if(val)S.causes[k]=val;else delete S.causes[k];save()},400)}})}
function newAct(k){const ind=INDS().find(i=>i.k===k),[y,m]=CP.k.split('-').map(Number),d=new Date(y,m,1),nk=d.getFullYear()+'-'+pad(d.getMonth()+1);
  const area={tc:'Tài chính',dt:'Tài chính',sx:'Sản xuất',ns:'Nhân sự',kho:'Kho',bh:'Bán hàng'}[ind.s];HDC.open(null,{period:nk,area,kpi:k,task:'Cải thiện '+ind.l.toLowerCase()+': ',due:ymd(new Date(y,m+1,0))})}
function exportCl(){const rows=rowsOf(CP).filter(r=>aggV(CP).has),key=pk(CP);
  XLSX.save('Phan tich chenh lech '+pName(CP),[{name:'Chênh lệch',title:'PHÂN TÍCH CHÊNH LỆCH THỰC HIỆN SO VỚI KẾ HOẠCH',subtitles:[co().name||'',pName(CP)+' · Đơn vị tiền: triệu đồng'],columns:[{label:'Nhóm',width:22},{label:'Chỉ tiêu',width:34},{label:'ĐVT',width:8},{label:'Kế hoạch',type:'num',width:13},{label:'Thực hiện',type:'num',width:13},{label:'Chênh lệch',type:'num',width:13},{label:'% TH/KH',type:'pct',width:10},{label:'Đánh giá',width:14},{label:'Nguyên nhân',width:60}],
    rows:rows.map(r=>[SECS[r.ind.s],r.ind.l,UN[r.ind.u],r.pl,r.ac,r.d,r.ht,r.lb,S.causes[key+'|'+r.ind.k]||''])}])}

/* =========================================================
   KẾ HOẠCH HÀNH ĐỘNG
   ========================================================= */
const AREAS=['Tài chính','Bán hàng','Sản xuất','Nhân sự','Kho','Khác'],AST=['Chưa bắt đầu','Đang làm','Hoàn thành','Tạm dừng'];
const actLate=a=>a.status!=='Hoàn thành'&&a.due&&a.due<TODAYS;
const actTag=a=>actLate(a)?'<span class="tag zero">Trễ hạn</span>':`<span class="tag ${{'Hoàn thành':'ok','Đang làm':'info','Chưa bắt đầu':'gray','Tạm dừng':'low'}[a.status]||'gray'}">${esc(a.status)}</span>`;
const HDC=crud({id:'hd',el:'#page-hd',name:'việc',title:'Kế hoạch hành động',arr:()=>S.actions,set:a=>S.actions=a,ph:'Tìm việc, người phụ trách...',need:'task',
  label:r=>r.task,search:r=>r.task+' '+r.owner+' '+r.area+' '+r.result,sort:(a,b)=>(b.period||'').localeCompare(a.period||'')||(a.due||'').localeCompare(b.due||''),
  fields:[{k:'period',l:'Kỳ thực hiện',t:'select',opts:()=>{const s=new Set(months());const ms=months();const l=ms[ms.length-1];if(l){const [y,m]=l.split('-').map(Number);for(let i=1;i<=3;i++){const d=new Date(y,m-1+i,1);s.add(d.getFullYear()+'-'+pad(d.getMonth()+1))}}return [...s].sort().reverse().map(m=>[m,mName(m)])},def:()=>{const d=new Date(TODAY.getFullYear(),TODAY.getMonth(),1);return d.getFullYear()+'-'+pad(d.getMonth()+1)},imp:v=>{const d=impDate(v);return d?d.slice(0,7):String(v).slice(0,7)}},
    {k:'area',l:'Lĩnh vực',t:'select',opts:AREAS,def:'Bán hàng'},{k:'task',l:'Việc cần làm',t:'textarea',req:1,w2:1},{k:'kpi',l:'Chỉ tiêu liên quan',t:'select',blank:'(không gắn chỉ tiêu)',opts:()=>INDS().map(i=>[i.k,i.l]),noimp:1},
    {k:'owner',l:'Người phụ trách',t:'combo',opts:()=>[...new Set(S.actions.map(a=>a.owner).filter(Boolean))]},{k:'due',l:'Hạn hoàn thành',t:'date'},{k:'status',l:'Tiến độ',t:'select',opts:AST,def:'Chưa bắt đầu'},{k:'result',l:'Kết quả / ghi chú',t:'textarea',w2:1}],
  filters:[{all:'Mọi kỳ',opts:()=>[...new Set(S.actions.map(a=>a.period))].sort().reverse().map(m=>[m,mName(m)]),test:(r,v)=>r.period===v},{all:'Mọi lĩnh vực',opts:AREAS,test:(r,v)=>r.area===v},{all:'Mọi tiến độ',opts:[...AST,'Trễ hạn'],test:(r,v)=>v==='Trễ hạn'?actLate(r):r.status===v}],
  top:()=>{const c=f=>S.actions.filter(f).length;return `<div class="kc-grid">${kc({c:CSSV('--chu-dao'),cb:'var(--cd-nhat)',icon:IC.flag,label:'Tổng số việc',text:S.actions.length,unit:'việc',sub:'Mọi kỳ'})}${kc({c:'#0369a1',cb:'#e0f2fe',icon:IC.clock,label:'Đang làm',text:c(a=>a.status==='Đang làm'&&!actLate(a)),unit:'việc',sub:'Còn trong hạn'})}${kc({c:'#e0384a',cb:'var(--dangerbg)',icon:IC.warn,label:'Trễ hạn',text:c(actLate),unit:'việc',sub:'Quá hạn chưa xong'})}${kc({c:'#16a36a',cb:'var(--inbg)',icon:IC.check,label:'Hoàn thành',text:c(a=>a.status==='Hoàn thành'),unit:'việc',sub:pct(S.actions.length?c(a=>a.status==='Hoàn thành')/S.actions.length*100:0,0)+' tổng số việc'})}</div>`},
  cols:[{l:'Kỳ',cls:'nw',h:r=>mName(r.period||'')},{l:'Lĩnh vực',h:r=>`<span class="tag cat">${esc(r.area)}</span>`},{l:'Việc cần làm',h:r=>`<div style="min-width:260px"><b style="font-weight:600">${esc(r.task)}</b>${r.kpi?`<br><small class="muted">Chỉ tiêu: ${esc((INDS().find(i=>i.k===r.kpi)||{}).l||'')}</small>`:''}</div>`},
    {l:'Phụ trách',h:r=>`<div style="min-width:140px">${esc(r.owner)}</div>`},{l:'Hạn',cls:'nw',h:r=>vnDate(r.due)},{l:'Tiến độ',h:actTag},{l:'Kết quả',h:r=>`<div style="min-width:180px" class="muted">${esc(r.result)}</div>`}]});

/* =========================================================
   BÁO CÁO QUẢN TRỊ (bản in)
   ========================================================= */
let BP=null;
function narr(P){const v=aggV(P),a=v.act,p=v.plan,pv=aggV(prevP(P)),R=Object.fromEntries(rowsOf(P).map(x=>[x.ind.k,x])),o=[];
  const cmp=(k)=>pv.has&&pv.act[k]?((a[k]-pv.act[k])/Math.abs(pv.act[k])*100):null,cs=x=>x==null?'':`, ${x>=0?'tăng':'giảm'} ${fmtD(Math.abs(x),1)}% so với kỳ trước`;
  o.push(`Doanh thu thuần đạt <b>${tr(a.rev)}</b>, bằng <b>${pct(R.rev.ht||0)}</b> kế hoạch${cs(cmp('rev'))}.`);
  o.push(`Lợi nhuận sau thuế <b>${tr(a.np)}</b> (${pct(R.np.ht||0)} kế hoạch), biên lợi nhuận gộp ${fmtD(a.gpm,1)}% so với kế hoạch ${fmtD(p.gpm,1)}%.`);
  o.push(`Dòng tiền thuần trong kỳ <b>${tr(a.ncf)}</b>, tiền tồn cuối kỳ ${tr(a.cash)}; phải thu khách hàng ${tr(a.ar)}.`);
  o.push(`Sản xuất ${fmt(a.pq)} sản phẩm (${pct(R.pq.ht||0)} kế hoạch), phế phẩm ${fmtD(a.def,1)}%. Nhân sự cuối kỳ ${fmt(a.hc)} người, tỷ lệ nghỉ việc ${fmtD(a.to,1)}%. Tồn kho ${tr(a.inv)}, tương đương ${fmtD(a.invd,0)} ngày.`);
  const bad=rowsOf(P).filter(r=>r.cls==='b'&&r.flag).map(r=>r.ind.l.toLowerCase());if(bad.length)o.push(`Chỉ tiêu chưa đạt cần lưu ý: ${bad.slice(0,6).join(', ')}.`);
  return o}
function secTable(P,s,opt){const rows=rowsOf(P).filter(r=>r.ind.s===s&&(!opt||opt(r)));return `<table><thead><tr><th>Chỉ tiêu</th><th class="c">ĐVT</th><th class="n">Kế hoạch</th><th class="n">Thực hiện</th><th class="n">% TH/KH</th><th class="n">Kỳ trước</th><th class="n">So kỳ trước</th></tr></thead><tbody>${rows.map(r=>`<tr${r.ind.t==='c'&&['gp','pbt','np','ncf','cash'].includes(r.ind.k)?' style="font-weight:700;background:#f7fafb"':''}><td>${esc(r.ind.grp?r.ind.n:r.ind.l)}</td><td class="c muted">${UN[r.ind.u]}</td><td class="n">${fv(r.ind,r.pl)}</td><td class="n"><b>${fv(r.ind,r.ac)}</b></td><td class="n">${r.ind.g?htCell(r):'-'}</td><td class="n muted">${fv(r.ind,r.pr)}</td><td class="n">${vsP(r)}</td></tr>`).join('')}</tbody></table>`}
function renderBc(){const e=$('#page-bc');BP=BP||{t:'m',k:lastAct()};if(!BP.k)return e.innerHTML='<div class="card empty">Chưa có số liệu</div>';
  const P=BP,v=aggV(P),a=v.act,R=Object.fromEntries(rowsOf(P).map(x=>[x.ind.k,x])),c=co(),key=pk(P),[y,mo]=P.k.split('-').map(Number);
  const ms12=monthsBack(12,P.k+'-01').filter(m=>S.periods[m]),ser=k=>ms12.map(m=>hasAct(m)?aggV({t:'m',k:m}).act[k]:0);
  const nxt=(()=>{const d=new Date(y,mo,1);return d.getFullYear()+'-'+pad(d.getMonth()+1)})(),acts=S.actions.filter(x=>P.t==='m'?x.period===nxt:x.period>P.k).sort((x,z)=>(x.due||'').localeCompare(z.due||''));
  const fl=rowsOf(P).filter(r=>r.flag);
  e.innerHTML=`<div class="toolbar">${perPicker('bc',P)}<div style="margin-left:auto;display:flex;gap:8px"><button class="btn xl" onclick="exportBc()">${IC.xl}Xuất Excel</button><button class="btn pri" onclick="window.print()">${IC.print}In báo cáo</button></div></div>
  ${v.has?'':'<div class="note-w noprint">Kỳ này chưa có số thực hiện, báo cáo chỉ có cột kế hoạch.</div>'}
  <div class="rep"><div class="page-a4">
    <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:14px"><div><b>${esc(c.name||'TÊN DOANH NGHIỆP')}</b><br><span class="muted">${esc(c.addr||'')}</span></div><div style="text-align:right" class="muted">Báo cáo nội bộ<br>Đơn vị tiền: triệu đồng</div></div>
    <h1 class="rt">BÁO CÁO QUẢN TRỊ TÌNH HÌNH DOANH NGHIỆP</h1><div class="rsub">${pName(P)}</div>
    <h2><span class="no">I</span>Thông tin tổng quan</h2>
    <div class="rinfo"><div><small>Doanh nghiệp</small><b>${esc(c.name||'')}</b></div><div><small>Ngành nghề</small><b>${esc(c.field||'')}</b></div><div><small>Kỳ báo cáo</small><b>${pName(P)}</b></div><div><small>Người lập</small><b>${esc(c.preparer||'')}</b></div><div><small>Ngày lập</small><b>${vnDate(TODAYS)}</b></div><div><small>Gửi</small><b>Ban giám đốc</b></div></div>
    <div class="rkpi">${[['rev','Doanh thu'],['np','Lợi nhuận sau thuế'],['ncf','Dòng tiền thuần'],['pq','Sản lượng'],['hc','Nhân sự']].map(([k,l])=>{const x=R[k];return `<div><small>${l}</small><b>${x.ind.u==='tr'?tr(x.ac):fmt(x.ac)+' '+UN[x.ind.u]}</b><span class="ht ${x.ind.g?x.cls:'n'}">${x.ind.g&&x.ht!=null?pct(x.ht,0)+' kế hoạch':x.vsPrev!=null?(x.vsPrev>=0?'+':'')+fmtD(x.vsPrev,1)+'% kỳ trước':''}</span></div>`}).join('')}</div>
    <h3>Tóm tắt</h3><div class="sumtxt"><ul>${narr(P).map(s=>`<li>${s}</li>`).join('')}</ul></div>
    <h3>Nhận định chung</h3><textarea class="rnote" id="bcNote" placeholder="Ghi nhận định chung của kỳ (tự lưu)...">${esc(S.notes[key]||'')}</textarea>
    <h2><span class="no">II</span>Tình hình tài chính, kết quả kinh doanh</h2>
    <h3>1. Kết quả kinh doanh</h3>${secTable(P,'tc')}
    <h3>2. Dòng tiền và công nợ</h3>${secTable(P,'dt')}
    <h3>3. Xu hướng 12 tháng</h3><div class="legend" style="margin-bottom:6px"><span><i style="background:var(--chu-dao)"></i>Doanh thu</span><span><i style="background:#16a36a;height:3px;vertical-align:3px"></i>Lợi nhuận sau thuế</span><span><i style="background:#8b5cf6;height:3px;vertical-align:3px"></i>Dòng tiền thuần</span></div>${combo({labels:ms12.map(mLabel),tipL:ms12.map(mName),bars:[{name:'Doanh thu',data:ser('rev'),color:CSSV('--chu-dao')}],lines:[{name:'LNST',data:ser('np'),color:'#16a36a'},{name:'Dòng tiền thuần',data:ser('ncf'),color:'#8b5cf6'}],H:250,fv:x=>fmt(x)+' tr đ',ax:x=>fmt(x)})}
    <h2><span class="no">III</span>Quản trị vận hành</h2>
    <h3>1. Sản xuất</h3>${secTable(P,'sx')}<h3>2. Nhân sự</h3>${secTable(P,'ns')}
    <h3>3. Kho</h3>${secTable(P,'kho')}
    <h3>4. Bán hàng</h3><div class="sec-t">Theo kênh bán</div>${secTable(P,'bh',r=>r.ind.grp==='ch')}<div class="sec-t" style="margin-top:10px">Theo nhóm sản phẩm</div>${secTable(P,'bh',r=>r.ind.grp==='sp')}
    <div style="margin-top:12px">${combo({labels:S.cfg.ch.map(x=>x.n.length>16?x.n.slice(0,15)+'…':x.n),tipL:S.cfg.ch.map(x=>x.n),bars:[{name:'Kế hoạch',data:S.cfg.ch.map(x=>R['ch_'+x.id].pl),color:'#cbd5e1'},{name:'Thực hiện',data:S.cfg.ch.map(x=>R['ch_'+x.id].ac),color:CSSV('--chu-dao')}],H:220,bw:40,fv:x=>fmt(x)+' tr đ',ax:x=>fmt(x)})}</div>
    <h2><span class="no">IV</span>Phân tích chênh lệch và nguyên nhân</h2>
    ${fl.length?`<table><thead><tr><th>Chỉ tiêu</th><th class="n">Kế hoạch</th><th class="n">Thực hiện</th><th class="n">Chênh lệch</th><th>Đánh giá</th><th>Nguyên nhân</th></tr></thead><tbody>${fl.map(r=>`<tr><td><b>${esc(r.ind.l)}</b></td><td class="n">${fv(r.ind,r.pl)}</td><td class="n">${fv(r.ind,r.ac)}</td><td class="n">${r.d>=0?'+':''}${fv(r.ind,r.d).replace('%',' điểm')}${r.ind.u!=='%'?' ('+(r.rel>=0?'+':'')+fmtD(r.rel,1)+'%)':''}</td><td>${tagJ(r)}</td><td>${esc(S.causes[key+'|'+r.ind.k]||'')||'<span class="muted noprint">Chưa ghi nguyên nhân</span>'}</td></tr>`).join('')}</tbody></table>`:'<p class="muted">Không có chỉ tiêu nào lệch quá ngưỡng '+S.cfg.thr+'%.</p>'}
    <h2><span class="no">V</span>Phương hướng hành động kỳ tới</h2>
    ${acts.length?`<table><thead><tr><th>STT</th><th>Lĩnh vực</th><th>Việc cần làm</th><th>Người phụ trách</th><th>Hạn</th><th>Tiến độ</th></tr></thead><tbody>${acts.map((x,i)=>`<tr><td class="c">${i+1}</td><td>${esc(x.area)}</td><td>${esc(x.task)}</td><td>${esc(x.owner)}</td><td class="nw">${vnDate(x.due)}</td><td>${actTag(x)}</td></tr>`).join('')}</tbody></table>`:'<p class="muted">Chưa có việc cho kỳ tới. Thêm ở mục Kế hoạch hành động.</p>'}
    <div class="signs"><div><b>Người lập</b><i>(Ký, họ tên)</i><br><br><br><br>${esc(c.preparer||'')}</div><div><b>Kế toán trưởng</b><i>(Ký, họ tên)</i><br><br><br><br>${esc(c.acc||'')}</div><div><b>Giám đốc</b><i>(Ký, họ tên)</i><br><br><br><br>${esc(c.ceo||'')}</div></div>
  </div></div>`;
  bindPicker('bc',P,renderBc);let tm;$('#bcNote').oninput=ev=>{clearTimeout(tm);tm=setTimeout(()=>{S.notes[key]=ev.target.value;save()},400)}}
function exportBc(){const P=BP,rows=rowsOf(P),key=pk(P),c=co();
  const sh=(name,title,f)=>({name,title,subtitles:[c.name||'',pName(P)+' · Đơn vị tiền: triệu đồng'],columns:[{label:'Chỉ tiêu',width:38},{label:'ĐVT',width:8},{label:'Kế hoạch',type:'num',width:13},{label:'Thực hiện',type:'num',width:13},{label:'% TH/KH',type:'pct',width:10},{label:'Kỳ trước',type:'num',width:13},{label:'So kỳ trước',type:'pct',width:11}],noFilter:true,
    rows:rows.filter(f).map(r=>[r.ind.grp?r.ind.n:r.ind.l,UN[r.ind.u],r.pl,r.ac,r.ind.g?r.ht:'',r.pr,r.vsPrev])});
  XLSX.save('Bao cao quan tri '+pName(P),[{name:'I. Tổng quan',title:'BÁO CÁO QUẢN TRỊ: THÔNG TIN TỔNG QUAN',subtitles:[c.name||'',pName(P)],columns:[{label:'Nội dung',width:30},{label:'Thông tin',width:110}],noFilter:true,
      rows:[['Doanh nghiệp',c.name],['Ngành nghề',c.field],['Kỳ báo cáo',pName(P)],['Người lập',c.preparer],['Ngày lập',vnDate(TODAYS)],...narr(P).map((s,i)=>['Tóm tắt '+(i+1),s.replace(/<[^>]+>/g,'')]),['Nhận định chung',S.notes[key]||'']]},
    sh('II. Tài chính','TÌNH HÌNH TÀI CHÍNH, KẾT QUẢ KINH DOANH, DÒNG TIỀN',r=>r.ind.s==='tc'||r.ind.s==='dt'),
    sh('III. Vận hành','QUẢN TRỊ VẬN HÀNH: SẢN XUẤT, NHÂN SỰ, KHO, BÁN HÀNG',r=>['sx','ns','kho','bh'].includes(r.ind.s)),
    {name:'IV. Chênh lệch',title:'PHÂN TÍCH CHÊNH LỆCH VÀ NGUYÊN NHÂN',subtitles:[c.name||'',pName(P)],columns:[{label:'Chỉ tiêu',width:34},{label:'Kế hoạch',type:'num',width:13},{label:'Thực hiện',type:'num',width:13},{label:'Chênh lệch',type:'num',width:13},{label:'% TH/KH',type:'pct',width:10},{label:'Đánh giá',width:14},{label:'Nguyên nhân',width:60}],rows:rows.filter(r=>r.flag).map(r=>[r.ind.l,r.pl,r.ac,r.d,r.ht,r.lb,S.causes[key+'|'+r.ind.k]||''])},
    {name:'V. Hành động',title:'PHƯƠNG HƯỚNG HÀNH ĐỘNG KỲ TỚI',subtitles:[c.name||'',pName(P)],columns:actCols(),rows:S.actions.filter(x=>x.period>P.k).map(actRow)}])}

/* =========================================================
   CẤU HÌNH
   ========================================================= */
function renderCfg(){const e=$('#page-cfg'),g=S.cfg;
  const lst=(k,lb)=>`<div class="card" style="padding:20px"><h4 style="font-size:15px;margin-bottom:4px">${lb}</h4><p class="muted" style="font-size:13px;margin-bottom:12px">Đổi tên thì số liệu cũ vẫn giữ. Xóa một dòng thì số liệu của dòng đó trong mọi tháng không còn hiện.</p>
    ${g[k].map((x,i)=>`<div style="display:flex;gap:8px;margin-bottom:8px"><input class="inp" value="${esc(x.n)}" onchange="S.cfg.${k}[${i}].n=this.value.trim()||S.cfg.${k}[${i}].n;save()"><button class="btn ico danger" title="Xóa" onclick="cfgDel('${k}',${i})">${IC.trash}</button></div>`).join('')}
    <button class="btn sm" onclick="S.cfg.${k}.push({id:'x'+uid(),n:'${k==='ch'?'Kênh mới':'Nhóm mới'}'});save();renderCfg()">${IC.plus}Thêm</button></div>`;
  e.innerHTML=`<div class="grid-c">${lst('ch','Kênh bán hàng')}${lst('sp','Nhóm sản phẩm')}</div>
  <div class="card" style="padding:20px"><h4 style="font-size:15px;margin-bottom:12px">Thông số tính toán</h4><div class="fgrid">
    <div class="fg"><label class="f">Ngưỡng chênh lệch cần giải trình (%)</label><input class="inp" type="number" id="cg_thr" value="${g.thr}"><div class="fhint">Chỉ tiêu lệch kế hoạch từ mức này trở lên sẽ được đánh dấu</div></div>
    <div class="fg"><label class="f">Ngưỡng cho chỉ tiêu tỷ lệ % (điểm %)</label><input class="inp" type="number" step="0.1" id="cg_thrPt" value="${g.thrPt}"><div class="fhint">VD phế phẩm kế hoạch 2%, thực hiện 3,1% là lệch 1,1 điểm</div></div>
    <div class="fg"><label class="f">Thuế suất thuế TNDN (%)</label><input class="inp" type="number" id="cg_tax" value="${g.tax}"></div>
    <div class="fg"><label class="f">Tiền tồn đầu kỳ (triệu đồng)</label><input class="inp" id="cg_cash0" value="${fmt(g.cash0)}"></div>
    <div class="fg"><label class="f">Tiền tồn đầu kỳ tính từ tháng</label><select class="inp" id="cg_cash0m">${months().map(m=>`<option value="${m}" ${m===g.cash0m?'selected':''}>${mName(m)}</option>`).join('')}</select></div></div>
    <button class="btn pri" onclick="cfgSave()">${IC.save}Lưu thông số</button></div>`}
function cfgSave(){const g=S.cfg;g.thr=+$('#cg_thr').value||5;g.thrPt=+$('#cg_thrPt').value||1;g.tax=+$('#cg_tax').value||0;g.cash0=parseNum($('#cg_cash0').value);g.cash0m=$('#cg_cash0m').value;save();toast('Đã lưu thông số');renderCfg()}
async function cfgDel(k,i){if(!await ask(`Xóa <b>${esc(S.cfg[k][i].n)}</b>?`))return;S.cfg[k].splice(i,1);save();renderCfg()}

/* =========================================================
   XUẤT EXCEL
   ========================================================= */
const actCols=()=>[{label:'Kỳ',width:13},{label:'Lĩnh vực',width:12},{label:'Việc cần làm',width:60},{label:'Chỉ tiêu liên quan',width:26},{label:'Người phụ trách',width:30},{label:'Hạn',type:'date'},{label:'Tiến độ',width:14},{label:'Kết quả',width:40}];
const actRow=a=>[mName(a.period||''),a.area,a.task,(INDS().find(i=>i.k===a.kpi)||{}).l||'',a.owner,a.due,actLate(a)?'Trễ hạn':a.status,a.result];
function exportMonth(){const P={t:'m',k:NM};XLSX.save('So lieu '+mName(NM),[{name:mName(NM),title:'SỐ LIỆU KẾ HOẠCH, THỰC HIỆN '+mName(NM).toUpperCase(),subtitles:[co().name||'','Đơn vị tiền: triệu đồng'],noFilter:true,columns:[{label:'Nhóm',width:24},{label:'Chỉ tiêu',width:36},{label:'ĐVT',width:8},{label:'Kế hoạch',type:'num',width:13},{label:'Thực hiện',type:'num',width:13},{label:'% TH/KH',type:'pct',width:10}],rows:rowsOf(P).map(r=>[SECS[r.ind.s],r.ind.l,UN[r.ind.u],r.pl,r.ac,r.ind.g?r.ht:''])}])}
function sheetsAll(){const ms=months(),L=INDS();
  const wide=side=>({name:side==='act'?'Thực hiện theo tháng':'Kế hoạch theo tháng',title:(side==='act'?'SỐ THỰC HIỆN':'SỐ KẾ HOẠCH')+' THEO THÁNG (tiền: triệu đồng)',noFilter:true,columns:[{label:'Nhóm',width:22},{label:'Chỉ tiêu',width:34},{label:'ĐVT',width:7},...ms.map(m=>({label:mName(m),type:'num',width:12}))],
    rows:L.map(i=>[SECS[i.s],i.l,UN[i.u],...ms.map(m=>side==='act'&&!hasAct(m)?'':aggV({t:'m',k:m})[side][i.k])])});
  const lm=lastAct(),P={t:'m',k:lm};
  return [wide('act'),wide('plan'),
    {name:'Chênh lệch tháng',title:'THỰC HIỆN SO VỚI KẾ HOẠCH '+(lm?mName(lm).toUpperCase():''),columns:[{label:'Nhóm',width:22},{label:'Chỉ tiêu',width:34},{label:'ĐVT',width:7},{label:'Kế hoạch',type:'num',width:13},{label:'Thực hiện',type:'num',width:13},{label:'Chênh lệch',type:'num',width:13},{label:'% TH/KH',type:'pct',width:10},{label:'Đánh giá',width:14},{label:'Nguyên nhân',width:60}],rows:lm?rowsOf(P).map(r=>[SECS[r.ind.s],r.ind.l,UN[r.ind.u],r.pl,r.ac,r.d,r.ht,r.lb,S.causes[pk(P)+'|'+r.ind.k]||'']):[]},
    {name:'Kế hoạch hành động',title:'KẾ HOẠCH HÀNH ĐỘNG',columns:actCols(),rows:S.actions.slice().sort((a,b)=>(a.period||'').localeCompare(b.period||'')).map(actRow)}]}

boot();
