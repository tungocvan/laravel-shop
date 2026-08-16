@php
    $envDocker = (bool) ($environmentStatus['docker'] ?? false);
    $envComplete = (bool) ($environmentStatus['complete'] ?? false);
    $envMissing = $environmentStatus['missing'] ?? [];
    $envPresent = (int) ($environmentStatus['present'] ?? 0);
    $envTotal = (int) ($environmentStatus['total'] ?? 0);
    $envSnippet = (string) ($environmentStatus['snippet'] ?? '');
@endphp

<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-gray-100 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-6">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-base font-semibold text-gray-900">Kiểm tra biến môi trường</h2>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $envDocker ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $envDocker ? 'Docker runtime' : 'Local / VPS thường' }}
                </span>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $envComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $envComplete ? 'Đầy đủ' : 'Thiếu biến' }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Kiểm tra {{ $envTotal }} biến <code>MUASAMCONG_*</code> bắt buộc trong file <code>.env</code>. Hiện có {{ $envPresent }}/{{ $envTotal }} biến.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="checkEnvironment" wire:loading.attr="disabled" wire:target="checkEnvironment,repairEnvironment"
                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                <span wire:loading.remove wire:target="checkEnvironment">Kiểm tra lại</span>
                <span wire:loading wire:target="checkEnvironment">Đang kiểm tra…</span>
            </button>

            @if (! $envDocker && ! $envComplete)
                <button type="button" wire:click="repairEnvironment" wire:loading.attr="disabled" wire:target="checkEnvironment,repairEnvironment"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="repairEnvironment">Bổ sung biến còn thiếu</span>
                    <span wire:loading wire:target="repairEnvironment">Đang cập nhật…</span>
                </button>
            @endif
        </div>
    </div>

    <div class="space-y-4 p-4 sm:p-6">
        @if ($envComplete)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Tất cả biến môi trường bắt buộc của module Mua sắm công đã tồn tại trong <code>.env</code>.
            </div>
        @else
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Thiếu <strong>{{ count($envMissing) }}</strong> biến. Các biến secret như token/cookie chỉ được tạo placeholder rỗng, không tự sinh credential.
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800">Biến còn thiếu</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach (array_keys($envMissing) as $key)
                        <code class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-700">{{ $key }}</code>
                    @endforeach
                </div>
            </div>

            @if ($envSnippet !== '')
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Nội dung có thể copy vào .env</h3>
                    <pre class="mt-2 max-h-80 overflow-auto whitespace-pre-wrap rounded-xl bg-gray-950 p-4 text-xs leading-6 text-gray-100">{{ $envSnippet }}</pre>
                </div>
            @endif
        @endif

        @if ($envDocker)
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                <p class="font-semibold">Docker VPS: không sửa .env bên trong container</p>
                <p class="mt-1 text-sky-800">
                    Hãy cập nhật file <code>.env</code> ở source/host dùng để build image. Sau đó rebuild và recreate container để giá trị mới trở thành cấu hình bền vững.
                </p>
                <div class="mt-3 rounded-lg bg-white/80 p-3">
                    <p class="font-medium">Ví dụ khi deployment dùng Docker Compose:</p>
                    <pre class="mt-2 overflow-x-auto text-xs leading-6">docker compose build --no-cache
docker compose up -d</pre>
                    <p class="mt-2 text-xs text-sky-700">Nếu VPS dùng tên file Compose hoặc command deploy khác, chạy lệnh build/redeploy tương ứng của hệ thống đó.</p>
                </div>
            </div>
        @elseif (! $envComplete)
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                Với Local/VPS thường, nút <strong>Bổ sung biến còn thiếu</strong> chỉ thêm key chưa tồn tại và giữ nguyên mọi giá trị đã có trong <code>.env</code>.
            </div>
        @endif
    </div>
</div>
