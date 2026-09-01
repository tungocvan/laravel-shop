<?php

namespace Modules\Attendance\Enums;

enum VerificationResult: string
{
    case Verified = 'verified';
    case AccuracyLow = 'accuracy_low';
    case OutsideArea = 'outside_area';
    case LocationUnavailable = 'location_unavailable';
}
