{{-- Pharma uses the same component renderer as Default; presentation and layout are scoped by the view composer. --}}
@include('Website::partials.footer', [
    'footerLayout' => $footerLayout,
    'footerPresentation' => $footerPresentation,
    'footerSettings' => $footerSettings,
    'footerColumns' => $footerColumns,
    'socialLinks' => $socialLinks,
    'websiteFeatures' => $websiteFeatures,
])
