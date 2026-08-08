<?php

return array (
  'name' => 'Website',
  'type' => 'domain',
  'enabled' => true,
  'depends' => 
  array (
    0 => 'User',
    1 => 'Product',
    2 => 'Category',
    3 => 'Post',
    4 => 'Order',
  ),
  'permissions' => 
  array (
    0 => 'view_website',
    1 => 'create_website',
    2 => 'edit_website',
    3 => 'delete_website',
  ),
);
