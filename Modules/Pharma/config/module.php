<?php

return array (
  'name' => 'Pharma',
  'type' => 'domain',
  'enabled' => false,
  'depends' => 
  array (
    0 => 'Shared',
  ),
  'permissions' => 
  array (
    0 => 'view_pharma',
    1 => 'create_pharma',
    2 => 'edit_pharma',
    3 => 'delete_pharma',
  ),
  'tables' => 
  array (
    0 => 'pharma_medicines',
    1 => 'pharma_drug_bid_awards',
    2 => 'pharma_supplier_trackings',
  ),
);
