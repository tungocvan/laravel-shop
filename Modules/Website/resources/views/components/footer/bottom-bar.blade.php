<div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-6" style="border-top: 1px solid var(--footer-border);">
    <div class="text-xs text-center md:text-left" style="color: var(--footer-muted);">
        <p>{{ $footerSettings['copyright'] ?? '© 2024 FlexBiz. All rights reserved.' }}</p>
        <div class="flex gap-4 mt-2 justify-center md:justify-start">
            <a href="#" class="transition-colors hover:underline" style="color: var(--footer-foreground);">Privacy Policy</a>
            <a href="#" class="transition-colors hover:underline" style="color: var(--footer-foreground);">Terms of Service</a>
            <a href="#" class="transition-colors hover:underline" style="color: var(--footer-foreground);">Cookie Settings</a>
        </div>
    </div>

    <div class="flex items-center gap-4 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
        <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
        <img src="https://webmedia.com.vn/images/2021/09/logo-da-thong-bao-bo-cong-thuong-mau-xanh.png" alt="Đã thông báo Bộ Công Thương" loading="lazy" class="h-10 w-auto">
    </div>
</div>
