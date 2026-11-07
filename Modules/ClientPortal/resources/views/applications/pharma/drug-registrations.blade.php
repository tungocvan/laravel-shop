@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-5">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route('client.pharma.lookups') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về Tra cứu</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black">{{ $featurePresentation['page_title'] }}</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $featurePresentation['page_description'] }}</p>
    </section>

    <form method="GET" action="{{ route($routeBase) }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,200px)_minmax(0,1fr)_auto] sm:items-end">
        <label class="block text-sm font-semibold text-slate-800">Tiêu chí
            <select name="field" class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">
                @foreach (['name' => 'Tên thuốc', 'registration' => 'Số GPLH', 'ingredient' => 'Hoạt chất', 'registrant' => 'Công ty đăng ký', 'manufacturer' => 'Công ty sản xuất'] as $key => $label)
                    <option value="{{ $key }}" @selected($field === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-semibold text-slate-800">Từ khóa
            <input name="q" value="{{ $keyword }}" maxlength="200" required placeholder="Nhập tên thuốc hoặc số đăng ký" class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2">
        </label>
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="min-h-11 flex-1 rounded-xl bg-indigo-700 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-800">Tìm kiếm</button>
            <a href="{{ route($routeBase) }}" aria-label="Xóa bộ lọc tìm kiếm" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Xóa lọc</a>
        </div>
    </form>

    @if(session('success'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->has('source_id'))<div role="alert" class="rounded-xl bg-amber-50 p-4 text-sm text-amber-900">{{ $errors->first('source_id') }}</div>@endif
    @if ($error)
        <div role="alert" class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-semibold text-amber-900">{{ $error }}</div>
    @elseif ($keyword !== '')
        <p class="text-sm text-slate-600">Tìm thấy {{ number_format($result['total']) }} kết quả từ nguồn công bố. Mỗi số đăng ký được hiển thị riêng.</p>
        <section class="grid grid-cols-1 gap-4 lg:grid-cols-2" aria-label="Kết quả tra cứu">
            @forelse ($result['items'] as $item)
                <article class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="break-words text-lg font-black text-slate-950">{{ $item['name'] }} <span class="text-indigo-700">{{ $item['strength'] }}</span></h2>
                    @include('ClientPortal::applications.pharma.partials.lookup-wishlist-heart')
                    <p class="mt-2 text-sm font-semibold text-slate-700">Số GPLH: {{ $item['registration'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">Hoạt chất: {{ $item['ingredient'] ?: 'Chưa công bố' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Dạng bào chế: {{ $item['dosage_form'] ?: 'Chưa công bố' }}</p>
                    <p class="mt-1 text-sm text-slate-600">Hết hạn: {{ $item['expires_at'] ? substr($item['expires_at'], 0, 10) : 'Chưa công bố' }}</p>
                    <a class="mt-4 inline-flex min-h-11 items-center font-bold text-indigo-700 underline" href="{{ route($routeBase.'.show', ['sourceId' => $item['source_id'], 'field' => $field, 'q' => $keyword, 'page' => $page]) }}">Xem chi tiết →</a>
                </article>
            @empty
                <p class="rounded-2xl bg-white p-5 text-sm text-slate-600">Không có kết quả trên trang này.</p>
            @endforelse
        </section>
        @if ($result['total'] > $perPage)
            <nav aria-label="Phân trang" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4">
                <span class="text-sm text-slate-600">Trang {{ $page }} / {{ (int) ceil($result['total'] / $perPage) }}</span>
                <div class="flex gap-3">
                    @if ($page > 1)
                        <a class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold" href="{{ route($routeBase, ['field' => $field, 'q' => $keyword, 'page' => $page - 1]) }}">← Trước</a>
                    @endif
                    @if ($page * $perPage < $result['total'] && $page < 1000)
                        <a class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold" href="{{ route($routeBase, ['field' => $field, 'q' => $keyword, 'page' => $page + 1]) }}">Tiếp →</a>
                    @endif
                </div>
            </nav>
        @endif
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Nhập từ khóa để bắt đầu tra cứu giấy đăng ký thuốc.</div>
    @endif
    <p class="text-xs text-slate-500">Nguồn: cơ quan công bố tương ứng. Dữ liệu chỉ phục vụ tra cứu; không đồng bộ hoặc chỉnh sửa Danh mục thuốc.</p>
</div>
@endsection
