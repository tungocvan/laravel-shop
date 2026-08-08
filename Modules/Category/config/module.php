<?php

return array (
  'name' => 'Category',
  'type' => 'support',
  'enabled' => true,
  'seeders' => 
  array (
    0 => 'Database\\Seeders\\CategoryTypeSeeder',
  ),
  'permissions' => 
  array (
    0 => 'view_category',
    1 => 'create_category',
    2 => 'edit_category',
    3 => 'delete_category',
  ),
);
