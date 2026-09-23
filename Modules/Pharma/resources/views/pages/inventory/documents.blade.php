@extends('Admin::layouts.master')
@section('title', $title)
@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">Tra cứu chứng từ, trạng thái và ghi sổ phiếu nháp.</p>
        </div>
        <a href="{{ route($type === 'receipt' ? 'admin.pharma.inventory.receipts.create' : 'admin.pharma.inventory.issues.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">
            + {{ $type === 'receipt' ? 'Lập phiếu nhập' : 'Lập phiếu xuất' }}
        </a>
    </header>

    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_auto_auto_auto]">
        <input name="q" value="{{ request('q') }}" placeholder="{{ $type === 'receipt' ? 'Tìm mã phiếu / nhà cung cấp' : 'Tìm mã phiếu / nơi nhận' }}" class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm">
        <select name="status" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            <option value="">Tất cả trạng thái</option>
            <option value="draft" @selected(request('status') === 'draft')>Nháp</option>
            <option value="posted" @selected(request('status') === 'posted')>Đã ghi sổ</option>
        </select>
        <select name="per_page" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">
            @foreach([25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',25)===$size)>{{ $size }} / trang</option>@endforeach
        </select>
        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tìm</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Mã phiếu</th><th class="px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">{{ $type === 'receipt' ? 'Nhà cung cấp' : 'Nơi nhận' }}</th>
                        <th class="px-4 py-3 text-right">Mặt hàng</th><th class="px-4 py-3 text-right">{{ $type === 'receipt' ? 'Tổng giá trị' : 'Tổng SL' }}</th>
                        <th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        @php
                            $date=$type === 'receipt' ? $doc->receipt_date : $doc->issue_date;
                            $party=$type === 'receipt' ? $doc->supplier_name : $doc->recipient_name;
                            $postRoute=$type === 'receipt' ? route('admin.pharma.inventory.receipts.post',$doc) : route('admin.pharma.inventory.issues.post',$doc);
                        @endphp
                        <tr>
                            <td class="px-4 py-4 font-mono font-bold text-indigo-700">{{ $doc->number }}</td>
                            <td class="px-4 py-4">{{ $date->format('d/m/Y') }}</td>
                            <td class="px-4 py-4">{{ $party ?: '—' }}</td>
                            <td class="px-4 py-4 text-right">{{ $doc->items_count }}</td>
                            <td class="px-4 py-4 text-right font-semibold">{{ $type === 'receipt' ? number_format((float)$doc->total_value,0,',','.').' đ' : number_format((float)$doc->items_sum_quantity,0,',','.') }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $doc->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $doc->status === 'posted' ? 'Đã ghi sổ' : 'Nháp' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($type === 'receipt')
                                        <a href="{{ route('admin.pharma.inventory.receipts.show',$doc) }}" class="text-xs font-semibold text-slate-700">Xem</a>
                                        @can('edit_pharma')
                                            <a href="{{ route('admin.pharma.inventory.receipts.edit',$doc) }}" class="text-xs font-semibold text-indigo-700">{{ $doc->status === 'draft' ? 'Sửa' : 'Cập nhật' }}</a>
                                            @if($doc->status === 'draft')
                                                <button type="button" onclick="document.getElementById('post-{{ $type }}-{{ $doc->id }}').showModal()" class="text-xs font-semibold text-emerald-700">Ghi sổ</button>
                                            @endif
                                        @endcan
                                        @can('delete_pharma')
                                            @if($doc->status === 'draft')
                                                <button type="button" onclick="document.getElementById('delete-receipt-{{ $doc->id }}').showModal()" class="text-xs font-semibold text-rose-700">Xóa</button>
                                            @elseif($doc->status === 'posted')
                                                <button type="button" onclick="document.getElementById('revert-receipt-{{ $doc->id }}').showModal()" class="text-xs font-semibold text-amber-700">Hoàn tác ghi sổ</button>
                                            @endif
                                        @endcan
                                    @else
                                        @can('edit_pharma')
                                            @if($doc->status === 'draft')<button type="button" onclick="document.getElementById('post-{{ $type }}-{{ $doc->id }}').showModal()" class="text-xs font-semibold text-emerald-700">Ghi sổ</button>@else<span class="text-xs text-slate-400">Hoàn tất</span>@endif
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($doc->status === 'draft')
                            <dialog id="post-{{ $type }}-{{ $doc->id }}" class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/40">
                                <form method="POST" action="{{ $postRoute }}" class="p-6">
                                    @csrf
                                    <h3 class="text-lg font-bold text-slate-950">Xác nhận ghi sổ {{ $doc->number }}?</h3>
                                    <p class="mt-2 text-sm text-slate-600">Phiếu có {{ $doc->items_count }} mặt hàng, tổng số lượng {{ number_format((float)$doc->items_sum_quantity,0,',','.') }}. Ghi sổ sẽ cập nhật tồn kho thực tế.</p>
                                    @if($type === 'issue')<p class="mt-2 text-sm font-medium text-amber-700">Hệ thống sẽ kiểm tra tồn khả dụng trước khi xuất.</p>@endif
                                    <div class="mt-6 flex justify-end gap-2">
                                        <button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button>
                                        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Xác nhận ghi sổ</button>
                                    </div>
                                </form>
                            </dialog>
                        @endif
                        @if($type === 'receipt' && $doc->status === 'posted')
                            @can('delete_pharma')
                                <dialog id="revert-receipt-{{ $doc->id }}" class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/40">
                                    <form method="POST" action="{{ route('admin.pharma.inventory.receipts.revert',$doc) }}" class="p-6">@csrf
                                        <h3 class="text-lg font-bold text-amber-700">Hoàn tác ghi sổ {{ $doc->number }}?</h3>
                                        <p class="mt-2 text-sm text-slate-600">Hệ thống sẽ rút đúng số lượng của phiếu này khỏi tồn kho và chuyển phiếu về trạng thái Nháp.</p>
                                        <p class="mt-2 text-sm font-medium text-rose-700">Nếu tồn hiện tại của bất kỳ lô nào không đủ để hoàn tác, toàn bộ thao tác sẽ bị hủy và tồn kho không thay đổi.</p>
                                        <div class="mt-6 flex justify-end gap-2"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white">Xác nhận hoàn tác</button></div>
                                    </form>
                                </dialog>
                            @endcan
                        @endif
                        @if($type === 'receipt' && $doc->status === 'draft')
                            <dialog id="delete-receipt-{{ $doc->id }}" class="w-full max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/40">
                                <form method="POST" action="{{ route('admin.pharma.inventory.receipts.destroy',$doc) }}" class="p-6">@csrf @method('DELETE')
                                    <h3 class="text-lg font-bold text-rose-700">Xóa phiếu nháp {{ $doc->number }}?</h3>
                                    <p class="mt-2 text-sm text-slate-600">Phiếu và toàn bộ chi tiết hàng hóa nháp sẽ bị xóa. Thao tác này không ảnh hưởng tồn kho vì phiếu chưa ghi sổ.</p>
                                    <div class="mt-6 flex justify-end gap-2"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Xác nhận xóa</button></div>
                                </form>
                            </dialog>
                        @endif
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Chưa có chứng từ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $documents->links() }}</div>
    </section>
</div>
@endsection
