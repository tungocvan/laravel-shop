
const THEME = {"p":"#0284c7","d":"#0369a1","s50":"#f0f9ff","s100":"#e0f2fe","ink":"#082f49","ink2":"#0c4a6e"};
/* ==========================================================
   CHẤM CÔNG & TÍNH LƯƠNG (thuế TNCN, BHXH) - Phong Excel
   ========================================================== */
const CODES = {
  X: { n: 'Đi làm cả ngày', lv: 1, hl: 1, kl: 0, bg: '#dcfce7', c: '#15803d' },
  N: { n: 'Làm nửa ngày', lv: .5, hl: .5, kl: .5, bg: '#ccfbf1', c: '#0f766e' },
  CT: { n: 'Đi công tác', lv: 1, hl: 1, kl: 0, bg: '#e0f2fe', c: '#0369a1' },
  P: { n: 'Nghỉ phép năm', lv: 0, hl: 1, kl: 0, bg: '#dbeafe', c: '#1d4ed8' },
  L: { n: 'Nghỉ lễ, Tết', lv: 0, hl: 1, kl: 0, bg: '#ede9fe', c: '#6d28d9' },
  O: { n: 'Nghỉ ốm (BHXH trả)', lv: 0, hl: 0, kl: 1, bg: '#fef3c7', c: '#b45309' },
  KP: { n: 'Nghỉ không lương', lv: 0, hl: 0, kl: 1, bg: '#fee2e2', c: '#b91c1c' }
};
addCSS(`
.cc-wrap{overflow:auto;max-height:calc(100vh - 290px);background:#fff;border:1px solid #e6e9f0;border-radius:14px}
table.cc{border-collapse:separate;border-spacing:0;font-size:12px;user-select:none}
.cc th,.cc td{border-right:1px solid #eef0f5;border-bottom:1px solid #eef0f5;text-align:center;padding:0;height:34px}
.cc thead th{position:sticky;top:0;background:#f7f9fc;z-index:3;font-weight:700;color:#475467;font-size:11px;padding:4px 2px;line-height:1.2}
.cc thead th.we{background:#fff1f2;color:#e11d48}
.cc thead th.td{background:var(--p);color:#fff}
.cc .nm{position:sticky;left:0;background:#fff;z-index:2;text-align:left;padding:0 12px;min-width:190px;max-width:190px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cc thead .nm{z-index:4;background:#f7f9fc}
.cc td.d{width:32px;min-width:32px;cursor:pointer;font-weight:700;font-size:11px}
.cc td.d:hover{outline:2px solid var(--p);outline-offset:-2px}
.cc td.we{background:#fff7f8}
.cc td.sum{min-width:58px;padding:0 6px;font-weight:700;background:#f9fafb;font-variant-numeric:tabular-nums}
.cc td.sum.hl{background:var(--p-50);color:var(--p-d)}
.cc td.ot input{width:54px;height:26px;border:1px solid #d6dce7;border-radius:6px;text-align:center;font:inherit}
.brush{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 12px;border-radius:9px;border:2px solid transparent;cursor:pointer;font-weight:700;font-size:12.5px}
.brush.on{border-color:#111827;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.brush small{font-weight:500;opacity:.8}
.pl th.stk,.pl td.stk{position:sticky;background:#fff;z-index:1}
.pl thead th.stk{z-index:3;background:#f7f9fc}
.pl tfoot td.stk{z-index:3;background:#f7f9fc}
.pl tbody tr:hover td.stk{background:var(--p-50)}
.pl td.in input{width:104px;height:30px;border:1px solid #d6dce7;border-radius:7px;text-align:right;padding:0 7px;font:inherit;font-variant-numeric:tabular-nums;background:#fffdf5}
.pl td.in input:focus{outline:none;border-color:var(--p);box-shadow:0 0 0 3px var(--p-100)}
.pl td.net{font-weight:700;color:var(--p-d)}
.pl th.grp{background:var(--p-50);color:var(--p-d)}
.slip{border:1px solid #c9ced8;border-radius:8px;padding:14px 16px;margin-bottom:14px;page-break-inside:avoid;font-size:11.5px}
.slip h3{margin:0 0 2px;text-align:center;text-transform:uppercase;font-size:15px;color:var(--p-d)}
.slip .s-sub{text-align:center;color:#555;margin-bottom:10px}
.slip .s-info{display:grid;grid-template-columns:1fr 1fr;gap:2px 18px;margin-bottom:8px}
.slip table td{padding:4px 6px}
.slip .tot td{font-weight:700;background:#eef1f6}
.calc-out div{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #eef0f5}
.calc-out div b{font-variant-numeric:tabular-nums}
.calc-out .big{font-size:18px;color:var(--p-d)}
.bac td input{width:100%;height:30px;border:1px solid #d6dce7;border-radius:7px;padding:0 8px;text-align:right;font:inherit}
`);
addCSS(`
/* ===== Dashboard: tông xanh da trời (khác file kho xanh navy) ===== */
.dh{position:relative;overflow:hidden;border-radius:20px;padding:26px 28px;margin-bottom:18px;color:#fff;
  background:radial-gradient(circle at 92% -25%,rgba(103,232,249,.55),transparent 45%),radial-gradient(circle at 0% 130%,rgba(52,211,153,.3),transparent 45%),linear-gradient(135deg,#082f49 0%,#075985 50%,#0ea5e9 100%);
  box-shadow:0 18px 40px rgba(3,105,161,.25);display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1.25fr) 180px;gap:24px;align-items:center}
.dh::after{content:"";position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.08) 1px,transparent 1px);background-size:18px 18px;pointer-events:none;-webkit-mask-image:linear-gradient(90deg,transparent 20%,#000);mask-image:linear-gradient(90deg,transparent 20%,#000)}
.dh>*{position:relative;z-index:1}
.dh-k{font-size:11.5px;letter-spacing:1.4px;font-weight:700;color:#bae6fd;text-transform:uppercase}
.dh-v{font-size:36px;font-weight:800;letter-spacing:-1px;line-height:1.15;margin:6px 0 8px;font-variant-numeric:tabular-nums;white-space:nowrap}
.dh-v small{font-size:19px;font-weight:700;color:#e0f2fe}
.dh-sub{font-size:13px;color:#e0f2fe;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.dh-st{display:flex;gap:26px;margin:20px 0}
.dh-st b{display:block;font-size:21px;font-weight:800;font-variant-numeric:tabular-nums}
.dh-st span{font-size:12px;color:#bae6fd}
.dh-act{display:flex;gap:8px;flex-wrap:wrap}
.dhb{border:0;border-radius:10px;padding:8px 11px;white-space:nowrap;font-weight:700;cursor:pointer;background:#fff;color:#075985;font-size:12.5px;transition:.15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.dhb svg{width:15px;height:15px}
.dhb:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.2)}
.dhb.gh{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25)}
.dh-ch{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.14);border-radius:16px;padding:14px 16px 8px}
.dh-ct{font-size:12px;color:#bae6fd;font-weight:600;display:flex;justify-content:space-between;margin-bottom:6px;gap:10px}
.dh-ct b{color:#fff}
.dh-ring{text-align:center}
.dh-ring svg{width:150px;height:150px;display:block;margin:0 auto}
.dh-ring b{display:block;font-size:14px;margin-top:8px}
.dh-ring span{font-size:12px;color:#bae6fd}
.pl-pill{flex-shrink:0;margin-left:auto;font-size:11.5px;font-weight:700;padding:3px 8px;border-radius:20px;white-space:nowrap}
.pl-pill.pu{background:#dcfce7;color:#15803d}.pl-pill.pd{background:#fee2e2;color:#b91c1c}.pl-pill.na{background:#eef1f6;color:#667085}
.dh .pl-pill.pu{background:rgba(134,239,172,.2);color:#a7f3d0}.dh .pl-pill.pd{background:rgba(252,165,165,.22);color:#fecaca}.dh .pl-pill.na{background:rgba(255,255,255,.14);color:#e0f2fe}
.kc4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:18px}
.kc{background:#fff;border:1px solid #e6e9f0;border-radius:14px;box-shadow:0 1px 2px rgba(16,24,40,.04);padding:16px 18px 0;overflow:hidden;transition:.2s}
.kc:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(16,24,40,.1)}
.kc-h{display:flex;align-items:center;gap:10px}
.kc-ic{width:36px;height:36px;border-radius:10px;display:grid;place-items:center;background:var(--cb);color:var(--c);flex-shrink:0}
.kc-ic svg{width:19px;height:19px}
.kc-l{font-size:13px;color:#667085;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.kc-v{font-size:24px;font-weight:800;letter-spacing:-.5px;margin:12px 0 2px;font-variant-numeric:tabular-nums;white-space:nowrap}
.kc-v small{font-size:13px;color:#98a2b3;font-weight:700}
.kc-s{font-size:12px;color:#667085;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.spk{display:block;width:calc(100% + 36px);margin:10px -18px 0;height:46px}
.dln,.spk-l{stroke-dasharray:1;stroke-dashoffset:1;animation:dldraw 1.3s .3s ease forwards}
@keyframes dldraw{to{stroke-dashoffset:0}}
.dring{animation:dring 1.3s cubic-bezier(.2,.8,.2,1) both}
@keyframes dring{from{stroke-dasharray:0 400}}
.dsh .chart rect{transform-box:fill-box;transform-origin:50% 100%;animation:dgrow .8s cubic-bezier(.2,.8,.2,1) both}
@keyframes dgrow{from{transform:scaleY(0)}}
.medal{width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0}
.tli{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px dashed #eef0f5}
.tli:last-child{border:0}
.tli .grow{min-width:0}
.tli .grow b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tli small{color:#667085;font-size:12px}
.tli .pb{height:6px;background:#eef1f6;border-radius:6px;margin-top:5px;overflow:hidden}
.tli .pb i{display:block;height:100%;border-radius:6px}
.tli .v{text-align:right;font-weight:700;white-space:nowrap}
.tli .v small{display:block;font-weight:500}
@media (prefers-reduced-motion:reduce){.dsh .chart rect,.dring{animation:none}.dln,.spk-l{animation:none;stroke-dashoffset:0}}
@media (max-width:1280px){.dh{grid-template-columns:1fr 1fr}.dh-ring{display:none}.kc4{grid-template-columns:1fr 1fr}}
@media (max-width:760px){.dh{grid-template-columns:1fr;padding:22px}.kc4{grid-template-columns:1fr}.dh-v{font-size:28px}}
@media print{.dln,.spk-l{stroke-dashoffset:0;animation:none}.dsh .chart rect,.dring{animation:none}}

/* ===== Phiếu lương (mỗi người 1 trang A4) ===== */
.ps{font-size:11.5px;color:#1f2937;break-after:page;page-break-after:always}
.ps:last-child{break-after:auto;page-break-after:auto}
.ps-top{display:flex;justify-content:space-between;align-items:flex-start;gap:20px}
.ps-co{display:flex;gap:10px;align-items:center;max-width:62%}
.ps-co img{max-height:52px;max-width:110px}
.ps-co b{font-size:14px;color:var(--p-d);text-transform:uppercase;display:block;margin-bottom:2px}
.ps-co .l{font-size:10.5px;color:#555}
.ps-tt{text-align:right;font-size:11px;color:#444;line-height:1.6}
.ps-tt h1{font-size:24px;letter-spacing:2px;margin:0 0 2px;color:var(--p-d);font-weight:800;line-height:1.1}
.ps-band{height:5px;border-radius:3px;background:linear-gradient(90deg,var(--p-d),var(--p),#7dd3fc);margin:9px 0 10px}
.ps-emp{display:flex;gap:16px;align-items:center;border:1px solid #dbe2ee;border-radius:10px;padding:9px 12px;background:var(--p-50);line-height:1.3}
.ps-av{width:52px;height:52px;border-radius:50%;background:var(--p);color:#fff;display:grid;place-items:center;font-weight:800;font-size:18px;flex-shrink:0}
.ps-eg{display:grid;grid-template-columns:repeat(4,1fr);gap:8px 14px;flex:1}
.ps-eg span{display:block;font-size:9.5px;color:#6b7280;text-transform:uppercase;letter-spacing:.4px}
.ps-eg b{font-size:12px}
.ps-att{display:grid;grid-template-columns:repeat(6,1fr);gap:8px;margin:9px 0}
.ps-att div{border:1px solid #e3e8f0;border-radius:8px;padding:5px 4px;text-align:center}
.ps-att b{display:block;font-size:15px;color:#111}
.ps-att span{font-size:9.5px;color:#6b7280}
.ps-att .hl{background:var(--p);border-color:var(--p)}
.ps-att .hl b,.ps-att .hl span{color:#fff}
.pr .ps-t td{border:0;border-bottom:1px solid #e5e9f0;padding:3.6px 8px;font-size:11.2px;vertical-align:middle;line-height:1.25}
.pr .ps-t th{padding:5px 8px;font-size:10.5px;line-height:1.25}
.ps-t tr.sec td{background:#f1f5fb;font-weight:700;color:var(--p-d);font-size:10.8px;letter-spacing:.3px}
.ps-t tr.sub td{font-weight:700;background:#f8fafc;border-bottom:2px solid #cbd5e1}
.ps-t td.dg{color:#6b7280;font-size:10.5px}
.ps-net{display:flex;justify-content:space-between;align-items:center;gap:16px;background:linear-gradient(90deg,var(--p-d),var(--p));color:#fff;border-radius:10px;padding:10px 16px;margin:9px 0}
.ps-net span{font-weight:800;font-size:13px;letter-spacing:.5px;display:block}
.ps-net small{font-style:italic;font-size:10.5px;opacity:.92}
.ps-net b{font-size:22px;white-space:nowrap}
.ps-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ps-box{border:1px solid #e3e8f0;border-radius:10px;padding:7px 12px}
.ps-box h4{margin:0 0 5px;font-size:10.5px;text-transform:uppercase;color:var(--p-d);letter-spacing:.3px}
.ps-box div{display:flex;justify-content:space-between;gap:10px;padding:2px 0;line-height:1.3;font-size:10.8px;border-bottom:1px dashed #edf0f5}
.ps-box div:last-child{border:0;font-weight:700}
.ps-pay{margin-top:8px;font-size:11px}
.ps-date{text-align:right;font-style:italic;margin-top:6px}
.ps-sign{display:grid;grid-template-columns:repeat(3,1fr);text-align:center;margin-top:4px}
.ps-sign b{display:block}
.ps-sign i{font-size:10px;color:#666}
.ps-sign .sp{height:50px}
.ps-foot{margin-top:6px;padding-top:5px;border-top:1px solid #e5e9f0;font-size:9.5px;color:#888;text-align:center}
`);

/* ---------- Dữ liệu ---------- */
function defaultSettings() {
  return {
    company: { ten: 'Công ty TNHH Thương mại Minh An', diaChi: '125 Nguyễn Văn Linh, Quận 7, TP. Hồ Chí Minh', sdt: '028 3775 6688', email: 'ketoan@minhan.vn', mst: '0316 789 456', website: 'minhan.vn', logo: '' },
    phongBan: ['Ban Giám đốc', 'Kế toán', 'Kinh doanh', 'Nhân sự', 'Kho vận', 'Sản xuất'],
    congChuan: 26, gioCong: 8, heSoOT: 1.5, ngayNghi: 'CN',
    luongCoSo: 2340000, ltVung: 5310000,
    bhxhNLD: 8, bhytNLD: 1.5, bhtnNLD: 1, bhxhDN: 17.5, bhytDN: 3, bhtnDN: 1, kpcdDN: 2,
    gtBanThan: 15500000, gtNPT: 6200000,
    bac: [{ den: 10000000, ts: 5 }, { den: 30000000, ts: 10 }, { den: 60000000, ts: 20 }, { den: 100000000, ts: 30 }, { den: 0, ts: 35 }]
  };
}
function emptyDB() { return { settings: defaultSettings(), employees: [], cham: {}, extra: {} }; }
function sampleDB() {
  const r = rng(2026), db = emptyDB();
  const plan = [
    ['Ban Giám đốc', 'Giám đốc', 45e6, 5e6, 0], ['Ban Giám đốc', 'Phó Giám đốc', 35e6, 3e6, 1],
    ['Kế toán', 'Kế toán trưởng', 22e6, 2e6, 0], ['Kế toán', 'Kế toán tổng hợp', 13e6, 5e5, 1], ['Kế toán', 'Kế toán kho', 11e6, 5e5, 1],
    ['Kinh doanh', 'Trưởng phòng KD', 25e6, 3e6, 0], ['Kinh doanh', 'Nhân viên KD', 10e6, 1e6, 0], ['Kinh doanh', 'Nhân viên KD', 10e6, 1e6, 1], ['Kinh doanh', 'Nhân viên KD', 9.5e6, 1e6, 0], ['Kinh doanh', 'Nhân viên KD', 9.5e6, 1e6, 1],
    ['Nhân sự', 'Trưởng phòng NS', 20e6, 2e6, 1], ['Nhân sự', 'Chuyên viên tuyển dụng', 11e6, 5e5, 1],
    ['Kho vận', 'Thủ kho', 10e6, 1e6, 0], ['Kho vận', 'Nhân viên kho', 8e6, 5e5, 0], ['Kho vận', 'Tài xế giao hàng', 8.5e6, 1e6, 0],
    ['Sản xuất', 'Tổ trưởng sản xuất', 12e6, 1e6, 0], ['Sản xuất', 'Công nhân', 7.5e6, 3e5, 1], ['Sản xuất', 'Công nhân', 7.5e6, 3e5, 0]
  ];
  const junior = [['Kinh doanh', 'Nhân viên KD', 9e6, 1e6], ['Sản xuất', 'Công nhân', 7.2e6, 3e5], ['Kho vận', 'Nhân viên kho', 7.8e6, 5e5], ['Kế toán', 'Kế toán viên', 10e6, 5e5], ['Sản xuất', 'Công nhân', 7.5e6, 3e5], ['Kinh doanh', 'Chăm sóc khách hàng', 8.5e6, 5e5]];
  for (let i = 0; i < 22; i++) { const j = junior[i % junior.length]; plan.push([j[0], j[1], j[2] + Sample.int(r, 0, 10) * 1e5, j[3], r() < .5 ? 1 : 0]); }
  const nows = D.ymNow();
  plan.forEach((p, i) => {
    const nu = !!p[4];
    db.employees.push({
      id: 'e' + (i + 1), ma: 'NV' + String(i + 1).padStart(3, '0'), ten: Sample.name(r, nu), gioiTinh: nu ? 'Nữ' : 'Nam',
      ngaySinh: `${Sample.int(r, 1978, 2002)}-${pad(Sample.int(r, 1, 12))}-${pad(Sample.int(r, 1, 28))}`,
      phongBan: p[0], chucVu: p[1], ngayVao: `${Sample.int(r, 2016, 2025)}-${pad(Sample.int(r, 1, 12))}-01`, trangThai: 'Đang làm',
      luongCB: p[2], luongBH: r() < .35 ? Math.round(p[2] * .7 / 1e5) * 1e5 : p[2], pcChiuThue: p[3], pcKhongThue: 730000,
      soNPT: Sample.int(r, 0, 2), mst: '8' + String(Sample.int(r, 100000000, 999999999)), cccd: '0' + String(Sample.int(r, 10000000000, 99999999999)), sdt: Sample.phone(r), email: '', stk: String(Sample.int(r, 1000000000, 9999999999)), nganHang: Sample.pick(r, ['Vietcombank', 'Techcombank', 'VietinBank', 'BIDV', 'ACB', 'MB Bank'])
    });
  });
  const today = D.today();
  D.ymRange(nows, 7).forEach(ym => {
    db.cham[ym] = {}; db.extra[ym] = {};
    const n = D.daysIn(ym);
    db.employees.forEach(e => {
      const d = {};
      for (let k = 1; k <= n; k++) {
        const ds = ym + '-' + pad(k); if (ds > today) break;
        if (D.dow(ym, k) === 0) continue;
        if (ym.endsWith('-09') && (k === 1 || k === 2)) { d[k] = 'L'; continue; }
        const x = r(); d[k] = x < .02 ? 'KP' : x < .05 ? 'P' : x < .065 ? 'O' : x < .09 ? 'N' : x < .12 && e.phongBan === 'Kinh doanh' ? 'CT' : 'X';
      }
      const heavy = ['Sản xuất', 'Kho vận'].includes(e.phongBan);
      db.cham[ym][e.id] = { d, ot: heavy ? Sample.int(r, 4, 24) : r() < .4 ? Sample.int(r, 0, 8) : 0 };
      const ex = {};
      if (e.phongBan === 'Kinh doanh') ex.thuong = Math.round(r() * 60) * 1e5;
      if (r() < .12) ex.tamUng = Sample.int(r, 1, 4) * 1e6;
      if (Object.keys(ex).length) db.extra[ym][e.id] = ex;
    });
  });
  return db;
}
function migrate(db) {
  db.settings = Object.assign(defaultSettings(), db.settings || {});
  db.settings.company = Object.assign(defaultSettings().company, db.settings.company || {});
  db.employees = db.employees || []; db.cham = db.cham || {}; db.extra = db.extra || {};
}

/* ---------- Tính toán ---------- */
const S = () => DB.settings;
const empById = id => DB.employees.find(e => e.id === id);
function att(ym, eid) { DB.cham[ym] = DB.cham[ym] || {}; return DB.cham[ym][eid] = DB.cham[ym][eid] || { d: {}, ot: 0 }; }
function summarize(ym, eid) {
  const a = (DB.cham[ym] || {})[eid] || { d: {}, ot: 0 };
  const o = { lv: 0, hl: 0, kl: 0, P: 0, L: 0, O: 0, KP: 0, ot: +a.ot || 0, has: Object.keys(a.d).length > 0 };
  for (const k in a.d) { const c = CODES[a.d[k]]; if (!c) continue; o.lv += c.lv; o.hl += c.hl; o.kl += c.kl; if (o[a.d[k]] !== undefined) o[a.d[k]]++; }
  return o;
}
function taxOf(tn) {
  let t = 0, prev = 0;
  for (const b of S().bac) {
    const top = +b.den || Infinity;
    if (tn <= prev) break;
    t += (Math.min(tn, top) - prev) * (+b.ts || 0) / 100; prev = top;
  }
  return Math.round(t);
}
function bhOf(base, nld = true) {
  const s = S(), capXH = 20 * s.luongCoSo, capTN = 20 * s.ltVung;
  const bx = Math.min(base, capXH), bt = Math.min(base, capTN);
  if (nld) return { bhxh: round(bx * s.bhxhNLD / 100), bhyt: round(bx * s.bhytNLD / 100), bhtn: round(bt * s.bhtnNLD / 100) };
  return { bhxh: round(bx * s.bhxhDN / 100), bhyt: round(bx * s.bhytDN / 100), bhtn: round(bt * s.bhtnDN / 100), kpcd: round(bx * s.kpcdDN / 100) };
}
function calcPay(e, ym) {
  const s = S(), a = summarize(ym, e.id), ex = (DB.extra[ym] || {})[e.id] || {};
  const cc = +s.congChuan || 26;
  const luongNgay = e.luongCB / cc, luongGio = luongNgay / (+s.gioCong || 8);
  const luongCong = round(luongNgay * a.hl);
  const pcCT = +e.pcChiuThue || 0, pcKT = +e.pcKhongThue || 0;
  const tienOT = round(luongGio * a.ot * s.heSoOT), otMien = round(luongGio * a.ot * Math.max(0, s.heSoOT - 1));
  const thuong = +ex.thuong || 0;
  const gross = luongCong + pcCT + pcKT + tienOT + thuong;
  const noBH = a.kl >= 14 || !e.luongBH; // nghỉ không lương từ 14 ngày trở lên thì không đóng BH tháng đó
  const b = noBH ? { bhxh: 0, bhyt: 0, bhtn: 0 } : bhOf(+e.luongBH, true);
  const bd = noBH ? { bhxh: 0, bhyt: 0, bhtn: 0, kpcd: 0 } : bhOf(+e.luongBH, false);
  const bhNLD = b.bhxh + b.bhyt + b.bhtn, bhDN = bd.bhxh + bd.bhyt + bd.bhtn + bd.kpcd;
  const tnChiuThue = Math.max(0, gross - pcKT - otMien);
  const giamTru = s.gtBanThan + (+e.soNPT || 0) * s.gtNPT;
  const tnTinhThue = Math.max(0, tnChiuThue - bhNLD - giamTru);
  const thue = taxOf(tnTinhThue);
  const tamUng = +ex.tamUng || 0, khauTru = +ex.khauTru || 0;
  const thucLinh = gross - bhNLD - thue - tamUng - khauTru;
  return { id: e.id, e, ma: e.ma, ten: e.ten, phongBan: e.phongBan, chucVu: e.chucVu, luongCB: e.luongCB, luongBH: e.luongBH, cong: a.hl, lv: a.lv, kl: a.kl, P: a.P, L: a.L, cc, luongCong, pcCT, pcKT, ot: a.ot, tienOT, otMien, thuong, gross, ...b, bhNLD, bhDN, bdxh: bd.bhxh, bdyt: bd.bhyt, bdtn: bd.bhtn, kpcd: bd.kpcd, noBH, tnChiuThue, giamTru, tnTinhThue, thue, tamUng, khauTru, thucLinh, chiPhi: gross + bhDN, soNPT: +e.soNPT || 0, mst: e.mst };
}
function payroll(ym, pb = '') {
  const end = ym + '-31';
  return DB.employees.filter(e => (!e.ngayVao || e.ngayVao <= end) && (e.trangThai !== 'Nghỉ việc' || summarize(ym, e.id).has) && (!pb || e.phongBan === pb)).map(e => calcPay(e, ym));
}
function defaultMonth() { const t = D.today(); return +t.slice(8) >= 25 ? t.slice(0, 7) : D.ymAdd(t.slice(0, 7), -1); }
UI.m = defaultMonth(); UI.pb = '';
const monthTool = () => monthPicker('mPick', UI.m, 'Kỳ lương');
const bindMonth = () => onVal('mPick', v => { if (v) { UI.m = v; App.render(); } });

/* ---------- Dashboard ---------- */
/* số chạy từ 0 lên */
function countUp(root) {
  const els = $$('[data-count]', root), fin = () => els.forEach(e => e.textContent = F.money(+e.dataset.count));
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) return fin();
  const t0 = performance.now(), Dur = 900; els.forEach(e => e.textContent = '0');
  const step = t => { const k = Math.min(1, (t - t0) / Dur), ez = 1 - Math.pow(1 - k, 3); els.forEach(e => e.textContent = F.money(+e.dataset.count * ez)); if (k < 1) requestAnimationFrame(step); };
  requestAnimationFrame(step); setTimeout(fin, 1300);
}
function plPill(cur, prev, note) {
  if (!prev) return `<span class="pl-pill na" title="${note}">${cur ? 'Mới' : '-'}</span>`;
  const p = (cur - prev) / Math.abs(prev) * 100, up = p >= 0;
  return `<span class="pl-pill ${up ? 'pu' : 'pd'}" title="${note}">${up ? '▲' : '▼'} ${Math.abs(p).toFixed(1).replace('.', ',')}%</span>`;
}
let SPK = 0;
function spark(data, color) {
  const W = 240, H = 46, n = data.length; if (n < 2) data = [data[0] || 0, data[0] || 0];
  const mx = Math.max(...data), mn = Math.min(0, ...data), r = (mx - mn) || 1, N = data.length;
  const d = 'M' + data.map((v, i) => (i * W / (N - 1)).toFixed(1) + ',' + (H - 4 - (v - mn) / r * (H - 12)).toFixed(1)).join('L'), id = 'spg' + (++SPK);
  return `<svg class="spk" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${color}" stop-opacity=".3"/><stop offset="1" stop-color="${color}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${color}" stroke-width="2" stroke-linejoin="round" class="spk-l" pathLength="1"/></svg>`;
}
function heroArea(labels, vals) {
  const W = 440, H = 150, pl = 8, pr = 8, pt = 12, pb = 22, n = vals.length;
  if (n < 2) return '<div style="padding:40px 0;text-align:center;color:#bae6fd">Cần ít nhất 2 tháng dữ liệu để vẽ xu hướng</div>';
  const mx = Math.max(1, ...vals), mn = Math.min(...vals), span = (mx - mn) || mx || 1;
  const x = i => pl + i * (W - pl - pr) / (n - 1), y = v => pt + (H - pt - pb) * (1 - ((v - mn) / span * 0.85 + 0.08)), L = n - 1;
  const line = 'M' + vals.map((v, i) => x(i).toFixed(1) + ',' + y(v).toFixed(1)).join('L');
  return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;display:block;overflow:visible"><defs><linearGradient id="dhA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#bae6fd" stop-opacity=".5"/><stop offset="1" stop-color="#bae6fd" stop-opacity="0"/></linearGradient></defs>
    <path d="${line}L${x(L)},${H - pb}L${x(0)},${H - pb}Z" fill="url(#dhA)"/>
    <path d="${line}" fill="none" stroke="#fff" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" class="dln" pathLength="1"/>
    ${vals.map((v, i) => `<circle cx="${x(i)}" cy="${y(v)}" r="${i === L ? 5.5 : 3.5}" fill="${i === L ? '#fff' : '#bae6fd'}" stroke="${i === L ? '#0284c7' : 'none'}" stroke-width="3" data-tip="${esc(labels[i])}\nTổng chi phí lương: ${F.vnd(v)}"/>`).join('')}
    ${labels.map((l, i) => `<text x="${x(i)}" y="${H - 3}" text-anchor="middle" font-size="10.5" fill="#bae6fd">${esc(l)}</text>`).join('')}</svg>`;
}
const kc = o => `<div class="kc" style="--c:${o.c};--cb:${o.cb}"><div class="kc-h"><span class="kc-ic">${ic(o.icon)}</span><span class="kc-l">${o.label}</span>${o.pill}</div><div class="kc-v"><span data-count="${Math.round(o.value)}">0</span> <small>đ</small></div><div class="kc-s">${o.sub}</div>${spark(o.data, o.c)}</div>`;
const vDash = {
  title: 'Dashboard', label: 'Dashboard', icon: 'dashboard', desc: 'Báo cáo tổng quan quỹ lương, thuế và bảo hiểm',
  tools: monthTool, after: bindMonth,
  render(el) {
    const ym = UI.m, L = payroll(ym), s = S(), prevM = D.ymAdd(ym, -1), P = payroll(prevM);
    App.subtitle = F.month(ym);
    const T = k => sum(L, x => x[k]), TP = k => sum(P, x => x[k]);
    const dangLam = DB.employees.filter(e => e.trangThai !== 'Nghỉ việc').length;
    const nopThue = L.filter(x => x.thue > 0).length;
    const tyLe = L.length ? sum(L, x => x.lv) / (L.length * (+s.congChuan || 26)) * 100 : 0;
    const pbs = [...new Set(L.map(x => x.phongBan))];
    let months = D.ymRange(ym, 12).filter(m => m === ym || (DB.cham[m] && Object.keys(DB.cham[m]).length));
    const trend = months.map(m => m === ym ? L : payroll(m));
    const ser = k => trend.map(l => sum(l, x => x[k]));
    const nghi = L.map(x => ({ label: x.ten, sub: x.phongBan, value: x.kl + x.P })).filter(x => x.value > 0).sort((a, b) => b.value - a.value).slice(0, 6);
    const top = [...L].sort((a, b) => b.gross - a.gross).slice(0, 5), topMax = top.length ? top[0].gross : 1;
    const mm = ym.slice(5);
    const sn = DB.employees.filter(e => e.trangThai !== 'Nghỉ việc' && e.ngaySinh && e.ngaySinh.slice(5, 7) === mm).sort((a, b) => a.ngaySinh.slice(8).localeCompare(b.ngaySinh.slice(8)));
    const C = 2 * Math.PI * 50, pct = Math.min(100, Math.round(tyLe)), MED = ['#f5b301', '#9aa5b8', '#cd7f32', '#c3cbda', '#c3cbda'];
    const cmp = 'so với ' + F.month(prevM).toLowerCase();
    el.innerHTML = `<div class="dsh">
      <div class="dh">
        <div>
          <div class="dh-k">Thực lĩnh chi trả ${F.month(ym).toLowerCase()}</div>
          <div class="dh-v"><span data-count="${Math.round(T('thucLinh'))}">0</span> <small>đ</small></div>
          <div class="dh-sub">${plPill(T('thucLinh'), TP('thucLinh'), cmp)}<span>${cmp} (${F.short(TP('thucLinh'))})</span></div>
          <div class="dh-st"><div><b data-count="${L.length}">0</b><span>Người hưởng lương</span></div><div><b data-count="${nopThue}">0</b><span>Người nộp thuế</span></div><div><b data-count="${Math.round(T('ot'))}">0</b><span>Giờ làm thêm</span></div></div>
          <div class="dh-act"><a class="dhb" data-nav="chamcong" href="#">${ic('calcheck')} Chấm công</a><a class="dhb gh" data-nav="bangluong" href="#">${ic('wallet')} Bảng lương</a><button class="dhb gh" data-act="slipAll">${ic('receipt')} Phiếu lương</button></div>
        </div>
        <div class="dh-ch"><div class="dh-ct"><span>Tổng chi phí lương doanh nghiệp theo tháng</span><span>Tháng này <b>${F.short(T('chiPhi'))}</b></span></div>${heroArea(months.map(F.mshort), ser('chiPhi'))}</div>        <div class="dh-ring"><svg viewBox="0 0 120 120"><defs><linearGradient id="dhR" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a5f3fc"/><stop offset="1" stop-color="#fde68a"/></linearGradient></defs>
          <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.14)" stroke-width="11"/>
          <circle class="dring" cx="60" cy="60" r="50" fill="none" stroke="url(#dhR)" stroke-width="11" stroke-linecap="round" stroke-dasharray="${(C * pct / 100).toFixed(1)} 400" transform="rotate(-90 60 60)"/>
          <text x="60" y="60" text-anchor="middle" font-size="26" font-weight="800" fill="#fff">${F.num(tyLe, 0)}%</text><text x="60" y="78" text-anchor="middle" font-size="9.5" fill="#bae6fd">tỷ lệ đi làm</text></svg>
          <b>${F.num(sum(L, x => x.lv))} / ${F.num(L.length * (+s.congChuan || 26), 0)} công</b><span>Công làm thực tế / công chuẩn</span></div>
      </div>
      <div class="kc4">
        ${kc({ c: '#0284c7', cb: '#e0f2fe', icon: 'money', label: 'Tổng thu nhập', value: T('gross'), pill: plPill(T('gross'), TP('gross'), cmp), sub: `${L.length} người · bình quân ${F.short(L.length ? T('gross') / L.length : 0)}/người`, data: ser('gross') })}
        ${kc({ c: '#d97706', cb: '#fef3c7', icon: 'percent', label: 'Thuế TNCN', value: T('thue'), pill: plPill(T('thue'), TP('thue'), cmp), sub: `${nopThue} người phải nộp thuế`, data: ser('thue') })}
        ${kc({ c: '#7c3aed', cb: '#ede9fe', icon: 'shield', label: 'Bảo hiểm', value: T('bhNLD') + T('bhDN'), pill: plPill(T('bhNLD') + T('bhDN'), TP('bhNLD') + TP('bhDN'), cmp), sub: `NLĐ ${F.short(T('bhNLD'))} · DN ${F.short(T('bhDN'))}`, data: trend.map(l => sum(l, x => x.bhNLD + x.bhDN)) })}
        ${kc({ c: '#e11d48', cb: '#ffe4e6', icon: 'building', label: 'Chi phí lương DN', value: T('chiPhi'), pill: plPill(T('chiPhi'), TP('chiPhi'), cmp), sub: `Đang làm việc: ${dangLam} người`, data: ser('chiPhi') })}
      </div>
      <div class="grid cols-21">
        ${card('Quỹ lương theo phòng ban', Chart.bar({ labels: pbs, stacked: true, series: [
          { name: 'Thực lĩnh', values: pbs.map(p => sum(L.filter(x => x.phongBan === p), x => x.thucLinh + x.tamUng + x.khauTru)), color: '#0284c7' },
          { name: 'BH người lao động', values: pbs.map(p => sum(L.filter(x => x.phongBan === p), x => x.bhNLD)), color: '#a855f7' },
          { name: 'Thuế TNCN', values: pbs.map(p => sum(L.filter(x => x.phongBan === p), x => x.thue)), color: '#f59e0b' }] }), { sub: F.month(ym) })}
        ${card('Cơ cấu chi phí doanh nghiệp', Chart.donut({ centerSub: 'Chi phí DN', items: [
          { label: 'Trả người lao động', value: T('thucLinh') + T('tamUng') + T('khauTru'), color: '#0284c7' },
          { label: 'Thuế TNCN (nộp hộ)', value: T('thue'), color: '#f59e0b' },
          { label: 'BH phần NLĐ', value: T('bhNLD'), color: '#a855f7' },
          { label: 'BH + KPCĐ phần DN', value: T('bhDN'), color: '#fb923c' }] }))}
      </div>
      <div class="grid cols-3 mt">
        ${card('Thu nhập cao nhất', top.length ? top.map((x, i) => `<div class="tli"><span class="medal" style="background:${MED[i]}">${i + 1}</span><span class="avatar">${F.initials(x.ten)}</span><div class="grow"><b>${esc(x.ten)}</b><small>${esc(x.chucVu || x.phongBan)}</small><div class="pb"><i style="width:${x.gross / topMax * 100}%;background:linear-gradient(90deg,#7dd3fc,#0284c7)"></i></div></div><div class="v">${F.short(x.gross)}<small>Thực lĩnh ${F.short(x.thucLinh)}</small></div></div>`).join('') : '<div class="empty">Chưa có dữ liệu</div>', { sub: F.month(ym) })}
        ${card('Nghỉ nhiều nhất tháng', Chart.hbars({ items: nghi, fmt: v => F.num(v) + ' ngày', color: 'linear-gradient(90deg,#fbbf24,#f472b6)', empty: 'Không ai nghỉ' }), { sub: 'Phép + ốm + không lương' })}
        ${card('Sinh nhật trong tháng', sn.length ? sn.map(e => `<div class="tli"><span class="avatar" style="background:#fce7f3;color:#db2777">${ic('cake')}</span><div class="grow"><b>${esc(e.ten)}</b><small>${esc(e.phongBan)}</small></div><span class="badge b-p">${F.date(e.ngaySinh).slice(0, 5)}</span></div>`).join('') : '<div class="empty">Không có</div>', { sub: sn.length ? sn.length + ' người' : '' })}
      </div></div>`;
    countUp(el);
  },
  excel() {
    const ym = UI.m, L = payroll(ym);
    const pbs = [...new Set(L.map(x => x.phongBan))];
    const rows = pbs.map(p => { const l = L.filter(x => x.phongBan === p); return [p, l.length, sum(l, x => x.gross), sum(l, x => x.bhNLD), sum(l, x => x.thue), sum(l, x => x.thucLinh), sum(l, x => x.bhDN), sum(l, x => x.chiPhi)]; });
    const cols = [{ label: 'Phòng ban', width: 22 }, { label: 'Số NV', type: 'int', sum: true, width: 9 }, { label: 'Tổng thu nhập', type: 'money', sum: true }, { label: 'BH NLĐ', type: 'money', sum: true }, { label: 'Thuế TNCN', type: 'money', sum: true }, { label: 'Thực lĩnh', type: 'money', sum: true }, { label: 'BH + KPCĐ DN', type: 'money', sum: true }, { label: 'Chi phí DN', type: 'money', sum: true }];
    const months = D.ymRange(ym, 12);
    const trend = months.map(m => { const l = payroll(m); return [F.month(m), l.length, sum(l, x => x.gross), sum(l, x => x.bhNLD), sum(l, x => x.thue), sum(l, x => x.thucLinh), sum(l, x => x.bhDN), sum(l, x => x.chiPhi)]; }).filter(r => r[2] > 0);
    XLSX.save(`Bao cao luong ${ym}`, [
      { name: 'Theo phòng ban', title: 'BÁO CÁO QUỸ LƯƠNG THEO PHÒNG BAN - ' + F.month(ym).toUpperCase(), subtitles: [companyLine()], columns: cols, rows, totals: true },
      { name: 'Xu hướng 12 tháng', title: 'XU HƯỚNG QUỸ LƯƠNG 12 THÁNG', subtitles: [companyLine()], columns: [{ label: 'Tháng', width: 16 }, ...cols.slice(1)], rows: trend, totals: true }
    ]);
  }
};

/* ---------- Nhân viên ---------- */
const EMP_FIELDS = () => [
  { type: 'section', label: 'Thông tin cá nhân' },
  { key: 'ma', label: 'Mã nhân viên', required: true }, { key: 'ten', label: 'Họ và tên', required: true },
  { key: 'gioiTinh', label: 'Giới tính', type: 'select', options: ['Nam', 'Nữ'] }, { key: 'ngaySinh', label: 'Ngày sinh', type: 'date' },
  { key: 'sdt', label: 'Số điện thoại', type: 'tel' }, { key: 'email', label: 'Email', type: 'email' },
  { key: 'cccd', label: 'Số CCCD' }, { key: 'mst', label: 'Mã số thuế cá nhân' },
  { type: 'section', label: 'Công việc' },
  { key: 'phongBan', label: 'Phòng ban', type: 'select', options: () => S().phongBan }, { key: 'chucVu', label: 'Chức vụ' },
  { key: 'ngayVao', label: 'Ngày vào làm', type: 'date' }, { key: 'trangThai', label: 'Trạng thái', type: 'select', options: ['Đang làm', 'Thử việc', 'Nghỉ việc'] },
  { type: 'section', label: 'Lương & phụ cấp' },
  { key: 'luongCB', label: 'Lương cơ bản (tháng)', type: 'money', required: true }, { key: 'luongBH', label: 'Lương đóng bảo hiểm', type: 'money', hint: 'Để 0 nếu không tham gia BHXH' },
  { key: 'pcChiuThue', label: 'Phụ cấp chịu thuế', type: 'money', hint: 'Trách nhiệm, xăng xe, nhà ở...' }, { key: 'pcKhongThue', label: 'Phụ cấp không chịu thuế', type: 'money', hint: 'Ăn ca, điện thoại theo quy chế...' },
  { key: 'soNPT', label: 'Số người phụ thuộc', type: 'number', min: 0, step: 1 }, { key: 'nganHang', label: 'Ngân hàng' },
  { key: 'stk', label: 'Số tài khoản', s2: true }
];
function empForm(e) {
  const isNew = !e;
  const v = e || { ma: 'NV' + String(DB.employees.length + 1).padStart(3, '0'), gioiTinh: 'Nam', phongBan: S().phongBan[0], trangThai: 'Đang làm', ngayVao: D.today(), soNPT: 0, pcKhongThue: 0, pcChiuThue: 0 };
  Form.open({
    title: isNew ? 'Thêm nhân viên' : 'Sửa thông tin nhân viên', fields: EMP_FIELDS(), value: v, width: 760,
    onSave: d => {
      if (DB.employees.some(x => x.ma === d.ma && x !== e)) { toast('Mã nhân viên đã tồn tại', 'err'); return false; }
      if (isNew) DB.employees.push({ id: uid(), ...d }); else Object.assign(e, d);
      App.changed(); toast('Đã lưu nhân viên ' + esc(d.ten), 'ok');
    },
    onDelete: isNew ? null : () => { DB.employees = DB.employees.filter(x => x !== e); App.changed(); }
  });
}
const empCols = [
  { key: 'ma', label: 'Mã NV', w: '80px' },
  { key: 'ten', label: 'Họ và tên', render: e => `<div class="who"><span class="avatar">${F.initials(e.ten)}</span><div><b>${esc(e.ten)}</b><small>${esc(e.chucVu || '')}</small></div></div>` },
  { key: 'phongBan', label: 'Phòng ban' }, { key: 'chucVu', label: 'Chức vụ', noShow: 1 },
  { key: 'ngayVao', label: 'Ngày vào', fmt: 'date' },
  { key: 'luongCB', label: 'Lương cơ bản', fmt: 'money' }, { key: 'luongBH', label: 'Lương đóng BH', fmt: 'money' },
  { key: 'pcChiuThue', label: 'PC chịu thuế', fmt: 'money' }, { key: 'pcKhongThue', label: 'PC không thuế', fmt: 'money' },
  { key: 'soNPT', label: 'NPT', align: 'c', xtype: 'int' },
  { key: 'trangThai', label: 'Trạng thái', render: e => `<span class="badge ${e.trangThai === 'Nghỉ việc' ? 'b-red' : e.trangThai === 'Thử việc' ? 'b-amber' : 'b-green'}">${esc(e.trangThai)}</span>` }
];
const vEmp = {
  title: 'Nhân viên', icon: 'users', desc: 'Hồ sơ nhân sự, lương cơ bản, lương đóng bảo hiểm, người phụ thuộc',
  tools: () => `<button class="btn" data-act="empImport">${ic('upload')} Nhập danh sách nhân viên</button><button class="btn pri" data-act="empAdd">${ic('plus')} Thêm nhân viên</button>`,
  rows() { return DB.employees.filter(e => !UI.pb || e.phongBan === UI.pb); },
  render(el) {
    table(el, {
      id: 'emp', columns: empCols.filter(c => !c.noShow), rows: vEmp.rows(), ph: 'Tìm mã, tên, phòng ban...',
      tools: `${selectHTML('pbF', [{ value: '', label: 'Tất cả phòng ban' }, ...S().phongBan], UI.pb)}<span class="grow"></span><span class="muted small">Bấm vào dòng để sửa</span>`,
      actions: e => rowBtns(e.id, 'empEdit', 'empDel'), onRow: empForm, empty: 'Chưa có nhân viên. Bấm "Thêm nhân viên".'
    });
    onVal('pbF', v => { UI.pb = v; App.render(); });
  },
  excel() { XLSX.save('Danh sach nhan vien', [xSheet('Nhân viên', 'DANH SÁCH NHÂN VIÊN', [...empCols.filter(c => c.key !== 'trangThai'), { key: 'trangThai', label: 'Trạng thái' }, { key: 'mst', label: 'MST' }, { key: 'cccd', label: 'CCCD' }, { key: 'sdt', label: 'Điện thoại' }, { key: 'stk', label: 'Số TK' }, { key: 'nganHang', label: 'Ngân hàng' }], vEmp.rows())]); },
  print() { Print.report({ title: 'Danh sách nhân viên', cols: empCols.filter(c => !['pcKhongThue', 'ngayVao'].includes(c.key)).map(c => c.key === 'ten' ? { ...c, render: null } : c.key === 'trangThai' ? { ...c, render: null } : c), rows: vEmp.rows(), landscape: true, signs: ['Người lập', 'Giám đốc'] }); }
};
Object.assign(ACT, {
  empAdd: () => empForm(), empImport: () => empImport(), empEdit: d => empForm(empById(d.id)),
  empDel: async d => { const e = empById(d.id); if (await Modal.confirm(`Xóa nhân viên <b>${esc(e.ten)}</b>?`, { ok: 'Xóa', danger: true })) { DB.employees = DB.employees.filter(x => x !== e); App.changed(); } }
});

/* ---------- Nhập nhiều nhân viên từ Excel (file .xlsx/.csv hoặc dán thẳng) ---------- */
const EIMP = [
  { key: 'ma', label: 'Mã nhân viên', w: 12, alias: ['ma', 'manv', 'manhanvien', 'msnv', 'id'] },
  { key: 'ten', label: 'Họ và tên', w: 24, alias: ['ten', 'hoten', 'hovaten', 'tennhanvien', 'hotennhanvien', 'nhanvien'] },
  { key: 'gioiTinh', label: 'Giới tính', w: 9, alias: ['gioitinh', 'phai'] },
  { key: 'ngaySinh', label: 'Ngày sinh', t: 'date', w: 12, alias: ['ngaysinh', 'sinhngay', 'namsinh'] },
  { key: 'phongBan', label: 'Phòng ban', w: 16, alias: ['phongban', 'bophan', 'phong'] },
  { key: 'chucVu', label: 'Chức vụ', w: 18, alias: ['chucvu', 'vitri', 'chucdanh'] },
  { key: 'ngayVao', label: 'Ngày vào làm', t: 'date', w: 12, alias: ['ngayvao', 'ngayvaolam', 'ngaynhanviec', 'ngaybatdau'] },
  { key: 'trangThai', label: 'Trạng thái', w: 11, alias: ['trangthai', 'tinhtrang'] },
  { key: 'luongCB', label: 'Lương cơ bản', t: 'money', w: 14, alias: ['luongcoban', 'luongcb', 'luong', 'mucluong', 'luongchinh'] },
  { key: 'luongBH', label: 'Lương đóng BH', t: 'money', w: 14, alias: ['luongdongbh', 'luongbh', 'luongdongbaohiem', 'mucdongbh', 'luongbaohiem'] },
  { key: 'pcChiuThue', label: 'Phụ cấp chịu thuế', t: 'money', w: 14, alias: ['pcchiuthue', 'phucapchiuthue', 'phucap'] },
  { key: 'pcKhongThue', label: 'Phụ cấp không chịu thuế', t: 'money', w: 14, alias: ['pckhongthue', 'phucapkhongchiuthue', 'phucapkhongthue'] },
  { key: 'soNPT', label: 'Số người phụ thuộc', t: 'int', w: 10, alias: ['songuoiphuthuoc', 'npt', 'sonpt', 'nguoiphuthuoc'] },
  { key: 'sdt', label: 'Điện thoại', w: 13, alias: ['sdt', 'dienthoai', 'sodienthoai', 'dt'] },
  { key: 'email', label: 'Email', w: 22, alias: ['email', 'mail'] },
  { key: 'cccd', label: 'Số CCCD', w: 15, alias: ['cccd', 'socccd', 'cmnd', 'socmnd', 'cancuoc', 'cmndcccd'] },
  { key: 'mst', label: 'Mã số thuế', w: 13, alias: ['mst', 'masothue', 'masothuecanhan', 'mstcanhan'] },
  { key: 'nganHang', label: 'Ngân hàng', w: 14, alias: ['nganhang', 'tennganhang'] },
  { key: 'stk', label: 'Số tài khoản', w: 16, alias: ['stk', 'sotaikhoan', 'taikhoan', 'sotk'] }
];
const nh = s => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/gi, 'd').toLowerCase().replace(/[^a-z0-9]/g, '');
function eNum(v) { if (typeof v === 'number') return Math.round(v); const s = String(v ?? '').trim(); if (!s) return 0; if (/^-?\d+[.,]\d{1,2}$/.test(s)) return Math.round(parseFloat(s.replace(',', '.'))); return F.parse(s); }
function eDate(v) {
  if (v === '' || v == null) return '';
  if (typeof v === 'number' && v > 59) { const d = new Date(Date.UTC(1899, 11, 30) + Math.round(v) * 864e5); return d.toISOString().slice(0, 10); }
  const s = String(v).trim(); let m;
  if ((m = s.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})/))) return `${m[1]}-${pad(+m[2])}-${pad(+m[3])}`;
  if ((m = s.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})/))) return `${m[3]}-${pad(+m[2])}-${pad(+m[1])}`;
  if (/^\d{4}$/.test(s)) return s + '-01-01';
  return '';
}
function eText(k, v) {
  let s = typeof v === 'number' ? String(Math.round(v)) : String(v ?? '').trim();
  if (k === 'sdt' && /^[1-9]\d{8}$/.test(s)) s = '0' + s;          // Excel làm mất số 0 đầu
  if (k === 'cccd' && /^\d{11}$/.test(s)) s = '0' + s;
  return s;
}
function empParse(rows, fromPaste) {
  rows = rows.filter(r => r && r.some(c => String(c ?? '').trim() !== ''));
  let hr = -1, map = {};
  for (let r = 0; r < Math.min(rows.length, 20) && hr < 0; r++) {
    const m = {};
    rows[r].forEach((h, i) => { const n = nh(h); const c = EIMP.find(c => c.alias.includes(n)); if (c && m[c.key] === undefined) m[c.key] = i; });
    if (m.ten !== undefined) { hr = r; map = m; }
  }
  if (hr < 0) { if (!fromPaste) throw new Error('Không thấy cột "Họ và tên" trong file. Tải file mẫu về để điền cho đúng nhé'); EIMP.forEach((c, i) => map[c.key] = i); }
  const out = [], seen = new Set(); let auto = 0;
  DB.employees.forEach(e => { const n = parseInt(String(e.ma).replace(/\D/g, '')) || 0; if (n > auto) auto = n; });
  const nextMa = () => { let m; do m = 'NV' + String(++auto).padStart(3, '0'); while (DB.employees.some(e => e.ma === m) || seen.has(m)); return m; };
  rows.slice(hr + 1).forEach((r, k) => {
    const g = key => map[key] === undefined ? '' : r[map[key]];
    const ten = String(g('ten') ?? '').trim(); if (!ten) return;
    const o = { dong: hr + k + 2, has: {} };
    EIMP.forEach(c => {
      const v = g(c.key); o.has[c.key] = map[c.key] !== undefined && String(v ?? '').trim() !== '';
      o[c.key] = c.t === 'money' || c.t === 'int' ? eNum(v) : c.t === 'date' ? eDate(v) : eText(c.key, v);
    });
    o.ten = ten;
    const gt = nh(o.gioiTinh); o.gioiTinh = gt.startsWith('nu') || gt === 'f' ? 'Nữ' : 'Nam';
    const tt = nh(o.trangThai); o.trangThai = tt.includes('thu') ? 'Thử việc' : tt.includes('nghi') ? 'Nghỉ việc' : 'Đang làm';
    o.ma = String(o.ma || '').toUpperCase(); if (!o.ma) o.ma = nextMa();
    if (!o.has.luongBH) o.luongBH = o.luongCB;
    if (seen.has(o.ma)) { o.tt = 'trung'; } else { seen.add(o.ma); o.cu = DB.employees.find(e => e.ma === o.ma); o.tt = o.cu ? 'cu' : 'moi'; }
    out.push(o);
  });
  return out;
}
function empImport() {
  let items = [], mode = 'capnhat', tm;
  const m = Modal.open({ title: 'Nhập danh sách nhân viên', width: 1040,
    body: `<div class="row mb" style="gap:10px;flex-wrap:wrap"><label class="btn pri">${ic('upload')} Chọn file Excel<input type="file" accept=".xlsx,.csv" hidden id="eiFile"></label><button class="btn" data-mau>${ic('download')} Tải file mẫu</button><span class="muted small" id="eiName">Chưa chọn file</span></div>
      <p class="small muted" style="margin:0 0 10px">File cần có dòng tiêu đề. Bắt buộc có cột <b>Họ và tên</b>, các cột khác có cột nào nhập cột đó: Mã nhân viên, Giới tính, Ngày sinh, Phòng ban, Chức vụ, Ngày vào làm, Trạng thái, Lương cơ bản, Lương đóng BH, Phụ cấp chịu thuế, Phụ cấp không chịu thuế, Số người phụ thuộc, Điện thoại, Email, CCCD, MST, Ngân hàng, Số tài khoản. Thiếu mã thì tự đánh mã. Bỏ trống Lương đóng BH thì lấy bằng lương cơ bản (ghi 0 nếu không đóng BH).</p>
      <div class="field" style="margin-bottom:10px"><label>Hoặc dán thẳng từ Excel: bôi đen vùng dữ liệu (có cả dòng tiêu đề), Ctrl + C rồi bấm vào ô dưới, Ctrl + V</label><textarea class="inp" id="eiPaste" rows="3" style="resize:vertical;font-size:12.5px" placeholder="Dán dữ liệu từ Excel vào đây..."></textarea></div>
      <div id="eiOpt" class="row mb small" style="gap:14px;display:none"><b>Nhân viên trùng mã đã có:</b><label class="row" style="gap:5px"><input type="radio" name="eiMode" value="capnhat" checked> Cập nhật theo danh sách mới</label><label class="row" style="gap:5px"><input type="radio" name="eiMode" value="boqua"> Bỏ qua, giữ bản cũ</label></div>
      <div id="eiT"></div>`,
    foot: `<span class="muted small left" id="eiSum"></span><button class="btn" data-no>Hủy</button><button class="btn pri" data-ok disabled>${ic('check')} Nhập nhân viên</button>` });
  const $m = s => m.el.querySelector(s), okBtn = $m('[data-ok]');
  const draw = () => {
    const moi = items.filter(o => o.tt === 'moi').length, cu = items.filter(o => o.tt === 'cu').length, tr = items.filter(o => o.tt === 'trung').length;
    const n = moi + (mode === 'capnhat' ? cu : 0);
    $m('#eiSum').innerHTML = items.length ? `${moi} mới · ${cu} trùng mã đã có${tr ? ` · ${tr} trùng mã trong danh sách (bỏ qua)` : ''}` : '';
    $m('#eiOpt').style.display = cu ? '' : 'none';
    okBtn.disabled = !n; okBtn.innerHTML = `${ic('check')} Nhập ${n || ''} nhân viên`;
    table($m('#eiT'), { id: 'eimp', pageSize: 10, compact: true, rows: items, ph: 'Tìm trong danh sách vừa đọc...', empty: 'Chọn file Excel hoặc dán dữ liệu để xem trước danh sách', columns: [
      { key: 'dong', label: 'Dòng', align: 'c' }, { key: 'ma', label: 'Mã NV' }, { key: 'ten', label: 'Họ và tên' }, { key: 'phongBan', label: 'Phòng ban' }, { key: 'chucVu', label: 'Chức vụ' },
      { key: 'ngayVao', label: 'Ngày vào', fmt: 'date' }, { key: 'luongCB', label: 'Lương CB', fmt: 'money' }, { key: 'luongBH', label: 'Lương BH', fmt: 'money' }, { key: 'soNPT', label: 'NPT', align: 'c' },
      { key: 'tt', label: 'Tình trạng', render: o => `<span class="badge ${o.tt === 'trung' ? 'b-red' : o.tt === 'moi' ? 'b-green' : mode === 'capnhat' ? 'b-amber' : ''}">${o.tt === 'trung' ? 'Trùng mã, bỏ qua' : o.tt === 'moi' ? 'Thêm mới' : mode === 'capnhat' ? 'Cập nhật' : 'Bỏ qua'}</span>` }] });
  };
  draw();
  $m('[data-no]').onclick = () => m.close();
  $m('[data-mau]').onclick = () => XLSX.save('Mau nhap danh sach nhan vien', [{ name: 'Nhân viên', title: 'DANH SÁCH NHÂN VIÊN', subtitles: ['Điền từ dòng dưới tiêu đề. Bắt buộc có Họ và tên. Ngày ghi dạng 15/03/2024. Lương ghi số, không cần chữ "đ".'], noFilter: true,
    columns: EIMP.map(c => ({ label: c.label, type: c.t === 'money' ? 'money' : c.t === 'int' ? 'int' : 'text', width: c.w })),
    rows: [['NV101', 'Nguyễn Văn An', 'Nam', '12/05/1992', 'Kinh doanh', 'Nhân viên KD', '01/03/2024', 'Đang làm', 10000000, 8000000, 1000000, 730000, 1, '0901234567', 'an.nguyen@congty.vn', '079092001234', '8123456789', 'Vietcombank', '0071000123456'],
           ['NV102', 'Trần Thị Bình', 'Nữ', '20/11/1996', 'Kế toán', 'Kế toán viên', '15/06/2025', 'Thử việc', 9000000, '', 500000, 730000, 0, '0912345678', '', '079196004321', '', 'Techcombank', '19033344455566'],
           ['', 'Lê Hoàng Cường', 'Nam', '03/02/1990', 'Kho vận', 'Thủ kho', '01/10/2023', 'Đang làm', 11000000, 0, 1000000, 730000, 2, '', '', '', '', '', '']] }]);
  m.el.querySelectorAll('[name=eiMode]').forEach(r => r.onchange = () => { mode = r.value; draw(); });
  $m('#eiFile').onchange = async e => {
    const f = e.target.files[0]; if (!f) return; e.target.value = '';
    $m('#eiName').textContent = 'Đang đọc ' + f.name + '...';
    try { items = empParse(await XLSX.read(f)); UI.tbl.eimp && (UI.tbl.eimp.page = 0, UI.tbl.eimp.q = ''); $m('#eiPaste').value = ''; $m('#eiName').innerHTML = `<b>${esc(f.name)}</b> · đọc được ${items.length} nhân viên`; if (!items.length) toast('File không có dòng nào có họ tên', 'err'); }
    catch (err) { items = []; $m('#eiName').textContent = 'Chưa đọc được file'; toast(esc(err.message || 'Không đọc được file'), 'err', 5000); }
    draw();
  };
  $m('#eiPaste').oninput = e => { clearTimeout(tm); tm = setTimeout(() => { const t = e.target.value; items = t.trim() ? empParse(t.replace(/\r/g, '').split('\n').map(l => l.split('\t')), true) : []; UI.tbl.eimp && (UI.tbl.eimp.page = 0); $m('#eiName').textContent = t.trim() ? 'Dữ liệu dán vào · đọc được ' + items.length + ' nhân viên' : 'Chưa chọn file'; draw(); }, 250); };
  okBtn.onclick = () => {
    let them = 0, sua = 0; const pbs = S().phongBan;
    items.forEach(o => {
      if (o.tt === 'trung') return;
      if (o.phongBan && !pbs.includes(o.phongBan)) pbs.push(o.phongBan);
      const d = {}; EIMP.forEach(c => d[c.key] = o[c.key]);
      if (o.cu) { if (mode !== 'capnhat') return; EIMP.forEach(c => { if (c.key !== 'ma' && o.has[c.key]) o.cu[c.key] = d[c.key]; }); sua++; }
      else { DB.employees.push({ id: uid(), ...d, phongBan: d.phongBan || pbs[0], ngayVao: d.ngayVao || D.today() }); them++; }
    });
    m.close(); App.changed();
    toast(`Đã thêm <b>${them}</b> nhân viên${sua ? `, cập nhật <b>${sua}</b>` : ''}. Nhớ bấm "Lưu vào file" để ghi vào file.`, 'ok', 4500);
  };
}

/* ---------- Bảng chấm công ---------- */
UI.brush = 'X';
let painting = false;
document.addEventListener('mouseup', () => { painting = false; });
function isOff(ym, d) { const w = D.dow(ym, d); return w === 0 || (S().ngayNghi === 'T7CN' && w === 6); }
function ccRowSum(ym, eid) { const a = summarize(ym, eid); return `<td class="sum">${F.num(a.lv)}</td><td class="sum">${F.num(a.P + a.L)}</td><td class="sum">${F.num(a.kl)}</td><td class="sum hl">${F.num(a.hl)}</td>`; }
const vCham = {
  title: 'Bảng chấm công', icon: 'calcheck', desc: 'Chọn ký hiệu rồi bấm (hoặc kéo chuột) lên các ô ngày để chấm',
  tools: () => monthTool(), after: bindMonth,
  emps() { const ym = UI.m, end = ym + '-31'; return DB.employees.filter(e => (!e.ngayVao || e.ngayVao <= end) && (e.trangThai !== 'Nghỉ việc' || summarize(ym, e.id).has) && (!UI.pb || e.phongBan === UI.pb)); },
  render(el) {
    const ym = UI.m, n = D.daysIn(ym), today = D.today(), all = vCham.emps();
    const q = (UI.ccq || '').toLowerCase();
    const found = all.filter(e => !q || (e.ten + ' ' + e.ma).toLowerCase().includes(q));
    const pst = pgState('cc', 20), emps = pageSlice(pst, found);
    App.subtitle = F.month(ym);
    const days = Array.from({ length: n }, (_, i) => i + 1);
    el.innerHTML = `
      <div class="card mb no-print"><div class="card-b row" style="gap:8px">
        ${Object.entries(CODES).map(([k, c]) => `<button class="brush ${UI.brush === k ? 'on' : ''}" style="background:${c.bg};color:${c.c}" data-act="brush" data-k="${k}">${k} <small>${c.n}</small></button>`).join('')}
        <button class="brush ${UI.brush === '' ? 'on' : ''}" style="background:#f2f4f7;color:#475467" data-act="brush" data-k="">Tẩy <small>xóa ô</small></button>
        <span class="grow"></span>
        <div class="search">${ic('search')}<input class="inp" id="ccQ" placeholder="Tìm nhân viên..." value="${esc(UI.ccq || '')}" style="width:190px"></div>
        ${selectHTML('pbF', [{ value: '', label: 'Tất cả phòng ban' }, ...S().phongBan], UI.pb)}
        <button class="btn" data-act="ccFill">${ic('check')} Điền "X" ngày còn trống</button>
        <button class="btn danger" data-act="ccClear">${ic('trash')} Xóa tháng</button>
      </div></div>
      <div class="pg-host"><div class="cc-wrap"><table class="cc"><thead><tr><th class="nm">Nhân viên<br><small style="font-weight:500">${F.month(ym)}</small></th>
        ${days.map(d => `<th class="${ym + '-' + pad(d) === today ? 'td' : isOff(ym, d) ? 'we' : ''}">${d}<br>${D.dowName[D.dow(ym, d)]}</th>`).join('')}
        <th>Công<br>làm</th><th>Phép<br>+Lễ</th><th>Nghỉ<br>KL</th><th>Công<br>hưởng</th><th>Giờ<br>OT</th></tr></thead>
        <tbody>${emps.map(e => { const a = att(ym, e.id); return `<tr data-e="${e.id}"><td class="nm"><b>${esc(e.ten)}</b><br><small class="muted">${esc(e.ma)} · ${esc(e.phongBan)}</small></td>
          ${days.map(d => { const k = a.d[d] || ''; const c = CODES[k]; return `<td class="d ${isOff(ym, d) ? 'we' : ''}" data-e="${e.id}" data-d="${d}" style="${c ? `background:${c.bg};color:${c.c}` : ''}">${k}</td>`; }).join('')}
          ${ccRowSum(ym, e.id)}<td class="ot"><input type="number" min="0" step="0.5" value="${a.ot || ''}" data-ot="${e.id}"></td></tr>`; }).join('') || `<tr><td class="nm">Chưa có nhân viên</td></tr>`}</tbody></table></div><div class="tbl-pager" id="ccP">${pagerHTML(pst, found.length)}</div></div>
      <p class="small muted">Công chuẩn: <b>${S().congChuan}</b> ngày/tháng · Ngày nghỉ tuần: <b>${S().ngayNghi === 'T7CN' ? 'Thứ 7 và Chủ nhật' : 'Chủ nhật'}</b> · Công hưởng lương = đi làm + công tác + phép + lễ. Sửa trong Cài đặt.</p>`;
    onVal('pbF', v => { UI.pb = v; pst.page = 0; App.render(); });
    bindPager($('#ccP'), pst, App.render);
    let tq; $('#ccQ').addEventListener('input', e => { clearTimeout(tq); tq = setTimeout(() => { UI.ccq = e.target.value; pst.page = 0; App.render(); const i = $('#ccQ'); i.focus(); i.setSelectionRange(i.value.length, i.value.length); }, 300); });
    const grid = $('.cc', el);
    const paint = td => {
      const e = td.dataset.e, d = +td.dataset.d, a = att(ym, e);
      if ((a.d[d] || '') === UI.brush) return;
      if (UI.brush) a.d[d] = UI.brush; else delete a.d[d];
      const c = CODES[UI.brush]; td.textContent = UI.brush; td.style.background = c ? c.bg : ''; td.style.color = c ? c.c : '';
      const tr = td.parentElement; $$('td.sum', tr).forEach(x => x.remove()); tr.querySelector('td.ot').insertAdjacentHTML('beforebegin', ccRowSum(ym, e));
      Store.changed();
    };
    grid.addEventListener('mousedown', e => { const td = e.target.closest('td.d'); if (!td) return; e.preventDefault(); painting = true; paint(td); });
    grid.addEventListener('mouseover', e => { if (!painting) return; const td = e.target.closest('td.d'); if (td) paint(td); });
    grid.addEventListener('change', e => { const i = e.target.closest('[data-ot]'); if (!i) return; att(ym, i.dataset.ot).ot = Math.max(0, parseFloat(i.value) || 0); Store.changed(); });
  },
  cols() { const ym = UI.m, n = D.daysIn(ym); return [{ label: 'Mã NV', width: 9 }, { label: 'Họ và tên', width: 24 }, { label: 'Phòng ban', width: 14 }, ...Array.from({ length: n }, (_, i) => ({ label: `${i + 1}\n${D.dowName[D.dow(ym, i + 1)]}`, type: 'center', width: 4.3 })), { label: 'Công làm', type: 'num', sum: true, width: 8 }, { label: 'Phép + Lễ', type: 'num', sum: true, width: 8 }, { label: 'Nghỉ KL', type: 'num', sum: true, width: 8 }, { label: 'Công hưởng', type: 'num', sum: true, width: 9 }, { label: 'Giờ OT', type: 'num', sum: true, width: 8 }]; },
  rows() { const ym = UI.m, n = D.daysIn(ym); return vCham.emps().map(e => { const a = att(ym, e.id), s = summarize(ym, e.id); return [e.ma, e.ten, e.phongBan, ...Array.from({ length: n }, (_, i) => a.d[i + 1] || ''), s.lv, s.P + s.L, s.kl, s.hl, s.ot]; }); },
  legend() { return 'Ký hiệu: ' + Object.entries(CODES).map(([k, c]) => `${k} = ${c.n}`).join(' · '); },
  excel() { XLSX.save(`Bang cham cong ${UI.m}`, [{ name: 'Chấm công ' + UI.m, title: 'BẢNG CHẤM CÔNG ' + F.month(UI.m).toUpperCase(), subtitles: [companyLine()], columns: vCham.cols(), rows: vCham.rows(), totals: true, freezeCols: 2, footer: [vCham.legend()] }]); },
  print() {
    const ym = UI.m, n = D.daysIn(ym), cols = vCham.cols(), rows = vCham.rows();
    const head = `<tr>${cols.map(c => `<th style="padding:3px 2px;font-size:9px">${esc(c.label).replace('\n', '<br>')}</th>`).join('')}</tr>`;
    const body = rows.map(r => `<tr>${r.map((v, i) => { const c = CODES[v]; return `<td class="${i > 2 ? 'c' : ''}" style="padding:3px 2px;font-size:9.5px;${c && i > 2 && i < 3 + n ? `background:${c.bg};color:${c.c};font-weight:700` : ''}">${typeof v === 'number' ? F.num(v) : esc(v)}</td>`; }).join('')}</tr>`).join('');
    Print.run(`<div class="pr">${Print.head()}<h2>Bảng chấm công ${F.month(ym)}</h2><table><thead>${head}</thead><tbody>${body}</tbody></table><div class="pr-note">${vCham.legend()}</div>${Print.signs(['Người chấm công', 'Phụ trách bộ phận', 'Giám đốc'])}</div>`, { landscape: true, title: 'Bang cham cong ' + ym });
  }
};
Object.assign(ACT, {
  brush: d => { UI.brush = d.k; $$('.brush').forEach(b => b.classList.toggle('on', b.dataset.k === d.k)); },
  ccFill: () => {
    const ym = UI.m, n = D.daysIn(ym), today = D.today(); let c = 0;
    vCham.emps().forEach(e => { const a = att(ym, e.id); for (let d = 1; d <= n; d++) { if (isOff(ym, d) || a.d[d]) continue; if (ym + '-' + pad(d) > today && ym <= D.ymNow()) continue; a.d[d] = 'X'; c++; } });
    App.changed(); toast(`Đã điền ${c} ô "X" (bỏ qua ngày nghỉ tuần và ngày chưa tới)`, 'ok');
  },
  ccClear: async () => { if (await Modal.confirm(`Xóa toàn bộ chấm công ${F.month(UI.m)}${UI.pb ? ' của ' + esc(UI.pb) : ''}?`, { ok: 'Xóa', danger: true })) { vCham.emps().forEach(e => { if (DB.cham[UI.m]) delete DB.cham[UI.m][e.id]; }); App.changed(); } }
});

/* ---------- Bảng lương ---------- */
const PCOLS = [
  { k: 'ma', l: 'Mã NV', t: 'text' }, { k: 'ten', l: 'Họ và tên', t: 'text', w: 24 }, { k: 'phongBan', l: 'Phòng ban', t: 'text' },
  { k: 'luongCB', l: 'Lương cơ bản', t: 'money', g: 'Thu nhập' }, { k: 'cong', l: 'Công hưởng lương', t: 'num', g: 'Thu nhập' }, { k: 'luongCong', l: 'Lương theo công', t: 'money', g: 'Thu nhập' },
  { k: 'pcCT', l: 'PC chịu thuế', t: 'money', g: 'Thu nhập' }, { k: 'pcKT', l: 'PC không thuế', t: 'money', g: 'Thu nhập' }, { k: 'ot', l: 'Giờ OT', t: 'num', g: 'Thu nhập' }, { k: 'tienOT', l: 'Tiền OT', t: 'money', g: 'Thu nhập' },
  { k: 'thuong', l: 'Thưởng', t: 'money', g: 'Thu nhập', input: 1 }, { k: 'gross', l: 'Tổng thu nhập', t: 'money', g: 'Thu nhập', b: 1 },
  { k: 'luongBH', l: 'Lương đóng BH', t: 'money', g: 'Bảo hiểm NLĐ' }, { k: 'bhxh', l: 'BHXH', t: 'money', g: 'Bảo hiểm NLĐ' }, { k: 'bhyt', l: 'BHYT', t: 'money', g: 'Bảo hiểm NLĐ' }, { k: 'bhtn', l: 'BHTN', t: 'money', g: 'Bảo hiểm NLĐ' },
  { k: 'tnChiuThue', l: 'TN chịu thuế', t: 'money', g: 'Thuế TNCN' }, { k: 'soNPT', l: 'Số NPT', t: 'int', g: 'Thuế TNCN' }, { k: 'giamTru', l: 'Giảm trừ gia cảnh', t: 'money', g: 'Thuế TNCN' }, { k: 'tnTinhThue', l: 'TN tính thuế', t: 'money', g: 'Thuế TNCN' }, { k: 'thue', l: 'Thuế TNCN', t: 'money', g: 'Thuế TNCN', b: 1 },
  { k: 'tamUng', l: 'Tạm ứng', t: 'money', g: 'Khấu trừ', input: 1 }, { k: 'khauTru', l: 'Khấu trừ khác', t: 'money', g: 'Khấu trừ', input: 1 },
  { k: 'thucLinh', l: 'THỰC LĨNH', t: 'money', b: 1 }
];
const vLuong = {
  title: 'Bảng lương', icon: 'wallet', desc: 'Tự tính lương theo công, OT, BHXH, thuế TNCN. Ô màu vàng nhập trực tiếp.',
  tools: () => monthTool() + `<button class="btn" data-act="slipAll">${ic('receipt')} In phiếu lương</button>`, after: bindMonth,
  render(el) {
    App.subtitle = F.month(UI.m);
    el.innerHTML = `<div class="tbl-card"><div class="tbl-tools"><div class="search">${ic('search')}<input class="inp" id="plQ" placeholder="Tìm nhân viên..." value="${esc(UI.plq || '')}"></div>${selectHTML('pbF', [{ value: '', label: 'Tất cả phòng ban' }, ...S().phongBan], UI.pb)}<span class="grow"></span><span class="small muted">Thuế theo biểu 5 bậc, giảm trừ ${F.short(S().gtBanThan)}/NPT ${F.short(S().gtNPT)}</span></div><div class="tbl-wrap" id="plW"></div><div class="tbl-pager" id="plP"></div></div>`;
    const pst = pgState('pl', 20);
    onVal('pbF', v => { UI.pb = v; pst.page = 0; App.render(); });
    bindPager($('#plP'), pst, vLuong.draw);
    $('#plQ').addEventListener('input', e => { UI.plq = e.target.value; pst.page = 0; vLuong.draw(); });
    $('#plW').addEventListener('change', e => {
      const i = e.target.closest('[data-k]'); if (!i) return;
      const ym = UI.m; DB.extra[ym] = DB.extra[ym] || {}; const x = DB.extra[ym][i.dataset.e] = DB.extra[ym][i.dataset.e] || {};
      x[i.dataset.k] = F.parse(i.value); Store.changed(); keepFocus(vLuong.draw);
    });
    vLuong.draw();
  },
  list() { const q = (UI.plq || '').toLowerCase(); return payroll(UI.m, UI.pb).filter(x => !q || (x.ten + ' ' + x.ma + ' ' + x.phongBan).toLowerCase().includes(q)); },
  draw() {
    const L = vLuong.list(), w = $('#plW'); if (!w) return;
    const pst = pgState('pl', 20), V = pageSlice(pst, L), off = pst.page * pst.size;
    $('#plP').innerHTML = pagerHTML(pst, L.length, ' · dòng Tổng cộng tính cho tất cả');
    // 3 cột đầu (STT, Mã, Họ tên) đứng yên khi cuộn ngang
    const LEFT = [0, 44, 116];
    const WID = [44, 72];
    const sk = (i, extra = '') => i <= 2 ? ` class="stk ${extra}" style="left:${LEFT[i]}px;${i < 2 ? `min-width:${WID[i]}px;max-width:${WID[i]}px;` : 'min-width:170px;'}${i === 2 ? 'box-shadow:inset -1px 0 0 #e3e7ef' : ''}"` : (extra ? ` class="${extra}"` : '');
    const fmtv = (c, v) => c.t === 'num' ? F.num(v) : c.t === 'int' ? v : c.t === 'text' ? esc(v) : F.money(v);
    const al = c => c.t !== 'text' ? 'r' : '';
    let r1 = `<th${sk(0, 'c')} rowspan="2">STT</th>`, r2 = '';
    PCOLS.forEach((c, i) => {
      if (!c.g) r1 += `<th${sk(i + 1, al(c))} rowspan="2">${c.l}</th>`;
      else { if (!i || PCOLS[i - 1].g !== c.g) r1 += `<th class="c grp" colspan="${PCOLS.filter(x => x.g === c.g).length}">${c.g}</th>`; r2 += `<th class="${al(c)}" style="top:33px">${c.l}</th>`; }
    });
    const totCell = c => c.t === 'money' && !['luongCB', 'luongBH'].includes(c.k) ? F.money(sum(L, x => x[c.k])) : ['cong', 'ot'].includes(c.k) ? F.num(sum(L, x => x[c.k])) : '';
    w.innerHTML = `<table class="tbl compact pl"><thead><tr style="height:33px">${r1}<th rowspan="2"></th></tr><tr>${r2}</tr></thead>
      <tbody>${V.map((x, ri) => `<tr><td${sk(0, 'c')}>${off + ri + 1}</td>${PCOLS.map((c, i) => c.input
        ? `<td class="in"><input data-k="${c.k}" data-e="${x.id}" value="${x[c.k] ? F.money(x[c.k]) : ''}" data-money inputmode="numeric" placeholder="0"></td>`
        : `<td${sk(i + 1, al(c) + (c.k === 'thucLinh' ? ' net' : ''))}>${c.k === 'ten' ? `<b>${esc(x.ten)}</b>` : c.b ? `<b>${fmtv(c, x[c.k])}</b>` : fmtv(c, x[c.k])}${c.k === 'luongBH' && x.noBH && x.luongBH ? ' <span class="badge b-amber" data-tip="Nghỉ không lương từ 14 ngày trở lên: tháng này không đóng BH">!</span>' : ''}</td>`).join('')}<td class="act"><button class="ibtn" data-act="slip" data-id="${x.id}" data-tip="In phiếu lương">${ic('receipt')}</button></td></tr>`).join('') || `<tr><td colspan="30"><div class="empty">Không có nhân viên trong kỳ này</div></td></tr>`}</tbody>
      ${L.length ? `<tfoot><tr><td${sk(0)}></td>${PCOLS.map((c, i) => `<td${sk(i + 1, al(c))}>${i === 1 ? 'TỔNG CỘNG' : totCell(c)}</td>`).join('')}<td></td></tr></tfoot>` : ''}</table>`;
  },
  excel() {
    const L = vLuong.list();
    const cols = [{ label: 'STT', type: 'int', width: 6 }, ...PCOLS.map(c => ({ label: c.l, type: c.t, sum: c.t === 'money' && !['luongCB', 'luongBH'].includes(c.k) || ['cong', 'ot'].includes(c.k), width: c.w || (c.t === 'money' ? 14 : c.t === 'text' ? 16 : 9) }))];
    const rows = L.map((x, i) => [i + 1, ...PCOLS.map(c => x[c.k])]);
    const s = S();
    const dn = L.map((x, i) => [i + 1, x.ma, x.ten, x.luongBH, x.bhxh, x.bhyt, x.bhtn, x.bhNLD, x.bdxh, x.bdyt, x.bdtn, x.kpcd, x.bhDN, x.bhNLD + x.bhDN]);
    XLSX.save(`Bang luong ${UI.m}`, [
      { name: 'Bảng lương ' + UI.m, title: 'BẢNG THANH TOÁN TIỀN LƯƠNG ' + F.month(UI.m).toUpperCase(), subtitles: [companyLine(), `Công chuẩn ${s.congChuan} ngày · Giảm trừ bản thân ${F.money(s.gtBanThan)} · NPT ${F.money(s.gtNPT)}/người · BH NLĐ ${s.bhxhNLD}% + ${s.bhytNLD}% + ${s.bhtnNLD}%`], columns: cols, rows, totals: true, freezeCols: 3 },
      { name: 'Bảo hiểm ' + UI.m, title: 'BẢNG TRÍCH NỘP BẢO HIỂM BẮT BUỘC ' + F.month(UI.m).toUpperCase(), subtitles: [companyLine()], columns: [{ label: 'STT', type: 'int', width: 6 }, { label: 'Mã NV', width: 9 }, { label: 'Họ và tên', width: 24 }, { label: 'Lương đóng BH', type: 'money' }, { label: `BHXH NLĐ ${s.bhxhNLD}%`, type: 'money', sum: true }, { label: `BHYT NLĐ ${s.bhytNLD}%`, type: 'money', sum: true }, { label: `BHTN NLĐ ${s.bhtnNLD}%`, type: 'money', sum: true }, { label: 'Cộng NLĐ', type: 'money', sum: true }, { label: `BHXH DN ${s.bhxhDN}%`, type: 'money', sum: true }, { label: `BHYT DN ${s.bhytDN}%`, type: 'money', sum: true }, { label: `BHTN DN ${s.bhtnDN}%`, type: 'money', sum: true }, { label: `KPCĐ ${s.kpcdDN}%`, type: 'money', sum: true }, { label: 'Cộng DN', type: 'money', sum: true }, { label: 'Tổng phải nộp', type: 'money', sum: true }], rows: dn, totals: true }
    ]);
  },
  print() {
    const L = vLuong.list();
    const pc = PCOLS.filter(c => !['phongBan', 'luongBH', 'pcKT', 'ot', 'soNPT', 'tnChiuThue'].includes(c.k));
    const body = L.map((x, i) => `<tr><td class="c">${i + 1}</td>${pc.map(c => `<td class="${c.t === 'text' ? '' : 'r'}">${c.t === 'text' ? esc(x[c.k]) : c.t === 'num' ? F.num(x[c.k]) : F.money(x[c.k])}</td>`).join('')}<td style="width:60px"></td></tr>`).join('');
    const foot = `<tr><td></td>${pc.map((c, i) => `<td class="r">${i === 1 ? 'Tổng cộng' : c.t === 'money' && c.k !== 'luongCB' ? F.money(sum(L, x => x[c.k])) : ''}</td>`).join('')}<td></td></tr>`;
    Print.run(`<div class="pr">${Print.head()}<h2>Bảng thanh toán tiền lương</h2><div class="pr-sub">${F.month(UI.m)}${UI.pb ? ' · ' + esc(UI.pb) : ''}</div><table><thead><tr><th>STT</th>${pc.map(c => `<th>${c.l}</th>`).join('')}<th>Ký nhận</th></tr></thead><tbody>${body}</tbody><tfoot>${foot}</tfoot></table><div class="pr-note">Tổng số tiền thực lĩnh bằng chữ: <b>${docSo(sum(L, x => x.thucLinh))}</b>.</div>${Print.signs(['Người lập biểu', 'Kế toán trưởng', 'Giám đốc'])}</div>`, { landscape: true, title: 'Bang luong ' + UI.m });
  }
};
function slipHTML(x) {
  const s = S(), c = DB.settings.company || {}, e = x.e || {}, ym = UI.m, [y, mo] = ym.split('-'), a = summarize(ym, x.id);
  const mn = v => v ? F.money(v) : '-';
  const row = (stt, l, dg, v) => `<tr><td class="c">${stt}</td><td>${l}</td><td class="dg">${dg || ''}</td><td class="r">${mn(v)}</td></tr>`;
  const ded = x.bhNLD + x.thue + x.tamUng + x.khauTru;
  const bhNote = p => x.noBH ? 'Tháng này không đóng (nghỉ KL từ 14 ngày)' : `${p}% × ${F.money(Math.min(+x.luongBH || 0, 20 * s.luongCoSo))}`;
  const d = new Date(), kv = (l, v) => `<div><span>${l}</span><b>${v}</b></div>`;
  return `<div class="ps">
    <div class="ps-top">
      <div class="ps-co">${c.logo ? `<img src="${c.logo}">` : ''}<div><b>${esc(c.ten || '')}</b>${c.diaChi ? `<div class="l">${esc(c.diaChi)}</div>` : ''}<div class="l">${[c.sdt ? 'ĐT: ' + esc(c.sdt) : '', c.mst ? 'MST: ' + esc(c.mst) : ''].filter(Boolean).join(' · ')}</div></div></div>
      <div class="ps-tt"><h1>PHIẾU LƯƠNG</h1><div>Kỳ lương: <b>Tháng ${+mo}/${y}</b></div><div>Số phiếu: <b>PL${y}${mo}-${esc(x.ma)}</b></div></div>
    </div>
    <div class="ps-band"></div>
    <div class="ps-emp"><div class="ps-av">${F.initials(x.ten)}</div><div class="ps-eg">
      ${kv('Họ và tên', esc(x.ten))}${kv('Mã nhân viên', esc(x.ma))}${kv('Phòng ban', esc(x.phongBan || ''))}${kv('Chức vụ', esc(x.chucVu || ''))}
      ${kv('Ngày vào làm', e.ngayVao ? F.date(e.ngayVao) : '-')}${kv('Mã số thuế', esc(e.mst || '-'))}${kv('Ngân hàng', esc(e.nganHang || '-'))}${kv('Số tài khoản', esc(e.stk || '-'))}
    </div></div>
    <div class="ps-att">${[['Công chuẩn', x.cc], ['Công làm thực tế', F.num(a.lv)], ['Phép + Lễ', F.num(a.P + a.L)], ['Nghỉ không lương', F.num(a.kl)], ['Giờ làm thêm', F.num(x.ot)], ['Công hưởng lương', F.num(x.cong), 1]].map(([l, v, hl]) => `<div class="${hl ? 'hl' : ''}"><b>${v}</b><span>${l}</span></div>`).join('')}</div>
    <table class="ps-t"><thead><tr><th style="width:38px">STT</th><th style="text-align:left">Khoản mục</th><th style="text-align:left">Diễn giải</th><th style="width:130px;text-align:right">Số tiền (đ)</th></tr></thead><tbody>
      <tr class="sec"><td colspan="4">A. CÁC KHOẢN THU NHẬP</td></tr>
      ${row(1, 'Lương theo ngày công', `${F.money(x.luongCB)} ÷ ${x.cc} công × ${F.num(x.cong)} công`, x.luongCong)}
      ${row(2, 'Phụ cấp chịu thuế', 'Trách nhiệm, xăng xe, nhà ở...', x.pcCT)}
      ${row(3, 'Phụ cấp không chịu thuế', 'Ăn ca, điện thoại theo quy chế', x.pcKT)}
      ${row(4, 'Tiền làm thêm giờ', x.ot ? `${F.num(x.ot)} giờ × hệ số ${String(s.heSoOT).replace('.', ',')}` : '', x.tienOT)}
      ${row(5, 'Thưởng', '', x.thuong)}
      <tr class="sub"><td></td><td colspan="2">Tổng thu nhập (A)</td><td class="r">${F.money(x.gross)}</td></tr>
      <tr class="sec"><td colspan="4">B. CÁC KHOẢN KHẤU TRỪ</td></tr>
      ${row(1, 'Bảo hiểm xã hội', bhNote(s.bhxhNLD), x.bhxh)}
      ${row(2, 'Bảo hiểm y tế', bhNote(s.bhytNLD), x.bhyt)}
      ${row(3, 'Bảo hiểm thất nghiệp', bhNote(s.bhtnNLD), x.bhtn)}
      ${row(4, 'Thuế thu nhập cá nhân', x.thue ? 'Theo biểu thuế lũy tiến từng phần' : 'Chưa đến mức phải nộp', x.thue)}
      ${row(5, 'Tạm ứng trong tháng', '', x.tamUng)}
      ${row(6, 'Khấu trừ khác', '', x.khauTru)}
      <tr class="sub"><td></td><td colspan="2">Tổng khấu trừ (B)</td><td class="r">${F.money(ded)}</td></tr>
    </tbody></table>
    <div class="ps-net"><div><span>THỰC LĨNH (A - B)</span><small>Bằng chữ: ${docSo(x.thucLinh)}</small></div><b>${F.money(x.thucLinh)} đ</b></div>
    <div class="ps-2">
      <div class="ps-box"><h4>Cách tính thuế TNCN</h4>
        ${kv('Thu nhập chịu thuế', F.money(x.tnChiuThue))}${kv('Giảm trừ bản thân', '- ' + F.money(s.gtBanThan))}${kv(`Giảm trừ người phụ thuộc (${x.soNPT} người)`, '- ' + F.money(x.soNPT * s.gtNPT))}${kv('Bảo hiểm bắt buộc phần NLĐ', '- ' + F.money(x.bhNLD))}${kv('Thu nhập tính thuế', F.money(x.tnTinhThue))}${kv('Thuế TNCN phải nộp', F.money(x.thue))}
      </div>
      <div class="ps-box"><h4>Phần doanh nghiệp đóng (không trừ vào lương)</h4>
        ${kv(`BHXH (${s.bhxhDN}%)`, F.money(x.bdxh))}${kv(`BHYT (${s.bhytDN}%)`, F.money(x.bdyt))}${kv(`BHTN (${s.bhtnDN}%)`, F.money(x.bdtn))}${kv(`Kinh phí công đoàn (${s.kpcdDN}%)`, F.money(x.kpcd))}${kv('Cộng phần doanh nghiệp đóng', F.money(x.bhDN))}${kv('Tổng chi phí doanh nghiệp cho bạn', F.money(x.chiPhi))}
      </div>
    </div>
    <div class="ps-pay">Hình thức nhận lương: <b>${e.stk ? `Chuyển khoản ${esc(e.nganHang || '')} - STK ${esc(e.stk)}` : 'Tiền mặt'}</b></div>
    <div class="ps-date">Ngày ${d.getDate()} tháng ${d.getMonth() + 1} năm ${d.getFullYear()}</div>
    <div class="ps-sign"><div><b>Người lập phiếu</b><i>(Ký, ghi rõ họ tên)</i><div class="sp"></div></div><div><b>Kế toán trưởng</b><i>(Ký, ghi rõ họ tên)</i><div class="sp"></div></div><div><b>Người nhận lương</b><i>(Ký, ghi rõ họ tên)</i><div class="sp"></div>${esc(x.ten)}</div></div>
    <div class="ps-foot">Phiếu lương là thông tin cá nhân, vui lòng không chia sẻ. Có thắc mắc xin phản hồi phòng Kế toán - Nhân sự trong vòng 03 ngày kể từ ngày nhận phiếu.</div>
  </div>`;
}
Object.assign(ACT, {
  slip: d => { const x = payroll(UI.m).find(p => p.id === d.id); if (x) Print.run(`<div class="pr">${slipHTML(x)}</div>`, { title: 'Phieu luong ' + x.ma + ' ' + UI.m }); },
  slipAll: () => { const L = vLuong.list ? payroll(UI.m, UI.pb) : []; if (!L.length) return toast('Không có nhân viên', 'err'); Print.run(`<div class="pr">${L.map(slipHTML).join('')}</div>`, { title: 'Phieu luong ' + UI.m }); }
});

/* ---------- Thuế & bảo hiểm ---------- */
UI.y = defaultMonth().slice(0, 4);
const vThue = {
  title: 'Thuế TNCN & Bảo hiểm', label: 'Thuế & bảo hiểm', icon: 'percent', desc: 'Tổng hợp theo năm và công cụ tính lương Gross / Net',
  tools: () => { const ys = [...new Set([...Object.keys(DB.cham).map(m => m.slice(0, 4)), D.today().slice(0, 4)])].sort(); return `<label class="row small" style="gap:6px">Năm ${selectHTML('yPick', ys, UI.y)}</label>`; },
  after: () => onVal('yPick', v => { UI.y = v; App.render(); }),
  months() { return Array.from({ length: 12 }, (_, i) => UI.y + '-' + pad(i + 1)).filter(m => DB.cham[m] && Object.keys(DB.cham[m]).length); },
  data() {
    const ms = vThue.months(), per = {};
    const mrows = ms.map(m => { const L = payroll(m); L.forEach(x => { const p = per[x.id] = per[x.id] || { id: x.id, ma: x.ma, ten: x.ten, mst: x.mst, soNPT: x.soNPT, thang: 0, gross: 0, tnct: 0, bh: 0, gt: 0, thue: 0, net: 0 }; p.thang++; p.gross += x.gross; p.tnct += x.tnChiuThue; p.bh += x.bhNLD; p.gt += x.giamTru; p.thue += x.thue; p.net += x.thucLinh; });
      return { id: m, thang: F.month(m), nv: L.length, gross: sum(L, x => x.gross), tnct: sum(L, x => x.tnChiuThue), bh: sum(L, x => x.bhNLD), bhdn: sum(L, x => x.bhDN), thue: sum(L, x => x.thue), net: sum(L, x => x.thucLinh) }; });
    return { mrows, erows: Object.values(per).sort((a, b) => b.thue - a.thue) };
  },
  mcols: [{ key: 'thang', label: 'Tháng' }, { key: 'nv', label: 'Số NV', align: 'c', xtype: 'int' }, { key: 'gross', label: 'Tổng thu nhập', fmt: 'money', sum: true }, { key: 'tnct', label: 'TN chịu thuế', fmt: 'money', sum: true }, { key: 'bh', label: 'BH NLĐ', fmt: 'money', sum: true }, { key: 'bhdn', label: 'BH + KPCĐ DN', fmt: 'money', sum: true }, { key: 'thue', label: 'Thuế TNCN', fmt: 'money', sum: true }, { key: 'net', label: 'Thực lĩnh', fmt: 'money', sum: true }],
  ecols: [{ key: 'ma', label: 'Mã NV' }, { key: 'ten', label: 'Họ và tên' }, { key: 'mst', label: 'MST' }, { key: 'soNPT', label: 'NPT', align: 'c', xtype: 'int' }, { key: 'thang', label: 'Số tháng', align: 'c', xtype: 'int' }, { key: 'tnct', label: 'TN chịu thuế', fmt: 'money', sum: true }, { key: 'bh', label: 'BH bắt buộc', fmt: 'money', sum: true }, { key: 'gt', label: 'Giảm trừ gia cảnh', fmt: 'money', sum: true }, { key: 'thue', label: 'Thuế đã khấu trừ', fmt: 'money', sum: true }],
  render(el) {
    const { mrows, erows } = vThue.data(); App.subtitle = 'Năm ' + UI.y;
    el.innerHTML = `<div class="grid cols-21"><div><div id="tM"></div></div><div>${card('Tính nhanh lương Gross ⇄ Net', `
      <div class="form"><label class="field"><span>Số tiền</span><input class="inp num" id="cAmt" data-money value="30.000.000"></label><label class="field"><span>Loại</span><select class="inp" id="cType"><option value="g">Đây là lương Gross</option><option value="n">Đây là lương Net</option></select></label>
      <label class="field"><span>Lương đóng BH</span><input class="inp num" id="cBH" data-money placeholder="Bằng lương Gross"></label><label class="field"><span>Số người phụ thuộc</span><input class="inp num" type="number" min="0" id="cNPT" value="0"></label></div>
      <div class="calc-out mt" id="cOut"></div>`)}</div></div>
      <div class="mt"><div class="card-h" style="padding:0 0 10px;border:0"><h3>Quyết toán thuế theo nhân viên · năm ${UI.y}</h3></div><div id="tE"></div></div>`;
    table($('#tM'), { id: 'tm', columns: vThue.mcols, rows: mrows, search: false, free: true, empty: 'Chưa có dữ liệu năm này' });
    table($('#tE'), { id: 'te', columns: vThue.ecols, rows: erows, ph: 'Tìm nhân viên...' });
    const run = () => {
      const amt = F.parse($('#cAmt').value), npt = +$('#cNPT').value || 0, bhIn = F.parse($('#cBH').value);
      const fromGross = g => { const b = bhOf(bhIn || g, true), bd = bhOf(bhIn || g, false); const bh = b.bhxh + b.bhyt + b.bhtn; const tn = Math.max(0, g - bh - S().gtBanThan - npt * S().gtNPT); const t = taxOf(tn); return { g, b, bh, tn, t, net: g - bh - t, dn: bd.bhxh + bd.bhyt + bd.bhtn + bd.kpcd }; };
      let r;
      if ($('#cType').value === 'g') r = fromGross(amt);
      else { let lo = amt, hi = amt * 2 + 1e7; for (let i = 0; i < 60; i++) { const mid = (lo + hi) / 2; if (fromGross(mid).net < amt) lo = mid; else hi = mid; } r = fromGross(Math.round(hi)); }
      $('#cOut').innerHTML = `<div><span>Lương Gross</span><b>${F.vnd(r.g)}</b></div><div><span>BHXH ${S().bhxhNLD}% · BHYT ${S().bhytNLD}% · BHTN ${S().bhtnNLD}%</span><b>- ${F.money(r.bh)}</b></div><div><span>Giảm trừ gia cảnh</span><b>${F.money(S().gtBanThan + npt * S().gtNPT)}</b></div><div><span>Thu nhập tính thuế</span><b>${F.money(r.tn)}</b></div><div><span>Thuế TNCN</span><b>- ${F.money(r.t)}</b></div><div><span>Lương Net</span><b class="big">${F.vnd(r.net)}</b></div><div><span class="muted">DN đóng thêm BH + KPCĐ</span><b class="muted">${F.money(r.dn)}</b></div><div><span class="muted">Tổng chi phí DN</span><b class="muted">${F.money(r.g + r.dn)}</b></div>`;
    };
    ['cAmt', 'cNPT', 'cBH'].forEach(i => $('#' + i).addEventListener('input', run)); $('#cType').addEventListener('change', run); run();
  },
  excel() { const { mrows, erows } = vThue.data(); XLSX.save(`Thue TNCN va BH nam ${UI.y}`, [xSheet('Theo tháng', 'TỔNG HỢP THUẾ TNCN & BẢO HIỂM NĂM ' + UI.y, vThue.mcols, mrows), xSheet('Theo nhân viên', 'QUYẾT TOÁN THUẾ TNCN THEO NHÂN VIÊN NĂM ' + UI.y, vThue.ecols, erows)]); },
  print() { const { mrows, erows } = vThue.data(); Print.run(`<div class="pr">${Print.head()}<h2>Tổng hợp thuế TNCN & bảo hiểm năm ${UI.y}</h2>${Print.table(vThue.mcols, mrows)}<h2 style="font-size:15px;margin-top:20px">Chi tiết theo nhân viên</h2>${Print.table(vThue.ecols, erows)}${Print.signs(['Người lập biểu', 'Kế toán trưởng', 'Giám đốc'])}</div>`, { landscape: true, title: 'Thue TNCN ' + UI.y }); }
};

/* ---------- Cài đặt ---------- */
const vSet = {
  title: 'Cài đặt', icon: 'settings', desc: 'Thông tin công ty, công chuẩn, tỷ lệ bảo hiểm, biểu thuế TNCN', print: false,
  render(el) {
    const s = S();
    el.innerHTML = `<div class="grid cols-2">
      ${settingsCard('Thông tin công ty', COMPANY_FIELDS, s.company)}
      <div class="grid">
        ${settingsCard('Chấm công & làm thêm', [{ key: 'congChuan', label: 'Công chuẩn / tháng', type: 'number' }, { key: 'gioCong', label: 'Số giờ / công', type: 'number' }, { key: 'heSoOT', label: 'Hệ số làm thêm giờ', type: 'number', hint: '1,5 ngày thường · 2 ngày nghỉ · 3 ngày lễ' }, { key: 'ngayNghi', label: 'Ngày nghỉ hằng tuần', type: 'select', options: [{ value: 'CN', label: 'Chủ nhật' }, { value: 'T7CN', label: 'Thứ 7 và Chủ nhật' }] }], s)}
        ${settingsCard('Phòng ban', [{ key: 'pbText', label: 'Mỗi dòng một phòng ban', type: 'textarea', rows: 6, full: true }], { pbText: s.phongBan.join('\n') }, 2)}
      </div>
      ${settingsCard('Bảo hiểm bắt buộc (%)', [{ key: 'luongCoSo', label: 'Lương cơ sở', type: 'money', hint: 'Trần BHXH, BHYT = 20 lần' }, { key: 'ltVung', label: 'Lương tối thiểu vùng', type: 'money', hint: 'Vùng I 5.310.000 (2026). Trần BHTN = 20 lần' },
        { type: 'section', label: 'Người lao động đóng' }, { key: 'bhxhNLD', label: 'BHXH', type: 'number' }, { key: 'bhytNLD', label: 'BHYT', type: 'number' }, { key: 'bhtnNLD', label: 'BHTN', type: 'number' },
        { type: 'section', label: 'Doanh nghiệp đóng' }, { key: 'bhxhDN', label: 'BHXH (gồm TNLĐ-BNN)', type: 'number' }, { key: 'bhytDN', label: 'BHYT', type: 'number' }, { key: 'bhtnDN', label: 'BHTN', type: 'number' }, { key: 'kpcdDN', label: 'Kinh phí công đoàn', type: 'number' }], s, 3)}
      <div id="taxBox">${card('Thuế TNCN (biểu lũy tiến từng phần)', `<div class="form">${fieldHTML({ key: 'gtBanThan', label: 'Giảm trừ bản thân / tháng', type: 'money' }, s.gtBanThan)}${fieldHTML({ key: 'gtNPT', label: 'Giảm trừ mỗi người phụ thuộc', type: 'money' }, s.gtNPT)}</div>
        <table class="tbl compact bac mt"><thead><tr><th>Bậc</th><th class="r">Thu nhập tính thuế đến (đồng/tháng)</th><th class="r">Thuế suất %</th></tr></thead><tbody>${s.bac.map((b, i) => `<tr><td>${i + 1}</td><td><input data-money data-bd="${i}" value="${b.den ? F.money(b.den) : ''}" placeholder="Không giới hạn"></td><td><input type="number" data-bt="${i}" value="${b.ts}"></td></tr>`).join('')}</tbody></table>
        <p class="small muted">Mặc định theo quy định áp dụng từ kỳ tính thuế 2026: giảm trừ 15,5 triệu, người phụ thuộc 6,2 triệu, biểu 5 bậc 5% · 10% · 20% · 30% · 35%. Khi luật thay đổi, sửa ở đây là bảng lương tự tính lại.</p>
        <div class="row"><button class="btn pri" data-act="taxSave">${ic('check')} Lưu cài đặt thuế</button><button class="btn" data-act="taxReset">${ic('refresh')} Về mặc định 2026</button></div>`)}</div>
    </div>`;
    setTimeout(() => { const b = $$('#view [data-sfsave]')[2]; if (b) b.onclick = () => { const v = b.closest('.card').querySelector('textarea').value.split('\n').map(x => x.trim()).filter(Boolean); if (!v.length) return toast('Cần ít nhất 1 phòng ban', 'err'); s.phongBan = [...new Set(v)]; App.changed(false); toast('Đã cập nhật phòng ban', 'ok'); }; }, 10);
  }
};
Object.assign(ACT, {
  taxSave: () => {
    const box = $('#taxBox'), s = S();
    s.gtBanThan = F.parse(box.querySelector('[name=gtBanThan]').value); s.gtNPT = F.parse(box.querySelector('[name=gtNPT]').value);
    s.bac = s.bac.map((b, i) => ({ den: F.parse(box.querySelector(`[data-bd="${i}"]`).value), ts: parseFloat(box.querySelector(`[data-bt="${i}"]`).value) || 0 }));
    App.changed(false); toast('Đã lưu cài đặt thuế', 'ok');
  },
  taxReset: () => { const d = defaultSettings(); Object.assign(S(), { gtBanThan: d.gtBanThan, gtNPT: d.gtNPT, bac: d.bac }); App.changed(); }
});

/* ---------- Khởi động ---------- */
App.init({
  id: 'luong', title: 'Chấm công & Tính lương', tagline: 'Thuế TNCN · BHXH · Phiếu lương', icon: 'wallet', fileName: 'Cham cong va Tinh luong.html', theme: THEME,
  palette: ['#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#64748b'],
  sample: sampleDB, empty: emptyDB, migrate,
  counts: () => [['Nhân viên', DB.employees.length], ['Tháng có chấm công', Object.keys(DB.cham).length]],
  nav: [{ group: 'Tổng quan', items: ['dashboard'] }, { group: 'Nghiệp vụ', items: ['nhanvien', 'chamcong', 'bangluong', 'thue'] }, { group: 'Thiết lập', items: ['caidat'] }],
  views: { dashboard: vDash, nhanvien: vEmp, chamcong: vCham, bangluong: vLuong, thue: vThue, caidat: vSet }
});

