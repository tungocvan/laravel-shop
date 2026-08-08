@props([
    'placeholder' => 'Chọn một mục...',
    'id',
    'options' => [],
])

<div wire:ignore class="w-full">
    <select
        {{ $attributes->merge(['class' => 'w-full']) }}
        id="{{ $id }}"
        x-data="selectSearchComponent({
            id: '{{ $id }}',
            model: '{{ $attributes->wire('model')->value() }}',
            placeholder: '{{ $placeholder }}',
            optionsWire: '{{ $attributes->get('options-wire') }}'
        })"
    >
        {{ $slot }}
    </select>
</div>

@once
<script>
function selectSearchComponent(config) {
    return {
        instance: null,

        init() {

            // 🔥 Destroy nếu đã tồn tại (fix double init)
            if (this.instance) {
                this.instance.destroy();
            }

            this.instance = new TomSelect('#' + config.id, {
                plugins: ['dropdown_input'],
                placeholder: config.placeholder,
                create: false,
                allowEmptyOption: true,

                onChange: (value) => {
                    if (config.model) {
                        @this.set(config.model, value);
                    }
                }
            });
            window.addEventListener('filters-reset', () => {
                if (this.instance) {
                    this.instance.clear(); // Xóa giá trị đang chọn trên giao diện UI
                }
            });
            // ✅ CHỈ watch khi có options-wire
            if (config.optionsWire) {
                this.$watch('$wire.' + config.optionsWire, (newOptions) => {

                    if (!this.instance) return;

                    this.instance.clearOptions();

                    if (newOptions && Object.keys(newOptions).length > 0) {

                        const formatted = Object.values(newOptions).map(item => ({
                            value: item.ward_name || item.name || item,
                            text: item.ward_name || item.name || item
                        }));

                        this.instance.addOptions(formatted);
                    }

                    this.instance.refreshOptions(false);
                });
            }
        }
    }
}
</script>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
.ts-control {
    border-radius: 0.75rem !important;
    padding: 0.75rem !important;
    border: 1px solid #d1d5db !important;
}

.ts-wrapper.focus .ts-control {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
    border-color: #6366f1 !important;
}
</style>
@endonce
