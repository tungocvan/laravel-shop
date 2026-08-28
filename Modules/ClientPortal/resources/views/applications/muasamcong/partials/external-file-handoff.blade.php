<script>
(() => {
    const isInstalledPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (!isInstalledPwa) return;

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const filenameFromResponse = (response, url) => {
        const disposition = response.headers.get('content-disposition') || '';
        const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8Match?.[1]) {
            try {
                return decodeURIComponent(utf8Match[1].trim().replace(/^"|"$/g, ''));
            } catch (_) {
                // Fall through to the plain filename/fallback.
            }
        }

        const plainMatch = disposition.match(/filename="?([^";]+)"?/i);
        if (plainMatch?.[1]) return plainMatch[1].trim();

        return url.includes('/pdf/download') ? 'bang-gia.pdf' : 'bang-gia.xlsx';
    };

    const createHandoffPanel = () => {
        const overlay = document.createElement('div');
        overlay.dataset.pwaFileHandoffOverlay = '1';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.style.cssText = [
            'position:fixed',
            'inset:0',
            'z-index:2147483647',
            'display:flex',
            'align-items:flex-end',
            'justify-content:center',
            'padding:1rem',
            'background:rgba(15,23,42,.42)',
        ].join(';');

        overlay.innerHTML = `
            <div style="width:min(100%,30rem);border-radius:1.25rem;background:#fff;padding:1.1rem;box-shadow:0 24px 60px rgba(15,23,42,.24)">
                <div style="font-size:1rem;font-weight:800;color:#0f172a">Mở tệp</div>
                <p data-pwa-file-handoff-status aria-live="polite" style="margin:.45rem 0 0;font-size:.875rem;line-height:1.45;color:#475569">Đang chuẩn bị tệp…</p>
                <div style="display:flex;gap:.65rem;margin-top:1rem">
                    <button type="button" data-pwa-file-handoff-close style="flex:1;min-height:2.75rem;border:1px solid #cbd5e1;border-radius:.8rem;background:#fff;color:#334155;font-weight:700">Đóng</button>
                    <button type="button" data-pwa-file-handoff-share disabled style="flex:1.4;min-height:2.75rem;border:0;border-radius:.8rem;background:#0f172a;color:#fff;font-weight:800;opacity:.45">Mở / Chia sẻ tệp</button>
                </div>
            </div>`;

        document.body.appendChild(overlay);

        const status = overlay.querySelector('[data-pwa-file-handoff-status]');
        const shareButton = overlay.querySelector('[data-pwa-file-handoff-share]');
        const closeButton = overlay.querySelector('[data-pwa-file-handoff-close]');
        const close = () => overlay.remove();

        closeButton.addEventListener('click', close);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) close();
        });

        return {overlay, status, shareButton, close};
    };

    const prepareFile = async (link) => {
        const panel = createHandoffPanel();

        try {
            const response = await fetch(link.href, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Accept': '*/*'},
            });

            if (!response.ok) throw new Error(`file-handoff-http-${response.status}`);

            const blob = await response.blob();
            const filename = filenameFromResponse(response, link.href);
            const file = new File([blob], filename, {
                type: blob.type || response.headers.get('content-type') || 'application/octet-stream',
            });
            const shareData = {files: [file], title: filename};
            const canShareFiles = typeof navigator.share === 'function'
                && (typeof navigator.canShare !== 'function' || navigator.canShare(shareData));

            if (!canShareFiles) {
                panel.status.innerHTML = 'Thiết bị này không hỗ trợ chia sẻ tệp trực tiếp từ PWA. Hãy mở trang bằng trình duyệt để tải tệp.';
                return;
            }

            panel.status.innerHTML = `Tệp <strong>${escapeHtml(filename)}</strong> đã sẵn sàng. Chạm <strong>Mở / Chia sẻ tệp</strong> để chọn Excel, Tệp hoặc ứng dụng phù hợp.`;
            panel.shareButton.disabled = false;
            panel.shareButton.style.opacity = '1';

            panel.shareButton.addEventListener('click', async () => {
                panel.shareButton.disabled = true;
                try {
                    await navigator.share(shareData);
                    panel.close();
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        panel.close();
                        return;
                    }

                    panel.status.textContent = 'Không thể chuyển tệp sang ứng dụng khác. Vui lòng thử lại.';
                    panel.shareButton.disabled = false;
                }
            }, {once: true});
        } catch (error) {
            panel.status.textContent = 'Không thể chuẩn bị tệp. Vui lòng kiểm tra kết nối hoặc quyền truy cập rồi thử lại.';
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-pwa-file-handoff]');
        if (!link || event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        prepareFile(link);
    }, true);
})();
</script>
