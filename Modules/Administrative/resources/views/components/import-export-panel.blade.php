<div class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
    @livewire('shared.import-export.panel', [
        'serviceClass' => \Modules\Administrative\Services\ImportExport::class,
        'title' => 'Import / Export hồ sơ hành chính',
        'description' => 'Tải file mẫu, kiểm tra dry-run, import Excel/CSV hoặc export hồ sơ theo bộ lọc hiện tại.',
        'filters' => $filters,
        'permission' => 'administrative.submission.import_export',
    ], key('administrative-submission-import-export'))
</div>
