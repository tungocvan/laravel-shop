<?php

namespace Modules\Request\Domain\Enums;

enum CandidateSource: string
{
    case FixedUser = 'fixed_user';
    case RoleMember = 'role_member';
    case FormUserField = 'form_user_field';
    case Reassignment = 'reassignment';
}
