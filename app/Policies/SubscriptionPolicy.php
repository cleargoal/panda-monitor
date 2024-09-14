<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Source;
use App\Models\User;

class SubscriptionPolicy
{
    public function own(User $user, Source $source): bool
    {
        return $user->source()->where('project_id', $source->id)->exists();
    }

}
