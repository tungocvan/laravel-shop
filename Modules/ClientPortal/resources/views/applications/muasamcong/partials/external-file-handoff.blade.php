<script>
(() => {
    const isInstalledPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (!isInstalledPwa) return;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-pwa-file-handoff]');
        if (!link || event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const externalWindow = window.open(link.href, '_blank', 'noopener');
        if (!externalWindow) {
            window.location.assign(link.href);
        }
    }, true);
})();
</script>
