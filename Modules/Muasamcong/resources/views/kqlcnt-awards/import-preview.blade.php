@extends('Admin::layouts.master')
@section('title', 'Đối chiếu Import Excel KQLCNT')
@section('content')
    @if(!empty($preview['package']))
    <section class="rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4 sm:p-5">
        <h2 class="text-base font-bold text-slate-950">Thay đổi thông tin trúng thầu (Sheet 1)</h2>
        <p class="mt-1 text-sm text-slate-600">Áp dụng đồng nhất cho các dòng thuốc thuộc đúng TBMT và nhà thầu đã chọn.</p>
        @foreach($preview['package']['errors'] as $error)
            <p role="alert" class="mt-2 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $error }}</p>
        @endforeach
        @if(count($preview['package']['comparison']) > 0)
            <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Thông tin</th><th class="p-3">Hiện tại</th><th class="p-3">Sau Import</th></tr></thead><tbody>
                @foreach($preview['package']['comparison'] as $change)
                    <tr class="border-t border-slate-100"><th class="p-3">{{ $change['label'] }}</th><td class="p-3">{{ $change['current'] ?? '—' }}</td><td class="p-3 font-semibold text-indigo-800">{{ $change['incoming'] ?? '—' }}</td></tr>
                @endforeach
                </tbody></table>
            </div>
        @else
            <p class="mt-2 text-sm text-slate-600">Không có thay đổi thông tin hợp đồng.</p>
        @endif
    </section>
    @endif
<div class="space-y-5 p-3 md:p-6" x-data="{ search: '', filter: 'actionable', showAllFields: false }">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Mua sắm công / KQLCNT</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Đối chiếu dữ liệu Import Excel</h1>
            <p class="mt-1 text-sm text-gray-600">So sánh từng trường với dữ liệu canonical trước khi xác nhận. Dòng lỗi và dòng không thay đổi không được ghi lại.</p>
        </div>
        <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700">Quay lại danh mục</a>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([['Tổng dòng', $preview['total'], 'gray'], ['Cần cập nhật', $preview['valid'], 'indigo'], ['Không thay đổi', $preview['unchanged'], 'green'], ['Lỗi / xung đột', $preview['errors'], 'red']] as $metric)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-medium text-gray-500">{{ $metric[0] }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $metric[1] }}</div>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-200 p-4 md:flex-row md:items-center">
            <label class="flex-1">
                <span class="mb-1 block text-xs font-semibold text-gray-600">Tìm theo thuốc, mã TBMT hoặc nhà thầu</span>
                <input type="search" x-model.debounce.200ms="search" placeholder="Nhập từ khóa..." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>
            <label>
                <span class="mb-1 block text-xs font-semibold text-gray-600">Lọc trạng thái</span>
                <select x-model="filter" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="actionable">Cần xử lý (mặc định)</option>
                    <option value="all">Tất cả (kể cả không thay đổi)</option>
                    <option value="changed">Có thay đổi</option>
                    <option value="unchanged">Không thay đổi</option>
                    <option value="error">Lỗi / xung đột</option>
                </select>
            </label>
        </div>
        @if ($preview['valid'] === 0 && $preview['errors'] === 0)
            <div class="border-b border-green-200 bg-green-50 px-5 py-6 text-center" role="status">
                <div class="text-lg font-semibold text-green-900">Dữ liệu đã được đồng bộ</div>
                <p class="mt-2 text-sm text-green-800">Không phát hiện sản phẩm cần cập nhật. Đã bỏ qua {{ $preview['unchanged'] }} dòng không thay đổi.</p>
                <p class="mt-1 text-xs text-green-700">Bạn có thể chọn “Tất cả” để xem lại dữ liệu đã đối chiếu.</p>
            </div>
        @endif
        <div class="divide-y divide-gray-200">
            @foreach ($preview['rows'] as $row)
                @php
                    $status = $row['status'];
                    $searchText = mb_strtolower(implode(' ', [$row['id'], $row['data']['medicine_name'] ?? '', $row['data']['notify_no'] ?? '', $row['data']['contractor_code'] ?? '', $row['data']['contractor_name'] ?? '']));
                @endphp
                <details class="group p-4 open:bg-gray-50" x-show="(filter === 'all' || (filter === 'actionable' && @js($status) !== 'unchanged') || filter === @js($status)) && (@js($searchText).includes(search.toLowerCase()))">
                    <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-gray-900">{{ $row['data']['medicine_name'] ?: 'Chưa có tên thuốc' }}</div>
                            <div class="mt-1 text-xs text-gray-500">Dòng {{ $row['line'] }} · ID {{ $row['id'] ?: '—' }} · TBMT {{ $row['data']['notify_no'] ?: '—' }} · Nhà thầu {{ $row['data']['contractor_code'] ?: '—' }}</div>
                        </div>
                        @if ($status === 'error')
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">Lỗi / xung đột</span>
                        @elseif ($status === 'unchanged')
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">Không thay đổi</span>
                        @else
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">{{ $row['changes'] }} trường thay đổi</span>
                        @endif
                        <span class="text-xs font-medium text-indigo-700 group-open:hidden">Xem 32 trường ▾</span>
                        <span class="hidden text-xs font-medium text-indigo-700 group-open:inline">Thu gọn ▴</span>
                    </summary>
                    @if ($row['errors'])
                        <div role="alert" class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ implode('; ', $row['errors']) }}</div>
                    @endif
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-gray-600">Mặc định ưu tiên trường thay đổi. Có thể xem đầy đủ 32 trường để kiểm tra.</p>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-indigo-700">
                            <input type="checkbox" x-model="showAllFields" class="rounded border-gray-300 text-indigo-600">
                            Hiển thị tất cả 32 trường
                        </label>
                    </div>
                    <div class="mt-2 overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-100"><tr>
                                <th scope="col" class="px-3 py-2 text-left">Trường dữ liệu</th>
                                <th scope="col" class="px-3 py-2 text-left">Dữ liệu hiện tại</th>
                                <th scope="col" class="px-3 py-2 text-left">Dữ liệu Excel</th>
                                <th scope="col" class="px-3 py-2 text-left">Đối chiếu</th>
                            </tr></thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($row['comparison'] as $field)
                                    <tr @class(['bg-amber-50' => $field['changed']]) x-show="showAllFields || @js($status) !== 'changed' || @js($field['changed'])">
                                        <th scope="row" class="whitespace-nowrap px-3 py-2 text-left font-medium text-gray-700">{{ $field['label'] }}</th>
                                        <td class="max-w-xs break-words px-3 py-2 text-gray-600">{{ $field['current'] ?? '—' }}</td>
                                        <td class="max-w-xs break-words px-3 py-2 font-medium text-gray-900">{{ $field['incoming'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 text-xs {{ $field['changed'] ? 'font-semibold text-amber-800' : 'text-gray-500' }}">{{ $field['changed'] ? 'Thay đổi' : 'Giữ nguyên' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
        </div>
    </div>
    <div class="sticky bottom-3 z-10 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-lg md:flex-row md:items-center md:justify-between">
        <p class="text-sm text-gray-600">Chỉ ghi <strong>{{ $preview['valid'] }}</strong> dòng có thay đổi hợp lệ; ẩn {{ $preview['unchanged'] }} dòng không thay đổi theo mặc định. Phiên xác nhận có hiệu lực 20 phút. Không tự xóa bản ghi.</p>
        <form method="POST" action="{{ route('muasamcong.kqlcnt-awards.import.confirm') }}" class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="hidden" name="token" value="{{ $preview['token'] }}">
            <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Hủy</a>
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" @disabled(($preview['valid'] === 0 && empty($preview['package']['changes'])) || !empty($preview['package']['errors']))>Xác nhận cập nhật {{ $preview['valid'] }} dòng</button>
        </form>
    </div>
</div>
@endsection
