# Pharma OTC Website Analysis

Ngay phan tich: 2026-06-30

## 1. Muc Tieu Website

Pharmalink OTC la storefront B2B cho nha thuoc/quay thuoc. Trang chu khong day gia cong khai; thay vao do, san pham hien thong diep "Xac minh ho so KD de xem gia". Dieu nay cho thay conversion chinh khong phai mua le truc tiep, ma la:

- Thu hut nha thuoc dang nhap/dang ky.
- Day nguoi dung xac minh ho so kinh doanh.
- Gioi thieu danh muc san pham, khuyen mai, doi tac.
- Tao duong vao nhanh cho dat hang va gio hang.

## 2. Thong Tin Xac Nhan Tu Website

- Website goc: `https://otc.pharmalink.vn/`.
- HTML la Angular SPA shell, co bundle `main-AYM2TGLT.js`, `styles-YEMVGMTX.css`.
- Co Google Analytics/Tag Manager va Facebook Pixel.
- Co Firebase/FCM cho push notification.
- Co app mobile:
  - Google Play: `com.gtt`.
  - Apple App Store: `id6648759606`.
- Co API ngoai duoc nhin thay trong bundle: `https://tracuu.Pharmalink.com/api/tracuu`.
- Co endpoint feature flag: `integration/CheckAvailableFeature/wheel`.

## 3. Cau Truc Trang Home

### Header

Header co hai lop:

- Thanh tren mau xanh dam:
  - Logo Pharmalink.
  - Search bar dai o trung tam.
  - Icon yeu thich/thong bao/gio hang, co badge.
  - Account pill hien so dien thoai va dropdown.
- Thanh navigation ben duoi:
  - Trang Chu.
  - San Pham co dropdown.
  - Dat Nhanh.
  - Khuyen Mai Dac Biet.
  - San Pham Ban Chay.

### Hero

Hero gom mot carousel banner lon ben trai va hai banner nho ben phai. Layout desktop dang 2 cot:

- Cot trai chiem khoang 2/3 chieu rong, banner "TOP cac san pham HOT".
- Cot phai xep doc 2 banner: "San ngay qua hot" va "Mua san pham tang ngay".
- Duoi banner lon co pagination dots.

### Ly Do Chon Pharmalink OTC

Section co 5 benefit card:

- Hang chinh hang: 100% hoa don VAT.
- Gia tot nhat thi truong: Tot hon moi gia tot.
- Bao hanh bat chap: Doi tra khong can ly do.
- Chiet khau vo dich: Cao hon moi chiet khau.
- Dich vu vuot troi: Nhanh chong - An toan - Hieu qua.

### Product Sections

Trang home lap lai pattern section:

- Tieu de co icon trang tri mau xanh.
- Nut "Xem them".
- Carousel san pham 6 item tren desktop.
- Nut dieu huong trai/phai.
- Product card gom:
  - Anh san pham.
  - Icon tim/wishlist.
  - Ten san pham.
  - CTA/notice mau do nhat: "Xac minh ho so KD de xem gia".

Section nhin thay trong anh:

- San pham moi.
- San pham Duoc Hau Giang.
- San pham Blackmores.
- San pham Santen.

Mot so section co banner khuyen mai ngang mau do: "San Pham DHG - Mua 500K, Tang 50K".

### Partner

Section doi tac cua Pharmalink OTC hien logo trong cac card ngang, gom cac nha thuoc/doi tac. Section nay giup tang niem tin hon la conversion truc tiep.

### Footer

Footer co 4 cot:

- Pharmalink: thong tin phap ly, chung nhan.
- Ve Pharmalink: Gioi thieu, Tin tuc, Chinh sach khach hang than thiet, Ket noi voi chung toi, tai app.
- Dieu khoan & Chinh sach: dieu khoan, bao mat, van chuyen, giai quyet khieu nai, kiem hang va doi tra.
- Ho tro khach hang: Huong dan dat hang, Cau hoi thuong gap, Lien he.

## 4. UX Pattern Chinh

- Trang uu tien scanning nhanh: banner lon, benefit cards, product rows.
- Gia bi khoa sau xac minh KD, nen card san pham can lam ro trang thai "can xac minh" va duong dan dang nhap/xac minh.
- Product carousel la don vi noi dung chinh; can toi uu lazy load anh va skeleton.
- Header sticky/gan sticky la quan trong vi search va gio hang la luong chinh.

## 5. Diem Manh

- Mau xanh duoc su dung nhat quan voi nganh duoc va thuong hieu.
- Home co nhieu proof points: VAT, gia, bao hanh, chiet khau, dich vu.
- Product card gon, de scan, phu hop B2B.
- Footer day du link chinh sach va tai app.

## 6. Rui Ro Khi Rebuild

- Can tranh public gia san pham neu user chua duoc xac minh.
- Banner va product carousel phai co CMS/config, khong nen hard-code.
- Ten san pham duoc pham dai, can clamp 2-3 dong de khong vo card.
- Desktop layout 6 card/hang; mobile can chuyen sang horizontal scroll hoac grid 2 cot.
- Cac badge header phai dong bo gio hang/wishlist/thong bao theo state dang nhap.

## 7. De Xuat Uu Tien

### P0

- Header, search, product card, locked-price state.
- Home sections config-driven.
- Responsive mobile cho banner va product rows.
- Auth/verification guard cho gia va dat hang.

### P1

- Wishlist, cart badge, notification badge.
- Quick order entry.
- Partner/footer CMS.
- Tracking events cho search, click banner, click product, add cart.

### P2

- Mini game/wheel feature flag.
- Personalized sections theo nha thuoc/lich su mua.
- A/B testing banner.
