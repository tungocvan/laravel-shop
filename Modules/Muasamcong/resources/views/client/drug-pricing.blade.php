@extends('Muasamcong::client.layout')

@section('title', 'Tra cứu thuốc trúng thầu')

@section('content')
    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
        <div class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Dữ liệu Mua sắm công</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Tra cứu thuốc trúng thầu</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Tìm theo tên thuốc, hoạt chất, mã TBMT hoặc đơn vị trúng thầu. Kết quả lấy từ nghiệp vụ tra cứu thực tế của Module Mua sắm công.</p>
        </div>

        <form method="GET" action="{{ route('client.muasamcong.drug-pricing') }}" class="mt-6">
            <div class="flex flex-col gap-3 sm:flex-row">
                <label for="keyword" class="sr-only">Từ khóa tra cứu</label>
                <input id="keyword" name="keyword" value="{{ $keyword }}" minlength="2" maxlength="200" placeholder="Ví dụ: Paracetamol 500mg, Cefixime 200mg..." class="min-w-0 flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base outline-none transition focus:border-slate-500 focus:ring-4 focus:ring-slate-100">
                <button type="submit" class="rounded-2xl bg-slate-900 px-6 py-3 font-bold text-white shadow-sm hover:bg-slate-800">Tra cứu</button>
            </div>
            @error('keyword')<p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
        </form>
    </section>

    @if($keyword !== '')
        @if(!($result['success'] ?? false))
            <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                <h2 class="font-bold">Chưa thể lấy dữ liệu</h2>
                <p class="mt-1 text-sm">{{ $result['message'] ?? 'Dịch vụ Mua sắm công đang tạm thời không phản hồi.' }}</p>
            </div>
        @else
            <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Kết quả</p>
                    <p class="mt-2 text-2xl font-bold">{{ number_format($summary['total']) }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Giá thấp nhất</p>
                    <p class="mt-2 text-2xl font-bold">{{ $summary['lowest_price'] !== null ? number_format($summary['lowest_price'], 0, ',', '.') . ' đ' : '—' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Giá trung bình</p>
                    <p class="mt-2 text-2xl font-bold">{{ $summary['average_price'] !== null ? number_format($summary['average_price'], 0, ',', '.') . ' đ' : '—' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Giá cao nhất</p>
                    <p class="mt-2 text-2xl font-bold">{{ $summary['highest_price'] !== null ? number_format($summary['highest_price'], 0, ',', '.') . ' đ' : '—' }}</p>
                </div>
            </section>

            <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <h2 class="font-bold">Kết quả cho “{{ $keyword }}”</h2>
                    <p class="mt-1 text-xs text-slate-500">Giá hiển thị là giá trúng thầu theo dữ liệu nguồn. Hãy kiểm tra thông tin chi tiết trước khi sử dụng cho hồ sơ nghiệp vụ.</p>
                </div>

                @if($items->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">Không tìm thấy kết quả phù hợp.</div>
                @else
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Thuốc</th><th class="px-5 py-3">Hoạt chất / Hàm lượng</th><th class="px-5 py-3">Giá trúng thầu</th><th class="px-5 py-3">Đơn vị trúng thầu</th><th class="px-5 py-3">Mã TBMT</th><th class="px-5 py-3">Quyết định</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($items as $item)
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="px-5 py-4"><div class="font-semibold text-slate-950">{{ $item['tenThuoc'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item['dangBaoChe'] ?? '' }} {{ !empty($item['duongDung']) ? '· '.$item['duongDung'] : '' }}</div></td>
                                        <td class="px-5 py-4"><div>{{ $item['tenHoatChat'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item['nongDo'] ?? '—' }}</div></td>
                                        <td class="whitespace-nowrap px-5 py-4 font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'], 0, ',', '.') . ' đ' : '—' }}</td>
                                        <td class="px-5 py-4">{{ implode('; ', array_map('strval', (array)($item['winningName'] ?? []))) ?: '—' }}</td>
                                        <td class="px-5 py-4">{{ $item['maTbmt'] ?? '—' }}</td>
                                        <td class="px-5 py-4"><div>{{ $item['soQuyetDinh'] ?? '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $item['ngayBanHanhQuyetDinh'] ?? $item['ngayDangTaiKqlcnt'] ?? '' }}</div></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="divide-y divide-slate-100 lg:hidden">
                        @foreach($items as $item)
                            <article class="p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0"><h3 class="font-bold text-slate-950">{{ $item['tenThuoc'] ?? 'Không có tên thuốc' }}</h3><p class="mt-1 text-sm text-slate-600">{{ $item['tenHoatChat'] ?? '—' }} · {{ $item['nongDo'] ?? '—' }}</p></div>
                                    <div class="shrink-0 text-right font-bold">{{ is_numeric($item['donGia'] ?? null) ? number_format((float)$item['donGia'], 0, ',', '.') . ' đ' : '—' }}</div>
                                </div>
                                <dl class="mt-4 grid gap-3 text-sm">
                                    <div><dt class="text-xs font-semibold uppercase text-slate-400">Đơn vị trúng thầu</dt><dd class="mt-1">{{ implode('; ', array_map('strval', (array)($item['winningName'] ?? []))) ?: '—' }}</dd></div>
                                    <div class="grid grid-cols-2 gap-3"><div><dt class="text-xs font-semibold uppercase text-slate-400">Mã TBMT</dt><dd class="mt-1">{{ $item['maTbmt'] ?? '—' }}</dd></div><div><dt class="text-xs font-semibold uppercase text-slate-400">Quyết định</dt><dd class="mt-1">{{ $item['soQuyetDinh'] ?? '—' }}</dd></div></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endif
@endsection
