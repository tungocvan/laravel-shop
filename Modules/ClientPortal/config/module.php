<?php

return [
    'name' => 'ClientPortal',
    'type' => 'support',
    'default_enabled' => env('MODULE_CLIENTPORTAL_ENABLED', true),
    'depends' => ['Auth'],
];
