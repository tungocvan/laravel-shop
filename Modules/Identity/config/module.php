<?php

return array (
  'name' => 'Identity',
  'type' => 'domain',
  'enabled' => false,
  'depends' => 
  array (
    0 => 'User',
  ),
  'permissions' => 
  array (
    0 => 'view_identity',
    1 => 'create_identity',
    2 => 'edit_identity',
    3 => 'delete_identity',
    4 => 'import_identity',
    5 => 'export_identity',
  ),
);
