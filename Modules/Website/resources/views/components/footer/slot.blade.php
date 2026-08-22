@foreach(($footerLayout[$slot] ?? []) as $component)
    @include($component['view'], ['componentConfig' => $component['config'] ?? []])
@endforeach
