# Data Model And API Notes

## 1. Core Entities

### Product

Fields can co:

- `id`
- `sku`
- `slug`
- `name`
- `short_name`
- `image_url`
- `gallery`
- `brand_id`
- `manufacturer_id`
- `category_ids`
- `dosage_form`
- `packing`
- `registration_number`
- `is_otc`
- `is_active`
- `price`
- `price_visibility`
- `stock_status`
- `created_at`
- `updated_at`

### ProductShelf

Dung cho cac section nhu "San pham moi", "San pham Duoc Hau Giang", "San pham Blackmores", "San pham Santen".

- `id`
- `title`
- `slug`
- `type`: `manual`, `newest`, `brand`, `best_seller`, `promotion`
- `position`
- `is_active`
- `view_more_url`
- `banner_id`
- `product_ids`

### Banner

- `id`
- `placement`: `home_hero_main`, `home_hero_side`, `product_shelf_strip`, `popup`
- `title`
- `image_desktop_url`
- `image_mobile_url`
- `target_url`
- `start_at`
- `end_at`
- `position`
- `is_active`

### Benefit

- `id`
- `title`
- `description`
- `icon`
- `position`
- `is_active`

### PartnerLogo

- `id`
- `name`
- `logo_url`
- `target_url`
- `position`
- `is_active`

### BusinessProfile

Dung de khoa/mo gia san pham.

- `user_id`
- `pharmacy_name`
- `representative_name`
- `tax_code`
- `license_number`
- `status`: `missing`, `pending`, `verified`, `rejected`
- `verified_at`

## 2. Home API Shape Goi Y

Endpoint:

```http
GET /api/pharma/home
```

Response:

```json
{
  "header": {
    "wishlist_count": 0,
    "notification_count": 1,
    "cart_count": 0,
    "business_profile_status": "verified"
  },
  "hero": {
    "main_banners": [],
    "side_banners": []
  },
  "benefits": [],
  "shelves": [
    {
      "title": "San pham moi",
      "slug": "new-products",
      "view_more_url": "/products?sort=newest",
      "banner": null,
      "products": []
    }
  ],
  "partners": [],
  "footer": {}
}
```

## 3. Price Visibility Rules

| User state | Product card |
|---|---|
| Guest | Dang nhap/dang ky de xem gia |
| Logged in, missing profile | Xac minh ho so KD de xem gia |
| Pending verification | Ho so dang duoc xet duyet |
| Rejected | Cap nhat lai ho so KD |
| Verified | Hien gia, ton kho, nut them gio hang |

## 4. Search

Search can ho tro:

- Ten san pham.
- Hoat chat/nhom hang neu co.
- Hang san xuat/thuong hieu.
- SKU/ma san pham.

Can co debounce tren UI va index database/search engine phu hop khi du lieu lon.

## 5. Tracking Events

Nen track:

- `home_banner_click`
- `home_product_click`
- `home_shelf_view_more`
- `search_submit`
- `locked_price_cta_click`
- `wishlist_toggle`
- `cart_open`
- `app_download_click`

## 6. External Signals Seen In Source

- Firebase Cloud Messaging: can co state notification token.
- Google Analytics/GTM and Facebook Pixel: can mapping event neu rebuild.
- App download links: Google Play and App Store.
- API tra cuu: `https://tracuu.Pharmalink.com/api/tracuu`, can xac minh muc dich truoc khi tich hop.
