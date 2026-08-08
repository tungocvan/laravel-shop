<?php

return array (
  'name' => 'Admission',
  'type' => 'domain',
  'enabled' => true,
  'enable_pdf_convert' => false,
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
