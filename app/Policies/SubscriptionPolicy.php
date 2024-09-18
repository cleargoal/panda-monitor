<?php

declare(strict_types = 1);

namespace App\Policies;

use App\Models\Advert;
use App\Models\User;

class SubscriptionPolicy
{
    public function own(User $user, Advert $advert): bool
    {
        return $user->adverts()->where('advert_id', $advert->id)->exists();
    }

}
