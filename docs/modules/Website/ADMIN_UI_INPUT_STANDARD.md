# Website Admin UI Input Standard

## Purpose

This document defines the default UI contract for editable controls inside `Modules/Website` admin screens.

The goal is simple: an administrator must be able to identify immediately what is editable, where to click, what state a field is in, and where to save changes.

This standard was validated during Phase 10F Footer administration and should be reused for future Website admin work unless a screen has a documented reason to differ.

---

## Core Rule

Editable controls must never visually resemble static text.

Every `input`, `select`, and `textarea` should have all of the following:

```text
Visible border
White or intentionally contrasting background
Comfortable internal padding
Readable text color
Clear hover state
Clear keyboard/mouse focus state
Visible placeholder contrast
Consistent rounded corners
```

Do not rely only on the browser default form styling.

---

## Standard Text Input / Select / Textarea

Recommended Tailwind baseline:

```text
mt-1 block w-full
rounded-lg
border border-gray-300
bg-white
px-3 py-2.5
text-sm text-gray-900
shadow-sm
transition
placeholder:text-gray-400
hover:border-gray-400
focus:border-blue-500
focus:outline-none
focus:ring-2 focus:ring-blue-100
```

This is the preferred visual baseline for Website admin forms.

A local Blade variable may be used inside a view to avoid repeating the class string:

```php
@php
    $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
@endphp
```

If the same pattern becomes widely reused across modules, it may later be promoted to a shared Blade component or design-system primitive.

---

## Labels

Every editable field should have a visible label unless the surrounding interaction makes the purpose unambiguous.

Recommended baseline:

```text
block text-sm font-semibold text-gray-700
```

For dense repeatable rows, a smaller label is acceptable:

```text
block text-xs font-semibold text-gray-600
```

Do not use placeholder text as the only label for important settings.

---

## Textarea

Textarea fields must use the same visual treatment as normal inputs.

They should also have enough height to look editable. Recommended minimum:

```text
rows="3"
```

Use a shorter textarea only when the content is known to be very small.

---

## File Upload

File upload controls should look like an upload zone rather than plain text.

Recommended treatment:

```text
rounded-lg
border border-dashed border-gray-300
bg-gray-50
px-3 py-3
text-sm text-gray-600
hover:border-blue-400
```

Where possible, also show:

```text
Current asset
Fallback source
New-file preview
Allowed formats
Maximum size
Remove/reset action
```

---

## Checkboxes / Toggles

Checkboxes must remain visually distinct from text and should always be paired with a label.

Recommended checkbox baseline:

```text
h-4 w-4
rounded
border-gray-300
text-blue-600
focus:ring-blue-500
```

Labels should describe the resulting state clearly, for example:

```text
Bật
Mở tab mới
Hiển thị trên mobile
```

---

## Repeatable Collections

Dynamic collections such as Legal Links, Trust Badges, menu items, social links, or similar records should use a card/row treatment.

Each item should have:

```text
Visible container border
Slightly contrasting background
Per-field labels
Clear remove action
Stable spacing
Responsive grid
```

Recommended item container:

```text
rounded-xl border border-gray-200 bg-gray-50 p-4
```

Do not render a row of borderless fields that visually blends into the page.

---

## Empty State

A repeatable collection with no items must not appear as unexplained blank space.

Show an explicit empty state such as:

```text
Chưa có Legal Link. Nhấn + Thêm link để tạo.
```

Recommended presentation:

```text
rounded-lg
border border-dashed border-gray-300
bg-gray-50
px-4 py-5
text-center text-sm text-gray-500
```

---

## Section Cards

Large configuration groups should be visually separated into cards.

Recommended section baseline:

```text
rounded-xl border border-gray-200 bg-gray-50 p-5
```

A section should normally contain:

```text
Section title
Short explanatory description
Fields / repeatable collection
Relevant actions
```

Examples:

```text
Tải ứng dụng
Footer Bottom
Logo Brand Footer
```

---

## Primary Save Action

Long admin forms should keep the primary save action easy to find.

For long settings pages, prefer a sticky action bar:

```text
sticky bottom-4 z-10
rounded-xl border border-gray-200
bg-white/95 p-3
shadow-lg backdrop-blur
```

Primary button baseline:

```text
rounded-lg
bg-blue-600
px-6 py-2.5
font-bold text-white
shadow-sm
hover:bg-blue-700
disabled:opacity-50
```

The button label should name the scope when useful, for example:

```text
Lưu thông tin Footer
Lưu cấu hình Header
Lưu giao diện
```

Avoid ambiguous labels such as only `Save` on complex multi-section screens.

---

## Focus and Accessibility

All interactive controls must remain keyboard focusable.

Do not remove focus indication without replacing it with an equally visible focus state.

Important controls should provide an associated label, `aria-label`, or accessible text where appropriate.

Color must not be the only signal for destructive actions or state changes.

---

## Visual Hierarchy

The preferred hierarchy for Website admin forms is:

```text
Page / tab
    ↓
Section card
    ↓
Section title + description
    ↓
Field label
    ↓
Visible editable control
    ↓
Help / validation text
```

An administrator should be able to scan the screen without needing to click around to discover editable areas.

---

## Responsive Behavior

Forms should default to one column on narrow screens and expand where useful:

```text
grid grid-cols-1 md:grid-cols-2
```

or for compact related fields:

```text
grid grid-cols-1 md:grid-cols-3
```

Repeatable item controls must remain usable on mobile and should not depend on hover-only actions.

---

## Validation and Error States

Validation errors should be displayed next to the relevant control and use clear text.

Recommended baseline:

```text
mt-1 text-sm text-red-600
```

A field with an error may additionally receive a red border/ring, but the error message must still be present.

---

## Do / Do Not

### Do

```text
Use visible input boundaries
Use consistent focus rings
Use labels above controls
Group complex configuration into cards
Use empty states for empty collections
Keep destructive actions visually distinct
Keep the main Save action easy to reach
Reuse the same field treatment throughout one screen
```

### Do Not

```text
Do not make editable fields look like normal text
Do not use placeholder-only labels for important settings
Do not rely on invisible/default browser borders
Do not hide essential actions behind hover on mobile
Do not leave empty dynamic sections visually blank
Do not introduce a different input style for each admin feature
```

---

## Reference Implementation

Current approved reference:

```text
Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php
```

The Phase 10F Footer form is the initial reference implementation for this standard.

Future Website admin refactors should either follow this document or explicitly document why a different UI contract is required.
