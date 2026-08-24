<!doctype html>
<html lang="en">
<body>
    <p>{{ $message->greeting }}</p>
    @foreach($message->lines as $line)<p>{{ $line }}</p>@endforeach
    <p><a href="{{ $message->actionUrl }}">{{ $message->actionLabel }}</a></p>
</body>
</html>
