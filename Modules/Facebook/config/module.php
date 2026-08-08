<?php

return array (
  'name' => 'Facebook',
  'type' => 'domain',
  'enabled' => false,
  'depends' => 
  array (
    0 => 'User',
  ),
  'permissions' => 
  array (
    0 => 'facebook.view',
    1 => 'facebook.connect',
    2 => 'facebook.pages.manage',
    3 => 'facebook.posts.view',
    4 => 'facebook.posts.create',
    5 => 'facebook.posts.update',
    6 => 'facebook.posts.delete',
    7 => 'facebook.posts.publish',
    8 => 'facebook.posts.retry',
  ),
);
