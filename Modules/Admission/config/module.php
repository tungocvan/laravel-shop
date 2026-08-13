<?php

return array (
  'name' => 'Admission',
  'type' => 'domain',
  'enabled' => true,
  'enable_pdf_convert' => env('ENABLE_PDF_CONVERT', false),
  'queues' =>
  array (
    0 =>
    array (
      'name' => 'admission-documents',
      'workers' => 1,
      'timeout' => 180,
      'tries' => 3,
      'sleep' => 2,
      'max_jobs' => 100,
      'max_time' => 3600,
      'description' => 'Tạo DOCX/PDF cho hồ sơ tuyển sinh đã duyệt.',
    ),
  ),
  'seeders' => 
  array (
    0 => 'Modules\\Admission\\database\\seeders\\DatabaseSeeder',
  ),
  'permissions' => 
  array (
    0 => 'view_admission',
    1 => 'create_admission',
    2 => 'edit_admission',
    3 => 'delete_admission',
    4 => 'import_admission',
    5 => 'export_admission',
    6 => 'approve_admission',
    7 => 'reject_admission',
    8 => 'download_admission_documents',
    9 => 'manage_admission_locations',
    10 => 'manage_admission_settings',
  ),
  'tables' => 
  array (
    0 => 'admission_locations',
    1 => 'admission_applications',
    2 => 'admission_catalogs',
    3 => 'admission_settings',
  ),
);
