<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Price;
use App\Models\User;

class SubscriptionPolicy
{
    public function own(User $user, Price $price): bool
    {
        return $user->prices()->where('project_id', $price->id)->exists();
    }

}
