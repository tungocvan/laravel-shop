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
                    @if(!empty($footerSettings['logo']))<img src="{{ str_starts_with($footerSettings['logo'], 'http') ? $footerSettings['logo'] : asset('storage/'.$footerSettings['logo']) }}" alt="{{ $footerSettings['brand_name'] }}" class="h-10 w-auto max-w-40 object-contain">@else<div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-gray-900 font-black text-2xl group-hover:bg-blue-500 group-hover:text-white transition-colors">{{ mb_substr($footerSettings['brand_name'] ?? 'F', 0, 1) }}</div>@endif
                    <span class="text-2xl font-bold text-white tracking-tight">
                        {{ $footerSettings['brand_name'] ?? 'FlexBiz' }}<span class="text-blue-500">.</span>
                    </span>
                </a>

                {{-- Description --}}
                <p class="text-sm leading-relaxed text-gray-500">
                    {{ $footerSettings['description'] ?? 'Mô tả thương hiệu chưa được cập nhật.' }}
                </p>

                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="text-sm">{{ $footerSettings['address'] ?? 'Địa chỉ chưa cập nhật' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="text-sm">{{ $footerSettings['email'] ?? 'email@domain.com' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        <span class="text-sm font-bold text-white">{{ $footerSettings['phone'] ?? '1900 xxxx' }}</span>
                    </div>
                </div>
            </div>

            @if(isset($footerColumns) && $footerColumns->isNotEmpty())
                @foreach($footerColumns as $column)
                    <div class="lg:col-span-2">
                        <h3 class="text-white font-bold text-lg mb-6">{{ $column->title }}</h3>
                        <ul class="space-y-4 text-sm">
                            @foreach($column->links as $link)
                                <li>
                                    <a href="{{ $link->url }}" target="{{ $link->new_tab ? '_blank' : '_self' }}" class="hover:text-blue-500 transition-colors flex items-center gap-2">{{ $link->label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif

            <div class="lg:col-span-3">
                <h3 class="text-white font-bold text-lg mb-6">Tải Ứng Dụng</h3>
                <p class="text-xs text-gray-500 mb-4">Cài FlexBiz như một ứng dụng trên iPhone, iPad, Android hoặc máy tính.</p>

                @include('Website::partials.pwa-installer')

                <div class="flex gap-4 flex-wrap">
                    @foreach($socialLinks as $social)
                        <a href="{{ $social->url }}" target="_blank"
                           class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all transform hover:-translate-y-1"
                           title="{{ $social->name }}" aria-label="{{ $social->name }}">
                            @if($social->icon_class)
                                <i class="{{ $social->icon_class }} text-lg"></i>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="text-xs text-gray-500 text-center md:text-left">
                <p>{{ $footerSettings['copyright'] ?? '© 2024 FlexBiz. All rights reserved.' }}</p>
                <div class="flex gap-4 mt-2 justify-center md:justify-start">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-white transition-colors">Cookie Settings</a>
                </div>
            </div>

            <div class="flex items-center gap-4 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" loading="lazy" class="h-6 w-auto bg-white rounded p-1">
                <img src="https://webmedia.com.vn/images/2021/09/logo-da-thong-bao-bo-cong-thuong-mau-xanh.png" alt="Đã thông báo Bộ Công Thương" loading="lazy" class="h-10 w-auto">
            </div>
        </div>
    </div>
</footer>

<button x-data="{ show: false }"
        x-on:scroll.window="show = window.pageYOffset > 300"
        x-show="show"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-10"
        @click="window.scrollTo({top: 0, behavior: 'smooth'})"
        aria-label="Về đầu trang" class="fixed bottom-8 right-8 z-40 p-3 rounded-full bg-blue-600 text-white shadow-xl hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300"
        style="display: none;">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
</button>

@livewire('website.chat.chat-widget')
