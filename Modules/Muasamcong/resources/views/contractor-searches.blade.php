@extends('Admin::layouts.master')

@section('title', 'Danh sách nhà thầu đã tra cứu')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh sách nhà thầu đã tra cứu</h1>
                <p class="mt-1 text-sm text-gray-500">Dữ liệu đã lưu trên server được ưu tiên sử dụng lại, không gọi API Mua sắm công nếu chưa yêu cầu tìm kiếm mới.</p>
            </div>
            <a href="{{ route('muasamcong.contractors') }}"
               class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                + Tra cứu nhà thầu
            </a>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <form method="get" class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row">
                <input type="text" name="q" value="{{ $keyword }}"
                       placeholder="Tên nhà thầu, CONTRACTOR_CODE hoặc mã số thuế..."
                       class="min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <button class="rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-800">Tìm trong dữ liệu đã lưu</button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Nhà thầu</th>
                        <th class="px-5 py-3">CONTRACTOR_CODE</th>
                        <th class="px-5 py-3">MST</th>
                        <th class="px-5 py-3 text-right">Số gói</th>
                        <th class="px-5 py-3">Tra cứu gần nhất</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($searches as $search)
                        <tr class="hover:bg-gray-50">
                            <td class="max-w-md px-5 py-4 font-medium text-gray-900">{{ $search->contractor_name ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-indigo-700">{{ $search->contractor_code }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $search->tax_code ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-gray-700">
                                {{ number_format($search->unique_total) }}
                                @if ($search->reported_total !== $search->unique_total)
                                    <span class="text-xs text-gray-400">/ API {{ number_format($search->reported_total) }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $search->last_searched_at?->format('d/m/Y H:i') ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <a href="{{ route('muasamcong.contractors.history.show', $search) }}"
                                   class="font-semibold text-indigo-600 hover:text-indigo-800">Chi tiết</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-500">Chưa có lịch sử tra cứu nhà thầu phù hợp.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if ($searches->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">{{ $searches->links() }}</div>
            @endif
        </div>
    </div>
@endsection
