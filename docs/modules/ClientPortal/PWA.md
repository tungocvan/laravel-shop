# ClientPortal — PWA Installer & Client Login

Updated: 2026-08-20  
Implementation branch: `agent/pwa-installer-client-login`

## 1. Purpose

ClientPortal provides a dedicated PWA entry experience for authenticated Client users. The PWA experience is intentionally separate from the Admin login flow while reusing the existing `web` guard authentication logic from `Modules/Auth`.

The user-facing flow is:

```text
Public Website
    -> footer "Cài ứng dụng"
    -> device/browser-specific PWA installer
    -> PWA starts at /my-apps
    -> guest is redirected to /my-apps/login
    -> authenticate with guard web
    -> redirect to /my-apps
    -> ApplicationRegistry exposes only permitted applications
```

The implementation does **not** create a second authentication system. It reuses `Modules\Auth\Livewire\Auth\LoginForm` with a PWA-specific visual variant.

---

## 2. Routes

### PWA login

```text
GET /my-apps/login
name: client.apps.login
middleware: web, guest:web
```

Controller:

```text
Modules\ClientPortal\Http\Controllers\PortalController@login
```

If the web user is already authenticated, the route redirects to:

```text
/my-apps
```

### Application launcher

```text
GET /my-apps
name: client.apps.index
middleware: web, auth:web
```

### Authentication exception redirect

For ClientPortal/PWA routes, unauthenticated access is redirected to:

```text
/my-apps/login
```

The generic site login remains:

```text
/login
```

Admin authentication remains completely separate:

```text
/admin/login
```

with guard `admin`.

---

## 3. PWA Login UI

Views:

```text
Modules/ClientPortal/resources/views/pages/login.blade.php
Modules/Auth/resources/views/livewire/auth/login-form-pwa.blade.php
```

The PWA login is mobile-first and designed to look like a native application entry screen rather than the generic web/admin authentication page.

Current UI includes:

- Client Portal branding;
- configurable site logo from the existing Settings service;
- Email input with mobile-friendly sizing;
- Password input;
- show/hide password action;
- "Giữ tôi đăng nhập trên thiết bị này";
- loading state while Livewire authenticates;
- clear validation errors;
- desktop presentation panel while preserving compact mobile layout;
- HTTPS/security guidance;
- PWA manifest/service-worker metadata.

Authentication logic stays inside:

```text
Modules\Auth\Livewire\Auth\LoginForm
```

The component accepts a visual variant so the existing `/login` and `/admin/login` pages are not forced to use the PWA design.

Successful `web` login redirects to:

```text
client.apps.index
```

which resolves to `/my-apps`.

---

## 4. Client Google authentication

MR-7 added a dedicated Client Google OAuth flow for the `web` guard. It is separate from the Admin Google callback and returns authenticated users to ClientPortal.

The Client flow:

- requires a Google-verified email;
- rejects provider/email ownership conflicts;
- limits automatic same-email linking to eligible MR-7 OTP-verified accounts;
- requires an authenticated explicit linking flow for other existing accounts;
- does not persist Google access or refresh tokens for PWA authentication.

MR-8 surfaces the existing explicit linking action from ClientPortal account settings; it does not duplicate or weaken Auth-owned identity rules.

---

## 5. Website PWA Installer

The public Website footer no longer needs App Store/Google Play badges for the PWA use case. It exposes a direct "Cài ứng dụng" experience.

Main partial:

```text
Modules/Website/resources/views/partials/pwa-installer.blade.php
```

Integrated from:

```text
Modules/Website/resources/views/partials/footer.blade.php
```

The Website frontend layout exposes the PWA metadata and registers the service worker:

```text
Modules/Website/resources/views/layouts/frontend.blade.php
```

PWA resources:

```text
/manifest.webmanifest
/service-worker.js
/pwa/*
```

---

## 6. Device/browser behavior

### Android / Chromium browsers

When the browser fires:

```javascript
beforeinstallprompt
```

the installer stores the deferred prompt. When the user presses the install CTA, the native browser PWA installation prompt is shown.

Expected user experience:

```text
Website footer
    -> Cài ứng dụng
    -> native Install prompt
    -> Add/Install
    -> application icon appears on home screen/app launcher
```

### iPhone / iPad Safari

iOS Safari does not expose the same programmatic install prompt used by Chromium. The installer therefore opens an instructional bottom sheet.

The guide tells the user to:

```text
1. Nhấn Chia sẻ
2. Chọn "Thêm vào Màn hình chính"
3. Nhấn "Thêm"
```

This is the expected and supported PWA installation mechanism on iOS.

### iPhone/iPad non-Safari browser

When an iOS user is in a browser/context that cannot complete the Home Screen installation flow directly, the UI instructs the user to open the site in Safari.

This includes typical cases such as Chrome/Edge or in-app browser contexts.

### Already running as installed PWA

Detection uses standalone state, including:

```javascript
window.matchMedia('(display-mode: standalone)').matches
```

and the iOS standalone indicator when available.

When the current page is running as an installed PWA, the installer CTA changes to an installed state instead of asking the user to install again.

---

## 7. Important iOS limitation

A normal web page cannot reliably query iOS and ask:

```text
"Has this PWA already been installed somewhere on this device?"
```

The application can reliably detect that **the current browsing context** is running in standalone/PWA mode. It cannot reliably detect an installation that exists elsewhere while the user is currently visiting through a normal Safari tab.

Do not implement fake persistent state such as `localStorage.pwa_installed = true` as proof of installation. A user may delete the PWA while that browser storage remains, or browser storage may be cleared while the PWA still exists.

---

## 8. Service Worker safety contract

Authenticated Client responses must not be cached as reusable navigation responses.

Current architecture keeps authenticated navigation network-first and avoids putting Client-specific HTML responses into Cache Storage.

This rule must be preserved because ClientPortal content depends on:

- authentication state;
- `client.*` permissions;
- application availability;
- user-owned data.

A future offline mode must use an explicit, security-reviewed data model rather than blindly caching authenticated pages.

---

## 9. Manifest/start URL behavior

The PWA manifest starts at:

```text
/my-apps
```

This is intentional.

If the user is authenticated:

```text
/my-apps -> launcher
```

If the session has expired:

```text
/my-apps -> AuthenticationException -> /my-apps/login
```

After successful login:

```text
/my-apps/login -> /my-apps
```

This keeps a single stable PWA entry point while still providing a dedicated guest login experience.

---

## 10. Logout behavior

Client logout must remain on guard:

```text
web
```

For a PWA-oriented session, the preferred post-logout destination is:

```text
/my-apps/login
```

Do not redirect Client PWA users to `/admin/login`.

Admin logout and Client logout are separate flows.

### Header account menu

MR-8 replaces duplicated Header logout actions with one shared ClientPortal account menu on both the `/my-apps` launcher and every application shell.

The menu provides:

- a safe current-user identity summary;
- read-only account information;
- Client account settings backed by existing Auth capabilities;
- the canonical CSRF-protected `POST /logout` action.

The Header presentation remains ClientPortal-owned. Logout, session invalidation and Google linking remain Auth-owned.

---

## 11. Ownership boundaries

### Website owns

- public footer placement;
- public "Cài ứng dụng" CTA;
- browser/device installer presentation;
- Website-level manifest/service-worker registration.

### ClientPortal owns

- `/my-apps` launcher;
- `/my-apps/login` PWA entry/login presentation;
- Client application shell;
- Client route authentication behavior;
- application/feature permission experience.

### Auth owns

- credentials authentication;
- guard handling;
- reusable LoginForm logic;
- login session regeneration/security.

This separation prevents PWA-specific UI from duplicating authentication business logic.

---

## 12. Files involved

Current implementation touches:

```text
Modules/Auth/Livewire/Auth/LoginForm.php
Modules/Auth/resources/views/livewire/auth/login-form-pwa.blade.php
Modules/ClientPortal/Http/Controllers/PortalController.php
Modules/ClientPortal/resources/views/pages/login.blade.php
Modules/ClientPortal/routes/web.php
Modules/Website/resources/views/layouts/frontend.blade.php
Modules/Website/resources/views/partials/footer.blade.php
Modules/Website/resources/views/partials/pwa-installer.blade.php
bootstrap/app.php
```

Targeted regression coverage:

```text
tests/Feature/ClientApps/ClientApplicationRegistryTest.php
tests/Feature/ClientApps/ClientPwaFoundationTest.php
```

---

## 13. Manual verification checklist

Before releasing changes to the PWA entry experience, verify:

- normal Website still renders correctly on desktop;
- footer shows the PWA install CTA;
- Android Chromium native install prompt works where supported;
- iPhone Safari shows the correct Home Screen guide;
- iPhone non-Safari gets an "open in Safari" guide;
- installed standalone PWA shows installed state;
- opening the PWA as a guest redirects to `/my-apps/login`;
- PWA login works with a valid web account;
- invalid credentials display validation/error feedback;
- successful login redirects to `/my-apps`;
- `/apps/*` guest requests redirect to `/my-apps/login`;
- authenticated Client permissions continue to work;
- Admin login remains unchanged;
- service worker does not cache authenticated navigation responses.

Targeted tests:

```bash
php artisan test \
  tests/Feature/ClientApps/ClientApplicationRegistryTest.php \
  tests/Feature/ClientApps/ClientPwaFoundationTest.php
```

Then run:

```bash
php artisan test tests/Feature/ClientApps
```

before broad/full regression.

---

## 14. Future improvements

Possible future phases, not required for the current implementation:

1. Dedicated Google OAuth for Client `web` guard.
2. Forgot-password/recovery flow optimized for PWA.
3. Push notifications after explicit notification permission UX.
4. Install analytics/events without storing sensitive Client data.
5. Optional PWA update-available banner when a new service worker is waiting.
6. Offline-safe static launcher shell without caching private application data.
7. Organization-specific PWA branding if multi-tenant requirements emerge.

Any future authentication work should preserve the current rule: **PWA changes presentation and routing, not the canonical authentication/security implementation.**
