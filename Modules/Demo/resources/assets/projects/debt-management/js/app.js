
const THEME = {"p":"#ea580c","d":"#c2410c","s50":"#fff7ed","s100":"#ffedd5","ink":"#2a1206","ink2":"#4a1f0b"};

// Demo appearance is independent of business JSON.
const DEBT_THEMES = {
  original: { name: 'Cam nguyên bản', p: '#ea580c', d: '#c2410c', s50: '#fff7ed', s100: '#ffedd5', ink: '#2a1206', ink2: '#4a1f0b' },
  ruby: { name: 'Ruby', p: '#c62828', d: '#a51e25', s50: '#fff1f2', s100: '#ffe4e6', ink: '#350d15', ink2: '#551525' },
  ocean: { name: 'Ocean', p: '#087e9b', d: '#076782', s50: '#ecfeff', s100: '#cffafe', ink: '#082f49', ink2: '#164e63' },
  emerald: { name: 'Emerald', p: '#16835d', d: '#106b4b', s50: '#ecfdf5', s100: '#d1fae5', ink: '#052e25', ink2: '#064e3b' },
  indigo: { name: 'Indigo', p: '#4f46e5', d: '#4338ca', s50: '#eef2ff', s100: '#e0e7ff', ink: '#1e1b4b', ink2: '#312e81' }
};
const DEBT_THEME_KEY = 'debt_management_theme_v1';
let debtTheme = 'original';
function applyDebtTheme(name) {
  const t = DEBT_THEMES[name] || DEBT_THEMES.original;
  debtTheme = Object.hasOwn(DEBT_THEMES, name) ? name : 'original';
  for (const [key, val] of Object.entries({ '--p': t.p, '--p-d': t.d, '--p-50': t.s50, '--p-100': t.s100, '--ink': t.ink, '--ink2': t.ink2 })) {
    document.documentElement.style.setProperty(key, val);
  }
  THEME.p = t.p; THEME.d = t.d;
  THEME.s50 = t.s50; THEME.s100 = t.s100;
  THEME.ink = t.ink; THEME.ink2 = t.ink2;
  PALETTE[0] = t.p;
  try { localStorage.setItem(DEBT_THEME_KEY, debtTheme); } catch (e) {}
}
try { applyDebtTheme(localStorage.getItem(DEBT_THEME_KEY) || 'original'); } catch (e) { applyDebtTheme('original'); }
function debtThemeSelector() {
  return card('Giao diện · Màu sắc', `<p class="muted small">Chọn màu giao diện. Lựa chọn được lưu riêng, không ảnh hưởng dữ liệu công nợ.</p><div class="debt-themes">${Object.entries(DEBT_THEMES).map(([key, t]) => `<button type="button" class="debt-theme" data-debt-theme="${key}" aria-pressed="${debtTheme === key}"><span class="debt-swatch" style="background:${t.p}"></span>${esc(t.name)}</button>`).join('')}</div>`);
}
document.addEventListener('click', e => {
  const button = e.target.closest('[data-debt-theme]');
  if (!button) return;
  applyDebtTheme(button.dataset.debtTheme);
  document.querySelectorAll('[data-debt-theme]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.debtTheme === debtTheme)));
});

/* ==========================================================
   QUẢN LÝ CÔNG NỢ CHI TIẾT - Phong Excel
   Phải thu khách hàng, phải trả nhà cung cấp, tuổi nợ, đối chiếu
   ========================================================== */
addCSS(`
.sumbox{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.sumbox div{background:#fff;border:1px solid #e6e9f0;border-radius:12px;padding:12px 14px}
.sumbox small{display:block;color:#667085;font-size:12px}
.sumbox b{font-size:18px;font-variant-numeric:tabular-nums}
@media(max-width:900px){.sumbox{grid-template-columns:1fr 1fr}}
.filters{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
.filters label{display:flex;align-items:center;gap:6px;font-size:12.5px;color:#475467}
`);
const KH = 'Khách hàng', NCC = 'Nhà cung cấp';
const LOAI = { PS: 'Phát sinh nợ', TT: 'Thanh toán', GG: 'Giảm trừ / trả hàng' };

/* ---------- Dữ liệu ---------- */
function defaultSettings() {
  return { company: { ten: 'Công ty TNHH Phân phối Hưng Thịnh', diaChi: '58 Lê Văn Lương, Thanh Xuân, Hà Nội', sdt: '024 3556 7788', email: 'ketoan@hungthinh.vn', mst: '0108 456 123', website: 'hungthinh.vn', logo: '' }, hanNoMacDinh: 30, canhBao: 7 };
}
function emptyDB() { return { settings: defaultSettings(), partners: [], vouchers: [] }; }
function sampleDB() {
  const r = rng(1810), db = emptyDB(), today = D.today();
  const kh = ['Siêu thị Minh Phát', 'Cửa hàng Hoa Mai', 'Công ty CP Xây dựng Đông Á', 'Nhà sách Tân Việt', 'Đại lý Phúc Lộc', 'Công ty TNHH An Khang', 'Cửa hàng Thành Đạt', 'Công ty CP Thực phẩm Sao Việt', 'Đại lý Bình Minh', 'Siêu thị mini Hà Thành', 'Công ty TNHH Kim Long', 'Cửa hàng Ngọc Diệp', 'Nhà hàng Phố Biển', 'Công ty TNHH Nội thất Gia Huy', 'Đại lý Thiên Phú', 'Công ty CP Dược Bảo An', 'Khách sạn Hương Giang', 'Cửa hàng Vạn Xuân', 'Công ty TNHH Điện máy Nam Việt', 'Đại lý Hồng Phát', 'Trường mầm non Ánh Dương', 'Công ty TNHH May mặc Việt Tiến', 'Cửa hàng tiện lợi 24h', 'Công ty CP Logistics Tân Cảng', 'Đại lý Quang Minh', 'Nhà thuốc Phương Nam', 'Công ty TNHH Cơ khí Hòa Bình', 'Cửa hàng Tuấn Linh', 'Công ty CP Giáo dục Trí Việt', 'Đại lý Mai Anh'];
  const ncc = ['Công ty CP Sữa Việt Nam', 'Công ty TNHH Bao bì Tân Tiến', 'Nhà máy Giấy Bãi Bằng', 'Công ty CP Nhựa Bình Minh', 'Công ty TNHH Vận tải Hoàng Long', 'Tổng kho Đồ gia dụng Sunhouse', 'Công ty CP Điện Quang', 'Công ty TNHH Hóa chất Đức Giang', 'Công ty CP Thép Hòa Phát', 'Xưởng in Ánh Sáng'];
  const tinh = ['Hà Nội', 'Bắc Ninh', 'Hải Phòng', 'Hưng Yên', 'Nam Định', 'Thái Nguyên', 'Vĩnh Phúc', 'Quảng Ninh'];
  const add = (ten, loai, i) => {
    const p = { id: uid() + i, ma: (loai === KH ? 'KH' : 'NCC') + String(i + 1).padStart(3, '0'), ten, loai, nguoiLH: Sample.name(r), sdt: Sample.phone(r), email: '', diaChi: Sample.pick(r, tinh), mst: '01' + Sample.int(r, 10000000, 99999999), hanNo: Sample.pick(r, [15, 30, 30, 45, 60]), hanMuc: Sample.pick(r, [50, 100, 150, 200, 300]) * 1e6, noDauKy: Sample.int(r, 0, 8) * 5e6, ngayDauKy: D.add(today, -365), ghiChu: '' };
    db.partners.push(p); return p;
  };
  const K = kh.map((t, i) => add(t, KH, i)), N = ncc.map((t, i) => add(t, NCC, i));
  let sHD = 0, sPT = 0, sPN = 0, sPC = 0;
  for (let d = -360; d <= 0; d++) {
    const day = D.add(today, d); if (D.parse(day).getDay() === 0) continue;
    const nBan = Sample.int(r, 0, 3);
    for (let k = 0; k < nBan; k++) {
      const p = Sample.pick(r, K), amt = Sample.int(r, 3, 60) * 1e6 + Sample.int(r, 0, 9) * 1e5;
      db.vouchers.push({ id: uid() + db.vouchers.length, ngay: day, so: 'HD' + String(++sHD).padStart(5, '0'), partnerId: p.id, loai: 'PS', dienGiai: Sample.pick(r, ['Bán hàng theo đơn', 'Xuất hàng đợt', 'Bán hàng hóa', 'Giao hàng theo hợp đồng']), soTien: amt, hanTT: D.add(day, p.hanNo), hinhThuc: '' });
      // phần lớn khách trả đúng hoặc trễ chút, vài khách trả chậm
      const slow = K.indexOf(p) % 7 === 0;
      const payDay = D.add(day, p.hanNo + Sample.int(r, -10, slow ? 70 : 15));
      if (payDay <= today && r() < (slow ? .82 : .985)) db.vouchers.push({ id: uid() + db.vouchers.length, ngay: payDay, so: 'PT' + String(++sPT).padStart(5, '0'), partnerId: p.id, loai: 'TT', dienGiai: 'Thu tiền hóa đơn ' + 'HD' + String(sHD).padStart(5, '0'), soTien: r() < .85 ? amt : Math.round(amt * .6 / 1e5) * 1e5, hanTT: '', hinhThuc: r() < .8 ? 'Chuyển khoản' : 'Tiền mặt' });
      if (r() < .03) db.vouchers.push({ id: uid() + db.vouchers.length, ngay: D.add(day, 3) > today ? day : D.add(day, 3), so: 'GG' + String(db.vouchers.length).padStart(5, '0'), partnerId: p.id, loai: 'GG', dienGiai: 'Khách trả lại hàng lỗi', soTien: Math.round(amt * .1 / 1e5) * 1e5, hanTT: '', hinhThuc: '' });
    }
    if (r() < .32) {
      const p = Sample.pick(r, N), amt = Sample.int(r, 20, 150) * 1e6;
      db.vouchers.push({ id: uid() + db.vouchers.length, ngay: day, so: 'PN' + String(++sPN).padStart(5, '0'), partnerId: p.id, loai: 'PS', dienGiai: 'Nhập hàng theo hóa đơn', soTien: amt, hanTT: D.add(day, p.hanNo), hinhThuc: '' });
      const payDay = D.add(day, p.hanNo + Sample.int(r, -5, 12));
      if (payDay <= today && r() < .9) db.vouchers.push({ id: uid() + db.vouchers.length, ngay: payDay, so: 'PC' + String(++sPC).padStart(5, '0'), partnerId: p.id, loai: 'TT', dienGiai: 'Trả tiền nhập hàng', soTien: amt, hanTT: '', hinhThuc: 'Chuyển khoản' });
    }
  }
  // khoản trả trước cho số dư đầu kỳ
  db.partners.forEach(p => { if (p.noDauKy) db.vouchers.push({ id: uid() + db.vouchers.length, ngay: D.add(today, -340 + Sample.int(r, 0, 30)), so: (p.loai === KH ? 'PT' : 'PC') + String(p.loai === KH ? ++sPT : ++sPC).padStart(5, '0'), partnerId: p.id, loai: 'TT', dienGiai: 'Thanh toán số dư đầu kỳ', soTien: p.noDauKy, hanTT: '', hinhThuc: 'Chuyển khoản' }); });
  return db;
}
function migrate(db) {
  db.settings = Object.assign(defaultSettings(), db.settings || {});
  db.settings.company = Object.assign(defaultSettings().company, db.settings.company || {});
  db.partners = db.partners || []; db.vouchers = db.vouchers || [];
}

/* ---------- Tính toán ---------- */
const pById = id => DB.partners.find(p => p.id === id);
const sign = v => v.loai === 'PS' ? 1 : -1;
let _cache = null, _cacheKey = '', _cacheDB = null;
// Gom chứng từ theo đối tác (lưu tạm để không tính lại nhiều lần)
function byPartner() {
  const key = DB.vouchers.length + ':' + Store.dirty + ':' + (DB._meta.savedAt || '') + ':' + _ver;
  if (_cache && _cacheKey === key && _cacheDB === DB) return _cache;
  const m = {}; DB.partners.forEach(p => m[p.id] = []);
  DB.vouchers.forEach(v => { (m[v.partnerId] = m[v.partnerId] || []).push(v); });
  for (const k in m) m[k].sort((a, b) => a.ngay.localeCompare(b.ngay) || (a.loai === 'PS' ? -1 : 1));
  _cache = m; _cacheKey = key; _cacheDB = DB; return m;
}
let _ver = 0; const bump = () => { _ver++; };
function balance(p, to = '9999-12-31', from = null) {
  const vs = byPartner()[p.id] || [];
  let dk = +p.noDauKy || 0, ps = 0, gg = 0;
  for (const v of vs) {
    if (v.ngay > to) break;
    if (from && v.ngay < from) { dk += sign(v) * v.soTien; continue; }
    if (v.loai === 'PS') ps += v.soTien; else gg += v.soTien;
  }
  return { dk, ps, gg, ck: dk + ps - gg };
}
// Phân bổ thanh toán theo nguyên tắc nợ cũ trả trước (FIFO) để tính tuổi nợ
function openItems(p, asOf = D.today()) {
  const vs = (byPartner()[p.id] || []).filter(v => v.ngay <= asOf);
  const items = [];
  if (+p.noDauKy) items.push({ id: 'dk' + p.id, so: 'Số dư đầu kỳ', ngay: p.ngayDauKy || '', hanTT: p.ngayDauKy || '', soTien: +p.noDauKy, con: +p.noDauKy, p });
  vs.filter(v => v.loai === 'PS').forEach(v => items.push({ id: v.id, so: v.so, ngay: v.ngay, hanTT: v.hanTT || D.add(v.ngay, +p.hanNo || 0), soTien: v.soTien, con: v.soTien, dienGiai: v.dienGiai, p }));
  let paid = sum(vs.filter(v => v.loai !== 'PS'), v => v.soTien);
  for (const it of items) { const a = Math.min(it.con, paid); it.con -= a; paid -= a; if (paid <= 0) break; }
  return items.filter(it => it.con > 0).map(it => ({ ...it, quaHan: it.hanTT ? D.diff(asOf, it.hanTT) : 0 }));
}
const BUCKETS = [['Trong hạn', x => x <= 0], ['1-30 ngày', x => x > 0 && x <= 30], ['31-60 ngày', x => x > 30 && x <= 60], ['61-90 ngày', x => x > 60 && x <= 90], ['Trên 90 ngày', x => x > 90]];
const BCOL = ['#10b981', '#f59e0b', '#f97316', '#ef4444', '#991b1b'];
function aging(p) { const its = openItems(p); return BUCKETS.map(([, f]) => sum(its.filter(i => f(i.quaHan)), i => i.con)); }
function partnerRow(p) {
  const b = balance(p), its = openItems(p);
  const qh = sum(its.filter(i => i.quaHan > 0), i => i.con);
  const maxQH = Math.max(0, ...its.map(i => i.quaHan));
  return { ...p, du: b.ck, quaHan: qh, maxQH, vuot: p.hanMuc && b.ck > p.hanMuc };
}
const side = () => UI.side || KH;

/* ---------- Bộ lọc dùng chung ---------- */
UI.side = KH;
UI.from = D.today().slice(0, 4) + '-01-01'; UI.to = D.today();
const sideSeg = () => `<div class="seg">${[KH, NCC].map(s => `<button class="${side() === s ? 'on' : ''}" data-act="side" data-v="${s}">${s === KH ? 'Phải thu (khách hàng)' : 'Phải trả (nhà cung cấp)'}</button>`).join('')}</div>`;
const rangeHTML = () => `<label>Từ <input type="date" class="inp" id="fFrom" value="${UI.from}"></label><label>Đến <input type="date" class="inp" id="fTo" value="${UI.to}"></label>`;
function bindRange() { onVal('fFrom', v => { UI.from = v; App.render(); }); onVal('fTo', v => { UI.to = v; App.render(); }); }
Object.assign(ACT, { side: d => { UI.side = d.v; Object.values(UI.tbl).forEach(t => t.page = 0); App.render(); } });
const partnersOf = s => DB.partners.filter(p => p.loai === s);

/* ---------- Dashboard ---------- */
const vDash = {
  title: 'Dashboard', icon: 'dashboard', desc: 'Toàn cảnh công nợ phải thu, phải trả, tuổi nợ và dòng tiền',
  tools: sideSeg,
  render(el) {
    const s = side(), isKH = s === KH, today = D.today();
    const rowsKH = partnersOf(KH).map(partnerRow), rowsNCC = partnersOf(NCC).map(partnerRow);
    const R = isKH ? rowsKH : rowsNCC;
    const ym = today.slice(0, 7);
    const tt = DB.vouchers.filter(v => v.loai === 'TT' && v.ngay.startsWith(ym) && (pById(v.partnerId) || {}).loai === s);
    const open = R.flatMap(p => openItems(DB.partners.find(x => x.id === p.id)));
    const sap = open.filter(i => i.quaHan <= 0 && i.quaHan >= -DB.settings.canhBao);
    const months = D.ymRange(ym, 12);
    const vs = DB.vouchers.filter(v => (pById(v.partnerId) || {}).loai === s);
    const agg = BUCKETS.map((b, i) => ({ label: b[0], value: sum(R, p => aging(DB.partners.find(x => x.id === p.id))[i]), color: BCOL[i] }));
    const top = [...R].sort((a, b) => b.du - a.du).slice(0, 8).filter(p => p.du > 0);
    App.subtitle = isKH ? 'Công nợ phải thu' : 'Công nợ phải trả';
    const T = list => sum(list, p => p.du), Q = list => sum(list, p => p.quaHan);
    el.innerHTML = `
      <div class="kpis">
        ${kpi({ label: 'Tổng phải thu', value: F.short(T(rowsKH)), sub: F.vnd(T(rowsKH)), icon: 'trend', tone: 'blue' })}
        ${kpi({ label: 'Phải thu quá hạn', value: F.short(Q(rowsKH)), sub: `${T(rowsKH) ? F.pct(Q(rowsKH) / T(rowsKH) * 100) : '0%'} tổng phải thu`, icon: 'alert', tone: 'red' })}
        ${kpi({ label: 'Tổng phải trả', value: F.short(T(rowsNCC)), sub: F.vnd(T(rowsNCC)), icon: 'wallet', tone: 'violet' })}
        ${kpi({ label: 'Phải trả quá hạn', value: F.short(Q(rowsNCC)), sub: `${rowsNCC.filter(p => p.quaHan > 0).length} nhà cung cấp`, icon: 'clock', tone: 'amber' })}
        ${kpi({ label: isKH ? 'Đã thu tháng này' : 'Đã trả tháng này', value: F.short(sum(tt, v => v.soTien)), sub: `${tt.length} chứng từ`, icon: 'money', tone: 'green' })}
        ${kpi({ label: `Đến hạn ${DB.settings.canhBao} ngày tới`, value: F.short(sum(sap, i => i.con)), sub: `${sap.length} hóa đơn · ${R.filter(p => p.vuot).length} vượt hạn mức`, icon: 'calendar' })}
      </div>
      <div class="grid cols-21">
        ${card(isKH ? 'Bán chịu và thu tiền 12 tháng' : 'Mua chịu và trả tiền 12 tháng', Chart.bar({ labels: months.map(F.mshort), series: [
          { name: isKH ? 'Phát sinh nợ (bán)' : 'Phát sinh nợ (mua)', values: months.map(m => sum(vs.filter(v => v.loai === 'PS' && v.ngay.startsWith(m)), v => v.soTien)) },
          { name: isKH ? 'Đã thu' : 'Đã trả', values: months.map(m => sum(vs.filter(v => v.loai !== 'PS' && v.ngay.startsWith(m)), v => v.soTien)), color: '#10b981' }] }))}
        ${card('Tuổi nợ', Chart.donut({ items: agg, centerSub: isKH ? 'Đang phải thu' : 'Đang phải trả' }))}
      </div>
      <div class="grid cols-12 mt">
        ${card(isKH ? 'Khách hàng nợ nhiều nhất' : 'Nợ nhà cung cấp nhiều nhất', Chart.hbars({ items: top.map(p => ({ label: p.ten, sub: p.quaHan ? '· quá hạn ' + F.short(p.quaHan) : '', value: p.du, color: p.quaHan ? '#ef4444' : undefined })) }))}
        <div><div class="card-h" style="padding:0 0 10px;border:0"><h3>Hóa đơn quá hạn và sắp đến hạn</h3></div><div id="dOpen"></div></div>
      </div>`;
    const due = open.filter(i => i.quaHan > -DB.settings.canhBao).sort((a, b) => b.quaHan - a.quaHan);
    table($('#dOpen'), { id: 'dopen', rows: due, pageSize: 10, ph: 'Tìm đối tác, số hóa đơn...', empty: 'Không có hóa đơn quá hạn', columns: [
      { key: 'so', label: 'Số HĐ' }, { key: 'doiTac', label: 'Đối tác', val: i => i.p.ten }, { key: 'hanTT', label: 'Hạn TT', fmt: 'date' }, { key: 'con', label: 'Còn nợ', fmt: 'money', sum: true },
      { key: 'quaHan', label: 'Tình trạng', sortVal: i => i.quaHan, render: i => i.quaHan > 0 ? `<span class="badge ${i.quaHan > 60 ? 'b-red' : 'b-amber'}">Quá ${i.quaHan} ngày</span>` : `<span class="badge b-blue">Còn ${-i.quaHan} ngày</span>` }] });
  },
  excel() {
    const s = side(), R = partnersOf(s).map(p => ({ ...partnerRow(p), ag: aging(p) }));
    XLSX.save(`Bao cao cong no ${s === KH ? 'phai thu' : 'phai tra'} ${D.today()}`, [
      xSheet('Tuổi nợ', `BÁO CÁO TUỔI NỢ ${s === KH ? 'PHẢI THU' : 'PHẢI TRẢ'} ĐẾN ${F.date(D.today())}`, AGE_COLS, R)
    ]);
  }
};
const AGE_COLS = [{ key: 'ma', label: 'Mã' }, { key: 'ten', label: 'Tên đối tác', xw: 32 }, { key: 'hanNo', label: 'Hạn nợ (ngày)', align: 'c', xtype: 'int' },
  ...BUCKETS.map((b, i) => ({ key: 'b' + i, label: b[0], fmt: 'money', sum: true, val: p => p.ag[i] })),
  { key: 'du', label: 'Tổng dư nợ', fmt: 'money', sum: true }];

/* ---------- Đối tác ---------- */
const P_FIELDS = () => [
  { key: 'ma', label: 'Mã đối tác', required: true }, { key: 'loai', label: 'Loại', type: 'select', options: [KH, NCC] },
  { key: 'ten', label: 'Tên đối tác', required: true, full: true },
  { key: 'nguoiLH', label: 'Người liên hệ' }, { key: 'sdt', label: 'Điện thoại', type: 'tel' },
  { key: 'email', label: 'Email', type: 'email' }, { key: 'mst', label: 'Mã số thuế' },
  { key: 'diaChi', label: 'Địa chỉ', full: true },
  { type: 'section', label: 'Điều khoản công nợ' },
  { key: 'hanNo', label: 'Hạn thanh toán (số ngày)', type: 'number', min: 0 }, { key: 'hanMuc', label: 'Hạn mức nợ tối đa', type: 'money', hint: 'Để 0 nếu không giới hạn' },
  { key: 'noDauKy', label: 'Số dư nợ đầu kỳ', type: 'money' }, { key: 'ngayDauKy', label: 'Ngày của số dư đầu kỳ', type: 'date' },
  { key: 'ghiChu', label: 'Ghi chú', type: 'textarea', full: true, rows: 2 }
];
function pForm(p, loai) {
  const isNew = !p; loai = loai || side();
  const n = DB.partners.filter(x => x.loai === loai).length + 1;
  Form.open({
    title: isNew ? 'Thêm ' + loai.toLowerCase() : 'Sửa ' + p.loai.toLowerCase(), fields: P_FIELDS(), width: 720,
    value: p || { ma: (loai === KH ? 'KH' : 'NCC') + String(n).padStart(3, '0'), loai, hanNo: DB.settings.hanNoMacDinh, hanMuc: 0, noDauKy: 0, ngayDauKy: D.today().slice(0, 4) + '-01-01' },
    onSave: d => {
      if (DB.partners.some(x => x.ma === d.ma && x !== p)) { toast('Mã đối tác đã tồn tại', 'err'); return false; }
      if (isNew) DB.partners.push({ id: uid(), ...d }); else Object.assign(p, d);
      bump(); App.changed(); toast('Đã lưu ' + esc(d.ten), 'ok');
    },
    onDelete: isNew ? null : () => delPartner(p)
  });
}
function delPartner(p) { const n = DB.vouchers.filter(v => v.partnerId === p.id).length; DB.partners = DB.partners.filter(x => x !== p); DB.vouchers = DB.vouchers.filter(v => v.partnerId !== p.id); bump(); App.changed(); toast(`Đã xóa ${esc(p.ten)} và ${n} chứng từ`, 'ok'); }
const PCOLS = [
  { key: 'ma', label: 'Mã' },
  { key: 'ten', label: 'Tên đối tác', render: p => `<div class="who"><span class="avatar">${F.initials(p.ten)}</span><div><b>${esc(p.ten)}</b><small>${esc(p.nguoiLH || '')} ${p.sdt ? '· ' + esc(p.sdt) : ''}</small></div></div>` },
  { key: 'diaChi', label: 'Khu vực' },
  { key: 'hanNo', label: 'Hạn nợ', align: 'c', render: p => p.hanNo + ' ngày', xtype: 'int' },
  { key: 'hanMuc', label: 'Hạn mức', fmt: 'money' },
  { key: 'du', label: 'Dư nợ hiện tại', fmt: 'money', sum: true },
  { key: 'quaHan', label: 'Trong đó quá hạn', fmt: 'money', sum: true, render: p => p.quaHan ? `<span class="neg">${F.money(p.quaHan)}</span>` : '0' },
  { key: 'tt', label: 'Tình trạng', noExport: 1, sortVal: p => p.maxQH, render: p => p.du <= 0 ? '<span class="badge b-green">Hết nợ</span>' : p.vuot ? '<span class="badge b-red">Vượt hạn mức</span>' : p.maxQH > 60 ? '<span class="badge b-red">Nợ xấu</span>' : p.maxQH > 0 ? `<span class="badge b-amber">Quá hạn ${p.maxQH} ngày</span>` : '<span class="badge b-blue">Trong hạn</span>' }
];
const vPartner = {
  title: 'Khách hàng & nhà cung cấp', label: 'Đối tác', icon: 'users', desc: 'Danh sách đối tác, hạn nợ, hạn mức và dư nợ hiện tại',
  tools: () => sideSeg() + `<button class="btn pri" data-act="pAdd">${ic('plus')} Thêm</button>`,
  rows: () => partnersOf(side()).map(partnerRow),
  render(el) {
    App.subtitle = side();
    table(el, { id: 'p' + side(), columns: PCOLS, rows: vPartner.rows(), ph: 'Tìm mã, tên, người liên hệ...', sort: 5, dir: -1, onRow: p => pForm(pById(p.id)),
      actions: p => `<button class="ibtn" data-act="pLedger" data-id="${p.id}" data-tip="Xem sổ chi tiết">${ic('book')}</button>` + rowBtns(p.id, 'pEdit', 'pDel'),
      empty: 'Chưa có đối tác. Bấm "Thêm".' });
  },
  excel() { XLSX.save('Danh sach ' + side(), [xSheet(side(), 'DANH SÁCH ' + side().toUpperCase(), [...PCOLS, { key: 'mst', label: 'MST' }, { key: 'sdt', label: 'Điện thoại' }, { key: 'nguoiLH', label: 'Người liên hệ' }], vPartner.rows())]); },
  print() { Print.report({ title: 'Danh sách ' + side().toLowerCase(), cols: PCOLS.map(c => ({ ...c, render: null })).filter(c => !c.noExport), rows: vPartner.rows(), signs: ['Người lập', 'Kế toán trưởng'] }); }
};
Object.assign(ACT, {
  pAdd: () => pForm(null, side()), pEdit: d => pForm(pById(d.id)),
  pDel: async d => { const p = pById(d.id); if (await Modal.confirm(`Xóa <b>${esc(p.ten)}</b> và toàn bộ chứng từ của đối tác này?`, { ok: 'Xóa', danger: true })) delPartner(p); },
  pLedger: d => { UI.ledger = d.id; App.go('chitiet'); }
});

/* ---------- Chứng từ ---------- */
const V_FIELDS = () => [
  { key: 'ngay', label: 'Ngày chứng từ', type: 'date', required: true }, { key: 'so', label: 'Số chứng từ', required: true },
  { key: 'partnerId', label: 'Đối tác', type: 'select', full: true, required: true, options: () => DB.partners.map(p => ({ value: p.id, label: `${p.ma} - ${p.ten} (${p.loai})` })) },
  { key: 'loai', label: 'Loại nghiệp vụ', type: 'select', options: Object.entries(LOAI).map(([value, label]) => ({ value, label })) },
  { key: 'soTien', label: 'Số tiền', type: 'money', required: true },
  { key: 'dienGiai', label: 'Diễn giải', full: true },
  { key: 'hanTT', label: 'Hạn thanh toán', type: 'date', hint: 'Chỉ cho phát sinh nợ. Để trống: tự tính theo hạn nợ của đối tác' },
  { key: 'hinhThuc', label: 'Hình thức thanh toán', type: 'select', options: ['', 'Chuyển khoản', 'Tiền mặt', 'Bù trừ'] }
];
function nextSo(prefix) { const n = DB.vouchers.filter(v => v.so && v.so.startsWith(prefix)).map(v => parseInt(v.so.slice(prefix.length)) || 0); return prefix + String(Math.max(0, ...n) + 1).padStart(5, '0'); }
function vForm(v, loai, partnerId) {
  const isNew = !v;
  const pre = l => { const kh = (pById(partnerId) || {}).loai !== NCC && side() === KH; return l === 'PS' ? (kh ? 'HD' : 'PN') : l === 'TT' ? (kh ? 'PT' : 'PC') : 'GG'; };
  const val = v || { ngay: D.today(), loai: loai || 'PS', so: nextSo(pre(loai || 'PS')), partnerId: partnerId || (partnersOf(side())[0] || {}).id, hinhThuc: loai === 'TT' ? 'Chuyển khoản' : '' };
  const m = Form.open({
    title: isNew ? (loai === 'TT' ? (side() === KH ? 'Thu tiền khách hàng' : 'Trả tiền nhà cung cấp') : loai === 'GG' ? 'Giảm trừ / trả hàng' : (side() === KH ? 'Ghi nợ bán hàng' : 'Ghi nợ mua hàng')) : 'Sửa chứng từ ' + esc(v.so),
    fields: V_FIELDS(), value: val, width: 680,
    extra: `<div class="banner mt" id="vInfo" style="margin-bottom:0"></div>`,
    onSave: d => {
      const p = pById(d.partnerId); if (!p) { toast('Chọn đối tác', 'err'); return false; }
      if (d.soTien <= 0) { toast('Số tiền phải lớn hơn 0', 'err'); return false; }
      if (d.loai === 'PS' && !d.hanTT) d.hanTT = D.add(d.ngay, +p.hanNo || 0);
      if (d.loai !== 'PS') d.hanTT = '';
      if (isNew) DB.vouchers.push({ id: uid(), ...d }); else Object.assign(v, d);
      bump(); App.changed(); toast('Đã lưu chứng từ ' + esc(d.so), 'ok');
      const after = balance(p).ck; if (d.loai === 'PS' && p.hanMuc && after > p.hanMuc) toast(`Cảnh báo: ${esc(p.ten)} đã vượt hạn mức nợ (${F.money(after)} / ${F.money(p.hanMuc)})`, 'err', 6000);
    },
    onDelete: isNew ? null : () => { DB.vouchers = DB.vouchers.filter(x => x !== v); bump(); App.changed(); }
  });
  const info = () => { const p = pById(m.el.querySelector('[name=partnerId]').value); m.el.querySelector('#vInfo').innerHTML = p ? `${ic('wallet')}<div>Dư nợ hiện tại của <b>${esc(p.ten)}</b>: <b>${F.vnd(balance(p).ck)}</b>${p.hanMuc ? ` · hạn mức ${F.money(p.hanMuc)}` : ''} · hạn nợ ${p.hanNo} ngày</div>` : ''; };
  m.el.querySelector('[name=partnerId]').addEventListener('change', info); info();
  if (isNew) m.el.querySelector('[name=loai]').addEventListener('change', e => { m.el.querySelector('[name=so]').value = nextSo(pre(e.target.value)); });
}
const VCOLS = [
  { key: 'ngay', label: 'Ngày', fmt: 'date' }, { key: 'so', label: 'Số CT' },
  { key: 'doiTac', label: 'Đối tác', val: v => (pById(v.partnerId) || {}).ten || '?' },
  { key: 'loai', label: 'Loại', val: v => LOAI[v.loai], render: v => `<span class="badge ${v.loai === 'PS' ? 'b-p' : v.loai === 'TT' ? 'b-green' : 'b-amber'}">${LOAI[v.loai]}</span>` },
  { key: 'dienGiai', label: 'Diễn giải' },
  { key: 'tang', label: 'Tăng nợ', fmt: 'money', sum: true, val: v => v.loai === 'PS' ? v.soTien : 0, render: v => v.loai === 'PS' ? F.money(v.soTien) : '' },
  { key: 'giam', label: 'Giảm nợ', fmt: 'money', sum: true, val: v => v.loai !== 'PS' ? v.soTien : 0, render: v => v.loai !== 'PS' ? F.money(v.soTien) : '' },
  { key: 'hanTT', label: 'Hạn TT', fmt: 'date' }, { key: 'hinhThuc', label: 'Hình thức' }
];
UI.vFrom = D.add(D.today(), -90); UI.vTo = D.today(); UI.vLoai = '';
const vVouch = {
  title: 'Chứng từ công nợ', label: 'Chứng từ', icon: 'receipt', desc: 'Ghi nợ khi bán / mua chịu, ghi giảm khi thu / trả tiền',
  tools: () => sideSeg() + `<button class="btn pri" data-act="vAdd" data-l="PS">${ic('plus')} Ghi nợ</button><button class="btn" data-act="vAdd" data-l="TT">${ic('money')} ${side() === KH ? 'Thu tiền' : 'Trả tiền'}</button>`,
  rows() { const s = side(); return DB.vouchers.filter(v => v.ngay >= UI.vFrom && v.ngay <= UI.vTo && (!UI.vLoai || v.loai === UI.vLoai) && (pById(v.partnerId) || {}).loai === s); },
  render(el) {
    App.subtitle = `${side()} · từ ${F.date(UI.vFrom)} đến ${F.date(UI.vTo)}`;
    el.innerHTML = `<div class="filters"><label>Từ <input type="date" class="inp" id="vF" value="${UI.vFrom}"></label><label>Đến <input type="date" class="inp" id="vT" value="${UI.vTo}"></label><label>Loại ${selectHTML('vL', [{ value: '', label: 'Tất cả' }, ...Object.entries(LOAI).map(([value, label]) => ({ value, label }))], UI.vLoai)}</label><button class="btn sm" data-act="vQuick" data-d="30">30 ngày</button><button class="btn sm" data-act="vQuick" data-d="90">90 ngày</button><button class="btn sm" data-act="vQuick" data-d="365">1 năm</button></div><div id="vT2"></div>`;
    onVal('vF', v => { UI.vFrom = v; App.render(); }); onVal('vT', v => { UI.vTo = v; App.render(); }); onVal('vL', v => { UI.vLoai = v; App.render(); });
    table($('#vT2'), { id: 'vch', columns: VCOLS, rows: vVouch.rows(), sort: 0, dir: -1, ph: 'Tìm số CT, đối tác, diễn giải...', onRow: v => vForm(DB.vouchers.find(x => x.id === v.id)), actions: v => rowBtns(v.id, 'vEdit', 'vDel'), empty: 'Không có chứng từ trong khoảng ngày này' });
  },
  excel() { XLSX.save(`Chung tu cong no ${UI.vFrom} den ${UI.vTo}`, [xSheet('Chứng từ', `NHẬT KÝ CHỨNG TỪ CÔNG NỢ ${side().toUpperCase()}`, VCOLS, vVouch.rows().sort((a, b) => a.ngay.localeCompare(b.ngay)), { subtitles: [companyLine(), `Từ ngày ${F.date(UI.vFrom)} đến ngày ${F.date(UI.vTo)}`] })]); },
  print() { Print.report({ title: 'Nhật ký chứng từ công nợ ' + side().toLowerCase(), sub: `Từ ngày ${F.date(UI.vFrom)} đến ngày ${F.date(UI.vTo)}`, cols: VCOLS.filter(c => c.key !== 'hinhThuc').map(c => ({ ...c, render: null })), rows: vVouch.rows().sort((a, b) => a.ngay.localeCompare(b.ngay)), landscape: true }); }
};
Object.assign(ACT, {
  vAdd: d => vForm(null, d.l, App.cur === 'chitiet' ? UI.ledger : null), vEdit: d => vForm(DB.vouchers.find(x => x.id === d.id)),
  vDel: async d => { const v = DB.vouchers.find(x => x.id === d.id); if (await Modal.confirm(`Xóa chứng từ <b>${esc(v.so)}</b>?`, { ok: 'Xóa', danger: true })) { DB.vouchers = DB.vouchers.filter(x => x !== v); bump(); App.changed(); } },
  vQuick: d => { UI.vTo = D.today(); UI.vFrom = D.add(UI.vTo, -(+d.d)); App.render(); }
});

/* ---------- Sổ chi tiết công nợ ---------- */
function ledger(p) {
  const vs = (byPartner()[p.id] || []).filter(v => v.ngay >= UI.from && v.ngay <= UI.to);
  const b = balance(p, UI.to, UI.from);
  let run = b.dk;
  const rows = vs.map(v => { run += sign(v) * v.soTien; return { ...v, tang: v.loai === 'PS' ? v.soTien : 0, giam: v.loai !== 'PS' ? v.soTien : 0, du: run }; });
  return { b, rows };
}
const LCOLS = [
  { key: 'ngay', label: 'Ngày', fmt: 'date' }, { key: 'so', label: 'Số CT' }, { key: 'dienGiai', label: 'Diễn giải', render: v => esc(v.dienGiai || LOAI[v.loai]) },
  { key: 'tang', label: 'Phát sinh tăng', fmt: 'money', sum: true, render: v => v.tang ? F.money(v.tang) : '' },
  { key: 'giam', label: 'Phát sinh giảm', fmt: 'money', sum: true, render: v => v.giam ? F.money(v.giam) : '' },
  { key: 'hanTT', label: 'Hạn TT', fmt: 'date' },
  { key: 'du', label: 'Số dư', fmt: 'money', render: v => `<b>${F.money(v.du)}</b>` }
];
const vLedger = {
  title: 'Chi tiết công nợ', icon: 'book', desc: 'Sổ chi tiết theo từng đối tác, có số dư lũy kế và biên bản đối chiếu',
  tools: () => `<button class="btn" data-act="vAdd" data-l="PS">${ic('plus')} Ghi nợ</button><button class="btn" data-act="vAdd" data-l="TT">${ic('money')} Thanh toán</button><button class="btn" data-act="doiChieu">${ic('file')} In biên bản đối chiếu</button>`,
  cur() { let p = pById(UI.ledger); if (!p) { p = partnersOf(side())[0] || DB.partners[0]; UI.ledger = p && p.id; } return p; },
  render(el) {
    const p = vLedger.cur();
    if (!p) { el.innerHTML = '<div class="empty">Chưa có đối tác nào</div>'; return; }
    const { b, rows } = ledger(p), its = openItems(p);
    App.subtitle = `${p.ten} · từ ${F.date(UI.from)} đến ${F.date(UI.to)}`;
    el.innerHTML = `<div class="filters"><label>Đối tác <select class="inp" id="lP" style="max-width:340px">${[KH, NCC].map(s => `<optgroup label="${s}">${partnersOf(s).map(x => `<option value="${x.id}" ${x.id === p.id ? 'selected' : ''}>${esc(x.ma)} - ${esc(x.ten)}</option>`).join('')}</optgroup>`).join('')}</select></label>${rangeHTML()}</div>
      <div class="sumbox"><div><small>Số dư đầu kỳ</small><b>${F.money(b.dk)}</b></div><div><small>Phát sinh tăng</small><b>${F.money(b.ps)}</b></div><div><small>Phát sinh giảm</small><b class="pos">${F.money(b.gg)}</b></div><div><small>Số dư cuối kỳ</small><b style="color:var(--p-d)">${F.money(b.ck)}</b></div></div>
      <div id="lT"></div><div class="mt">${card('Hóa đơn còn nợ', its.length ? `<div id="lO"></div>` : '<div class="empty">Đã thanh toán hết</div>', { bcls: 'p0', sub: `${its.length} hóa đơn · hạn nợ ${p.hanNo} ngày · thanh toán được trừ vào hóa đơn cũ nhất trước` })}</div>`;
    onVal('lP', v => { UI.ledger = v; UI.tbl.ledger && (UI.tbl.ledger.page = 0); App.render(); }); bindRange();
    table($('#lT'), { id: 'ledger', columns: LCOLS, rows, ph: 'Tìm số CT, diễn giải...', actions: v => rowBtns(v.id, 'vEdit', 'vDel'), empty: 'Không có phát sinh trong kỳ' });
    if (its.length) table($('#lO'), { id: 'lopen', search: false, pageSize: 10, rows: its, columns: [{ key: 'so', label: 'Số HĐ' }, { key: 'ngay', label: 'Ngày HĐ', fmt: 'date' }, { key: 'dienGiai', label: 'Diễn giải' }, { key: 'soTien', label: 'Giá trị HĐ', fmt: 'money', sum: true }, { key: 'hanTT', label: 'Hạn thanh toán', fmt: 'date' }, { key: 'con', label: 'Còn nợ', fmt: 'money', sum: true }, { key: 'quaHan', label: '', render: i => i.quaHan > 0 ? `<span class="badge ${i.quaHan > 60 ? 'b-red' : 'b-amber'}">Quá ${i.quaHan} ngày</span>` : '<span class="badge b-green">Trong hạn</span>' }] });
  },
  excel() {
    const p = vLedger.cur(); if (!p) return; const { b, rows } = ledger(p);
    XLSX.save(`So chi tiet cong no ${p.ma}`, [xSheet('Sổ chi tiết', `SỔ CHI TIẾT CÔNG NỢ - ${p.ten.toUpperCase()}`, LCOLS, rows, { subtitles: [companyLine(), `Từ ${F.date(UI.from)} đến ${F.date(UI.to)} · Đầu kỳ: ${F.money(b.dk)} · Cuối kỳ: ${F.money(b.ck)}`] })]);
  },
  print() {
    const p = vLedger.cur(); if (!p) return; const { b, rows } = ledger(p);
    Print.report({ title: 'Sổ chi tiết công nợ', sub: `${p.ten} · Từ ngày ${F.date(UI.from)} đến ngày ${F.date(UI.to)}`, cols: LCOLS.map(c => ({ ...c, render: null })), rows, before: `<p>Số dư đầu kỳ: <b>${F.money(b.dk)}</b></p>`, after: `<p>Số dư cuối kỳ: <b>${F.money(b.ck)}</b></p>`, signs: ['Người lập', 'Kế toán trưởng'] });
  }
};
Object.assign(ACT, {
  doiChieu: () => {
    const p = vLedger.cur(); if (!p) return; const { b, rows } = ledger(p), c = DB.settings.company, isKH = p.loai === KH;
    const d = new Date();
    Print.run(`<div class="pr">${Print.head()}<div style="text-align:center;font-weight:700">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM<br><span style="font-weight:600">Độc lập - Tự do - Hạnh phúc</span></div>
      <h2>Biên bản đối chiếu công nợ</h2><div class="pr-sub">Từ ngày ${F.date(UI.from)} đến ngày ${F.date(UI.to)}</div>
      <p>Hôm nay, ngày ${d.getDate()} tháng ${d.getMonth() + 1} năm ${d.getFullYear()}, chúng tôi gồm:</p>
      <p><b>Bên A: ${esc(c.ten)}</b><br>Địa chỉ: ${esc(c.diaChi)} · MST: ${esc(c.mst)}</p>
      <p><b>Bên B: ${esc(p.ten)}</b><br>Địa chỉ: ${esc(p.diaChi || '')} · MST: ${esc(p.mst || '')} · Người liên hệ: ${esc(p.nguoiLH || '')}</p>
      <p>Cùng đối chiếu công nợ ${isKH ? 'Bên B phải trả Bên A' : 'Bên A phải trả Bên B'} như sau:</p>
      ${Print.table(LCOLS.filter(x => x.key !== 'hanTT').map(x => ({ ...x, render: null })), rows)}
      <table style="margin-top:10px"><tbody><tr><td>Số dư đầu kỳ</td><td class="r">${F.money(b.dk)}</td></tr><tr><td>Phát sinh tăng trong kỳ</td><td class="r">${F.money(b.ps)}</td></tr><tr><td>Phát sinh giảm trong kỳ</td><td class="r">${F.money(b.gg)}</td></tr><tr><td><b>Số dư cuối kỳ</b></td><td class="r"><b>${F.money(b.ck)}</b></td></tr></tbody></table>
      <p>Bằng chữ: <i>${docSo(b.ck)}</i>.</p><p>Biên bản lập thành 02 bản, mỗi bên giữ 01 bản có giá trị như nhau. Sau 07 ngày kể từ ngày nhận, nếu Bên B không có ý kiến phản hồi thì xem như đã xác nhận số liệu trên.</p>
      ${Print.signs(['ĐẠI DIỆN BÊN A', 'ĐẠI DIỆN BÊN B'], false)}</div>`, { title: 'Bien ban doi chieu ' + p.ma });
  }
});

/* ---------- Tổng hợp công nợ ---------- */
const TCOLS = [{ key: 'ma', label: 'Mã' }, { key: 'ten', label: 'Tên đối tác', xw: 32 }, { key: 'dk', label: 'Dư đầu kỳ', fmt: 'money', sum: true }, { key: 'ps', label: 'Phát sinh tăng', fmt: 'money', sum: true }, { key: 'gg', label: 'Phát sinh giảm', fmt: 'money', sum: true }, { key: 'ck', label: 'Dư cuối kỳ', fmt: 'money', sum: true, render: r => `<b>${F.money(r.ck)}</b>` }];
const vSum = {
  title: 'Tổng hợp công nợ', label: 'Tổng hợp', icon: 'table', desc: 'Đầu kỳ, phát sinh, cuối kỳ của tất cả đối tác trong kỳ',
  tools: sideSeg,
  rows() { return partnersOf(side()).map(p => ({ id: p.id, ma: p.ma, ten: p.ten, ...balance(p, UI.to, UI.from) })).filter(r => r.dk || r.ps || r.gg || r.ck); },
  render(el) {
    App.subtitle = `${side()} · từ ${F.date(UI.from)} đến ${F.date(UI.to)}`;
    el.innerHTML = `<div class="filters">${rangeHTML()}</div><div id="sT"></div>`; bindRange();
    table($('#sT'), { id: 'sum' + side(), columns: TCOLS, rows: vSum.rows(), sort: 5, dir: -1, onRow: r => { UI.ledger = r.id; App.go('chitiet'); }, actions: r => `<button class="ibtn" data-act="pLedger" data-id="${r.id}" data-tip="Sổ chi tiết">${ic('book')}</button>` });
  },
  excel() { XLSX.save(`Tong hop cong no ${UI.from} den ${UI.to}`, [xSheet('Tổng hợp', `BẢNG TỔNG HỢP CÔNG NỢ ${side() === KH ? 'PHẢI THU' : 'PHẢI TRẢ'}`, TCOLS, vSum.rows(), { subtitles: [companyLine(), `Từ ngày ${F.date(UI.from)} đến ngày ${F.date(UI.to)}`] })]); },
  print() { Print.report({ title: `Bảng tổng hợp công nợ ${side() === KH ? 'phải thu' : 'phải trả'}`, sub: `Từ ngày ${F.date(UI.from)} đến ngày ${F.date(UI.to)}`, cols: TCOLS.map(c => ({ ...c, render: null })), rows: vSum.rows() }); }
};

/* ---------- Tuổi nợ ---------- */
const vAge = {
  title: 'Phân tích tuổi nợ', label: 'Tuổi nợ', icon: 'clock', desc: 'Nợ được chia theo số ngày quá hạn (thanh toán trừ vào hóa đơn cũ trước)',
  tools: sideSeg,
  rows() { return partnersOf(side()).map(p => ({ ...partnerRow(p), ag: aging(p) })).filter(r => r.du > 0); },
  render(el) {
    const R = vAge.rows(); App.subtitle = side() + ' · đến ngày ' + F.date(D.today());
    const tot = BUCKETS.map((b, i) => sum(R, r => r.ag[i]));
    el.innerHTML = `<div class="grid cols-2 mb">${card('Cơ cấu tuổi nợ', Chart.donut({ items: BUCKETS.map((b, i) => ({ label: b[0], value: tot[i], color: BCOL[i] })) }))}${card('Đối tác quá hạn lâu nhất', Chart.hbars({ items: [...R].filter(r => r.maxQH > 0).sort((a, b) => b.maxQH - a.maxQH).slice(0, 6).map(r => ({ label: r.ten, value: r.maxQH, sub: '· ' + F.short(r.quaHan), color: r.maxQH > 60 ? '#ef4444' : '#f59e0b' })), fmt: v => v + ' ngày', empty: 'Không có nợ quá hạn' }))}</div><div id="aT"></div>`;
    table($('#aT'), { id: 'age' + side(), columns: AGE_COLS.map((c, i) => i >= 3 && i <= 7 ? { ...c, render: r => r.ag[i - 3] ? `<span style="color:${BCOL[i - 3]};font-weight:600">${F.money(r.ag[i - 3])}</span>` : '' } : c), rows: R, sort: 8, dir: -1, onRow: r => { UI.ledger = r.id; App.go('chitiet'); } });
  },
  excel() { XLSX.save(`Tuoi no ${side()} ${D.today()}`, [xSheet('Tuổi nợ', `BÁO CÁO TUỔI NỢ ${side() === KH ? 'PHẢI THU' : 'PHẢI TRẢ'} ĐẾN ${F.date(D.today())}`, AGE_COLS, vAge.rows())]); },
  print() { Print.report({ title: `Báo cáo tuổi nợ ${side() === KH ? 'phải thu' : 'phải trả'}`, sub: 'Đến ngày ' + F.date(D.today()), cols: AGE_COLS, rows: vAge.rows(), landscape: true }); }
};

/* ---------- Cài đặt ---------- */
const vSet = {
  title: 'Cài đặt', icon: 'settings', desc: 'Thông tin công ty và quy định công nợ', print: false,
  render(el) {
    el.innerHTML = `<div class="grid cols-2">${settingsCard('Thông tin công ty', COMPANY_FIELDS, DB.settings.company)}${settingsCard('Quy định công nợ', [{ key: 'hanNoMacDinh', label: 'Hạn nợ mặc định cho đối tác mới (ngày)', type: 'number', full: true }, { key: 'canhBao', label: 'Cảnh báo trước hạn (ngày)', type: 'number', full: true, hint: 'Dashboard liệt kê hóa đơn sắp đến hạn trong số ngày này' }], DB.settings)}${debtThemeSelector()}</div>`;
  }
};

/* ---------- Khởi động ---------- */
App.init({
  id: 'congno', title: 'Quản lý công nợ', tagline: 'Phải thu · Phải trả · Tuổi nợ', icon: 'receipt', fileName: 'Quan ly cong no.html', theme: THEME,
  palette: ['#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#64748b'],
  sample: sampleDB, empty: emptyDB, migrate,
  counts: () => [['Khách hàng', partnersOf(KH).length], ['Nhà cung cấp', partnersOf(NCC).length], ['Chứng từ', DB.vouchers.length]],
  nav: [{ group: 'Tổng quan', items: ['dashboard'] }, { group: 'Nghiệp vụ', items: ['doitac', 'chungtu', 'chitiet'] }, { group: 'Báo cáo', items: ['tonghop', 'tuoino'] }, { group: 'Thiết lập', items: ['caidat'] }],
  views: { dashboard: vDash, doitac: vPartner, chungtu: vVouch, chitiet: vLedger, tonghop: vSum, tuoino: vAge, caidat: vSet }
});

