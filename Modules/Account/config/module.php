<?php

return array (
  'name' => 'Account',
  'type' => 'domain',
  'enabled' => false,
  'depends' => 
  array (
    0 => 'User',
  ),
  'permissions' => 
  array (
    0 => 'view_account',
    1 => 'create_account',
    2 => 'edit_account',
    3 => 'delete_account',
  ),
);
