
/* Chụp mã gốc của trang TRƯỚC khi vẽ giao diện, để khi Lưu đè chỉ thay khối dữ liệu */
const TEMPLATE=document.documentElement.outerHTML;
const MAU={"chu":"#c62828","dam":"#9a1f1f","sang":"#da5b5b","nhat":"#fceeee","nhat2":"#eec8c8","sb":"#391313","sb0":"#260d0d","sb2":"#4b2020","sbtext":"#e4c8c8","sbmuted":"#be9393","heroT":"#f9dcdc","heroT2":"#eab8b8"};
const SALES_THEME_STORAGE_KEY='sales_employees_theme_v1';
const SALES_THEMES={
  ruby:{label:'Đỏ Ruby',main:'#c62828',dark:'#391313',colors:{chu:'#c62828',dam:'#9a1f1f',sang:'#da5b5b',nhat:'#fceeee',nhat2:'#eec8c8',sb:'#391313',sb0:'#260d0d',sb2:'#4b2020',sbtext:'#e4c8c8',sbmuted:'#be9393',heroT:'#f9dcdc',heroT2:'#eab8b8'}},
  ocean:{label:'Xanh Ocean',main:'#087e9b',dark:'#123b52',colors:{chu:'#087e9b',dam:'#07637c',sang:'#289eb6',nhat:'#e6f5f9',nhat2:'#c2e8f0',sb:'#123b52',sb0:'#0b2839',sb2:'#1c5067',sbtext:'#d7edf5',sbmuted:'#a6cbd9',heroT:'#e3f6fc',heroT2:'#bde5f0'}},
  emerald:{label:'Xanh Emerald',main:'#16835d',dark:'#164537',colors:{chu:'#16835d',dam:'#116a4b',sang:'#2ca57b',nhat:'#e6f6ef',nhat2:'#c4e9d8',sb:'#164537',sb0:'#0d2e25',sb2:'#225846',sbtext:'#d8f2e6',sbmuted:'#acd4c1',heroT:'#e5f9ef',heroT2:'#c0e9d6'}},
  indigo:{label:'Tím Indigo',main:'#6546c5',dark:'#29204f',colors:{chu:'#6546c5',dam:'#5034a6',sang:'#8166d6',nhat:'#f0ecff',nhat2:'#dbd2fa',sb:'#29204f',sb0:'#1c1738',sb2:'#392d65',sbtext:'#e8e0fc',sbmuted:'#c0b3e3',heroT:'#f0eaff',heroT2:'#d8c9fb'}}
};
let SALES_ACTIVE_THEME='ruby';
function applySalesTheme(id,savePreference=false){
  if(!Object.prototype.hasOwnProperty.call(SALES_THEMES,id))id='ruby';
  SALES_ACTIVE_THEME=id;
  const palette=SALES_THEMES[id];
  document.documentElement.dataset.salesTheme=id;
  Object.assign(MAU,palette.colors);
  if(savePreference){try{localStorage.setItem(SALES_THEME_STORAGE_KEY,id)}catch(e){toast('Không thể ghi nhớ theme trong trình duyệt','err')}}
  document.querySelectorAll('[data-sales-theme-choice]').forEach(button=>{
    const active=button.dataset.salesThemeChoice===id;
    button.setAttribute('aria-pressed',String(active));
  });
  if(savePreference&&typeof CUR!=='undefined'&&typeof APP!=='undefined'&&APP.pages[CUR])rerender();
}
function salesThemeChoices(){
  return Object.entries(SALES_THEMES).map(([id,theme])=>
    `<button type="button" class="sales-theme-choice" data-sales-theme-choice="${id}" aria-pressed="${id===SALES_ACTIVE_THEME}" onclick="applySalesTheme('${id}',true)" style="--swatch-main:${theme.main};--swatch-dark:${theme.dark}"><span class="sales-theme-swatch" aria-hidden="true"></span><span><strong>${theme.label}</strong><small>${id==='ruby'?'Mặc định':'Đổi màu giao diện'}</small></span></button>`
  ).join('');
}
/* =========================================================
   TỔNG HỢP DOANH SỐ BÁN HÀNG THEO NHÂN VIÊN
   - nv: nhân viên + chỉ tiêu tháng · sp: sản phẩm · ban: nhật ký bán hàng
   ========================================================= */
const amt=b=>(+b.qty||0)*(+b.price||0)*(1-(+b.ck||0)/100);
const nvAll=()=>[...rowsOf('nv')].sort((a,b)=>a.code.localeCompare(b.code));
const nvName=id=>{const n=byId('nv',id);return n?n.name:'(đã xóa)'};
const shortName=s=>String(s).trim().split(/\s+/).slice(-2).join(' ');
const groups=()=>[...new Set(rowsOf('nv').map(n=>n.group).filter(Boolean))].sort((a,b)=>a.localeCompare(b,'vi'));
const spGroups=()=>[...new Set(rowsOf('sp').map(p=>p.group).filter(Boolean))].sort((a,b)=>a.localeCompare(b,'vi'));
const GC=()=>[MAU.chu,'#f59e0b','#0ea5b7','#7c3aed','#16a36a','#64748b'];
const hexRgb=h=>[1,3,5].map(i=>parseInt(h.slice(i,i+2),16)).join(',');
const firstSale=()=>rowsOf('ban').reduce((m,b)=>b.date<m?b.date:m,TODAYS);
/* chỉ tiêu cho 1 khoảng ngày = chỉ tiêu tháng x phần ngày của từng tháng nằm trong khoảng */
function tgFor(n,f,t){if(!n||!(+n.target))return 0;if(f<firstSale())f=firstSale();if(n.start&&f<n.start)f=n.start;if(t<f)return 0;let s=0,d=parseD(f.slice(0,7)+'-01');
  while(ymd(d)<=t){const y=d.getFullYear(),m=d.getMonth(),dim=new Date(y,m+1,0).getDate(),ms=ymd(d),me=ymd(new Date(y,m,dim)),a=f>ms?f:ms,b=t<me?t:me;
    if(a<=b)s+=+n.target*(daysBetween(a,b)+1)/dim;d=new Date(y,m+1,1)}return s}
/* phần thời gian đã trôi qua của kỳ (để so tiến độ) */
const elapsed=(f,t)=>t<=TODAYS?1:Math.max(0,Math.min(1,(daysBetween(f,TODAYS)+1)/(daysBetween(f,t)+1)));
function stat(rows){return {ds:sum(rows,amt),don:rows.length,kh:new Set(rows.map(b=>fold(b.kh))).size,sl:sum(rows,b=>b.qty)}}
function grade(p){return p>=110?['Xuất sắc','g1']:p>=100?['Đạt','g2']:p>=80?['Gần đạt','g3']:['Chưa đạt','g4']}
function prevRange(f,t){const te=t>TODAYS?TODAYS:t,len=daysBetween(f,te)+1;let pf;
  if(f.slice(8)==='01'){const a=parseD(f),b=parseD(t),m=(b.getFullYear()-a.getFullYear())*12+b.getMonth()-a.getMonth()+1;pf=ymd(new Date(a.getFullYear(),a.getMonth()-m,1))}else pf=addDays(f,-len);
  let pt=addDays(pf,len-1);if(pt>=f)pt=addDays(f,-1);return [pf,pt]}
function nvStats(f,t,grp){const [pf,pt]=prevRange(f,t),el=elapsed(f,t);
  return nvAll().filter(n=>(!grp||n.group===grp)&&(n.active!=='Đã nghỉ'||rowsOf('ban').some(b=>b.nv===n.id&&b.date>=f&&b.date<=t))).map(n=>{
    const rs=rowsOf('ban').filter(b=>b.nv===n.id&&b.date>=f&&b.date<=t),st=stat(rs),tg=tgFor(n,f,t),prev=sum(rowsOf('ban').filter(b=>b.nv===n.id&&b.date>=pf&&b.date<=pt),amt);
    return {n,...st,tg,pct:tg?st.ds/tg*100:null,pace:tg&&el?st.ds/(tg*el)*100:null,miss:Math.max(0,tg-st.ds),avg:st.don?st.ds/st.don:0,prev}}).sort((a,b)=>b.ds-a.ds)}

const APP={
  title:'Tổng hợp doanh số theo nhân viên',file:'Tổng hợp doanh số theo nhân viên.html',key:'dsnv_phongexcel_v1',
  valid:d=>d&&Array.isArray(d.nv)&&Array.isArray(d.sp)&&Array.isArray(d.ban),
  seed:()=>seed(),
  empty:()=>({nv:S.nv||[],sp:S.sp||[],ban:[],updated:0}),
  clearNote:'Xóa toàn bộ nhật ký bán hàng mẫu. Danh sách nhân viên, chỉ tiêu và sản phẩm được giữ lại để sửa thành dữ liệu thật.',
  coExtra:'Chi nhánh / Cửa hàng',
  pages:{
    dashboard:['Dashboard doanh số','Doanh số, chỉ tiêu, xếp hạng nhân viên và sản phẩm bán chạy',()=>renderDashboard()],
    ban:['Nhật ký bán hàng','Mỗi dòng là một đơn bán: ngày, nhân viên, khách, sản phẩm, số lượng, đơn giá',()=>crud('ban',{dateKey:'date',per:'month',filters:['nv','sp'],ph:'Tìm số HĐ, khách hàng, nhân viên, sản phẩm...',addLbl:'Thêm đơn bán',
      top:rows=>{const s=stat(rows);$('#banTop').innerHTML=`<div class="card" style="padding:16px 18px;margin-bottom:16px"><div class="msum" style="margin:0"><div><small>Số đơn</small><b>${fmt(s.don)}</b></div><div><small>Số lượng bán</small><b>${fmt(s.sl)}</b></div><div><small>Doanh số</small><b style="color:var(--chu-dao)">${money(s.ds)}</b></div><div><small>Giá trị TB / đơn</small><b>${money(s.don?s.ds/s.don:0)}</b></div><div><small>Số khách</small><b>${fmt(s.kh)}</b></div><div><small>Nhân viên bán</small><b>${fmt(new Set(rows.map(b=>b.nv)).size)}</b></div></div></div>`}})],
    tonghop:['Tổng hợp theo nhân viên','Doanh số, % hoàn thành chỉ tiêu, xếp hạng theo kỳ',()=>renderTH()],
    thang:['Doanh số theo tháng','Bảng nhân viên x 12 tháng, so với chỉ tiêu',()=>renderThang()],
    sanpham:['Doanh số theo sản phẩm','Sản phẩm nào bán chạy, ai bán được gì',()=>renderSp()],
    nv:['Nhân viên, chỉ tiêu','Danh sách nhân viên bán hàng và chỉ tiêu doanh số mỗi tháng',()=>crud('nv',{filters:['group','active'],ph:'Tìm mã, tên, nhóm...',addLbl:'Thêm nhân viên'})],
    sp:['Sản phẩm','Danh mục sản phẩm và đơn giá bán',()=>crud('sp',{filters:['group'],ph:'Tìm mã, tên sản phẩm...',addLbl:'Thêm sản phẩm'})],
    guide:['Hướng dẫn','Cách dùng file tổng hợp doanh số',()=>renderGuide()],
    settings:['Cài đặt & dữ liệu','Lưu đè, sao lưu, khôi phục và làm mới dữ liệu',()=>renderSettings()],
  },
  tables:{
    nv:{name:'Nhân viên',one:'nhân viên',code:'code',prefix:'NV',imp:true,cols:[
      {k:'code',l:'Mã NV',req:1,bold:1,w:9},
      {k:'name',l:'Họ và tên',req:1,w:24,html:n=>`<div class="pcell">${av(n.name)}<div><b>${esc(n.name)}</b><small>${esc(n.role||'')}</small></div></div>`},
      {k:'group',l:'Nhóm / chi nhánh',w:18,fl:'Mọi nhóm',list:()=>groups(),req:1},
      {k:'role',l:'Chức vụ',w:16,hide:1},
      {k:'target',l:'Chỉ tiêu tháng',type:'money',req:1,sum:1,w:16},
      {k:'start',l:'Ngày bắt đầu bán',type:'date',def:'',hide:1,help:'để trống nếu đã làm lâu'},
      {k:'active',l:'Trạng thái',type:'select',opts:['Đang làm','Đã nghỉ'],def:'Đang làm',tags:{'Đang làm':'ok','Đã nghỉ':'mut'},w:11},
      {k:'dsm',l:'Doanh số tháng này',type:'calc',fmt:'money',noForm:1,sum:1,calc:n=>{const r=rangeOf('month');return sum(rowsOf('ban').filter(b=>b.nv===n.id&&inR(b.date,r)),amt)}},
      {k:'pct',l:'% chỉ tiêu tháng',type:'calc',fmt:'pct',noForm:1,calc:n=>{const r=rangeOf('month'),t=tgFor(n,r.from,r.to);return t?sum(rowsOf('ban').filter(b=>b.nv===n.id&&inR(b.date,r)),amt)/t*100:null},
        html:n=>{const r=rangeOf('month'),t=tgFor(n,r.from,r.to),p=t?sum(rowsOf('ban').filter(b=>b.nv===n.id&&inR(b.date,r)),amt)/t*100:0;return pbarHtml(p)}},
    ]},
    sp:{name:'Sản phẩm',one:'sản phẩm',code:'code',prefix:'SP',imp:true,cols:[
      {k:'code',l:'Mã SP',req:1,bold:1,w:9},
      {k:'name',l:'Tên sản phẩm',req:1,w:32},
      {k:'group',l:'Nhóm hàng',w:18,fl:'Mọi nhóm hàng',list:()=>spGroups()},
      {k:'unit',l:'ĐVT',def:'Cái',center:1,w:8},
      {k:'price',l:'Đơn giá bán',type:'money',req:1},
      {k:'sl',l:'SL bán tháng này',type:'calc',fmt:'num',noForm:1,sum:1,calc:p=>{const r=rangeOf('month');return sum(rowsOf('ban').filter(b=>b.sp===p.id&&inR(b.date,r)),b=>b.qty)}},
      {k:'ds',l:'Doanh số tháng này',type:'calc',fmt:'money',noForm:1,sum:1,calc:p=>{const r=rangeOf('month');return sum(rowsOf('ban').filter(b=>b.sp===p.id&&inR(b.date,r)),amt)}},
    ]},
    ban:{name:'Nhật ký bán hàng',one:'đơn bán',sheet:'Nhật ký bán hàng',code:'code',prefix:'HD',digits:5,imp:true,sort:(a,b)=>b.date.localeCompare(a.date)||b.code.localeCompare(a.code),keep:['date','nv'],cols:[
      {k:'code',l:'Số HĐ',bold:1,w:10},
      {k:'date',l:'Ngày bán',type:'date',req:1},
      {k:'nv',l:'Nhân viên',type:'ref',ref:'nv',req:1,fl:'Mọi nhân viên',w:22,html:b=>{const n=byId('nv',b.nv);return n?`<div class="pcell" style="min-width:150px">${av(n.name)}<div><b>${esc(n.name)}</b><small>${esc(n.group||'')}</small></div></div>`:''}},
      {k:'kh',l:'Khách hàng',w:24,list:()=>rowsOf('ban').map(b=>b.kh).filter(Boolean)},
      {k:'sp',l:'Sản phẩm',type:'ref',ref:'sp',req:1,fl:'Mọi sản phẩm',w:28},
      {k:'qty',l:'Số lượng',type:'num',def:1,min:0,req:1,sum:1},
      {k:'price',l:'Đơn giá',type:'money'},
      {k:'ck',l:'Chiết khấu %',type:'dec',def:0,min:0,maxv:100,w:10},
      {k:'total',l:'Thành tiền',type:'calc',fmt:'money',sum:1,calc:amt,html:b=>`<b>${fmt(amt(b))}</b>`},
      {k:'note',l:'Ghi chú',hide:1,w:24},
    ],onInput:(f)=>{if(f==='sp'){const p=byId('sp',$('#fm_sp').value);if(p)$('#fm_price').value=p.price||''}}},
  },
  chrome(){
    const r=rangeOf('month'),d=nvStats(r.from,r.to),tot=sum(d,x=>x.ds),tg=sum(d,x=>x.tg),low=d.filter(x=>x.pace!==null&&x.pace<80).length;
    $('#bdLow').textContent=low||'';
    $('#sbCard').innerHTML=`<small>Doanh số tháng này</small><b>${short(tot)} ₫</b><div class="bar"><i style="width:${tg?Math.min(100,tot/tg*100):0}%"></i></div><p>${pctS(tg?tot/tg*100:null,0)} chỉ tiêu ${short(tg)} · tháng đã qua ${pctS(elapsed(r.from,r.to)*100,0)}</p>`},
  reports:()=>{const r=rangeOf('month');return [thSheet(r.from,r.to,'','Tháng '+(+r.from.slice(5,7))+'/'+r.from.slice(0,4)),thangSheet(TODAYS.slice(0,4),'ds',''),spSheet(r,'')]},
  guide:{
    what:'Ghi nhật ký bán hàng theo từng đơn (ngày, nhân viên, khách, sản phẩm, số lượng, đơn giá, chiết khấu), file tự tổng hợp doanh số theo nhân viên theo tháng, quý, năm hoặc khoảng ngày bất kỳ, so với chỉ tiêu, tính % hoàn thành, xếp hạng, so với kỳ trước, và cho biết sản phẩm nào bán chạy.',
    steps:[['Khai báo nhân viên và chỉ tiêu','Vào Nhân viên, chỉ tiêu: mã, tên, nhóm hoặc chi nhánh, chỉ tiêu doanh số mỗi tháng.'],
      ['Khai báo sản phẩm','Vào Sản phẩm: mã, tên, nhóm hàng, đơn giá bán. Có sẵn trong Excel thì bấm Nhập từ Excel.'],
      ['Ghi đơn bán hàng','Bấm Thêm đơn bán: chọn ngày, nhân viên, khách, sản phẩm, số lượng. Đơn giá tự lấy theo sản phẩm, sửa được. Có thể Nhập từ Excel nếu đã có danh sách đơn.'],
      ['Xem tổng hợp','Dashboard xem nhanh; Tổng hợp theo nhân viên cho bảng xếp hạng, % chỉ tiêu, còn thiếu bao nhiêu; Doanh số theo tháng cho bảng 12 tháng.'],
      ['Xuất Excel, in báo cáo','Mỗi trang báo cáo có nút Xuất Excel và In báo cáo để gửi sếp hoặc dán vào báo cáo tháng.'],
      ['Lưu dữ liệu','Dữ liệu tự lưu trên trình duyệt; Ctrl + S để xác nhận.']],
    more:`<div class="note-box blue"><b>Cách file tính:</b><br>Thành tiền = Số lượng x Đơn giá x (1 - Chiết khấu %).<br>Chỉ tiêu của kỳ = chỉ tiêu tháng x số tháng trong kỳ (kỳ lẻ ngày thì tính theo số ngày).<br>% hoàn thành = Doanh số / Chỉ tiêu. Tiến độ = so với phần chỉ tiêu lẽ ra phải đạt tới hôm nay.<br>Xếp loại: Xuất sắc từ 110%, Đạt từ 100%, Gần đạt từ 80%, dưới 80% là Chưa đạt.</div>`,
    xls:'bảng tổng hợp theo nhân viên tháng này, bảng 12 tháng và doanh số theo sản phẩm'},
};
const pbarHtml=p=>`<div class="rbar"><div class="pbar"><i style="width:${Math.max(2,Math.min(100,p||0))}%;background:${p>=100?MAU.chu:p>=80?'#e6a100':'#f4a3a3'}"></i></div><b>${pctS(p,0)}</b></div>`;

/* =========================================================
   DỮ LIỆU MẪU: 10 nhân viên, 12 tháng bán hàng
   ========================================================= */
function seed(){
  let s=53;const rnd=()=>{s=(s*16807)%2147483647;return (s-1)/2147483646},ri=(a,b)=>a+Math.floor(rnd()*(b-a+1)),pick=a=>a[Math.floor(rnd()*a.length)];
  const wpick=(a,w)=>{let t=rnd()*sum(w);for(let i=0;i<a.length;i++){t-=w[i];if(t<=0)return a[i]}return a[a.length-1]};
  const N=[['NV001','Nguyễn Minh Tuấn','Chi nhánh Quận 1','Trưởng nhóm',95e6,1.12],['NV002','Trần Thị Thảo','Chi nhánh Quận 1','Nhân viên bán hàng',70e6,0.98],['NV003','Lê Quốc Bảo','Chi nhánh Quận 1','Nhân viên bán hàng',70e6,0.82],
    ['NV004','Phạm Thùy Linh','Chi nhánh Thủ Đức','Trưởng nhóm',85e6,1.05],['NV005','Võ Thanh Sơn','Chi nhánh Thủ Đức','Nhân viên bán hàng',65e6,0.78],['NV006','Đặng Mỹ Duyên','Chi nhánh Thủ Đức','Nhân viên bán hàng',65e6,0.93],
    ['NV007','Hoàng Gia Huy','Kênh online','Trưởng nhóm',90e6,1.18],['NV008','Ngô Bảo Ngọc','Kênh online','Nhân viên online',75e6,1.0],['NV009','Bùi Đức Mạnh','Kênh online','Nhân viên online',60e6,0.7],['NV010','Trịnh Thu Trang','Chi nhánh Quận 1','Nhân viên mới',55e6,0.85]];
  const nv=N.map((a,i)=>({id:'n'+a[0],code:a[0],name:a[1],group:a[2],role:a[3],target:a[4],start:i===9?addDays(TODAYS,-100):'',active:'Đang làm',_f:a[5]}));
  const P=[['SP001','Nồi chiên không dầu 5L','Nhà bếp',1890000,9],['SP002','Máy lọc nước RO 10 lõi','Lọc nước & không khí',6500000,3],['SP003','Bếp từ đôi','Nhà bếp',4200000,4],['SP004','Quạt điều hòa 40L','Điện lạnh & quạt',3150000,4],['SP005','Máy hút bụi cầm tay','Làm sạch',2450000,5],
    ['SP006','Nồi cơm điện 1,8L','Nhà bếp',1290000,8],['SP007','Máy xay sinh tố','Nhà bếp',890000,8],['SP008','Ấm siêu tốc 1,7L','Nhà bếp',390000,9],['SP009','Lò vi sóng 25L','Nhà bếp',2190000,4],['SP010','Máy lọc không khí','Lọc nước & không khí',3890000,3]];
  const sp=P.map(a=>({id:'p'+a[0],code:a[0],name:a[1],group:a[2],unit:'Cái',price:a[3],_w:a[4]}));
  const KO=['Anh Phúc - Quận 7','Chị Hằng - Bình Thạnh','Cửa hàng điện máy Hòa Phát','Công ty TNHH An Bình','Chị Mai - Thủ Đức','Anh Khoa - Gò Vấp','Siêu thị mini Phương Nam','Chị Ngọc - Quận 3','Anh Dũng - Tân Bình','Văn phòng Công ty Sao Việt','Chị Thu - Dĩ An','Khách lẻ','Khách lẻ'],KN=['Khách Shopee','Khách Lazada','Khách TikTok Shop','Khách Facebook','Khách Zalo'];
  const SEA=[1.35,0.75,1,1.1,1.15,1.15,0.95,0.92,1,1.05,1.1,1.25];
  const ban=[];
  for(let d=364;d>=0;d--){const date=addDays(TODAYS,-d),dt=parseD(date);if(dt.getDay()===0&&rnd()<0.6)continue;
    const f0=SEA[dt.getMonth()]*(0.88+0.24*(364-d)/364);
    nv.forEach(n=>{if(n.start&&date<n.start)return;const f=f0*n._f*(n.code==='NV010'?0.6+0.4*Math.min(1,daysBetween(n.start,date)/60):1)*0.95;
      const cnt=(rnd()<0.42*f?1:0)+(rnd()<0.4*f?1:0)+(rnd()<0.13*f?1:0);
      for(let j=0;j<cnt;j++){const p=wpick(sp,sp.map(x=>x._w*(x.group==='Điện lạnh & quạt'&&[3,4,5].includes(dt.getMonth())?2.5:1))),big=rnd()<0.05;
        ban.push({id:'b'+ban.length,date,nv:n.id,kh:n.group==='Kênh online'?pick(KN):big?pick(KO.slice(2,7)):pick(KO),sp:p.id,qty:big?ri(4,10):rnd()<0.85?1:2,price:p.price,ck:big?pick([3,5,5,8]):rnd()<0.15?pick([2,3,5]):0,note:big?'Đơn sỉ':''})}})}
  ban.forEach((b,i)=>b.code='HD'+String(i+1).padStart(5,'0'));
  nv.forEach(n=>delete n._f);sp.forEach(p=>delete p._w);
  return {nv,sp,ban,updated:0}}

/* =========================================================
   DASHBOARD
   ========================================================= */
let DP='month',DG='';
function nvChart(items){
  if(!items.length)return '<div class="empty">Chưa có dữ liệu</div>';
  const W=760,H=310,pl=56,pr=10,pt=24,pb=44,ih=H-pt-pb,n=items.length,max=niceMax(Math.max(1,...items.map(x=>Math.max(x.v,x.t)))),cw=(W-pl-pr)/n,bw=Math.min(40,cw-18),Y=v=>pt+ih*(1-v/max);let g='';
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${short(max*i/4)}</text>`}
  items.forEach((x,i)=>{const cx=pl+cw*i+cw/2,hh=Math.max(2,ih*x.v/max),ok=x.v>=x.t&&x.t>0,p=x.t?x.v/x.t*100:0;
    g+=`<rect class="bar" style="animation-delay:${i*50}ms" x="${cx-bw/2}" y="${pt+ih-hh}" width="${bw}" height="${hh}" rx="6" fill="${ok?MAU.chu:'#f4a3a3'}" data-tip="${esc(x.full+': '+money(x.v)+' / chỉ tiêu '+money(x.t))}"/>`;
    if(x.t)g+=`<line x1="${cx-bw/2-7}" x2="${cx+bw/2+7}" y1="${Y(x.t)}" y2="${Y(x.t)}" stroke="#1b2333" stroke-width="3" stroke-linecap="round" data-tip="${esc('Chỉ tiêu '+x.full+': '+money(x.t))}"/>`;
    g+=`<text x="${cx}" y="${Math.min(Y(x.v),x.t?Y(x.t):H)-8}" text-anchor="middle" style="font-size:11px;font-weight:800;fill:${ok?MAU.dam:'#d33a4c'}">${x.t?fmt(p)+'%':''}</text>`;
    g+=`<text x="${cx}" y="${H-pb+18}" text-anchor="middle" class="ax">${esc(x.label)}</text><text x="${cx}" y="${H-pb+32}" text-anchor="middle" class="ax" style="font-size:10.5px">${short(x.v)}</text>`});
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function renderDashboard(){
  segBind('#dPer',v=>{DP=v;renderDashboard()});
  const sel=$('#dGrp');sel.innerHTML='<option value="">Tất cả nhóm / chi nhánh</option>'+groups().map(g=>`<option ${g===DG?'selected':''}>${esc(g)}</option>`).join('');sel.onchange=()=>{DG=sel.value;renderDashboard()};
  const r=DP==='all'?{from:firstSale(),to:TODAYS,label:'Toàn bộ thời gian'}:rangeOf(DP),f=r.from,t=r.to,el=elapsed(f,t),inG=b=>{if(!DG)return true;const n=byId('nv',b.nv);return n&&n.group===DG};
  $('#dRange').textContent=r.label+' ('+vnDate(f).slice(0,5)+' - '+vnDate(t>TODAYS?TODAYS:t).slice(0,5)+')';
  const rows=rowsOf('ban').filter(b=>b.date>=f&&b.date<=t&&inG(b)),A=stat(rows),d=nvStats(f,t,DG),tg=sum(d,x=>x.tg),pct=tg?A.ds/tg*100:0;
  const pRows=r.pfrom?rowsOf('ban').filter(b=>inP(b.date,r)&&inG(b)):null,B=pRows?stat(pRows):null;
  const mk=monthKeys(12),byM=groupBy(rowsOf('ban').filter(b=>b.date.slice(0,7)>=mk[0]&&inG(b)),b=>b.date.slice(0,7)),mDs=mk.map(k=>sum(byM[k]||[],amt));
  const ok=d.filter(x=>x.pace!==null&&x.pace>=100).length;
  $('#dHero').innerHTML=`<div>
    <div class="h-k">DOANH SỐ · ${esc(r.label.toUpperCase())}${DG?' · '+esc(DG.toUpperCase()):''}</div>
    <div class="h-v"><span data-count="${Math.round(A.ds)}">0</span> <small>₫</small></div>
    <div class="h-sub">${B?pill(A.ds,B.ds,r.cmp):'<span class="pill na">Toàn bộ</span>'}<span>${B?esc(r.cmp)+' ('+short(B.ds)+' ₫)':''}</span></div>
    <div class="h-stats"><div><b data-count="${A.don}">0</b><span>Đơn bán</span></div><div><b data-count="${A.kh}">0</b><span>Khách hàng</span></div><div><b>${short(A.don?A.ds/A.don:0)}</b><span>TB mỗi đơn</span></div></div>
    <div class="h-act"><button class="hbtn" onclick="crudForm('ban')">+ Thêm đơn bán</button><button class="hbtn ghost" onclick="go('tonghop')">Bảng xếp hạng</button></div></div>
   <div class="h-chart"><div class="h-ct"><span>Doanh số 12 tháng gần nhất</span><span>Cao nhất <b>${short(Math.max(...mDs))} ₫</b></span></div>${heroArea(mk.map(mLabel),mDs,money)}</div>
   ${heroRing(pct,'chỉ tiêu',`${short(A.ds)} / ${short(tg)} ₫`,el<1?`thời gian đã qua ${pctS(el*100,0)}`:'kỳ đã kết thúc')}`;
  const mDon=mk.map(k=>(byM[k]||[]).length),mTg=mk.map(k=>sum(nvAll().filter(n=>!DG||n.group===DG),n=>tgFor(n,k+'-01',ymd(new Date(+k.slice(0,4),+k.slice(5),0)))));
  $('#dKpi').innerHTML=
    kc({icon:IC.money,label:'Doanh số',value:Math.round(A.ds),unit:'₫',pill:B?pill(A.ds,B.ds,r.cmp):'',sub:`${fmt(A.sl)} sản phẩm đã bán`,data:mDs})+
    kc({c:'#f59e0b',cb:'var(--warnbg)',icon:IC.doc,label:'Số đơn bán',value:A.don,unit:'đơn',pill:B?pill(A.don,B.don,r.cmp):'',sub:`TB ${short(A.don?A.ds/A.don:0)} ₫ mỗi đơn`,data:mDon})+
    kc({c:'#16a36a',cb:'var(--inbg)',icon:IC.target,label:'Hoàn thành chỉ tiêu',value:pct,dec:1,unit:'%',sub:`chỉ tiêu ${short(tg)} ₫ · tiến độ ${pctS(el*100,0)}`,data:mk.map((k,i)=>mTg[i]?mDs[i]/mTg[i]*100:0)})+
    kc({c:'#7c3aed',cb:'#f1ebff',icon:IC.users,label:'NV đạt tiến độ',value:ok,unit:'/ '+d.length+' người',sub:`${d.filter(x=>x.pace!==null&&x.pace<80).length} người dưới 80% tiến độ`});
  $('#nvHint').textContent=r.label;
  $('#dByNv').innerHTML=nvChart(d.map(x=>({label:shortName(x.n.name),full:x.n.name,v:x.ds,t:x.tg})));
  const byC={};rows.forEach(b=>{const p=byId('sp',b.sp),k=p&&p.group||'Khác';byC[k]=(byC[k]||0)+amt(b)});
  $('#dCat').innerHTML=donut(Object.entries(byC).sort((a,b)=>b[1]-a[1]).map(([l,v],i)=>({label:l,value:v,color:GC()[i%6]})),{center:'Doanh số',tf:money});
  // xu hướng 12 tháng theo nhóm
  const gs=DG?[DG]:groups(),col=g=>GC()[groups().indexOf(g)%6];
  $('#trLeg').innerHTML=gs.map(g=>`<span><i style="background:${col(g)}"></i>${esc(g)}</span>`).join('')+'<span><i style="background:#1b2333;height:3px;vertical-align:3px"></i>Chỉ tiêu</span>';
  $('#trSum').innerHTML=`<div><small>Tổng 12 tháng</small><b style="color:var(--chu-dao)">${short(sum(mDs))} ₫</b></div><div><small>TB mỗi tháng</small><b>${short(sum(mDs)/12)} ₫</b></div><div><small>Tháng cao nhất</small><b>${mLabel(mk[mDs.indexOf(Math.max(...mDs))],1)} · ${short(Math.max(...mDs))} ₫</b></div><div><small>Đạt chỉ tiêu</small><b>${mk.filter((k,i)=>mTg[i]&&mDs[i]>=mTg[i]).length}/12 tháng</b></div>`;
  $('#dTrend').innerHTML=chartCombo({labels:mk.map(mLabel),stacked:true,bw:34,tf:money,bars:gs.map(g=>({name:g,color:col(g),data:mk.map(k=>sum((byM[k]||[]).filter(b=>(byId('nv',b.nv)||{}).group===g),amt))})),line:{name:'Chỉ tiêu',data:mTg,color:'#1b2333',tf:money},hlLast:true,H:300});
  $('#topHint').textContent=r.label;
  $('#dTopNv').innerHTML=d.length?d.slice(0,6).map((x,i)=>`<div class="li">${medal(i)}${av(x.n.name)}<div class="meta"><b>${esc(x.n.name)}</b><small>${esc(x.n.group||'')} · ${x.don} đơn</small><div class="pbar"><i style="width:${Math.min(100,x.pct||0)}%;background:${(x.pct||0)>=100?MAU.chu:'#f4a3a3'}"></i></div></div><div class="v">${short(x.ds)}<small>${pctS(x.pct,0)} chỉ tiêu</small></div></div>`).join(''):'<div class="empty">Chưa có dữ liệu</div>';
  const bySp=Object.entries(groupBy(rows,b=>b.sp)).map(([id,rs])=>({p:byId('sp',id),ds:sum(rs,amt),sl:sum(rs,b=>b.qty)})).filter(x=>x.p).sort((a,b)=>b.ds-a.ds).slice(0,6);
  $('#dTopSp').innerHTML=hbars(bySp.map((x,i)=>({label:x.p.name,sub:fmt(x.sl)+' '+esc(x.p.unit||''),value:x.ds,color:i<3?MAU.chu:MAU.nhat2})),{fmt:short});
  const low=d.filter(x=>x.pace!==null&&x.pace<80).sort((a,b)=>a.pace-b.pace);
  $('#dLowN').textContent=low.length?low.length+' người':'';
  $('#dLow').innerHTML=low.length?low.slice(0,6).map(x=>`<div class="li">${av(x.n.name)}<div class="meta"><b>${esc(x.n.name)}</b><small>${pctS(x.pct,0)} chỉ tiêu · còn thiếu ${short(x.miss)} ₫</small><div class="pbar"><i style="width:${Math.min(100,x.pace)}%;background:#f4a3a3"></i></div></div><div class="v" style="color:var(--danger)">${pctS(x.pace,0)}<small>tiến độ</small></div></div>`).join(''):'<div class="empty">Mọi người đều đạt từ 80% tiến độ</div>';
  countUp($('#page-dashboard'))}

/* =========================================================
   TỔNG HỢP THEO NHÂN VIÊN
   ========================================================= */
function perDates(from,to,v){const r=rangeOf(v);$(from).value=r.from;$(to).value=r.to}
function renderTH(){
  if(!renderTH.init){renderTH.init=1;segBind('#rPer',v=>{perDates('#rFrom','#rTo',v);renderTH()});perDates('#rFrom','#rTo','month');
    ['#rFrom','#rTo'].forEach(s=>$(s).oninput=()=>{$$('#rPer button').forEach(b=>b.classList.remove('on'));renderTH()});$('#rGrp').onchange=renderTH}
  const gv=$('#rGrp').value;$('#rGrp').innerHTML='<option value="">Tất cả nhóm / chi nhánh</option>'+groups().map(g=>`<option ${g===gv?'selected':''}>${esc(g)}</option>`).join('');
  const f=$('#rFrom').value||firstSale(),t=$('#rTo').value||TODAYS,d=nvStats(f,t,gv),T0={ds:sum(d,x=>x.ds),tg:sum(d,x=>x.tg),don:sum(d,x=>x.don),sl:sum(d,x=>x.sl),prev:sum(d,x=>x.prev)},el=elapsed(f,t);
  $('#rPrint').textContent=`Từ ngày ${vnDate(f)} đến ngày ${vnDate(t)}${gv?' · '+gv:''}`;$('#rHint').textContent=d.length+' nhân viên'+(el<1?' · thời gian đã qua '+pctS(el*100,0):'');
  $('#rSum').innerHTML=`<div><small>Doanh số</small><b style="color:var(--chu-dao)">${money(T0.ds)}</b></div><div><small>Chỉ tiêu</small><b>${money(T0.tg)}</b></div><div><small>% hoàn thành</small><b>${pctS(T0.tg?T0.ds/T0.tg*100:null)}</b></div><div><small>Số đơn</small><b>${fmt(T0.don)}</b></div><div><small>Kỳ trước (cùng số ngày)</small><b>${money(T0.prev)}</b></div><div><small>Tăng giảm</small><b style="color:${T0.ds>=T0.prev?'var(--in)':'var(--danger)'}">${T0.prev?(T0.ds>=T0.prev?'▲ ':'▼ ')+pctS(Math.abs(T0.ds-T0.prev)/T0.prev*100):'-'}</b></div>`;
  const RC=['#f5b301','#9aa5b8','#cd7f32'];
  $('#rTable').innerHTML=d.length?`<table><thead><tr><th class="c">Hạng</th><th>Nhân viên</th><th>Nhóm</th><th class="n">Số đơn</th><th class="n">Số khách</th><th class="n">SL bán</th><th class="n">Doanh số</th><th class="n">Chỉ tiêu</th><th>% hoàn thành</th><th class="n">Còn thiếu</th><th class="n">TB / đơn</th><th class="n">Kỳ trước</th><th class="n">Tăng giảm</th><th>Xếp loại</th></tr></thead><tbody>
   ${d.map((x,i)=>{const g=grade(x.pct||0),ch=x.prev?(x.ds-x.prev)/x.prev*100:null;return `<tr><td class="c"><span class="rk" style="background:${RC[i]||'#c3cbda'}">${i+1}</span></td><td><div class="pcell">${av(x.n.name)}<div><b>${esc(x.n.name)}</b><small>${esc(x.n.code)}</small></div></div></td><td class="nw">${esc(x.n.group||'')}</td><td class="n">${fmt(x.don)}</td><td class="n">${fmt(x.kh)}</td><td class="n">${fmt(x.sl)}</td><td class="n"><b>${fmt(x.ds)}</b></td><td class="n">${fmt(x.tg)}</td><td>${pbarHtml(x.pct)}</td><td class="n" style="color:${x.miss?'var(--danger)':'var(--muted)'}">${x.miss?fmt(x.miss):'-'}</td><td class="n">${fmt(x.avg)}</td><td class="n">${fmt(x.prev)}</td><td class="n" style="color:${ch===null?'var(--muted)':ch>=0?'var(--in)':'var(--danger)'}"><b>${ch===null?'-':(ch>=0?'▲ ':'▼ ')+fmt1(Math.abs(ch))+'%'}</b></td><td><span class="grade ${g[1]}">${g[0]}</span></td></tr>`}).join('')}
   </tbody><tfoot><tr><td></td><td>TỔNG CỘNG</td><td></td><td class="n">${fmt(T0.don)}</td><td></td><td class="n">${fmt(T0.sl)}</td><td class="n">${fmt(T0.ds)}</td><td class="n">${fmt(T0.tg)}</td><td>${pctS(T0.tg?T0.ds/T0.tg*100:null)}</td><td class="n">${fmt(sum(d,x=>x.miss))}</td><td></td><td class="n">${fmt(T0.prev)}</td><td></td><td></td></tr></tfoot></table>`:'<div class="empty">Chưa có nhân viên</div>'}
function thSheet(f,t,grp,sub){const d=nvStats(f,t,grp);
  return {name:'Tổng hợp theo NV',title:'TỔNG HỢP DOANH SỐ THEO NHÂN VIÊN',subtitles:[co().name||APP.title,(sub||`Từ ngày ${vnDate(f)} đến ngày ${vnDate(t)}`)+(grp?' · '+grp:'')],totals:true,
    columns:[{label:'Hạng',type:'int',width:6},{label:'Mã NV',width:9},{label:'Họ và tên',width:24},{label:'Nhóm / chi nhánh',width:18},{label:'Số đơn',type:'int',sum:true,width:9},{label:'Số khách',type:'int',width:9},{label:'SL bán',type:'int',sum:true,width:9},{label:'Doanh số',type:'money',sum:true,width:16},{label:'Chỉ tiêu',type:'money',sum:true,width:16},{label:'% hoàn thành',type:'pct',width:11,tot:sum(d,x=>x.tg)?sum(d,x=>x.ds)/sum(d,x=>x.tg)*100:0},{label:'Còn thiếu',type:'money',sum:true},{label:'TB / đơn',type:'money'},{label:'Kỳ trước',type:'money',sum:true},{label:'Tăng giảm',type:'pct'},{label:'Xếp loại',width:11}],
    rows:d.map((x,i)=>[i+1,x.n.code,x.n.name,x.n.group,x.don,x.kh,x.sl,Math.round(x.ds),Math.round(x.tg),x.pct,Math.round(x.miss),Math.round(x.avg),Math.round(x.prev),x.prev?(x.ds-x.prev)/x.prev*100:null,grade(x.pct||0)[0]])}}
function exportTH(){const f=$('#rFrom').value||firstSale(),t=$('#rTo').value||TODAYS,g=$('#rGrp').value,rows=rowsOf('ban').filter(b=>b.date>=f&&b.date<=t&&(!g||(byId('nv',b.nv)||{}).group===g)).sort(T('ban').sort);
  XLSX.save('Tong hop doanh so nhan vien '+TODAYS,[thSheet(f,t,g),tableSheet('ban',rows,'CHI TIẾT ĐƠN BÁN TRONG KỲ')])}

/* =========================================================
   DOANH SỐ THEO THÁNG (bảng 12 tháng)
   ========================================================= */
let TM='ds',TY=null;
function thangData(y,mode,grp){const ms=[];for(let i=1;i<=12;i++)ms.push(y+'-'+pad(i));
  const nvs=nvAll().filter(n=>(!grp||n.group===grp));
  const rows=nvs.map(n=>{const v=ms.map(m=>{const rs=rowsOf('ban').filter(b=>b.nv===n.id&&b.date.slice(0,7)===m),ds=sum(rs,amt),tg=tgFor(n,m+'-01',ymd(new Date(+y,+m.slice(5),0)));
    return mode==='don'?rs.length:mode==='pct'?(tg&&m<=TODAYS.slice(0,7)?ds/tg*100:null):ds});
    const ds=sum(rowsOf('ban').filter(b=>b.nv===n.id&&b.date.slice(0,4)===y),amt),tg=tgFor(n,y+'-01-01',y+'-12-31'>TODAYS?TODAYS:y+'-12-31');
    return {n,v,tot:mode==='pct'?(tg?ds/tg*100:null):sum(v)}}).filter(x=>mode==='pct'?x.v.some(v=>v!==null):x.tot);
  return {ms,rows}}
function renderThang(){
  const ys=[...new Set(rowsOf('ban').map(b=>b.date.slice(0,4)))].sort().reverse();TY=TY&&ys.includes(TY)?TY:(ys[0]||TODAYS.slice(0,4));
  if(!renderThang.init){renderThang.init=1;segBind('#tMode',v=>{TM=v;renderThang()});$('#tYear').onchange=e=>{TY=e.target.value;renderThang()};$('#tGrp').onchange=renderThang}
  $('#tYear').innerHTML=(ys.length?ys:[TY]).map(y=>`<option ${y===TY?'selected':''}>${y}</option>`).join('');
  const gv=$('#tGrp').value;$('#tGrp').innerHTML='<option value="">Tất cả nhóm / chi nhánh</option>'+groups().map(g=>`<option ${g===gv?'selected':''}>${esc(g)}</option>`).join('');
  const D=thangData(TY,TM,gv),fv=v=>v===null||v===undefined?'':TM==='pct'?fmt(v)+'%':TM==='don'?fmt(v):short(v),mx=Math.max(1,...D.rows.flatMap(x=>x.v.filter(v=>v!==null)));
  $('#tTitle').textContent=(TM==='ds'?'Doanh số':TM==='pct'?'% hoàn thành chỉ tiêu':'Số đơn bán')+' theo tháng năm '+TY;$('#tPrint').textContent=$('#tTitle').textContent+(gv?' · '+gv:'');
  const cell=v=>{if(v===null||v===undefined)return '<td class="h" style="color:#c3cbda">-</td>';const a=TM==='pct'?Math.min(1,v/130):v/mx,bad=TM==='pct'&&v<80;
    return `<td class="h" style="background:${bad?`rgba(224,56,74,${(0.08+0.15*(1-v/80)).toFixed(2)})`:`rgba(${hexRgb(MAU.chu)},${(a*0.7).toFixed(2)})`};color:${!bad&&a>0.5?'#fff':'var(--text)'}" data-tip="${esc(TM==='ds'?money(v):TM==='pct'?pctS(v):fmt(v)+' đơn')}">${fv(v)}</td>`};
  const colTot=D.ms.map((m,i)=>TM==='pct'?null:sum(D.rows,x=>x.v[i]||0));
  $('#tTable').innerHTML=D.rows.length?`<table class="heat"><thead><tr><th>Nhân viên</th>${D.ms.map(m=>`<th class="h">T${+m.slice(5)}</th>`).join('')}<th class="h">${TM==='pct'?'Cả năm':'Tổng'}</th></tr></thead><tbody>
    ${D.rows.map(x=>`<tr><td style="white-space:nowrap"><b>${esc(x.n.name)}</b><br><small style="color:var(--muted)">${esc(x.n.group||'')}</small></td>${x.v.map(cell).join('')}<td class="tot">${fv(x.tot)}</td></tr>`).join('')}
    ${TM==='pct'?'':`<tr class="sum"><td>TỔNG CỘNG</td>${colTot.map(v=>`<td class="h">${fv(v)}</td>`).join('')}<td class="tot">${fv(sum(colTot))}</td></tr>`}</tbody></table>`:'<div class="empty">Chưa có dữ liệu năm này</div>'}
function thangSheet(y,mode,grp){const D=thangData(y,mode,grp),tp=mode==='pct'?'pct':mode==='don'?'int':'money';
  return {name:'Theo tháng '+y,title:(mode==='ds'?'DOANH SỐ':mode==='pct'?'% HOÀN THÀNH CHỈ TIÊU':'SỐ ĐƠN BÁN')+' NHÂN VIÊN THEO THÁNG NĂM '+y,subtitles:[co().name||APP.title,grp||'Tất cả nhóm / chi nhánh'],totals:mode!=='pct',
    columns:[{label:'Mã NV',width:9},{label:'Họ và tên',width:22},{label:'Nhóm',width:16},...D.ms.map(m=>({label:'T'+(+m.slice(5)),type:tp,sum:mode!=='pct',width:mode==='ds'?13:8})),{label:mode==='pct'?'Cả năm':'Tổng',type:tp,sum:mode!=='pct',width:mode==='ds'?15:9}],
    rows:D.rows.map(x=>[x.n.code,x.n.name,x.n.group,...x.v.map(v=>v===null?'':mode==='ds'?Math.round(v):v),mode==='ds'?Math.round(x.tot):x.tot])}}
function exportThang(){XLSX.save('Doanh so theo thang '+TY,[thangSheet(TY,TM,$('#tGrp').value)])}

/* =========================================================
   DOANH SỐ THEO SẢN PHẨM
   ========================================================= */
let SP='month';
function spData(r,nv){const rows=rowsOf('ban').filter(b=>b.date>=r.from&&b.date<=r.to&&(!nv||b.nv===nv));
  return {rows,list:rowsOf('sp').map(p=>{const rs=rows.filter(b=>b.sp===p.id);return {p,sl:sum(rs,b=>b.qty),ds:sum(rs,amt),don:rs.length}}).sort((a,b)=>b.ds-a.ds)}}
function renderSp(){
  if(!renderSp.init){renderSp.init=1;segBind('#sPer',v=>{SP=v;renderSp()});$('#sNv').onchange=renderSp}
  const nv=$('#sNv').value;$('#sNv').innerHTML='<option value="">Tất cả nhân viên</option>'+nvAll().map(n=>`<option value="${n.id}" ${n.id===nv?'selected':''}>${esc(n.name)}</option>`).join('');
  const r=SP==='all'?{from:firstSale(),to:TODAYS,label:'Toàn bộ thời gian'}:rangeOf(SP),D=spData(r,nv),tot=sum(D.list,x=>x.ds);$('#sRange').textContent=r.label;
  $('#sChart').innerHTML=hbars(D.list.filter(x=>x.ds).map((x,i)=>({label:x.p.code+' · '+x.p.name,sub:fmt(x.sl)+' '+esc(x.p.unit||'')+' · '+pctS(tot?x.ds/tot*100:0,1),value:x.ds,color:i<3?MAU.chu:MAU.nhat2})),{fmt:money});
  const byC={};D.list.forEach(x=>{const k=x.p.group||'Khác';byC[k]=(byC[k]||0)+x.ds});
  $('#sCat').innerHTML=donut(Object.entries(byC).sort((a,b)=>b[1]-a[1]).map(([l,v],i)=>({label:l,value:v,color:GC()[i%6]})),{center:'Doanh số',tf:money});
  const sps=D.list.filter(x=>x.sl),nvs=nvAll().filter(n=>D.rows.some(b=>b.nv===n.id)),cell={},cds={};D.rows.forEach(b=>{const k=b.nv+'|'+b.sp;cell[k]=(cell[k]||0)+(+b.qty||0);cds[k]=(cds[k]||0)+amt(b)});const mx=Math.max(1,...Object.values(cell));
  $('#sMatrix').innerHTML=sps.length?`<table class="heat"><thead><tr><th>Nhân viên</th>${sps.map(x=>`<th class="h" title="${esc(x.p.name)}">${esc(x.p.code)}</th>`).join('')}<th class="h">Doanh số</th></tr></thead><tbody>${nvs.map(n=>`<tr><td style="white-space:nowrap"><b>${esc(n.name)}</b></td>${sps.map(x=>{const v=cell[n.id+'|'+x.p.id]||0,a=v/mx;return `<td class="h" style="background:rgba(${hexRgb(MAU.chu)},${(a*0.75).toFixed(2)});color:${a>0.5?'#fff':v?'var(--text)':'#c3cbda'}" data-tip="${esc(n.name+' · '+x.p.name+': '+fmt(v)+' cái, '+money(cds[n.id+'|'+x.p.id]||0))}">${v||'-'}</td>`}).join('')}<td class="tot">${short(sum(D.rows.filter(b=>b.nv===n.id),amt))}</td></tr>`).join('')}</tbody></table><div class="hint2" style="padding:0 16px 12px">Cột là mã sản phẩm, rê chuột vào ô để xem tên và doanh số.</div>`:'<div class="empty">Chưa có đơn bán trong kỳ</div>'}
function spSheet(r,nv){const D=spData(r,nv);return {name:'Theo sản phẩm',title:'DOANH SỐ THEO SẢN PHẨM',subtitles:[co().name||APP.title,r.label+(nv?' · '+nvName(nv):'')],totals:true,
  columns:[{label:'STT',type:'int',width:6},{label:'Mã SP',width:9},{label:'Tên sản phẩm',width:30},{label:'Nhóm hàng',width:20},{label:'Số đơn',type:'int',sum:true},{label:'SL bán',type:'int',sum:true},{label:'Doanh số',type:'money',sum:true,width:16},{label:'Tỷ trọng',type:'pct'}],
  rows:D.list.map((x,i)=>{const t=sum(D.list,y=>y.ds);return [i+1,x.p.code,x.p.name,x.p.group,x.don,x.sl,Math.round(x.ds),t?x.ds/t*100:0]})}}
function exportSp(){XLSX.save('Doanh so theo san pham '+TODAYS,[spSheet(SP==='all'?{from:firstSale(),to:TODAYS,label:'Toàn bộ thời gian'}:rangeOf(SP),$('#sNv').value)])}

/* =========================================================
   TIỆN ÍCH
   ========================================================= */
const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN').format(Math.round(+n||0));
const fmt1=(n,d=1)=>new Intl.NumberFormat('vi-VN',{maximumFractionDigits:d}).format(+n||0);
const pctS=(n,d=1)=>(n===null||n===undefined||isNaN(n))?'-':fmt1(n,d)+'%';
const money=n=>fmt(n)+' ₫';
const short=n=>{n=Math.round(+n||0);const a=Math.abs(n);
  if(a>=1e9)return (n/1e9).toFixed(a>=1e10?0:1).replace('.',',')+' tỷ';
  if(a>=1e6)return (n/1e6).toFixed(a>=1e8?0:1).replace('.',',')+' tr';
  if(a>=1e4)return Math.round(n/1e3)+'k';return fmt(n)};
const pad=n=>String(n).padStart(2,'0');
const ymd=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const vnDate=s=>{if(!s)return '';const [y,m,d]=String(s).slice(0,10).split('-');return d+'/'+m+'/'+y};
const parseD=s=>{const [y,m,d]=String(s).split('-').map(Number);return new Date(y,m-1,d)};
const addDays=(s,n)=>{const d=parseD(s);d.setDate(d.getDate()+n);return ymd(d)};
const daysBetween=(a,b)=>Math.round((parseD(b)-parseD(a))/864e5);
const hm=t=>{const d=new Date(t);return pad(d.getHours())+':'+pad(d.getMinutes())+' '+pad(d.getDate())+'/'+pad(d.getMonth()+1)};
const uid=()=>Date.now().toString(36)+Math.random().toString(36).slice(2,7);
const fold=s=>String(s??'').normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/đ/gi,'d').toLowerCase();
const normH=s=>fold(s).replace(/[^a-z0-9]/g,'');
const sum=(a,f)=>a.reduce((s,x)=>s+(+(f?f(x):x)||0),0);
const groupBy=(a,f)=>{const m={};a.forEach(x=>{const k=f(x);(m[k]=m[k]||[]).push(x)});return m};
const WD=['CN','T2','T3','T4','T5','T6','T7'];
const TODAY=new Date(), TODAYS=ymd(TODAY);
const initials=n=>{const p=String(n||'?').trim().split(/\s+/);return (p.length>1?p[p.length-2][0]+p[p.length-1][0]:p[0].slice(0,2)).toUpperCase()};
const avColor=s=>{let h=0;for(const c of String(s))h=(h*31+c.charCodeAt(0))%360;return `hsl(${h},55%,52%)`};
const av=n=>`<span class="av" style="background:${avColor(n)}">${esc(initials(n))}</span>`;
function monthKeys(n){const a=[];for(let i=n-1;i>=0;i--){const d=new Date(TODAY.getFullYear(),TODAY.getMonth()-i,1);a.push(d.getFullYear()+'-'+pad(d.getMonth()+1))}return a}
const mLabel=(k,i)=>{const [y,m]=k.split('-');return 'T'+(+m)+(m==='01'||i===0?'/'+y.slice(2):'')};
function dayKeys(n,end){const a=[];for(let i=n-1;i>=0;i--)a.push(addDays(end||TODAYS,-i));return a}
const weekStart=s=>{const d=parseD(s),w=(d.getDay()+6)%7;d.setDate(d.getDate()-w);return ymd(d)};

/* kỳ báo cáo: tháng này, quý này, năm nay, tất cả (kèm kỳ trước để so sánh) */
function rangeOf(k){
  const y=TODAY.getFullYear(),m=TODAY.getMonth();let f,t,pf,pt,lbl,cmp;
  if(k==='today'){f=t=TODAY;pf=pt=new Date(y,m,TODAY.getDate()-1);lbl='Hôm nay';cmp='so với hôm qua'}
  else if(k==='week'){const ws=parseD(weekStart(TODAYS));f=ws;t=new Date(ws.getFullYear(),ws.getMonth(),ws.getDate()+6);pf=new Date(ws.getFullYear(),ws.getMonth(),ws.getDate()-7);pt=new Date(ws.getFullYear(),ws.getMonth(),ws.getDate()-1);lbl='Tuần này';cmp='so với tuần trước'}
  else if(k==='month'){f=new Date(y,m,1);t=new Date(y,m+1,0);pf=new Date(y,m-1,1);pt=new Date(y,m,0);lbl='Tháng '+(m+1)+'/'+y;cmp='so với tháng trước'}
  else if(k==='prev'){f=new Date(y,m-1,1);t=new Date(y,m,0);pf=new Date(y,m-2,1);pt=new Date(y,m-1,0);lbl='Tháng '+(f.getMonth()+1)+'/'+f.getFullYear();cmp='so với tháng trước đó'}
  else if(k==='quarter'){const q=Math.floor(m/3);f=new Date(y,q*3,1);t=new Date(y,q*3+3,0);pf=new Date(y,q*3-3,1);pt=new Date(y,q*3,0);lbl='Quý '+(q+1)+'/'+y;cmp='so với quý trước'}
  else if(k==='year'){f=new Date(y,0,1);t=new Date(y,11,31);pf=new Date(y-1,0,1);pt=new Date(y-1,11,31);lbl='Năm '+y;cmp='so với năm trước'}
  else return {from:'0000-01-01',to:'9999-12-31',label:'Toàn bộ thời gian'};
  if(t>TODAY&&k!=='today'){const el=Math.round((new Date(TODAY.getFullYear(),TODAY.getMonth(),TODAY.getDate())-f)/864e5),cut=new Date(pf.getFullYear(),pf.getMonth(),pf.getDate()+el);if(cut<pt){pt=cut;cmp+=' (cùng số ngày)'}}
  return {from:ymd(f),to:ymd(t),pfrom:ymd(pf),pto:ymd(pt),label:lbl,cmp}}
const inR=(d,r)=>d>=r.from&&d<=r.to;
const inP=(d,r)=>r.pfrom&&d>=r.pfrom&&d<=r.pto;
/* gắn nút chọn kỳ nhanh vào 1 nhóm nút .seg */
function segBind(sel,cb){$$(sel+' button').forEach(b=>b.onclick=()=>{$$(sel+' button').forEach(x=>x.classList.toggle('on',x===b));cb(b.dataset.v)})}

/* =========================================================
   BIỂU TƯỢNG SVG
   ========================================================= */
const sv=p=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${p}</svg>`;
const IC={
  dash:sv('<rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>'),
  list:sv('<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>'),
  check:sv('<path d="M20 6L9 17l-5-5"/>'),
  checkc:sv('<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>'),
  x:sv('<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>'),
  warn:sv('<path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/>'),
  chart:sv('<path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/>'),
  bars:sv('<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'),
  pie:sv('<path d="M21 12A9 9 0 1112 3v9z"/><path d="M15 3.5A9 9 0 0120.5 9H15z"/>'),
  users:sv('<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.9M16 3.1a4 4 0 010 7.8"/>'),
  user:sv('<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0112 0v1"/>'),
  box:sv('<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'),
  in:sv('<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>'),
  out:sv('<path d="M12 15V3M7 8l5-5 5 5"/><path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>'),
  doc:sv('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>'),
  settings:sv('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>'),
  help:sv('<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 015.8 1c0 2-3 3-3 3M12 17h.01"/>'),
  cal:sv('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
  clock:sv('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>'),
  trend:sv('<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>'),
  down:sv('<path d="M3 7l6 6 4-4 8 8"/><path d="M14 17h7v-7"/>'),
  target:sv('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>'),
  tag:sv('<path d="M20.6 13.4l-7.2 7.2a2 2 0 01-2.8 0L2 12V2h10l8.6 8.6a2 2 0 010 2.8z"/><path d="M7 7h.01"/>'),
  cart:sv('<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 002 1.6h9.7a2 2 0 002-1.6L23 6H6"/>'),
  money:sv('<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>'),
  star:sv('<path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>'),
  factory:sv('<path d="M2 20h20V9l-6 4V9l-6 4V4H2z"/><path d="M6 16h.01M10 16h.01M14 16h.01M18 16h.01"/>'),
  layers:sv('<path d="M12 2l10 5-10 5L2 7z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>'),
  search:sv('<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>'),
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>',
  edit:sv('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>'),
  trash:sv('<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/>'),
  print:sv('<path d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="7"/>'),
  xls:sv('<path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z"/><path d="M14 3v6h6M9 12l6 6M15 12l-6 6"/>'),
  dl:sv('<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>'),
  up:sv('<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>'),
  save:sv('<path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>'),
  reset:sv('<path d="M3 12a9 9 0 109-9 9.7 9.7 0 00-6.7 2.7L3 8"/><path d="M3 3v5h5"/>'),
  heart:sv('<path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 000-7.8z"/>'),
  link:sv('<path d="M10 13a5 5 0 007.5.5l3-3a5 5 0 00-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 00-7.5-.5l-3 3a5 5 0 007 7l1.7-1.7"/>'),
  flag:sv('<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22v-7"/>'),
  tool:sv('<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.8-3.8a6 6 0 01-7.9 7.9l-6.9 6.9a2.1 2.1 0 01-3-3l6.9-6.9a6 6 0 017.9-7.9z"/>'),
  shield:sv('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>'),
  bug:sv('<rect x="8" y="6" width="8" height="14" rx="4"/><path d="M12 6V3M19 7l-3 2M5 7l3 2M19 13h-3M5 13h3M19 19l-3-2M5 19l3-2"/>'),
  medal:sv('<circle cx="12" cy="15" r="6"/><path d="M8.5 10L6 2h4l2 5 2-5h4l-2.5 8"/>'),
};

/* =========================================================
   DỮ LIỆU
   - Nguồn chính: khối <script id="du-lieu"> ngay trong file (nút Lưu đè ghi vào)
   - Bản tạm: bộ nhớ trình duyệt, giữ thay đổi chưa kịp lưu vào file
   ========================================================= */
const FILE_NAME='sales-employees-demo.html';
const LS=APP.key+'|sales-demo|v1|'+location.pathname;
const EMB=(()=>{try{return JSON.parse($('#du-lieu').textContent)}catch(e){return null}})();
let S=null,DIRTY=false,SAVED_AT=0;
function persist(d){try{localStorage.setItem(LS,JSON.stringify(d));return true}catch(e){return false}}
function save(){S.updated=Date.now();DIRTY=true;
  if(!persist(S))toast('Bộ nhớ trình duyệt đã đầy. Hãy xuất JSON sao lưu ngay.','err');
  refreshChrome();refreshSave()}

/* chia trang */
const PG={},PSIZES=[10,20,50,100];let PRINT_ALL=false;
function pgState(k,def){return PG[k]=PG[k]||{p:0,n:def||20}}
function pgReset(k){if(PG[k])PG[k].p=0}
function pgSlice(k,list,def){const st=pgState(k,def);if(PRINT_ALL)return list;const pages=Math.max(1,Math.ceil(list.length/st.n));if(st.p>=pages)st.p=pages-1;if(st.p<0)st.p=0;return list.slice(st.p*st.n,st.p*st.n+st.n)}
function pgOff(k){const st=pgState(k);return PRINT_ALL?0:st.p*st.n}
const PGFN={};
function pgBar(k,total,fn){const st=pgState(k),pages=Math.max(1,Math.ceil(total/st.n)),p=st.p;
  if(PRINT_ALL||total<=10)return '';PGFN[k]=fn;
  const from=total?p*st.n+1:0,to=Math.min(total,(p+1)*st.n);const b=i=>`<button class="${i===p?'on':''}" onclick="pgGo('${k}',${i})">${i+1}</button>`;
  let nums='';if(pages<=7)for(let i=0;i<pages;i++)nums+=b(i);else{nums+=b(0);const a=Math.max(1,p-1),e=Math.min(pages-2,p+1);if(a>1)nums+='<span>…</span>';for(let i=a;i<=e;i++)nums+=b(i);if(e<pages-2)nums+='<span>…</span>';nums+=b(pages-1)}
  return `<div class="pgbar"><span>Hiển thị <b>${fmt(from)}-${fmt(to)}</b> / ${fmt(total)} dòng</span><span class="pgs">Số dòng/trang<select onchange="pgSize('${k}',this.value)">${PSIZES.map(n=>`<option ${n===st.n?'selected':''}>${n}</option>`).join('')}</select>${pages>1?`<button ${p?'':'disabled'} onclick="pgGo('${k}',${p-1})">‹</button>${nums}<button ${p<pages-1?'':'disabled'} onclick="pgGo('${k}',${p+1})">›</button>`:''}</span></div>`}
function pgGo(k,p){pgState(k).p=p;PGFN[k]()}
function pgSize(k,n){const st=pgState(k);st.n=+n;st.p=0;PGFN[k]()}
window.addEventListener('beforeprint',()=>{PRINT_ALL=true;try{rerender()}catch(e){}});
window.addEventListener('afterprint',()=>{PRINT_ALL=false;try{rerender()}catch(e){}});

/* =========================================================
   LƯU DEMO RIÊNG TRÊN TRÌNH DUYỆT
   ========================================================= */
function saveToFile(){
  if(!persist(S)){
    toast('Không thể lưu dữ liệu trong trình duyệt. Hãy xuất JSON sao lưu.','err');
    return;
  }
  S.savedAt=Date.now();SAVED_AT=S.savedAt;DIRTY=false;
  persist(S);refreshSave();toast('Đã lưu dữ liệu demo trong trình duyệt');
}
function forgetFile(){backup();toast('Đã xuất bản sao JSON; dữ liệu demo vẫn nằm trên trình duyệt')}
function refreshSave(){
  const st=$('#saveSt'),bt=$('#saveBtn');
  bt.classList.toggle('dirty',DIRTY);st.className='savest '+(DIRTY?'dirty':'clean');
  st.innerHTML=DIRTY?'<b>Đã tự lưu</b>trên trình duyệt':SAVED_AT?'<b>Đã lưu</b>trên trình duyệt':'<b>Dữ liệu mẫu</b>riêng cho thiết bị này';
}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();saveToFile()}});

/* =========================================================
   ĐIỀU HƯỚNG
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
function rerender(){APP.pages[CUR][2]()}
function refreshChrome(){if(APP.chrome)APP.chrome()}

/* =========================================================
   MODAL, THÔNG BÁO, TOOLTIP
   ========================================================= */
function openModal(html,size){const m=$('#modal');m.className='modal'+(size?' '+size:'');m.innerHTML=html;$('#ov').classList.add('show')}
function closeModal(){$('#ov').classList.remove('show')}
function toast(msg,type){const t=document.createElement('div');t.className='toast'+(type?' '+type:'');t.textContent=msg;$('#toasts').appendChild(t);setTimeout(()=>{t.style.opacity=0;t.style.transition='.3s'},3800);setTimeout(()=>t.remove(),4200)}
document.addEventListener('mousemove',e=>{const tip=$('#tip'),t=e.target.closest&&e.target.closest('[data-tip]');
  if(!t){tip.style.display='none';return}tip.textContent=t.dataset.tip;tip.style.display='block';
  const w=tip.offsetWidth;tip.style.left=Math.min(e.clientX+14,innerWidth-w-8)+'px';tip.style.top=(e.clientY-36)+'px'});

/* =========================================================
   BIỂU ĐỒ (SVG tự vẽ, không cần mạng)
   ========================================================= */
function niceMax(v){if(v<=0)return 1;const p=Math.pow(10,Math.floor(Math.log10(v)));const n=v/p;return ([1,1.2,1.6,2,3,4,5,6,8,10].find(x=>n<=x+1e-9))*p}
let GID=0;
/* cột (nhóm hoặc chồng) + đường, đường có thể dùng trục phải */
function chartCombo(o){
  const W=760,H=o.H||290,bars=o.bars||[],L=o.line,R=L&&L.right,pl=54,pr=R?50:14,pt=16,pb=34,ih=H-pt-pb,n=o.labels.length;
  const fB=o.fmt||short,tB=o.tf||fmt;
  const bmax=o.stacked?Math.max(0,...o.labels.map((_,i)=>sum(bars,s=>s.data[i]))):Math.max(0,...bars.flatMap(s=>s.data));
  const max=o.max||niceMax(Math.max(1,bmax,L&&!R?Math.max(...L.data):0,o.target&&!o.target.right?o.target.value:0));
  const rmax=R?(L.max||niceMax(Math.max(1,...L.data,o.target&&o.target.right?o.target.value:0))):max;
  const cw=(W-pl-pr)/n,nb=o.stacked?1:Math.max(1,bars.length),bw=Math.max(3,Math.min(o.bw||18,(cw-6)/nb-3));
  const Y=v=>pt+ih*(1-Math.max(0,v)/max),YR=v=>pt+ih*(1-Math.max(0,v)/rmax);
  let g='';
  if(o.hlLast)g+=`<rect class="hl" x="${pl+cw*(n-1)+2}" y="${pt-8}" width="${cw-4}" height="${ih+8}" rx="10" fill="${MAU.nhat}"/>`;
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${bars.length||!R?fB(max*i/4):''}</text>`;
    if(R)g+=`<text x="${W-pr+8}" y="${yy+4}" class="ax">${(L.fmt||short)(rmax*i/4)}</text>`}
  const step=Math.ceil(n/(o.maxLabels||12));
  o.labels.forEach((l,i)=>{const cx=pl+cw*i+cw/2;let acc=0;
    bars.forEach((s,j)=>{const v=+s.data[i]||0,hh=Math.max(v>0?2:0,ih*v/max);
      const x=o.stacked?cx-bw/2:cx-(bw*nb+3*(nb-1))/2+j*(bw+3),y=pt+ih-acc-hh;if(o.stacked)acc+=hh;
      g+=`<rect class="bar" style="animation-delay:${Math.min(i*35,700)+j*20}ms" x="${x.toFixed(1)}" y="${y.toFixed(1)}" width="${bw.toFixed(1)}" height="${hh.toFixed(1)}" rx="${Math.min(5,bw/3)}" fill="${s.color}" data-tip="${esc(s.name+' '+(o.tips?o.tips[i]:l)+': '+tB(v))}"/>`});
    if((n-1-i)%step===0)g+=`<text x="${cx}" y="${H-pb+20}" text-anchor="middle" class="ax"${o.hlLast&&i===n-1?` style="font-weight:700;fill:${MAU.dam}"`:''}>${l}</text>`});
  if(o.target){const t=o.target,yy=(t.right?YR:Y)(t.value);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" stroke="${t.color||'#e0384a'}" stroke-width="1.5" stroke-dasharray="6 5"/><text x="${W-pr-4}" y="${yy-6}" text-anchor="end" style="font-size:11px;font-weight:700;fill:${t.color||'#e0384a'}">${esc(t.label)}</text>`}
  if(L){const yf=R?YR:Y,pts=L.data.map((v,i)=>[pl+cw*i+cw/2,yf(+v||0)]);
    if(L.area){const id='la'+(++GID);g+=`<defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${L.color}" stop-opacity=".22"/><stop offset="1" stop-color="${L.color}" stop-opacity="0"/></linearGradient></defs><path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}L${pts[pts.length-1][0]},${pt+ih}L${pts[0][0]},${pt+ih}Z" fill="url(#${id})"/>`}
    g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="${L.color}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>`;
    g+=pts.map((p,i)=>L.skip&&L.skip[i]?'':`<circle class="seg" cx="${p[0].toFixed(1)}" cy="${p[1].toFixed(1)}" r="${n>20?3:4}" fill="#fff" stroke="${L.color}" stroke-width="2.5" data-tip="${esc(L.name+' '+(o.tips?o.tips[i]:o.labels[i])+': '+(L.tf||L.fmt||fmt)(L.data[i]))}"/>`).join('')}
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
/* biểu đồ Pareto: cột giảm dần + đường % tích lũy, vạch 80% */
function pareto(items,o){o=o||{};
  items=items.filter(x=>x.value>0).sort((a,b)=>b.value-a.value).slice(0,o.top||10);
  if(!items.length)return '<div class="empty">Chưa có dữ liệu trong kỳ</div>';
  const tot=o.total||sum(items,x=>x.value);let acc=0;const cum=items.map(x=>(acc+=x.value)/tot*100);
  const W=o.W||1100,H=o.H||300,pl=50,pr=46,pt=18,pb=52,ih=H-pt-pb,n=items.length,max=niceMax(items[0].value),cw=(W-pl-pr)/n,bw=Math.min(46,cw-14);
  const Y=v=>pt+ih*(1-v/max),YR=v=>pt+ih*(1-v/100);let g='';
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${short(max*i/4)}</text><text x="${W-pr+8}" y="${yy+4}" class="ax">${i*25}%</text>`}
  const y80=YR(80);g+=`<line x1="${pl}" x2="${W-pr}" y1="${y80}" y2="${y80}" stroke="#e0384a" stroke-width="1.3" stroke-dasharray="6 5"/><text x="${W-pr-6}" y="${y80+15}" text-anchor="end" style="font-size:11px;font-weight:700;fill:#e0384a">Mốc 80%</text>`;
  items.forEach((x,i)=>{const cx=pl+cw*i+cw/2,hh=Math.max(2,ih*x.value/max),vital=i===0||cum[i-1]<80;
    g+=`<rect class="bar" style="animation-delay:${i*50}ms" x="${cx-bw/2}" y="${pt+ih-hh}" width="${bw}" height="${hh}" rx="5" fill="${vital?MAU.chu:MAU.nhat2}" data-tip="${esc(x.label+': '+fmt(x.value)+(o.unit?' '+o.unit:'')+' ('+pctS(x.value/tot*100)+')')}"/>`;
    g+=`<text x="${cx}" y="${pt+ih-hh-6}" text-anchor="middle" style="font-size:11px;font-weight:700;fill:${vital?MAU.dam:'#8a93a6'}">${short(x.value)}</text>`;
    const mc=Math.max(6,Math.floor(cw/7.2)),lb=x.label.length>mc?x.label.slice(0,mc-1)+'…':x.label;
    g+=`<text x="${cx}" y="${H-pb+18}" text-anchor="middle" class="ax">${esc(lb)}</text><text x="${cx}" y="${H-pb+33}" text-anchor="middle" class="ax" style="font-size:10.5px">${pctS(cum[i],0)}</text>`});
  const pts=cum.map((v,i)=>[pl+cw*i+cw/2,YR(v)]);
  g+=`<path d="M${pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L')}" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linejoin="round" class="pline" pathLength="1"/>`;
  g+=pts.map((p,i)=>`<circle class="seg" cx="${p[0]}" cy="${p[1]}" r="4.5" fill="#fff" stroke="#f59e0b" stroke-width="2.5" data-tip="${esc('Tích lũy đến '+items[i].label+': '+pctS(cum[i]))}"/>`).join('');
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
/* nhiều đường (xu hướng) */
function chartLines(o){
  const W=760,H=o.H||260,pl=50,pr=14,pt=16,pb=34,ih=H-pt-pb,n=o.labels.length,f=o.fmt||short;
  const max=o.max||niceMax(Math.max(1,...o.series.flatMap(s=>s.data.filter(v=>v!==null)),o.target?o.target.value:0));
  const X=i=>pl+(n>1?i*(W-pl-pr)/(n-1):(W-pl-pr)/2),Y=v=>pt+ih*(1-Math.max(0,v)/max);let g='';
  for(let i=0;i<=4;i++){const yy=pt+ih*(1-i/4);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" class="grid"${i?' stroke-dasharray="3 4"':''}/><text x="${pl-8}" y="${yy+4}" text-anchor="end" class="ax">${f(max*i/4)}</text>`}
  const step=Math.ceil(n/(o.maxLabels||12));
  o.labels.forEach((l,i)=>{if((n-1-i)%step===0)g+=`<text x="${X(i)}" y="${H-pb+20}" text-anchor="middle" class="ax">${l}</text>`});
  if(o.target){const yy=Y(o.target.value);g+=`<line x1="${pl}" x2="${W-pr}" y1="${yy}" y2="${yy}" stroke="#e0384a" stroke-width="1.5" stroke-dasharray="6 5"/><text x="${W-pr-4}" y="${yy-6}" text-anchor="end" style="font-size:11px;font-weight:700;fill:#e0384a">${esc(o.target.label)}</text>`}
  o.series.forEach((s,si)=>{const pts=s.data.map((v,i)=>v===null?null:[X(i),Y(v)]).filter(Boolean),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L');if(!pts.length)return;
    if(s.area){const id='ar'+(++GID);g+=`<defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s.color}" stop-opacity=".25"/><stop offset="1" stop-color="${s.color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${pts[pts.length-1][0]},${pt+ih}L${pts[0][0]},${pt+ih}Z" fill="url(#${id})"/>`}
    g+=`<path d="${d}" fill="none" stroke="${s.color}" stroke-width="${s.w||2.5}" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"${s.dash?' stroke-dasharray="5 5"':''}/>`;
    s.data.forEach((v,i)=>{if(v===null)return;g+=`<circle class="seg" cx="${X(i).toFixed(1)}" cy="${Y(v).toFixed(1)}" r="${n>20?3:4}" fill="#fff" stroke="${s.color}" stroke-width="2.3" data-tip="${esc(s.name+' '+(o.tips?o.tips[i]:o.labels[i])+': '+(o.tf||f)(v))}"/>`})});
  return `<svg viewBox="0 0 ${W} ${H}" class="chart">${g}</svg>`}
function donut(items,o){o=o||{};items=items.filter(x=>x.value>0);
  const tot=sum(items,x=>x.value),tf=o.tf||fmt;
  if(!tot)return '<div class="empty">Chưa có dữ liệu</div>';
  const R=70,C=2*Math.PI*R;let off=0,segs='';
  items.forEach(it=>{const len=it.value/tot*C;
    segs+=`<circle class="seg" cx="100" cy="100" r="${R}" fill="none" stroke="${it.color}" stroke-width="26" stroke-dasharray="${Math.max(0,len-2)} ${C}" stroke-dashoffset="${-off}" transform="rotate(-90 100 100)" data-tip="${esc(it.label+': '+tf(it.value)+' ('+pctS(it.value/tot*100)+')')}"/>`;off+=len});
  return `<div class="donut-wrap"><svg viewBox="0 0 200 200" class="chart">${segs}<text x="100" y="94" text-anchor="middle" class="ax" style="font-size:12px">${esc(o.center||'Tổng')}</text><text x="100" y="117" text-anchor="middle" style="font-size:20px;font-weight:800;fill:var(--text)">${(o.cf||short)(tot)}</text></svg>
  <div class="dl">${items.map(it=>`<div><i style="background:${it.color}"></i>${esc(it.label)}<span>${(o.vf||short)(it.value)}</span><em>${pctS(it.value/tot*100,0)}</em></div>`).join('')}</div></div>`}
/* danh sách thanh ngang */
function hbars(items,o){o=o||{};if(!items.length)return '<div class="empty">Chưa có dữ liệu</div>';
  const mx=o.max||Math.max(1,...items.map(x=>x.value));
  return items.map((x,i)=>`<div class="cb"><div class="cb-h"><b title="${esc(x.label)}">${esc(x.label)}</b><span>${x.sub||''}</span><strong style="color:${x.vc||'inherit'}">${(o.fmt||fmt)(x.value)}</strong></div><div class="bar"><i style="width:${Math.max(1.5,Math.min(100,x.value/mx*100))}%;background:${x.color||o.color||MAU.chu};animation-delay:${i*60}ms"></i></div></div>`).join('')}
let SPK=0;
function spark(data,color){if(!data||data.length<2)return '';const W=240,H=46,n=data.length,mx=Math.max(...data),mn=Math.min(0,...data),r=(mx-mn)||1;
  const pts=data.map((v,i)=>[i*W/(n-1),H-4-(v-mn)/r*(H-12)]),d='M'+pts.map(p=>p[0].toFixed(1)+','+p[1].toFixed(1)).join('L'),id='spg'+(++SPK);
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`}
/* vòng tròn % trong khối hero */
function heroRing(p,label,b,s){const C=2*Math.PI*50,id='rg'+(++GID);p=Math.max(0,Math.min(100,+p||0));
  return `<div class="h-ring"><svg viewBox="0 0 120 120"><defs><linearGradient id="${id}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8ef5c9"/><stop offset="1" stop-color="#22c58b"/></linearGradient></defs>
  <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="11"/>
  <circle class="ring-a" cx="60" cy="60" r="50" fill="none" stroke="url(#${id})" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C*p/100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
  <text x="60" y="61" text-anchor="middle" style="font-size:25px;font-weight:800;fill:#fff">${fmt1(p,p>=99.95||p<10?1:0)}%</text><text x="60" y="78" text-anchor="middle" style="font-size:9.5px;fill:${MAU.heroT2}">${esc(label)}</text></svg>
  <b>${b||''}</b><span>${s||''}</span></div>`}
/* vùng đường trắng trong khối hero */
function heroArea(labels,d,tf,unit){
  const W=440,H=150,pl=8,pr=8,pt=12,pb=22,mx=Math.max(1,...d),mn=Math.min(...d),span=(mx-mn)||mx||1;
  const x=i=>pl+i*(W-pl-pr)/Math.max(1,d.length-1),y=v=>pt+(H-pt-pb)*(1-((v-mn)/span*0.85+0.08));
  const line='M'+d.map((v,i)=>x(i).toFixed(1)+','+y(v).toFixed(1)).join('L'),L=d.length-1,id='hg'+(++GID),step=Math.ceil(d.length/7);
  return `<svg viewBox="0 0 ${W} ${H}" class="chart" style="overflow:visible"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs>
  <path d="${line}L${x(L)},${H-pb}L${x(0)},${H-pb}Z" fill="url(#${id})"/>
  <path d="${line}" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="pline" pathLength="1"/>
  ${d.map((v,i)=>`<circle class="seg" cx="${x(i)}" cy="${y(v)}" r="${i===L?5.5:3}" fill="${i===L?'#fff':'rgba(255,255,255,.75)'}" stroke="${i===L?MAU.chu:'none'}" stroke-width="3" data-tip="${esc(labels[i]+': '+tf(v)+(unit||''))}"/>`).join('')}
  ${labels.map((l,i)=>(L-i)%step===0?`<text x="${x(i)}" y="${H-3}" text-anchor="middle" style="font-size:10.5px;fill:${MAU.heroT2}">${l}</text>`:'').join('')}</svg>`}
function pill(cur,prev,cmp,inverse){
  if(prev===undefined||prev===null)return '<span class="pill na">Toàn bộ</span>';
  if(!prev)return `<span class="pill na" title="${cmp||''}">${cur?'Mới':'-'}</span>`;
  const p=(cur-prev)/Math.abs(prev)*100,up=p>=0,good=inverse?!up:up;
  return `<span class="pill ${good?'pu':'pd'}" title="${cmp||''}">${up?'▲':'▼'} ${fmt1(Math.abs(p))}%</span>`}
/* thay đổi theo điểm % (dùng cho tỷ lệ) */
function pillPt(cur,prev,cmp,inverse){
  if(prev===undefined||prev===null||isNaN(prev))return '<span class="pill na">-</span>';
  const d=cur-prev,up=d>=0,good=inverse?!up:up;
  return `<span class="pill ${Math.abs(d)<0.05?'na':good?'pu':'pd'}" title="${cmp||''}">${up?'▲':'▼'} ${fmt1(Math.abs(d))} điểm</span>`}
/* thẻ KPI */
function kc(o){return `<div class="card kc${o.data?'':' nospk'}" style="--c:${o.c||MAU.chu};--cb:${o.cb||MAU.nhat}"><div class="kc-h"><span class="kc-ic">${o.icon}</span><span class="kc-l">${o.label}</span>${o.pill||''}</div><div class="kc-v"><span data-count="${o.value}"${o.dec?` data-dec="${o.dec}"`:''}>0</span>${o.unit?` <small>${o.unit}</small>`:''}</div><div class="kc-s">${o.sub||''}</div>${o.data?spark(o.data,o.c||MAU.chu):''}</div>`}
/* số chạy từ 0 lên */
function countUp(root){
  const els=[...root.querySelectorAll('[data-count]')],f=e=>v=>e.dataset.dec?fmt1(v,+e.dataset.dec):fmt(v),fin=()=>els.forEach(e=>e.textContent=f(e)(+e.dataset.count));
  if(PRINT_ALL||matchMedia('(prefers-reduced-motion: reduce)').matches)return fin();
  const t0=performance.now(),D=900;els.forEach(e=>e.textContent='0');
  const step=t=>{const k=Math.min(1,(t-t0)/D),ez=1-Math.pow(1-k,3);els.forEach(e=>e.textContent=f(e)(+e.dataset.count*ez));if(k<1)requestAnimationFrame(step)};
  requestAnimationFrame(step);setTimeout(fin,1300)}
const MED=['#f5b301','#9aa5b8','#cd7f32','#c3cbda','#c3cbda','#c3cbda','#c3cbda','#c3cbda','#c3cbda','#c3cbda'];
const medal=i=>`<span class="medal" style="background:${MED[i]||'#c3cbda'}">${i+1}</span>`;

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
  // kiểu ô: 1 tiêu đề cột, 2 chữ, 3 số #,##0, 4 ngày, 5 tiêu đề lớn, 6 tổng số, 7 %, 8 tổng chữ, 9 dòng phụ, 10 số lẻ, 11 chữ giữa, 12 tổng số lẻ, 13 tổng %
  const ST={text:2,money:3,int:3,date:4,pct:7,num:10,center:11};
  function styles(hex){const c='FF'+hex.replace('#','').toUpperCase();
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="0.0%"/><numFmt numFmtId="167" formatCode="#,##0.0"/></numFmts>
<fonts count="5"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="${c}"/><name val="Calibri"/></font><font><i/><sz val="10"/><color rgb="FF667085"/><name val="Calibri"/></font></fonts>
<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="${c}"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEF1F6"/><bgColor indexed="64"/></patternFill></fill></fills>
<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD0D5DD"/></left><right style="thin"><color rgb="FFD0D5DD"/></right><top style="thin"><color rgb="FFD0D5DD"/></top><bottom style="thin"><color rgb="FFD0D5DD"/></bottom><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="14">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>
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
<xf numFmtId="166" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>`}
  function cell(ref,v,type,s){
    if(v===null||v===undefined||v===''||(typeof v==='number'&&!isFinite(v)))return `<c r="${ref}" s="${s}"/>`;
    if(type==='date'){if(!/^\d{4}-\d{2}-\d{2}/.test(String(v)))return `<c r="${ref}" s="11" t="inlineStr"><is><t>${x(v)}</t></is></c>`;return `<c r="${ref}" s="${s}"><v>${serial(v)}</v></c>`}
    if(['money','int','num','pct'].includes(type)){const n=type==='pct'?(+v||0)/100:+v||0;return `<c r="${ref}" s="${s}"><v>${n}</v></c>`}
    return `<c r="${ref}" s="${s}" t="inlineStr"><is><t xml:space="preserve">${x(v)}</t></is></c>`}
  const filterRow=s=>(s.title?1:0)+(s.subtitles||[]).length+((s.title||(s.subtitles||[]).length)?1:0)+1;
  function sheetXML(sh){
    const cols=sh.columns,nc=cols.length,last=colName(nc-1);let r=0;const rows=[],merges=[];
    if(sh.title){r++;rows.push(`<row r="${r}" ht="24" customHeight="1">${cell('A'+r,sh.title,'text',5)}</row>`);merges.push(`A${r}:${last}${r}`)}
    (sh.subtitles||[]).forEach(t=>{r++;rows.push(`<row r="${r}">${cell('A'+r,t,'text',9)}</row>`);merges.push(`A${r}:${last}${r}`)});
    if(r)r++;r++;const hr=r;
    rows.push(`<row r="${r}" ht="30" customHeight="1">${cols.map((c,i)=>cell(colName(i)+r,c.label,'text',1)).join('')}</row>`);
    for(const row of sh.rows){r++;rows.push(`<row r="${r}">${cols.map((c,i)=>{const t=c.type||'text';return cell(colName(i)+r,Array.isArray(row)?row[i]:row[c.key],t,ST[t]||2)}).join('')}</row>`)}
    const lastData=r;
    if(sh.totals&&sh.rows.length){r++;
      rows.push(`<row r="${r}">${cols.map((c,i)=>{const ref=colName(i)+r;
        if(c.sum){const f=`SUBTOTAL(9,${colName(i)}${hr+1}:${colName(i)}${lastData})`,v=sum(sh.rows,row=>Array.isArray(row)?row[i]:row[c.key]);return `<c r="${ref}" s="${c.type==='num'?12:6}"><f>${f}</f><v>${v}</v></c>`}
        if(c.tot!==undefined){if(c.type==='pct')return `<c r="${ref}" s="13"><v>${(+c.tot||0)/100}</v></c>`;return `<c r="${ref}" s="${c.type==='num'?12:6}"><v>${+c.tot||0}</v></c>`}
        return cell(ref,i===0?(sh.totalLabel||'TỔNG CỘNG'):'','text',8)}).join('')}</row>`)}
    if((sh.footer||[]).length)r++;
    (sh.footer||[]).forEach(t=>{r++;rows.push(`<row r="${r}">${cell('A'+r,t,'text',9)}</row>`)});
    const colsXml=`<cols>${cols.map((c,i)=>`<col min="${i+1}" max="${i+1}" width="${c.width||({money:15,date:12,pct:10,num:10,int:10}[c.type]||18)}" customWidth="1"/>`).join('')}</cols>`;
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:${last}${Math.max(r,1)}"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="${hr}" topLeftCell="A${hr+1}" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>${colsXml}<sheetData>${rows.join('')}</sheetData>${sh.rows.length&&!sh.noFilter?`<autoFilter ref="A${hr}:${last}${lastData}"/>`:''}${merges.length?`<mergeCells count="${merges.length}">${merges.map(m=>`<mergeCell ref="${m}"/>`).join('')}</mergeCells>`:''}<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="${sh.portrait?'portrait':'landscape'}" fitToWidth="1" fitToHeight="0"/></worksheet>`}
  function save(fileName,sheets){
    const used={};
    const names=sheets.map(s=>{let n=String(s.name||'Sheet').replace(/[\[\]:*?\/\\]/g,' ').slice(0,31)||'Sheet';let k=n,i=2;while(used[k.toLowerCase()])k=n.slice(0,28)+' '+i++;used[k.toLowerCase()]=1;return k});
    const files=[
      {name:'[Content_Types].xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>${sheets.map((_,i)=>`<Override PartName="/xl/worksheets/sheet${i+1}.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>`).join('')}</Types>`},
      {name:'_rels/.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>`},
      {name:'xl/workbook.xml',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>${names.map((n,i)=>`<sheet name="${x(n)}" sheetId="${i+1}" r:id="rId${i+1}"/>`).join('')}</sheets><definedNames>${sheets.map((s,i)=>s.rows.length&&!s.noFilter?`<definedName name="_xlnm._FilterDatabase" localSheetId="${i}" hidden="1">'${x(names[i]).replace(/'/g,"''")}'!$A$${filterRow(s)}:$${colName(s.columns.length-1)}$${filterRow(s)+s.rows.length}</definedName>`:'').join('')}</definedNames></workbook>`},
      {name:'xl/_rels/workbook.xml.rels',data:`<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">${sheets.map((_,i)=>`<Relationship Id="rId${i+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet${i+1}.xml"/>`).join('')}<Relationship Id="rId${sheets.length+1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>`},
      {name:'xl/styles.xml',data:styles(MAU.chu)},
      ...sheets.map((s,i)=>({name:`xl/worksheets/sheet${i+1}.xml`,data:sheetXML(s)}))];
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
    const sep=(t.split('\n')[0].match(/;/g)||[]).length>(t.split('\n')[0].match(/,/g)||[]).length?';':',';
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
  return {save,read};
})();

/* =========================================================
   BẢNG DỮ LIỆU CHUNG: danh mục và nhật ký
   Mỗi bảng khai báo trong APP.tables: tên, cột, kiểu cột
   ========================================================= */
const T=k=>APP.tables[k];
const rowsOf=k=>S[k]||(S[k]=[]);
const byId=(k,id)=>rowsOf(k).find(r=>r.id===id);
const opts=c=>typeof c.opts==='function'?c.opts():(c.opts||[]);
function refName(k,id){const r=byId(k,id),t=T(k);if(!r)return '';return t.label?t.label(r):r.name}
function cv(c,r){return c.type==='calc'?c.calc(r):r[c.k]}
function ctext(c,r){const v=cv(c,r),f=c.fmt||c.type;
  if(v===''||v===null||v===undefined)return '';
  if(f==='money'||f==='num')return fmt(v);
  if(f==='dec')return fmt1(v,2);
  if(f==='pct')return pctS(v);
  if(f==='date')return vnDate(v);
  if(c.type==='ref')return refName(c.ref,v);
  return String(v)}
function chtml(c,r){if(c.html)return c.html(r);const t=ctext(c,r);
  if(c.tags&&t)return `<span class="tag ${c.tags[t]||'mut'}">${esc(t)}</span>`;
  if(c.bold)return `<b>${esc(t)}</b>`;return esc(t)}
const isNum=c=>['money','num','dec','pct'].includes(c.fmt||c.type);
const CR={};
/* trang danh sách: tìm kiếm, lọc, khoảng ngày, thêm / sửa / xóa, nhập & xuất Excel */
function crud(k,o){
  o=o||{};const t=T(k);t._o=o;const el=$('#page-'+(o.page||k)),st=CR[k]=CR[k]||{q:'',f:{},from:'',to:'',per:o.per||''};
  const fl=(o.filters||[]).map(fk=>{const c=t.cols.find(x=>x.k===fk);let op;
    if(c.type==='calc')op=opts(c).map(v=>[v,v]);
    else if(c.type==='ref')op=[...rowsOf(c.ref)].sort((a,b)=>refName(c.ref,a.id).localeCompare(refName(c.ref,b.id),'vi')).map(r=>[r.id,refName(c.ref,r.id)]);
    else if(c.type==='select')op=opts(c).map(v=>[v,v]);
    else op=[...new Set(rowsOf(k).map(r=>r[fk]).filter(Boolean))].sort((a,b)=>String(a).localeCompare(String(b),'vi')).map(v=>[v,v]);
    return `<select class="inp" data-fk="${fk}"><option value="">${esc(c.fl||('Mọi '+c.l.toLowerCase()))}</option>${op.map(([v,l])=>`<option value="${esc(v)}" ${st.f[fk]===v?'selected':''}>${esc(l)}</option>`).join('')}</select>`}).join('');
  el.innerHTML=`${o.top?`<div id="${k}Top"></div>`:''}<div class="toolbar">
    <div class="search grow">${IC.search}<input class="inp" id="${k}Q" value="${esc(st.q)}" placeholder="${esc(o.ph||'Tìm kiếm...')}"></div>${fl}
    ${o.dateKey?`<div class="seg" id="${k}Per"><button data-v="today">Hôm nay</button><button data-v="week">Tuần này</button><button data-v="month">Tháng này</button><button data-v="">Tất cả</button></div><div class="rdate"><input type="date" class="inp" id="${k}From" value="${st.from}"><span>đến</span><input type="date" class="inp" id="${k}To" value="${st.to}"></div>`:''}
    ${o.extra||''}
    ${t.imp?`<button class="btn" onclick="crudImport('${k}')">${IC.up}Nhập từ Excel</button>`:''}
    <button class="btn" onclick="crudExport('${k}')">${IC.xls}Xuất Excel</button>
    ${o.noAdd?'':`<button class="btn pri" onclick="crudForm('${k}')">${IC.plus}${esc(o.addLbl||'Thêm mới')}</button>`}
  </div><div class="card"><div class="tbl-wrap" id="${k}Tbl"></div></div>`;
  const upd=()=>{pgReset(k);crudTable(k)};
  $('#'+k+'Q').oninput=e=>{st.q=e.target.value;upd()};
  $$(`#page-${o.page||k} [data-fk]`).forEach(s=>s.onchange=()=>{st.f[s.dataset.fk]=s.value;upd()});
  if(o.dateKey){
    const setPer=v=>{st.per=v;$$('#'+k+'Per button').forEach(b=>b.classList.toggle('on',b.dataset.v===v));
      if(v){const r=rangeOf(v);st.from=r.from;st.to=v==='today'?r.to:(r.to>TODAYS?TODAYS:r.to)}else{st.from='';st.to=''}
      $('#'+k+'From').value=st.from;$('#'+k+'To').value=st.to};
    $$('#'+k+'Per button').forEach(b=>b.onclick=()=>{setPer(b.dataset.v);upd()});
    if(st.per!==null)setPer(st.per);
    ['From','To'].forEach(x=>$('#'+k+x).oninput=e=>{st[x.toLowerCase()]=e.target.value;st.per=null;$$('#'+k+'Per button').forEach(b=>b.classList.remove('on'));upd()})}
  crudTable(k)}
function crudRows(k){
  const t=T(k),o=t._o||{},st=CR[k]||{q:'',f:{}},q=fold(st.q.trim()),words=q.split(/\s+/).filter(Boolean);
  let rows=rowsOf(k).filter(r=>{
    for(const fk in st.f){if(!st.f[fk])continue;const c=t.cols.find(x=>x.k===fk);if(String(c?cv(c,r):r[fk])!==st.f[fk])return false}
    if(o.dateKey){if(st.from&&r[o.dateKey]<st.from)return false;if(st.to&&r[o.dateKey]>st.to)return false}
    if(words.length){const txt=fold(t.cols.map(c=>ctext(c,r)).join(' '));if(!words.every(w=>txt.includes(w)))return false}
    return o.where?o.where(r):true});
  return rows.sort(t.sort||((a,b)=>String(a[t.code||'name']).localeCompare(String(b[t.code||'name']),'vi')))}
function crudTable(k){
  const t=T(k),o=t._o||{},rows=crudRows(k),cols=t.cols.filter(c=>!c.hide);
  if(o.top)o.top(rows);
  const box=$('#'+k+'Tbl');if(!box)return;
  if(!rows.length){box.innerHTML=`<div class="empty">${rowsOf(k).length?'Không có dòng nào phù hợp bộ lọc':'Chưa có dữ liệu'}<br><br>${o.noAdd?'':`<button class="btn pri" onclick="crudForm('${k}')">${IC.plus}${esc(o.addLbl||'Thêm mới')}</button>`}</div>`;return}
  const foot=cols.map((c,i)=>{if(i===0)return `<td>${fmt(rows.length)} dòng</td>`;if(c.sum)return `<td class="n">${fmt(sum(rows,r=>cv(c,r)))}</td>`;if(c.agg)return `<td class="n">${c.agg(rows)}</td>`;return '<td></td>'}).join('');
  const off=pgOff(k);
  box.innerHTML=`<table><thead><tr>${o.noStt?'':'<th class="c">#</th>'}${cols.map(c=>`<th class="${isNum(c)?'n':c.center?'c':''}">${esc(c.th||c.l)}</th>`).join('')}<th></th></tr></thead><tbody>
  ${pgSlice(k,rows).map((r,i)=>`<tr${o.rowCls?` class="${o.rowCls(r)}"`:''}>${o.noStt?'':`<td class="c" style="color:var(--muted)">${off+i+1}</td>`}${cols.map(c=>`<td class="${isNum(c)?'n':c.center?'c':c.wrap?'wrap':c.type==='date'||c.bold?'nw':''}">${chtml(c,r)}</td>`).join('')}<td class="act">${o.act?o.act(r):''}<button class="btn sm ico" title="Sửa" onclick="crudForm('${k}','${r.id}')">${IC.edit}</button> <button class="btn sm ico del" title="Xóa" onclick="crudDel('${k}','${r.id}')">${IC.trash}</button></td></tr>`).join('')}
  </tbody>${o.noFoot?'':`<tfoot><tr>${o.noStt?'':'<td></td>'}${foot}<td></td></tr></tfoot>`}</table>${pgBar(k,rows.length,()=>crudTable(k))}`}
function nextCode(k){const t=T(k);let m=0;rowsOf(k).forEach(r=>{const n=parseInt(String(r[t.code]).replace(/\D/g,''))||0;if(n>m)m=n});return (t.prefix||'')+String(m+1).padStart(t.digits||3,'0')}
let FORM=null;
function crudForm(k,id,preset){
  const t=T(k);if(t.form)return t.form(id,preset);
  const old=id?byId(k,id):null,r={...(old||{})};
  if(!old){t.cols.forEach(c=>{if(c.type==='calc')return;let d=typeof c.def==='function'?c.def():c.def;if(d===undefined)d=c.type==='date'?TODAYS:'';r[c.k]=d});if(t.code)r[t.code]=nextCode(k);Object.assign(r,preset||{})}
  FORM={k,old,r};
  const field=c=>{const v=r[c.k]??'',idf='fm_'+c.k,req=c.req?' <em>*</em>':'';let inp;
    if(c.type==='calc')inp=`<input class="inp" id="${idf}" readonly>`;
    else if(c.type==='select')inp=`<select class="inp" id="${idf}">${c.req?'':'<option value=""></option>'}${opts(c).map(o=>`<option ${String(o)===String(v)?'selected':''}>${esc(o)}</option>`).join('')}</select>`;
    else if(c.type==='ref'){const list=[...rowsOf(c.ref)].filter(x=>!c.refWhere||c.refWhere(x)||x.id===v).sort((a,b)=>refName(c.ref,a.id).localeCompare(refName(c.ref,b.id),'vi'));
      inp=`<select class="inp" id="${idf}"><option value="">-- Chọn --</option>${list.map(x=>`<option value="${x.id}" ${x.id===v?'selected':''}>${esc(refName(c.ref,x.id))}</option>`).join('')}</select>`}
    else if(c.type==='note')inp=`<textarea class="inp" id="${idf}" rows="2">${esc(v)}</textarea>`;
    else if(c.type==='date')inp=`<input class="inp" type="date" id="${idf}" value="${esc(v)}">`;
    else if(isNum(c))inp=`<input class="inp" type="number" step="any" ${c.min!==undefined?`min="${c.min}"`:''} ${c.maxv!==undefined?`max="${c.maxv}"`:''} id="${idf}" value="${esc(v)}">`;
    else inp=`<input class="inp" id="${idf}" value="${esc(v)}" ${c.list?`list="dl_${c.k}"`:''} placeholder="${esc(c.ph||'')}">${c.list?`<datalist id="dl_${c.k}">${[...new Set(typeof c.list==='function'?c.list():c.list)].map(x=>`<option>${esc(x)}</option>`).join('')}</datalist>`:''}`;
    return `<div class="fg ${c.full||c.type==='note'?'full':''}"><label class="f">${esc(c.l)}${req}${c.help?` <span style="font-weight:400;color:var(--muted)">(${esc(c.help)})</span>`:''}</label>${inp}</div>`};
  openModal(`<div class="modal-h"><h3>${old?'Sửa ':'Thêm '}${esc(t.one||t.name.toLowerCase())}</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b"><div class="fgrid">${t.cols.filter(c=>!c.noForm).map(field).join('')}</div>${t.formNote?`<div class="note-box blue">${t.formNote}</div>`:''}</div>
  <div class="modal-f">${old?`<button class="btn danger" style="margin-right:auto" onclick="crudDel('${k}','${old.id}')">${IC.trash}Xóa</button>`:''}<button class="btn" onclick="closeModal()">Hủy</button>${old?'':`<button class="btn" onclick="crudSave(true)">Lưu &amp; thêm tiếp</button>`}<button class="btn pri" onclick="crudSave()">${IC.check}Lưu</button></div>`);
  const calc=()=>{const x=formRead();t.cols.filter(c=>c.type==='calc'&&!c.noForm).forEach(c=>{const e=$('#fm_'+c.k);if(e)e.value=ctext(c,x)})};
  $$('#modal .inp').forEach(e=>{e.addEventListener('input',()=>{if(t.onInput)t.onInput(e.id.slice(3),FORM);calc()})});calc();
  setTimeout(()=>{const f=$('#modal .inp:not([readonly])');f&&f.focus()},60)}
function formRead(){const {k,r}=FORM,t=T(k),x={...r};
  t.cols.forEach(c=>{if(c.type==='calc'||c.noForm)return;const e=$('#fm_'+c.k);if(!e)return;let v=e.value;
    if(isNum(c))v=v===''?'':+v;else v=String(v).trim();x[c.k]=v});return x}
function crudSave(again){
  const {k,old}=FORM,t=T(k),x=formRead();
  for(const c of t.cols){if(c.req&&!c.noForm&&(x[c.k]===''||x[c.k]===undefined))return toast('Chưa điền '+c.l,'err')}
  if(t.code&&rowsOf(k).some(r=>String(r[t.code]).toLowerCase()===String(x[t.code]).toLowerCase()&&(!old||r.id!==old.id)))return toast('Mã '+x[t.code]+' đã có, chọn mã khác','err');
  if(t.check){const e=t.check(x,old);if(e)return toast(e,'err')}
  if(old)Object.assign(old,x);else{x.id=uid();rowsOf(k).push(x)}
  if(t.after)t.after(x,old);
  save();toast(old?'Đã cập nhật':'Đã thêm '+(t.one||'dòng mới'));
  if(again){const keep={};(t.keep||[]).forEach(c=>keep[c]=x[c]);crudForm(k,null,keep)}else closeModal();
  rerender()}
function crudDel(k,id){const t=T(k),r=byId(k,id);if(!r)return;
  let used=0,where='';for(const ok in APP.tables)APP.tables[ok].cols.forEach(c=>{if(c.type==='ref'&&c.ref===k){const n=rowsOf(ok).filter(x=>x[c.k]===id).length;if(n){used+=n;where=APP.tables[ok].name}}});
  if(used)return toast(`Không xóa được: đang dùng ở ${used} dòng của "${where}". Xóa hoặc đổi các dòng đó trước.`,'err');
  if(t.inUse){const m=t.inUse(r);if(m)return toast(m,'err')}
  const name=t.label?t.label(r):(r.name||r[t.code]||'dòng này');
  if(!confirm(`Xóa "${name}"?${t.delNote?'\n\n'+t.delNote:''} Không hoàn tác được.`))return;
  S[k]=rowsOf(k).filter(x=>x.id!==id);if(t.onDel)t.onDel(r);save();closeModal();toast('Đã xóa');rerender()}
/* bảng thành sheet Excel */
function xtype(c){const f=c.fmt||c.type;return f==='money'?'money':f==='num'?'int':f==='dec'?'num':f==='pct'?'pct':f==='date'?'date':'text'}
function tableSheet(k,rows,title){const t=T(k),cols=t.cols.filter(c=>!c.noX);
  return {name:t.sheet||t.name,title:title||(t.xtitle||t.name.toUpperCase()),subtitles:[co().name||APP.title,'Xuất ngày '+vnDate(TODAYS)],totals:cols.some(c=>c.sum),
    columns:[{label:'STT',type:'int',width:6},...cols.map(c=>({label:c.l,type:xtype(c),sum:c.sum,width:c.w}))],
    rows:rows.map((r,i)=>[i+1,...cols.map(c=>{const v=cv(c,r);return c.type==='ref'?refName(c.ref,v):(xtype(c)==='text'?ctext(c,r):v)})])}}
function crudExport(k){XLSX.save(T(k).name+' '+TODAYS,[tableSheet(k,crudRows(k))])}
function exportAll(){const sh=[...(APP.reports?APP.reports():[]),...Object.keys(APP.tables).map(k=>tableSheet(k,[...rowsOf(k)].sort(T(k).sort||(()=>0))))];XLSX.save(APP.title+' '+TODAYS,sh)}
/* nhập từ Excel: khớp tên cột theo tiêu đề */
function impVal(c,v){if(v===undefined||v===null)return '';
  if(isNum(c)){if(typeof v==='number')return c.fmt==='pct'||c.type==='pct'?(v<=1&&v>0?v*100:v):v;const s=String(v).trim();if(!s)return '';if(/^-?\d+[.,]\d{1,2}$/.test(s))return parseFloat(s.replace(',','.'));const n=parseFloat(s.replace(/[^\d,.-]/g,'').replace(/\./g,'').replace(',','.'));return isNaN(n)?'':n}
  if(c.type==='date'){if(typeof v==='number')return ymd(new Date(Math.round((v-25569)*864e5)+new Date().getTimezoneOffset()*6e4));const s=String(v).trim();let m=s.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})/);if(m)return m[3]+'-'+pad(m[2])+'-'+pad(m[1]);m=s.match(/^(\d{4})-(\d{2})-(\d{2})/);return m?m[0]:''}
  if(c.type==='ref'){const s=fold(String(v).trim());if(!s)return '';const t=T(c.ref),hit=rowsOf(c.ref).find(r=>fold(refName(c.ref,r.id))===s||(t.code&&fold(r[t.code])===s)||fold(r.name)===s);return hit?hit.id:null}
  if(c.type==='select'){const s=fold(String(v).trim());const hit=opts(c).find(o=>fold(o)===s);return hit===undefined?String(v).trim():hit}
  return String(v).trim()}
let IMP=null;
function crudImport(k){const t=T(k);IMP={k,items:[],mode:'update'};
  const cols=t.cols.filter(c=>c.type!=='calc');
  openModal(`<div class="modal-h"><h3>Nhập ${esc(t.name.toLowerCase())} từ Excel</h3><button class="x" onclick="closeModal()">×</button></div>
  <div class="modal-b">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><label class="btn pri" style="cursor:pointer">${IC.up}Chọn file Excel<input type="file" id="impFile" accept=".xlsx,.csv" hidden></label>
      <button class="btn" onclick="impTemplate('${k}')">${IC.dl}Tải file mẫu</button><span id="impName" style="color:var(--muted);font-size:13px">Chưa chọn file</span></div>
    <p class="hint2">File có dòng tiêu đề gồm các cột: <b>${cols.map(c=>esc(c.l)).join(', ')}</b>. Cột thiếu thì để trống. ${cols.filter(c=>c.type==='ref').map(c=>`Cột "${esc(c.l)}" ghi đúng tên đã có trong danh mục.`).join(' ')} Ngày ghi dạng dd/mm/yyyy.</p>
    <label class="f">Hoặc dán thẳng từ Excel: bôi đen vùng dữ liệu (có cả dòng tiêu đề), Ctrl + C, bấm vào ô dưới, Ctrl + V</label>
    <textarea class="inp" id="impPaste" rows="3" placeholder="Dán dữ liệu từ Excel vào đây..." style="font-size:12.5px"></textarea>
    ${t.code?`<div class="imp-opt"><b>Trùng mã đã có:</b><label><input type="radio" name="impMode" value="update" checked> Cập nhật theo file</label><label><input type="radio" name="impMode" value="skip"> Bỏ qua</label></div>`:''}
    <div id="impT" style="margin-top:10px"></div></div>
  <div class="modal-f"><span id="impSum" style="margin-right:auto;align-self:center;color:var(--muted);font-size:13px"></span><button class="btn" onclick="closeModal()">Hủy</button><button class="btn pri" id="impOk" disabled onclick="impApply()">Nhập dữ liệu</button></div>`,'wide');
  $$('[name=impMode]').forEach(r=>r.onchange=()=>{IMP.mode=r.value;drawImp()});
  const parse=rows=>{rows=rows.filter(r=>r&&r.some(c=>String(c??'').trim()!==''));let hr=-1,map={};
    for(let i=0;i<Math.min(rows.length,15)&&hr<0;i++){const m={};rows[i].forEach((h,j)=>{const n=normH(h);const c=cols.find(c=>normH(c.l)===n||(c.alias||[]).includes(n));if(c&&m[c.k]===undefined)m[c.k]=j});if(Object.keys(m).length>=Math.min(2,cols.length))hr=i,map=m}
    if(hr<0)cols.forEach((c,j)=>map[c.k]=j);
    return rows.slice(hr+1).map((row,i)=>{const o={_row:hr+i+2,_bad:[]};cols.forEach(c=>{if(map[c.k]===undefined)return;const v=impVal(c,row[map[c.k]]);if(v===null){o._bad.push(c.l+' "'+row[map[c.k]]+'" chưa có');o[c.k]=''}else o[c.k]=v});
      const req=cols.filter(c=>c.req&&c.k!==t.code).find(c=>o[c.k]===''||o[c.k]===undefined);if(req)o._bad.push('Thiếu '+req.l);
      if(t.code&&o[t.code]){o._old=rowsOf(k).find(r=>fold(r[t.code])===fold(o[t.code]))}return o})};
  $('#impFile').onchange=async e=>{const f=e.target.files[0];if(!f)return;e.target.value='';$('#impName').textContent='Đang đọc '+f.name+'...';
    try{IMP.items=parse(await XLSX.read(f));$('#impName').innerHTML=`<b>${esc(f.name)}</b> · đọc được ${IMP.items.length} dòng`}catch(err){IMP.items=[];$('#impName').textContent='Chưa đọc được file';toast(err.message||'Không đọc được file','err')}drawImp()};
  let tm;$('#impPaste').oninput=e=>{clearTimeout(tm);tm=setTimeout(()=>{const s=e.target.value;IMP.items=s.trim()?parse(s.replace(/\r/g,'').split('\n').map(l=>l.split('\t'))):[];$('#impName').textContent=s.trim()?'Dữ liệu dán vào · '+IMP.items.length+' dòng':'Chưa chọn file';drawImp()},250)};
  drawImp()}
function drawImp(){const {k,items}=IMP,t=T(k),cols=t.cols.filter(c=>c.type!=='calc'&&!c.hide).slice(0,8);
  const ok=items.filter(o=>!o._bad.some(b=>b.startsWith('Thiếu'))&&!(o._old&&IMP.mode==='skip'));
  $('#impOk').disabled=!ok.length;$('#impOk').textContent=ok.length?`Nhập ${ok.length} dòng`:'Nhập dữ liệu';
  $('#impSum').textContent=items.length?`${items.filter(o=>!o._old).length} dòng mới · ${items.filter(o=>o._old).length} trùng mã · ${items.filter(o=>o._bad.length).length} dòng cần xem lại`:'';
  $('#impT').innerHTML=items.length?`<div class="tbl-wrap" style="border:1px solid var(--line);border-radius:12px"><table><thead><tr><th class="c">Dòng</th>${cols.map(c=>`<th>${esc(c.l)}</th>`).join('')}<th>Tình trạng</th></tr></thead><tbody>${pgSlice('imp',items,10).map(o=>`<tr><td class="c">${o._row}</td>${cols.map(c=>`<td>${esc(ctext(c,o))}</td>`).join('')}<td>${o._bad.length?`<span class="tag ${o._bad.some(b=>b.startsWith('Thiếu'))?'zero':'low'}" title="${esc(o._bad.join('; '))}">${esc(o._bad[0])}</span>`:o._old?`<span class="tag low">${IMP.mode==='skip'?'Bỏ qua':'Cập nhật'}</span>`:'<span class="tag ok">Thêm mới</span>'}</td></tr>`).join('')}</tbody></table>${pgBar('imp',items.length,drawImp)}</div>`:'<div class="empty" style="padding:20px">Chọn file Excel hoặc dán dữ liệu để xem trước</div>'}
function impApply(){const {k,items}=IMP,t=T(k);let add=0,upd=0;
  items.forEach(o=>{if(o._bad.some(b=>b.startsWith('Thiếu')))return;const x={};Object.keys(o).forEach(f=>{if(!f.startsWith('_'))x[f]=o[f]});
    if(o._old){if(IMP.mode==='skip')return;Object.keys(x).forEach(f=>{if(x[f]!==''&&x[f]!==undefined)o._old[f]=x[f]});upd++}
    else{t.cols.forEach(c=>{if(c.type!=='calc'&&x[c.k]===undefined)x[c.k]=typeof c.def==='function'?c.def():(c.def??'')});if(t.code&&!x[t.code])x[t.code]=nextCode(k);x.id=uid();rowsOf(k).push(x);add++}});
  save();closeModal();rerender();toast(`Đã thêm ${add} dòng${upd?', cập nhật '+upd+' dòng':''}. Dữ liệu đã tự lưu.`)}
function impTemplate(k){const t=T(k),cols=t.cols.filter(c=>c.type!=='calc');
  XLSX.save('Mau nhap '+t.name,[{name:t.sheet||t.name,title:'MẪU NHẬP '+t.name.toUpperCase(),subtitles:['Điền từ dòng dưới tiêu đề. Số ghi số, ngày ghi dd/mm/yyyy.'],noFilter:true,
    columns:cols.map(c=>({label:c.l,type:xtype(c)==='date'?'text':xtype(c),width:c.w})),
    rows:rowsOf(k).slice(0,3).map(r=>cols.map(c=>xtype(c)==='text'||c.type==='date'?ctext(c,r):r[c.k]))}])}

/* =========================================================
   CÀI ĐẶT & HƯỚNG DẪN (dùng chung)
   ========================================================= */
const co=()=>S.company||{};
function renderSettings(){
  const el=$('#page-settings'),c=co(),size=JSON.stringify(S).length;
  el.innerHTML=`<div class="card" style="padding:20px;margin-bottom:16px">
   <h4>Giao diện &amp; màu sắc</h4>
   <p>Chọn một trong 4 theme. Màu được áp dụng ngay và lưu riêng trên trình duyệt, không ảnh hưởng dữ liệu doanh số.</p>
   <div class="sales-theme-grid" role="group" aria-label="Chọn theme màu">${salesThemeChoices()}</div>
  </div><div class="card" style="padding:20px;margin-bottom:16px">
   <h4>Thư viện dữ liệu Demo doanh số nhân viên</h4>
   <p>Dữ liệu mẫu trên server chỉ đọc. Mỗi khách hàng có dữ liệu riêng trong trình duyệt.</p>
   <div class="row2"><div class="fg"><label class="f" for="salesDemoDataset">Bộ dữ liệu</label><select class="inp" id="salesDemoDataset"><option value="">Đang tải…</option></select></div>
   <div class="fg"><label class="f">&nbsp;</label><button class="btn pri" onclick="loadSalesDemoDataset()">Nạp dữ liệu Demo</button></div></div>
   <p id="salesDemoLibraryStatus" role="status" aria-live="polite"></p>
  </div><div class="set-grid">
   <div class="card"><div class="set-ic" style="background:#e9ebf1;color:var(--sb)">${IC.save}</div><h4>Lưu trên trình duyệt</h4><p>Dữ liệu demo được tự động lưu riêng trên trình duyệt này. Phím tắt Ctrl + S.</p><button class="btn save" onclick="saveToFile()">Lưu dữ liệu</button> <button class="btn sm" onclick="backup()">Tải JSON</button></div>
   <div class="card"><div class="set-ic" style="background:var(--chu-dao-nhat);color:var(--chu-dao)">${IC.xls}</div><h4>Xuất toàn bộ ra Excel</h4><p>Tải một file .xlsx, mỗi bảng dữ liệu và báo cáo là một sheet, có tiêu đề màu, số là số.</p><button class="btn pri" onclick="exportAll()">Xuất Excel</button></div>
   <div class="card"><div class="set-ic" style="background:var(--inbg);color:var(--in)">${IC.dl}</div><h4>Sao lưu dữ liệu</h4><p>Tải JSON về máy và tạo bản mới trên server bằng mã quản trị.</p>
   <label class="f" for="salesBackupName">Tên bộ dữ liệu</label><input class="inp" id="salesBackupName" maxlength="100" placeholder="Ví dụ: Doanh số chi nhánh 2026">
   <button class="btn pri" style="margin-top:10px" onclick="openSalesBackupModal()">Sao lưu về máy và server</button>
   <p id="salesBackupStatus" role="status" aria-live="polite"></p></div>
   <div class="card"><div class="set-ic" style="background:#e6f4fb;color:#0b7cad">${IC.up}</div><h4>Khôi phục dữ liệu</h4><p>Chọn file .json đã sao lưu. Dữ liệu hiện tại sẽ được thay bằng dữ liệu trong file.</p><label class="btn" style="cursor:pointer">Chọn file sao lưu<input type="file" id="restoreFile" accept=".json" hidden></label></div>
   <div class="card"><div class="set-ic" style="background:var(--warnbg);color:#b07a00">${IC.reset}</div><h4>Nạp lại dữ liệu mẫu</h4><p>Thay dữ liệu hiện tại bằng dữ liệu mẫu để xem thử các chức năng.</p><button class="btn" onclick="resetSample()">Nạp dữ liệu mẫu</button></div>
   <div class="card"><div class="set-ic" style="background:var(--dangerbg);color:var(--danger)">${IC.trash}</div><h4>Bắt đầu dùng thật</h4><p>${esc(APP.clearNote||'Xóa sạch dữ liệu mẫu để nhập dữ liệu thật của mình từ đầu.')}</p><button class="btn danger" onclick="clearAll()">Xóa dữ liệu mẫu</button></div>
  </div>
  <div id="salesBackupModal" role="dialog" aria-modal="true" aria-labelledby="salesBackupModalTitle" hidden style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);padding:16px;overflow:auto">
   <div class="card" style="max-width:480px;margin:8vh auto;padding:22px;background:var(--bg,#fff)">
    <h3 id="salesBackupModalTitle">Xác nhận sao lưu server</h3>
    <p>Nhập YES để xác nhận và mã quản trị để lưu dữ liệu lên server.</p>
    <label class="f" for="salesBackupYes">Nhập YES</label><input class="inp" id="salesBackupYes" autocomplete="off">
    <label class="f" for="salesBackupCode">Mã quản trị</label><input class="inp" id="salesBackupCode" type="password" autocomplete="off">
    <p id="salesBackupError" role="alert" style="color:var(--danger)"></p>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px"><button class="btn" onclick="closeSalesBackupModal()">Hủy</button><button class="btn pri" id="salesBackupSubmit" onclick="submitSalesBackup()">Xác nhận và sao lưu</button></div>
   </div>
  </div>
  <div class="card" style="margin-top:16px;padding:20px"><h4 style="font-size:15px;margin-bottom:4px">Thông tin đơn vị</h4><p style="color:var(--muted);font-size:13px;margin-bottom:14px">Điền một lần, tên đơn vị sẽ hiện trên file Excel xuất ra và trên bản in.</p>
   <div class="row2"><div class="fg"><label class="f">Tên đơn vị / công ty</label><input class="inp" id="coName" value="${esc(c.name||'')}" placeholder="VD: Công ty TNHH Minh An"></div><div class="fg"><label class="f">${esc(APP.coExtra||'Bộ phận / Chi nhánh')}</label><input class="inp" id="coDept" value="${esc(c.dept||'')}"></div></div>
   <div class="row2"><div class="fg"><label class="f">Địa chỉ</label><input class="inp" id="coAddr" value="${esc(c.addr||'')}"></div><div class="fg"><label class="f">Điện thoại</label><input class="inp" id="coPhone" value="${esc(c.phone||'')}"></div></div>
   ${APP.settingsExtra?APP.settingsExtra():''}
   <button class="btn pri" onclick="saveCompany()">Lưu thông tin</button></div>
  <div class="card" style="margin-top:16px;padding:18px"><b>Dung lượng dữ liệu:</b> ${fmt1(size/1024,1)} KB
   <div class="pbar" style="height:8px;margin:10px 0"><i style="width:${Math.min(100,size/4/1024/1024*100)}%;background:var(--chu-dao)"></i></div>
   <span style="color:var(--muted)">${Object.keys(APP.tables).map(k=>fmt(rowsOf(k).length)+' '+T(k).name.toLowerCase()).join(' · ')}. Dữ liệu demo lưu riêng trên trình duyệt này. Hãy tải JSON để sao lưu.</span></div>`;
  renderSalesDemoLibrary();
  $('#restoreFile').onchange=e=>{const f=e.target.files[0];if(!f)return;const fr=new FileReader();fr.onload=()=>{try{const d=JSON.parse(fr.result);if(!APP.valid(d))throw 0;
    if(!confirm('Khôi phục dữ liệu từ file sao lưu? Dữ liệu hiện tại sẽ được thay thế.'))return;S=d;save();toast('Đã khôi phục và lưu trên trình duyệt');rerender()}catch(_){toast('File sao lưu không hợp lệ','err')}};fr.readAsText(f);e.target.value=''}}

const SALES_DATASET_URL='/demo/doanh-so-nhan-vien/datasets';
let SALES_BACKUP_BUSY=false;
async function renderSalesDemoLibrary(){
  const select=$('#salesDemoDataset'),status=$('#salesDemoLibraryStatus');
  if(!select)return;
  try{
    const res=await fetch(SALES_DATASET_URL,{headers:{Accept:'application/json'},cache:'no-store'});
    if(!res.ok)throw new Error('HTTP '+res.status);
    const payload=await res.json();
    select.replaceChildren(new Option('Chọn bộ dữ liệu',''));
    for(const item of payload.datasets||[])select.add(new Option(item.name+' ('+item.nv+' NV, '+item.sp+' SP, '+item.ban+' đơn)',item.id));
    status.textContent=(payload.datasets||[]).length?'Chọn dữ liệu và nhấn Nạp. Dữ liệu trên server không thay đổi.':'Chưa có bộ dữ liệu trên server.';
  }catch(e){status.textContent='Không tải được thư viện: '+e.message}
}
async function loadSalesDemoDataset(){
  const id=$('#salesDemoDataset')?.value,status=$('#salesDemoLibraryStatus');
  if(!id){status.textContent='Hãy chọn bộ dữ liệu.';return}
  if(!confirm('Nạp bộ dữ liệu này? Dữ liệu hiện tại trên trình duyệt sẽ bị thay thế.'))return;
  try{
    const res=await fetch(SALES_DATASET_URL+'/'+encodeURIComponent(id),{cache:'no-store'});
    if(!res.ok)throw new Error('HTTP '+res.status);
    const next=await res.json();
    if(!APP.valid(next))throw new Error('Dữ liệu không hợp lệ');
    if(!persist(next))throw new Error('Bộ nhớ trình duyệt đã đầy');
    S=next;
    toast('Đã nạp dữ liệu Demo');
    rerender();
  }catch(e){status.textContent='Không thể nạp: '+e.message}
}
function openSalesBackupModal(){
  const name=$('#salesBackupName')?.value.trim();
  if(!name||name.length<2){$('#salesBackupStatus').textContent='Nhập tên bộ dữ liệu ít nhất 2 ký tự.';return}
  $('#salesBackupYes').value='';$('#salesBackupCode').value='';$('#salesBackupError').textContent='';
  $('#salesBackupModal').hidden=false;$('#salesBackupYes').focus();
}
function closeSalesBackupModal(){
  if(SALES_BACKUP_BUSY)return;
  $('#salesBackupCode').value='';$('#salesBackupModal').hidden=true;
}
async function submitSalesBackup(){
  if(SALES_BACKUP_BUSY)return;
  if($('#salesBackupYes').value.trim()!=='YES'){$('#salesBackupError').textContent='Vui lòng nhập chính xác YES.';return}
  const code=$('#salesBackupCode').value,name=$('#salesBackupName').value.trim();
  if(!code){$('#salesBackupError').textContent='Cần nhập mã quản trị.';return}
  SALES_BACKUP_BUSY=true;$('#salesBackupSubmit').disabled=true;
  backup();
  $('#salesBackupStatus').textContent='Đã tải JSON về máy. Đang lưu server…';
  try{
    const res=await fetch(SALES_DATASET_URL,{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||'','X-Demo-Backup-Code':code},body:JSON.stringify({name,data:S})});
    if(!res.ok){
      const messages={403:'Mã quản trị không đúng.',413:'JSON quá lớn.',419:'Phiên hết hạn, tải lại trang.',429:'Thao tác quá nhanh.',503:'Server chưa cấu hình mã quản trị.',409:'Thư viện đã đầy.'};
      throw new Error(messages[res.status]||'HTTP '+res.status);
    }
    const saved=await res.json();
    $('#salesBackupStatus').textContent='Đã tải JSON về máy và lưu server: '+saved.name;
    $('#salesBackupModal').hidden=true;
    toast('Sao lưu server thành công');
    await renderSalesDemoLibrary();
  }catch(e){
    $('#salesBackupError').textContent=e.message;
    $('#salesBackupStatus').textContent='Đã tải JSON về máy nhưng CHƯA lưu lên server: '+e.message;
  }finally{$('#salesBackupCode').value='';SALES_BACKUP_BUSY=false;$('#salesBackupSubmit').disabled=false}
}
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!$('#salesBackupModal')?.hidden)closeSalesBackupModal()});
function saveCompany(){S.company={...co(),name:$('#coName').value.trim(),dept:$('#coDept').value.trim(),addr:$('#coAddr').value.trim(),phone:$('#coPhone').value.trim()};if(APP.saveExtra)APP.saveExtra();save();toast('Đã lưu thông tin đơn vị')}
function backup(){const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([JSON.stringify(S)],{type:'application/json'}));a.download=`Sao luu ${APP.title} ${TODAYS}.json`;document.body.appendChild(a);a.click();setTimeout(()=>a.remove(),1000);toast('Đã tải file sao lưu')}
function resetSample(){if(!confirm('Thay toàn bộ dữ liệu hiện tại bằng dữ liệu mẫu?'))return;const c=S.company;S=APP.seed();if(c)S.company=c;save();toast('Đã nạp dữ liệu mẫu');rerender()}
function clearAll(){if(!confirm('Xóa SẠCH toàn bộ dữ liệu? Nên tải file sao lưu trước khi xóa.'))return;const c=S.company;S=APP.empty();if(c)S.company=c;save();toast('Đã xóa toàn bộ dữ liệu, bắt đầu nhập dữ liệu thật nhé');rerender()}
function renderGuide(){const g=APP.guide;
  $('#page-guide').innerHTML=`<div class="guide"><div class="card"><h3>File này dùng để làm gì?</h3><p>${g.what}</p><h3 style="margin-top:18px">Các bước sử dụng</h3><ol class="steps">${g.steps.map(s=>`<li><b>${s[0]}</b><span>${s[1]}</span></li>`).join('')}</ol>${g.more||''}</div>
  <div><div class="card"><h3>Lưu dữ liệu demo</h3><p>Dữ liệu được tự động lưu riêng trên trình duyệt này. Bấm <b>Lưu dữ liệu</b> hoặc <span class="kbd">Ctrl</span> + <span class="kbd">S</span> để xác nhận.</p><p>Để chuyển dữ liệu sang thiết bị khác hoặc tránh mất dữ liệu khi xóa bộ nhớ trình duyệt, vào Cài đặt &amp; dữ liệu và tải file sao lưu JSON.</p>
   <h3 style="margin-top:16px">Xuất Excel</h3><p>Nút <b>Xuất Excel</b> trên thanh trên cùng tải về file .xlsx gồm mọi bảng${g.xls?', '+g.xls:''}. Mỗi trang danh sách cũng có nút Xuất Excel riêng, xuất đúng các dòng đang lọc.</p>
   <div class="note-box">Đây là <b>dữ liệu mẫu</b> để xem thử. Trước khi dùng thật, vào <b>Cài đặt &amp; dữ liệu</b>, bấm <b>Xóa dữ liệu mẫu</b>, rồi nhập dữ liệu của mình.</div>
   ${g.lite?`<div class="note-box blue">${g.lite}</div>`:''}</div>
   <div class="gift">File mẫu tặng học viên lớp Excel cuối tuần - thầy Phong Excel</div></div></div>`}

/* =========================================================
   KHỞI ĐỘNG
   ========================================================= */
function boot(){
  let preferredTheme='ruby';
  try{preferredTheme=localStorage.getItem(SALES_THEME_STORAGE_KEY)||'ruby'}catch(e){}
  applySalesTheme(preferredTheme);
  const base=APP.valid(EMB)?EMB:APP.seed();
  let draft=null;try{draft=JSON.parse(localStorage.getItem(LS))}catch(e){}
  S=APP.valid(draft)?draft:base;SAVED_AT=S.savedAt||S.updated||0;
  $('#today').textContent=TODAY.toLocaleDateString('vi-VN',{weekday:'long',day:'2-digit',month:'2-digit',year:'numeric'});
  $$('#nav a').forEach(a=>{const ic=IC[a.dataset.ic];if(ic)a.insertAdjacentHTML('afterbegin',ic);a.onclick=()=>go(a.dataset.page)});
  $('#burger').onclick=()=>{$('#sb').classList.add('open');$('#mask').classList.add('show')};
  $('#mask').onclick=()=>{$('#sb').classList.remove('open');$('#mask').classList.remove('show')};
  $('#ov').addEventListener('mousedown',e=>{if(e.target.id==='ov')closeModal()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
  $$('[data-icon]').forEach(e=>e.insertAdjacentHTML('afterbegin',IC[e.dataset.icon]||''));
  refreshChrome();refreshSave();
  go((location.hash||'#dashboard').slice(1));

}

boot();
