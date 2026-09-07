<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Tổng Partner', $metrics['total']],
            ['Doanh nghiệp', $metrics['companies']],
            ['Bệnh viện', $metrics['hospitals']],
            ['Nhà cung cấp', $metrics['suppliers']],
            ['Khách hàng', $metrics['customers']],
            ['Đang hoạt động', $metrics['active']],
            ['Có mã số thuế', $metrics['with_tax_code']],
            ['Có nguồn ngoài', $metrics['with_external_source']],
        ] as [$label, $value])
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Chất lượng dữ liệu</h2>
            <dl class="mt-4 divide-y divide-gray-100 text-sm">
                <div class="flex justify-between py-3"><dt class="text-gray-600">Thiếu mã số thuế</dt><dd class="font-semibold text-gray-900">{{ number_format($metrics['missing_tax_code']) }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-gray-600">Thiếu tỉnh/thành</dt><dd class="font-semibold text-gray-900">{{ number_format($metrics['missing_province']) }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-gray-600">Partner mới trong 30 ngày</dt><dd class="font-semibold text-gray-900">{{ number_format($metrics['recent']) }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-gray-600">Tham chiếu MaSoThue</dt><dd class="font-semibold text-gray-900">{{ number_format($metrics['masothue_references']) }}</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Phân bố theo tỉnh/thành</h2>
            <div class="mt-4 space-y-3">
                @forelse ($topProvinces as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">{{ $row->province_code }}</span>
                        <span class="font-semibold text-gray-900">{{ number_format($row->total) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Chưa có dữ liệu tỉnh/thành để thống kê.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
