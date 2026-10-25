<div class="space-y-4">
    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6" aria-label="Trạng thái cài đặt công nợ">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Trạng thái chức năng và quy tắc công nợ</h2>
                <p class="mt-1 text-sm text-slate-600">Thông tin tham khảo theo phạm vi Finance Demo hiện tại; không phải bảng cấu hình có thể chỉnh sửa.</p>
            </div>
            <span class="rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">Chỉ xem · Không lưu thay đổi</span>
        </div>
        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Giá trị công nợ trên các màn hình báo cáo là <strong>gốc cộng điều chỉnh</strong>; chưa trừ thanh toán hoặc đối trừ. Không sử dụng các số này như dư nợ thực tế hay số dư kế toán đầu/cuối kỳ.
        </p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6" aria-label="Danh mục khả năng công nợ">
        <h3 class="mb-4 font-semibold text-slate-900">Danh mục chức năng</h3>
        <div class="overflow-x-auto">
            <table class="min-w-[720px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-700">
                    <tr><th class="p-3">Chức năng</th><th class="p-3">Trạng thái</th><th class="p-3">Giới hạn và điều kiện</th></tr>
                </thead>
                <tbody>
                    @foreach($capabilities as $capability)
                        <tr class="border-b border-slate-100" wire:key="debt-capability-{{ $loop->index }}">
                            <td class="p-3 font-semibold text-slate-900">{{ $capability['label'] }}</td>
                            <td class="p-3">
                                @if($capability['state'] === 'active')
                                    <span class="inline-block rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800">Đã triển khai có điều kiện</span>
                                @elseif($capability['state'] === 'readonly')
                                    <span class="inline-block rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800">Chỉ đọc</span>
                                @else
                                    <span class="inline-block rounded-full border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-900">Chưa kích hoạt</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-600">{{ $capability['detail'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6" aria-label="Điều hướng và quyền truy cập">
        <h3 class="font-semibold text-slate-900">Quyền và thao tác</h3>
        <p class="mt-2 text-sm text-slate-600"><strong>finance.view</strong> cho phép đọc các màn hình công nợ. Các thao tác tạo, xác nhận và điều chỉnh công nợ thủ công được kiểm tra riêng bằng <strong>finance.debt.manage</strong>. Màn hình này không có nút lưu, bật/tắt quy tắc hay API ghi cấu hình.</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-slate-50" href="{{ route('admin.finance.debts.index', ['section' => 'documents']) }}">Mở Chứng từ</a>
            <a class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-slate-50" href="{{ route('admin.finance.debts.index', ['section' => 'dashboard']) }}">Mở Dashboard</a>
            <a class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-slate-50" href="{{ route('admin.finance.debts.index', ['section' => 'aging']) }}">Mở Tuổi nợ</a>
        </div>
    </section>
</div>
