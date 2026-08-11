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
    // Legacy permissions retained for backward compatibility.
    0 => 'view_website',
    1 => 'create_website',
    2 => 'edit_website',
    3 => 'delete_website',

    // Website capabilities.
    4 => 'website.view',
    5 => 'website.home.manage',
    6 => 'website.menu.manage',
    7 => 'website.footer.manage',
    8 => 'website.banner.manage',
    9 => 'website.settings.manage',

    // Marketing capabilities currently hosted by Website.
    10 => 'marketing.coupon.view',
    11 => 'marketing.coupon.manage',
    12 => 'marketing.flash-sale.view',
    13 => 'marketing.flash-sale.manage',

    // Customer capabilities currently hosted by Website.
    14 => 'customer.view',
    15 => 'customer.create',
    16 => 'customer.update',
    17 => 'customer.delete',

    // Affiliate capabilities.
    18 => 'affiliate.view',
    19 => 'affiliate.manage',
  ),
);
