
/* Chụp mã gốc của trang TRƯỚC khi vẽ giao diện, để khi lưu chỉ thay khối dữ liệu */
const TEMPLATE=document.documentElement.outerHTML;

/* =========================================================
   TIỆN ÍCH
   ========================================================= */
const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN').format(Math.round(n||0));
const money=n=>fmt(n)+' ₫';
const short=n=>{n=Math.round(n||0);const a=Math.abs(n);
  if(a>=1e9)return (n/1e9).toFixed(a>=1e10?0:1).replace('.',',')+' tỷ';
  if(a>=1e6)return (n/1e6).toFixed(a>=1e8?0:1).replace('.',',')+' tr';
  if(a>=1e3)return Math.round(n/1e3)+'k';return String(n)};
const pad=n=>String(n).padStart(2,'0');
const ymd=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const vnDate=s=>{if(!s)return '';const [y,m,d]=String(s).split('-');return d+'/'+m+'/'+y};
const hm=t=>{const d=new Date(t);return pad(d.getHours())+':'+pad(d.getMinutes())+' '+pad(d.getDate())+'/'+pad(d.getMonth()+1)};
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2,7);
const pd=s=>{const [y,m,d]=String(s).split('-').map(Number);return new Date(y,m-1,d||1)};
function dDiff(a,b){const A=pd(a),B=pd(b);return Math.round((Date.UTC(B.getFullYear(),B.getMonth(),B.getDate())-Date.UTC(A.getFullYear(),A.getMonth(),A.getDate()))/864e5)}
const TODAY=new Date(),TODAYS=ymd(TODAY),KY_NOW=TODAYS.slice(0,7);
const kyAdd=(k,n)=>{const [y,m]=k.split('-').map(Number);const d=new Date(y,m-1+n,1);return d.getFullYear()+'-'+pad(d.getMonth()+1)};
const kyFirst=k=>k+'-01';
const kyDim=k=>{const [y,m]=k.split('-').map(Number);return new Date(y,m,0).getDate()};
const kyLast=k=>k+'-'+pad(kyDim(k));
const kyLbl=k=>{const [y,m]=k.split('-');return 'Tháng '+(+m)+'/'+y};
const kyShort=k=>{const [y,m]=k.split('-');return 'T'+(+m)+'/'+y.slice(2)};
const addMonths=(s,n)=>{const d=pd(s),t=new Date(d.getFullYear(),d.getMonth()+n,1),dim=new Date(t.getFullYear(),t.getMonth()+1,0).getDate();t.setDate(Math.min(d.getDate(),dim));return ymd(t)};
const addDays=(s,n)=>{const d=pd(s);d.setDate(d.getDate()+n);return ymd(d)};
const fold=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase();
const numIn=v=>{const n=parseInt(String(v??'').replace(/[^\d-]/g,''),10);return isNaN(n)?0:n};
const phoneFmt=s=>{s=String(s||'').replace(/\D/g,'');return s.length===10?s.slice(0,4)+' '+s.slice(4,7)+' '+s.slice(7):s};
const sv=p=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${p}</svg>`;
const IC={
  dash:sv('<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>'),
  map:sv('<path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-5h6v5M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>'),
  door:sv('<path d="M5 21V4a1 1 0 011-1h12a1 1 0 011 1v17M3 21h18"/><path d="M15 12h.01"/>'),
  user:sv('<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/>'),
  users:sv('<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M16 4.5a3.5 3.5 0 010 7M18 14a6 6 0 013.5 6"/>'),
  bolt:sv('<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>'),
  drop:sv('<path d="M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z"/>'),
  receipt:sv('<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>'),
  wallet:sv('<path d="M20 7H5a2 2 0 010-4h13v4"/><path d="M3 5v14a2 2 0 002 2h15V7"/><circle cx="16" cy="14" r="1.3"/>'),
  chart:sv('<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>'),
  gear:sv('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>'),
  book:sv('<path d="M4 4.5A2.5 2.5 0 016.5 2H20v17H6.5A2.5 2.5 0 004 21.5z"/><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>'),
  plus:sv('<path d="M12 5v14M5 12h14"/>'),
  print:sv('<path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/>'),
  copy:sv('<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 01-1-1V4a1 1 0 011-1h10a1 1 0 011 1v1"/>'),
  edit:sv('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>'),
  trash:sv('<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>'),
  renew:sv('<path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/>'),
  out:sv('<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>'),
  down:sv('<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>'),
  up:sv('<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>'),
  save:sv('<path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>'),
  search:sv('<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>'),
  warn:sv('<path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/>'),
  cal:sv('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
  tool:sv('<path d="M14.7 6.3a4 4 0 00-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 005.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>'),
  check:sv('<path d="M20 6L9 17l-5-5"/>'),
  money:sv('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 10v4M18 10v4"/>'),
  doc:sv('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>')
};
function paintIcons(root){(root||document).querySelectorAll('i[data-ic]').forEach(i=>{i.outerHTML=IC[i.dataset.ic]||''})}
const PALETTE=['#6A1B9A','#AB47BC','#0e9fb1','#16a36a','#e6a100','#f0663a','#CE93D8','#64748b'];

/* =========================================================
   DỮ LIỆU
   - Nguồn chính: khối du-lieu nằm ngay trong file (nút Lưu đè dữ liệu ghi vào)
   - Bản tạm: bộ nhớ trình duyệt, giữ thay đổi chưa kịp lưu vào file
   ========================================================= */
const DEF_CFG={ten:'Nhà trọ An Phú',diaChi:'Số 45 Lê Văn Việt, phường Tăng Nhơn Phú, TP. Hồ Chí Minh',sdt:'0903456789',chu:'Nguyễn Văn An',nganHang:'Vietcombank',stk:'0071001234567',chuTK:'NGUYEN VAN AN',
  giaDien:3500,nuocKieu:'khoi',giaNuoc:18000,giaNuocNguoi:100000,racKieu:'phong',rac:30000,wifi:100000,xe:100000,hanTT:5,canhBao:30};
const ONLINE=/^https?:$/.test(location.protocol);
const DEF_NAME='Quản lý phòng trọ.html';
const FILE_NAME=ONLINE?DEF_NAME:decodeURIComponent(location.pathname.split('/').pop()||DEF_NAME);
const LS='phong_tro_phongexcel_v1|'+location.pathname;
const valid=d=>d&&Array.isArray(d.rooms)&&Array.isArray(d.contracts)&&Array.isArray(d.readings)&&Array.isArray(d.invoices)&&Array.isArray(d.payments);
const EMB=(()=>{try{return JSON.parse($('#du-lieu').textContent)}catch(e){return null}})();
let DIRTY=false,ASK_DRAFT=null,PM=null;
function norm(d){d.cfg={...DEF_CFG,...(d.cfg||{})};return d}
function load(){
  let draft=null;try{draft=JSON.parse(localStorage.getItem(LS))}catch(e){}
  const base=valid(EMB)?EMB:null;
  if(valid(draft)&&(draft.updated||0)>((base&&base.updated)||0))ASK_DRAFT=draft;
  return base?norm(base):seed()}
let S=null; // nạp ở cuối file, sau khi mọi hàm đã sẵn sàng
function persist(d){try{localStorage.setItem(LS,JSON.stringify(d));return true}catch(e){return false}}
function save(){S.updated=Date.now();DIRTY=true;PM=null;
  if(!persist(S))toast('Bộ nhớ tạm của trình duyệt đã đầy, hãy bấm Lưu đè dữ liệu ngay','err');
  refreshChrome();refreshSave()}

/* tra cứu nhanh */
const room=id=>S.rooms.find(r=>r.id===id);
const ctr=id=>S.contracts.find(c=>c.id===id);
const invById=id=>S.invoices.find(i=>i.id===id);
const curC=rid=>S.contracts.find(c=>c.roomId===rid&&!c.ngayTra);
const roomSt=r=>curC(r.id)?'thue':(r.sua?'sua':'trong');
const ST_R={thue:'Đang thuê',trong:'Phòng trống',sua:'Đang sửa chữa'};
function paidOf(inv){if(!PM){PM={};S.payments.forEach(p=>PM[p.invId]=(PM[p.invId]||0)+(+p.tien||0))}return PM[inv.id]||0}
const remOf=inv=>Math.max(0,(+inv.tong||0)-paidOf(inv));
function invSt(inv){const p=paidOf(inv);if(p>=(+inv.tong||0))return 'du';if(TODAYS>inv.han)return 'qua';return p>0?'mot':'chua'}
const ST_I={du:['ok','Đã thu đủ'],mot:['w','Thu một phần'],chua:['g','Chưa thu'],qua:['d','Quá hạn']};
const invTag=inv=>{const s=ST_I[invSt(inv)];return `<span class="tag ${s[0]}">${s[1]}</span>`};
const debtOfC=cid=>S.invoices.reduce((s,i)=>i.cid===cid?s+remOf(i):s,0);
const lateOfC=cid=>S.invoices.reduce((s,i)=>i.cid===cid&&TODAYS>i.han?s+remOf(i):s,0);
const invOf=(cid,k)=>S.invoices.find(i=>i.cid===cid&&i.ky===k);
const getRd=(cid,k)=>S.readings.find(r=>r.cid===cid&&r.ky===k);
const isNguoi=()=>S.cfg.nuocKieu==='nguoi';
const rdDone=rd=>!!rd&&rd.dm!==''&&rd.dm!=null&&(isNguoi()||(rd.nm!==''&&rd.nm!=null));
const dueOf=(k,cfg)=>{const [y,m]=k.split('-').map(Number);return ymd(new Date(y,m,Math.min(28,Math.max(1,+(cfg||S.cfg).hanTT||5))))};
const sortRooms=list=>list.sort((a,b)=>String(a.code).localeCompare(String(b.code),'vi',{numeric:true}));
function activeIn(k){const f=kyFirst(k),l=kyLast(k);return S.contracts.filter(c=>c.ngayVao<=l&&c.ngayVao<=TODAYS&&(!c.ngayTra||c.ngayTra>=f))}
function expDays(c){return c.ngayHet?dDiff(TODAYS,c.ngayHet):9999}
function expiring(){return S.contracts.filter(c=>!c.ngayTra&&c.ngayHet&&expDays(c)<=(+S.cfg.canhBao||30)).sort((a,b)=>expDays(a)-expDays(b))}
function expTag(c){if(c.ngayTra)return `<span class="tag g">Đã trả ${vnDate(c.ngayTra)}</span>`;if(!c.ngayHet)return '<span class="tag g">Không thời hạn</span>';const d=expDays(c);
  if(d<0)return `<span class="tag d">Quá hạn ${-d} ngày</span>`;if(d<=(+S.cfg.canhBao||30))return `<span class="tag w">Còn ${d} ngày</span>`;return `<span class="tag ok">Còn ${d} ngày</span>`}

/* =========================================================
   TÍNH HÓA ĐƠN MỘT PHÒNG MỘT THÁNG
   - Tiền phòng tính theo số ngày ở thực tế nếu vào ở hoặc trả phòng giữa tháng
   - Đơn giá được chép vào hóa đơn, đổi giá sau này không ảnh hưởng hóa đơn cũ
   ========================================================= */
function calcInv(r,c,rd,k,cfg,keep){
  cfg=cfg||S.cfg;keep=keep||{};
  const f=kyFirst(k),l=kyLast(k),dim=kyDim(k);
  const st=c.ngayVao>f?c.ngayVao:f,en=c.ngayTra&&c.ngayTra<l?c.ngayTra:l;
  const n=Math.max(0,dDiff(st,en)+1),gia=+c.gia||+r.gia||0;
  const tienPhong=n>=dim?gia:Math.round(gia*n/dim/1000)*1000;
  const has=v=>v!==''&&v!=null;
  const dc=rd&&has(rd.dc)?+rd.dc:'',dm=rd&&has(rd.dm)?+rd.dm:'',nc=rd&&has(rd.nc)?+rd.nc:'',nm=rd&&has(rd.nm)?+rd.nm:'';
  const kwh=dm!==''&&dc!==''?Math.max(0,dm-dc):0,tienDien=kwh*(+cfg.giaDien||0);
  let m3=0,tienNuoc=0;const nk=cfg.nuocKieu==='nguoi'?'nguoi':'khoi';
  if(nk==='nguoi')tienNuoc=(+c.soNguoi||0)*(+cfg.giaNuocNguoi||0);
  else{m3=nm!==''&&nc!==''?Math.max(0,nm-nc):0;tienNuoc=m3*(+cfg.giaNuoc||0)}
  const rac=cfg.racKieu==='nguoi'?(+c.soNguoi||0)*(+cfg.rac||0):(+cfg.rac||0);
  const wifi=c.wifi?(+cfg.wifi||0):0,xe=(+c.soXe||0)*(+cfg.xe||0);
  const khac=+keep.khac||0,giam=+keep.giam||0;
  const tong=tienPhong+tienDien+tienNuoc+rac+wifi+xe+khac-giam;
  return {ky:k,roomId:r.id,cid:c.id,han:keep.han||dueOf(k,cfg),soNgay:n,dim,gia,tienPhong,dc,dm,kwh,giaDien:+cfg.giaDien||0,tienDien,
    nuocKieu:nk,nc,nm,m3,giaNuoc:nk==='nguoi'?+cfg.giaNuocNguoi||0:+cfg.giaNuoc||0,soNguoi:+c.soNguoi||0,tienNuoc,
    rac,racKieu:cfg.racKieu,wifi,xe,soXe:+c.soXe||0,giaXe:+cfg.xe||0,khac,khacNd:keep.khacNd||'',giam,giamNd:keep.giamNd||'',ghiChu:keep.ghiChu||'',tong}}
const dv=i=>(+i.rac||0)+(+i.wifi||0)+(+i.xe||0);
function soHD(r,k,list){let s='HD'+k.replace('-','').slice(2)+'-'+r.code,b=s,n=2;while((list||S.invoices).some(i=>i.so===s))s=b+'-'+n++;return s}

/* chỉ số cũ gợi ý: lấy chỉ số mới của lần ghi gần nhất trước kỳ này */
function prevVals(c,k){
  let best=null;S.readings.forEach(r=>{if(r.roomId!==c.roomId||r.ky>=k)return;if(!best||r.ky>best.ky)best=r});
  const has=v=>v!==''&&v!=null;
  if(best&&best.cid===c.id)return {dc:has(best.dm)?+best.dm:0,nc:has(best.nm)?+best.nm:0};
  return {dc:has(c.dienDau)?+c.dienDau:(best&&has(best.dm)?+best.dm:0),nc:has(c.nuocDau)?+c.nuocDau:(best&&has(best.nm)?+best.nm:0)}}
function lastMeter(rid){let best=null;S.readings.forEach(r=>{if(r.roomId===rid&&r.dm!==''&&(!best||r.ky>best.ky))best=r});return best}

/* =========================================================
   DỮ LIỆU MẪU (sinh theo ngày hôm nay, số ngẫu nhiên cố định)
   ========================================================= */
function seed(){
  let rs=11;const rnd=()=>{rs=(rs*16807)%2147483647;return (rs-1)/2147483646};const ri=(a,b)=>a+Math.floor(rnd()*(b-a+1));const pick=a=>a[ri(0,a.length-1)];
  const cfg={...DEF_CFG};
  const R=[['A101','Dãy A',1,20,2800000,3,'Máy lạnh, nóng lạnh, WC riêng'],['A102','Dãy A',1,20,2800000,3,'Máy lạnh, nóng lạnh, WC riêng'],['A103','Dãy A',1,18,2500000,2,'Máy lạnh, WC riêng'],['A104','Dãy A',1,18,2500000,2,'Máy lạnh, WC riêng'],['A105','Dãy A',1,25,3200000,4,'Gác lửng, máy lạnh, nóng lạnh'],
    ['A201','Dãy A',2,20,2700000,3,'Ban công, máy lạnh'],['A202','Dãy A',2,20,2700000,3,'Ban công, máy lạnh'],['A203','Dãy A',2,18,2400000,2,'Máy lạnh, WC riêng'],['A204','Dãy A',2,18,2400000,2,'Máy lạnh, WC riêng'],['A205','Dãy A',2,25,3100000,4,'Gác lửng, ban công, máy lạnh'],
    ['B101','Dãy B',1,28,3500000,4,'Gác lửng, bếp riêng, máy lạnh'],['B102','Dãy B',1,28,3500000,4,'Gác lửng, bếp riêng, máy lạnh'],['B103','Dãy B',1,24,3000000,3,'Gác lửng, máy lạnh'],['B104','Dãy B',1,24,3000000,3,'Gác lửng, máy lạnh'],
    ['B201','Dãy B',2,28,3400000,4,'Gác lửng, ban công, bếp riêng'],['B202','Dãy B',2,24,2900000,3,'Gác lửng, ban công'],['B203','Dãy B',2,24,2900000,3,'Gác lửng, ban công'],['B204','Dãy B',2,22,2700000,2,'Ban công, máy lạnh']];
  const rooms=R.map(a=>({id:'r'+a[0],code:a[0],day:a[1],tang:a[2],dt:a[3],gia:a[4],toiDa:a[5],tienIch:a[6],sua:a[0]==='A205',note:a[0]==='A205'?'Đang sơn lại tường, thay vòi sen':''}));
  const rm=code=>rooms.find(r=>r.code===code);
  const que=['Long An','Tiền Giang','Bến Tre','Đồng Tháp','Quảng Ngãi','Bình Định','Nghệ An','Thanh Hóa','Đắk Lắk','An Giang','Cần Thơ','Bình Thuận','Phú Yên','Quảng Nam'];
  const nghe=['Nhân viên văn phòng','Công nhân','Sinh viên','Kỹ sư phần mềm','Giáo viên','Kinh doanh tự do','Điều dưỡng','Nhân viên bán hàng','Thợ điện'];
  const dau=['090','091','093','097','098','086','035','038','070','079','083'];
  const prov=['79','74','75','80','82','83','87','89','51','52','40','38','66','92'];
  const plate=()=>pick(['59','51','50','61','62','63','71'])+pick(['X','H','F','G','K','T'])+ri(1,9)+'-'+ri(100,999)+'.'+pad(ri(0,99));
  // phòng, họ tên, vào ở cách nay (ngày), kỳ hạn (tháng), số người, số xe, wifi, nữ, đã trả phòng cách nay (ngày)
  const T=[['A101','Trần Minh Khoa',650,12,2,2,1,0],['A102','Lê Thị Hồng Nhung',410,12,2,1,1,1],['A103','Phạm Quốc Bảo',345,12,1,1,1,0],['A105','Nguyễn Thị Thu Trang',530,12,3,2,1,1],
    ['A201','Võ Thành Đạt',190,6,2,2,0,0],['A202','Huỳnh Ngọc Ánh',260,12,2,1,1,1],['A203','Đặng Văn Hùng',600,12,1,1,0,0],['A204','Bùi Thị Mai Anh',95,6,2,1,1,1],
    ['B101','Đỗ Hoàng Long',820,12,4,3,1,0],['B102','Phan Văn Tài',300,12,3,2,1,0],['B103','Ngô Thị Kim Ngân',175,12,2,2,1,1],['B104','Trương Mỹ Linh',356,12,2,1,1,1],
    ['B201','Lý Gia Huy',450,12,4,2,1,0],['B202','Hồ Thị Thanh Tâm',60,12,2,1,1,1],['B204','Dương Minh Tuấn',14,6,1,1,1,0],
    ['A104','Châu Văn Lộc',500,12,2,1,1,0,52],['B203','Mai Thị Phương',400,12,2,1,1,1,120],['B202','Kiều Văn Nam',380,12,2,2,1,0,75]];
  let contracts=T.map(a=>{const r=rm(a[0]),vao=addDays(TODAYS,-a[2]);let het=addDays(addMonths(vao,a[3]),-1);
    if(!a[8])while(dDiff(TODAYS,het)<-10)het=addDays(addMonths(addDays(het,1),a[3]),-1);
    const gia=a[2]>500?r.gia-100000:r.gia,nam=TODAY.getFullYear()-ri(20,38),ns=nam+'-'+pad(ri(1,12))+'-'+pad(ri(1,28));
    const o={id:'c'+uid()+a[0],roomId:r.id,ten:a[1],sdt:pick(dau)+ri(1000000,9999999),cccd:'0'+pick(prov)+(nam>=2000?2:0)+a[7]+String(nam).slice(2)+ri(100000,999999),ngaySinh:ns,que:pick(que),nghe:pick(nghe),
      ngayVao:vao,ngayHet:het,kyHan:a[3],gia,coc:gia,soNguoi:a[4],soXe:a[5],bienSo:Array.from({length:a[5]},plate).join(', '),wifi:!!a[6],dienDau:null,nuocDau:null,oCung:'',note:'',ngayTra:a[8]?addDays(TODAYS,-a[8]):''};
    if(o.ngayTra){o.ngayHet=o.ngayTra;o.ketToan={ngay:o.ngayTra,coc:o.coc,khauTru:a[0]==='A104'?300000:0,khauTruNd:a[0]==='A104'?'Sơn lại tường, thay ổ khóa':'',truNo:0,traLai:o.coc-(a[0]==='A104'?300000:0)}}
    return o});
  contracts.sort((a,b)=>a.ngayVao.localeCompare(b.ngayVao)).forEach((c,i)=>c.code='HD'+String(i+1).padStart(3,'0'));
  const readings=[],invoices=[],payments=[];
  const meter={};rooms.forEach(r=>meter[r.id]={e:ri(1500,8000),w:ri(150,900)});
  const SEA=[0,.9,.85,1.05,1.25,1.3,1.2,1.1,1.05,1,.95,.9,.9];
  const skipNow=['A105','A202','B103','B201'];
  const dToday=TODAY.getDate();
  for(let m=11;m>=0;m--){const k=kyAdd(KY_NOW,-m),f=kyFirst(k),l=kyLast(k),dim=kyDim(k),mo=+k.slice(5);
    rooms.forEach(r=>{
      contracts.filter(c=>c.roomId===r.id&&c.ngayVao<=l&&(!c.ngayTra||c.ngayTra>=f)).forEach(c=>{
        if(k===KY_NOW&&skipNow.includes(r.code))return;
        const mt=meter[r.id];if(c.dienDau===null){c.dienDau=mt.e;c.nuocDau=mt.w}
        const st=c.ngayVao>f?c.ngayVao:f,en=c.ngayTra&&c.ngayTra<l?c.ngayTra:l,fr=(dDiff(st,en)+1)/dim;
        const kwh=Math.max(5,Math.round((55+c.soNguoi*38+ri(-10,30))*SEA[mo]*fr)),m3=Math.max(1,Math.round(c.soNguoi*ri(25,42)/10*fr));
        const day=k===KY_NOW?Math.max(1,dToday-ri(0,2)):(c.ngayTra&&c.ngayTra<=l?+c.ngayTra.slice(8):dim-ri(0,3));
        const rd={id:uid()+readings.length,cid:c.id,roomId:r.id,ky:k,dc:mt.e,dm:mt.e+kwh,nc:mt.w,nm:mt.w+m3,ngay:k+'-'+pad(day)};
        mt.e+=kwh;mt.w+=m3;readings.push(rd);
        const inv={id:'i'+uid()+invoices.length,so:soHD(r,k,invoices),ngay:rd.ngay,...calcInv(r,c,rd,k,cfg)};invoices.push(inv);
        // thu tiền
        const ck=()=>rnd()<.7?'Chuyển khoản':'Tiền mặt',add=(tien,ngay)=>{if(ngay>TODAYS)return;payments.push({id:uid()+payments.length,invId:inv.id,cid:c.id,ngay,tien,ht:ck(),nguoi:c.ten,note:''})};
        const back=kyAdd(KY_NOW,-m)===k?m:0;
        if(c.ngayTra){add(inv.tong,addDays(inv.han,-ri(0,6)));return}
        if(r.code==='B102'&&back===1)return;
        if(r.code==='B102'&&back===2){add(2000000,addDays(inv.han,ri(2,8)));return}
        if(r.code==='A201'&&back===1){add(Math.round(inv.tong/2/100000)*100000,addDays(inv.han,-1));return}
        if(back===0){if(rnd()<.5)add(inv.tong,addDays(rd.ngay,ri(0,2)));return}
        add(inv.tong,addDays(inv.han,-ri(0,6)));
      })})}
  payments.sort((a,b)=>a.ngay.localeCompare(b.ngay)).forEach((p,i)=>p.so='PT'+String(i+1).padStart(5,'0'));
  contracts.forEach(c=>{if(c.dienDau===null){c.dienDau='';c.nuocDau=''}});
  return {cfg,rooms,contracts,readings,invoices,payments,ver:1,updated:0}}

/* =========================================================
   LƯU ĐÈ VÀO CHÍNH FILE HTML
   ========================================================= */
let FH=null;
let SAVED_AT=ONLINE?0:(EMB&&EMB.savedAt||0);
const idb=(mode,fn)=>new Promise((ok,no)=>{const rq=indexedDB.open('phong_tro_file',1);rq.onupgradeneeded=()=>rq.result.createObjectStore('h');
  rq.onsuccess=()=>{const tx=rq.result.transaction('h',mode),r=fn(tx.objectStore('h'));tx.oncomplete=()=>ok(r&&r.result);tx.onerror=()=>no(tx.error)};rq.onerror=()=>no(rq.error)});
const idbGet=k=>idb('readonly',s=>s.get(k)).catch(()=>null);
const idbSet=(k,v)=>idb('readwrite',s=>s.put(v,k)).catch(()=>null);
function buildHtml(data){
  const json=JSON.stringify(data).replace(/</g,'\\u003c');
  const open='<script id="du-lieu" type="application/json">',close='</scr'+'ipt>';
  const a=TEMPLATE.indexOf(open),b=TEMPLATE.indexOf(close,a);
  return '<!DOCTYPE html>\n'+TEMPLATE.slice(0,a+open.length)+json+TEMPLATE.slice(b)}
async function saveToFile(){
  const data={...S,savedAt:Date.now()};data.updated=Math.max(S.updated||0,data.savedAt);
  const html=buildHtml(data);
  if(ONLINE||!window.showSaveFilePicker)return downloadHtml(html,data);
  try{
    let h=FH;
    if(!h){const kept=await idbGet(LS);if(kept&&kept.name===FILE_NAME)h=kept}
    if(h){let p=await h.queryPermission({mode:'readwrite'});if(p!=='granted')p=await h.requestPermission({mode:'readwrite'});if(p!=='granted')h=null}
    if(!h){if(!await askPick())return;
      h=await showSaveFilePicker({suggestedName:FILE_NAME,types:[{description:'Trang HTML',accept:{'text/html':['.html']}}]})}
    const w=await h.createWritable();await w.write(html);await w.close();
    FH=h;idbSet(LS,h);done(data);
    toast(h.name===FILE_NAME?'Đã lưu đè vào file '+h.name:'Đã lưu vào file '+h.name+' (khác tên file đang mở)');
  }catch(e){if(e&&e.name==='AbortError')return;console.error(e);downloadHtml(html,data)}
}
function done(data){S.updated=data.updated;S.savedAt=data.savedAt;SAVED_AT=data.savedAt;DIRTY=false;persist(S);refreshSave()}
function downloadHtml(html,data){
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([html],{type:'text/html'}));a.download=FILE_NAME;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},2000);
  done(data);toast('Đã tải bản mới về máy, hãy dùng file vừa tải (chép đè lên file cũ cũng được).')}
function askPick(){return new Promise(res=>{
  openModal(`<div class="modal-h"><h3>Lưu đè lần đầu</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p>Trình duyệt cần anh chị chỉ đúng file để ghi đè <b>một lần duy nhất</b>:</p>
  <ol><li>Bấm <b>Chọn file để ghi đè</b> bên dưới.</li><li>Tìm tới thư mục chứa file đang mở, chọn đúng file <b>${esc(FILE_NAME)}</b>.</li><li>Bấm <b>Save / Lưu</b>, rồi <b>Yes / Có</b> khi được hỏi có thay file cũ không.</li><li>Nếu trình duyệt hỏi quyền chỉnh sửa, bấm <b>Allow / Cho phép</b>.</li></ol>
  <p style="color:var(--muted)">Từ lần sau chỉ cần bấm Lưu đè dữ liệu hoặc Ctrl + S là xong.</p></div>
  <div class="modal-f"><button class="btn" id="pkNo">Để sau</button><button class="btn pri" id="pkYes">Chọn file để ghi đè</button></div>`,'small');
  $('#pkNo').onclick=()=>{closeModal();res(false)};$('#pkYes').onclick=()=>{closeModal();res(true)}})}
async function forgetFile(){FH=null;await idbSet(LS,null);saveToFile()}
function refreshSave(){
  const st=$('#saveSt'),bt=$('#saveBtn');
  bt.classList.toggle('dirty',DIRTY);st.className='savest '+(DIRTY?'dirty':'clean');
  st.innerHTML=DIRTY?'<b>Chưa lưu vào file</b>có thay đổi mới':SAVED_AT?'<b>Đã lưu vào file</b>lúc '+hm(SAVED_AT):'<b>Dữ liệu mẫu</b>chưa lưu lần nào'}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveToFile()}});
window.addEventListener('beforeunload',e=>{if(DIRTY){e.preventDefault();e.returnValue=''}});

/* CHIA TRANG */
const PG={},PSIZES=[10,20,50,100];let PRINT_ALL=false;
function pgState(k,def){return PG[k]=PG[k]||{p:0,n:def||20}}
function pgReset(k){if(PG[k])PG[k].p=0}
function pgSlice(k,list,def){const st=pgState(k,def);if(PRINT_ALL)return list;const pages=Math.max(1,Math.ceil(list.length/st.n));if(st.p>=pages)st.p=pages-1;if(st.p<0)st.p=0;return list.slice(st.p*st.n,st.p*st.n+st.n)}
function pgOff(k){const st=pgState(k);return PRINT_ALL?0:st.p*st.n}
function pgBar(k,total,fn){const st=pgState(k),pages=Math.max(1,Math.ceil(total/st.n)),p=st.p;
  if(PRINT_ALL||total<=10)return '';
  const from=total?p*st.n+1:0,to=Math.min(total,(p+1)*st.n);const b=i=>`<button class="${i===p?'on':''}" onclick="pgGo('${k}',${i},'${fn}')">${i+1}</button>`;
  let nums='';if(pages<=7)for(let i=0;i<pages;i++)nums+=b(i);else{nums+=b(0);const a=Math.max(1,p-1),e=Math.min(pages-2,p+1);if(a>1)nums+='<span>…</span>';for(let i=a;i<=e;i++)nums+=b(i);if(e<pages-2)nums+='<span>…</span>';nums+=b(pages-1)}
  return `<div class="pgbar"><span>Hiển thị <b>${fmt(from)}-${fmt(to)}</b> / ${fmt(total)} dòng</span><span class="pgs">Số dòng/trang<select onchange="pgSize('${k}',this.value,'${fn}')">${PSIZES.map(n=>`<option ${n===st.n?'selected':''}>${n}</option>`).join('')}</select>${pages>1?`<button ${p?'':'disabled'} onclick="pgGo('${k}',${p-1},'${fn}')">‹</button>${nums}<button ${p<pages-1?'':'disabled'} onclick="pgGo('${k}',${p+1},'${fn}')">›</button>`:''}</span></div>`}
function pgGo(k,p,fn){pgState(k).p=p;window[fn]()}
function pgSize(k,n,fn){const st=pgState(k);st.n=+n;st.p=0;window[fn]()}
window.addEventListener('beforeprint',()=>{if(document.body.classList.contains('pdoc'))return;PRINT_ALL=true;try{rerender()}catch(e){}});
window.addEventListener('afterprint',()=>{document.body.classList.remove('pdoc');if(PRINT_ALL){PRINT_ALL=false;try{rerender()}catch(e){}}});

/* =========================================================
   ĐIỀU HƯỚNG
   ========================================================= */
const PAGES={
  dashboard:['Dashboard','Tổng quan nhà trọ: lấp đầy, doanh thu, công nợ, việc cần làm',renderDashboard],
  rooms:['Sơ đồ phòng','Danh sách phòng theo dãy, tầng và tình trạng',renderRooms],
  meter:['Ghi điện nước','Nhập chỉ số mới hằng tháng, file tự tính số dùng và tiền',renderMeter],
  invoices:['Hóa đơn tiền phòng','Hóa đơn từng phòng mỗi tháng: in, gửi tin nhắn, thu tiền',renderInvoices],
  debts:['Thu tiền & công nợ','Phòng còn nợ, phiếu thu và sổ thu tiền',renderDebts],
  tenants:['Người thuê & hợp đồng','Hồ sơ người thuê, tiền cọc, thời hạn hợp đồng, trả phòng',renderTenants],
  report:['Báo cáo doanh thu','Doanh thu, đã thu, còn nợ theo tháng và theo phòng',renderReport],
  settings:['Đơn giá & dữ liệu','Đơn giá điện nước dịch vụ, thông tin nhà trọ, lưu và sao lưu',renderSettings],
  guide:['Hướng dẫn','Cách dùng file quản lý phòng trọ',()=>{}],
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
  const N=S.rooms.length,th=S.rooms.filter(r=>roomSt(r)==='thue').length,tr=S.rooms.filter(r=>roomSt(r)==='trong').length,pct=N?Math.round(th/N*100):0;
  $('#sbValue').textContent=pct+'%';$('#sbBar').style.width=pct+'%';$('#sbNote').textContent=`${th}/${N} phòng đang thuê · ${tr} phòng trống`;
  $('#sbName').textContent=S.cfg.ten||'Phòng · Hóa đơn · Công nợ';
  const act=activeIn(KY_NOW),miss=act.filter(c=>!rdDone(getRd(c.id,KY_NOW))).length;
  const qua=S.invoices.filter(i=>invSt(i)==='qua').length,exp=expiring().length;
  const b=(id,n)=>{const e=$(id);e.style.display=n?'':'none';e.textContent=n};
  b('#bEmpty',tr);b('#bMeter',miss);b('#bDebt',qua);b('#bExp',exp)}

/* =========================================================
   BIỂU ĐỒ (SVG tự vẽ, không cần mạng)
   ========================================================= */
function niceMax(v){if(v<=0)return 1;const p=Math.pow(10,Math.floor(Math.log10(v)));const n=v/p;return (n<=1?1:n<=2?2:n<=2.5?2.5:n<=5?5:10)*p}
const tip=$('#tip');
document.addEventListener('mousemove',e=>{const t=e.target.closest&&e.target.closest('[data-tip]');
  if(!t){tip.style.display='none';return}tip.textContent=t.dataset.tip;tip.style.display='block';
  const w=tip.offsetWidth;tip.style.left=Math.min(e.clientX+14,innerWidth-w-8)+'px';tip.style.top=(e.clientY-36)+'px'});
function countUp(root){
  const els=[...root.querySelectorAll('[data-count]')],fin=()=>els.forEach(e=>e.textContent=fmt(+e.dataset.count));
  if(PRINT_ALL||matchMedia('(prefers-reduced-motion: reduce)').matches)return fin();  const t0=performance.now(),D=900;els.forEach(e=>e.textContent='0');
  const step=t=>{const k=Math.min(1,(t-t0)/D),ez=1-Math.pow(1-k,3);els.forEach(e=>e.textContent=fmt(+e.dataset.count*ez));if(k<1)requestAnimationFrame(step)};
  requestAnimationFrame(step);setTimeout(fin,1300)}
function pill(cur,prev,cmp){
  if(prev===undefined)return '<span class="pill na">Hiện tại</span>';
  if(!prev)return `<span class="pill na" title="${cmp}">${cur?'Mới':'-'}</span>`;
  const p=(cur-prev)/Math.abs(prev)*100,up=p>=0;
  return `<span class="pill ${up?'pu':'pd'}" title="${cmp}">${up?'▲':'▼'} ${Math.abs(p).toFixed(1).replace('.',',')}%</span>`}
let SPK=0;
function spark(data,color){const W=240,H=46,n=data.length,mx=Math.max(...data),mn=Math.min(0,...data),r=(mx-mn)||1;
  const pts=data.map((v,i)=>[i*W/(n-1),H-4-(v-mn)/r*(H-12)]),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L'),id='spg'+(++SPK);
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`}
function kc(o){return `<div class="card kc" style="--c:${o.c};--cb:${o.cb}"><div class="kc-h"><span class="kc-ic">${o.icon}</span><span class="kc-l">${o.label}</span>${o.pill}</div><div class="kc-v"><span data-count="${Math.round(o.value)}">0</span>${o.unit?` <small>${o.unit}</small>`:''}</div><div class="kc-s">${o.sub}</div>${spark(o.data,o.c)}</div>`}
function stackChart(ser){
  const W=760,H=300,pl=56,pr=12,pt=16,pb=34,ih=H-pt-pb,n=ser.labels.length;
  const max=niceMax(Math.max(1,...ser.tong,...ser.thu));
  const cw=(W-pl-pr)/n,bw=Math.min(28,cw-16),Y=v=>pt+ih*(1-Math.max(0,v)/max);
  let g=`<rect class="hl" x="${pl+cw*(n-1)+2}" y="${pt-8}" width="${cw-4}" height="${ih+8}" rx="10" fill="#f6eefa"/>`;
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${short(max*i/4)}</text>`}
  ser.labels.forEach((l,i)=>{const cx=pl+cw*i+cw/2;let y0=pt+ih,segs='';
    [[ser.phong[i],'#6A1B9A','Tiền phòng'],[ser.dn[i],'#BA68C8','Điện nước'],[ser.dvk[i],'#E1BEE7','Dịch vụ & khác']].forEach(([v,col,nm])=>{const hh=ih*Math.max(0,v)/max;y0-=hh;
      if(hh>0)segs+=`<rect x="${cx-bw/2}" y="${y0}" width="${bw}" height="${hh}" fill="${col}" data-tip="${esc(nm+' '+l+': '+money(v))}"/>`});
    g+=`<g class="bar" style="animation-delay:${i*45}ms">${segs}</g>`;
    g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax"${i===n-1?' style="font-weight:700;fill:#6A1B9A"':''}>${l}</text>`});
  const pts=ser.thu.map((v,i)=>[pl+cw*i+cw/2,Y(v)]);
  g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="#16a36a" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>`;
  g+=pts.map((p,i)=>`<circle class="seg" cx="${p[0]}" cy="${p[1]}" r="4" fill="#fff" stroke="#16a36a" stroke-width="2.5" data-tip="${esc('Đã thu trong '+ser.labels[i]+': '+money(ser.thu[i]))}"/>`).join('');
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function donut(items,center){
  const tot=items.reduce((s,i)=>s+i.value,0);
  if(!tot)return '<div class="empty">Chưa có hóa đơn trong kỳ</div>';
  const R=70,C=2*Math.PI*R;let off=0,segs='';
  items.forEach(it=>{const len=it.value/tot*C;if(len<=0)return;
    segs+=`<circle class="seg" cx="100" cy="100" r="${R}" fill="none" stroke="${it.color}" stroke-width="26" stroke-dasharray="${Math.max(0,len-2)} ${C}" stroke-dashoffset="${-off}" transform="rotate(-90 100 100)" data-tip="${esc(it.label+': '+money(it.value))}"/>`;off+=len});
  return `<div class="donut-wrap"><svg viewBox="0 0 200 200" class="chart">${segs}<text x="100" y="94" text-anchor="middle" class="ax" style="font-size:12px">${center}</text><text x="100" y="116" text-anchor="middle" style="font-size:19px;font-weight:800;fill:var(--text)">${short(tot)}</text></svg>
  <div class="dl">${items.filter(it=>it.value>0).map(it=>`<div><i style="background:${it.color}"></i>${esc(it.label)}<span>${short(it.value)}</span><em>${Math.round(it.value/tot*100)}%</em></div>`).join('')}</div></div>`}

/* =========================================================
   DASHBOARD
   ========================================================= */
let PERIOD='month';
$$('#dPeriod button').forEach(b=>b.onclick=()=>{PERIOD=b.dataset.v;$$('#dPeriod button').forEach(x=>x.classList.toggle('on',x===b));renderDashboard()});
function rangeOf(k){
  const y=TODAY.getFullYear(),m=TODAY.getMonth()+1;let kF,kT,pF,pT,lbl,cmp;
  if(k==='month'){kF=kT=KY_NOW;pF=pT=kyAdd(KY_NOW,-1);lbl=kyLbl(KY_NOW);cmp='so với tháng trước'}
  else if(k==='prev'){kF=kT=kyAdd(KY_NOW,-1);pF=pT=kyAdd(KY_NOW,-2);lbl=kyLbl(kF);cmp='so với tháng liền trước'}
  else if(k==='quarter'){const q=Math.floor((m-1)/3);kF=y+'-'+pad(q*3+1);kT=kyAdd(kF,2);pF=kyAdd(kF,-3);pT=kyAdd(kF,-1);lbl='Quý '+(q+1)+'/'+y;cmp='so với quý trước'}
  else{kF=y+'-01';kT=y+'-12';pF=(y-1)+'-01';pT=(y-1)+'-12';lbl='Năm '+y;cmp='so với năm trước'}
  return {kF,kT,pF,pT,label:lbl,cmp}}
function sumInv(kF,kT){const o={tong:0,phong:0,dien:0,nuoc:0,rac:0,wifi:0,xe:0,khac:0,paid:0,rem:0,n:0};
  S.invoices.forEach(i=>{if(i.ky<kF||i.ky>kT)return;o.n++;o.tong+=+i.tong||0;o.phong+=+i.tienPhong||0;o.dien+=+i.tienDien||0;o.nuoc+=+i.tienNuoc||0;o.rac+=+i.rac||0;o.wifi+=+i.wifi||0;o.xe+=+i.xe||0;o.khac+=(+i.khac||0)-(+i.giam||0);
    const p=Math.min(paidOf(i),+i.tong||0);o.paid+=p;o.rem+=remOf(i)});return o}
function sumPay(dF,dT){const o={v:0,n:0,ck:0};const so=new Set();S.payments.forEach(p=>{if(p.ngay<dF||p.ngay>dT)return;o.v+=+p.tien||0;if(!so.has(p.so)){so.add(p.so);o.n++;if(p.ht==='Chuyển khoản')o.ck++}});return o}
function series12(){
  const ks=[];for(let i=11;i>=0;i--)ks.push(kyAdd(KY_NOW,-i));const idx={};ks.forEach((k,i)=>idx[k]=i);
  const z=()=>ks.map(()=>0),o={ks,phong:z(),dn:z(),dvk:z(),tong:z(),thu:z(),occ:z(),rem:z()};
  S.invoices.forEach(i=>{const j=idx[i.ky];if(j===undefined)return;o.phong[j]+=+i.tienPhong||0;o.dn[j]+=(+i.tienDien||0)+(+i.tienNuoc||0);o.dvk[j]+=dv(i)+(+i.khac||0)-(+i.giam||0);o.tong[j]+=+i.tong||0;o.rem[j]+=remOf(i)});
  S.payments.forEach(p=>{const j=idx[p.ngay.slice(0,7)];if(j!==undefined)o.thu[j]+=+p.tien||0});
  ks.forEach((k,j)=>{const f=kyFirst(k),l=kyLast(k);o.occ[j]=new Set(S.contracts.filter(c=>c.ngayVao<=l&&(!c.ngayTra||c.ngayTra>=f)).map(c=>c.roomId)).size});
  o.labels=ks.map((k,i)=>{const [y,m]=k.split('-');return 'T'+(+m)+(m==='01'||i===0?'/'+y.slice(2):'')});return o}
function debtRows(){const m={};
  S.invoices.forEach(i=>{const r=remOf(i);if(r<=0)return;const g=m[i.cid]=m[i.cid]||{cid:i.cid,rem:0,n:0,oldest:i.ky,han:i.han,qua:0};
    g.rem+=r;g.n++;if(i.ky<g.oldest){g.oldest=i.ky}if(i.han<g.han)g.han=i.han;if(TODAYS>i.han)g.qua++});
  return Object.values(m).filter(g=>ctr(g.cid)).sort((a,b)=>b.qua-a.qua||b.rem-a.rem)}
function renderDashboard(){
  const r=rangeOf(PERIOD);$('#dRange').textContent=r.label;
  const c=sumInv(r.kF,r.kT),p=sumInv(r.pF,r.pT),ser=series12();
  const N=S.rooms.length,th=S.rooms.filter(x=>roomSt(x)==='thue').length,tr=S.rooms.filter(x=>roomSt(x)==='trong').length,su=N-th-tr,pct=N?Math.round(th/N*100):0;
  const pay=sumPay(kyFirst(r.kF),kyLast(r.kT)),ppay=sumPay(kyFirst(r.pF),kyLast(r.pT));
  const debts=debtRows(),totDebt=debts.reduce((s,g)=>s+g.rem,0),quaInv=S.invoices.filter(i=>invSt(i)==='qua').length;
  // khối lớn đầu trang: doanh thu kỳ + sơ đồ phòng thu nhỏ + vòng lấp đầy
  const byDay={};sortRooms([...S.rooms]).forEach(x=>{(byDay[x.day||'Khác']=byDay[x.day||'Khác']||[]).push(x)});
  const tiles=Object.entries(byDay).map(([d,list])=>`<div class="h-day"><span>${esc(d)}</span><div class="h-tiles">${list.map(x=>{const s=roomSt(x),cc=curC(x.id),dbt=cc?lateOfC(cc.id):0;
    return `<div class="ht ${s}${dbt>0?' no':''}" onclick="openRoom('${x.id}')" data-tip="${esc(x.code+': '+(cc?cc.ten+(dbt?' · nợ quá hạn '+money(dbt):''):ST_R[s]))}">${esc(x.code)}</div>`}).join('')}</div></div>`).join('');
  const C=2*Math.PI*50;
  $('#dHero').innerHTML=`<div>
      <div class="h-k">PHẢI THU ${esc(r.label.toUpperCase())}</div>
      <div class="h-v"><span data-count="${Math.round(c.tong)}">0</span> <small>₫</small></div>
      <div class="h-sub">${pill(c.tong,p.tong,r.cmp)}<span>${r.cmp} (${short(p.tong)} ₫)</span></div>
      <div class="h-stats"><div><b>${short(c.paid)}</b><span>Đã thu của kỳ</span></div><div><b>${short(c.rem)}</b><span>Còn phải thu</span></div><div><b>${c.n}</b><span>Hóa đơn</span></div></div>
      <div class="h-act"><button class="hbtn" onclick="go('meter')">Ghi điện nước</button><button class="hbtn ghost" onclick="go('debts')">Thu tiền</button></div>
    </div>
    <div class="h-chart"><div class="h-ct"><span>Sơ đồ phòng (bấm để xem)</span><span><b>${th}</b> đang thuê · <b>${tr}</b> trống · <b>${su}</b> sửa</span></div>${tiles||'<div style="font-size:13px;color:#e6cdf2">Chưa có phòng nào</div>'}
      <div class="h-leg"><span><i style="background:#fff"></i>Đang thuê</span><span><i style="background:rgba(52,211,153,.5)"></i>Trống</span><span><i style="background:rgba(251,191,36,.6)"></i>Sửa chữa</span><span><i style="background:#ff5c70;border-radius:50%"></i>Nợ quá hạn</span></div></div>
    <div class="h-ring"><svg viewBox="0 0 120 120"><defs><linearGradient id="rgA" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#86f3c7"/><stop offset="1" stop-color="#22c58b"/></linearGradient></defs>
      <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.13)" stroke-width="11"/>
      <circle class="ring-a" cx="60" cy="60" r="50" fill="none" stroke="url(#rgA)" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C*pct/100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
      <text x="60" y="60" text-anchor="middle" style="font-size:27px;font-weight:800;fill:#fff">${pct}%</text><text x="60" y="78" text-anchor="middle" style="font-size:9.5px;fill:#e6cdf2">tỷ lệ lấp đầy</text></svg>
      <b>${th}/${N} phòng</b><span>${tr} trống · ${su} sửa chữa</span></div>`;
  // 4 thẻ số liệu
  const ckPct=pay.n?Math.round(pay.ck/pay.n*100):0;
  $('#kpis').innerHTML=
    kc({c:'#6A1B9A',cb:'var(--chu-dao-nhat)',icon:IC.door,label:'Phòng đang thuê',value:th,unit:'/ '+N+' phòng',pill:'<span class="pill na">'+pct+'%</span>',sub:`${tr} phòng trống · ${su} đang sửa`,data:ser.occ})+
    kc({c:'#AB47BC',cb:'#f6e9f9',icon:IC.receipt,label:'Phải thu trong kỳ',value:c.tong,unit:'₫',pill:pill(c.tong,p.tong,r.cmp),sub:`${c.n} hóa đơn · tiền phòng ${short(c.phong)}`,data:ser.tong})+
    kc({c:'#16a36a',cb:'var(--inbg)',icon:IC.money,label:'Tiền đã thu trong kỳ',value:pay.v,unit:'₫',pill:pill(pay.v,ppay.v,r.cmp),sub:`${pay.n} phiếu thu · ${ckPct}% chuyển khoản`,data:ser.thu})+
    kc({c:'#e0384a',cb:'var(--dangerbg)',icon:IC.warn,label:'Còn nợ (tất cả)',value:totDebt,unit:'₫',pill:pill(0,undefined),sub:`${debts.length} phòng nợ · ${quaInv} hóa đơn quá hạn`,data:ser.rem});
  // biểu đồ 12 tháng
  const sm=a=>a.reduce((s,v)=>s+v,0),T12=sm(ser.tong),P12=sm(ser.thu);
  $('#mSum').innerHTML=`<div><small>Phải thu 12 tháng</small><b style="color:#6A1B9A">${short(T12)} ₫</b></div><div><small>Đã thu 12 tháng</small><b style="color:var(--in)">${short(P12)} ₫</b></div><div><small>Trung bình mỗi tháng</small><b>${short(T12/12)} ₫</b></div><div><small>Phòng thuê bình quân</small><b>${(sm(ser.occ)/12).toFixed(1).replace('.',',')}</b></div>`;
  $('#chMonth').innerHTML=stackChart(ser);
  $('#donutHint').textContent=r.label;
  $('#chCat').innerHTML=donut([{label:'Tiền phòng',value:c.phong,color:PALETTE[0]},{label:'Tiền điện',value:c.dien,color:'#e6a100'},{label:'Tiền nước',value:c.nuoc,color:'#0e9fb1'},{label:'Rác',value:c.rac,color:'#64748b'},{label:'Wifi',value:c.wifi,color:'#AB47BC'},{label:'Gửi xe',value:c.xe,color:'#16a36a'},{label:'Khác (trừ giảm)',value:Math.max(0,c.khac),color:'#f0663a'}],'Tổng phải thu');
  // phòng nợ
  $('#debtCnt').textContent=debts.length?debts.length+' phòng':'';
  $('#debtList').innerHTML=debts.length?debts.slice(0,5).map(g=>{const cc=ctr(g.cid),rr=room(cc.roomId)||{},late=dDiff(g.han,TODAYS);
    return `<div class="li"><span class="rcode" style="${g.qua?'background:var(--dangerbg);color:var(--danger)':''}">${esc(rr.code||'?')}</span><div class="meta"><b>${esc(cc.ten)}</b><small>${g.n} hóa đơn · từ ${kyShort(g.oldest)}${late>0?` · <span class="red">quá hạn ${late} ngày</span>`:' · chưa tới hạn'}</small></div><div class="v red">${short(g.rem)}<small><button class="btn sm" style="margin-top:3px" onclick="openCollect('${g.cid}')">Thu tiền</button></small></div></div>`}).join(''):'<div class="empty">Không phòng nào nợ tiền</div>';
  // hợp đồng sắp hết hạn
  const ex=expiring();$('#expCnt').textContent=ex.length?ex.length+' hợp đồng':'';
  $('#expList').innerHTML=ex.length?ex.slice(0,5).map(cc=>{const rr=room(cc.roomId)||{},d=expDays(cc);
    return `<div class="li"><span class="rcode">${esc(rr.code||'?')}</span><div class="meta"><b>${esc(cc.ten)}</b><small>Hết hạn ${vnDate(cc.ngayHet)} · ${d<0?'<span class="red">đã quá '+(-d)+' ngày</span>':'còn '+d+' ngày'}</small></div><button class="btn sm" onclick="openRenew('${cc.id}')">${IC.renew}Gia hạn</button></div>`}).join(''):'<div class="empty">Không hợp đồng nào sắp hết hạn</div>';
  // việc cần làm
  const act=activeIn(KY_NOW),nRec=act.filter(x=>rdDone(getRd(x.id,KY_NOW))).length,noInv=act.filter(x=>rdDone(getRd(x.id,KY_NOW))&&!invOf(x.id,KY_NOW)).length;
  const invNow=S.invoices.filter(i=>i.ky===KY_NOW),paidNow=invNow.filter(i=>invSt(i)==='du').length;
  const empties=sortRooms(S.rooms.filter(x=>roomSt(x)==='trong'));
  const bar=(a,b,col)=>`<div class="pbar" style="margin-left:44px"><i style="width:${b?Math.round(a/b*100):0}%;background:${col}"></i></div>`;
  $('#todoHint').textContent=kyLbl(KY_NOW);
  $('#todo').innerHTML=`<div class="todo"><div class="todo-h"><span class="tico" style="background:var(--warnbg);color:#a87200">${IC.bolt}</span><div class="meta"><b>Ghi chỉ số điện nước</b><small>Đã ghi ${nRec}/${act.length} phòng${act.length-nRec?' · còn '+(act.length-nRec)+' phòng':''}</small></div><button class="btn sm" onclick="go('meter')">Ghi số</button></div>${bar(nRec,act.length,'#e6a100')}</div>
    <div class="todo"><div class="todo-h"><span class="tico" style="background:var(--chu-dao-nhat);color:var(--chu-dao)">${IC.receipt}</span><div class="meta"><b>Lập hóa đơn tháng này</b><small>${invNow.length} hóa đơn đã lập${noInv?' · <span class="red">'+noInv+' phòng ghi số xong chưa lập</span>':''}</small></div><button class="btn sm" onclick="lapHD(KY_NOW)">Lập</button></div>${bar(invNow.length,act.length,'#6A1B9A')}</div>
    <div class="todo"><div class="todo-h"><span class="tico" style="background:var(--inbg);color:var(--in)">${IC.money}</span><div class="meta"><b>Thu tiền tháng này</b><small>Đã thu đủ ${paidNow}/${invNow.length} hóa đơn</small></div><button class="btn sm" onclick="IVKY=KY_NOW;go('invoices')">Xem</button></div>${bar(paidNow,invNow.length,'#16a36a')}</div>
    <div class="todo"><div class="todo-h"><span class="tico" style="background:var(--inbg);color:var(--in)">${IC.door}</span><div class="meta"><b>Phòng trống cần cho thuê</b><small>${empties.length?empties.length+' phòng, bấm để cho thuê':'Đã kín phòng'}</small></div></div>
      ${empties.length?`<div class="empt">${empties.map(x=>{const since=emptySince(x.id);return `<span onclick="openContract(null,'${x.id}')" data-tip="${esc('Giá '+money(x.gia)+(since?' · trống '+dDiff(since,TODAYS)+' ngày':''))}">${esc(x.code)}${since?' · '+dDiff(since,TODAYS)+' ngày':''}</span>`}).join('')}</div>`:''}</div>`;
  countUp($('#page-dashboard'));
}
function emptySince(rid){let s='';S.contracts.forEach(c=>{if(c.roomId===rid&&c.ngayTra&&c.ngayTra>s)s=c.ngayTra});return s}

/* =========================================================
   SƠ ĐỒ PHÒNG
   ========================================================= */
let RMVIEW='map';
$$('#rmView button').forEach(b=>b.onclick=()=>{RMVIEW=b.dataset.v;$$('#rmView button').forEach(x=>x.classList.toggle('on',x===b));renderRoomList()});
['#rmSearch','#rmDay','#rmSt'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('rm');renderRoomList()}));
const days=()=>[...new Set(S.rooms.map(r=>r.day).filter(Boolean))].sort((a,b)=>a.localeCompare(b,'vi'));
function renderRooms(){
  const sel=$('#rmDay'),v=sel.value;sel.innerHTML='<option value="">Mọi dãy</option>'+days().map(d=>`<option>${esc(d)}</option>`).join('');sel.value=days().includes(v)?v:'';
  const N=S.rooms.length,th=S.rooms.filter(r=>roomSt(r)==='thue'),tr=S.rooms.filter(r=>roomSt(r)==='trong').length;
  const ng=th.reduce((s,r)=>s+(+curC(r.id).soNguoi||0),0),gt=th.reduce((s,r)=>s+(+curC(r.id).gia||0),0),gtr=S.rooms.filter(r=>roomSt(r)!=='thue').reduce((s,r)=>s+(+r.gia||0),0);
  $('#roomStat').innerHTML=ms(IC.door,'var(--chu-dao-nhat)','var(--chu-dao)','Tổng số phòng',N+' phòng')+ms(IC.users,'var(--infobg)','var(--info)','Đang thuê / số người ở',th.length+' phòng · '+ng+' người')+ms(IC.money,'var(--inbg)','var(--in)','Tiền phòng mỗi tháng',short(gt)+' ₫')+ms(IC.warn,'var(--warnbg)','#a87200','Thất thu do phòng trống',short(gtr)+' ₫/tháng');
  renderRoomList()}
function ms(ic,bg,col,lbl,val){return `<div class="card ms"><span class="ic" style="background:${bg};color:${col}">${ic}</span><div><small>${lbl}</small><b>${val}</b></div></div>`}
function roomRows(){
  const q=fold($('#rmSearch').value.trim()),d=$('#rmDay').value,st=$('#rmSt').value;
  return sortRooms(S.rooms.filter(r=>{const c=curC(r.id),s=roomSt(r);
    if(d&&r.day!==d)return false;
    if(st==='no'){if(!c||debtOfC(c.id)<=0)return false}else if(st&&s!==st)return false;
    return !q||fold(r.code+' '+r.day+' '+(c?c.ten+' '+c.sdt:'')).includes(q)}))}
function renderRoomList(){
  const list=roomRows(),box=$('#rmList');
  if(!list.length){box.innerHTML=`<div class="card empty">Không có phòng nào phù hợp.<br><br><button class="btn pri" onclick="openRoomForm()">Thêm phòng</button> <button class="btn" onclick="openImport()">Nhập danh sách phòng</button></div>`;return}
  if(RMVIEW==='map'){
    const g={};list.forEach(r=>{const k=(r.day||'Khác')+'|'+(r.tang||'');(g[k]=g[k]||[]).push(r)});
    box.innerHTML=Object.entries(g).map(([k,rs])=>{const [d,t]=k.split('|'),th=rs.filter(r=>roomSt(r)==='thue').length;
      return `<div class="floor"><div class="floor-h">${esc(d)}${t?' · Tầng '+esc(t):''} <small>${th}/${rs.length} phòng đang thuê</small></div><div class="rooms">${rs.map(roomCard).join('')}</div></div>`}).join('');
  }else{
    let tg=0,td=0;
    const rows=pgSlice('rm',list).map(r=>{const c=curC(r.id),s=roomSt(r),db=c?debtOfC(c.id):0;
      return `<tr><td><span class="rcode">${esc(r.code)}</span></td><td>${esc(r.day)}</td><td class="c">${esc(r.tang)}</td><td class="n">${fmt(r.dt)} m²</td><td class="n">${fmt(r.gia)}</td><td class="c">${fmt(r.toiDa)}</td><td style="max-width:220px">${esc(r.tienIch)}</td><td><span class="tag ${s==='thue'?'p':s==='trong'?'ok':'w'}">${ST_R[s]}</span></td><td>${c?`<div class="pcell"><b>${esc(c.ten)}</b><small>${phoneFmt(c.sdt)}</small></div>`:'<span class="mut">-</span>'}</td><td class="c">${c?c.soNguoi:'-'}</td><td class="n ${db?'red':''}"><b>${db?fmt(db):'-'}</b></td>
      <td><div class="acts"><button class="btn sm" onclick="openRoom('${r.id}')">Chi tiết</button><button class="btn sm ico" title="Sửa phòng" onclick="openRoomForm('${r.id}')">${IC.edit}</button><button class="btn sm ico danger" title="Xóa phòng" onclick="delRoom('${r.id}')">${IC.trash}</button></div></td></tr>`}).join('');
    list.forEach(r=>{tg+=+r.gia||0;const c=curC(r.id);if(c)td+=debtOfC(c.id)});
    box.innerHTML=`<div class="card"><div class="tbl-wrap"><table><thead><tr><th>Phòng</th><th>Dãy</th><th class="c">Tầng</th><th class="n">Diện tích</th><th class="n">Giá thuê</th><th class="c">Tối đa</th><th>Tiện ích</th><th>Tình trạng</th><th>Người thuê</th><th class="c">Số người</th><th class="n">Còn nợ</th><th></th></tr></thead><tbody>${rows}</tbody><tfoot><tr><td colspan="4">Tổng ${list.length} phòng</td><td class="n">${fmt(tg)}</td><td colspan="5"></td><td class="n">${fmt(td)}</td><td></td></tr></tfoot></table></div>${pgBar('rm',list.length,'renderRoomList')}</div>`}}
function roomCard(r){const c=curC(r.id),s=roomSt(r),db=c?debtOfC(c.id):0;let body='',flags='';
  if(c){const d=expDays(c);body=`<div class="rten">${IC.user}<b>${esc(c.ten)}</b></div><div class="rsub">${c.soNguoi} người · ${c.soXe} xe · vào ${vnDate(c.ngayVao)}</div>`;
    const lt=lateOfC(c.id);if(lt>0)flags+=`<span class="flag d">Nợ quá hạn ${short(lt)}</span>`;if(db-lt>0)flags+=`<span class="flag w">Chờ đóng ${short(db-lt)}</span>`;if(c.ngayHet&&d<=(+S.cfg.canhBao||30))flags+=`<span class="flag w">${d<0?'HĐ quá hạn':'HĐ còn '+d+' ngày'}</span>`;if(!db&&!flags)flags='<span class="flag ok">Đã đóng đủ</span>'}
  else if(s==='sua')body=`<div class="rten mut">${IC.tool}<b>Đang sửa chữa</b></div><div class="rsub">${esc(r.note||'Chưa cho thuê')}</div>`;
  else{const since=emptySince(r.id);body=`<div class="rten mut">${IC.door}<b>Phòng trống</b></div><div class="rsub">${since?'Trống từ '+vnDate(since)+' ('+dDiff(since,TODAYS)+' ngày)':'Sẵn sàng cho thuê'}</div>`}
  return `<div class="room ${s}" onclick="openRoom('${r.id}')"><div class="rtop"><span class="rc">${esc(r.code)}</span><span class="rst">${ST_R[s]}</span></div>${body}<div class="rflags">${flags}</div>
    <div class="rfoot"><div><small>Giá thuê</small><strong>${fmt(c?c.gia:r.gia)}</strong></div><div style="text-align:right"><small>Diện tích</small><strong>${fmt(r.dt)} m²</strong></div></div></div>`}

/* chi tiết phòng */
function openRoom(id){const r=room(id);if(!r)return;const c=curC(id),s=roomSt(r);
  const invs=S.invoices.filter(i=>i.roomId===id).sort((a,b)=>b.ky.localeCompare(a.ky)).slice(0,6);
  const hist=S.contracts.filter(x=>x.roomId===id&&x.ngayTra).sort((a,b)=>b.ngayTra.localeCompare(a.ngayTra));
  const lm=lastMeter(id);
  openModal(`<div class="modal-h"><span class="rcode" style="font-size:15px;height:36px">${esc(r.code)}</span><div><h3>Phòng ${esc(r.code)}</h3><small class="mut">${esc(r.day)} · tầng ${esc(r.tang)} · ${fmt(r.dt)} m²</small></div><span class="tag ${s==='thue'?'p':s==='trong'?'ok':'w'}">${ST_R[s]}</span><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="msec">Thông tin phòng</div>
    <div class="kvg"><div><small>Giá niêm yết</small><b>${money(r.gia)}</b></div><div><small>Số người tối đa</small><b>${fmt(r.toiDa)} người</b></div><div><small>Chỉ số gần nhất</small><b>${lm?'Điện '+fmt(lm.dm)+(lm.nm!==''?' · Nước '+fmt(lm.nm):''):'Chưa ghi'}</b></div><div style="grid-column:1/-1"><small>Tiện ích</small><b>${esc(r.tienIch||'-')}</b></div>${r.note?`<div style="grid-column:1/-1"><small>Ghi chú</small><b>${esc(r.note)}</b></div>`:''}</div>
    ${c?`<div class="msec">Người đang thuê</div>
    <div class="kvg"><div><small>Họ tên</small><b>${esc(c.ten)}</b></div><div><small>Điện thoại</small><b>${phoneFmt(c.sdt)}</b></div><div><small>CCCD</small><b>${esc(c.cccd)}</b></div>
      <div><small>Ngày vào ở</small><b>${vnDate(c.ngayVao)}</b></div><div><small>Hết hạn hợp đồng</small><b>${vnDate(c.ngayHet)||'-'}</b> ${expTag(c)}</div><div><small>Giá thuê / tiền cọc</small><b>${fmt(c.gia)} / ${fmt(c.coc)}</b></div>
      <div><small>Số người · số xe</small><b>${c.soNguoi} người · ${c.soXe} xe</b></div><div><small>Biển số xe</small><b>${esc(c.bienSo||'-')}</b></div><div><small>Còn nợ</small><b class="${debtOfC(c.id)?'red':'green'}">${money(debtOfC(c.id))}</b></div></div>`:''}
    <div class="msec">Hóa đơn gần đây</div>
    ${invs.length?`<div class="tbl-wrap"><table><thead><tr><th>Kỳ</th><th>Người thuê</th><th class="n">Tổng</th><th class="n">Còn lại</th><th>Trạng thái</th><th></th></tr></thead><tbody>${invs.map(i=>`<tr><td><b>${kyShort(i.ky)}</b></td><td>${esc((ctr(i.cid)||{}).ten||'')}</td><td class="n">${fmt(i.tong)}</td><td class="n ${remOf(i)?'red':''}">${fmt(remOf(i))}</td><td>${invTag(i)}</td><td class="c"><button class="btn sm ico" title="In hóa đơn" onclick="printInv(['${i.id}'])">${IC.print}</button></td></tr>`).join('')}</tbody></table></div>`:'<div class="empty" style="padding:16px">Chưa có hóa đơn</div>'}
    ${hist.length?`<div class="msec" style="margin-top:14px">Người thuê trước</div>${hist.map(x=>`<div class="li"><div class="meta"><b>${esc(x.ten)}</b><small>${vnDate(x.ngayVao)} đến ${vnDate(x.ngayTra)} · ${esc(x.code)}</small></div></div>`).join('')}`:''}
  </div>
  <div class="modal-f"><button class="btn" style="margin-right:auto" onclick="openRoomForm('${id}')">${IC.edit}Sửa phòng</button>
    ${c?`<button class="btn" onclick="printContract('${c.id}')">${IC.print}In hợp đồng</button><button class="btn" onclick="openCheckout('${c.id}')">${IC.out}Trả phòng</button>${debtOfC(c.id)?`<button class="btn ok" onclick="openCollect('${c.id}')">${IC.money}Thu tiền</button>`:''}`
      :`<button class="btn" onclick="toggleSua('${id}')">${IC.tool}${r.sua?'Đã sửa xong':'Đánh dấu đang sửa'}</button><button class="btn pri" onclick="openContract(null,'${id}')">${IC.plus}Cho thuê phòng này</button>`}</div>`,'wide')}
function toggleSua(id){const r=room(id);r.sua=!r.sua;if(!r.sua&&/sửa|sơn/i.test(r.note||''))r.note='';save();closeModal();toast(r.sua?'Đã đánh dấu phòng '+r.code+' đang sửa chữa':'Phòng '+r.code+' đã sẵn sàng cho thuê');rerender()}

/* thêm, sửa phòng */
let EDR=null;
function openRoomForm(id){EDR=id?room(id):null;const r=EDR||{code:autoRoomCode(),day:days()[0]||'Dãy A',tang:1,dt:20,gia:2500000,toiDa:2,tienIch:'',note:''};
  openModal(`<div class="modal-h"><h3>${EDR?'Sửa phòng '+esc(r.code):'Thêm phòng mới'}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="row3"><div class="fg"><label class="f">Mã phòng *</label><input class="inp" id="fCode" value="${esc(r.code)}"></div><div class="fg"><label class="f">Dãy / khu</label><input class="inp" id="fDay" value="${esc(r.day)}" list="dlDay"></div><div class="fg"><label class="f">Tầng</label><input class="inp" id="fTang" type="number" value="${esc(r.tang)}"></div></div>
    <div class="row3"><div class="fg"><label class="f">Diện tích (m²)</label><input class="inp" id="fDt" type="number" min="0" step="any" value="${esc(r.dt)}"></div><div class="fg"><label class="f">Giá thuê (₫/tháng) *</label><input class="inp" id="fGia" type="number" min="0" value="${esc(r.gia)}"></div><div class="fg"><label class="f">Số người tối đa</label><input class="inp" id="fToiDa" type="number" min="1" value="${esc(r.toiDa)}"></div></div>
    <div class="fg"><label class="f">Tiện ích</label><input class="inp" id="fTienIch" value="${esc(r.tienIch)}" placeholder="VD: Gác lửng, máy lạnh, nóng lạnh, WC riêng"></div>
    <div class="fg"><label class="f">Ghi chú</label><input class="inp" id="fNote" value="${esc(r.note)}"></div>
    ${EDR&&curC(EDR.id)?'<p class="note">Đổi giá ở đây là giá niêm yết của phòng. Giá của người đang thuê sửa ở hợp đồng (hoặc khi gia hạn).</p>':''}
    <datalist id="dlDay">${days().map(d=>`<option>${esc(d)}</option>`).join('')}</datalist>
  </div>
  <div class="modal-f">${EDR?`<button class="btn danger" style="margin-right:auto" onclick="delRoom('${EDR.id}')">Xóa phòng</button>`:''}<button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="saveRoom()">Lưu phòng</button></div>`);
  setTimeout(()=>$('#fCode').focus(),50)}
function autoRoomCode(){let m=0;S.rooms.forEach(r=>{const n=parseInt(String(r.code).replace(/\D/g,''))||0;if(n>m)m=n});return 'P'+String(m+1).padStart(3,'0')}
function saveRoom(){const v=id=>$(id).value.trim();
  const d={code:v('#fCode').toUpperCase(),day:v('#fDay')||'Dãy A',tang:v('#fTang'),dt:+v('#fDt')||0,gia:+v('#fGia')||0,toiDa:+v('#fToiDa')||1,tienIch:v('#fTienIch'),note:v('#fNote')};
  if(!d.code)return toast('Chưa nhập mã phòng','err');if(!d.gia)return toast('Chưa nhập giá thuê','err');
  if(S.rooms.some(r=>r.code===d.code&&(!EDR||r.id!==EDR.id)))return toast('Mã phòng '+d.code+' đã có','err');
  if(EDR)Object.assign(EDR,d);else S.rooms.push({id:'r'+uid(),sua:false,...d});
  save();closeModal();toast(EDR?'Đã cập nhật phòng '+d.code:'Đã thêm phòng '+d.code);rerender()}
function delRoom(id){const r=room(id),cs=S.contracts.filter(c=>c.roomId===id);
  if(curC(id))return toast('Phòng '+r.code+' đang có người thuê, làm Trả phòng trước khi xóa','err');
  if(!confirm(`Xóa phòng ${r.code}?`+(cs.length?`\n\nPhòng có ${cs.length} hợp đồng cũ cùng hóa đơn, phiếu thu liên quan, tất cả sẽ bị xóa.`:'')))return;
  const ids=new Set(cs.map(c=>c.id)),iv=new Set(S.invoices.filter(i=>i.roomId===id).map(i=>i.id));
  S.rooms=S.rooms.filter(x=>x.id!==id);S.contracts=S.contracts.filter(c=>!ids.has(c.id));S.readings=S.readings.filter(x=>x.roomId!==id);S.invoices=S.invoices.filter(i=>!iv.has(i.id));S.payments=S.payments.filter(p=>!iv.has(p.invId));
  save();closeModal();toast('Đã xóa phòng '+r.code);rerender()}

/* =========================================================
   NGƯỜI THUÊ & HỢP ĐỒNG
   ========================================================= */
let TTAB='dang';
$$('#tnTab button').forEach(b=>b.onclick=()=>{TTAB=b.dataset.v;pgReset('tn');renderTenants()});
$('#tnSearch').addEventListener('input',()=>{pgReset('tn');renderTenantTable()});
function renderTenants(){$$('#tnTab button').forEach(x=>x.classList.toggle('on',x.dataset.v===TTAB));renderTenantTable()}
function renderTenantTable(){
  const q=fold($('#tnSearch').value.trim()),cw=+S.cfg.canhBao||30;
  let list=S.contracts.filter(c=>TTAB==='all'||(TTAB==='dang'&&!c.ngayTra)||(TTAB==='tra'&&c.ngayTra)||(TTAB==='exp'&&!c.ngayTra&&c.ngayHet&&expDays(c)<=cw));
  list=list.filter(c=>{const r=room(c.roomId)||{};return !q||fold(c.ten+' '+c.sdt+' '+c.cccd+' '+r.code+' '+c.code).includes(q)});
  list.sort(TTAB==='exp'?(a,b)=>expDays(a)-expDays(b):(a,b)=>String((room(a.roomId)||{}).code).localeCompare(String((room(b.roomId)||{}).code),'vi',{numeric:true})||b.ngayVao.localeCompare(a.ngayVao));
  let tc=0,tg=0,tn=0,td=0;list.forEach(c=>{tc+=+c.coc||0;if(!c.ngayTra){tg+=+c.gia||0;tn+=+c.soNguoi||0}td+=debtOfC(c.id)});
  const rows=pgSlice('tn',list).map(c=>{const r=room(c.roomId)||{},db=debtOfC(c.id);
    return `<tr><td><b>${esc(c.code)}</b></td><td><span class="rcode">${esc(r.code||'?')}</span></td><td><div class="pcell"><b>${esc(c.ten)}</b><small>${phoneFmt(c.sdt)}${c.nghe?' · '+esc(c.nghe):''}</small></div></td><td class="nw">${esc(c.cccd)}</td><td class="nw">${vnDate(c.ngayVao)}</td><td class="nw">${vnDate(c.ngayHet)}<br>${expTag(c)}</td><td class="n">${fmt(c.gia)}</td><td class="n">${fmt(c.coc)}</td><td class="c">${c.soNguoi} / ${c.soXe}</td><td class="n ${db?'red':''}"><b>${db?fmt(db):'-'}</b></td>
    <td><div class="acts">${c.ngayTra?'':`<button class="btn sm" onclick="openRenew('${c.id}')" title="Gia hạn hợp đồng">${IC.renew}Gia hạn</button><button class="btn sm" onclick="openCheckout('${c.id}')" title="Trả phòng">${IC.out}Trả phòng</button>`}<button class="btn sm ico" title="In hợp đồng" onclick="printContract('${c.id}')">${IC.print}</button><button class="btn sm ico" title="Sửa" onclick="openContract('${c.id}')">${IC.edit}</button><button class="btn sm ico danger" title="Xóa" onclick="delContract('${c.id}')">${IC.trash}</button></div></td></tr>`}).join('');
  $('#tnTable').innerHTML=list.length?`<table><thead><tr><th>Mã HĐ</th><th>Phòng</th><th>Người thuê</th><th>CCCD</th><th>Ngày vào</th><th>Hết hạn HĐ</th><th class="n">Giá thuê</th><th class="n">Tiền cọc</th><th class="c">Người / xe</th><th class="n">Còn nợ</th><th></th></tr></thead><tbody>${rows}</tbody>
    <tfoot><tr><td colspan="6">${list.length} hợp đồng · ${tn} người đang ở</td><td class="n">${fmt(tg)}</td><td class="n">${fmt(tc)}</td><td></td><td class="n">${fmt(td)}</td><td></td></tr></tfoot></table>${pgBar('tn',list.length,'renderTenantTable')}`
    :`<div class="empty">${TTAB==='exp'?'Không hợp đồng nào sắp hết hạn':'Chưa có người thuê nào'}<br><br><button class="btn pri" onclick="openContract()">Thêm người thuê</button></div>`}
let EDC=null;
function openContract(id,roomId){
  EDC=id?ctr(id):null;
  const free=sortRooms(S.rooms.filter(r=>!curC(r.id)||(EDC&&r.id===EDC.roomId)));
  if(!free.length)return toast('Không còn phòng trống. Thêm phòng mới hoặc làm Trả phòng trước','err');
  const r0=EDC?room(EDC.roomId):(roomId?room(roomId):free.find(r=>!r.sua)||free[0]);
  const lm=r0?lastMeter(r0.id):null,maxC=S.contracts.reduce((m,c)=>Math.max(m,parseInt(String(c.code).replace(/\D/g,''))||0),0);
  const c=EDC||{code:'HD'+String(maxC+1).padStart(3,'0'),ten:'',sdt:'',cccd:'',ngaySinh:'',que:'',nghe:'',ngayVao:TODAYS,kyHan:12,ngayHet:addDays(addMonths(TODAYS,12),-1),gia:r0?r0.gia:0,coc:r0?r0.gia:0,soNguoi:1,soXe:1,bienSo:'',wifi:true,dienDau:lm?lm.dm:'',nuocDau:lm?lm.nm:'',oCung:'',note:''};
  openModal(`<div class="modal-h"><h3>${EDC?'Sửa hợp đồng '+esc(c.code):'Cho thuê phòng: thêm người thuê'}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="msec">Phòng và thời hạn</div>
    <div class="row3"><div class="fg"><label class="f">Phòng *</label><select class="inp" id="kRoom">${free.map(r=>`<option value="${r.id}" ${r0&&r.id===r0.id?'selected':''}>${esc(r.code)} · ${esc(r.day)} · ${fmt(r.gia)}${r.sua?' (đang sửa)':''}</option>`).join('')}</select></div><div class="fg"><label class="f">Mã hợp đồng</label><input class="inp" id="kCode" value="${esc(c.code)}"></div><div class="fg"><label class="f">Ngày vào ở *</label><input class="inp" type="date" id="kVao" value="${c.ngayVao}"></div></div>
    <div class="row3"><div class="fg"><label class="f">Thời hạn</label><select class="inp" id="kHan">${[3,6,12,24].map(n=>`<option value="${n}" ${+c.kyHan===n?'selected':''}>${n} tháng</option>`).join('')}<option value="0" ${!+c.kyHan?'selected':''}>Tự nhập ngày</option></select></div><div class="fg"><label class="f">Ngày hết hạn</label><input class="inp" type="date" id="kHet" value="${c.ngayHet}"></div><div class="fg"><label class="f">Giá thuê (₫/tháng) *</label><input class="inp" type="number" id="kGia" value="${c.gia}"></div></div>
    <div class="row3"><div class="fg"><label class="f">Tiền cọc (₫)</label><input class="inp" type="number" id="kCoc" value="${c.coc}"></div><div class="fg"><label class="f">Số người ở</label><input class="inp" type="number" min="1" id="kNguoi" value="${c.soNguoi}"></div><div class="fg"><label class="f">Số xe gửi</label><input class="inp" type="number" min="0" id="kXe" value="${c.soXe}"></div></div>
    <div class="row3"><div class="fg"><label class="f">Chỉ số điện lúc vào</label><input class="inp" type="number" id="kDien" value="${c.dienDau??''}"></div><div class="fg"><label class="f">Chỉ số nước lúc vào</label><input class="inp" type="number" id="kNuoc" value="${c.nuocDau??''}"></div><div class="fg"><label class="f">&nbsp;</label><label class="chk"><input type="checkbox" id="kWifi" ${c.wifi?'checked':''}> Có dùng wifi (${fmt(S.cfg.wifi)} ₫/tháng)</label></div></div>
    <div class="msec">Người đứng hợp đồng</div>
    <div class="row3"><div class="fg"><label class="f">Họ và tên *</label><input class="inp" id="kTen" value="${esc(c.ten)}"></div><div class="fg"><label class="f">Số điện thoại</label><input class="inp" id="kSdt" value="${esc(c.sdt)}"></div><div class="fg"><label class="f">Số CCCD</label><input class="inp" id="kCccd" value="${esc(c.cccd)}" maxlength="12"></div></div>
    <div class="row3"><div class="fg"><label class="f">Ngày sinh</label><input class="inp" type="date" id="kSinh" value="${esc(c.ngaySinh)}"></div><div class="fg"><label class="f">Quê quán / thường trú</label><input class="inp" id="kQue" value="${esc(c.que)}"></div><div class="fg"><label class="f">Nghề nghiệp</label><input class="inp" id="kNghe" value="${esc(c.nghe)}"></div></div>
    <div class="row2"><div class="fg"><label class="f">Biển số xe</label><input class="inp" id="kBien" value="${esc(c.bienSo)}" placeholder="VD: 59X2-123.45, 51F1-678.90"></div><div class="fg"><label class="f">Người ở cùng (họ tên, năm sinh)</label><input class="inp" id="kOCung" value="${esc(c.oCung)}"></div></div>
    <div class="fg"><label class="f">Ghi chú</label><input class="inp" id="kNote" value="${esc(c.note)}"></div>
  </div>
  <div class="modal-f"><button class="btn" onclick="closeModal()">Hủy</button>${EDC?'':`<button class="btn" onclick="saveContract(true)">${IC.print}Lưu &amp; in hợp đồng</button>`}<button class="btn pri" onclick="saveContract()">Lưu hợp đồng</button></div>`,'wide');
  const upHet=()=>{const n=+$('#kHan').value;if(n)$('#kHet').value=addDays(addMonths($('#kVao').value||TODAYS,n),-1)};
  $('#kHan').onchange=upHet;$('#kVao').onchange=upHet;
  $('#kRoom').onchange=()=>{const r=room($('#kRoom').value);if(!r)return;if(!EDC){$('#kGia').value=r.gia;$('#kCoc').value=r.gia;const m=lastMeter(r.id);$('#kDien').value=m?m.dm:'';$('#kNuoc').value=m?m.nm:''}};
  setTimeout(()=>$('#kTen').focus(),50)}
function saveContract(andPrint){const v=id=>$(id).value.trim();
  const d={roomId:v('#kRoom'),code:v('#kCode')||'HD',ten:v('#kTen'),sdt:v('#kSdt').replace(/\s/g,''),cccd:v('#kCccd'),ngaySinh:v('#kSinh'),que:v('#kQue'),nghe:v('#kNghe'),ngayVao:v('#kVao'),kyHan:+v('#kHan'),ngayHet:v('#kHet'),
    gia:+v('#kGia')||0,coc:+v('#kCoc')||0,soNguoi:Math.max(1,+v('#kNguoi')||1),soXe:Math.max(0,+v('#kXe')||0),dienDau:v('#kDien')===''?'':+v('#kDien'),nuocDau:v('#kNuoc')===''?'':+v('#kNuoc'),wifi:$('#kWifi').checked,bienSo:v('#kBien'),oCung:v('#kOCung'),note:v('#kNote')};
  if(!d.ten)return toast('Chưa nhập họ tên người thuê','err');if(!d.ngayVao)return toast('Chưa chọn ngày vào ở','err');if(!d.gia)return toast('Chưa nhập giá thuê','err');
  if(d.ngayHet&&d.ngayHet<d.ngayVao)return toast('Ngày hết hạn đang trước ngày vào ở','err');
  if(d.cccd&&!/^\d{9}(\d{3})?$/.test(d.cccd))return toast('Số CCCD gồm 12 chữ số (CMND cũ 9 số)','err');
  const r=room(d.roomId),other=S.contracts.find(c=>c.roomId===d.roomId&&!c.ngayTra&&(!EDC||c.id!==EDC.id));
  if(other)return toast('Phòng '+r.code+' đang có '+other.ten+' thuê','err');
  if(S.contracts.some(c=>c.code===d.code&&(!EDC||c.id!==EDC.id)))return toast('Mã hợp đồng '+d.code+' đã có','err');
  let c;if(EDC){c=Object.assign(EDC,d)}else{c={id:'c'+uid(),ngayTra:'',...d};S.contracts.push(c);r.sua=false}
  save();closeModal();toast(EDC?'Đã cập nhật hợp đồng '+d.code:`Đã cho ${d.ten} thuê phòng ${r.code}`);
  if(d.soNguoi>(+r.toiDa||99))setTimeout(()=>toast(`Lưu ý: phòng ${r.code} tối đa ${r.toiDa} người, hợp đồng ghi ${d.soNguoi} người`,'err'),400);
  rerender();if(andPrint)setTimeout(()=>printContract(c.id),300)}
function delContract(id){const c=ctr(id),iv=S.invoices.filter(i=>i.cid===id);
  if(!confirm(`Xóa hợp đồng ${c.code} của ${c.ten}?`+(iv.length?`\n\nCó ${iv.length} hóa đơn và các phiếu thu đi kèm cũng sẽ bị xóa. Nếu khách chỉ dọn đi, hãy dùng Trả phòng để giữ lịch sử.`:'')))return;
  const ids=new Set(iv.map(i=>i.id));S.contracts=S.contracts.filter(x=>x.id!==id);S.readings=S.readings.filter(r=>r.cid!==id);S.invoices=S.invoices.filter(i=>i.cid!==id);S.payments=S.payments.filter(p=>!ids.has(p.invId));
  save();closeModal();toast('Đã xóa hợp đồng '+c.code);rerender()}
function openRenew(id){const c=ctr(id);if(!c)return;const base=c.ngayHet||TODAYS;
  openModal(`<div class="modal-h"><h3>Gia hạn hợp đồng ${esc(c.code)}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p style="margin-bottom:12px"><b>${esc(c.ten)}</b>, phòng ${esc((room(c.roomId)||{}).code)}. Hết hạn hiện tại: <b>${vnDate(c.ngayHet)||'-'}</b> ${expTag(c)}</p>
    <div class="row2"><div class="fg"><label class="f">Gia hạn thêm</label><select class="inp" id="gThem">${[3,6,12].map(n=>`<option value="${n}" ${n===(+c.kyHan||12)?'selected':''}>${n} tháng</option>`).join('')}</select></div><div class="fg"><label class="f">Hết hạn mới</label><input class="inp" type="date" id="gHet"></div></div>
    <div class="row2"><div class="fg"><label class="f">Giá thuê mới (₫/tháng)</label><input class="inp" type="number" id="gGia" value="${c.gia}"></div><div class="fg"><label class="f">Áp dụng giá mới từ</label><input class="inp" readonly value="Hóa đơn lập từ nay về sau"></div></div></div>
  <div class="modal-f"><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="doRenew('${id}')">Gia hạn</button></div>`,'small');
  const up=()=>{const from=base<TODAYS?TODAYS:addDays(base,1);$('#gHet').value=addDays(addMonths(from,+$('#gThem').value),-1)};$('#gThem').onchange=up;up()}
function doRenew(id){const c=ctr(id),het=$('#gHet').value,gia=+$('#gGia').value||c.gia;if(!het)return toast('Chưa có ngày hết hạn mới','err');
  const old=c.gia;c.ngayHet=het;c.gia=gia;c.note=((c.note?c.note+'. ':'')+'Gia hạn ngày '+vnDate(TODAYS)+' đến '+vnDate(het)+(gia!==old?', giá '+fmt(old)+' thành '+fmt(gia):'')).slice(-300);
  save();closeModal();toast('Đã gia hạn hợp đồng '+c.code+' đến '+vnDate(het));rerender()}

/* trả phòng: chốt điện nước cuối, trừ nợ vào tiền cọc */
function openCheckout(id){const c=ctr(id);if(!c)return;const r=room(c.roomId)||{};const k=KY_NOW,pv=prevVals(c,k),rd=getRd(c.id,k),inv=invOf(c.id,k);
  openModal(`<div class="modal-h"><h3>Trả phòng ${esc(r.code)}: ${esc(c.ten)}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="row3"><div class="fg"><label class="f">Ngày trả phòng *</label><input class="inp" type="date" id="oNgay" value="${TODAYS}"></div><div class="fg"><label class="f">Khấu trừ hư hỏng (₫)</label><input class="inp" type="number" min="0" id="oKt" value="0"></div><div class="fg"><label class="f">Lý do khấu trừ</label><input class="inp" id="oKtNd" placeholder="VD: Sơn lại tường"></div></div>
    ${inv?`<p class="note" style="margin-bottom:12px">Hóa đơn ${kyLbl(k)} đã lập (${money(inv.tong)}), không lập thêm hóa đơn cuối.</p>`:`<label class="chk" style="margin-bottom:10px"><input type="checkbox" id="oLap" checked> Lập hóa đơn tháng cuối (${kyLbl(k)}, tiền phòng tính theo số ngày ở)</label>
    <div class="row2" id="oMeter"><div class="fg"><label class="f">Chỉ số điện lúc trả (cũ ${fmt(rd&&rd.dc!==''?rd.dc:pv.dc)})</label><input class="inp" type="number" id="oDien" value="${rd&&rd.dm!==''?rd.dm:''}"></div>${isNguoi()?'':`<div class="fg"><label class="f">Chỉ số nước lúc trả (cũ ${fmt(rd&&rd.nc!==''?rd.nc:pv.nc)})</label><input class="inp" type="number" id="oNuoc" value="${rd&&rd.nm!==''?rd.nm:''}"></div>`}</div>`}
    <div class="msec">Quyết toán tiền cọc</div><div id="oSum"></div>
  </div>
  <div class="modal-f"><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="doCheckout('${id}')">${IC.out}Xác nhận trả phòng</button></div>`,'wide');
  $$('#modal input').forEach(i=>i.addEventListener('input',()=>coPreview(id)));coPreview(id)}
function coCalc(id){const c=ctr(id),r=room(c.roomId),k=KY_NOW,ngay=$('#oNgay').value||TODAYS,kt=Math.max(0,+$('#oKt').value||0);
  let fin=0;const lap=$('#oLap')&&$('#oLap').checked;
  if(lap){const pv=prevVals(c,k),rd0=getRd(c.id,k),dc=rd0&&rd0.dc!==''?+rd0.dc:pv.dc,nc=rd0&&rd0.nc!==''?+rd0.nc:pv.nc,dmv=$('#oDien').value,nmv=$('#oNuoc')?$('#oNuoc').value:'';
    const tmp={...c,ngayTra:ngay},rd={dc,dm:dmv===''?'':+dmv,nc,nm:nmv===''?'':+nmv};fin=calcInv(r,tmp,rd,k).tong}
  const owe=debtOfC(id)+fin,avail=Math.max(0,(+c.coc||0)-kt),tru=Math.min(avail,owe);
  return {ngay,kt,fin,lap,owe,tru,traLai:avail-tru,them:owe-tru}}
function coPreview(id){const c=ctr(id),o=coCalc(id);const row=(a,b,cl)=>`<div class="li"><div class="meta"><b style="font-weight:500">${a}</b></div><div class="v ${cl||''}">${b}</div></div>`;
  $('#oSum').innerHTML=row('Tiền cọc đã nhận',money(c.coc))+row('Khấu trừ hư hỏng',o.kt?'- '+money(o.kt):'0 ₫','red')+row('Nợ các hóa đơn cũ',money(debtOfC(id)),debtOfC(id)?'red':'')+(o.lap?row('Hóa đơn tháng cuối (ước tính)',money(o.fin)):'')+row('Trừ nợ vào tiền cọc',money(o.tru))+
    `<div class="bigsum"><small>${o.them>0?'Người thuê còn phải trả thêm':'Trả lại người thuê'}</small><b style="color:${o.them>0?'var(--danger)':'var(--in)'}">${money(o.them>0?o.them:o.traLai)}</b></div>`}
function doCheckout(id){const c=ctr(id),r=room(c.roomId),o=coCalc(id);
  if(o.ngay<c.ngayVao)return toast('Ngày trả phòng trước ngày vào ở','err');
  if(o.lap&&$('#oDien').value===''&&!confirm('Chưa nhập chỉ số điện lúc trả, hóa đơn cuối sẽ không có tiền điện. Vẫn tiếp tục?'))return;
  c.ngayTra=o.ngay;
  if(o.lap){const k=KY_NOW,pv=prevVals(c,k);let rd=getRd(c.id,k);
    if(!rd){rd={id:uid(),cid:c.id,roomId:c.roomId,ky:k,dc:pv.dc,dm:'',nc:pv.nc,nm:'',ngay:o.ngay};S.readings.push(rd)}
    if($('#oDien').value!=='')rd.dm=+$('#oDien').value;if($('#oNuoc')&&$('#oNuoc').value!=='')rd.nm=+$('#oNuoc').value;rd.ngay=o.ngay;
    S.invoices.push({id:'i'+uid(),so:soHD(r,k),ngay:o.ngay,...calcInv(r,c,rd,k,S.cfg,{ghiChu:'Hóa đơn tháng cuối khi trả phòng'})});PM=null}
  // trừ nợ vào cọc, hóa đơn cũ nhất trước
  let left=o.tru;const so=nextPT();
  S.invoices.filter(i=>i.cid===id&&remOf(i)>0).sort((a,b)=>a.ky.localeCompare(b.ky)).forEach(i=>{if(left<=0)return;const t=Math.min(left,remOf(i));left-=t;S.payments.push({id:uid(),so,invId:i.id,cid:id,ngay:o.ngay,tien:t,ht:'Trừ tiền cọc',nguoi:c.ten,note:'Trừ vào tiền cọc khi trả phòng'})});
  c.ketToan={ngay:o.ngay,coc:+c.coc||0,khauTru:o.kt,khauTruNd:$('#oKtNd').value.trim(),truNo:o.tru,traLai:o.traLai};
  save();closeModal();toast(`Đã trả phòng ${r.code}. ${o.them>0?'Người thuê còn nợ '+money(o.them):'Trả lại người thuê '+money(o.traLai)}`);rerender()}

/* =========================================================
   GHI ĐIỆN NƯỚC
   ========================================================= */
let MKY=KY_NOW;
$('#mtKy').addEventListener('change',e=>{if(e.target.value){MKY=e.target.value;renderMeter()}});
function mtKy(n){MKY=kyAdd(MKY,n);renderMeter()}
function mtList(){return activeIn(MKY).sort((a,b)=>String((room(a.roomId)||{}).code).localeCompare(String((room(b.roomId)||{}).code),'vi',{numeric:true})||a.ngayVao.localeCompare(b.ngayVao))}
function renderMeter(){
  $('#mtKy').value=MKY;const list=mtList(),ng=isNguoi(),cf=S.cfg;
  $('#mtNote').innerHTML=`Kỳ <b>${kyLbl(MKY)}</b>: chỉ gõ <b>chỉ số mới</b>, bấm Enter để xuống phòng dưới. Chỉ số cũ tự lấy từ tháng trước (sửa được khi thay đồng hồ). Đơn giá: điện <b>${fmt(cf.giaDien)} ₫/kWh</b> · nước <b>${ng?fmt(cf.giaNuocNguoi)+' ₫/người/tháng':fmt(cf.giaNuoc)+' ₫/m³'}</b>. <a href="#" onclick="go('settings');return false" style="color:var(--chu-dao);font-weight:600">Sửa đơn giá</a>`;
  if(!list.length){$('#mtTable').innerHTML='<div class="empty">Kỳ này không có phòng nào đang thuê</div>';$('#mtProg').textContent='';return}
  $('#mtTable').innerHTML=`<table class="mt"><thead><tr><th rowspan="2" class="c">#</th><th rowspan="2">Phòng · người thuê</th><th colspan="4" class="g ge">ĐIỆN (kWh)</th><th colspan="${ng?2:4}" class="g gw">NƯỚC${ng?' (khoán)':' (m³)'}</th><th rowspan="2">Trạng thái</th></tr>
    <tr><th class="se">Chỉ số cũ</th><th class="se">Chỉ số mới</th><th class="se">Tiêu thụ</th><th class="se">Tiền điện</th>${ng?'<th class="sw">Số người</th><th class="sw">Tiền nước</th>':'<th class="sw">Chỉ số cũ</th><th class="sw">Chỉ số mới</th><th class="sw">Tiêu thụ</th><th class="sw">Tiền nước</th>'}</tr></thead>
    <tbody>${list.map((c,i)=>{const r=room(c.roomId)||{},rd=getRd(c.id,MKY),pv=prevVals(c,MKY),dc=rd&&rd.dc!==''?rd.dc:pv.dc,nc=rd&&rd.nc!==''?rd.nc:pv.nc;
      return `<tr data-c="${c.id}"><td class="c mut">${i+1}</td><td><div class="pcell"><b><span class="rcode" style="height:24px;min-width:40px;margin-right:6px">${esc(r.code)}</span>${esc(c.ten)}</b>${c.ngayVao.slice(0,7)===MKY||(c.ngayTra&&c.ngayTra.slice(0,7)===MKY)?`<small>${c.ngayVao.slice(0,7)===MKY?'Vào ở '+vnDate(c.ngayVao):''}${c.ngayTra&&c.ngayTra.slice(0,7)===MKY?' Trả phòng '+vnDate(c.ngayTra):''}</small>`:''}</div></td>
        <td class="bl"><input class="cin sm" data-f="dc" value="${dc}" inputmode="numeric"></td><td><input class="cin new" data-f="dm" value="${rd&&rd.dm!==''?rd.dm:''}" inputmode="numeric" placeholder="nhập"></td><td class="use" data-u="e"></td><td class="n" data-t="e"></td>
        ${ng?`<td class="c bl">${c.soNguoi}</td><td class="n" data-t="w"></td>`:`<td class="bl"><input class="cin sm" data-f="nc" value="${nc}" inputmode="numeric"></td><td><input class="cin new" data-f="nm" value="${rd&&rd.nm!==''?rd.nm:''}" inputmode="numeric" placeholder="nhập"></td><td class="use" data-u="w"></td><td class="n" data-t="w"></td>`}
        <td data-s></td></tr>`}).join('')}</tbody><tfoot><tr><td></td><td>Tổng cộng</td><td></td><td></td><td class="n" id="mtE"></td><td class="n" id="mtEV"></td>${ng?'<td></td><td class="n" id="mtWV"></td>':'<td></td><td></td><td class="n" id="mtW"></td><td class="n" id="mtWV"></td>'}<td></td></tr></tfoot></table>`;
  $$('#mtTable tr[data-c]').forEach(tr=>{mtRow(tr);
    tr.querySelectorAll('.cin').forEach(inp=>{
      inp.oninput=()=>mtRow(tr,true);
      inp.onchange=()=>{mtSet(tr.dataset.c,inp.dataset.f,inp.value);mtRow(tr);mtSum()};
      inp.onkeydown=e=>{if(e.key==='Enter'||e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();const rows=$$('#mtTable tr[data-c]'),i=rows.indexOf(tr),nx=rows[i+(e.key==='ArrowUp'?-1:1)];
        if(e.key==='Enter'&&!nx&&inp.dataset.f==='dm'&&!isNguoi()){const w=rows[0].querySelector('[data-f=nm]');if(w){w.focus();w.select()}return}
        if(nx){const t=nx.querySelector(`[data-f=${inp.dataset.f}]`);t.focus();t.select()}}}})});
  mtSum()}
function mtVals(tr){const g=f=>{const i=tr.querySelector(`[data-f=${f}]`);return i?i.value.trim():''};return {dc:g('dc'),dm:g('dm'),nc:g('nc'),nm:g('nm')}}
function mtRow(tr,live){const c=ctr(tr.dataset.c),v=mtVals(tr),cf=S.cfg,ng=isNguoi();
  const e=v.dm!==''?(+v.dm)-(+v.dc):null,w=!ng&&v.nm!==''?(+v.nm)-(+v.nc):null;
  tr.querySelector('[data-u=e]').innerHTML=e===null?'<span class="mut">-</span>':`<span class="${e<0?'red':''}">${fmt(e)}</span>`;
  tr.querySelector('[data-t=e]').textContent=e===null||e<0?'':fmt(e*cf.giaDien);
  tr.querySelector('[data-f=dm]').classList.toggle('bad',e!==null&&e<0);
  if(ng)tr.querySelector('[data-t=w]').textContent=fmt((+c.soNguoi||0)*cf.giaNuocNguoi);
  else{tr.querySelector('[data-u=w]').innerHTML=w===null?'<span class="mut">-</span>':`<span class="${w<0?'red':''}">${fmt(w)}</span>`;tr.querySelector('[data-t=w]').textContent=w===null||w<0?'':fmt(w*cf.giaNuoc);tr.querySelector('[data-f=nm]').classList.toggle('bad',w!==null&&w<0)}
  if(live)return;
  const rd=getRd(c.id,MKY),inv=invOf(c.id,MKY);let s;
  if(inv){const diff=rd&&(String(inv.dm)!==String(rd.dm)||(!ng&&String(inv.nm)!==String(rd.nm))||String(inv.dc)!==String(rd.dc)||(!ng&&String(inv.nc)!==String(rd.nc)));
    s=paidOf(inv)>=inv.tong?'<span class="tag ok">Đã thu tiền</span>':diff?'<span class="tag w">Cần cập nhật HĐ</span>':'<span class="tag p">Đã lập hóa đơn</span>'}
  else s=rdDone(rd)?'<span class="tag i">Đã ghi số</span>':'<span class="tag g">Chưa ghi</span>';
  tr.querySelector('[data-s]').innerHTML=s}
function mtSet(cid,f,val){const c=ctr(cid);let rd=getRd(cid,MKY);
  if(!rd){const pv=prevVals(c,MKY);rd={id:uid(),cid,roomId:c.roomId,ky:MKY,dc:pv.dc,dm:'',nc:pv.nc,nm:'',ngay:MKY===KY_NOW?TODAYS:kyLast(MKY)};S.readings.push(rd)}
  rd[f]=val===''?'':numIn(val);if(f==='dm'||f==='nm')rd.ngay=MKY===KY_NOW?TODAYS:kyLast(MKY);
  if(f==='dm'&&rd.dm!==''&&rd.dm<rd.dc)toast('Phòng '+(room(c.roomId)||{}).code+': chỉ số điện mới nhỏ hơn chỉ số cũ, kiểm tra lại','err');
  if(f==='nm'&&rd.nm!==''&&rd.nm<rd.nc)toast('Phòng '+(room(c.roomId)||{}).code+': chỉ số nước mới nhỏ hơn chỉ số cũ, kiểm tra lại','err');
  if(rd.dm===''&&rd.nm==='')S.readings=S.readings.filter(x=>x!==rd);
  save()}
function mtSum(){const list=mtList(),ng=isNguoi(),cf=S.cfg;let e=0,w=0,wv=0,done=0;
  list.forEach(c=>{const rd=getRd(c.id,MKY);if(rdDone(rd))done++;if(rd&&rd.dm!=='')e+=Math.max(0,rd.dm-rd.dc);if(ng)wv+=(+c.soNguoi||0)*cf.giaNuocNguoi;else if(rd&&rd.nm!=='')w+=Math.max(0,rd.nm-rd.nc)});
  if(!ng)wv=w*cf.giaNuoc;
  const set=(id,t)=>{const el=$(id);if(el)el.textContent=t};set('#mtE',fmt(e));set('#mtEV',fmt(e*cf.giaDien));set('#mtW',fmt(w));set('#mtWV',fmt(wv));
  $('#mtProg').innerHTML=`Đã ghi <b>${done}/${list.length}</b> phòng`}
function invDiff(inv,rd){return String(inv.dc)!==String(rd.dc)||String(inv.dm)!==String(rd.dm)||(!isNguoi()&&(String(inv.nc)!==String(rd.nc)||String(inv.nm)!==String(rd.nm)))}
function lapHD(k){
  const list=activeIn(k),miss=list.filter(c=>!rdDone(getRd(c.id,k))&&!invOf(c.id,k));
  const ready=list.filter(c=>rdDone(getRd(c.id,k)));
  if(!ready.length)return toast('Kỳ '+kyLbl(k)+' chưa phòng nào ghi đủ chỉ số điện nước','err');
  if(miss.length&&!confirm(`Còn ${miss.length} phòng chưa ghi đủ chỉ số (${miss.map(c=>(room(c.roomId)||{}).code).join(', ')}).\nVẫn lập hóa đơn cho ${ready.length} phòng đã ghi?`))return;
  let add=0,upd=0,lock=0;
  ready.forEach(c=>{const r=room(c.roomId),rd=getRd(c.id,k),inv=invOf(c.id,k);
    if(inv){if(!invDiff(inv,rd))return;if(paidOf(inv)>0){lock++;return}Object.assign(inv,calcInv(r,c,rd,k,S.cfg,inv));upd++;return}
    S.invoices.push({id:'i'+uid(),so:soHD(r,k),ngay:TODAYS,...calcInv(r,c,rd,k)});add++});
  if(!add&&!upd&&!lock)return toast('Hóa đơn kỳ '+kyLbl(k)+' đã khớp chỉ số, không có gì cần lập thêm');
  save();toast(`Kỳ ${kyLbl(k)}: lập mới ${add} hóa đơn${upd?', cập nhật '+upd:''}${lock?', '+lock+' hóa đơn đã thu tiền nên giữ nguyên':''}`);
  if(CUR==='meter')renderMeter();else rerender()}

/* =========================================================
   HÓA ĐƠN
   ========================================================= */
let IVKY=KY_NOW;
['#ivSearch','#ivSt'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('iv');renderInvTable()}));
$('#ivKy').addEventListener('input',e=>{IVKY=e.target.value;pgReset('iv');renderInvoices()});
function renderInvoices(){
  const kys=[...new Set(S.invoices.map(i=>i.ky))].sort().reverse();if(IVKY&&!kys.includes(IVKY))IVKY=kys[0]||'';
  $('#ivKy').innerHTML='<option value="">Tất cả các kỳ</option>'+kys.map(k=>`<option value="${k}" ${k===IVKY?'selected':''}>${kyLbl(k)}</option>`).join('');$('#ivKy').value=IVKY;
  const l=S.invoices.filter(i=>!IVKY||i.ky===IVKY),t=l.reduce((s,i)=>s+(+i.tong||0),0),pd2=l.reduce((s,i)=>s+Math.min(paidOf(i),+i.tong||0),0),qua=l.filter(i=>invSt(i)==='qua');
  $('#ivStat').innerHTML=ms(IC.receipt,'var(--chu-dao-nhat)','var(--chu-dao)','Phải thu '+(IVKY?kyShort(IVKY):'tất cả'),short(t)+' ₫ · '+l.length+' HĐ')+ms(IC.check,'var(--inbg)','var(--in)','Đã thu',short(pd2)+' ₫ ('+(t?Math.round(pd2/t*100):0)+'%)')+ms(IC.wallet,'var(--warnbg)','#a87200','Còn phải thu',short(t-pd2)+' ₫')+ms(IC.warn,'var(--dangerbg)','var(--danger)','Hóa đơn quá hạn',qua.length+' HĐ · '+short(qua.reduce((s,i)=>s+remOf(i),0))+' ₫');
  renderInvTable()}
function invRows(){const q=fold($('#ivSearch').value.trim()),st=$('#ivSt').value;
  return S.invoices.filter(i=>{if(IVKY&&i.ky!==IVKY)return false;const s=invSt(i);if(st==='con'?s==='du':st&&s!==st)return false;
    const c=ctr(i.cid)||{},r=room(i.roomId)||{};return !q||fold(i.so+' '+r.code+' '+c.ten+' '+c.sdt).includes(q)})
    .sort((a,b)=>b.ky.localeCompare(a.ky)||String((room(a.roomId)||{}).code).localeCompare(String((room(b.roomId)||{}).code),'vi',{numeric:true}))}
function renderInvTable(){const list=invRows();const T={p:0,e:0,w:0,d:0,k:0,t:0,pd:0,r:0};
  list.forEach(i=>{T.p+=+i.tienPhong||0;T.e+=+i.tienDien||0;T.w+=+i.tienNuoc||0;T.d+=dv(i);T.k+=(+i.khac||0)-(+i.giam||0);T.t+=+i.tong||0;T.pd+=Math.min(paidOf(i),+i.tong||0);T.r+=remOf(i)});
  const rows=pgSlice('iv',list).map(i=>{const c=ctr(i.cid)||{},r=room(i.roomId)||{},rem=remOf(i),kg=(+i.khac||0)-(+i.giam||0);
    return `<tr><td class="nw"><b>${esc(i.so)}</b><br><small class="mut">${kyLbl(i.ky)}</small></td><td><span class="rcode">${esc(r.code||'?')}</span></td><td><div class="pcell"><b>${esc(c.ten||'(đã xóa)')}</b><small>${phoneFmt(c.sdt)}</small></div></td>
      <td class="n">${fmt(i.tienPhong)}${i.soNgay<i.dim?`<br><small class="mut">${i.soNgay}/${i.dim} ngày</small>`:''}</td><td class="n">${fmt(i.tienDien)}<br><small class="mut">${fmt(i.kwh)} kWh</small></td><td class="n">${fmt(i.tienNuoc)}<br><small class="mut">${i.nuocKieu==='nguoi'?i.soNguoi+' người':fmt(i.m3)+' m³'}</small></td><td class="n">${fmt(dv(i))}</td><td class="n">${kg?fmt(kg):'-'}</td>
      <td class="n"><b>${fmt(i.tong)}</b></td><td class="n green">${fmt(Math.min(paidOf(i),i.tong))}</td><td class="n ${rem?'red':''}"><b>${fmt(rem)}</b></td><td class="nw">${vnDate(i.han)}<br>${invTag(i)}</td>
      <td><div class="acts">${rem?`<button class="btn sm ok" onclick="openCollect(null,'${i.id}')">Thu</button>`:''}<button class="btn sm ico" title="In hóa đơn" onclick="printInv(['${i.id}'])">${IC.print}</button><button class="btn sm ico" title="Sao chép tin nhắn gửi người thuê" onclick="copyInv('${i.id}')">${IC.copy}</button><button class="btn sm ico" title="Sửa khoản khác, giảm trừ" onclick="openInv('${i.id}')">${IC.edit}</button><button class="btn sm ico danger" title="Xóa hóa đơn" onclick="delInv('${i.id}')">${IC.trash}</button></div></td></tr>`}).join('');
  $('#ivTable').innerHTML=list.length?`<table><thead><tr><th>Số HĐ</th><th>Phòng</th><th>Người thuê</th><th class="n">Tiền phòng</th><th class="n">Điện</th><th class="n">Nước</th><th class="n">Dịch vụ</th><th class="n">Khác</th><th class="n">Tổng cộng</th><th class="n">Đã thu</th><th class="n">Còn lại</th><th>Hạn / trạng thái</th><th></th></tr></thead><tbody>${rows}</tbody>
    <tfoot><tr><td colspan="3">${list.length} hóa đơn</td><td class="n">${fmt(T.p)}</td><td class="n">${fmt(T.e)}</td><td class="n">${fmt(T.w)}</td><td class="n">${fmt(T.d)}</td><td class="n">${fmt(T.k)}</td><td class="n">${fmt(T.t)}</td><td class="n">${fmt(T.pd)}</td><td class="n">${fmt(T.r)}</td><td colspan="2"></td></tr></tfoot></table>${pgBar('iv',list.length,'renderInvTable')}`
    :`<div class="empty">Chưa có hóa đơn phù hợp.<br><br><button class="btn pri" onclick="go('meter')">Ghi điện nước để lập hóa đơn</button></div>`}
function openInv(id){const i=invById(id);if(!i)return;const c=ctr(i.cid)||{},r=room(i.roomId)||{};
  openModal(`<div class="modal-h"><h3>Hóa đơn ${esc(i.so)}</h3><span class="tag p">${kyLbl(i.ky)}</span><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="kvg"><div><small>Phòng</small><b>${esc(r.code)}</b></div><div><small>Người thuê</small><b>${esc(c.ten)}</b></div><div><small>Đã thu</small><b class="green">${money(paidOf(i))}</b></div>
      <div><small>Tiền phòng</small><b>${money(i.tienPhong)}</b></div><div><small>Điện ${fmt(i.dc)} đến ${fmt(i.dm)}</small><b>${fmt(i.kwh)} kWh = ${money(i.tienDien)}</b></div><div><small>Nước</small><b>${i.nuocKieu==='nguoi'?i.soNguoi+' người':fmt(i.m3)+' m³'} = ${money(i.tienNuoc)}</b></div>
      <div><small>Rác · wifi · gửi xe</small><b>${fmt(i.rac)} · ${fmt(i.wifi)} · ${fmt(i.xe)}</b></div></div>
    <div class="row2"><div class="fg"><label class="f">Khoản thu khác (₫)</label><input class="inp" type="number" min="0" id="eKhac" value="${i.khac||0}"></div><div class="fg"><label class="f">Nội dung khoản khác</label><input class="inp" id="eKhacNd" value="${esc(i.khacNd)}" placeholder="VD: Thay bóng đèn"></div></div>
    <div class="row2"><div class="fg"><label class="f">Giảm trừ (₫)</label><input class="inp" type="number" min="0" id="eGiam" value="${i.giam||0}"></div><div class="fg"><label class="f">Lý do giảm trừ</label><input class="inp" id="eGiamNd" value="${esc(i.giamNd)}" placeholder="VD: Cúp nước 3 ngày"></div></div>
    <div class="row2"><div class="fg"><label class="f">Hạn thanh toán</label><input class="inp" type="date" id="eHan" value="${i.han}"></div><div class="fg"><label class="f">Ghi chú</label><input class="inp" id="eNote" value="${esc(i.ghiChu)}"></div></div>
    <div class="bigsum"><small>Tổng cộng sau khi sửa</small><b id="eTong">${money(i.tong)}</b></div>
    ${paidOf(i)?'':'<label class="chk" style="margin-top:10px"><input type="checkbox" id="eRe"> Tính lại theo chỉ số đã ghi và đơn giá hiện tại</label>'}
  </div>
  <div class="modal-f"><button class="btn" style="margin-right:auto" onclick="printInv(['${id}'])">${IC.print}In hóa đơn</button><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" onclick="saveInv('${id}')">Lưu hóa đơn</button></div>`);
  const base=i.tong-(+i.khac||0)+(+i.giam||0);
  ['#eKhac','#eGiam'].forEach(s=>$(s).oninput=()=>$('#eTong').textContent=money(base+(+$('#eKhac').value||0)-(+$('#eGiam').value||0)))}
function saveInv(id){const i=invById(id),keep={khac:Math.max(0,+$('#eKhac').value||0),khacNd:$('#eKhacNd').value.trim(),giam:Math.max(0,+$('#eGiam').value||0),giamNd:$('#eGiamNd').value.trim(),han:$('#eHan').value||i.han,ghiChu:$('#eNote').value.trim()};
  if($('#eRe')&&$('#eRe').checked){const c=ctr(i.cid),r=room(i.roomId);if(c&&r)Object.assign(i,calcInv(r,c,getRd(c.id,i.ky),i.ky,S.cfg,keep))}
  else{const base=i.tong-(+i.khac||0)+(+i.giam||0);Object.assign(i,keep);i.tong=base+keep.khac-keep.giam}
  if(paidOf(i)>i.tong)toast('Lưu ý: số đã thu đang lớn hơn tổng hóa đơn','err');
  save();closeModal();toast('Đã lưu hóa đơn '+i.so);rerender()}
function delInv(id){const i=invById(id),n=S.payments.filter(p=>p.invId===id).length;
  if(!confirm(`Xóa hóa đơn ${i.so}?`+(n?`\n\nHóa đơn đã có ${n} lần thu tiền, các phiếu thu này cũng bị xóa.`:'')))return;
  S.invoices=S.invoices.filter(x=>x.id!==id);S.payments=S.payments.filter(p=>p.invId!==id);save();toast('Đã xóa hóa đơn '+i.so);rerender()}
function invText(i){const c=ctr(i.cid)||{},r=room(i.roomId)||{},cf=S.cfg,p=paidOf(i),L=[];
  L.push(`${cf.ten||'Nhà trọ'} - HÓA ĐƠN TIỀN PHÒNG ${r.code} ${kyLbl(i.ky).toUpperCase()}`);
  L.push(`Người thuê: ${c.ten||''}`);
  L.push(`- Tiền phòng${i.soNgay<i.dim?` (${i.soNgay}/${i.dim} ngày)`:''}: ${fmt(i.tienPhong)}`);
  L.push(`- Điện: ${fmt(i.dc)} -> ${fmt(i.dm)} = ${fmt(i.kwh)} kWh x ${fmt(i.giaDien)} = ${fmt(i.tienDien)}`);
  L.push(i.nuocKieu==='nguoi'?`- Nước: ${i.soNguoi} người x ${fmt(i.giaNuoc)} = ${fmt(i.tienNuoc)}`:`- Nước: ${fmt(i.nc)} -> ${fmt(i.nm)} = ${fmt(i.m3)} m3 x ${fmt(i.giaNuoc)} = ${fmt(i.tienNuoc)}`);
  if(i.rac)L.push(`- Rác: ${fmt(i.rac)}`);if(i.wifi)L.push(`- Wifi: ${fmt(i.wifi)}`);if(i.xe)L.push(`- Gửi xe (${i.soXe} xe): ${fmt(i.xe)}`);
  if(i.khac)L.push(`- ${i.khacNd||'Khoản khác'}: ${fmt(i.khac)}`);if(i.giam)L.push(`- Giảm trừ${i.giamNd?' ('+i.giamNd+')':''}: -${fmt(i.giam)}`);
  L.push(`TỔNG CỘNG: ${fmt(i.tong)} đ`);if(p)L.push(`Đã thanh toán: ${fmt(p)} đ. Còn lại: ${fmt(remOf(i))} đ`);
  L.push(`Hạn thanh toán: ${vnDate(i.han)}`);
  if(cf.stk)L.push(`Chuyển khoản: ${cf.nganHang} ${cf.stk} - ${cf.chuTK}. Nội dung: ${r.code} ${kyShort(i.ky)}`);
  L.push('Cảm ơn anh/chị!');return L.join('\n')}
function copyText(t,msg){const ok=()=>toast(msg||'Đã sao chép, dán vào Zalo để gửi');
  const fb=()=>{const ta=document.createElement('textarea');ta.value=t;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');ok()}catch(e){toast('Không sao chép được, hãy bôi đen và Ctrl + C','err')}ta.remove()};
  if(navigator.clipboard&&window.isSecureContext)navigator.clipboard.writeText(t).then(ok,fb);else fb()}
function copyInv(id){copyText(invText(invById(id)),'Đã sao chép tin nhắn hóa đơn, dán vào Zalo để gửi người thuê')}

/* =========================================================
   THU TIỀN & CÔNG NỢ
   ========================================================= */
function nextPT(){let m=0;S.payments.forEach(p=>{const n=parseInt(String(p.so).replace(/\D/g,''))||0;if(n>m)m=n});return 'PT'+String(m+1).padStart(5,'0')}
let COL=[];
function openCollect(cid,invId){
  const list=(invId?[invById(invId)]:S.invoices.filter(i=>i.cid===cid)).filter(i=>i&&remOf(i)>0).sort((a,b)=>a.ky.localeCompare(b.ky));
  if(!list.length)return toast('Không còn khoản nợ nào','err');COL=list.map(i=>i.id);
  const c=ctr(list[0].cid)||{},r=room(list[0].roomId)||{},tot=list.reduce((s,i)=>s+remOf(i),0);
  openModal(`<div class="modal-h"><span class="rcode">${esc(r.code)}</span><h3>Thu tiền: ${esc(c.ten)}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div class="tbl-wrap" style="margin-bottom:14px"><table><thead><tr><th>Hóa đơn</th><th class="n">Tổng</th><th class="n">Đã thu</th><th class="n">Còn lại</th><th class="n">Thu lần này</th></tr></thead><tbody>${list.map(i=>`<tr><td><b>${esc(i.so)}</b><br><small class="mut">${kyLbl(i.ky)} · hạn ${vnDate(i.han)}</small></td><td class="n">${fmt(i.tong)}</td><td class="n">${fmt(paidOf(i))}</td><td class="n red">${fmt(remOf(i))}</td><td class="n"><b data-al="${i.id}"></b></td></tr>`).join('')}</tbody></table></div>
    <div class="row3"><div class="fg"><label class="f">Số tiền thu (₫) *</label><input class="inp" id="cTien" inputmode="numeric" value="${fmt(tot)}" style="font-weight:800;font-size:16px"></div><div class="fg"><label class="f">Ngày thu</label><input class="inp" type="date" id="cNgay" value="${TODAYS}"></div><div class="fg"><label class="f">Hình thức</label><select class="inp" id="cHt"><option>Chuyển khoản</option><option>Tiền mặt</option></select></div></div>
    <div class="row2"><div class="fg"><label class="f">Người nộp</label><input class="inp" id="cNguoi" value="${esc(c.ten)}"></div><div class="fg"><label class="f">Ghi chú</label><input class="inp" id="cNote"></div></div>
    <div class="bigsum"><small>Tổng còn nợ ${list.length>1?'('+list.length+' hóa đơn, thu hóa đơn cũ trước)':''}</small><b>${money(tot)}</b></div><div class="words" id="cWords"></div>
  </div>
  <div class="modal-f"><button class="btn" onclick="closeModal()">Hủy</button><button class="btn" onclick="doCollect(true)">${IC.print}Lưu &amp; in phiếu thu</button><button class="btn ok" onclick="doCollect()">${IC.check}Lưu phiếu thu</button></div>`);
  const inp=$('#cTien');inp.oninput=()=>colPreview();inp.onblur=()=>{inp.value=fmt(numIn(inp.value))};colPreview();setTimeout(()=>{inp.focus();inp.select()},50)}
function colAlloc(){let left=numIn($('#cTien').value);return COL.map(id=>{const i=invById(id),t=Math.max(0,Math.min(left,remOf(i)));left-=t;return [i,t]})}
function colPreview(){const al=colAlloc(),v=numIn($('#cTien').value);al.forEach(([i,t])=>{const e=$(`[data-al="${i.id}"]`);if(e)e.textContent=t?fmt(t):'-'});$('#cWords').innerHTML=v?'Bằng chữ: <b>'+docSo(v)+'</b>':''}
function doCollect(andPrint){const v=numIn($('#cTien').value),tot=COL.reduce((s,id)=>s+remOf(invById(id)),0);
  if(v<=0)return toast('Số tiền thu phải lớn hơn 0','err');if(v>tot)return toast('Số tiền thu lớn hơn số còn nợ ('+money(tot)+')','err');
  const so=nextPT(),ngay=$('#cNgay').value||TODAYS,ht=$('#cHt').value,nguoi=$('#cNguoi').value.trim(),note=$('#cNote').value.trim();
  colAlloc().forEach(([i,t])=>{if(t>0)S.payments.push({id:uid(),so,invId:i.id,cid:i.cid,ngay,tien:t,ht,nguoi,note})});
  save();closeModal();toast(`Đã thu ${money(v)} (${so})`);rerender();if(andPrint)setTimeout(()=>printReceipt(so),300)}
['#pySearch','#pyHt','#pyFrom','#pyTo'].forEach(s=>$(s).addEventListener('input',()=>{pgReset('py');renderPayTable()}));
function renderDebts(){
  const g=debtRows(),tot=g.reduce((s,x)=>s+x.rem,0),qua=S.invoices.filter(i=>invSt(i)==='qua'),mo=sumPay(kyFirst(KY_NOW),kyLast(KY_NOW)),over=g.filter(x=>lateOfC(x.cid)>(+(ctr(x.cid)||{}).coc||0)).length;
  $('#dbStat').innerHTML=ms(IC.wallet,'var(--dangerbg)','var(--danger)','Tổng còn nợ',short(tot)+' ₫ · '+g.length+' phòng')+ms(IC.warn,'var(--warnbg)','#a87200','Hóa đơn quá hạn',qua.length+' HĐ · '+short(qua.reduce((s,i)=>s+remOf(i),0))+' ₫')+ms(IC.money,'var(--inbg)','var(--in)','Đã thu tháng này',short(mo.v)+' ₫ · '+mo.n+' phiếu')+ms(IC.doc,'var(--chu-dao-nhat)','var(--chu-dao)','Nợ quá hạn vượt tiền cọc',over+' phòng');
  $('#dbTable').innerHTML=g.length?`<table><thead><tr><th>Phòng</th><th>Người thuê</th><th class="c">Số HĐ nợ</th><th>Nợ từ kỳ</th><th>Quá hạn</th><th class="n">Tiền cọc</th><th class="n">Còn nợ</th><th></th></tr></thead><tbody>${g.map(x=>{const c=ctr(x.cid),r=room(c.roomId)||{},late=dDiff(x.han,TODAYS);
    return `<tr><td><span class="rcode">${esc(r.code||'?')}</span></td><td><div class="pcell"><b>${esc(c.ten)}${c.ngayTra?' <span class="tag g">Đã trả phòng</span>':''}</b><small>${phoneFmt(c.sdt)}</small></div></td><td class="c">${x.n}</td><td>${kyLbl(x.oldest)}</td><td>${late>0?`<span class="tag d">${late} ngày</span>`:'<span class="tag g">Chưa tới hạn</span>'}</td><td class="n">${fmt(c.coc)}</td><td class="n red"><b>${fmt(x.rem)}</b>${lateOfC(c.id)>(+c.coc||0)?'<br><small>quá hạn vượt tiền cọc</small>':''}</td>
      <td><div class="acts"><button class="btn sm ok" onclick="openCollect('${c.id}')">${IC.money}Thu tiền</button><button class="btn sm ico" title="Sao chép tin nhắn nhắc nợ" onclick="copyRemind('${c.id}')">${IC.copy}</button></div></td></tr>`}).join('')}</tbody><tfoot><tr><td colspan="6">${g.length} phòng còn nợ</td><td class="n">${fmt(tot)}</td><td></td></tr></tfoot></table>`:'<div class="empty">Không phòng nào còn nợ. Tốt quá!</div>';
  renderPayTable()}
function copyRemind(cid){const c=ctr(cid),r=room(c.roomId)||{},cf=S.cfg,iv=S.invoices.filter(i=>i.cid===cid&&remOf(i)>0).sort((a,b)=>a.ky.localeCompare(b.ky));
  const L=[`Chào ${c.ten}, ${cf.ten||'nhà trọ'} xin nhắc phòng ${r.code} còn nợ tiền phòng:`];iv.forEach(i=>L.push(`- ${kyLbl(i.ky)}: ${fmt(remOf(i))} đ (hạn ${vnDate(i.han)})`));
  L.push(`Tổng cộng: ${fmt(iv.reduce((s,i)=>s+remOf(i),0))} đ`);if(cf.stk)L.push(`Chuyển khoản: ${cf.nganHang} ${cf.stk} - ${cf.chuTK}, nội dung: ${r.code} tien phong`);L.push('Mong anh/chị thu xếp đóng sớm giúp. Cảm ơn!');
  copyText(L.join('\n'),'Đã sao chép tin nhắn nhắc nợ')}
function payGroups(){const q=fold($('#pySearch').value.trim()),ht=$('#pyHt').value,f=$('#pyFrom').value,t=$('#pyTo').value,m={};
  S.payments.forEach(p=>{const g=m[p.so]=m[p.so]||{so:p.so,ngay:p.ngay,cid:p.cid,ht:p.ht,nguoi:p.nguoi,note:p.note,tien:0,kys:[]};g.tien+=+p.tien||0;const i=invById(p.invId);if(i)g.kys.push(i.ky)});
  return Object.values(m).filter(g=>{const c=ctr(g.cid)||{},r=room(c.roomId)||{};return (!ht||g.ht===ht)&&(!f||g.ngay>=f)&&(!t||g.ngay<=t)&&(!q||fold(g.so+' '+r.code+' '+c.ten+' '+g.nguoi).includes(q))}).sort((a,b)=>b.ngay.localeCompare(a.ngay)||b.so.localeCompare(a.so))}
function renderPayTable(){const list=payGroups(),tot=list.reduce((s,g)=>s+g.tien,0);
  $('#pyTable').innerHTML=list.length?`<table><thead><tr><th>Ngày thu</th><th>Số phiếu</th><th>Phòng</th><th>Người nộp</th><th>Thu cho kỳ</th><th>Hình thức</th><th class="n">Số tiền</th><th></th></tr></thead><tbody>${pgSlice('py',list).map(g=>{const c=ctr(g.cid)||{},r=room(c.roomId)||{};
    return `<tr><td>${vnDate(g.ngay)}</td><td><b>${esc(g.so)}</b></td><td><span class="rcode">${esc(r.code||'?')}</span></td><td>${esc(g.nguoi||c.ten)}${g.note?`<br><small class="mut">${esc(g.note)}</small>`:''}</td><td>${[...new Set(g.kys)].sort().map(kyShort).join(', ')}</td><td><span class="tag ${g.ht==='Tiền mặt'?'w':g.ht==='Trừ tiền cọc'?'g':'i'}">${esc(g.ht)}</span></td><td class="n"><b>${fmt(g.tien)}</b></td>
    <td><div class="acts"><button class="btn sm ico" title="In phiếu thu" onclick="printReceipt('${g.so}')">${IC.print}</button><button class="btn sm ico danger" title="Xóa phiếu thu" onclick="delReceipt('${g.so}')">${IC.trash}</button></div></td></tr>`}).join('')}</tbody><tfoot><tr><td colspan="6">${list.length} phiếu thu</td><td class="n">${fmt(tot)}</td><td></td></tr></tfoot></table>${pgBar('py',list.length,'renderPayTable')}`:'<div class="empty">Chưa có phiếu thu phù hợp</div>'}
function delReceipt(so){if(!confirm('Xóa phiếu thu '+so+'? Các hóa đơn liên quan sẽ quay về trạng thái còn nợ.'))return;S.payments=S.payments.filter(p=>p.so!==so);save();toast('Đã xóa phiếu thu '+so);rerender()}

/* =========================================================
   BÁO CÁO DOANH THU
   ========================================================= */
let RPV='month';
$$('#rpView button').forEach(b=>b.onclick=()=>{RPV=b.dataset.v;$$('#rpView button').forEach(x=>x.classList.toggle('on',x===b));renderReport()});
$('#rpYear').addEventListener('input',()=>renderReport());
function repMonths(y){return Array.from({length:12},(_,i)=>{const k=y+'-'+pad(i+1),f=kyFirst(k),l=kyLast(k),o=sumInv(k,k);
  o.k=k;o.occ=new Set(S.contracts.filter(c=>c.ngayVao<=l&&(!c.ngayTra||c.ngayTra>=f)&&f<=TODAYS).map(c=>c.roomId)).size;o.rate=S.rooms.length?o.occ/S.rooms.length*100:0;o.dvk=o.rac+o.wifi+o.xe;o.thu=sumPay(f,l).v;return o})}
function repRooms(y){return sortRooms([...S.rooms]).map(r=>{const o={r,n:0,kwh:0,m3:0,phong:0,dn:0,dvk:0,tong:0,paid:0,rem:0};
  S.invoices.forEach(i=>{if(i.roomId!==r.id||i.ky.slice(0,4)!==String(y))return;o.n++;o.kwh+=+i.kwh||0;o.m3+=+i.m3||0;o.phong+=+i.tienPhong||0;o.dn+=(+i.tienDien||0)+(+i.tienNuoc||0);o.dvk+=dv(i)+(+i.khac||0)-(+i.giam||0);o.tong+=+i.tong||0;o.paid+=Math.min(paidOf(i),+i.tong||0);o.rem+=remOf(i)});return o})}
function renderReport(){
  const ys=[...new Set([TODAY.getFullYear(),...S.invoices.map(i=>+i.ky.slice(0,4))])].sort((a,b)=>b-a),sel=$('#rpYear'),cur=+sel.value||TODAY.getFullYear();
  sel.innerHTML=ys.map(y=>`<option value="${y}" ${y===cur?'selected':''}>Năm ${y}</option>`).join('');const y=+sel.value;
  const M=repMonths(y),A=M.reduce((a,o)=>{['tong','phong','dien','nuoc','dvk','khac','paid','rem','n','thu'].forEach(k=>a[k]=(a[k]||0)+o[k]);return a},{});
  const live=M.filter(o=>o.k<=KY_NOW&&o.n),avgRate=live.length?live.reduce((s,o)=>s+o.rate,0)/live.length:0,avgRoom=live.length?A.tong/live.reduce((s,o)=>s+o.occ,0):0;
  $('#rpPrintT').textContent='BÁO CÁO DOANH THU NHÀ TRỌ NĂM '+y;$('#rpPrintS').textContent=[S.cfg.ten,S.cfg.diaChi].filter(Boolean).join(' · ');
  $('#rpHero').innerHTML=`<div class="rh-t"><div><small>BÁO CÁO DOANH THU</small><h2>Năm ${y}${S.cfg.ten?' · '+esc(S.cfg.ten):''}</h2></div><span class="chip">${S.rooms.length} phòng · ${A.n} hóa đơn</span></div>
    <div class="flow"><div class="fb"><small>${IC.door}Tiền phòng</small><div class="q">${short(A.phong)}</div><div class="v2">${A.tong?Math.round(A.phong/A.tong*100):0}% tổng thu</div></div><div class="op">+</div>
      <div class="fb"><small>${IC.bolt}Điện nước</small><div class="q">${short(A.dien+A.nuoc)}</div><div class="v2">điện ${short(A.dien)} · nước ${short(A.nuoc)}</div></div><div class="op">+</div>
      <div class="fb"><small>${IC.receipt}Dịch vụ và khác</small><div class="q">${short(A.dvk+A.khac)}</div><div class="v2">rác, wifi, gửi xe, khoản khác</div></div><div class="op">=</div>
      <div class="fb end"><small>${IC.chart}Tổng phải thu</small><div class="q">${fmt(A.tong)}</div><div class="v2">đã thu ${short(A.paid)} · còn nợ ${short(A.rem)}</div></div></div>
    <div class="rh-m"><div><b>${String(A.tong?Math.round(A.paid/A.tong*1000)/10:0).replace('.',',')}%</b><span>Tỷ lệ đã thu</span></div><div><b>${String(Math.round(avgRate*10)/10).replace('.',',')}%</b><span>Lấp đầy bình quân</span></div><div><b>${short(avgRoom)} ₫</b><span>Thu bình quân 1 phòng / tháng</span></div><div><b>${short(A.thu)} ₫</b><span>Tiền thực thu trong năm (theo ngày thu)</span></div></div>`;
  if(RPV==='month'){$('#rpTitle').textContent='Doanh thu theo tháng';$('#rpHint').textContent='Theo kỳ hóa đơn';
    $('#rpTable').innerHTML=`<table><thead><tr><th>Tháng</th><th class="c">Phòng thuê</th><th class="n">Lấp đầy</th><th class="n">Tiền phòng</th><th class="n">Tiền điện</th><th class="n">Tiền nước</th><th class="n">Dịch vụ</th><th class="n">Khác</th><th class="n">Tổng phải thu</th><th class="n">Đã thu</th><th class="n">Còn nợ</th></tr></thead><tbody>${M.map(o=>`<tr${o.k===KY_NOW?' style="background:#fbf6fd"':''}><td class="nw"><b>${kyLbl(o.k)}</b></td><td class="c">${o.occ}</td><td class="n">${o.n?String(Math.round(o.rate*10)/10).replace('.',',')+'%':'-'}</td><td class="n">${fmt(o.phong)}</td><td class="n">${fmt(o.dien)}</td><td class="n">${fmt(o.nuoc)}</td><td class="n">${fmt(o.dvk)}</td><td class="n">${fmt(o.khac)}</td><td class="n"><b>${fmt(o.tong)}</b></td><td class="n green">${fmt(o.paid)}</td><td class="n ${o.rem?'red':''}">${fmt(o.rem)}</td></tr>`).join('')}</tbody>
      <tfoot><tr><td>Cả năm</td><td></td><td class="n">${String(Math.round(avgRate*10)/10).replace('.',',')}%</td><td class="n">${fmt(A.phong)}</td><td class="n">${fmt(A.dien)}</td><td class="n">${fmt(A.nuoc)}</td><td class="n">${fmt(A.dvk)}</td><td class="n">${fmt(A.khac)}</td><td class="n">${fmt(A.tong)}</td><td class="n">${fmt(A.paid)}</td><td class="n">${fmt(A.rem)}</td></tr></tfoot></table>`}
  else{const R=repRooms(y),B=R.reduce((a,o)=>{['n','kwh','m3','phong','dn','dvk','tong','paid','rem'].forEach(k=>a[k]=(a[k]||0)+o[k]);return a},{});$('#rpTitle').textContent='Doanh thu theo phòng';$('#rpHint').textContent='Năm '+y;
    $('#rpTable').innerHTML=`<table><thead><tr><th>Phòng</th><th>Dãy</th><th class="c">Số tháng có HĐ</th><th class="n">Điện (kWh)</th><th class="n">Nước (m³)</th><th class="n">Tiền phòng</th><th class="n">Điện nước</th><th class="n">Dịch vụ, khác</th><th class="n">Tổng phải thu</th><th class="n">Đã thu</th><th class="n">Còn nợ</th></tr></thead><tbody>${R.map(o=>`<tr><td><span class="rcode">${esc(o.r.code)}</span></td><td>${esc(o.r.day)}</td><td class="c">${o.n}</td><td class="n">${fmt(o.kwh)}</td><td class="n">${fmt(o.m3)}</td><td class="n">${fmt(o.phong)}</td><td class="n">${fmt(o.dn)}</td><td class="n">${fmt(o.dvk)}</td><td class="n"><b>${fmt(o.tong)}</b></td><td class="n green">${fmt(o.paid)}</td><td class="n ${o.rem?'red':''}">${fmt(o.rem)}</td></tr>`).join('')}</tbody>
      <tfoot><tr><td colspan="2">${R.length} phòng</td><td class="c">${B.n||0}</td><td class="n">${fmt(B.kwh)}</td><td class="n">${fmt(B.m3)}</td><td class="n">${fmt(B.phong)}</td><td class="n">${fmt(B.dn)}</td><td class="n">${fmt(B.dvk)}</td><td class="n">${fmt(B.tong)}</td><td class="n">${fmt(B.paid)}</td><td class="n">${fmt(B.rem)}</td></tr></tfoot></table>`}}

/* =========================================================
   IN: hóa đơn, phiếu thu, hợp đồng
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
function doPrint(html){$('#printArea').innerHTML=html;document.body.classList.add('pdoc');setTimeout(()=>window.print(),80)}
const coHead=()=>{const cf=S.cfg;return `<div><b class="t">${esc(cf.ten||'Nhà trọ')}</b><br>${esc(cf.diaChi||'')}${cf.sdt?'<br>Điện thoại: '+esc(phoneFmt(cf.sdt)):''}</div>`};
function invHtml(i,last){const c=ctr(i.cid)||{},r=room(i.roomId)||{},cf=S.cfg,p=Math.min(paidOf(i),i.tong);let n=0;const row=(a,b,cc,d,e,f)=>`<tr><td class="c">${++n}</td><td>${a}</td><td class="n">${b}</td><td class="n">${cc}</td><td class="c">${d}</td><td class="n">${e}</td><td class="n">${f}</td></tr>`;
  return `<div class="pv${last?'':' brk'}"><div class="hd">${coHead()}<div class="r">Số: <b>${esc(i.so)}</b><br>Ngày lập: ${vnDate(i.ngay)}</div></div>
    <h2>HÓA ĐƠN TIỀN PHÒNG</h2><div class="dt">${kyLbl(i.ky)}</div>
    <div class="info"><div>Phòng: <b>${esc(r.code)}</b> (${esc(r.day||'')}, tầng ${esc(r.tang||'')})</div><div>Người thuê: <b>${esc(c.ten||'')}</b></div><div>Số người ở: ${i.soNguoi} · số xe: ${i.soXe}</div><div>Điện thoại: ${phoneFmt(c.sdt)}</div></div>
    <table><thead><tr><th style="width:36px">STT</th><th>Khoản thu</th><th style="width:85px">Chỉ số cũ</th><th style="width:85px">Chỉ số mới</th><th style="width:95px">Số lượng</th><th style="width:95px">Đơn giá</th><th style="width:115px">Thành tiền</th></tr></thead><tbody>
    ${row('Tiền phòng'+(i.soNgay<i.dim?` (ở ${i.soNgay}/${i.dim} ngày)`:''),'','',i.soNgay<i.dim?i.soNgay+' ngày':'1 tháng',fmt(i.gia),fmt(i.tienPhong))}
    ${row('Tiền điện',fmt(i.dc),fmt(i.dm),fmt(i.kwh)+' kWh',fmt(i.giaDien),fmt(i.tienDien))}
    ${i.nuocKieu==='nguoi'?row('Tiền nước (khoán theo người)','','',i.soNguoi+' người',fmt(i.giaNuoc),fmt(i.tienNuoc)):row('Tiền nước',fmt(i.nc),fmt(i.nm),fmt(i.m3)+' m³',fmt(i.giaNuoc),fmt(i.tienNuoc))}
    ${i.rac?row('Tiền rác','','',i.racKieu==='nguoi'?i.soNguoi+' người':'1 phòng','',fmt(i.rac)):''}${i.wifi?row('Wifi','','','1 tháng','',fmt(i.wifi)):''}${i.xe?row('Gửi xe','','',i.soXe+' xe',fmt(i.giaXe),fmt(i.xe)):''}
    ${i.khac?row(esc(i.khacNd||'Khoản khác'),'','','','',fmt(i.khac)):''}${i.giam?row('Giảm trừ'+(i.giamNd?': '+esc(i.giamNd):''),'','','','','-'+fmt(i.giam)):''}
    <tr class="tot"><td colspan="6" style="text-align:right">TỔNG CỘNG</td><td class="n">${fmt(i.tong)}</td></tr>
    ${p?`<tr><td colspan="6" style="text-align:right">Đã thanh toán</td><td class="n">${fmt(p)}</td></tr><tr class="tot"><td colspan="6" style="text-align:right">CÒN PHẢI THANH TOÁN</td><td class="n">${fmt(i.tong-p)}</td></tr>`:''}</tbody></table>
    <div>Bằng chữ: <b><i>${docSo(i.tong-p)}</i></b></div>
    <div class="pay">Hạn thanh toán: <b>${vnDate(i.han)}</b>${cf.stk?`<br>Chuyển khoản: <b>${esc(cf.nganHang)} · ${esc(cf.stk)} · ${esc(cf.chuTK)}</b><br>Nội dung chuyển khoản: <b>${esc(r.code)} ${kyShort(i.ky)}</b>`:''}${i.ghiChu?'<br>Ghi chú: '+esc(i.ghiChu):''}</div>
    <div class="sign" style="--n:2"><div><b>Người thuê phòng</b><i>(Ký, ghi rõ họ tên)</i></div><div><b>Người lập hóa đơn</b><i>(Ký, ghi rõ họ tên)</i></div></div></div>`}
function printInv(ids){const list=ids.map(invById).filter(Boolean);if(!list.length)return toast('Không có hóa đơn để in','err');doPrint(list.map((i,k)=>invHtml(i,k===list.length-1)).join(''))}
function printKy(){const list=invRows();if(!list.length)return toast('Không có hóa đơn nào để in','err');if(list.length>5&&!confirm(`In ${list.length} hóa đơn (mỗi hóa đơn một trang)?`))return;printInv(list.map(i=>i.id))}
function printReceipt(so){const ps=S.payments.filter(p=>p.so===so);if(!ps.length)return toast('Không tìm thấy phiếu thu '+so,'err');
  const p0=ps[0],c=ctr(p0.cid)||{},r=room(c.roomId)||{},tot=ps.reduce((s,p)=>s+p.tien,0),[y,m,d]=p0.ngay.split('-');
  const kys=[...new Set(ps.map(p=>(invById(p.invId)||{}).ky).filter(Boolean))].sort().map(kyLbl).join(', ');
  doPrint(`<div class="pv"><div class="hd">${coHead()}<div class="r">Số: <b>${esc(so)}</b></div></div><h2>PHIẾU THU</h2><div class="dt">Ngày ${d} tháng ${m} năm ${y}</div>
    <p>Họ tên người nộp tiền: <b>${esc(p0.nguoi||c.ten)}</b></p><p>Phòng: <b>${esc(r.code)}</b> · Người thuê: ${esc(c.ten||'')}</p><p>Lý do nộp: Tiền phòng ${esc(kys)}${p0.note?' ('+esc(p0.note)+')':''}</p>
    <p>Số tiền: <b style="font-size:17px">${money(tot)}</b> · Hình thức: ${esc(p0.ht)}</p><p>Viết bằng chữ: <b><i>${docSo(tot)}</i></b></p>
    <table><thead><tr><th>Hóa đơn</th><th>Kỳ</th><th>Tổng hóa đơn</th><th>Thu lần này</th><th>Còn lại sau khi thu</th></tr></thead><tbody>${ps.map(p=>{const i=invById(p.invId)||{};return `<tr><td>${esc(i.so||'')}</td><td class="c">${i.ky?kyLbl(i.ky):''}</td><td class="n">${fmt(i.tong)}</td><td class="n">${fmt(p.tien)}</td><td class="n">${i.id?fmt(remOf(i)):''}</td></tr>`}).join('')}</tbody></table>
    <div class="sign" style="--n:2"><div><b>Người nộp tiền</b><i>(Ký, ghi rõ họ tên)</i></div><div><b>Người thu tiền</b><i>(Ký, ghi rõ họ tên)</i></div></div></div>`)}
function printContract(id){const c=ctr(id);if(!c)return;const r=room(c.roomId)||{},cf=S.cfg,[y,m,d]=(c.ngayVao||TODAYS).split('-');
  const dots='....................................';
  doPrint(`<div class="vt"><div class="qh">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<div>Độc lập - Tự do - Hạnh phúc</div></div>
    <h2>HỢP ĐỒNG THUÊ PHÒNG TRỌ</h2><div class="so">Số: ${esc(c.code)}</div>
    <p>Hôm nay, ngày ${d} tháng ${m} năm ${y}, tại ${esc(cf.diaChi||dots)}, chúng tôi gồm:</p>
    <h4>BÊN CHO THUÊ (Bên A)</h4><p>Ông/Bà: <b>${esc(cf.chu||dots)}</b> · Điện thoại: ${esc(phoneFmt(cf.sdt)||dots)}</p><p>Địa chỉ nhà trọ: ${esc(cf.diaChi||dots)}</p>
    <h4>BÊN THUÊ (Bên B)</h4><p>Ông/Bà: <b>${esc(c.ten)}</b> · Ngày sinh: ${vnDate(c.ngaySinh)||dots}</p><p>Số CCCD: ${esc(c.cccd||dots)} · Điện thoại: ${phoneFmt(c.sdt)||dots}</p><p>Quê quán / thường trú: ${esc(c.que||dots)}${c.oCung?'</p><p>Người ở cùng: '+esc(c.oCung):''}</p>
    <p>Hai bên thống nhất ký hợp đồng thuê phòng trọ với các điều khoản sau:</p>
    <h4>Điều 1. Phòng cho thuê</h4><p>Bên A cho Bên B thuê phòng số <b>${esc(r.code)}</b> (${esc(r.day||'')}, tầng ${esc(r.tang||'')}), diện tích ${fmt(r.dt)} m², tiện ích: ${esc(r.tienIch||'theo hiện trạng')}. Số người ở: ${c.soNguoi} người (tối đa ${fmt(r.toiDa)} người).</p>
    <h4>Điều 2. Giá thuê và cách thanh toán</h4><p>Giá thuê: <b>${money(c.gia)}</b>/tháng (${docSo(c.gia)}). Tiền điện ${fmt(cf.giaDien)} ₫/kWh; tiền nước ${cf.nuocKieu==='nguoi'?fmt(cf.giaNuocNguoi)+' ₫/người/tháng':fmt(cf.giaNuoc)+' ₫/m³'}; tiền rác ${fmt(cf.rac)} ₫/${cf.racKieu==='nguoi'?'người':'phòng'}/tháng${c.wifi?'; wifi '+fmt(cf.wifi)+' ₫/tháng':''}${c.soXe?'; gửi xe '+fmt(cf.xe)+' ₫/xe/tháng ('+c.soXe+' xe'+(c.bienSo?': '+esc(c.bienSo):'')+')':''}.</p><p>Bên B thanh toán tiền phòng và các khoản trên chậm nhất ngày ${+cf.hanTT||5} tháng sau, bằng tiền mặt hoặc chuyển khoản${cf.stk?' vào tài khoản '+esc(cf.nganHang)+' '+esc(cf.stk)+' ('+esc(cf.chuTK)+')':''}.</p>
    <h4>Điều 3. Tiền đặt cọc</h4><p>Bên B đặt cọc <b>${money(c.coc)}</b> (${docSo(c.coc)}). Khi hết hạn hợp đồng, Bên A hoàn trả tiền cọc sau khi trừ các khoản Bên B còn nợ và chi phí sửa chữa hư hỏng do Bên B gây ra (nếu có). Bên B tự ý chấm dứt hợp đồng trước hạn mà không báo trước 30 ngày thì không được hoàn cọc.</p>
    <h4>Điều 4. Thời hạn thuê</h4><p>Từ ngày ${vnDate(c.ngayVao)} đến ngày ${vnDate(c.ngayHet)||dots}. Hết hạn, hai bên có thể thỏa thuận gia hạn.</p>
    <h4>Điều 5. Trách nhiệm hai bên</h4><p>Bên A: giao phòng đúng hiện trạng, bảo đảm điện nước sinh hoạt, sửa chữa hư hỏng không do Bên B gây ra, hỗ trợ đăng ký tạm trú.</p><p>Bên B: sử dụng phòng đúng mục đích để ở, giữ gìn vệ sinh và tài sản, thanh toán đúng hạn, không tự ý sửa chữa, cho người khác ở thêm hoặc cho thuê lại khi chưa có đồng ý của Bên A; chấp hành nội quy nhà trọ và quy định về phòng cháy chữa cháy, an ninh trật tự.</p>
    <p>Hợp đồng lập thành 02 bản, mỗi bên giữ 01 bản, có giá trị như nhau.</p>
    <div class="sign"><div><b>BÊN CHO THUÊ (Bên A)</b><i>(Ký, ghi rõ họ tên)</i></div><div><b>BÊN THUÊ (Bên B)</b><i>(Ký, ghi rõ họ tên)</i></div></div>
    <p style="font-size:11px;color:#666;text-align:center">Mẫu hợp đồng tham khảo, chủ trọ chỉnh điều khoản cho phù hợp thực tế.</p></div>`)}

/* =========================================================
   GHI / ĐỌC FILE EXCEL .xlsx (tự viết, không cần mạng)
   ========================================================= */
const XLSX=(()=>{
const D={serial(s){const [y,m,d]=String(s).slice(0,10).split('-').map(Number);return (Date.UTC(y,m-1,d)-Date.UTC(1899,11,30))/864e5}};
const sum=(arr,f)=>arr.reduce((s,x)=>s+(+(f?f(x):x)||0),0);
function download(blob,name){const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=name;document.body.appendChild(a);a.click();setTimeout(()=>{URL.revokeObjectURL(a.href);a.remove()},1500)}
const crcT=(()=>{const t=new Uint32Array(256);for(let n=0;n<256;n++){let c=n;for(let k=0;k<8;k++)c=c&1?0xEDB88320^(c>>>1):c>>>1;t[n]=c>>>0}return t})();
const crc32=b=>{let c=0xFFFFFFFF;for(let i=0;i<b.length;i++)c=crcT[(c^b[i])&0xFF]^(c>>>8);return (c^0xFFFFFFFF)>>>0};
const enc=new TextEncoder();
function zip(files){
  const parts=[],cd=[];let off=0;
  const now=new Date();const dt=((now.getFullYear()-1980)<<9)|((now.getMonth()+1)<<5)|now.getDate();const tm=(now.getHours()<<11)|(now.getMinutes()<<5)|(now.getSeconds()>>1);
  for(const f of files){
    const name=enc.encode(f.name),data=typeof f.data==='string'?enc.encode(f.data):f.data,crc=crc32(data);
    const h=new DataView(new ArrayBuffer(30));
    h.setUint32(0,0x04034b50,true);h.setUint16(4,20,true);h.setUint16(6,0x0800,true);h.setUint16(8,0,true);h.setUint16(10,tm,true);h.setUint16(12,dt,true);
    h.setUint32(14,crc,true);h.setUint32(18,data.length,true);h.setUint32(22,data.length,true);h.setUint16(26,name.length,true);h.setUint16(28,0,true);
    parts.push(new Uint8Array(h.buffer),name,data);
    const c=new DataView(new ArrayBuffer(46));
    c.setUint32(0,0x02014b50,true);c.setUint16(4,20,true);c.setUint16(6,20,true);c.setUint16(8,0x0800,true);c.setUint16(10,0,true);c.setUint16(12,tm,true);c.setUint16(14,dt,true);
    c.setUint32(16,crc,true);c.setUint32(20,data.length,true);c.setUint32(24,data.length,true);c.setUint16(28,name.length,true);
    c.setUint32(42,off,true);
    cd.push(new Uint8Array(c.buffer),name);
    off+=30+name.length+data.length;
  }
  const cdSize=cd.reduce((s,a)=>s+a.length,0);
  const e=new DataView(new ArrayBuffer(22));
  e.setUint32(0,0x06054b50,true);e.setUint16(8,files.length,true);e.setUint16(10,files.length,true);e.setUint32(12,cdSize,true);e.setUint32(16,off,true);
  return new Blob([...parts,...cd,new Uint8Array(e.buffer)],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
}
const x=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g,'');
const colName=i=>{let s='';i++;while(i>0){const m=(i-1)%26;s=String.fromCharCode(65+m)+s;i=Math.floor((i-1)/26)}return s};
// Kiểu ô: 1 tiêu đề cột, 2 chữ, 3 số #,##0, 4 ngày, 5 tiêu đề lớn, 6 tổng số, 7 %, 8 tổng chữ, 9 dòng phụ, 10 số lẻ, 11 chữ giữa, 12 tổng số lẻ
const ST={text:2,money:3,int:3,date:4,pct:7,num:10,center:11};
function styles(hex){
  const c='FF'+hex.replace('#','').toUpperCase();
  return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="0.0%"/><numFmt numFmtId="167" formatCode="#,##0.0"/></numFmts>
<fonts count="5"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="${c}"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="${c}"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF3E5F5"/><bgColor indexed="64"/></patternFill></fill></fills>
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
function cell(ref,v,type,s){
  if(v===null||v===undefined||v==='')return `<c r="${ref}" s="${s}"/>`;
  if(type==='date'){if(!/^\d{4}-\d{2}-\d{2}/.test(String(v)))return `<c r="${ref}" s="11" t="inlineStr"><is><t>${x(v)}</t></is></c>`;return `<c r="${ref}" s="${s}"><v>${D.serial(v)}</v></c>`}
  if(['money','int','num','pct'].includes(type)){const n=type==='pct'?(+v||0)/100:+v||0;return `<c r="${ref}" s="${s}"><v>${n}</v></c>`}
  return `<c r="${ref}" s="${s}" t="inlineStr"><is><t xml:space="preserve">${x(v)}</t></is></c>`;
}
function filterRow(s){return (s.title?1:0)+(s.subtitles||[]).length+((s.title||(s.subtitles||[]).length)?1:0)+1}
function sheetXML(sh){
  const cols=sh.columns,nc=cols.length,last=colName(nc-1);
  let r=0;const rows=[];const merges=[];
  if(sh.title){r++;rows.push(`<row r="${r}" ht="24" customHeight="1">${cell('A'+r,sh.title,'text',5)}</row>`);merges.push(`A${r}:${last}${r}`)}
  (sh.subtitles||[]).forEach(t=>{r++;rows.push(`<row r="${r}">${cell('A'+r,t,'text',9)}</row>`);merges.push(`A${r}:${last}${r}`)});
  if(r)r++;
  r++;const hr=r;
  rows.push(`<row r="${r}" ht="30" customHeight="1">${cols.map((c,i)=>cell(colName(i)+r,c.label,'text',1)).join('')}</row>`);
  for(const row of sh.rows){r++;rows.push(`<row r="${r}">${cols.map((c,i)=>{const t=c.type||'text';return cell(colName(i)+r,Array.isArray(row)?row[i]:row[c.key],t,ST[t]||2)}).join('')}</row>`)}
  const lastData=r;
  if(sh.totals&&sh.rows.length){r++;
    rows.push(`<row r="${r}">${cols.map((c,i)=>{const ref=colName(i)+r;
      if(c.sum){const f=`SUBTOTAL(9,${colName(i)}${hr+1}:${colName(i)}${lastData})`;const v=sum(sh.rows,row=>Array.isArray(row)?row[i]:row[c.key]);return `<c r="${ref}" s="${c.type==='num'?12:6}"><f>${f}</f><v>${v}</v></c>`}
      return cell(ref,i===0?(sh.totalLabel||'TỔNG CỘNG'):'','text',8)}).join('')}</row>`)}
  const colsXml=`<cols>${cols.map((c,i)=>`<col min="${i+1}" max="${i+1}" width="${c.width||({money:15,date:12,pct:9,num:9,int:9}[c.type]||18)}" customWidth="1"/>`).join('')}</cols>`;
  return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:${last}${Math.max(r,1)}"/><sheetViews><sheetView workbookViewId="0"><pane ${sh.freezeCols?`xSplit="${sh.freezeCols}" `:''}ySplit="${hr}" topLeftCell="${sh.freezeCols?colName(sh.freezeCols):'A'}${hr+1}" activePane="bottomRight" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>${colsXml}<sheetData>${rows.join('')}</sheetData>${sh.rows.length&&!sh.noFilter?`<autoFilter ref="A${hr}:${last}${lastData}"/>`:''}${merges.length?`<mergeCells count="${merges.length}">${merges.map(m=>`<mergeCell ref="${m}"/>`).join('')}</mergeCells>`:''}<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="${sh.portrait?'portrait':'landscape'}" fitToWidth="1" fitToHeight="0"/></worksheet>`;
}
function save(fileName,sheets){
  const used={};
  const names=sheets.map(s=>{let n=String(s.name||'Sheet').replace(/[\[\]:*?\/\\]/g,' ').slice(0,31)||'Sheet';let k=n,i=2;while(used[k.toLowerCase()])k=n.slice(0,28)+' '+i++;used[k.toLowerCase()]=1;return k});
  const files=[
    {name:'[Content_Types].xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>${sheets.map((_,i)=>`<Override PartName="/xl/worksheets/sheet${i+1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}</Types>`},
    {name:'_rels/.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`},
    {name:'xl/workbook.xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>${names.map((n,i)=>`<sheet name="${x(n)}" sheetId="${i+1}" r:id="rId${i+1}"/>`).join('')}</sheets><definedNames>${sheets.map((s,i)=>s.rows.length&&!s.noFilter?`<definedName name="_xlnm._FilterDatabase" localSheetId="${i}" hidden="1">'${x(names[i]).replace(/'/g,"''")}'!$A$${filterRow(s)}:$${colName(s.columns.length-1)}$${filterRow(s)+s.rows.length}</definedName>`:'').join('')}</definedNames></workbook>`},
    {name:'xl/_rels/workbook.xml.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${sheets.map((_,i)=>`<Relationship Id="rId${i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${i+1}.xml"/>`).join('')}<Relationship Id="rId${sheets.length+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`},
    {name:'xl/styles.xml',data:styles('#6A1B9A')},
    ...sheets.map((s,i)=>({name:`xl/worksheets/sheet${i+1}.xml`,data:sheetXML(s)}))
  ];
  download(zip(files),fileName.replace(/\.xlsx$/i,'')+'.xlsx');
  toast('Đã tải file Excel '+fileName+'.xlsx');
}
async function unzip(buf){
  const u=new Uint8Array(buf),v=new DataView(buf),dec=new TextDecoder(),out={};
  let e=u.length-22;while(e>=0&&v.getUint32(e,true)!==0x06054b50)e--;
  if(e<0)throw new Error('Không phải file Excel (.xlsx)');
  let p=v.getUint32(e+16,true);const n=v.getUint16(e+10,true);
  for(let i=0;i<n;i++){
    const method=v.getUint16(p+10,true),size=v.getUint32(p+20,true),nl=v.getUint16(p+28,true),xl=v.getUint16(p+30,true),cl=v.getUint16(p+32,true),lo=v.getUint32(p+42,true);
    const name=dec.decode(u.subarray(p+46,p+46+nl));p+=46+nl+xl+cl;
    const start=lo+30+v.getUint16(lo+26,true)+v.getUint16(lo+28,true),raw=u.subarray(start,start+size);
    out[name]=method===0?raw:method===8?new Uint8Array(await new Response(new Blob([raw]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).arrayBuffer()):null;
  }
  return out;
}
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
return {save,read};
})();

/* =========================================================
   XUẤT EXCEL
   ========================================================= */
const sub1=()=>[[S.cfg.ten,S.cfg.diaChi].filter(Boolean).join(' · ')||'Quản lý phòng trọ','Xuất ngày '+vnDate(TODAYS)];
function shRooms(){return {name:'Phòng',title:'DANH SÁCH PHÒNG',subtitles:sub1(),totals:true,columns:[{label:'Mã phòng',width:10},{label:'Dãy',width:10},{label:'Tầng',type:'int',width:7},{label:'Diện tích (m²)',type:'num',width:11},{label:'Giá thuê',type:'money',sum:true},{label:'Tối đa (người)',type:'int',width:10},{label:'Tiện ích',width:32},{label:'Tình trạng',width:14},{label:'Người thuê',width:24},{label:'Số người',type:'int',sum:true,width:9},{label:'Còn nợ',type:'money',sum:true},{label:'Ghi chú',width:26}],
  rows:sortRooms([...S.rooms]).map(r=>{const c=curC(r.id);return [r.code,r.day,+r.tang||'',+r.dt||0,+r.gia||0,+r.toiDa||0,r.tienIch,ST_R[roomSt(r)],c?c.ten:'',c?+c.soNguoi:0,c?debtOfC(c.id):0,r.note]})}}
function shTenants(list){return {name:'Người thuê',title:'DANH SÁCH NGƯỜI THUÊ VÀ HỢP ĐỒNG',subtitles:sub1(),totals:true,freezeCols:3,columns:[{label:'Mã HĐ',width:9},{label:'Phòng',width:8},{label:'Họ tên',width:24},{label:'Điện thoại',width:13},{label:'CCCD',width:15},{label:'Ngày sinh',type:'date'},{label:'Quê quán',width:16},{label:'Nghề nghiệp',width:18},{label:'Ngày vào',type:'date'},{label:'Hết hạn HĐ',type:'date'},{label:'Ngày trả phòng',type:'date'},{label:'Giá thuê',type:'money'},{label:'Tiền cọc',type:'money',sum:true},{label:'Số người',type:'int',width:9},{label:'Số xe',type:'int',width:7},{label:'Biển số xe',width:22},{label:'Wifi',width:7},{label:'Còn nợ',type:'money',sum:true},{label:'Ghi chú',width:30}],
  rows:(list||S.contracts).map(c=>[c.code,(room(c.roomId)||{}).code,c.ten,c.sdt,c.cccd,c.ngaySinh,c.que,c.nghe,c.ngayVao,c.ngayHet,c.ngayTra,+c.gia||0,+c.coc||0,+c.soNguoi||0,+c.soXe||0,c.bienSo,c.wifi?'Có':'Không',debtOfC(c.id),c.note])}}
function shReadings(k){const rs=S.readings.filter(r=>!k||r.ky===k).sort((a,b)=>b.ky.localeCompare(a.ky)||String((room(a.roomId)||{}).code).localeCompare(String((room(b.roomId)||{}).code),'vi',{numeric:true}));
  return {name:'Chỉ số điện nước',title:'CHỈ SỐ ĐIỆN NƯỚC'+(k?' '+kyLbl(k).toUpperCase():''),subtitles:sub1(),totals:true,columns:[{label:'Kỳ',width:10},{label:'Phòng',width:8},{label:'Người thuê',width:24},{label:'Ngày ghi',type:'date'},{label:'Điện cũ',type:'int',width:10},{label:'Điện mới',type:'int',width:10},{label:'Điện tiêu thụ (kWh)',type:'int',sum:true,width:12},{label:'Nước cũ',type:'int',width:10},{label:'Nước mới',type:'int',width:10},{label:'Nước tiêu thụ (m³)',type:'int',sum:true,width:12}],
  rows:rs.map(r=>[kyShort(r.ky),(room(r.roomId)||{}).code,(ctr(r.cid)||{}).ten,r.ngay,r.dc,r.dm,r.dm!==''&&r.dc!==''?Math.max(0,r.dm-r.dc):'',r.nc,r.nm,r.nm!==''&&r.nc!==''?Math.max(0,r.nm-r.nc):''])}}
function shInv(list){return {name:'Hóa đơn',title:'HÓA ĐƠN TIỀN PHÒNG',subtitles:sub1(),totals:true,freezeCols:3,columns:[{label:'Số HĐ',width:15},{label:'Kỳ',width:9},{label:'Phòng',width:8},{label:'Người thuê',width:24},{label:'Ngày lập',type:'date'},{label:'Hạn thanh toán',type:'date'},{label:'Số ngày ở',type:'int',width:9},{label:'Tiền phòng',type:'money',sum:true},{label:'Điện (kWh)',type:'int',sum:true,width:10},{label:'Tiền điện',type:'money',sum:true},{label:'Nước (m³)',type:'int',sum:true,width:10},{label:'Tiền nước',type:'money',sum:true},{label:'Rác',type:'money',sum:true,width:11},{label:'Wifi',type:'money',sum:true,width:11},{label:'Gửi xe',type:'money',sum:true,width:11},{label:'Khoản khác',type:'money',sum:true,width:12},{label:'Giảm trừ',type:'money',sum:true,width:12},{label:'Tổng cộng',type:'money',sum:true},{label:'Đã thu',type:'money',sum:true},{label:'Còn lại',type:'money',sum:true},{label:'Trạng thái',width:13}],
  rows:(list||S.invoices).slice().sort((a,b)=>b.ky.localeCompare(a.ky)).map(i=>[i.so,kyShort(i.ky),(room(i.roomId)||{}).code,(ctr(i.cid)||{}).ten,i.ngay,i.han,i.soNgay,i.tienPhong,i.kwh,i.tienDien,i.m3,i.tienNuoc,i.rac,i.wifi,i.xe,i.khac,i.giam,i.tong,Math.min(paidOf(i),i.tong),remOf(i),ST_I[invSt(i)][1]])}}
function shPay(list){return {name:'Phiếu thu',title:'SỔ THU TIỀN PHÒNG',subtitles:sub1(),totals:true,columns:[{label:'Ngày thu',type:'date'},{label:'Số phiếu',width:11},{label:'Phòng',width:8},{label:'Người nộp',width:24},{label:'Thu cho kỳ',width:18},{label:'Hình thức',width:14},{label:'Số tiền',type:'money',sum:true},{label:'Ghi chú',width:30}],
  rows:(list||payAll()).map(g=>[g.ngay,g.so,(room((ctr(g.cid)||{}).roomId)||{}).code,g.nguoi,[...new Set(g.kys)].sort().map(kyShort).join(', '),g.ht,g.tien,g.note])}}
function payAll(){const m={};S.payments.forEach(p=>{const g=m[p.so]=m[p.so]||{so:p.so,ngay:p.ngay,cid:p.cid,ht:p.ht,nguoi:p.nguoi,note:p.note,tien:0,kys:[]};g.tien+=+p.tien||0;const i=invById(p.invId);if(i)g.kys.push(i.ky)});return Object.values(m).sort((a,b)=>b.ngay.localeCompare(a.ngay))}
function shDebt(){return {name:'Công nợ',title:'CÔNG NỢ TIỀN PHÒNG',subtitles:sub1(),totals:true,columns:[{label:'Phòng',width:8},{label:'Người thuê',width:24},{label:'Điện thoại',width:13},{label:'Số HĐ nợ',type:'int',width:9},{label:'Nợ từ kỳ',width:10},{label:'Quá hạn (ngày)',type:'int',width:11},{label:'Tiền cọc',type:'money'},{label:'Còn nợ',type:'money',sum:true}],
  rows:debtRows().map(g=>{const c=ctr(g.cid);return [(room(c.roomId)||{}).code,c.ten,c.sdt,g.n,kyShort(g.oldest),Math.max(0,dDiff(g.han,TODAYS)),+c.coc||0,g.rem]})}}
function shMonths(y){return {name:'Doanh thu '+y,title:'DOANH THU THEO THÁNG NĂM '+y,subtitles:sub1(),totals:true,totalLabel:'CẢ NĂM',columns:[{label:'Tháng',width:13},{label:'Phòng thuê',type:'int',width:10},{label:'Lấp đầy',type:'pct',width:9},{label:'Tiền phòng',type:'money',sum:true},{label:'Tiền điện',type:'money',sum:true},{label:'Tiền nước',type:'money',sum:true},{label:'Dịch vụ',type:'money',sum:true},{label:'Khác',type:'money',sum:true},{label:'Tổng phải thu',type:'money',sum:true},{label:'Đã thu',type:'money',sum:true},{label:'Còn nợ',type:'money',sum:true}],
  rows:repMonths(y).map(o=>[kyLbl(o.k),o.occ,Math.round(o.rate*10)/10,o.phong,o.dien,o.nuoc,o.dvk,o.khac,o.tong,o.paid,o.rem])}}
function shRoomsRep(y){return {name:'Theo phòng '+y,title:'DOANH THU THEO PHÒNG NĂM '+y,subtitles:sub1(),totals:true,columns:[{label:'Phòng',width:8},{label:'Dãy',width:10},{label:'Số tháng có HĐ',type:'int',width:11},{label:'Điện (kWh)',type:'int',sum:true,width:11},{label:'Nước (m³)',type:'int',sum:true,width:10},{label:'Tiền phòng',type:'money',sum:true},{label:'Điện nước',type:'money',sum:true},{label:'Dịch vụ, khác',type:'money',sum:true},{label:'Tổng phải thu',type:'money',sum:true},{label:'Đã thu',type:'money',sum:true},{label:'Còn nợ',type:'money',sum:true}],
  rows:repRooms(y).map(o=>[o.r.code,o.r.day,o.n,o.kwh,o.m3,o.phong,o.dn,o.dvk,o.tong,o.paid,o.rem])}}
function exportAll(){const y=TODAY.getFullYear();XLSX.save('Quan ly phong tro '+TODAYS,[shRooms(),shTenants(),shReadings(),shInv(),shPay(),shDebt(),shMonths(y),shRoomsRep(y)])}
function exportMeter(){XLSX.save('Chi so dien nuoc '+MKY,[shReadings(MKY)])}
function exportInv(){XLSX.save('Hoa don '+(IVKY||'tat ca'),[shInv(invRows())])}
function exportDebt(){XLSX.save('Cong no tien phong '+TODAYS,[shDebt(),shPay(payGroups())])}
function exportPay(){XLSX.save('So thu tien '+TODAYS,[shPay(payGroups())])}
function exportTenants(){const q=fold($('#tnSearch').value.trim());XLSX.save('Nguoi thue '+TODAYS,[shTenants(S.contracts.filter(c=>!q||fold(c.ten+' '+c.sdt+' '+c.cccd).includes(q)))])}
function exportReport(){const y=+$('#rpYear').value||TODAY.getFullYear();XLSX.save('Bao cao doanh thu '+y,[shMonths(y),shRoomsRep(y)])}

/* =========================================================
   NHẬP DANH SÁCH PHÒNG TỪ EXCEL / CSV / DÁN
   ========================================================= */
const IMP_COLS=[
  {key:'code',label:'Mã phòng',w:11,alias:['ma','maphong','phong','sophong','code']},
  {key:'day',label:'Dãy',w:11,alias:['day','khu','daykhu','toa','block']},
  {key:'tang',label:'Tầng',num:1,w:7,alias:['tang','lau']},
  {key:'dt',label:'Diện tích (m²)',num:1,w:12,alias:['dientich','dientichm2','m2','dt']},
  {key:'gia',label:'Giá thuê',num:1,w:14,alias:['gia','giathue','giaphong','tienphong','dongia']},
  {key:'toiDa',label:'Số người tối đa',num:1,w:13,alias:['songuoitoida','toida','songuoi','succhua']},
  {key:'tienIch',label:'Tiện ích',w:32,alias:['tienich','trangbi','noithat']},
  {key:'note',label:'Ghi chú',w:26,alias:['ghichu','mota']}];
const normH=s=>fold(s).replace(/[^a-z0-9]/g,'');
function impNum(v){if(typeof v==='number')return v;const s=String(v??'').trim();if(!s)return 0;if(/^-?\d+[.,]\d{1,2}$/.test(s))return parseFloat(s.replace(',','.'));const n=parseInt(s.replace(/[^\d-]/g,''),10);return isNaN(n)?0:n}
let IMP={items:[],mode:'update'};
function impParse(rows){
  rows=rows.filter(r=>r&&r.some(c=>String(c??'').trim()!==''));let hr=-1,map={};
  for(let r=0;r<Math.min(rows.length,20)&&hr<0;r++){const m={};rows[r].forEach((h,i)=>{const n=normH(h),c=IMP_COLS.find(c=>c.alias.includes(n));if(c&&m[c.key]===undefined)m[c.key]=i});if(m.code!==undefined)
{hr=r;map=m}}
  if(hr<0)IMP_COLS.forEach((c,i)=>map[c.key]=i);
  const seen=new Set(),out=[];
  rows.slice(hr+1).forEach((r,k)=>{const g=key=>map[key]===undefined?'':r[map[key]],has={};IMP_COLS.forEach(c=>has[c.key]=map[c.key]!==undefined&&String(g(c.key)??'').trim()!=='');
    const code=String(g('code')??'').trim().toUpperCase();if(!code)return;
    const o={row:hr+k+2,has,code,day:String(g('day')??'').trim(),tang:impNum(g('tang')),dt:impNum(g('dt')),gia:impNum(g('gia')),toiDa:impNum(g('toiDa')),tienIch:String(g('tienIch')??'').trim(),note:String(g('note')??'').trim()};
    if(seen.has(code))o.st='dup';else{seen.add(code);o.old=S.rooms.find(x=>x.code===code);o.st=o.old?'old':'new'}out.push(o)});
  return out}
function openImport(){IMP={items:[],mode:'update'};pgReset('imp');
  openModal(`<div class="modal-h"><h3>Nhập danh sách phòng</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><label class="btn pri" style="cursor:pointer">${IC.up}Chọn file Excel<input type="file" id="impFile" accept=".xlsx,.csv" hidden></label>
      <button class="btn" onclick="impTemplate()">${IC.down}Tải file mẫu</button><span id="impName" style="color:var(--muted);font-size:13px">Chưa chọn file</span></div>
    <p class="hint2">File cần dòng tiêu đề với các cột: <b>Mã phòng, Dãy, Tầng, Diện tích (m²), Giá thuê, Số người tối đa, Tiện ích, Ghi chú</b>. Bắt buộc có cột Mã phòng, các cột khác thiếu cũng được.</p>
    <label class="f">Hoặc dán thẳng từ Excel: bôi đen vùng dữ liệu (cả dòng tiêu đề), Ctrl + C rồi bấm vào ô dưới, Ctrl + V</label>
    <textarea class="inp" id="impPaste" rows="3" placeholder="Dán dữ liệu từ Excel vào đây..." style="resize:vertical;font-size:12.5px"></textarea>
    <div class="imp-opt" id="impOpt" style="display:none"><b>Phòng trùng mã đã có:</b><label><input type="radio" name="impMode" value="update" checked> Cập nhật theo danh sách mới</label><label><input type="radio" name="impMode" value="skip"> Bỏ qua, giữ bản cũ</label></div>
    <div id="impT" style="margin-top:10px"></div></div>
  <div class="modal-f"><span id="impSum" style="margin-right:auto;align-self:center;color:var(--muted);font-size:13px"></span><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" id="impOk" disabled onclick="impApply()">Nhập phòng</button></div>`,'wide');
  $$('[name=impMode]').forEach(r=>r.onchange=()=>{IMP.mode=r.value;drawImp()});
  $('#impFile').onchange=async e=>{const f=e.target.files[0];if(!f)return;e.target.value='';$('#impName').textContent='Đang đọc '+f.name+'...';
    try{IMP.items=impParse(await XLSX.read(f));pgReset('imp');$('#impPaste').value='';$('#impName').innerHTML=`<b>${esc(f.name)}</b> · đọc được ${IMP.items.length} phòng`;if(!IMP.items.length)toast('File không có dòng nào có mã phòng','err')}
    catch(err){IMP.items=[];$('#impName').textContent='Chưa đọc được file';toast(err.message||'Không đọc được file','err')}drawImp()};
  let tm;$('#impPaste').oninput=e=>{clearTimeout(tm);tm=setTimeout(()=>{const t=e.target.value;IMP.items=t.trim()?impParse(t.replace(/\r/g,'').split('\n').map(l=>l.split('\t'))):[];pgReset('imp');
    $('#impName').textContent=t.trim()?'Dữ liệu dán vào · đọc được '+IMP.items.length+' phòng':'Chưa chọn file';drawImp()},250)};
  drawImp()}
function drawImp(){const it=IMP.items,nNew=it.filter(o=>o.st==='new').length,nOld=it.filter(o=>o.st==='old').length,nDup=it.filter(o=>o.st==='dup').length,n=nNew+(IMP.mode==='update'?nOld:0),ok=$('#impOk');if(!ok)return;
  $('#impOpt').style.display=nOld?'':'none';$('#impSum').textContent=it.length?`${nNew} mới · ${nOld} trùng mã đã có${nDup?' · '+nDup+' trùng trong danh sách (bỏ qua)':''}`:'';
  ok.disabled=!n;ok.textContent=n?`Nhập ${n} phòng`:'Nhập phòng';
  const tag=o=>o.st==='dup'?'<span class="tag d">Trùng mã, bỏ qua</span>':o.st==='new'?'<span class="tag ok">Thêm mới</span>':IMP.mode==='update'?'<span class="tag w">Cập nhật</span>':'<span class="tag g">Bỏ qua</span>';
  $('#impT').innerHTML=it.length?`<div class="card" style="box-shadow:none"><div class="tbl-wrap"><table><thead><tr><th class="c">Dòng</th><th>Mã phòng</th><th>Dãy</th><th class="c">Tầng</th><th class="n">Diện tích</th><th class="n">Giá thuê</th><th class="c">Tối đa</th><th>Tiện ích</th><th>Tình trạng</th></tr></thead><tbody>
    ${pgSlice('imp',it,10).map(o=>`<tr><td class="c">${o.row}</td><td><b>${esc(o.code)}</b></td><td>${esc(o.day)}</td><td class="c">${o.has.tang?o.tang:''}</td><td class="n">${o.has.dt?fmt(o.dt):''}</td><td class="n">${o.has.gia?fmt(o.gia):''}</td><td class="c">${o.has.toiDa?o.toiDa:''}</td><td>${esc(o.tienIch)}</td><td>${tag(o)}</td></tr>`).join('')}
    </tbody></table></div>${pgBar('imp',it.length,'drawImp')}</div>`:'<div class="empty" style="padding:20px">Chọn file Excel hoặc dán dữ liệu để xem trước danh sách</div>'}
function impApply(){let add=0,upd=0;
  IMP.items.forEach(o=>{if(o.st==='dup')return;
    if(o.old){if(IMP.mode!=='update')return;IMP_COLS.forEach(c=>{if(c.key!=='code'&&o.has[c.key])o.old[c.key]=o[c.key]});upd++}
    else{S.rooms.push({id:'r'+uid(),code:o.code,day:o.day||'Dãy A',tang:o.tang||1,dt:o.dt,gia:o.gia,toiDa:o.toiDa||2,tienIch:o.tienIch,note:o.note,sua:false});add++}});
  save();closeModal();rerender();toast(`Đã thêm ${add} phòng${upd?', cập nhật '+upd+' phòng':''}. Nhớ bấm Lưu đè dữ liệu.`)}
function impTemplate(){XLSX.save('Mau nhap danh sach phong',[{name:'Phòng',title:'DANH SÁCH PHÒNG',subtitles:['Điền từ dòng dưới tiêu đề. Bắt buộc có Mã phòng. Giá ghi số, không cần chữ "đ".'],noFilter:true,
  columns:IMP_COLS.map(c=>({label:c.label,type:c.num?'int':'text',width:c.w})),
  rows:[['C101','Dãy C',1,20,2600000,3,'Máy lạnh, WC riêng',''],['C102','Dãy C',1,22,2800000,3,'Gác lửng, máy lạnh',''],['C201','Dãy C',2,20,2500000,2,'Ban công','Phòng góc, thoáng']]}])}

/* =========================================================
   CÀI ĐẶT
   ========================================================= */
const CFG_F={ten:'cTen',diaChi:'cDiaChi',sdt:'cSdt',chu:'cChu',nganHang:'cNganHang',stk:'cStk',chuTK:'cChuTK',giaDien:'cGiaDien',nuocKieu:'cNuocKieu',giaNuoc:'cGiaNuoc',giaNuocNguoi:'cGiaNuocNguoi',racKieu:'cRacKieu',rac:'cRac',wifi:'cWifi',xe:'cXe',hanTT:'cHanTT',canhBao:'cCanhBao'};
const CFG_NUM=['giaDien','giaNuoc','giaNuocNguoi','rac','wifi','xe','hanTT','canhBao'];
function renderSettings(){Object.entries(CFG_F).forEach(([k,id])=>$('#'+id).value=S.cfg[k]??'');
  const size=JSON.stringify(S).length;
  $('#storeInfo').innerHTML=`<b>Dung lượng dữ liệu:</b> ${(size/1024).toFixed(0)} KB
  <div class="pbar" style="height:8px;margin:10px 0"><i style="width:${Math.min(100,size/5/1024/1024*100)}%;background:var(--chu-dao)"></i></div>
  <span style="color:var(--muted)">${S.rooms.length} phòng · ${S.contracts.length} hợp đồng · ${fmt(S.readings.length)} lần ghi chỉ số · ${fmt(S.invoices.length)} hóa đơn · ${fmt(payAll().length)} phiếu thu. Dữ liệu nằm ngay trong file <b>${esc(FILE_NAME)}</b> sau mỗi lần bấm Lưu đè dữ liệu. ${DIRTY?'<b style="color:#c26a00">Hiện có thay đổi chưa lưu vào file.</b>':''}</span>`}
function saveCfg(){const o={};Object.entries(CFG_F).forEach(([k,id])=>{const v=$('#'+id).value.trim();o[k]=CFG_NUM.includes(k)?Math.max(0,+v||0):v});
  o.hanTT=Math.min(28,Math.max(1,o.hanTT||5));o.canhBao=o.canhBao||30;S.cfg={...S.cfg,...o};save();toast('Đã lưu đơn giá và thông tin nhà trọ');renderSettings()}
function backup(){const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(S)],{type:'application/json'}));a.download=`Sao_luu_phong_tro_${TODAYS}.json`;document.body.appendChild(a);a.click();setTimeout(()=>a.remove(),1000);toast('Đã tải file sao lưu')}
$('#restoreFile').onchange=e=>{const f=e.target.files[0];if(!f)return;const fr=new FileReader();fr.onload=()=>{try{const d=JSON.parse(fr.result);if(!valid(d))throw 0;
  if(!confirm(`Khôi phục ${d.rooms.length} phòng, ${d.contracts.length} hợp đồng, ${d.invoices.length} hóa đơn? Dữ liệu hiện tại sẽ bị thay thế.`))return;S=norm(d);save();toast('Khôi phục xong, bấm Lưu đè dữ liệu để ghi vào file');rerender()}catch(_){toast('File sao lưu không hợp lệ','err')}};fr.readAsText(f);e.target.value=''};
function resetSample(){if(!confirm('Thay toàn bộ dữ liệu hiện tại bằng dữ liệu mẫu?'))return;S=seed();save();toast('Đã nạp dữ liệu mẫu');rerender()}
function clearAll(keepRooms){if(!confirm(keepRooms?'Xóa toàn bộ người thuê, chỉ số, hóa đơn, phiếu thu (giữ danh sách phòng)? Nên tải file sao lưu trước.':'Xóa SẠCH toàn bộ dữ liệu, kể cả danh sách phòng? Nên tải file sao lưu trước.'))return;
  S={cfg:S.cfg,rooms:keepRooms?S.rooms.map(r=>({...r,sua:false,note:''})):[],contracts:[],readings:[],invoices:[],payments:[],ver:1};save();toast('Đã xóa dữ liệu, bắt đầu nhập dữ liệu thật');rerender()}

/* =========================================================
   CHUNG
   ========================================================= */
function openModal(html,size){const m=$('#modal');m.className='modal'+(size?' '+size:'');m.innerHTML=html;$('#ov').classList.add('show')}
function closeModal(){$('#ov').classList.remove('show')}
$('#ov').addEventListener('mousedown',e=>{if(e.target.id==='ov')closeModal()});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
function toast(msg,type){const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;$('#toasts').appendChild(t);setTimeout(()=>{t.style.opacity=0;t.style.transition='.3s'},3800);setTimeout(()=>t.remove(),4200)}
S=load();
paintIcons();
$('#today').textContent=TODAY.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'});
refreshChrome();refreshSave();
go((location.hash||'#dashboard').slice(1));
if(ASK_DRAFT){const d=ASK_DRAFT;setTimeout(()=>{
  openModal(`<div class="modal-h"><h3>Có bản tạm chưa lưu</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><p>Lần trước có thay đổi lúc <b>${hm(d.updated)}</b> nhưng chưa bấm Lưu đè dữ liệu vào file. Anh chị có muốn khôi phục bản tạm này không?</p><p class="mut" style="margin-top:8px">${d.rooms.length} phòng · ${d.contracts.length} hợp đồng · ${d.invoices.length} hóa đơn</p></div>
  <div class="modal-f"><button class="btn" id="drNo">Bỏ bản tạm, dùng dữ liệu trong file</button><button class="btn pri" id="drYes">Khôi phục bản tạm</button></div>`,'small');
  $('#drYes').onclick=()=>{S=norm(d);PM=null;DIRTY=true;closeModal();refreshChrome();refreshSave();rerender();toast('Đã khôi phục bản tạm. Bấm Lưu đè dữ liệu để ghi vào file.')};
  $('#drNo').onclick=()=>{try{localStorage.removeItem(LS)}catch(e){}closeModal();toast('Đã bỏ bản tạm')}},500)}
