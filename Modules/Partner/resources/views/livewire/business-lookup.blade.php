<div class="space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <form wire:submit="search" class="grid gap-3 md:grid-cols-[220px_minmax(0,1fr)_auto] md:items-end">
            <div>
                <label for="business-source" class="mb-1 block text-sm font-medium text-gray-700">Nguồn tra cứu</label>
                <select id="business-source" wire:model.live="selectedSource" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200">
                    @foreach ($sourceOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('selectedSource') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="business-query" class="mb-1 block text-sm font-medium text-gray-700">Tên doanh nghiệp hoặc mã số thuế</label>
                <input id="business-query" wire:model="query" type="text" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200" placeholder="Ví dụ: Công ty ABC hoặc 0317193865">
                @error('query') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <button type="submit" wire:loading.attr="disabled" wire:target="search" class="w-full rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="search">Tra cứu</span>
                    <span wire:loading wire:target="search">Đang tra cứu...</span>
                </button>
            </div>
        </form>

        <div class="mt-3 flex flex-col gap-1 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Mặc định: <strong>MSTCongTy</strong>. Bạn có thể đổi sang nguồn khác trước khi tra cứu.</p>
            <p>Dữ liệu nguồn ngoài chỉ dùng để đối chiếu; không tự ghi vào Partner.</p>
        </div>

        @if ($providerErrors)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                Nguồn đang chọn tạm thời không khả dụng. Bạn có thể đổi sang nguồn khác và thử lại.
            </div>
        @endif
        @if ($errorMessage) <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errorMessage }}</div> @endif
    </section>

    @if ($candidates)
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Kết quả tra cứu</h2>
                <p class="text-sm text-gray-500">Kết quả chỉ đến từ nguồn bạn đã chọn. Sau khi chọn doanh nghiệp, hệ thống mới đối chiếu thêm với nguồn còn lại.</p>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach ($candidates as $index => $candidate)
                    @php
                        $matchLabel = match ($candidate['match_type'] ?? 'approximate') {
                            'exact_tax_code' => 'Khớp chính xác MST', 'exact_name' => 'Khớp chính xác tên', default => 'Kết quả gần đúng',
                        };
                    @endphp
                    <button type="button" wire:key="business-candidate-{{ $index }}" wire:click="selectCandidate({{ $index }})" wire:loading.attr="disabled" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-gray-50 disabled:opacity-50">
                        <span>
                            <span class="block font-medium text-gray-900">{{ $candidate['name'] }}</span>
                            <span class="mt-1 block text-sm text-gray-500">MST: {{ $candidate['tax_code'] ?: '—' }}</span>
                            @if (! empty($candidate['address'])) <span class="mt-1 block text-xs text-gray-500">{{ $candidate['address'] }}</span> @endif
                            <span class="mt-1 block text-xs text-gray-500">Nguồn: {{ $candidate['source_label'] ?? 'Nguồn công khai' }}</span>
                            <span class="mt-2 inline-flex rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $matchLabel }}</span>
                        </span>
                        <span class="text-sm font-semibold text-indigo-600">Chọn doanh nghiệp</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    @if ($selectedCandidate && $detail)
        @if ($sourceConflicts)
            <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
                <h2 class="font-semibold text-amber-900">⚠ Có xung đột dữ liệu giữa các nguồn</h2>
                <p class="mt-1 text-sm text-amber-800">Các trường khác nhau: {{ implode(', ', $sourceConflicts) }}. Hãy xem bảng đối chiếu trước khi đồng bộ.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="border-b border-amber-200 text-left"><th class="py-2 pr-4">Nguồn</th><th class="py-2 pr-4">Tên</th><th class="py-2 pr-4">Địa chỉ</th><th class="py-2">Người đại diện</th></tr></thead>
                        <tbody>
                            @foreach ($sourceComparison as $source => $row)
                                <tr class="border-b border-amber-100 align-top">
                                    <td class="py-3 pr-4 font-medium">{{ $row['candidate']['source_label'] ?? $source }}</td>
                                    <td class="py-3 pr-4">{{ $row['candidate']['name'] ?? '—' }}</td>
                                    <td class="py-3 pr-4">{{ $row['detail']['tax_address'] ?? $row['candidate']['address'] ?? '—' }}</td>
                                    <td class="py-3">{{ $row['detail']['representative'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 class="font-semibold text-gray-900">Dữ liệu nguồn được chọn</h2><p class="mt-1 text-xs text-gray-500">Nguồn: {{ $selectedCandidate['source_label'] ?? 'Nguồn công khai' }} · kiểm tra lúc {{ $selectedCandidate['checked_at'] ?? '—' }}</p></div>
                    @if (! empty($selectedCandidate['canonical_url'])) <a href="{{ $selectedCandidate['canonical_url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-indigo-600">Mở nguồn gốc</a> @endif
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    @foreach (['Tên pháp lý' => $selectedCandidate['name'] ?? null, 'Mã số thuế' => $selectedCandidate['tax_code'] ?? null, 'Trạng thái nguồn' => $detail['status'] ?? null, 'Địa chỉ' => $detail['tax_address'] ?? null, 'Người đại diện' => $detail['representative'] ?? null, 'Ngày đăng ký/hoạt động' => $detail['active_since'] ?? null, 'Cơ quan quản lý' => $detail['managed_by'] ?? null, 'Loại hình doanh nghiệp' => $detail['organization_type'] ?? null, 'Ngành chính' => $detail['industry'] ?? null, 'Tỉnh/Thành phố' => $detail['province_name'] ?? null] as $label => $value)
                        <div class="grid grid-cols-3 gap-3"><dt class="text-gray-500">{{ $label }}</dt><dd class="col-span-2 font-medium text-gray-900">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Đối chiếu Partner</h2>
                @if ($match && $match['partner_id'])
                    <p class="mt-2 text-sm text-gray-600">Khớp với <strong>{{ $match['partner_name'] }}</strong> — {{ $match['reason'] }} (điểm {{ $match['score'] }}).</p>
                @else
                    <p class="mt-2 text-sm text-amber-700">Chưa tìm thấy Partner phù hợp. Tạo mới chỉ xảy ra sau khi bạn phân loại và xác nhận.</p>
                    <div class="mt-4 grid gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-medium text-gray-700">Loại pháp lý</label><select wire:model="newLegalType" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">@foreach ($legalTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('newLegalType')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                        <div><span class="mb-1 block text-sm font-medium text-gray-700">Vai trò Partner</span><div class="space-y-2">@foreach ($partnerTypes as $value => $label)<label class="flex items-center gap-2 text-sm text-gray-700"><input wire:model="newPartnerTypes" type="checkbox" value="{{ $value }}" class="rounded border-gray-300 text-indigo-600"><span>{{ $label }}</span></label>@endforeach</div>@error('newPartnerTypes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Partner mới có trạng thái ERP “Chờ xử lý”; trạng thái nguồn không tự ánh xạ vào ERP.</p>
                @endif

                @php $stateLabels = ['new_value'=>'Giá trị mới','same'=>'Giống dữ liệu local','local_differs'=>'Khác dữ liệu local','missing_locally'=>'Local đang thiếu','source_missing'=>'Nguồn không có']; $fieldLabels = ['tax_code'=>'Mã số thuế','name'=>'Tên Partner','address'=>'Địa chỉ']; @endphp
                <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="border-b border-gray-200 text-left text-gray-500"><th class="py-2 pr-3">Chọn</th><th class="py-2 pr-3">Trường</th><th class="py-2 pr-3">Local</th><th class="py-2 pr-3">Nguồn được chọn</th><th class="py-2">Trạng thái</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach ($plan as $field => $row)<tr wire:key="sync-plan-{{ $field }}"><td class="py-3 pr-3"><input wire:model="selectedFields" value="{{ $field }}" type="checkbox" class="rounded border-gray-300 text-indigo-600"></td><td class="py-3 pr-3 font-medium">{{ $fieldLabels[$field] ?? $field }}</td><td class="py-3 pr-3 text-gray-600">{{ $row['local'] ?: '—' }}</td><td class="py-3 pr-3 text-gray-600">{{ $row['source'] ?: '—' }}</td><td class="py-3 text-gray-600">{{ $stateLabels[$row['state']] ?? $row['state'] }}</td></tr>@endforeach</tbody></table></div>
                <button type="button" wire:click="sync" wire:loading.attr="disabled" wire:target="sync" class="mt-5 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="sync">Đồng bộ nguồn đã chọn vào Partner</span><span wire:loading wire:target="sync">Đang đồng bộ...</span></button>
                @error('sync') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($syncedPartnerId) <p class="mt-3 text-sm font-medium text-emerald-700">Đồng bộ thành công Partner #{{ $syncedPartnerId }}.</p> @endif
            </div>
        </section>
    @endif
</div>
