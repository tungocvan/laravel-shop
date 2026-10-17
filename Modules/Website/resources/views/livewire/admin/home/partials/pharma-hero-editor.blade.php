<section class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm" wire:key="pharma-hero-editor">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Hero Banner — Template Pharma</h2>
            <p class="mt-1 text-sm text-slate-500">Chỉnh sửa Hero trang chủ; không thay đổi thư viện /admin/banners hoặc template Default.</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-800">Xem trang chủ ↗</a>
    </div>
    <div class="grid gap-6 xl:grid-cols-2">
        <div class="space-y-5">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['badge' => 'Nhãn giới thiệu', 'title' => 'Tiêu đề chính', 'button_text' => 'Nhãn nút CTA', 'button_url' => 'Đường dẫn CTA', 'caption' => 'Dòng chú thích'] as $field => $label)
                    <label class="block text-xs font-semibold text-slate-700">{{ $label }}
                        <input type="text" wire:model.live.debounce.400ms="pharmaHero.{{ $field }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                @endforeach
                <label class="block text-xs font-semibold text-slate-700 sm:col-span-2">Mô tả
                    <textarea rows="3" wire:model.live.debounce.400ms="pharmaHero.description" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                </label>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['show_badge' => 'Hiện nhãn', 'show_button' => 'Hiện nút CTA', 'show_caption' => 'Hiện chú thích', 'show_illustration' => 'Hiện minh họa dược phẩm'] as $field => $label)
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <input type="checkbox" wire:model.live="pharmaHero.{{ $field }}" class="rounded border-slate-300 text-emerald-700"> {{ $label }}
                    </label>
                @endforeach
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['background_start' => 'Gradient bắt đầu', 'background_end' => 'Gradient kết thúc', 'text_color' => 'Màu chữ', 'button_background' => 'Nền nút CTA', 'button_color' => 'Chữ nút CTA'] as $field => $label)
                    <label class="text-xs font-semibold text-slate-700">{{ $label }}
                        <input type="color" wire:model.live="pharmaHero.{{ $field }}" class="mt-1 block h-10 w-full rounded-lg border border-slate-300">
                    </label>
                @endforeach
                <label class="text-xs font-semibold text-slate-700">Font tiêu đề
                    <select wire:model.live="pharmaHero.title_font" class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                        @foreach(['inherit' => 'Kế thừa', 'Arial, sans-serif' => 'Arial', 'Roboto, sans-serif' => 'Roboto', 'Inter, sans-serif' => 'Inter', 'system-ui, sans-serif' => 'System UI', 'Georgia, serif' => 'Georgia'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                <h3 class="text-sm font-bold text-slate-800">Hình minh họa Hero</h3>
                <label class="block text-xs font-semibold text-slate-700">Nguồn minh họa
                    <select wire:model.live="pharmaHero.illustration_type" class="mt-1 w-full rounded-lg border border-slate-300 p-2">
                        <option value="default">Icon dược phẩm mặc định</option>
                        <option value="image">Ảnh tải lên</option>
                        <option value="none">Không hiển thị</option>
                    </select>
                </label>
                @if(($pharmaHero['illustration_type'] ?? 'default') === 'image')
                    <label class="block text-xs font-semibold text-slate-700">Tải ảnh (JPG, PNG, WebP, GIF — tối đa 4MB)
                        <input type="file" wire:model="pharmaHeroUpload" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm">
                    </label>
                    @if($pharmaHeroUpload)
                        <img src="{{ $pharmaHeroUpload->temporaryUrl() }}" alt="Xem trước ảnh vừa tải lên" class="h-40 w-full object-contain">
                    @elseif(!empty($pharmaHero['illustration_image']))
                        <img src="{{ asset('storage/'.$pharmaHero['illustration_image']) }}" alt="Ảnh Hero đã lưu" class="h-40 w-full object-contain">
                    @endif
                    @error('pharmaHeroUpload') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                @endif
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['desktop_width' => 'Chiều rộng nội dung Desktop (720–1920px)', 'mobile_width' => 'Chiều rộng Mobile (80–100%)', 'desktop_min_height' => 'Chiều cao tối thiểu Desktop (0–900px)', 'mobile_min_height' => 'Chiều cao tối thiểu Mobile (0–700px)', 'desktop_illustration_size' => 'Kích thước minh họa Desktop (120–600px)', 'mobile_illustration_size' => 'Kích thước minh họa Mobile (80–400px)'] as $field => $label)
                    <label class="text-xs font-semibold text-slate-700">{{ $label }}
                        <input type="number" wire:model.live.debounce.400ms="pharmaHero.{{ $field }}" min="0" max="1920" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                @endforeach
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach(['desktop_title_size' => 'Cỡ tiêu đề Desktop (28–80px)', 'mobile_title_size' => 'Cỡ tiêu đề Mobile (20–52px)', 'desktop_padding' => 'Padding dọc Desktop (0–160px)', 'mobile_padding' => 'Padding dọc Mobile (0–120px)'] as $field => $label)
                    <label class="text-xs font-semibold text-slate-700">{{ $label }}
                        <input type="number" wire:model.live.debounce.400ms="pharmaHero.{{ $field }}" min="0" max="160" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                @endforeach
            </div>
        </div>
        <div class="space-y-3">
            <h3 class="text-sm font-bold text-slate-800">Xem trước Hero (minh họa)</h3>
            <div class="overflow-hidden rounded-xl p-6 sm:p-8" style="background:linear-gradient(115deg, {{ $pharmaHero['background_start'] ?? '#073b48' }}, {{ $pharmaHero['background_end'] ?? '#0c6870' }});color:{{ $pharmaHero['text_color'] ?? '#ffffff' }}">
                @if($pharmaHero['show_badge'] ?? true)
                    <p class="mb-4 inline-block rounded-full border border-current px-3 py-1 text-xs font-bold">{{ $pharmaHero['badge'] ?? '' }}</p>
                @endif
                <h3 class="mb-3 break-words text-3xl font-extrabold" style="font-family:{{ $pharmaHero['title_font'] ?? 'inherit' }}">{{ $pharmaHero['title'] ?? '' }}</h3>
                <p class="mb-5 text-sm leading-relaxed">{{ $pharmaHero['description'] ?? '' }}</p>
                @if($pharmaHero['show_button'] ?? true)
                    <span class="inline-flex rounded-lg px-4 py-2 text-xs font-bold" style="background:{{ $pharmaHero['button_background'] ?? '#ffffff' }};color:{{ $pharmaHero['button_color'] ?? '#047857' }}">{{ $pharmaHero['button_text'] ?? '' }} →</span>
                @endif
                @if($pharmaHero['show_caption'] ?? true)
                    <p class="mt-4 text-xs opacity-80">{{ $pharmaHero['caption'] ?? '' }}</p>
                @endif
            </div>
            <p class="text-xs text-slate-500">Xem trước nội dung/màu sắc. Desktop/Mobile thực tế cần kiểm tra trên trang chủ sau khi lưu. Khoảng cách Header → Banner được quản lý riêng trong Header Settings.</p>
        </div>
    </div>
    @if($pharmaHeroSavedModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="pharma-hero-save-title">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 id="pharma-hero-save-title" class="text-lg font-bold text-emerald-800">Đã lưu Hero Banner thành công</h3>
                <p class="mt-2 text-sm text-slate-600">Cấu hình đã được ghi và xác nhận. Bạn có thể mở trang chủ để xem kết quả.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closePharmaHeroSavedModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Đóng</button>
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Xem trang chủ</a>
                </div>
            </div>
        </div>
    @endif
    @error('pharmaHero') <p class="mt-4 text-sm text-red-700">{{ $message }}</p> @enderror
    <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        <button type="button" wire:click="resetPharmaHero" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Khôi phục mặc định (chưa lưu)</button>
        <button type="button" wire:click="savePharmaHero" wire:loading.attr="disabled" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Lưu Hero Banner</button>
    </div>
</section>
