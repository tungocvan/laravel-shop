@php
    $adminDesignVariables = app(\Modules\Admin\Services\AdminDesignService::class)->cssVariables();
@endphp

<style id="admin-design-tokens">
    :root {
@foreach ($adminDesignVariables as $name => $value)
        {{ $name }}: {{ $value }};
@endforeach
    }
</style>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('admin-design-preview', (event) => {
            const variables = event?.variables ?? event?.[0]?.variables ?? event?.[0] ?? {};

            Object.entries(variables).forEach(([name, value]) => {
                if (typeof name === 'string' && name.startsWith('--admin-')) {
                    document.documentElement.style.setProperty(name, value);
                }
            });
        });
    });
</script>
