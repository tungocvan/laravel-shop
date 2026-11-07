@extends('ClientPortal::layouts.application')

@section('title', $item['name'].' — '.$item['registration'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-5">
    <section class="rounded-3xl bg-slate-950 px-5 py-6 text-white sm:px-7">
        <a href="{{ route($routeBase, ['field' => $field, 'q' => $keyword, 'page' => $page]) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-200">← Quay về kết quả</a>
        <p class="mt-3 text-xs font-bold uppercase tracking-widest text-slate-300">Thông tin công bố DAV</p>
        <h1 class="mt-2 break-words text-2xl font-black">{{ $item['name'] }} — {{ $item['strength'] }}</h1>
        <p class="mt-2 text-sm text-slate-300">Số GPLH: {{ $item['registration'] }}</p>
    </section>
    @foreach ([
        'Thông tin đăng ký' => [
            'Số GPLH' => $item['registration'], 'Số đăng ký cũ' => $item['old_registration'],
            'Ngày cấp' => $item['issued_at'], 'Ngày hết hạn' => $item['expires_at'],
            'Số quyết định' => $item['decision'], 'Đợt cấp' => $item['batch'],
        ],
        'Thông tin thuốc' => [
            'Tên thuốc' => $item['name'], 'Hoạt chất' => $item['ingredient'],
            'Hàm lượng' => $item['strength'], 'Dạng bào chế' => $item['dosage_form'],
            'Quy cách đóng gói' => $item['packaging'], 'Tiêu chuẩn' => $item['standard'],
            'Tuổi thọ (tháng)' => $item['shelf_life'],
        ],
        'Cơ sở sản xuất' => [
            'Tên công ty' => $item['manufacturer'], 'Địa chỉ' => $item['manufacturer_address'],
            'Quốc gia' => $item['manufacturer_country'],
        ],
        'Cơ sở đăng ký' => [
            'Tên công ty' => $item['registrant'], 'Địa chỉ' => $item['registrant_address'],
            'Quốc gia' => $item['registrant_country'],
        ],
    ] as $heading => $fields)
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">{{ $heading }}</h2>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($fields as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 break-words whitespace-pre-line text-sm text-slate-900">{{ $value !== '' ? $value : 'Chưa công bố' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach
    <p class="text-xs text-slate-500">Nguồn công bố chính thức. Các liên kết PDF chưa được kích hoạt vì cơ chế tải tài liệu chưa được xác minh. Không có chức năng đồng bộ vào Danh mục thuốc.</p>
</div>
@endsection
