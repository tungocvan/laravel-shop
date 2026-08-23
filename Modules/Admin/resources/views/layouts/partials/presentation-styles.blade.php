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
