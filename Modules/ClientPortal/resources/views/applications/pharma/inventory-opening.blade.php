@extends('ClientPortal::layouts.application')
@section('title', 'Tồn đầu kỳ')
@section('content')
<div class="mx-auto max-w-3xl space-y-5 p-4">
    <a href="{{ route('client.pharma.inventory') }}" class="text-sm font-bold text-indigo-700">← Tồn kho</a>
    <h1 class="text-2xl font-black">Nhập tồn đầu kỳ</h1>
    <p class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">Chỉ nhập tồn đầu kỳ cho lô chưa phát sinh số dư. Import ghi sổ ngay sau khi kiểm tra thành công, không ghi đè lô đã có tồn. Hãy kiểm tra file trước khi gửi.</p>
    @if(session('success')) <div class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="rounded-xl bg-red-50 p-4 text-red-700">{{ $errors->first() }}</div> @endif
    <section class="space-y-4 rounded-2xl border bg-white p-5">
        <a class="inline-flex rounded-xl border border-indigo-300 px-4 py-2 font-bold text-indigo-700" href="{{ route('client.pharma.inventory.opening.template') }}">↓ Tải mẫu Excel XLSX</a>
        <form method="POST" action="{{ route('client.pharma.inventory.opening.import') }}" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Xác nhận ghi sổ tồn đầu kỳ từ file Excel? Không thể nhập lại các lô đã có số dư.');">
            @csrf
            <label class="block text-sm font-bold">Chọn file XLSX/CSV (tối đa 10 MB)
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-2 block w-full rounded-xl border p-3">
            </label>
            <p class="text-xs text-slate-500">Cột bắt buộc: Ma thuoc, So lo, Han dung (YYYY-MM-DD), Ton dau ky. Tối đa 1.000 dòng.</p>
            <button class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Kiểm tra và nhập tồn đầu kỳ</button>
        </form>
    </section>
</div>
@endsection