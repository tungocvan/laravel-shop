{{--
    FOOTER COMPONENT
    Data Injected by: Modules/Website/Providers/WebsiteServiceProvider.php (View::composer)
    Variables: $footerSettings, $footerColumns, $socialLinks
--}}

<footer class="bg-gray-900 text-gray-400 border-t border-gray-800 font-sans relative">

    {{-- Decorative Gradient Top --}}
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

    <div class="container mx-auto px-4 pt-16 pb-8">

        {{-- MAIN FOOTER GRID --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 mb-16">

            {{-- ================================================= --}}
            {{-- COL 1: BRAND INFO (Dynamic from Settings)         --}}
            {{-- ================================================= --}}
            <div class="lg:col-span-4 space-y-6">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2 group">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-900 font-black text-2xl group-hover:bg-blue-500 group-hover:text-white transition-colors">
                        F
                    </div>
                    <span class="text-2xl font-bold text-white tracking-tight">
                        FlexBiz<span class="text-blue-500">.</span>
                    </span>
                </a>

                {{-- Description --}}
                <p class="text-sm leading-relaxed text-gray-500">
                    {{ $footerSettings['description'] ?? 'Mô tả thương hiệu chưa được cập nhật.' }}
                </p>

                <div class="space-y-3">
                    {{-- Address --}}
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-sm">{{ $footerSettings['address'] ?? 'Địa chỉ chưa cập nhật' }}</span>
                    </div>
                    {{-- Email --}}
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="text-sm">{{ $footerSettings['email'] ?? 'email@domain.com' }}</span>
                    </div>
                    {{-- Hotline --}}
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="text-sm font-bold text-white">{{ $footerSettings['phone'] ?? '1900 xxxx' }}</span>
                    </div>
                </div>
            </div>

            {{-- ================================================= --}}
            {{-- DYNAMIC COLUMNS (Rendered from DB: footer_columns) --}}
            {{-- ================================================= --}}
            @if(isset($footerColumns) && $footerColumns->isNotEmpty())
                @foreach($footerColumns as $column)
                    <div class="lg:col-span-2">
                        <h3 class="text-white font-bold text-lg mb-6">{{ $column->title }}</h3>
                        <ul class="space-y-4 text-sm">
                            @foreach($column->links as $link)
                                <li>
                                    <a href="{{ $link->url }}"
                                       target="{{ $link->new_tab ? '_blank' : '_self' }}"
                                       class="hover:text-blue-500 transition-colors flex items-center gap-2">
                                        {{ $link->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif

            {{-- ================================================= --}}
            {{-- COL LAST: APP DOWNLOAD & SOCIAL LINKS             --}}
            {{-- ================================================= --}}
            <div class="lg:col-span-3">
                <h3 class="text-white font-bold text-lg mb-6">Tải Ứng Dụng</h3>
                <p class="text-xs text-gray-500 mb-4">Mua sắm dễ dàng hơn với App FlexBiz</p>

                <div class="flex flex-col gap-3 mb-8">
                    {{-- App Store --}}
                    @if(!empty($footerSettings['appstore']))
                        <a href="{{ $footerSettings['appstore'] }}" target="_blank" class="flex items-center gap-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg p-2 transition-all group">
                            <svg class="w-8 h-8 text-white group-hover:text-blue-400 transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M16.365 1.43c0 1.14-.493 2.27-1.177 3.08-.978 1.13-2.338 1.545-2.88 1.545-.095 0-.187 0-.252-.012-.138-1.525.88-2.906 1.885-3.666.903-.68 2.018-1.07 2.424-1.07.03 0 .03 1.123 0 1.123zm-4.39 5.753c-1.385 0-2.48.917-3.13.917-.635 0-1.63-.873-2.707-.873-2.14 0-4.14 1.73-4.14 4.195 0 2.29 1.95 5.76 3.99 5.76.712 0 1.15-.46 2.08-.46.907 0 1.34.46 2.08.46 1.575 0 3.33-2.22 3.915-3.32-.085-.05-2.29-1.34-2.29-4.005 0-2.44 1.95-3.51 2.05-3.565-.96-1.425-2.44-1.585-2.95-1.61z"/></svg>
                            <div class="flex flex-col">
                                <span class="text-[10px] leading-none uppercase text-gray-400">Download on the</span>
                                <span class="text-sm font-bold text-white leading-tight">App Store</span>
                            </div>
                        </a>
                    @endif

                    {{-- Google Play --}}
                    @if(!empty($footerSettings['playstore']))
                        <a href="{{ $footerSettings['playstore'] }}" target="_blank" class="flex items-center gap-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-lg p-2 transition-all group">
                            <svg class="w-8 h-8 text-white group-hover:text-green-400 transition-colors" fill="currentColor" viewBox="0 0 24 24"><path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.92 20.16,13.19L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L3.84,2.15C3.84,2.15 6.05,2.66 6.05,2.66Z"/></svg>
                            <div class="flex flex-col">
                                <span class="text-[10px] leading-none uppercase text-gray-400">Get it on</span>
                                <span class="text-sm font-bold text-white leading-tight">Google Play</span>
                            </div>
                        </a>
                    @endif
                </div>

                {{-- Social Icons (Dynamic) --}}
                <div class="flex gap-4 flex-wrap">
                    @foreach($socialLinks as $social)
                        <a href="{{ $social->url }}" target="_blank"
                           class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all transform hover:-translate-y-1"
                           title="{{ $social->name }}">

                            {{-- Render Icon FontAwesome --}}
                            @if($social->icon_class)
                                <i class="{{ $social->icon_class }} text-lg"></i>
                            @else
                                {{-- Fallback SVG Icon --}}
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- BOTTOM BAR --}}
        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-6">

            {{-- Copyright & Policy --}}
            <div class="text-xs text-gray-500 text-center md:text-left">
                <p>{{ $footerSettings['copyright'] ?? '© 2024 FlexBiz. All rights reserved.' }}</p>
                <div class="flex gap-4 mt-2 justify-center md:justify-start">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-white transition-colors">Cookie Settings</a>
                </div>
            </div>

            {{-- Trust Badges / Payment Icons (Static Images for now) --}}
            <div class="flex items-center gap-4 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-6 w-auto bg-white rounded p-1">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-6 w-auto bg-white rounded p-1">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" class="h-6 w-auto bg-white rounded p-1">

                {{-- Logo Bộ Công Thương --}}
                <img src="https://webmedia.com.vn/images/2021/09/logo-da-thong-bao-bo-cong-thuong-mau-xanh.png" class="h-10 w-auto">
            </div>
        </div>
    </div>
</footer>

{{-- Back to Top Button (AlpineJS Logic) --}}
<button x-data="{ show: false }"
        x-on:scroll.window="show = window.pageYOffset > 300"
        x-show="show"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        @click="window.scrollTo({top: 0, behavior: 'smooth'})"
        class="fixed bottom-8 right-8 z-40 p-3 rounded-full bg-blue-600 text-white shadow-xl hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300"
        style="display: none;">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
</button>

@livewire('website.chat.chat-widget')
