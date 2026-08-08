# Pharma OTC Home Rebuild Spec

## 1. Design Direction

Giao dien can giu cam giac B2B pharmacy marketplace: ro rang, sach, nhieu san pham, it trang tri thua. Mau chu dao la xanh thuong hieu, nen trang/xam nhat, diem nhan vang/do cho khuyen mai.

Khong lam landing page marketing rieng; man hinh dau tien phai la storefront that voi search, banner, san pham va gio hang.

## 2. Layout

### Desktop

- Max content width khoang 1320px.
- Header full width, sticky.
- Hero grid:
  - Main banner: 2/3 width.
  - Side banners: 1/3 width, 2 item xep doc.
- Benefit cards: 5 cot.
- Product shelf: carousel 6 card.
- Partner shelf: 6 logo card.
- Footer: 4 cot.

### Tablet

- Hero: main banner full row, side banners 2 cot ben duoi.
- Benefit cards: 2-3 cot.
- Product shelf: 3-4 card visible.
- Footer: 2 cot.

### Mobile

- Header:
  - Logo + action icons hang tren.
  - Search full width hang duoi.
  - Nav co horizontal scroll.
- Hero: banner full width, side banners thanh carousel hoac xep doc.
- Benefit cards: 1-2 cot.
- Product card: grid 2 cot hoac horizontal shelf.
- Footer: accordion hoac stacked columns.

## 3. Header Requirements

- Logo click ve home.
- Search bar co placeholder ro rang.
- Icon action co badge:
  - Yeu thich.
  - Thong bao.
  - Gio hang.
- Account pill:
  - Guest: Dang nhap/Dang ky.
  - Logged in: so dien thoai + dropdown.
- Navigation active state mau xanh.
- Dropdown San Pham can co category list.

## 4. Banner Requirements

- Anh banner phai co aspect ratio on dinh.
- Support desktop/mobile image.
- Co alt text.
- Click tracking.
- Fallback neutral khi khong co banner.
- Pagination dots duoi main banner.

## 5. Product Card Requirements

- Card radius nho, border nhat, shadow rat nhe.
- Anh san pham can fit contain, khong crop hop thuoc/chai lo.
- Ten san pham clamp 2-3 dong.
- Wishlist icon o goc tren phai.
- Locked price notice mau do nhat, text:
  - "Xac minh ho so KD de xem gia"
- Khi verified:
  - Gia noi bat.
  - Don vi tinh/quy cach.
  - Nut them gio hang.
- Card click den detail, wishlist click khong trigger card navigation.

## 6. Product Shelf Requirements

- Title co icon trang tri mau xanh.
- Nut "Xem them" o phai.
- Arrow carousel trai/phai.
- Support optional promo strip banner truoc danh sach san pham.
- Product data fetch theo shelf config, khong hard-code.

## 7. Footer Requirements

- Giu day du nhom link:
  - Pharmalink/legal.
  - Ve Pharmalink.
  - Dieu khoan & Chinh sach.
  - Ho tro khach hang.
- Co Google Play/App Store badges.
- Co version text neu can.

## 8. Accessibility

- Tat ca button/icon co accessible label.
- Banner image co alt text theo campaign.
- Carousel co keyboard navigation.
- Text mau do tren nen do nhat phai dat contrast.
- Focus state ro cho search, nav, product card, action icons.

## 9. Performance

- Lazy load anh san pham va partner logo.
- Preload logo/header critical assets.
- Use responsive images cho banner.
- Skeleton cho product shelf.
- Cache home config trong 5-15 phut, invalidate khi CMS update.

## 10. Security And Business Rules

- Khong render price trong HTML/API response neu user khong duoc phep xem gia.
- Cart/add-to-cart phai check server-side business profile status.
- Wishlist/cart count phai lay theo user dang nhap.
- Anh/banner URL can duoc validate neu cho admin nhap.
- Tracking khong duoc gui thong tin nhay cam cua nha thuoc.
