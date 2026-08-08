<script>
    window.APP_CONFIG = window.APP_CONFIG || {};
    window.APP_CONFIG.realtime = @json(app(\App\Services\RealtimeManager::class)->browserConfig());
</script>
