
'use strict';
/* ==========================================================
   LÕI DÙNG CHUNG - Phong Excel Academy
   Giao diện, lưu đè file, xuất Excel (.xlsx), in, biểu đồ
   ========================================================== */
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
const pad = n => String(n).padStart(2, '0');
const sum = (arr, f) => arr.reduce((s, x) => s + (+(f ? f(x) : x) || 0), 0);
const round = n => Math.round(+n || 0);
const by = (arr, f) => arr.reduce((m, x) => { const k = f(x); (m[k] = m[k] || []).push(x); return m; }, {});
const clone = o => JSON.parse(JSON.stringify(o));

/* ---------- Định dạng ---------- */
const F = {
  money(n) { return round(n).toLocaleString('vi-VN'); },
  vnd(n) { return F.money(n) + ' đ'; },
  num(n, d = 1) { return (+n || 0).toLocaleString('vi-VN', { maximumFractionDigits: d }); },
  pct(n, d = 1) { return F.num(n, d) + '%'; },
  short(n) {
    n = +n || 0; const a = Math.abs(n);
    const f = (v, u) => (Math.abs(v) >= 100 ? Math.round(v) : Math.round(v * 10) / 10).toLocaleString('vi-VN') + u;
    if (a >= 1e9) return f(n / 1e9, ' tỷ');
    if (a >= 1e6) return f(n / 1e6, ' tr');
    if (a >= 1e3) return f(n / 1e3, 'k');
    return String(round(n));
  },
  date(s) { if (!s) return ''; const [y, m, d] = String(s).slice(0, 10).split('-'); return d ? `${d}/${m}/${y}` : s; },
  month(ym) { if (!ym) return ''; const [y, m] = ym.split('-'); return `Tháng ${+m}/${y}`; },
  mshort(ym) { const [y, m] = ym.split('-'); return `T${+m}/${String(y).slice(2)}`; },
  parse(v) { if (typeof v === 'number') return v; const s = String(v ?? '').replace(/[^\d-]/g, ''); return s && s !== '-' ? parseInt(s, 10) : 0; },
  initials(name) { const p = String(name || '?').trim().split(/\s+/); return ((p.length > 1 ? p[p.length - 2][0] : '') + p[p.length - 1][0]).toUpperCase(); }
};

/* ---------- Ngày tháng ---------- */
const D = {
  iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); },
  today() { return D.iso(new Date()); },
  parse(s) { const [y, m, d] = String(s).slice(0, 10).split('-').map(Number); return new Date(y, m - 1, d || 1); },
  add(s, n) { const d = D.parse(s); d.setDate(d.getDate() + n); return D.iso(d); },
  diff(a, b) { return Math.round((D.parse(a) - D.parse(b)) / 864e5); },
  ymNow() { return D.today().slice(0, 7); },
  ymAdd(ym, n) { let [y, m] = ym.split('-').map(Number); m += n; while (m > 12) { m -= 12; y++; } while (m < 1) { m += 12; y--; } return y + '-' + pad(m); },
  ymRange(endYm, count) { const out = []; for (let i = count - 1; i >= 0; i--) out.push(D.ymAdd(endYm, -i)); return out; },
  daysIn(ym) { const [y, m] = ym.split('-').map(Number); return new Date(y, m, 0).getDate(); },
  dow(ym, d) { const [y, m] = ym.split('-').map(Number); return new Date(y, m - 1, d).getDay(); },
  serial(s) { const [y, m, d] = String(s).slice(0, 10).split('-').map(Number); return (Date.UTC(y, m - 1, d) - Date.UTC(1899, 11, 30)) / 864e5; },
  dowName: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
};

/* ---------- Đọc số thành chữ ---------- */
function docSo(n) {
  n = Math.round(Math.abs(+n || 0));
  if (n === 0) return 'Không đồng';
  const cs = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
  const units = ['', ' nghìn', ' triệu', ' tỷ', ' nghìn tỷ', ' triệu tỷ'];
  const doc3 = (x, full) => {
    const tr = Math.floor(x / 100), ch = Math.floor(x % 100 / 10), dv = x % 10; let s = '';
    if (full || tr > 0) s += cs[tr] + ' trăm';
    if (ch > 1) { s += ' ' + cs[ch] + ' mươi'; if (dv === 1) s += ' mốt'; else if (dv === 5) s += ' lăm'; else if (dv === 4) s += ' tư'; else if (dv > 0) s += ' ' + cs[dv]; }
    else if (ch === 1) { s += ' mười'; if (dv === 5) s += ' lăm'; else if (dv > 0) s += ' ' + cs[dv]; }
    else if (dv > 0) { if (full || tr > 0) s += ' lẻ'; s += ' ' + cs[dv]; }
    return s.trim();
  };
  const g = []; let t = n; while (t > 0) { g.push(t % 1000); t = Math.floor(t / 1000); }
  let out = [];
  for (let i = g.length - 1; i >= 0; i--) { if (g[i] === 0) continue; out.push(doc3(g[i], i !== g.length - 1) + units[i]); }
  const s = out.join(' ').replace(/\s+/g, ' ').trim();
  return s.charAt(0).toUpperCase() + s.slice(1) + ' đồng';
}

/* ---------- Số ngẫu nhiên có hạt giống (dữ liệu mẫu) ---------- */
function rng(seed) {
  return () => { seed |= 0; seed = seed + 0x6D2B79F5 | 0; let t = Math.imul(seed ^ seed >>> 15, 1 | seed); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; };
}
const Sample = {
  ho: ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Huỳnh', 'Phan', 'Vũ', 'Võ', 'Đặng', 'Bùi', 'Đỗ', 'Hồ', 'Ngô', 'Dương', 'Lý'],
  demNam: ['Văn', 'Minh', 'Đức', 'Quốc', 'Hữu', 'Thành', 'Gia', 'Anh', 'Công', 'Tuấn'],
  demNu: ['Thị', 'Ngọc', 'Thu', 'Thanh', 'Minh', 'Phương', 'Kim', 'Bảo', 'Mai', 'Hồng'],
  tenNam: ['Hùng', 'Dũng', 'Nam', 'Long', 'Phúc', 'Khang', 'Bảo', 'Huy', 'Tùng', 'Sơn', 'Hiếu', 'Thắng', 'Quân', 'Trung', 'Đạt', 'Khoa'],
  tenNu: ['Lan', 'Hương', 'Trang', 'Linh', 'Ngân', 'Thảo', 'Hà', 'Vy', 'Yến', 'Mai', 'Nhung', 'Hạnh', 'Quyên', 'Châu', 'Oanh', 'Diệp'],
  pick(r, a) { return a[Math.floor(r() * a.length)]; },
  name(r, nu) { return nu === undefined ? Sample.name(r, r() < .5) : [Sample.pick(r, Sample.ho), Sample.pick(r, nu ? Sample.demNu : Sample.demNam), Sample.pick(r, nu ? Sample.tenNu : Sample.tenNam)].join(' '); },
  phone(r) { return '09' + String(Math.floor(r() * 1e8)).padStart(8, '0'); },
  int(r, a, b) { return a + Math.floor(r() * (b - a + 1)); }
};

/* ---------- Biểu tượng ---------- */
const ICONS = {
  dashboard: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  calendar: '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  calcheck: '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>',
  wallet: '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
  receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
  file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8M16 13H8M16 17H8"/>',
  sheet: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M8 13h2M14 13h2M8 17h2M14 17h2"/>',
  settings: '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
  database: '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
  printer: '<path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
  download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
  upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
  save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
  trash: '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
  x: '<path d="M18 6 6 18M6 6l12 12"/>',
  menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  copy: '<rect width="14" height="14" x="8" y="8" rx="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
  eye: '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
  box: '<path d="M21 8 12 3 3 8v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
  trend: '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>',
  clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
  kanban: '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M8 7v7M12 7v4M16 7v9"/>',
  book: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/>',
  pie: '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>',
  money: '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
  percent: '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
  table: '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>',
  calc: '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M8 6h8M16 14v4M16 10h.01M12 10h.01M8 10h.01M12 14h.01M8 14h.01M12 18h.01M8 18h.01"/>',
  refresh: '<path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M8 16H3v5"/>',
  cake: '<path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8M4 16s.5-1 2-1 2.5 2 4 2 2.5-2 4-2 2.5 2 4 2 2-1 2-1M2 21h20M7 8v3M12 8v3M17 8v3"/>',
  message: '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
  back: '<path d="m12 19-7-7 7-7M19 12H5"/>',
  building: '<rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>',
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  tag: '<path d="M12.59 2.59A2 2 0 0 0 11.17 2H4a2 2 0 0 0-2 2v7.17a2 2 0 0 0 .59 1.41l8.7 8.71a2.43 2.43 0 0 0 3.42 0l6.58-6.58a2.43 2.43 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
  send: '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>'
};
const ic = (n, cls = '') => `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS[n] || ''}</svg>`;

/* ---------- Hành động (bấm nút có data-act) ---------- */
const ACT = {};
document.addEventListener('click', e => {
  const b = e.target.closest('[data-act]');
  if (!b) return;
  const fn = ACT[b.dataset.act];
  if (fn) { e.preventDefault(); e.stopPropagation(); fn(b.dataset, b, e); }
});

/* ---------- Thông báo, gợi ý chuột ---------- */
function rootEl(id, tag = 'div') {
  let el = document.getElementById(id);
  if (!el) { el = document.createElement(tag); el.id = id; el.dataset.runtime = '1'; document.body.appendChild(el); }
  return el;
}
function toast(msg, type = '', ms = 3200) {
  const t = document.createElement('div');
  t.className = 'toast ' + type; t.innerHTML = msg;
  rootEl('toasts').appendChild(t);
  setTimeout(() => { t.style.transition = 'opacity .3s'; t.style.opacity = 0; setTimeout(() => t.remove(), 300); }, ms);
}
(function tipInit() {
  document.addEventListener('mouseover', e => {
    const el = e.target.closest && e.target.closest('[data-tip]');
    const tip = rootEl('tip');
    if (!el) { tip.style.display = 'none'; return; }
    tip.textContent = el.getAttribute('data-tip'); tip.style.display = 'block';
  });
  document.addEventListener('mousemove', e => {
    const tip = document.getElementById('tip'); if (!tip || tip.style.display !== 'block') return;
    const w = tip.offsetWidth, h = tip.offsetHeight;
    let x = e.clientX + 14, y = e.clientY - h - 10;
    if (x + w > innerWidth - 8) x = e.clientX - w - 14;
    if (y < 8) y = e.clientY + 16;
    tip.style.left = x + 'px'; tip.style.top = y + 'px';
  });
})();

/* ---------- Modal, xác nhận ---------- */
const Modal = {
  open({ title, body = '', width = 640, foot = '', onClose }) {
    const wrap = document.createElement('div');
    wrap.className = 'modal-wrap'; wrap.dataset.runtime = '1';
    wrap.innerHTML = `<div class="modal" style="max-width:${width}px"><div class="modal-h"><h3>${title}</h3><button class="ibtn" data-x>${ic('x')}</button></div><div class="modal-b">${body}</div>${foot ? `<div class="modal-f">${foot}</div>` : ''}</div>`;
    document.body.appendChild(wrap);
    const close = () => { wrap.remove(); document.removeEventListener('keydown', key); onClose && onClose(); };
    const key = e => { if (e.key === 'Escape' && document.querySelector('.modal-wrap:last-of-type') === wrap) close(); };
    document.addEventListener('keydown', key);
    wrap.querySelector('[data-x]').onclick = close;
    wrap.addEventListener('mousedown', e => { if (e.target === wrap) wrap._down = true; });
    wrap.addEventListener('mouseup', e => { if (e.target === wrap && wrap._down) close(); wrap._down = false; });
    const f = wrap.querySelector('input:not([type=hidden]),select,textarea');
    if (f) setTimeout(() => f.focus(), 30);
    return { el: wrap, close, body: wrap.querySelector('.modal-b') };
  },
  confirm(msg, { ok = 'Đồng ý', danger = false, title = 'Xác nhận' } = {}) {
    return new Promise(res => {
      let done = false;
      const m = Modal.open({ title, width: 440, body: `<div style="font-size:14px">${msg}</div>`, foot: `<button class="btn" data-no>Hủy</button><button class="btn ${danger ? 'danger' : 'pri'}" data-ok>${ok}</button>`, onClose: () => { if (!done) res(false); } });
      m.el.querySelector('[data-no]').onclick = () => m.close();
      m.el.querySelector('[data-ok]').onclick = () => { done = true; m.close(); res(true); };
    });
  }
};

/* ---------- Form ---------- */
function optList(o) { return (o || []).map(x => typeof x === 'object' ? x : { value: x, label: x }); }
function fieldHTML(f, v) {
  if (f.type === 'section') return `<div class="fsec">${f.label}</div>`;
  const nm = `name="${f.key}"`, req = f.required ? ' <i class="req">*</i>' : '';
  const cls = 'field' + (f.full ? ' full' : '') + (f.s2 ? ' s2' : '');
  const ph = f.ph ? ` placeholder="${esc(f.ph)}"` : '';
  let input;
  v = v ?? f.def ?? '';
  switch (f.type) {
    case 'select': input = `<select class="inp" ${nm}>${f.blank ? `<option value="">${f.blank}</option>` : ''}${optList(typeof f.options === 'function' ? f.options() : f.options).map(o => `<option value="${esc(o.value)}" ${String(o.value) === String(v) ? 'selected' : ''}>${esc(o.label)}</option>`).join('')}</select>`; break;
    case 'textarea': input = `<textarea class="inp" ${nm} rows="${f.rows || 3}"${ph}>${esc(v)}</textarea>`; break;
    case 'money': input = `<input class="inp num" ${nm} data-money inputmode="numeric" value="${v === '' ? '' : F.money(v)}"${ph}>`; break;
    case 'number': input = `<input class="inp num" type="number" step="${f.step || 'any'}" ${f.min !== undefined ? `min="${f.min}"` : ''} ${nm} value="${esc(v)}"${ph}>`; break;
    case 'checkbox': return `<label class="${cls} chk"><input type="checkbox" ${nm} ${v ? 'checked' : ''}> <span>${f.label}</span></label>`;
    case 'image': return `<div class="${cls}"><span>${f.label}</span><div class="img-pick"><div class="ph" data-ph>${v ? `<img src="${v}">` : ic('upload')}</div><div class="row"><label class="btn sm">${ic('upload')} Chọn ảnh<input type="file" accept="image/*" hidden data-img="${f.key}" data-max="${f.max || 240}"></label><button type="button" class="btn sm" data-imgclear="${f.key}">Bỏ ảnh</button></div><input type="hidden" ${nm} data-image value="${esc(v)}"></div>${f.hint ? `<small>${f.hint}</small>` : ''}</div>`;
    default: input = `<input class="inp" type="${f.type || 'text'}" ${nm} value="${esc(v)}"${ph}${f.list ? ` list="dl_${f.key}"` : ''}>${f.list ? `<datalist id="dl_${f.key}">${(typeof f.list === 'function' ? f.list() : f.list).map(x => `<option value="${esc(x)}">`).join('')}</datalist>` : ''}`;
  }
  return `<label class="${cls}"><span>${f.label}${req}</span>${input}${f.hint ? `<small>${f.hint}</small>` : ''}</label>`;
}
function formHTML(fields, val = {}, cols = 2) {
  return `<div class="form ${cols === 3 ? 'c3' : cols === 4 ? 'c4' : ''}">${fields.map(f => fieldHTML(f, val[f.key])).join('')}</div>`;
}
function readForm(root, fields) {
  const out = {};
  for (const f of fields) {
    if (f.type === 'section') continue;
    const el = root.querySelector(`[name="${f.key}"]`); if (!el) continue;
    if (f.type === 'money') out[f.key] = F.parse(el.value);
    else if (f.type === 'number') out[f.key] = el.value === '' ? 0 : parseFloat(el.value);
    else if (f.type === 'checkbox') out[f.key] = el.checked;
    else out[f.key] = el.value.trim();
  }
  return out;
}
function checkForm(root, fields) {
  for (const f of fields) {
    if (!f.required) continue;
    const el = root.querySelector(`[name="${f.key}"]`);
    if (el && !String(el.value).trim()) { el.focus(); toast(`Vui lòng nhập "${f.label}"`, 'err'); return false; }
  }
  return true;
}
document.addEventListener('input', e => {
  const el = e.target;
  if (el.matches && el.matches('[data-money]')) {
    const neg = /^\s*-/.test(el.value); const d = el.value.replace(/[^\d]/g, '');
    el.value = d ? (neg ? '-' : '') + Number(d).toLocaleString('vi-VN') : (neg ? '-' : '');
  }
});
document.addEventListener('change', e => {
  const el = e.target;
  if (el.matches && el.matches('[data-img]') && el.files[0]) {
    const max = +el.dataset.max || 240;
    resizeImage(el.files[0], max).then(url => {
      const box = el.closest('.img-pick');
      box.querySelector('[data-image]').value = url;
      box.querySelector('[data-ph]').innerHTML = `<img src="${url}">`;
    }).catch(() => toast('Không đọc được ảnh', 'err'));
  }
});
document.addEventListener('click', e => {
  const b = e.target.closest && e.target.closest('[data-imgclear]');
  if (!b) return;
  const box = b.closest('.img-pick'); box.querySelector('[data-image]').value = ''; box.querySelector('[data-ph]').innerHTML = ic('upload');
});
function resizeImage(file, max) {
  return new Promise((res, rej) => {
    const r = new FileReader();
    r.onload = () => {
      const img = new Image();
      img.onload = () => {
        const k = Math.min(1, max / Math.max(img.width, img.height));
        const c = document.createElement('canvas'); c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
        const g = c.getContext('2d');
        const png = /png|svg|gif/.test(file.type);
        if (!png) { g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); }
        g.drawImage(img, 0, 0, c.width, c.height);
        res(png ? c.toDataURL('image/png') : c.toDataURL('image/jpeg', .85));
      };
      img.onerror = rej; img.src = r.result;
    };
    r.onerror = rej; r.readAsDataURL(file);
  });
}
const Form = {
  open({ title, fields, value = {}, width = 680, cols = 2, onSave, onDelete, saveText = 'Lưu', extra = '' }) {
    const m = Modal.open({
      title, width,
      body: formHTML(fields, value, cols) + extra,
      foot: `${onDelete ? `<button class="btn danger left" data-del>${ic('trash')} Xóa</button>` : ''}<button class="btn" data-cancel>Hủy</button><button class="btn pri" data-save>${ic('check')} ${saveText}</button>`
    });
    m.el.querySelector('[data-cancel]').onclick = () => m.close();
    const save = () => {
      if (!checkForm(m.el, fields)) return;
      const v = readForm(m.el, fields);
      if (onSave(v, m) !== false) m.close();
    };
    m.el.querySelector('[data-save]').onclick = save;
    m.el.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'file') { e.preventDefault(); save(); } });
    if (onDelete) m.el.querySelector('[data-del]').onclick = async () => { if (await Modal.confirm('Xóa mục này? Thao tác không hoàn tác được.', { ok: 'Xóa', danger: true })) { onDelete(); m.close(); } };
    return m;
  }
};

/* ---------- Chia trang (dùng cho mọi bảng) ---------- */
const UI = { tbl: {}, pg: {} };
const PAGE_SIZES = [10, 20, 50, 100, 200];
function pgState(id, size = 20) { return UI.pg[id] = UI.pg[id] || { page: 0, size }; }
function pageSlice(st, list) {
  const pages = Math.max(1, Math.ceil(list.length / st.size));
  if (st.page >= pages) st.page = pages - 1; if (st.page < 0) st.page = 0;
  return list.slice(st.page * st.size, st.page * st.size + st.size);
}
function pagerHTML(st, total, note = '') {
  const pages = Math.max(1, Math.ceil(total / st.size)), p = st.page;
  const from = total ? p * st.size + 1 : 0, to = Math.min(total, (p + 1) * st.size);
  let nums = []; const add = i => nums.push(`<button class="pg-n ${i === p ? 'on' : ''}" data-pg="${i}">${i + 1}</button>`);
  if (pages <= 7) for (let i = 0; i < pages; i++) add(i);
  else { add(0); const a = Math.max(1, p - 1), b = Math.min(pages - 2, p + 1); if (a > 1) nums.push('<span class="pg-dot">…</span>'); for (let i = a; i <= b; i++) add(i); if (b < pages - 2) nums.push('<span class="pg-dot">…</span>'); add(pages - 1); }
  return `<span>Hiển thị <b>${from}-${to}</b> / ${total} dòng${note}</span><span class="pg">Số dòng/trang <select class="inp sm" data-psize style="width:auto">${PAGE_SIZES.map(s => `<option ${s === st.size ? 'selected' : ''}>${s}</option>`).join('')}</select>${pages > 1 ? `<button class="pg-n" data-pg="${p - 1}" ${p ? '' : 'disabled'}>‹</button>${nums.join('')}<button class="pg-n" data-pg="${p + 1}" ${p < pages - 1 ? '' : 'disabled'}>›</button>` : ''}</span>`;
}
// Gắn sự kiện cho thanh chia trang; redraw() vẽ lại phần bảng
function bindPager(el, st, redraw) {
  el.addEventListener('click', e => { const b = e.target.closest('[data-pg]'); if (!b || b.disabled) return; st.page = +b.dataset.pg; redraw(); const w = el.closest('.tbl-card,.pg-host'); const sc = w && w.querySelector('.tbl-wrap,.cc-wrap'); if (sc) sc.scrollTop = 0; });
  el.addEventListener('change', e => { const s = e.target.closest('[data-psize]'); if (!s) return; st.size = +s.value; st.page = 0; redraw(); });
}

/* ---------- Bảng dữ liệu ---------- */
function colText(c, r) { const v = c.val ? c.val(r) : r[c.key]; return v == null ? '' : v; }
function cellHTML(c, r) {
  if (c.render) return c.render(r);
  const v = colText(c, r);
  switch (c.fmt) {
    case 'money': return F.money(v);
    case 'date': return F.date(v);
    case 'pct': return F.pct(v);
    case 'num': return F.num(v, c.dec ?? 1);
    default: return esc(v);
  }
}
function table(el, cfg) {
  const st = UI.tbl[cfg.id] = UI.tbl[cfg.id] || { q: '', sort: cfg.sort ?? null, dir: cfg.dir || 1, page: 0, size: cfg.pageSize || 20 };
  const cols = cfg.columns;
  const al = c => c.align === 'r' || ['money', 'num', 'pct'].includes(c.fmt) ? 'r' : c.align === 'c' || c.fmt === 'date' ? 'c' : '';
  el.innerHTML = `<div class="tbl-card">
    ${cfg.search === false && !cfg.tools ? '' : `<div class="tbl-tools">${cfg.search === false ? '' : `<div class="search">${ic('search')}<input class="inp" placeholder="${cfg.ph || 'Tìm kiếm...'}" value="${esc(st.q)}" data-q></div>`}${cfg.tools || ''}</div>`}
    <div class="tbl-wrap ${cfg.free ? 'free' : ''}"><table class="tbl ${cfg.compact ? 'compact' : ''}"><thead><tr>${cols.map((c, i) => `<th class="${al(c)} ${c.nosort ? '' : 'sort'} ${st.sort === i ? (st.dir > 0 ? 'asc' : 'desc') : ''}" data-i="${i}" ${c.w ? `style="width:${c.w}"` : ''}>${c.label}</th>`).join('')}${cfg.actions ? '<th class="act"></th>' : ''}</tr></thead><tbody></tbody><tfoot></tfoot></table></div>
    <div class="tbl-pager"></div></div>`;
  const tbody = $('tbody', el), tfoot = $('tfoot', el), pager = $('.tbl-pager', el);
  let rows = [];
  const draw = () => {
    const q = st.q.trim().toLowerCase();
    rows = cfg.rows.filter(r => !q || cols.some(c => String(colText(c, r)).toLowerCase().includes(q)) || (cfg.searchText && cfg.searchText(r).toLowerCase().includes(q)));
    if (st.sort !== null && cols[st.sort]) {
      const c = cols[st.sort];
      const sv = r => c.sortVal ? c.sortVal(r) : colText(c, r);
      rows.sort((a, b) => { const x = sv(a), y = sv(b); return (typeof x === 'number' && typeof y === 'number' ? x - y : String(x).localeCompare(String(y), 'vi')) * st.dir; });
    }
    const view = pageSlice(st, rows);
    tbody.innerHTML = view.length ? view.map(r => `<tr class="${cfg.rowClass ? cfg.rowClass(r) : ''} ${cfg.onRow ? 'click' : ''}" data-rid="${esc(r.id)}">${cols.map(c => `<td class="${al(c)} ${c.cls || ''}">${cellHTML(c, r)}</td>`).join('')}${cfg.actions ? `<td class="act">${cfg.actions(r)}</td>` : ''}</tr>`).join('')
      : `<tr><td colspan="${cols.length + (cfg.actions ? 1 : 0)}"><div class="empty">${cfg.empty || 'Chưa có dữ liệu'}</div></td></tr>`;
    if (cols.some(c => c.sum) && rows.length) {
      tfoot.innerHTML = `<tr>${cols.map((c, i) => `<td class="${al(c)}">${c.sum ? (c.fmt === 'num' ? F.num(sum(rows, r => colText(c, r)), c.dec ?? 1) : F.money(sum(rows, r => colText(c, r)))) : i === 0 ? 'Tổng cộng' : ''}</td>`).join('')}${cfg.actions ? '<td></td>' : ''}</tr>`;
    } else tfoot.innerHTML = '';
    pager.innerHTML = pagerHTML(st, rows.length, q ? ` (lọc từ ${cfg.rows.length})` : '');
  };
  const qi = $('[data-q]', el);
  if (qi) qi.addEventListener('input', () => { st.q = qi.value; st.page = 0; draw(); });
  $('thead', el).addEventListener('click', e => {
    const th = e.target.closest('th.sort'); if (!th) return;
    const i = +th.dataset.i; if (st.sort === i) st.dir = -st.dir; else { st.sort = i; st.dir = 1; }
    $$('th', el).forEach(t => t.classList.remove('asc', 'desc')); th.classList.add(st.dir > 0 ? 'asc' : 'desc'); draw();
  });
  bindPager(pager, st, draw);
  if (cfg.onRow) tbody.addEventListener('click', e => { if (e.target.closest('button,a,input,select,label')) return; const tr = e.target.closest('tr[data-rid]'); if (tr) { const r = cfg.rows.find(x => String(x.id) === tr.dataset.rid); if (r) cfg.onRow(r); } });
  draw();
  return { draw, rows: () => rows };
}
const rowBtns = (id, edit = 'edit', del = 'del', more = '') => `${more}<button class="ibtn" data-act="${edit}" data-id="${id}" data-tip="Sửa">${ic('edit')}</button><button class="ibtn del" data-act="${del}" data-id="${id}" data-tip="Xóa">${ic('trash')}</button>`;

/* ---------- Biểu đồ SVG ---------- */
let PALETTE = ['#2563eb', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16', '#64748b', '#f97316'];
function niceMax(v) {
  if (v <= 0) return 1;
  const e = Math.pow(10, Math.floor(Math.log10(v))); const f = v / e;
  return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 2.5 ? 2.5 : f <= 5 ? 5 : 10) * e;
}
const Chart = {
  legend(series) { return `<div class="legend">${series.map((s, i) => `<span><i style="background:${s.color || PALETTE[i]}"></i>${esc(s.name)}</span>`).join('')}</div>`; },
  bar({ labels, series, h = 250, stacked = false, fmt = F.short, tipFmt = F.money, legend = true }) {
    const W = 640, H = h, L = 56, R = 10, T = 12, B = 30;
    const iw = W - L - R, ih = H - T - B, n = labels.length || 1;
    const totals = labels.map((_, i) => stacked ? sum(series, s => Math.max(0, s.values[i])) : Math.max(0, ...series.map(s => s.values[i] || 0)));
    const max = niceMax(Math.max(...totals, 0)); const band = iw / n;
    let g = '';
    for (let k = 0; k <= 4; k++) { const y = T + ih - ih * k / 4; g += `<line x1="${L}" x2="${W - R}" y1="${y}" y2="${y}" stroke="#eef0f5" ${k ? 'stroke-dasharray="3 4"' : ''}/><text x="${L - 8}" y="${y + 4}" text-anchor="end" font-size="11" fill="#98a2b3">${fmt(max * k / 4)}</text>`; }
    const every = Math.ceil(n / 14);
    labels.forEach((lb, i) => {
      const x0 = L + band * i;
      if (i % every === 0) g += `<text x="${x0 + band / 2}" y="${H - 9}" text-anchor="middle" font-size="11" fill="#667085">${esc(lb)}</text>`;
      if (stacked) {
        let acc = 0; const bw = Math.min(46, band * .58);
        series.forEach((s, si) => { const v = Math.max(0, s.values[i] || 0); if (!v) return; const bh = ih * v / max; const y = T + ih - ih * acc / max - bh; acc += v;
          g += `<rect x="${x0 + (band - bw) / 2}" y="${y}" width="${bw}" height="${Math.max(bh, .5)}" fill="${s.color || PALETTE[si]}" rx="${si === series.length - 1 ? 3 : 0}" data-tip="${esc(lb)}\n${esc(s.name)}: ${tipFmt(v)}"/>`; });
      } else {
        const gw = Math.min(band * .72, 30 * series.length + 8), bw = gw / series.length;
        series.forEach((s, si) => { const v = Math.max(0, s.values[i] || 0); const bh = ih * v / max;
          g += `<rect x="${x0 + (band - gw) / 2 + bw * si + 1}" y="${T + ih - bh}" width="${Math.max(bw - 2, 2)}" height="${Math.max(bh, v ? 1 : 0)}" fill="${s.color || PALETTE[si]}" rx="3" data-tip="${esc(lb)}\n${esc(s.name)}: ${tipFmt(v)}"/>`; });
      }
    });
    return `<div class="chart">${legend && series.length > 1 ? Chart.legend(series) : ''}<svg viewBox="0 0 ${W} ${H}">${g}</svg></div>`;
  },
  line({ labels, series, h = 250, fmt = F.short, tipFmt = F.money, area = true }) {
    const W = 640, H = h, L = 56, R = 16, T = 14, B = 30, iw = W - L - R, ih = H - T - B, n = labels.length;
    const max = niceMax(Math.max(0, ...series.flatMap(s => s.values)));
    const X = i => L + (n === 1 ? iw / 2 : iw * i / (n - 1)), Y = v => T + ih - ih * Math.max(0, v) / max;
    let g = '<defs>' + series.map((s, si) => `<linearGradient id="lg${si}" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="${s.color || PALETTE[si]}" stop-opacity=".22"/><stop offset="1" stop-color="${s.color || PALETTE[si]}" stop-opacity="0"/></linearGradient>`).join('') + '</defs>';
    for (let k = 0; k <= 4; k++) { const y = T + ih - ih * k / 4; g += `<line x1="${L}" x2="${W - R}" y1="${y}" y2="${y}" stroke="#eef0f5" ${k ? 'stroke-dasharray="3 4"' : ''}/><text x="${L - 8}" y="${y + 4}" text-anchor="end" font-size="11" fill="#98a2b3">${fmt(max * k / 4)}</text>`; }
    const every = Math.ceil(n / 12);
    labels.forEach((lb, i) => { if (i % every === 0) g += `<text x="${X(i)}" y="${H - 9}" text-anchor="middle" font-size="11" fill="#667085">${esc(lb)}</text>`; });
    series.forEach((s, si) => {
      const c = s.color || PALETTE[si]; const pts = s.values.map((v, i) => `${X(i)},${Y(v)}`).join(' ');
      if (area && n > 1) g += `<polygon points="${X(0)},${T + ih} ${pts} ${X(n - 1)},${T + ih}" fill="url(#lg${si})"/>`;
      g += `<polyline points="${pts}" fill="none" stroke="${c}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>`;
      s.values.forEach((v, i) => { g += `<circle cx="${X(i)}" cy="${Y(v)}" r="4" fill="#fff" stroke="${c}" stroke-width="2" data-tip="${esc(labels[i])}\n${esc(s.name)}: ${tipFmt(v)}"/>`; });
    });
    return `<div class="chart">${series.length > 1 ? Chart.legend(series) : ''}<svg viewBox="0 0 ${W} ${H}">${g}</svg></div>`;
  },
  donut({ items, center = '', centerSub = 'Tổng', fmt = F.money }) {
    const tot = sum(items, x => x.value); const r = 62, C = 2 * Math.PI * r; let off = 0, g = '';
    if (!tot) g = `<circle cx="85" cy="85" r="${r}" fill="none" stroke="#eef0f5" stroke-width="24"/>`;
    items.forEach((it, i) => { if (!it.value) return; const len = C * it.value / tot; const col = it.color || PALETTE[i % PALETTE.length];
      g += `<circle cx="85" cy="85" r="${r}" fill="none" stroke="${col}" stroke-width="24" stroke-dasharray="${len} ${C - len}" stroke-dashoffset="${-off}" transform="rotate(-90 85 85)" data-tip="${esc(it.label)}: ${fmt(it.value)} (${F.pct(it.value / tot * 100)})"/>`; off += len; });
    g += `<text x="85" y="82" text-anchor="middle" font-size="17" font-weight="700" fill="#1f2937">${esc(center || F.short(tot))}</text><text x="85" y="101" text-anchor="middle" font-size="11" fill="#98a2b3">${esc(centerSub)}</text>`;
    return `<div class="donut"><svg viewBox="0 0 170 170">${g}</svg><div class="dl">${items.map((it, i) => `<div><i style="background:${it.color || PALETTE[i % PALETTE.length]}"></i><span>${esc(it.label)}</span><b>${fmt(it.value)}</b><em>${tot ? F.pct(it.value / tot * 100) : '0%'}</em></div>`).join('')}</div></div>`;
  },
  hbars({ items, fmt = F.money, color, empty = 'Chưa có dữ liệu' }) {
    if (!items.length) return `<div class="empty">${empty}</div>`;
    const max = Math.max(...items.map(x => x.value), 1);
    return `<div class="hbars">${items.map((x, i) => `<div class="hb"><div class="hb-top"><span>${esc(x.label)}${x.sub ? ` <small class="muted">${esc(x.sub)}</small>` : ''}</span><b>${fmt(x.value)}</b></div><div class="hb-bar"><i style="width:${Math.max(1.5, x.value / max * 100)}%;background:${x.color || color || 'var(--p)'}"></i></div></div>`).join('')}</div>`;
  },
  funnel({ items, fmt = v => v }) {
    const max = Math.max(...items.map(x => x.value), 1);
    return `<div class="funnel">${items.map((x, i) => `<div class="fn"><div class="fn-l">${esc(x.label)}</div><div class="fn-b"><i style="width:${Math.max(6, x.value / max * 100)}%;background:${x.color || PALETTE[i % PALETTE.length]}" data-tip="${esc(x.label)}: ${fmt(x.value)}">${fmt(x.value)}</i></div></div>`).join('')}</div>`;
  }
};
const kpi = ({ label, value, sub = '', icon = 'trend', tone = '' }) => `<div class="kpi ${tone ? 't-' + tone : ''}"><div class="k-ic">${ic(icon)}</div><div class="k-lb">${label}</div><div class="k-v" title="${esc(String(value).replace(/<[^>]+>/g, ''))}">${value}</div>${sub ? `<div class="k-sub">${sub}</div>` : ''}</div>`;
const card = (title, body, { sub = '', right = '', cls = '', bcls = '' } = {}) => `<div class="card ${cls}"><div class="card-h"><h3>${title}</h3>${sub ? `<span class="sub">${sub}</span>` : ''}${right ? `<div class="right">${right}</div>` : ''}</div><div class="card-b ${bcls}">${body}</div></div>`;

/* ---------- Xuất Excel (.xlsx thật, không cần mạng) ---------- */
const XLSX = (() => {
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
      { name: 'xl/styles.xml', data: styles(App.cfg.theme.p) },
      ...sheets.map((s, i) => ({ name: `xl/worksheets/sheet${i + 1}.xml`, data: sheetXML(s) }))
    ];
    download(zip(files), fileName.replace(/\.xlsx$/i, '') + '.xlsx');
    toast(`${ic('check')} Đã xuất file Excel <b>${esc(fileName)}.xlsx</b>`, 'ok');
  }
  function filterRow(s) { return (s.title ? 1 : 0) + (s.subtitles || []).length + ((s.title || (s.subtitles || []).length) ? 1 : 0) + 1; }
  return { save };
})();
// Biến cột của bảng thành cột Excel
function xCols(cols) {
  return cols.filter(c => !c.noExport).map(c => ({ label: String(c.label).replace(/<[^>]+>/g, ''), key: c.key, type: c.xtype || ({ money: 'money', date: 'date', pct: 'pct', num: 'num' }[c.fmt] || 'text'), sum: !!c.sum, width: c.xw, _c: c }));
}
function xRows(cols, rows) {
  return rows.map(r => cols.map(c => { const v = c._c.xval ? c._c.xval(r) : colText(c._c, r); return typeof v === 'string' ? v.replace(/<[^>]+>/g, '') : v; }));
}
function xSheet(name, title, cols, rows, extra = {}) {
  const xc = xCols(cols);
  return Object.assign({ name, title, subtitles: [companyLine(), 'Ngày xuất: ' + F.date(D.today())], columns: xc, rows: xRows(xc, rows), totals: xc.some(c => c.sum) }, extra);
}
function companyLine() { const c = (DB.settings && DB.settings.company) || {}; return [c.ten, c.diaChi, c.sdt ? 'ĐT: ' + c.sdt : ''].filter(Boolean).join(' · '); }
function download(blob, name) {
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name;
  document.body.appendChild(a); a.click(); setTimeout(() => { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
}

/* ---------- In ---------- */
const Print = {
  run(html, { landscape = false, title } = {}) {
    $$('#print-root,#print-page').forEach(n => n.remove());
    const root = document.createElement('div'); root.id = 'print-root'; root.dataset.runtime = '1'; root.innerHTML = html;
    document.body.appendChild(root);
    const st = document.createElement('style'); st.id = 'print-page'; st.dataset.runtime = '1';
    st.textContent = `@page{size:A4 ${landscape ? 'landscape' : 'portrait'};margin:11mm 10mm}`;
    document.head.appendChild(st);
    document.body.classList.add('is-printing');
    const t0 = document.title; if (title) document.title = title;
    const done = () => { document.body.classList.remove('is-printing'); root.remove(); st.remove(); document.title = t0; window.removeEventListener('afterprint', done); };
    window.addEventListener('afterprint', done);
    setTimeout(() => { window.print(); setTimeout(() => { if (document.body.classList.contains('is-printing')) done(); }, 800); }, 120);
  },
  head(meta = '') {
    const c = DB.settings.company || {};
    return `<div class="pr-head">${c.logo ? `<img src="${c.logo}">` : ''}<div class="pr-co"><b>${esc(c.ten || '')}</b>${c.diaChi ? `<div>${esc(c.diaChi)}</div>` : ''}<div>${[c.sdt ? 'ĐT: ' + esc(c.sdt) : '', c.email ? 'Email: ' + esc(c.email) : '', c.mst ? 'MST: ' + esc(c.mst) : ''].filter(Boolean).join(' · ')}</div></div><div class="pr-meta">${meta || 'Ngày in: ' + F.date(D.today())}</div></div>`;
  },
  signs(list, date = true) {
    const d = new Date();
    return `${date ? `<div style="text-align:right;margin-top:16px;font-style:italic">Ngày ${d.getDate()} tháng ${d.getMonth() + 1} năm ${d.getFullYear()}</div>` : ''}<div class="pr-signs">${list.map(s => `<div><b>${s}</b><i>(Ký, ghi rõ họ tên)</i><div class="sp"></div></div>`).join('')}</div>`;
  },
  table(cols, rows, totals = true) {
    const xc = xCols(cols);
    const body = rows.map((r, ri) => `<tr>${xc.map(c => { const v = c._c.xval ? c._c.xval(r) : colText(c._c, r); const t = c.type; return `<td class="${['money', 'num', 'pct'].includes(t) ? 'r' : t === 'date' || t === 'center' ? 'c' : ''}">${t === 'money' ? F.money(v) : t === 'num' ? F.num(v) : t === 'pct' ? F.pct(v) : t === 'date' ? F.date(v) : esc(v)}</td>`; }).join('')}</tr>`).join('');
    const foot = totals && xc.some(c => c.sum) ? `<tfoot><tr>${xc.map((c, i) => `<td class="${c.sum ? 'r' : ''}">${c.sum ? (c.type === 'num' ? F.num(sum(rows, r => colText(c._c, r))) : F.money(sum(rows, r => c._c.xval ? c._c.xval(r) : colText(c._c, r)))) : i === 0 ? 'Tổng cộng' : ''}</td>`).join('')}</tr></tfoot>` : '';
    return `<table><thead><tr>${xc.map(c => `<th>${esc(c.label)}</th>`).join('')}</tr></thead><tbody>${body}</tbody>${foot}</table>`;
  },
  report({ title, sub = '', cols, rows, landscape = false, signs = ['Người lập biểu', 'Kế toán trưởng', 'Giám đốc'], before = '', after = '' }) {
    Print.run(`<div class="pr">${Print.head()}<h2>${title}</h2>${sub ? `<div class="pr-sub">${sub}</div>` : ''}${before}${Print.table(cols, rows)}${after}${signs ? Print.signs(signs) : ''}</div>`, { landscape, title });
  },
  view() {
    const v = $('#view').cloneNode(true);
    $$('input', v).forEach(i => { if (i.type !== 'checkbox') i.setAttribute('value', i.value); });
    $$('select', v).forEach(s => { const o = s.options[s.selectedIndex]; if (o) o.setAttribute('selected', ''); });
    const view = App.views[App.cur];
    Print.run(`<div class="pr">${Print.head()}<h2>${esc(view.title)}</h2>${App.subtitle ? `<div class="pr-sub">${esc(App.subtitle)}</div>` : ''}</div>${v.innerHTML}`, { landscape: true, title: view.title });
  }
};

/* ---------- Lưu trữ: dữ liệu nằm ngay trong file HTML ---------- */
let DB = null;
const IDB = {
  open() { return new Promise((res, rej) => { const r = indexedDB.open('phongexcel-html', 1); r.onupgradeneeded = () => r.result.createObjectStore('h'); r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error); }); },
  async get(k) { const db = await IDB.open(); return new Promise(res => { const q = db.transaction('h').objectStore('h').get(k); q.onsuccess = () => res(q.result); q.onerror = () => res(null); }); },
  async set(k, v) { const db = await IDB.open(); return new Promise(res => { const t = db.transaction('h', 'readwrite'); t.objectStore('h').put(v, k); t.oncomplete = () => res(); t.onerror = () => res(); }); },
  async del(k) { const db = await IDB.open(); return new Promise(res => { const t = db.transaction('h', 'readwrite'); t.objectStore('h').delete(k); t.oncomplete = () => res(); t.onerror = () => res(); }); }
};
const Store = {
  handle: null, dirty: false, timer: null,
  key() { return 'px:' + App.cfg.id + ':' + decodeURIComponent(location.pathname); },
  fileName() { const n = decodeURIComponent(location.pathname.split('/').pop() || ''); return /\.html?$/i.test(n) ? n : App.cfg.fileName; },
  load() {
    let draft = null;
    try { draft = JSON.parse(localStorage.getItem(Store.key()) || 'null'); } catch (e) {}
    DB = draft && draft.data && typeof draft.data === 'object' ? draft.data : App.cfg.sample();
    DB._meta = DB._meta || {};
    if (!draft) DB._meta.sample = true;
    App.cfg.migrate && App.cfg.migrate(DB);
  },
  serialize() {
    DB._meta.savedAt = new Date().toISOString(); delete DB._meta.sample;
    const doc = document.documentElement.cloneNode(true);
    doc.querySelector('#app').innerHTML = '';
    doc.querySelectorAll('[data-runtime]').forEach(n => n.remove());
    const body = doc.querySelector('body'); body.removeAttribute('class'); body.removeAttribute('style');
    doc.querySelector('#app-data').textContent = JSON.stringify(DB).replace(/</g, '\\u003c');
    return '<!DOCTYPE html>\n' + doc.outerHTML;
  },
  changed() {
    Store.dirty = true; Store.status();
    clearTimeout(Store.timer);
    Store.timer = setTimeout(() => { try { localStorage.setItem(Store.key(), JSON.stringify({ at: new Date().toISOString(), data: DB })); } catch (e) { } }, 600);
  },
  status() {
    const el = $('#saveState'); if (!el) return;
    el.classList.toggle('dirty', Store.dirty);
    el.querySelector('span').textContent = Store.dirty ? 'Có thay đổi chưa lưu vào file' : DB._meta.savedAt ? 'Đã lưu ' + new Date(DB._meta.savedAt).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }) : 'Đang dùng dữ liệu mẫu';
  },
  saved() {
    Store.dirty = false; Store.status();
    /* Browser-local save is retained across reloads. */
  },
  async guide() {
    let seen = false; try { seen = localStorage.getItem('px:guide') === '1'; } catch (e) { }
    if (seen) return true;
    const ok = await Modal.confirm(`<p style="margin-top:0">Lần đầu lưu, cửa sổ chọn file sẽ hiện ra. Thầy/cô <b>chọn đúng file đang mở</b>:</p><p style="padding:10px 12px;background:var(--p-50);border-radius:10px;font-weight:600">${esc(Store.fileName())}</p><p>rồi bấm <b>Save / Lưu</b> và đồng ý <b>Replace / Thay thế</b>.</p><p class="muted small" style="margin-bottom:0">Từ lần sau, bấm "Lưu trong trình duyệt" (hoặc Ctrl + S) là ghi đè ngay, không hỏi lại.</p>`, { title: 'Lưu dữ liệu trong trình duyệt', ok: 'Đã hiểu, chọn file' });
    if (ok) try { localStorage.setItem('px:guide', '1'); } catch (e) { }
    return ok;
  },
  async save(pickNew = false) {
    DB._meta.savedAt = new Date().toISOString();
    delete DB._meta.sample;
    try {
      localStorage.setItem(Store.key(), JSON.stringify({ at: DB._meta.savedAt, data: DB }));
      Store.dirty = false;
      Store.status();
      if (pickNew) {
        download(new Blob([JSON.stringify(Object.assign({ _app: App.cfg.id }, DB), null, 2)], { type: 'application/json' }), 'Cong-no-'+D.today()+'.json');
        toast('Đã tải bản sao lưu JSON về máy.', 'ok');
      } else toast('Đã lưu dữ liệu trong trình duyệt này.', 'ok');
    } catch (e) { toast('Không thể lưu dữ liệu: '+esc(e.message), 'err'); }
  },
  draft() { try { const d = JSON.parse(localStorage.getItem(Store.key()) || 'null'); if (d && d.data && (!DB._meta.savedAt || d.at > DB._meta.savedAt)) return d; } catch (e) { } return null; }
};
window.addEventListener('beforeunload', e => { if (Store.dirty) { e.preventDefault(); e.returnValue = ''; } });
document.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); Store.save(); } });

/* ---------- Ứng dụng ---------- */
const App = {
  cfg: null, views: {}, cur: null, subtitle: '',
  init(cfg) {
    App.cfg = cfg; App.views = cfg.views;
    PALETTE = [cfg.theme.p, ...(cfg.palette || PALETTE.filter(c => c !== cfg.theme.p))];
    Store.load();
    App.views.data = App.views.data || DataScreen;
    const navHTML = cfg.nav.map(g => `<div class="nav-group">${g.group}</div>${g.items.map(id => { const v = App.views[id]; return `<a data-nav="${id}">${ic(v.icon)}<span>${v.label || v.title}</span></a>`; }).join('')}`).join('')
      + `<div class="nav-group">Hệ thống</div><a data-nav="data">${ic('database')}<span>Dữ liệu & sao lưu</span></a>`;
    $('#app').innerHTML = `<div class="shell"><aside class="sidebar"><div class="brand"><div class="logo">${ic(cfg.icon)}</div><div><b>${cfg.title}</b><small>${cfg.tagline}</small></div></div><nav class="nav">${navHTML}</nav><div class="side-foot"><div class="save-state" id="saveState"><i></i><span></span></div><button class="btn-save" data-act="saveFile">${ic('save')} Lưu trong trình duyệt</button><div class="credit">Phong Excel Academy</div></div></aside><div class="backdrop" data-act="navClose"></div><main class="main"><header class="topbar"><button class="ibtn menu-btn" data-act="navOpen">${ic('menu')}</button><div><h1 id="vTitle"></h1><p id="vDesc"></p></div><div class="top-actions" id="vTools"></div></header><section class="content"><div id="draftBox"></div><div id="view"></div></section></main></div>`;
    $('#app').addEventListener('click', e => { const a = e.target.closest('[data-nav]'); if (a) { e.preventDefault(); App.go(a.dataset.nav); document.body.classList.remove('nav-open'); } });
    Store.status();
    const d = Store.draft();
    if (d) $('#draftBox').innerHTML = `<div class="banner warn">${ic('alert')}<div class="grow"><b>Có dữ liệu chưa lưu vào file</b> từ lần làm việc trước (${new Date(d.at).toLocaleString('vi-VN')}).</div><button class="btn sm pri" data-act="draftUse">Khôi phục</button><button class="btn sm" data-act="draftDrop">Bỏ qua</button></div>`;
    else if (DB._meta.sample) $('#draftBox').innerHTML = `<div class="banner">${ic('alert')}<div class="grow">File đang có <b>dữ liệu mẫu</b> để xem thử. Khi dùng thật: vào <a data-nav="data" href="#">Dữ liệu & sao lưu</a> bấm "Xóa dữ liệu mẫu", rồi nhập dữ liệu của mình.</div><button class="ibtn" data-act="bannerClose">${ic('x')}</button></div>`;
    window.addEventListener('hashchange', () => { const h = location.hash.slice(1); if (h && h !== App.cur && App.views[h]) App.go(h); });
    const start = (location.hash || '').slice(1);
    App.go(App.views[start] ? start : cfg.home || 'dashboard');
  },
  go(id) {
    if (!App.views[id]) id = App.cfg.home || 'dashboard';
    const old = App.views[App.cur];
    if (old && old.leave && id !== App.cur) { const msg = old.leave(); if (msg && !window.confirm(msg)) { history.replaceState(null, '', '#' + App.cur); return; } }
    App.cur = id; history.replaceState(null, '', '#' + id);
    $$('.nav a').forEach(a => a.classList.toggle('on', a.dataset.nav === id));
    App.render();
    window.scrollTo(0, 0);
  },
  render() {
    const v = App.views[App.cur];
    App.subtitle = '';
    $('#vTitle').textContent = v.title; $('#vDesc').textContent = v.desc || '';
    const el = $('#view'); el.innerHTML = '';
    v.render(el);
    const t = (v.tools ? v.tools() : '') +
      (v.excel ? `<button class="btn xl" data-act="viewExcel">${ic('sheet')} Xuất Excel</button>` : '') +
      (v.print !== false ? `<button class="btn" data-act="viewPrint">${ic('printer')} In</button>` : '');
    $('#vTools').innerHTML = t;
    if (v.after) v.after(el);
    document.title = v.title + ' · ' + App.cfg.title;
  },
  changed(rerender = true) { Store.changed(); if (rerender) App.render(); }
};
Object.assign(ACT, {
  saveFile: () => Store.save(),
  viewExcel: () => App.views[App.cur].excel(),
  viewPrint: () => { const v = App.views[App.cur]; typeof v.print === 'function' ? v.print() : Print.view(); },
  navOpen: () => document.body.classList.add('nav-open'),
  navClose: () => document.body.classList.remove('nav-open'),
  bannerClose: (d, b) => b.closest('.banner').remove(),
  draftUse: () => { const d = Store.draft(); if (d) { DB = d.data; App.cfg.migrate && App.cfg.migrate(DB); Store.dirty = true; Store.status(); $('#draftBox').innerHTML = ''; App.render(); toast('Đã khôi phục. Bấm "Lưu trong trình duyệt" để ghi hẳn vào file.', 'ok'); } },
  draftDrop: () => { try { localStorage.removeItem(Store.key()); } catch (e) { } $('#draftBox').innerHTML = ''; }
});

/* ---------- Màn hình Dữ liệu & sao lưu (chung) ---------- */
const DataScreen = {
  title: 'Dữ liệu & sao lưu', icon: 'database', desc: 'Lưu file, sao lưu, khôi phục, làm mới dữ liệu', print: false,
  render(el) {
    const counts = (App.cfg.counts ? App.cfg.counts() : []).map(([k, v]) => `<div class="dl"><div><span>${k}</span><b>${F.money(v)}</b></div></div>`).join('');
    el.innerHTML = `<div class="grid cols-2">
      ${card('Lưu dữ liệu trong trình duyệt', `<p class="muted" style="margin-top:0">Toàn bộ dữ liệu nằm ngay trong file <b>${esc(Store.fileName())}</b>. Bấm <b>Lưu trong trình duyệt</b> (hoặc Ctrl + S) để ghi đè dữ liệu mới lên file. Gửi file này cho người khác là gửi kèm cả dữ liệu.</p>
        <ol class="small" style="padding-left:18px;line-height:1.8"><li>Lần đầu: chọn đúng file đang mở, bấm Save, chọn Replace.</li><li>Các lần sau: bấm Lưu là xong, không hỏi lại.</li><li>Nên mở bằng <b>Google Chrome</b> hoặc <b>Microsoft Edge</b> để ghi đè trực tiếp được.</li></ol>
        <div class="row"><button class="btn pri" data-act="saveFile">${ic('save')} Lưu trong trình duyệt</button><button class="btn" data-act="saveAs">${ic('copy')} Tải JSON về máy</button></div>`)}
      ${card('Tình trạng dữ liệu', `${counts}<p class="small muted">Lần lưu gần nhất: <b>${DB._meta.savedAt ? new Date(DB._meta.savedAt).toLocaleString('vi-VN') : 'chưa lưu'}</b></p>`)}
      ${card('Sao lưu & khôi phục', `<p class="muted" style="margin-top:0">Tải bản sao lưu dữ liệu (.json) để cất giữ, hoặc chuyển dữ liệu sang file mới hơn.</p><div class="row"><button class="btn" data-act="backup">${ic('download')} Tải bản sao lưu</button><label class="btn">${ic('upload')} Khôi phục từ bản sao lưu<input type="file" accept=".json,application/json" hidden id="restoreFile"></label></div>`)}
      ${card('Làm mới dữ liệu', `<p class="muted" style="margin-top:0">Xóa dữ liệu mẫu để bắt đầu nhập dữ liệu thật. Phần thông tin công ty và cài đặt được giữ lại.</p><div class="row"><button class="btn danger" data-act="wipe">${ic('trash')} Xóa dữ liệu mẫu / xóa hết</button><button class="btn" data-act="loadSample">${ic('refresh')} Nạp lại dữ liệu mẫu</button></div>`)}
    </div>`;
    $('#restoreFile').onchange = e => {
      const f = e.target.files[0]; if (!f) return;
      const r = new FileReader(); r.onload = async () => {
        try { const d = JSON.parse(r.result); if (d._app && d._app !== App.cfg.id) throw new Error('Bản sao lưu của ứng dụng khác'); if (!(await Modal.confirm('Thay toàn bộ dữ liệu hiện tại bằng bản sao lưu này?', { ok: 'Khôi phục' }))) return; delete d._app; DB = d; DB._meta = DB._meta || {}; App.cfg.migrate && App.cfg.migrate(DB); App.changed(); toast('Đã khôi phục. Bấm "Lưu trong trình duyệt" để ghi vào file.', 'ok'); }
        catch (err) { toast('File sao lưu không hợp lệ: ' + esc(err.message), 'err'); }
      }; r.readAsText(f);
    };
  }
};
Object.assign(ACT, {
  saveAs: () => Store.save(true),
  backup: () => download(new Blob([JSON.stringify(Object.assign({ _app: App.cfg.id }, DB), null, 1)], { type: 'application/json' }), `Sao luu - ${App.cfg.title} - ${D.today()}.json`),
  wipe: async () => {
    if (!(await Modal.confirm('Xóa toàn bộ dữ liệu (giữ lại thông tin công ty và cài đặt)?', { ok: 'Xóa hết', danger: true }))) return;
    const keep = DB.settings; DB = App.cfg.empty(); DB.settings = keep; DB._meta = {}; App.cfg.migrate && App.cfg.migrate(DB);
    $('#draftBox').innerHTML = ''; App.changed(); toast('Đã xóa. Nhớ bấm "Lưu trong trình duyệt".', 'ok');
  },
  loadSample: async () => {
    if (!(await Modal.confirm('Thay dữ liệu hiện tại bằng dữ liệu mẫu?', { ok: 'Nạp mẫu', danger: true }))) return;
    const keep = DB.settings.company; DB = App.cfg.sample(); DB.settings.company = keep; DB._meta = {}; App.changed();
  }
});

/* ---------- Tiện ích nhỏ ---------- */
function addCSS(t) { const s = document.createElement('style'); s.dataset.runtime = '1'; s.textContent = t; document.head.appendChild(s); }
// Giữ chỗ con trỏ khi vẽ lại bảng có ô nhập
function keepFocus(redraw) {
  setTimeout(() => {
    const a = document.activeElement; const key = a && a.dataset && a.dataset.k ? `[data-k="${a.dataset.k}"][data-e="${a.dataset.e}"]` : null;
    const wrap = $('#view .tbl-wrap'); const sl = wrap ? wrap.scrollLeft : 0, st = wrap ? wrap.scrollTop : 0, wy = window.scrollY;
    redraw();
    const w2 = $('#view .tbl-wrap'); if (w2) { w2.scrollLeft = sl; w2.scrollTop = st; } window.scrollTo(0, wy);
    if (key) { const n = document.querySelector(key); if (n) { n.focus(); n.select && n.select(); } }
  });
}
function monthPicker(id, val, label = '') { return `<label class="row small" style="gap:6px">${label}<input type="month" class="inp" id="${id}" value="${val}" style="width:160px"></label>`; }
function onVal(id, fn) { const el = document.getElementById(id); if (el) el.addEventListener('change', () => fn(el.value)); }
function selectHTML(id, opts, val, cls = '') { return `<select class="inp ${cls}" id="${id}">${optList(opts).map(o => `<option value="${esc(o.value)}" ${String(o.value) === String(val) ? 'selected' : ''}>${esc(o.label)}</option>`).join('')}</select>`; }
function settingsCard(title, fields, obj, cols = 2, onSaved) {
  const id = 'sf' + Math.random().toString(36).slice(2, 7);
  setTimeout(() => {
    const box = document.getElementById(id); if (!box) return;
    box.querySelector('[data-sfsave]').onclick = () => { Object.assign(obj, readForm(box, fields)); App.changed(false); onSaved && onSaved(); toast('Đã cập nhật cài đặt', 'ok'); };
  });
  return `<div id="${id}">${card(title, formHTML(fields, obj, cols) + `<div class="row mt"><button class="btn pri" data-sfsave>${ic('check')} Lưu cài đặt</button></div>`)}</div>`;
}
const COMPANY_FIELDS = [
  { key: 'ten', label: 'Tên công ty / cửa hàng', full: true },
  { key: 'diaChi', label: 'Địa chỉ', full: true },
  { key: 'sdt', label: 'Điện thoại' }, { key: 'email', label: 'Email' },
  { key: 'mst', label: 'Mã số thuế' }, { key: 'website', label: 'Website' },
  { key: 'logo', label: 'Logo (hiện trên bản in)', type: 'image', max: 300, full: true }
];

