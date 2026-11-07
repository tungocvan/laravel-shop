@extends('Admin::layouts.master')
@section('content')
<div class="p-4 md:p-6 space-y-4">
    <h1 class="text-xl font-bold">Xem trước Import Excel KQLCNT</h1>
    <p class="text-sm text-gray-600">Hỗ trợ file xuất 36 cột cũ và 38 cột mới. File cũ chỉ cập nhật khi nhận dạng đúng duy nhất một bản ghi. Dòng lỗi được bỏ qua; không xóa bản ghi vắng mặt. Phiên xác nhận hết hạn sau 20 phút.</p>
    <div class="rounded-lg border bg-white p-4">Hợp lệ: {{ $preview['valid'] }} · Lỗi: {{ $preview['errors'] }}</div>
    <div class="overflow-x-auto rounded-lg border bg-white"><table class="min-w-full text-sm"><thead><tr><th class="p-3 text-left">Dòng</th><th class="p-3 text-left">ID</th><th class="p-3 text-left">Tên thuốc</th><th class="p-3 text-left">Trạng thái</th></tr></thead><tbody>
    @foreach($preview['rows'] as $row)<tr class="border-t"><td class="p-3">{{ $row['line'] }}</td><td class="p-3">{{ $row['id'] }}</td><td class="p-3">{{ $row['data']['medicine_name'] }}</td><td class="p-3">{{ $row['errors'] ? implode('; ', $row['errors']) : 'Sẵn sàng cập nhật' }}</td></tr>@endforeach
    </tbody></table></div>
    <form method="POST" action="{{ route('muasamcong.kqlcnt-awards.import.confirm') }}" class="flex flex-wrap gap-3 items-center">@csrf
        <input type="hidden" name="token" value="{{ $preview['token'] }}">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white disabled:opacity-50" @disabled($preview['valid'] === 0)>Xác nhận cập nhật {{ $preview['valid'] }} dòng</button>
        <a href="{{ route('muasamcong.kqlcnt-awards.index') }}" class="rounded-lg border px-4 py-2">Hủy</a>
    </form>
</div>
@endsection
