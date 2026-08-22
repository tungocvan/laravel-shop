@stack('scripts')
@if(data_get($websiteAppearance ?? [], 'service_worker_enabled', true))
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js').catch(() => {}));
    }
</script>
@endif
