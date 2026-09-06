@extends('Admin::layouts.master')

@section('title', 'Kho dữ liệu cơ sở KCB nguồn')

@section('content')
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-600">Pharma / Official Source Mirror</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Kho dữ liệu cơ sở KCB nguồn</h1>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Kho mirror giữ dữ liệu nguồn BHXH đã đồng bộ để vẫn tra cứu được khi API nguồn tạm thời không khả dụng. Đây không phải Partner master.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.pharma.official-facilities.bhxh.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Tra cứu BHXH</a>
                <a href="{{ route('admin.pharma.official-facilities.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Official Import</a>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="grid gap-3 md:grid-cols-5">
                <input name="search" value="{{ request('search') }}" class="min-h-11 rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Mã CSKCB hoặc tên cơ sở">
                <select name="source" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả nguồn</option>
                    <option value="bhxh" @selected(request('source') === 'bhxh')>BHXH</option>
                </select>
                <select name="province" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả tỉnh/thành</option>
                    @foreach ($provinceOptions as $province)
                        <option value="{{ $province->source_province_code }}" @selected(request('province') === $province->source_province_code)>{{ $province->province_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="stale" @selected(request('status') === 'stale')>Stale</option>
                </select>
                <div class="flex gap-2">
                    <select name="per_page" class="min-h-11 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                        @foreach ([10,25,50,100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / trang</option>
                        @endforeach
                    </select>
                    <button class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:border-sky-300">Lọc</button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Dữ liệu đã đồng bộ</h2>
                    <p class="mt-1 text-sm text-slate-500">Identity nguồn: <code>(source, external_id)</code>. Bản ghi stale được giữ lại để phục vụ lịch sử.</p>
                </div>
                <div class="text-sm font-semibold text-slate-700">{{ number_format($facilities->total()) }} cơ sở</div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">Nguồn</th><th class="px-4 py-3">Mã CSKCB</th><th class="px-4 py-3">Tên cơ sở</th><th class="px-4 py-3">Tỉnh/Thành</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Lần đồng bộ cuối</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($facilities as $facility)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-700">{{ strtoupper($facility->source) }}</td>
                                <td class="px-4 py-3 font-mono text-slate-800">{{ $facility->external_id }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $facility->facility_name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $facility->province_name ?: $facility->source_province_code }}</td>
                                <td class="px-4 py-3"><span class="rounded-full border px-2 py-1 text-xs font-semibold {{ $facility->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">{{ $facility->is_active ? 'ACTIVE' : 'STALE' }}</span></td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($facility->last_synced_at)->format('d/m/Y H:i') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có dữ liệu nguồn đã đồng bộ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $facilities->links() }}</div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4"><h2 class="text-lg font-semibold text-slate-900">20 batch đồng bộ gần nhất</h2><p class="mt-1 text-sm text-slate-500">Theo dõi queue và kết quả upsert/stale.</p></div>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600"><tr><th class="px-3 py-3">Batch</th><th class="px-3 py-3">Tỉnh/Thành</th><th class="px-3 py-3">Scope</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Fetched</th><th class="px-3 py-3">Created</th><th class="px-3 py-3">Updated</th><th class="px-3 py-3">Unchanged</th><th class="px-3 py-3">Stale</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($batches as $batch)
                            <tr><td class="px-3 py-3 font-semibold">#{{ $batch->id }}</td><td class="px-3 py-3">{{ $batch->province_name }}</td><td class="px-3 py-3">{{ strtoupper($batch->sync_scope) }}</td><td class="px-3 py-3 font-semibold">{{ $batch->status }}</td><td class="px-3 py-3">{{ $batch->fetched_count }}</td><td class="px-3 py-3">{{ $batch->created_count }}</td><td class="px-3 py-3">{{ $batch->updated_count }}</td><td class="px-3 py-3">{{ $batch->unchanged_count }}</td><td class="px-3 py-3">{{ $batch->stale_count }}</td></tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Chưa có batch đồng bộ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
