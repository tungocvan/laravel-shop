<div class="space-y-6">
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{ $title }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $description }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button
                        type="button"
                        wire:click="exportTemplate"
                        wire:loading.attr="disabled"
                        wire:target="exportTemplate"
                        class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="exportTemplate">
                            Tải file mẫu
                        </span>
                        <span wire:loading wire:target="exportTemplate">
                            Đang tạo mẫu...
                        </span>
                    </button>

                    <button
                        type="button"
                        wire:click="export"
                        wire:loading.attr="disabled"
                        wire:target="export"
                        class="inline-flex min-h-[46px] items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="export">
                            Export dữ liệu
                        </span>
                        <span wire:loading wire:target="export">
                            Đang export...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-5 px-6 py-5">
            @if (session()->has('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <label class="text-sm font-medium text-gray-700">
                        File import
                    </label>

                    <input
                        type="file"
                        wire:model.live="file"
                        accept=".xlsx,.csv"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    >

                    @error('file')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <p class="mt-2 text-xs text-gray-500">
                        Hỗ trợ .xlsx, .csv. Dung lượng tối đa 10MB.
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700">
                        Chế độ import
                    </label>

                    <select
                        wire:model.live="mode"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    >
                        <option value="update_or_create">Cập nhật hoặc tạo mới</option>
                        <option value="create_only">Chỉ tạo mới</option>
                        <option value="skip_duplicate">Bỏ qua dữ liệu trùng</option>
                        <option value="replace">Xóa sạch và nhập lại</option>
                    </select>

                    @error('mode')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            @if ($mode === 'replace')
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <strong>Cảnh báo:</strong> toàn bộ dữ liệu hiện tại sẽ bị xóa khi file hợp lệ. Nếu có bất kỳ dòng lỗi nào, transaction sẽ rollback và dữ liệu cũ được giữ nguyên.
                </div>
            @endif

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <label class="inline-flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model.live="dryRun"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >

                    <span class="text-sm text-gray-700">
                        Dry-run: chỉ kiểm tra dữ liệu, chưa ghi database
                    </span>
                </label>

                <button
                    type="button"
                    wire:click="import"
                    wire:loading.attr="disabled"
                    wire:target="import,file"
                    class="inline-flex min-h-[46px] items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="import,file">
                        Import dữ liệu
                    </span>
                    <span wire:loading wire:target="import,file">
                        Đang xử lý...
                    </span>
                </button>
            </div>
        </div>
    </div>

    @if ($report)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-5">
                <h3 class="text-base font-semibold text-gray-900">
                    Kết quả import
                </h3>
            </div>

            <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm text-gray-500">Tổng dòng</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">
                        {{ $report['total_rows'] ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                    <p class="text-sm text-green-700">Thành công</p>
                    <p class="mt-1 text-2xl font-semibold text-green-700">
                        {{ $report['success_rows'] ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                    <p class="text-sm text-red-700">Lỗi</p>
                    <p class="mt-1 text-2xl font-semibold text-red-700">
                        {{ $report['error_rows'] ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm text-amber-700">Bỏ qua</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-700">
                        {{ $report['skipped_rows'] ?? 0 }}
                    </p>
                </div>
            </div>

            @if (!empty($report['errors']))
                <div class="border-t border-gray-200 px-6 py-5">
                    <h4 class="mb-4 text-sm font-semibold text-gray-900">
                        Danh sách lỗi
                    </h4>

                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Sheet</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Dòng</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Cột</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Giá trị</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Lý do</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($report['errors'] as $error)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $error['sheet'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $error['row'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $error['column'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $error['value'] ?? '-' }}
                                            </td>

                                            <td class="px-4 py-3 text-red-600">
                                                {{ $error['reason'] ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="border-t border-gray-200 px-6 py-5">
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        Không có lỗi import.
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center">
            <p class="text-sm font-medium text-gray-900">
                Chưa có kết quả import
            </p>
            <p class="mt-1 text-sm text-gray-500">
                Sau khi import, hệ thống sẽ hiển thị tổng dòng, dòng thành công, dòng lỗi và chi tiết lỗi tại đây.
            </p>
        </div>
    @endif
</div>
