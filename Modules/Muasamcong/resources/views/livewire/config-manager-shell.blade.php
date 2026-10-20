<div class="space-y-5">
    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
        Cấu hình mặc định thuộc <code>Modules/Muasamcong/config/muasamcong.php</code>. Các thay đổi được lưu trong bảng <code>muasamcong_settings</code> (cần migration). Không bổ sung hay chỉnh sửa <code>.env</code> từ trang này. Trong giai đoạn chuyển đổi, các biến MUASAMCONG_* cũ vẫn được đọc làm fallback cho đến khi được loại bỏ có kiểm soát.
    </div>
    @include('Muasamcong::livewire.partials.personal-session-manager')
    @include('Muasamcong::livewire.config-manager')
</div>
