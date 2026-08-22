<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="{{ $websiteAppearance['theme_color'] ?? '#0f172a' }}">
<meta name="application-name" content="{{ $websiteAppearance['application_name'] ?? $siteName ?? 'FlexBiz' }}">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $websiteAppearance['apple_status_bar_style'] ?? 'default' }}">
<meta name="apple-mobile-web-app-title" content="{{ $websiteAppearance['apple_title'] ?? $siteName ?? 'FlexBiz' }}">
@if(data_get($websiteAppearance ?? [], 'manifest_enabled', true))
    <link rel="manifest" href="{{ route('website.manifest') }}">
@endif

@php
    $faviconExtension = strtolower(pathinfo((string) $siteFavicon, PATHINFO_EXTENSION));
    $faviconType = match ($faviconExtension) {
        'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
        default => 'image/png',
    };
@endphp
@if ($siteFavicon)
    <link id="site-favicon" rel="icon" type="{{ $faviconType }}" href="{{ str_starts_with($siteFavicon, 'http') ? $siteFavicon : asset('storage/' . $siteFavicon).'?v='.md5($siteFavicon) }}">
@endif

<title>@yield('title', $websiteSeo['title'] ?? 'HOMEPAGE')</title>
<meta name="description" content="@yield('meta_description', $websiteSeo['description'] ?? '')">
<meta name="robots" content="{{ $websiteSeo['robots'] ?? 'index,follow' }}">
<link rel="canonical" href="@yield('canonical', $websiteSeo['canonical'] ?? url()->current())">
<meta property="og:type" content="website">
<meta property="og:title" content="@yield('title', $websiteSeo['title'] ?? 'HOMEPAGE')">
<meta property="og:description" content="@yield('meta_description', $websiteSeo['description'] ?? '')">
<meta property="og:url" content="@yield('canonical', $websiteSeo['canonical'] ?? url()->current())">
@if(!empty($websiteSeo['image']))
    <meta property="og:image" content="{{ str_starts_with($websiteSeo['image'], 'http') ? $websiteSeo['image'] : asset('storage/'.$websiteSeo['image']) }}">
@endif
