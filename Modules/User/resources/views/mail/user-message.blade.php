<!doctype html>
<html lang="vi">
<body>
    <p>{{ $mailMessage->greeting }}</p>

    @foreach($mailMessage->lines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @if(filled($mailMessage->actionUrl) && filled($mailMessage->actionLabel))
        <p><a href="{{ $mailMessage->actionUrl }}">{{ $mailMessage->actionLabel }}</a></p>
    @endif
</body>
</html>
