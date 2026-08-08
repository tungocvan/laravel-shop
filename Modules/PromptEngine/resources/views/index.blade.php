<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tạo infographic chữ Hán</title>
    <style>
        :root { color-scheme: light; --ink:#211d19; --muted:#756b61; --paper:#f6f0e5; --card:#fffdf9; --red:#9f2f25; --line:#ded3c3; }
        * { box-sizing:border-box; }
        body { margin:0; color:var(--ink); background:linear-gradient(135deg,#eee3d2,#faf7f0 45%,#e7dccb); font-family:Inter,system-ui,sans-serif; min-height:100vh; }
        .shell { width:min(1180px,calc(100% - 32px)); margin:40px auto; }
        header { margin-bottom:24px; }
        h1 { font-family:Georgia,serif; font-size:clamp(30px,4vw,52px); margin:0 0 8px; }
        header p,.hint { color:var(--muted); }
        .grid { display:grid; grid-template-columns:minmax(300px,420px) 1fr; gap:24px; align-items:start; }
        .card { background:rgba(255,253,249,.96); border:1px solid var(--line); border-radius:18px; padding:24px; box-shadow:0 18px 50px rgba(54,42,28,.10); }
        label { display:block; font-size:14px; font-weight:700; margin:0 0 7px; }
        .field { margin-bottom:18px; }
        input,select { width:100%; border:1px solid #cfc2b2; border-radius:10px; padding:12px 13px; background:white; color:var(--ink); font:inherit; }
        #character { font-size:32px; text-align:center; }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        button { width:100%; border:0; border-radius:11px; padding:14px; background:var(--red); color:white; font-size:16px; font-weight:800; cursor:pointer; }
        button:disabled { opacity:.55; cursor:wait; }
        .notice { margin-bottom:18px; padding:12px 14px; border-radius:10px; background:#fff4d9; color:#684b04; }
        .result { min-height:520px; display:grid; place-items:center; text-align:center; }
        .result.has-image { display:block; text-align:left; }
        .placeholder { max-width:430px; color:var(--muted); }
        .spinner { width:50px; height:50px; margin:0 auto 18px; border:4px solid #eadfd1; border-top-color:var(--red); border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .generated { width:100%; max-height:720px; object-fit:contain; border-radius:12px; background:#eee5d8; }
        .actions { display:flex; gap:10px; margin-top:14px; }
        .actions a { display:inline-flex; padding:10px 14px; border-radius:9px; color:white; background:var(--red); text-decoration:none; font-weight:700; }
        .meta { margin-top:16px; padding-top:16px; border-top:1px solid var(--line); color:var(--muted); font-size:14px; }
        .error { color:#8e211b; background:#fff0ee; border:1px solid #e9b9b5; padding:14px; border-radius:10px; }
        @media (max-width:820px) { .grid { grid-template-columns:1fr; } .shell { margin:22px auto; } .result { min-height:320px; } }
    </style>
</head>
<body>
<main class="shell">
    <header>
        <h1>Infographic chữ Hán</h1>
        <p>Nhập một chữ Hán, chọn phong cách và để ChatGPT Image tạo tác phẩm hoàn chỉnh.</p>
    </header>

    <div class="grid">
        <section class="card">
            @unless($imageGenerationEnabled)
                <div class="notice">Tạo ảnh đang tắt. Đặt <code>PROMPT_ENGINE_IMAGE_GENERATION=true</code> trong <code>.env</code> rồi chạy <code>php artisan config:clear</code>.</div>
            @endunless

            <form id="generator-form">
                <div class="field">
                    <label for="character">Chữ Hán</label>
                    <input id="character" name="character" maxlength="2" required value="福" autocomplete="off">
                    <div class="hint">Chỉ nhập đúng một chữ Hán.</div>
                </div>

                <div class="field">
                    <label for="style">Phong cách</label>
                    <select id="style" name="style">
                        @foreach($styles as $style)
                            <option value="{{ $style }}" @selected($style === $defaultStyle)>{{ str($style)->replace('-', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="aspect_ratio">Tỷ lệ</label>
                        <select id="aspect_ratio" name="aspect_ratio">
                            @foreach($aspectRatios as $ratio)
                                <option value="{{ $ratio }}" @selected($ratio === $defaultAspectRatio)>{{ $ratio }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="text_mode">Chế độ chữ</label>
                        <select id="text_mode" name="text_mode">
                            <option value="editable-layout">Vùng chữ trống</option>
                            <option value="rendered">Render chữ</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label><input type="checkbox" id="save" style="width:auto" checked> Lưu kết quả vào database</label>
                </div>

                <input type="hidden" name="platform" value="chatgpt-image">
                <button id="submit-button" type="submit" @disabled(!$imageGenerationEnabled)>Tạo ảnh bằng ChatGPT</button>
            </form>
        </section>

        <section id="result" class="card result" aria-live="polite">
            <div class="placeholder">
                <div style="font-size:82px;font-family:serif">福</div>
                <h2>Ảnh sẽ xuất hiện tại đây</h2>
                <p>Quá trình tạo có thể mất từ vài chục giây đến vài phút. Không đóng trang trong lúc xử lý.</p>
            </div>
        </section>
    </div>
</main>

<script>
    const form = document.getElementById('generator-form');
    const result = document.getElementById('result');
    const button = document.getElementById('submit-button');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        button.disabled = true;
        button.textContent = 'Đang tạo ảnh…';
        result.className = 'card result';
        result.innerHTML = '<div><div class="spinner"></div><h2>ChatGPT đang tạo ảnh</h2><p class="hint">Đang phân tích chữ Hán, dựng bố cục và render hình ảnh…</p></div>';

        const payload = {
            character: document.getElementById('character').value.trim(),
            platform: 'chatgpt-image',
            style: document.getElementById('style').value,
            aspect_ratio: document.getElementById('aspect_ratio').value,
            text_mode: document.getElementById('text_mode').value,
            save: document.getElementById('save').checked,
        };

        try {
            const response = await fetch(@json(url('/api/prompt-engine/generate-image')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const body = await response.json();
            if (!response.ok || !body.success) throw new Error(body.message || firstValidationError(body.errors) || 'Không thể tạo ảnh.');

            const image = body.data.image;
            if (!image || image.status !== 'completed') throw new Error(image?.reason || 'Nhà cung cấp chưa trả về ảnh.');

            result.className = 'card result has-image';
            result.innerHTML = `
                <img class="generated" src="${escapeAttribute(image.url)}" alt="Infographic chữ ${escapeAttribute(payload.character)}">
                <div class="actions"><a href="${escapeAttribute(image.url)}" target="_blank" rel="noopener">Mở ảnh gốc</a></div>
                <div class="meta">${escapeHtml(image.width || '?')} × ${escapeHtml(image.height || '?')} px · ${escapeHtml(image.mime_type)}<br><small>${escapeHtml(image.path)}</small></div>`;
        } catch (error) {
            result.className = 'card result';
            result.innerHTML = `<div class="error"><strong>Tạo ảnh thất bại</strong><br>${escapeHtml(error.message)}</div>`;
        } finally {
            button.disabled = false;
            button.textContent = 'Tạo ảnh bằng ChatGPT';
        }
    });

    function firstValidationError(errors) {
        if (!errors) return null;
        const first = Object.values(errors)[0];
        return Array.isArray(first) ? first[0] : first;
    }
    function escapeHtml(value) { const node = document.createElement('div'); node.textContent = String(value ?? ''); return node.innerHTML; }
    function escapeAttribute(value) { return escapeHtml(value).replaceAll('`', '&#96;'); }
</script>
</body>
</html>
