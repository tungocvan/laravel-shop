<!doctype html>
<html lang="vi">
<body>
    <p>{{ $message->greeting }}</p>

    @foreach($message->lines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @if(filled($message->actionUrl) && filled($message->actionLabel))
        <p><a href="{{ $message->actionUrl }}">{{ $message->actionLabel }}</a></p>
    @endif
</body>
</html>
