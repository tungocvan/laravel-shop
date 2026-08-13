@extends('Admin::layouts.master')

@section('content')
<div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Lỗi Import #{{ $run->id }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $run->original_filename }} — {{ $run->failed_rows }} dòng lỗi / {{ $run->total_rows }} dòng.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.admission.imports.index') }}"
               class="inline-flex items-center justify-center px-4 h-10 rounded-xl border border-gray-300 bg-white text-sm font-medium hover:bg-gray-50">
                ← Lịch sử Import
            </a>
            <a href="{{ route('admin.admission.index') }}"
               class="inline-flex items-center justify-center px-4 h-10 rounded-xl bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
                Hồ sơ tuyển sinh
            </a>
        </div>
    </div>

    @if ($run->status === 'failed' && $run->fatal_error)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <strong>Lỗi file:</strong> {{ $run->fatal_error }}
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Dòng Excel</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Mã định danh</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Mã hồ sơ</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Học sinh</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Trường</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Mã lỗi</th>
                        <th class="px-5 py-3 text-left font-semibold text-gray-600">Nội dung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($errors as $error)
                        <tr>
                            <td class="px-5 py-4 font-semibold text-gray-900">{{ $error->row_number }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $error->ma_dinh_danh ?: '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $error->mhs ?: '—' }}</td>
                            <td class="px-5 py-4 text-gray-900">{{ $error->student_name ?: '—' }}</td>
                            <td class="px-5 py-4 text-gray-600">{{ $error->field ?: '—' }}</td>
                            <td class="px-5 py-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $error->error_code }}</code></td>
                            <td class="px-5 py-4 text-rose-700">{{ $error->error_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Lần Import này không có lỗi dòng dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($errors->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">{{ $errors->links() }}</div>
        @endif
    </div>
</div>
@endsection
