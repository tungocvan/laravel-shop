@extends('Admin::layouts.master')

@section('title', 'Bổ sung KQLCNT '.$item->notify_no)

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công · Phục hồi KQLCNT</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Bổ sung KQLCNT · {{ $item->notify_no }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $item->bid_name ?: data_get($item->raw_payload, 'bidName', 'Gói thầu') }}</p>
        </div>
        <a href="{{ route('muasamcong.contractors.kqlcnt-recovery', $contractorSearch) }}" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">← Về Phục hồi & Export</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 font-bold text-indigo-700">1</div>
            <h2 class="mt-3 text-lg font-bold text-gray-900">Tải Excel hiện trạng</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">File đã khóa theo <strong>{{ $item->notify_no }}</strong> và điền sẵn toàn bộ dữ liệu hệ thống đang có. Chỉ chỉnh hoặc bổ sung các ô còn thiếu; không cần nhập lại dữ liệu đã biết.</p>
            <div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600">
                <div><strong>{{ number_format(count($rows)) }}</strong> dòng hiện trạng sẽ được đưa vào file.</div>
                <div class="mt-1">Nhà thầu: {{ $contractorSearch->contractor_name ?: $contractorSearch->contractor_code }}</div>
            </div>
            <a href="{{ route('muasamcong.contractors.kqlcnt-recovery.supplement.download', [$contractorSearch, $item->notify_no]) }}" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tải Excel TBMT {{ $item->notify_no }}</a>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 font-bold text-emerald-700">2</div>
            <h2 class="mt-3 text-lg font-bold text-gray-900">Import file đã bổ sung</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600">Sau khi chỉnh Excel, tải file trở lại đây. Hệ thống tự khóa mọi dòng vào <strong>{{ $item->notify_no }}</strong>, tự nhận mapping của file chuẩn và chuyển thẳng sang Preview để kiểm tra trước khi ghi DB.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('muasamcong.contractors.kqlcnt-recovery.supplement.upload', [$contractorSearch, $item->notify_no]) }}" class="mt-4 space-y-3">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm">
                <button class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Upload & kiểm tra Preview</button>
            </form>
            <p class="mt-3 text-xs leading-5 text-gray-500">Import chỉ bổ sung/phục hồi dữ liệu đã lưu. Không xóa snapshot API. Conflict vẫn phải được xác nhận ở bước Preview.</p>
        </section>
    </div>
</div>
@endsection
