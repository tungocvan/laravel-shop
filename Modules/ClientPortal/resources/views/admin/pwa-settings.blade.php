@extends('Admin::layouts.master')
@section('title', 'Cấu hình PWA Client')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cấu hình PWA Client</h1>
            <p class="mt-1 text-sm text-gray-500">Quản trị branding và nội dung giao diện đăng nhập. Route, guard và permission vẫn do source code kiểm soát.</p>
        </div>
        <a href="{{ route('admin.client-apps.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">← Ứng dụng Client</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Chưa thể lưu cấu hình.</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.72fr)]">
        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.client-apps.pwa.general.update') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                @method('PUT')
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Thông tin PWA chung</h2>
                        <p class="mt-1 text-sm text-gray-500">Các giá trị này được dùng làm metadata/branding cho khu vực ClientPortal.</p>
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu cấu hình chung</button>
                </div>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach(($adminUi['general_fields'] ?? []) as $key => $field)
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-800">{{ $field['label'] ?? $key }}</span>
                            <input name="{{ $key }}" value="{{ old($key, $general[$key] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            @if(filled($field['hint'] ?? null))
                                <span class="mt-1 block text-xs text-gray-500">{{ $field['hint'] }}</span>
                            @endif
                            @if(in_array($key, $adminUi['color_fields'] ?? [], true) && filled($adminUi['color_picker_url'] ?? null))
                                <a
                                    href="{{ $adminUi['color_picker_url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    {{ $adminUi['color_picker_label'] ?? 'Mở công cụ lấy mã màu' }} ↗
                                </a>
                            @endif
                        </label>
                    @endforeach
                </div>
            </form>

            <form method="POST" action="{{ route('admin.client-apps.pwa.bottom-navigation.update') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf @method('PUT')
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 class="text-lg font-bold text-gray-900">Bottom Navigation dùng chung</h2><p class="mt-1 text-sm text-gray-500">Áp dụng cho Bottom Navigation mobile của tất cả Client Applications. Giá trị mặc định giữ giao diện hiện tại.</p></div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu Bottom Navigation</button>
                </div>
                <div class="mt-5 grid gap-3 rounded-2xl border border-gray-200 bg-gray-50 p-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                    <div>
                        <label class="text-sm font-semibold text-gray-800">Theme đã lưu</label>
                        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                            <select name="theme_key" form="bottom-navigation-theme-apply" onchange="if(this.value){this.form.requestSubmit()}" class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm">
                                <option value="">Chọn theme...</option>
                                @foreach($bottomNavigationThemes as $theme)
                                    <option value="{{ $theme['key'] }}">{{ $theme['builtin'] ? '★ ' : '' }}{{ $theme['name'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">★ Theme thiết kế sẵn · Theme tự tạo được lưu trong System Settings.</p>
                        </div>
                    </div>
                    <button type="submit" form="bottom-navigation-reset" class="self-end rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Reset mặc định</button>
                </div>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="text-sm font-semibold text-gray-800">Kiểu hiển thị</span>
                        <select name="presentation_style" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">
                            <option value="default" @selected(old('presentation_style', $bottomNavigation['presentation_style'] ?? 'default') === 'default')>Mặc định · Flat</option>
                            <option value="neumorphism" @selected(old('presentation_style', $bottomNavigation['presentation_style'] ?? 'default') === 'neumorphism')>Neumorphism · Nổi/lõm mềm</option>
                        </select>
                        <span class="mt-1 block text-xs text-gray-500">Neumorphism chỉ áp dụng cho Bottom Navigation mobile; sidebar tablet/desktop giữ nguyên.</span>
                    </label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Màu nền</span><input type="color" name="background_color" value="{{ old('background_color', $bottomNavigation['background_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Độ trong nền (%)</span><input type="number" min="0" max="100" name="background_opacity" value="{{ old('background_opacity', $bottomNavigation['background_opacity']) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Màu icon</span><input type="color" name="icon_color" value="{{ old('icon_color', $bottomNavigation['icon_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Màu text</span><input type="color" name="text_color" value="{{ old('text_color', $bottomNavigation['text_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Màu icon đang chọn</span><input type="color" name="active_icon_color" value="{{ old('active_icon_color', $bottomNavigation['active_icon_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Màu text đang chọn</span><input type="color" name="active_text_color" value="{{ old('active_text_color', $bottomNavigation['active_text_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Nền mục đang chọn</span><input type="color" name="active_background_color" value="{{ old('active_background_color', $bottomNavigation['active_background_color']) }}" class="mt-1 h-11 w-full rounded-xl border border-gray-300 bg-white p-1"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Font-size text (px)</span><input type="number" min="9" max="18" name="text_font_size" value="{{ old('text_font_size', $bottomNavigation['text_font_size']) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Kích thước icon (px)</span><input type="number" min="16" max="36" name="icon_size" value="{{ old('icon_size', $bottomNavigation['icon_size']) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm"></label>
                    <label class="block"><span class="text-sm font-semibold text-gray-800">Chiều cao tối thiểu (px)</span><input type="number" min="56" max="120" name="min_height" value="{{ old('min_height', $bottomNavigation['min_height']) }}" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm"><span class="mt-1 block text-xs text-gray-500">Safe-area iPhone vẫn được cộng riêng.</span></label>
                </div>
                <div class="mt-5 border-t border-gray-200 pt-5">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input name="theme_name" form="bottom-navigation-theme-store" maxlength="80" placeholder="Tên theme mới, ví dụ: Blue Compact" class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm">
                        <button type="submit" form="bottom-navigation-theme-store" class="rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700">Lưu thành theme mới</button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Theme lưu snapshot của kiểu hiển thị, toàn bộ màu sắc, kích thước và chiều cao Bottom Navigation hiện tại.</p>
                </div>
            </form>

            <form id="bottom-navigation-reset" method="POST" action="{{ route('admin.client-apps.pwa.bottom-navigation.reset') }}" class="hidden">@csrf</form>
            <form id="bottom-navigation-theme-store" method="POST" action="{{ route('admin.client-apps.pwa.bottom-navigation.themes.store') }}" class="hidden">@csrf</form>
            <form id="bottom-navigation-theme-apply" method="POST" action="{{ route('admin.client-apps.pwa.bottom-navigation.themes.apply') }}" class="hidden">@csrf</form>

            <form method="POST" action="{{ route('admin.client-apps.pwa.login.update') }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Nội dung Login PWA</h2>
                        <p class="mt-1 text-sm text-gray-500">Nội dung được render động qua ClientPortal settings service, không đọc trực tiếp từ database trong Blade.</p>
                    </div>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Lưu giao diện Login</button>
                </div>

                <div class="mt-6 space-y-5">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-800">Badge</span>
                        <input name="badge" value="{{ old('badge', $login['badge'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-800">Tiêu đề lớn</span>
                        <textarea name="heading" rows="2" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('heading', $login['heading'] ?? '') }}</textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-800">Mô tả</span>
                        <textarea name="description" rows="3" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old('description', $login['description'] ?? '') }}</textarea>
                    </label>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <input type="hidden" name="show_intro_panel" value="0">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="show_intro_panel" value="1" @checked(old('show_intro_panel', $login['show_intro_panel'] ?? true)) class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span><strong class="block text-sm text-gray-900">Hiển thị panel giới thiệu trên desktop</strong><span class="text-xs text-gray-500">Tắt nếu chỉ muốn hiển thị form đăng nhập.</span></span>
                        </label>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <label class="block"><span class="text-sm font-semibold text-gray-800">Link về Website</span><input name="back_to_website_text" value="{{ old('back_to_website_text', $login['back_to_website_text'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
                        <label class="block"><span class="text-sm font-semibold text-gray-800">Nhãn Web</span><input name="web_mode_label" value="{{ old('web_mode_label', $login['web_mode_label'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
                        <label class="block"><span class="text-sm font-semibold text-gray-800">Nhãn PWA đã cài</span><input name="standalone_mode_label" value="{{ old('standalone_mode_label', $login['standalone_mode_label'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div><h3 class="font-bold text-gray-900">Các thẻ giới thiệu chức năng</h3><p class="text-xs text-gray-500">Có thể đổi nội dung và bật/tắt từng thẻ. Thứ tự hiện tại được giữ theo danh sách.</p></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Tối đa 8 thẻ</span>
                        </div>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            @foreach(($login['feature_cards'] ?? []) as $index => $card)
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <input type="hidden" name="feature_cards[{{ $index }}][enabled]" value="0">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-800"><input type="checkbox" name="feature_cards[{{ $index }}][enabled]" value="1" @checked(old("feature_cards.$index.enabled", $card['enabled'] ?? true)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"> Hiển thị thẻ</label>
                                    <label class="mt-3 block"><span class="text-xs font-semibold text-gray-600">Tiêu đề</span><input name="feature_cards[{{ $index }}][title]" value="{{ old("feature_cards.$index.title", $card['title'] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
                                    <label class="mt-3 block"><span class="text-xs font-semibold text-gray-600">Mô tả</span><textarea name="feature_cards[{{ $index }}][description]" rows="2" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">{{ old("feature_cards.$index.description", $card['description'] ?? '') }}</textarea></label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <aside class="xl:sticky xl:top-4 xl:self-start">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-slate-950 shadow-xl">
                <div class="border-b border-white/10 px-5 py-4 text-white">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Preview nội dung</p>
                    <h2 class="mt-1 text-lg font-bold">{{ $general['application_name'] ?? '' }}</h2>
                </div>
                <div class="p-5 text-white">
                    @if($login['show_intro_panel'] ?? true)
                        <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-slate-300">{{ $login['badge'] ?? '' }}</span>
                        <h3 class="mt-5 text-3xl font-black leading-tight">{{ $login['heading'] ?? '' }}</h3>
                        <p class="mt-4 text-sm leading-6 text-slate-300">{{ $login['description'] ?? '' }}</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            @foreach(collect($login['feature_cards'] ?? [])->where('enabled', true) as $card)
                                <div class="rounded-xl border border-white/10 bg-white/5 p-3"><strong class="block text-sm text-white">{{ $card['title'] }}</strong><span class="mt-1 block text-xs leading-5 text-slate-400">{{ $card['description'] }}</span></div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300">Panel giới thiệu đang tắt. Client sẽ chỉ thấy form đăng nhập.</p>
                    @endif
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
