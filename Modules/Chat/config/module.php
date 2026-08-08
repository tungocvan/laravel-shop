<?php

return array (
  'name' => 'Chat',
  'type' => 'support',
  'enabled' => true,
  'depends' => 
  array (
    0 => 'Admin',
    1 => 'User',
  ),
  'permissions' => 
  array (
    0 => 'view_chat',
    1 => 'create_chat',
    2 => 'edit_chat',
    3 => 'delete_chat',
  ),
);
