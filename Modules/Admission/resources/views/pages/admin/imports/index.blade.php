@extends('Admin::layouts.master')

@section('content')
<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
    @if (session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lịch sử Import tuyển sinh</h1>
            <p class="text-sm text-gray-500 mt-1">Theo dõi kết quả từng lần import và các dòng cần chỉnh sửa.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($runs->total() > 0)
                <form action="{{ route('admin.admission.imports.clear') }}" method="POST"
                      onsubmit="return confirm('Xóa toàn bộ lịch sử Import và tất cả log lỗi liên quan? Hành động này không thể hoàn tác.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center justify-center px-4 h-10 rounded-xl bg-rose-700 text-white text-sm font-semibold hover:bg-rose-800">
                        Clear logs Import
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.admission.index') }}"
               class="inline-flex items-center justify-center px-4 h-10 rounded-xl border border-gray-300 bg-white text-sm font-medium hover:bg-gray-50">
                ← Hồ sơ tuyển sinh
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">#</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">File</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Thời gian</th>
                        <th class="px-5 py-3 text-center font-semibold text-gray-600">Tổng</th>
                        <th class="px-5 py-3 text-center font-semibold text-gray-600">Thành công</th>
                        <th class="px-5 py-3 text-center font-semibold text-gray-600">Lỗi</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Trạng thái</th>
                        <th class="px-5 py-3 text-right font-semibold text-gray-600">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($runs as $run)
                        <tr>
                            <td class="px-5 py-4 text-gray-500">{{ $run->id }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900">{{ $run->original_filename }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ optional($run->started_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-4 text-center">{{ $run->total_rows }}</td>
                            <td class="px-5 py-4 text-center text-emerald-700">{{ $run->success_rows }}</td>
                            <td class="px-5 py-4 text-center text-rose-700">{{ $run->failed_rows }}</td>
                            <td class="px-5 py-4">
                                @if ($run->status === 'completed')
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-emerald-100 text-emerald-800">Hoàn tất</span>
                                @elseif ($run->status === 'failed')
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-rose-100 text-rose-800">Lỗi file</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs bg-amber-100 text-amber-800">Đang xử lý</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.admission.imports.errors', $run) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-blue-700 hover:bg-blue-50">
                                    Xem lỗi{{ $run->failed_rows ? ' (' . $run->failed_rows . ')' : '' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-12 text-center text-gray-500">Chưa có lịch sử Import.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($runs->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">{{ $runs->links() }}</div>
        @endif
    </div>
</div>
@endsection
