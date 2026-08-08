<?php

namespace Modules\Administrative\Enums;

enum SubmissionAction: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case SupplementRequested = 'supplement_requested';
    case Resubmitted = 'resubmitted';
    case EmailSent = 'email_sent';
}
