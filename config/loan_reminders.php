<?php

return [
    // Remains disabled until Mail, Queue and scheduler have been verified in the target environment.
    'dispatch_enabled' => env('LOAN_REMINDERS_DISPATCH_ENABLED', false),
];
