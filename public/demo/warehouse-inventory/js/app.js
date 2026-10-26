
/* Chụp lại mã gốc của trang TRƯỚC khi vẽ giao diện, để khi Lưu chỉ thay phần dữ liệu */
// Standalone HTML file generation is not supported in this Blade demo.

/* =========================================================
   TIỆN ÍCH
   ========================================================= */
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN').format(Math.round(n||0));
const money=n=>fmt(n)+' ₫';
const short=n=>{n=Math.round(n||0);const a=Math.abs(n);
  if(a>=1e9)return (n/1e9).toFixed(a>=1e10?0:1).replace('.',',')+' tỷ';
  if(a>=1e6)return (n/1e6).toFixed(a>=1e8?0:1).replace('.',',')+' tr';
  if(a>=1e3)return Math.round(n/1e3)+'k';return String(n)};
const pad=n=>String(n).padStart(2,'0');
const ymd=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const vnDate=s=>{if(!s)return '';const [y,m,d]=s.split('-');return d+'/'+m+'/'+y};
const hm=t=>{const d=new Date(t);return pad(d.getHours())+':'+pad(d.getMinutes())+' '+pad(d.getDate())+'/'+pad(d.getMonth()+1)};
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2,7);
const TODAY=new Date(), TODAYS=ymd(TODAY);
const PALETTE=['#4f46e5','#16a36a','#f0663a','#8b5cf6','#e6a100','#0ea5b7','#e0384a','#64748b'];

/* =========================================================
   DỮ LIỆU
   - Nguồn chính: khối khoData nằm ngay trong file (do nút Lưu ghi vào)
   - Bản nháp: bộ nhớ trình duyệt, giữ lại thay đổi chưa kịp lưu vào file
   ========================================================= */
const ONLINE=/^https?:$/.test(location.protocol); // mở trên web: dữ liệu tự lưu ở trình duyệt
const FILE_NAME=ONLINE?'Quan_ly_kho_XNT.html':decodeURIComponent(location.pathname.split('/').pop()||'Quan_ly_kho_XNT.html');
const LS='kho_xnt_phongexcel_v2|'+location.pathname;
const valid=d=>d&&Array.isArray(d.products)&&Array.isArray(d.txns);
const EMB=(()=>{try{return JSON.parse($('#khoData').textContent)}catch(e){return null}})();
const SAMPLE=(()=>{try{return JSON.parse($('#sampleImgs').textContent)}catch(e){return {imgs:{}}}})();
let DIRTY=false, FROM_DRAFT=false;
let S=load();
function load(){
  let draft=null;try{draft=JSON.parse(localStorage.getItem(LS))}catch(e){}
  if(valid(EMB)){
    if(valid(draft)&&(draft.updated||0)>(EMB.updated||0)){DIRTY=true;FROM_DRAFT=true;return draft}
    return EMB}
  if(valid(draft)){DIRTY=(draft.updated||0)>0;FROM_DRAFT=DIRTY;return draft}
  return seed()}
function persist(d){try{localStorage.setItem(LS,JSON.stringify(d));return true}catch(e){return false}}
function save(){S.updated=Date.now();DIRTY=true;
  if(!persist(S))toast('Bộ nhớ nháp của trình duyệt đã đầy, thầy bấm Lưu để ghi vào file ngay nhé','err');
  refreshChrome();refreshSave()}

/* CHIA TRANG: mỗi danh sách có trang và số dòng riêng; khi in thì hiện đủ */
const PG={},PSIZES=[10,20,50,100];let PRINT_ALL=false;
function pgState(k,def){return PG[k]=PG[k]||{p:0,n:def||20}}
function pgReset(k){if(PG[k])PG[k].p=0}
function pgSlice(k,list,def){const st=pgState(k,def);if(PRINT_ALL)return list;const pages=Math.max(1,Math.ceil(list.length/st.n));if(st.p>=pages)st.p=pages-1;if(st.p<0)st.p=0;return list.slice(st.p*st.n,st.p*st.n+st.n)}
function pgOff(k){const st=pgState(k);return PRINT_ALL?0:st.p*st.n}
function pgBar(k,total,fn,cls){const st=pgState(k),pages=Math.max(1,Math.ceil(total/st.n)),p=st.p;
  if(PRINT_ALL)return '';
  const from=total?p*st.n+1:0,to=Math.min(total,(p+1)*st.n);const b=i=>`<button class="${i===p?'on':''}" onclick="pgGo('${k}',${i},'${fn}')">${i+1}</button>`;
  let nums='';if(pages<=7)for(let i=0;i<pages;i++)nums+=b(i);else{nums+=b(0);const a=Math.max(1,p-1),e=Math.min(pages-2,p+1);if(a>1)nums+='<span>…</span>';for(let i=a;i<=e;i++)nums+=b(i);if(e<pages-2)nums+='<span>…</span>';nums+=b(pages-1)}
  return `<div class="pgbar ${cls||''}"><span>Hiển thị <b>${fmt(from)}-${fmt(to)}</b> / ${fmt(total)} dòng</span><span class="pgs">Số dòng/trang<select onchange="pgSize('${k}',this.value,'${fn}')">${PSIZES.map(n=>`<option ${n===st.n?'selected':''}>${n}</option>`).join('')}</select>${pages>1?`<button ${p?'':'disabled'} onclick="pgGo('${k}',${p-1},'${fn}')">‹</button>${nums}<button ${p<pages-1?'':'disabled'} onclick="pgGo('${k}',${p+1},'${fn}')">›</button>`:''}</span></div>`}
function pgGo(k,p,fn){pgState(k).p=p;window[fn]();window.scrollTo({top:0,behavior:'smooth'})}
function pgSize(k,n,fn){const st=pgState(k);st.n=+n;st.p=0;window[fn]()}
window.addEventListener('beforeprint',()=>{PRINT_ALL=true;try{rerender()}catch(e){}});
window.addEventListener('afterprint',()=>{PRINT_ALL=false;try{rerender()}catch(e){}});

const prod=id=>S.products.find(p=>p.id===id);
const cats=()=>[...new Set(S.products.map(p=>p.cat).filter(Boolean))].sort((a,b)=>a.localeCompare(b,'vi'));
function stock(pid,before){ // tồn tới trước ngày "before" (nếu có)
  const p=prod(pid);let q=p?+p.openQty||0:0;
  for(const t of S.txns){if(t.pid!==pid)continue;if(before&&t.date>=before)continue;q+=t.type==='in'?t.qty:-t.qty}
  return q}
function status(p){const s=stock(p.id);if(s<=0)return 'zero';if(s<=(+p.min||0))return 'low';return 'ok'}
const ST={ok:['ok','Còn hàng'],low:['low','Sắp hết'],zero:['zero','Hết hàng']};
const stTag=k=>`<span class="tag ${ST[k][0]}">${ST[k][1]}</span>`;
const invValue=()=>S.products.reduce((s,p)=>s+Math.max(0,stock(p.id))*(+p.cost||0),0);
function nextDoc(type){const pre=type==='in'?'PN':'PX';let m=0;S.txns.forEach(t=>{if(t.type===type){const n=parseInt(String(t.doc).replace(/\D/g,''))||0;if(n>m)m=n}});return pre+String(m+1).padStart(5,'0')}

/* ảnh giữ chỗ cho dữ liệu mẫu */
function phImg(emoji,c1,c2){
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${c1}"/><stop offset="1" stop-color="${c2}"/></linearGradient></defs><rect width="400" height="300" fill="url(#g)"/><circle cx="330" cy="40" r="90" fill="#fff" opacity=".12"/><circle cx="60" cy="280" r="70" fill="#fff" opacity=".1"/><text x="200" y="185" font-size="130" text-anchor="middle">${emoji}</text></svg>`;
  return 'data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg)}
function noImg(name){const ch=(name||'?').trim().charAt(0).toUpperCase();
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="300" height="300" fill="#e8edf7"/><text x="150" y="190" font-family="Segoe UI,Arial" font-weight="700" font-size="130" fill="#9aa8c4" text-anchor="middle">${esc(ch)}</text></svg>`;
  return 'data:image/svg+xml;charset=utf-8,'+encodeURIComponent(svg)}
const img=p=>p&&p.img?p.img:noImg(p?p.name:'?');

/* =========================================================
   DỮ LIỆU MẪU
   ========================================================= */
function seed(){
  let r=7;const rnd=()=>{r=(r*16807)%2147483647;return (r-1)/2147483646};const ri=(a,b)=>a+Math.floor(rnd()*(b-a+1));
  const P=[
    ['SP001','Cà phê hạt Arabica 1kg','Đồ uống','Gói',280000,350000,10,'☕','#7b4a2d','#c08457'],
    ['SP002','Trà xanh túi lọc (hộp 25 gói)','Đồ uống','Hộp',45000,62000,20,'🍵','#2f855a','#9ae6b4'],
    ['SP003','Nước suối 500ml (thùng 24 chai)','Đồ uống','Thùng',85000,110000,15,'💧','#2b6cb0','#90cdf4'],
    ['SP004','Gạo ST25 túi 5kg','Thực phẩm','Túi',160000,195000,12,'🌾','#b7791f','#f6e05e'],
    ['SP005','Mì ăn liền (thùng 30 gói)','Thực phẩm','Thùng',110000,135000,10,'🍜','#c05621','#fbd38d'],
    ['SP006','Dầu ăn thực vật 1 lít','Thực phẩm','Chai',48000,59000,24,'🫙','#d69e2e','#faf089'],
    ['SP007','Nước rửa chén 750ml','Gia dụng','Chai',32000,42000,20,'🧽','#319795','#b2f5ea'],
    ['SP008','Khăn giấy rút (hộp)','Gia dụng','Hộp',18000,25000,30,'🧻','#718096','#e2e8f0'],
    ['SP009','Pin AA (vỉ 4 viên)','Gia dụng','Vỉ',28000,39000,20,'🔋','#2f855a','#68d391'],
    ['SP010','Giấy A4 70gsm (ram 500 tờ)','Văn phòng phẩm','Ram',72000,89000,25,'📄','#4c51bf','#c3dafe'],
    ['SP011','Bút bi xanh (hộp 20 cây)','Văn phòng phẩm','Hộp',60000,78000,15,'🖊️','#2c5282','#90cdf4'],
    ['SP012','Bìa hồ sơ nhựa (xấp 10 cái)','Văn phòng phẩm','Xấp',35000,48000,10,'📁','#b83280','#fbb6ce'],
  ];
  const products=P.map(a=>({id:'p'+a[0],code:a[0],name:a[1],cat:a[2],unit:a[3],cost:a[4],price:a[5],min:a[6],openQty:ri(15,40),img:SAMPLE.imgs[a[0]]||phImg(a[7],a[8],a[9]),note:''}));
  const sup=['Công ty TNHH Minh Phát','Nhà phân phối Hòa Bình','Tổng kho Sài Gòn','Công ty CP Thành Đạt'];
  const cus=['Cửa hàng Bảo Anh','Siêu thị mini Gia Phúc','Văn phòng Công ty An Khang','Đại lý Kim Ngân','Khách lẻ','Tạp hóa Cô Ba'];
  const start=new Date(TODAY.getFullYear(),TODAY.getMonth()-11,1);
  const ev=[];
  for(let m=0;m<12;m++){
    const y=start.getFullYear(),mo=start.getMonth()+m,dim=new Date(y,mo+1,0).getDate();
    const grow=0.6+m*0.06;
    products.forEach(p=>{
      for(let k=0;k<ri(1,2);k++)ev.push({type:'in',p,d:new Date(y,mo,ri(1,Math.min(dim,26))),q:Math.round(ri(18,45)*grow)});
      for(let k=0;k<ri(3,6);k++)ev.push({type:'out',p,d:new Date(y,mo,ri(1,dim)),q:Math.max(1,Math.round(ri(2,11)*grow))});
    });
  }
  const txns=[],bal={};products.forEach(p=>bal[p.id]=p.openQty);
  ev.filter(e=>e.d<=TODAY).sort((a,b)=>a.d-b.d).forEach(e=>{
    if(e.type==='out'&&bal[e.p.id]<e.q)return;
    bal[e.p.id]+=e.type==='in'?e.q:-e.q;
    txns.push({id:uid()+txns.length,type:e.type,date:ymd(e.d),pid:e.p.id,qty:e.q,
      price:e.type==='in'?e.p.cost:e.p.price,partner:e.type==='in'?sup[ri(0,sup.length-1)]:cus[ri(0,cus.length-1)],note:''});
  });
  // tạo vài mặt hàng sắp hết / hết hàng để Dashboard có cảnh báo
  [['pSP005',3],['pSP011',0],['pSP002',8]].forEach(([pid,left])=>{
    const p=products.find(x=>x.id===pid);const q=bal[pid]-left;
    if(q>0)txns.push({id:uid()+pid,type:'out',date:TODAYS,pid,qty:q,price:p.price,partner:'Đại lý Kim Ngân',note:'Đơn sỉ'});
  });
  let ni=0,no=0;txns.sort((a,b)=>a.date.localeCompare(b.date)).forEach(t=>{t.doc=t.type==='in'?'PN'+String(++ni).padStart(5,'0'):'PX'+String(++no).padStart(5,'0')});
  return {products,txns,ver:2,updated:0};
}

/* =========================================================
   LƯU VÀO CHÍNH FILE HTML
   ========================================================= */
let FH=null;          // "tay cầm" file đã được thầy cho phép ghi
let SAVED_AT=ONLINE?0:(EMB&&EMB.savedAt||0);
const idb=(mode,fn)=>new Promise((ok,no)=>{const rq=indexedDB.open('kho_xnt_file',1);rq.onupgradeneeded=()=>rq.result.createObjectStore('h');
  rq.onsuccess=()=>{const tx=rq.result.transaction('h',mode),r=fn(tx.objectStore('h'));tx.oncomplete=()=>ok(r&&r.result);tx.onerror=()=>no(tx.error)};rq.onerror=()=>no(rq.error)});
const idbGet=k=>idb('readonly',s=>s.get(k)).catch(()=>null);
const idbSet=(k,v)=>idb('readwrite',s=>s.put(v,k)).catch(()=>null);

// Blade demo uses browser storage; the Save button exports a JSON backup.
function saveToFile(){backup();toast('Đã xuất bản sao lưu JSON.');}
function forgetFile(){saveToFile();}
function refreshSave(){
  const st=$('#saveSt'),bt=$('#saveBtn');
  if(bt){bt.lastChild.textContent='Sao lưu JSON';bt.title='Xuất bản sao dữ liệu JSON (Ctrl + S)';bt.classList.remove('dirty');}
  if(st){st.className='savest clean';st.innerHTML='<b>Tự lưu trên trình duyệt</b>Dùng Sao lưu JSON để lưu bản dự phòng';}
}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveToFile()}});
window.addEventListener('beforeunload',e=>{if(DIRTY&&!ONLINE){e.preventDefault();e.returnValue=''}});

/* =========================================================
   ĐIỀU HƯỚNG
   ========================================================= */
const PAGES={
  dashboard:['Dashboard','Tổng quan tình hình kho hàng',renderDashboard],
  in:['Nhập kho','Lập phiếu nhập hàng vào kho',()=>renderTxn('in')],
  out:['Xuất kho','Lập phiếu xuất hàng khỏi kho',()=>renderTxn('out')],
  products:['Sản phẩm','Danh mục hàng hóa kèm hình ảnh',renderProducts],
  history:['Lịch sử giao dịch','Toàn bộ phiếu nhập và phiếu xuất',renderHistory],
  report:['Báo cáo Xuất Nhập Tồn','Tồn đầu kỳ, nhập, xuất, tồn cuối kỳ theo từng mặt hàng',renderReport],
  settings:['Cài đặt & dữ liệu','Lưu vào file, sao lưu, khôi phục và làm mới dữ liệu',renderSettings],
};
let CUR='dashboard';
function go(pg){
  if(!PAGES[pg])pg='dashboard';CUR=pg;
  $$('#nav a').forEach(a=>a.classList.toggle('active',a.dataset.page===pg));
  $$('.page').forEach(s=>s.classList.toggle('show',s.id==='page-'+pg));
  $('#pTitle').textContent=PAGES[pg][0];$('#pSub').textContent=PAGES[pg][1];
  PAGES[pg][2]();
  try{if(location.hash!=='#'+pg)history.replaceState(null,'','#'+pg)}catch(e){}
  $('#sb').classList.remove('open');$('#mask').classList.remove('show');window.scrollTo(0,0);
}
$$('#nav a').forEach(a=>a.onclick=()=>go(a.dataset.page));
$('#burger').onclick=()=>{$('#sb').classList.add('open');$('#mask').classList.add('show')};
$('#mask').onclick=()=>{$('#sb').classList.remove('open');$('#mask').classList.remove('show')};
function rerender(){PAGES[CUR][2]()}

function refreshChrome(){
  const low=S.products.filter(p=>status(p)!=='ok').length;
  const b=$('#lowBadge');b.style.display=low?'':'none';b.textContent=low;
  const v=invValue();$('#sbValue').textContent=money(v);
  const ok=S.products.length?Math.round((S.products.length-low)/S.products.length*100):0;
  $('#sbBar').style.width=ok+'%';
  $('#sbNote').textContent=`${S.products.length} mặt hàng · ${ok}% đủ tồn an toàn`;
}

/* =========================================================
   BIỂU ĐỒ (SVG tự vẽ, không cần mạng)
   ========================================================= */
function niceMax(v){if(v<=0)return 1;const p=Math.pow(10,Math.floor(Math.log10(v)));const n=v/p;return (n<=1?1:n<=2?2:n<=2.5?2.5:n<=5?5:10)*p}
function barChart(labels,series){
  const W=760,H=290,pl=54,pr=10,pt=14,pb=34,ih=H-pt-pb;
  const max=niceMax(Math.max(1,...series.flatMap(s=>s.data)));
  const cw=(W-pl-pr)/labels.length,bw=Math.min(16,(cw-12)/series.length),gap=3;
  let g='';
  for(let i=0;i<=4;i++){const y=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${y}" y2="${y}" class="grid"/><text x="${pl-8}" y="${y+4}" text-anchor="end" class="ax">${short(max*i/4)}</text>`}
  labels.forEach((l,i)=>{
    const cx=pl+cw*i+cw/2,tw=bw*series.length+gap*(series.length-1);
    series.forEach((s,j)=>{const v=s.data[i],h=Math.max(v>0?2:0,ih*v/max),x=cx-tw/2+j*(bw+gap);
      g+=`<rect x="${x}" y="${pt+ih-h}" width="${bw}" height="${h}" rx="4" fill="${s.color}" data-tip="${esc(s.name+' '+l+': '+money(v))}"/>`});
    g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax">${l}</text>`});
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function donut(items){
  const tot=items.reduce((s,i)=>s+i.value,0);
  if(!tot)return '<div class="empty">Chưa có hàng tồn</div>';
  const R=70,C=2*Math.PI*R;let off=0,segs='';
  items.forEach(it=>{const len=it.value/tot*C;
    segs+=`<circle class="seg" cx="100" cy="100" r="${R}" fill="none" stroke="${it.color}" stroke-width="26" stroke-dasharray="${Math.max(0,len-2)} ${C}" stroke-dashoffset="${-off}" transform="rotate(-90 100 100)" data-tip="${esc(it.label+': '+money(it.value))}"/>`;off+=len});
  return `<div class="donut-wrap"><svg viewBox="0 0 200 200" class="chart">${segs}<text x="100" y="94" text-anchor="middle" class="ax" style="font-size:12px">Tổng giá trị</text><text x="100" y="116" text-anchor="middle" style="font-size:19px;font-weight:800;fill:var(--text)">${short(tot)}</text></svg>
  <div class="dl">${items.map(it=>`<div><i style="background:${it.color}"></i>${esc(it.label)}<span>${short(it.value)}</span><em>${Math.round(it.value/tot*100)}%</em></div>`).join('')}</div></div>`}

/* tooltip */
const tip=$('#tip');
document.addEventListener('mousemove',e=>{const t=e.target.closest&&e.target.closest('[data-tip]');
  if(!t){tip.style.display='none';return}tip.textContent=t.dataset.tip;tip.style.display='block';
  const w=tip.offsetWidth;tip.style.left=Math.min(e.clientX+14,innerWidth-w-8)+'px';tip.style.top=(e.clientY-36)+'px'});

/* =========================================================
   DASHBOARD
   ========================================================= */
let PERIOD='month';
$$('#dPeriod button').forEach(b=>b.onclick=()=>{PERIOD=b.dataset.v;$$('#dPeriod button').forEach(x=>x.classList.toggle('on',x===b));renderDashboard()});
function rangeOf(k){
  const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t,pf,pt,lbl,cmp;
  if(k==='month'){f=new Date(y,m,1);t=new Date(y,m+1,0);pf=new Date(y,m-1,1);pt=new Date(y,m,0);lbl='Tháng '+(m+1)+'/'+y;cmp='so với tháng trước'}
  else if(k==='quarter'){const q=Math.floor(m/3);f=new Date(y,q*3,1);t=new Date(y,q*3+3,0);pf=new Date(y,q*3-3,1);pt=new Date(y,q*3,0);lbl='Quý '+(q+1)+'/'+y;cmp='so với quý trước'}
  else if(k==='year'){f=new Date(y,0,1);t=new Date(y,11,31);pf=new Date(y-1,0,1);pt=new Date(y-1,11,31);lbl='Năm '+y;cmp='so với năm trước'}
  else return {from:'0000-01-01',to:'9999-12-31',label:'Toàn bộ thời gian'};
  return {from:ymd(f),to:ymd(t),pfrom:ymd(pf),pto:ymd(pt),label:lbl,cmp}}
function sumTx(from,to){const o={inV:0,inQ:0,outV:0,outQ:0,profit:0,nIn:0,nOut:0};
  S.txns.forEach(t=>{if(t.date<from||t.date>to)return;const v=t.qty*t.price;
    if(t.type==='in'){o.inV+=v;o.inQ+=t.qty;o.nIn++}else{o.outV+=v;o.outQ+=t.qty;o.nOut++;const p=prod(t.pid);o.profit+=t.qty*(t.price-(p?+p.cost:0))}});return o}
function delta(cur,prev,cmp){if(prev===undefined)return '<span class="d">Tính trên toàn bộ dữ liệu</span>';
  if(!prev)return `<span class="d">${cur?'Mới phát sinh':'Chưa phát sinh'} ${cmp}</span>`;
  const p=(cur-prev)/prev*100,up=p>=0;return `<span class="d ${up?'gain':'loss'}">${up?'▲':'▼'} ${Math.abs(p).toFixed(1).replace('.',',')}% <span style="color:var(--muted);font-weight:500">${cmp}</span></span>`}
function kpi(icon,bg,col,label,val,sub){return `<div class="card kpi" style="color:${col}"><div class="ic" style="background:${bg};color:${col}">${icon}</div><small>${label}</small><b style="color:var(--text)">${val}</b>${sub}</div>`}
const IC={
  box:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>',
  in:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>',
  out:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15V3M7 8l5-5 5 5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>',
  profit:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>',
  warn:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>'};
/* số chạy từ 0 lên cho sinh động */
function countUp(root){
  const els=[...root.querySelectorAll('[data-count]')],fin=()=>els.forEach(e=>e.textContent=fmt(+e.dataset.count));
  if(PRINT_ALL||matchMedia('(prefers-reduced-motion: reduce)').matches)return fin();
  const t0=performance.now(),D=900;els.forEach(e=>e.textContent='0');
  const step=t=>{const k=Math.min(1,(t-t0)/D),ez=1-Math.pow(1-k,3);els.forEach(e=>e.textContent=fmt(+e.dataset.count*ez));if(k<1)requestAnimationFrame(step)};
  requestAnimationFrame(step);setTimeout(fin,1300)}
/* số liệu 12 tháng: nhập, xuất, lãi gộp, số phiếu, giá trị tồn cuối tháng */
function dashSeries(){
  const months=[];for(let i=11;i>=0;i--){const d=new Date(TODAY.getFullYear(),TODAY.getMonth()-i,1);months.push(d.getFullYear()+'-'+pad(d.getMonth()+1))}
  const idx={};months.forEach((k,i)=>idx[k]=i);
  const z=()=>months.map(()=>0),o={months,inV:z(),outV:z(),profit:z(),docs:z(),inv:z()},docs=months.map(()=>new Set());
  const q={},byM={};S.products.forEach(p=>q[p.id]=+p.openQty||0);
  S.txns.forEach(t=>{const k=t.date.slice(0,7),i=idx[k],s=t.type==='in'?t.qty:-t.qty;
    if(k<months[0]){q[t.pid]=(q[t.pid]||0)+s;return}
    if(i===undefined)return;(byM[k]=byM[k]||[]).push([t.pid,s]);const v=t.qty*t.price;
    if(t.type==='in')o.inV[i]+=v;else{o.outV[i]+=v;const p=prod(t.pid);o.profit[i]+=t.qty*(t.price-(p?+p.cost||0:0))}
    docs[i].add(t.doc)});
  months.forEach((k,i)=>{(byM[k]||[]).forEach(([pid,s])=>q[pid]=(q[pid]||0)+s);
    o.inv[i]=S.products.reduce((a,p)=>a+Math.max(0,q[p.id]||0)*(+p.cost||0),0);o.docs[i]=docs[i].size});
  o.labels=months.map((k,i)=>{const [y,m]=k.split('-');return 'T'+(+m)+(m==='01'||i===0?'/'+y.slice(2):'')});
  return o}
function docCount(from,to,type){const s=new Set();S.txns.forEach(t=>{if(t.date>=from&&t.date<=to&&(!type||t.type===type))s.add(t.doc)});return s.size}
function pill(cur,prev,cmp){
  if(prev===undefined)return '<span class="pill na">Toàn bộ</span>';
  if(!prev)return `<span class="pill na" title="${cmp}">${cur?'Mới':'-'}</span>`;
  const p=(cur-prev)/Math.abs(prev)*100,up=p>=0;
  return `<span class="pill ${up?'pu':'pd'}" title="${cmp}">${up?'▲':'▼'} ${Math.abs(p).toFixed(1).replace('.',',')}%</span>`}
let SPK=0;
function spark(data,color){const W=240,H=46,n=data.length,mx=Math.max(...data),mn=Math.min(0,...data),r=(mx-mn)||1;
  const pts=data.map((v,i)=>[i*W/(n-1),H-4-(v-mn)/r*(H-12)]),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L'),id='spg'+(++SPK);
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`}
function heroArea(ser){
  const W=440,H=150,pl=8,pr=8,pt=12,pb=22,d=ser.inv,mx=Math.max(1,...d),mn=Math.min(...d),span=(mx-mn)||mx||1;
  const x=i=>pl+i*(W-pl-pr)/(d.length-1),y=v=>pt+(H-pt-pb)*(1-((v-mn)/span*0.85+0.08));
  const line='M'+d.map((v,i)=>x(i).toFixed(1)+','+y(v).toFixed(1)).join('L'),L=d.length-1;
  return `<svg viewBox="0 0 ${W} ${H}" class="chart" style="overflow:visible"><defs><linearGradient id="hgA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#c4b5fd" stop-opacity=".5"/><stop offset="1" stop-color="#c4b5fd" stop-opacity="0"/></linearGradient></defs>
  <path d="${line}L${x(L)},${H-pb}L${x(0)},${H-pb}Z" fill="url(#hgA)"/>
  <path d="${line}" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>
  ${d.map((v,i)=>`<circle class="seg" cx="${x(i)}" cy="${y(v)}" r="${i===L?5.5:3.5}" fill="${i===L?'#fff':'#ddd6fe'}" stroke="${i===L?'#4f46e5':'none'}" stroke-width="3" data-tip="${esc('Tồn cuối '+ser.labels[i]+': '+money(v))}"/>`).join('')}
  ${ser.labels.map((l,i)=>i%2===1?`<text x="${x(i)}" y="${H-3}" text-anchor="middle" style="font-size:10.5px;fill:#c7c2ff">${l}</text>`:'').join('')}</svg>`}
function comboChart(ser){
  const W=760,H=300,pl=56,pr=12,pt=16,pb=34,ih=H-pt-pb,n=ser.labels.length;
  const max=niceMax(Math.max(1,...ser.inV,...ser.outV,...ser.profit));
  const cw=(W-pl-pr)/n,bw=Math.min(18,(cw-14)/2),Y=v=>pt+ih*(1-Math.max(0,v)/max);
  let g=`<defs><linearGradient id="gbIn" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2fd394"/><stop offset="1" stop-color="#139a63"/></linearGradient><linearGradient id="gbOut" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ff9466"/><stop offset="1" stop-color="#e95a2c"/></linearGradient></defs>`;
  g+=`<rect class="hl" x="${pl+cw*(n-1)+2}" y="${pt-8}" width="${cw-4}" height="${ih+8}" rx="10" fill="#f1efff"/>`;
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${short(max*i/4)}</text>`}
  ser.labels.forEach((l,i)=>{const cx=pl+cw*i+cw/2;
    [[ser.inV[i],'url(#gbIn)','Nhập'],[ser.outV[i],'url(#gbOut)','Xuất']].forEach(([v,fill,nm],j)=>{const hh=Math.max(v>0?2:0,ih*v/max);
      g+=`<rect class="bar" style="animation-delay:${i*45+j*20}ms" x="${cx-bw-2+j*(bw+4)}" y="${pt+ih-hh}" width="${bw}" height="${hh}" rx="5" fill="${fill}" data-tip="${esc(nm+' '+l+': '+money(v))}"/>`});
    g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax"${i===n-1?' style="font-weight:700;fill:#4f46e5"':''}>${l}</text>`});
  const pts=ser.profit.map((v,i)=>[pl+cw*i+cw/2,Y(v)]);
  g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="#8b5cf6" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>`;
  g+=pts.map((p,i)=>`<circle class="seg" cx="${p[0]}" cy="${p[1]}" r="4" fill="#fff" stroke="#8b5cf6" stroke-width="2.5" data-tip="${esc('Lãi gộp '+ser.labels[i]+': '+money(ser.profit[i]))}"/>`).join('');
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function kc(o){return `<div class="card kc" style="--c:${o.c};--cb:${o.cb}"><div class="kc-h"><span class="kc-ic">${o.icon}</span><span class="kc-l">${o.label}</span>${o.pill}</div><div class="kc-v"><span data-count="${Math.round(o.value)}">0</span>${o.unit?` <small>${o.unit}</small>`:''}</div><div class="kc-s">${o.sub}</div>${spark(o.data,o.c)}</div>`}
const IC_DOC='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></svg>';
function renderDashboard(){
  const r=rangeOf(PERIOD);$('#dRange').textContent=r.label;
  const c=sumTx(r.from,r.to),p=r.pfrom?sumTx(r.pfrom,r.pto):null,ser=dashSeries();
  const inv=invValue(),tq=S.products.reduce((s,x)=>s+Math.max(0,stock(x.id)),0),N=S.products.length;
  const lows=S.products.filter(x=>status(x)!=='ok'),zero=lows.filter(x=>status(x)==='zero').length,okN=N-lows.length,pct=N?Math.round(okN/N*100):0;
  const dIn=docCount(r.from,r.to,'in'),dOut=docCount(r.from,r.to,'out'),pdIn=r.pfrom?docCount(r.pfrom,r.pto,'in'):0,pdOut=r.pfrom?docCount(r.pfrom,r.pto,'out'):0;

  // khung lớn đầu trang
  const C=2*Math.PI*50,prevInv=ser.inv[10];
  $('#dHero').innerHTML=`<div>
      <div class="h-k">GIÁ TRỊ TỒN KHO HIỆN TẠI</div>
      <div class="h-v"><span data-count="${Math.round(inv)}">0</span> <small>₫</small></div>
      <div class="h-sub">${pill(inv,prevInv,'so với cuối tháng trước')}<span>so với cuối tháng trước (${short(prevInv)} ₫)</span></div>
      <div class="h-stats"><div><b data-count="${N}">0</b><span>Mặt hàng</span></div><div><b data-count="${tq}">0</b><span>Đơn vị đang tồn</span></div><div><b data-count="${dIn+dOut}">0</b><span>Phiếu ${r.label.toLowerCase()}</span></div></div>
      <div class="h-act"><button class="hbtn" onclick="go('in')">+ Lập phiếu nhập</button><button class="hbtn ghost" onclick="go('out')">− Lập phiếu xuất</button></div>
    </div>
    <div class="h-chart"><div class="h-ct"><span>Giá trị tồn kho cuối mỗi tháng</span><span>Cao nhất <b>${short(Math.max(...ser.inv))} ₫</b></span></div>${heroArea(ser)}</div>
    <div class="h-ring"><svg viewBox="0 0 120 120"><defs><linearGradient id="rgA" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#7ef0c0"/><stop offset="1" stop-color="#22c58b"/></linearGradient></defs>
      <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="11"/>
      <circle class="ring-a" cx="60" cy="60" r="50" fill="none" stroke="url(#rgA)" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C*pct/100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
      <text x="60" y="60" text-anchor="middle" style="font-size:27px;font-weight:800;fill:#fff">${pct}%</text><text x="60" y="78" text-anchor="middle" style="font-size:9.5px;fill:#c7c2ff">đủ tồn an toàn</text></svg>
      <b>${okN}/${N} mặt hàng</b><span>${zero} hết hàng · ${lows.length-zero} sắp hết</span></div>`;

  // 4 thẻ số liệu
  const margin=c.outV?Math.round(c.profit/c.outV*1000)/10:0;
  $('#kpis').innerHTML=
    kc({c:'#16a36a',cb:'var(--inbg)',icon:IC.in,label:'Giá trị nhập',value:c.inV,unit:'₫',pill:pill(c.inV,p?p.inV:undefined,r.cmp),sub:`${fmt(c.inQ)} đơn vị · ${dIn} phiếu nhập`,data:ser.inV})+
    kc({c:'#f0663a',cb:'var(--outbg)',icon:IC.out,label:'Giá trị xuất',value:c.outV,unit:'₫',pill:pill(c.outV,p?p.outV:undefined,r.cmp),sub:`${fmt(c.outQ)} đơn vị · ${dOut} phiếu xuất`,data:ser.outV})+
    kc({c:'#8b5cf6',cb:'#f1ebff',icon:IC.profit,label:'Lãi gộp',value:c.profit,unit:'₫',pill:pill(c.profit,p?p.profit:undefined,r.cmp),sub:`Biên lãi ${String(margin).replace('.',',')}% trên giá trị xuất`,data:ser.profit})+
    kc({c:'#4f46e5',cb:'var(--accent2)',icon:IC_DOC,label:'Số phiếu lập',value:dIn+dOut,unit:'phiếu',pill:pill(dIn+dOut,p?pdIn+pdOut:undefined,r.cmp),sub:`${dIn} phiếu nhập · ${dOut} phiếu xuất`,data:ser.docs});

  // biểu đồ 12 tháng
  const sm=a=>a.reduce((s,v)=>s+v,0);
  $('#mSum').innerHTML=`<div><small>Tổng nhập 12 tháng</small><b style="color:var(--in)">${short(sm(ser.inV))} ₫</b></div><div><small>Tổng xuất 12 tháng</small><b style="color:var(--out)">${short(sm(ser.outV))} ₫</b></div><div><small>Lãi gộp 12 tháng</small><b style="color:#8b5cf6">${short(sm(ser.profit))} ₫</b></div>`;
  $('#chMonth').innerHTML=comboChart(ser);

  // cơ cấu nhóm
  const byCat={};S.products.forEach(x=>{const v=Math.max(0,stock(x.id))*(+x.cost||0);byCat[x.cat||'Khác']=(byCat[x.cat||'Khác']||0)+v});
  $('#chCat').innerHTML=donut(Object.entries(byCat).sort((a,b)=>b[1]-a[1]).map(([l,v],i)=>({label:l,value:v,color:PALETTE[i%PALETTE.length]})));

  // top 5
  $('#topHint').textContent=r.label;
  const agg={};S.txns.forEach(t=>{if(t.type!=='out'||t.date<r.from||t.date>r.to)return;agg[t.pid]=agg[t.pid]||{q:0,v:0};agg[t.pid].q+=t.qty;agg[t.pid].v+=t.qty*t.price});
  const top=Object.entries(agg).filter(([pid])=>prod(pid)).sort((a,b)=>b[1].v-a[1].v).slice(0,5),mx=top.length?top[0][1].v:1;
  const MED=['#f5b301','#9aa5b8','#cd7f32','#c3cbda','#c3cbda'];
  $('#top5').innerHTML=top.length?top.map(([pid,o],i)=>{const x=prod(pid);return `<div class="li"><span class="medal" style="background:${MED[i]}">${i+1}</span><img class="thumb" src="${img(x)}" data-zoom alt=""><div class="meta"><b>${esc(x.name)}</b><small>${fmt(o.q)} ${esc(x.unit)}</small><div class="pbar"><i style="width:${o.v/mx*100}%;background:linear-gradient(90deg,#ff9466,#f0663a)"></i></div></div><div class="v">${short(o.v)}</div></div>`}).join(''):'<div class="empty">Chưa có phiếu xuất trong kỳ</div>';

  // cảnh báo
  $('#lowCnt').textContent=lows.length?lows.length+' mặt hàng':'';
  const sorted=lows.map(x=>({x,s:stock(x.id)})).sort((a,b)=>a.s-b.s);
  $('#lowList').innerHTML=sorted.length?sorted.slice(0,5).map(({x,s})=>{const col=s<=0?'var(--danger)':'#e6a100',w=x.min?Math.max(3,Math.min(100,s/x.min*100)):3;
    return `<div class="li"><img class="thumb" src="${img(x)}" data-zoom alt=""><div class="meta"><b>${esc(x.name)}</b><small>Tồn <b style="display:inline;color:${col}">${fmt(s)}</b> / tối thiểu ${fmt(x.min)} ${esc(x.unit)}</small><div class="pbar"><i style="width:${s<=0?3:w}%;background:${col}"></i></div></div><button class="btn sm in" onclick="go('in');pickProduct('in','${x.id}')">Nhập thêm</button></div>`}).join('')
    +`<button class="btn" style="width:100%;justify-content:center;margin-top:10px" onclick="go('in');addLowLines()">Lập 1 phiếu nhập cho tất cả hàng sắp hết</button>`
    :'<div class="empty">Tất cả mặt hàng đều đủ tồn an toàn 👍</div>';

  // phiếu gần đây (gom theo số phiếu)
  const rec=docsOf().slice(0,6);
  $('#recent').innerHTML=rec.length?rec.map(g=>{const isIn=g.type==='in';return `<div class="li click" title="Bấm để in phiếu ${g.doc}" onclick="printDoc('${g.doc}')"><span class="tico ${g.type}">${isIn?IC.in:IC.out}</span><div class="meta"><b>${esc(g.partner||(isIn?'Phiếu nhập':'Phiếu xuất'))}</b><small>${g.doc} · ${vnDate(g.date)} · ${g.names.length} mặt hàng</small></div><div class="v" style="color:${isIn?'var(--in)':'var(--out)'}">${isIn?'+':'−'}${short(g.v)}<small>${isIn?'Nhập':'Xuất'}</small></div></div>`}).join(''):'<div class="empty">Chưa có phiếu nào</div>';

  countUp($('#page-dashboard'));
}

/* =========================================================
   SẢN PHẨM
   ========================================================= */
let PVIEW='grid';
$$('#pView button').forEach(b=>b.onclick=()=>{PVIEW=b.dataset.v;$$('#pView button').forEach(x=>x.classList.toggle('on',x===b));renderProductList()});
['#pSearch','#pCat','#pStatus'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('prod');renderProductList()}));
function fillCatSelect(sel,allLabel){const v=sel.value;sel.innerHTML=`<option value="">${allLabel}</option>`+cats().map(c=>`<option>${esc(c)}</option>`).join('');sel.value=cats().includes(v)?v:''}
function renderProducts(){fillCatSelect($('#pCat'),'Mọi nhóm hàng');renderProductList()}
function renderProductList(){
  const q=$('#pSearch').value.trim().toLowerCase(),c=$('#pCat').value,st=$('#pStatus').value;
  const list=S.products.filter(p=>(!q||(p.code+' '+p.name).toLowerCase().includes(q))&&(!c||p.cat===c)&&(!st||status(p)===st)).sort((a,b)=>a.code.localeCompare(b.code));
  const box=$('#pList');
  if(!list.length){box.innerHTML='<div class="card empty">Không có sản phẩm nào phù hợp.<br><br><button class="btn pri" onclick="openProduct()">Thêm sản phẩm mới</button> <button class="btn" onclick="openImport()">Nhập danh sách sản phẩm</button></div>';return}
  if(PVIEW==='grid'){
    box.innerHTML='<div class="pgrid">'+pgSlice('prod',list).map(p=>{const s=stock(p.id);return `<div class="card pc"><div class="img"><img src="${img(p)}" data-zoom alt="">${stTag(status(p))}</div><div class="bd"><small>${esc(p.code)} · ${esc(p.cat)}</small><b>${esc(p.name)}</b>
    <div class="st"><div><small>Tồn kho</small><strong>${fmt(s)} <span style="font-size:12px;font-weight:500;color:var(--muted)">${esc(p.unit)}</span></strong></div><div style="text-align:right"><small>Giá bán</small><strong>${short(p.price)}</strong></div></div>
    <div class="act"><button class="btn sm in" onclick="go('in');pickProduct('in','${p.id}')">Nhập</button><button class="btn sm out" onclick="go('out');pickProduct('out','${p.id}')">Xuất</button><button class="btn sm ico" title="Sửa" onclick="openProduct('${p.id}')">✏️</button></div></div></div>`}).join('')+'</div>'+pgBar('prod',list.length,'renderProductList','free');
  }else{
    let tq=0,tv=0;
    list.forEach(p=>{const s=stock(p.id);tq+=s;tv+=Math.max(0,s)*p.cost});
    const rows=pgSlice('prod',list).map(p=>{const s=stock(p.id),v=Math.max(0,s)*p.cost;return `<tr><td><img class="thumb" src="${img(p)}" data-zoom alt=""></td><td><b>${esc(p.code)}</b></td><td>${esc(p.name)}</td><td><span class="tag cat">${esc(p.cat)}</span></td><td class="c">${esc(p.unit)}</td><td class="n">${fmt(p.cost)}</td><td class="n">${fmt(p.price)}</td><td class="n"><b>${fmt(s)}</b></td><td class="n">${fmt(p.min)}</td><td class="n">${fmt(v)}</td><td>${stTag(status(p))}</td><td class="c" style="white-space:nowrap"><button class="btn sm ico" title="Sửa" onclick="openProduct('${p.id}')">✏️</button> <button class="btn sm ico" title="Xóa" onclick="delProduct('${p.id}')">🗑️</button></td></tr>`}).join('');
    box.innerHTML=`<div class="card"><div class="tbl-wrap"><table><thead><tr><th>Ảnh</th><th>Mã</th><th>Tên sản phẩm</th><th>Nhóm</th><th class="c">ĐVT</th><th class="n">Giá nhập</th><th class="n">Giá bán</th><th class="n">Tồn</th><th class="n">Tồn tối thiểu</th><th class="n">Giá trị tồn</th><th>Trạng thái</th><th></th></tr></thead><tbody>${rows}</tbody><tfoot><tr><td colspan="7">Tổng cộng ${list.length} mặt hàng</td><td class="n">${fmt(tq)}</td><td></td><td class="n">${fmt(tv)}</td><td colspan="2"></td></tr></tfoot></table></div>${pgBar('prod',list.length,'renderProductList')}</div>`;
  }
}

/* modal sản phẩm */
let EDIT=null,TMPIMG='';
function openModal(html,size){const m=$('#modal');m.className='modal'+(size?' '+size:'');m.innerHTML=html;$('#ov').classList.add('show')}
function closeModal(){$('#ov').classList.remove('show')}
$('#ov').addEventListener('mousedown',e=>{if(e.target.id==='ov')closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();$('#zoom').classList.remove('show')}});
function openProduct(id){
  EDIT=id?prod(id):null;const p=EDIT||{code:autoCode(),name:'',cat:'',unit:'Cái',cost:'',price:'',min:10,openQty:0,img:'',note:''};TMPIMG=p.img||'';
  openModal(`<div class="modal-h"><h3>${EDIT?'Sửa sản phẩm':'Thêm sản phẩm mới'}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><div class="pform">
    <div><div class="up" id="upBox">${TMPIMG?`<img src="${TMPIMG}" alt="">`:''}<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="M21 15l-5-5L5 21"/></svg><b>Bấm hoặc kéo thả ảnh</b>JPG, PNG. Ảnh tự thu nhỏ để lưu gọn.</div>
      <div style="display:flex;gap:8px;margin-top:10px"><button class="btn sm" style="flex:1;justify-content:center" onclick="$('#imgFile').click()">Chọn ảnh</button><button class="btn sm danger" onclick="setImg('')">Bỏ ảnh</button></div></div>
    <div>
      <div class="row2"><div class="fg"><label class="f">Mã sản phẩm *</label><input class="inp" id="fCode" value="${esc(p.code)}"></div><div class="fg"><label class="f">Đơn vị tính *</label><input class="inp" id="fUnit" value="${esc(p.unit)}" list="dlUnit"></div></div>
      <div class="fg"><label class="f">Tên sản phẩm *</label><input class="inp" id="fName" value="${esc(p.name)}" placeholder="VD: Cà phê hạt Arabica 1kg"></div>
      <div class="row2"><div class="fg"><label class="f">Nhóm hàng</label><input class="inp" id="fCat" value="${esc(p.cat)}" list="dlCat" placeholder="Chọn hoặc gõ nhóm mới"></div><div class="fg"><label class="f">Tồn tối thiểu (cảnh báo)</label><input class="inp" id="fMin" type="number" min="0" value="${p.min}"></div></div>
      <div class="row2"><div class="fg"><label class="f">Giá nhập (₫)</label><input class="inp" id="fCost" type="number" min="0" value="${p.cost}"></div><div class="fg"><label class="f">Giá bán (₫)</label><input class="inp" id="fPrice" type="number" min="0" value="${p.price}"></div></div>
      <div class="fg"><label class="f">Tồn đầu (số lượng có sẵn khi bắt đầu dùng file)</label><input class="inp" id="fOpen" type="number" min="0" value="${p.openQty}"></div>
      <div class="fg"><label class="f">Ghi chú</label><input class="inp" id="fNote" value="${esc(p.note)}"></div>
      <datalist id="dlCat">${cats().map(c=>`<option>${esc(c)}</option>`).join('')}</datalist>
      <datalist id="dlUnit">${[...new Set(['Cái','Hộp','Thùng','Chai','Gói','Kg','Túi','Ram','Vỉ',...S.products.map(x=>x.unit)])].map(u=>`<option>${esc(u)}</option>`).join('')}</datalist>
    </div></div></div>
  <div class="modal-f">${EDIT?`<button class="btn danger" style="margin-right:auto" onclick="delProduct('${EDIT.id}')">Xóa sản phẩm</button>`:''}<button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="saveProduct()">Lưu sản phẩm</button></div>`);
  const up=$('#upBox');up.onclick=()=>$('#imgFile').click();
  up.ondragover=e=>{e.preventDefault();up.classList.add('drag')};up.ondragleave=()=>up.classList.remove('drag');
  up.ondrop=e=>{e.preventDefault();up.classList.remove('drag');if(e.dataTransfer.files[0])readImg(e.dataTransfer.files[0])};
  setTimeout(()=>$('#fName').focus(),50);
}
function autoCode(){let m=0;S.products.forEach(p=>{const n=parseInt(String(p.code).replace(/\D/g,''))||0;if(n>m)m=n});return 'SP'+String(m+1).padStart(3,'0')}
$('#imgFile').onchange=e=>{if(e.target.files[0])readImg(e.target.files[0]);e.target.value=''};
function readImg(file){
  if(!file.type.startsWith('image/'))return toast('File này không phải hình ảnh','err');
  const fr=new FileReader();fr.onload=()=>{const im=new Image();im.onload=()=>{
    const M=480,k=Math.min(1,M/Math.max(im.width,im.height)),cv=document.createElement('canvas');
    cv.width=Math.round(im.width*k);cv.height=Math.round(im.height*k);const cx=cv.getContext('2d');cx.fillStyle='#fff';cx.fillRect(0,0,cv.width,cv.height);cx.drawImage(im,0,0,cv.width,cv.height);
    setImg(cv.toDataURL('image/jpeg',.82))};im.src=fr.result};fr.readAsDataURL(file)}
function setImg(d){TMPIMG=d;const up=$('#upBox');if(!up)return;const old=up.querySelector('img');if(old)old.remove();if(d){const i=new Image();i.src=d;up.prepend(i)}}
function saveProduct(){
  const v=id=>$(id).value.trim();
  const d={code:v('#fCode').toUpperCase(),name:v('#fName'),unit:v('#fUnit'),cat:v('#fCat')||'Khác',min:+v('#fMin')||0,cost:+v('#fCost')||0,price:+v('#fPrice')||0,openQty:+v('#fOpen')||0,note:v('#fNote'),img:TMPIMG};
  if(!d.code||!d.name||!d.unit)return toast('Thầy điền đủ Mã, Tên và Đơn vị tính nhé','err');
  if(S.products.some(p=>p.code===d.code&&(!EDIT||p.id!==EDIT.id)))return toast('Mã '+d.code+' đã tồn tại','err');
  if(EDIT)Object.assign(EDIT,d);else S.products.push({id:'p'+uid(),...d});
  save();closeModal();toast(EDIT?'Đã cập nhật sản phẩm':'Đã thêm sản phẩm mới');rerender()}
function delProduct(id){const p=prod(id);const n=S.txns.filter(t=>t.pid===id).length;
  if(!confirm(`Xóa sản phẩm "${p.name}"?`+(n?`\n\nSản phẩm này có ${n} phiếu nhập/xuất, các phiếu đó cũng sẽ bị xóa.`:'')))return;
  S.products=S.products.filter(x=>x.id!==id);S.txns=S.txns.filter(t=>t.pid!==id);save();closeModal();toast('Đã xóa sản phẩm');rerender()}

/* Đọc / ghi file Excel .xlsx không cần mạng */
const XLSX=(()=>{
const D={serial(s){const [y,m,d]=String(s).slice(0,10).split('-').map(Number);return (Date.UTC(y,m-1,d)-Date.UTC(1899,11,30))/864e5}};
const sum=(arr,f)=>arr.reduce((s,x)=>s+(+(f?f(x):x)||0),0);
function download(blob,name){const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},1500)}
return (() => {
  const crcT = (() => { const t = new Uint32Array(256); for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; } return t; })();
  const crc32 = b => { let c = 0xFFFFFFFF; for (let i = 0; i < b.length; i++) c = crcT[(c ^ b[i]) & 0xFF] ^ (c >>> 8); return (c ^ 0xFFFFFFFF) >>> 0; };
  const enc = new TextEncoder();
  function zip(files) {
    const parts = [], cd = []; let off = 0;
    const now = new Date(); const dt = ((now.getFullYear() - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate(); const tm = (now.getHours() << 11) | (now.getMinutes() << 5) | (now.getSeconds() >> 1);
    for (const f of files) {
      const name = enc.encode(f.name), data = typeof f.data === 'string' ? enc.encode(f.data) : f.data, crc = crc32(data);
      const h = new DataView(new ArrayBuffer(30));
      h.setUint32(0, 0x04034b50, true); h.setUint16(4, 20, true); h.setUint16(6, 0x0800, true); h.setUint16(8, 0, true); h.setUint16(10, tm, true); h.setUint16(12, dt, true);
      h.setUint32(14, crc, true); h.setUint32(18, data.length, true); h.setUint32(22, data.length, true); h.setUint16(26, name.length, true); h.setUint16(28, 0, true);
      parts.push(new Uint8Array(h.buffer), name, data);
      const c = new DataView(new ArrayBuffer(46));
      c.setUint32(0, 0x02014b50, true); c.setUint16(4, 20, true); c.setUint16(6, 20, true); c.setUint16(8, 0x0800, true); c.setUint16(10, 0, true); c.setUint16(12, tm, true); c.setUint16(14, dt, true);
      c.setUint32(16, crc, true); c.setUint32(20, data.length, true); c.setUint32(24, data.length, true); c.setUint16(28, name.length, true);
      c.setUint32(42, off, true);
      cd.push(new Uint8Array(c.buffer), name);
      off += 30 + name.length + data.length;
    }
    const cdSize = cd.reduce((s, a) => s + a.length, 0);
    const e = new DataView(new ArrayBuffer(22));
    e.setUint32(0, 0x06054b50, true); e.setUint16(8, files.length, true); e.setUint16(10, files.length, true); e.setUint32(12, cdSize, true); e.setUint32(16, off, true);
    return new Blob([...parts, ...cd, new Uint8Array(e.buffer)], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  }
  const x = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '');
  const colName = i => { let s = ''; i++; while (i > 0) { const m = (i - 1) % 26; s = String.fromCharCode(65 + m) + s; i = Math.floor((i - 1) / 26); } return s; };
  // Kiểu ô: 1 tiêu đề cột, 2 chữ, 3 số #,##0, 4 ngày, 5 tiêu đề lớn, 6 tổng số, 7 %, 8 tổng chữ, 9 dòng phụ, 10 số lẻ, 11 chữ giữa, 12 tổng số lẻ
  const ST = { text: 2, money: 3, int: 3, date: 4, pct: 7, num: 10, center: 11 };
  function styles(hex) {
    const c = 'FF' + hex.replace('#', '').toUpperCase();
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
</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>`;
  }
  function cell(ref, v, type, s) {
    if (v === null || v === undefined || v === '') return `<c r="${ref}" s="${s}"/>`;
    if (type === 'date') { if (!/^\d{4}-\d{2}-\d{2}/.test(String(v))) return `<c r="${ref}" s="11" t="inlineStr"><is><t>${x(v)}</t></is></c>`; return `<c r="${ref}" s="${s}"><v>${D.serial(v)}</v></c>`; }
    if (['money', 'int', 'num', 'pct'].includes(type)) { const n = type === 'pct' ? (+v || 0) / 100 : +v || 0; return `<c r="${ref}" s="${s}"><v>${n}</v></c>`; }
    return `<c r="${ref}" s="${s}" t="inlineStr"><is><t xml:space="preserve">${x(v)}</t></is></c>`;
  }
  function sheetXML(sh) {
    const cols = sh.columns, nc = cols.length, last = colName(nc - 1);
    let r = 0; const rows = []; const merges = [];
    if (sh.title) { r++; rows.push(`<row r="${r}" ht="24" customHeight="1">${cell('A' + r, sh.title, 'text', 5)}</row>`); merges.push(`A${r}:${last}${r}`); }
    (sh.subtitles || []).forEach(t => { r++; rows.push(`<row r="${r}">${cell('A' + r, t, 'text', 9)}</row>`); merges.push(`A${r}:${last}${r}`); });
    if (r) r++;
    r++; const hr = r;
    rows.push(`<row r="${r}" ht="30" customHeight="1">${cols.map((c, i) => cell(colName(i) + r, c.label, 'text', 1)).join('')}</row>`);
    for (const row of sh.rows) {
      r++;
      rows.push(`<row r="${r}">${cols.map((c, i) => { const t = c.type || 'text'; return cell(colName(i) + r, Array.isArray(row) ? row[i] : row[c.key], t, ST[t] || 2); }).join('')}</row>`);
    }
    const lastData = r;
    if (sh.totals && sh.rows.length) {
      r++;
      rows.push(`<row r="${r}">${cols.map((c, i) => {
        const ref = colName(i) + r;
        if (c.sum) { const f = `SUBTOTAL(9,${colName(i)}${hr + 1}:${colName(i)}${lastData})`; const v = sum(sh.rows, row => Array.isArray(row) ? row[i] : row[c.key]); return `<c r="${ref}" s="${c.type === 'num' ? 12 : 6}"><f>${f}</f><v>${v}</v></c>`; }
        return cell(ref, i === 0 ? (sh.totalLabel || 'TỔNG CỘNG') : '', 'text', 8);
      }).join('')}</row>`);
    }
    if ((sh.footer || []).length) r++;
    (sh.footer || []).forEach(t => { r++; rows.push(`<row r="${r}">${cell('A' + r, t, 'text', 9)}</row>`); });
    const colsXml = `<cols>${cols.map((c, i) => `<col min="${i + 1}" max="${i + 1}" width="${c.width || ({ money: 15, date: 12, pct: 9, num: 9, int: 9 }[c.type] || 18)}" customWidth="1"/>`).join('')}</cols>`;
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:${last}${Math.max(r, 1)}"/><sheetViews><sheetView workbookViewId="0"${sh.noGrid ? ' showGridLines="0"' : ''}><pane ${sh.freezeCols ? `xSplit="${sh.freezeCols}" ` : ''}ySplit="${hr}" topLeftCell="${sh.freezeCols ? colName(sh.freezeCols) : 'A'}${hr + 1}" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>${colsXml}<sheetData>${rows.join('')}</sheetData>${sh.rows.length && !sh.noFilter ? `<autoFilter ref="A${hr}:${last}${lastData}"/>` : ''}${merges.length ? `<mergeCells count="${merges.length}">${merges.map(m => `<mergeCell ref="${m}"/>`).join('')}</mergeCells>` : ''}<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="${sh.portrait ? 'portrait' : 'landscape'}" fitToWidth="1" fitToHeight="0"/></worksheet>`;
  }
  function save(fileName, sheets) {
    const used = {};
    const names = sheets.map(s => { let n = String(s.name || 'Sheet').replace(/[\[\]:*?\/\\]/g, ' ').slice(0, 31) || 'Sheet'; let k = n, i = 2; while (used[k.toLowerCase()]) k = n.slice(0, 28) + ' ' + i++; used[k.toLowerCase()] = 1; return k; });
    const files = [
      { name: '[Content_Types].xml', data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>${sheets.map((_, i) => `<Override PartName="/xl/worksheets/sheet${i + 1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}</Types>` },
      { name: '_rels/.rels', data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>` },
      { name: 'xl/workbook.xml', data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>${names.map((n, i) => `<sheet name="${x(n)}" sheetId="${i + 1}" r:id="rId${i + 1}"/>`).join('')}</sheets><definedNames>${sheets.map((s, i) => s.rows.length && !s.noFilter ? `<definedName name="_xlnm._FilterDatabase" localSheetId="${i}" hidden="1">'${x(names[i]).replace(/'/g, "''")}'!$A$${filterRow(s)}:$${colName(s.columns.length - 1)}$${filterRow(s) + s.rows.length}</definedName>` : '').join('')}</definedNames></workbook>` },
      { name: 'xl/_rels/workbook.xml.rels', data: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${sheets.map((_, i) => `<Relationship Id="rId${i + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${i + 1}.xml"/>`).join('')}<Relationship Id="rId${sheets.length + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>` },
      { name: 'xl/styles.xml', data: styles('#4f46e5') },
      ...sheets.map((s, i) => ({ name: `xl/worksheets/sheet${i + 1}.xml`, data: sheetXML(s) }))
    ];
    download(zip(files), fileName.replace(/\.xlsx$/i, '') + '.xlsx');
    toast('Đã tải file Excel ' + fileName + '.xlsx');
  }
  function filterRow(s) { return (s.title ? 1 : 0) + (s.subtitles || []).length + ((s.title || (s.subtitles || []).length) ? 1 : 0) + 1; }
  // Đọc sheet đầu tiên của file .xlsx (hoặc .csv) thành mảng các dòng, không cần mạng
  async function unzip(buf) {
    const u = new Uint8Array(buf), v = new DataView(buf), dec = new TextDecoder(), out = {};
    let e = u.length - 22; while (e >= 0 && v.getUint32(e, true) !== 0x06054b50) e--;
    if (e < 0) throw new Error('Không phải file Excel (.xlsx)');
    let p = v.getUint32(e + 16, true); const n = v.getUint16(e + 10, true);
    for (let i = 0; i < n; i++) {
      const method = v.getUint16(p + 10, true), size = v.getUint32(p + 20, true), nl = v.getUint16(p + 28, true), xl = v.getUint16(p + 30, true), cl = v.getUint16(p + 32, true), lo = v.getUint32(p + 42, true);
      const name = dec.decode(u.subarray(p + 46, p + 46 + nl)); p += 46 + nl + xl + cl;
      const start = lo + 30 + v.getUint16(lo + 26, true) + v.getUint16(lo + 28, true), raw = u.subarray(start, start + size);
      out[name] = method === 0 ? raw : method === 8 ? new Uint8Array(await new Response(new Blob([raw]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).arrayBuffer()) : null;
    }
    return out;
  }
  const xmlOf = (z, k) => z[k] ? new DOMParser().parseFromString(new TextDecoder().decode(z[k]), 'application/xml') : null;
  const tagAll = (el, t) => [...el.getElementsByTagNameNS('*', t)];
  const colIdx = ref => { let n = 0; for (const ch of ref.replace(/\d+/g, '')) n = n * 26 + ch.charCodeAt(0) - 64; return n - 1; };
  function csvRows(t) {
    const sep = (t.split('\n')[0].match(/;/g) || []).length > (t.split('\n')[0].match(/,/g) || []).length ? ';' : ',';
    const rows = []; let row = [], cur = '', q = false;
    for (let i = 0; i < t.length; i++) {
      const ch = t[i];
      if (q) { if (ch === '"' && t[i + 1] === '"') { cur += '"'; i++; } else if (ch === '"') q = false; else cur += ch; }
      else if (ch === '"') q = true; else if (ch === sep) { row.push(cur); cur = ''; } else if (ch === '\n') { row.push(cur.replace(/\r$/, '')); rows.push(row); row = []; cur = ''; } else cur += ch;
    }
    if (cur || row.length) { row.push(cur); rows.push(row); }
    return rows;
  }
  async function read(file) {
    if (/\.csv$/i.test(file.name)) return csvRows((await file.text()).replace(/^﻿/, ''));
    if (/\.xls$/i.test(file.name)) throw new Error('File .xls đời cũ chưa đọc được. Mở bằng Excel rồi lưu lại thành .xlsx nhé');
    const z = await unzip(await file.arrayBuffer());
    const ss = xmlOf(z, 'xl/sharedStrings.xml'), strs = ss ? tagAll(ss.documentElement, 'si').map(si => tagAll(si, 't').map(t => t.textContent).join('')) : [];
    const wb = xmlOf(z, 'xl/workbook.xml'), rels = xmlOf(z, 'xl/_rels/workbook.xml.rels');
    let path = 'xl/worksheets/sheet1.xml';
    if (wb && rels) {
      const sh = tagAll(wb.documentElement, 'sheet')[0], rid = sh && (sh.getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id') || sh.getAttribute('r:id'));
      const rel = tagAll(rels.documentElement, 'Relationship').find(r => r.getAttribute('Id') === rid);
      if (rel) { const t = rel.getAttribute('Target'); path = t.startsWith('/') ? t.slice(1) : 'xl/' + t.replace(/^\.\//, ''); }
    }
    const doc = xmlOf(z, path); if (!doc) throw new Error('Không tìm thấy sheet dữ liệu trong file');
    return tagAll(doc.documentElement, 'row').map(r => {
      const row = [];
      tagAll(r, 'c').forEach((c, k) => {
        const i = c.getAttribute('r') ? colIdx(c.getAttribute('r')) : k, t = c.getAttribute('t'), ve = tagAll(c, 'v')[0];
        let val = ve ? ve.textContent : '';
        if (t === 's') val = strs[+val] ?? ''; else if (t === 'inlineStr') val = tagAll(c, 't').map(x => x.textContent).join(''); else if (t === 'b') val = val === '1'; else if (val !== '' && t !== 'str' && t !== 'e') val = +val;
        row[i] = val;
      });
      return Array.from(row, x => x ?? '');
    });
  }
  return { save, read };
})();
})();

/* =========================================================
   NHẬP DANH SÁCH SẢN PHẨM (file Excel .xlsx / .csv hoặc dán từ Excel)
   ========================================================= */
const IMP_COLS=[
  {key:'code',label:'Mã sản phẩm',w:13,alias:['ma','masp','masanpham','mahang','mahh','mavt','mavattu','code','sku']},
  {key:'name',label:'Tên sản phẩm',w:38,alias:['ten','tensp','tensanpham','tenhang','tenhanghoa','tenvattu','tenhanghoaquycach','tensanphamquycach','sanpham','hanghoa']},
  {key:'cat',label:'Nhóm hàng',w:16,alias:['nhom','nhomhang','nhomsanpham','loai','loaihang','danhmuc']},
  {key:'unit',label:'ĐVT',w:9,alias:['dvt','donvi','donvitinh']},
  {key:'cost',label:'Giá nhập',num:1,w:14,alias:['gianhap','giavon','giamua','dongianhap']},
  {key:'price',label:'Giá bán',num:1,w:14,alias:['giaban','giabanle','dongiaban','gia','dongia']},
  {key:'min',label:'Tồn tối thiểu',num:1,w:12,alias:['tontoithieu','toithieu','tonantoan','min']},
  {key:'openQty',label:'Tồn đầu',num:1,w:10,alias:['tondau','tondauky','soluongton','tonkho','ton','soluong']},
  {key:'note',label:'Ghi chú',w:26,alias:['ghichu','mota','diengiai']}];
const normH=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase().replace(/[^a-z0-9]/g,'');
function impNum(v){if(typeof v==='number')return v;const s=String(v??'').trim();if(!s)return 0;
  if(/^-?\d+[.,]\d{1,2}$/.test(s))return parseFloat(s.replace(',','.'));
  const n=parseInt(s.replace(/[^\d-]/g,''),10);return isNaN(n)?0:n}
let IMP={items:[],mode:'update'};
function impParse(rows){
  rows=rows.filter(r=>r&&r.some(c=>String(c??'').trim()!==''));
  let hr=-1,map={};
  for(let r=0;r<Math.min(rows.length,20)&&hr<0;r++){const m={};
    rows[r].forEach((h,i)=>{const n=normH(h);const c=IMP_COLS.find(c=>c.alias.includes(n))||(n.startsWith('ten')&&!n.startsWith('tennhom')&&IMP_COLS[1]);if(c&&m[c.key]===undefined)m[c.key]=i});
    if(m.name!==undefined){hr=r;map=m}}
  if(hr<0){IMP_COLS.forEach((c,i)=>map[c.key]=i)} // không có dòng tiêu đề: hiểu theo đúng thứ tự cột file mẫu
  let mx=0;S.products.forEach(p=>{const n=parseInt(String(p.code).replace(/\D/g,''))||0;if(n>mx)mx=n});
  const seen=new Set(),out=[];
  const nextCode=()=>{let c;do c='SP'+String(++mx).padStart(3,'0');while(S.products.some(p=>p.code===c)||seen.has(c));return c};
  rows.slice(hr+1).forEach((r,k)=>{
    const g=key=>map[key]===undefined?'':r[map[key]],has={};
    IMP_COLS.forEach(c=>has[c.key]=map[c.key]!==undefined&&String(g(c.key)??'').trim()!=='');
    const name=String(g('name')??'').trim();if(!name)return;
    const o={row:hr+k+2,has,code:String(g('code')??'').trim().toUpperCase(),name,cat:String(g('cat')??'').trim(),unit:String(g('unit')??'').trim(),
      cost:impNum(g('cost')),price:impNum(g('price')),min:impNum(g('min')),openQty:impNum(g('openQty')),note:String(g('note')??'').trim()};
    if(!o.code)o.code=nextCode();
    if(seen.has(o.code)){o.st='dup'}else{seen.add(o.code);o.old=S.products.find(p=>p.code===o.code);o.st=o.old?'old':'new'}
    out.push(o)});
  return out}
function openImport(){
  IMP={items:[],mode:'update'};pgReset('imp');
  openModal(`<div class="modal-h"><h3>Nhập danh sách sản phẩm</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><label class="btn pri" style="cursor:pointer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9M7 14l5-5 5 5M4 3h16"></path></svg>Chọn file Excel<input type="file" id="impFile" accept=".xlsx,.csv" hidden></label>
      <button class="btn" onclick="impTemplate()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"></path></svg>Tải file mẫu</button>
      <span id="impName" style="color:var(--muted);font-size:13px">Chưa chọn file</span></div>
    <p class="hint2">File cần có dòng tiêu đề với các cột: <b>Mã sản phẩm, Tên sản phẩm, Nhóm hàng, ĐVT, Giá nhập, Giá bán, Tồn tối thiểu, Tồn đầu, Ghi chú</b>. Bắt buộc có cột Tên, các cột khác thiếu cũng được. Để trống mã thì phần mềm tự đánh mã. Ảnh sản phẩm thêm sau ở từng sản phẩm.</p>
    <label class="f">Hoặc dán thẳng từ Excel: bôi đen vùng dữ liệu (có cả dòng tiêu đề), bấm Ctrl + C rồi bấm vào ô dưới đây, Ctrl + V</label>
    <textarea class="inp" id="impPaste" rows="3" placeholder="Dán dữ liệu từ Excel vào đây..." style="resize:vertical;font-size:12.5px"></textarea>
    <div class="imp-opt" id="impOpt" style="display:none"><b>Sản phẩm trùng mã đã có:</b><label><input type="radio" name="impMode" value="update" checked> Cập nhật theo danh sách mới</label><label><input type="radio" name="impMode" value="skip"> Bỏ qua, giữ bản cũ</label></div>
    <div id="impT" style="margin-top:10px"></div>
  </div>
  <div class="modal-f"><span id="impSum" style="margin-right:auto;align-self:center;color:var(--muted);font-size:13px"></span><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" id="impOk" disabled onclick="impApply()">Nhập sản phẩm</button></div>`,'wide');
  $$('[name=impMode]').forEach(r=>r.onchange=()=>{IMP.mode=r.value;drawImp()});
  $('#impFile').onchange=async e=>{const f=e.target.files[0];if(!f)return;e.target.value='';
    $('#impName').textContent='Đang đọc '+f.name+'...';
    try{IMP.items=impParse(await XLSX.read(f));pgReset('imp');$('#impPaste').value='';
      $('#impName').innerHTML=`<b>${esc(f.name)}</b> · đọc được ${IMP.items.length} sản phẩm`;
      if(!IMP.items.length)toast('File không có dòng sản phẩm nào có tên','err')}
    catch(err){IMP.items=[];$('#impName').textContent='Chưa đọc được file';toast(err.message||'Không đọc được file','err')}
    drawImp()};
  let tm;$('#impPaste').oninput=e=>{clearTimeout(tm);tm=setTimeout(()=>{const t=e.target.value;
    IMP.items=t.trim()?impParse(t.replace(/\r/g,'').split('\n').map(l=>l.split('\t'))):[];pgReset('imp');
    $('#impName').textContent=t.trim()?'Dữ liệu dán vào · đọc được '+IMP.items.length+' sản phẩm':'Chưa chọn file';drawImp()},250)};
  drawImp()}
function drawImp(){
  const it=IMP.items,nNew=it.filter(o=>o.st==='new').length,nOld=it.filter(o=>o.st==='old').length,nDup=it.filter(o=>o.st==='dup').length;
  const n=nNew+(IMP.mode==='update'?nOld:0),ok=$('#impOk');if(!ok)return;
  $('#impOpt').style.display=nOld?'':'none';
  $('#impSum').textContent=it.length?`${nNew} mới · ${nOld} trùng mã đã có${nDup?' · '+nDup+' trùng mã trong danh sách (bỏ qua)':''}`:'';
  ok.disabled=!n;ok.textContent=n?`Nhập ${n} sản phẩm`:'Nhập sản phẩm';
  const tag=o=>o.st==='dup'?'<span class="tag zero">Trùng mã, bỏ qua</span>':o.st==='new'?'<span class="tag ok">Thêm mới</span>':IMP.mode==='update'?'<span class="tag low">Cập nhật</span>':'<span class="tag" style="background:#eef1f6;color:var(--muted)">Bỏ qua</span>';
  $('#impT').innerHTML=it.length?`<div class="card" style="box-shadow:none"><div class="tbl-wrap"><table><thead><tr><th class="c">Dòng</th><th>Mã</th><th>Tên sản phẩm</th><th>Nhóm</th><th class="c">ĐVT</th><th class="n">Giá nhập</th><th class="n">Giá bán</th><th class="n">Tồn tối thiểu</th><th class="n">Tồn đầu</th><th>Tình trạng</th></tr></thead><tbody>
    ${pgSlice('imp',it,10).map(o=>`<tr><td class="c">${o.row}</td><td><b>${esc(o.code)}</b></td><td>${esc(o.name)}</td><td>${esc(o.cat)}</td><td class="c">${esc(o.unit)}</td><td class="n">${o.has.cost?fmt(o.cost):''}</td><td class="n">${o.has.price?fmt(o.price):''}</td><td class="n">${o.has.min?fmt(o.min):''}</td><td class="n">${o.has.openQty?fmt(o.openQty):''}</td><td>${tag(o)}</td></tr>`).join('')}
    </tbody></table></div>${pgBar('imp',it.length,'drawImp')}</div>`:'<div class="empty" style="padding:20px">Chọn file Excel hoặc dán dữ liệu để xem trước danh sách</div>'}
function impApply(){
  let add=0,upd=0;
  IMP.items.forEach(o=>{if(o.st==='dup')return;
    if(o.old){if(IMP.mode!=='update')return;
      IMP_COLS.forEach(c=>{if(c.key!=='code'&&o.has[c.key])o.old[c.key]=o[c.key]});upd++}
    else{S.products.push({id:'p'+uid(),code:o.code,name:o.name,cat:o.cat||'Khác',unit:o.unit||'Cái',cost:o.cost,price:o.price,min:o.has.min?o.min:10,openQty:o.openQty,img:'',note:o.note});add++}});
  save();closeModal();pgReset('prod');rerender();
  toast(`Đã thêm ${add} sản phẩm${upd?', cập nhật '+upd+' sản phẩm':''}. Nhớ bấm Lưu để ghi vào file.`)}
function impTemplate(){
  XLSX.save('Mau nhap danh sach san pham',[{name:'Sản phẩm',title:'DANH SÁCH SẢN PHẨM',subtitles:['Điền từ dòng dưới tiêu đề. Bắt buộc có Tên sản phẩm. Giá và số lượng ghi số, không cần chữ "đ".'],noFilter:true,
    columns:IMP_COLS.map(c=>({label:c.label,type:c.num?'int':'text',width:c.w})),
    rows:[['SP101','Sữa tươi tiệt trùng 1 lít (thùng 12 hộp)','Đồ uống','Thùng',360000,420000,5,20,''],
          ['SP102','Đường kính trắng 1kg','Thực phẩm','Gói',24000,29000,30,100,''],
          ['','Băng keo trong 5cm','Văn phòng phẩm','Cuộn',12000,18000,20,50,'Để trống mã thì phần mềm tự đánh mã']]}])}

/* =========================================================
   NHẬP / XUẤT KHO: một phiếu được nhiều sản phẩm
   - Mỗi dòng hàng lưu thành một giao dịch, các dòng cùng phiếu chung số phiếu
   ========================================================= */
const TX={in:{t:'PHIẾU NHẬP KHO',who:'Nhà cung cấp',btn:'Lưu phiếu nhập',cls:'in',priceLbl:'Đơn giá nhập',verb:'nhập'},
          out:{t:'PHIẾU XUẤT KHO',who:'Khách hàng / Nơi nhận',btn:'Lưu phiếu xuất',cls:'out',priceLbl:'Đơn giá xuất',verb:'xuất'}};
const DRAFT={in:null,out:null}; // phiếu đang lập dở, giữ lại khi chuyển trang
function draft(type){return DRAFT[type]=DRAFT[type]||{date:TODAYS,partner:'',note:'',lines:[]}}
const defPrice=(type,p)=>p?(type==='in'?+p.cost||0:+p.price||0):'';
function newLine(type,pid){const p=prod(pid);return {k:uid(),pid:p?p.id:'',qty:p?1:'',price:defPrice(type,p)}}
const sortedProds=()=>[...S.products].sort((a,b)=>a.code.localeCompare(b.code));
const ICPRINT='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/></svg>';

function renderTxn(type){
  const c=TX[type],el=$('#page-'+type),d=draft(type);
  const partners=[...new Set(S.txns.filter(t=>t.type===type).map(t=>t.partner).filter(Boolean))];
  d.lines=d.lines.filter(l=>prod(l.pid));
  el.innerHTML=`<div class="txl">
   <div class="card hd-${c.cls}">
    <div class="card-h"><h3>${c.t}</h3><span class="tag ${c.cls}">Số ${nextDoc(type)}</span>
      ${type==='in'?`<div class="r"><button class="btn sm" onclick="addLowLines()" title="Thêm tất cả mặt hàng sắp hết / hết hàng, số lượng gợi ý đưa tồn về gấp đôi mức tối thiểu">${IC.warn.replace('<svg','<svg style="color:#d18f00"')}Thêm hàng sắp hết</button></div>`:''}</div>
    <div class="card-b">
     ${S.products.length?'':'<div class="empty">Chưa có sản phẩm nào. <button class="btn pri" onclick="go(\'products\');openProduct()">Thêm sản phẩm</button> <button class="btn" onclick="go(\'products\');openImport()">Nhập danh sách sản phẩm</button></div>'}
     <div class="hgrid">
      <div class="fg"><label class="f">Ngày ${c.verb} *</label><input class="inp" type="date" id="${type}Date" value="${d.date}"></div>
      <div class="fg"><label class="f">${c.who}</label><input class="inp" id="${type}Pt" list="${type}Dl" value="${esc(d.partner)}" placeholder="Gõ tên hoặc chọn"><datalist id="${type}Dl">${partners.map(x=>`<option>${esc(x)}</option>`).join('')}</datalist></div>
      <div class="fg"><label class="f">${type==='in'?'Ghi chú':'Lý do xuất / Ghi chú'}</label><input class="inp" id="${type}N" value="${esc(d.note)}"></div>
     </div>
    </div>
    <div class="sec">
     <div class="sec-t">Hàng hóa trong phiếu <span class="cnt" id="${type}Cnt">0</span></div>
     <div class="tbl-wrap"><table class="lines t-${c.cls}"><thead><tr><th class="c" style="width:40px">#</th><th>Sản phẩm</th><th class="c" style="width:70px">ĐVT</th><th class="n" style="width:110px">Số lượng</th><th class="n" style="width:140px">${c.priceLbl}</th><th class="n" style="width:130px">Thành tiền</th><th style="width:40px"></th></tr></thead><tbody id="${type}Lines"></tbody>
      <tbody class="addrow"><tr><td><span class="plus">+</span></td><td colspan="6"><input class="inp" id="${type}Add" autocomplete="off" placeholder="Chọn sản phẩm: gõ mã hoặc tên rồi chọn trong danh sách (hoặc bấm Enter)"></td></tr></tbody></table></div>
     <div class="ac-list" id="${type}Ac"></div>
    </div>
   </div>
   <div class="side"><div class="card sum" style="border-top:4px solid var(--${c.cls})">
     <small class="lb">Tổng tiền phiếu ${c.verb}</small>
     <div class="big" id="${type}Big" style="color:var(--${c.cls})">0 ₫</div>
     <div class="kv2"><span>Số mặt hàng</span><b id="${type}SumN">0</b></div>
     <div class="kv2"><span>Tổng số lượng</span><b id="${type}SumQ">0</b></div>
     <div class="words" id="${type}Words"></div>
     <div class="acts">
       <button class="btn ${c.cls}" onclick="saveTxn('${type}')">${c.btn}</button>
       <button class="btn" onclick="saveTxn('${type}',true)">${ICPRINT}Lưu &amp; in phiếu</button>
       <button class="lnk" onclick="clearDraft('${type}')">Làm lại phiếu</button>
     </div>
   </div></div>
  </div>
  <div class="card"><div class="card-h"><h3>Phiếu ${c.verb} gần đây</h3><div class="r"><button class="btn sm" onclick="$('#hType').value='${type}';go('history')">Xem chi tiết từng dòng</button></div></div><div id="${type}Recent"></div></div>`;
  $('#'+type+'Date').oninput=e=>d.date=e.target.value;
  $('#'+type+'Pt').oninput=e=>d.partner=e.target.value;
  $('#'+type+'N').oninput=e=>d.note=e.target.value;
  acSetup(type);renderLines(type);renderRecent(type);
}
/* ô thêm sản phẩm: gợi ý kèm ảnh, mã, tồn */
['scroll','resize'].forEach(ev=>window.addEventListener(ev,()=>$$('.ac-list.show').forEach(b=>b._place&&b._place()),true));
const fold=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase();
function acSetup(type){
  const inp=$('#'+type+'Add'),box=$('#'+type+'Ac');let items=[],on=0;
  const draw=()=>{const q=fold(inp.value.trim()),words=q.split(/\s+/).filter(Boolean),had=new Set(draft(type).lines.map(l=>l.pid));
    let list=sortedProds();
    if(words.length)list=list.filter(p=>{const t=fold(p.code+' '+p.name);return words.every(w=>t.includes(w))});
    else if(type==='in')list.sort((a,b)=>(status(a)==='ok')-(status(b)==='ok'));
    items=list.slice(0,30);on=Math.min(on,Math.max(0,items.length-1));
    box.innerHTML=items.length?items.map((p,i)=>{const s=stock(p.id);return `<div class="ac-it${i===on?' on':''}" data-i="${i}"><img src="${img(p)}" alt=""><div class="m"><b>${esc(p.name)}</b><small>${esc(p.code)} · Tồn <span style="color:${s<=0?'var(--danger)':s<=p.min?'#b07a00':'inherit'};font-weight:600">${fmt(s)} ${esc(p.unit)}</span> · ${type==='in'?'Giá nhập '+fmt(p.cost):'Giá bán '+fmt(p.price)}</small></div>${had.has(p.id)?'<span class="had">Đã có trong phiếu</span>':''}</div>`}).join(''):'<div class="ac-none">Không tìm thấy sản phẩm phù hợp</div>';
    box.classList.add('show');place()};
  const place=()=>{const r=inp.getBoundingClientRect();box.style.left=r.left+'px';box.style.width=r.width+'px';
    const below=innerHeight-r.bottom;if(below<240&&r.top>below){box.style.top='';box.style.bottom=(innerHeight-r.top+4)+'px'}else{box.style.bottom='';box.style.top=(r.bottom+4)+'px'}};
  box._place=place;
  const pick=i=>{const p=items[i];if(!p)return;inp.value='';box.classList.remove('show');on=0;addLine(type,p.id)};
  inp.onfocus=()=>{on=0;draw()};
  inp.oninput=()=>{on=0;draw()};
  inp.onblur=()=>setTimeout(()=>box.classList.remove('show'),150);
  inp.onkeydown=e=>{
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();if(!box.classList.contains('show'))draw();on=(on+(e.key==='ArrowDown'?1:-1)+items.length)%Math.max(1,items.length);draw();const el=box.querySelector('.ac-it.on');el&&el.scrollIntoView({block:'nearest'})}
    else if(e.key==='Enter'){e.preventDefault();if(items.length)pick(on);else if(inp.value.trim())toast('Không tìm thấy sản phẩm "'+inp.value.trim()+'"','err')}
    else if(e.key==='Escape'){box.classList.remove('show')}};
  box.onmousedown=e=>{e.preventDefault();const it=e.target.closest('.ac-it');if(it)pick(+it.dataset.i)};
}
function renderLines(type){
  const d=draft(type),tb=$('#'+type+'Lines');if(!tb)return;
  tb.innerHTML=d.lines.length?d.lines.map((l,i)=>{const p=prod(l.pid),s=stock(p.id);
    return `<tr data-k="${l.k}"><td class="c" style="color:var(--muted)">${i+1}</td>
    <td><div class="lp"><img class="thumb" src="${img(p)}" data-zoom alt=""><div><b>${esc(p.name)}</b><small>${esc(p.code)} · Tồn <span style="color:${s<=0?'var(--danger)':s<=p.min?'#b07a00':'inherit'};font-weight:600">${fmt(s)}</span></small></div></div></td>
    <td class="c">${esc(p.unit)}</td>
    <td><input class="cin" type="number" min="0" step="any" data-f="qty" value="${l.qty}" placeholder="0"></td>
    <td><input class="cin" type="text" inputmode="numeric" data-f="price" value="${l.price===''?'':fmt(l.price)}" placeholder="0"></td>
    <td class="amt" data-amt>${fmt((+l.qty||0)*(+l.price||0))}</td>
    <td class="c"><button class="xbtn" title="Bỏ khỏi phiếu" onclick="delLine('${type}','${l.k}')">✕</button></td></tr>`}).join('')
    :'';
  tb.querySelectorAll('tr[data-k]').forEach(tr=>{const l=d.lines.find(x=>x.k===tr.dataset.k);
    ['qty','price'].forEach(f=>{const inp=tr.querySelector(`[data-f=${f}]`);
      if(f==='price')inp.onblur=()=>{inp.value=l.price===''?'':fmt(l.price)};
      inp.oninput=()=>{l[f]=f==='price'?(parseInt(inp.value.replace(/\D/g,''),10)||0):inp.value;tr.querySelector('[data-amt]').textContent=fmt((+l.qty||0)*(+l.price||0));lineTotals(type)};
      inp.onkeydown=e=>{if(e.key==='Enter'){e.preventDefault();$('#'+type+'Add').focus()}}})});
  lineTotals(type);
}
function lineTotals(type){
  const ls=draft(type).lines;
  const q=ls.reduce((s,l)=>s+(+l.qty||0),0),v=ls.reduce((s,l)=>s+(+l.qty||0)*(+l.price||0),0);
  $('#'+type+'Cnt').textContent=ls.length;$('#'+type+'SumN').textContent=ls.length;$('#'+type+'SumQ').textContent=fmt(q);
  $('#'+type+'Big').textContent=money(v);
  $('#'+type+'Words').innerHTML=v?`Bằng chữ: <b>${docSo(v)}</b>`:''}
function focusQty(type,k){const i=$(`#${type}Lines tr[data-k="${k}"] [data-f=qty]`);if(i){i.focus();i.select()}}
function addLine(type,pid){
  const d=draft(type),p=prod(pid);if(!p)return;
  const ex=d.lines.find(l=>l.pid===pid);
  if(ex){renderLines(type);focusQty(type,ex.k);return toast(p.name+' đã có trong phiếu, sửa số lượng ở dòng đó nhé')}
  const l=newLine(type,pid);d.lines.push(l);renderLines(type);focusQty(type,l.k)}
function pickProduct(type,pid){addLine(type,pid)}
function addLowLines(){
  const d=draft('in');const lows=S.products.filter(p=>status(p)!=='ok'&&!d.lines.some(l=>l.pid===p.id));
  if(!lows.length)return toast('Không còn mặt hàng sắp hết nào chưa có trong phiếu');
  lows.sort((a,b)=>a.code.localeCompare(b.code)).forEach(p=>{const l=newLine('in',p.id);l.qty=Math.max(1,2*(+p.min||0)-Math.max(0,stock(p.id)));d.lines.push(l)});
  renderLines('in');toast('Đã thêm '+lows.length+' mặt hàng sắp hết, số lượng là gợi ý, sửa lại nếu cần')}
function delLine(type,k){const d=draft(type);d.lines=d.lines.filter(l=>l.k!==k);renderLines(type)}
function clearDraft(type){const d=draft(type);if(d.lines.length&&!confirm('Bỏ phiếu đang lập và làm lại từ đầu?'))return;DRAFT[type]=null;renderTxn(type)}
function saveTxn(type,andPrint){
  const c=TX[type],d=draft(type);
  d.date=$('#'+type+'Date').value;d.partner=$('#'+type+'Pt').value.trim();d.note=$('#'+type+'N').value.trim();
  const ls=d.lines.filter(l=>l.pid&&prod(l.pid));
  if(!ls.length)return toast('Phiếu chưa có sản phẩm nào','err');
  if(!d.date)return toast('Chưa chọn ngày','err');
  for(const l of ls){const p=prod(l.pid),q=Math.round(+l.qty||0);
    if(q<=0)return toast(`Dòng ${d.lines.indexOf(l)+1}: số lượng của ${p.name} phải lớn hơn 0`,'err');
    if(type==='out'){const s=stock(l.pid);if(q>s)return toast(`Không đủ hàng: ${p.name} chỉ còn ${fmt(s)} ${p.unit}, phiếu đang xuất ${fmt(q)}`,'err')}}
  const doc=nextDoc(type);let tot=0;
  ls.forEach(l=>{const q=Math.round(+l.qty),pr=+l.price||0;tot+=q*pr;
    S.txns.push({id:uid(),type,doc,date:d.date,pid:l.pid,qty:q,price:pr,partner:d.partner,note:d.note})});
  save();DRAFT[type]=null;
  toast(`Đã lưu ${doc}: ${c.verb} ${ls.length} mặt hàng, tổng ${money(tot)}`);
  renderTxn(type);
  if(type==='out'){const lows=ls.map(l=>prod(l.pid)).filter(p=>stock(p.id)<=(+p.min||0));
    if(lows.length)setTimeout(()=>toast('Lưu ý: '+lows.map(p=>p.name).join(', ')+' đã xuống dưới mức tồn tối thiểu','err'),400)}
  if(andPrint)setTimeout(()=>printDoc(doc),300);
}
/* gom các dòng theo số phiếu */
function docsOf(type){const m={};
  S.txns.forEach(t=>{if(type&&t.type!==type)return;const g=m[t.doc]=m[t.doc]||{doc:t.doc,type:t.type,date:t.date,partner:t.partner,names:[],q:0,v:0};
    const p=prod(t.pid);g.names.push(p?p.name:'(đã xóa)');g.q+=t.qty;g.v+=t.qty*t.price});
  return Object.values(m).sort((a,b)=>b.date.localeCompare(a.date)||b.doc.localeCompare(a.doc))}
function renderRecent(type){const box=$('#'+type+'Recent');if(!box)return;const list=docsOf(type);
  box.innerHTML=list.length?`<div class="tbl-wrap"><table><thead><tr><th>Số phiếu</th><th>Ngày</th><th>${TX[type].who}</th><th>Hàng hóa</th><th class="n">Số mặt hàng</th><th class="n">Tổng tiền</th><th></th></tr></thead><tbody>${pgSlice('rec'+type,list,10).map(g=>`<tr><td><b>${g.doc}</b></td><td>${vnDate(g.date)}</td><td>${esc(g.partner||'')}</td><td><div class="items-s" title="${esc(g.names.join(', '))}">${esc(g.names.slice(0,2).join(', '))}${g.names.length>2?' và '+(g.names.length-2)+' mặt hàng khác':''}</div></td><td class="n">${g.names.length}</td><td class="n"><b>${fmt(g.v)}</b></td><td class="c"><button class="btn sm" onclick="printDoc('${g.doc}')">${ICPRINT}In phiếu</button></td></tr>`).join('')}</tbody></table></div>${pgBar('rec'+type,list.length,type==='in'?'renderRecentIn':'renderRecentOut')}`:'<div class="empty">Chưa có phiếu nào</div>'}
function renderRecentIn(){renderRecent('in')}
function renderRecentOut(){renderRecent('out')}

/* =========================================================
   IN PHIẾU NHẬP / PHIẾU XUẤT (theo mẫu 01-VT, 02-VT)
   ========================================================= */
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
function printDoc(doc){
  const ls=S.txns.filter(t=>t.doc===doc);if(!ls.length)return toast('Không tìm thấy phiếu '+doc,'err');
  const t0=ls[0],isIn=t0.type==='in',co=S.company||{},[y,m,dd]=t0.date.split('-');
  const tq=ls.reduce((s,t)=>s+t.qty,0),tot=ls.reduce((s,t)=>s+t.qty*t.price,0),dots='..........................................................';
  const signs=isIn?['Người lập phiếu','Người giao hàng','Thủ kho','Kế toán trưởng']:['Người lập phiếu','Người nhận hàng','Thủ kho','Kế toán trưởng','Giám đốc'];
  $('#printArea').innerHTML=`<div class="vt">
   <div class="hd"><div><b>Đơn vị: ${esc(co.name||dots)}</b><br>Địa chỉ: ${esc(co.addr||dots)}${co.phone?'<br>Điện thoại: '+esc(co.phone):''}</div><div class="r"><b>Mẫu số ${isIn?'01':'02'} - VT</b></div></div>
   <h2>${isIn?'PHIẾU NHẬP KHO':'PHIẾU XUẤT KHO'}</h2>
   <div class="dt">Ngày ${dd} tháng ${m} năm ${y}</div>
   <div class="so">Số: <b>${esc(doc)}</b></div>
   <div class="ln">${isIn?'Họ và tên người giao':'Họ và tên người nhận hàng'}: ${dots}</div>
   <div class="ln">${isIn?'Nhà cung cấp':'Khách hàng / Nơi nhận'}: <b>${esc(t0.partner||'')}</b></div>
   <div class="ln">${isIn?'Diễn giải':'Lý do xuất kho'}: ${esc(t0.note||'')}</div>
   <div class="ln">${isIn?'Nhập tại kho':'Xuất tại kho'}: ${esc(co.kho||'')}</div>
   <table><thead><tr><th style="width:40px">STT</th><th style="width:90px">Mã số</th><th>Tên hàng hóa, quy cách</th><th style="width:60px">ĐVT</th><th style="width:80px">Số lượng</th><th style="width:105px">Đơn giá</th><th style="width:125px">Thành tiền</th></tr>
   <tr><th>A</th><th>B</th><th>C</th><th>D</th><th>1</th><th>2</th><th>3</th></tr></thead>
   <tbody>${ls.map((t,i)=>{const p=prod(t.pid)||{};return `<tr><td class="c">${i+1}</td><td class="c">${esc(p.code||'')}</td><td>${esc(p.name||'(đã xóa)')}</td><td class="c">${esc(p.unit||'')}</td><td class="n">${fmt(t.qty)}</td><td class="n">${fmt(t.price)}</td><td class="n">${fmt(t.qty*t.price)}</td></tr>`}).join('')}</tbody>
   <tfoot><tr><td></td><td></td><td class="c">Cộng</td><td></td><td class="n">${fmt(tq)}</td><td></td><td class="n">${fmt(tot)}</td></tr></tfoot></table>
   <div class="ln">Tổng số tiền (viết bằng chữ): <b><i>${docSo(tot)}</i></b></div>
   <div class="sdate">Ngày ${dd} tháng ${m} năm ${y}</div>
   <div class="sign" style="--n:${signs.length}">${signs.map(s=>`<div><b>${s}</b><i>(Ký, họ tên)</i></div>`).join('')}</div>
  </div>`;
  document.body.classList.add('pdoc');
  setTimeout(()=>window.print(),80)}
window.addEventListener('afterprint',()=>document.body.classList.remove('pdoc'));
function saveCompany(){S.company={name:$('#coName').value.trim(),phone:$('#coPhone').value.trim(),addr:$('#coAddr').value.trim(),kho:$('#coKho').value.trim()};save();toast('Đã lưu thông tin in trên phiếu')}

/* =========================================================
   LỊCH SỬ GIAO DỊCH
   ========================================================= */
['#hSearch','#hType','#hFrom','#hTo'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('hist');renderHistory()}));
function histRows(){
  const q=$('#hSearch').value.trim().toLowerCase(),ty=$('#hType').value,f=$('#hFrom').value,t=$('#hTo').value;
  return S.txns.filter(x=>{const p=prod(x.pid);return (!ty||x.type===ty)&&(!f||x.date>=f)&&(!t||x.date<=t)&&(!q||(x.doc+' '+(p?p.code+' '+p.name:'')+' '+x.partner+' '+x.note).toLowerCase().includes(q))})
    .sort((a,b)=>b.date.localeCompare(a.date)||b.doc.localeCompare(a.doc))}
function renderHistory(){
  const rows=histRows();let ti=0,to=0;
  rows.forEach(x=>{const v=x.qty*x.price;x.type==='in'?ti+=v:to+=v});
  const html=pgSlice('hist',rows).map(x=>{const p=prod(x.pid),v=x.qty*x.price;
    return `<tr><td>${vnDate(x.date)}</td><td><b>${x.doc}</b></td><td><span class="tag ${x.type}">${x.type==='in'?'Nhập':'Xuất'}</span></td><td><div class="pcell"><img class="thumb" src="${img(p)}" data-zoom alt=""><div><b>${esc(p?p.name:'(đã xóa)')}</b><small>${esc(p?p.code:'')}</small></div></div></td><td class="n">${fmt(x.qty)} ${esc(p?p.unit:'')}</td><td class="n">${fmt(x.price)}</td><td class="n"><b>${fmt(v)}</b></td><td>${esc(x.partner)}</td><td>${esc(x.note)}</td><td class="c" style="white-space:nowrap"><button class="btn sm ico" title="In phiếu ${x.doc}" onclick="printDoc('${x.doc}')">🖨️</button> <button class="btn sm ico" title="Xóa dòng này khỏi phiếu" onclick="delTxn('${x.id}')">🗑️</button></td></tr>`}).join('');
  $('#hTable').innerHTML=rows.length?`<table><thead><tr><th>Ngày</th><th>Số phiếu</th><th>Loại</th><th>Sản phẩm</th><th class="n">Số lượng</th><th class="n">Đơn giá</th><th class="n">Thành tiền</th><th>Đối tác</th><th>Ghi chú</th><th></th></tr></thead><tbody>${html}</tbody>
  <tfoot><tr><td colspan="4">Tổng ${fmt(rows.length)} phiếu</td><td colspan="3" class="n"><span style="color:var(--in)">Nhập ${money(ti)}</span> · <span style="color:var(--out)">Xuất ${money(to)}</span></td><td colspan="3"></td></tr></tfoot></table>${pgBar('hist',rows.length,'renderHistory')}`:'<div class="empty">Không có giao dịch phù hợp</div>'}
function delTxn(id){const t=S.txns.find(x=>x.id===id);if(!t)return;
  if(t.type==='in'){const s=stock(t.pid);if(s-t.qty<0&&!confirm('Xóa phiếu nhập này sẽ làm tồn kho bị âm. Vẫn xóa?'))return}
  if(!confirm(`Xóa phiếu ${t.doc}?`))return;S.txns=S.txns.filter(x=>x.id!==id);save();toast('Đã xóa phiếu '+t.doc);renderHistory()}

/* =========================================================
   BÁO CÁO XUẤT NHẬP TỒN
   ========================================================= */
let RSORT='code';
['#rFrom','#rTo'].forEach(s=>$(s).addEventListener('input',()=>{$$('#rQuick button').forEach(b=>b.classList.remove('on'));pgReset('rep');renderReport()}));
$('#rCat').addEventListener('input',()=>{pgReset('rep');renderReport()});
$('#rSearch').addEventListener('input',()=>{pgReset('rep');renderRepTable()});
$$('#rQuick button').forEach(b=>b.onclick=()=>{const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t=TODAY;
  if(b.dataset.v==='month')f=new Date(y,m,1);else if(b.dataset.v==='prev'){f=new Date(y,m-1,1);t=new Date(y,m,0)}
  else if(b.dataset.v==='quarter')f=new Date(y,Math.floor(m/3)*3,1);else f=new Date(y,0,1);
  $('#rFrom').value=ymd(f);$('#rTo').value=ymd(t);$$('#rQuick button').forEach(x=>x.classList.toggle('on',x===b));pgReset('rep');renderReport()});
$$('#rSort button').forEach(b=>b.onclick=()=>{RSORT=b.dataset.v;$$('#rSort button').forEach(x=>x.classList.toggle('on',x===b));pgReset('rep');renderRepTable()});
function reportData(q){
  const f=$('#rFrom').value,t=$('#rTo').value,c=$('#rCat').value;q=fold(q||'');
  const d=S.products.filter(p=>(!c||p.cat===c)&&(!q||fold(p.code+' '+p.name).includes(q))).map(p=>{
    let open=+p.openQty||0,inQ=0,outQ=0,inV=0,outV=0;const cost=+p.cost||0;
    S.txns.forEach(x=>{if(x.pid!==p.id)return;const sg=x.type==='in'?1:-1;
      if(x.date<f)open+=sg*x.qty;else if(x.date<=t){if(sg>0){inQ+=x.qty;inV+=x.qty*x.price}else{outQ+=x.qty;outV+=x.qty*x.price}}});
    const close=open+inQ-outQ;return {p,open,inQ,outQ,inV,outV,close,openV:Math.max(0,open)*cost,val:Math.max(0,close)*cost,cogs:outQ*cost}});
  const S2={code:(a,b)=>a.p.code.localeCompare(b.p.code),out:(a,b)=>b.outV-a.outV||b.outQ-a.outQ,val:(a,b)=>b.val-a.val};
  return d.sort(S2[RSORT]||S2.code)}
const repSum=d=>d.reduce((a,r)=>{['open','inQ','outQ','inV','outV','close','openV','val','cogs'].forEach(k=>a[k]+=r[k]);return a},{open:0,inQ:0,outQ:0,inV:0,outV:0,close:0,openV:0,val:0,cogs:0});
function renderReport(){
  if(!$('#rFrom').value){$('#rFrom').value=ymd(new Date(TODAY.getFullYear(),TODAY.getMonth(),1));$('#rTo').value=TODAYS;$('#rQuick [data-v=month]').classList.add('on')}
  fillCatSelect($('#rCat'),'Tất cả nhóm hàng');
  const f=$('#rFrom').value,t=$('#rTo').value,c=$('#rCat').value,d=reportData(),T=repSum(d);
  const range=`Từ ngày ${vnDate(f)} đến ngày ${vnDate(t)}`;
  $('#rPrintRange').textContent=range+(c?' · Nhóm hàng: '+c:'');
  const moved=d.filter(r=>r.inQ||r.outQ).length,outRate=(T.open+T.inQ)>0?T.outQ/(T.open+T.inQ)*100:0,zero=d.filter(r=>r.close<=0).length,gp=T.outV-T.cogs;
  const nIn=docCount(f,t,'in'),nOut=docCount(f,t,'out');
  $('#rHero').innerHTML=`<div class="rh-t"><div><small>BÁO CÁO XUẤT NHẬP TỒN</small><h2>${range}</h2></div><span class="chip">${d.length} mặt hàng${c?' · '+esc(c):' · tất cả nhóm'}</span></div>
    <div class="flow">
      <div class="fb"><small>${IC.box}Tồn đầu kỳ</small><div class="q"><span data-count="${T.open}">0</span><em>đơn vị</em></div><div class="v">${money(T.openV)}</div><div class="v2">tính theo giá nhập</div></div>
      <div class="op">+</div>
      <div class="fb in"><small>${IC.in}Nhập trong kỳ</small><div class="q"><span data-count="${T.inQ}">0</span><em>đơn vị</em></div><div class="v">${money(T.inV)}</div><div class="v2">${nIn} phiếu nhập</div></div>
      <div class="op">−</div>
      <div class="fb out"><small>${IC.out}Xuất trong kỳ</small><div class="q"><span data-count="${T.outQ}">0</span><em>đơn vị</em></div><div class="v">${money(T.outV)}</div><div class="v2">${nOut} phiếu xuất · giá vốn ${short(T.cogs)}</div></div>
      <div class="op">=</div>
      <div class="fb end"><small>${IC.box}Tồn cuối kỳ</small><div class="q"><span data-count="${T.close}">0</span><em>đơn vị</em></div><div class="v">${money(T.val)}</div><div class="v2">${zero?zero+' mặt hàng hết hàng':'không mặt hàng nào hết'}</div></div>
    </div>
    <div class="rh-m"><div><b>${short(gp)} ₫</b><span>Lãi gộp ước tính (giá xuất - giá vốn)</span></div><div><b>${String(Math.round(outRate*10)/10).replace('.',',')}%</b><span>Tỷ lệ hàng đã xuất (xuất / (đầu kỳ + nhập))</span></div><div><b>${moved}/${d.length}</b><span>Mặt hàng có phát sinh</span></div><div><b>${short(T.val-T.openV)} ₫</b><span>Chênh lệch giá trị tồn so với đầu kỳ</span></div></div>`;
  renderRepTable();
  countUp($('#rHero'));
}
function renderRepTable(){
  const d=reportData($('#rSearch').value.trim()),T=repSum(d),st=r=>r.close<=0?'zero':r.close<=(+r.p.min||0)?'low':'ok';
  $('#rCount').textContent=d.length+' mặt hàng';
  $('#rTable').innerHTML=d.length?`<table class="xnt"><thead>
    <tr><th rowspan="2" class="c">STT</th><th rowspan="2">Sản phẩm</th><th rowspan="2" class="c">ĐVT</th><th colspan="2" class="g g0">TỒN ĐẦU KỲ</th><th colspan="2" class="g gi">NHẬP TRONG KỲ</th><th colspan="2" class="g go">XUẤT TRONG KỲ</th><th colspan="2" class="g gc">TỒN CUỐI KỲ</th></tr>
    <tr><th class="s0">Số lượng</th><th class="s0">Giá trị</th><th class="si">Số lượng</th><th class="si">Giá trị</th><th class="so">Số lượng</th><th class="so">Giá trị</th><th class="sc">Số lượng</th><th class="sc">Giá trị</th></tr></thead><tbody>
    ${pgSlice('rep',d).map((r,i)=>`<tr><td class="c">${pgOff('rep')+i+1}</td><td><div class="pcell"><img class="thumb" src="${img(r.p)}" data-zoom alt=""><div><b>${esc(r.p.name)}</b><small>${esc(r.p.code)} · ${esc(r.p.cat)}</small></div></div></td><td class="c">${esc(r.p.unit)}</td>
      <td class="n bl">${fmt(r.open)}</td><td class="n">${fmt(r.openV)}</td>
      <td class="n bl ci">${r.inQ?fmt(r.inQ):'-'}</td><td class="n ci">${r.inV?fmt(r.inV):'-'}</td>
      <td class="n bl co">${r.outQ?fmt(r.outQ):'-'}</td><td class="n co">${r.outV?fmt(r.outV):'-'}</td>
      <td class="n bl cc"><span class="sdot ${st(r)}" title="${ST[st(r)][1]}"></span>${fmt(r.close)}</td><td class="n cc">${fmt(r.val)}</td></tr>`).join('')}
    </tbody><tfoot><tr><td></td><td>TỔNG CỘNG</td><td></td><td class="n">${fmt(T.open)}</td><td class="n">${fmt(T.openV)}</td><td class="n">${fmt(T.inQ)}</td><td class="n">${fmt(T.inV)}</td><td class="n">${fmt(T.outQ)}</td><td class="n">${fmt(T.outV)}</td><td class="n">${fmt(T.close)}</td><td class="n">${fmt(T.val)}</td></tr></tfoot></table><div class="sleg noprint"><span><i class="sdot ok"></i>Còn hàng</span><span><i class="sdot low"></i>Sắp hết</span><span><i class="sdot zero"></i>Hết hàng</span></div>${pgBar('rep',d.length,'renderRepTable')}`:'<div class="empty">Không có mặt hàng phù hợp</div>'}

/* xuất CSV mở được bằng Excel (UTF-8 có BOM) */
function csv(name,rows){const s='﻿'+rows.map(r=>r.map(v=>{v=String(v??'');return /[",;\n]/.test(v)?'"'+v.replace(/"/g,'""')+'"':v}).join(',')).join('\r\n');
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([s],{type:'text/csv;charset=utf-8'}));a.download=name;a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000);toast('Đã tải '+name)}
function exportReport(){const d=reportData($('#rSearch').value.trim()),f=$('#rFrom').value,t=$('#rTo').value,co=S.company||{};
  XLSX.save(`Bao cao XNT ${f} den ${t}`,[{name:'Xuất nhập tồn',title:'BÁO CÁO XUẤT NHẬP TỒN',subtitles:[[co.name,co.addr].filter(Boolean).join(' · ')||'Quản lý kho',`Từ ngày ${vnDate(f)} đến ngày ${vnDate(t)}${$('#rCat').value?' · Nhóm hàng: '+$('#rCat').value:''}`],totals:true,
    columns:[{label:'STT',type:'int',width:6},{label:'Mã SP',width:10},{label:'Tên sản phẩm',width:34},{label:'Nhóm hàng',width:16},{label:'ĐVT',width:8},
      {label:'Tồn đầu kỳ SL',type:'int',sum:true,width:11},{label:'Tồn đầu kỳ giá trị',type:'money',sum:true},{label:'Nhập SL',type:'int',sum:true,width:10},{label:'Nhập giá trị',type:'money',sum:true},
      {label:'Xuất SL',type:'int',sum:true,width:10},{label:'Xuất giá trị',type:'money',sum:true},{label:'Tồn cuối kỳ SL',type:'int',sum:true,width:11},{label:'Giá nhập',type:'money',width:13},{label:'Tồn cuối kỳ giá trị',type:'money',sum:true}],
    rows:d.map((r,i)=>[i+1,r.p.code,r.p.name,r.p.cat,r.p.unit,r.open,r.openV,r.inQ,r.inV,r.outQ,r.outV,r.close,r.p.cost,r.val])}])}
function exportHistory(){csv('Lich_su_nhap_xuat.csv',[['Ngày','Số phiếu','Loại','Mã SP','Tên sản phẩm','ĐVT','Số lượng','Đơn giá','Thành tiền','Đối tác','Ghi chú'],...histRows().map(x=>{const p=prod(x.pid)||{};return [vnDate(x.date),x.doc,x.type==='in'?'Nhập':'Xuất',p.code,p.name,p.unit,x.qty,x.price,x.qty*x.price,x.partner,x.note]})])}

/* =========================================================
   CÀI ĐẶT
   ========================================================= */
function renderSettings(){
  const ic=$('#imgCredit');if(ic)ic.title=SAMPLE.credits||'';
  renderDemoLibrary();
  if(ONLINE){const c=$('.set-grid .card');c.querySelector('h4').textContent='Tải file về máy';
    c.querySelector('p').textContent='Dữ liệu tự lưu trong trình duyệt. Bấm để tải bản sao lưu JSON về máy (Ctrl + S).';
    c.querySelector('.btn.save').textContent='Tải JSON về máy';const f=c.querySelector('.btn.sm');if(f)f.remove()}
  const co=S.company||{};$('#coName').value=co.name||'';$('#coPhone').value=co.phone||'';$('#coAddr').value=co.addr||'';$('#coKho').value=co.kho||'';
  const size=JSON.stringify(S).length;
  const nImg=S.products.filter(p=>p.img&&p.img.startsWith('data:image/jpeg')).length;
  $('#storeInfo').innerHTML=`<b>Dung lượng dữ liệu:</b> ${(size/1024/1024).toFixed(2).replace('.',',')} MB
  <div class="pbar" style="height:8px;margin:10px 0"><i style="width:${Math.min(100,size/5/1024/1024*100)}%;background:var(--accent)"></i></div>
  <span style="color:var(--muted)">${S.products.length} sản phẩm (${nImg} ảnh tải lên) · ${fmt(S.txns.length)} phiếu nhập xuất. ${ONLINE?'Dữ liệu tự lưu trên trình duyệt của máy này, đổi máy hoặc xóa lịch sử duyệt web sẽ mất, nên thỉnh thoảng bấm Tải về.':'Dữ liệu nằm ngay trong file <b>'+esc(FILE_NAME)+'</b> sau mỗi lần bấm Lưu.'} ${DIRTY&&!ONLINE?'<b style="color:#c26a00">Hiện có thay đổi chưa lưu vào file.</b>':''}</span>`}
function backup(){const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(S)],{type:'application/json'}));a.download=`Sao_luu_kho_${TODAYS}.json`;a.click();toast('Đã tải file sao lưu')}
async function backupToServerAndDownload(){
  // Download first: server failure must never prevent a local copy.
  backup();
  const name=$('#backupDatasetName')?.value.trim();
  const status=$('#backupServerStatus'),button=$('#backupServerBtn');
  if(!name||name.length<2){status.textContent='Đã tải JSON về máy. Nhập tên bộ dữ liệu (ít nhất 2 ký tự) để lưu lên server.';return}
  const code=prompt('Nhập mã quản trị để lưu bản sao JSON lên server (không lưu mã trên trình duyệt):');
  if(!code){status.textContent='Đã tải JSON về máy. Chưa lưu lên server vì chưa nhập mã quản trị.';return}
  button.disabled=true;
  status.textContent='Đang lưu bản sao lên server…';
  try{
    const response=await fetch('/demo/quan-ly-xuat-nhap-ton/datasets',{
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'',
        'X-Demo-Backup-Code':code
      },
      body:JSON.stringify({name,data:S})
    });
    if(!response.ok){
      const messages={403:'Mã quản trị không đúng.',413:'JSON vượt giới hạn dung lượng.',419:'Phiên làm việc đã hết hạn, hãy tải lại trang.',429:'Thao tác quá nhanh, vui lòng thử lại sau.',503:'Chưa cấu hình mã quản trị trên server.',409:'Thư viện đã đạt giới hạn lưu trữ.'};
      throw new Error(messages[response.status]||'Server từ chối lưu dữ liệu (HTTP '+response.status+').');
    }
    const saved=await response.json();
    status.textContent='Đã tải JSON về máy và lưu lên server: '+saved.name+' ('+saved.id+').';
    toast('Đã sao lưu lên server và tải về máy');
    await renderDemoLibrary();
  }catch(error){
    status.textContent='Đã tải JSON về máy, nhưng CHƯA lưu được lên server. '+error.message;
    toast('Sao lưu server thất bại; file trên máy vẫn có thể dùng','err');
  }finally{button.disabled=false}
}
$('#restoreFile').onchange=e=>{const f=e.target.files[0];if(!f)return;const fr=new FileReader();fr.onload=()=>{try{const d=JSON.parse(fr.result);if(!valid(d))throw 0;
  if(!confirm(`Khôi phục ${d.products.length} sản phẩm và ${d.txns.length} phiếu? Dữ liệu hiện tại sẽ được thay thế.`))return;S=d;save();toast('Đã khôi phục JSON vào trình duyệt');rerender()}catch(_){toast('File sao lưu không hợp lệ','err')}};fr.readAsText(f);e.target.value=''};
/* Read-only demo datasets: loaded into this browser only, never written back to server. */
const DEMO_DATASETS_URL='/demo/quan-ly-xuat-nhap-ton/datasets';
let DEMO_DATASETS=[];
async function renderDemoLibrary(){
  const select=$('#demoDataset'),info=$('#demoDatasetInfo');
  if(!select)return;
  try{
    const response=await fetch(DEMO_DATASETS_URL,{headers:{Accept:'application/json'},cache:'no-store'});
    if(!response.ok)throw new Error('HTTP '+response.status);
    const payload=await response.json();
    DEMO_DATASETS=Array.isArray(payload.datasets)?payload.datasets:[];
    select.replaceChildren(new Option(DEMO_DATASETS.length?'-- Chọn ngành nghề --':'Chưa có bộ dữ liệu trên server',''));
    for(const item of DEMO_DATASETS){
      select.add(new Option(item.name+' ('+fmt(item.products)+' sản phẩm · '+fmt(item.txns)+' phiếu)',item.id));
    }
    info.textContent=DEMO_DATASETS.length?'Chọn bộ dữ liệu rồi nhấn Nạp dữ liệu demo.':'Chưa có dữ liệu mẫu. Người quản trị cần bổ sung JSON vào kho dữ liệu server.';
  }catch(error){
    select.replaceChildren(new Option('Không thể tải danh sách',''));
    info.textContent='Không thể tải thư viện dữ liệu từ server. Vui lòng thử lại.';
  }
}
async function loadSelectedDemoDataset(){
  const select=$('#demoDataset'),info=$('#demoDatasetInfo'),button=$('#loadDemoDataset');
  const id=select?.value;
  if(!id||!DEMO_DATASETS.some(item=>item.id===id)){toast('Vui lòng chọn bộ dữ liệu demo','err');return}
  const selected=DEMO_DATASETS.find(item=>item.id===id);
  if(!confirm('Nạp bộ dữ liệu '+selected.name+'? Dữ liệu hiện tại trong trình duyệt sẽ bị thay thế. Hãy sao lưu JSON trước nếu cần.'))return;
  button.disabled=true;
  try{
    const response=await fetch(DEMO_DATASETS_URL+'/'+encodeURIComponent(id),{headers:{Accept:'application/json'},cache:'no-store'});
    if(!response.ok)throw new Error('HTTP '+response.status);
    const data=await response.json();
    if(!valid(data)||!data.products.every(p=>p&&typeof p.id==='string'&&typeof p.code==='string'&&typeof p.name==='string')||!data.txns.every(t=>t&&['in','out'].includes(t.type)&&Number(t.qty)>0))throw new Error('Invalid dataset');
    const next={...data,updated:Date.now()};
    if(!persist(next))throw new Error('Browser storage unavailable');
    S=next;DIRTY=true;FROM_DRAFT=true;
    toast('Đã nạp '+data.products.length+' sản phẩm và '+data.txns.length+' phiếu từ server');
    rerender();
  }catch(error){
    info.textContent='Không thể nạp bộ dữ liệu. Dữ liệu hiện tại được giữ nguyên.';
    toast('Nạp dữ liệu demo thất bại','err');
  }finally{button.disabled=false}
}
function resetSample(){if(!confirm('Thay toàn bộ dữ liệu hiện tại bằng dữ liệu mẫu?'))return;S=seed();save();toast('Đã nạp dữ liệu mẫu');rerender()}
function clearAll(){if(!confirm('Xóa SẠCH toàn bộ sản phẩm và phiếu nhập xuất? Nên sao lưu trước khi xóa.'))return;S={products:[],txns:[],ver:2};save();toast('Đã xóa toàn bộ dữ liệu');rerender()}

/* =========================================================
   CHUNG
   ========================================================= */
function toast(msg,type){const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;$('#toasts').appendChild(t);setTimeout(()=>{t.style.opacity=0;t.style.transition='.3s'},3800);setTimeout(()=>t.remove(),4200)}
document.addEventListener('click',e=>{const z=e.target.closest('[data-zoom]');if(z){$('#zoomImg').src=z.src;$('#zoom').classList.add('show')}});
$('#today').textContent=TODAY.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'});
refreshChrome();refreshSave();
go((location.hash||'#dashboard').slice(1));
if(FROM_DRAFT&&!ONLINE)setTimeout(()=>toast('Đang mở các thay đổi chưa lưu từ lần trước. Thầy bấm Lưu để ghi vào file nhé.','err'),600);
