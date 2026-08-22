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
