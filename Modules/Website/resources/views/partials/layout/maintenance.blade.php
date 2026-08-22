<section class="mx-auto flex min-h-[50vh] max-w-3xl items-center justify-center px-4 py-12 text-center">
    <div class="w-full rounded-2xl border border-website-border bg-website-surface p-8 shadow-website-soft sm:p-12">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-2xl text-amber-700">⚙</div>
        <h1 class="mt-5 font-website-heading text-2xl font-bold text-website-text sm:text-3xl">
            {{ data_get($websiteShell ?? [], 'maintenance.title', 'Website đang được bảo trì') }}
        </h1>
        <p class="mx-auto mt-4 max-w-2xl whitespace-pre-line text-sm leading-7 text-website-muted sm:text-base">{{ data_get($websiteShell ?? [], 'maintenance.message', 'Chúng tôi đang cập nhật hệ thống để phục vụ bạn tốt hơn. Vui lòng quay lại sau.') }}</p>
    </div>
</section>
