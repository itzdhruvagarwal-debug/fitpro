<?php

namespace App\Observers;

use App\Jobs\SendWelcomeNotification;
use App\Models\Member;
use App\Support\Data;

class MemberObserver
{
    public function created(Member $member): void
    {
        SendWelcomeNotification::dispatch(memberId: Data::int($member->getKey()))
            ->afterCommit();
    }
}
