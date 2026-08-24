<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ __('Request::exports.pdf_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        .note { margin-bottom: 16px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { width: 30%; background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ __('Request::exports.pdf_title') }}</h1>
    <p class="note">{{ __('Request::exports.pdf_safe_note') }}</p>
    <table>
        @foreach($data as $key => $value)
            <tr>
                <th>{{ $key }}</th>
                <td>{{ is_scalar($value) || $value === null ? (string) ($value ?? '') : '' }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>
