{{--
    FOOTER COMPONENT
    Data Injected by: Modules/Website/Providers/WebsiteServiceProvider.php (View::composer)
    Variables: $footerSettings, $footerColumns, $socialLinks
--}}

<footer class="bg-gray-900 text-gray-400 border-t border-gray-800 font-sans relative">
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>

    <div class="container mx-auto px-4 pt-16 pb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-12 mb-16">
            @include('Website::components.footer.brand-contact')
            @include('Website::components.footer.menu-columns')
            @include('Website::components.footer.app-social')
        </div>

        @include('Website::components.footer.bottom-bar')
    </div>
</footer>

@include('Website::components.footer.back-to-top')

@livewire('website.chat.chat-widget')
