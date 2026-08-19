<?php

return [
    'type' => 'support',
    'enabled' => env('MODULE_CLIENTPORTAL_ENABLED', true),
    'depends' => ['Auth'],
];
