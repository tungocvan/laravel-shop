# Component Tree

## Page Shell

```text
PharmaOtcHomePage
+-- OtcHeader
|   +-- BrandLogo
|   +-- ProductSearchBox
|   +-- HeaderActionIcon(wishlist)
|   +-- HeaderActionIcon(notification)
|   +-- HeaderActionIcon(cart)
|   +-- AccountDropdown
+-- OtcNavigation
|   +-- NavItem(home)
|   +-- ProductCategoryMenu
|   +-- NavItem(quick-order)
|   +-- NavItem(promotions)
|   +-- NavItem(best-sellers)
+-- HomeHero
|   +-- MainBannerCarousel
|   +-- SideBannerStack
+-- TrustBenefits
|   +-- BenefitCard[]
+-- ProductShelf(new-products)
|   +-- ProductCarousel
+-- ProductShelf(dhg)
|   +-- PromoStripBanner
|   +-- ProductCarousel
+-- ProductShelf(blackmores)
|   +-- PromoStripBanner
|   +-- ProductCarousel
+-- ProductShelf(santen)
|   +-- ProductCarousel
+-- PartnerLogoShelf
+-- OtcFooter
    +-- LegalCompanyColumn
    +-- AboutColumn
    +-- PolicyColumn
    +-- SupportColumn
```

## Shared Components

### ProductCard

Props:

- `id`
- `slug`
- `name`
- `image_url`
- `brand_name`
- `manufacturer_name`
- `price_visibility_state`
- `is_wishlisted`
- `badges`

States:

- Guest: hien "Dang nhap de xem gia" neu chua dang nhap.
- Logged in, chua xac minh KD: hien "Xac minh ho so KD de xem gia".
- Verified: hien gia, ton kho, nut them gio hang.
- Out of stock: vo hieu nut mua, hien trang thai het hang.

### ProductShelf

Props:

- `title`
- `slug`
- `products`
- `banner`
- `view_more_url`
- `tracking_key`

Behavior:

- Desktop: carousel 6 card.
- Tablet: 3-4 card.
- Mobile: 2 cot hoac horizontal scroll.
- Lazy-load anh.
- Khong lam thay doi chieu cao section khi anh load cham.

### BannerCarousel

Props:

- `items[]`
- `autoplay`
- `interval`
- `aspect_ratio`

Behavior:

- Click banner dieu huong theo `target_url`.
- Co pagination dots.
- Co fallback khi khong co banner.

### OtcHeader

State:

- `current_user`
- `business_profile_status`
- `wishlist_count`
- `notification_count`
- `cart_count`

Behavior:

- Search submit den trang ket qua.
- Account dropdown hien so dien thoai khi da dang nhap.
- Header nen sticky tren desktop va mobile.

## Laravel/Livewire Mapping Goi Y

- Blade page: `resources/views/pages/pharma/home.blade.php`
- Livewire:
  - `Pharma\\HomePage`
  - `Pharma\\ProductShelf`
  - `Pharma\\SearchBox`
  - `Pharma\\CartBadge`
- Blade components:
  - `x-pharma.header`
  - `x-pharma.nav`
  - `x-pharma.banner-carousel`
  - `x-pharma.product-card`
  - `x-pharma.product-shelf`
  - `x-pharma.footer`

Neu storefront duoc tach thanh SPA, component tree tren van giu nguyen, chi doi renderer.
