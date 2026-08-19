<div>
    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 mt-8">
        <div class="mb-6 pb-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-lg font-bold text-gray-800">Google Drive Backup</h3>
                    @if(($status['connected'] ?? false))
                        <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Đã kết nối</span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-bold text-gray-600"><span class="w-2 h-2 rounded-full bg-gray-400"></span> Chưa kết nối</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-1">Kết nối tài khoản Google để lưu bản backup database lên thư mục riêng của hệ thống.</p>
            </div>
            @if(($status['connected'] ?? false))
                <div class="text-xs text-gray-500 lg:text-right"><div class="font-bold text-gray-700">{{ $status['email'] ?: 'Tài khoản Google' }}</div>@if(!empty($status['last_checked_at']))<div>Kiểm tra gần nhất: {{ $status['last_checked_at'] }}</div>@endif</div>
            @endif
        </div>

        @unless($canUpdate)
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Bạn đang ở chế độ chỉ xem. Cần quyền <code>system.env.update</code> để lưu cấu hình hoặc thay đổi kết nối Google Drive.</div>
        @endunless

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 space-y-5">
                <div class="rounded-xl border border-gray-200 p-5">
                    <div class="mb-4"><h4 class="text-sm font-black text-gray-800 uppercase tracking-wide">1. OAuth Application</h4><p class="text-xs text-gray-500 mt-1">Tạo OAuth Client loại Web application trong Google Cloud Console và khai báo Redirect URI đúng như bên dưới.</p></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Google Client ID</label><input type="text" wire:model="form.GOOGLE_DRIVE_CLIENT_ID" autocomplete="off" @disabled(!$canUpdate) placeholder="xxxxxxxx.apps.googleusercontent.com" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">@error('form.GOOGLE_DRIVE_CLIENT_ID') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                        <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Google Client Secret mới</label><input type="password" wire:model="form.GOOGLE_DRIVE_CLIENT_SECRET" autocomplete="new-password" @disabled(!$canUpdate) placeholder="Để trống để giữ Client Secret hiện tại" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100"><p class="mt-1 text-xs text-gray-500">Client Secret hiện tại không được tải ngược ra trình duyệt.</p>@error('form.GOOGLE_DRIVE_CLIENT_SECRET') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                        <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Authorized Redirect URI</label><input type="url" wire:model="form.GOOGLE_DRIVE_REDIRECT_URI" @disabled(!$canUpdate) class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100"><p class="mt-1 text-xs text-gray-500">Copy chính xác URI này vào Google Cloud Console → OAuth Client → Authorized redirect URIs.</p>@error('form.GOOGLE_DRIVE_REDIRECT_URI') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                        <div class="md:col-span-2"><label class="block text-sm font-semibold text-gray-700 mb-1">Tên thư mục Backup</label><input type="text" wire:model="form.GOOGLE_DRIVE_FOLDER_NAME" @disabled(!$canUpdate) class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary/20 outline-none disabled:bg-gray-100">@error('form.GOOGLE_DRIVE_FOLDER_NAME') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                    </div>
                    <div class="mt-5 pt-5 border-t border-gray-100 flex flex-wrap items-center gap-3">
                        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" @disabled(!$canUpdate) class="px-5 py-2.5 bg-primary text-white text-sm font-bold rounded-lg shadow hover:bg-primary/90 transition disabled:opacity-50 disabled:cursor-not-allowed"><span wire:loading.remove wire:target="save">LƯU CẤU HÌNH GOOGLE</span><span wire:loading wire:target="save">ĐANG LƯU...</span></button>
                        @if($configured && !($status['connected'] ?? false))<a href="{{ route('admin.system.settings.cloud.google.connect') }}" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-lg shadow hover:bg-blue-700 transition">KẾT NỐI GOOGLE DRIVE</a>@endif
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-5">
                    <div class="mb-4"><h4 class="text-sm font-black text-gray-800 uppercase tracking-wide">2. Trạng thái kết nối</h4><p class="text-xs text-gray-500 mt-1">Hệ thống chỉ xin quyền <code>drive.file</code> để quản lý file do ứng dụng tạo hoặc được cấp quyền.</p></div>
                    @if(($status['connected'] ?? false))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4"><div class="text-xs font-bold uppercase tracking-wide text-gray-400">Tài khoản</div><div class="mt-1 font-semibold text-gray-800 break-all">{{ $status['email'] ?: 'Đã xác thực' }}</div></div>
                            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4"><div class="text-xs font-bold uppercase tracking-wide text-gray-400">Thư mục</div><div class="mt-1 font-semibold text-gray-800">{{ $status['folder_name'] ?? 'Laravel-Backup' }}</div>@if(!empty($status['folder_id']))<div class="mt-1 text-xs text-gray-500 break-all">ID: {{ $status['folder_id'] }}</div>@endif</div>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <button wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection" @disabled(!$canUpdate) class="px-4 py-2 rounded-lg bg-gray-800 text-white text-xs font-bold hover:bg-black transition disabled:opacity-50"><span wire:loading.remove wire:target="testConnection">KIỂM TRA KẾT NỐI</span><span wire:loading wire:target="testConnection">ĐANG KIỂM TRA...</span></button>
                            @if(!empty($status['folder_id']))<a href="https://drive.google.com/drive/folders/{{ $status['folder_id'] }}" target="_blank" rel="noopener noreferrer" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-xs font-bold hover:bg-gray-50 transition">MỞ THƯ MỤC DRIVE</a>@endif
                            <button wire:click="disconnect" wire:confirm="Ngắt kết nối sẽ xóa token Google Drive đã lưu. Bạn chắc chắn muốn tiếp tục?" wire:loading.attr="disabled" wire:target="disconnect" @disabled(!$canUpdate) class="px-4 py-2 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-bold hover:bg-red-100 transition disabled:opacity-50">NGẮT KẾT NỐI</button>
                        </div>
                    @else
                        <div class="rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 p-8 text-center"><div class="text-sm font-bold text-gray-700">Chưa có tài khoản Google Drive được kết nối.</div><p class="text-xs text-gray-500 mt-2">Lưu Client ID / Client Secret trước, sau đó bấm “Kết nối Google Drive”.</p></div>
                    @endif
                </div>

                <details class="group rounded-xl border border-indigo-200 bg-indigo-50/40 p-5">
                    <summary class="cursor-pointer list-none flex items-center justify-between gap-4"><div><h4 class="text-sm font-black text-indigo-950 uppercase tracking-wide">Hướng dẫn kết nối Google Drive / thêm Test User</h4><p class="mt-1 text-xs text-indigo-700">Dùng khi triển khai website mới, đổi Google Cloud Project, chạy VPS/Docker hoặc kết nối tài khoản Google khác.</p></div><span class="text-xs font-bold text-indigo-700 group-open:hidden">XEM HƯỚNG DẪN</span><span class="text-xs font-bold text-indigo-700 hidden group-open:inline">THU GỌN</span></summary>
                    <div class="mt-5 space-y-5 text-sm text-gray-700">
                        <div><div class="font-bold text-gray-900">A. Chuẩn bị Google Cloud</div><ol class="mt-2 list-decimal pl-5 space-y-1.5 text-xs leading-5"><li>Mở Google Cloud Console và chọn/tạo Project dùng cho website.</li><li>Vào APIs & Services → Library, tìm <strong>Google Drive API</strong> và chọn Enable.</li><li>Google OAuth hoạt động không đồng nghĩa Drive API đã bật; Google Drive API phải được Enable riêng trong cùng Project.</li><li>Vào Google Auth Platform. Nếu đang phát triển, có thể giữ Publishing status là <strong>Testing</strong>.</li></ol></div>
                        <div><div class="font-bold text-gray-900">B. Thêm tài khoản được phép test</div><ol class="mt-2 list-decimal pl-5 space-y-1.5 text-xs leading-5"><li>Google Auth Platform → <strong>Audience</strong>.</li><li>Tại <strong>Test users</strong>, chọn Add users.</li><li>Nhập Gmail sẽ dùng để kết nối Drive rồi Save.</li><li>Nếu gặp lỗi <code>403 access_denied</code>, kiểm tra Gmail đã nằm trong Test users.</li></ol></div>
                        <div><div class="font-bold text-gray-900">C. OAuth Client</div><ol class="mt-2 list-decimal pl-5 space-y-1.5 text-xs leading-5"><li>Google Auth Platform → Clients → Create client → <strong>Web application</strong>.</li><li>Copy Client ID/Secret vào phần trên.</li><li>Authorized redirect URI phải khớp chính xác URL đang hiển thị.</li><li>Không xóa callback Google Login nếu website đang dùng đăng nhập Google.</li></ol></div>
                        <div><div class="font-bold text-gray-900">D. Kết nối</div><ol class="mt-2 list-decimal pl-5 space-y-1.5 text-xs leading-5"><li>Lưu cấu hình Google rồi bấm Kết nối Google Drive.</li><li>Đăng nhập đúng Gmail tester.</li><li>Trong chế độ Testing, có thể chọn Continue ở màn hình cảnh báo chưa verified.</li><li>Scope <code>drive.file</code> do code tự yêu cầu, không thêm vào .env.</li></ol></div>
                        <div class="rounded-xl border border-sky-200 bg-white p-4"><div class="font-bold text-sky-950">E. VPS / Docker</div><ol class="mt-2 list-decimal pl-5 space-y-2 text-xs leading-5"><li>Dùng domain public HTTPS cho Redirect URI, không dùng tên container/IP nội bộ.</li><li>Đảm bảo <code>APP_URL</code> production đúng HTTPS và reverse proxy forward đúng scheme.</li><li>Biến Google Drive phải nằm trong nguồn environment thực sự được container nạp.</li><li>Sau thay đổi environment: recreate/restart container nếu cần và chạy <code>php artisan optimize:clear</code>.</li><li>Queue worker và scheduler phải chạy bằng cùng environment/database/storage với web container.</li><li>Không commit Client Secret/token vào Git hoặc Docker image.</li></ol><div class="mt-3 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-5 text-sky-900"><strong>Kiểm tra nhanh:</strong> <code>php artisan optimize:clear</code> → <code>php artisan route:list --path=admin/system</code> → mở tab Lưu trữ Cloud → Kiểm tra kết nối.</div></div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-900"><strong>Lưu ý:</strong> Không chia sẻ Client Secret, access token hoặc refresh token.</div>
                    </div>
                </details>
            </div>

            <div class="space-y-5">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-5"><h4 class="text-sm font-black text-blue-900">Phạm vi hiện tại</h4><ul class="mt-3 space-y-2 text-xs text-blue-800 list-disc pl-5"><li>OAuth Google Drive.</li><li>Backup/upload database.</li><li>Duyệt và restore backup remote.</li><li>Lịch backup và retention.</li></ul></div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5"><h4 class="text-sm font-black text-amber-900">Lưu ý bảo mật</h4><p class="mt-2 text-xs leading-5 text-amber-800">Access token và refresh token được mã hóa bằng APP_KEY trước khi lưu trong bảng settings. Không hiển thị token ra giao diện quản trị.</p></div>
            </div>
        </div>
    </div>

    @include('System::livewire.settings.storage-automation')
</div>
