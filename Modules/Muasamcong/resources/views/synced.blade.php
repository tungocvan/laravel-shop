@extends('Admin::layouts.master')

@section('title', 'Danh sách đã đồng bộ')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh sách đã đồng bộ</h1>
                <p class="mt-1 text-sm text-gray-500">Quản lý các thuốc đã lưu vào database, bổ sung KQLCNT, cập nhật đơn vị trúng thầu và xóa dữ liệu không còn cần theo dõi.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="exportSelectedBbg()" class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm hover:bg-amber-100">
                    Xuất BBG đã chọn
                </button>
                <a href="{{ route('muasamcong.wishlist') }}" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">♥ Wishlist</a>
                <a href="{{ route('muasamcong.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">← Về tra cứu thuốc</a>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            BBG sử dụng đúng bố cục template INAFO: đầy đủ header công ty, tiêu đề BẢNG CHÀO GIÁ, 19 cột dữ liệu và phần ký GIÁM ĐỐC CÔNG TY. Hãy tick các dòng trong bảng rồi bấm “Xuất BBG đã chọn”.
        </div>

        @livewire('muasamcong.synced-pricing-list')
    </div>

    <script>
        function exportSelectedBbg() {
            if (typeof Livewire === 'undefined') {
                alert('Không tìm thấy Livewire. Vui lòng tải lại trang.');
                return;
            }

            const selectedCheckbox = document.querySelector('input[wire\\:model\\.live="selectedIds"]');
            const root = selectedCheckbox ? selectedCheckbox.closest('[wire\\:id]') : null;

            if (!root) {
                alert('Không tìm thấy danh sách đồng bộ. Vui lòng tải lại trang.');
                return;
            }

            const component = Livewire.find(root.getAttribute('wire:id'));
            const selectedIds = component ? (component.get('selectedIds') || []) : [];

            if (!Array.isArray(selectedIds) || selectedIds.length === 0) {
                alert('Vui lòng chọn ít nhất một bản ghi để xuất BBG.');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = @js(route('muasamcong.synced.export-bbg'));
            form.style.display = 'none';

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = @js(csrf_token());
            form.appendChild(csrf);

            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        }
    </script>
@endsection
