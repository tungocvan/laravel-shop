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
                    @php
                        $generalFields = [
                            'application_name' => ['Tên PWA', 'Tên đầy đủ hiển thị cho Client'],
                            'short_name' => ['Tên ngắn', 'Tên ngắn dùng trên thiết bị'],
                            'browser_title' => ['Tiêu đề trình duyệt', 'Title của trang đăng nhập PWA'],
                            'apple_title' => ['Apple Web App title', 'Tên khi chạy trên iPhone/iPad'],
                            'theme_color' => ['Theme color', 'Màu hex, ví dụ #0f172a'],
                            'background_color' => ['Background color', 'Màu nền PWA dạng hex'],
                        ];
                    @endphp
                    @foreach($generalFields as $key => [$label, $hint])
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                            <input name="{{ $key }}" value="{{ old($key, $general[$key] ?? '') }}" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <span class="mt-1 block text-xs text-gray-500">{{ $hint }}</span>
                        </label>
                    @endforeach
                </div>
            </form>

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
