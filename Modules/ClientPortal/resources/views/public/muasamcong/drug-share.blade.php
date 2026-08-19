<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>{{ $share->title }} · INAFO</title>
    <meta property="og:title" content="{{ $share->title }}">
    <meta property="og:description" content="{{ trim(($item['tenHoatChat'] ?? '').' · '.(is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'],0,',','.').' đ' : '')) }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="INAFO">
    @vite(['resources/css/tailwind.css', 'resources/js/tailwind.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<main class="mx-auto max-w-4xl px-4 py-6 sm:py-10">
    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="/" class="font-bold">INAFO</a>
        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold">Chia sẻ công khai</span>
    </div>
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-900 px-5 py-6 text-white sm:px-7">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dữ liệu Mua sắm công</p>
            <h1 class="mt-2 text-2xl font-bold">{{ $item['tenThuoc'] ?? 'Chi tiết thuốc trúng thầu' }}</h1>
            <p class="mt-2 text-sm text-slate-300">{{ $item['tenHoatChat'] ?? '—' }}{{ !empty($item['nongDo']) ? ' · '.$item['nongDo'] : '' }}</p>
        </div>
        @php($fields = [
            'Nhóm thuốc' => $item['nhomThuoc'] ?? $item['groupMedicine'] ?? null,
            'Đường dùng' => $item['duongDung'] ?? null,
            'Dạng bào chế' => $item['dangBaoChe'] ?? null,
            'Đơn vị tính' => $item['donViTinh'] ?? null,
            'Quy cách đóng gói' => $item['quyCachDongGoi'] ?? null,
            'Giá trúng thầu' => is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'],0,',','.').' đ' : null,
            'Số lượng' => is_numeric($item['soLuong'] ?? null) ? number_format((float)$item['soLuong'],0,',','.') : null,
            'Đơn vị trúng thầu' => implode('; ', array_map('strval', (array)($item['winningName'] ?? []))),
            'Chủ đầu tư / Bên mời thầu' => $item['tenCdtBmt'] ?? null,
            'Mã TBMT' => $item['maTbmt'] ?? null,
            'Số quyết định' => $item['soQuyetDinh'] ?? null,
            'Ngày quyết định' => $item['ngayBanHanhQuyetDinh'] ?? null,
            'Cơ sở sản xuất' => $item['tenCoSoSanXuat'] ?? null,
            'Nước sản xuất' => $item['nuocSanXuat'] ?? null,
        ])
        <dl class="grid md:grid-cols-2">@foreach($fields as $label=>$value)<div class="border-b border-slate-100 px-5 py-4 md:border-r"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 break-words text-sm font-medium">{{ ($value!==null&&$value!=='') ? $value : '—' }}</dd></div>@endforeach</dl>
        <div class="bg-slate-50 px-5 py-5 text-xs leading-5 text-slate-500 sm:px-7">Thông tin được chia sẻ từ ứng dụng INAFO. Dữ liệu phản ánh bản ghi tại thời điểm người dùng tạo liên kết chia sẻ.</div>
    </section>
</main>
</body>
</html>
