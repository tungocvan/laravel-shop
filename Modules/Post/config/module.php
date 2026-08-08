<?php

return array (
  'name' => 'Post',
  'type' => 'domain',
  'enabled' => true,
  'depends' => 
  array (
    0 => 'User',
    1 => 'Category',
    2 => 'Shared',
  ),
  'permissions' => 
  array (
    0 => 'view_post',
    1 => 'create_post',
    2 => 'edit_post',
    3 => 'delete_post',
  ),
);
