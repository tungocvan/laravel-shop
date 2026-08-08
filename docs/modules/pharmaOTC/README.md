# Pharma OTC Website Docs

Ngay phan tich: 2026-06-30

Nguon tham chieu:

- Website: https://otc.pharmalink.vn/
- Anh chup: `C:\Users\Administrator\Downloads\home.png`
- Crawl text: `C:\Users\Administrator\Downloads\home.md`

Ghi chu: website goc la Angular SPA, HTML tra ve chu yeu la app shell va bundle JS. Noi dung home duoc doi chieu tu anh chup va cac dau vet trong bundle, vi vay nhung API/dynamic config can duoc xac minh lai khi bat dau trien khai.

## File Index

- `ANALYSIS.md`: phan tich man hinh home, luong nguoi dung, diem manh/yeu.
- `COMPONENT_TREE.md`: cau truc component de rebuild trong Laravel/Blade/Livewire hoac storefront moi.
- `DATA_MODEL.md`: du lieu, entity, API va config can co.
- `REBUILD_SPEC.md`: dac ta giao dien va hanh vi can tai tao.
- `IMPLEMENTATION_ROADMAP.md`: lo trinh thuc hien theo giai doan.
- `CHECKLIST.md`: checklist nghiem thu UI, responsive, du lieu, SEO, tracking.

## Scope

Tai lieu nay tap trung vao trang chu B2B OTC Pharmalink, gom:

- Header, search, shortcut icon, account/cart state.
- Navigation: Trang chu, San pham, Dat nhanh, Khuyen mai dac biet, San pham ban chay.
- Hero/banner area.
- Khoi ly do chon Pharmalink OTC.
- Cac carousel san pham.
- Banner khuyen mai trong section.
- Doi tac.
- Footer va app download.

Out of scope:

- Admin Pharma module hien co trong `Modules/Pharma`.
- Logic nghiep vu day du cua order/payment/ERP.
- Du lieu gia ban that, vi website yeu cau xac minh ho so kinh doanh de xem gia.
