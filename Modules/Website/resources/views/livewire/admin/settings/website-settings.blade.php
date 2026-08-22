<div class="mx-auto max-w-6xl space-y-6 pb-24">
    @php
        $fieldClass = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm transition placeholder:text-gray-400 hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
        $labelClass = 'block text-sm font-semibold text-gray-700';
    @endphp

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-bold text-slate-900">Cài đặt Website</h1><p class="mt-1 text-sm text-slate-500">Quản trị nhận diện, thiết kế toàn site, SEO và cấu hình nâng cao.</p></div>
        <button wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="save">Lưu thay đổi</span><span wire:loading wire:target="save">Đang lưu...</span></button>
    </div>

    <nav class="flex overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        @foreach(['seo'=>'SEO','identity'=>'Nhận diện','design'=>'Thiết kế toàn site','themes'=>'Themes','advanced'=>'Nâng cao'] as $tab=>$label)
            <button wire:click="setTab('{{ $tab }}')" class="whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold {{ $activeTab===$tab?'bg-indigo-50 text-indigo-700':'text-slate-500 hover:bg-slate-50 hover:text-slate-800' }}">{{ $label }}</button>
        @endforeach
    </nav>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @if($activeTab==='seo')
            <div class="grid gap-5">
                <label class="{{ $labelClass }}">SEO title<input wire:model="seoTitle" maxlength="70" class="{{ $fieldClass }}"></label>
                <label class="{{ $labelClass }}">Meta description<textarea wire:model="seoDescription" rows="3" maxlength="170" class="{{ $fieldClass }}"></textarea></label>
                <label class="{{ $labelClass }}">Canonical URL<input wire:model="canonicalUrl" type="url" class="{{ $fieldClass }}"></label>
                <label class="{{ $labelClass }}">Robots<select wire:model="robots" class="{{ $fieldClass }}"><option>index,follow</option><option>index,nofollow</option><option>noindex,follow</option><option>noindex,nofollow</option></select></label>
                <label class="{{ $labelClass }}">OpenGraph image URL<input wire:model="ogImage" class="{{ $fieldClass }}"></label>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-emerald-700">{{ parse_url($canonicalUrl, PHP_URL_HOST) ?: request()->getHost() }}</p><p class="mt-1 text-lg text-blue-700">{{ $seoTitle ?: 'Tiêu đề trang' }}</p><p class="mt-1 text-sm text-slate-600">{{ $seoDescription ?: 'Mô tả trang sẽ xuất hiện tại đây.' }}</p></div>
            </div>
        @elseif($activeTab==='identity')
            <div class="grid gap-5 md:grid-cols-2">
                <label class="{{ $labelClass }} md:col-span-2">Tên thương hiệu<input wire:model="siteName" class="{{ $fieldClass }}"></label>
                <label class="{{ $labelClass }}">Logo<input wire:model="newLogo" type="file" accept="image/*" class="{{ $fieldClass }}"></label>
                <label class="{{ $labelClass }}">Favicon<input wire:model="newFavicon" type="file" accept=".png,.ico,.svg" class="{{ $fieldClass }}"></label>
            </div>
        @elseif($activeTab==='design')
            <div class="space-y-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h2 class="text-lg font-bold text-slate-900">Global Design Tokens</h2><p class="mt-1 text-sm text-slate-500">Các giá trị này áp dụng cho toàn storefront. Config/design.php vẫn là fallback an toàn.</p></div>
                    <button type="button" wire:click="resetDesign" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Khôi phục mặc định</button>
                </div>
                <section class="space-y-4">
                    <div><h3 class="font-bold text-slate-900">Typography</h3><p class="text-sm text-slate-500">Font family và kích thước chữ nền của toàn Website.</p></div>
                    <div class="grid gap-5 lg:grid-cols-2">
                        <label class="{{ $labelClass }}">Body font family<input wire:model="design.typography.font_family_body" class="{{ $fieldClass }}"></label>
                        <label class="{{ $labelClass }}">Heading font family<input wire:model="design.typography.font_family_heading" class="{{ $fieldClass }}"></label>
                        <label class="{{ $labelClass }}">Base font size<input wire:model="design.typography.base_font_size" placeholder="16px" class="{{ $fieldClass }}"></label>
                        <label class="{{ $labelClass }}">Default container<select wire:model="design.layout.default_container" class="{{ $fieldClass }}"><option value="compact">Compact</option><option value="standard">Standard</option><option value="wide">Wide</option><option value="full">Full width</option></select></label>
                    </div>
                </section>
                <section class="space-y-4 border-t border-slate-200 pt-6">
                    <div><h3 class="font-bold text-slate-900">Colors</h3><p class="text-sm text-slate-500">Bộ màu semantic dùng chung cho Website.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach(['primary'=>'Primary','secondary'=>'Secondary','background'=>'Background','surface'=>'Surface','text'=>'Text','muted'=>'Muted','border'=>'Border','success'=>'Success','warning'=>'Warning','danger'=>'Danger'] as $key=>$label)
                            <label class="{{ $labelClass }}">{{ $label }}<div class="mt-1 flex gap-2"><input type="color" wire:model="design.colors.{{ $key }}" class="h-11 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1"><input wire:model="design.colors.{{ $key }}" class="block min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></div></label>
                        @endforeach
                    </div>
                </section>
                <section class="space-y-4 border-t border-slate-200 pt-6">
                    <div><h3 class="font-bold text-slate-900">Container Width</h3><p class="text-sm text-slate-500">Giới hạn chiều rộng dùng bởi các component theo global token.</p></div>
                    <div class="grid gap-5 md:grid-cols-3">
                        @foreach(['compact'=>'Compact','standard'=>'Standard','wide'=>'Wide'] as $key=>$label)
                            <label class="{{ $labelClass }}">{{ $label }}<input wire:model="design.layout.container_width.{{ $key }}" class="{{ $fieldClass }}"></label>
                        @endforeach
                    </div>
                </section>
                <section class="space-y-4 border-t border-slate-200 pt-6">
                    <div><h3 class="font-bold text-slate-900">Border Radius</h3><p class="text-sm text-slate-500">Bo góc semantic cho card, input và surface.</p></div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach(['sm'=>'Small','md'=>'Medium','lg'=>'Large','xl'=>'Extra large'] as $key=>$label)
                            <label class="{{ $labelClass }}">{{ $label }}<input wire:model="design.layout.radius.{{ $key }}" class="{{ $fieldClass }}"></label>
                        @endforeach
                    </div>
                </section>
                <section class="space-y-4 border-t border-slate-200 pt-6">
                    <div><h3 class="font-bold text-slate-900">Tiện ích nổi toàn site</h3><p class="text-sm text-slate-500">Bật/tắt và chọn vị trí an toàn cho Chat Widget và Back to Top. Hiện hỗ trợ hai góc dưới để popup Chat luôn mở đúng viewport.</p></div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-4">
                                <div><div class="font-semibold text-slate-900">Chat Widget</div><div class="mt-1 text-xs text-slate-500">Hiện nút/chat hỗ trợ nổi trên toàn Website.</div></div>
                                <input type="checkbox" wire:model="features.chat_widget" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </label>
                            <label class="mt-4 block text-sm font-semibold text-gray-700">Vị trí hiển thị
                                <select wire:model="features.chat_position" class="{{ $fieldClass }}">
                                    <option value="bottom-left">Góc trái dưới</option>
                                    <option value="bottom-right">Góc phải dưới</option>
                                </select>
                            </label>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-4">
                                <div><div class="font-semibold text-slate-900">Back to Top</div><div class="mt-1 text-xs text-slate-500">Hiện nút về đầu trang sau khi người dùng cuộn xuống.</div></div>
                                <input type="checkbox" wire:model="features.back_to_top" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </label>
                            <label class="mt-4 block text-sm font-semibold text-gray-700">Vị trí hiển thị
                                <select wire:model="features.back_to_top_position" class="{{ $fieldClass }}">
                                    <option value="bottom-left">Góc trái dưới</option>
                                    <option value="bottom-right">Góc phải dưới</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    @if(($features['chat_widget'] ?? true) && ($features['back_to_top'] ?? true) && ($features['chat_position'] ?? 'bottom-right') === ($features['back_to_top_position'] ?? 'bottom-right'))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">Chat Widget và Back to Top đang cùng một góc. Nên chọn hai vị trí khác nhau để tránh chồng nút.</div>
                    @endif
                </section>
            </div>
        @elseif($activeTab==='themes')
            @include('Website::livewire.admin.settings.partials.design-themes')
        @else
            <div class="grid gap-5">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Chỉ quản trị viên có quyền cấu hình Website mới được lưu mã nâng cao. Mã sai có thể ảnh hưởng giao diện.</div>
                <label class="{{ $labelClass }}">Analytics code<textarea wire:model="analyticsCode" rows="6" class="{{ $fieldClass }} font-mono text-xs"></textarea></label>
                <label class="{{ $labelClass }}">Header scripts<textarea wire:model="headerScript" rows="8" class="{{ $fieldClass }} font-mono text-xs"></textarea></label>
            </div>
        @endif

        @if($errors->any())<div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    </div>

    <div class="sticky bottom-4 flex justify-end rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
        <button wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50">Lưu thay đổi</button>
    </div>
</div>
