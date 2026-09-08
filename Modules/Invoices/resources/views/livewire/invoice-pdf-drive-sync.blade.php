<div class="rounded-2xl border border-indigo-200 bg-white shadow-sm">
    @if(in_array($batchStatus['status'] ?? null, ['queued','processing'], true))
        <div wire:poll.2s="refreshBatchStatus" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <div class="mt-1 h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-indigo-100 border-t-indigo-600"></div>
                    <div><h3 class="text-lg font-bold text-slate-900">Đang đồng bộ PDF với Google Drive</h3><p class="mt-1 text-sm text-slate-500">{{ $batchStatus['message'] ?? '' }}</p></div>
                </div>
                @php($total=max(1,(int)($batchStatus['total']??0))) @php($processed=(int)($batchStatus['processed']??0)) @php($percent=min(100,(int)round($processed/$total*100)))
                <div class="mt-5"><div class="mb-2 flex justify-between text-xs font-semibold text-slate-600"><span>{{ $processed }} / {{ (int)($batchStatus['total']??0) }} PDF</span><span>{{ $percent }}%</span></div><div class="h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600" style="width:{{ $percent }}%"></div></div></div>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center"><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Queue</p><p class="font-bold">{{ (int)($batchStatus['completed_chunks']??0) }}/{{ (int)($batchStatus['chunks']??0) }}</p></div><div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-700">Thành công</p><p class="font-bold text-emerald-900">{{ (int)($batchStatus['success']??0) }}</p></div><div class="rounded-xl bg-red-50 p-3"><p class="text-xs text-red-700">Lỗi</p><p class="font-bold text-red-900">{{ (int)($batchStatus['failed']??0) }}</p></div></div>
            </div>
        </div>
    @elseif(in_array($batchStatus['status'] ?? null, ['completed','completed_with_errors','connection_lost'], true))
        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/45 px-4 backdrop-blur-sm">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex h-12 w-12 items-center justify-center rounded-full {{ ($batchStatus['failed']??0)>0 || ($batchStatus['status']??'')==='connection_lost' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} text-2xl font-bold">{{ ($batchStatus['failed']??0)>0 || ($batchStatus['status']??'')==='connection_lost' ? '!' : '✓' }}</div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Đồng bộ Google Drive đã hoàn tất</h3><p class="mt-2 text-sm text-slate-600">{{ $batchStatus['message'] ?? '' }} Thành công {{ (int)($batchStatus['success']??0) }} · Đã có {{ (int)($batchStatus['existing']??0) }} · Lỗi {{ (int)($batchStatus['failed']??0) }}.</p>
                @if(!empty($batchStatus['errors']))<p class="mt-2 text-xs text-amber-700">{{ $batchStatus['errors'][0] }}</p>@endif
                <div class="mt-5 flex justify-end"><button type="button" wire:click="dismissBatch" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Đóng</button></div>
            </div>
        </div>
    @endif

    <div class="border-b border-indigo-100 px-5 py-5 sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">Google Drive — Kho PDF theo tháng</h3><p class="mt-1 text-sm text-slate-500">Đồng bộ hai chiều, không tự ghi đè. Drive: Laravel-Backup/Invoices/PDF/YYYY/MM/purchase|sold.</p></div>@if($year!==''&&$month!=='')<span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">Tháng {{ str_pad($month,2,'0',STR_PAD_LEFT) }}/{{ $year }}</span>@endif</div>
    </div>

    <div class="space-y-4 p-5 sm:p-6">
        @if($notice)<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>@endif
        @if($error)<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ $error }}</div>@endif

        @if($year===''||$month==='')
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-600">Hãy chọn cụ thể <strong>Năm + Tháng</strong> trong Kỳ dữ liệu phía trên để sử dụng đồng bộ PDF Google Drive.</div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase text-slate-500">PDF Local</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format((int)($snapshot['local']??0)) }}</p></div>
                <div class="rounded-xl bg-indigo-50 p-4"><p class="text-xs font-semibold uppercase text-indigo-600">Google Drive</p><p class="mt-1 text-2xl font-bold text-indigo-900">{{ number_format((int)($snapshot['drive']??0)) }}</p></div>
                <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs font-semibold uppercase text-emerald-600">Đã đồng bộ</p><p class="mt-1 text-2xl font-bold text-emerald-900">{{ number_format((int)($snapshot['synced']??0)) }}</p></div>
                <div class="rounded-xl bg-amber-50 p-4"><p class="text-xs font-semibold uppercase text-amber-600">Chờ lên Drive</p><p class="mt-1 text-2xl font-bold text-amber-900">{{ number_format(count($snapshot['upload']??[])) }}</p></div>
                <div class="rounded-xl bg-sky-50 p-4"><p class="text-xs font-semibold uppercase text-sky-600">Chỉ có trên Drive</p><p class="mt-1 text-2xl font-bold text-sky-900">{{ number_format(count($snapshot['restore']??[])) }}</p></div>
            </div>
            @if(count($snapshot['different']??[])>0)<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Có {{ count($snapshot['different']) }} PDF cùng tên nhưng khác kích thước. Hệ thống không tự ghi đè các file này.</div>@endif
            <div class="flex flex-wrap gap-3">
                <button type="button" wire:click="queueUpload" @disabled(!($snapshot['connected']??false)||count($snapshot['upload']??[])===0) class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:bg-slate-300">☁ Đồng bộ {{ count($snapshot['upload']??[]) }} PDF lên Google Drive</button>
                <button type="button" wire:click="queueRestore" @disabled(!($snapshot['connected']??false)||count($snapshot['restore']??[])===0) class="rounded-xl border border-sky-300 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400">↓ Khôi phục {{ count($snapshot['restore']??[]) }} PDF về Local</button>
                <button type="button" wire:click="refreshSnapshot" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Làm mới trạng thái</button>
            </div>
        @endif
    </div>
</div>
