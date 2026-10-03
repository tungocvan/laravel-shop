@php
    $name = $name ?? 'squares-2x2';
    $class = $class ?? 'h-5 w-5';
    $paths = [
        'beaker' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3v5.25l-4.5 7.794A3 3 0 0 0 7.848 20.5h8.304a3 3 0 0 0 2.598-4.456l-4.5-7.794V3m-4.5 0h4.5m-7.125 12h9.75" />',
        'clipboard-document-list' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25H6.375A2.625 2.625 0 0 0 3.75 7.875v11.25a2.625 2.625 0 0 0 2.625 2.625h11.25a2.625 2.625 0 0 0 2.625-2.625V7.875a2.625 2.625 0 0 0-2.625-2.625H15M9 5.25a3 3 0 0 1 6 0M9 5.25A1.5 1.5 0 0 0 10.5 6.75h3A1.5 1.5 0 0 0 15 5.25M8.25 12h.008v.008H8.25V12Zm0 3h.008v.008H8.25V15Zm0 3h.008v.008H8.25V18Zm3-6h4.5m-4.5 3h4.5m-4.5 3h4.5" />',
        'archive-box' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v10.125c0 1.036-.84 1.875-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 17.625V7.5m16.5 0-1.5-3.75H5.25L3.75 7.5m16.5 0H3.75m5.25 3h6" />',
        'banknotes' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75h19.5v10.5H2.25V6.75Zm3 0v10.5m13.5-10.5v10.5M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
        'building-library' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 9h15M6 9v8.25m4-8.25v8.25m4-8.25v8.25m4-8.25v8.25M3 6.75 12 2.25l9 4.5H3Z" />',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6V4.875A1.875 1.875 0 0 1 10.125 3h3.75a1.875 1.875 0 0 1 1.875 1.875V6m-12 0h16.5A1.75 1.75 0 0 1 22 7.75v10.5A1.75 1.75 0 0 1 20.25 20H3.75A1.75 1.75 0 0 1 2 18.25V7.75A1.75 1.75 0 0 1 3.75 6Zm-1.75 5.5c3.25 1.5 6.583 2.25 10 2.25s6.75-.75 10-2.25" />',
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
        'magnifying-glass' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" />',
        'document-chart-bar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625a9 9 0 0 0-9-9Z" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'heart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733C11.285 4.876 9.623 3.75 7.688 3.75 5.099 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />',
        'plus-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'document-text' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 8.25h4.5m-4.5 3h4.5m-4.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625a9 9 0 0 0-9-9Z" />',
        'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.012 1.244h3.22a2.25 2.25 0 0 0 2.012-1.244l.256-.512A2.25 2.25 0 0 1 17.89 13.5h3.86m-19.5 0V6.375c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125V13.5m-19.5 0v6.375c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125V13.5" />',
        'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="m9 12.75 2.25 2.25L15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        'ellipsis-horizontal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />',
        'squares-2x2' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Zm-10 10h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Z" />',
    ];
    $path = $paths[$name] ?? $paths['squares-2x2'];
@endphp

<svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
    {!! $path !!}
</svg>
