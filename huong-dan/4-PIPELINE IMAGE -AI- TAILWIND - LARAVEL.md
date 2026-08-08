# 🧠 0. OVERVIEW

Input:

* Image (banner/UI)

Output:

* Blade component (Laravel)
* Tailwind CSS ready
* Optional: Figma JSON

Flow:

1. Image → AI phân tích
2. AI → JSON chuẩn hóa
3. JSON → Tailwind HTML
4. HTML → Blade component

---

# 📥 1. STEP 1 — IMAGE → AI ANALYSIS

## Prompt (bắt buộc dùng MASTER PROMPT v2)

"Phân tích bố cục ảnh này theo MASTER PROMPT v2, output thêm JSON schema cho dev"

⚠️ Yêu cầu thêm:

* "Không mô tả chung"
* "Có px cụ thể"
* "Tách layer đầy đủ"
* "Sinh thêm JSON chuẩn hóa"

---

# 🧾 2. STEP 2 — JSON SCHEMA (CORE)

## 🎯 Mục tiêu:

JSON là “single source of truth”

## ✅ Schema chuẩn:

```json
{
  "canvas": {
    "width": 1200,
    "height": 628
  },
  "tokens": {
    "colors": {
      "primary-500": "#FF5733",
      "bg": "#FFFFFF"
    },
    "spacing": {
      "4": "16px",
      "6": "24px"
    }
  },
  "layout": {
    "container": "max-w-[1200px] mx-auto",
    "grid": "flex justify-between items-center"
  },
  "layers": [
    {
      "name": "headline",
      "type": "text",
      "content": "Big Sale",
      "class": "text-4xl font-bold text-primary-500",
      "position": {
        "x": 80,
        "y": 120
      }
    },
    {
      "name": "cta",
      "type": "button",
      "content": "Buy Now",
      "class": "px-6 py-3 bg-primary-500 text-white rounded-lg"
    },
    {
      "name": "image",
      "type": "image",
      "src": "/img/product.png",
      "class": "w-[400px]"
    }
  ]
}
```

---

# ⚙️ 3. STEP 3 — JSON → TAILWIND HTML

## Rule:

* Không suy luận thêm
* Mapping 1:1 từ JSON

## Generator logic:

```js
function renderLayer(layer) {
  if (layer.type === "text") {
    return `<h1 class="${layer.class}">${layer.content}</h1>`;
  }

  if (layer.type === "button") {
    return `<button class="${layer.class}">${layer.content}</button>`;
  }

  if (layer.type === "image") {
    return `<img src="${layer.src}" class="${layer.class}" />`;
  }
}
```

---

# 🧩 4. STEP 4 — HTML → BLADE COMPONENT

## 📁 Structure:

```id="blade-structure"
resources/views/components/banner.blade.php
```

## Blade:

```html
<section class="bg-white py-12">
  <div class="max-w-[1200px] mx-auto flex items-center justify-between">

    <div class="space-y-4">
      <h1 class="text-4xl font-bold text-primary-500">
        {{ $headline }}
      </h1>

      <button class="px-6 py-3 bg-primary-500 text-white rounded-lg">
        {{ $cta }}
      </button>
    </div>

    <div>
      <img src="{{ $image }}" class="w-[400px]" />
    </div>

  </div>
</section>
```

---

# 🔁 5. DYNAMIC PROPS (LIVEWIRE READY)

```php
<x-banner 
  headline="Big Sale"
  cta="Buy Now"
  image="/img/product.png"
/>
```

---

# 🎨 6. OPTIONAL — TAILWIND CONFIG AUTO-GEN

```js
theme: {
  extend: {
    colors: {
      primary: {
        500: "#FF5733"
      }
    }
  }
}
```

---

# 🧠 7. AUTOMATION (QUAN TRỌNG)

## Bạn có thể build:

### Option A — Semi Auto

* ChatGPT → JSON
* Copy → Script → HTML

### Option B — Full Auto

* Upload image
* API gọi AI
* AI trả JSON
* Node.js convert → Blade

---

# 🧪 8. VALIDATION LAYER

⚠️ Bắt buộc thêm:

* Check missing layer
* Check invalid Tailwind class
* Check overlap UI

---

# ⚠️ 9. NHỮNG THỨ SẼ SAI NẾU KHÔNG FIX

* AI đo sai spacing
* Font không match 100%
* Gradient lệch nhẹ
* Z-index sai

👉 Cách fix:

* Cho phép override manual JSON

---

# 🚀 10. NÂNG CẤP SAU

* Export Figma JSON
* Drag-drop builder
* AI fine-tune riêng UI

---

# 🔥 FINAL GOAL

👉 1 image → 1 JSON → 1 Blade component
👉 Dev KHÔNG cần đo lại UI

---
