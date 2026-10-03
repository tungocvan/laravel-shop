@extends('Admin::layouts.master')

@section('title', 'Đơn vị Hành chính')

@section('content')
<div class="p-4 sm:p-6">
    <div class="mx-auto max-w-7xl space-y-5" x-data="{ toolsOpen: false, selectedCount: 0, allSelected: false }">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <button type="button"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                    x-on:click="toolsOpen = !toolsOpen"
                    x-bind:aria-expanded="toolsOpen">
                <div>
                    <div class="text-sm font-semibold text-gray-900">Import / Export Đơn vị Hành chính</div>
                    <div class="mt-0.5 text-xs text-gray-500">Tải file mẫu, import hoặc export khi cần.</div>
                </div>
                <div class="flex shrink-0 items-center gap-2 text-sm font-medium text-indigo-600">
                    <span x-text="toolsOpen ? 'Thu gọn' : 'Mở công cụ'">Mở công cụ</span>
                    <svg class="h-4 w-4 transition-transform" x-bind:class="{ 'rotate-180': toolsOpen }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </button>

            <div x-cloak x-show="toolsOpen" class="border-t border-gray-100">
                @livewire('shared.import-export.panel', [
                    'serviceClass' => \Modules\Admission\Services\ImportExport::class,
                    'title' => 'Import / Export Đơn vị Hành chính',
                    'description' => 'Export Excel có thể import lại; hỗ trợ file nguồn theo mapping B/C/F/G.',
                    'permission' => 'manage_admission_locations',
                ], key('admission-location-import-export'))
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quản lý Đơn vị Hành chính</h1>
                <p class="mt-1 text-sm text-gray-500">Lọc, tìm kiếm và chỉnh sửa tỉnh / phường mà không tải lại qua Livewire.</p>
            </div>
            <div class="text-sm text-gray-500">
                Hiển thị {{ $rows->count() }} / {{ number_format($totalRows) }} phường
            </div>
        </div>

        <form method="GET" action="{{ route('admin.admission.dvhc') }}"
              class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 md:grid-cols-[minmax(220px,0.8fr)_minmax(280px,1.4fr)_auto] md:items-end">
                <div>
                    <label for="dvhc-province-filter" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tỉnh / Thành phố</label>
                    <select id="dvhc-province-filter" name="province"
                            class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            onchange="this.form.submit()">
                        <option value="">Tất cả tỉnh / thành phố</option>
                        @foreach ($provinces as $provinceName)
                            <option value="{{ $provinceName }}" @selected($province === $provinceName)>{{ $provinceName }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="dvhc-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tìm phường / xã</label>
                    <div class="relative">
                        <input id="dvhc-search" type="search" name="search" value="{{ $search }}"
                               placeholder="Nhập tên phường / xã..."
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 pr-9 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @if ($search !== '')
                            <a href="{{ route('admin.admission.dvhc', array_filter(['province' => $province])) }}"
                               class="absolute right-3 top-1/2 -translate-y-1/2 text-lg leading-none text-gray-400 hover:text-gray-700"
                               aria-label="Xóa tìm kiếm">×</a>
                        @endif
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Lọc</button>
                    @if ($province !== '' || $search !== '')
                        <a href="{{ route('admin.admission.dvhc') }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Xóa lọc</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($province !== '')
            <form method="POST" action="{{ route('admin.admission.dvhc.update-province') }}"
                  class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                @csrf
                <input type="hidden" name="current_province_name" value="{{ $province }}">
                <div class="grid gap-3 md:grid-cols-[minmax(220px,0.8fr)_minmax(280px,1.4fr)_auto] md:items-end">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Đang chỉnh sửa</div>
                        <div class="mt-2 truncate text-sm font-semibold text-gray-900">{{ $province }}</div>
                    </div>
                    <div>
                        <label for="province-name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tên tỉnh / thành phố mới</label>
                        <input id="province-name" name="province_name" value="{{ old('province_name', $province) }}" required maxlength="255"
                               class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Cập nhật tên tỉnh</button>
                </div>
            </form>
        @endif

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500">
                    @if ($province !== '' || $search !== '')
                        Kết quả theo bộ lọc hiện tại
                    @else
                        Tối đa 200 dòng mỗi lần hiển thị
                    @endif
                </div>

                <form id="dvhc-bulk-delete" method="POST" action="{{ route('admin.admission.dvhc.delete-selected') }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn xóa các đơn vị hành chính đã chọn?')">
                    @csrf
                    <button type="submit" x-bind:disabled="selectedCount === 0"
                            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40">
                        Xóa đã chọn (<span x-text="selectedCount">0</span>)
                    </button>
                </form>
            </div>

            <div class="max-h-[560px] overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50">
                        <tr class="text-left text-gray-600">
                            <th class="w-12 px-4 py-3 text-center">
                                <input type="checkbox" x-model="allSelected"
                                       x-on:change="document.querySelectorAll('.dvhc-select-row').forEach(el => el.checked = allSelected); selectedCount = allSelected ? document.querySelectorAll('.dvhc-select-row').length : 0;"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                       aria-label="Chọn tất cả dòng đang hiển thị">
                            </th>
                            <th class="px-4 py-3 font-medium">Tỉnh / Thành phố</th>
                            <th class="px-4 py-3 font-medium">Mã</th>
                            <th class="px-4 py-3 font-medium">Phường / Xã</th>
                            <th class="w-20 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rows as $row)
                            <tr class="hover:bg-gray-50/70">
                                <form id="dvhc-row-{{ $row->id }}" method="POST" action="{{ route('admin.admission.dvhc.update', $row) }}">
                                    @csrf
                                </form>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" form="dvhc-bulk-delete" name="ids[]" value="{{ $row->id }}"
                                           class="dvhc-select-row rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                           x-on:change="selectedCount = document.querySelectorAll('.dvhc-select-row:checked').length; allSelected = selectedCount === document.querySelectorAll('.dvhc-select-row').length;"
                                           aria-label="Chọn {{ $row->ward_name }}">
                                </td>
                                <td class="px-4 py-3">
                                    <input form="dvhc-row-{{ $row->id }}" name="province_name" value="{{ $row->province_name }}" required maxlength="255"
                                           class="w-full min-w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">{{ $row->ward_code }}</td>
                                <td class="px-4 py-3">
                                    <input form="dvhc-row-{{ $row->id }}" name="ward_name" value="{{ $row->ward_name }}" required maxlength="255"
                                           class="w-full min-w-48 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="submit" form="dvhc-row-{{ $row->id }}"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Lưu</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">Không có dữ liệu phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
