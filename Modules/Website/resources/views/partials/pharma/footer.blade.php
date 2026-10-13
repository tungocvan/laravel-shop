@php
    $settings = app(\Modules\System\Services\SettingsService::class);
    $companyName = (string) $settings->get('site_name', 'Dược phẩm');
    $saved = $settings->get('website.pharma_footer.content', []);
    $saved = is_array($saved) ? $saved : [];
    $content = array_replace([
        'description' => 'Cung cấp thông tin sản phẩm và danh mục dược phẩm được công bố để hỗ trợ tra cứu, tìm hiểu và tham khảo.',
        'links_title' => 'Khám phá',
        'links' => [
            ['label' => 'Trang chủ', 'url' => '/'],
            ['label' => 'Danh mục dược phẩm', 'url' => '/product'],
        ],
        'notice_title' => 'Lưu ý y khoa',
        'notice' => 'Thông tin trên website mang tính tham khảo, không thay thế chẩn đoán, chỉ định hoặc tư vấn của nhân viên y tế. Sử dụng thuốc theo hướng dẫn của người có chuyên môn.',
        'copyright' => 'Bảo lưu quyền.',
        'background' => '#020617',
        'foreground' => '#cbd5e1',
        'heading' => '#ffffff',
    ], $saved);
    $safeColor = static fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
    $links = is_array($content['links']) ? $content['links'] : [];
@endphp
<footer style="background:{{ $safeColor($content['background'], '#020617') }};color:{{ $safeColor($content['foreground'], '#cbd5e1') }}">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 sm:px-8 md:grid-cols-3">
        <div>
            <a href="{{ route('home') }}" class="text-xl font-extrabold" style="color:{{ $safeColor($content['heading'], '#ffffff') }}">{{ $companyName }}</a>
            @if(filled($content['description']))<p class="mt-4 max-w-sm text-sm leading-7">{{ $content['description'] }}</p>@endif
        </div>
        <div>
            <h2 class="font-bold uppercase tracking-wider" style="color:{{ $safeColor($content['heading'], '#ffffff') }}">{{ $content['links_title'] }}</h2>
            <nav aria-label="Liên kết cuối trang" class="mt-4 flex flex-col gap-3 text-sm">
                @foreach($links as $link)
                    @if(is_array($link) && filled($link['label'] ?? null) && filled($link['url'] ?? null))
                        @php($href = trim((string) $link['url']))
                        @if(str_starts_with($href, '/') && !str_starts_with($href, '//'))
                            <a class="hover:underline" href="{{ url($href) }}">{{ $link['label'] }}</a>
                        @elseif(filter_var($href, FILTER_VALIDATE_URL) && in_array(parse_url($href, PHP_URL_SCHEME), ['https', 'http'], true))
                            <a class="hover:underline" href="{{ $href }}" rel="noopener noreferrer">{{ $link['label'] }}</a>
                        @endif
                    @endif
                @endforeach
            </nav>
        </div>
        <div>
            <h2 class="font-bold uppercase tracking-wider" style="color:{{ $safeColor($content['heading'], '#ffffff') }}">{{ $content['notice_title'] }}</h2>
            <p class="mt-4 whitespace-pre-line text-sm leading-7">{{ $content['notice'] }}</p>
        </div>
    </div>
    <div class="border-t border-white/10 px-5 py-5 text-center text-xs opacity-80">© {{ date('Y') }} {{ $companyName }}. {{ $content['copyright'] }}</div>
</footer>
