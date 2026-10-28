// Browser-first HR demo; never overwrite rendered Blade/HTML.\nconst KEY = 'qlns_data_v2';\nconst DEPTS = ['Ban Giám đốc','Kế toán','Nhân sự','Kinh doanh','Marketing','Kho vận','IT'];
const STATUSES = [['Đang làm','#22c55e'],['Thử việc','#3b82f6'],['Nghỉ phép','#f59e0b'],['Đã nghỉ việc','#94a3b8']];
const photo = (g, n) => `https://randomuser.me/api/portraits/${g === 'Nữ' ? 'women' : 'men'}/${n}.jpg`;

const SAMPLE = [
  ['NV001','Nguyễn Văn An','Nam','1982-03-15','Ban Giám đốc','Giám đốc','0912345678','an.nguyen@congty.vn','2015-01-05',45000000,'Đang làm',32],
  ['NV002','Trần Thị Bích Ngọc','Nữ','1988-07-22','Kế toán','Kế toán trưởng','0903456789','ngoc.tran@congty.vn','2016-04-12',28000000,'Đang làm',44],
  ['NV003','Lê Minh Tuấn','Nam','1990-11-02','Kinh doanh','Trưởng phòng KD','0987654321','tuan.le@congty.vn','2017-06-01',30000000,'Đang làm',45],
  ['NV004','Phạm Thu Hà','Nữ','1993-05-18','Nhân sự','Chuyên viên tuyển dụng','0934567890','ha.pham@congty.vn','2019-09-16',16000000,'Nghỉ phép',65],
  ['NV005','Hoàng Đức Thắng','Nam','1995-12-09','IT','Lập trình viên','0976543210','thang.hoang@congty.vn','2020-02-10',22000000,'Đang làm',51],
  ['NV006','Vũ Ngọc Lan','Nữ','1996-08-30','Marketing','Chuyên viên Marketing','0945678901','lan.vu@congty.vn','2021-03-01',15000000,'Đang làm',68],
  ['NV007','Đặng Quốc Bảo','Nam','1991-01-25','Kho vận','Thủ kho','0967890123','bao.dang@congty.vn','2018-07-20',13000000,'Đang làm',75],
  ['NV008','Bùi Thị Mai','Nữ','1998-04-14','Kế toán','Kế toán viên','0918765432','mai.bui@congty.vn','2022-05-09',12000000,'Đang làm',79],
  ['NV009','Đỗ Hoàng Long','Nam','1999-10-03','Kinh doanh','Nhân viên kinh doanh','0929876543','long.do@congty.vn','2024-08-01',10000000,'Thử việc',22],
  ['NV010','Ngô Thanh Hương','Nữ','1994-02-11','Nhân sự','Trưởng phòng Nhân sự','0908123456','huong.ngo@congty.vn','2017-11-13',25000000,'Đang làm',26],
  ['NV011','Phan Văn Khánh','Nam','1987-06-27','Kho vận','Trưởng kho','0938234567','khanh.phan@congty.vn','2016-10-03',20000000,'Đang làm',60],
  ['NV012','Lý Thị Thảo','Nữ','2000-09-19','Marketing','Thiết kế đồ họa','0948345678','thao.ly@congty.vn','2025-01-06',11000000,'Thử việc',90],
  ['NV013','Trịnh Công Sơn','Nam','1992-12-01','IT','Quản trị hệ thống','0958456789','son.trinh@congty.vn','2019-04-22',21000000,'Đã nghỉ việc',11],
  ['NV014','Mai Phương Thảo','Nữ','1997-03-08','Kinh doanh','Nhân viên kinh doanh','0968567890','thao.mai@congty.vn','2023-02-15',12500000,'Đang làm',33],
].map(r => ({code:r[0],name:r[1],gender:r[2],dob:r[3],dept:r[4],role:r[5],phone:r[6],email:r[7],start:r[8],salary:r[9],status:r[10],photo:photo(r[2],r[11])}));

// Dữ liệu đã lưu trong file (nếu có) và dấu thời gian của lần lưu đó
const FILE = (() => { try{ return JSON.parse(document.getElementById('savedData').textContent); }catch(e){ return null; } })();
let fileStamp = FILE ? FILE.savedAt : null;
let dirty = false;

let data = load();
let page = 'dashboard';
let view = 'card';
let sortKey = 'code', sortAsc = true;
/* Chia trang danh sách nhân viên */
let pgPage = 0, pgSizeN = 20, pgSig = '', printAll = false;
const PG_SIZES = [10, 20, 50, 100];
function pgGo(p){ pgPage = p; renderList(); window.scrollTo({top:0, behavior:'smooth'}); }
function pgSize(n){ pgSizeN = +n; pgPage = 0; renderList(); }
window.addEventListener('beforeprint', () => { printAll = true; if(page === 'employees') renderList(); });
window.addEventListener('afterprint', () => { printAll = false; if(page === 'employees') renderList(); });
function pgBar(total){
  if(printAll) return '';
  const pages = Math.max(1, Math.ceil(total / pgSizeN)), p = pgPage;
  const from = total ? p * pgSizeN + 1 : 0, to = Math.min(total, (p + 1) * pgSizeN);
  const b = i => `<button class="${i === p ? 'on' : ''}" onclick="pgGo(${i})">${i + 1}</button>`;
  let nums = '';
  if(pages <= 7) for(let i = 0; i < pages; i++) nums += b(i);
  else { nums += b(0); const a = Math.max(1, p - 1), e = Math.min(pages - 2, p + 1); if(a > 1) nums += '<span>…</span>'; for(let i = a; i <= e; i++) nums += b(i); if(e < pages - 2) nums += '<span>…</span>'; nums += b(pages - 1); }
  return `<div class="pgbar"><span>Hiển thị <b>${from}-${to}</b> / ${total} nhân viên</span><span class="pgs">Số dòng/trang<select onchange="pgSize(this.value)">${PG_SIZES.map(n => `<option ${n === pgSizeN ? 'selected' : ''}>${n}</option>`).join('')}</select>${pages > 1 ? `<button ${p ? '' : 'disabled'} onclick="pgGo(${p - 1})">‹</button>${nums}<button ${p < pages - 1 ? '' : 'disabled'} onclick="pgGo(${p + 1})">›</button>` : ''}</span></div>`;
}
let editing = null, tempPhoto = '';

// Ưu tiên dữ liệu trong file. Chỉ lấy bản trong trình duyệt khi đó là phần sửa tiếp trên đúng bản file này mà chưa bấm Lưu.
function load(){
  try {
    const saved=JSON.parse(localStorage.getItem(KEY));
    if (saved && Array.isArray(saved.data)) return saved.data;
    const legacy=JSON.parse(localStorage.getItem('qlns_data_v1'));
    if (Array.isArray(legacy?.data)) return legacy.data;
    if (Array.isArray(legacy)) return legacy;
  } catch(e) { console.warn('Không đọc được dữ liệu cục bộ', e); }
  if (FILE && Array.isArray(FILE.data)) return JSON.parse(JSON.stringify(FILE.data));
  return JSON.parse(JSON.stringify(SAMPLE));
}
function save(){
  dirty = true;
  try { localStorage.setItem(KEY, JSON.stringify({data, savedAt:new Date().toISOString()})); }
  catch(e) { toast('Bộ nhớ trình duyệt đầy: hãy xuất JSON để sao lưu'); }
  updateSaveBtn();
}

const $ = id => document.getElementById(id);
const money = n => (Number(n)||0).toLocaleString('vi-VN') + ' đ';
const fmtDate = d => d ? d.split('-').reverse().join('/') : '';
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const initials = name => name.trim().split(/\s+/).slice(-2).map(w => w[0]).join('').toUpperCase();
function fallbackAvatar(name){
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#22c55e"/><text x="60" y="74" font-size="44" font-family="Arial" fill="#fff" text-anchor="middle">${initials(name||'?')}</text></svg>`;
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
}
function img(e, cls='avatar'){
  return `<img class="${cls}" src="${esc(e.photo || fallbackAvatar(e.name))}" alt="${esc(e.name)}" onerror="this.onerror=null;this.src='${fallbackAvatar(e.name)}'">`;
}
const badgeCls = s => ({'Đang làm':'b-lam','Nghỉ phép':'b-phep','Thử việc':'b-thu','Đã nghỉ việc':'b-nghi'}[s] || 'b-nghi');
function seniority(start){
  if(!start) return '';
  const m = Math.floor((Date.now() - new Date(start)) / (1000*60*60*24*30.44));
  return m >= 12 ? `${Math.floor(m/12)} năm ${m%12} tháng` : `${m} tháng`;
}
function age(dob){ if(!dob) return ''; return Math.floor((Date.now() - new Date(dob)) / (1000*60*60*24*365.25)); }

/* ---------- Điều hướng ---------- */
function goPage(p){
  page = p;
  ['dashboard','analytics','employees'].forEach(x => $('page-' + x).hidden = p !== x);
  toggleSide(false);
  window.scrollTo(0,0);
  render();
}
function goFilter(dept, status){
  $('fDept').value = dept; $('fStatus').value = status; $('q').value = '';
  goPage('employees');
}
function toggleSide(open){
  $('sidebar').classList.toggle('open', open);
  $('overlay').classList.toggle('show', open);
}

function renderNav(){
  const d = $('fDept').value, s = $('fStatus').value, onList = page === 'employees';
  const active = data.filter(e => e.status !== 'Đã nghỉ việc');
  $('navDash').classList.toggle('active', page === 'dashboard');
  $('navAnalytics').classList.toggle('active', page === 'analytics');
  $('navAll').classList.toggle('active', onList && !d && !s);
  $('cntAll').textContent = data.length;
  $('navDept').innerHTML = DEPTS.map(x => `
    <a class="item ${onList && d===x && !s ? 'active':''}" onclick="goFilter('${x}','')">
      <span class="ic">🏢</span>${x}<span class="cnt">${active.filter(e=>e.dept===x).length}</span></a>`).join('');
  $('navStatus').innerHTML = STATUSES.map(([x,c]) => `
    <a class="item ${onList && s===x && !d ? 'active':''}" onclick="goFilter('','${x}')">
      <span class="ic"><span class="dot" style="background:${c}"></span></span>${x}<span class="cnt">${data.filter(e=>e.status===x).length}</span></a>`).join('');

  if(page === 'dashboard'){
    $('pageTitle').textContent = 'Tổng quan';
    $('pageSub').textContent = 'Tình hình nhân sự công ty hôm nay';
  } else if(page === 'analytics'){
    $('pageTitle').textContent = 'Dashboard';
    $('pageSub').textContent = 'Phân tích số liệu nhân sự bằng biểu đồ';
  } else {
    $('pageTitle').textContent = d || s || 'Danh sách nhân viên';
    $('pageSub').textContent = d ? 'Nhân viên thuộc phòng ' + d : s ? 'Nhân viên có trạng thái: ' + s : 'Toàn bộ hồ sơ nhân viên';
  }
}

/* ---------- Trang tổng quan ---------- */
function renderDashboard(){
  const active = data.filter(e => e.status !== 'Đã nghỉ việc');
  const fund = active.reduce((s,e) => s + (Number(e.salary)||0), 0);
  $('stats').innerHTML = `
    <div class="stat" onclick="goFilter('','Đang làm')"><div class="si" style="background:#ecfdf3">👥</div><div><div class="lb">Tổng nhân sự đang làm</div><div class="vl">${active.length}</div></div></div>
    <div class="stat" onclick="goFilter('','Thử việc')"><div class="si" style="background:#eff6ff">🌱</div><div><div class="lb">Đang thử việc</div><div class="vl">${data.filter(e=>e.status==='Thử việc').length}</div></div></div>
    <div class="stat" onclick="goFilter('','Nghỉ phép')"><div class="si" style="background:#fffbeb">🏖️</div><div><div class="lb">Đang nghỉ phép</div><div class="vl">${data.filter(e=>e.status==='Nghỉ phép').length}</div></div></div>
    <div class="stat" onclick="goFilter('','')"><div class="si" style="background:#f5f3ff">💰</div><div><div class="lb">Quỹ lương tháng</div><div class="vl" style="font-size:19px">${money(fund)}</div></div></div>`;

  const max = Math.max(1, ...DEPTS.map(d => active.filter(e=>e.dept===d).length));
  $('deptBars').innerHTML = DEPTS.map(d => {
    const n = active.filter(e=>e.dept===d).length;
    return `<div class="bar-row" onclick="goFilter('${d}','')"><span>${d}</span><div class="bar-bg"><div class="bar-fill" style="width:${n/max*100}%"></div></div><b>${n}</b></div>`;
  }).join('');

  const recent = [...data].filter(e => e.start).sort((a,b) => b.start.localeCompare(a.start)).slice(0,5);
  $('recent').innerHTML = recent.map(e => `
    <div class="mini" onclick="showDetail('${e.code}')">${img(e,'avatar avatar-sm')}
      <div class="t"><b>${esc(e.name)}</b><small>${esc(e.role)} · ${esc(e.dept)}</small></div>
      <div class="r">${fmtDate(e.start)}</div></div>`).join('') || '<div class="empty">Chưa có dữ liệu</div>';

  const m = new Date().getMonth() + 1;
  $('bdTitle').textContent = `🎂 Sinh nhật tháng ${m}`;
  const bd = data.filter(e => e.dob && +e.dob.slice(5,7) === m && e.status !== 'Đã nghỉ việc')
                 .sort((a,b) => a.dob.slice(8).localeCompare(b.dob.slice(8)));
  $('birthdays').innerHTML = bd.map(e => `
    <div class="mini" onclick="showDetail('${e.code}')">${img(e,'avatar avatar-sm')}
      <div class="t"><b>${esc(e.name)}</b><small>${esc(e.dept)} · tròn ${new Date().getFullYear() - +e.dob.slice(0,4)} tuổi</small></div>
      <div class="r">Ngày ${e.dob.slice(8)}/${e.dob.slice(5,7)}</div></div>`).join('') || '<div class="empty">Tháng này không có ai sinh nhật</div>';
}

/* ---------- Trang Dashboard ---------- */
const trieu = n => (n/1e6).toLocaleString('vi-VN', {maximumFractionDigits:1});
const avg = arr => arr.length ? arr.reduce((a,b) => a+b, 0) / arr.length : 0;
const fmt1 = n => n.toLocaleString('vi-VN', {maximumFractionDigits:1});

// Biểu đồ vòng: segs = [{label, value, color}]
function donut(segs, centerLabel){
  const total = segs.reduce((a,b) => a + b.value, 0);
  const r = 62, C = 2 * Math.PI * r;
  let acc = 0;
  const arcs = total ? segs.filter(x => x.value > 0).map(x => {
    const len = x.value / total * C;
    const gap = segs.filter(y => y.value > 0).length > 1 ? 2 : 0;
    const el = `<circle class="seg" r="${r}" cx="80" cy="80" fill="none" stroke="${x.color}" stroke-width="24"
      stroke-dasharray="${Math.max(len - gap, 0)} ${C}" stroke-dashoffset="${-acc}" transform="rotate(-90 80 80)">
      <title>${x.label}: ${x.value} người (${Math.round(x.value/total*100)}%)</title></circle>`;
    acc += len; return el;
  }).join('') : `<circle r="${r}" cx="80" cy="80" fill="none" stroke="#eef2f6" stroke-width="24"/>`;
  return `<svg width="160" height="160" viewBox="0 0 160 160" role="img" aria-label="${centerLabel}">${arcs}
      <text x="80" y="78" text-anchor="middle" font-size="30" font-weight="700" fill="#0f172a">${total}</text>
      <text x="80" y="100" text-anchor="middle" font-size="13" fill="#475569">${centerLabel}</text></svg>
    <div class="legend">${segs.map(x => `<div class="lg"><span class="sw" style="background:${x.color}"></span>${x.label}
      <b>${x.value}<small>${total ? Math.round(x.value/total*100) : 0}%</small></b></div>`).join('')}</div>`;
}

// Biểu đồ cột: items = [{label, value}]
function columns(items, color){
  const max = Math.max(1, ...items.map(x => x.value));
  return items.map(x => `<div class="col">
      <div class="col-area"><div class="v">${x.value}</div>
        <div class="cbar" style="height:${x.value/max*150}px;background:${color}" title="${x.label}: ${x.value} người"></div></div>
      <div class="lbl">${x.label}</div></div>`).join('');
}

function renderAnalytics(){
  const dd = $('dbDept').value;
  const all = data.filter(e => !dd || e.dept === dd);
  const act = all.filter(e => e.status !== 'Đã nghỉ việc');
  const nam = act.filter(e => e.gender === 'Nam').length, nu = act.length - nam;
  const ages = act.filter(e => e.dob).map(e => age(e.dob));
  const yrs = act.filter(e => e.start).map(e => (Date.now() - new Date(e.start)) / (1000*60*60*24*365.25));
  const left = all.filter(e => e.status === 'Đã nghỉ việc').length;

  $('kpis').innerHTML = `
    <div class="kpi"><div class="lb">Nhân sự đang làm</div><div class="vl">${act.length}</div><div class="sub">${nam} nam · ${nu} nữ</div></div>
    <div class="kpi"><div class="lb">Lương trung bình</div><div class="vl">${trieu(avg(act.map(e => +e.salary||0)))} tr</div><div class="sub">mỗi người / tháng</div></div>
    <div class="kpi"><div class="lb">Tuổi trung bình</div><div class="vl">${fmt1(avg(ages))}</div><div class="sub">tuổi</div></div>
    <div class="kpi"><div class="lb">Thâm niên trung bình</div><div class="vl">${fmt1(avg(yrs))}</div><div class="sub">năm</div></div>
    <div class="kpi"><div class="lb">Tỷ lệ nghỉ việc</div><div class="vl">${all.length ? Math.round(left/all.length*100) : 0}%</div><div class="sub">${left} / ${all.length} người</div></div>`;

  // Quỹ lương theo phòng ban (chỉ người đang làm)
  const depts = dd ? [dd] : DEPTS;
  const sal = depts.map(d => ({d, v: act.filter(e => e.dept === d).reduce((a,e) => a + (+e.salary||0), 0)})).sort((a,b) => b.v - a.v);
  const smax = Math.max(1, ...sal.map(x => x.v));
  $('chSalary').innerHTML = sal.map(x => `<div class="hb-row"><span>${x.d}</span>
      <div class="hb-track"><div class="hb-bar" style="width:${x.v/smax*80}%" title="${x.d}: ${money(x.v)}"></div><span class="hb-val">${trieu(x.v)}</span></div></div>`).join('');

  $('chStatus').innerHTML = donut(STATUSES.map(([label, color]) => ({label, color, value: all.filter(e => e.status === label).length})), 'nhân viên');
  $('chGender').innerHTML = donut([{label:'Nam', value:nam, color:'var(--c1)'}, {label:'Nữ', value:nu, color:'var(--c2)'}], 'đang làm');

  const bins = [['Dưới 25',0,24],['25 - 29',25,29],['30 - 34',30,34],['35 - 39',35,39],['Từ 40',40,200]];
  $('chAge').innerHTML = columns(bins.map(([label,a,b]) => ({label, value: ages.filter(x => x >= a && x <= b).length})), 'var(--c1)');

  const years = all.filter(e => e.start).map(e => +e.start.slice(0,4));
  const y0 = years.length ? Math.min(...years) : new Date().getFullYear(), y1 = new Date().getFullYear();
  const hire = [];
  for(let y = y0; y <= y1; y++) hire.push({label: String(y), value: years.filter(x => x === y).length});
  $('chHire').innerHTML = columns(hire, 'var(--c3)');

  const top = [...act].sort((a,b) => b.salary - a.salary).slice(0,5);
  $('chTop').innerHTML = top.map((e,i) => `<div class="mini" onclick="showDetail('${e.code}')">
      <span class="rank">${i+1}</span>${img(e,'avatar avatar-sm')}
      <div class="t"><b>${esc(e.name)}</b><small>${esc(e.role)} · ${esc(e.dept)}</small></div>
      <div class="r">${money(e.salary)}</div></div>`).join('') || '<div class="empty">Chưa có dữ liệu</div>';
}

/* ---------- Trang danh sách ---------- */
function filtered(){
  const q = $('q').value.trim().toLowerCase();
  const d = $('fDept').value;
  const s = $('fStatus').value;
  return data.filter(e =>
    (!d || e.dept === d) && (!s || e.status === s) &&
    (!q || [e.code,e.name,e.phone,e.email,e.role].join(' ').toLowerCase().includes(q))
  ).sort((a,b) => {
    let x = a[sortKey], y = b[sortKey];
    if(sortKey === 'salary'){ x = +x; y = +y; }
    return (x > y ? 1 : x < y ? -1 : 0) * (sortAsc ? 1 : -1);
  });
}

function renderList(){
  const list = filtered();
  // đổi bộ lọc hoặc cách sắp xếp thì quay về trang 1
  const sig = [$('q').value, $('fDept').value, $('fStatus').value, sortKey, sortAsc, view].join('|');
  if(sig !== pgSig){ pgSig = sig; pgPage = 0; }
  const pages = Math.max(1, Math.ceil(list.length / pgSizeN));
  if(pgPage >= pages) pgPage = pages - 1;
  const shown = printAll ? list : list.slice(pgPage * pgSizeN, pgPage * pgSizeN + pgSizeN);
  $('count').textContent = `Tìm thấy ${list.length} / ${data.length} nhân viên`;
  const box = $('list');
  if(!list.length){ box.innerHTML = '<div class="empty panel">Không tìm thấy nhân viên phù hợp.</div>'; return; }

  if(view === 'card'){
    box.innerHTML = '<div class="grid">' + shown.map(e => `
      <div class="card">
        <span class="code">${esc(e.code)}</span>
        <span class="badge ${badgeCls(e.status)}">${esc(e.status)}</span>
        ${img(e)}
        <h3>${esc(e.name)}</h3>
        <div class="role">${esc(e.role)}</div>
        <span class="dept">${esc(e.dept)}</span>
        <div style="font-size:12px;color:var(--muted);margin-top:8px">📞 ${esc(e.phone)}</div>
        <div class="actions">
          <button class="btn btn-light btn-sm" onclick="showDetail('${e.code}')">Xem</button>
          <button class="btn btn-light btn-sm" onclick="openForm('${e.code}')">Sửa</button>
          <button class="btn btn-danger btn-sm" onclick="del('${e.code}')">Xóa</button>
        </div>
      </div>`).join('') + '</div>' + pgBar(list.length);
  } else {
    const th = (k, t, cls='') => `<th class="${cls}" onclick="sortBy('${k}')">${t}${sortKey===k ? (sortAsc?' ▲':' ▼') : ''}</th>`;
    box.innerHTML = `<div class="table-wrap"><table>
      <thead><tr>${th('code','Mã NV')}${th('name','Họ tên')}${th('dept','Phòng ban')}${th('role','Chức vụ')}${th('phone','Điện thoại')}${th('start','Ngày vào')}${th('salary','Lương','num')}${th('status','Trạng thái')}<th></th></tr></thead>
      <tbody>${shown.map(e => `<tr>
        <td><b>${esc(e.code)}</b></td>
        <td><div class="name-cell">${img(e,'avatar avatar-sm')}<span>${esc(e.name)}</span></div></td>
        <td>${esc(e.dept)}</td><td>${esc(e.role)}</td><td>${esc(e.phone)}</td>
        <td>${fmtDate(e.start)}</td><td class="num">${money(e.salary)}</td>
        <td><span class="badge ${badgeCls(e.status)}">${esc(e.status)}</span></td>
        <td><button class="btn btn-light btn-sm" onclick="showDetail('${e.code}')">Xem</button>
            <button class="btn btn-light btn-sm" onclick="openForm('${e.code}')">Sửa</button>
            <button class="btn btn-danger btn-sm" onclick="del('${e.code}')">Xóa</button></td>
      </tr>`).join('')}</tbody></table></div>${pgBar(list.length)}`;
  }
}

function render(){
  renderNav();
  if(page === 'dashboard') renderDashboard();
  else if(page === 'analytics') renderAnalytics();
  else renderList();
}

function setView(v){
  view = v;
  $('vCard').classList.toggle('on', v==='card');
  $('vTable').classList.toggle('on', v==='table');
  render();
}
function sortBy(k){ if(sortKey === k) sortAsc = !sortAsc; else { sortKey = k; sortAsc = true; } render(); }

/* ---------- Thêm / sửa / xóa ---------- */
function nextCode(){
  const max = data.reduce((m,e) => Math.max(m, parseInt(e.code.replace(/\D/g,''))||0), 0);
  return 'NV' + String(max + 1).padStart(3,'0');
}

function openForm(code){
  editing = code || null;
  const e = code ? data.find(x => x.code === code) : {code:nextCode(),name:'',gender:'Nam',dob:'',dept:$('fDept').value || DEPTS[1],role:'',phone:'',email:'',start:new Date().toISOString().slice(0,10),salary:'',status:'Thử việc',photo:''};
  $('formTitle').textContent = code ? 'Sửa thông tin nhân viên' : 'Thêm nhân viên mới';
  const set = (id,v) => $(id).value = v ?? '';
  set('fCode',e.code); set('fName',e.name); set('fGender',e.gender); set('fDob',e.dob);
  set('fDeptIn',e.dept); set('fRole',e.role); set('fPhone',e.phone); set('fEmail',e.email);
  set('fStart',e.start); set('fSalary',e.salary); set('fStatusIn',e.status);
  $('fPhoto').value = '';
  tempPhoto = e.photo || '';
  const pv = $('fPhotoPreview');
  pv.onerror = () => { pv.onerror = null; pv.src = fallbackAvatar(e.name || '?'); };
  pv.src = tempPhoto || fallbackAvatar(e.name || '?');
  closeModal('detailModal');
  toggleSide(false);
  $('formModal').classList.add('show');
}

function loadPhoto(ev){
  const file = ev.target.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = () => {
    const im = new Image();
    im.onload = () => {
      // Cắt vuông giữa ảnh và thu về 240px cho nhẹ
      const size = 240, c = document.createElement('canvas');
      c.width = c.height = size;
      const s = Math.min(im.width, im.height);
      c.getContext('2d').drawImage(im, (im.width-s)/2, (im.height-s)/2, s, s, 0, 0, size, size);
      tempPhoto = c.toDataURL('image/jpeg', 0.85);
      $('fPhotoPreview').src = tempPhoto;
    };
    im.src = reader.result;
  };
  reader.readAsDataURL(file);
}

function saveForm(){
  const g = id => $(id).value.trim();
  if(!g('fName')){ toast('Vui lòng nhập họ tên'); return; }
  const e = {code:g('fCode'),name:g('fName'),gender:g('fGender'),dob:g('fDob'),dept:g('fDeptIn'),role:g('fRole'),
    phone:g('fPhone'),email:g('fEmail'),start:g('fStart'),salary:Number(g('fSalary'))||0,status:g('fStatusIn'),photo:tempPhoto};
  if(editing){ data[data.findIndex(x => x.code === editing)] = e; toast('Đã cập nhật ' + e.name); }
  else { data.push(e); toast('Đã thêm ' + e.name); }
  save(); closeModal('formModal'); render();
}

function del(code){
  const e = data.find(x => x.code === code);
  if(!confirm(`Xóa nhân viên ${e.name} (${code})?`)) return;
  data = data.filter(x => x.code !== code);
  save(); render(); toast('Đã xóa ' + e.name);
}

function showDetail(code){
  const e = data.find(x => x.code === code);
  $('detailBody').innerHTML = `
    <div class="detail">
      <div>${img(e)}<div style="text-align:center;margin-top:10px"><span class="badge ${badgeCls(e.status)}">${esc(e.status)}</span></div></div>
      <div>
        <h2 style="margin:0 0 4px">${esc(e.name)}</h2>
        <div style="color:var(--muted);margin-bottom:14px">${esc(e.role)} · ${esc(e.dept)}</div>
        <dl>
          <dt>Mã nhân viên</dt><dd>${esc(e.code)}</dd>
          <dt>Giới tính</dt><dd>${esc(e.gender)}</dd>
          <dt>Ngày sinh</dt><dd>${fmtDate(e.dob)} ${e.dob ? `(${age(e.dob)} tuổi)` : ''}</dd>
          <dt>Điện thoại</dt><dd>${esc(e.phone)}</dd>
          <dt>Email</dt><dd>${esc(e.email)}</dd>
          <dt>Ngày vào làm</dt><dd>${fmtDate(e.start)}</dd>
          <dt>Thâm niên</dt><dd>${seniority(e.start)}</dd>
          <dt>Lương cơ bản</dt><dd>${money(e.salary)}</dd>
        </dl>
      </div>
    </div>`;
  $('detailEdit').onclick = () => openForm(code);
  $('detailModal').classList.add('show');
}

function closeModal(id){ $(id).classList.remove('show'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', ev => { if(ev.target === m) m.classList.remove('show'); }));
document.addEventListener('keydown', ev => { if(ev.key === 'Escape'){ document.querySelectorAll('.modal-bg.show').forEach(m => m.classList.remove('show')); toggleSide(false); } });

/* ---------- Tiện ích ---------- */
function exportCSV(){
  const head = ['Mã NV','Họ tên','Giới tính','Ngày sinh','Phòng ban','Chức vụ','Điện thoại','Email','Ngày vào làm','Lương','Trạng thái'];
  const rows = (page === 'employees' ? filtered() : data).map(e => [e.code,e.name,e.gender,fmtDate(e.dob),e.dept,e.role,"'"+e.phone,e.email,fmtDate(e.start),e.salary,e.status]);
  const csv = '﻿' + [head, ...rows].map(r => r.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\r\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], {type:'text/csv;charset=utf-8'}));
  a.download = 'Danh_sach_nhan_vien.csv';
  a.click();
  toggleSide(false);
  toast('Đã xuất file, mở bằng Excel được ngay');
}

function resetData(){
  if(!confirm('Đưa về dữ liệu mẫu ban đầu? Mọi thay đổi sẽ mất.')) return;
  data = JSON.parse(JSON.stringify(SAMPLE)); save(); toggleSide(false); render(); toast('Đã nạp lại dữ liệu mẫu');
}

/* ---------- Sao lưu JSON; thư viện server không ghi đè file HTML ---------- */
function hrPayload(name='Dữ liệu nhân sự') {
  return {name, savedAt:new Date().toISOString(), data};
}
function hrDownload(payload, filename) {
  const url=URL.createObjectURL(new Blob([JSON.stringify(payload,null,2)],{type:'application/json;charset=utf-8'}));
  const a=document.createElement('a');a.href=url;a.download=filename;a.click();
  setTimeout(()=>URL.revokeObjectURL(url),1000);
}
function saveFile(){
  toggleSide(false);
  hrDownload(hrPayload(), 'Quan_ly_nhan_su_'+new Date().toISOString().slice(0,10)+'.json');
  dirty=false;updateSaveBtn();toast('Đã tải bản sao lưu JSON');
}
function updateSaveBtn(){
  const b=$('btnSave');if(!b)return;
  b.textContent=dirty?'💾 Xuất JSON •':'💾 Xuất JSON';
  b.title='Sao lưu dữ liệu thành file JSON (Ctrl+S)';
}
function hrValidate(rows){
  if(!Array.isArray(rows)||rows.length>10000)return false;
  const ids=new Set();
  return rows.every(e=>{
    if(!e||typeof e!=='object'||typeof e.code!=='string'||!e.code||ids.has(e.code))return false;
    ids.add(e.code);
    return ['name','gender','dob','dept','role','phone','email','start','status'].every(k=>typeof e[k]==='string')
      && Number.isFinite(Number(e.salary)) && Number(e.salary)>=0
      && (e.photo===undefined || (typeof e.photo==='string'&&e.photo.length<=2000000));
  });
}
function hrRestore(payload){
  if(!hrValidate(payload?.data))throw Error('Dữ liệu nhân viên không hợp lệ.');
  if(!confirm('Thay thế toàn bộ dữ liệu nhân sự hiện tại?'))return;
  data=JSON.parse(JSON.stringify(payload.data));save();render();toast('Đã khôi phục dữ liệu JSON');
}
async function hrImportFile(input){
  const file=input.files?.[0];if(!file)return;
  try{
    if(file.size>10485760)throw Error('File vượt 10 MiB.');
    hrRestore(JSON.parse(await file.text()));
  }catch(e){toast(e.message);}finally{input.value='';}
}
async function openHrLibrary(){
  toggleSide(false);
  const dlg=$('hrLibrary');dlg.showModal();await hrRefreshLibrary();
}
async function hrRefreshLibrary(){
  const target=$('hrDatasetList');target.textContent='Đang tải...';
  try{
    const r=await fetch('/demo/quan-ly-nhan-su/datasets',{cache:'no-store'});
    if(!r.ok)throw Error('HTTP '+r.status);
    const {datasets}=await r.json();target.replaceChildren();
    if(!datasets.length)target.textContent='Chưa có dữ liệu trên server.';
    datasets.forEach(item=>{
      const b=document.createElement('button');b.type='button';b.className='btn btn-light';
      b.textContent=item.name+' ('+item.employees+' nhân viên)';
      b.onclick=async()=>{
        try{const res=await fetch('/demo/quan-ly-nhan-su/datasets/'+encodeURIComponent(item.id),{cache:'no-store'});
          if(!res.ok)throw Error('HTTP '+res.status);
          hrRestore(await res.json());$('hrLibrary').close();
        }catch(e){toast('Không thể nạp JSON: '+e.message);}
      };target.append(b);
    });
  }catch(e){target.textContent='Không tải được thư viện: '+e.message;}
}
async function hrStoreLibrary(){
  const code=$('hrBackupCode').value;
  const name=$('hrDatasetName').value.trim();
  if(!name||!code){toast('Nhập tên và mã quản trị');return;}
  try{
    const r=await fetch('/demo/quan-ly-nhan-su/datasets',{method:'POST',
      headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'X-Demo-Backup-Code':code},
      body:JSON.stringify({name,data})});
    if(!r.ok)throw Error('HTTP '+r.status);
    $('hrBackupCode').value='';toast('Đã lưu JSON lên server');await hrRefreshLibrary();
  }catch(e){toast('Không thể lưu JSON: '+e.message);}
}
const HR_THEMES={ruby:'#C62828',ocean:'#087E9B',emerald:'#16835D',indigo:'#4F46E5'};
function hrSetTheme(name){
  if(!Object.hasOwn(HR_THEMES,name))name='emerald';
  document.documentElement.style.setProperty('--primary',HR_THEMES[name]);
  document.documentElement.style.setProperty('--accent',HR_THEMES[name]);
  document.documentElement.dataset.hrTheme=name;
  try{localStorage.setItem('qlns_theme_v1',name);}catch(e){}
  const select=$('hrTheme');if(select)select.value=name;
}
document.addEventListener('keydown', ev => { if((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's'){ ev.preventDefault(); saveFile(); } });


function toast(msg){
  const t = $('toast');
  t.textContent = msg; t.classList.add('show');
  clearTimeout(t._h); t._h = setTimeout(() => t.classList.remove('show'), 2200);
}

// Khởi tạo
const opts = DEPTS.map(d => `<option>${d}</option>`).join('');
$('fDept').innerHTML += opts;
$('fDeptIn').innerHTML = opts;
$('dbDept').innerHTML += opts;
render();
updateSaveBtn();

hrSetTheme(localStorage.getItem('qlns_theme_v1')||'emerald');
