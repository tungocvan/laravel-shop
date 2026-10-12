<x-admin-layout>
    <div class="mx-auto max-w-5xl space-y-5 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Import danh mục thuốc</h1>
                <p class="mt-1 text-sm text-slate-600">Chỉ cập nhật danh mục type=medicine; không tạo thuốc hoặc thay đổi giá, SKU, tồn kho.</p>
            </div>
            <a href="{{ route('admin.pharma.medicines.index') }}" class="rounded-xl border px-4 py-2 text-sm">← Danh mục thuốc chuẩn</a>
        </div>
        @if(session('success'))<p class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>@endif
        @if($errors->any())<div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="rounded-2xl border bg-white p-5 space-y-4">
            <h2 class="font-semibold">1. Xuất danh sách Medicine Master</h2>
            <a href="{{ route('admin.pharma.medicines.categories-csv.export') }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tải CSV để điền danh mục</a>
            <p class="text-sm text-slate-600">Giữ nguyên medicine_code và medicine_name. Điền category_slugs bằng slug danh mục thuốc, phân cách bằng dấu phẩy. Ô trống sẽ bỏ qua, không xóa liên kết cũ. Không dùng file này để thay đổi thông tin thuốc.</p>
        </section>
        <section class="rounded-2xl border bg-white p-5 space-y-4">
            <h2 class="font-semibold">2. Tải CSV và xem trước</h2>
            <form method="POST" action="{{ route('admin.pharma.medicines.categories-csv.preview') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="block text-sm font-medium">File CSV UTF-8 (tối đa 2 MB)<input type="file" name="file" accept=".csv,.txt" required class="mt-2 block w-full rounded-xl border p-2"></label>
                <button type="submit" class="min-h-10 rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white">Kiểm tra file</button>
            </form>
        </section>
        @if($preview !== [])
            <section class="rounded-2xl border bg-white p-5 space-y-4">
                <h2 class="font-semibold">3. Xem trước {{ count($preview) }} dòng</h2>
                <p class="text-sm text-amber-700">Chỉ các dòng có category_slugs mới cập nhật. Xác nhận sẽ thay thế toàn bộ danh mục của từng thuốc trong các dòng đó.</p>
                <div class="max-h-96 overflow-auto rounded-xl border">
                    <table class="w-full min-w-[650px] text-left text-sm">
                        <thead class="sticky top-0 bg-slate-100"><tr><th class="p-3">Mã thuốc</th><th class="p-3">Tên thuốc</th><th class="p-3">Danh mục</th><th class="p-3">Trạng thái</th></tr></thead>
                        <tbody>
                            @foreach($preview as $row)
                                <tr class="border-t"><td class="p-3 font-mono">{{ $row['code'] }}</td><td class="p-3">{{ $row['name'] }}</td><td class="p-3">{{ implode(', ', $row['slugs']) ?: '—' }}</td><td class="p-3">{{ $row['skip'] ? 'Bỏ qua' : 'Cập nhật' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('admin.pharma.medicines.categories-csv.commit') }}">@csrf
                    <button type="submit" class="min-h-11 rounded-xl bg-emerald-600 px-5 py-2 font-semibold text-white">Xác nhận cập nhật danh mục</button>
                </form>
            </section>
        @endif
    </div>
</x-admin-layout>
