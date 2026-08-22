@php
    $design = $websiteDesign ?? [];
    $typography = $design['typography'] ?? [];
    $colors = $design['colors'] ?? [];
    $layout = $design['layout'] ?? [];
    $fontSize = $typography['font_size'] ?? [];
    $lineHeight = $typography['line_height'] ?? [];
    $containerWidth = $layout['container_width'] ?? [];
    $radius = $layout['radius'] ?? [];
    $shadow = $layout['shadow'] ?? [];
@endphp
<style id="website-design-tokens">
    :root {
        --website-font-body: {!! $typography['font_family_body'] ?? 'ui-sans-serif, system-ui, sans-serif' !!};
        --website-font-heading: {!! $typography['font_family_heading'] ?? 'ui-sans-serif, system-ui, sans-serif' !!};
        --website-font-mono: {!! $typography['font_family_mono'] ?? 'ui-monospace, monospace' !!};
        --website-font-size-base: {{ $typography['base_font_size'] ?? '16px' }};
        --website-font-size-xs: {{ $fontSize['xs'] ?? '0.75rem' }};
        --website-font-size-sm: {{ $fontSize['sm'] ?? '0.875rem' }};
        --website-font-size-md: {{ $fontSize['base'] ?? '1rem' }};
        --website-font-size-lg: {{ $fontSize['lg'] ?? '1.125rem' }};
        --website-font-size-xl: {{ $fontSize['xl'] ?? '1.25rem' }};
        --website-font-size-2xl: {{ $fontSize['2xl'] ?? '1.5rem' }};
        --website-font-size-3xl: {{ $fontSize['3xl'] ?? '1.875rem' }};
        --website-font-size-4xl: {{ $fontSize['4xl'] ?? '2.25rem' }};
        --website-line-height-tight: {{ $lineHeight['tight'] ?? '1.25' }};
        --website-line-height-normal: {{ $lineHeight['normal'] ?? '1.5' }};
        --website-line-height-relaxed: {{ $lineHeight['relaxed'] ?? '1.7' }};

        --website-color-primary: {{ $colors['primary'] ?? '#2563eb' }};
        --website-color-secondary: {{ $colors['secondary'] ?? '#4f46e5' }};
        --website-color-background: {{ $colors['background'] ?? '#f9fafb' }};
        --website-color-surface: {{ $colors['surface'] ?? '#ffffff' }};
        --website-color-text: {{ $colors['text'] ?? '#111827' }};
        --website-color-muted: {{ $colors['muted'] ?? '#6b7280' }};
        --website-color-border: {{ $colors['border'] ?? '#e5e7eb' }};
        --website-color-success: {{ $colors['success'] ?? '#059669' }};
        --website-color-warning: {{ $colors['warning'] ?? '#d97706' }};
        --website-color-danger: {{ $colors['danger'] ?? '#dc2626' }};

        --website-container-compact: {{ $containerWidth['compact'] ?? '1024px' }};
        --website-container-standard: {{ $containerWidth['standard'] ?? '1280px' }};
        --website-container-wide: {{ $containerWidth['wide'] ?? '1440px' }};
        --website-container-full: {{ $containerWidth['full'] ?? '100%' }};
        --website-radius-sm: {{ $radius['sm'] ?? '0.375rem' }};
        --website-radius-md: {{ $radius['md'] ?? '0.5rem' }};
        --website-radius-lg: {{ $radius['lg'] ?? '0.75rem' }};
        --website-radius-xl: {{ $radius['xl'] ?? '1rem' }};
        --website-shadow-soft: {{ $shadow['soft'] ?? '0 1px 3px 0 rgb(0 0 0 / 0.08)' }};
        --website-shadow-medium: {{ $shadow['medium'] ?? '0 4px 12px rgb(0 0 0 / 0.10)' }};
    }
</style>
