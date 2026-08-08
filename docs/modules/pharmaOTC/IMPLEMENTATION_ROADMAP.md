# Implementation Roadmap

## Phase 0 - Discovery

- Xac minh API/backoffice hien co cho product, category, cart, user, business profile.
- Xac minh co dung `Modules/Pharma`, `Modules/Product`, `Modules/WebsiteV2` hay tao module storefront moi.
- Lay danh sach banner, shelf, partner, footer tu CMS/DB neu da co.
- Chot business rules cho price visibility.

## Phase 1 - Static Rebuild

- Tao home route va layout shell.
- Dung du lieu mock/config file de render:
  - Header.
  - Hero.
  - Benefit cards.
  - Product shelves.
  - Partner shelf.
  - Footer.
- Hoan thien responsive desktop/tablet/mobile.

## Phase 2 - Dynamic Data

- Noi API/home service.
- Noi product shelf voi database.
- Noi banner/partner/footer config.
- Noi state dang nhap, profile verification, cart/wishlist/notification count.
- Them skeleton/loading/error/empty state.

## Phase 3 - Commerce Flow

- Search.
- Product detail.
- Wishlist.
- Quick order.
- Cart.
- Add-to-cart guard theo business profile.
- CTA xac minh ho so KD.

## Phase 4 - CMS And Operations

- Admin quan ly banner placement.
- Admin quan ly product shelf.
- Admin quan ly partner logo/footer links.
- Cache invalidation khi update CMS.
- Audit log cho thay doi campaign/home config.

## Phase 5 - Analytics And Polish

- Mapping GTM/Facebook Pixel/Firebase events.
- Lighthouse/performance pass.
- Accessibility pass.
- Cross-browser QA.
- Production smoke test.

## Suggested Build Order

1. `ProductCard`
2. `ProductShelf`
3. `HomeHero`
4. `OtcHeader`
5. `OtcFooter`
6. `HomePage` composition
7. Dynamic service/API
8. Tracking and QA
