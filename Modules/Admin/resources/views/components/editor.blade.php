@props([
    'label' => null,
    'placeholder' => 'Nhập nội dung...',
    'height' => '300px',
    'mode' => 'simple', // simple | full
    'required' => false,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();
    $editorId = 'summernote-' . md5($model);
@endphp

<div wire:ignore class="w-full {{ $attributes->get('class') }}">

    @if ($label)
        <label class="block text-sm font-bold leading-6 text-gray-900 mb-2">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

	    <div x-data="{
	        value: @entangle($model).live,
	        editor: null,

	        init() {
	            const boot = () => {
	                if (!window.jQuery || !$.fn.summernote) {
	                    setTimeout(boot, 100);
	                    return;
	                }

	                if (this.editor) {
	                    return;
	                }

	                // Cấu hình Toolbar theo chế độ
	                let toolbarConfig = this.getToolbar();
	                this.editor = $('#{{ $editorId }}');

	                this.editor.summernote({
	                    placeholder: '{{ $placeholder }}',
	                    tabsize: 2,
	                    height: '{{ $height }}',
	                    toolbar: toolbarConfig,

                    // --- ĐÃ XÓA PHẦN 'icons': {...} ĐỂ DÙNG ICON MẶC ĐỊNH ---
                    // Summernote Lite tự động dùng font mặc định của nó, đảm bảo hiện icon 100%

                    callbacks: {
                        onChange: (contents) => { this.value = contents; },
                        onInit: function() {
                            // Chỉnh font placeholder cho đồng bộ
                            $('.note-placeholder').css('font-family', 'ui-sans-serif, system-ui, sans-serif');
                        }
	                    }
	                });

	                if (this.value) { this.editor.summernote('code', this.value); }

	                this.$watch('value', (newVal) => {
	                    if (newVal !== this.editor.summernote('code')) {
	                        this.editor.summernote('code', newVal);
	                    }
	                });
	            };

	            boot();
	        },

        getToolbar() {
            // CHẾ ĐỘ ĐƠN GIẢN (SIMPLE) - Dùng cho Mô tả ngắn
            if ('{{ $mode }}' === 'simple') {
                return [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ];
            }

            // CHẾ ĐỘ ĐẦY ĐỦ (FULL) - Dùng cho Bài viết
            return [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear', 'strikethrough']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']], // Bỏ 'height' để đỡ rối
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ];
        }
    }" class="relative rounded-lg">
        <style>
            .note-editor.note-frame {
                border: 1px solid #d1d5db !important;
                border-radius: 0.5rem !important;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            }

            .note-editor.note-frame.focus {
                border-color: #4f46e5 !important;
                box-shadow: 0 0 0 2px #c7d2fe !important;
            }

            /* Toolbar nền xám nhẹ, bo góc trên */
            .note-toolbar {
                background-color: #f9fafb !important;
                border-bottom: 1px solid #d1d5db !important;
                border-top-left-radius: 0.5rem;
                border-top-right-radius: 0.5rem;
                padding: 8px !important;
            }

            .note-editing-area {
                border-bottom-left-radius: 0.5rem;
                border-bottom-right-radius: 0.5rem;
                background: white;
            }

            .note-statusbar {
                border-top: 1px solid #f3f4f6 !important;
                border-bottom-left-radius: 0.5rem;
                border-bottom-right-radius: 0.5rem;
                background-color: #f9fafb !important;
            }

            .note-editable {
                font-family: ui-sans-serif, system-ui, sans-serif !important;
                line-height: 1.6 !important;
                font-size: 0.875rem !important;
            }

            /* Style nút bấm phẳng (Flat) */
            .note-btn {
                background: white !important;
                border: 1px solid #e5e7eb !important;
                color: #374151 !important;
                border-radius: 0.375rem !important;
                padding: 0.3rem 0.6rem !important;
                font-size: 13px !important;
                box-shadow: 0 1px 1px rgba(0, 0, 0, 0.05);
            }

            .note-btn:hover,
            .note-btn.active {
                background-color: #eff6ff !important;
                color: #4f46e5 !important;
                border-color: #4f46e5 !important;
            }

            .note-btn.dropdown-toggle::after {
                display: none;
            }

            /* Ẩn mũi tên xấu */

            /* Fix icon mặc định */
            .note-icon-bar {
                margin-bottom: 3px;
            }
        </style>

        <textarea id="{{ $editorId }}" style="display: none;"></textarea>
    </div>

    @error($model)
        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
    @enderror

</div>

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    @endpush
@endonce
