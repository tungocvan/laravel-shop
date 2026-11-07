@extends('ClientPortal::layouts.application')

@section('title', $item['name'] ?: $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $dateText = static fn (string $date): string => preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})/', $date, $m) ? $m[3].'/'.$m[2].'/'.$m[1] : '—';
    $expiry = substr($item['expires_at'], 0, 10);
    $expired = preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $expiry) && $expiry < now()->toDateString();
    $fields = [
        'Mã cơ sở' => $item['company_code'],
        'Mã chứng nhận nguồn (idCC)' => $item['certificate_record_id'],
        'Nguyên tắc GMP' => $item['standard'],
        'Giấy chứng nhận' => $item['certificate'],
        'Cơ quan cấp' => $item['authority'],
        'Đợt công bố' => $item['publication_batch'],
        'Ngày cấp' => $dateText($item['issued_at']),
        'Ngày hết hạn' => $dateText($item['expires_at']),
        'Trạng thái công bố' => $item['active'] ? 'Đang hoạt động' : 'Chưa xác nhận hoạt động',
        'Hiệu lực theo ngày hết hạn' => $expiry === '' ? 'Chưa có dữ liệu' : ($expired ? 'Đã hết hạn' : 'Chưa hết hạn theo ngày công bố'),
        'ID bản ghi nguồn' => $item['source_id'],
    ];
@endphp
<div class="mx-auto max-w-5xl space-y-4">
    <a href="{{ route('client.pharma.gmp', ['q' => $keyword, 'page' => $page]) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-slate-700">← Quay lại kết quả GMP</a>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <header class="bg-slate-950 p-5 text-white sm:p-7">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
            <h1 class="mt-2 text-xl font-black sm:text-2xl">{{ $item['name'] ?: 'Cơ sở GMP' }}</h1>
            <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-300">{{ $item['address'] ?: 'Chưa có địa chỉ' }}</p>
        </header>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($fields as $label => $value)
                <div class="min-w-0 border-b border-r border-slate-100 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                    <dd class="mt-2 break-words text-sm font-semibold text-slate-900">{{ $value !== '' ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>
        <div class="p-4 sm:p-6">
            <h2 class="text-base font-black text-slate-950">Phạm vi chứng nhận GMP</h2>
            <div class="mt-3 whitespace-pre-wrap break-words rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-7 text-slate-800">{{ $item['scope'] ?: 'Chưa có thông tin phạm vi chứng nhận.' }}</div>
        </div>
    </section>
    <p class="text-xs text-slate-500">Nguồn: Cục Quản lý Dược – Bộ Y tế. Chỉ để tham khảo; cần đối chiếu bản gốc và tình trạng chứng nhận trước khi sử dụng cho hồ sơ pháp lý. Không tự cập nhật dữ liệu thuốc hoặc HSSP.</p>
</div>
@endsection
