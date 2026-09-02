@extends('Admin::layouts.master')

@section('title', 'Tra cứu Mua sắm công')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Tra cứu Mua sắm công</h1>
                <p class="mt-1 text-sm text-gray-500">Tra cứu đơn giá thuốc trúng thầu từ Hệ thống mạng đấu thầu quốc gia.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
                @can('muasamcong.pricing.sync')
                    <button type="button" onclick="exportSmartPricingScope()"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        Xuất Excel
                    </button>
                @endcan
                <a href="{{ route('muasamcong.synced') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 shadow-sm hover:border-emerald-300 hover:bg-emerald-100">
                    Danh sách đã đồng bộ
                </a>
                <a href="{{ route('muasamcong.wishlist') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:border-rose-300 hover:bg-rose-100">
                    ♥ Wishlist thuốc
                </a>
                <a href="{{ route('muasamcong.contractors') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm hover:border-indigo-300 hover:bg-indigo-100">
                    Lịch sử nhà thầu
                </a>
            </div>
        </div>

        <section class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Thuốc & đơn giá trúng thầu</h2>
                <p class="text-sm text-gray-500">Smart Pricing, wishlist và đồng bộ dữ liệu mặt hàng.</p>
            </div>
            @livewire('muasamcong.tracuu-thuoctrungthau')
        </section>
    </div>

    @can('muasamcong.pricing.sync')
        <script>
            function exportSmartPricingScope() {
                if (typeof Livewire === 'undefined') {
                    alert('Không tìm thấy Livewire.');
                    return;
                }

                const checkbox = document.querySelector('input[wire\\:model\\.live="selectedSourceIds"]');
                const root = checkbox ? checkbox.closest('[wire\\:id]') : document.querySelector('[wire\\:id]');
                const component = root ? Livewire.find(root.getAttribute('wire:id')) : null;
                const keyword = component ? String(component.get('keyword') || '').trim() : '';
                const ids = component ? (component.get('selectedSourceIds') || []) : [];

                if (!keyword) {
                    alert('Hãy tra cứu dữ liệu trước khi xuất Excel.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = @js(route('muasamcong.pricing.export-selected'));
                form.innerHTML = '<input type="hidden" name="_token" value="' + @js(csrf_token()) + '">' +
                    '<input type="hidden" name="keyword" value="">';
                form.querySelector('input[name="keyword"]').value = keyword;

                if (Array.isArray(ids)) {
                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                }

                document.body.appendChild(form);
                form.submit();
            }
        </script>
    @endcan
@endsection
