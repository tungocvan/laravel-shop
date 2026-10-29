<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Thư viện Demo | Tungocvan</title>
<style>
:root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color-scheme:light;--ink:#162339;--muted:#5e6c80;--line:#dce5f0;--accent:#2259be}
*{box-sizing:border-box}body{margin:0;background:#f5f7fc;color:var(--ink)}main{max-width:1180px;margin:auto;padding:clamp(24px,5vw,64px) 20px 80px}
.eyebrow{font-size:13px;font-weight:800;letter-spacing:.12em;color:var(--accent);text-transform:uppercase}h1{font-size:clamp(30px,5vw,52px);line-height:1.15;margin:14px 0}header p{max-width:680px;color:var(--muted);font-size:17px;line-height:1.65;margin:0 0 32px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,270px),1fr));gap:18px}
a.card{display:flex;flex-direction:column;min-height:195px;padding:24px;background:#fff;border:1px solid var(--line);border-radius:18px;color:inherit;text-decoration:none;box-shadow:0 6px 20px rgba(24,46,84,.035);transition:transform .16s,border-color .16s,box-shadow .16s}
a.card:hover,a.card:focus-visible{transform:translateY(-3px);border-color:#7da6ec;box-shadow:0 10px 30px rgba(24,46,84,.12);outline-offset:3px}
.number{font-size:12px;font-weight:800;color:var(--accent)}h2{font-size:19px;line-height:1.35;margin:16px 0 8px}.description{font-size:14px;color:var(--muted);line-height:1.5;margin:0 0 20px}.action{margin-top:auto;font-weight:700;font-size:14px;color:var(--accent)}
footer{color:var(--muted);font-size:13px;margin-top:32px}
@media(prefers-reduced-motion:reduce){a.card{transition:none}a.card:hover{transform:none}}
</style>
</head>
<body>
<main>
<header><div class="eyebrow">Tungocvan · Demo Hub</div><h1>Khám phá 12 giao diện Demo</h1><p>Chọn một ứng dụng để trải nghiệm. Mỗi Demo hoạt động độc lập và lưu dữ liệu theo cơ chế riêng của ứng dụng.</p></header>
<section class="grid" aria-label="Danh sách Demo">
@foreach ($demos as $demo)
<a class="card" href="{{ route($demo['route']) }}">
<span class="number">DEMO {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
<h2>{{ $demo['title'] }}</h2>
<p class="description">{{ $demo['description'] }}</p>
<span class="action">Mở giao diện <span aria-hidden="true">→</span></span>
</a>
@endforeach
</section>
<footer>Danh mục Demo được quản lý tập trung trong Modules/Demo.</footer>
</main>
</body>
</html>
