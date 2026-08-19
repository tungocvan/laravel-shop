<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ứng dụng của tôi</title>
</head>
<body>
    <main>
        <h1>Ứng dụng của tôi</h1>
        <p>Client Application Portal đã sẵn sàng. Application Dashboard sẽ được triển khai ở Phase P1.</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Đăng xuất</button>
        </form>
    </main>
</body>
</html>
