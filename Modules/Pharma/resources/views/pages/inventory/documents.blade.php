@extends('Admin::layouts.master')
@section('title', $title)
@section('admin_container','full')
@section('content')
<div class="mx-auto flex min-h-[calc(100vh-7.5rem)] w-full max-w-[1580px] flex-col gap-6">
    <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">Tra cứu chứng từ, trạng thái và ghi sổ phiếu nháp.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('edit_pharma')
                @if($type === 'receipt')
                    <a href="{{ route('admin.pharma.inventory.receipts.settings') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">⚙ Cấu hình phiếu nhập</a>
                @else
                    <a href="{{ route('admin.pharma.inventory.issues.settings') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">⚙ Cấu hình phiếu xuất</a>
                @endif
            @endcan
            @if($type === 'issue')<a href="{{ route('admin.pharma.inventory.issues.export',request()->only(['q','status'])) }}" class="rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700">Export Excel</a>@endif
            <a href="{{ route($type === 'receipt' ? 'admin.pharma.inventory.receipts.create' : 'admin.pharma.inventory.issues.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">+ {{ $type === 'receipt' ? 'Lập phiếu nhập' : 'Lập phiếu xuất' }}</a>
        </div>
    </header>

    <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(260px,1fr)_auto_auto_auto]">
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

    <section class="flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="min-h-[420px] flex-1 overflow-auto rounded-t-2xl">
            <table class="min-w-[1120px] w-full table-fixed text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase text-slate-600 shadow-[0_1px_0_0_rgb(226_232_240)]">
                    <tr>
                        <th class="w-[180px] px-4 py-3">Mã phiếu</th><th class="w-[110px] px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">{{ $type === 'receipt' ? 'Nhà cung cấp' : 'Khách hàng / Nơi nhận' }}</th>
                        <th class="w-[100px] px-4 py-3 text-right">Mặt hàng</th><th class="w-[160px] px-4 py-3 text-right">Tổng giá trị</th>
                        <th class="w-[125px] px-4 py-3">Trạng thái</th><th class="{{ $type === 'issue' ? 'w-[150px]' : 'w-[230px]' }} px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        @php
                            $date=$type === 'receipt' ? $doc->receipt_date : $doc->issue_date;
                            $party=$type === 'receipt' ? $doc->supplier_name : $doc->recipient_name;
                            $postRoute=$type === 'receipt' ? route('admin.pharma.inventory.receipts.post',$doc) : route('admin.pharma.inventory.issues.post',$doc);
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4 font-mono font-bold text-indigo-700">{{ $doc->number }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $date->format('d/m/Y') }}</td>
                            <td class="px-4 py-4"><div class="truncate font-semibold text-slate-800" title="{{ $party ?: '—' }}">{{ $party ?: '—' }}</div>@if($type === 'issue' && $doc->priceList?->manager)<div class="mt-1 truncate text-xs text-slate-500">{{ $doc->priceList->manager->name }}</div>@endif</td>
                            <td class="px-4 py-4 text-right">{{ $doc->items_count }}</td>
                            <td class="px-4 py-4 text-right font-semibold">{{ number_format((float)$doc->total_value,0,',','.').' đ' }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $doc->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $doc->status === 'posted' ? 'Đã ghi sổ' : 'Nháp' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($type === 'receipt')
                                        <a href="{{ route('admin.pharma.inventory.receipts.show',$doc) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">Xem</a>
                                        <details class="relative"><summary class="flex min-h-9 cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-3 text-base font-bold leading-none text-slate-600 hover:bg-slate-50" aria-label="Thao tác phiếu nhập">⋯</summary>
                                         <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl">
                                          @can('edit_pharma')<a href="{{ route('admin.pharma.inventory.receipts.edit',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $doc->status === 'draft' ? 'Sửa phiếu' : 'Cập nhật phiếu' }}</a>@if($doc->status === 'draft')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('post-{{ $type }}-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Ghi sổ</button>@endif @endcan
                                          <a href="{{ route('admin.pharma.inventory.receipts.pdf',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tải PDF</a>
                                          <a href="{{ route('admin.pharma.inventory.receipts.print',$doc) }}" target="_blank" rel="noopener" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In trực tiếp</a>
                                          @can('delete_pharma')<div class="my-1 border-t border-slate-100"></div>@if($doc->status === 'draft')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('delete-receipt-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa phiếu</button>@elseif($doc->status === 'posted')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('revert-receipt-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác ghi sổ</button>@endif @endcan
                                         </div></details>
                                    @else
                                        <a href="{{ route('admin.pharma.inventory.issues.show',$doc) }}" class="inline-flex min-h-9 items-center rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">Xem</a>
                                        <details class="relative">
                                            <summary class="flex min-h-9 cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-3 text-base font-bold leading-none text-slate-600 hover:bg-slate-50" aria-label="Thao tác khác">⋯</summary>
                                            <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl">
                                                @can('edit_pharma')
                                                    <a href="{{ route('admin.pharma.inventory.issues.edit',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $doc->status === 'draft' ? 'Sửa phiếu' : 'Cập nhật phiếu' }}</a>
                                                    @if($doc->status === 'draft')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('post-{{ $type }}-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Ghi sổ</button>@endif
                                                @endcan
                                                <a href="{{ route('admin.pharma.inventory.issues.pdf',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tải PDF</a>
                                                <a href="{{ route('admin.pharma.inventory.issues.print',$doc) }}" target="_blank" rel="noopener" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In trực tiếp</a>
                                                @can('delete_pharma')
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                    @if($doc->status === 'draft')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('delete-issue-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa phiếu</button>
                                                    @elseif($doc->status === 'posted')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('revert-issue-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác ghi sổ</button>@endif
                                                @endcan
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($doc->status === 'draft')
                            <dialog id="post-{{ $type }}-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                <form method="POST" action="{{ $postRoute }}" class="overflow-hidden rounded-2xl bg-white">
                                    @csrf
                                    <div class="flex items-start gap-4 p-6">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xl text-emerald-700">✓</div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Xác nhận ghi sổ?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                            <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu có <strong>{{ $doc->items_count }} mặt hàng</strong>, tổng số lượng <strong>{{ number_format((float)$doc->items_sum_quantity,0,',','.') }}</strong>. Sau khi xác nhận, tồn kho thực tế sẽ được cập nhật.</p>
                                            @if($type === 'issue')<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-5 text-amber-800"><strong>Kiểm tra tồn kho:</strong> hệ thống sẽ kiểm tra tồn khả dụng trước khi xuất.</div>@endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Xác nhận ghi sổ</button></div>
                                </form>
                            </dialog>
                        @endif
                        @if($type === 'issue' && $doc->status === 'posted')
                            @can('delete_pharma')
                            <dialog id="revert-issue-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]"><form method="POST" action="{{ route('admin.pharma.inventory.issues.revert',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf<div class="p-6"><h3 class="text-lg font-bold">Hoàn tác ghi sổ phiếu xuất?</h3><p class="mt-2 text-sm text-slate-600">Hàng của phiếu sẽ được cộng trả đúng lô tồn kho và phiếu trở về Nháp.</p></div><div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border bg-white px-4 py-2">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2 font-semibold text-white">Hoàn tác ghi sổ</button></div></form></dialog>
                            @endcan
                        @endif
                        @if($type === 'issue' && $doc->status === 'draft')
                            @can('delete_pharma')
                            <dialog id="delete-issue-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]"><form method="POST" action="{{ route('admin.pharma.inventory.issues.destroy',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf @method('DELETE')<div class="p-6"><h3 class="text-lg font-bold">Xóa phiếu xuất nháp?</h3><p class="mt-2 text-sm text-slate-600">Phiếu chưa ghi sổ nên xóa không ảnh hưởng tồn kho.</p></div><div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border bg-white px-4 py-2">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white">Xác nhận xóa</button></div></form></dialog>
                            @endcan
                        @endif
                        @if($type === 'receipt' && $doc->status === 'posted')
                            @can('delete_pharma')
                                <dialog id="revert-receipt-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                    <form method="POST" action="{{ route('admin.pharma.inventory.receipts.revert',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf
                                        <div class="flex items-start gap-4 p-6">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-700">!</div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Hoàn tác ghi sổ?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                                <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu sẽ được chuyển về <strong>Nháp</strong> và đúng số lượng đã nhập của phiếu này sẽ được rút khỏi tồn kho.</p>
                                                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-5 text-amber-900"><strong>Kiểm tra tồn kho:</strong> nếu bất kỳ lô nào không đủ tồn để hoàn tác, hệ thống sẽ hủy toàn bộ thao tác và không thay đổi tồn kho.</div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Hoàn tác ghi sổ</button></div>
                                    </form>
                                </dialog>
                            @endcan
                        @endif
                        @if($type === 'receipt' && $doc->status === 'draft')
                            <dialog id="delete-receipt-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                <form method="POST" action="{{ route('admin.pharma.inventory.receipts.destroy',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf @method('DELETE')
                                    <div class="flex items-start gap-4 p-6">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-xl font-bold text-rose-700">×</div>
                                        <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Xóa phiếu nhập nháp?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                        <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu và toàn bộ chi tiết hàng hóa nháp sẽ bị xóa. Phiếu chưa ghi sổ nên thao tác này không làm thay đổi tồn kho.</p></div>
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Xác nhận xóa</button></div>
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
