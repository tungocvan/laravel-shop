@extends('Admin::layouts.master')

@section('title', 'Danh mục lô / thuốc nhà thầu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">Mua sắm công · Người dùng xác nhận</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh mục lô / thuốc của nhà thầu</h1>
                <p class="mt-1 text-sm text-gray-500">
                    TBMT: <strong class="text-gray-700">{{ $notifyNo }}</strong> · Nhà thầu: <strong class="text-gray-700">{{ $contractorName ?: $contractorCode }}</strong>
                    <span class="text-gray-400">({{ $contractorCode }})</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('muasamcong.contractors.manual-lots.download', ['contractorCode' => $contractorCode, 'notifyNo' => $notifyNo]) }}"
                   class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Tải Excel
                </a>
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    ← Quay lại
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-violet-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lô đã xác nhận</div>
                <div class="mt-1 text-2xl font-bold text-violet-700">{{ number_format($lots->count(), 0, ',', '.') }}</div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng số lượng</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format((float) $lots->sum('quantity'), 0, ',', '.') }}</div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng KH</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format((float) $lots->sum('plan_amount'), 0, ',', '.') }}</div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tổng giá lô</div>
                <div class="mt-1 text-xl font-bold text-gray-900">{{ number_format((float) $lots->sum('lot_price'), 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Mã lô</th>
                        <th class="px-4 py-3">Tên lô / thuốc</th>
                        <th class="px-4 py-3">Hoạt chất</th>
                        <th class="px-4 py-3 text-right">Số lượng</th>
                        <th class="px-4 py-3 text-right">Giá KH</th>
                        <th class="px-4 py-3 text-right">SL × Giá KH</th>
                        <th class="px-4 py-3 text-right">Giá lô</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach ($lots as $lot)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-indigo-700">{{ $lot->lot_no ?: '—' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $lot->lot_name ?: ($lot->medicine_name ?: '—') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $lot->active_ingredient ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ is_numeric($lot->quantity) ? number_format((float) $lot->quantity, 0, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ is_numeric($lot->price_plan) ? number_format((float) $lot->price_plan, 0, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums font-medium">{{ is_numeric($lot->plan_amount) ? number_format((float) $lot->plan_amount, 0, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ is_numeric($lot->lot_price) ? number_format((float) $lot->lot_price, 0, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
            Danh mục này được lập từ các lô do người dùng xác nhận. Đây không phải danh mục lô được API KQLCNT tự động xác minh nếu dữ liệu nguồn chưa có khóa lô ↔ nhà thầu.
        </div>
    </div>
@endsection
