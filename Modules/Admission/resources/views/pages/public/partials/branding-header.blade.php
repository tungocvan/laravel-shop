@php
    $websiteLogo = 'logo.png';
    $admissionFallbackLogo = 'admission/img/logo.png';
    $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->exists($websiteLogo)
        ? $websiteLogo
        : $admissionFallbackLogo;
    $logoUrl = asset('storage/'.$logoPath);
@endphp

<div class="flex items-center gap-4 mb-8 border-b pb-6">
    <img src="{{ $logoUrl }}"
         alt="{{ $logoAlt ?? 'Logo' }}"
         class="w-16 h-16 object-contain rounded-xl border border-gray-200 shadow-sm">

    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-blue-900 tracking-tight">
            {{ $title }}
        </h1>

        <p class="text-gray-600 mt-1 text-sm sm:text-base">
            {{ $description }}
        </p>
    </div>
</div>
