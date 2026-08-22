@foreach(($headerLayout[$slot] ?? []) as $component)
    @include($component['view'], $component['config'] ?? [])
@endforeach
