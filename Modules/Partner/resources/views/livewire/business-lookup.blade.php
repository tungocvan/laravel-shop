<div class="space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form wire:submit="search" class="flex flex-col gap-3 md:flex-row">
            <div class="flex-1">
                <label for="business-query" class="mb-1 block text-sm font-medium text-gray-700">Tên doanh nghiệp hoặc mã số thuế</label>
                <input id="business-query" wire:model="query" type="text" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Ví dụ: Công ty ABC hoặc 030...">
                @error('query') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:self-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="search" class="w-full rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Tra cứu</button>
            </div>
        </form>
        <p class="mt-3 text-xs text-gray-500">MaSoThue là nguồn tham khảo bên thứ ba, không phải nguồn xác minh pháp lý chính thức.</p>
        @if ($errorMessage)<div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errorMessage }}</div>@endif
    </section>

    @if ($candidates)
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4"><h2 class="font-semibold text-gray-900">Kết quả tra cứu</h2><p class="text-sm text-gray-500">Chọn đúng doanh nghiệp trước khi xem chi tiết. Hệ thống không tự chọn kết quả gần đúng.</p></div>
            <div class="divide-y divide-gray-100">
                @foreach ($candidates as $index => $candidate)
                    <button type="button" wire:click="selectCandidate({{ $index }})" wire:loading.attr="disabled" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-gray-50 disabled:opacity-50">
                        <span><span class="block font-medium text-gray-900">{{ $candidate['name'] }}</span><span class="mt-1 block text-sm text-gray-500">MST: {{ $candidate['tax_code'] }}</span></span>
                        <span class="text-sm font-semibold text-indigo-600">Chọn</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if ($selectedCandidate && $detail)
        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Dữ liệu nguồn</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    @foreach ([
                        'Tên pháp lý' => $selectedCandidate['name'] ?? null,
                        'Mã số thuế' => $selectedCandidate['tax_code'] ?? null,
                        'Trạng thái nguồn' => $detail['status'] ?? null,
                        'Địa chỉ thuế' => $detail['tax_address'] ?? null,
                        'Người đại diện' => $detail['representative'] ?? null,
                        'Ngày hoạt động' => $detail['active_since'] ?? null,
                        'Cơ quan quản lý' => $detail['managed_by'] ?? null,
                        'Loại tổ chức' => $detail['organization_type'] ?? null,
                    ] as $label => $value)
                        <div class="grid grid-cols-3 gap-3"><dt class="text-gray-500">{{ $label }}</dt><dd class="col-span-2 font-medium text-gray-900">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Đối chiếu Partner</h2>
                @if ($match && $match['partner_id'])
                    <p class="mt-2 text-sm text-gray-600">Khớp với <strong>{{ $match['partner_name'] }}</strong> — {{ $match['reason'] }} ({{ $match['score'] }}).</p>
                @else
                    <p class="mt-2 text-sm text-amber-700">Chưa tìm thấy Partner phù hợp. Đồng bộ sẽ tạo Partner mới sau khi xác nhận.</p>
                @endif

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="border-b border-gray-200 text-left text-gray-500"><th class="py-2 pr-3">Chọn</th><th class="py-2 pr-3">Trường</th><th class="py-2 pr-3">Local</th><th class="py-2 pr-3">Nguồn</th><th class="py-2">Trạng thái</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($plan as $field => $row)
                                <tr>
                                    <td class="py-3 pr-3"><input wire:model="selectedFields" value="{{ $field }}" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>
                                    <td class="py-3 pr-3 font-medium text-gray-900">{{ $field }}</td>
                                    <td class="py-3 pr-3 text-gray-600">{{ $row['local'] ?: '—' }}</td>
                                    <td class="py-3 pr-3 text-gray-600">{{ $row['source'] ?: '—' }}</td>
                                    <td class="py-3 text-gray-600">{{ $row['state'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync" class="mt-5 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Đồng bộ vào Partner</button>
                @error('sync') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($syncedPartnerId)<p class="mt-3 text-sm font-medium text-emerald-700">Đồng bộ thành công Partner #{{ $syncedPartnerId }}.</p>@endif
            </div>
        </section>
    @endif
</div>
